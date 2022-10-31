<?php
/**
 * find_cuid.php
 *
 * @package   PhpStorm
 * @file      find_cuid.php
 * @author    gparkin
 * @date      7/10/17
 * @version   7.0
 *
 * @brief     Locate the user cuid and other information by prompting user for their name.
 *
 */

// ---------------------------------------------

set_include_path("/opt/ibmtools/www/cct7/classes");

//
// Class autoloader - Removes the need to add include and require statements
//
if (!function_exists('__autoload'))
{
	require_once('classes/autoloader.php');
}

//
// Required to start once in order to retrieve user session information
//
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

//
// header() is a PHP function for modifying the HTML header information
// going to the client's browser. Here we tell their browser not to cache
// the page coming in so their browser will always rebuild the page from
// scratch instead of retrieving a copy of one from its cache.
//
// header("Expires: " . gmstrftime("%a, %d %b %Y %H:%M:%S GMT"));
//
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

//
// Disable buffering - This is what makes the loading screen work properly.
//
@apache_setenv('no-gzip', 1);
@ini_set('zlib.output_compression', 0);
@ini_set('output_buffering', 'Off');
@ini_set('implicit_flush', 1);

ob_implicit_flush(1); // Flush buffers

for ($i = 0, $level = ob_get_level(); $i < $level; $i++)
{
	ob_end_flush();
}

$lib = new library();  // classes/library.php
$lib->debug_start('find_cuid.html');
date_default_timezone_set('America/Denver');

$lib->globalCounter();

//
// URL Arguments
//
$argv = array();
$argc = 0;

//
// Parse QUERY_STRING
//
if (isset($_SERVER['QUERY_STRING']))   // Parse QUERY_STRING if it exists
{
	//printf("<!-- QUERY_STRING = %s -->\n", $_SERVER['QUERY_STRING']);

	//
	// http://www.foo.com/somepath?keyword1=value1&keyword2=value2&keyword3=value3
	//
	// $myQryStr = "first=1&second=Z&third[]=5000&third[]=6000";
	// parse_str($myQryStr, $myArray);
	// echo $myArray['first']; //will output 1
	// echo $myArray['second']; //will output Z
	// echo $myArray['third'][0]; //will output 5000
	// echo $myArray['third'][1]; //will output 6000
	//
	parse_str($_SERVER['QUERY_STRING'], $argv);  // Parses URL parameter options into an array called $this->argv
	$argc = count($argv);                        // Get the argv count. Number of items in the $argv array.
}

$msg = "&nbsp;";

// Array ( [new_cuid] => dsfg [button] => Login )

$first_name = '';
$last_name  = '';
$button     = '';

if (isset($argv['first_name']))
	$first_name = $argv['first_name'];

if (isset($argv['last_name']))
	$last_name = $argv['last_name'];

if (isset($argv['button']))
	$button = $argv['button'];

$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "first_name: %s", $first_name);
$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "last_name: %s",  $last_name);
$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "button: %s",     $button);

$ora = new oracle();

$mnet_cuid              = "";
$mnet_workstation_login = "";
$mnet_last_name         = "";
$mnet_first_name        = "";
$mnet_nick_name         = "";
$mnet_middle            = "";
$mnet_name              = "";
$mnet_job_title         = "";
$mnet_email             = "";
$mnet_work_phone        = "";
$mnet_street            = "";
$mnet_city              = "";
$mnet_state             = "";
$mnet_country           = "";
$mnet_company           = "";
$mnet_status            = "";
$mnet_change_date       = "";
$mnet_ctl_cuid          = "";
$mnet_mgr_cuid          = "";

$manager_name           = "";
$manager_email          = "";

$user_cuid              = "";
$local_timezone         = "";
$local_timezone_name    = "";
$local_timezone_abbr    = "";
$time_difference        = "";
$user_or_admin          = "";
$last_login_date        = "";
$http_user_agent        = "";
$user_groups            = "";

if ((strlen($first_name) > 0 && strlen($last_name) == 0) || (strlen($first_name) == 0 && strlen($last_name) > 0))
{
	$msg = "You need to type in both the first and last.";
}
else if (strlen($first_name) > 0 && strlen($last_name) > 0)
{
    $first_name = strtolower($first_name);

	$query  = "select * from cct7_mnet where ";
	$query .= sprintf("(lower(mnet_first_name) like '%s%%' or lower(mnet_nick_name) = '%s') and ",
                      $first_name, $first_name);
	$query .= sprintf("lower(mnet_last_name)  = lower('%s')", $last_name);

	if ($ora->sql2($query) == false)
	{
		$lib->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $ora->sql_statement);
		$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $ora->dbErrMsg);
		$msg = sprintf("SQL syntax error in file: %s, line: %d. Please contact Greg Parkin - %s",
					   __FILE__, __LINE__, $ora->dbErrMsg);
	}

	if ($ora->fetch())
	{
		$mnet_cuid              = $ora->mnet_cuid;
		$mnet_workstation_login = $ora->mnet_workstation_login;
		$mnet_last_name         = $ora->mnet_last_name;
		$mnet_first_name        = $ora->mnet_first_name;
		$mnet_nick_name         = $ora->mnet_nick_name;
		$mnet_middle            = $ora->mnet_middle;
		$mnet_name              = $ora->mnet_name;
		$mnet_job_title         = $ora->mnet_job_title;
		$mnet_email             = $ora->mnet_email;
		$mnet_work_phone        = $ora->mnet_work_phone;
		$mnet_street            = $ora->mnet_street;
		$mnet_city              = $ora->mnet_city;
		$mnet_state             = $ora->mnet_state;
		$mnet_country           = $ora->mnet_country;
		$mnet_company           = $ora->mnet_company;
		$mnet_status            = $ora->mnet_status;
		$mnet_change_date       = $ora->mnet_change_date;
		$mnet_ctl_cuid          = $ora->mnet_ctl_cuid;
		$mnet_mgr_cuid          = $ora->mnet_mgr_cuid;

		$query  = sprintf("select * from cct7_mnet where mnet_cuid = '%s'", $mnet_mgr_cuid);

		if ($ora->sql2($query) == false)
		{
			$lib->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $ora->sql_statement);
			$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $ora->dbErrMsg);
			$msg = sprintf("SQL syntax error in file: %s, line: %d. Please contact Greg Parkin - %s",
						   __FILE__, __LINE__, $ora->dbErrMsg);
		}

		if ($ora->fetch())
        {
            $manager_name = $ora->mnet_name;
            $manager_email = $ora->mnet_email;
        }

        $query = sprintf("select * from cct7_users where user_cuid = '%s'", $mnet_cuid);

		if ($ora->sql2($query) == false)
		{
			$lib->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $ora->sql_statement);
			$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $ora->dbErrMsg);
			$msg = sprintf("SQL syntax error in file: %s, line: %d. Please contact Greg Parkin - %s",
						   __FILE__, __LINE__, $ora->dbErrMsg);
		}

		if ($ora->fetch())
		{
			$user_cuid              = $ora->user_cuid;
			$local_timezone         = $ora->local_timezone;
			$local_timezone_name    = $ora->local_timezone_name;
			$local_timezone_abbr    = $ora->local_timezone_abbr;
			$time_difference        = $ora->time_difference;
			$user_or_admin          = $ora->user_or_admin;
			$last_login_date        = $ora->last_login_date;

			$tz = "America/Denver";

			if (isset($_SESSION['local_timezone_name']))
			{
				$tz = $_SESSION['local_timezone_name'];
			}

			$format_weekday_mmddyyyy_hhmm_tz    = 'D m/d/Y H:i T';

			$cm_create_date         = $lib->gmt_to_format(
				$ora->last_login_date,   $format_weekday_mmddyyyy_hhmm_tz, $tz);

			$http_user_agent        = $ora->http_user_agent;
			$user_groups            = $ora->user_groups ;
		}
	}
	else
	{
		$msg = sprintf("Unable to find MNET record for %s %s.", $first_name, $last_name);
	}
}
?>
<!DOCTYPE HTML>
<html>
<head>
	<meta http-equiv="X-UA-Compatible" content="IE=EmulateIE10">
	<title>CCT 7 - Switch User</title>
	<link rel="stylesheet" type="text/css" media="all" href="css/tabcontent.css" />
	<style type="text/css">
		.button_gray
		{
			border: font-size: 11px;
			width: 110px;
			color: #0099CC;
			font-weight: bold;
			font-family: arial;
			background-color: #EEEEEE;
			text-decoration: none;
			//cursor: url('cursors/nbblink2.ani');
		}
		#my_table
		{
			padding: 10px;
			border-radius: 10px;
			top: 10px;
			left: 10px;
			color: rgb(0, 0, 0); /* 136, 136, 136 = #888888 */
			font-family: Verdana, Geneva, sans-serif;
			font-size: 10pt;
			position: absolute;
			opacity: 1;
			box-shadow: 3px 3px 10px #000000;
			/* text-shadow: 1px 1px 0px #000000; */
			/* text-shadow: none !important; */
			/* text-align: center; */
			background-color: rgb(236, 233, 216); /* light tan */
			/*
			** See: http://www.javascripter.net/faq/rgbtohex.htm for more colors
			** RGB: 221, 221, 221 = #DDDDDD  light gray
			** RGB: 236, 233, 216 = #ECE9D8  light tan
			** RGB: 152, 251, 152 = #98FB98  light green
			** RGB: 102, 205, 170 = #66CDAA  light blue
			*/
		}
		#my_header
		{
			white-space: pre;
		}
	</style>

	<script type="text/javascript" src="js/tabcontent.js"></script>
	<script type="text/javascript">
        function reload_index()
        {
            var a = document.createElement('a');
            a.href='index.php';
            a.target = '_blank';
            document.body.appendChild(a);
            a.click();
            //window.location.href = "index.php";
            //window.open('index.php','_blank');
        }
	</script>
</head>
<body>
<form name="f1" method="get" action="find_cuid.php">
    <center>
    <table>
        <tr>
            <td align="center" colspan="2"><font size="+2"><b>Find CUID</b></font></td>
        </tr>
        <tr>
            <td align="right"><b>First Name:</b></td>
            <td align="left"><input type="text" name="first_name" size="15" maxlength="20" /></td>
        </tr>
        <tr>
            <td align="right"><b>Last Name:</b></td>
            <td align="left"><input type="text" name="last_name" size="15" maxlength="20" /></td>
        </tr>
        <tr>
            <td colspan="2" align="center"><font color="red"><b><?php echo $msg ?></b></font></td>
        </tr>
        <tr>
            <td align="center" colspan="2">
                <table border="0" cellpadding="2" cellspacing="2">
                    <tr>
                        <td align="center"><input type="submit" name="button" value="Okay" class="button_gray"></td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
    <br><br>
    <table>
        <tr><td align="right"><b>CUID:</b></td><td align="left"><?php echo $mnet_cuid; ?></td></tr>
        <tr><td align="right"><b>Workstation Login:</b></td><td align="left"><?php echo $mnet_workstation_login; ?></td></tr>
        <tr><td align="right"><b>Last Name:</b></td><td align="left"><?php echo $mnet_last_name; ?></td></tr>
        <tr><td align="right"><b>First Name:</b></td><td align="left"><?php echo $mnet_first_name; ?></td></tr>
        <tr><td align="right"><b>Nick Name:</b></td><td align="left"><?php echo $mnet_nick_name; ?></td></tr>
        <tr><td align="right"><b>Middle Name:</b></td><td align="left"><?php echo $mnet_middle; ?></td></tr>
        <tr><td align="right"><b>Full Name:</b></td><td align="left"><?php echo $mnet_name; ?></td></tr>
        <tr><td align="right"><b>Job Title:</b></td><td align="left"><?php echo $mnet_job_title; ?></td></tr>
        <tr><td align="right"><b>Email Address:</b></td><td align="left"><?php echo $mnet_email; ?></td></tr>
        <tr><td align="right"><b>Work Phone:</b></td><td align="left"><?php echo $mnet_work_phone; ?></td></tr>
        <tr><td align="right"><b>Street:</b></td><td align="left"><?php echo $mnet_street; ?></td></tr>
        <tr><td align="right"><b>City:</b></td><td align="left"><?php echo $mnet_city; ?></td></tr>
        <tr><td align="right"><b>State:</b></td><td align="left"><?php echo $mnet_state; ?></td></tr>
        <tr><td align="right"><b>Country:</b></td><td align="left"><?php echo $mnet_country; ?></td></tr>
        <tr><td align="right"><b>Company:</b></td><td align="left"><?php echo $mnet_company; ?></td></tr>
        <tr><td align="right"><b>Status:</b></td><td align="left"><?php echo $mnet_status; ?></td></tr>
        <tr><td align="right"><b>Change Date:</b></td><td align="left"><?php echo $mnet_change_date; ?></td></tr>
        <tr><td align="right"><b>CTL Sponsor:</b></td><td align="left"><?php echo $mnet_ctl_cuid; ?></td></tr><tr><td align="right"><b>User Timezone:</b></td><td align="left"><?php echo $local_timezone; ?></td></tr>
        <tr><td align="right"><b>Timezone Name:</b></td><td align="left"><?php echo $local_timezone_name; ?></td></tr>
        <tr><td align="right"><b>Timezone Abbr:</b></td><td align="left"><?php echo $local_timezone_abbr; ?></td></tr>
        <tr><td align="right"><b>GMT Time Diff:</b></td><td align="left"><?php echo $time_difference; ?></td></tr>
        <tr><td align="right"><b>Type:</b></td><td align="left"><?php echo $user_or_admin; ?></td></tr>
        <tr><td align="right"><b>Last Login:</b></td><td align="left"><?php echo $last_login_date; ?></td></tr>
        <tr><td align="right"><b>HTTP Agent:</b></td><td align="left"><?php echo $http_user_agent; ?></td></tr>
        <tr><td align="right"><b>User Groups:</b></td><td align="left"><?php echo $user_groups; ?></td></tr>
        <tr><td align="right"><b>Manager CUID:</b></td><td align="left"><?php echo $mnet_mgr_cuid; ?></td></tr>
        <tr><td align="right"><b>Manager Name:</b></td><td align="left"><?php echo $manager_name; ?></td></tr>
        <tr><td align="right"><b>Manager Email:</b></td><td align="left"><?php echo $manager_email; ?></td></tr>
    </table>
    </center>
</form>
</body>
</html>