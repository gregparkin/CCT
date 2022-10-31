<?php
/**
 * @package    CCT
 * @file       cct_initialize.php
 * @author     Greg Parkin <gregparkin58@gmail.com>
 * @date       $Date:  $ GMT
 * @version    $Revision:  $
 *
 * $Log:  $
 *
 * $Source:  $
 */

//
// Called once when a user signs into CCT
// The purpose of this module is to figure out what time zone a user is in and to retrieve
// their personal information from cct7_mnet and cct7_users. $_SESSION[] variables are set
// to make the information available to all the programs during their session.    
//

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

ini_set("error_reporting",        "E_ALL & ~E_DEPRECATED & ~E_STRICT");
ini_set("log_errors",             1);
ini_set("error_log",              "/opt/ibmtools/cct7/logs/php-error.log");
ini_set("log_errors_max_len",     0);
ini_set("report_memleaks",        1);
ini_set("track_errors",           1);
ini_set("html_errors",            1);
ini_set("ignore_repeated_errors", 0);
ini_set("ignore_repeated_source", 0);

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

if (isset($_SESSION['cct_init']))
    exit();

$_SESSION['BUILD_VERSION'] = "7.3";
$_SESSION['BUILD_DATE']    = "07/25/2017";

$_SESSION['CCT_INIT'] = "READY";    

$ora = new oracle();
$lib = new library();
date_default_timezone_set('America/Denver');

switch ( $_SERVER['SERVER_NAME'] )
{
    case 'cct.corp.intranet':
        $_SESSION['REMOTE_USER']       = $_SERVER['REMOTE_USER'];
        $_SESSION['WWW']               = 'https://cct.corp.intranet';
        $_SESSION['CCT_APPLICATION']   = 'Change Coordination Tool - lxomp47x';
        $_SESSION['CCT_SERVER']        = 'Production Server';
        $_SESSION['DOCUMENT_ROOT']     = $_SERVER['DOCUMENT_ROOT'];
        $_SESSION['HTML_TITLE']        = 'CCT 7.0';
        $_SESSION['COUNTER_FILE']      = '/opt/ibmtools/cct7/etc/cct_page_counts';
        break;
    case 'lxomp47x.corp.intranet':
        $_SESSION['REMOTE_USER']       = $_SERVER['REMOTE_USER'];
        $_SESSION['WWW']               = 'https://lxomp47x.corp.intranet/cct7';
        $_SESSION['CCT_APPLICATION']   = 'Change Coordination Tool - lxomp47x';
        $_SESSION['CCT_SERVER']        = 'Production Server';
        $_SESSION['DOCUMENT_ROOT']     = $_SERVER['DOCUMENT_ROOT'];
        $_SESSION['HTML_TITLE']        = 'CCT 7.0';
        $_SESSION['COUNTER_FILE']      = '/opt/ibmtools/cct7/etc/cct_page_counts';
        break;

    case 'cct.test.intranet':
        $_SESSION['REMOTE_USER']       = $_SERVER['REMOTE_USER'];
        $_SESSION['WWW']               = 'https://cct.test.intranet';
        $_SESSION['CCT_APPLICATION']   = 'Change Coordination Tool - vlodts022';
        $_SESSION['CCT_SERVER']        = 'Test Server';
        $_SESSION['DOCUMENT_ROOT']     = $_SERVER['DOCUMENT_ROOT'];
        $_SESSION['HTML_TITLE']        = 'CCT 7.0';
        $_SESSION['COUNTER_FILE']      = '/opt/ibmtools/cct7/etc/cct_page_counts';
        break;
    case 'vlodts022.test.intranet':
        $_SESSION['REMOTE_USER']       = $_SERVER['REMOTE_USER'];
        $_SESSION['WWW']               = 'https://vlodts022.test.intranet';
        $_SESSION['CCT_APPLICATION']   = 'Change Coordination Tool - vlodts022';
        $_SESSION['CCT_SERVER']        = 'Test Server';
        $_SESSION['DOCUMENT_ROOT']     = $_SERVER['DOCUMENT_ROOT'];
        $_SESSION['HTML_TITLE']        = 'CCT 7.0';
        $_SESSION['COUNTER_FILE']      = '/opt/ibmtools/cct7/etc/cct_page_counts';
        break;
    default:
        $_SESSION['REMOTE_USER']       = "gparkin";
        $_SESSION['WWW']               = 'http://cct7.localhost';
        $_SESSION['CCT_APPLICATION']   = 'Change Coordination Tool - LENOVO';
        $_SESSION['CCT_SERVER']        = 'Development Server';
        $_SESSION['DOCUMENT_ROOT']     = $_SERVER['DOCUMENT_ROOT'];
        $_SESSION['HTML_TITLE']        = 'Change Coordination Tool - LENOVO';
        $_SESSION['COUNTER_FILE']      = '/opt/ibmtools/cct7/etc/cct_page_counts';
        break;
}

if ($_SESSION['REMOTE_USER'] === 'gparkin')
{
    $_SESSION['user_access_level'] = "admin";
    $_SESSION['is_debug_on']       = 'Y';
    $_SESSION['debug_level1']      = 'Y';
    $_SESSION['debug_level2']      = 'Y';
    $_SESSION['debug_level3']      = 'Y';
    $_SESSION['debug_level4']      = 'Y';
    $_SESSION['debug_level5']      = 'Y';
    $_SESSION['debug_path']        = '/opt/ibmtools/cct7/debug/';
    $_SESSION['debug_mode']        = 'w';

    $lib->debug_start('cct_init.html');
}
else
{
    $_SESSION['user_access_level'] = "user";
    $_SESSION['is_debug_on']       = 'N';
    $_SESSION['debug_level1']      = 'N';
    $_SESSION['debug_level2']      = 'N';
    $_SESSION['debug_level3']      = 'N';
    $_SESSION['debug_level4']      = 'N';
    $_SESSION['debug_level5']      = 'N';
    $_SESSION['debug_path']        = '/opt/ibmtools/cct7/debug/';
    $_SESSION['debug_mode']        = 'w';
}

//
// Contact Footer information
//
$_SESSION['NET_GROUP_NAME']  = "AIM-TOOLS-BOM";   // Footer Remedy Assign Group for creating trouble tickets
$_SESSION['NET_PIN']         = "17340";           // Footer Net-Pin number for NET paging
$_SESSION['APP_ACRONYM']     = 'CCT';             // Change Coordination Tool acronym - CCT

//
// The time zone name (i.e. America/Chicago) is pasted as an argument to this script.
// Next we create a DateTimeZone object using the $local_timezone_name. Then we get
// a DateTime object so we can return the time zone abbreviation name and GMT offset.
//
$local_timezone_name      = $_GET['timezone'];
$local_dtz                = new DateTimeZone($local_timezone_name);
$local_dt                 = new DateTime('now', $local_dtz);
$local_timezone_abbr      = $local_dt->format('T'); // (i.e. MST)
$local_timezone_offset    = $local_dt->getOffset();

//
// Our Oracle function fn_number_to_date() needs a number (UNIX mktime) value and
// a valid time zone abbreviation name in order to convert a number to a date.
//
// Not all timezones are identified in Oracle (such as Bangalore, India), so we will
// use Denver (MST7MDT) as a baseline and add another offset value to the datetime
// number to convert it to the proper user's timezone. So below we get the baseline
// information for Denver, Colorado.
//
$baseline_timezone_name   = 'America/Denver';
$baseline_dtz             = new DateTimeZone($baseline_timezone_name);
$baseline_dt              = new DateTime('now', $baseline_dtz);
$baseline_timezone_abbr   = $baseline_dt->format('T'); // (i.e. MST)
$baseline_timezone_offset = $baseline_dt->getOffset();

//
// $sql_time_offset and $sql_time_zone are pasted to all the SQL SELECT builder code to convert
// dates (represented as numbers) into the local user dates.
//
// Exa.: select fn_number_to_date(cm_start_date + $sql_time_offset, '$sql_time_zone') as cm_start_time from cct7_tickets;
//       select fn_number_to_date(1428602318 + (-3600), 'MDT') as cm_start_time from cct7_tickets;
//
// $sql_time_offset can be a positive or negative number so we will put it in ( ) just to make sure Oracle
// can deal with the calculation.
//
$sql_zone_offset      = "(" . $baseline_timezone_offset - $local_timezone_offset . ")";
$sql_zone_abbr        = $baseline_timezone_abbr;

$_SESSION['remedy_timezone']           = $local_timezone_name  . " (" . $local_timezone_abbr . ")";
$_SESSION['remedy_timezone_name']      = $local_timezone_name;
$_SESSION['remedy_timezone_abbr']      = $local_timezone_abbr;
$_SESSION['remedy_timezone_offset']    = $local_timezone_offset;

//
// Put all this information in the user's session cache so it can be used by all the PHP programs.
//
// All this information gets recorded in the user's settings record found in table cct7_user_settings for
// debugging purposes.
//
// $_SESSION['local_timezone']           = $local_timezone_name  . " (" . $local_timezone_abbr . ")";
// $_SESSION['local_timezone_name']      = $local_timezone_name;
// $_SESSION['local_timezone_abbr']      = $local_timezone_abbr;
// $_SESSION['local_timezone_offset']    = $local_timezone_offset;

$_SESSION['local_timezone']           = $baseline_timezone_name . " (" . $baseline_timezone_abbr . ")";
$_SESSION['local_timezone_name']      = $baseline_timezone_name;
$_SESSION['local_timezone_abbr']      = $baseline_timezone_abbr;
$_SESSION['local_timezone_offset']    = $baseline_timezone_offset;

$_SESSION['sql_zone_offset']          = $sql_zone_offset;
$_SESSION['sql_zone_abbr']            = $sql_zone_abbr;

$_SESSION['baseline_timezone']        = $baseline_timezone_name . " (" . $baseline_timezone_abbr . ")";
$_SESSION['baseline_timezone_name']   = $baseline_timezone_name;
$_SESSION['baseline_timezone_abbr']   = $baseline_timezone_abbr;
$_SESSION['baseline_timezone_offset'] = $baseline_timezone_offset;

$_SESSION['time_difference']          = sprintf("%d seconds or %d hours", $sql_zone_offset, $sql_zone_offset / 3600);

$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "Getting cct7_mnet record for user_cuid=%s", $_SERVER['REMOTE_USER']);

//
// Retrieve this users list of NET group pins and Subscriber pins.

$user_groups_hash = array();
$remote_user = $_SESSION['REMOTE_USER'];

$query =
	sprintf("select * from cct7_netpin_to_cuid where user_cuid = '%s'", $remote_user);

if ($ora->sql2($query) == false)
{
	$lib->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $ora->sql_statement);
	$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $ora->dbErrMsg);
}

while ($ora->fetch())
{
	// $user_groups_hash[$remote_user] = $ora->net_pin_no;
	$user_groups_hash[$ora->net_pin_no] = $remote_user;
}

$query =
	sprintf("select * from cct7_subscriber_members where member_cuid = '%s'",
			$_SESSION['REMOTE_USER']);

if ($ora->sql2($query) == false)
{
	$lib->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $ora->sql_statement);
	$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $ora->dbErrMsg);
}

while ($ora->fetch())
{
	// $user_groups_hash[$remote_user] = $ora->group_id;
	$user_groups_hash[$ora->group_id] = $remote_user;
}

$user_groups = '';

foreach ($user_groups_hash as $netpin => $cuid)
{
	if (strlen($user_groups) == 0)
	{
		$user_groups = $netpin;
	}
	else
	{
		$user_groups .= "," . $netpin;
	}
}

//
// Retrieve user information so we can store it in the session cache.
//
$query = "select * from cct7_mnet where ";
$query .= sprintf("lower(mnet_cuid) = lower('%s') or ", $_SESSION['REMOTE_USER']);
$query .= sprintf("lower(mnet_workstation_login) = lower('%s')", $_SESSION['REMOTE_USER']);

if ($ora->sql($query) == false)
{
    $lib->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $ora->sql_statement);
    $lib->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $ora->dbErrMsg);
}

if ($ora->fetch())
{
    $lib->debug1(__FILE__, __FUNCTION__, __LINE__, "Got cct7_mnet record for user %s", $_SERVER['REMOTE_USER']);

    //
    // Copy this user's MNET data into their $_SESSION data variables
    //
    $_SESSION['real_cuid'] = $ora->mnet_cuid;
    $_SESSION['real_name'] =
        (empty($ora->mnet_nick_name)
            ? $ora->mnet_first_name
            : $ora->mnet_nick_name) . " " . $ora->mnet_last_name;
	$_SESSION['real_user_groups'] = $user_groups;

    $_SESSION['user_cuid']        = $ora->mnet_cuid;
    $_SESSION['user_first_name']  = $ora->mnet_first_name;
    $_SESSION['user_last_name']   = $ora->mnet_last_name;
    $_SESSION['user_name']        = $ora->mnet_name;
    $_SESSION['user_email']       = $ora->mnet_email;
    $_SESSION['user_company']     = $ora->mnet_company;
    $_SESSION['user_job_title']   = $ora->mnet_job_title;
    $_SESSION['user_work_phone']  = $ora->mnet_work_phone;
	$_SESSION['user_groups']      = $user_groups;

    //
    // Does the users manager's cuid exist?
    //
    if (!empty($ora->mnet_mgr_cuid))
    {
        $lib->debug1(__FILE__, __FUNCTION__, __LINE__,
                     "Getting cct7_mnet record for user %s managers cuid=%s",
                     $_SERVER['REMOTE_USER'], $ora->mnet_mgr_cuid);

        //
        // Retrieve the manager's cuid from MNET
        //
        if ($ora->sql("select * from cct7_mnet where mnet_cuid = '" . $ora->mnet_mgr_cuid . "'") == false)
        {
            $lib->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $ora->sql_statement);
            $lib->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $ora->dbErrMsg);
        }

        if ($ora->fetch())
        {
            $lib->debug1(__FILE__, __FUNCTION__, __LINE__,
                "Got cct7_mnet record for user %s manager's cuid %s",
                $_SESSION['REMOTE_USER'], $ora->mnet_cuid);
            //
            // Add the users manager's MNET data to the $_SESSION data
            //
            $_SESSION['manager_cuid']       = $ora->mnet_cuid;
            $_SESSION['manager_first_name'] = $ora->mnet_first_name;
            $_SESSION['manager_last_name']  = $ora->mnet_last_name;
            $_SESSION['manager_name']       = $ora->mnet_name;
            $_SESSION['manager_email']      = $ora->mnet_email;
            $_SESSION['manager_company']    = $ora->mnet_company;
            $_SESSION['manager_job_title']  = $ora->mnet_job_title;
			$_SESSION['manager_work_phone'] = $ora->mnet_work_phone;
        }
    }

    //
	// India time zones: -- Asia/Kolkata   Asia/Shanghai
	//
	// ab60310 defaults to America/Denver
	// Street:  manytha Embassy Business Park
	// City:    Bangalore
	// State:   NO
	// Country: IND

    //
    // Next we want to create or update the cct7_users record for this user and retrieve their
    // debugging session data. Debugging is only for the CCT admins, but all debugging settings are
    // now located in the cct7_users table instead of everyone using the global settings found in
    // the old cct7_debugging table.
    //
    // First off set see if the user record in cct7_users exist. If not create a new record. If it
    // does exist, then update the record with information about their browser settings, when they
    // lasted logged, and their local timezone information. The information about their browser and
    // local timezone is used debugging problems with CCT. It's helpful to know what browser they are
    // using where they are located in the world.
    //
    $query = "select * from cct7_users where user_cuid = '" . $_SESSION['REMOTE_USER'] . "'";

    if ($ora->sql($query) == false)
    {
        $lib->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $ora->sql_statement);
        $lib->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $ora->dbErrMsg);
    }

    if ($ora->fetch() == false)
    {
        //
        // Record does not exist so lets create one for this user.
        //
        $rc = $ora
            ->insert("cct7_users")
            ->column("user_cuid")
            ->column("insert_cuid")
            ->column("insert_name")
            ->column("local_timezone")
            ->column("local_timezone_name")
            ->column("local_timezone_abbr")
            ->column("local_timezone_offset")
            ->column("baseline_timezone")
            ->column("baseline_timezone_name")
            ->column("baseline_timezone_abbr")
            ->column("baseline_timezone_offset")
            ->column("time_difference")
            ->column("sql_zone_offset")
            ->column("sql_zone_abbr")
            ->column("last_login_date")
            ->column("http_user_agent")
			->column("user_groups")
            ->value("char", $_SESSION['user_cuid'])                // user_cuid
            ->value("char", $_SESSION['user_cuid'])                // insert_cuid
            ->value("char", $_SESSION['user_name'])                // insert_name
            ->value("char", $_SESSION['local_timezone'])           // local_timezone
            ->value("char", $_SESSION['local_timezone_name'])      // local_timezone_name
            ->value("char", $_SESSION['local_timezone_abbr'])      // local_timezone_abbr
            ->value("int",  $_SESSION['local_timezone_offset'])    // local_timezone_offset
            ->value("char", $_SESSION['baseline_timezone'])        // baseline_timezone
            ->value("char", $_SESSION['baseline_timezone_name'])   // baseline_timezone_name
            ->value("char", $_SESSION['baseline_timezone_abbr'])   // baseline_timezone_abbr
            ->value("int",  $_SESSION['baseline_timezone_offset']) // baseline_timezone_offset
            ->value("char", $_SESSION['time_difference'])          // time_difference
            ->value("char", $_SESSION['sql_zone_offset'])          // sql_zone_offset
            ->value("char", $_SESSION['sql_zone_abbr'])            // sql_zone_abbr
            ->value("now")                                         // last_login_date
            ->value("char", $_SERVER['HTTP_USER_AGENT'])           // http_user_agent
			->value("char", $user_groups)                          // user_groups
            ->execute();

        if ($rc == false)
        {
            $lib->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $lib->ora->sql_statement);
            $lib->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $lib->ora->dbErrMsg);
        }

        $ora->commit();

		//
		// Set the default user preferences as defined in the cct7_users.sql
		//
		$_SESSION['pref_toolbar_open'] = 'group';
		$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "pref_toolbar_open: %s", $_SESSION['pref_toolbar_open']);
    }
    else
    {
        //
        // Update the user's cct7_users record with the information we want to save.
        //
        $rc = $ora
            ->update("cct7_users")
            ->set("char", "update_cuid",              $_SESSION['user_cuid'])
            ->set("char", "update_name",              $_SESSION['user_cuid'])
            ->set("char", "local_timezone",           $_SESSION['local_timezone'])
            ->set("char", "local_timezone_name",      $_SESSION['local_timezone_name'])
            ->set("char", "local_timezone_abbr",      $_SESSION['local_timezone_abbr'])
            ->set("int",  "local_timezone_offset",    $_SESSION['local_timezone_offset'])
            ->set("char", "baseline_timezone",        $_SESSION['baseline_timezone'])
            ->set("char", "baseline_timezone_name",   $_SESSION['baseline_timezone_name'])
            ->set("char", "baseline_timezone_abbr",   $_SESSION['baseline_timezone_abbr'])
            ->set("int",  "baseline_timezone_offset", $_SESSION['baseline_timezone_offset'])
            ->set("char", "time_difference",          $_SESSION['time_difference'])
            ->set("char", "sql_zone_offset",          $_SESSION['sql_zone_offset'])
            ->set("char", "sql_zone_abbr",            $_SESSION['sql_zone_abbr'])
            ->set("now",  "last_login_date")
            ->set("char", "http_user_agent",          $_SERVER['HTTP_USER_AGENT'])
			->set("char", "user_groups",              $user_groups)
            ->where("char", "user_cuid", "=", $_SESSION['user_cuid'])
            ->execute();

        if ($rc == false)
        {
            $lib->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $ora->sql_statement);
            $lib->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $ora->dbErrMsg);
        }

        $ora->commit();

        //
        // Update the user's debugging session cache variables.
        //
        $_SESSION['user_access_level'] = $ora->user_or_admin;
        $_SESSION['is_debug_on']       = $ora->is_debug_on;
        $_SESSION['debug_level1']      = $ora->debug_level1;
        $_SESSION['debug_level2']      = $ora->debug_level2;
        $_SESSION['debug_level3']      = $ora->debug_level3;
        $_SESSION['debug_level4']      = $ora->debug_level4;
        $_SESSION['debug_level5']      = $ora->debug_level5;
        $_SESSION['debug_path']        = $ora->debug_path;
        $_SESSION['debug_mode']        = $ora->debug_mode;

		//
		// Update the user's preference settings
		//
		$_SESSION['pref_toolbar_open'] = $ora->pref_toolbar_open;
		$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "pref_toolbar_open: %s", $ora->pref_toolbar_open);
    }
}
else
{
	//
	// This $_SERVER['REMOTE_USER'] is not found in my copy of cct7_mnet which I get
	// from David Aho. So I need to see it to something or I will start to see errors in the programs
	// that use this information.
	//

	$_SESSION['real_cuid']        = $_SERVER['REMOTE_USER'];
	$_SESSION['real_name']        = "Yolanda Squatpump";
	$_SESSION['real_user_groups'] = "";

	$_SESSION['user_cuid']        = $_SERVER['REMOTE_USER'];
	$_SESSION['user_first_name']  = "Yolanda";
	$_SESSION['user_last_name']   = "Squatpump";
	$_SESSION['user_name']        = "Yolanda Squatpump";
	$_SESSION['user_email']       = "no-reply.CMP.com";
	$_SESSION['user_company']     = "Walmart";
	$_SESSION['user_job_title']   = "Janitor";
	$_SESSION['user_work_phone']  = "988 999-3377";
	$_SESSION['user_groups']      = "";
}

