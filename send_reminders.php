#!/opt/lampp/bin/php -q
<?php
/**
 * send_reminders.php
 *
 * @package   PhpStorm
 * @file      send_reminders.php
 * @author    gparkin
 * @date      07/24/17
 * @version   7.0
 *
 * Daily cronjob to send out reminder messages to those who have not responded to
 * work requests.
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
$lib->debug_start('send_reminders.html');
date_default_timezone_set('America/Denver');

//$lib->html_dump();
$ora  = new oracle();
$list = new email_contacts($ora);

//
// Set the timezone to GMT and get the current time.
// Substract the 30 hours from the date and return UTIME.
//
$date = new DateTime();
$date->setTimezone(new DateTimeZone('GMT'));
$date->setTimestamp(time());

$date = date('m/d/Y'); // Get current date in format: MM/DD/YYYY

$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "Sending reminders for %s", $date);

$today_start = $date . " 00:00";
$today_end   = $date . " 23:59";

$today_start = $lib->to_gmt($today_start, "GMT");
$today_end   = $lib->to_gmt($today_end,   "GMT");

$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "-- Start GMT UTIME: %d", $today_start);
$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "--   End GMT UTIME: %d", $today_end);

printf("Processing reminder emails for %s\n", $date);
printf("-- Start GMT UTIME: %d\n", $today_start);
printf("--   End GMT UTIME: %d\n\n", $today_end);

$query  = "select distinct ";
$query .= "  t.ticket_no         as ticket_no ";
$query .= "from ";
$query .= "  cct7_tickets t ";
$query .= "where ";
$query .= "  t.status = 'ACTIVE' and ";
$query .= "  ((t.email_reminder1_date >= " . $today_start . " and t.email_reminder1_date <= " . $today_end . ") or ";
$query .= "   (t.email_reminder2_date >= " . $today_start . " and t.email_reminder2_date <= " . $today_end . ") or ";
$query .= "   (t.email_reminder3_date >= " . $today_start . " and t.email_reminder3_date <= " . $today_end . "))";

$lib->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $query);

if ($ora->sql2($query) == false)
{
	$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $ora->dbErrMsg);
	$error_message = $ora->dbErrMsg;
	return false;
}

$ticket_reminders = array();

while ($ora->fetch())
{
    $ticket_reminders[$ora->ticket_no] = "There be whales here captain!";
}

$count = 0;

foreach ($ticket_reminders as $ticket_no => $whales)
{
	/**
	 *  function byTicket($ticket_no,                           $send_to_approvers="Y", $send_to_fyi="N", $waiting_only="N")
	 *  function bySystem(             $system_id,              $send_to_approvers="Y", $send_to_fyi="N", $waiting_only="N")
	 *  function byContact($ticket_no, $system_id, $contact_id, $send_to_approvers="Y", $send_to_fyi="N", $waiting_only="N")
	 */
    if ($list->byTicket($ticket_no, "Y", "N", "Y") == false)
    {
		$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $list->error);
		printf("%s\n", $list->error);
		continue;
    }

	foreach ($list->email_list as $cuid => $name_and_email_and_type)
    {
		//
		// Break apart $name_and_email_and_type  (i.e. Mary Subach|Mary.Subach@CMP.com|FYI)
		//
		$str = explode('|', $name_and_email_and_type);

		$name          = isset($str[0]) ? $str[0] : "";
		$email_address = isset($str[1]) ? $str[1] : "";
		// $notify_type   = isset($str[2]) ? $str[2] : "";

		if (strlen($email_address) == 0)
		{
			$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "Missing email address: %s", $str);
			continue;
		}
		
		printf("Sending reminder for: %s|%s\n", $ticket_no, $name_and_email_and_type);

		$to        = $email_address;
		$to_header = sprintf("%s <%s>", $name, $email_address);

		// To send HTML mail, the Content-type header must be set
		$headers  = 'MIME-Version: 1.0' . "\r\n";
		$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";

		// Additional headers
		$headers .= 'From: CCT Reminder <no-reply@us.ibm.com>' . "\r\n";;
		$headers .= 'To: ' . $to_header . "\r\n";

		$subject = sprintf("CCT Work Request Reminder: %s", $ticket_no);

		$message_body  = sprintf("<p>This is automated reminder message from CCT letting you know that you ");
		$message_body .= sprintf("haven't yet approved work for CCT ticket: %s</p>\r\n", $ticket_no);

		$message_body .= "<p>Please sign-on to CCT at https://cct.corp.intranet and click on the Approve link ";
		$message_body .= "located on the menu bar.</p>\r\n";

		//mail($to, $subject, $message_body, $headers);
		$count += 1;
    }
}

printf("\nEmails sent: %d\n", $count);
