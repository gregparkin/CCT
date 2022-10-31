<?php
/**
 * switch_user.php
 *
 * @package   PhpStorm
 * @file      switch_user.php
 * @author    gparkin
 * @date      7/10/17
 * @version   7.0
 *
 * @brief     About this module.
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
$lib->debug_start('switch_user.html');
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

$new_cuid   = '';
$first_name = '';
$last_name  = '';
$button     = '';

if (isset($argv['new_cuid']))
	$new_cuid = $argv['new_cuid'];

if (isset($argv['first_name']))
	$first_name = $argv['first_name'];

if (isset($argv['last_name']))
	$last_name = $argv['last_name'];

if (isset($argv['button']))
	$button = $argv['button'];

$real_cuid = $_SESSION['real_cuid'];

$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "new_cuid: %s",   $new_cuid);
$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "first_name: %s", $first_name);
$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "last_name: %s",  $last_name);
$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "button: %s",     $button);
$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "real_cuid: %s",  $real_cuid);

$ora = new oracle();

if (strlen($new_cuid) == 0)
{
    if ((strlen($first_name) > 0 && strlen($last_name) == 0) || (strlen($first_name) == 0 && strlen($last_name) > 0))
    {
        $msg = "You need to type in both the first name and the last name.";
    }
    else if (strlen($first_name) > 0 && strlen($last_name) > 0)
    {
        $query  = "select * from cct7_mnet where ";
        $query .= sprintf("lower(mnet_first_name) = lower('%s') and ", $first_name);
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
            $new_cuid = $ora->mnet_cuid;
        }
        else
        {
            $msg = sprintf("Unable to find MNET record for %s %s.", $first_name, $last_name);
        }
    }
}


if ($button === 'Login')
{
	$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "You clicked on the Login button.");

	if (strlen($new_cuid) > 0)
	{
		$query  = "select * from cct7_mnet where ";
		$query .= sprintf("lower(mnet_cuid) = lower('%s') or ", $new_cuid);
		$query .= sprintf("lower(mnet_workstation_login) = lower('%s')", $new_cuid);

		if ($ora->sql2($query) == false)
		{
			$lib->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $ora->sql_statement);
			$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $ora->dbErrMsg);
			$msg = sprintf("SQL syntax error in file: %s, line: %d. Please contact Greg Parkin - %s",
						   __FILE__, __LINE__, $ora->dbErrMsg);
		}
		else if ($ora->fetch())
		{
			$_SESSION['user_cuid']       = $ora->mnet_cuid;
			$_SESSION['user_first_name'] = $ora->mnet_first_name;
			$_SESSION['user_last_name']  = $ora->mnet_last_name;
			$_SESSION['user_name']       = $ora->mnet_name;
			$_SESSION['user_email']      = $ora->mnet_email;
			$_SESSION['user_company']    = $ora->mnet_company;

			$_SESSION['real_user_groups'] = $_SESSION['user_groups'];
			$_SESSION['user_groups'] = getUserGroups($new_cuid);

			$msg = "You are now: &quot;" . $ora->mnet_cuid . "&quot;";

			if (!empty($ora->mnet_mgr_cuid))
			{
				//
				// Retrieve the manager's cuid from MNET
				//
				if ($ora->sql("select * from cct7_mnet where mnet_cuid = '" . $ora->mnet_mgr_cuid . "'") == false)
				{
					$lib->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $ora->sql_statement);
					$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $ora->dbErrMsg);
					$msg = sprintf("SQL syntax error in file: %s, line: %d. Please contact Greg Parkin - %s",
								   __FILE__, __LINE__, $ora->dbErrMsg);
				}
				else if ($ora->fetch())
				{
					$_SESSION['manager_cuid']       = $ora->mnet_cuid;
					$_SESSION['manager_first_name'] = $ora->mnet_first_name;
					$_SESSION['manager_last_name']  = $ora->mnet_last_name;
					$_SESSION['manager_name']       = $ora->mnet_name;
					$_SESSION['manager_email']      = $ora->mnet_email;
					$_SESSION['manager_company']    = $ora->mnet_company;
				}
				else
				{
					$msg = "No manager data available for cuid: " . $ora->mnet_mgr_cuid;

					$_SESSION['manager_cuid']       = '';
					$_SESSION['manager_first_name'] = '';
					$_SESSION['manager_last_name']  = '';
					$_SESSION['manager_name']       = '';
					$_SESSION['manager_email']      = '';
					$_SESSION['manager_company']    = '';
				}
			}
			else
			{
				$msg = "No manager data available!";

				$_SESSION['manager_cuid']       = '';
				$_SESSION['manager_first_name'] = '';
				$_SESSION['manager_last_name']  = '';
				$_SESSION['manager_name']       = '';
				$_SESSION['manager_email']      = '';
				$_SESSION['manager_company']    = '';
			}

		}
		else
		{
			$msg = "Cannot find cuid  &quot;" . $new_cuid . "&quot; in MNET table";
		}
	}
	else
	{
		$msg = 'Please enter a cuid you want to login too!';
	}
}

if ($button == 'Logoff')
{
	// Switch back to Real User
	if (isset($real_cuid) && strlen($real_cuid) > 0)
	{
		if ($ora->sql("select * from cct7_mnet where mnet_cuid = '" . $real_cuid . "'") == false)
		{
			$lib->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $ora->sql_statement);
			$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $ora->dbErrMsg);
			$msg = sprintf("SQL syntax error in file: %s, line: %d. Please contact Greg Parkin - %s",
						   __FILE__, __LINE__, $ora->dbErrMsg);
		}
		else if ($ora->fetch())
		{
			$_SESSION['user_cuid']       = $ora->mnet_cuid;
			$_SESSION['user_first_name'] = $ora->mnet_first_name;
			$_SESSION['user_last_name']  = $ora->mnet_last_name;
			$_SESSION['user_name']       = $ora->mnet_name;
			$_SESSION['user_email']      = $ora->mnet_email;
			$_SESSION['user_company']    = $ora->mnet_company;

			$msg = "Logoff Successful!";

			if (!empty($ora->mnet_mgr_cuid))
			{
				//
				// Retrieve the manager's cuid from MNET
				//
				if ($ora->sql("select * from cct7_mnet where mnet_cuid = '" . $ora->mnet_mgr_cuid . "'") == false)
				{
					$h->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $ora->sql_statement);
					$h->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $ora->dbErrMsg);
					$msg = sprintf("SQL syntax error in file: %s, line: %d. Please contact Greg Parkin - %s",
								   __FILE__, __LINE__, $ora->dbErrMsg);
				}
				else if ($ora->fetch())
				{
					$_SESSION['manager_cuid']       = $ora->mnet_cuid;
					$_SESSION['manager_first_name'] = $ora->mnet_first_name;
					$_SESSION['manager_last_name']  = $ora->mnet_last_name;
					$_SESSION['manager_name']       = $ora->mnet_name;
					$_SESSION['manager_email']      = $ora->mnet_email;
					$_SESSION['manager_company']    = $ora->mnet_company	;
				}
				else
				{
					$msg = "No manager data available for cuid: " . $ora->mnet_mgr_cuid;

					$_SESSION['manager_cuid']       = '';
					$_SESSION['manager_first_name'] = '';
					$_SESSION['manager_last_name']  = '';
					$_SESSION['manager_name']       = '';
					$_SESSION['manager_email']      = '';
					$_SESSION['manager_company']    = '';
				}
			}
			else
			{
				$msg = "No manager data available!";

				$_SESSION['manager_cuid']       = '';
				$_SESSION['manager_first_name'] = '';
				$_SESSION['manager_last_name']  = '';
				$_SESSION['manager_name']       = '';
				$_SESSION['manager_email']      = '';
				$_SESSION['manager_company']    = '';
			}

			$_SESSION['user_groups'] = $_SESSION['real_user_groups'];
		}
		else
		{
			$msg = "Cannot find cuid in MNET table: " . $new_cuid;
		}
	}
	else
	{
		$msg = "I don't know who the real user cuid is! Please close all browser windows and login again.";
	}
}

function getUserGroups($cuid)
{
    global $ora, $lib;

	//
    // Retrieve this users list of NET group pins and Subscriber pins.
    //
	$user_groups_hash = array();
	$remote_user = $cuid;

	$query =
		sprintf("select * from cct7_netpin_to_cuid where user_cuid = '%s'", $cuid);

	if ($ora->sql2($query) == false)
	{
		$lib->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $ora->sql_statement);
		$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $ora->dbErrMsg);
	}

	while ($ora->fetch())
	{
		$user_groups_hash[$ora->net_pin_no] = $cuid;
	}

	$query =
		sprintf("select * from cct7_subscriber_members where member_cuid = '%s'",	$cuid);

	if ($ora->sql2($query) == false)
	{
		$lib->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $ora->sql_statement);
		$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $ora->dbErrMsg);
	}

	while ($ora->fetch())
	{
		$user_groups_hash[$ora->group_id] = $cuid;
	}

	$user_groups = '';

	foreach ($user_groups_hash as $netpin => $val)
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

	return $user_groups;
}
?>
<!DOCTYPE HTML>
<html>
<head>
	<meta http-equiv="X-UA-Compatible" content="IE=EmulateIE10">
	<title>CCT 7 - Switch User</title>
	<link rel="stylesheet" type="text/css" media="all" href="css/tabcontent.css" />
	<style type="text/css">
		.button_blue
		{
			border: font-size: 11px;
			width: 110px;
			color: #FFFFFF;
			font-weight: bold;
			font-family: arial;
			background-color: #3333CC;
			text-decoration: none;
			//cursor: url('cursors/nbblink2.ani');
		}
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
		#bg_mask2 {
			position: absolute;
			top: 0;
			right: 0;
			bottom: 0;
			left: 0;
			margin: auto;
			margin-top: 0px;
			width: 308px;
			height: 207px;
			z-index: 15;
		}
		#frontlayer2 {
			position: absolute;
			top: 0;
			right: 0;
			bottom: 0;
			left: 0;
			margin: 5px 5px 5px 5px;
			padding : 10px;
			width: 303px;
			height: 202px;
			border: 0px solid black;
			z-index: 20;
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
<form name="f1" method="get" action="switch_user.php">
	<div id="bg_mask2" align="center">
		<div id="frontlayer2" align="center">
			<table id="my_table">
				<tr>
					<td align="center" colspan="2"><font size="+2"><b>Switch User</b></font></td>
				</tr>
				<tr>
					<td align="right"><b>Real User:</b></td>
					<td align="left"><?php echo $_SESSION['real_name']; ?> (<?php echo $_SESSION['real_cuid']; ?>)</td>
				</tr>
				<tr>
					<td align="right"><b>Current User:</b></td>
					<td align="left"><?php echo $_SESSION['user_name']; ?> (<?php echo $_SESSION['user_cuid']; ?>)</td>
				</tr>
				<tr>
					<td colspan="2">&nbsp;</td>
				</tr>
				<tr>
					<td align="right"><b>New User:</b></td>
					<td align="left"><input type="text" name="new_cuid" size="15" maxlength="20" /></td>
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
								<td align="center"><input type="submit" name="button" value="Login" class="button_gray"></td>
								<td align="center"><input type="submit" name="button" value="Logoff" class="button_gray"></td>
							</tr>
						</table>
					</td>
				</tr>
			</table>
		</div>
	</div>

</form>
</body>
</html>