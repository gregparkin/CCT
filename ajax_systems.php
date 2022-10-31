<?php
/**
 * ajax_systems.php
 *
 * @package   PhpStorm
 * @file      ajax_systems.php
 * @author    gparkin
 * @date      6/29/16
 * @version   7.0
 *
 * @brief     Ajax calls this module to retrieve JSON data about servers identified in cct7_tickets records.
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

if (!isset($_SESSION['issue_changed']))
    $_SESSION['issue_changed'] = time();

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
$lib->debug_start('ajax_systems.html');
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
//		foreach ($_REQUEST as $key => $value)
//		{
//			$this->data[$key] = $value;
//		}

$input = json_decode(json_encode($my_request), FALSE);  // Convert $my_request into an object called $input

$lib->debug1(  __FILE__, __FUNCTION__, __LINE__, "my_request: ");
$lib->debug_r1(__FILE__, __FUNCTION__, __LINE__, $my_request);
$lib->debug1(  __FILE__, __FUNCTION__, __LINE__, "input_count=%d\n", $input_count);
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

$page         = isset($input->page)         ? $input->page         : 1;
$limit        = isset($input->rows)         ? $input->rows         : 25; // get how many rows we want to have in the grid
$filters      = isset($input->filters)      ? $input->filters      : ''; // These are the search parameters used in building the sql where clause
$ticket_no    = isset($input->ticket_no)    ? $input->ticket_no    : '';



// filter_name = by_ticket  - Return all the tickets for a person and there team where they are owners of the tickets
// filter_name = by_contact - Return all the tickets for a person and there team where they are listed as contacts.
// filter_name = by_system  - Return all the tickets for a matching system name.

// filter_name = all_tickets - Return all the tickets matching a certain criteria.

// filter1 requires: member_cuid = xxx

//
// Calculate the total records that will be returned for the query. We need this for paging the result.
//
$current_page = $page;
$total_pages = 0;
$total_records = 0;

$query  = "select count(*) as total_records from cct7_systems where ";
$query .= sprintf("cm_ticket_no = '%s'", $ticket_no);

if ($ora->sql2($query) == false)
{
    $lib->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $ora->sql_statement);
    $lib->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $ora->dbErrMsg);
    exit();
}

$ora->fetch();
$total_records = $ora->total_records;

$lib->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $ora->sql_statement);
$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "total_records = %d", $total_records);

//
// Calculate the total pages for the query
//
if( $total_records > 0 && $limit > 0)
{
    $total_pages = ceil($total_records / $limit);
}
else
{
    $total_pages = 0;
}

//
// If for some reasons the requested page is greater than the total
// set the requested $page = $total_pages.
//
if ($page > $total_pages)
{
    $page = $total_pages;
}

//
// Calculate the starting position of the rows
//
$start = $limit * $page - $limit; // do not put $limit*($page - 1)

//
// If for some reasons start position is negative set it to 1
// typical case is that the user type 1 for the requested page
//
if($start < 1)
{
    $start = 1;
}

$tz    = $_SESSION['local_timezone_abbr'];

$query  = "select ";
$query .= "  system_id, ";
$query .= "  cm_ticket_no, ";
$query .= "  cm_ipl_boot, ";
$query .= sprintf("  fn_number_to_date(cm_start_date, '%s')                  as cm_start_date, ",                  $tz);
$query .= sprintf("  fn_number_to_date(cm_end_date, '%s')                    as cm_end_date, ",                    $tz);
$query .= "  cm_duration_computed, ";
$query .= sprintf("  fn_number_to_date(system_work_start, '%s')              as system_work_start, ",              $tz);
$query .= sprintf("  fn_number_to_date(system_work_end, '%s')                as system_work_end, ",                $tz);
$query .= "  system_work_duration, ";
$query .= "  system_work_status, ";
$query .= "  system_total_approvers, ";
$query .= "  system_total_waiting, ";
$query .= "  system_total_approved, ";
$query .= "  system_total_rejected, ";
$query .= "  system_insert_cuid, ";
$query .= "  system_insert_name, ";
$query .= sprintf("  fn_number_to_date(system_insert_date, '%s')             as system_insert_date, ",             $tz);
$query .= "  system_update_cuid, ";
$query .= "  system_update_name, ";
$query .= sprintf("  fn_number_to_date(system_update_date, '%s')             as system_update_date, ",             $tz);
$query .= "  computer_lastid, ";
$query .= "  computer_hostname, ";
$query .= "  computer_operating_system, ";
$query .= "  computer_os_lite, ";
$query .= "  computer_status, ";
$query .= "  computer_status_description, ";
$query .= "  computer_description, ";
$query .= "  computer_timezone, ";
$query .= "  computer_model_category, ";
$query .= "  computer_model, ";
$query .= "  computer_model_mfg, ";
$query .= "  computer_cpu_type, ";
$query .= "  computer_dmz, ";
$query .= "  computer_managing_group, ";
$query .= "  computer_contract, ";
$query .= "  computer_city, ";
$query .= "  computer_state, ";
$query .= "  computer_slevel_objective, ";
$query .= "  computer_slevel_score, ";
$query .= "  computer_slevel_colors, ";
$query .= "  computer_gold_server, ";
$query .= "  computer_special_handling, ";
$query .= "  app_server_assn_sox_critical, ";
$query .= "  db_server_assn_sox_critical, ";
$query .= "  computer_osmaint_weekly, ";
$query .= sprintf("  fn_number_to_date(system_email_notification_date, '%s') as system_email_notification_date, ", $tz);
$query .= sprintf("  fn_number_to_date(system_work_cancel_date, '%s')        as system_work_cancel_date, ",        $tz);
$query .= "  system_approvals_required, ";
$query .= sprintf("  fn_number_to_date(system_override_status_date, '%s')    as system_override_status_date, ",    $tz);
$query .= "  system_override_status_cuid, ";
$query .= "  system_override_status_name, ";
$query .= "  system_override_status_notes, ";
$query .= sprintf("  fn_number_to_date(system_completion_date, '%s')         as system_completion_date, ",         $tz);
$query .= "  system_completion_status, ";
$query .= "  system_completion_cuid, ";
$query .= "  system_completion_name, ";
$query .= "  system_completion_notes ";
$query .= "from ";
$query .= "  cct7_systems ";
$query .= "where ";
$query .= sprintf("cm_ticket_no = '%s' ", $ticket_no);
$query .= "order by ";
$query .= "  computer_hostname ";
$query .= sprintf("OFFSET %d ROWS FETCH NEXT %d ROWS ONLY", $start - 1, $limit);

if ($ora->sql2($query) == false)
{
    $lib->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $ora->sql_statement);
    $lib->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $ora->dbErrMsg);
    exit();
}

$lib->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $ora->sql_statement);


printf("{\n");
printf("\"page\": \"%s\",\n",    $current_page);   // Current page
printf("\"total\": \"%s\",\n",   $total_pages);    // Number of pages
printf("\"records\": \"%s\",\n", $total_records);  // # of records in total
printf("\"rows\": [\n");                           // Array of data being returned

$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "Current page          = %d", $current_page);
$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "Number of pages       = %d", $total_records);
$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "# of records in total = %d", $total_records);

$count_records = 0;

$order = array("\r\n", "\n", "\r");
$replace = ' ';

$row = array();

while ($ora->fetch())
{
    if ($count_records > 0)
    {
        printf(",\n");  // Let the client know that another record is coming.
    }

    $sox = "N";

    if ($ora->app_server_assn_sox_critical == 1 || $ora->db_server_assn_sox_critical == 1)
        $sox = "Y";

    $location = $ora->computer_city . ", " . $ora->computer_state;

// System    OS     Usage       Work Start        Work End          Work Duration  Status   TOTAL  WAITING  APPROVED  REJECTED
// lxomp11m  Linux  PRODUCTION  07/04/2015 21:00  07/04/2015 23:30    0 : 2 : 30   WAITING      5        2         3         0

    $row['system_id']                 = $ora->system_id;                 // hidden column (key)
    $row['cm_ticket_no']              = $ora->cm_ticket_no;              // hidden column (for debugging)
    $row['computer_hostname']         = $ora->computer_hostname;         // System
    $row['computer_os_lite']          = $ora->computer_os_lite;          // OS
    $row['computer_status']           = $ora->computer_status;           // Usage
    $row['location']                  = $location;                       // Location
    $row['gold']                      = $ora->computer_gold_server;      // Gold (server? Y or N)
    $row['special']                   = $ora->computer_special_handling; // Special
    $row['sox']                       = $sox;                            // Sox App and/or DB?  Y or N
    $row['computer_managing_group']   = $ora->computer_managing_group;   // Managing Group
    $row['computer_contract']         = $ora->computer_contract;         // CMP Contract
    $row['system_work_status']        = $ora->system_work_status;        // Status
    $row['system_total_approvers']    = $ora->system_total_approvers;    // TOTAL
    $row['system_total_waiting']      = $ora->system_total_waiting;      // WAITING
    $row['system_total_approved']     = $ora->system_total_approved;     // APPROVED
    $row['system_total_rejected']     = $ora->system_total_rejected;     // REJECTED

    echo json_encode($row);  // {"issue_ticket_no":"HDxxx","issue_category":"Incident-Complex",...}

    $lib->debug1(__FILE__, __FUNCTION__, __LINE__, "SENDING: %s", json_encode($row));

    $count_records++;  // Count up the records to determine if we sent anything.
}

printf("]}\n");  // Close out the data stream
exit();

