<?php
/**
 * ajax_jqgrid_toolbar_open_old_contacts.php
 *
 * @package   PhpStorm
 * @file      ajax_jqgrid_toolbar_open_old_contacts.php
 * @author    gparkin
 * @date      6/29/16
 * @version   7.0
 *
 * @brief     Ajax calls this module to retrieve JSON data about contacts identified in cct7_systems records.
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
		case 'contact_insert_date':
		case 'contact_update_date':
		case 'contact_respond_by_date':
		case 'contact_response_date':
			return "date";
		case 'contact_id':
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
$lib->debug_start('ajax_jqgrid_toolbar_open_contacts.html');
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

$page         = isset($input->page)         ? $input->page         : 1;
$limit        = isset($input->rows)         ? $input->rows         : 25; // get how many rows we want to have in the grid
$filters      = isset($input->filters)      ? $input->filters      : ''; // These are the search parameters used in building the sql where clause
$ticket_no    = isset($input->ticket_no)    ? $input->ticket_no    : '';

$action       = isset($input->action)       ? $input->action       : '';
$system_id    = isset($input->system_id)    ? $input->system_id    : 0;

$lib->debug1(__FILE__, __FUNCTION__, __LINE__,   "action    = %s", $action);
$lib->debug1(__FILE__, __FUNCTION__, __LINE__,   "system_id = %s", $system_id);

$lib->debug1(__FILE__, __FUNCTION__, __LINE__,   "page      = %s", $page);
$lib->debug1(__FILE__, __FUNCTION__, __LINE__,   "limit     = %s", $limit);
$lib->debug1(__FILE__, __FUNCTION__, __LINE__,   "filters   = %s", $filters);
$lib->debug1(__FILE__, __FUNCTION__, __LINE__,   "ticket_no = %s", $ticket_no);

if (strlen($system_id) > 0)
	$where_clause = sprintf(" where t.system_id = %d ", $system_id);

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

//$query = "select count(*) as total_records from cct7_contacts where system_id = " . $system_id;

$query = "select count(*) as total_records from cct7_contacts t " . $where_clause;

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

//$query = "select * from cct7_contacts where system_id = " . $system_id . " order by contact_netpin_no " .
//	sprintf(" OFFSET %d ROWS FETCH NEXT %d ROWS ONLY", $start - 1, $limit);

$tz        = $_SESSION['local_timezone_abbr'];
$order_by  = "t.contact_netpin_no";
$direction = "asc";

$query  = "select * from cct7_contacts t ";
$query .= $where_clause . "order by " . $order_by . " " . $direction;
$query .= sprintf(" OFFSET %d ROWS FETCH NEXT %d ROWS ONLY", $start - 1, $limit);

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
$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "Number of pages       = %d", $total_pages);
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

	$contact_response_date = $lib->gmt_to_format($ora->contact_response_date, 'm/d/Y',      $tz);

	//
	// CONTACTS
	// ===========================================
	// contact_id              = (hidden)
	// system_id               = (hidden)
	// contact_netpin_no       = Netpin
	// contact_connection      = Connections
	// contact_server_os       = OS
	// contact_server_usage    = Usage
	// contact_work_group      = Group
	// contact_approver_fyi    = Type
	// contact_csc_banner      = CSC Banner
	// contact_apps_databases  = App or DB
	// contact_respond_by_date = Respond By
	// contact_response_status = Response
	// contact_response_date   = Response Date
	// contact_response_cuid   = Response CUID
	// contact_send_page       = Page On-Call
	// contact_send_email      = Send Email

	$row['contact_id']               = $ora->contact_id;               // hidden column (ticket_id)
	$row['system_id']                = $ora->system_id;                // hidden column (system_id) (key)
	$row['contact_netpin_no']        = $ora->contact_netpin_no;        // Netpin
	$row['contact_connection']       = $ora->contact_connection;       // Connections
	$row['contact_server_os']        = $ora->contact_server_os;        // OS
	$row['contact_server_usage']     = $ora->contact_server_usage;     // Usage
	$row['contact_work_group']       = $ora->contact_work_group;       // Group
	$row['contact_approver_fyi']     = $ora->contact_approver_fyi;     // Type
	$row['contact_csc_banner']       = $ora->contact_csc_banner;       // CSC Banner
	$row['contact_apps_databases']   = $ora->contact_apps_databases;   // App or DB
	$row['contact_respond_by_date']  = '08/08/2016';                   // Respond By
	$row['contact_response_status']  = $ora->contact_response_status;  // Response
	$row['contact_response_date']    = '08/07/2016 23:30'; // $contact_response_date;         // Response Date
	$row['contact_response_cuid']    = 'gparkin'; // $ora->contact_response_date;    // Response Name
	$row['contact_send_page']        = 'Y'; // $ora->contact_send_page;        // Page On-Call
	$row['contact_send_email']       = 'Y'; // $ora->contact_send_email;       // Send Email

	echo json_encode($row);  // {"issue_ticket_no":"HDxxx","issue_category":"Incident-Complex",...}

	$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "SENDING: %s", json_encode($row));

	$count_records++;  // Count up the records to determine if we sent anything.
}

printf("]}\n");  // Close out the data stream
exit();

