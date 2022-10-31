<?php
/**
 * ajax_jqgrid_toolbar_open_approve_all.php
 *
 * @package   PhpStorm
 * @file      ajax_jqgrid_toolbar_open_approve_all.php
 * @author    gparkin
 * @date      6/29/16
 * @version   7.0
 *
 * @brief     Ajax call to this module will approve all server work for a given ticket.
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

if (isset($_SESSION['local_timezone_name']))
	$tz = $_SESSION['local_timezone_name'];
else
	$tz = 'America/Denver';

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
$lib->debug_start('ajax_jqgrid_toolbar_open_approve_all.html');
date_default_timezone_set('America/Denver');

$ora = new oracle();         // classes/oracle.php
$con = new cct7_contacts();  // classes/cct7_contacts.php

// Read-only stream allows you to read raw data from the request body.
$input = json_decode(file_get_contents("php://input"));

$lib->debug1(  __FILE__, __FUNCTION__, __LINE__, "input: ");
$lib->debug_r1(__FILE__, __FUNCTION__, __LINE__, $input);
$lib->debug1(  __FILE__, __FUNCTION__, __LINE__, "_POST: ");
$lib->debug_r1(__FILE__, __FUNCTION__, __LINE__, $_POST);
$lib->debug1(  __FILE__, __FUNCTION__, __LINE__, "_GET: ");
$lib->debug_r1(__FILE__, __FUNCTION__, __LINE__, $_GET);
$lib->debug1(  __FILE__, __FUNCTION__, __LINE__, "_REQUEST: ");
$lib->debug_r1(__FILE__, __FUNCTION__, __LINE__, $_REQUEST);
$lib->debug1(  __FILE__, __FUNCTION__, __LINE__, "_SERVER: ");
$lib->debug_r1(__FILE__, __FUNCTION__, __LINE__, $_SERVER);
$lib->debug1(  __FILE__, __FUNCTION__, __LINE__, "_SESSION: ");
$lib->debug_r1(__FILE__, __FUNCTION__, __LINE__, $_SESSION);

$user_cuid = $_SESSION['user_cuid'];
$ticket_no = $input->{'ticket_no'};

$lib->debug1(__FILE__, __FUNCTION__, __LINE__,   "ticket_no      = %s", $ticket_no);
$lib->debug1(__FILE__, __FUNCTION__, __LINE__,   "user_cuid      = %s", $user_cuid);

$groups      = isset($_SESSION['user_groups']) ? $_SESSION['user_groups'] : '';
$user_groups = explode(',', $groups);

$sql_clause = '';

foreach ($user_groups as $group_pin)
{
	if (strlen($sql_clause) == 0)
	{
		$sql_clause = "'" . $group_pin . "'";
	}
	else
	{
		$sql_clause .= ",'" . $group_pin . "'";
	}
}

if (strlen($sql_clause) > 0)
{
	$query  = "select ";
	$query .= "  t.ticket_no, ";
	$query .= "  s.system_id, ";
	$query .= "  s.system_hostname, ";
	$query .= "  c.contact_netpin_no ";
	$query .= "from ";
	$query .= "    cct7_tickets  t, ";
	$query .= "    cct7_systems  s, ";
	$query .= "    cct7_contacts c ";
	$query .= "  where ";
	$query .= "    t.ticket_no = '" . $ticket_no . "' and ";
	$query .= "    s.ticket_no = t.ticket_no and ";
	$query .= "    c.system_id = s.system_id and ";
	$query .= "    c.contact_netpin_no IN (" . $sql_clause . ") ";

	if ($ora->sql2($query) == false)
	{
		$lib->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $ora->sql_statement);
		$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $ora->dbErrMsg);
		$json['ajax_status']  = 'FAILED';
		$json['ajax_message'] = $ora->dbErrMsg;
		echo json_encode($json);
		exit();
	}

	$count = 0;

	while ($ora->fetch())
	{
		++$count;

		if ($con->approve((int)$ora->system_id, $ora->contact_netpin_no) == false)
		{
			$json['ajax_status']  = 'FAILED';
			$json['ajax_message'] = $con->error;
			echo json_encode($json);
			exit();
		}
	}
}

$json['ajax_status']  = 'SUCCESS';
$json['ajax_message'] = sprintf("%s: Approved work for %s groups and %d servers.",
								$ticket_no, $user_groups, $count);
echo json_encode($json);

exit();


