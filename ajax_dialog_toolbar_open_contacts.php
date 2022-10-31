<?php
/**
 * ajax_dialog_toolbar_open_contacts.php
 *
 * @package   PhpStorm
 * @file      ajax_dialog_toolbar_open_contacts.php
 * @author    gparkin
 * @date      08/23/16
 * @version   7.0
 *
 * @brief     Called by ajax request from dialog_toolbar_open_systems.php
 * @brief     Performs the following operations:
 *            [get, approve, reject, exempt, email, toggle_page, toggle_email, log]
 *            action = get
 *            action = approve
 *            action = reject
 *            action = exempt
 *            action = email
 *            action = toggle_page
 *            action = toggle_email
 *            action = log
 *
 *            // approve, reject, exempt, email, toggle_page, toggle_email
 * @brief     All operations are performed by class: cct7_systems.php
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
$lib->debug_start('ajax_dialog_toolbar_open_contacts.html');
date_default_timezone_set('America/Denver');

$con = new cct7_contacts();   // classes/cct7_contacts.php

// Read-only stream allows you to read raw data from the request body.
$input = json_decode(file_get_contents("php://input"));

//
// action values:
//   get          - Get ticket info from cct7_tickets and return the data.
//   refresh      - Refresh data from Remedy into cct7_tickets and return the data.
//   approve      - Approve work for this server.
//   reject       - Reject Work for this server.
//   email        - Spool email message to go out to all contacts for this server.
//   toggle_page  - Toggle Page On-Call: Y/N
//   toggle_email - Toggle Send Email: Y/N
//   log          - Log note
//
// [ APPROVE ] [ REJECT ] [ EXEMPT ] [ Email ] [ Toggle Page ] [ Toggle Email ] -->

$action            = '';
$ticket_no         = '';
$system_id         = 0;
$contact_netpin_no = '';

// action = log
$log_entry         = '';

// action = sendmail
$email_cc          = '';
$email_bcc         = '';
$subject_line      = '';
$message_body      = '';

if (isset($input->{'action'}))
    $action = $input->{'action'};

if (isset($input->{'ticket_no'}))
	$ticket_no = $input->{'ticket_no'};

if (isset($input->{'system_id'}))
    $system_id = $input->{'system_id'};

if (isset($input->{'contact_netpin_no'}))
    $contact_netpin_no = $input->{'contact_netpin_no'};

if (isset($input->{'log_entry'}))
	$log_entry = $input->{'log_entry'};

if (isset($input->{'email_cc'}))
	$email_cc = $input->{'email_cc'};

if (isset($input->{'email_bcc'}))
	$email_bcc = $input->{'email_bcc'};

if (isset($input->{'subject_line'}))
	$subject_line = $input->{'subject_line'};

if (isset($input->{'message_body'}))
	$message_body = $input->{'message_body'};

$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "action = %s",            $action);
$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "ticket_no = %s",         $ticket_no);
$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "system_id = %s",         $system_id);
$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "contact_netpin_no = %s", $contact_netpin_no);
$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "log_entry = %s",         $log_entry);
$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "email_cc = %s",          $email_cc);
$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "email_bcc = %s",         $email_bcc);
$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "subject_line = %s",      $subject_line);
$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "message_body = %s",      $message_body);

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

function get()
{
	global $lib, $tic, $con, $system_id, $contact_netpin_no;

	//
	// Get the contact record from cct7_contacts
	//
	if ($con->getContactNetpin($system_id, $contact_netpin_no) == false)
	{
		$json['ajax_status']  = 'FAILED';
		$json['ajax_message'] = $con->error;
		echo json_encode($json);
		exit();
	}

	/*
		$con->contact_id;
		$con->system_id;
		$con->contact_netpin_no;

		$con->contact_insert_date_num;
		$con->contact_insert_date_char;
		$con->contact_insert_cuid;
		$con->contact_insert_name;

		$con->contact_update_date_num;
		$con->contact_insert_date_char;
		$con->contact_update_cuid;
		$con->contact_update_name;

		$con->contact_connection;
		$con->contact_server_os;
		$con->contact_server_usage;
		$con->contact_work_group;
		$con->contact_approver_fyi;
		$con->contact_csc_banner;
		$con->contact_apps_databases;

		$con->contact_response_status;
		$con->contact_response_date;
		$con->contact_response_cuid;
		$con->contact_response_name;

		$con->contact_send_page;
		$con->contact_send_email;
	*/

	$json['ajax_status']               = 'SUCCESS';
	$json['ajax_message']              = '';

	$json['contact_netpin_no']         = $con->contact_netpin_no;
	$json['contact_respond_by_date']   = $con->contact_respond_by_date_char;
	$json['contact_response_date']     = $con->contact_response_date_char;
	$json['contact_response_status']   = $con->contact_response_status;
	$json['contact_response_name']     = $con->contact_response_name;
	$json['contact_send_page']         = $con->contact_send_page;
	$json['contact_send_email']        = $con->contact_send_email;

	$lib->debug1(  __FILE__, __FUNCTION__, __LINE__, "json: ");
	$lib->debug_r1(__FILE__, __FUNCTION__, __LINE__, $json);

	//
	// Return a list of contact netgroup members for this $contact_netpin_no
	//
	$top = $con->getNetGroupMembers($contact_netpin_no);

	$member_list = '';

	for ($p=$top; $p!=null; $p=$p->next)
	{
		if ($p->oncall_primary == 'Y' && $p->oncall_backup == 'Y')
		{
			$hold = sprintf("%s (P, B) %-12s", $contact_netpin_no, $p->mnet_cuid);
		}
		else if ($p->oncall_primary == 'Y')
		{
			$hold = sprintf("%s (P)    %-12s", $contact_netpin_no, $p->mnet_cuid);
		}
		else if ($p->oncall_backup == 'Y')
		{
			$hold = sprintf("%s (B)    %-12s", $contact_netpin_no, $p->mnet_cuid);
		}
		else
		{
			$hold = sprintf("%s        %-12s", $contact_netpin_no, $p->mnet_cuid);
		}

		$hold .= sprintf("%-40s %s\n", $p->mnet_name, $p->mnet_email);

		if (strlen($member_list) == 0)
		{
			$member_list = $hold;
		}
		else
		{
			$member_list .= $hold;
		}
	}

	$json['member_list'] = $member_list;

	unset($top);

	$top = $con->getLogContacts($system_id, $contact_netpin_no);
	$log_entries = '';

	for ($p=$top; $p!=null; $p=$p->next)
	{
		$hold = sprintf("%-17s %s\n%-17s %s\n",
						$p->event_date_char, $p->event_name,
						$p->event_type, $p->event_message);

		if (strlen($log_entries) == 0)
		{
			$log_entries = $hold;
		}
		else
		{
			$log_entries .= "\n" . $hold;
		}
	}

	$json['log_entries'] = $log_entries;

	echo json_encode($json);

    exit();
}

//
// [get, approve, reject, exempt, email, toggle_page, toggle_email, log]
//

if ($action == "approve_with_paging")
{
	$lib->debug1(  __FILE__, __FUNCTION__, __LINE__, "Action: %s", $action);

	$groups      = isset($_SESSION['user_groups']) ? $_SESSION['user_groups'] : '';
	$user_groups = explode(',', $groups);
	
	$okay = false;
	
	foreach ($user_groups as $group_name)
	{
		$lib->debug1(  __FILE__, __FUNCTION__, __LINE__,
					   "user_group: %s match? contact_netpin_no: %s",
					   $group_name, $contact_netpin_no);

		if ($group_name == $contact_netpin_no)
		{
			$lib->debug1(  __FILE__, __FUNCTION__, __LINE__, "MATCH!");
			$okay = true;
			break;
		}
	}
	
	if ($okay == false)
	{
		$lib->debug1(  __FILE__, __FUNCTION__, __LINE__,
					   "Sorry, but you are not authorized to approve this work.");
		$json['ajax_status']  = 'FAILED';
		$json['ajax_message'] = 'Sorry, but you are not authorized to approve this work.';
		echo json_encode($json);
		exit();
	}
	
	if ($con->approve($system_id, $contact_netpin_no, 'Y') == false)
	{
		$json['ajax_status']  = 'FAILED';
		$json['ajax_message'] = $con->error;
		echo json_encode($json);
		exit();
	}

	get();
}

if ($action == "approve_no_paging")
{
	$lib->debug1(  __FILE__, __FUNCTION__, __LINE__, "Action: %s", $action);

	$groups      = isset($_SESSION['user_groups']) ? $_SESSION['user_groups'] : '';
	$user_groups = explode(',', $groups);

	$okay = false;

	foreach ($user_groups as $group_name)
	{
		$lib->debug1(  __FILE__, __FUNCTION__, __LINE__,
					   "user_group: %s match? contact_netpin_no: %s",
					   $group_name, $contact_netpin_no);

		if ($group_name == $contact_netpin_no)
		{
			$lib->debug1(  __FILE__, __FUNCTION__, __LINE__, "MATCH!");
			$okay = true;
			break;
		}
	}

	if ($okay == false)
	{
		$lib->debug1(  __FILE__, __FUNCTION__, __LINE__,
					   "Sorry, but you are not authorized to approve this work.");
		$json['ajax_status']  = 'FAILED';
		$json['ajax_message'] = 'Sorry, but you are not authorized to approve this work.';
		echo json_encode($json);
		exit();
	}

	if ($con->approve($system_id, $contact_netpin_no, 'N') == false)
	{
		$json['ajax_status']  = 'FAILED';
		$json['ajax_message'] = $con->error;
		echo json_encode($json);
		exit();
	}

	get();
}

if ($action == "reject")
{
	$groups      = isset($_SESSION['user_groups']) ? $_SESSION['user_groups'] : '';
	$user_groups = explode(',', $groups);

	$okay = false;

	foreach ($user_groups as $group_name)
	{
		$lib->debug1(  __FILE__, __FUNCTION__, __LINE__,
					   "user_group: %s match? contact_netpin_no: %s",
					   $group_name, $contact_netpin_no);

		if ($group_name == $contact_netpin_no)
		{
			$lib->debug1(  __FILE__, __FUNCTION__, __LINE__, "MATCH!");
			$okay = true;
			break;
		}
	}

	if ($okay == false)
	{
		$lib->debug1(  __FILE__, __FUNCTION__, __LINE__,
					   'Sorry, but you are not authorized to reject this work.');
		$json['ajax_status']  = 'FAILED';
		$json['ajax_message'] = 'Sorry, but you are not authorized to reject this work.';
		echo json_encode($json);
		exit();
	}

	if ($con->reject($system_id, $contact_netpin_no) == false)
	{
		$json['ajax_status']  = 'FAILED';
		$json['ajax_message'] = $con->error;
		echo json_encode($json);
		exit();
	}

	get();
}

if ($action == "exempt")
{
	$groups      = isset($_SESSION['user_groups']) ? $_SESSION['user_groups'] : '';
	$user_groups = explode(',', $groups);

	$okay = false;

	foreach ($user_groups as $group_name)
	{
		$lib->debug1(  __FILE__, __FUNCTION__, __LINE__,
					   "user_group: %s match? contact_netpin_no: %s",
					   $group_name, $contact_netpin_no);

		if ($group_name == $contact_netpin_no)
		{
			$lib->debug1(  __FILE__, __FUNCTION__, __LINE__, "MATCH!");
			$okay = true;
			break;
		}
	}

	if ($okay == false)
	{
		$lib->debug1(  __FILE__, __FUNCTION__, __LINE__,
					   'Sorry, but you are not authorized to exempt this work.');
		$json['ajax_status']  = 'FAILED';
		$json['ajax_message'] = 'Sorry, but you are not authorized to exempt this work.';
		echo json_encode($json);
		exit();
	}

	if ($con->exempt($system_id, $contact_netpin_no) == false)
	{
		$json['ajax_status']  = 'FAILED';
		$json['ajax_message'] = $con->error;
		echo json_encode($json);
		exit();
	}

	get();
}

if ($action == 'toggle_page')
{
	if ($con->togglePageOncall($ticket_no, $system_id, $contact_netpin_no) == false)
	{
		$json['ajax_status']  = 'FAILED';
		$json['ajax_message'] = $con->error;
		echo json_encode($json);
		exit();
	}

	get();
}

if ($action == 'toggle_email')
{
	if ($con->toggleSendEmail($ticket_no, $system_id, $contact_netpin_no) == false)
	{
		$json['ajax_status']  = 'FAILED';
		$json['ajax_message'] = $con->error;
		echo json_encode($json);
		exit();
	}

	get();
}

//
// log - Log note
//
if ($action == "log")
{
	if ($con->log($system_id, $contact_netpin_no, $log_entry) == false)
	{
		$json['ajax_status']  = 'FAILED';
		$json['ajax_message'] = $con->error;
		echo json_encode($json);
		exit();
	}

	get();
}

//
// Send email to everyone for this ticket.
//
if ($action == "sendmail_ticket_owner")
{
	if ($con->sendmailTicketOwner($ticket_no, $subject_line, $email_cc, $email_bcc, $message_body) == false)
	{
		$json['ajax_status']  = 'FAILED';
		$json['ajax_message'] = $con->error;
		echo json_encode($json);
		exit();
	}

	get();
}

//
// sendmail
//
if ($action == "sendmail")
{
	if ($con->sendmail($ticket_no, $system_id, $contact_netpin_no, $subject_line, $email_cc, $email_bcc, $message_body, "N") == false)
	{
		$json['ajax_status']  = 'FAILED';
		$json['ajax_message'] = $con->error;
		echo json_encode($json);
		exit();
	}

	get();
}

//
// Send email only to those who have not responded (where contact_response_status = 'WAITING')
//
if ($action == "sendmail_not_responded")
{
	if ($con->sendmail($ticket_no, $system_id, $contact_netpin_no, $subject_line, $email_cc, $email_bcc, $message_body, "Y") == false)
	{
		$json['ajax_status']  = 'FAILED';
		$json['ajax_message'] = $sys->error;
		echo json_encode($json);
		exit();
	}

	get();
}

//
// Just get the record and exit.
//
get();

