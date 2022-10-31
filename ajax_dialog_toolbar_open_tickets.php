<?php
/**
 * ajax_dialog_toolbar_open_tickets.php
 *
 * @package   PhpStorm
 * @file      ajax_dialog_toolbar_open_tickets.php
 * @author    gparkin
 * @date      7/1/16
 * @version   7.0
 *
 * @brief     Called by ajax request from dialog_ticket.php
 * @brief     Performs the following operations:
 *            [get, refresh, activate, cancel, delete, freeze, unfreeze]
 *            action = get       Retrieve ticket information from cct7_tickets
 *            action = refresh   Refresh ticket from Remedy into cct7_tickets and return the information
 *            action = activate  Change ticket_status from DRAFT to ACTIVE
 *            action = cancel    Change ticket_status from ACTIVE to CANCELED
 *            action = delete    Delete the ticket from cct7_tickets along with all cct7_systems and cct7_contacts records
 *            action = freeze    Change ticket_status from ACTIVE to FROZEN
 *            action = unfreeze  Change ticket_status from FROZEN to ACTIVE
 *
 * @brief     All operations are performed by class: cct7_tickets.php
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
$lib->debug_start('ajax_dialog_toolbar_open_tickets.html');
date_default_timezone_set('America/Denver');

$tic = new cct7_tickets();   // classes/cct7_tickets.php

$rows_affected = 0;          // Number of rows affectec by update.

// Read-only stream allows you to read raw data from the request body.
$input = json_decode(file_get_contents("php://input"));

//
// action values:
//   get       - Get ticket info from cct7_tickets and return the data.
//   refresh   - Refresh data from Remedy into cct7_tickets and return the data.
//   activate  - Activate this DRAFT ticket.
//   cancel    - Cancel this ACTIVE ticket.
//   delete    - Delete this DRAFT ticket.
//   freeze    - Freeze this ACTIVE ticket. (Lock, no more updates)
//   unfreeze  - Unfreeze this FROZEN ticket. (Unlock, allow more updates)
//
$action               = '';
$ticket_no            = '';
$log_entry            = '';
$email_cc             = '';
$email_bcc            = '';
$subject_line         = '';
$message_body         = '';

$cm_ticket_no         = '';
$work_description     = '';
$work_implementation  = '';
$work_backoff_plan    = '';
$work_business_reason = '';
$work_user_impact     = '';

if (isset($input->{'action'}))
    $action               = $input->{'action'};

if (isset($input->{'ticket_no'}))
    $ticket_no            = $input->{'ticket_no'};

if (isset($input->{'log_entry'}))
	$log_entry            = $input->{'log_entry'};

if (isset($input->{'email_cc'}))
	$email_cc             = $input->{'email_cc'};

if (isset($input->{'email_bcc'}))
	$email_bcc            = $input->{'email_bcc'};

if (isset($input->{'subject_line'}))
	$subject_line         = $input->{'subject_line'};

if (isset($input->{'message_body'}))
	$message_body         = $input->{'message_body'};

if (isset($input->{'cm_ticket_no'}))
	$cm_ticket_no         = $input->{'cm_ticket_no'};

if (isset($input->{'work_description'}))
	$work_description     = $input->{'work_description'};

if (isset($input->{'work_implementation'}))
	$work_implementation  = $input->{'work_implementation'};

if (isset($input->{'work_backoff_plan'}))
	$work_backoff_plan    = $input->{'work_backoff_plan'};

if (isset($input->{'work_business_reason'}))
	$work_business_reason = $input->{'work_business_reason'};

if (isset($input->{'work_user_impact'}))
	$work_user_impact     = $input->{'work_user_impact'};

$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "action = %s",               $action);
$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "ticket_no = %s",            $ticket_no);
$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "log_entry = %s",            $log_entry);
$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "email_cc = %s",             $email_cc);
$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "email_bcc = %s",            $email_bcc);
$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "subject_line = %s",         $subject_line);
$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "message_body = %s",         $message_body);
$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "cm_ticket_no = %s",         $cm_ticket_no);
$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "work_description = %s",     $work_description);
$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "work_implementation = %s",  $work_implementation);
$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "work_backoff_plan = %s",    $work_backoff_plan);
$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "work_business_reason = %s", $work_business_reason);
$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "work_user_impact = %s",     $work_user_impact);

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
	global $tic, $lib, $ticket_no, $action, $rows_affected, $json;

	if ($tic->getTicket($ticket_no) == false)
	{
		$json['status']  = 'REFRESH';
		$json['message'] = $tic->error;
		echo json_encode($json);
		exit();
	}

	$json['ajax_status']                 = 'SUCCESS';

	$json['ajax_message']                = '';

	$json['rows_affected']               = $rows_affected;

	$json['ticket_no']                   = $tic->ticket_no;
	$json['insert_date']                 = $tic->insert_date_char;
	$json['insert_cuid']                 = $tic->insert_cuid;
	$json['insert_name']                 = $tic->insert_name;
	$json['update_date']                 = $tic->update_date_char;
	$json['update_cuid']                 = $tic->update_cuid;
	$json['update_name']                 = $tic->update_name;
	$json['status']                      = $tic->status;
	$json['status_date']                 = $tic->status_date_char;
	$json['status_cuid']                 = $tic->status_cuid;
	$json['status_name']                 = $tic->status_name;
	$json['owner_cuid']                  = $tic->owner_cuid;
	$json['owner_first_name']            = $tic->owner_first_name;
	$json['owner_name']                  = $tic->owner_name;
	$json['owner_email']                 = $tic->owner_email;
	$json['owner_job_title']             = $tic->owner_job_title;
	$json['manager_cuid']                = $tic->manager_cuid;
	$json['manager_first_name']          = $tic->manager_first_name;
	$json['manager_name']                = $tic->manager_name;
	$json['manager_email']               = $tic->manager_email;
	$json['manager_job_title']           = $tic->manager_job_title;
	$json['work_activity']               = $tic->work_activity;
	$json['approvals_required']          = $tic->approvals_required;
	$json['reboot_required']             = $tic->reboot_required;
	$json['email_reminder1_date']        = $tic->email_reminder1_date_char;
	$json['email_reminder2_date']        = $tic->email_reminder2_date_char;
	$json['email_reminder3_date']        = $tic->email_reminder3_date_char;
	$json['respond_by_date']             = $tic->respond_by_date_char;

	if ($tic->schedule_start_date_num == 0)
	{
		$json['schedule_start_date']         = "(See Remedy)";
		$json['schedule_end_date']           = "(See Remedy)";
	}
	else
	{
		$json['schedule_start_date']         = $tic->schedule_start_date_char;
		$json['schedule_end_date']           = $tic->schedule_end_date_char;
	}

	$json['work_description']            = $tic->work_description;
	$json['work_implementation']         = $tic->work_implementation;
	$json['work_backoff_plan']           = $tic->work_backoff_plan;
	$json['work_business_reason']        = $tic->work_business_reason;
	$json['work_user_impact']            = $tic->work_user_impact;
	$json['cm_ticket_no']                = $tic->cm_ticket_no;

	if ($tic->remedy_cm_start_date_num == 0)
	{
		$json['remedy_cm_start_date']        = "(See Remedy)";
		$json['remedy_cm_end_date']          = "(See Remedy)";
	}
	else
	{
		$json['remedy_cm_start_date']        = $tic->remedy_cm_start_date_char;
		$json['remedy_cm_end_date']          = $tic->remedy_cm_end_date_char;
	}

	$json['total_servers_scheduled']     = $tic->total_servers_scheduled;
	$json['total_servers_waiting']       = $tic->total_servers_waiting;
	$json['total_servers_approved']      = $tic->total_servers_approved;
	$json['total_servers_rejected']      = $tic->total_servers_rejected;
	$json['total_servers_not_scheduled'] = $tic->total_servers_not_scheduled;
	$json['servers_not_scheduled']       = $tic->servers_not_scheduled;
	$json['generator_runtime']           = $tic->generator_runtime;

	$lib->debug1(  __FILE__, __FUNCTION__, __LINE__, "json: ");
	$lib->debug_r1(__FILE__, __FUNCTION__, __LINE__, $json);

	$top         = $tic->getLogTicket($ticket_no);
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
// Approve all servers where this user is a contact
//
if ($action == "approve_group_with_paging")
{

	if (!isset($_SESSION['user_cuid']))
	{
		$json['ajax_status']  = 'FAILED';
		$json['ajax_message'] = 'Missing user_cuid in _SESSION array.';
		echo json_encode($json);
		exit();
	}

	if ($tic->approveAllByCUID($ticket_no, $_SESSION['user_cuid'], "Y") == false)
	{
		$json['ajax_status']  = 'FAILED';
		$json['ajax_message'] = $tic->error;
		echo json_encode($json);
		exit();
	}

	$rows_affected = $tic->rows_affected;
	get();
}

if ($action == "approve_group_no_paging")
{
	if (!isset($_SESSION['user_cuid']))
	{
		$json['ajax_status']  = 'FAILED';
		$json['ajax_message'] = 'Missing user_cuid in _SESSION array.';
		echo json_encode($json);
		exit();
	}

	if ($tic->approveAllByCUID($ticket_no, $_SESSION['user_cuid'], "N") == false)
	{
		$json['ajax_status']  = 'FAILED';
		$json['ajax_message'] = $tic->error;
		echo json_encode($json);
		exit();
	}

	$rows_affected = $tic->rows_affected;
	get();
}

if ($action == "approve_servers_with_paging")
{
	if ($tic->approveAllServers($ticket_no, "Y") == false)
	{
		$json['ajax_status']  = 'FAILED';
		$json['ajax_message'] = $tic->error;
		echo json_encode($json);
		exit();
	}

	$rows_affected = $tic->rows_affected;
	get();
}

if ($action == "approve_servers_no_paging")
{
	if ($tic->approveAllServers($ticket_no, "N") == false)
	{
		$json['ajax_status']  = 'FAILED';
		$json['ajax_message'] = $tic->error;
		echo json_encode($json);
		exit();
	}

	$rows_affected = $tic->rows_affected;
	get();
}

if ($action == "activate") // Activate this DRAFT ticket.
{
 	if ($tic->activate($ticket_no) == false)
	{
		$json['ajax_status']  = 'FAILED';
		$json['ajax_message'] = $tic->error;
		echo json_encode($json);
		exit();
	}

	$rows_affected = $tic->rows_affected;
    get();
}

if ($action == "cancel")   // Cancel this ACTIVE ticket.
{
	if ($tic->cancel($ticket_no) == false)
	{
		$json['ajax_status']  = 'FAILED';
		$json['ajax_message'] = $tic->error;
		echo json_encode($json);
		exit();
	}

	$rows_affected = $tic->rows_affected;
	get();
}

if ($action == "delete")   // Delete this DRAFT ticket.
{
    if ($tic->delete($ticket_no) == false)
	{
		$json['ajax_status']  = 'FAILED';
		$json['ajax_message'] = $tic->error;
		echo json_encode($json);
		exit();
	}

	$json['ajax_status']  = 'REFRESH';
	$json['ajax_message'] = 'Ticket: ' . $ticket_no . ' has been deleted.';
	echo json_encode($json);
	exit();
}

//
// Send email to everyone for this ticket.
//
if ($action == "sendmail_ticket_owner")
{
	if ($tic->sendmailTicketOwner($ticket_no, $subject_line, $email_cc, $email_bcc, $message_body) == false)
	{
		$json['ajax_status']  = 'FAILED';
		$json['ajax_message'] = $tic->error;
		echo json_encode($json);
		exit();
	}

	get();
}

//
// Send email to everyone for this ticket.
//
if ($action == "sendmail")
{
	if ($tic->sendmail($ticket_no, $subject_line, $email_cc, $email_bcc, $message_body, "N") == false)
	{
		$json['ajax_status']  = 'FAILED';
		$json['ajax_message'] = $tic->error;
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
	if ($tic->sendmail($ticket_no, $subject_line, $email_cc, $email_bcc, $message_body, "Y") == false)
	{
		$json['ajax_status']  = 'FAILED';
		$json['ajax_message'] = $tic->error;
		echo json_encode($json);
		exit();
	}

	get();
}

if ($action == "log")
{
	if ($tic->getTicket($ticket_no) == false)
	{
		$json['ajax_status']  = 'REFRESH';
		$json['ajax_message'] = $tic->error;
		echo json_encode($json);
		exit();
	}

	if ($tic->putLogTicket($ticket_no, "NOTE", $log_entry) == false)
	{
		$json['ajax_status']  = 'FAILED';
		$json['ajax_message'] = $tic->error;
		echo json_encode($json);
		exit();
	}

	get();
}

if ($action == "update")
{
	$ora = new oracle();

	$user_cuid = $_SESSION['user_cuid'];
	$user_name = $_SESSION['user_name'];

	$rc = $ora
		->update("cct7_tickets")
		->set("int",  "update_date",          $lib->now_to_gmt_utime())
		->set("char", "update_cuid",          $user_cuid)
		->set("char", "update_name",          $user_name)
		->set("char", "cm_ticket_no",         $cm_ticket_no)
		->set("char", "work_description",     $work_description)
		->set("char", "work_implementation",  $work_implementation)
		->set("char", "work_backoff_plan",    $work_backoff_plan)
		->set("char", "work_business_reason", $work_business_reason)
		->set("char", "work_user_impact",     $work_user_impact)
		->where("char", "ticket_no", "=", $ticket_no)
		->execute();

	if ($rc == false)
	{
		$lib->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $ora->sql_statement);
		$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $ora->dbErrMsg);
		$json['ajax_status']  = 'FAILED';
		$json['ajax_message'] = $ora->dbErrMsg;
		echo json_encode($json);
		exit();
	}

	$ora->commit();

	if ($tic->putLogTicket($ticket_no, "NOTE", "Ticket information has been updated.") == false)
	{
		$json['ajax_status']  = 'FAILED';
		$json['ajax_message'] = $tic->error;
		echo json_encode($json);
		exit();
	}

	get();
}

//
// Copy text from Remedy ticket to this CCT ticket
//
if ($action == "copy_to")
{
	$ora = new oracle();

	//
	// Get the remedy ticket
	//
	if ($ora->sql("select * from t_cm_implementation_request@remedy_prod where change_id = '" . $cm_ticket_no . "'") == false)
	{
		$lib->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $ora->sql_statement);
		$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $ora->dbErrMsg);
		$json['ajax_status']  = 'FAILED';
		$json['ajax_message'] = $ora->dbErrMsg;
		echo json_encode($json);
		exit();
	}

	if ($ora->fetch() == false)
	{
		$message = "Unable to pull Remedy ticket: " . $cm_ticket_no;
		$lib->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $ora->sql_statement);
		$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $message);
		$json['ajax_status']  = 'FAILED';
		$json['ajax_message'] = $message;
		echo json_encode($json);
		exit();
	}

	$user_cuid = $_SESSION['user_cuid'];
	$user_name = $_SESSION['user_name'];

	$rc = $ora
		->update("cct7_tickets")
		->set("int",  "update_date",          $lib->now_to_gmt_utime())
		->set("char", "update_cuid",          $user_cuid)
		->set("char", "update_name",          $user_name)
		->set("char", "cm_ticket_no",         $cm_ticket_no)
		->set("char", "work_description",     $ora->description)
		->set("char", "work_implementation",  $ora->implementation_instructions)
		->set("char", "work_backoff_plan",    $ora->backoff_plan)
		->set("char", "work_business_reason", $ora->business_reason)
		->set("char", "work_user_impact",     $ora->impact)
		->where("char", "ticket_no", "=", $ticket_no)
		->execute();

	if ($rc == false)
	{
		$lib->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $ora->sql_statement);
		$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $ora->dbErrMsg);
		$json['ajax_status']  = 'FAILED';
		$json['ajax_message'] = $ora->dbErrMsg;
		echo json_encode($json);
		exit();
	}

	$ora->commit();

	if ($tic->putLogTicket($ticket_no, "NOTE", "Ticket information has been updated.") == false)
	{
		$json['ajax_status']  = 'FAILED';
		$json['ajax_message'] = $tic->error;
		echo json_encode($json);
		exit();
	}

	get();
}

/**
 * NOT USED !!!
 */
if ($action == "get_remedy_ticket")
{
	$ora = new oracle();

	$user_tz = "America/Denver";

	if (isset($_SESSION['remedy_timezone_name']) && strlen($_SESSION['remedy_timezone_name']) > 0)
	{
		$user_tz = $_SESSION['remedy_timezone_name'];
	}

	//
	// Get the remedy ticket
	//
	if ($ora->sql("select * from t_cm_implementation_request@remedy_prod where change_id = '" . $cm_ticket_no . "'") == false)
	{
		$lib->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $ora->sql_statement);
		$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $ora->dbErrMsg);
		$json['ajax_status']  = 'FAILED';
		$json['ajax_message'] = $ora->dbErrMsg;
		echo json_encode($json);
		exit();
	}

	if ($ora->fetch() == false)
	{
		$message = "Unable to pull Remedy ticket: " . $cm_ticket_no;
		$lib->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $ora->sql_statement);
		$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $message);
		$json['ajax_status']  = 'FAILED';
		$json['ajax_message'] = $message;
		echo json_encode($json);
		exit();
	}

	$json['ajax_status']           = 'SUCCESS';
	$json['ajax_message']          = '';
	$json['cm_ticket_no']          = $cm_ticket_no;
	$json['work_description']      = $ora->description;
	$json['work_implementation']   = $ora->implementation_instructions;
	$json['work_backoff_plan']     = $ora->backoff_plan;
	$json['work_business_reason']  = $ora->business_reason;
	$json['work_user_impact']      = $ora->impact;
	$json['cm_start_date']         = $ora->start_date;
	$json['cm_end_date']           = $ora->end_date;
	$json['cm_duration_computed']  = $ora->duration_computed;
	$json['cm_ipl_boot']           = $ora->ipl_boot;
	$json['cm_status']             = $ora->status;
	$json['cm_open_closed']        = $ora->open_closed;
	$json['cm_close_date']         = $ora->close_date;
	$json['cm_owner_first_name']   = $ora->owner_first_name;
	$json['cm_owner_last_name']    = $ora->owner_last_name;
	$json['cm_owner_cuid']         = $ora->owner_cuid;
	$json['cm_owner_group']        = $ora->owner_group;

	echo json_encode($json);
	exit();
}

get();
