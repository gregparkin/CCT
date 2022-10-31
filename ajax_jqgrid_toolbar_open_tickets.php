<?php
/**
 * ajax_jqgrid_toolbar_open_tickets.php
 *
 * @package   PhpStorm
 * @file      ajax_jqgrid_toolbar_open_tickets.php
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

$lib = new library();
$lib->debug_start('ajax_jqgrid_toolbar_open_tickets.html');
date_default_timezone_set('America/Denver');

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
//$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "user_groups: %s", $_SESSION['user_groups']);
//$lib->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $list);


foreach ($list as $group_name)
{
	//$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "group_name: %s", $group_name);

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
 * @brief  Returns the Oracle data type for cct7_tickets field names
 *
 * @param  string $field_name
 *
 * @return string Data type
 */
function fieldType($field_name)
{
	//
	// All the data types in cct7_tickets are either VARCHAR2 or NUMBER. So to make things easy
	// we will just match on field names that are NUMBER's and consider the rest VARCHAR2's.
	//
	switch ($field_name)
	{
		case 'insert_date':
		case 'update_date':
		case 'status_date':
		case 'email_reminder1_date':
		case 'email_reminder2_date':
		case 'email_reminder3_date':
		case 'respond_by_date':
		case 'schedule_start_date':
		case 'schedule_end_date':
		case 'remedy_cm_start_date':
		case 'remedy_cm_end_date':
			return "date";
		case 'total_servers_scheduled':
		case 'total_servers_waiting':
		case 'total_servers_approved':
		case 'total_servers_rejected':
		case 'total_servers_not_scheduled':
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
				//$dt = new DateTime($str, new DateTimeZone($tz));
				//$dt->setTimezone(new DateTimeZone('GMT'));
				$dt = new DateTime($str, new DateTimeZone('GMT'));
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


$ora = new oracle();

$my_request  = array();
$input       = array();
$input_count = 0;

if (isset($_SERVER['QUERY_STRING']))   // Parse QUERY_STRING if it exists
{
	parse_str($_SERVER['QUERY_STRING'], $my_request);  // Parses URL parameter options into an array called $my_request
	$input_count = count($my_request);                 // Get the count of the number of $my_request array elements.
}

//
// We should see something like this when $my_request is parsed.
//
// _search      = "true"
// action       = "action"
// direction    = "asc"
// filters      = {"groupOp":"AND","rules":[{"field":"ticket_no","op":"eq","data":"5456"}]}
// nd           = "1470665354656"
// order_by     = "order_by"
// page         = "1"
// rows         = "25"
// searchField  = ""
// searchOper   = ""
// searchstring = ""
// sidx         = ""
// sord         = "asc"
// where_clause = "where_clause"
//
$input          = json_decode(json_encode($my_request), FALSE);            // Convert $my_request into an object called $input

$page           = isset($input->page)           ? $input->page           : 1;
$limit          = isset($input->rows)           ? $input->rows           : 25; // get how many rows we want to have in the grid

$filters        = isset($input->filters)        ? $input->filters        : ''; // These are the search parameters used in building the sql where clause
$ticket_no      = isset($input->ticket_no)      ? $input->ticket_no      : '';
$where_clause   = isset($input->where_clause)   ? $input->where_clause   : '';
$order_by       = isset($input->order_by)       ? $input->order_by       : 't.ticket_no ';
$direction      = isset($input->direction)      ? $input->direction      : 'asc';
$what_tickets   = isset($input->what_tickets)   ? $input->what_tickets   : 'group';  // group, approve
$from_part2     = '';  // Used if $what_tickets = 'group';
$what_hostname  = isset($input->what_hostname)  ? $input->what_hostname  : '';
$what_netpin_no = isset($input->what_netpin_no) ? $input->what_netpin_no : '';
$what_cuid      = isset($input->what_cuid)      ? $input->what_cuid      : '';
$action         = isset($input->action)         ? $input->action         : '';
$approve_filter = isset($input->approve_filter) ? $input->approve_filter : '';

if (strtoupper(substr($what_tickets, 0, 4)) == 'CCT7')
{
	$where_clause = sprintf("where upper(t.ticket_no) = upper('%s') ", $what_tickets);
	//$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $where_clause);
}
else if (strtoupper(substr($what_tickets, 0, 2)) == 'CM')
{
	$where_clause = sprintf("where upper(t.cm_ticket_no) = upper('%s') ", $what_tickets);
	//$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $where_clause);
}
else
{
	//$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "what_tickets does not match CCT7 or CM (%s)", $what_tickets);
}

//$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "user_groups: %s", $user_groups);

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

$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "       what_tickets: %s", $what_tickets);
$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "      what_hostname: %s", $what_hostname);
$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "     what_netpin_no: %s", $what_netpin_no);
$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "          what_cuid: %s", $what_cuid);

$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "            filters: %s", $filters);
$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "       where_clause: %s", $where_clause);
$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "search_where_clause: %s", $search_where_clause);
$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "             action: %s", $action);
$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "     approve_filter: %s", $approve_filter);

if (strlen($what_netpin_no) > 0)
{
	$lib->debug1(  __FILE__, __FUNCTION__, __LINE__, "what_netpin_no > 0");

	// "  t.status != 'DRAFT' " .
	$from_part2  = "(";
	$from_part2 .= "select distinct ";
	$from_part2 .= "  t.ticket_no ";
	$from_part2 .= "from ";
	$from_part2 .= "  cct7_tickets t, ";
	$from_part2 .= "  cct7_systems s, ";
	$from_part2 .= "  cct7_contacts c ";
	$from_part2 .= "where ";
	$from_part2 .= "  c.contact_netpin_no = '" . $what_netpin_no . "' and ";
	$from_part2 .= "  s.system_id = c.system_id and ";
	$from_part2 .= "  t.ticket_no = s.ticket_no ";
	$from_part2 .= "order by ";
	$from_part2 .= "  t.ticket_no) m";

	$where_clause = " where (t.ticket_no = m.ticket_no)";
}
else if (strlen($what_cuid) > 0)
{
	$lib->debug1(  __FILE__, __FUNCTION__, __LINE__, "what_cuid > 0");

	$where_clause = " where (lower(t.owner_cuid) = lower('" . $what_cuid . "'))";
}
else if (strlen($what_hostname) > 0)
{
	$lib->debug1(  __FILE__, __FUNCTION__, __LINE__, "what_hostname > 0");

	// "  t.status != 'DRAFT' " .

	$from_part2  = "(";
	$from_part2 .= "select distinct ";
	$from_part2 .= "  s.ticket_no ";
	$from_part2 .= "from ";
	$from_part2 .= "  cct7_tickets t, ";
	$from_part2 .= "  cct7_systems s ";
	$from_part2 .= "where ";
	$from_part2 .= "  lower(s.system_hostname) = lower('" . $what_hostname . "') and ";
	$from_part2 .= "  t.ticket_no = s.ticket_no ";
	$from_part2 .= "order by ";
	$from_part2 .= "  s.ticket_no) m";

	$where_clause = " where (t.ticket_no = m.ticket_no)";
}
else if ($what_tickets == 'group')
{
	$lib->debug1(  __FILE__, __FUNCTION__, __LINE__, "what_tickets == 'group'");

	$from_part2  = "(";
	$from_part2 .= "select distinct ";
	$from_part2 .= "  n1.user_cuid as user_cuid ";
	$from_part2 .= "from ";
	$from_part2 .= "  cct7_netpin_to_cuid n1 ";
	$from_part2 .= "where ";
	$from_part2 .= "  n1.net_pin_no " . $user_groups . " ";
	$from_part2 .= "order by ";
	$from_part2 .= "  n1.user_cuid ";
	$from_part2 .= ") m";
	$where_clause = " where (t.owner_cuid = m.user_cuid or t.manager_cuid = m.user_cuid)";
}
else if ($what_tickets == 'approve')
{
	$lib->debug1(  __FILE__, __FUNCTION__, __LINE__, "what_tickets == 'approve'");

	// select distinct system_work_status from cct7_systems;
	//
	// FAILED
	// REJECTED
	// STARTING
	// SUCCESS
	// UNKNOWN
	// CANCELED
	// APPROVED
	// WAITING
	// BACKOUT

	// "  t.status != 'DRAFT' " .

	$from_part2  = "(";
	$from_part2 .= "select distinct ";
	$from_part2 .= "  t.ticket_no ";
	$from_part2 .= "from ";
	$from_part2 .= "  cct7_tickets t, ";
	$from_part2 .= "  cct7_systems s, ";
	$from_part2 .= "  cct7_contacts c ";
	$from_part2 .= "where ";
	$from_part2 .= "  c.contact_netpin_no " . $user_groups . " and ";
	$from_part2 .= "  s.system_id = c.system_id and ";
	$from_part2 .= "  t.ticket_no = s.ticket_no and ";
	$from_part2 .= "  t.status = 'ACTIVE' and ";
	$from_part2 .= "  c.contact_approver_fyi = 'APPROVER' ";

	if ($approve_filter == "remove_approved")
	{
		$from_part2 .= "  and ";
		$from_part2 .= "  s.system_work_status = 'WAITING' and ";
		$from_part2 .= "  c.contact_response_status = 'WAITING' ";
	}

	$from_part2 .= "order by ";
	$from_part2 .= "  t.ticket_no) m";

	$where_clause = " where (t.ticket_no = m.ticket_no)";
}
else if (strlen($where_clause) > 0)
{
	$lib->debug1(  __FILE__, __FUNCTION__, __LINE__, "where_clause > 0");

	if ($what_tickets == 'group' || $what_tickets == 'approve')
	{
		$lib->debug1(  __FILE__, __FUNCTION__, __LINE__, "what_tickets = %s", $what_tickets);
		$where_clause .= " and t.status = 'ACTIVE'";
	}
}
else
{
	$lib->debug1(  __FILE__, __FUNCTION__, __LINE__, "*** else ***");

	if ($what_tickets == 'group' || $what_tickets == 'approve')
	{
		$lib->debug1(  __FILE__, __FUNCTION__, __LINE__, "what_tickets = %s", $what_tickets);
		$where_clause = " where t.status = 'ACTIVE'";
	}
}

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

$lib->debug1(  __FILE__, __FUNCTION__, __LINE__, "where_clause: %s", $where_clause);
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

//
// Calculate the total records that will be returned for the query. We need this for paging the result.
//
$current_page = $page;
$total_pages = 0;
$total_records = 0;

//$query  = "select count(*) as total_records from cct7_tickets where ";
//$query .= sprintf("cm_ticket_no = '%s'", $ticket_no);

if ($what_tickets == 'group' || $what_tickets == 'approve' || strlen($what_hostname) > 0 || strlen($what_netpin_no) > 0)
{
	$query  = "select count(*) as total_records from ";
	$query .= "cct7_tickets t, ";
	$query .= $from_part2;
	$query .= $where_clause;
}
else
{
	$query = "select count(*) as total_records from cct7_tickets t " . $where_clause;
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

$tz     = $_SESSION['local_timezone_abbr'];

// Create the SQL sort statement in the PHP web service

$SortIndex = isset($_GET['sidx']) ? $_GET['sidx'] : (isset($_POST['sidx']) ? $_POST['sidx'] : '');
$SortOrder = isset($_GET['sord']) ? $_GET['sord'] : (isset($_POST['sord']) ? $_POST['sord'] : 1);
$Order = '';
$Comma = '';
$SortIndices = explode(',', $SortIndex);
$order_by = '';

foreach ($SortIndices as $SortValue)
{
	$SortValues = explode(' ', trim($SortValue));

	foreach ($SortValues as $SortField)
	{
		if (strlen($SortField) > 0 && ($SortField == 'desc' || $SortField == 'asc'))
			$Order .= ' ' . $SortField;
		else if (strlen($SortField) > 0)
			$Order .= $Comma . 't.' . $SortField;
	}

	$Comma = ', ';
}

// ORDER BY hire_date DESC, 4, last_name ASC;

if (strlen($Order) > 0)
	$Order .= 'order by ' . $SortOrder; // $Order contains the full SQL sort string

$lib->debug1(  __FILE__, __FUNCTION__, __LINE__, "%s", $order_by);

if (isset($_SESSION['sort_list']) && strlen($_SESSION['sort_list']) > 0)
	$order_by = 'order by ' . $_SESSION['sort_list'];

if (strlen($order_by) == 0)
	$order_by = 'order by t.ticket_no';

if ($what_tickets == 'group' || $what_tickets == 'approve' || strlen($what_hostname) > 0 || strlen($what_netpin_no) > 0)
{
	$query = "select t.* ";
	/**
	 * Getting: ORA-00918 column ambiguously defined

	$query .= "  t.ticket_no, ";
	$query .= "  t.insert_date, ";
	$query .= "  t.insert_cuid, ";
	$query .= "  t.insert_name, ";
	$query .= "  t.update_date, ";
	$query .= "  t.update_cuid, ";
	$query .= "  t.update_name, ";
	$query .= "  t.status, ";
	$query .= "  t.status_date, ";
	$query .= "  t.status_cuid, ";
	$query .= "  t.status_name, ";
	$query .= "  t.owner_cuid, ";
	$query .= "  t.owner_first_name, ";
	$query .= "  t.owner_name, ";
	$query .= "  t.owner_email, ";
	$query .= "  t.owner_job_title, ";
	$query .= "  t.manager_cuid, ";
	$query .= "  t.manager_first_name, ";
	$query .= "  t.manager_name, ";
	$query .= "  t.manager_email, ";
	$query .= "  t.manager_job_title, ";
	$query .= "  t.work_activity, ";
	$query .= "  t.approvals_required, ";
	$query .= "  t.reboot_required, ";
	$query .= "  t.email_reminder1_date, ";
	$query .= "  t.email_reminder2_date, ";
	$query .= "  t.email_reminder3_date, ";
	$query .= "  t.respond_by_date, ";
	$query .= "  t.schedule_start_date, ";
	$query .= "  t.schedule_end_date, ";
	$query .= "  t.work_description, ";
	$query .= "  t.work_implementation, ";
	$query .= "  t.work_backoff_plan, ";
	$query .= "  t.work_business_reason, ";
	$query .= "  t.work_user_impact, ";
	$query .= "  t.cm_ticket_no, ";
	$query .= "  t.remedy_cm_start_date, ";
	$query .= "  t.remedy_cm_end_date, ";
	$query .= "  t.schedule_start_date, ";
	$query .= "  t.schedule_end_date, ";
	$query .= "  t.total_servers_scheduled, ";
	$query .= "  t.total_servers_waiting, ";
	$query .= "  t.total_servers_approved, ";
	$query .= "  t.total_servers_rejected, ";
	$query .= "  t.total_servers_not_scheduled, ";
	$query .= "  t.servers_not_scheduled, ";
	$query .= "  t.generator_runtime, ";
	$query .= "  t.csc_banner1, ";
	$query .= "  t.csc_banner2, ";
	$query .= "  t.csc_banner3, ";
	$query .= "  t.csc_banner4, ";
	$query .= "  t.csc_banner5, ";
	$query .= "  t.csc_banner6, ";
	$query .= "  t.csc_banner7, ";
	$query .= "  t.csc_banner8, ";
	$query .= "  t.csc_banner9, ";
	$query .= "  t.csc_banner10 ";
	 */
	$query .= "from ";
	$query .= "  cct7_tickets t, ";
	$query .= $from_part2;
	$query .= $where_clause . $order_by;

	if ($action !== "excel")
	{
		$query .= sprintf(" OFFSET %d ROWS FETCH NEXT %d ROWS ONLY", $start - 1, $limit);
	}
}
else
{
	$query  = "select t.* from cct7_tickets t ";
	$query .= $where_clause . $order_by;

	if ($action !== "excel")
	{
		$query .= sprintf(" OFFSET %d ROWS FETCH NEXT %d ROWS ONLY", $start - 1, $limit);
	}
}

$lib->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $query);

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

	$created       = $lib->gmt_to_format($ora->insert_date, $mmddyyyy_hhmm_tz, $tz);

	//
	// Don't really use Remedy CM start/End date ranges, but we do look at schedule_start_date and schedule_end_date
	// which is calculated by SQL procedure updateScheduleDates(<ticket_no>)
	//

	//$remedy_cm_start_date = $lib->gmt_to_format($ora->remedy_cm_start_date, $mmddyyyy_hhmm, $tz);
	//$remedy_cm_end_date   = $lib->gmt_to_format($ora->remedy_cm_end_date,   $mmddyyyy_hhmm, $tz);

	if ($ora->schedule_start_date == 0)
	{
		$schedule_start_date = "(Contact Greg)";
		$schedule_end_date   = "(Contact Greg)";
	}
	else
	{
		$schedule_start_date =
			$lib->gmt_to_format(
				$ora->schedule_start_date,
				$mmddyyyy_hhmm_tz,
				$tz);

		$schedule_end_date   =
			$lib->gmt_to_format(
				$ora->schedule_end_date,
				$mmddyyyy_hhmm_tz,
				$tz);
	}

	$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "schedule_start_date: %d - %s - %s", $ora->schedule_start_date, $schedule_start_date, $tz);
	$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "schedule_end_date:   %d - %s - %s", $ora->schedule_end_date,   $schedule_end_date,   $tz);

	$respond_by_date      = $lib->gmt_to_format($ora->respond_by_date, $mmddyyyy_tz, $tz);

	$row['cm_ticket_no']              = $ora->cm_ticket_no;                   // Remedy CM #
	$row['ticket_no']                 = $ora->ticket_no;                      // CCT Ticket #
	$row['work_activity']             = $ora->work_activity;                  // Work Activity
	$row['status']                    = $ora->status;                         // CCT Status
	$row['insert_date']               = $created;                             // Created
	$row['owner_cuid']                = $ora->owner_cuid;                     // Owner CUID
	$row['owner_name']                = $ora->owner_name;                     // Owner Name

	//$row['remedy_cm_start_date']      = $remedy_cm_start_date;                // Schedule From
	//$row['remedy_cm_end_date']        = $remedy_cm_end_date;                  // Schedule To

	$row['schedule_start_date']       = $schedule_start_date;                 // Schedule From
	$row['schedule_end_date']         = $schedule_end_date;                   // Schedule To

	$row['approvals_required']        = $ora->approvals_required;             // Approvals
	$row['reboot_required']           = $ora->reboot_required;                // Reboots
	$row['respond_by_date']           = $respond_by_date;                     // Respond By
	$row['total_servers_scheduled']   = $ora->total_servers_scheduled;        // TOTAL
	$row['total_servers_waiting']     = $ora->total_servers_waiting;          // WAITING
	$row['total_servers_approved']    = $ora->total_servers_approved;         // APPROVED
	$row['total_servers_rejected']    = $ora->total_servers_rejected;         // REJECTED

/*
cm_ticket_no            = Remedy CM #
ticket_no               = CCT Ticket #
work_activity           = Work Activity
status                  = CCT Status
insert_date             = Created
owner_cuid              = Owner CUID (hidden)
owner_name              = Owner Name
remedy_cm_start_date    = Schedule From
remedy_cm_end_date      = Schedule To
approvals_required      = Approvals
reboot_required         = Reboots
respond_by_date         = Respond By
total_servers_scheduled = TOTAL
total_servers_waiting   = WAITING
total_servers_approved  = APPROVED
total_servers_rejected  = REJECTED
	*/
	echo json_encode($row);  // {"issue_ticket_no":"HDxxx","issue_category":"Incident-Complex",...}

	$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "SENDING: %s", json_encode($row));

	$count_records++;  // Count up the records to determine if we sent anything.
}

printf("]}\n");  // Close out the data stream
exit();

