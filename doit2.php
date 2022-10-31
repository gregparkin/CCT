<?php
/**
 * doit2.php
 *
 * @package   PhpStorm
 * @file      doit.php
 * @author    gparkin
 * @date      7/23/16
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
?>

<html>
<head>
	<base href="http://cct7.localhost/">
	<meta http-equiv="X-UA-Compatible" content="IE=EmulateIE10">
	<title>CCT 7.0</title>

	<!-- SCRIPTS -->
	<script type="text/javascript" src="js/jquery-2.1.4.js"></script>

	<!-- <script type="text/javascript" src="js/DateTimePicker.js"></script> -->
	<!-- Bootstrap core JavaScript ================================================== -->
	<script type="text/javascript" src="js/bootstrap.js"></script>
	<script type="text/javascript" src="js/jquery-ui.js"></script><!-- must come after bootstrap.min.js -->
	<script type="text/javascript" src="js/jquery.themeswitcher.js"></script>

	<script type="text/javascript" src="js/jqxcore.js"></script><!-- jqWidgets: base code -->
	<script type="text/javascript" src="js/jqxwindow.js"></script>
	<script type="text/javascript" src="js/jqxbuttons.js"></script>
	<script type="text/javascript" src="js/jqxscrollbar.js"></script>
	<script type="text/javascript" src="js/jqxpanel.js"></script>
	<script type="text/javascript" src="js/jqxtabs.js"></script>
	<script type="text/javascript" src="js/jqxcheckbox.js"></script>

	<!-- script type="text/javascript" src="js/jqxmenu.js"></script><!-- jqWidgets: jqxMenu - runs nav menu -->
	<script type="text/javascript" src="js/jstz.js"></script><!-- Determines user's timezone of the PC their using -->
	<script type="text/javascript" src="js/html.js"></script>

	<!-- jqGrid.4.8.2 does not work so don't use it -->
	<!-- jqGrid.4.7.1 works -->
	<script type="text/javascript" src="jqGrid.5.1.1/js/jquery.jqGrid.min.js"></script>
	<script type="text/javascript" src="jqGrid.5.1.1/js/i18n/grid.locale-en.js"></script>
	<!-- <script type="text/javascript" src="js/jquery.blockUI.js"></script> -->

	<script type="text/javascript" src="js/jquery-ui-timepicker-addon.js"></script>
	<script type="text/javascript" src="js/jquery-ui-sliderAccess.js"></script>

	<script type="text/javascript" src="js/detect.js"></script><!-- Detect browser so we can size dialog boxes -->

    <!-- w2ui popup -->
    <!-- link rel="stylesheet" type="text/css" href="css/w2ui-1.4.3.css" / -->
    <!-- script type="text/javascript" src="js/w2ui-1.4.3.js"></script -->

    <link rel="stylesheet" type="text/css" href="css/w2ui-1.5.rc1.css" />
    <script type="text/javascript" src="js/w2ui-1.5.rc1.js"></script>

	<script src="js/go-debug.js"></script>

	<!--[if lt IE 9]>
	<script src="js/IE9.js"></script>
	<![endif]-->
</head>
</head>
<body>

<pre style="font-size: 14px;">
<?php

printf("_POST: ");
print_r($_POST);
printf("_GET: ");
print_r($_GET);
printf("_REQUEST: ");
print_r($_REQUEST);
printf("_SERVER: ");
print_r($_SERVER);
printf("_SESSION: ");
print_r($_SESSION);
?>
</pre>
</body>
</html>

