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
 *            he tickets email_respond[1-3] dates. When records are find it will create a log entry in the records where
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
//$lib->html_dump();
$ora1 = new oracle();
$ora2 = new oracle();

parse_str(implode('&', array_slice($argv, 1)), $_GET);
//print_r($argv);

$disable_email = 'NO';
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

$count_emails = 0;

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

	$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "-- Start GMT UTIME: %d", $lib->to_gmt($today_start, "GMT"));
	$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "--   End GMT UTIME: %d", $lib->to_gmt($today_end,   "GMT"));

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
	$event_message = "*** This is a reminder to please login to CCT and approve this work request. Thank you! ***";

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
	printf("\nTotal reminders posted: %d", $count_reminders);
	printf("Reminders runtime: %s\n", $interval->format('%H:%I:%S'));

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

	$count_emails += 1;

	printf("%d - Sending email to: %s <%s>\n", $count_emails, $to_name, $to_email);

	$headers  = sprintf("From: CCT <no-reply.us.ibm.com>\r\n");
	$headers .= "MIME-Version: 1.0\r\n";
	$headers .= "Content-Type: text/html; charset=ISO-8859-1\r\n";

	$to       = $to_email;

	$subject  = 'Change Coordination Tool - Email Notification';
	$message  = "<html><body>";
	$message .= $body;
	$message .= "</body></html>";

	$success = 'N';

	if ($disable_email == 'NO')
	{
		//mail($to, $subject, $message, $headers);
        $success = 'Y';
	}

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
}

/**
 * @fn    addbody($str)
 *
 * @brief Append string to $message_body and add newline char to the end. (Used for formatting.)
 *
 * @param string $str
 */
function addbody($str)
{
    global $message_body;

	$message_body .= $str;
	$message_body .= "\n";
}

// Record when we started processing notifications.
$date1 = new DateTime("now");

//
// Get the information that has changed and a list of contacts that need to be notified.
//
$query  = "select distinct ";
$query .= "  c.ticket_no                    as tic_ticket_no,  ";
$query .= "  c.cm_ticket_no                 as tic_cm_ticket_no, ";
$query .= "  c.remedy_cm_start_date         as tic_cm_start_date, ";
$query .= "  c.remedy_cm_end_date           as tic_cm_end_date, ";
$query .= "  c.status                       as tic_status, ";
$query .= "  c.work_activity                as tic_work_activity, ";
$query .= "  c.system_id                    as sys_system_id,  ";
$query .= "  c.system_hostname              as sys_hostname, ";
$query .= "  c.system_work_status           as sys_work_status, ";
$query .= "  c.contact_netpin_no            as con_netpin_no,  ";
$query .= "  c.contact_response_status      as con_response_status, ";
$query .= "  m.mnet_cuid                    as con_cuid,  ";
$query .= "  m.mnet_name                    as con_name,  ";
$query .= "  m.mnet_first_name              as con_first_name,  ";
$query .= "  m.mnet_email                   as con_email, ";
$query .= "  c.contact_connection           as con_connection, ";
$query .= "  c.contact_server_os            as con_connection_os, ";
$query .= "  c.contact_server_usage         as con_connection_usage, ";
$query .= "  c.contact_work_group           as con_work_group, ";
$query .= "  c.contact_approver_fyi         as con_approver_fyi, ";
$query .= "  c.contact_response_date        as con_response_date, ";
$query .= "  c.contact_response_cuid        as con_response_cuid, ";
$query .= "  c.contact_response_name        as con_response_name, ";
$query .= "  c.contact_respond_by_date      as con_respond_by_date, ";
$query .= "  c.contact_send_page            as con_send_page, ";
$query .= "  c.contact_send_email           as con_send_email, ";
$query .= "  c.system_os                    as sys_os, ";
$query .= "  c.system_usage                 as sys_usage, ";
$query .= "  c.system_location              as sys_location, ";
$query .= "  c.system_timezone_name         as sys_timezone, ";
$query .= "  c.system_osmaint_weekly        as sys_osmaint, ";
$query .= "  c.system_work_start_date       as sys_work_start_date, ";
$query .= "  c.system_work_end_date         as sys_work_end_date, ";
$query .= "  c.system_work_duration         as sys_work_duration, ";
$query .= "  c.total_contacts_responded     as sys_total_responded, ";
$query .= "  c.total_contacts_not_responded as sys_total_not_responded, ";
$query .= "  c.insert_date                  as tic_insert_date, ";
$query .= "  c.update_date                  as tic_update_date, ";
$query .= "  c.update_name                  as tic_update_name, ";
$query .= "  c.respond_by_date              as tic_respond_by_date, ";
$query .= "  c.approvals_required           as tic_approvals_required, ";
$query .= "  c.reboot_required              as tic_reboot_required, ";
$query .= "  c.owner_cuid                   as tic_owner_cuid, ";
$query .= "  c.owner_name                   as tic_owner_name, ";
$query .= "  c.owner_email                  as tic_owner_email, ";
$query .= "  c.owner_job_title              as tic_owner_job_title, ";
$query .= "  c.manager_cuid                 as tic_owner_manager_cuid, ";
$query .= "  c.manager_name                 as tic_owner_manager_name, ";
$query .= "  c.manager_email                as tic_owner_manager_email, ";
$query .= "  c.manager_job_title            as tic_owner_manager_job_title, ";
$query .= "  c.work_description             as tic_work_description, ";
$query .= "  c.work_implementation          as tic_work_implementation, ";
$query .= "  c.work_backoff_plan            as tic_work_backoff_plan, ";
$query .= "  c.work_business_reason         as tic_work_business_reason, ";
$query .= "  c.work_user_impact             as tic_user_impact, ";
$query .= "  c.email_reminder1_date         as tic_email_reminder1_date, ";
$query .= "  c.email_reminder2_date         as tic_email_reminder2_date, ";
$query .= "  c.email_reminder3_date         as tic_email_reminder3_date, ";
$query .= "  c.total_servers_scheduled      as tic_total_servers_scheduled, ";
$query .= "  c.total_servers_waiting        as tic_total_servers_waiting, ";
$query .= "  c.total_servers_approved       as tic_total_servers_approved, ";
$query .= "  c.total_servers_rejected       as tic_total_servers_rejected, ";
$query .= "  c.schedule_start_date          as tic_schedule_start_date, ";
$query .= "  c.schedule_end_date            as tic_schedule_end_date ";
$query .= "from  ";
$query .= "  cct7_mnet m,  ";
$query .= "  cct7_netpin_to_cuid n,  ";
$query .= "  (select  ";
$query .= "    t.*, ";
$query .= "    s.system_id,  ";
$query .= "    s.system_hostname, ";
$query .= "    s.system_work_status, ";
$query .= "    s.system_os, ";
$query .= "    s.system_usage, ";
$query .= "    s.system_location, ";
$query .= "    s.system_timezone_name, ";
$query .= "    s.system_osmaint_weekly, ";
$query .= "    s.system_work_start_date, ";
$query .= "    s.system_work_end_date, ";
$query .= "    s.system_work_duration, ";
$query .= "    s.total_contacts_responded, ";
$query .= "    s.total_contacts_not_responded, ";
$query .= "    c.contact_netpin_no, ";
$query .= "    c.contact_response_status, ";
$query .= "    c.contact_connection, ";
$query .= "    c.contact_server_os, ";
$query .= "    c.contact_server_usage, ";
$query .= "    c.contact_work_group, ";
$query .= "    c.contact_approver_fyi, ";
$query .= "    c.contact_response_date, ";
$query .= "    c.contact_response_cuid, ";
$query .= "    c.contact_response_name, ";
$query .= "    c.contact_respond_by_date, ";
$query .= "    c.contact_send_page, ";
$query .= "    c.contact_send_email ";
$query .= "  from  ";
$query .= "    cct7_tickets t,  ";
$query .= "    cct7_systems s,  ";
$query .= "    cct7_contacts c  ";
$query .= "  where  ";
$query .= "    s.ticket_no = t.ticket_no and   ";
$query .= "    c.system_id = s.system_id and ";
$query .= "    t.status = 'ACTIVE' and ";
$query .= "    (t.update_date != t.change_date or  ";
$query .= "    s.system_update_date != s.change_date or  ";
$query .= "    c.contact_update_date != c.change_date)  ";
$query .= "  order by  ";
$query .= "    t.ticket_no,  ";
$query .= "    s.system_hostname,  ";
$query .= "    c.contact_netpin_no) c  ";
$query .= "where  ";
$query .= "  m.mnet_email is not null and   ";
$query .= "  m.mnet_cuid = n.user_cuid and  ";
$query .= "  c.contact_netpin_no = n.net_pin_no  ";
$query .= "order by  ";
$query .= "  m.mnet_cuid,  ";
$query .= "  c.ticket_no,  ";
$query .= "  c.system_id,   ";
$query .= "  c.contact_netpin_no";

if ($ora1->sql2($query) == false)
{
	$lib->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $ora1->sql_statement);
	$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $ora1->dbErrMsg);
	exit();
}

$last_con_cuid      = '';
$last_con_name      = '';
$last_con_email     = '';
$last_tic_ticket_no = '';

$hash_tickets  = array();   // Used to count up the number of tickets we sent out notifications for.
$hash_servers  = array();   // Used to count up the number of servers we sent out notifications for.

while ($ora1->fetch())
{
    $hash_tickets[$ora1->tic_ticket_no] = 'Got a fish';
    $hash_servers[$ora1->sys_hostname]  = 'Got a fish';

    //
    // Do we need to open the email file for this contact?
    //
    if ($last_con_cuid != $ora1->con_cuid)
    {
        //
        // Close and send the last email to this person.
        //
        if (isset($fp) && $fp)
        {
			$message_body .= '</td></tr></table></center>';
			fwrite($fp, $message_body);
            fclose($fp);
			sendEmail($last_con_cuid, $last_con_name, $last_con_email, $message_body);
        }

		$last_tic_ticket_no = '';

		$filename = sprintf("/opt/ibmtools/cct7/email/%s.html", $ora1->con_cuid);
		//printf("Opening for write: %s\n", $filename);

		$fp = fopen($filename, "w");

		if (!$fp)
			die('File handle not available!');

		$message_body = '';

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
		addbody('<center>');
		addbody('<div style="width: 1250px;">');

		addbody('<font size="+2" color="red" face="Zapf Chancery, cursive"><b>Change Coordination Tool - Email Notification</b></font>');

		addbody("<p align='left'>" . $ora1->con_first_name . ",</p>");

		addbody("<p align='left'>The following is a detailed notification outlining the status of pending work requiring your approval ");
		addbody("or attention.</p>");

		addbody("<u>Please read through this email carefully!</u></p>");

		addbody("<p align='left'>Logon to CCT to and click on the Approval button to approve work for the servers you support.</p>");

		addbody("<p><a href='http://cct.corp.intranet'>https://cct.corp.intranet</a></p>");

		addbody("<p align='left'>If there are servers that are parent hosts for virtual servers you support then you will be asked to ");
		addbody("approve the work for the parent servers where there is a reboot that will effect your virtual server.</p>");

		addbody("<p align='left'><b>Please Note:</b> All dates and times in this email are shown in <u>Mountain Time</u>. When you logon to ");
		addbody("CCT all dates and times are shown in your local time zone. This means that the dates and times in this email may ");
		addbody("not match what you see in the CCT application.</p>");

		addbody("<p align='left'>CCT work notifications go out to Netpin Groups so your team members will receive the same notification. ");
		addbody("Any member of your Net Group team can approve or reject work requests so it is important that you work closely with your ");
		addbody("team in deciding who should be approving the work.");
		addbody('</div>');
    }

    //
    // Write the ticket information?
    //
    if ($last_tic_ticket_no != $ora1->tic_ticket_no)
    {
        $last_tic_ticket_no = $ora1->tic_ticket_no;

		$ticket_no               = isset($ora1->tic_ticket_no)               ? $ora1->tic_ticket_no               : "";
		$owner_name              = isset($ora1->tic_owner_name)              ? $ora1->tic_owner_name              : "";
		$insert_date             = isset($ora1->tic_insert_date)             ? $ora1->tic_insert_date             : "";
		$owner_email             = isset($ora1->tic_owner_email)             ? $ora1->tic_owner_email             : "";
		$owner_job_title         = isset($ora1->tic_owner_job_title)         ? $ora1->tic_owner_job_title         : "";
		$work_activity           = isset($ora1->tic_work_activity)           ? $ora1->tic_work_activity           : "";
		$manager_name            = isset($ora1->tic_owner_manager_name)      ? $ora1->tic_owner_manager_name      : "";
		$reboot_required         = isset($ora1->tic_reboot_required)         ? $ora1->tic_reboot_required         : "";
		$manager_email           = isset($ora1->tic_owner_manager_email)     ? $ora1->tic_owner_manager_email     : "";
		$approvals_required      = isset($ora1->tic_approvals_required)      ? $ora1->tic_approvals_required      : "";
		$manager_job_title       = isset($ora1->tic_owner_manager_job_title) ? $ora1->tic_owner_manager_job_title : "";
		$email_reminder1_date    = isset($ora1->tic_email_reminder1_date)    ? $ora1->tic_email_reminder1_date    : "";
		$update_date             = isset($ora1->tic_update_date)             ? $ora1->tic_update_date             : "";
		$email_reminder2_date    = isset($ora1->tic_email_reminder2_date)    ? $ora1->tic_email_reminder2_date    : "";
		$update_name             = isset($ora1->tic_update_name)             ? $ora1->tic_update_name             : "";
		$email_reminder3_date    = isset($ora1->tic_email_reminder3_date)    ? $ora1->tic_email_reminder3_date    : "";
		$total_servers_scheduled = isset($ora1->tic_total_servers_scheduled) ? $ora1->tic_total_servers_scheduled : "";
		$respond_by_date         = isset($ora1->tic_respond_by_date)         ? $ora1->tic_respond_by_date         : "";
		$total_servers_approved  = isset($ora1->tic_total_servers_approved)  ? $ora1->tic_total_servers_approved  : "";
		$schedule_start_date     = isset($ora1->tic_schedule_start_date)     ? $ora1->tic_schedule_start_date     : "";
		$remedy_cm_start_date    = isset($ora1->tic_cm_start_date)           ? $ora1->tic_cm_start_date           : "";
		$cm_ticket_no            = isset($ora1->tic_cm_ticket_no)            ? $ora1->tic_cm_ticket_no            : "";
		$remedy_cm_end_date      = isset($ora1->tic_cm_end_date)             ? $ora1->tic_cm_end_date             : "";
		$status                  = isset($ora1->tic_status)                  ? $ora1->tic_status                  : "";
		$work_description        = isset($ora1->tic_work_description)        ? $ora1->tic_work_description        : "";
		$work_implementation     = isset($ora1->tic_work_implementation)     ? $ora1->tic_work_implementation     : "";
		$work_backoff_plan       = isset($ora1->tic_work_backoff_plan)       ? $ora1->tic_work_backoff_plan       : "";
		$work_business_reason    = isset($ora1->tic_work_business_reason)    ? $ora1->tic_work_business_reason    : "";
		$work_user_impact        = isset($ora1->tic_user_impact)             ? $ora1->tic_user_impact             : "";

		$tz = "America/Denver";  // Mountain Time Zone

		$insert_date          = $lib->gmt_to_format($insert_date,          'm/d/Y H:i', $tz);
		$update_date          = $lib->gmt_to_format($update_date,          'm/d/Y H:i', $tz);
		$email_reminder1_date = $lib->gmt_to_format($email_reminder1_date, 'm/d/Y',     $tz);
		$email_reminder2_date = $lib->gmt_to_format($email_reminder2_date, 'm/d/Y',     $tz);
		$email_reminder3_date = $lib->gmt_to_format($email_reminder3_date, 'm/d/Y',     $tz);
		$respond_by_date      = $lib->gmt_to_format($respond_by_date,      'm/d/Y',     $tz);
		$schedule_start_date  = $lib->gmt_to_format($schedule_start_date,  'm/d/Y H:i', $tz);

		if ($remedy_cm_start_date == 0)
		{
			$remedy_cm_start_date = "(See Remedy)";
			$remedy_cm_end_date   = "(See Remedy)";
		}
		else
		{
			$remedy_cm_start_date = $lib->gmt_to_format($remedy_cm_start_date, 'm/d/Y H:i', $tz);
			$remedy_cm_end_date   = $lib->gmt_to_format($remedy_cm_end_date,   'm/d/Y H:i', $tz);
		}

		switch ($ora1->tic_status)
		{
			case 'CANCELED':
				$status = '<font color="red"><b>CANCELED</b></font>';
				break;
			default:
				$status = '<font color="blue"><b>' . $ora1->tic_status . '</b></font>';
				break;
		}

		addbody('<div style="width: 1250px;"><p>');
		addbody('<table bgcolor="#ECE9D8" width="100%" cellspacing="1" cellpadding="1" style="height: 100%; color: black">');
		addbody('<tr>');
		addbody('<td align="right" valign="top"><b>CCT Ticket:</b></td><td align="left" valign="top">' . $ticket_no . '</td>');
		addbody('<td align="right" valign="top"><b>Owner Name:</b></td><td align="left" valign="top">' . $owner_name . '</td>');
		addbody('</tr>');
		addbody('<tr>');
		addbody('<td align="right" valign="top"><b>Creation Date:</b></td><td align="left" valign="top">' . $insert_date . '</td>');
		addbody('<td align="right" valign="top"><b>Owner Email:</b></td><td align="left" valign="top">' .
			'<a href="mailto:' . $owner_email . '">' . $owner_email . '</a></td>');
		addbody('</tr>');
		addbody('<tr>');
		addbody('<td align="right" valign="top"><b>Ticket Status:</b></td><td align="left" valign="top">' . $status . '</td>');
		addbody('<td align="right" valign="top"><b>Owner Job Title:</b></td><td align="left" valign="top">' . $owner_job_title . '</td>');
		addbody('</tr>');
		addbody('<tr>');
		addbody('<td align="right" valign="top"><b>Work Activity:</b></td><td align="left" valign="top">' . $work_activity . '</td>');
		addbody('<td align="right" valign="top"><b>Manager Name:</b></td><td align="left" valign="top">' . $manager_name . '</td>');
		addbody('</tr>');
		addbody('<tr>');
		addbody('<td align="right" valign="top"><b>Reboot Required:</b></td><td align="left" valign="top">' . $reboot_required . '</td>');
		addbody('<td align="right" valign="top"><b>Manager Email:</b></td><td align="left" valign="top">' . $manager_email . '</td>');
		addbody('</tr>');
		addbody('<tr>');
		addbody('<td align="right" valign="top"><b>Approvals Required:</b></td><td align="left" valign="top">' . $approvals_required . '</td>');
		addbody('<td align="right" valign="top"><b>Manager Job Title:</b></td><td align="left" valign="top">' . $manager_job_title . '</td>');
		addbody('</tr>');
		addbody('<tr>');
		addbody('<td colspan="4">&nbsp;</td>');
		addbody('</tr>');
		addbody('<tr>');
		addbody('<td align="right" valign="top"><b>Email Reminder 1:</b></td><td align="left" valign="top">' . $email_reminder1_date . '</td>');
		addbody('<td align="right" valign="top"><b>Last Modified:</b></td><td align="left" valign="top">' . $update_date . '</td>');
		addbody('</tr>');
		addbody('<tr>');
		addbody('<td align="right" valign="top"><b>Email Reminder 2:</b></td><td align="left" valign="top">' . $email_reminder2_date . '</td>');
		addbody('<td align="right" valign="top"><b>Modified By:</b></td><td align="left" valign="top">' . $update_name . '</td>');
		addbody('</tr>');
		addbody('<tr>');
		addbody('<td align="right" valign="top"><b>Email Reminder 3:</b></td><td align="left" valign="top">' . $email_reminder3_date . '</td>');
		addbody('<td align="right" valign="top"><b>Total Servers:</b></td><td align="left" valign="top">' . $total_servers_scheduled . '</td>');
		addbody('</tr>');
		addbody('<tr>');
		addbody('<td align="right" valign="top"><b>Respond By:</b></td><td align="left" valign="top"><font color="blue"><b>' . $respond_by_date . '</b></font></td>');
		addbody('<td align="right" valign="top"><b>Servers Approved:</b></td><td align="left" valign="top">' . $total_servers_approved . '</td>');
		addbody('</tr>');
		addbody('<tr>');
		addbody('<td align="right" valign="top"><b>Schedule Start:</b></td>');
		addbody('<td align="left" valign="top">' . $schedule_start_date . '</td>');
		addbody('<td align="right" valign="top"><b>Remedy Start:</b></td>');
		addbody('<td align="left" valign="top">' . $remedy_cm_start_date . '</td>');
		addbody('</tr>');
		addbody('<tr>');
		addbody('<td align="right" valign="middle"><b>Remedy CM:</b></td>');
		addbody('<td align="left" valign="middle">' . $cm_ticket_no . '</td>');
		addbody('<td align="right" valign="top"><b>Remedy End:</b></td>');
		addbody('<td align="left" valign="top">' . $remedy_cm_end_date . '</td>');
		addbody('</tr>');
		addbody('<tr>');
		addbody('<td align="left" valign="top" colspan="4"><b><u>Description</u></b><pre>' . $work_description . '</pre></td>');
		addbody('</tr>');
		addbody('<tr>');
		addbody('<td align="left" valign="top" colspan="4"><b><u>Implementation/Instructions</u></b><pre>' . $work_implementation . '</pre></td>');
		addbody('</tr>');
		addbody('<tr>');
		addbody('<td align="left" valign="top" colspan="4"><b><u>Backoff Plans</u></b><pre>' . $work_backoff_plan . '</pre></td>');
		addbody('</tr>');
		addbody('<tr>');
		addbody('<td align="left" valign="top" colspan="4"><b><u>Business Reasons</u></b><pre>' . $work_business_reason . '</pre></td>');
		addbody('</tr>');
		addbody('<tr>');
		addbody('<td align="left" valign="top" colspan="4"><b><u>Impact</u></b><pre>' . $work_user_impact . '</pre></td>');
		addbody('</tr>');

		//
		// Write any Ticket log information from cct7_log_tickets from the last 30 hours.
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
		$query .= "  ticket_no = '" . $ticket_no . "' and ";
		$query .= "  event_date > " . $thirty_hours_ago . " ";
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

			$event_date    = $lib->gmt_to_format($event_date, 'm/d/Y H:i', $tz);

			if ($count == 0)
			{
				addbody('<tr>');
				addbody('<td colspan="4" align="left" valign="top">');
			}

			$count += 1;

			addbody('<b>' . $event_date . '</b> ' . $event_name . '<br>');
			addbody('<b>' . $event_type . '</b> ' . $event_message . '<br><br>');
		}

		if ($count > 0)
		{
			addbody('</td></tr>');
		}

		addbody('</table>');
		addbody('</p><br></div>');
    }

    //
    // Write the system information
    //
	$system_id                    = isset($ora1->sys_system_id)           ? $ora1->sys_system_id           : 0;
	$ticket_no                    = isset($ora1->tic_ticket_no)           ? $ora1->tic_ticket_no           : "";
	$system_hostname              = isset($ora1->sys_hostname)            ? $ora1->sys_hostname            : "";
	$system_os                    = isset($ora1->sys_os)                  ? $ora1->sys_os                  : "";
	$system_usage                 = isset($ora1->sys_usage)               ? $ora1->sys_usage               : "";
	$system_location              = isset($ora1->sys_location)            ? $ora1->sys_location            : "";
	$system_timezone_name         = isset($ora1->sys_timezone)            ? $ora1->sys_timezone            : "";
	$system_osmaint_weekly        = isset($ora1->sys_osmaint)             ? $ora1->sys_osmaint             : "";
	$system_respond_by_date       = isset($ora1->system_respond_by_date)  ? $ora1->system_respond_by_date  : 0;
	$system_work_start_date       = isset($ora1->sys_work_start_date)     ? $ora1->sys_work_start_date     : 0;
	$system_work_end_date         = isset($ora1->sys_work_end_date)       ? $ora1->sys_work_end_date       : 0;
	$system_work_duration         = isset($ora1->sys_work_duration)       ? $ora1->sys_work_duration       : "";
	$system_work_status           = isset($ora1->sys_work_status)         ? $ora1->sys_work_status         : "";
	$total_contacts_responded     = isset($ora1->sys_total_responded)     ? $ora1->sys_total_responded     : "";
	$total_contacts_not_responded = isset($ora1->sys_total_not_responded) ? $ora1->sys_total_not_responded : "";

	$tz = "America/Denver";  // TODO: Should we use $system_timezone_name here? ???

	$system_respond_by_date   = $lib->gmt_to_format($system_respond_by_date,   'm/d/Y',     $tz);

	if ($system_work_start_date == 0)
	{
		$system_work_start_date   = "(See Remedy)";
		$system_work_end_date     = "(See Remedy)";
	}
	else
	{
		$system_work_start_date   = $lib->gmt_to_format($system_work_start_date,   'm/d/Y H:i', $tz);
		$system_work_end_date     = $lib->gmt_to_format($system_work_end_date,     'm/d/Y H:i', $tz);
	}

	switch ($system_work_status)
	{
		case 'APPROVED':
			$system_work_status = '<font color="green"><b>APPROVED</b></font>';
			break;
		case 'CANCELED':
			$system_work_status = '<font color="red"><b>CANCELED</b></font>';
			break;
		case 'FAILED':
			$system_work_status = '<font color="red"><b>FAILED</b></font>';
			break;
		case 'REJECTED':
			$system_work_status = '<font color="red"><b>REJECTED</b></font>';
			break;
		case 'STARTING':
			$system_work_status = '<font color="purple"><b>STARTING</b></font>';
			break;
		case 'SUCCESS':
			$system_work_status = '<font color="green"><b>SUCCESS</b></font>';
			break;
		case 'WAITING':
			$system_work_status = '<font color="red"><b>WAITING</b></font>';
			break;
		default:
			$system_work_status = '<font color="blue"><b>' . $system_work_status . '</b></font>';
			break;
	}

	addbody('<div style="width: 1250px;">');
	addbody('<table bgcolor="#a7ce83" width="100%" cellspacing="2" cellpadding="2" style="color: black">');
	addbody('<tr>');
	addbody('<td align="right" valign="top" width="20%"><b>Server:</b></td>');
	addbody('<td align="left" valign="top" width="30%"><font color="blue">' . $system_hostname . '</font></td>');
	addbody('<td align="right" valign="top" width="20%"><b>Respond By:</b></td>');
	addbody('<td align="left" valign="top" width="30%"><font color="blue"><b>' . $system_respond_by_date . '</b></font></td>');
	addbody('</tr>');
	addbody('<tr>');
	addbody('<td align="right" valign="top" width="20%"><b>OS:</b></td>');
	addbody('<td align="left" valign="top" width="30%">' . $system_os . '</td>');
	addbody('<td align="right" valign="top" width="20%"><b>Work Status:</b></td>');
	addbody('<td align="left" valign="top" width="30%">' . $system_work_status . '</td>');
	addbody('</tr>');
	addbody('<tr>');
	addbody('<td align="right" valign="top" width="20%"><b>Usage:</b></td>');
	addbody('<td align="left" valign="top" width="30%">' . $system_usage . '</td>');
	addbody('<td align="right" valign="top" width="20%"><b>Work Start:</b></td>');
	addbody('<td align="left" valign="top" width="30%">' . $system_work_start_date . '</td>');
	addbody('</tr>');
	addbody('<tr>');
	addbody('<td align="right" valign="top" width="20%"><b>Location:</b></td>');
	addbody('<td align="left" valign="top" width="30%">' . $system_location . '</td>');
	addbody('<td align="right" valign="top" width="20%"><b>Work End:</b></td>');
	addbody('<td align="left" valign="top" width="30%">' . $system_work_end_date . '</td>');
	addbody('</tr>');
	addbody('<tr>');
	addbody('<td align="right" valign="top" width="20%"><b>Time Zone:</b></td>');
	addbody('<td align="left" valign="top" width="30%">' . $system_timezone_name . '</td>');
	addbody('<td align="right" valign="top" width="20%"><b>Duration:</b></td>');
	addbody('<td align="left" valign="top" width="30%">' . $system_work_duration . '</td>');
	addbody('</tr>');
	addbody('<tr>');
	addbody('<td colspan="4">&nbsp;</td>');
	addbody('</tr>');
	addbody('<tr>');
	addbody('<td align="right" valign="top" width="20%"><b>Maintenance:</b></td>');
	addbody('<td align="left" valign="top" width="30%">' . $system_osmaint_weekly . '</td>');
	addbody('<td align="right" valign="top" width="20%"><b># Responded:</b></td>');
	addbody('<td align="left" valign="top" width="30%">' . $total_contacts_responded . '</td>');
	addbody('</tr>');
	addbody('<tr>');
	addbody('<td colspan="2">&nbsp;</td>');
	addbody('<td align="right" valign="top" width="20%"><b># Waiting:</b></td>');
	addbody('<td align="left" valign="top" width="30%">' . $total_contacts_not_responded . '</td>');
	addbody('</tr>');

	//
	// Get the System log information from cct7_log_systems
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
	$query .= "  system_id = " . $system_id . " and ";
	$query .= "  event_date > " . $thirty_hours_ago . " ";
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

		$event_date    = $lib->gmt_to_format($event_date, 'm/d/Y H:i', $tz);

		if ($count == 0)
		{
			addbody('<tr>');
			addbody('<td colspan="4" align="left" valign="top">');
		}

		$count += 1;

		addbody('<b>' . $event_date . '</b> ' . $event_name . '<br>');
		addbody('<b>' . $event_type . '</b> ' . $event_message . '<br><br>');
	}

	if ($count > 0)
	{
		addbody('</td></tr>');
	}

	addbody('</table>');
	addbody('</div>');

    //
    // Write the contact information
    //
    $contact_netpin_no       = isset($ora1->con_netpin_no)       ? $ora1->con_netpin_no       : "";
	$contact_connection      = isset($ora1->con_connection)      ? $ora1->con_connection      : "";
	$contact_work_group      = isset($ora1->con_work_group)      ? $ora1->con_work_group      : "";
	$contact_approver_fyi    = isset($ora1->con_approver_fyi)    ? $ora1->con_approver_fyi    : "";
	$contact_respond_by_date = isset($ora1->con_respond_by_date) ? $ora1->con_respond_by_date : 0;
	$contact_response_status = isset($ora1->con_response_status) ? $ora1->con_response_status : "";
	$contact_response_date   = isset($ora1->con_response_date)   ? $ora1->con_response_date   : 0;
	$contact_response_name   = isset($ora1->con_response_name)   ? $ora1->con_response_name   : "";
	$contact_send_page       = isset($ora1->con_send_page)       ? $ora1->con_send_page       : "";
	$contact_send_email      = isset($ora1->con_send_email)      ? $ora1->con_send_email      : "";

	$tz = "America/Denver";

	$contact_respond_by_date = $lib->gmt_to_format($contact_respond_by_date, 'm/d/Y',     $tz);
	$contact_response_date   = $lib->gmt_to_format($contact_response_date,   'm/d/Y H:i', $tz);

	switch ($contact_response_status)
	{
		case 'APPROVED':
			$contact_response_status = '<font color="green"><b>APPROVED</b></font>';
			break;
		case 'REJECTED':
			$contact_response_status = '<font color="red"><b>REJECTED</b></font>';
			break;
		case 'EXEMPT':
			$contact_response_status = '<font color="red"><b>EXEMPT</b></font>';
			break;
		case 'WAITING':
			$contact_response_status = '<font color="purple"><b>WAITING</b></font>';
			break;
		default:
			$contact_response_status = '<font color="blue"><b>' . $contact_response_status . '</b></font>';
			break;
	}

	addbody('<div style="width: 1250px;">');
	addbody('<table bgcolor="#ffffcc" width="100%" cellspacing="2" cellpadding="2" style="color: black">');
	addbody('<tr>');
	addbody('<td colspan="4">&nbsp;</td>');
	addbody('</tr>');
	addbody('<tr>');
	addbody('<td align="right" valign="top" width="20%"><b>Netpin:</b></td>');
	addbody('<td align="left" valign="top" width="30%">' . $contact_netpin_no . '</td>');
	addbody('<td colspan="2">&nbsp;</td>');
	addbody('</tr>');
	addbody('<tr>');
	addbody('<td align="right" valign="top" width="20%"><b>Respond By:</b></td>');
	addbody('<td align="left" valign="top" width="30%"><font color="blue"><b>' . $contact_respond_by_date . '</b></font></td>');
	addbody('<td align="right" valign="top" width="20%"><b>Response Date:</b></td>');
	addbody('<td align="left"  valign="top" width="30%">' . $contact_response_date . '</td>');
	addbody('</tr>');
	addbody('<tr>');
	addbody('<td align="right" valign="top" width="20%"><b>Response:</b></td>');
	addbody('<td align="left"  valign="top" width="30%">' . $contact_response_status . '</td>');
	addbody('<td align="right" valign="top" width="20%"><b>Responder Name:</b></td>');
	addbody('<td align="left"  valign="top" width="30%">' . $contact_response_name . '</td>');
	addbody('</tr>');
	addbody('<tr>');
	addbody('<td align="right" valign="top" width="20%"><b>Connection:</b></td>');
	addbody('<td align="left"  valign="top" width="30%"><font color="blue">' . $contact_connection . '</font></td>');
	addbody('<td align="right" valign="top" width="20%"><b>Group:</b></td>');
	addbody('<td align="left"  valign="top" width="30%">' . $contact_work_group . '</td>');
	addbody('</tr>');
	addbody('<tr>');
	addbody('<td align="right" valign="top" width="20%"><b>Page On-Call:</b></td>');
	addbody('<td align="left"  valign="top" width="30%">' . $contact_send_page . '</td>');
	addbody('<td align="right" valign="top" width="20%"><b>Type:</b></td>');
	addbody('<td align="left"  valign="top" width="30%">' . $contact_approver_fyi . '</td>');
	addbody('</tr>');
	addbody('<tr>');
	addbody('<td align="right" valign="top" width="20%"><b>Send Email:</b></td>');
	addbody('<td align="left"  valign="top" width="30%">' . $contact_send_email . '</td>');
	addbody('<td align="right" valign="top" width="20%">&nbsp;</td>');
	addbody('<td align="left"  valign="top" width="30%">&nbsp;</td>');
	addbody('</tr>');

	//
	// Get the Contact log information from cct7_log_contacts
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
	$query .= "  system_id = " . $system_id . " and ";
	//$query .= "  netpin_no = '" . $contact_netpin_no . "' and ";
	$query .= "  event_date > " . $thirty_hours_ago . " ";
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

		$event_date    = $lib->gmt_to_format($event_date, 'm/d/Y H:i', $tz);

		if ($count == 0)
		{
			addbody('<tr>');
			addbody('<td colspan="4" align="left" valign="top">');
		}

		$count += 1;

		addbody('<b>' . $event_date . '</b> ' . $event_name . '<br>');
		addbody('<b>' . $event_type . '</b> ' . $event_message . '<br><br>');
	}

	if ($count > 0)
	{
		addbody('</td></tr>');
	}

	//addbody('<tr><td colspan="4"><hr></td></tr>');
	addbody('</table>');
	addbody('</div>');

	$last_con_cuid      = $ora1->con_cuid;
	$last_con_name      = $ora1->con_name;
	$last_con_email     = $ora1->con_email;
}

//
// Close and send the last email for this person.
//
if (isset($fp) && $fp)
{
	addbody('</td></tr></table></center>');
	fwrite($fp, $message_body);
	fclose($fp);
	sendEmail($last_con_cuid, $last_con_name, $last_con_email, $message_body);
}

$count_tickets = count($hash_tickets);
$count_servers = count($hash_servers);

printf("\nProcessed notifications for %d tickets and %d servers.\n", $count_tickets, $count_servers);
printf("Total Email notifications sent: %d\n", $count_emails);

// Record when we ended processing notifications.
$date2 = new DateTime("now");
$interval = $date1->diff($date2);
printf("\nProcessing runtime: %s\n", $interval->format('%H:%I:%S'));

//
// Set the change_date to match update_date in cct7_tickets, cct7_systems, and cct7_contacts.
//
$date1 = new DateTime("now");
printf("Setting change_date to match update_date in cct7_tickets\n");
$ora1->sql2('update cct7_tickets set change_date = update_date where change_date != update_date');
$date2 = new DateTime("now");
$interval = $date1->diff($date2);
printf("\nProcessing runtime: %s\n", $interval->format('%H:%I:%S'));

$date1 = new DateTime("now");
printf("Setting change_date to match update_date in cct7_tickets\n");
$ora1->sql2('update cct7_systems set change_date = system_update_date where change_date != system_update_date');
$date2 = new DateTime("now");
$interval = $date1->diff($date2);
printf("\nProcessing runtime: %s\n", $interval->format('%H:%I:%S'));

$date1 = new DateTime("now");
printf("Setting change_date to match update_date in cct7_tickets\n");
$ora1->sql2('update cct7_contacts set change_date = contact_update_date where change_date != contact_update_date');
$date2 = new DateTime("now");
$interval = $date1->diff($date2);
printf("\nProcessing runtime: %s\n", $interval->format('%H:%I:%S'));

$ora1->commit();
exit();

