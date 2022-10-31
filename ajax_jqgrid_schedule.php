<?php
/**
 * ajax_jqgrid_schedule.php
 *
 * @package   PhpStorm
 * @file      ajax_jqgrid_schedule.php
 * @author    gparkin
 * @date      11/6/16
 * @version   7.0
 *
 * @brief     Retrieve a list of server lists owned by this user's group.
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

if (!isset($_SESSION['issue_changed']))
	$_SESSION['issue_changed'] = time();

if (isset($_SESSION['local_timezone_name']))
	$tz = $_SESSION['local_timezone_name'];
else
	$tz = 'America/Denver';


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

$lib = new library();       // classes/library.php
$lib->debug_start('ajax_jqgrid_schedule.html');
date_default_timezone_set('America/Denver');

$ora = new oracle();

$where_clause         = '';
$search_where_clause  = '';  // Constructed from $filter
$this_operation_group = '';
$this_operation_code  = '';
$this_field_name      = '';
$this_field_type      = '';

$my_request           = array();
$input                = array();
$input_count          = 0;

if (isset($_SERVER['QUERY_STRING']))   // Parse QUERY_STRING if it exists
{
	parse_str($_SERVER['QUERY_STRING'], $my_request);  // Parses URL parameter options into an array called $my_request
	$input_count = count($my_request);                 // Get the count of the number of $my_request array elements.
}

$input          = json_decode(json_encode($my_request), FALSE);            // Convert $my_request into an object called $input

$filters        = isset($input->filters)        ? $input->filters        : ''; // These are the search parameters used in building the sql where clause
$where_clause   = isset($input->where_clause)   ? $input->where_clause   : '';

$page           = isset($input->page)           ? $input->page           : 1;
$limit          = isset($input->rows)           ? $input->rows           : 25; // get how many rows we want to have in the grid

$action         = isset($input->action)         ? $input->action         : '';

$filter_button  = isset($input->filter_button)  ? $input->filter_button  : '';

$lib->debug1(  __FILE__, __FUNCTION__, __LINE__, "action=%s\n", $action);
$lib->debug1(  __FILE__, __FUNCTION__, __LINE__, "filter_button=%s", $filter_button);
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

$lib->debug1(  __FILE__, __FUNCTION__, __LINE__, "what_tickets=%s\n", $input->what_tickets);

if ($input->what_tickets == "all")
{
	$what_records = "ALL";
}
else
{
	$what_records = "GROUP";
}

$lib->debug1(  __FILE__, __FUNCTION__, __LINE__, "what_records: %s", $what_records);

//
// Do we have any search filters to apply to the $where_clause?
//
// [filters] => {"groupOp":"AND","rules":[{"field":"ticket_no","op":"ew","data":"81203"}]}
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

$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "search_where_clause: %s", $search_where_clause);

//
// Get the date rage for either yesterday, today, or tomorrow
//
$start_of_day = 0;  // (i.e.  07/04/2017 00:00)
$end_of_day   = 0;  // (i.e.  07/04/2017 23:59)

$start_of_day_string = "";
$end_of_day_string   = "";

switch ( $filter_button )
{
	case "yesterday":
		$lib->yesterday($start_of_day, $end_of_day, $start_of_day_string, $end_of_day_string);
		break;
	case "today":
		$lib->today($start_of_day, $end_of_day, $start_of_day_string, $end_of_day_string);
		break;
	case "tomorrow":
		$lib->tomorrow($start_of_day, $end_of_day, $start_of_day_string, $end_of_day_string);
		break;
	default:
		$lib->today($start_of_day, $end_of_day, $start_of_day_string, $end_of_day_string);
		break;
}

$lib->debug1(  __FILE__, __FUNCTION__, __LINE__, "start_of_day: %d (%s)", $start_of_day, $start_of_day_string);
$lib->debug1(  __FILE__, __FUNCTION__, __LINE__, "end_of_day:   %d (%s)", $end_of_day,   $end_of_day_string);

/**
 * @fn    dateToNumber($date_string)
 *
 * @brief  Convert a date string to a utime (int) value using the user's local time zone setting.
 *
 * @param string $date_string
 *
 * @return int utime
 */
function dateToNumber($date_string)
{
	global $local_timezone_abbr;

	$dt = new DateTime($date_string . ' ' . $local_timezone_abbr);

	return $dt->getTimestamp();
}

/**
 * @fn fieldType($field_name)
 *
 * @brief  Returns the Oracle data type for cct7_systems field names that we will be working with.
 *
 * @param  string $field_name
 *
 * @return string Data type
 */
function fieldType($field_name)
{
	switch ($field_name)
	{
		case 'system_work_start_date':
		case 'system_work_end_date':
			return "date";
		case 'system_id':
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
			$search_where_clause .= sprintf(" s.%s ", $str);
		}
		else
		{
			$search_where_clause .= sprintf(" %s s.%s ", $this_operation_group, $str);
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

function get()
{
	global $lib, $ora, $page, $limit, $what_records, $search_where_clause, $action;
	global $start_of_day, $end_of_day;

	$json = array();

	/**
	 * Available system_work_status values:
	 *
	 * WAITING
	 * APPROVED
	 * REJECTED
	 * CANCELED
	 * STARTING
	 * SUCCESS
	 * FAILED
	 */

	// Show work result for the past seven days forward onto the future

	//
	// Get the default timezone to the user's timezone
	//
	date_default_timezone_set($_SESSION['local_timezone_name']);

	//
	// Compute what the date was seven days ago.
	//
	$seven_days_ago = mktime(0, 0 , 0, date("m"), date("d") - 7, date("Y"));

	if ($what_records == "ALL")
	{
		$query_part2  = "from ";
		$query_part2 .= "  cct7_tickets t, ";
		$query_part2 .= "  cct7_systems s ";
		$query_part2 .= "where ";
		$query_part2 .= "  s.ticket_no = t.ticket_no and ";
		$query_part2 .= "  s.system_work_status != 'WAITING' and ";
		//$query_part2 .= "  s.system_work_end_date >= " . $seven_days_ago . " ";
		$query_part2 .= "  s.system_work_start_date >= " . $start_of_day . " and ";
		$query_part2 .= "  s.system_work_end_date <= " . $end_of_day . " ";

		if (strlen($search_where_clause) > 0)
		{
			$query_part2 .= "  and " . $search_where_clause . " ";
		}

		$query_part2 .= "order by ";
		$query_part2 .= "  s.system_work_start_date, s.ticket_no, s.system_hostname ";
	}
	else
	{
		$query_part2 = "from ";
		$query_part2 .= "  cct7_tickets t, ";
		$query_part2 .= "  cct7_systems s, ";
		$query_part2 .= "  (select distinct ";
		$query_part2 .= "    n.user_cuid ";
		$query_part2 .= "  from ";
		$query_part2 .= "    cct7_netpin_to_cuid n, ";
		$query_part2 .= "    (select net_pin_no from cct7_netpin_to_cuid where user_cuid = '" . $_SESSION['user_cuid'] . "') u ";
		$query_part2 .= "  where ";
		$query_part2 .= "    n.net_pin_no = u.net_pin_no) m ";
		$query_part2 .= "where ";
		$query_part2 .= "  t.owner_cuid = m.user_cuid and ";
		$query_part2 .= "  s.ticket_no = t.ticket_no and ";
		$query_part2 .= "  s.system_work_status != 'WAITING' and ";
		//$query_part2 .= "  s.system_work_end_date >= " . $seven_days_ago . " ";
		$query_part2 .= "  s.system_work_start_date >= " . $start_of_day . " and ";
		$query_part2 .= "  s.system_work_end_date <= " . $end_of_day . " ";

		if (strlen($search_where_clause) > 0)
		{
			$query_part2 .= "  and " . $search_where_clause . " ";
		}

		$query_part2 .= "order by ";
		$query_part2 .= "  s.system_work_start_date, s.ticket_no, s.system_hostname ";
	}

	//
	// Calculate the total records that will be returned for the query. We need this for paging the result.
	//
	$current_page  = $page;
	$total_pages   = 0;
	$total_records = 0;

	$query = "select count(*) as total_records " . $query_part2;

	$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "what_records: %s", $what_records);
	$lib->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $query);

	if ($ora->sql2($query) == false)
	{
		$lib->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $ora->sql_statement);
		$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $ora->dbErrMsg);
		$json['ajax_status']  = 'FAILED';
		$json['ajax_message'] = $ora->dbErrMsg;
		echo json_encode($json);
		exit();
	}

	$ora->fetch();
	$total_records = $ora->total_records;

	$lib->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $ora->sql_statement);
	$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "total_records = %d", $total_records);

	//
	// Calculate the total pages for the next query
	//
	if ($total_records > 0 && $limit > 0)
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

	$lib->debug1(__FILE__, __FUNCTION__, __LINE__,
				 "start = %d, limit = %d, page = %d, total_pages = %d", $start, $limit, $page, $total_pages);

	//
	// If for some reasons start position is negative set it to 1
	// typical case is that the user type 1 for the requested page
	//
	if ($start < 1)
	{
		$start = 1;
	}

	// System ID
	// ===========
	// s.system_id

	// Remedy Ticket   CCT Ticket   Work Activity    Hostname           Status                OS           Usage
	// =============   ===========  =============    =================  ====================  ===========  ==============
	// t.cm_ticket_no  t.ticket_no  t.work_activity  s.system_hostname  s.system_work_status  s.system_os  s.system_usage

	// Ticket Owner  Work Start                Work End                Duration                Reboot
	// ============  ========================  ======================  ======================  =================
	// t.owner_name  s.system_work_start_date  s.system_work_end_date  s.system_work_duration  t.reboot_required

	$query = "select ";
	$query .= "  s.system_id                as system_id, ";
	$query .= "  t.cm_ticket_no             as cm_ticket_no, ";
	$query .= "  t.ticket_no                as ticket_no, ";
	$query .= "  t.work_activity            as work_activity, ";
	$query .= "  s.system_hostname          as system_hostname, ";
	$query .= "  s.system_work_status       as system_work_status, ";
	$query .= "  s.system_os                as system_os, ";
	$query .= "  s.system_usage             as system_usage, ";
	$query .= "  t.owner_name               as owner_name, ";
	$query .= "  s.system_work_start_date   as system_work_start_date, ";
	$query .= "  s.system_work_end_date     as system_work_end_date, ";
	$query .= "  s.system_timezone_name     as system_timezone_name, ";
	$query .= "  s.system_work_duration     as system_work_duration, ";
	$query .= "  t.reboot_required          as reboot_required ";

	$query .= $query_part2;

	if ($action !== "excel")
	{
		$query .= sprintf(" OFFSET %d ROWS FETCH NEXT %d ROWS ONLY", $start - 1, $limit);
	}

	if ($ora->sql2($query) == false)
	{
		$lib->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $ora->sql_statement);
		$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $ora->dbErrMsg);
		$json['ajax_status']  = 'FAILED';
		$json['ajax_message'] = $ora->dbErrMsg;
		echo json_encode($json);
		exit();
	}

	printf("{\n");
	printf("\"ajax_status\":  \"SUCCESS\",\n");
	printf("\"ajax_message\": \"\",\n");
	printf("\"page\":         \"%s\",\n", $current_page);   // Current page
	printf("\"total\":        \"%s\",\n", $total_pages);    // Number of pages
	printf("\"records\":      \"%s\",\n", $total_records);  // # of records in total
	printf("\"rows\": [\n");                                // Array of data being returned

	$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "Current page          = %d", $current_page);
	$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "Number of pages       = %d", $total_pages);
	$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "# of records in total = %d", $total_records);

	$tz = $_SESSION['local_timezone_abbr'];

	$count_records = 0;

	$row = array();

	while ($ora->fetch())
	{
		if ($count_records > 0)
		{
			printf(",\n");  // Let the client know that another record is coming.
		}

		$row['system_id']              = $ora->system_id;
		$row['cm_ticket_no']           = $ora->cm_ticket_no;
		$row['ticket_no']              = $ora->ticket_no;
		$row['work_activity']          = $ora->work_activity;
		$row['system_hostname']        = $ora->system_hostname;

		if ($ora->system_work_status == "APPROVED")
		{
			$row['system_work_status']     = "READY";
		}
		else
		{
			$row['system_work_status']     = $ora->system_work_status;
		}

		$row['system_os']              = $ora->system_os;
		$row['system_usage']           = $ora->system_usage;
		$row['owner_name']             = $ora->owner_name;

		if ($ora->system_work_start_date == 0)
		{
			$row['system_work_start_date'] = '(See Remedy)';
			$row['system_work_end_date']   = '(See Remedy)';
			$row['system_work_duration']   = '(See Remedy)';
		}
		else
		{
			$row['system_work_start_date'] =
				$lib->gmt_to_format(
					$ora->system_work_start_date,
					'm/d/Y H:i T',
					$ora->system_timezone_name); // $tz);

			$row['system_work_end_date']   =
				$lib->gmt_to_format(
					$ora->system_work_end_date,
					'm/d/Y H:i T',
					$ora->system_timezone_name); // $tz);

			$row['system_work_duration']   = $ora->system_work_duration;
		}

		$row['reboot_required']        = $ora->reboot_required;

		echo json_encode($row);  // {"issue_ticket_no":"HDxxx","issue_category":"Incident-Complex",...}

		$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "SENDING: %s", json_encode($row));

		$count_records++;  // Count up the records to determine if we sent anything.
	}

	printf("]}\n");  // Close out the data stream

	exit();
}

get();


