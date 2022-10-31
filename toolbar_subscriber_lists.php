<?php
/**
 * toolbar_subscriber_lists.php
 *
 * @package   PhpStorm
 * @file      doit.php
 * @author    gparkin
 * @date      11/21/16
 * @version   7.0
 *
 * @brief     Subscriber Lists Editor.
 *
 * toolbar_subscriber_lists.php
 *
 *   ajax_jqgrid_subscriber_groups.php
 *   ajax_jqgrid_subscriber_members.php
 *   ajax_jqgrid_subscriber_servers.php
 *
 *   dialog_subscriber_groups.php
 *     ajax_dialog_subscriber_groups.php
 *
 *   dialog_subscriber_members.php
 *     ajax_dialog_subscriber_members.php
 *
 *   dialog_subscriber_servers.php
 *     ajax_dialog_subscriber_servers.php
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
$lib->debug_start('toolbar_subscriber_lists.html');
date_default_timezone_set('America/Denver');

$lib->globalCounter();

//$lib->html_dump();

$my_request   = array();
$param        = array();
$param_count  = 0;

//
// URL command line options
//
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

	var dialog_member_title  = '';
	var dialog_server_title  = '';

	var onselect_group_id    = 0;
	
	var members_caption      = '';
	var servers_caption      = '';

	$(document).ready(function ()
	{
		//
		// Hide the Detail list buttons until the first list is loaded.
		//

		$(".member_add").hide();
		$(".member_delete").hide();
		$(".member_delete_all").hide();
		
		$(".server_add").hide();
		$(".server_delete").hide();
		$(".server_remove_all").hide();
		$(".server_approver").hide();
		$(".server_fyi").hide();

		var groupGridID      = 'groupGrid';
		var groupGridPagerID = 'groupGridPager';
		var groupGrid        = '#' + groupGridID;
		var groupGridPager   = '#' + groupGridPagerID;

		var memberGridID      = 'memberGrid';
		var memberGridPagerID = 'memberGridPager';
		var memberGrid        = '#' + memberGridID;
		var memberGridPager   = '#' + memberGridPagerID;

		var serverGridID      = 'serverGrid';
		var serverGridPagerID = 'serverGridPager';
		var serverGrid        = '#' + serverGridID;
		var serverGridPager   = '#' + serverGridPagerID;

		/**
		 * =========================================== groupGrid ====================================================
		 */

		//
		// Begin: groupsGrid
		//
		// cct7_subscriber_groups
		//
		// group_id|VARCHAR2|20|NOT NULL|PK: Unique Record ID
		// create_date|NUMBER|0||GMT date record was created
		// owner_cuid|VARCHAR2|20||Owner CUID of this subscriber list
		// owner_name|VARCHAR2|200||Owner NAME of this subscriber list
		// group_name|VARCHAR2|200||Group Name
		//
		$(groupGrid).jqGrid({
			url: 'ajax_jqgrid_subscriber_groups.php',
			mtype: "GET",
			datatype: "json",
			postData: {
				action: 'get',
				where_clause: '',
				order_by: 't.group_name',
				direction: 'asc'
			},
			colModel: [
				{
					label: 'Members',
					name:  'member_count',
					align: 'center',
					width: 100
				},
				{
					label: 'Servers',
					name:  'server_count',
					align: 'center',
					width: 100
				},
				{
					key:   true,
					label: 'Group ID',
					name:  'group_id',
					align: 'center',
					width: 120
				},
				{
					label: 'Group Name',
					name:  'group_name',
					width: 300,
					align: 'left',
					search: true
				},
				{
					label: 'Create Date',
					name:  'create_date',
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
					name:  'owner_cuid',
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
					name:  'owner_name',
					width: 300,
					align: 'left',
					search: true
				}
			],
			caption:      'Subscriber Groups',
			width:        '100%',
			height:       '100%',
			rowNum:       10,
			viewrecords:  true,
			altRows:      true,
			viewsortcols: true,
			loadtext:     'Loading Subscriber Lists...',
			imgpath:      'images',
			multiselect:  false,
			multiboxonly: false,
			pager:        groupGridPager,
			onSelectRow:  function(group_id)
			{
				//
				// Okay now make the edit buttons appear for other grids: members and servers
				//
				$(".member_add").show();
				$(".member_delete").show();
				$(".member_delete_all").show();
				$(".server_add").show();
				$(".server_delete").show();
				$(".server_remove_all").show();
				$(".server_approver").show();
				$(".server_fyi").show();

				if (group_id == null)
				{
					alert('Nothing to view!');
					return;
				}

				onselect_group_id = group_id;
				//alert(onselect_group_id);

				//
				// Load up the member and server grids
				//
				jQuery(memberGrid).jqGrid('setGridParam',
					{
						url:  "ajax_jqgrid_subscriber_members.php?group_id=" + onselect_group_id,
						page: 1
					}
				);

				jQuery(serverGrid).jqGrid('setGridParam',
					{
						url:  "ajax_jqgrid_subscriber_servers.php?group_id=" + onselect_group_id,
						page: 1
					}
				);

				//
				// Set the member and server grid captions to tell the user what subscriber list data
				// they are working with.
				//
				group_name        = $(this).jqGrid("getCell", group_id, "group_name");

				members_caption = group_id + ' - ' + group_name + ' - Member List';
				servers_caption = group_id + ' - ' + group_name + ' - Server List';

				dialog_member_title = members_caption;
				dialog_server_title = servers_caption;

				jQuery(memberGrid).jqGrid('setCaption', members_caption).trigger('reloadGrid');
				jQuery(serverGrid).jqGrid('setCaption', servers_caption).trigger('reloadGrid');
			}
		});

		jQuery(groupGrid).jqGrid('setLabel', 'group_id',          '', {'text-align': 'center'}, {title: 'CCT assigned Group ID. Like a netpin-no.'});
		jQuery(groupGrid).jqGrid('setLabel', 'member_count',      '', {'text-align': 'center'}, {title: 'Number of members.'});
		jQuery(groupGrid).jqGrid('setLabel', 'server_count',      '', {'text-align': 'center'}, {title: 'Number of servers.'});
		jQuery(groupGrid).jqGrid('setLabel', 'create_date',       '', {'text-align': 'center'}, {title: 'Number of servers in the list.'});
		jQuery(groupGrid).jqGrid('setLabel', 'owner_cuid',        '', {'text-align': 'left'},   {title: 'Name of the list.'});
		jQuery(groupGrid).jqGrid('setLabel', 'create_date',       '', {'text-align': 'center'}, {title: 'Subscriber creation date.'});
		jQuery(groupGrid).jqGrid('setLabel', 'owner_cuid',        '', {'text-align': 'center'}, {title: 'The owner CUID.'});
		jQuery(groupGrid).jqGrid('setLabel', 'owner_name',        '', {'text-align': 'left'},   {title: 'The owner Name.'});
		jQuery(groupGrid).jqGrid('setLabel', 'group_name',        '', {'text-align': 'left'},   {title: 'The Subscriber Group Name.'});

		jQuery(groupGrid).jqGrid('navGrid', groupGridPager,
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
		).navButtonAdd(groupGridPager,
			{
				caption:     '',
				title:       "Refresh the Grid",
				buttonicon : 'ui-icon-refresh',
				onClickButton:function()
				{
					$(groupGrid).trigger("reloadGrid",[{current:true}]);
				}
			}
		);
		// End: groupGrid

		/**
		 * =========================================== memberGrid ======================================================
		 */

		// Begin: memberGrid
		//
		// cct7_subscriber_members
		//
		// member_id|NUMBER|0|NOT NULL|PK: Unique Record ID
		// group_id|VARCHAR2|20||FK: cct7_subscriber_groups
		// create_date|NUMBER|0||GMT date record was created
		// member_cuid|VARCHAR2|20||Member CUID
		// member_name|VARCHAR2|200||Member NAME
		//
		$(memberGrid).jqGrid({
			url:      'ajax_jqgrid_subscriber_members.php?group_id=' + onselect_group_id,
			mtype:    "GET",
			datatype: "json",
			postData:
			{
				action:       'get',
				where_clause: '',
				order_by:     't.member_cuid',
				direction:    'asc'
			},
			colModel: [
				{
					key: true,
					label: 'PK: member_id',
					name: 'member_id',
					align: 'left',
					width: 120,
					hidden: true
				},
				{
					label: 'FK: group_id',
					name: 'group_id',
					align: 'left',
					width: 120,
					hidden: true
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
					label: 'Member CUID',
					name: 'member_cuid',
					width: 120,
					align: 'left',
					search: true,
					stype: "select",
					searchoptions: {
						value: "ALL:ALL;<?php printf("%s;%s", $_SESSION['user_cuid'], $_SESSION['user_cuid']); ?>",
						defaultValue: "<?php printf("%s", $_SESSION['user_cuid']); ?>"
					}
				},
				{
					label: 'Member Name',
					name: 'member_name',
					width: 300,
					align: 'left',
					search: true
				}
			],
			caption:      members_caption,
			width:        '100%',
			height:       '100%',
			rowNum:       10,
			viewrecords:  true,
			altRows:      true,
			viewsortcols: true,
			loadtext:     'Loading Member List...',
			imgpath:      'images',
			multiselect:  true,
			pager:        memberGridPager,
			onSelectRow:  function (member_id)
			{
			}
		});

		//
		// cct7_subscriber_members
		//
		// member_id|NUMBER|0|NOT NULL|PK: Unique Record ID
		// group_id|VARCHAR2|20||FK: cct7_subscriber_groups
		// create_date|NUMBER|0||GMT date record was created
		// member_cuid|VARCHAR2|20||Member CUID
		// member_name|VARCHAR2|200||Member NAME
		//
		jQuery(memberGrid).jqGrid('setLabel', 'member_id',   '', {'text-align': 'center'}, {title: 'Unique record ID.'});
		jQuery(memberGrid).jqGrid('setLabel', 'group_id',    '', {'text-align': 'center'}, {title: 'Unique record ID.'});
		jQuery(memberGrid).jqGrid('setLabel', 'create_date', '', {'text-align': 'center'}, {title: 'List creation date.'});
		jQuery(memberGrid).jqGrid('setLabel', 'member_cuid', '', {'text-align': 'center'}, {title: 'The member CUID.'});
		jQuery(memberGrid).jqGrid('setLabel', 'member_name', '', {'text-align': 'left'},   {title: 'The member Name.'});
		
		jQuery(memberGrid).jqGrid('navGrid', memberGridPager,
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
		).navButtonAdd(memberGridPager,
			{
				caption:     '',
				title:       "Refresh the Grid",
				buttonicon : 'ui-icon-refresh',
				onClickButton:function()
				{
					$(groupGrid).trigger("reloadGrid",[{current:true}]);
					$(memberGrid).trigger("reloadGrid",[{current:true}]);
				}
			}
		);
		// End of stuff for memberGrid

		/**
		 * =========================================== serverGrid ======================================================
		 */

		// Begin: serverGrid
		//
		// cct7_subscriber_servers
		//
		// server_id|NUMBER|0|NOT NULL|PK: Unique Record ID
		// group_id|VARCHAR2|20|FK: cct7_subscriber_groups
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
		$(serverGrid).jqGrid({
			url:      'ajax_jqgrid_subscriber_servers.php?group_id=' + onselect_group_id,
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
					label: 'PK: server_id',
					name: 'server_id',
					align: 'left',
					width: 120,
					hidden: true
				},
				{
					label: 'FK: group_id',
					name: 'group_id',
					align: 'left',
					width: 120,
					hidden: true
				},
				{
					label: 'LASTID',
					name: 'computer_lastid',
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
					label: 'Type',
					name: 'notification_type',
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
					width: 300,
					align: 'left',
					search: true
				}
			],
			caption:      servers_caption,
			width:        '100%',
			height:       '100%',
			rowNum:       10,
			viewrecords:  true,
			altRows:      true,
			viewsortcols: true,
			loadtext:     'Loading Server List...',
			imgpath:      'images',
			multiselect:  true,
			pager:        serverGridPager,
			onSelectRow:  function (server_id)
			{
			}
		});

		//
		// cct7_subscriber_servers
		//
		// server_id|NUMBER|0|NOT NULL|PK: Unique Record ID
		// group_id|VARCHAR2|20||FK: cct7_subscriber_groups
		// create_date|VARCHAR2|20||GMT creation date
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
		jQuery(serverGrid).jqGrid('setLabel', 'server_id',               '', {'text-align': 'center'}, {title: 'Unique record ID.'});
		jQuery(serverGrid).jqGrid('setLabel', 'group_id',                '', {'text-align': 'center'}, {title: 'Unique record ID.'});
		jQuery(serverGrid).jqGrid('setLabel', 'create_date',             '', {'text-align': 'center'}, {title: 'Creation date.'});
		jQuery(serverGrid).jqGrid('setLabel', 'owner_cuid',              '', {'text-align': 'center'}, {title: 'The owner CUID.'});
		jQuery(serverGrid).jqGrid('setLabel', 'owner_name',              '', {'text-align': 'left'},   {title: 'The owner Name.'});
		jQuery(serverGrid).jqGrid('setLabel', 'computer_lastid',         '', {'text-align': 'left'},   {title: 'Computer LASTID'});
		jQuery(serverGrid).jqGrid('setLabel', 'computer_hostname',       '', {'text-align': 'left'},   {title: 'Target Host or Server name.'});
		jQuery(serverGrid).jqGrid('setLabel', 'computer_ip_address',     '', {'text-align': 'left'},   {title: 'Hostname IP Address.'});
		jQuery(serverGrid).jqGrid('setLabel', 'computer_os_lite',        '', {'text-align': 'left'},   {title: 'Operating System Name.'});
		jQuery(serverGrid).jqGrid('setLabel', 'computer_status',         '', {'text-align': 'left'},   {title: 'Server Usage.'});
		jQuery(serverGrid).jqGrid('setLabel', 'computer_managing_group', '', {'text-align': 'left'},   {title: 'Managing Support Group.'});
		jQuery(serverGrid).jqGrid('setLabel', 'notification_type',       '', {'text-align': 'left'},   {title: 'Notification type: APPROVER or FYI.'});

		jQuery(serverGrid).jqGrid('navGrid', serverGridPager,
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
		).navButtonAdd(serverGridPager,
			{
				caption:     '',
				title:       "Refresh the Grid",
				buttonicon : 'ui-icon-refresh',
				onClickButton:function()
				{
					$(groupGrid).trigger("reloadGrid",[{current:true}]);
					$(memberGrid).trigger("reloadGrid",[{current:true}]);
					$(serverGrid).trigger("reloadGrid",[{current:true}]);
				}
			}
		);
		// End: serverGrid

	}); // END: .ready(function()...

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
		w2popup.close();

	},false);

</script>

<script type="text/javascript">
	$(window).load(function()
	{
		$(".loader").fadeOut("slow");
	});

	function groupAddDialog()
	{
		var url = 'dialog_subscriber_group.php?action=add&group_id=';
		var content = '<iframe src="' + url + '" ' +
			'style="border: none; min-width: 100%; min-height: 100%;">' + '</iframe>';

		w2popup.open({
			title     : 'Add New Subscriber Group',
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
				$(groupGrid).trigger("reloadGrid",[{current:true}]);
				$(memberGrid).jqGrid('clearGridData');
				$(serverGrid).jqGrid('clearGridData');
			},
			onMax     : function (event) { console.log('max'); },
			onMin     : function (event) { console.log('min'); },
			onKeydown : function (event) { console.log('keydown'); }
		});
	}
	
	function groupEditDialog()
	{
		var url = 'dialog_subscriber_group.php?action=edit&group_id=' + onselect_group_id;
		var content = '<iframe src="' + url + '" ' +
			'style="border: none; min-width: 100%; min-height: 100%;">' + '</iframe>';

		//alert(url);

		w2popup.open({
			title     : 'Edit Subscriber Group',
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
				$(groupGrid).trigger("reloadGrid",[{current:true}]);
			},
			onMax     : function (event) { console.log('max'); },
			onMin     : function (event) { console.log('min'); },
			onKeydown : function (event) { console.log('keydown'); }
		});
	}

	function groupDeleteDialog()
	{
		var url = 'dialog_subscriber_group.php?action=delete&group_id=' + onselect_group_id;
		var content = '<iframe src="' + url + '" ' +
			'style="border: none; min-width: 100%; min-height: 100%;">' + '</iframe>';

		//alert(url);

		w2popup.open({
			title     : 'Delete Subscriber Group',
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
				$(groupGrid).trigger("reloadGrid",[{current:true}]);
				$(memberGrid).jqGrid('clearGridData');
				$(serverGrid).jqGrid('clearGridData');
			},
			onMax     : function (event) { console.log('max'); },
			onMin     : function (event) { console.log('min'); },
			onKeydown : function (event) { console.log('keydown'); }
		});
	}

	function memberAddDialog()
	{
		var url = 'dialog_subscriber_member_add.php?action=add&group_id=' + onselect_group_id;
		var content = '<iframe src="' + url + '" ' +
			'style="border: none; min-width: 100%; min-height: 100%;">' + '</iframe>';

		//alert(url);

		w2popup.open({
			title     : 'Add Subscriber Members',
			body      : content, // html for body
			width     : 650,     // width of the popup
			height    : 400,     // height of the popu
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
				$(groupGrid).trigger("reloadGrid",[{current:true}]);
				$(memberGrid).trigger("reloadGrid",[{current:true}]);
			},
			onMax     : function (event) { console.log('max'); },
			onMin     : function (event) { console.log('min'); },
			onKeydown : function (event) { console.log('keydown'); }
		});
	}

	function memberDeleteDialog()
	{
		w2confirm('Please confirm delete?')
			.yes(function ()
				{
					//
					// Grab the list of server_id's to delete
					//
					var list = jQuery(memberGrid).jqGrid('getGridParam', 'selarrrow');
					//alert(s);
					//return;

					// dialog_subscriber_server.php?action=add&group_id=' + onselect_group_id;

					var url = 'ajax_dialog_subscriber_members.php';
					var data;

					$(".loader").show();

					//
					// Prepare the data that will be sent to ajax_ticket.php
					//
					data = {
						"action":   'delete',
						"group_id": onselect_group_id,
						"list":     list
					};

					$.ajax(
						{
							type:     "POST",
							url:      url,
							dataType: "json",
							data:     JSON.stringify(data),
							success:  function(data)
							{
								$(".loader").fadeOut("slow");

								if (data['ajax_status'] != 'SUCCESS')
									alert(data['ajax_message']);
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

					$(groupGrid).trigger("reloadGrid",[{current:true}]);
					$(memberGrid).trigger("reloadGrid",[{current:true}]);
				}
			)
			.no(function ()
				{
					console.log('NO');
				}
			);
	}

	function memberDeleteAllDialog()
	{
		w2confirm('Are you sure you want to remove all the members?')
			.yes(function ()
				{
					var url = 'ajax_dialog_subscriber_members.php';
					var data;

					$(".loader").show();

					//
					// Prepare the data that will be sent to ajax_ticket.php
					//
					data = {
						"action":   'delete_all',
						"group_id": onselect_group_id
					};

					$.ajax(
						{
							type:     "POST",
							url:      url,
							dataType: "json",
							data:     JSON.stringify(data),
							success:  function(data)
							{
								$(".loader").fadeOut("slow");

								if (data['ajax_status'] != 'SUCCESS')
									alert(data['ajax_message']);

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

					$(groupGrid).trigger("reloadGrid",[{current:true}]);
					$(memberGrid).trigger("reloadGrid",[{current:true}]);
				}
			)
			.no(function ()
				{
					console.log('NO');
				}
			);
	}

	function serverAddDialog()
	{
		var url = 'dialog_subscriber_servers_import.php?action=add&group_id=' + onselect_group_id;
		var content = '<iframe src="' + url + '" ' +
			'style="border: none; min-width: 100%; min-height: 100%;">' + '</iframe>';

		w2popup.open({
			title     : 'Add New Servers',
			body      : content, // html for body
			width     : 1300,
			height    : 700,
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
				$(serverGrid).trigger("reloadGrid",[{current:true}]);
			},
			onMax     : function (event) { console.log('max'); },
			onMin     : function (event) { console.log('min'); },
			onKeydown : function (event) { console.log('keydown'); }
		});
	}

	function serverDeleteChecked()
	{
		w2confirm('Please confirm delete?')
			.yes(function ()
				{
					//
					// Grab the list of server_id's to delete
					//
					var list = jQuery(serverGrid).jqGrid('getGridParam', 'selarrrow');
					//alert(s);
					//return;

					// dialog_subscriber_server.php?action=add&group_id=' + onselect_group_id;

					var url = 'ajax_dialog_subscriber_servers.php';
					var data;

					$(".loader").show();

					//
					// Prepare the data that will be sent to ajax_ticket.php
					//
					data = {
						"action":   'delete',
						"group_id": onselect_group_id,
						"list":     list
					};

					$.ajax(
						{
							type:     "POST",
							url:      url,
							dataType: "json",
							data:     JSON.stringify(data),
							success:  function(data)
							{
								$(".loader").fadeOut("slow");

								if (data['ajax_status'] != 'SUCCESS')
									alert(data['ajax_message']);
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

					$(groupGrid).trigger("reloadGrid",[{current:true}]);
					$(serverGrid).trigger("reloadGrid",[{current:true}]);
				}
			)
			.no(function ()
				{
					console.log('NO');
				}
			);
	}

	function serverDeleteAllDialog()
	{
		w2confirm('Are you sure you want to remove all the servers?')
			.yes(function ()
				{
					var url = 'ajax_dialog_subscriber_servers.php';
					var data;

					$(".loader").show();

					//
					// Prepare the data that will be sent to ajax_ticket.php
					//
					data = {
						"action":   'delete_all',
						"group_id": onselect_group_id
					};

					$.ajax(
						{
							type:     "POST",
							url:      url,
							dataType: "json",
							data:     JSON.stringify(data),
							success:  function(data)
							{
								$(".loader").fadeOut("slow");

								if (data['ajax_status'] != 'SUCCESS')
									alert(data['ajax_message']);
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

					$(groupGrid).trigger("reloadGrid",[{current:true}]);
					$(serverGrid).trigger("reloadGrid",[{current:true}]);
				}
			)
			.no(function ()
				{
					console.log('NO');
				}
			);
	}

	function serverSetAPPROVER()
	{
		//
		// Grab the list of server_id's to delete
		//
		var list = jQuery(serverGrid).jqGrid('getGridParam', 'selarrrow');
		//alert(s);
		//return;

		// dialog_subscriber_server.php?action=add&group_id=' + onselect_group_id;

		var url = 'ajax_dialog_subscriber_servers.php';
		var data;

		$(".loader").show();

		//
		// Prepare the data that will be sent to ajax_ticket.php
		//
		data = {
			"action":   'set_approver',
			"group_id": onselect_group_id,
			"list":     list
		};

		$.ajax(
			{
				type:     "POST",
				url:      url,
				dataType: "json",
				data:     JSON.stringify(data),
				success:  function(data)
				{
					$(".loader").fadeOut("slow");

					if (data['ajax_status'] != 'SUCCESS')
						alert(data['ajax_message']);
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

		$(groupGrid).trigger("reloadGrid",[{current:true}]);
		$(serverGrid).trigger("reloadGrid",[{current:true}]);
		$(serverGrid).jqGrid('resetSelection');
	}

	function serverSetFYI()
	{
		//
		// Grab the list of server_id's to delete
		//
		var list = jQuery(serverGrid).jqGrid('getGridParam', 'selarrrow');
		//alert(s);
		//return;

		// dialog_subscriber_server.php?action=add&group_id=' + onselect_group_id;

		var url = 'ajax_dialog_subscriber_servers.php';
		var data;

		$(".loader").show();

		//
		// Prepare the data that will be sent to ajax_ticket.php
		//
		data = {
			"action":   'set_fyi',
			"group_id": onselect_group_id,
			"list":     list
		};

		$.ajax(
			{
				type:     "POST",
				url:      url,
				dataType: "json",
				data:     JSON.stringify(data),
				success:  function(data)
				{
					$(".loader").fadeOut("slow");

					if (data['ajax_status'] != 'SUCCESS')
						alert(data['ajax_message']);
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

		$(groupGrid).trigger("reloadGrid",[{current:true}]);
		$(serverGrid).trigger("reloadGrid",[{current:true}]);
		$(serverGrid).jqGrid('resetSelection');
	}
</script>

<body style="background-color: lightgoldenrodyellow">

<form name="f1" method="post" action="toolbar_subscriber_lists.php">

	<div class="loader"></div>

	<center>
		<table border="0" cellpadding="4" cellspacing="4">
			<tr>
				<td align="center" valign="top">
					<table id="groupGrid"></table>
					<div   id="groupGridPager" class=scroll></div><br>
				</td>
				<td align="center" valign="top">
					<table id="memberGrid"></table>
					<div   id="memberGridPager" class=scroll></div><br>
				</td>
			</tr>
			<tr>
				<td align="center" valign="top">
					<input id='group_add'    type="button" value="Add"
						   title="Add new subscriber list."
						   onclick="groupAddDialog();">&nbsp;&nbsp;
					<input id='group_edit'   type="button" value="Edit"
						   title="Edit the group name."
						   onclick="groupEditDialog();">&nbsp;&nbsp;
					<input id='group_delete' type="button" value="Delete"
						   title="Delete this subscriber list."
						   onclick="groupDeleteDialog();">
				</td>
				<td align="center" valign="top">
					<input id='member_add'    class="member_add"    type="button" value="Add"
						   title="Add one or more members to this group."
						   onclick="memberAddDialog();">&nbsp;&nbsp;
					<input id='member_delete'   class="member_delete"   type="button" value="Delete"
						   title="Delete selected members for this list. "
						   onclick="memberDeleteDialog();">&nbsp;&nbsp;
					<input id='member_delete_all' class="member_delete_all" type="button" value="Delete All"
						   title="Delete all the members from this list."
						   onclick="memberDeleteAllDialog();">
				</td>
			</tr>
			<tr>
				<td align="center" valign="top" colspan="2">
					<hr>
					<table id="serverGrid"></table>
					<div   id="serverGridPager"   class=scroll></div><br>
					<input id='server_add'        class="server_add"        type="button" value="Add"
						   title="Add one or more servers to this group."
						   onclick="serverAddDialog();">&nbsp;&nbsp;
					<input id='server_delete'     class="server_delete"     type="button" value="Delete"
						   title="Delete selected servers from this list."
						   onclick="serverDeleteChecked();">&nbsp;&nbsp;
					<input id='server_remove_all' class="server_remove_all" type="button" value="Delete All"
						   title="Delete all the servers from this list."
						   onclick="serverDeleteAllDialog();">&nbsp;&nbsp;
					<input id='server_approver'   class="server_approver"   type="button" value="APPROVER"
						   title="Set notification type for selected servers to APPROVER."
						   onclick="serverSetAPPROVER();">&nbsp;&nbsp;
					<input id='server_fyi'        class="server_fyi"        type="button" value="FYI"
						   title="Set notification type for selected servers to FYI."
						   onclick="serverSetFYI();">
				</td>
			</tr>
		</table>
	</center>
</form>
</body>
</html>
