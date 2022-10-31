<?php
/**
 * ajax_contacts.php
 *
 * @package   PhpStorm
 * @file      ajax_contacts.php
 * @author    gparkin
 * @date      7/1/16
 * @version   7.0
 *
 * @brief     Called by ajax request from dialog_ticket.php
 * @brief     Performs the following operations:
 *            [get, refresh, activate, cancel, delete, freeze, unfreeze]
 *            action = get       Retrieve ticket information from cct7_contacts
 *            action = approve   Refresh ticket from Remedy into cct7_contacts and return the information
 *            action = reject    Change ticket_status from DRAFT to ACTIVE
 *
 * @brief     All operations are performed by class: cct7_contacts.php
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

$lib = new library();        // classes/library.php
$lib->debug_start('ajax_contacts.html');
date_default_timezone_set('America/Denver');

$log = new cct7_event_log(); // classes/cct7_event_log.php
$con = new cct7_contacts();  // classes/cct7_contacts.php
// $sys = new cct7_systems();   // classes/cct7_systems.php

// Read-only stream allows you to read raw data from the request body.
$json = json_decode(file_get_contents("php://input"));

//
// action values:
//   get       - Get contact and connection info from cct7_contacts.
//   approve   - Client approves this work request.
//   reject    - Client rejects this work request.
//   delete    - Delete this contact record.
//
$action          = '';
$system_id       = 0;

if (isset($json->{'action'}))
    $action          = $json->{'action'};

if (isset($json->{'system_id'}))
    $system_id       = $json->{'system_id'};

$lib->debug1(__FILE__, __FUNCTION__, __LINE__,   "action = %s",    $action);
$lib->debug1(__FILE__, __FUNCTION__, __LINE__,   "$system_id = %s", $system_id);

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

if ($action == "get")      // Get ticket info from cct7_contacts and return the data.
{
    // cct7_contacts
    //
    // contact_id                 NUMBER      0 PRIMARY KEY - Unique record ID
    // system_id                  NUMBER      0 FOREIGN KEY - cct7_systems.system_id - CASCADE DELETE
    // contact_insert_cuid        VARCHAR2   20 CUID of person who created this record
    // contact_insert_name        VARCHAR2  200 Name of person who created this record
    // contact_insert_date        NUMBER      0 Date of person who created this record
    // contact_update_cuid        VARCHAR2   20 CUID of person who updated this record
    // contact_update_name        VARCHAR2  200 Name of person who updated this record
    // contact_update_date        NUMBER      0 Date of person who updated this record
    // contact_netpin_no          VARCHAR2   20 Netpin
    // contact_netpin_members     VARCHAR2 4000 Netpin Members
    // contact_connections        VARCHAR2 4000 Connections
    // contact_os                 VARCHAR2 4000 OS
    // contact_status             VARCHAR2 4000 Status
    // contact_approval           VARCHAR2 4000 Approval
    // contact_group_type         VARCHAR2 4000 Group Types
    // contact_notify_type        VARCHAR2 4000 Notify Type
    // contact_csc_banners        VARCHAR2 4000 CSC Support Banners (Primary)
    // contact_apps_and_databases VARCHAR2 4000 Apps/DBMS: MAL and MDL list of applications and databases
    // contact_response_status    VARCHAR2   20 Group WAITING, APPROVED, REJECTED, RESCHEDULE
    // contact_response_date      NUMBER      0 Contact response date
    // contact_response_cuid      VARCHAR2   20 CUID of the net-group member that approved this work
    // contact_response_name      VARCHAR2  200 Name of the net-group member that approved this work
    // contact_page_me            VARCHAR2   10 Does this user want a page? Y/N
    // contact_email_me           VARCHAR2   10 Does this person want an email?

    $top = $con->getContacts($system_id);
    
    if ($top == null)
    {
        printf("{\n");
        printf("\"status\":  \"FAILED\",\n");
        printf("\"message\": \"%s\"\n", $con->error);
        printf("}\n");
        exit();
    }
    
    printf("{\n");
    printf("\"status\":                         \"SUCCESS\",\n");
    printf("\"message\":                        \"\",\n");

    $lib->debug1(__FILE__, __FUNCTION__, __LINE__,   "{");
    $lib->debug1(__FILE__, __FUNCTION__, __LINE__,   "\"status\":  \"SUCCESS\",");
    $lib->debug1(__FILE__, __FUNCTION__, __LINE__,   "\"message\": \"\",");

    /*
    var json = {
        "features": [
            {
                "comparisonFeatureId":   1182,
                "comparisonFeatureType": "Category",
                "comparisonValues":      [ "Not Available", "Standard", "Not Available", "Not Available" ],
                "featureDescription":    "Rear Seat Heat Ducts"
            },
            {
                "comparisonFeatureId":   1183,
                "comparisonFeatureType": "Category",
                "comparisonValues":      [ "Not Available", "Standard", "Not Available", "Not Available" ],
                "featureDescription":    "Some Description"
            }
        ]
    };
    */


    //
    // Create a JSON array for contact data
    //
    printf("\"contacts\": [\n");
    $lib->debug1(__FILE__, __FUNCTION__, __LINE__,   "\"contacts\": [");

    $row = array();

    //
    // Table Headers
    //
    //$row['netpin']          = '<b>Netpin/Members</b>';
    //$row['connections']     = '<b>Connections</b>';
    //$row['os']              = '<b>OS</b>';
    //$row['status']          = '<b>Status</b>';
    //$row['approval']        = '<b>Approval</b>';
    //$row['group_type']      = '<b>Group Type</b>';
    //$row['notify_type']     = '<b>Notify Type</b>';
    //$row['csc_banners']     = '<b>CSC Support Banners</b>';
    //$row['apps_dbs']        = '<b>Apps/DBMS</b>';

    //echo json_encode($row);  // {"netpin":"<b>Netpin/Members</b>","connections":"<b>Connections</b>",...}
    //$lib->debug1(__FILE__, __FUNCTION__, __LINE__,   "%s", json_encode($row));

    $count = 0;

    for ($p=$top; $p!=null; $p=$p->next)
    {
        if ($count > 0)
        {
            printf(",\n");  // Let the client know that another record is coming.
            $lib->debug1(__FILE__, __FUNCTION__, __LINE__,   ",");
        }

        $count++;

        $hold = sprintf("<b>%s</b><br>", $p->contact_netpin_no);
        
        //
        // Netpin/Members - contact_netpin_no and contact_netpin_members
        //
        $list = explode(",", $p->contact_netpin_members);
        $count = 0;
        
        foreach ($list as $netpin_member)
        {
            if ($count > 0)
                $hold .= ",<br>";
            
            $hold .= $netpin_member;
            
            $count++;
        }

        $row['netpin']          = $hold; // Netpin/Members
        
        //
        // Connections - contact_connections
        //
        $hold = '';
        $list = explode(",", $p->contact_connections);
        $count = 0;

        foreach ($list as $connection)
        {
            if ($count > 0)
                $hold .= ",<br>";

            $hold .= $connection;

            $count++;
        }

        $row['connections']     = $hold; // Connections

        //
        // OS - contact_os
        //
        $hold = '';
        $list = explode(",", $p->contact_os);
        $count = 0;

        foreach ($list as $os)
        {
            if ($count > 0)
                $hold .= ",<br>";

            $hold .= $os;

            $count++;
        }

        $row['os']              = $hold; // OS

        //
        // Approval - contact_status
        //
        $hold = '';
        $list = explode(",", $p->contact_status);
        $count = 0;

        foreach ($list as $status)
        {
            if ($count > 0)
                $hold .= ",<br>";

            $hold .= $status;

            $count++;
        }

        $row['status']          = $hold; // Status (i.e. PRODUCTION)

        //
        // Approval
        //
        $row['approval']        = $p->contact_approval;     // Approval
        $row['group_type']      = $p->contact_group_type;   // Group Type
        $row['notify_type']     = $p->contact_notify_type;  // Notify Type

        //
        // CSC Support Banners (Primary) - contact_csc_banners
        //
        $hold = '';
        $list = explode(",", $p->contact_csc_banners);
        $count = 0;

        foreach ($list as $csc_banner)
        {
            if ($count > 0)
                $hold .= ",<br>";

            $hold .= $csc_banner;

            $count++;
        }

        $row['csc_banners']     = $hold; // CSC Support Banners (Primary)

        //
        // Apps/DBMS - contact_apps_and_databases
        //
        $hold = '';
        $list = explode(",", $p->contact_apps_and_databases);
        $count = 0;

        foreach ($list as $app_or_db)
        {
            if ($count > 0)
                $hold .= ",<br>";

            $hold .= $app_or_db;

            $count++;
        }

        $row['apps_dbs']        = $hold; // Apps/DBMS

        echo json_encode($row);
        $lib->debug1(__FILE__, __FUNCTION__, __LINE__,   "%s", json_encode($row));
    }

    printf("],\n");  // Close out the array for contacts
    $lib->debug1(__FILE__, __FUNCTION__, __LINE__,   "],");

    //
    // Get a list of events for this $system_id
    //
    $top = $log->getEvents($system_id);
    printf("\"events\": [\n");
    $lib->debug1(__FILE__, __FUNCTION__, __LINE__,   "\"events\": [");

    unset($row);
    $row = array();

    //
    // Table Headers
    //
    //$row['date']    = '<b>Date</b>';
    //$row['who']     = '<b>Who</b>';
    //$row['event']   = '<b>Event</b>';
    //$row['message'] = '<b>Message</b>';

    //echo json_encode($row);
    //$lib->debug1(__FILE__, __FUNCTION__, __LINE__,   "%s", json_encode($row));

    $count = 0;

    for ($p=$top; $p!=null; $p=$p->next)
    {
        if ($count > 0)
        {
            printf(",\n");  // Let the client know that another record is coming.
            $lib->debug1(__FILE__, __FUNCTION__, __LINE__,   ",");
        }

        $row['data']    = $p->event_date;
        $row['who']     = sprintf("%s (%s)", $p->user_name, $p->user_cuid);
        $row['event']   = $p->event_type;
        $row['message'] = $p->event_message;

        echo json_encode($row);
        $lib->debug1(__FILE__, __FUNCTION__, __LINE__,   "%s", json_encode($row));
    }

    printf("]}\n");  // Close out array and the data stream
    $lib->debug1(__FILE__, __FUNCTION__, __LINE__,   "]}");
    exit();
}

/*
if ($action == "approve") // Contact approves the work
{
    if ($con->activateTicket($conket_no) == false)
    {
        printf("{\n");
        printf("\"status\":        \"FAILED\",\n");
        printf("\"message\":       \"%s\",\n", $con->error);
        printf("\"ticket_status\": \"%s\"\n", $con->ticket_status);
        printf("}\n");
        exit();
    }

    printf("{\n");
    printf("\"status\":        \"SUCCESS\",\n");
    printf("\"message\":       \"%s\",\n", $con->error);
    printf("\"ticket_status\": \"ACTIVE\"\n");
    printf("}\n");
    exit();
}

if ($action == "reject")   // Contact rejects the work
{
    if ($con->cancelTicket($conket_no, $cancel_comments) == false)
    {
        printf("{\n");
        printf("\"status\":        \"FAILED\",\n");
        printf("\"message\":       \"%s\",\n", $con->error);
        printf("}\n");
        exit();
    }

    printf("{\n");
    printf("\"status\":        \"SUCCESS\",\n");
    printf("\"message\":       \"%s\",\n", $con->error);
    printf("}\n");
    exit();
}

if ($action == "delete")   // Delete this contact record
{
    if ($con->deleteTicket($conket_no) == false)
    {
        printf("{\n");
        printf("\"status\":        \"FAILED\",\n");
        printf("\"message\":       \"%s\",\n", $con->error);
        printf("}\n");
        exit();
    }

    printf("{\n");
    printf("\"status\":        \"SUCCESS\",\n");
    printf("\"message\":       \"%s\",\n", $con->error);
    printf("}\n");
    exit();
}

if ($action == "approve_all")   // Approve all contact records for this server
{
    if ($con->freezeTicket($conket_no) == false)
    {
        printf("{\n");
        printf("\"status\":        \"FAILED\",\n");
        printf("\"message\":       \"%s\",\n", $con->error);
        printf("\"ticket_status\": \"%s\"\n", $con->ticket_status);
        printf("}\n");
        exit();
    }

    printf("{\n");
    printf("\"status\":        \"SUCCESS\",\n");
    printf("\"message\":       \"%s\",\n", $con->error);
    printf("\"ticket_status\": \"FROZEN\"\n");
    printf("}\n");
    exit();
}

if ($action == "reject_all") // Reject all contact records for this server
{
    if ($con->unfreezeTicket($conket_no) == false)
    {
        printf("{\n");
        printf("\"status\":        \"FAILED\",\n");
        printf("\"message\":       \"%s\",\n", $con->error);
        printf("\"ticket_status\": \"%s\"\n", $con->ticket_status);
        printf("}\n");
        exit();
    }

    printf("{\n");
    printf("\"status\":        \"SUCCESS\",\n");
    printf("\"message\":       \"%s\",\n", $con->error);
    printf("\"ticket_status\": \"ACTIVE\"\n");
    printf("}\n");
    exit();
}
*/

printf("{\n");
printf("\"status\":  \"FAILED\",\n");
printf("\"message\": \"ajax_contacts.php unknown action: [get, delete, approve, reject, approve_all, reject_all]\",\n");
printf("}\n");
exit();
