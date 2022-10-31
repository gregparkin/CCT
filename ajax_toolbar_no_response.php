<?php
/**
 * ajax_toolbar_no_response.php
 *
 * @package   PhpStorm
 * @file      ajax_toolbar_no_response.php
 * @author    gparkin
 * @date      4/29/17
 * @version   7.0
 *
 * @brief     This module returns a list of contacts that have not responded to a work request.
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
$lib->debug_start('ajax_toolbar_no_response.html');
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

$tic = new cct7_tickets();

if ($tic->getTicket($ticket_no) == false)
{
	printf("{\n");
	printf("\"ajax_status\":   \"FAILED\",\n");
	printf("\"ajax_message\":  \"%s\",\n", $tic->error);
	printf("\"cm_ticket_no\":  \"\",\n");
	printf("\"ticket_no\":     \"\",\n");
	printf("\"work_activity\": \"\",\n");
	printf("\"owner_name\":    \"\",\n");
	printf("\"respond_by\":    \"\",\n");
	printf("\"rows\": [\n");
	printf("]}\n");  // Close out the data stream
	exit();
}

$query  = "select distinct ";
$query .= "  c.contact_netpin_no, ";
$query .= "  n.user_cuid, ";
$query .= "  m.mnet_name, ";
$query .= "  m.mnet_email, ";
$query .= "  c.contact_work_group, ";
$query .= "  c.contact_connection, ";
$query .= "  c.contact_apps_databases, ";
$query .= "  s.system_hostname, ";
$query .= "  s.system_work_start_date, ";
$query .= "  s.system_work_end_date, ";
$query .= "  s.system_work_duration ";
$query .= "from ";
$query .= "  cct7_tickets t, ";
$query .= "  cct7_systems s, ";
$query .= "  cct7_contacts c, ";
$query .= "  cct7_netpin_to_cuid n, ";
$query .= "  cct7_mnet m ";
$query .= "where ";
$query .= "  (t.ticket_no = upper('" . $ticket_no . "') or ";
$query .= "   t.cm_ticket_no = upper('" . $ticket_no . "')) and ";
$query .= "  s.ticket_no = t.ticket_no and ";
$query .= "  s.system_work_status = 'WAITING' and ";
$query .= "  c.system_id = s.system_id and ";
$query .= "  c.contact_response_status = 'WAITING' and ";
$query .= "  n.net_pin_no = c.contact_netpin_no and ";
$query .= "  m.mnet_cuid = n.user_cuid ";
$query .= "order by ";
$query .= "  c.contact_netpin_no, n.user_cuid, s.system_hostname";

$lib->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $query);

if ($ora->sql2($query) == false)
{
	//$lib->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $ora->sql_statement);
	$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $ora->dbErrMsg);
}

$tz = 'America/Denver';  // Convert all dates and times to Mountain Time.

$row = array();
$count_records = 0;

$mmddyyyy_hhmm = 'm/d/Y H:i';
$mmddyyyy      = 'm/d/Y';

$no_response = array();

//
// Consolidate the email addresses for matching servers and netpins so we only produce one row data for
// the matching netpin and server.
//
while ($ora->fetch())
{
	if ($ora->system_work_start_date == 0)
	{
		$system_work_start_date = '(See Remedy)';
		$system_work_end_date   = '(See Remedy)';
	}
	else
	{
		$system_work_start_date = $lib->gmt_to_format($ora->system_work_start_date, $mmddyyyy_hhmm, $tz);
		$system_work_end_date   = $lib->gmt_to_format($ora->system_work_end_date, $mmddyyyy_hhmm, $tz);
	}

	$key = $ora->system_hostname . $ora->contact_netpin_no;

	if (array_key_exists($key, $no_response))
	{
		$node = $no_response[$key];
		$node->email_list .= "," . $ora->mnet_email;  // Consolidate contact email addresses
	}
	else
	{
		$node = new data_node();
		$node->netpin                 = $ora->contact_netpin_no;
		$node->hostname               = $ora->system_hostname;
		$node->contact_work_group     = $ora->contact_work_group;
		$node->contact_connection     = $ora->contact_connection;
		$node->contact_apps_databases = $ora->contact_apps_databases;
		$node->system_work_start_date = $system_work_start_date;
		$node->system_work_end_date   = $system_work_end_date;
		$node->system_work_duration   = $ora->system_work_duration;
		$node->email_list             = $ora->mnet_email;
		$no_response[$key] = $node;
	}

	$count_records++;

} // while ($ora->fetch())

if ($count_records == 0)
{
	$respond_by_date = $lib->gmt_to_format($tic->respond_by_date_num, $mmddyyyy, $tz);

	printf("{\n");
	printf("\"ajax_status\":   \"FAILED\",\n");
	printf("\"ajax_message\":  \"No Response Contacts Found!\",\n");
	printf("\"cm_ticket_no\":  \"%s\",\n", $tic->cm_ticket_no);
	printf("\"ticket_no\":     \"%s\",\n", $tic->ticket_no);
	printf("\"work_activity\": \"%s\",\n", $tic->work_activity);
	printf("\"owner_name\":    \"%s\",\n", $tic->owner_name);
	printf("\"respond_by\":    \"%s\",\n", $respond_by_date);
	printf("\"rows\": [\n");
	printf("]}\n");  // Close out the data stream
	exit();
}
else
{
	$respond_by_date = $lib->gmt_to_format($tic->respond_by_date_num, $mmddyyyy, $tz);

	printf("{\n");
	printf("\"ajax_status\":   \"SUCCESS\",\n");
	printf("\"ajax_message\":  \"There be Whales here Captain!\",\n");
	printf("\"cm_ticket_no\":  \"%s\",\n", $tic->cm_ticket_no);
	printf("\"ticket_no\":     \"%s\",\n", $tic->ticket_no);
	printf("\"work_activity\": \"%s\",\n", $tic->work_activity);
	printf("\"owner_name\":    \"%s\",\n", $tic->owner_name);
	printf("\"respond_by\":    \"%s\",\n", $respond_by_date);
	printf("\"rows\": [\n");
}

$count_records = 0;

foreach ($no_response as $key => $node)
{
	if ($count_records > 0)
	{
		printf(",\n");  // Let the client know that another record is coming.
	}

	$row['Hostname']            = $node->hostname;
	$row['Work Start']          = $node->system_work_start_date;
	$row['Work End']            = $node->system_work_end_date;
	$row['Work Duration']       = $node->system_work_duration;
	$row['Net-Pin']             = $node->netpin;$node->netpin;
	$row['Work Group']          = $node->contact_work_group;
	$row['Connection']          = $node->contact_connection;
	$row['Apps/DBs']            = $node->contact_apps_databases;
	$row['Contact Email List']  = $node->email_list;

	echo json_encode($row);

	$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "SENDING: %s", json_encode($row));

	$count_records++;  // Count up the records to determine if we sent anything
}

printf("]}\n");  // Close out the data stream
exit();