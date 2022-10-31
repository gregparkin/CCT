<?php
/**
 * ajax_dialog_ticket.php
 *
 * @package   PhpStorm
 * @file      ajax_dialog_ticket.php
 * @author    gparkin
 * @date      08/12/2016
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
 * @brief     All operations are performed by dialog_ticket.php
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
$lib->debug_start('ajax_dialog_ticket.html');
date_default_timezone_set('America/Denver');

$tic = new cct7_tickets();   // classes/cct7_tickets.php

// Read-only stream allows you to read raw data from the request body.
$json = json_decode(file_get_contents("php://input")); 

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
$action          = '';
$ticket_no       = '';
$cancel_comments = '';

if (isset($json->{'action'}))
    $action          = $json->{'action'};

if (isset($json->{'ticket_no'}))
    $ticket_no       = $json->{'ticket_no'};

if (isset($json->{'cancel_comments'}))
    $cancel_comments = $json->{'cancel_comments'};

$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "action = %s",          $action);
$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "ticket_no = %s",       $ticket_no);
$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "cancel_comments = %s", $cancel_comments);

$lib->debug1(  __FILE__, __FUNCTION__, __LINE__, "json: ");
$lib->debug_r1(__FILE__, __FUNCTION__, __LINE__, $json);
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

if ($action == "get")      // Get ticket info from cct7_tickets and return the data.
{
	if ($tic->getTicket($ticket_no) == false)
	{
		printf("{\n");
		printf("\"status\":  \"FAILED\",\n");
		printf("\"message\": \"%s\",\n", $tic->error);
		printf("}\n");
		exit();
	}

	// ticket_no|VARCHAR2|20|NOT NULL|PK: Unique Record ID
	// insert_date|NUMBER|0||Date record was inserted (GMT)
	// insert_cuid|VARCHAR2|20||CUID of person who inserted the record
	// insert_name|VARCHAR2|200||Name of person who inserted the record
	// update_date|NUMBER|0||Date record was updated (GMT)
	// update_cuid|VARCHAR2|20||CUID of person who updated the record
	// update_name|VARCHAR2|200||Name of person who updated the record
	// status|VARCHAR2|20||DRAFT, ACTIVE, FROZEN, CANCELED
	// status_date|NUMBER|0||Date when status last changed (GMT)
	// status_cuid|VARCHAR2|20||CUID of person who changed the status
	// status_name|VARCHAR2|200||Name of person who changed the status
	// owner_cuid|VARCHAR2|20||CUID of person who owns this work request.
	// owner_first_name|VARCHAR2|80||First name of person who created this record
	// owner_name|VARCHAR2|200||Name of person who created this record. (Same as insert_name)
	// owner_email|VARCHAR2|40||Email address of person who created this record
	// owner_job_title|VARCHAR2|80||Owners job title
	// manager_cuid|VARCHAR2|20||Owners managers CUID
	// manager_first_name|VARCHAR2|80||Owners managers first_name
	// manager_name|VARCHAR2|200||Owners managers full name
	// manager_email|VARCHAR2|40||Owners managers email address
	// manager_job_title|VARCHAR2|80||Owners managers job title
	// work_activity|VARCHAR2|80||Patching, GSD331, etc.
	// approvals_required|VARCHAR2|1||Y or N
	// reboot_required|VARCHAR2|1||Y or N
	// email_reminder1_date|NUMBER|0||Reminder Email 1 date (GMT)
	// email_reminder2_date|NUMBER|0||Reminder Email 2 date (GMT)
	// email_reminder3_date|NUMBER|0||Reminder Email 3 date (GMT)
	// respond_by_date|NUMBER|0||Respond by date (GMT)
	// schedule_start_date|NUMBER|0||Work Start Date (GMT)
	// schedule_end_date|NUMBER|0||Work End Date (GMT)
	// work_description|VARCHAR2|4000||Detail description of the work activity
	// work_implementation|VARCHAR2|4000||Implementation Instructions
	// work_backoff_plan|VARCHAR2|4000||Back out plans if there are problems
	// work_business_reason|VARCHAR2|4000||Business reason for the change
	// work_user_impact|VARCHAR2|4000||What impacts to users while doing the change
	// cm_ticket_no|VARCHAR2|20||Remedy CM Ticket Number
	// remedy_cm_start_date|NUMBER|0||Start Date for the Remedy CM Ticket
	// remedy_cm_end_date|NUMBER|0||End Date for the Remedy CM Ticket
	// total_servers_scheduled|NUMBER|0||Total scheduled servers
	// total_servers_waiting|NUMBER|0||Total servers WAITING for responses from clients
	// total_servers_approved|NUMBER|0||Total servers APPROVED by clients
	// total_servers_rejected|NUMBER|0||Total servers REJECTED by clients
	// total_servers_not_scheduled|NUMBER|0||Total servers not scheduled
	// servers_not_scheduled|VARCHAR2|4000||Servers not scheduled. Not found in cct7_computers.
	// generator_runtime|VARCHAR2|80||Total minutes and seconds the server took to generate the schedule.

    // add:
    // cm_implementor_login
    // cm_assign_group
    
    printf("{\n");
    printf("\"status\":                         \"SUCCESS\",\n");
    printf("\"message\":                        \"\",\n");
    printf("\"ticket_no\":                      \"%s\",\n", $tic->ticket_no);
	printf("\"create_date\":                    \"%s\",\n", $tic->insert_date_char);
	printf("\"update_date\":                    \"%s\",\n", $tic->update_date_char);
	printf("\"status\":                         \"%s\",\n", $tic->status);
	printf("\"status_date\":                    \"%s\",\n", $tic->status_date_char);
	printf("\"status_cuid\":                    \"%s\",\n", $tic->status_cuid);
	printf("\"status_name\":                    \"%s\",\n", $tic->status_name);
	printf("\"owner_cuid\":                     \"%s\",\n", $tic->owner_cuid);
	printf("\"owner_first_name\":               \"%s\",\n", $tic->owner_first_name);
	printf("\"owner_name\":                     \"%s\",\n", $tic->owner_name);
	printf("\"owner_email\":                    \"%s\",\n", $tic->owner_email);
	printf("\"owner_job_title\":                \"%s\",\n", $tic->owner_job_title);
	printf("\"manager_cuid\":                   \"%s\",\n", $tic->manager_cuid);
	printf("\"manager_first_name\":             \"%s\",\n", $tic->manager_first_name);
	printf("\"manager_name\":                   \"%s\",\n", $tic->manager_name);
	printf("\"manager_email\":                  \"%s\",\n", $tic->manager_email);
	printf("\"manager_job_title\":              \"%s\",\n", $tic->manager_job_title);
	printf("\"work_activity\":                  \"%s\",\n", $tic->work_activity);
	printf("\"approvals_required\":             \"%s\",\n", $tic->approvals_required);
	printf("\"reboot_required\":                \"%s\",\n", $tic->reboot_required);
	printf("\"email_reminder1_date\":           \"%s\",\n", $tic->email_reminder1_date_char);
	printf("\"email_reminder2_date\":           \"%s\",\n", $tic->email_reminder2_date_char);
	printf("\"email_reminder3_date\":           \"%s\",\n", $tic->email_reminder3_date_char);
	printf("\"respond_by_date\":                \"%s\",\n", $tic->respond_by_date_char);
	printf("\"schedule_start_date\":            \"%s\",\n", $tic->schedule_start_date_char);
	printf("\"schedule_end_date\":              \"%s\",\n", $tic->schedule_end_date_char);
    printf("\"work_description\":               \"%s\",\n", $tic->work_description);
    printf("\"work_implementation\":            \"%s\",\n", $tic->work_implementation);
    printf("\"work_backoff_plan\":              \"%s\",\n", $tic->work_backoff_plan);
    printf("\"work_business_reason\":           \"%s\",\n", $tic->work_business_reason);
	printf("\"cm_ticket_no\":                   \"%s\",\n", $tic->cm_ticket_no);
	printf("\"remedy_cm_start_date\":           \"%s\",\n", $tic->remedy_cm_start_date_char);
	printf("\"remedy_cm_end_date\":             \"%s\",\n", $tic->remedy_cm_end_date_char);
	printf("\"total_servers_scheduled\":        \"%s\",\n", $tic->total_servers_scheduled);
	printf("\"total_servers_waiting\":          \"%s\",\n", $tic->total_servers_waiting);
	printf("\"total_servers_approved\":         \"%s\",\n", $tic->total_servers_approved);
	printf("\"total_servers_rejected\":         \"%s\",\n", $tic->total_servers_rejected);
	printf("\"total_servers_not_scheduled\":    \"%s\",\n", $tic->total_servers_not_scheduled);
	printf("\"servers_not_scheduled\":          \"%s\",\n", $tic->servers_not_scheduled);
    printf("\"generator_runtime\":              \"%s\"\n",  $tic->generator_runtime);
    printf("}\n");
    exit();
}

if ($action == "activate") // Activate this DRAFT ticket.
{
    if ($tic->activateTicket($ticket_no) == false)
    {
        printf("{\n");
        printf("\"status\":        \"FAILED\",\n");
        printf("\"message\":       \"%s\",\n", $tic->error);
        printf("\"ticket_status\": \"%s\"\n", $tic->ticket_status);
        printf("}\n");
        exit();
    }

    printf("{\n");
    printf("\"status\":        \"SUCCESS\",\n");
    printf("\"message\":       \"%s\",\n", $tic->error);
    printf("\"ticket_status\": \"ACTIVE\"\n");
    printf("}\n");
    exit();
}

if ($action == "cancel")   // Cancel this ACTIVE ticket.
{
    if ($tic->cancelTicket($ticket_no, $cancel_comments) == false)
    {
        printf("{\n");
        printf("\"status\":        \"FAILED\",\n");
        printf("\"message\":       \"%s\",\n", $tic->error);
        printf("}\n");
        exit();
    }

    printf("{\n");
    printf("\"status\":        \"SUCCESS\",\n");
    printf("\"message\":       \"%s\",\n", $tic->error);
    printf("}\n");
    exit();
}

if ($action == "delete")   // Delete this DRAFT ticket.
{
    if ($tic->deleteTicket($ticket_no) == false)
    {
        printf("{\n");
        printf("\"status\":        \"FAILED\",\n");
        printf("\"message\":       \"%s\",\n", $tic->error);
        printf("}\n");
        exit();
    }

    printf("{\n");
    printf("\"status\":        \"SUCCESS\",\n");
    printf("\"message\":       \"%s\",\n", $tic->error);
    printf("}\n");
    exit();
}

if ($action == "freeze")   // Freeze this ACTIVE ticket. (Lock, no more updates)
{
    if ($tic->freezeTicket($ticket_no) == false)
    {
        printf("{\n");
        printf("\"status\":        \"FAILED\",\n");
        printf("\"message\":       \"%s\",\n", $tic->error);
        printf("\"ticket_status\": \"%s\"\n", $tic->ticket_status);
        printf("}\n");
        exit();
    }

    printf("{\n");
    printf("\"status\":        \"SUCCESS\",\n");
    printf("\"message\":       \"%s\",\n", $tic->error);
    printf("\"ticket_status\": \"FROZEN\"\n");
    printf("}\n");
    exit();
}

if ($action == "unfreeze") // Unfreeze this FROZEN ticket. (Unlock, allow more updates)
{
    if ($tic->unfreezeTicket($ticket_no) == false)
    {
        printf("{\n");
        printf("\"status\":        \"FAILED\",\n");
        printf("\"message\":       \"%s\",\n", $tic->error);
        printf("\"ticket_status\": \"%s\"\n", $tic->ticket_status);
        printf("}\n");
        exit();
    }

    printf("{\n");
    printf("\"status\":        \"SUCCESS\",\n");
    printf("\"message\":       \"%s\",\n", $tic->error);
    printf("\"ticket_status\": \"ACTIVE\"\n");
    printf("}\n");
    exit();
}

printf("{\n");
printf("\"status\":  \"FAILED\",\n");
printf("\"message\": \"ajax_dialog_ticket.php unknown action: [get, refresh, activate, cancel, delete, freeze, unfreeze]\",\n");
printf("}\n");
exit();
