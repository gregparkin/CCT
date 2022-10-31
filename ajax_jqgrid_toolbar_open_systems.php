<?php
/**
 * ajax_jqgrid_toolbar_open_systems.php
 *
 * @package   PhpStorm
 * @file      ajax_jqgrid_toolbar_open_systems.php
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

if (isset($_SESSION['local_timezone_name']))
	$tz = $_SESSION['local_timezone_name'];
else
	$tz = 'America/Denver';

$where_clause = '';
$search_where_clause = '';  // Constructed from $filter
$this_operation_group = '';
$this_operation_code = '';
$this_field_name = '';
$this_field_type = '';

$user_groups = '';
$list = explode(',', $_SESSION['user_groups']);

foreach ($list as $group_name)
{
	if (strlen($user_groups) == 0)
	{
		$user_groups = sprintf("IN ('%s'", $group_name);
	}
	else
	{
		$user_groups .= sprintf(",'%s'", $group_name);
	}
}

if (strlen($user_groups) > 0)
	$user_groups .= ")";

/**
 * @fn     fieldType($field_name)
 *
 * @brief  Returns the Oracle data type for cct7_systems field names
 *
 * @param  string $field_name
 *
 * @return string Data type
 */
function fieldType($field_name)
{
	switch ($field_name)
	{
		case 'system_insert_date':
		case 'system_update_date':
		case 'system_respond_by_date':
		case 'system_work_start_date':
		case 'respond_by_date':
		case 'system_work_end_date':
			return "date";
		case 'system_id':
		case 'system_lastid':
		case 'total_contacts_responded':
		case 'total_contacts_not_responded':
			return "number";
		default:
			return "string";
	}
}

/**
 * @fn    prepareWhereClause($key, $str)
 *
 * @brief Prepare the $search_where_clause where
 *
 * @param string $key
 * @param string $str
 */
function prepareWhereClause($key, $str)
{
	global $search_where_clause, $this_operation_group, $this_operation_code;
	global $this_field_name, $this_field_type, $lib, $tz;

	if ($key == "groupOp")
	{
		$this_operation_group = $str;
		return;
	}

	if ($key == "field")
	{
		$this_field_name = $str;
		$this_field_type = fieldType($str);

		if (strlen($search_where_clause) == 0)
		{
			$search_where_clause .= sprintf(" t.%s ", $str);
		}
		else
		{
			$search_where_clause .= sprintf(" %s t.%s ", $this_operation_group, $str);
		}

		return;
	}

	// Operation Codes ("op":)
	// eq = equal
	// ne = not equal
	// lt = less
	// le = less or equal
	// gt = greater
	// ge = greater or equal
	// bw = begins with
	// bn = does not begin with
	// in = is in
	// ni = is not in
	// ew = ends with
	// en = does not end with
	// cn = contains
	// nc = does not contain

	if ($key == "op")
	{
		$this_operation_code = $str;

		switch ($str)
		{
			case 'eq':  // equal
				$search_where_clause .= " = ";
				break;
			case 'ne':  // not equal
				$search_where_clause .= " != ";
				break;
			case 'lt':  // less
				$search_where_clause .= " < ";
				break;
			case 'le':  // less or equal
				$search_where_clause .= " <= ";
				break;
			case 'gt':  // greater
				$search_where_clause .= " > ";
				break;
			case 'ge':  // greater or equal
				$search_where_clause .= " >= ";
				break;
			case 'bw':  // begins with
				$search_where_clause .= " like ";
				break;
			case 'bn':  // does not begin with
				$search_where_clause .= " not like ";
				break;
			case 'in':  // is in
				$search_where_clause .= " in ";
				break;
			case 'ni':  // is not in
				$search_where_clause .= " not in ";
				break;
			case 'ew':  // ends with
				$search_where_clause .= " like ";
				break;
			case 'en':  // does not end with
				$search_where_clause .= " not like ";
				break;
			case 'cn':  // contains
				$search_where_clause .= " like ";
				break;
			case 'nc':  // does not contain
				$search_where_clause .= " not like ";
				break;
			default:
				break;
		}

		return;
	}

	if ($key == "data")
	{
		switch ($this_field_type)
		{
			case 'number':
				$search_where_clause .= sprintf(" %d ", $str);
				break;
			case 'date': // Convert Date and Time to a utime (GMT) numeric value.
				$dt = new DateTime($str, new DateTimeZone($tz));
				$dt->setTimezone(new DateTimeZone('GMT'));
				$search_where_clause .=	sprintf(" %d ", $dt->format('U'));
				break;
			default:  // string
				switch ($this_operation_code)
				{
					case 'bw':  // begins with (like 'xxx%')
					case 'bn':  // does not begin with (not like 'xxx%')
						$search_where_clause .= sprintf(" '%s%%' ", $str);
						break;
					case 'in':  // is in (in ('xxx','xxx')
					case 'ni':  // is not in (not in ('xxx','xxx')
						$search_where_clause .= sprintf(" (%s) ", $str);
						break;
					case 'ew':  // ends with (like '%xxx')
					case 'en':  // does not end with (not like ('%xxx')
						$search_where_clause .= sprintf(" '%%%s' ", $str);
						break;
					case 'cn':  // contains (like '%xxx%')
					case 'nc':  // does not contain (not like ('%xxx%')
						$search_where_clause .= sprintf(" '%%%s%%' ", $str);
						break;
					default:
						$search_where_clause .= sprintf(" '%s' ", $str);
						break;
				}
				break;
		}

		return;
	}
}

/**
 * @fn    multiDimensionalArrayMap($func, $arr)
 *
 * @brief Used to construct $search_where_clause from the $input->filter string
 *
 * @param string $func
 * @param string $arr
 *
 * @return array
 */
function multiDimensionalArrayMap($func, $arr)
{
	$newArr = array();

	if (!empty($arr))
	{
		foreach($arr AS $key => $value)
		{
			$newArr[$key] = (is_array($value) ? multiDimensionalArrayMap($func, $value) : $func($key, $value));
		}
	}

	return $newArr;
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
$lib->debug_start('ajax_jqgrid_toolbar_open_systems.html');
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

//
// action values:
//   get       - Get contact and connection info from cct7_contacts.
//   approve   - Client approves this work request.
//   reject    - Client rejects this work request.
//   delete    - Delete this contact record.
//

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

$lib->debug1(  __FILE__, __FUNCTION__, __LINE__, "user_groups: %s", $user_groups);

$page           = isset($input->page)           ? $input->page           : 1;
$limit          = isset($input->rows)           ? $input->rows           : 25; // get how many rows we want to have in the grid
$filters        = isset($input->filters)        ? $input->filters        : ''; // These are the search parameters used in building the sql where clause
$ticket_no      = isset($input->ticket_no)      ? $input->ticket_no      : '';

$action         = isset($input->action)         ? $input->action         : '';
$ticket_no      = isset($input->ticket_no)      ? $input->ticket_no      : 0;
$system_id      = isset($input->system_id)      ? $input->system_id      : 0;

$ticket_no      = isset($input->ticket_no)      ? $input->ticket_no      : '';
$what_tickets   = isset($input->what_tickets)   ? $input->what_tickets   : '';  // relevant if 'approve'
$from_part2     = '';  // Used if $what_tickets = 'group';
$what_hostname  = isset($input->what_hostname)  ? $input->what_hostname  : '';
$what_netpin_no = isset($input->what_netpin_no) ? $input->what_netpin_no : '';
$approve_filter = isset($input->approve_filter) ? $input->approve_filter : '';

$lib->debug1(__FILE__, __FUNCTION__, __LINE__,   "action         = %s", $action);
$lib->debug1(__FILE__, __FUNCTION__, __LINE__,   "ticket_no      = %s", $ticket_no);
$lib->debug1(__FILE__, __FUNCTION__, __LINE__,   "system_id      = %s", $system_id);
$lib->debug1(__FILE__, __FUNCTION__, __LINE__,   "what_tickets   = %s", $what_tickets);
$lib->debug1(__FILE__, __FUNCTION__, __LINE__,   "what_hostname  = %s", $what_hostname);
$lib->debug1(__FILE__, __FUNCTION__, __LINE__,   "what_netpin_no = %s", $what_netpin_no);
$lib->debug1(__FILE__, __FUNCTION__, __LINE__,   "approve_filter = %s", $approve_filter);

if (strlen($what_netpin_no) > 0)
{
	$from_part2  = "(select distinct ";
	$from_part2 .= "  s.system_id, s.system_hostname ";
	$from_part2 .= "from ";
	$from_part2 .= "  cct7_systems s, ";
	$from_part2 .= "  cct7_contacts c ";
	$from_part2 .= "where ";
	$from_part2 .= "  c.contact_netpin_no = '" . $what_netpin_no . "' and ";
	$from_part2 .= "  s.system_id = c.system_id and ";
	$from_part2 .= "  s.ticket_no = '" . $ticket_no . "' ";
	$from_part2 .= "order by ";
	$from_part2 .= "  s.system_hostname) m ";

	$where_clause = sprintf(" where (t.system_id = m.system_id) ");
}
else if (strlen($what_hostname) > 0)
{
	$where_clause = sprintf(" where ticket_no = '%s' and lower(system_hostname) = lower('%s') ",
							$ticket_no, $what_hostname);
}
else if ($what_tickets == 'approve')
{
	$from_part2  = "(select distinct ";
	$from_part2 .= "  s.system_id, s.system_hostname ";
	$from_part2 .= "from ";
	$from_part2 .= "  cct7_systems s, ";
	$from_part2 .= "  cct7_contacts c ";
	$from_part2 .= "where ";
	$from_part2 .= "  c.contact_netpin_no " . $user_groups . " and ";
	$from_part2 .= "  s.system_id = c.system_id and ";

	if ($approve_filter == "remove_approved")
	{
		$from_part2 .= "  s.system_work_status = 'WAITING' and ";
		$from_part2 .= "  c.contact_response_status = 'WAITING' and ";
	}

	$from_part2 .= "  s.ticket_no = '" . $ticket_no . "' ";
	$from_part2 .= "order by ";
	$from_part2 .= "  s.system_hostname) m ";

	$where_clause = sprintf(" where (t.system_id = m.system_id) ");

}
else if (strlen($ticket_no) > 0)
{
	$where_clause = sprintf(" where ticket_no = '%s' ", $ticket_no);
}

//
// Do we have any search filters to apply to $where_clause?
//
$input_new = array();

if (strlen($filters) > 0)
{
	$x = array();
	$x = json_decode($filters, true);

	$lib->debug1(  __FILE__, __FUNCTION__, __LINE__, "Decoding filters: ");
	$lib->debug_r1(__FILE__, __FUNCTION__, __LINE__, $x);

	$input_new = multiDimensionalArrayMap('prepareWhereClause', $x);
}

$lib->debug1(  __FILE__, __FUNCTION__, __LINE__, "            filters: %s", $filters);
$lib->debug1(  __FILE__, __FUNCTION__, __LINE__, "       where_clause: %s", $where_clause);
$lib->debug1(  __FILE__, __FUNCTION__, __LINE__, "search_where_clause: %s", $search_where_clause);

//
// Final assembly for the $where_clause string
//
if (strlen($search_where_clause) > 0)
{
	$lib->debug1(  __FILE__, __FUNCTION__, __LINE__, "search_where_clause length > 0");

	if (strlen($where_clause) > 0)
		$where_clause .= " AND " . $search_where_clause;
	else
		$where_clause = " where " . $search_where_clause;
}

$lib->debug1(  __FILE__, __FUNCTION__, __LINE__, "       where_clause: %s", $where_clause);

//
// Calculate the total records that will be returned for the query. We need this for paging the result.
//
$current_page  = $page;
$total_pages   = 0;
$total_records = 0;

if ($what_tickets == 'approve' || strlen($what_netpin_no) > 0)
{
	$query  = "select count(*) as total_records from ";
	$query .= "cct7_systems t, ";
	$query .= $from_part2;
	$query .= $where_clause;
}
else
{
	$query = "select count(*) as total_records from cct7_systems t " . $where_clause;
}

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

$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "start = %d, limit = %d, page = %d, total_pages = %d", $start, $limit, $page, $total_pages);

//
// If for some reasons start position is negative set it to 1
// typical case is that the user type 1 for the requested page
//
if($start < 1)
{
	$start = 1;
}

$tz        = $_SESSION['local_timezone_abbr'];

$order_by  = "t.system_hostname";
$direction = "asc";

//$query = "select * from cct7_systems where ticket_no = '" . $ticket_no . "' order by system_hostname " .
//	sprintf(" OFFSET %d ROWS FETCH NEXT %d ROWS ONLY", $start - 1, $limit);

if ($what_tickets == 'approve' || strlen($what_netpin_no) > 0)
{
	$query = "select ";
	$query .= "  t.system_id, ";
	$query .= "  t.ticket_no, ";
	$query .= "  t.system_insert_date, ";
	$query .= "  t.system_insert_cuid, ";
	$query .= "  t.system_insert_name, ";
	$query .= "  t.system_update_date, ";
	$query .= "  t.system_update_cuid, ";
	$query .= "  t.system_update_name, ";
	$query .= "  t.system_lastid, ";
	$query .= "  t.system_hostname, ";
	$query .= "  t.system_os, ";
	$query .= "  t.system_usage, ";
	$query .= "  t.system_location, ";
	$query .= "  t.system_timezone_name, ";
	$query .= "  t.system_osmaint_weekly, ";
	$query .= "  t.system_respond_by_date, ";
	$query .= "  t.system_work_start_date, ";
	$query .= "  t.system_work_end_date, ";
	$query .= "  t.system_work_duration, ";
	$query .= "  t.system_work_status, ";
	$query .= "  t.total_contacts_responded, ";
	$query .= "  t.total_contacts_not_responded, ";
	$query .= "  t.original_work_start_date, ";
	$query .= "  t.original_work_end_date, ";
	$query .= "  t.original_work_duration ";
	$query .= "from ";
	$query .= "  cct7_systems t, ";
	$query .= $from_part2;
	$query .= $where_clause . "order by " . $order_by . " " . $direction;
}
else
{
	$query  = "select * from cct7_systems t ";
	$query .= $where_clause . "order by " . $order_by . " " . $direction;
}

// $query .= sprintf(" OFFSET %d ROWS FETCH NEXT %d ROWS ONLY", $start - 1, $limit);

if ($action !== "excel")
{
	$query .= sprintf(" OFFSET %d ROWS FETCH NEXT %d ROWS ONLY", $start - 1, $limit);
}

if ($ora->sql2($query) == false)
{
	$lib->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $ora->sql_statement);
	$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $ora->dbErrMsg);
	exit();
}

$lib->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $ora->sql_statement);

printf("{\n");
printf("\"page\":    \"%s\",\n", $current_page);   // Current page
printf("\"total\":   \"%s\",\n", $total_pages);    // Number of pages
printf("\"records\": \"%s\",\n", $total_records);  // # of records in total
printf("\"rows\": [\n");                           // Array of data being returned

$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "Current page          = %d", $current_page);
$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "Number of pages       = %d", $total_records);
$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "# of records in total = %d", $total_records);

$count_records = 0;

$order = array("\r\n", "\n", "\r");
$replace = ' ';

$row = array();

// = $this->gmt_to_format($this->ora->system_update_date, $mmddyyyy_hhmm, $this->user_timezone_name);

while ($ora->fetch())
{
	if ($count_records > 0)
	{
		printf(",\n");  // Let the client know that another record is coming.
	}

	$mmddyyyy_hhmm_tz = 'm/d/Y H:i T';
	$mmddyyyy_hhmm    = 'm/d/Y H:i';
	$mmddyyyy         = 'm/d/Y';
	$mmddyyyy_tz      = 'm/d/Y T';

	if ($ora->system_work_start_date == 0)
	{
		$system_work_start_date = "(Call Greg)";
		$system_work_end_date   = "(Call Greg)";
		$system_work_duration   = "(Call Greg)";
	}
	else
	{
		// I changed the $tz to use the servers timezone setting

		$system_work_start_date =
			$lib->gmt_to_format(
				$ora->system_work_start_date,
				$mmddyyyy_hhmm_tz,
				$ora->system_timezone_name);

		$system_work_end_date   =
			$lib->gmt_to_format(
				$ora->system_work_end_date,
				$mmddyyyy_hhmm_tz,
				$ora->system_timezone_name);

		$system_work_duration   = $ora->system_work_duration;         // Work Duration
	}

	$system_respond_by_date = $lib->gmt_to_format($ora->system_respond_by_date, $mmddyyyy_tz, $tz);

	$row['ticket_no']                    = $ora->ticket_no;                    // hidden column (ticket_no)
	$row['system_id']                    = $ora->system_id;                    // hidden column (system_id) (key)
	$row['system_hostname']              = $ora->system_hostname;              // Server
	$row['system_os']                    = $ora->system_os;                    // OS
	$row['system_usage']                 = $ora->system_usage;                 // Usage
	$row['system_location']              = $ora->system_location;              // Location
	$row['system_timezone_name']         = $ora->system_timezone_name;         // Time Zone
	$row['system_work_status']           = $ora->system_work_status;           // Work Status
	$row['total_contacts_responded']     = $ora->total_contacts_responded;     // # Responded
	$row['total_contacts_not_responded'] = $ora->total_contacts_not_responded; // # Not Responded
	$row['system_respond_by_date']       = $system_respond_by_date;            // Respond By
	$row['system_work_start_date']       = $system_work_start_date;            // Work Start
	$row['system_work_end_date']         = $system_work_end_date;              // Work End
	$row['system_work_duration']         = $system_work_duration;              // Work Duration
	$row['system_osmaint_weekly']        = $ora->system_osmaint_weekly;        // Maintenance Window

/*
SERVERS
===========================================
ticket_no                      = <hidden>
system_id                      = <hidden>
system_hostname                = Server
system_os                      = OS
system_usage                   = Usage
system_location                = Location
system_timezone_name           = Time Zone
system_work_status             = Work Status
total_contacts_responded       = Approved
total_contacts_not_responded   = Not Approved
system_respond_by_date         = Respond By
system_work_start_date         = Work Start
system_work_end_date           = Work End
system_work_duration           = Work Duration
system_osmaint_weekly          = Maintenance Window
*/

	echo json_encode($row);  // {"issue_ticket_no":"HDxxx","issue_category":"Incident-Complex",...}

	$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "SENDING: %s", json_encode($row));

	$count_records++;  // Count up the records to determine if we sent anything.
}

printf("]}\n");  // Close out the data stream
exit();

