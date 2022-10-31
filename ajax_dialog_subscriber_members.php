<?php
/**
 * ajax_dialog_subscriber_members.php
 *
 * @package   PhpStorm
 * @file      ajax_dialog_subscriber_members.php
 * @author    gparkin
 * @date      7/1/16
 * @version   7.0
 *
 * @brief     Called by the following dialogs:
 *            - dialog_subscriber_servers_add.php
 *
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
// NOTE: It is very addant that you do not turn on debugging without writing to a file for server side AJAX code. Any HTML
//       comments coming from functions while writing JSON will show up in the JSON output and you will get a parsing error
//       in the client side program.
//
$lib = new library();        // classes/library.php
$lib->debug_start('ajax_dialog_subscriber_members.html');

// Read-only stream allows you to read raw data from the request body.
$input = json_decode(file_get_contents("php://input"), FALSE);
date_default_timezone_set('America/Denver');

// $input = json_decode(json_encode($my_request), FALSE);

$action      = '';
$group_id    = 0;
$member_list = '';
$list        = '';

if (isset($input->{'action'}))
	$action      = $input->action;

if (isset($input->{'group_id'}))
	$group_id    = $input->group_id;

if (isset($input->{'member_list'}))
	$member_list = $input->member_list;

if (isset($input->{'list'}))
	$list        = $input->{'list'};

$lib->debug1(__FILE__, __FUNCTION__, __LINE__,   "action = %s",     $action);
$lib->debug1(__FILE__, __FUNCTION__, __LINE__,   "group_id = %s",   $group_id);
$lib->debug1(__FILE__, __FUNCTION__, __LINE__,   "member_list: %s", $member_list);
$lib->debug1(__FILE__, __FUNCTION__, __LINE__,   "list: ");
$lib->debug_r1(__FILE__, __FUNCTION__, __LINE__, $list);

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

$json = array();

$ora = new oracle();

/**
 * @fn    add()
 *
 * @brief Add all servers identified by the user.
 *
 */
function add()
{
	global $ora, $lib, $json;
	global $group_id, $member_list;

	$current_cuids = array();

	$query = "select * from cct7_subscriber_members where ";
	$query .= sprintf("group_id = '%s'", $group_id);

	if ($ora->sql2($query) == false)
	{
		$json['ajax_status']  = 'FAILED';
		$json['ajax_message'] = $ora->error;
		echo json_encode($json);
		exit();
	}

	while ($ora->fetch())
	{
		$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "Current member_cuid: %s", $ora->member_cuid);
		$current_cuids[$ora->member_cuid] = $ora->member_id;
	}

	$lib->debug1(__FILE__, __FUNCTION__, __LINE__,   "member_list: %s", $member_list);

	//
	// split the $member_list by any number of commas or space characters, which include " ", \r, \t, \n and \f
	//
	$cuids = preg_split("/[\s,]+/", $member_list);
	$count = 0;

	foreach ($cuids as $member_cuid)
	{
		$lib->debug1(__FILE__, __FUNCTION__, __LINE__,   "member_cuid: %s", $member_cuid);

		//
		// Valid the cuid against cct7_mnet and make sure we have a valid email address.
		//
		$rc = $ora
			->select()
			->column("mnet_cuid")
			->column("mnet_name")
			->column("mnet_email")
			->from("cct7_mnet")
			->where("char", "mnet_cuid", "=", trim($member_cuid))
			->execute();

		if ($rc == false)
		{
			$json['ajax_status']  = 'FAILED';
			$json['ajax_message'] = $ora->error;
			echo json_encode($json);
			exit();
		}

		$ora->fetch();

		//
		// filter_var() available sence PHP 5.2.0
		//
		if (filter_var($ora->mnet_email, FILTER_VALIDATE_EMAIL))
		{
			$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "mnet_email looks good: %s", $ora->mnet_cuid);

			//
			// Insert the cuid in cct7_subscriber_members
			//
			$member_id = $ora->next_seq("cct7_subscriber_membersseq");

			// cct7_subscriber_members
			// member_id|NUMBER|0|NOT NULL|PK: Unique Record ID
			// group_id|VARCHAR2|20||FK: cct7_subscriber_groups
			// create_date|NUMBER|0||GMT date record was created
			// member_cuid|VARCHAR2|20||Member CUID
			// member_name|VARCHAR2|200||Member NAME
			//
			$query  = "insert into cct7_subscriber_members ( ";
			$query .= "  member_id, ";
			$query .= "  group_id, ";
			$query .= "  create_date, ";
			$query .= "  member_cuid, ";
			$query .= "  member_name ";
			$query .= ") values ( ";
			$query .= sprintf("%d, ",   $member_id);
			$query .= sprintf("'%s', ", $group_id);
			$query .= sprintf("%d, ",   $lib->now_to_gmt_utime());
			$query .= sprintf("'%s', ", $ora->mnet_cuid);
			$query .= sprintf("'%s')",  $ora->mnet_name);

			if ($ora->sql2($query) == false)
			{
				$json['ajax_status']  = 'FAILED';
				$json['ajax_message'] = $ora->error . " Unable to insert: " . $member_cuid;
				echo json_encode($json);
				exit();
			}

			++$count;
		}
	}

	$ora->commit();

	$query  = "select group_name from cct7_subscriber_groups where ";
	$query .= sprintf("group_id = '%s'", $group_id);

	if ($ora->sql2($query) == false)
	{
		$json['ajax_status']  = 'FAILED';
		$json['ajax_message'] = $ora->error;
		echo json_encode($json);
		exit();
	}

	$group_name = '';

	if ($ora->fetch() == true)
	{
		$group_name = $ora->group_name;
	}

	$json['ajax_status']  = 'SUCCESS';
	$json['ajax_message'] = sprintf("%s - Total members added: %d", $group_name, $count);
	echo json_encode($json);
	exit();
}

/**
 * @fn    delete()
 *
 * @brief Remove all the members identified in $list
 *
 */
function delete()
{
	global $ora, $lib, $list, $json, $group_id;

	$members = '';

	foreach ($list as $id)
	{
		$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "foreach: member_id = %d", $id);

		//
		// Put together a list of members we are removing.
		//
		$rc = $ora
			->select()
			->column('member_cuid')
			->from('cct7_subscriber_members')
			->where('int', 'member_id', '=', $id)
			->execute();

		if ($rc == false)
		{
			$lib->debug1(  __FILE__, __FUNCTION__, __LINE__, "%s - %s", $ora->sql_statement, $ora->dbErrMsg);
			$json['ajax_status']  = 'FAILED';
			$json['ajax_message'] = $ora->error;
			echo json_encode($json);
			exit();
		}

		$ora->fetch();

		if (strlen($members) > 0)
		{
			$members .= ", " . $ora->member_cuid;
		}
		else
		{
			$members = $ora->member_cuid;
		}

		//
		// Delete this member from cct7_subscriber_members.
		//
		$query = "delete from cct7_subscriber_members where member_id = " . $id;

		$lib->debug1(__FILE__, __FUNCTION__, __LINE__,   "%s", $query);

		if ($ora->sql2($query) == false)
		{
			$lib->debug1(  __FILE__, __FUNCTION__, __LINE__, "%s - %s", $ora->sql_statement, $ora->dbErrMsg);
			$json['ajax_status']  = 'FAILED';
			$json['ajax_message'] = $ora->error;
			echo json_encode($json);
			exit();
		}
	}

	$ora->commit();

	$query = sprintf("select group_name from cct7_subscriber_groups where group_id = '%s'", $group_id);

	if ($ora->sql2($query) == false)
	{
		$json['ajax_status']  = 'FAILED';
		$json['ajax_message'] = $ora->error;
		echo json_encode($json);
		exit();
	}

	$group_name = '';

	if ($ora->fetch() == true)
	{
		$group_name = $ora->group_name;
	}

	$json['ajax_status']  = 'SUCCESS';
	$json['ajax_message'] = sprintf("%s - Members removed: %s", $group_name, $members);
	echo json_encode($json);
	exit();
}

/**
 * @fn    delete_all()
 *
 * @brief Remove all the servers for this subscriber group_id
 *
 */
function delete_all()
{
	global $lib, $ora, $group_id, $json;

	$query  = "select group_name from cct7_subscriber_groups where ";
	$query .= sprintf("group_id = '%s'", $group_id);

	if ($ora->sql2($query) == false)
	{
		$lib->debug1(  __FILE__, __FUNCTION__, __LINE__, "%s - %s", $ora->sql_statement, $ora->dbErrMsg);
		$json['ajax_status']  = 'FAILED';
		$json['ajax_message'] = $ora->error;
		echo json_encode($json);
		exit();
	}

	$ora->fetch();

	$group_name = $ora->group_name;

	$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "action = delete_all");

	$query = "delete from cct7_subscriber_members where group_id = '" . $group_id . "'";

	$lib->debug1(__FILE__, __FUNCTION__, __LINE__,   "%s", $query);

	if ($ora->sql2($query) == false)
	{
		$lib->debug1(  __FILE__, __FUNCTION__, __LINE__, "%s - %s", $ora->sql_statement, $ora->dbErrMsg);
		$json['ajax_status']  = 'FAILED';
		$json['ajax_message'] = $ora->error;
		echo json_encode($json);
		exit();
	}

	$ora->commit();

	$json['ajax_status']  = 'SUCCESS';
	$json['ajax_message'] = sprintf("%s - All members have been removed.", $group_name);
	echo json_encode($json);
	exit();
}

switch ($action)
{
	case 'add':
		add();
		break;
	case 'delete':
		delete();
		break;
	case 'delete_all':
		delete_all();
		break;
	default:
		$lib->debug1(  __FILE__, __FUNCTION__, __LINE__, "Invalid action: %s", $action);
		$json['ajax_status']  = 'FAILED';
		$json['ajax_message'] = 'Invalid action: ' . $action;
		echo json_encode($json);
		exit();
}


