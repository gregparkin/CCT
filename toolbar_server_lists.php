<?php
/**
 * toolbar_server_lists.php
 *
 * @package   PhpStorm
 * @file      doit.php
 * @author    gparkin
 * @date      7/23/16
 * @version   7.0
 *
 * @brief     Master Detail jqGrid server lists edit module.
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
$lib->debug_start('toolbar_server_lists.html');
date_default_timezone_set('America/Denver');

$lib->globalCounter();

//$lib->html_dump();

$my_request   = array();
$param        = array();
$param_count  = 0;

//
// Will be either 'group' or 'all' depending on the user's CCT preferences.
//
//$what_tickets = isset($_SESSION['pref_toolbar_open']) ? $_SESSION['pref_toolbar_open'] : 'group';

//
// Used to control how much and what information to show in the grids.
//
$what_tickets   = '';  // <ticket_no>, 'group', 'approve', Not set means give me all tickets.
$what_hostname  = '';  // <hostname> = List all tickets containing work for this server.
$what_netpin_no = '';  // <netpin_no> = List all tickets where this netpin contact is found.
$what_cuid      = '';  // <cuid> = List all tickets owned or created by this cuid.

if (isset($_SERVER['QUERY_STRING']))   // Parse QUERY_STRING if it exists
{
	parse_str($_SERVER['QUERY_STRING'], $my_request);  // Parses URL parameter options into an array called $my_request
	$param_count = count($my_request);                 // Get the count of the number of $my_request array elements.

	//
	// Copy the $_REQUEST information to our $this->data[key] = value
	//
	// For example if you have a HTML form input variable called 'cm_ticket_no' then you will be
	// to access the information in this form: $obj->cm_ticket_name
	//
	foreach ($_REQUEST as $key => $value)
	{
		$param[$key] = $value;
	}
}

//
// $what_tickets = [ '', 'group', or 'approve' ]
//
if (array_key_exists('what_tickets', $param))
{
	$what_tickets = $param['what_tickets'];
}

//
// search for tickets where $what_hostname is found
//
if (array_key_exists('what_hostname', $param))
{
	$what_hostname = $param['what_hostname'];
}

//
// search for ticket where $what_netpin_no is found as a contact for any given server
//
if (array_key_exists('what_netpin_no', $param))
{
	$what_netpin_no = $param['what_netpin_no'];
}

//
// search for ticket where $what_cuid is the owner for the ticket.
//
if (array_key_exists('what_cuid', $param))
{
	$what_cuid = $param['what_cuid'];
}

$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "what_tickets: %s",   $what_tickets);
$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "what_hostname: %s",  $what_hostname);
$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "what_netpin_no: %s", $what_netpin_no);
$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "what_cuid: %s",      $what_cuid);

//
// Setup grid captions
//
$caption_tickets = "Tickets (All)";

if (strlen($what_netpin_no) > 0)
{
	$caption_tickets = sprintf("Tickets (%s)", $what_netpin_no);
}
else if (strlen($what_hostname) > 0)
{
	$caption_tickets = sprintf("Tickets (%s)", $what_hostname);
}
else if ($what_tickets == 'group')
{
	$caption_tickets = sprintf("Tickets (Group Owned)");
}
else if ($what_tickets == 'approve')
{
	$caption_tickets = sprintf("Tickets (To Approve)");
}
?>

<html>
<link rel="stylesheet" type="text/css" href="css/jqx.base.css">
<link rel="stylesheet" type="text/css" href="css/jqx.bootstrap.css">
<link rel="stylesheet" type="text/css" media="screen" href="css/jquery-ui.css">

<!--
  base, black-tie, blitzer, cupertino, dark-hive, dot-luv, eggplant, excite-bike, flick, hot-sneaks,
  humanity, le-frog, mint-choc, overcast, pepper-grinder, redmond, smoothness, south-street, start,
  sunny, swanky-purse, trontastic, ui-darkness, ui-lightness, vadar
-->
<link id="pagestyle" rel="stylesheet" type="text/css" href="css/themes/humanity/jquery-ui.css">
<!-- 4.7.1 or 5.1.1 -->
<link rel="stylesheet" type="text/css" media="screen" href="jqGrid.5.1.1/css/ui.jqgrid.css">
<link rel="stylesheet" type="text/css" media="all"    href="css/jquery-ui-timepicker-addon.css">

<!link rel="stylesheet" type="text/css" media="screen" href="css/tabcontent.css">

<style type="text/css">
	.textalignright
	{
		text-align: right !important;
	}
	.textalignleft
	{
		text-align:left  !important;
	}
	.textalignright div
	{
		padding-right: 5px;
	}
	.textalignleft div
	{
		padding-left: 5px;
	}
	ol, ul
	{
		list-style: none;
	}
	/* Bump up the font-size in the grid */
	/*
	.ui-jqgrid,
	.ui-jqgrid .ui-jqgrid-view,
	.ui-jqgrid .ui-jqgrid-pager,
	.ui-jqgrid .ui-pg-input {
		font-size: 13px;
	}
	*/
	/* Page loading spinner image */
	.loader
	{
		position:         fixed;
		left:             0px;
		top:              0px;
		width:            100%;
		height:           100%;
		z-index:          9999;
		background:       url('images/page-loader.gif') 50% 50% no-repeat rgb(249,249,249);
	}
    .blue {
        display: inline-block;
        text-align: center;
        vertical-align: middle;
        padding: 4px 23px;
        border: 1px solid #07347b;
        border-radius: 8px;
        background: #0d62e8;
        background: -webkit-gradient(linear, left top, left bottom, from(#0d62e8), to(#07347b));
        background: -moz-linear-gradient(top, #0d62e8, #07347b);
        background: linear-gradient(to bottom, #0d62e8, #07347b);
        -webkit-box-shadow: #1076ff 4px 4px 5px 0px;
        -moz-box-shadow: #1076ff 4px 4px 5px 0px;
        box-shadow: #1076ff 4px 4px 5px 0px;
        text-shadow: #041f49 3px 2px 0px;
        font: normal normal bold 20px arial;
        color: #ffffff;
        text-decoration: none;
    }
    .blue:hover,
    .blue:focus {
        border: 1px solid ##083d91;
        background: #1076ff;
        background: -webkit-gradient(linear, left top, left bottom, from(#1076ff), to(#083e94));
        background: -moz-linear-gradient(top, #1076ff, #083e94);
        background: linear-gradient(to bottom, #1076ff, #083e94);
        color: #ffffff;
        text-decoration: none;
    }
    .blue:active {
        background: #07347b;
        background: -webkit-gradient(linear, left top, left bottom, from(#07347b), to(#07347b));
        background: -moz-linear-gradient(top, #07347b, #07347b);
        background: linear-gradient(to bottom, #07347b, #07347b);
    }
    .green {
        display: inline-block;
        text-align: center;
        vertical-align: middle;
        padding: 4px 23px;
        border: 1px solid #046a14;
        border-radius: 8px;
        background: #08c825;
        background: -webkit-gradient(linear, left top, left bottom, from(#08c825), to(#046a14));
        background: -moz-linear-gradient(top, #08c825, #046a14);
        background: linear-gradient(to bottom, #08c825, #046a14);
        -webkit-box-shadow: #0af02c 4px 4px 5px 0px;
        -moz-box-shadow: #0af02c 4px 4px 5px 0px;
        box-shadow: #0af02c 4px 4px 5px 0px;
        text-shadow: #033f0c 3px 2px 0px;
        font: normal normal bold 20px arial;
        color: #ffffff;
        text-decoration: none;
    }
    .green:hover,
    .green:focus {
        border: 1px solid ##057d17;
        background: #0af02c;
        background: -webkit-gradient(linear, left top, left bottom, from(#0af02c), to(#057f18));
        background: -moz-linear-gradient(top, #0af02c, #057f18);
        background: linear-gradient(to bottom, #0af02c, #057f18);
        color: #ffffff;
        text-decoration: none;
    }
    .green:active {
        background: #046a14;
        background: -webkit-gradient(linear, left top, left bottom, from(#046a14), to(#046a14));
        background: -moz-linear-gradient(top, #046a14, #046a14);
        background: linear-gradient(to bottom, #046a14, #046a14);
    }
    .detail_green {
        display: inline-block;
        text-align: center;
        vertical-align: middle;
        padding: 4px 23px;
        border: 1px solid #046a14;
        border-radius: 8px;
        background: #08c825;
        background: -webkit-gradient(linear, left top, left bottom, from(#08c825), to(#046a14));
        background: -moz-linear-gradient(top, #08c825, #046a14);
        background: linear-gradient(to bottom, #08c825, #046a14);
        -webkit-box-shadow: #0af02c 4px 4px 5px 0px;
        -moz-box-shadow: #0af02c 4px 4px 5px 0px;
        box-shadow: #0af02c 4px 4px 5px 0px;
        text-shadow: #033f0c 3px 2px 0px;
        font: normal normal bold 20px arial;
        color: #ffffff;
        text-decoration: none;
    }
    .detail_green:hover,
    .detail_green:focus {
        border: 1px solid ##057d17;
        background: #0af02c;
        background: -webkit-gradient(linear, left top, left bottom, from(#0af02c), to(#057f18));
        background: -moz-linear-gradient(top, #0af02c, #057f18);
        background: linear-gradient(to bottom, #0af02c, #057f18);
        color: #ffffff;
        text-decoration: none;
    }
    .detail_green:active {
        background: #046a14;
        background: -webkit-gradient(linear, left top, left bottom, from(#046a14), to(#046a14));
        background: -moz-linear-gradient(top, #046a14, #046a14);
        background: linear-gradient(to bottom, #046a14, #046a14);
    }
    .red {
        display: inline-block;
        text-align: center;
        vertical-align: middle;
        padding: 4px 23px;
        border: 1px solid #64070d;
        border-radius: 8px;
        background: #ff1423;
        background: -webkit-gradient(linear, left top, left bottom, from(#ff1423), to(#64070d));
        background: -moz-linear-gradient(top, #ff1423, #64070d);
        background: linear-gradient(to bottom, #ff1423, #64070d);
        -webkit-box-shadow: #ff182a 4px 4px 5px 0px;
        -moz-box-shadow: #ff182a 4px 4px 5px 0px;
        box-shadow: #ff182a 4px 4px 5px 0px;
        text-shadow: #380407 3px 2px 0px;
        font: normal normal bold 20px arial;
        color: #ffffff;
        text-decoration: none;
    }
    .red:hover,
    .red:focus {
        border: 1px solid ##6f080e;
        background: #ff182a;
        background: -webkit-gradient(linear, left top, left bottom, from(#ff182a), to(#780810));
        background: -moz-linear-gradient(top, #ff182a, #780810);
        background: linear-gradient(to bottom, #ff182a, #780810);
        color: #ffffff;
        text-decoration: none;
    }
    .red:active {
        background: #64070d;
        background: -webkit-gradient(linear, left top, left bottom, from(#64070d), to(#64070d));
        background: -moz-linear-gradient(top, #64070d, #64070d);
        background: linear-gradient(to bottom, #64070d, #64070d);
    }
    .detail_red {
        display: inline-block;
        text-align: center;
        vertical-align: middle;
        padding: 4px 23px;
        border: 1px solid #64070d;
        border-radius: 8px;
        background: #ff1423;
        background: -webkit-gradient(linear, left top, left bottom, from(#ff1423), to(#64070d));
        background: -moz-linear-gradient(top, #ff1423, #64070d);
        background: linear-gradient(to bottom, #ff1423, #64070d);
        -webkit-box-shadow: #ff182a 4px 4px 5px 0px;
        -moz-box-shadow: #ff182a 4px 4px 5px 0px;
        box-shadow: #ff182a 4px 4px 5px 0px;
        text-shadow: #380407 3px 2px 0px;
        font: normal normal bold 20px arial;
        color: #ffffff;
        text-decoration: none;
    }
    .detail_red:hover,
    .detail_red:focus {
        border: 1px solid ##6f080e;
        background: #ff182a;
        background: -webkit-gradient(linear, left top, left bottom, from(#ff182a), to(#780810));
        background: -moz-linear-gradient(top, #ff182a, #780810);
        background: linear-gradient(to bottom, #ff182a, #780810);
        color: #ffffff;
        text-decoration: none;
    }
    .detail_red:active {
        background: #64070d;
        background: -webkit-gradient(linear, left top, left bottom, from(#64070d), to(#64070d));
        background: -moz-linear-gradient(top, #64070d, #64070d);
        background: linear-gradient(to bottom, #64070d, #64070d);
    }
    .brown {
        display: inline-block;
        text-align: center;
        vertical-align: middle;
        padding: 4px 23px;
        border: 1px solid #89441e;
        border-radius: 8px;
        background: #de6f31;
        background: -webkit-gradient(linear, left top, left bottom, from(#de6f31), to(#89441e));
        background: -moz-linear-gradient(top, #de6f31, #89441e);
        background: linear-gradient(to bottom, #de6f31, #89441e);
        -webkit-box-shadow: #cd662e 4px 4px 5px 0px;
        -moz-box-shadow: #cd662e 4px 4px 5px 0px;
        box-shadow: #cd662e 4px 4px 5px 0px;
        text-shadow: #562b13 3px 2px 0px;
        font: normal normal bold 20px arial;
        color: #ffffff;
        text-decoration: none;
    }
    .brown:hover,
    .brown:focus {
        border: 1px solid ##ab5526;
        background: #ff853b;
        background: -webkit-gradient(linear, left top, left bottom, from(#ff853b), to(#a45224));
        background: -moz-linear-gradient(top, #ff853b, #a45224);
        background: linear-gradient(to bottom, #ff853b, #a45224);
        color: #ffffff;
        text-decoration: none;
    }
    .brown:active {
        background: #89441e;
        background: -webkit-gradient(linear, left top, left bottom, from(#89441e), to(#89441e));
        background: -moz-linear-gradient(top, #89441e, #89441e);
        background: linear-gradient(to bottom, #89441e, #89441e);
    }
    .detail_brown {
        display: inline-block;
        text-align: center;
        vertical-align: middle;
        padding: 4px 23px;
        border: 1px solid #89441e;
        border-radius: 8px;
        background: #de6f31;
        background: -webkit-gradient(linear, left top, left bottom, from(#de6f31), to(#89441e));
        background: -moz-linear-gradient(top, #de6f31, #89441e);
        background: linear-gradient(to bottom, #de6f31, #89441e);
        -webkit-box-shadow: #cd662e 4px 4px 5px 0px;
        -moz-box-shadow: #cd662e 4px 4px 5px 0px;
        box-shadow: #cd662e 4px 4px 5px 0px;
        text-shadow: #562b13 3px 2px 0px;
        font: normal normal bold 20px arial;
        color: #ffffff;
        text-decoration: none;
    }
    .detail_brown:hover,
    .detail_brown:focus {
        border: 1px solid ##ab5526;
        background: #ff853b;
        background: -webkit-gradient(linear, left top, left bottom, from(#ff853b), to(#a45224));
        background: -moz-linear-gradient(top, #ff853b, #a45224);
        background: linear-gradient(to bottom, #ff853b, #a45224);
        color: #ffffff;
        text-decoration: none;
    }
    .detail_brown:active {
        background: #89441e;
        background: -webkit-gradient(linear, left top, left bottom, from(#89441e), to(#89441e));
        background: -moz-linear-gradient(top, #89441e, #89441e);
        background: linear-gradient(to bottom, #89441e, #89441e);
    }
    .purple {
        display: inline-block;
        text-align: center;
        vertical-align: middle;
        padding: 4px 23px;
        border: 1px solid #570784;
        border-radius: 8px;
        background: #a30df8;
        background: -webkit-gradient(linear, left top, left bottom, from(#a30df8), to(#570784));
        background: -moz-linear-gradient(top, #a30df8, #570784);
        background: linear-gradient(to bottom, #a30df8, #570784);
        -webkit-box-shadow: #c410ff 4px 4px 5px 0px;
        -moz-box-shadow: #c410ff 4px 4px 5px 0px;
        box-shadow: #c410ff 4px 4px 5px 0px;
        text-shadow: #33044e 3px 2px 0px;
        font: normal normal bold 20px arial;
        color: #ffffff;
        text-decoration: none;
    }
    .purple:hover,
    .purple:focus {
        border: 1px solid ##66089b;
        background: #c410ff;
        background: -webkit-gradient(linear, left top, left bottom, from(#c410ff), to(#68089e));
        background: -moz-linear-gradient(top, #c410ff, #68089e);
        background: linear-gradient(to bottom, #c410ff, #68089e);
        color: #ffffff;
        text-decoration: none;
    }
    .purple:active {
        background: #570784;
        background: -webkit-gradient(linear, left top, left bottom, from(#570784), to(#570784));
        background: -moz-linear-gradient(top, #570784, #570784);
        background: linear-gradient(to bottom, #570784, #570784);
    }

</style>

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

<script type="text/javascript">

	var list_name           = '';
	var dialog_title        = '';
	var dialog_list_name_id = 0;

	$(document).ready(function ()
	{
		//
		// Hide the Detail list buttons until the first list is loaded.
		//
		$(".detail_green").hide();
		$(".detail_red").hide();
        $(".detail_brown").hide();

		var masterGridID      = 'masterGrid';
		var masterGridPagerID = 'masterGridPager';
		var masterGrid        = '#' + masterGridID;
		var masterGridPager   = '#' + masterGridPagerID;

		var detailGridID      = 'detailGrid';
		var detailGridPagerID = 'detailGridPager';
		var detailGrid        = '#' + detailGridID;
		var detailGridPager   = '#' + detailGridPagerID;

		//
		//  Server Count  List Name                 Create Date  Owner CUID  Owner Name
		//  ------------  ------------------------  -----------  ----------  ---------------
		//       354      PDR13332_CMP-UNIX-PROD01  09/20/2016   gparkin     Greg Parkin
		//
		$(masterGrid).jqGrid({
			url: 'ajax_jqgrid_server_lists_master.php',
			mtype: "GET",
			datatype: "json",
			postData: {
				action: 'get',
				where_clause: '',
				order_by: 't.list_name',
				direction: 'asc'
			},
			colModel: [
				{
					key: true,
					label: 'List Name ID',
					name: 'list_name_id',
					align: 'left',
					width: 120,
					hidden: true
				},
				{
					label: 'Server Count',
					name: 'server_count',
					align: 'center',
					width: 120
				},
				{
					label: 'List Name',
					name: 'list_name',
					width: 350,
					align: 'left',
					search: true
				},
				{
					label: 'Create Date',
					name: 'create_date',
					width: 120,
					align: 'center',
					search: true,
					searchoptions: {
						sopt: ['eq', 'ne', 'lt', 'le', 'gt', 'ge'],
						dataInit: function (elem)
						{
							$(elem).datetimepicker(
								{
									changeMonth: true,
									changeYear: true,
									showSecond: true,
									dateFormat: 'mm/dd/yy',
									timeFormat: 'hh:mm',
									hourGrid: 4,
									minuteGrid: 10,
									secondGrid: 10,
									addSliderAccess: true,
									sliderAccessArgs: {touchonly: false}
								}
							);
						}
					}
				},
				{
					label: 'Owner CUID',
					name: 'owner_cuid',
					width: 150,
					align: 'left',
					hidden: true,
					search: true,
					stype: "select",
					searchoptions: {
						value: "ALL:ALL;<?php printf("%s;%s", $_SESSION['user_cuid'], $_SESSION['user_cuid']); ?>",
						defaultValue: "<?php printf("%s", $_SESSION['user_cuid']); ?>"
					}
				},
				{
					label: 'Owner Name',
					name: 'owner_name',
					width: 350,
					align: 'left',
					search: true
				}
			],
			caption:      'List Names',
			width:        '100%',
			height:       '100%',
			rowNum:       10,
			viewrecords:  true,
			altRows:      true,
			viewsortcols: true,
			loadtext:     'Loading Group Lists...',
			imgpath:      'images',
			multiselect:  false,
			pager:        masterGridPager,
			onSelectRow:  function(list_name_id)  // onSelectRow: function(ticket_no, selected)
			{
				//
				// Okay now make the detail Add, Import, and Remove buttons appear under the detail grid.
				//
                $(".detail_green").show();
                $(".detail_red").show();
                $(".detail_brown").show();

				dialog_list_name_id = list_name_id;

				if(list_name_id == null)
				{
					list_name_id = 0;

					if(jQuery(detailGrid).jqGrid('getGridParam', 'records') > 0)
					{
						jQuery(detailGrid).jqGrid('setGridParam',
							{
								url:  "ajax_jqgrid_server_lists_detail.php?list_name_id=" + list_name_id,
								page: 1
							}
						);

						list_name = $(this).jqGrid("getCell", list_name_id, "list_name");
						caption =  list_name + ' - Server List';
						dialog_title = caption;

						jQuery(detailGrid).jqGrid('setCaption', caption).trigger('reloadGrid');
					}
				}
				else
				{
					jQuery(detailGrid).jqGrid('setGridParam',
						{
							url:  "ajax_jqgrid_server_lists_detail.php?list_name_id=" + list_name_id,
							page: 1
						}
					);

					list_name = $(this).jqGrid("getCell", list_name_id, "list_name");
					caption =  list_name + ' - Server List';
					dialog_title = caption;

					jQuery(detailGrid).jqGrid('setCaption', caption).trigger('reloadGrid');
				}
			}
		});

		jQuery(masterGrid).jqGrid('setLabel', 'list_name_id', '', {'text-align': 'center'}, {title: 'Unique record ID.'});
		jQuery(masterGrid).jqGrid('setLabel', 'server_count', '', {'text-align': 'center'}, {title: 'Number of servers in the list.'});
		jQuery(masterGrid).jqGrid('setLabel', 'list_name',    '', {'text-align': 'left'},   {title: 'Name of the list.'});
		jQuery(masterGrid).jqGrid('setLabel', 'create_date',  '', {'text-align': 'center'}, {title: 'TList creation date.'});
		jQuery(masterGrid).jqGrid('setLabel', 'owner_cuid',   '', {'text-align': 'center'}, {title: 'The owner CUID.'});
		jQuery(masterGrid).jqGrid('setLabel', 'owner_name',   '', {'text-align': 'left'},   {title: 'The owner Name.'});

		jQuery(masterGrid).jqGrid('navGrid', masterGridPager,
			{ add: false, edit: false, del: false, refresh: false },  // options
			{}, // edit options
			{}, // add options
			{}, // del options
			{
				// Search
				closeOnEscape:  true,
				multipleSearch: true,
				multipleGroup:  true
			}
		).navButtonAdd(masterGridPager,
			{
				caption:     '',
				title:       "Refresh the Grid",
				buttonicon : 'ui-icon-refresh',
				onClickButton:function()
				{
					$(masterGrid).trigger("reloadGrid",[{current:true}]);
				}
			}
		);

		// End of stuff for masterGrid

		//
		//  Create Date  Owner CUID  Owner Name       Hostname      IP Address    OS      System Usage   Managing Group
		//  -----------  ----------  ---------------  ------------  ------------  ------  -------------  --------------
		//  09/20/2016   gparkin     Greg Parkin      lxomp11m      153.73.2.15   Linux   PRODUCTION     CMP-UNIX
		//

		// list_system_id
		// list_name_id
		// create_date
		// owner_cuid
		// owner_name
		// computer_hostname
		// computer_ip_address
		// computer_os_lite
		// computer_status
		// computer_managing_group

		$(detailGrid).jqGrid({
			url:      'ajax_jqgrid_server_lists_detail.php?list_name_id=0',
			mtype:    "GET",
			datatype: "json",
			postData:
			{
				action:       'get',
				where_clause: '',
				order_by:     't.computer_hostname',
				direction:    'asc'
			},
			colModel: [
				{
					key: true,
					label: 'List System ID',
					name: 'list_system_id',
					align: 'left',
					width: 120,
					hidden: true
				},
				{
					label: 'List Name ID',
					name: 'list_name_id',
					align: 'left',
					width: 120,
					hidden: true
				},
				{
					label: 'Hostname',
					name: 'computer_hostname',
					align: 'left',
					width: 120
				},
				{
					label: 'IP Address',
					name: 'computer_ip_address',
					width: 120,
					align: 'left',
					search: true
				},
				{
					label: 'OS',
					name: 'computer_os_lite',
					align: 'left',
					width: 120
				},
				{
					label: 'Server Usage',
					name: 'computer_status',
					width: 200,
					align: 'left',
					search: true
				},
				{
					label: 'Managing Group',
					name: 'computer_managing_group',
					align: 'left',
					width: 120
				},
				{
					label: 'Create Date',
					name: 'create_date',
					width: 120,
					align: 'center',
					search: true,
					searchoptions: {
						sopt: ['eq', 'ne', 'lt', 'le', 'gt', 'ge'],
						dataInit: function (elem)
						{
							$(elem).datetimepicker(
								{
									changeMonth: true,
									changeYear: true,
									showSecond: true,
									dateFormat: 'mm/dd/yy',
									timeFormat: 'hh:mm',
									hourGrid: 4,
									minuteGrid: 10,
									secondGrid: 10,
									addSliderAccess: true,
									sliderAccessArgs: {touchonly: false}
								}
							);
						}
					}
				},
				{
					label: 'Owner CUID',
					name: 'owner_cuid',
					width: 150,
					align: 'left',
					hidden: true,
					search: true,
					stype: "select",
					searchoptions: {
						value: "ALL:ALL;<?php printf("%s;%s", $_SESSION['user_cuid'], $_SESSION['user_cuid']); ?>",
						defaultValue: "<?php printf("%s", $_SESSION['user_cuid']); ?>"
					}
				},
				{
					label: 'Owner Name',
					name: 'owner_name',
					width: 250,
					align: 'left',
					search: true
				}
			],
			caption:      'Server List',
			width:        '100%',
			height:       '100%',
			rowNum:       10,
			viewrecords:  true,
			altRows:      true,
			viewsortcols: true,
			loadtext:     'Loading List of Servers...',
			imgpath:      'images',
			multiselect:  true,
			pager:        detailGridPager,
			onSelectRow:  function (list_system_id)
			{
			}
		});

		jQuery("#ms1").click(
			function()
			{
				var s;
				s = jQuery(detailGrid).jqGrid('getGridParam', 'selarrrow');
				alert(s);
			}
		);

		jQuery(detailGrid).jqGrid('setLabel', 'list_system_id',          '', {'text-align': 'center'}, {title: 'Unique record ID.'});
		jQuery(detailGrid).jqGrid('setLabel', 'list_name_id',            '', {'text-align': 'center'}, {title: 'Unique record ID.'});
		jQuery(detailGrid).jqGrid('setLabel', 'create_date',             '', {'text-align': 'center'}, {title: 'List creation date.'});
		jQuery(detailGrid).jqGrid('setLabel', 'owner_cuid',              '', {'text-align': 'center'}, {title: 'The owner CUID.'});
		jQuery(detailGrid).jqGrid('setLabel', 'owner_name',              '', {'text-align': 'left'},   {title: 'The owner Name.'});
		jQuery(detailGrid).jqGrid('setLabel', 'computer_hostname',       '', {'text-align': 'left'},   {title: 'Target Host or Server name.'});
		jQuery(detailGrid).jqGrid('setLabel', 'computer_ip_address',     '', {'text-align': 'left'},   {title: 'Hostname IP Address.'});
		jQuery(detailGrid).jqGrid('setLabel', 'computer_os_lite',        '', {'text-align': 'left'},   {title: 'Operating System Name.'});
		jQuery(detailGrid).jqGrid('setLabel', 'computer_status',         '', {'text-align': 'left'},   {title: 'Server Usage.'});
		jQuery(detailGrid).jqGrid('setLabel', 'computer_managing_group', '', {'text-align': 'left'},   {title: 'Managing Support Group.'});

		jQuery(detailGrid).jqGrid('navGrid', detailGridPager,
			{ add: false, edit: false, del: false, refresh: false },  // options
			{}, // edit options
			{}, // add options
			{}, // del options
			{
				// Search
				closeOnEscape:  true,
				multipleSearch: true,
				multipleGroup:  true
			}
		).navButtonAdd(detailGridPager,
			{
				caption:     '',
				title:       "Refresh the Grid",
				buttonicon : 'ui-icon-refresh',
				onClickButton:function()
				{
					$(masterGrid).trigger("reloadGrid",[{current:true}]);
					$(detailGrid).trigger("reloadGrid",[{current:true}]);
				}
			}
		);
		// End of stuff for detailGrid
	});

	function openAddDialog()
	{
		// dialog_list_name_add.php

		var url = 'dialog_list_name.php?action=add&list_name_id=0';
		var content = '<iframe src="' + url + '" ' +
			'style="border: none; min-width: 100%; min-height: 100%;">' + '</iframe>';

		w2popup.open({
			title     : 'Add List Name',
			body      : content, // html for body
			width     : 650,     // width of the popup
			height    : 185,     // height of the popu
			overflow  : 'hidden',
			color     : '#333',  // color of the screen lock
			speed     : '0.3',   // speed popup appears
			opacity   : '0.8',   // opacity of the screen lock
			modal     : true,    // if modal, it cannot be closed by clicking on the screen lock
			showClose : true,
			showMax   : true,
			keyboard  : true,    // Close if ESC is pressed
			onOpen    : function (event)
			{

			},
			onClose   : function (event)
			{
				$('#masterGrid').trigger("reloadGrid",[{current:true}]);
				$('#detailGrid').jqGrid('clearGridData');
			},
			onMax     : function (event) { console.log('max'); },
			onMin     : function (event) { console.log('min'); },
			onKeydown : function (event) { console.log('keydown'); }
		});
	}

	function openEditDialog()
	{
		// dialog_system_list_edit.php

		var url = 'dialog_list_name.php?action=edit&list_name_id=' + dialog_list_name_id;
		var content = '<iframe src="' + url + '" ' +
			'style="border: none; min-width: 100%; min-height: 100%;">' + '</iframe>';

		if (dialog_list_name_id == 0)
		{
			alert('Please select a list name you want to edit before clicking on the Edit button.');
			return;
		}

		w2popup.open({
			title     : 'Edit List Name',
			body      : content, // html for body
			width     : 650,     // width of the popup
			height    : 185,     // height of the popu
			overflow  : 'hidden',
			color     : '#333',  // color of the screen lock
			speed     : '0.3',   // speed popup appears
			opacity   : '0.8',   // opacity of the screen lock
			modal     : true,    // if modal, it cannot be closed by clicking on the screen lock
			showClose : true,
			showMax   : true,
			keyboard  : true,    // Close if ESC is pressed
			onOpen    : function (event)
			{

			},
			onClose   : function (event)
			{
				$('#masterGrid').trigger("reloadGrid",[{current:true}]);
				$('#detailGrid').trigger("reloadGrid",[{current:true}]);
			},
			onMax     : function (event) { console.log('max'); },
			onMin     : function (event) { console.log('min'); },
			onKeydown : function (event) { console.log('keydown'); }
		});
	}

	//
	// Remove: Remove list
	//
	function openRemoveDialog()
	{
		var url = 'dialog_list_name_remove.php?list_name_id=' + dialog_list_name_id;
		var content = '<iframe src="' + url + '" ' +
			'style="border: none; min-width: 100%; min-height: 100%;">' + '</iframe>';

		w2popup.open({
			title     : 'List Name: ' + list_name,
			body      : content, // html for body
			width     : 600,     // width of the popup
			height    : 185,     // height of the popu
			overflow  : 'hidden',
			color     : '#333',  // color of the screen lock
			speed     : '0.3',   // speed popup appears
			opacity   : '0.8',   // opacity of the screen lock
			modal     : true,    // if modal, it cannot be closed by clicking on the screen lock
			showClose : true,
			showMax   : true,
			keyboard  : true,    // Close if ESC is pressed
			onOpen    : function (event)
			{
			},
			onClose   : function (event)
			{
				$('#masterGrid').trigger("reloadGrid",[{current:true}]);
				//$('#detailGrid').trigger("reloadGrid",[{current:true}]);
				$('#detailGrid').jqGrid('clearGridData');
			},
			onMax     : function (event) { console.log('max'); },
			onMin     : function (event) { console.log('min'); },
			onKeydown : function (event) { console.log('keydown'); }
		});
	}

	function openImportDialog()
	{
		var url = 'dialog_list_systems_import.php?list_name_id=' + dialog_list_name_id;
		var content = '<iframe src="' + url + '" ' +
			'style="border: none; min-width: 100%; min-height: 98%;">' + '</iframe>';

		w2popup.open({
			title     : dialog_title,
			body      : content,
			width     : 1300,
			height    : 700,
			overflow  : 'hidden',
			color     : '#333',
			speed     : '0.3',
			opacity   : '0.8',
			modal     : true,
			showClose : true,
			showMax   : true,
			keyboard  : true,    // Close if ESC is pressed
			onOpen    : function (event)
			{

			},
			onClose   : function (event)
			{
				$('#masterGrid').trigger("reloadGrid",[{current:true}]);
				$('#detailGrid').trigger("reloadGrid",[{current:true}]);
			},
			onMax     : function (event) { console.log('max'); },
			onMin     : function (event) { console.log('min'); },
			onKeydown : function (event) { console.log('keydown'); }
		});
	}

	//
	// Delete one or more selected (checked boxes).
	//
	function deleteCheckedServers()
	{
		//
		// Grab the list of list_system_id's to delete
		//
		var list;
		list = jQuery(detailGrid).jqGrid('getGridParam', 'selarrrow');
		//alert(s);
		//return;

		//var url = 'dialog_system_list_delete.php';
		var data;

		$(".loader").show();

		//
		// Prepare the data that will be sent to ajax_ticket.php
		//
		data = {
			"list_name_id": dialog_list_name_id,
			"list":         list
		};

		var url = 'ajax_system_list_remove.php';

		$.ajax(
			{
				type:     "POST",
				url:      url,
				dataType: "json",
				data:     JSON.stringify(data),
				success:  function(data)
				{
					$(".loader").fadeOut("slow");
					alert(data['ajax_message']);

					$('#masterGrid').trigger("reloadGrid",[{current:true}]);
					$('#detailGrid').trigger("reloadGrid",[{current:true}]);
				},
				error: function(jqXHR, exception, errorThrown)
				{
					if (jqXHR.status === 0) {
						alert('Not connect.\n Verfiy Network.');
					} else if (jqXHR.status == 404) {
						alert('Requested page not found. [404]');
					} else if (jqXHR.status == 500) {
						alert('Internal Server Error [500]');
					} else if (exception === 'parsererror') {
						alert('Requested JSON parse failed.' + ' Error code: ' + errorThrown + ' ResponseText: ' + jqXHR.responseText);
					} else if (exception === 'timeout') {
						alert('Time out error.');
					} else if (exception === 'abort') {
						alert('Ajax request aborted.');
					} else {
						alert('Uncaught Error.\n' + jqXHR.responseText);
					}
				}
			}
		);
	}

	function openDeleteAllDialog()
	{
		// dialog_system_list_delete_all.php

		var url = 'dialog_list_systems_delete_all.php?list_name_id=' + dialog_list_name_id;
		var content = '<iframe src="' + url + '" ' +
			'style="border: none; min-width: 100%; min-height: 100%;">' + '</iframe>';

		w2popup.open({
			title     : dialog_title,
			body      : content, // html for body
			width     : 600,     // width of the popup
			height    : 185,     // height of the popu
			overflow  : 'hidden',
			color     : '#333',  // color of the screen lock
			speed     : '0.3',   // speed popup appears
			opacity   : '0.8',   // opacity of the screen lock
			modal     : true,    // if modal, it cannot be closed by clicking on the screen lock
			showClose : true,
			showMax   : true,
			keyboard  : true,    // Close if ESC is pressed
			onOpen    : function (event)
			{
			},
			onClose   : function (event)
			{
				$('#masterGrid').trigger("reloadGrid",[{current:true}]);
				$('#detailGrid').jqGrid('clearGridData');
			},
			onMax     : function (event) { console.log('max'); },
			onMin     : function (event) { console.log('min'); },
			onKeydown : function (event) { console.log('keydown'); }
		});
	}

	//
	// Setup a event listener to close w2popup (iframe) windows.
	// This event is sent from the child popup iframe window to
	// the parent (this). The event will instruct the parent to
	// close the w2popup window.
	//
	window.addEventListener('message', function(e) {
		var key = e.message ? 'message' : 'data';
		var data = e[key];

		//alert('Event fired!');

        //alert(e.type);

		w2popup.close();

	},false);

</script>

<script type="text/javascript">
	$(window).load(function()
	{
		$(".loader").fadeOut("slow");
	});

</script>

<body style="background-color: lightgoldenrodyellow">

<form name="f1" method="post" action="toolbar_server_lists.php">

	<div class="loader"></div>

	<center>
		<table border="0" cellpadding="4" cellspacing="4">
			<tr>
				<td align="center">
					<table id="masterGrid"></table>
					<div id="masterGridPager" class=scroll></div><br>
                    <a class="green" title="Add a new server list."
                       href="#" onclick="openAddDialog();">Add</a>
                    <a class="blue" title="Edit server list name."
                       href="#" onclick="openEditDialog();">Edit</a>
                    <a class="red" title="Delete a new server list."
                       href="#" onclick="openRemoveDialog();">Delete</a>
				</td>
			</tr>
			<tr>
				<td align="center">
					<table id="detailGrid"></table>
					<div id="detailGridPager" class=scroll></div><br>
                    <a class="detail_green" title="Add server to your selected list."
                       href="#" onclick="openImportDialog();">Add</a>
                    <a class="detail_red" title="Delete one or more servers from your selected list."
                       href="#" onclick="deleteCheckedServers();">Delete</a>
                    <a class="detail_brown" title="Delete all the servers from your selected list."
                       href="#" onclick="openDeleteAllDialog();">Delete All</a>
				</td>
			</tr>
		</table>
	</center>

</form>
</body>
</html>
