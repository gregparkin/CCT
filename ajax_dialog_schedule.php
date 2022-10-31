<?php
/**
 * ajax_dialog_schedule.php
 *
 * @package   PhpStorm
 * @file      ajax_dialog_schedule.php
 * @author    gparkin
 * @date      12/07/16
 * @version   7.0
 *
 * @brief     Returns all log messages for a list of servers.
 *            - dialog_schedule.php
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
// NOTE: It is very addant that you do not turn on debugging without writing to a file for server side AJAX code. Any HTML
//       comments coming from functions while writing JSON will show up in the JSON output and you will get a parsing error
//       in the client side program.
//
$lib = new library();        // classes/library.php
$lib->debug_start('ajax_dialog_schedule.html');
date_default_timezone_set('America/Denver');

// Read-only stream allows you to read raw data from the request body.
$input = json_decode(file_get_contents("php://input"), FALSE);
// $input = json_decode(json_encode($my_request), FALSE);

$action         = '';
$list_system_id = '';
$message        = '';

if (isset($input->{'action'}))
	$action         = $input->action;

if (isset($input->{'message'}))
	$message        = $input->message;

if (isset($input->{'list_system_id'}))
	$list_system_id = $input->{'list_system_id'};

$lib->debug1(__FILE__, __FUNCTION__, __LINE__,   "action = %s",     $action);
$lib->debug1(__FILE__, __FUNCTION__, __LINE__,   "message = %s",    $message);
$lib->debug1(__FILE__, __FUNCTION__, __LINE__,   "list_system_id: ");
$lib->debug_r1(__FILE__, __FUNCTION__, __LINE__, $list_system_id);

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

$json = array();

//
// Split the $list_system_id by any number of commas or space characters, which include " ", \r, \t, \n and \f
//
$list = preg_split("/[\s,]+/", $list_system_id);

function actionLog()
{
	global $lib, $json, $list;

	$sys = new cct7_systems();
	$con = new cct7_contacts();

	$log_entries              = '';
	$last_ticket_and_hostname = '';

	foreach ($list as $system_id)
	{
		$top = $sys->getLogSystem($system_id);

		for ($p = $top; $p != null; $p = $p->next)
		{
			$ticket_and_hostname = $p->ticket_no . " " . $p->hostname;

			if ($last_ticket_and_hostname != $ticket_and_hostname)
			{
				$last_ticket_and_hostname = $ticket_and_hostname;

				$log_entries .= sprintf("\n====================================================================================================\n");
				$log_entries .= sprintf("\n%s\n", $ticket_and_hostname);
			}

			// cct7_log_systems
			// ticket_no|VARCHAR2|20|NOT NULL|FOREIGN KEY - cct7_tickets.ticket_no - CASCADE DELETE
			// system_id|NUMBER|0|NOT NULL|FOREIGN KEY - cct7_systems.system_id - CASCADE DELETE
			// hostname|VARCHAR2|255|NOT NULL|Hostname for this log entry
			// event_date|NUMBER|0||Event Date (GMT)
			// event_cuid|VARCHAR2|20||Event Owner CUID
			// event_name|VARCHAR2|200||Event Owner Name
			// event_type|VARCHAR2|20||Event type
			// event_message|VARCHAR2|4000||Event message
			// sendmail_date|NUMBER|0||Date when this log message was sent to users

			$log_entries .= sprintf("\n%-17s %s\n%-17s %s\n",
									$p->event_date_char, $p->event_name,
									$p->event_type, $p->event_message);
		}

		$top_contacts = $con->getContacts($system_id);

		for ($pcon = $top_contacts; $pcon != null; $pcon = $pcon->next)
		{
			unset($top);

			$top = $con->getLogContacts($system_id, $pcon->contact_netpin_no);

			for ($p = $top; $p != null; $p = $p->next)
			{
				// cct7_log_contacts
				// ticket_no|VARCHAR2|20|NOT NULL|CCT ticket number.
				// system_id|NUMBER|0|NOT NULL|FOREIGN KEY - cct7_systems.system_id - CASCADE DELETE
				// hostname|VARCHAR2|255|NOT NULL|Hostname for this log entry
				// netpin_no|VARCHAR2|20|NOT NULL|CSC/NET Pin No.
				// event_date|NUMBER|0||Event Date (GMT)
				// event_cuid|VARCHAR2|20||Event Owner CUID
				// event_name|VARCHAR2|200||Event Owner Name
				// event_type|VARCHAR2|20||Event type
				// event_message|VARCHAR2|4000||Event message
				// sendmail_date|NUMBER|0||Date when this log message was sent to users

				$log_entries .= sprintf("\n%-17s %s\n%-17s %s\n",
										$p->event_date_char, $p->event_name,
										$p->event_type, $p->event_message);
			}
		}
	}

	$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $log_entries);

	$json['ajax_status']  = 'SUCCESS';
	$json['ajax_message'] = $log_entries;
	echo json_encode($json);
	exit();
}

function actionStarting()
{
	global $sys, $lib, $json, $list, $message;

	$sys = new cct7_systems();

	if (strlen($message) == 0)
	{
		$message = "READY-STARTING";
	}
	else
	{
		$message = sprintf("READY-STARTING - %s", $message);
	}

	foreach ($list as $system_id)
	{
		$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "system_id = %d", $system_id);

		if ($sys->starting($system_id) == false)
		{
			$json['ajax_status']  = 'FAILED';
			$json['ajax_message'] = $sys->error;
			echo json_encode($json);
			exit();
		}

		if ($sys->page($system_id, $message) == false)
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

function actionSuccess()
{
	global $sys, $lib, $json, $list, $message;

	$sys = new cct7_systems();

	if (strlen($message) == 0)
	{
		$message = "READY-SUCCESS";
	}
	else
	{
		$message = sprintf("READY-SUCCESS - %s", $message);
	}

	foreach ($list as $system_id)
	{
		$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "system_id = %d", $system_id);

		if ($sys->success($system_id) == false)
		{
			$json['ajax_status']  = 'FAILED';
			$json['ajax_message'] = $sys->error;
			echo json_encode($json);
			exit();
		}

		if ($sys->page($system_id, $message) == false)
		{
			$json['ajax_status']  = 'FAILED';
			$json['ajax_message'] = $sys->error;
			echo json_encode($json);
			exit();
		}
	}

	// $command = sprintf("%s -oprty=4 -t %s -f %s -m \"%s\"", $fastpg, $to_net_group_pin, $from_cuid, $message);

	$json['ajax_status']  = 'SUCCESS';
	$json['ajax_message'] = sprintf("Work success notifications have been sent.");
	echo json_encode($json);

	exit();
}

function actionFailed()
{
	global $sys, $lib, $json, $list, $message;

	$sys = new cct7_systems();

	if (strlen($message) == 0)
	{
		$message = "READY-FAILED";
	}
	else
	{
		$message = sprintf("READY-FAILED - %s", $message);
	}

	foreach ($list as $system_id)
	{
		$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "system_id = %d", $system_id);

		if ($sys->failed($system_id) == false)
		{
			$json['ajax_status']  = 'FAILED';
			$json['ajax_message'] = $sys->error;
			echo json_encode($json);
			exit();
		}

		if ($sys->page($system_id, $message) == false)
		{
			$json['ajax_status']  = 'FAILED';
			$json['ajax_message'] = $sys->error;
			echo json_encode($json);
			exit();
		}
	}

	// $command = sprintf("%s -oprty=4 -t %s -f %s -m \"%s\"", $fastpg, $to_net_group_pin, $from_cuid, $message);

	$json['ajax_status']  = 'SUCCESS';
	$json['ajax_message'] = sprintf("Work failed notifications have been sent.");
	echo json_encode($json);

	exit();
}

function actionCanceled()
{
	global $sys, $lib, $json, $list, $message;

	$sys = new cct7_systems();

	if (strlen($message) == 0)
	{
		$message = "READY-CANCELED";
	}
	else
	{
		$message = sprintf("READY-CANCELED - %s", $message);
	}

	foreach ($list as $system_id)
	{
		$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "system_id = %d", $system_id);

		if ($sys->cancel($system_id) == false)
		{
			$json['ajax_status']  = 'FAILED';
			$json['ajax_message'] = $sys->error;
			echo json_encode($json);
			exit();
		}

		if ($sys->page($system_id, $message) == false)
		{
			$json['ajax_status']  = 'FAILED';
			$json['ajax_message'] = $sys->error;
			echo json_encode($json);
			exit();
		}
	}

	// $command = sprintf("%s -oprty=4 -t %s -f %s -m \"%s\"", $fastpg, $to_net_group_pin, $from_cuid, $message);

	$json['ajax_status']  = 'SUCCESS';
	$json['ajax_message'] = sprintf("Work failed notifications have been sent.");
	echo json_encode($json);

	exit();
}

switch ($action)
{
	case "log":
		actionLog();
		break;
	case "starting":
		actionStarting();
		break;
	case "success":
		actionSuccess();
		break;
	case "failed":
		actionFailed();
		break;
	case "canceled":
		actionCanceled();
		break;
	default:
		$json['ajax_status']  = 'FAILED';
		$json['ajax_message'] = sprintf("Syntax error: unknown action request: %s", $action);
		echo json_encode($json);
		exit();
		break;
}
