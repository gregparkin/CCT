<?php
/**
 * ajax_get_status_info.php
 *
 * @package   PhpStorm
 * @file      ajax_get_status_info.php
 * @author    gparkin
 * @date      8/26/16
 * @version   7.0
 *
 * @brief     Return ticket and system status information that will be used to update grids in toolbar_open.php
 *
 * @brief     Incoming JSON data sent from toolbar_open.php
 *            ticket_no:
 *            system_id:
 *
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
$lib = new library();       // classes/library.php
$lib->debug_start('ajax_get_status_info.html');
date_default_timezone_set('America/Denver');

$tic = new cct7_tickets();  // classes/cct7_tickets.php
$sys = new cct7_systems();  // classes/cct7_systems.php

$ticket_no         = '';
$system_id         = 0;

if (isset($_GET['ticket_no']))
	$ticket_no = $_GET['ticket_no'];

if (isset($_GET['system_id']))
	$system_id = $_GET['system_id'];

$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "ticket_no = %s",         $ticket_no);
$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "system_id = %s",         $system_id);

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

$json = array();

//
// Get the ticket status from cct7_tickets for $ticket_no
//
if ($tic->getTicket($ticket_no) == false)
{
	$json['ajax_status']   = "REFRESH";
	$json['ajax_message']  = $tic->error;
	echo json_encode($json);
	exit();
}

//
// Get the system status from cct7_systems for $system_id
//
if ($system_id > 0)
{
	if ($sys->getSystem($system_id) == false)
	{
		$json['ajax_status']   = "FAILED";
		$json['ajax_message']  = $sys->error;
		echo json_encode($json);
		exit();
	}
}

$json['ajax_status']               = 'SUCCESS';
$json['ajax_message']              = '';

if ($tic->schedule_start_date_num == 0)
{
	$schedule_start_date_char = "(See Remedy)";
	$schedule_end_date_char   = "(See Remedy)";
}
else
{
	$schedule_start_date_char = $tic->schedule_start_date_char;
	$schedule_end_date_char   = $tic->schedule_end_date_char;
}

//
// Here is the data from cct7_tickets for $ticket_no
//
$json['ticket_no']                    = $tic->ticket_no;
$json['status']                       = $tic->status;
$json['schedule_start_date']          = $schedule_start_date_char;
$json['schedule_end_date']            = $schedule_end_date_char;
$json['total_servers_scheduled']      = $tic->total_servers_scheduled;
$json['total_servers_waiting']        = $tic->total_servers_waiting;
$json['total_servers_approved']       = $tic->total_servers_approved;
$json['total_servers_rejected']       = $tic->total_servers_rejected;
$json['total_servers_not_scheduled']  = $tic->total_servers_not_scheduled;

//
// Here is the data from cct7_systems for $system_id
//
if ($system_id > 0)
{
	if ($sys->system_work_start_date_num == 0)
	{
		$system_work_start_date       = "(See Remedy)";
		$system_work_end_date         = "(See Remedy)";
		$system_work_duration         = "(See Remedy)";
	}
	else
	{
		$system_work_start_date       = $sys->system_work_start_date_char;
		$system_work_end_date         = $sys->system_work_end_date_char;
		$system_work_duration         = $sys->system_work_duration;
	}

	$json['system_id']                    = $sys->system_id;
	$json['system_work_start_date']       = $system_work_start_date;
	$json['system_work_end_date']         = $system_work_end_date;
	$json['system_work_duration']         = $system_work_duration;
	$json['system_work_status']           = $sys->system_work_status;
	$json['total_contacts_responded']     = $sys->total_contacts_responded;
	$json['total_contacts_not_responded'] = $sys->total_contacts_not_responded;
}

$lib->debug1(  __FILE__, __FUNCTION__, __LINE__, "json: ");
$lib->debug_r1(__FILE__, __FUNCTION__, __LINE__, $json);

echo json_encode($json);

exit();


