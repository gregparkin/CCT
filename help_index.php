<?php
/**
 * help_index.php
 *
 * @package   PhpStorm
 * @file      help_index.php
 * @author    gparkin
 * @date      5/9/17
 * @version   7.0
 *
 * @brief     Display a list of all available help files.
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

$lib = new library();
date_default_timezone_set('America/Denver');
$lib->globalCounter();


?>
<head>
	<?php
	switch ( $_SERVER['SERVER_NAME'] )
	{
		case 'cct.corp.intranet':
			?><base href="https://cct.corp.intranet/"><?php
			break;
		case 'lxomp47x.corp.intranet':
			?><base href="https://lxomp47x.corp.intranet/cct7/"><?php
			break;
		case 'cct.test.intranet':
			?><base href="https://cct.test.intranet/"><?php
			break;
		case 'vlodts022.test.intranet':
			?><base href="https://vlodts022.test.intranet/cct7/"><?php
			break;
		default:
			?><base href="http://cct7.localhost/"><?php
			break;
	}
	?>
	<meta http-equiv="X-UA-Compatible" content="IE=EmulateIE10">
	<title>CCT 7.0</title>
	<link rel="icon" type="image/png" href="images/handshake1.ico">
	<link rel="stylesheet" type="text/css" media="screen" href="css/jquery-ui.css">
	<link rel="stylesheet" type="text/css"                href="css/font-awesome.css">
<style>
	p {
		font-size: 18px;
	}
</style>
	<script type="text/javascript" src="js/jquery-2.1.4.js"></script>
	<script type="text/javascript" src="js/jquery-ui.js"></script>
    <!-- w2ui popup -->
    <!-- link rel="stylesheet" type="text/css" href="css/w2ui-1.4.3.css" / -->
    <!-- script type="text/javascript" src="js/w2ui-1.4.3.js"></script -->

    <link rel="stylesheet" type="text/css" href="css/w2ui-1.5.rc1.css" />
    <script type="text/javascript" src="js/w2ui-1.5.rc1.js"></script>
</head>
<script type="application/javascript">
    // All Ready       New Ticket            Search
    // All Tickets     No Response           Server Lists
    // Approve         Open Tickets          Slides
    // Email           Ready Work            Subscriber Lists
    // Help            Remedy Attachment     Welcome
    function loadFile(filename)
	{
        w2ui['layout'].load('main', help_file, 'flip-top', function () {
            console.log('content loaded');
        });
	}
</script>
<body style="margin-botton: 0; margin-top: 0; margin-right: 0; margin-left: 0;">
<div align="center">
	<table border="0" cellspacing="4" cellpadding="4">
		<tr>
			<td align="left" valign="top" width="20%">

			</td>
			<td align="left" valign="top" width="20%">

			</td>
			<td align="left" valign="top" width="20%">

			</td>
		</tr>
	</table>
</div>
</body>
</html>