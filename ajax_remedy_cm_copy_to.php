<?php
/**
 * ajax_remedy_cm_copy_to.php
 *
 * @package   PhpStorm
 * @file      ajax_remedy_cm_copy_to.php
 * @author    gparkin
 * @date      3/21/2017
 * @version   7.0
 *
 * @brief     Called by ajax request from toolbar_new.php  "Copy To" button.
 * @brief     Performs the following operations:
 *            action = copy_to   Retrieve Remedy CM ticket information for copy to operation.
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
$lib->debug_start('ajax_remedy_cm_copy_to.html');
date_default_timezone_set('America/Denver');

$rows_affected = 0;          // Number of rows affectec by update.

// Read-only stream allows you to read raw data from the request body.
$input = json_decode(file_get_contents("php://input"));

if (isset($input->{'cm_ticket_no'}))
	$cm_ticket_no         = $input->{'cm_ticket_no'};

$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "cm_ticket_no = %s", $cm_ticket_no);

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

//
// Make sure the Remedy CM ticket is not currently being used in another ACTIVE CCT ticket.
//


//
// Check to see if the ticket is opened or closed. (Open, Closed)
//
if ($ora->open_closed == "Closed")
{
	$message = sprintf("Remedy ticket: %s has been CLOSED and cannot be reused.", $cm_ticket_no);
	$message .= "Please create a new ticket.";
	$lib->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $ora->sql_statement);
	$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $message);
	$json['ajax_status']  = 'FAILED';
	$json['ajax_message'] = $message;
	echo json_encode($json);
	exit();
}

//
// Possible status: Returned, Approved, Pending, Turnover
//
if ($ora->status != "Returned" && $ora->status != "Pending" && $ora->status != "Turnover")
{
	$message = sprintf("Remedy ticket: %s has a status of %s and cannot be reused.",
					   $cm_ticket_no, $ora->status);
	$message .= "Please create a new ticket.";
	$lib->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $ora->sql_statement);
	$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $message);
	$json['ajax_status']  = 'FAILED';
	$json['ajax_message'] = $message;
	echo json_encode($json);
	exit();
}

$query  = "select ";
$query .= "  ticket_no ";
$query .= "from ";
$query .= "  cct7_tickets ";
$query .= "where ";
$query .= "  cm_ticket_no = '" . $cm_ticket_no . "' and ";
$query .= "  status = 'ACTIVE' ";
$query .= "order by ";
$query .= "  ticket_no";

if ($ora->sql($query) == false)
{
	$lib->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $ora->sql_statement);
	$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $ora->dbErrMsg);
	$json['ajax_status']  = 'FAILED';
	$json['ajax_message'] = $ora->dbErrMsg;
	echo json_encode($json);
	exit();
}

if ($ora->fetch())
{
	$message =
		sprintf("Remedy ticket: %s is currently in use by ACTIVE CCT ticket: %s. ",
				$cm_ticket_no, $ora->ticket_no);
	$message .=
		sprintf(
			"You must first cancel the work for CCT ticket: %s before you can reuse this Remedy ticket.",
			$ora->ticket_no);

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
