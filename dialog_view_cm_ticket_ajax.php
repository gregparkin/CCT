<?php
/**
 * dialog_view_cm_ticket_ajax.php
 *
 * @package   PhpStorm
 * @file      dialog_view_cm_ticket_ajax.php
 * @author    gparkin
 * @date      01/04/2017
 * @version   7.0
 *
 * @brief     Ajax calls this module to retrieve JSON data about a Remedy CM Ticket.
 */

ini_set('xdebug.collect_vars',    '5');
ini_set('xdebug.collect_vars',    'on');
ini_set('xdebug.collect_params',  '4');
ini_set('xdebug.dump_globals',    'on');
ini_set('xdebug.dump.SERVER',     'REQUEST_URI');
ini_set('xdebug.show_local_vars', 'on');

set_include_path("/opt/ibmtools/www/cct7/classes");

//
// Class autoloader - Removes the need to add include and require statements
//
if (!function_exists('__autoload'))
{
	require_once('classes/autoloader.php');
}

session_start(); // Call to make $_SESSION['...'] data available

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
$lib = new library();  // classes/library.php
$lib->debug_start('dialog_view_cm_ticket_ajax.html');
date_default_timezone_set('America/Denver');

$ora = new oracle();     // classes/dbms.php

$my_request  = array();
$input       = array();
$input_count = 0;

if (isset($_SERVER['QUERY_STRING']))   // Parse QUERY_STRING if it exists
{
	parse_str($_SERVER['QUERY_STRING'], $my_request);  // Parses URL parameter options into an array called $my_request
	$input_count = count($my_request);                 // Get the count of the number of $my_request array elements.
}

$input        = json_decode(json_encode($my_request), FALSE); // Convert $my_request into an object called $input
$cm_ticket_no = isset($input->cm_ticket_no) ? $input->cm_ticket_no : '';
$ticket_no    = isset($input->ticket_no)    ? $input->ticket_no : '';
$start_date   = '';
$end_date     = '';
$tz           = $_SESSION['local_timezone_abbr'];

//
// Do we have any search filters to apply to $where_clause?
//
$input_new = array();

$lib->debug1(__FILE__, __FUNCTION__, __LINE__,   "cm_ticket_no: %s", $cm_ticket_no);
$lib->debug1(__FILE__, __FUNCTION__, __LINE__,   "ticket_no: %s",    $ticket_no);
$lib->debug1(  __FILE__, __FUNCTION__, __LINE__, "my_request: ");
$lib->debug_r1(__FILE__, __FUNCTION__, __LINE__, $my_request);
$lib->debug1(  __FILE__, __FUNCTION__, __LINE__, "input_count=%d\n", $input_count);
$lib->debug1(  __FILE__, __FUNCTION__, __LINE__, "input: ");
$lib->debug_r1(__FILE__, __FUNCTION__, __LINE__, $input);
$lib->debug1(  __FILE__, __FUNCTION__, __LINE__, "input_new: ");
$lib->debug_r1(__FILE__, __FUNCTION__, __LINE__, $input_new);
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


if ($ora->sql("select * from t_cm_implementation_request@remedy_prod where change_id = '" . $cm_ticket_no . "'") == false)
{
	$lib->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $ora->sql_statement);
	$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $ora->dbErrMsg);
	$lib->html_stop(__FILE__, __FUNCTION__, __LINE__, "SQL Error: %s. Please contact Greg Parkin", $ora->dbErrMsg);
}

if ($ora->fetch() == false)
{
	$lib->setErrorMessage = "Unable to pull ticket: " . $cm_ticket_no;
}

//
// Dates are stored in Remedy as numbers (GMT). Remedy provide me with the function
// fn_number_to_date() to convert the numbers to DATE objects.
//
$query  =         "select ";
$query .= sprintf("  fn_number_to_date(create_date, '%s')             as create_date, ",             $tz);
$query .= sprintf("  fn_number_to_date(start_date, '%s')              as start_date, ",              $tz);
$query .= sprintf("  fn_number_to_date(end_date, '%s')                as end_date, ",                $tz);
$query .= sprintf("  fn_number_to_date(close_date, '%s')              as close_date, ",              $tz);
$query .= sprintf("  fn_number_to_date(last_modified, '%s')           as last_modified, ",           $tz);
$query .= sprintf("  fn_number_to_date(late_date, '%s')               as late_date, ",               $tz);
$query .= sprintf("  fn_number_to_date(last_status_change_time, '%s') as last_status_change_time, ", $tz);
$query .= sprintf("  fn_number_to_date(turn_overdate, '%s')           as turn_overdate ",            $tz);
$query .= sprintf("from t_cm_implementation_request@remedy_prod where change_id = '%s'", $cm_ticket_no);

$lib->debug_sql1(__FILE__, __FUNCTION__, __LINE__, $query);

if ($ora->sql($query) == false)
{
	$lib->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $ora->sql_statement);
	$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $ora->dbErrMsg);
	printf("<p>%s %s %d: SQL Error: %s. Please contact Greg Parkin</p>\n", __FILE__, __FUNCTION__, __LINE__, $ora->dbErrMsg);
	exit(1);
}

if ($ora->fetch() == false)
{
	printf("<p>%s %s %d: Cannot putll ticket: %s. Please contact Greg Parkin</p>\n", __FILE__, __FUNCTION__, __LINE__, $cm_ticket_no);
	exit(1);
}

$start_date = $ora->start_date;   // Used to compute escalation dates
$end_date   = $ora->end_date;

$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "close_date = %s",              $ora->close_date);
$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "end_date = %s",                $ora->end_date);
$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "create_date = %s",             $ora->create_date);
$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "start_date = %s",              $ora->start_date);
$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "last_modified = %s",           $ora->last_modified);
$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "late_date = %s",               $ora->late_date);
$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "last_status_change_time = %s", $ora->last_status_change_time);
$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "turn_overdate = %s",           $ora->turn_overdate);

if ($ora->sql(
		"select " .
		"CASE WHEN tested_itv is not NULL THEN 'checked' END as tested_itv, " .
		"CASE WHEN tested_endtoend is not NULL THEN 'checked' END as tested_endtoend, " .
		"CASE WHEN tested_development is not NULL THEN 'checked' END as tested_development, " .
		"CASE WHEN tested_user is not NULL THEN 'checked' END as tested_user, " .
		"CASE WHEN tested_orl is not NULL THEN 'checked' END as tested_orl, " .
		"CASE WHEN emergency_change is not NULL THEN 'checked' END as emergency_change, " .
		"CASE WHEN featured_project is not NULL THEN 'checked' END as featured_project " .
		"from t_cm_implementation_request@remedy_prod where change_id = '" . $cm_ticket_no . "'") == false)
{
	$lib->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $ora->sql_statement);
	$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $ora->dbErrMsg);
	printf("<p>%s %s %d: SQL Error: %s. Please contact Greg Parkin</p>\n", __FILE__, __FUNCTION__, __LINE__, $ora->dbErrMsg);
	exit(1);
}

if ($ora->fetch() == false)
{
	printf("<p>%s %s %d: Cannot pull ticket: %s. Please contact Greg Parkin</p>\n", __FILE__, __FUNCTION__, __LINE__, $cm_ticket_no);
	exit(1);
}

$row['change_id']                   = $ora->change_id;
$row['assign_group']                = $ora->assign_group;
$row['category']                    = $ora->category;
$row['category_type']               = $ora->category_type;
$row['closed_by']                   = $ora->closed_by;
$row['close_code']                  = $ora->close_code;
$row['close_date']                  = $ora->close_date;
$row['component']                   = $ora->component;
$row['scheduling_flexibility']      = $ora->scheduling_flexibility;
$row['end_date']                    = $ora->end_date;
$row['entered_by']                  = $ora->entered_by;
$row['exp_code']                    = $ora->exp_code;
$row['fix_level']                   = $ora->fix_level;
$row['impact']                      = $ora->impact;
$row['implementor_first_last']      = $ora->implementor_first_last;
$row['implementor_login']           = $ora->implementor_login;
$row['ipl_boot']                    = $ora->ipl_boot == 1 ? "True" : "False";
$row['late']                        = $ora->late;
$row['parent_ir']                   = $ora->parent_ir;
$row['normal_release_session']      = $ora->normal_release_session;
$row['create_date']                 = $ora->create_date;
$row['pager']                       = $ora->pager;
$row['phone']                       = $ora->phone;
$row['phone2']                      = $ora->phone;
$row['pin']                         = $ora->pin;
$row['plan_a_b']                    = $ora->plan_a_b;
$row['product']                     = $ora->product;
$row['product_type']                = $ora->product_type;
$row['risk']                        = $ora->risk;
$row['software_object']             = $ora->software_object;
$row['start_date']                  = $ora->start_date;
$row['status']                      = $ora->status;
$row['tested']                      = $ora->tested == 1 ? "Yes" : "No";
$row['duration']                    = $ora->duration;
$row['business_unit']               = $ora->business_unit;
$row['duration_computed']           = $ora->duration_computed;
$row['email']                       = $ora->email;
$row['company_name']                = $ora->company_name;
$row['director']                    = $ora->director;
$row['manager']                     = $ora->manager;
$row['tested_itv']                  = $ora->tested_itv == "checked" ? "Yes" : "No";
$row['tested_endtoend']             = $ora->tested_endtoend == "checked" ? "Yes" : "No";
$row['tested_development']          = $ora->tested_development == "checked" ? "Yes" : "No";
$row['tested_user']                 = $ora->tested_user == "checked" ? "Yes" : "No";
$row['owner_name']                  = $ora->owner_name;
$row['owner_cuid']                  = $ora->owner_cuid;
$row['groupid']                     = $ora->groupid;
$row['temp']                        = $ora->temp;
$row['last_modified_by']            = $ora->last_modified_by;
$row['last_modified']               = $ora->last_modified;
$row['late_date']                   = $ora->late_date;
$row['risk_integer']                = $ora->risk_integer;
$row['owner_login_id']              = $ora->owner_login_id;
$row['open_closed']                 = $ora->open_closed;
$row['user_timestamp']              = $ora->user_timestamp;
$row['description']                 = $ora->description;
$row['backoff_plan']                = $ora->backoff_plan;
$row['implementation_instructions'] = $ora->implementation_instructions;
$row['business_reason']             = $ora->business_reason;
$row['owner_first_name']            = $ora->owner_first_name;
$row['owner_last_name']             = $ora->owner_last_name;
$row['change_occurs']               = $ora->change_occurs;
$row['lla_refresh']                 = $ora->lla_refresh;
$row['ims_cold_start']              = $ora->ims_cold_start;
$row['release_level']               = $ora->release_level;
$row['master_ir']                   = $ora->master_ir;
$row['owner_group']                 = $ora->owner_group;
$row['cab_approval_required']       = $ora->cab_approval_required;
$row['change_executive_team_flag']  = $ora->change_executive_team_flag;
$row['emergency_change']            = $ora->emergency_change == "checked" ? "Yes" : "No";
$row['approval_status']             = $ora->approval_status;
$row['component_type']              = $ora->component_type;
$row['desc_short']                  = $ora->desc_short;
$row['last_status_change_by']       = $ora->last_status_change_by;
$row['last_status_change_time']     = $ora->last_status_change_time;
$row['previous_status']             = $ora->previous_status;
$row['component_id']                = $ora->component_id;
$row['test_tool']                   = $ora->test_tool;
$row['tested_orl']                  = $ora->tested_orl == "checked" ? "Yes" : "No";
$row['featured_project']            = $ora->featured_project == "checked" ? "Yes" : "No";
$row['featured_proj_name']          = $ora->featured_proj_name;
$row['tmpmainplatform']             = $ora->tmpmainplatform;
$row['tmpblockmessage']             = $ora->tmpblockmessage;
$row['guid']                        = $ora->guid;
$row['platform']                    = $ora->platform;
$row['cllicodes']                   = $ora->cllicodes;
$row['processor_name']              = $ora->processor_name;
$row['system_name']                 = $ora->system_name;
$row['city']                        = $ora->city;
$row['state']                       = $ora->state;
$row['tmpdesc']                     = $ora->tmpdesc;
$row['turn_overdate']               = $ora->turn_overdate;
$row['assign_group2']               = $ora->assign_group2;
$row['assign_group3']               = $ora->assign_group3;
$row['implementor_name2']           = $ora->implementor_name2;
$row['implementor_name3']           = $ora->implementor_name3;
$row['groupid2']                    = $ora->groupid2;
$row['groupid3']                    = $ora->groupid3;
$row['template']                    = $ora->template;
$row['hd_outage_ticket_number']     = $ora->hd_outage_ticket_number;
$row['root_cause_owner']            = $ora->root_cause_owner;
$row['control_count']               = $ora->control_count;
$row['chng_req_fail_over']          = $ora->chng_req_fail_over;
$row['implementor_group4']          = $ora->implementor_group4;
$row['implementor_group5']          = $ora->implementor_group5;
$row['implementor_group6']          = $ora->implementor_group6;
$row['implementor_name4']           = $ora->implementor_name4;
$row['implementor_name5']           = $ora->implementor_name5;
$row['implementor_name6']           = $ora->implementor_name6;
$row['groupid4']                    = $ora->groupid4;
$row['groupid5']                    = $ora->groupid5;
$row['groupid6']                    = $ora->groupid6;
$row['daytime_waiver']              = $ora->daytime_waiver;
$row['parent']                      = $ora->parent;
$row['impact_cust_svc_call_ctr']    = $ora->impact_cust_svc_call_ctr;
$row['app_avail_during_chng']       = $ora->app_avail_during_chng;
$row['from_datetime']               = $ora->from_datetime;
$row['to_datetime']                 = $ora->to_datetime;
$row['reason_no_testing']           = $ora->reason_no_testing;
$row['tested_regression']           = $ora->tested_regression;
$row['tested_vendor']               = $ora->tested_vendor;
$row['tested_rls_mgd_mjr']          = $ora->tested_rls_mgd_mjr;
$row['tester_first_name']           = $ora->tester_first_name;
$row['tester_last_name']            = $ora->tester_last_name;
$row['tester_cuid']                 = $ora->tester_cuid;

echo json_encode($row);  // Output example: {"issue_ticket_no":"HDxxx","issue_category":"Incident-Complex",...}

$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "SENDING: %s", json_encode($row));
exit();

