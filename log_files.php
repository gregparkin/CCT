<?php
/**
 * log_files.php
 *
 * @package   PhpStorm
 * @file      log_files.php
 * @author    gparkin
 * @date      7/10/17
 * @version   7.0
 *
 * @brief     About this module.
 */
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
$lib->debug_start('log_files.html');
date_default_timezone_set('America/Denver');

$lib->globalCounter();

//
// URL Arguments
//
$argv = array();
$argc = 0;

$dp = null;
$debug_path = "/opt/ibmtools/cct7/logs";

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

/*! @fn endsWith($haystack, $needle)
 *  @brief Check to see if the end of the string (haystack) matches the pattern string (needle)
 *  @param $haystack is the the string to search
 *  @param $needle is the pattern we are checking for at the end of the string
 *  @return -1, 0, or 1, where 0 means match
 */
function endsWith($haystack, $needle)
{
	return substr_compare($haystack, $needle, -strlen($needle)) == 0;
}
?>

<!doctype html>
<html lang = "en">
<head>
    <meta charset = "utf-8">
    <title>Debug File Viewer</title>
    <link rel="stylesheet" type="text/css" href="css/calendar.css">
    <style type="text/css">
        .my_button
        {
            width:       450px;
            color:       #0099CC;
            font-weight: bold;
            font-family: arial;
            text-align:  left;
        }
    </style>
    <script type="text/javascript" src="js/jquery-2.1.4.js"></script>
    <!-- w2ui popup -->
    <!-- link rel="stylesheet" type="text/css" href="css/w2ui-1.4.3.css" / -->
    <!-- script type="text/javascript" src="js/w2ui-1.4.3.js"></script -->

    <link rel="stylesheet" type="text/css" href="css/w2ui-1.5.rc1.css" />
    <script type="text/javascript" src="js/w2ui-1.5.rc1.js"></script>
    <script type="text/javascript" src="js/date_functions.js"></script>
</head>
<body>
<center>
    <h1>Log File Viewer</h1>
    <table border="0" cellpadding="4" cellspacing="4">
        <tr>
            <td align="left">
				<?php

				if ((!$dp = opendir($debug_path)))
				{
					die("Cannot open Directory: " . $debug_path);
				}

				$file_array = array();

				while ($file = readdir($dp))
				{
					if (endsWith($file, ".log"))
					{
						$file_array[$file] = sprintf("logs/%s", $file);
					}
				}

				closedir($dp);
				ksort($file_array);

				foreach ($file_array as $key => $val)
				{
					$stat = stat($val);
					$mtime = $stat['mtime'];
					$date = $lib->gmt_to_format($mtime, 'm/d/Y H:i T', 'America/Denver');

					$filename = sprintf("%s %s (%d)",
										$date, $key, $stat['size']);

					$html  = "<input type='button' class='my_button' value='" . $filename . "' ";
					$html .= "onClick=\"window.open('" . $val . "', '_blank', ";
					$html .= "'location=yes,height=800,width=1200,scrollbars=yes,status=yes');\">";
					printf("%s<br>\n", $html);
				}
				?>
            </td>
        </tr>
    </table>
</center>
</body>
</html>
