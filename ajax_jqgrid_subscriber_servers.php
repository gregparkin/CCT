<?php
/**
 * ajax_jqgrid_subscriber_servers.php
 *
 * @package   PhpStorm
 * @file      ajax_jqgrid_subscriber_servers.php
 * @author    gparkin
 * @date      8/26/16
 * @version   7.0
 *
 * @brief     Retrieve list of servers by group_id
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
$lib->debug_start('ajax_jqgrid_subscriber_servers.html');
date_default_timezone_set('America/Denver');

$ora = new oracle();

$my_request  = array();
$input       = array();
$input_count = 0;

if (isset($_SERVER['QUERY_STRING']))   // Parse QUERY_STRING if it exists
{
	parse_str($_SERVER['QUERY_STRING'], $my_request);  // Parses URL parameter options into an array called $my_request
	$input_count = count($my_request);                 // Get the count of the number of $my_request array elements.
}

$input          = json_decode(json_encode($my_request), FALSE);            // Convert $my_request into an object called $input

$group_id       = isset($input->group_id)       ? $input->group_id       : 0;
$page           = isset($input->page)           ? $input->page           : 1;
$limit          = isset($input->rows)           ? $input->rows           : 25; // get how many rows we want to have in the grid

$lib->debug1(  __FILE__, __FUNCTION__, __LINE__, "group_id: %d", $group_id);
$lib->debug_r1(__FILE__, __FUNCTION__, __LINE__, $my_request);
$lib->debug1(  __FILE__, __FUNCTION__, __LINE__, "input_count=%d\n", $input_count);
$lib->debug1(  __FILE__, __FUNCTION__, __LINE__, "input: ");
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
// cct7_subscriber_servers
//
// server_id|NUMBER|0|NOT NULL|PK: Unique Record ID
// group_id|VARCHAR2|20||FK: cct7_subscriber_groups
// member_id|NUMBER|0||FK: cct7_subscriber_members
// create_date|NUMBER|0||GMT creation date
// owner_cuid|VARCHAR2|20||Owner CUID
// owner_name|VARCHAR2|200||Owner NAME
// computer_hostname|VARCHAR2|255||Server Hostname
// computer_ip_address|VARCHAR2|64||Server IP Address
// computer_os_lite|VARCHAR2|20||Server Operating System
// computer_status|VARCHAR2|80||Server Status: PRODUCTION, DEVELOPMENT, etc.
// computer_managing_group|VARCHAR2|40||Server Managing Group name
// notification_type|VARCHAR2|20||Notification Type: APPROVER or FYI
//
function get()
{
	global $lib, $ora, $page, $limit, $group_id;

	$json = array();

	$query_part2 = "from ";
	$query_part2 .= "  cct7_subscriber_servers t ";
	$query_part2 .= "where ";
	$query_part2 .= "  t.group_id = '" . $group_id . "' ";
	$query_part2 .= "order by ";
	$query_part2 .= "  t.computer_hostname";

	//
	// Calculate the total records that will be returned for the query. We need this for paging the result.
	//
	$current_page  = $page;
	$total_pages   = 0;
	$total_records = 0;

	$query = "select count(*) as total_records " . $query_part2;

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

	//
	// cct7_subscriber_servers
	//
	// server_id|NUMBER|0|NOT NULL|PK: Unique Record ID
	// group_id|VARCHAR2|20||FK: cct7_subscriber_groups
	// create_date|NUMBER|0||GMT creation date
	// owner_cuid|VARCHAR2|20||Owner CUID
	// owner_name|VARCHAR2|200||Owner NAME
	// computer_hostname|VARCHAR2|255||Server Hostname
	// computer_ip_address|VARCHAR2|64||Server IP Address
	// computer_os_lite|VARCHAR2|20||Server Operating System
	// computer_status|VARCHAR2|80||Server Status: PRODUCTION, DEVELOPMENT, etc.
	// computer_managing_group|VARCHAR2|40||Server Managing Group name
	// notification_type|VARCHAR2|20||Notification Type: APPROVER or FYI
	//
	$query = "select ";
	$query .= "  t.server_id, ";
	$query .= "  t.group_id, ";
	$query .= "  t.create_date, ";
	$query .= "  t.owner_cuid, ";
	$query .= "  t.owner_name, ";
	$query .= "  t.computer_lastid, ";
	$query .= "  t.computer_hostname, ";
	$query .= "  t.computer_ip_address, ";
	$query .= "  t.computer_os_lite, ";
	$query .= "  t.computer_status, ";
	$query .= "  t.computer_managing_group, ";
	$query .= "  t.notification_type ";
	$query .= $query_part2;
	$query .= sprintf(" OFFSET %d ROWS FETCH NEXT %d ROWS ONLY", $start - 1, $limit);

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
		$server_count = 0;

		if ($count_records > 0)
		{
			printf(",\n");  // Let the client know that another record is coming.
		}

		$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "server_count = %d", $server_count);

		//
		// cct7_subscriber_servers
		//
		// server_id|NUMBER|0|NOT NULL|PK: Unique Record ID
		// group_id|VARCHAR2|20||FK: cct7_subscriber_groups
		// create_date|NUMBER|0||GMT creation date
		// owner_cuid|VARCHAR2|20||Owner CUID
		// owner_name|VARCHAR2|200||Owner NAME
		// computer_lastid|NUMBER|0||Asset Manager computer record ID
		// computer_hostname|VARCHAR2|255||Server Hostname
		// computer_ip_address|VARCHAR2|64||Server IP Address
		// computer_os_lite|VARCHAR2|20||Server Operating System
		// computer_status|VARCHAR2|80||Server Status: PRODUCTION, DEVELOPMENT, etc.
		// computer_managing_group|VARCHAR2|40||Server Managing Group name
		// notification_type|VARCHAR2|20||Notification Type: APPROVER or FYI
		//
		$row['server_id']               = $ora->server_id;
		$row['group_id']                = $ora->group_id;
		$row['create_date']             = $lib->gmt_to_format($ora->create_date, 'm/d/Y', $tz);
		$row['owner_cuid']              = $ora->owner_cuid;
		$row['owner_name']              = $ora->owner_name;
		$row['computer_lastid']         = $ora->computer_lastid;
		$row['computer_hostname']       = $ora->computer_hostname;
		$row['computer_ip_address']     = $ora->computer_ip_address;
		$row['computer_os_lite']        = $ora->computer_os_lite;
		$row['computer_status']         = $ora->computer_status;
		$row['computer_managing_group'] = $ora->computer_managing_group;
		$row['notification_type']       = $ora->notification_type;

		echo json_encode($row);  // {"issue_ticket_no":"HDxxx","issue_category":"Incident-Complex",...}

		$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "SENDING: %s", json_encode($row));

		$count_records++;  // Count up the records to determine if we sent anything.
	}

	printf("]}\n");  // Close out the data stream

	exit();
}

get();


