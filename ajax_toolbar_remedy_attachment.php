<?php
/**
 * ajax_toolbar_remedy_attachment.php
 *
 * @package   PhpStorm
 * @file      ajax_toolbar_remedy_attachment.php
 * @author    gparkin
 * @date      4/13/17
 * @version   7.0
 *
 * @brief     This module gathers the ready work for a ticket and sends it back
 *            to toolbar_rememdy_attachment.php. Dates are converted to America/Denver
 *            timezone and not the user's local timezone.
 */

set_include_path("/opt/ibmtools/www/cct7/classes");

//
// Class autoloader - Removes the need to add include and require statements
//
if (!function_exists('__autoload'))
{
	require_once('classes/autoloader.php');
}

if (session_id() == '')
	session_start();

if (isset($_SESSION['user_cuid']) && $_SESSION['user_cuid'] == 'gparkin')
{
	ini_set('xdebug.collect_vars',    '5');
	ini_set('xdebug.collect_vars',    'on');
	ini_set('xdebug.collect_params',  '4');
	ini_set('xdebug.dump_globals',    'on');
	ini_set('xdebug.dump.SERVER',     'REQUEST_URI');
	ini_set('xdebug.show_local_vars', 'on');

	//$path = '/usr/lib/pear';
	//set_include_path(get_include_path() . PATH_SEPARATOR . $path);
}
else
{
	ini_set('display_errors', 'Off');
}

if (!isset($_SESSION['issue_changed']))
	$_SESSION['issue_changed'] = time();

// Prevent caching.
header('Cache-Control: no-cache, must-revalidate');
header('Expires: Mon, 01 Jan 1996 00:00:00 GMT');

// The JSON standard MIME header.
header('Content-type: application/json');

//
// NOTE: It is very important that you do not turn on debugging without writing to a file for server side AJAX code. Any HTML
//       comments coming from functions while writing JSON will show up in the JSON output and you will get a parsing error
//       in the client side program.
//
$lib = new library();  // classes/library.php
$lib->debug_start('ajax_toolbar_remedy_attachment.html');
date_default_timezone_set('America/Denver');

$ora = new oracle();

$my_request  = array();
$input       = array();
$input_count = 0;

if (isset($_SERVER['QUERY_STRING']))   // Parse QUERY_STRING if it exists
{
	parse_str($_SERVER['QUERY_STRING'], $my_request);  // Parses URL parameter options into an array called $my_request
	$input_count = count($my_request);                 // Get the count of the number of $my_request array elements.
}

// Convert $my_request into an object called $input
$input          = json_decode(json_encode($my_request), FALSE);

$ticket_no      = isset($input->ticket_no)      ? $input->ticket_no      : '';
$convert        = isset($input->convert)        ? $input->convert        : '';

//
// We want to select records where the system work status is either APPROVED, STARTING, SUCCESS,
// FAILED, or CANCEL. Initially when the user pulls the report all the statuses should be just APPROVED.
// But later after the work is done, they should be changed to one of the other statuses, that is if
// the use the Ready Work (on the toolbar) to actually send out notifications to clients as they do
// work. They can then use this report to build a spreadsheet of the status of all the servers as work
// was completed.
//
$query  = "select ";
$query .= "  t.cm_ticket_no, ";
$query .= "  t.ticket_no, ";
$query .= "  t.work_activity, ";
$query .= "  s.system_work_status, ";
$query .= "  s.system_hostname, ";
$query .= "  s.system_os, ";
$query .= "  s.system_usage, ";
$query .= "  t.owner_name, ";
$query .= "  s.system_osmaint_weekly, ";
$query .= "  s.system_timezone_name, ";
$query .= "  s.system_work_start_date, ";
$query .= "  s.system_work_end_date, ";
$query .= "  s.system_work_duration, ";
$query .= "  t.reboot_required ";
$query .= "from ";
$query .= "  cct7_tickets t, ";
$query .= "  cct7_systems s ";
$query .= "where ";
$query .= "  (t.ticket_no = upper('" . $ticket_no . "') or ";
$query .= "   t.cm_ticket_no = upper('" . $ticket_no . "')) and ";
$query .= "  s.ticket_no = t.ticket_no and ";
$query .= "  s.system_work_status in ('APPROVED', 'STARTING', 'SUCCESS', 'FAILED', 'CANCELED') ";
$query .= "order by ";
$query .= "  s.system_hostname";

$lib->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $query);

if ($ora->sql2($query) == false)
{
	//$lib->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $ora->sql_statement);
	$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $ora->dbErrMsg);
}

printf("{\n");

if ($convert == 'user')
	$tz = $_SESSION['local_timezone_name'];
else
	$tz = 'America/Denver';  // Convert all dates and times to Mountain Time.

$row = array();
$count_records = 0;

while ($ora->fetch())
{
	if ($count_records == 0)
	{
		printf("\"ajax_status\": \"SUCCESS\",\n");
		printf("\"ajax_message\": \"There be whales here Captain!\",\n");
		printf("\"cm_ticket_no\": \"%s\",\n",  $ora->cm_ticket_no);
		printf("\"ticket_no\": \"%s\",\n",     $ora->ticket_no);
		printf("\"work_activity\": \"%s\",\n", $ora->work_activity);
		printf("\"owner_name\": \"%s\",\n",    $ora->owner_name);
		printf("\"rows\": [\n");
	}

	if ($count_records > 0)
	{
		printf(",\n");  // Let the client know that another record is coming.
	}

	$mmddyyyy_hhmm = 'm/d/Y H:i';
	$mmddyyyy      = 'm/d/Y';

	if ($ora->system_work_start_date == 0)
	{
		$system_work_start_date = '(See Remedy)';
		$system_work_end_date   = '(See Remedy)';
	}
	else
	{
		if ($convert == 'server')
		{
			$system_work_start_date = $lib->gmt_to_format(
				$ora->system_work_start_date, $mmddyyyy_hhmm, $ora->system_timezone_name);
			$system_work_end_date   = $lib->gmt_to_format(
				$ora->system_work_end_date,   $mmddyyyy_hhmm, $ora->system_timezone_name);
		}
		else
		{
			// $tz will be the user's localtime or the Mountain timezone
			$system_work_start_date = $lib->gmt_to_format($ora->system_work_start_date, $mmddyyyy_hhmm, $tz);
			$system_work_end_date   = $lib->gmt_to_format($ora->system_work_end_date,   $mmddyyyy_hhmm, $tz);
		}
	}

	//$row['Remedy Ticket']   = $ora->cm_ticket_no;
	//$row['CCT Ticket']      = $ora->ticket_no;
	//$row['Work Activity']   = $ora->work_activity;
	//$row['Ticket Owner']    = $ora->owner_name;

	$row['Hostname']           = $ora->system_hostname;
	$row['OS']                 = $ora->system_os;
	$row['System Usage']       = $ora->system_usage;
	$row['Approval Status']    = $ora->system_work_status;
	$row['Maintenance Window'] = $ora->system_osmaint_weekly;
	$row['Work Start']         = $system_work_start_date;
	$row['Work End']           = $system_work_end_date;
	$row['Work Duration']      = $ora->system_work_duration;
	$row['Reboot']             = $ora->reboot_required;

	echo json_encode($row);

	$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "SENDING: %s", json_encode($row));

	$count_records++;  // Count up the records to determine if we sent anything.
}

if ($count_records == 0)
{
	printf("\"ajax_status\": \"FAILED\",\n");
	printf("\"ajax_message\": \"No Approved Ready Work available.\",\n");
	printf("\"cm_ticket_no\": \"%s\",\n",  $ora->cm_ticket_no);
	printf("\"ticket_no\": \"%s\",\n",     $ora->ticket_no);
	printf("\"work_activity\": \"%s\",\n", $ora->work_activity);
	printf("\"owner_name\": \"%s\",\n",    $ora->owner_name);
	printf("\"rows\": [\n");
}

printf("]}\n");  // Close out the data stream
exit();