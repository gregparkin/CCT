<?php
/**
 * file_viewer.php
 *
 * @package   PhpStorm
 * @file      file_viewer.php
 * @author    gparkin
 * @date      7/11/17
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
$lib->debug_start('debug_files.html');
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

$filename = '';

if (isset($argv['filename']))
	$filename = $argv['filename'];

?>
<!doctype html>
<html lang = "en">
<head>
	<meta charset = "utf-8">
	<title>Log File</title>
	<link rel="stylesheet" type="text/css" href="css/calendar.css">
	<script type="text/javascript" src="js/jquery-2.1.4.js"></script>
    <!-- w2ui popup -->
    <!-- link rel="stylesheet" type="text/css" href="css/w2ui-1.4.3.css" / -->
    <!-- script type="text/javascript" src="js/w2ui-1.4.3.js"></script -->

    <link rel="stylesheet" type="text/css" href="css/w2ui-1.5.rc1.css" />
    <script type="text/javascript" src="js/w2ui-1.5.rc1.js"></script>
	<script type="text/javascript" src="js/date_functions.js"></script>
	<style>
		.my_class
		{
			font-size: 14px
		}
	</style>
</head>
<body>
<?php
printf("<center><h1>%s</h1></center>\n", $filename);
printf("<pre class='my_class'>\n");

$fp = @fopen($filename, "r");

if ($fp)
{
	while (($buffer = fgets($fp, 4096)) !== false)
	{
		echo $buffer;
	}

	if (!feof($fp))
	{
		echo "Error: unexpected fgets() fail\n";
	}

	fclose($fp);
}
else
{
	printf("Error: cannot open for read: %s\n", $filename);
}

printf("</pre>\n");
?>
</body>
</html>