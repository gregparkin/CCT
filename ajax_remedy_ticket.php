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

$tz = 'America/Denver';

if (isset($_SESSION['local_timezone_name']))
    $tz = $_SESSION['local_timezone_name'];

// Prevent caching.
//header('Cache-Control: no-cache, must-revalidate');
//header('Expires: Mon, 01 Jan 1996 00:00:00 GMT');

// The JSON standard MIME header.
//header('Content-type: application/json');

//
// NOTE: It is very important that you do not turn on debugging without writing to a file for server side AJAX code. Any HTML
//       comments coming from functions while writing JSON will show up in the JSON output and you will get a parsing error
//       in the client side program.
//
$lib = new library();        // classes/library.php
$lib->debug_start('ajax_remedy_ticket.html');
date_default_timezone_set('America/Denver');

$ora = new oracle();

// Read-only stream allows you to read raw data from the request body.
$input = json_decode(file_get_contents("php://input"));

$ticket_no = '';

if (isset($json->{'ticket_no'}))
    $ticket_no = $input->{'ticket_no'};

//$ticket_no = 'CM0000308525';

$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "ticket_no = %s", $ticket_no);

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

//
// Retrieve the Remedy Ticket from the Remedy CM database.
// Table: t_cm_implementation_request is a oracle view on remedy_prod
//
$query = sprintf("select * from t_cm_implementation_request@remedy_prod where change_id = '%s'", $ticket_no);

if ($ora->sql2($query) == false)
{
    $lib->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->sql_statement);
    $lib->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->dbErrMsg);

    printf("{\n");
    printf("\"status\":  \"FAILED\",\n");
    printf("\"message\": \"Problem with ajax_remedy_ticket.php. Error: %s\",\n", $this->ora->dbErrMsg);
    printf("}\n");
    exit();
}

if ($ora->fetch() == false)
{
    $lib->debug1(__FILE__, __FUNCTION__, __LINE__, "Remedy CM ticket not found: %s", $ticket_no);

    printf("{\n");
    printf("\"status\":  \"FAILED\",\n");
    printf("\"message\": \"%s\",\n", "Remedy CM ticket not found: %s", $ticket_no);
    printf("}\n");
    exit();
}

$mmddyyyy_hhmm = 'm/d/Y H:i';

$start_date = '';
$end_date   = '';
$close_date = '';

if ($ora->start_date > 0)
    $start_date = $lib->gmt_to_format($ora->start_date, $mmddyyyy_hhmm, $tz);

if ($ora->end_date > 0)
    $end_date   = $lib->gmt_to_format($ora->end_date,   $mmddyyyy_hhmm, $tz);

if ($ora->close_date > 0)
    $close_date = $lib->gmt_to_format($ora->close_date, $mmddyyyy_hhmm, $tz);

$json = array();

$json['ajax_status']                    = 'SUCCESS';
$json['ajax_message']                   = '';
$json['cm_ticket_no']                   = $ora->change_id;
$json['cm_start_date']                  = $start_date;
$json['cm_status']                      = $ora->status;
$json['cm_open_closed']                 = $ora->open_closed;
$json['cm_end_date']                    = $end_date;
$json['cm_duration_computed']           = $ora->duration_computed;
$json['cm_closed_by']                   = $ora->closed_by;
$json['cm_close_date']                  = $close_date;
$json['close_code']                     = $ora->close_code;
$json['cm_owner_first_name']            = $ora->owner_first_name;
$json['cm_owner_last_name']             = $ora->owner_last_name;
$json['cm_owner_cuid']                  = $ora->owner_cuid;
$json['cm_owner_group']                 = $ora->owner_group;
$json['cm_director']                    = $ora->director;
$json['cm_manager']                     = $ora->manager;
$json['cm_phone']                       = $ora->phone;
$json['cm_email']                       = $ora->email;
$json['cm_company_name']                = $ora->company_name;
$json['cm_implementor_login']           = $ora->implementor_login;
$json['cm_assign_group']                = $ora->assign_group;
$json['cm_category']                    = $ora->category;
$json['cm_category_type']               = $ora->category_type;
$json['cm_tested']                      = $ora->tested;
$json['cm_scheduling_flexibility']      = $ora->scheduling_flexibility;
$json['cm_risk']                        = $ora->risk;
$json['cm_tested_itv']                  = $ora->tested_itv;
$json['cm_tested_endtoend']             = $ora->tested_endtoend;
$json['cm_tested_development']          = $ora->tested_development;
$json['cm_tested_user']                 = $ora->tested_user;
$json['cm_tested_orl']                  = $ora->tested_orl;
$json['cm_emergency_change']            = $ora->emergency_change;
$json['cm_featured_project']            = $ora->featured_project;
$json['cm_ipl_boot']                    = $ora->ipl_boot;
$json['cm_plan_a_b']                    = $ora->plan_a_b;
$json['cm_description']                 = $ora->description;
$json['cm_implementation_instructions'] = $ora->implementation_instructions;
$json['cm_backoff_plan']                = $ora->backoff_plan;
$json['cm_business_reason']             = $ora->business_reason;
$json['cm_impact']                      = $ora->impact;

echo json_encode($json);

exit();

/**
cm_ticket_no
cm_start_date
cm_status
cm_open_closed
cm_end_date
cm_duration_computed
cm_closed_by
cm_close_date
close_code
cm_owner_first_name
cm_owner_last_name
cm_owner_cuid
cm_owner_group
cm_director
cm_manager
cm_phone
cm_email
cm_company_name
cm_implementor_login
cm_assign_group
cm_category
cm_category_type
cm_tested
cm_scheduling_flexibility
cm_risk
cm_tested_itv
cm_tested_endtoend
cm_tested_development
cm_tested_user
cm_tested_orl
cm_emergency_change
cm_featured_project
cm_ipl_boot
cm_plan_a_b
cm_description
cm_implementation_instructions
cm_backoff_plan
cm_business_reason
cm_impact
*/



