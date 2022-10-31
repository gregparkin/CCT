<?php
/**
 * ajax_toolbar_schedule.php
 *
 * @package   PhpStorm
 * @file      ajax_toolbar_schedule.php
 * @author    gparkin
 * @date      11/10/16
 * @version   7.0
 *
 * @brief     Called by ajax request from toolbar_schedule.php
 * @brief     Used to perform status changes for selected servers.
 *            Buttons: Starting, Success, Failed, Canceled, Email, Page
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
$lib = new library();        // classes/library.php
$lib->debug_start('ajax_toolbar_schedule.html');
date_default_timezone_set('America/Denver');

// Read-only stream allows you to read raw data from the request body.
$input = json_decode(file_get_contents("php://input"), FALSE);
// $input = json_decode(json_encode($my_request), FALSE);

$action = "";
$system_id_list = "";

if (isset($input->{'action'}))
	$action = $input->action;

if (isset($input->{'system_id_list'}))
	$system_id_list = $input->system_id_list;

$lib->debug1(__FILE__, __FUNCTION__, __LINE__,   "action         = %s", $action);
//$lib->debug1(__FILE__, __FUNCTION__, __LINE__,   "system_id_list = %s", $system_id_list);

$lib->debug1(  __FILE__, __FUNCTION__, __LINE__, "system_id_list: ");
$lib->debug_r1(__FILE__, __FUNCTION__, __LINE__, $system_id_list);

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

if ($system_id_list == "")
{
	$json['ajax_status']  = 'FAILED';
	$json['ajax_message'] = sprintf("No system_ids past to ajax command. Please contact Greg.");
	echo json_encode($json);
	exit();
}

$sys = new cct7_systems();

/**
 * @fn    starting()
 *
 * @brief Mark selected servers (system_id_lists) status as starting.
 *        Page on-call clients desiring page notification.
 *        Email clients desiring email notification.
 */
function starting()
{
	global $sys, $system_id_list;

	foreach ($system_id_list as $system_id)
	{
		if ($sys->starting($system_id) == false)
		{
			$json['ajax_status']  = 'FAILED';
			$json['ajax_message'] = $sys->error;
			echo json_encode($json);
			exit();
		}
	}

	// $command = sprintf("%s -oprty=4 -t %s -f %s -m \"%s\"", $fastpg, $to_net_group_pin, $from_cuid, $message);

	$json['ajax_status']  = 'SUCCESS';
	$json['ajax_message'] = sprintf("Work start notifications have been sent.");
	echo json_encode($json);

	exit();
}

/**
 * @fn    success()
 *
 * @brief Mark selected servers (system_id_lists) status as success.
 *        Page on-call clients desiring page notification.
 *        Email clients desiring email notification.
 */
function success()
{
	global $sys, $system_id_list;

	foreach ($system_id_list as $system_id)
	{
		if ($sys->success($system_id) == false)
		{
			$json['ajax_status']  = 'FAILED';
			$json['ajax_message'] = $sys->error;
			echo json_encode($json);
			exit();
		}
	}

	$json['ajax_status']  = 'SUCCESS';
	$json['ajax_message'] = sprintf("Success notifications have been sent.");
	echo json_encode($json);

	exit();
}

/**
 * @fn    failed()
 *
 * @brief Mark selected servers (system_id_lists) status as failed.
 *        Page on-call clients desiring page notification.
 *        Email clients desiring email notification.
 */
function failed()
{
	global $sys, $system_id_list;

	foreach ($system_id_list as $system_id)
	{
		if ($sys->failed($system_id) == false)
		{
			$json['ajax_status']  = 'FAILED';
			$json['ajax_message'] = $sys->error;
			echo json_encode($json);
			exit();
		}
	}

	$json['ajax_status']  = 'SUCCESS';
	$json['ajax_message'] = sprintf("Failure notifications have been sent.");
	echo json_encode($json);

	exit();
}

/**
 * @fn    canceled()
 *
 * @brief Mark selected servers (system_id_lists) status as canceled.
 *        Page on-call clients desiring page notification.
 *        Email clients desiring email notification.
 */
function canceled()
{
	global $sys, $system_id_list;

	foreach ($system_id_list as $system_id)
	{
		if ($sys->cancel($system_id) == false)
		{
			$json['ajax_status']  = 'FAILED';
			$json['ajax_message'] = $sys->error;
			echo json_encode($json);
			exit();
		}
	}

	$json['ajax_status']  = 'SUCCESS';
	$json['ajax_message'] = sprintf("Cancellation notifications have been sent.");
	echo json_encode($json);

	exit();
}

/**
 * @fn    email()
 *
 * @brief Email clients for selected servers (system_id_lists).
 *
 *        NOT USED!
 */
function email()
{
	global $sys;

	//$sys->sendmail($system_id, $subject_line, $email_cc, $email_bcc, $message_body);

	$json['ajax_status']  = 'SUCCESS';
	$json['ajax_message'] = sprintf("Email have been sent.");
	echo json_encode($json);

	exit();
}

/**
 * @fn    page()
 *
 * @brief Page on-call clients for selected servers (system_id_lists).
 */
function page()
{

	$json['ajax_status']  = 'SUCCESS';
	$json['ajax_message'] = sprintf("Pages have been sent.");
	echo json_encode($json);

	exit();
}

switch ($action)
{
	case "starting":
		starting();
		break;
	case "success":
		success();
		break;
	case "failed":
		failed();
		break;
	case "canceled":
		canceled();
		break;
	case "email":
		email();
		break;
	case "page":
		page();
		break;
	default:
		break;
}

$json['ajax_status']  = 'FAILED';
$json['ajax_message'] = sprintf("Invalid ajax action request. Please contact Greg.");
echo json_encode($json);
exit();
