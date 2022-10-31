#!/opt/lampp/bin/php -q
<?php
/**
 * send_notifications.php
 *
 * @package   PhpStorm
 * @file      send_notifications.php
 * @author    gparkin
 * @date      11/15/16
 * @version   7.0
 *
 * @brief     Syntax: send_notifications.php [reminders, noemail]
 *
 * @brief     This is a cronjob that runs once a day at 6 in the morning. It's purpose is to look at new and changed
 *            records so it can create email for the users. The module is the only code that actually sends email for
 *            CCT 7.0
 *
 * @brief     Currently email is being redirected to Greg Parkin in the sendEmail() function. Be sure to set this
 *            back to the client before this code goes into production.
 *
 * @brief     If argv[1] or argv[2] == 'reminders' then this program will identify contacts that have not responded by
 *            the tickets email_respond[1-3] dates. When records are found it will create a log entry in the records where
 *            the script then sends out a real email to the contacts telling them they need to respond and approve.
 *
 * @brief     If argv[1] or argv[2] == 'noemail' then the program will create email files but will not send them out
 *            using the mail() function.
 *
 */

//
// Called once when a user signs into CCT.
//
ini_set('xdebug.collect_vars',    '5');
ini_set('xdebug.collect_vars',    'on');
ini_set('xdebug.collect_params',  '4');
ini_set('xdebug.dump_globals',    'on');
ini_set('xdebug.dump.SERVER',     'REQUEST_URI');
ini_set('xdebug.show_local_vars', 'on');

set_include_path("/opt/ibmtools/www/cct7/classes");

ini_set('memory_limit', '4048M');

//
// Class autoloader - Removes the need to add include and require statements
//
if (!function_exists('__autoload'))
{
	require_once('classes/autoloader.php');
}

$lib = new library();  // classes/library.php
$lib->debug_start('send_notifications.html');
date_default_timezone_set('America/Denver');

//$lib->html_dump();
$ora1 = new oracle();
$ora2 = new oracle();

parse_str(implode('&', array_slice($argv, 1)), $_GET);
//print_r($argv);

$error_message = '';

//
// Set the timezone to GMT and get the current time.
// Substract the 30 hours from the date and return UTIME.
//
$date = new DateTime();
$date->setTimezone(new DateTimeZone('GMT'));
$date->setTimestamp(time());

$date->sub(new DateInterval('PT30H'));
$thirty_hours_ago = $date->format('U');

$date->sub(new DateInterval('PT48H'));
$forty_eight_hours_ago = $date->format('U');

$count_emails = 0;
$fp = null;

if ((isset($argv[1]) && $argv[1] == 'noemail') || (isset($argv[2]) && $argv[2] == 'noemail'))
{
	$disable_email = 'YES';
}

if ((isset($argv[1]) && $argv[1] == 'reminders') || (isset($argv[2]) && $argv[2] == 'reminders'))
{
	if (logReminders() == false)
	{
		//
		// Send me a email message about the problem.
		//
		$headers  = sprintf("From: CCT <no-reply.us.ibm.com>\r\n");
		$headers .= "MIME-Version: 1.0\r\n";
		$headers .= "Content-Type: text/html; charset=ISO-8859-1\r\n";

		$to       = 'gregparkin58@gmail.com';

		$subject  = 'Change Coordination Tool - ERROR: send_notifications.php';
		$message  = "<html><body>";
		$message .= $error_message;
		$message .= "</body></html>";

		//
		// PHP mail() returns true if email is seccessfully sent, otherwise false.
		//
		mail($to, $subject, $message, $headers);
	}
}

/**
 * @fn     logReminders()
 *
 * @brief  Scan records to see if contact reminders need to be sent out.
 *
 * @return bool
 */
function logReminders()
{
	global $lib, $ora1, $error_message;

	$date1 = new DateTime("now");

	$con = new cct7_contacts();

	$date = date('m/d/Y'); // Get current date in format: MM/DD/YYYY

	$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "Sending reminders for %s", $date);

	$today_start = $date . " 00:00";
	$today_end   = $date . " 23:59";

	$today_start = $lib->to_gmt($today_start, "GMT");
	$today_end   = $lib->to_gmt($today_end,   "GMT");

	$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "-- Start GMT UTIME: %d", $today_start);
	$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "--   End GMT UTIME: %d", $today_end);

	$query  = "select ";
	$query .= "  t.ticket_no         as ticket_no, ";
	$query .= "  s.system_id         as system_id, ";
	$query .= "  s.system_hostname   as hostname, ";
	$query .= "  c.contact_netpin_no as netpin_no ";
	$query .= "from ";
	$query .= "  cct7_tickets t, ";
	$query .= "  cct7_systems s, ";
	$query .= "  cct7_contacts c ";
	$query .= "where ";
	$query .= "  t.status = 'ACTIVE' and ";
	$query .= "  ((t.email_reminder1_date >= " . $today_start . " and t.email_reminder1_date <= " . $today_end . ") or ";
	$query .= "   (t.email_reminder2_date >= " . $today_start . " and t.email_reminder2_date <= " . $today_end . ") or ";
	$query .= "   (t.email_reminder3_date >= " . $today_start . " and t.email_reminder3_date <= " . $today_end . ")) and ";
	$query .= "  s.ticket_no = t.ticket_no and ";
	$query .= "  s.system_work_status = 'WAITING' and ";
	$query .= "  c.system_id = s.system_id and ";
	$query .= "  c.contact_response_status = 'WAITING'";

	$lib->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $query);

	if ($ora1->sql2($query) == false)
	{
		$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $ora1->dbErrMsg);
		$error_message = $ora1->dbErrMsg;
		return false;
	}

	$count_reminders = 0;
	$event_message = "*** This is a reminder to please login to CCT and approve the work requests. Thank you! ***";

	while ($ora1->fetch())
	{
	    $count_reminders += 1;

		if ($con->putLogContacts(
			$ora1->ticket_no,
			(int)$ora1->system_id,
			$ora1->hostname,
			$ora1->netpin_no,
			"REMINDER",
			$event_message) == false)
		{
			$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $con->error);
			$error_message = $con->error;
			return false;
		}

		$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "Reminder logged: %s %s %s",
			$ora1->ticket_no, $ora1->hostname, $ora1->netpin_no);
	}

	$date2 = new DateTime("now");
	$interval = $date1->diff($date2);
	printf("\nTotal reminders posted: %d\n", $count_reminders);
	printf("Reminders runtime: %s\n\n", $interval->format('%H:%I:%S'));

	return true;
}

/**
 * @fn    sendEmail($from_cuid, $from_name, $from_email, $body)
 *
 * @brief Call PHP mail() function to send email out to contact. Then log the event in cct7_sendmail_log
 *
 * @param $from_cuid
 * @param $from_name
 * @param $from_email
 * @param $body
 */
function sendEmail($to_cuid, $to_name, $to_email, $body)
{
	global $lib, $ora2, $disable_email, $count_emails;

	//
    // Let a cronjob look at the files in the email/run directory and then have it run
    // sendmail individually using a shell script. Then move the files back one directory.
    //
    return true;

	$count_emails += 1;

	// return true;

	printf("%d - Sending email to: %s <%s>\n", $count_emails, $to_name, $to_email);


    $message  = sprintf("From: CCT <no-reply.us.ibm.com>\r\n");
    $message .= sprintf("To: %s <%s>\r\n", $to_name, $to_email);
    $message .= sprintf("Content-Type: text/html; charset=ISO-8859-1\r\n");
    $message .= sprintf("Subject: CCT 7 - Change Coordination Tool - Automated Email Notification");
    $message .= $body;

    $cmd = "echo "
    $output = $output = shell_exec('ls -lart');


	//$headers  = sprintf("From: CCT <no-reply.us.ibm.com>\r\n");
	//$headers .= "MIME-Version: 1.0\r\n";
	//$headers .= "Content-Type: text/html; charset=ISO-8859-1\r\n";

	//$to       = $to_email;

	//$subject  = 'Change Coordination Tool - Email Notification';
	//$message  = "<html><body>";
	//$message .= $body;
	//$message .= "</body></html>";

	$success = 'N';

    //mail($to, $subject, $message, $headers);
    //$success = 'Y';

	//
	// Record email event in cct7_sendmail_log
	//
	$rc = $ora2
		->insert("cct7_sendmail_log")
		->column("sendmail_date")
		->column("sendmail_cuid")
		->column('sendmail_name')
		->column('sendmail_email')
		->column('sendmail_success')
		->value("int",  $lib->now_to_gmt_utime())
		->value("char", $to_cuid)
		->value("char", $to_name)
		->value("char", $to_email)
		->value("char", $success)
		->execute();

	if ($rc == false)
	{
		$lib->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $ora2->sql_statement);
		$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $ora2->dbErrMsg);
	}

	$ora2->commit();

	return true;
}

function startsWith($haystack, $needle)
{
	return strncmp($haystack, $needle, strlen($needle)) == 0;
}

function closeEmailFile()
{
    global $fp;

    if (isset($fp) && $fp)
        fclose($fp);
}

function openEmailFile($cuid)
{
    global $fp;

	if (isset($fp) && $fp)
		fclose($fp);

	$filename = sprintf("/opt/ibmtools/cct7/email/new/%s.html", $cuid);

	if (file_exists($filename))
	{
		$fp = fopen($filename, "a");

		if (!$fp)
			die(sprintf("Cannot open file for append: %s", $filename));

		fprintf($fp, "<hr>\n");
	}
	else
    {
		$fp = fopen($filename, "w");

		if (!$fp)
			die(sprintf("Cannot open file for write: %s", $filename));

		$message_body = '<html><body>';

		/**
		 * Greg,
		 *
		 * The following is a detailed notification outlining the status of pending work requiring your approval
		 * or attention. Please read through this email carefully!
		 *
		 * Logon to CCT to and click on the Approval button to approve work for the servers you support.
		 *
		 *       https://cct.corp.intranet
		 *
		 * If there are servers that are parent hosts for virtual servers you support then you will be asked to
		 * approve the work for the parent servers when there is a reboot that will effect your virtual server.
		 *
		 * Please Note: All dates and times in this email are in Mountain Time. When you logon to CCT all dates
		 * and times are shown in your local time zone. This means that the dates and times in this email may
		 * not match what you see in the CCT application.
		 *
		 */
		fprintf($fp, "<p><b><u>CCT - Change Coordination Tool - Email Notification</u></b></p>\n");

		fprintf($fp, "<p>Login to CCT to get more information about the work. Always check the Approve queue for work needing your\n");
		fprintf($fp, "authorization. Use the Search feature to find things. Look at the Help and Index if you don't know how to\n");
		fprintf($fp, "do something.</p>\n");

		fprintf($fp, "<p><u>You are responsible</u> to keep your group's contact information updated and correct in CSC,\n");
		fprintf($fp, "NET and CCT. Use the \"Server Contacts\" option under the Reports menu to help you identify where you\n");
		fprintf($fp, "need to go to update your contact information.</p>\n");

		fprintf($fp, "<p>Notifications go out to all members of your NET group or CCT subscriber list team. This means\n");
		fprintf($fp, "that someone from your group may have already approved the work. Your Approve queue may be empty!</p>\n");

		fprintf($fp, "<p>If you see <font color='green'><b>green text</b></font> next to the server below in parentheses,\n");
		fprintf($fp, "this information will contain your groups current status response, the persons name,\n");
		fprintf($fp, "and the date of they responded.</p>\n");

		fprintf($fp, "<p>Some of these work requests are for FYI notification only, which means you are not required \n");
		fprintf($fp, "to approve the work, but your group wants to be informed of any pending work.\n");
		fprintf($fp, "<i>(Please note the \"Response Required\" field in the ticket below.)</i></p>\n");

		fprintf($fp, "<p>As status information changes for work, you will get updates. This doesn't mean you have to do\n");
		fprintf($fp, "anything. New <u><b>status messages</b></u> in the past 30 hours under the server names below are \n");
		fprintf($fp, "<font color=blue><b>colored in blue</b></font> and are meant to keep you informed of changes.\n");
		fprintf($fp, "Email notifications will only show messages from the last 48 hours. To see all the messages,\n");
		fprintf($fp, "login to CCT and bring up the record. Use the search tool.</p>\n");

		fprintf($fp, "<p>CCT URL: <a href='https://cct.corp.intranet'><b>https://cct.corp.intranet</b></a></p>\n");

		fprintf($fp, "<p><u>If you discover an error or problem</u>, please send screenshots and detail information about it\n");
		fprintf($fp, "to Greg Parkin (gregparkin58@gmail.com). In most cases he needs to try and recreate the error\n");
		fprintf($fp, "in order to fix the problem. You can also create a Remedy ticket to AIM-TOOLS-BOM and assign it to Greg.</p>\n");

		fprintf($fp, "<p>CCT 7 is a new major release and is <b>best viewed</b> using Firefox or Chrome. There are always\n");
		fprintf($fp, "new feature requests and enhancements to make it better. Please be patient, your input is always\n");
		fprintf($fp, "welcome.</p>\n");
    }
}

//
// Cleanup the Subscriber lists by removing any invalid groups owners and member cuids that are no
// longer found in cct7_mnet.
//
// *** WARNING *** If cct7_mnet is empty the entire subscriber list will be removed!
//
// Get the record count of cct7_mnet to make sure it is not empty!
//
$ora1->sql2("select count(*) as total_records from cct7_mnet");
$ora1->fetch();
$total_records = $ora1->total_records;

//
// Make sure we have at least 90,000 records in the cct7_mnet table before doing anything!
//
if ($total_records > 90000)  // Average record count in cct7_mnet: 95499
{
	printf("\nRemoving obsolete subscriber groups owners and subscriber members not found in cct7_mnet\n");

	if ($ora1->sql2("select * from cct7_subscriber_groups order by group_name") == false)
	{
		printf("%s - %s\n", $ora1->sql_statement, $ora1->dbErrMsg);
		exit();
	}

	while ($ora1->fetch())
	{
		$query = sprintf(
			"select * from cct7_mnet where mnet_cuid = '%s'", $ora1->owner_cuid);

		if ($ora2->sql2($query) == false)
		{
			printf("%s - %s\n", $ora1->sql_statement, $ora1->dbErrMsg);
			exit();
		}

		if ($ora2->fetch())
		{
			printf("Subscriber Group has been validated: %s\n", $ora1->group_name);
		}
		else
		{
			printf("Deleting Subscriber Group owned by %s - %s\n", $ora1->owner_cuid, $ora1->group_name);
			$query = sprintf("delete cct7_subscriber_groups where owner_cuid = '%s'", $ora1->owner_cuid);

			if ($ora2->sql2($query) == false)
			{
				printf("%s - %s\n", $ora1->sql_statement, $ora1->dbErrMsg);
				exit();
			}
		}
	}

	if ($ora2->sql2("delete from cct7_subscriber_groups where owner_cuid is null") == false)
	{
		printf("%s - %s\n", $ora2->sql_statement, $ora2->dbErrMsg);
		exit();
	}

	$ora2->commit();

	//
    // Delete obsolete subscriber members
    //
	if ($ora2->sql2("select * from cct7_subscriber_members order by member_cuid") == false)
	{
		printf("%s - %s\n", $ora2->sql_statement, $ora2->dbErrMsg);
		exit();
	}

	$member_list = array();

	while ($ora1->fetch())
	{
		$query = sprintf(
			"select * from cct7_mnet where mnet_cuid = '%s'", $ora1->member_cuid);

		if ($ora2->sql2($query) == false)
		{
			printf("%s - %s\n", $ora2->sql_statement, $ora2->dbErrMsg);
			exit();
		}

		if ($ora2->fetch())
		{
			printf("Subscriber member has been validated: %s\n", $ora1->member_cuid);
		}
		else
		{
			printf("Deleting subscriber member: %s\n", $ora1->member_cuid);
			$member_list[$ora1->member_cuid] = $ora1->member_name;
		}
	}

	if ($ora2->sql2("delete from cct7_subscriber_members where member_cuid is null") == false)
	{
		printf("%s - %s\n", $ora2->sql_statement, $ora2->dbErrMsg);
		exit();
	}

	$ora2->commit();
}



// Record when we started processing notifications.
$date1 = new DateTime("now");
// -------------------------------------------------------------------------------------------------
// New Code

$query  = "select distinct ";
$query .= "  t.cm_ticket_no, ";
$query .= "  t.work_activity, ";
$query .= "  t.status, ";
$query .= "  t.owner_name, ";
$query .= "  t.owner_email, ";
$query .= "  t.approvals_required, ";
$query .= "  t.reboot_required, ";
$query .= "  t.respond_by_date, ";
$query .= "  c.contact_netpin_no, ";
$query .= "  c.contact_response_status, ";
$query .= "  c.contact_response_date, ";
$query .= "  c.contact_response_name, ";
$query .= "  s.system_id, ";
$query .= "  s.ticket_no, ";
$query .= "  s.system_hostname, ";
$query .= "  s.system_work_status, ";
$query .= "  s.system_work_start_date, ";
$query .= "  s.system_work_end_date, ";
$query .= "  s.system_work_duration, ";
$query .= "  s.system_timezone_name ";
$query .= "from ";
$query .= "  cct7_tickets t, ";
$query .= "  cct7_systems s, ";
$query .= "  cct7_contacts c ";
$query .= "where ";
$query .= "  t.status = 'ACTIVE' and ";
$query .= "  t.ticket_no = s.ticket_no and ";
$query .= "  c.system_id = s.system_id and ";
$query .= "  s.system_update_date >= 1499839200 and c.contact_update_date >= 1499839200 and ";
$query .= "  (s.system_update_date != s.change_date or c.contact_update_date != c.change_date) ";
$query .= "order by ";
$query .= "  c.contact_netpin_no";

if ($ora1->sql2($query) == false)
{
	$lib->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $ora1->sql_statement);
	$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $ora1->dbErrMsg);
	exit();
}

$contact_list           = array();
$last_contact_netpin_no = "";
$top                    = null;
$p                      = null;

while ($ora1->fetch())
{
    $cm_ticket_no            = $ora1->cm_ticket_no;
	$work_activity           = $ora1->work_activity;
	$status                  = $ora1->status;
	$owner_name              = $ora1->owner_name;
	$owner_email             = $ora1->owner_email;
	$approvals_required      = $ora1->approvals_required;
	$reboot_required         = $ora1->reboot_required;
	$respond_by_date         = $ora1->respond_by_date;
	$contact_netpin_no       = $ora1->contact_netpin_no;
	$contact_response_status = $ora1->contact_response_status;
	$contact_response_date   = $ora1->contact_response_date;
	$contact_response_name   = $ora1->contact_response_name;
	$system_id               = $ora1->system_id;
	$ticket_no               = $ora1->ticket_no;
	$system_hostname         = $ora1->system_hostname;
	$system_work_status      = $ora1->system_work_status;
	$system_work_duration    = $ora1->system_work_duration;
	$system_timezone_name    = $ora1->system_timezone_name;

    $tz = $system_timezone_name;

    if ($ora1->system_work_start_date == 0)
    {
        $system_work_start_date = "(See Remedy)";
    }
    else
    {
		$system_work_start_date   =
			$lib->gmt_to_format(
				$ora1->system_work_start_date,
				'm/d/Y H:i T',
				$tz) . " " . $tz;
	}

    if ($ora1->system_work_end_date == 0)
    {
        $system_work_end_date = "(See Remedy)";
        $system_work_duration = "(See Remedy)";
    }
    else
    {
		$system_work_end_date   =
			$lib->gmt_to_format(
				$ora1->system_work_end_date,
				'm/d/Y H:i T',
				$tz) . " " . $tz;
    }

	$contact_response_date   =
		$lib->gmt_to_format(
			$ora1->contact_response_date,
			'm/d/Y H:i',
			'America/Denver') . " " . 'America/Denver';

	$respond_by_date   =
		$lib->gmt_to_format(
			$ora1->respond_by_date,
			'm/d/Y',
			'America/Denver') . " " . 'America/Denver';

    if ($last_contact_netpin_no == '')
    {
        $last_contact_netpin_no = $contact_netpin_no;

         $top = new data_node();
         $p   = $top;
    }
    else if ($last_contact_netpin_no != $contact_netpin_no)
    {
		$contact_list[$last_contact_netpin_no] = $top;

		$last_contact_netpin_no = $contact_netpin_no;

		$top = new data_node();
		$p   = $top;
    }
    else
    {
        $p->next = new data_node();
        $p = $p->next;
    }

    $p->cm_ticket_no            = $cm_ticket_no;
	$p->work_activity           = $work_activity;
	$p->status                  = $status;
	$p->owner_name              = $owner_name;
	$p->owner_email             = $owner_email;
	$p->approvals_required      = $approvals_required;
	$p->reboot_required         = $reboot_required;
	$p->respond_by_date         = $respond_by_date;
	$p->contact_netpin_no       = $contact_netpin_no;
	$p->contact_response_status = $contact_response_status;
	$p->contact_response_date   = $contact_response_date;
	$p->contact_response_name   = $contact_response_name;
	$p->system_id               = $system_id;
	$p->ticket_no               = $ticket_no;
	$p->system_hostname         = $system_hostname;
	$p->system_work_status      = $system_work_status;
	$p->system_work_start_date  = $system_work_start_date;
	$p->system_work_end_date    = $system_work_end_date;
	$p->system_work_duration    = $system_work_duration;
	$p->system_timezone_name    = $system_timezone_name;
}

if (strlen($last_contact_netpin_no) > 0)
{
	$contact_list[$last_contact_netpin_no] = $top;
}

//
// Retrieve the NET and Subscriber group members
//
$member_contacts = array();  // $member_contacts['<contact_netpin_no>'] = link-list object

$top = null;
$p   = null;

foreach ($contact_list as $contact_netpin_no => $node)
{
    if (array_key_exists($contact_netpin_no, $member_contacts))
        continue;

    if (startsWith($contact_netpin_no, "SUB"))
    {
		$query  = "select distinct  ";
		$query .= "  m.mnet_cuid, ";
		$query .= "  m.mnet_name, ";
		$query .= "  m.mnet_email ";
		$query .= "from ";
		$query .= "  cct7_contacts c, ";
		$query .= "  cct7_subscriber_members n, ";
		$query .= "  cct7_mnet m ";
		$query .= "where  ";
		$query .= "  c.contact_netpin_no = '" . $contact_netpin_no . "' and ";
		$query .= "  n.group_id = c.contact_netpin_no and ";
		$query .= "  m.mnet_cuid = n.member_cuid and ";
		$query .= "  m.mnet_email is not null ";
		$query .= "order by ";
		$query .= "  m.mnet_cuid";
    }
    else
    {
		$query  = "select distinct ";
		$query .= "  m.mnet_cuid, ";
		$query .= "  m.mnet_name, ";
		$query .= "  m.mnet_email ";
		$query .= "from ";
		$query .= "  cct7_contacts c, ";
		$query .= "  cct7_netpin_to_cuid n, ";
		$query .= "  cct7_mnet m ";
		$query .= "where ";
		$query .= "  c.contact_netpin_no = '" . $contact_netpin_no . "' and ";
		$query .= "  n.net_pin_no = c.contact_netpin_no and ";
		$query .= "  m.mnet_cuid = n.user_cuid and ";
		$query .= "  m.mnet_email is not null ";
		$query .= "order by ";
		$query .= "  m.mnet_cuid";
    }

	if ($ora1->sql2($query) == false)
	{
		$lib->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $ora1->sql_statement);
		$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $ora1->dbErrMsg);
		exit();
	}

	$top = null;
    $p   = null;

	while ($ora1->fetch())
    {
        if ($top == null)
        {
            $top = new data_node();
            $p   = $top;
        }
        else
        {
            $p->next = new data_node();
            $p = $p->next;
        }

		$p->mnet_cuid  = $ora1->mnet_cuid;
		$p->mnet_name  = $ora1->mnet_name;
		$p->mnet_email = $ora1->mnet_email;
    }

	$member_contacts[$contact_netpin_no] = $top;
}

//
// You now have 2 hash arrays containing link lists (class data_node())
//
//   $contact_list[$contact_netpin_no] = <link list>
//     ->contact_netpin_no
//     ->system_id
//     ->ticket_no
//     ->system_hostname
//     ->system_work_status
//     ->system_work_start_date
//     ->system_work_end_date
//     ->system_work_duration
//     ->system_timezone_name
//
//   $member_contacts[$contact_netpin_no] = <link list>
//     ->mnet_cuid
//     ->mnet_name
//     ->mnet_email

//
// Clean up the email output directory
//
$cmd = sprintf("rm -rf /opt/ibmtools/cct7/email/new; mkdir -p /opt/ibmtools/cct7/email/new");
printf("\nCleaning up: %s\n%s\n\n", $cmd, shell_exec($cmd));

foreach ($member_contacts as $contact_netpin_no => $mnet)
{
    for ($con=$mnet; $con!=null; $con=$con->next)
    {
        $mnet_cuid = $con->mnet_cuid;
        $mnet_name = $con->mnet_name;
        $mnet_email = $con->mnet_email;

        openEmailFile($mnet_cuid);  // Opens it for write or append. h(string); can now be used to write to email file.

        if (array_key_exists($contact_netpin_no, $contact_list))
        {
            $top = $contact_list[$contact_netpin_no];

            for ($p=$top; $p!=null; $p=$p->next)
            {
                if ($p != $top)
                    fprintf($fp, "</p>\n");

				fprintf($fp, "<p>Remedy Ticket: %s<br>\n", $p->cm_ticket_no);
                fprintf($fp, "CCT Ticket: %s<br>\n", $p->ticket_no);
                fprintf($fp, "Ticket Status: %s<br>\n", $p->status);
                fprintf($fp, "Work Activity: %s<br>\n", $p->work_activity);
                fprintf($fp, "Approvals Required: %s<br>\n", $p->approvals_required);
                fprintf($fp, "Reboot Required: %s<br>\n", $p->reboot_required);
                fprintf($fp, "Respond By: %s<br>\n", $p->respond_by_date);
				// fprintf($fp, "Hostname: %s\n", $p->system_hostname);
				fprintf($fp, "%s Work Status: %s<br>\n", $p->system_hostname, $p->system_work_status);
				fprintf($fp, "%s Work Start: %s<br>\n", $p->system_hostname, $p->system_work_start_date);
				fprintf($fp, "%s Work End: %s<br>\n", $p->system_hostname, $p->system_work_end_date);
				fprintf($fp, "%s Work Duration: %s<br>\n", $p->system_hostname, $p->system_work_duration);
				// fprintf($fp, "Timezone Name: %s\n", $p->system_timezone_name);
				fprintf($fp, "%s %s Contact Netpin: %s<br>\n", $p->system_hostname, $p->system_hostname, $p->contact_netpin_no);
				fprintf($fp, "%s %s Contact Response Status: %s<br>\n", $p->system_hostname, $p->contact_netpin_no, $p->contact_response_status);
				fprintf($fp, "%s %s Contact Response Date: %s<br>\n", $p->system_hostname, $p->contact_netpin_no, $p->contact_response_date);
				fprintf($fp, "%s %s Contact Response Name: %s<br>\n", $p->system_hostname, $p->contact_netpin_no, $p->contact_response_name);

				//
                // Go get the event messages
                //
				//
				// Write any Ticket log information from cct7_log_tickets from the last 30 hours.
				//
				// select distinct event_type from cct7_log_tickets;  -- Ignore: ACTIVATED
				// (NOTE, CANCELED, ACTIVATED)
				//
				$query  = "select ";
				$query .= "  ticket_no, ";
				$query .= "  event_date, ";
				$query .= "  event_cuid, ";
				$query .= "  event_name, ";
				$query .= "  event_type, ";
				$query .= "  event_message ";
				$query .= "from ";
				$query .= "  cct7_log_tickets ";
				$query .= "where ";
				$query .= "  ticket_no = '" . $p->ticket_no . "' and ";
				$query .= "  event_date >= " . $forty_eight_hours_ago . " and ";
				$query .= "  event_type != 'ACTIVATED' ";
				$query .= "order by ";
				$query .= "  event_date desc";

				if ($ora2->sql2($query) == false)
				{
					$lib->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $ora2->sql_statement);
					$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $ora2->dbErrMsg);
					exit();
				}

				$count = 0;

				while ($ora2->fetch())
				{
					$event_date    = isset($ora2->event_date)    ? $ora2->event_date    : "";
					$event_name    = isset($ora2->event_name)    ? $ora2->event_name    : "";
					$event_type    = isset($ora2->event_type)    ? $ora2->event_type    : "";
					$event_message = isset($ora2->event_message) ? $ora2->event_message : "";

					$event_date    = $lib->gmt_to_format(
					        $event_date, 'm/d/Y H:i', 'America/Denver') . " America/Denver";;

					if ($count == 0)
					{
						//fprintf($fp, "<pre style='margin-left: 10px; margin-top: 2px; margin-bottom: 2px; line-height: 8px;'>");
                        fprintf($fp, "<pre>\n");
						$count += 1;
					}

					if ($ora2->event_date > $thirty_hours_ago)
					{
						fprintf($fp, "<font color=blue><b>%s (Mountain) - %s - %s: %s</b></font>\n",
										$event_date, $event_name, $event_type, $event_message);
					}
					else
					{
						fprintf($fp, "%s (Mountain) - %s - %s: %s\n\n",
										$event_date, $event_name, $event_type, $event_message);
					}
				}

				if ($count > 0)
					fprintf($fp, "</pre>\n");

				//
				// Get the System log information from cct7_log_systems
				//
				// select distinct event_type from cct7_log_systems;  -- Ignore: EMAIL
				// (EMAIL, RESET, STARTING, SUCCESS, CANCELED, RESCHEDULE, FAILED, NOTE)
				//
				$query  = "select ";
				$query .= "  ticket_no, ";
				$query .= "  system_id, ";
				$query .= "  hostname, ";
				$query .= "  event_date, ";
				$query .= "  event_cuid, ";
				$query .= "  event_name, ";
				$query .= "  event_type, ";
				$query .= "  event_message ";
				$query .= "from ";
				$query .= "  cct7_log_systems ";
				$query .= "where ";
				$query .= "  system_id = " . $p->system_id . " and ";
				$query .= "  event_date >= " . $forty_eight_hours_ago . " and ";
				$query .= "  event_type != 'EMAIL' ";
				$query .= "order by ";
				$query .= "  event_date desc";

				if ($ora2->sql2($query) == false)
				{
					$lib->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $ora2->sql_statement);
					$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $ora2->dbErrMsg);
					exit();
				}

				while ($ora2->fetch())
				{
					$event_date    = isset($ora2->event_date)    ? $ora2->event_date    : "";
					$event_name    = isset($ora2->event_name)    ? $ora2->event_name    : "";
					$event_type    = isset($ora2->event_type)    ? $ora2->event_type    : "";
					$event_message = isset($ora2->event_message) ? $ora2->event_message : "";

					$event_date    = $lib->gmt_to_format(
							$event_date, 'm/d/Y H:i', 'America/Denver') . " America/Denver";

					if ($count == 0)
					{
						//fprintf($fp, "<pre style='margin-left: 10px; margin-top: 2px; margin-bottom: 2px; line-height: 8px;'>");
						fprintf($fp, "<pre>\n");
						$count += 1;
					}

					if ($ora2->event_date > $thirty_hours_ago)
					{
						fprintf($fp, "<font color=blue><b>%s (Mountain) - %s - %s: %s</b></font>\n",
								$event_date, $event_name, $event_type, $event_message);
					}
					else
					{
						fprintf($fp, "%s (Mountain) - %s - %s: %s\n\n",
								$event_date, $event_name, $event_type, $event_message);
					}
				}

				//
				// Get the Contact log information from cct7_log_contacts
				//
				// select distinct event_type from cct7_log_contacts; -- Ignore: PAGE
				// (NOTE, EXEMPTED, REMINDER, APPROVED, PAGE)
				//
				$query  = "select ";
				$query .= "  ticket_no, ";
				$query .= "  system_id, ";
				$query .= "  hostname, ";
				$query .= "  netpin_no, ";
				$query .= "  event_date, ";
				$query .= "  event_cuid, ";
				$query .= "  event_name, ";
				$query .= "  event_type, ";
				$query .= "  event_message ";
				$query .= "from ";
				$query .= "  cct7_log_contacts ";
				$query .= "where ";
				$query .= "  system_id = " . $p->system_id . " and ";
				$query .= "  event_date >= " . $forty_eight_hours_ago . " and ";
				$query .= "  event_type != 'PAGE' ";
				$query .= "order by ";
				$query .= "  event_date desc";

				if ($ora2->sql2($query) == false)
				{
					$lib->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $ora2->sql_statement);
					$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $ora2->dbErrMsg);
					exit();
				}

				while ($ora2->fetch())
				{
					$event_date    = isset($ora2->event_date)    ? $ora2->event_date    : "";
					$event_name    = isset($ora2->event_name)    ? $ora2->event_name    : "";
					$event_type    = isset($ora2->event_type)    ? $ora2->event_type    : "";
					$event_message = isset($ora2->event_message) ? $ora2->event_message : "";

					$event_date    = $lib->gmt_to_format(
							$event_date, 'm/d/Y H:i', 'America/Denver') . " America/Denver";

					if ($count == 0)
					{
						//fprintf($fp, "<pre style='margin-left: 10px; margin-top: 2px; margin-bottom: 2px; line-height: 8px;'>");
						fprintf($fp, "<pre>\n");
						$count += 1;
					}

					if ($ora2->event_date > $thirty_hours_ago)
					{
						fprintf($fp, "<font color=blue><b>%s (Mountain) - %s - %s: %s</b></font>\n",
								$event_date, $event_name, $event_type, $event_message);
					}
					else
					{
						fprintf($fp, "%s (Mountain) - %s - %s: %s\n\n",
								$event_date, $event_name, $event_type, $event_message);
					}
				}
            }

			if ($count > 0)
				fprintf($fp, "</pre>\n");
        }
    }
}

fclose($fp);

foreach ($member_contacts as $contact_netpin_no => $top)
{
    for ($p=$top; $p!=null; $p=$p->next)
	{
		$to_cuid  = $p->mnet_cuid;
		$to_name  = $p->mnet_name;
		$to_email = $p->mnet_email;

        $cmd = sprintf("/opt/ibmtools/cct7/email/new/%s.html", $mnet_cuid);

		$body = file_get_contents($cmd);

		sendEmail($to_cuid, $to_name, $to_email, $body);

		$cmd = sprintf("cp /opt/ibmtools/cct7/email/new/%s.html /opt/ibmtools/cct7/email/%s.html",
            $to_cuid, $to_cuid);

		printf("%s\n%s\n\n", $cmd, shell_exec($cmd));
	}
}

// Record when we ended processing notifications.
$date2 = new DateTime("now");
$interval = $date1->diff($date2);
printf("\nSend Notifications Processing runtime: %s\n", $interval->format('%H:%I:%S'));

//
// Set the change_date to match update_date in cct7_tickets, cct7_systems, and cct7_contacts.
//
$date1 = new DateTime("now");
printf("\nSetting change_date to match update_date in cct7_tickets\n");
$ora1->sql2('update cct7_tickets set change_date = update_date where change_date != update_date');
$date2 = new DateTime("now");
$interval = $date1->diff($date2);
printf("Processing runtime: %s\n", $interval->format('%H:%I:%S'));

$date1 = new DateTime("now");
printf("\nSetting change_date to match update_date in cct7_systems\n");
$ora1->sql2('update cct7_systems set change_date = system_update_date where change_date != system_update_date');
$date2 = new DateTime("now");
$interval = $date1->diff($date2);
printf("Processing runtime: %s\n", $interval->format('%H:%I:%S'));

$date1 = new DateTime("now");
printf("\nSetting change_date to match update_date in cct7_contacts\n");
$ora1->sql2('update cct7_contacts set change_date = contact_update_date where change_date != contact_update_date');
$date2 = new DateTime("now");
$interval = $date1->diff($date2);
printf("Processing runtime: %s\n", $interval->format('%H:%I:%S'));

printf("\nAll done! Good-bye!\n\n");
$ora1->commit();
exit();

