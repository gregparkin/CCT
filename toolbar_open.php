<?php
/**
 * toolbar_open.php
 *
 * @package   PhpStorm
 * @file      doit.php
 * @author    gparkin
 * @date      7/23/16
 * @version   7.0
 *
 * @brief     Open up CCT work requests. Main program used for toolbar icons:
 *            - Open Tickets
 *            - Approve
 *            - All Tickets
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
$lib->debug_start('toolbar_open.html');
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
// $what_tickets = [ '', '<ticket_no | cm_ticket_no>', 'group', or 'approve' ]
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
$title = "All Tickets";

if (strlen($what_netpin_no) > 0)
{
    $title = sprintf("Search: %s", $what_netpin_no);
	$caption_tickets = sprintf("Tickets (%s)", $what_netpin_no);
}
else if (strlen($what_hostname) > 0)
{
    $title = sprintf("Search: %s", $what_hostname);
	$caption_tickets = sprintf("Tickets (%s)", $what_hostname);
}
else if ($what_tickets == 'group')
{
    $title = "Group Tickets";
	$caption_tickets = sprintf("Tickets (Group Owned)");
}
else if ($what_tickets == 'approve')
{
    $title = "Approve Queue and FYI Notification Tickets";
	$caption_tickets = sprintf("Tickets (To Approve)");
}
else if (strlen($what_tickets) > 0)
{
    $title = sprintf("Search: %s", $what_tickets);
    $caption_tickets = sprintf("Tickets (%s)", $what_tickets);
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

    .button_blue
    {
        border: font-size: 11px;
        width: 10em;
        color: #0000cc;
        font-weight: bold;
        font-family: arial;
        background-color: #d3d3d3;
        text-decoration: none;
    }

    .button_gray
    {
        border: font-size: 11px;
        width: 10em;
        color: #000000;
        font-weight: bold;
        font-family: arial;
        background-color: #d3d3d3;
        text-decoration: none;
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
<script type="text/javascript" src="js/jquery.jqGrid.min.js"></script>
<script type="text/javascript" src="js/grid.locale-en.js"></script>

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

    var approve_filter = 'show_all';

	/**
	 * @fn    updateGridAllStatus(ticket_no, system_id, subGrid)
	 *
	 * @brief Called when openContactsDialog() closes. It's purpose is to retrieve ticket and server status
	 *        information and then update rows that have changed in the ticket and server grids.
	 *
	 * @param ticket_no
	 * @param system_id
	 * @param subGrid
	 */
	function updateGridAllStatus(ticket_no, system_id, subGrid)
	{
		// #mainGrid_CCT700000001_subgrid1_1_subgrid2
		//alert(subGrid);
		//grid_info();

		//
		// Prepare JSON data containing the ticket_no and system_id we want update.
		//
		var data = {
			'ticket_no': ticket_no,
			'system_id': system_id
		};

		var url = 'ajax_get_status_info.php?ticket_no=' + ticket_no + '&system_id=' + system_id;

		//
		// Make a jQuery AJAX call to ajax_get_status_info.php to return the data we want to update in the grids.
		//
		$.ajax({
			type: 'GET',
			url:   url,
			async: false,
			beforeSend: function (xhr) {
				if (xhr && xhr.overrideMimeType) {
					xhr.overrideMimeType('application/json;charset=utf-8');
				}
			},
			dataType: 'json',
			success: function (data) {
				//alert('We got our data!');

				// alert('updateGridAllStatus() - ajax_status: ' + data['ajax_status']);

				// data[ajax_status] => SUCCESS
				// data[ajax_message] =>
				// data[ticket_no] => CCT700000002
				// data[status] => DRAFT
				// data[total_servers_scheduled] => 3
				// data[total_servers_waiting] => 0
				// data[total_servers_approved] => 2
				// data[total_servers_rejected] => 1
				// data[total_servers_not_scheduled] => 0
				// data[system_id] => 327
				// data[system_work_start_date] => 09/05/2016 02:00
				// data[system_work_end_date] => 09/05/2016 04:00
				// data[system_work_duration] => 00:02:00
				// data[system_work_status] => REJECTED
				// data[total_contacts_responded] => 1
				// data[total_contacts_not_responded] => 0

				//
				// Check ajax return status
				//
				// $json['ajax_status']               = 'SUCCESS';
				// $json['ajax_message']              = '';
				//
				if (data['ajax_status'] === 'REFRESH')
				{
					alert('toolbar_open.php updateGridAllStatus() - REFRESH');
					$('#mainGrid').trigger("reloadGrid");
					//$('#mainGrid').trigger("reloadGrid",[{current:true}]);
					return;
				}

				if (data['ajax_status'] !== 'SUCCESS')
				{
					alert(data['ajax_message']);
					return;
				}

				//
				// Parse the subGrid string to get the names of the grids we want to update.
				//
				// Example of a subGrid string: #mainGrid_CCT700000001_subgrid1_1_subgrid2

				// Grid: mainGrid,                                  pager: #mainGridPager
				// Grid: mainGrid_CCT700000001_subgrid1,            pager: #mainGrid_CCT700000001_subgrid1_page
				// Grid: mainGrid_CCT700000001_subgrid1_1_subgrid2, pager: #mainGrid_CCT700000001_subgrid1_1_subgrid2_pager

				var arr = subGrid.split('_');

				if (arr.length >= 5)
				{
					var ticketGrid    = arr[0];
					var ticketRowKey  = arr[1];

					$(ticketGrid).setCell(ticketRowKey, "status",                       data['status']);
					$(ticketGrid).setCell(ticketRowKey, "schedule_start_date",          data['schedule_start_date']);
                    $(ticketGrid).setCell(ticketRowKey, "schedule_end_date",            data['schedule_end_date']);
					$(ticketGrid).setCell(ticketRowKey, "total_servers_scheduled",      data['total_servers_scheduled']);
					$(ticketGrid).setCell(ticketRowKey, "total_servers_waiting",        data['total_servers_waiting']);
					$(ticketGrid).setCell(ticketRowKey, "total_servers_approved",       data['total_servers_approved']);
					$(ticketGrid).setCell(ticketRowKey, "total_servers_rejected",       data['total_servers_rejected']);
					$(ticketGrid).setCell(ticketRowKey, "total_servers_not_scheduled",  data['total_servers_not_scheduled']);

					// i.e. #mainGrid_CCT700000010_subgrid1_pager
					var systemGrid    = arr[0] + '_' + arr[1] + '_' + arr[2];
					var systemRowKey  = arr[3];

					//var ColumnName = $(systemGrid).jqGrid("getCell", systemRowKey, "system_work_status");
					//alert(ColumnName);
					//$(systemGrid).setCell(systemRowKey, "system_work_status", "CRAPIT");

					$(systemGrid).setCell(systemRowKey, "system_work_start_date",       data['system_work_start_date']);
					$(systemGrid).setCell(systemRowKey, "system_work_end_date",         data['system_work_end_date']);
					$(systemGrid).setCell(systemRowKey, "system_work_duration",         data['system_work_duration']);
					//$(systemGrid).setCell(systemRowKey, "system_work_status",           data['system_work_status']);
					$(systemGrid).setCell(systemRowKey, "total_contacts_responded",     data['total_contacts_responded']);
					$(systemGrid).setCell(systemRowKey, "total_contacts_not_responded", data['total_contacts_not_responded']);

					switch ( data['system_work_status'] )
					{
						case 'DRAFT':    // DRAFT    - Magenta         #FF00FF
							$(systemGrid).setCell(systemRowKey , "system_work_status", "DRAFT",    { color: '#FF00FF'});
							break;
						case 'ACTIVE':   // ACTIVE   - navy            #000080
							$(systemGrid).setCell(systemRowKey , "system_work_status", "ACTIVE",   { color: '#000080'});
							break;
						case 'FROZEN':   // FROZEN   - dark slate gray #2F4F4F
							$(systemGrid).setCell(systemRowKey , "system_work_status", "FROZEN",   { color: '#2F4F4F'});
							break;
						case 'WAITING':  // WAITING  - Teal            #008080
							$(systemGrid).setCell(systemRowKey , "system_work_status", "WAITING",  { color: '#008080'});
							break;
						case 'APPROVED': // APPROVED - dark green      #006400
							$(systemGrid).setCell(systemRowKey , "system_work_status", "APPROVED", { color: '#006400'});
							break;
						case 'REJECTED': // REJECTED - Maroon          #800000
							$(systemGrid).setCell(systemRowKey , "system_work_status", "REJECTED", { color: '#800000'});
							break;
						case 'EXEMPT':   // EXEMPT   - orange red      #FF4500
							$(systemGrid).setCell(systemRowKey , "system_work_status", "EXEMPT",   { color: '#FF4500'});
							break;
						case 'CANCELED': // CANCELED - Indian Red      #CD5C5C
							$(systemGrid).setCell(systemRowKey , "system_work_status", "CANCELED", { color: '#CD5C5C'});
							break;
						case 'CLOSED':   // CLOSED   - black           #000000
							$(systemGrid).setCell(systemRowKey , "system_work_status", "CLOSED",   { color: '#000000'});
							break;
						default:
							$(systemGrid).setCell(systemRowKey, "system_work_status", data['system_work_status']);
							break;
					}

					//alert('We made it!');
				}
				else
				{
					//alert('length of subGrid split arr not >= 5. It is ' + arr.length);

					var ticketGrid    = arr[0];
					var ticketRowKey  = arr[1];

					//alert('ticketGrid: ' + ticketGrid + '  ticketRowKey: ' + ticketRowKey);

					$(ticketGrid).setCell(ticketRowKey, "status",                       data['status']);
                    $(ticketGrid).setCell(ticketRowKey, "schedule_start_date",          data['schedule_start_date']);
                    $(ticketGrid).setCell(ticketRowKey, "schedule_end_date",            data['schedule_end_date']);
					$(ticketGrid).setCell(ticketRowKey, "total_servers_scheduled",      data['total_servers_scheduled']);
					$(ticketGrid).setCell(ticketRowKey, "total_servers_waiting",        data['total_servers_waiting']);
					$(ticketGrid).setCell(ticketRowKey, "total_servers_approved",       data['total_servers_approved']);
					$(ticketGrid).setCell(ticketRowKey, "total_servers_rejected",       data['total_servers_rejected']);
					$(ticketGrid).setCell(ticketRowKey, "total_servers_not_scheduled",  data['total_servers_not_scheduled']);

					// #mainGrid_CCT700000002_subgrid1
					// system_id, subGrid

					var systemGrid    = subGrid;
					var systemRowKey  = system_id;

					//alert('subGrid: ' + subGrid + '  system_id: ' + system_id);

					//var ColumnName = $(systemGrid).jqGrid("getCell", systemRowKey, "system_work_status");
					//alert(ColumnName);
					//$(systemGrid).setCell(systemRowKey, "system_work_status", "CRAPIT");

					$(systemGrid).setCell(systemRowKey, "system_work_start_date",       data['system_work_start_date']);
					$(systemGrid).setCell(systemRowKey, "system_work_end_date",         data['system_work_end_date']);
					$(systemGrid).setCell(systemRowKey, "system_work_duration",         data['system_work_duration']);
					//$(systemGrid).setCell(systemRowKey, "system_work_status",           data['system_work_status']);
					$(systemGrid).setCell(systemRowKey, "total_contacts_responded",     data['total_contacts_responded']);
					$(systemGrid).setCell(systemRowKey, "total_contacts_not_responded", data['total_contacts_not_responded']);

					switch ( data['system_work_status'] )
					{
						case 'DRAFT':    // DRAFT    - Magenta         #FF00FF
							$(systemGrid).setCell(systemRowKey , "system_work_status", "DRAFT",    { color: '#FF00FF'});
							break;
						case 'ACTIVE':   // ACTIVE   - navy            #000080
							$(systemGrid).setCell(systemRowKey , "system_work_status", "ACTIVE",   { color: '#000080'});
							break;
						case 'FROZEN':   // FROZEN   - dark slate gray #2F4F4F
							$(systemGrid).setCell(systemRowKey , "system_work_status", "FROZEN",   { color: '#2F4F4F'});
							break;
						case 'WAITING':  // WAITING  - Teal            #008080
							$(systemGrid).setCell(systemRowKey , "system_work_status", "WAITING",  { color: '#008080'});
							break;
						case 'APPROVED': // APPROVED - dark green      #006400
							$(systemGrid).setCell(systemRowKey , "system_work_status", "APPROVED", { color: '#006400'});
							break;
						case 'REJECTED': // REJECTED - Maroon          #800000
							$(systemGrid).setCell(systemRowKey , "system_work_status", "REJECTED", { color: '#800000'});
							break;
						case 'EXEMPT':   // EXEMPT   - orange red      #FF4500
							$(systemGrid).setCell(systemRowKey , "system_work_status", "EXEMPT",   { color: '#FF4500'});
							break;
						case 'CANCELED': // CANCELED - Indian Red      #CD5C5C
							$(systemGrid).setCell(systemRowKey , "system_work_status", "CANCELED", { color: '#CD5C5C'});
							break;
						case 'CLOSED':   // CLOSED   - black           #000000
							$(systemGrid).setCell(systemRowKey , "system_work_status", "CLOSED",   { color: '#000000'});
							break;
						default:
							$(systemGrid).setCell(systemRowKey, "system_work_status", data['system_work_status']);
							break;
					}
				}
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
		});

		//alert('got through ajax call.');
	}

	function grid_info()
	{
		var jqGrids = $('table.ui-jqgrid-btable');

		if (jqGrids.length > 0)
		{
			jqGrids.each(
				function(i)
				{
					var output = '';

					if (this.grid)
					{
						//alert('grid: ' + jqGrids[i].id);
						output += 'Grid: ' + jqGrids[i].id;
						//var output = '';
						//for (var property in jqGrids[i])
						//{
						//    output += property + ': ' + jqGrids[i][property] + '; ';
						//}
						//alert(output);
					}

					if (this.p.toppager)
					{
						// alert('toppager: ' + this.id + '_toppager');
						output += ', toppager: ' + this.id + '_toppager';
					}

					if (this.p.pager)
					{
						// alert('pager: ' + this.p.pager);
						output += ', pager: ' + this.p.pager;
					}

					alert(output);
				}
			);
		}
	};

	$(document).ready(function ()
	{
		//$( "#doit" ).on( "click", function() {
		//	alert( $( this ).text() );
		//});

		//alert('<?php echo $what_tickets; ?>');

		// Table: cct7_tickets
		// ===========================================
		// cm_ticket_no            = Remedy CM #
		// ticket_no               = Record ID (hidden)
		// ticket_no               = CCT Ticket #
		// work_activity           = Work Activity
		// status                  = CCT Status
		// insert_date             = Create Date
		// insert_name             = Ticket Owner
		// remedy_cm_start_date    = Schedule From
		// remedy_cm_end_date      = Schedule To
		// approvals_required      = FYI Only
		// reboot_required         = Reboots
		// respond_by_date         = Respond By
		// total_servers_scheduled = TOTAL
		// total_servers_waiting   = WAITING
		// total_servers_ready     = READY
		// total_servers_rejected  = REJECTED

		var mainGridID      = 'mainGrid';
		var mainGridPagerID = 'mainGridPager';
		var mainGrid        = '#' + mainGridID;
		var mainGridPager   = '#' + mainGridPagerID;

		var tickets_caption = '<?php echo $caption_tickets; ?>';

		//var where_clause = "where t.ticket_no = 'CCT700000002'";

		// what_tickets = 'group' or 'CCT7xxxxxxxx' or 'approve'

        var sortname = 'ticket_no,cm_ticket_no,work_activity,status,insert_date,owner_name,schedule_start_date,' +
            'schedule_end_date,approvals_required,reboot_required,respond_by_date';

		$(mainGrid).jqGrid({
			url:      'ajax_jqgrid_toolbar_open_tickets.php',
			mtype:    "GET",
			datatype: "json",
            ajaxGridOptions: { contentType: 'application/json; charset=utf-8', cache: false },
			postData:
			{
				action:         'get',
				where_clause:   '',
				order_by:       't.ticket_no',
				direction:      'asc',
				what_tickets:   '<?php echo $what_tickets; ?>',
				what_hostname:  '<?php echo $what_hostname; ?>',
				what_netpin_no: '<?php echo $what_netpin_no; ?>',
				what_cuid:      '<?php echo $what_cuid; ?>',
                approve_filter: approve_filter
			},
			colModel: [
				{
					label:          'Remedy CM #',
					name:           'cm_ticket_no',
                    index:          'cm_ticket_no',
                    sortable:       true,
                    sorttype:       'string',
                    firstsortorder: 'desc',
					align:          'left',
					width:          120,
					hidden:         false,
                    search:         true,
                    searchoptions:  { sopt: ['eq','bw','bn','cn','nc','ew','en'] }
				},
				{
					key:           true,
					label:         'CCT Ticket #',
					name:          'ticket_no',
					align:         'left',
					width:         120,
                    sortable:       true,
                    sorttype:       'string',
					search:        true,
					searchoptions: { sopt: ['eq','bw','bn','cn','nc','ew','en'] }
				},
				{
					label:         'Work Activity',
					name:          'work_activity',
					width:         120,
					align:         'left',
					search:        true,
                    searchoptions: { sopt: ['eq','bw','bn','cn','nc','ew','en'] }
				},
				{
					label:         'Status',
					name:          'status',
					width:         100,
					align:         'left',
                    sortable:      true,
                    sorttype:      'string',
					search:        true,
					stype:         "select",
					searchoptions:
					{
						value:     "DRAFT:DRAFT;ACTIVE:ACTIVE;CANCELED:CANCELED;DELETED:DELETED;FROZEN:FROZEN",
						defaultValue: "ACTIVE"
					}
				},
				{
					label:         'Create Date',
					name:          'insert_date',
					width:         150,
					align:         'left',
                    sortable:      true,
                    sorttype:      'string',
					search:        true,
					searchoptions:
					{
						sopt: ['eq','ne','lt','le','gt','ge'],
						dataInit: function (elem)
						{
							$(elem).datetimepicker(
								{
									changeMonth: true,
									changeYear: true,
									showSecond:true,
									dateFormat:'mm/dd/yy',
									timeFormat:'hh:mm',
									hourGrid: 4,
									minuteGrid: 10,
									secondGrid: 10,
									addSliderAccess: true,
									sliderAccessArgs: { touchonly: false }
								}
							);
						}
					}
				},
				{
					label:         'Owner CUID',
					name:          'owner_cuid',
					align:         'left',
					hidden:        true,
                    sortable:      true,
                    sorttype:      'string',
					search:        true,
					stype:         "select",
					searchoptions:
					{
						value:     "ALL:ALL;<?php printf("%s;%s", $_SESSION['user_cuid'], $_SESSION['user_cuid']); ?>",
						defaultValue: "<?php printf("%s", $_SESSION['user_cuid']); ?>"
					}
				},
				{
					label:         'Ticket Owner',
					name:          'owner_name',
					width:         570,
					align:         'left',
                    sortable:      true,
                    sorttype:      'string',
					search:        true,
                    searchoptions: { sopt: ['eq','bw','bn','cn','nc','ew','en'] }
				},
				{
					label:         'Schedule From',
					name:          'schedule_start_date',
					width:         150,
					align:         'left',
                    sortable:      true,
                    sorttype:      'string',
                    hidden:        true,
					search:        true,
					searchoptions:
					{
						sopt: ['eq','ne','lt','le','gt','ge'],
						dataInit: function (elem) {
							$(elem).datepicker(
								{
									dateFormat: 'mm/dd/yy',
									changeYear: true,
									changeMonth: true,
									showWeek: true
								}
							);
						}
					}
				},
				{
					label:         'Schedule To',
					name:          'schedule_end_date',
					width:         150,
					align:         'left',
                    sortable:      true,
                    sorttype:      'string',
                    hidden:        true,
					search:        true,
					searchoptions:
					{
						sopt: ['eq','ne','lt','le','gt','ge'],
						dataInit: function (elem) {
							$(elem).datepicker(
								{
									dateFormat: 'mm/dd/yy',
									changeYear: true,
									changeMonth: true,
									showWeek: true
								}
							);
						}
					}
				},
				{
					label:         'Approve',
					name:          'approvals_required',
					width:         70,
					align:         'center',
                    sortable:      true,
                    sorttype:      'string',
					search:        true,
					stype:         "select",
					searchoptions:
					{
						value:     "All:All;Yes:Yes;No:No",
						defaultValue: "All"
					}
				},
				{
					label:         'Reboots',
					name:          'reboot_required',
					width:         70,
					align:         'center',
                    sortable:      true,
                    sorttype:      'string',
					search:        true,
					stype:         "select",
					searchoptions:
					{
						value:     "All:All;Yes:Yes;No:No",
						defaultValue: "All"
					}
				},
				{
					label:         'Respond',
					name:          'respond_by_date',
					width:         120,
					align:         'left',
                    sortable:      true,
                    sorttype:      'string',
					search:        true,
					searchoptions:
					{
						sopt: ['eq','ne','lt','le','gt','ge'],
						dataInit: function (elem) {
							$(elem).datepicker(
								{
									dateFormat: 'mm/dd/yy',
									changeYear: true,
									changeMonth: true,
									showWeek: true
								}
							);
						}
					}
				},
				{
					label:         'TOTAL',
					name:          'total_servers_scheduled',
					align:         'center',
					width:         70,
					search:        true,
					searchoptions: { sopt: ['eq','ne','lt','le','gt','ge'] }
				},
				{
					label:         'WAITING',
					name:          'total_servers_waiting',
					align:         'center',
					width:         70,
					search:        true,
					searchoptions: { sopt: ['eq','ne','lt','le','gt','ge'] }
				},
				{
					label:         'READY',
					name:          'total_servers_approved',
					align:         'center',
					width:         70,
					search:        true,
					searchoptions: { sopt: ['eq','ne','lt','le','gt','ge'] }
				},
				{
					label:         'REJECTED',
					name:          'total_servers_rejected',
					align:         'center',
					width:         85,
					search:        true,
					searchoptions: { sopt: ['eq','ne','lt','le','gt','ge'] }
				}
			],
            multiSort:          true,
			caption:            tickets_caption,
			width:              '100%',
			height:             '100%',
			rowNum:             25,
			viewrecords:        true,
			altRows:            true,
			viewsortcols:       true,
			loadtext:           'Loading Tickets',
			imgpath:            'images',
			subGrid:            true,
			subGridRowExpanded: subgrid1,
			pager: mainGridPager,
			onSelectRow: function(ticket_no, selected)
			{
				if (ticket_no.length == 0)
				{
					alert('No CCT Ticket number specified!');
					return;
				}

				last_ticket_no_selected = ticket_no;

				openTicketDialog(ticket_no, ticket_no);

				//alert('Row selected: ' + ticket_no);

				// Retrieve the following from the grid: ticket_status

				//openTicketDialog("Remedy Ticket as stored in CCT database.", cm_ticket_no);

				/*
				 var url = 'view_cct7_ticket.php?ticket=' + cm_ticket_no;

				 var height = 640;  // 605
				 var width  = 970;  // 910
				 var left = (screen.width/2) - (width/2);
				 var top  = (screen.heigth/2) - (height/2);
				 var options = 'resizable=yes,directories=no,titlebar=no,toolbar=no,location=no,status=no,menubar=no,scrollbars=no, ' +
				 'height=' + height + ',width=' + width + ',top=' + top + ',left=' + left;

				 var newwindow = window.open(url, 'newwindow', options).focus();
				 newwindow.moveTo(0, 0);
				 */
			},
			loadComplete: function()
			{
				var ids = $(this).jqGrid("getDataIDs"), l = ids.length, i, rowid, status;

				for (i = 0; i < l; i++)
				{
					rowid = ids[i];

					//
					// This is column index value used to update the cell for status.
					// If you change the columns in the ticketGrids you need adjust this value.
					//
					colid = 3;

					//
					// get data from some column "ColumnName"
					//
					var ColumnName = $(this).jqGrid("getCell", rowid, "status");

					switch ( ColumnName )
					{
						case 'DRAFT':    // DRAFT    - Magenta         #FF00FF
							$(this).setCell(rowid , colid, "DRAFT",    { color: '#FF00FF'});
							break;
						case 'ACTIVE':   // ACTIVE   - navy            #000080
							$(this).setCell(rowid , colid, "ACTIVE",   { color: '#000080'});
							break;
						case 'FROZEN':   // FROZEN   - dark slate gray #2F4F4F
							$(this).setCell(rowid , colid, "FROZEN",   { color: '#2F4F4F'});
							break;
						case 'WAITING':  // WAITING  - Teal            #008080
							$(this).setCell(rowid , colid, "WAITING",  { color: '#008080'});
							break;
						case 'APPROVED': // APPROVED - dark green      #006400
							$(this).setCell(rowid , colid, "APPROVED", { color: '#006400'});
							break;
						case 'REJECTED': // REJECTED - Maroon          #800000
							$(this).setCell(rowid , colid, "REJECTED", { color: '#800000'});
							break;
						case 'EXEMPT':   // EXEMPT   - orange red      #FF4500
							$(this).setCell(rowid , colid, "EXEMPT",   { color: '#FF4500'});
							break;
						case 'CANCELED': // CANCELED - Indian Red      #CD5C5C
							$(this).setCell(rowid , colid, "CANCELED", { color: '#CD5C5C'});
							break;
						case 'CLOSED':   // CLOSED   - black           #000000
							$(this).setCell(rowid , colid, "CLOSED",   { color: '#000000'});
							break;
						default:
							break;
					}
				}
			}
		});

        jQuery(mainGrid).setColProp('ticket_no', {sortable: false});
        //sortable:      true,
        //sorttype:      'string',

        //jQuery(mainGrid).jqGrid('filterToolbar',{searchOperators : true});

		//$("#grid").jqGrid('addRowData', i + 1, mydata[i]);

		// ===========================================
		// cm_ticket_no            = Remedy CM #
		// ticket_no               = CCT Ticket #
		// work_activity           = Work Activity
		// status                  = CCT Status
		// insert_date             = Create Date
		// insert_name             = Ticket Owner
		// remedy_cm_start_date    = Schedule From
		// remedy_cm_end_date      = Schedule To
		// approvals_required      = FYI Only
		// reboot_required         = Reboots
		// respond_by_date         = Respond By
		// total_servers_scheduled = TOTAL
		// total_servers_waiting   = WAITING
		// total_servers_ready     = READY
		// total_servers_rejected  = REJECTED
		//
		jQuery(mainGrid).jqGrid('setLabel', 'cm_ticket_no',            '', {'text-align': 'left'},   {title: 'Optional Remedy CM Ticket number.'});
		jQuery(mainGrid).jqGrid('setLabel', 'ticket_no',               '', {'text-align': 'left'},   {title: 'Unique CCT Ticket number.'});
		jQuery(mainGrid).jqGrid('setLabel', 'work_activity',           '', {'text-align': 'left'},   {title: 'Work Activity or work classification type.'});
		jQuery(mainGrid).jqGrid('setLabel', 'status',                  '', {'text-align': 'left'},   {title: 'CCT Ticket status: [ACTIVE, APPROVED, CANCELED, FROZEN, DRAFT, CLOSED]'});
		jQuery(mainGrid).jqGrid('setLabel', 'insert_date',             '', {'text-align': 'left'},   {title: 'Date and time the work request was created.'});
		jQuery(mainGrid).jqGrid('setLabel', 'owner_date',              '', {'text-align': 'left'},   {title: 'The date and time this CCT ticket was created.'});
		jQuery(mainGrid).jqGrid('setLabel', 'owner_name',              '', {'text-align': 'left'},   {title: 'The name of the person who created this CCT ticket.'});
		jQuery(mainGrid).jqGrid('setLabel', 'schedule_start_date',     '', {'text-align': 'left'},   {title: 'Scheduled starting date when all server work begins.'});
		jQuery(mainGrid).jqGrid('setLabel', 'schedule_end_date',       '', {'text-align': 'left'},   {title: 'Scheduled ending date when all server work ends.'});
		jQuery(mainGrid).jqGrid('setLabel', 'approvals_required',      '', {'text-align': 'left'},   {title: 'Are approvals required? Y/N'});
		jQuery(mainGrid).jqGrid('setLabel', 'reboot_required',         '', {'text-align': 'center'}, {title: 'Will the servers need to be rebooted? Y/N'});
		jQuery(mainGrid).jqGrid('setLabel', 'respond_by_date',         '', {'text-align': 'left'},   {title: 'Clients need to respond by end of this date.'});
		jQuery(mainGrid).jqGrid('setLabel', 'total_servers_scheduled', '', {'text-align': 'center'}, {title: 'Total number of servers scheduled in this CCT ticket.'});
		jQuery(mainGrid).jqGrid('setLabel', 'total_servers_waiting',   '', {'text-align': 'center'}, {title: 'Number of servers still pending approval from clients.'});
		jQuery(mainGrid).jqGrid('setLabel', 'total_servers_ready',     '', {'text-align': 'center'}, {title: 'Number of servers that have received approval from all clients.'});
		jQuery(mainGrid).jqGrid('setLabel', 'total_servers_rejected',  '', {'text-align': 'center'}, {title: 'Number of servers where one or more clients have rejected the work.'});

		// Operation Codes ("op":)
		// eq = equal
		// ne = not equal
		// lt = less
		// le = less or equal
		// gt = greater
		// ge = greater or equal
		// bw = begins with
		// bn = does not begin with
		// in = is in
		// ni = is not in
		// ew = ends with
		// en = does not end with
		// cn = contains
		// nc = does not contain

		//
		// Search by Remedy CM No.
		//
		var search_template1 =
			{
				"groupOp":   "AND",
				"rules":     [
					{ "field": "cm_ticket_no", "op": "ew", "data": "" }
				]
			};

		//
		// Search by CCT Ticket No.
		//
		var search_template2 =
			{
				"groupOp":   "AND",
				"rules":     [
					{ "field": "ticket_no", "op": "eq", "data": "" }
				]
			};

		//
		// Search by: Work Activity
		//
		var search_template3 =
			{
				"groupOp":   "AND",
				"rules":     [
					{ "field": "work_activity", "op": "eq", "data": "" }
				]
			};

		//
		// Search by Owner
		//
		var search_template4 =
			{
				"groupOp":   "AND",
				"rules":     [
					{ "field": "owner_cuid",   "op": "eq", "data": "" },
					{ "field": "manager_cuid", "op": "eq", "data": "" }
				]
			};

		//
		// Search by: Owner CUID
		//
		var search_template5 =
			{
				"groupOp":   "AND",
				"rules":     [
					{ "field": "owner_cuid", "op": "eq", "data": "<?php printf("%s", $_SESSION['user_cuid']); ?>" }
				]
			};

		jQuery(mainGrid).jqGrid('navGrid', mainGridPager,
			{ add: false, edit: false, del: false, refresh: false },  // options
			{}, // edit options
			{}, // add options
			{}, // del options
			{   // Search
				closeOnEscape:  true,
				multipleSearch: true,
				multipleGroup:  true,
				tmpLabel:       'Search by template:&nbsp;',
				tmplNames:      [ "Remedy CM No.", "CCT Ticket No.", "Work Activity", "Ticket Owner", "Owner CUID" ],
				tmplFilters:    [ search_template1, search_template2, search_template3, search_template4, search_template5 ]
			}
		).navButtonAdd(mainGridPager,
			{
				caption:     '',
				title:       "Excel",
				buttonicon : 'ui-icon-suitcase',
				onClickButton:function()
				{
                    //
                    // Prepare JSON data containing the ticket_no and system_id we want update.
                    //
                    var data = {
                        'action':       'excel',
                        'where_clause': '',
                        'order_by':     't.ticket_no',
                        'direction':    'asc',
                        what_tickets:   '<?php echo $what_tickets; ?>',
                        what_hostname:  '<?php echo $what_hostname; ?>',
                        what_netpin_no: '<?php echo $what_netpin_no; ?>',
                        what_cuid:      '<?php echo $what_cuid; ?>'
                    };

                    var url = 'ajax_jqgrid_toolbar_open_tickets.php';

                    $.ajax({
                        type: 'GET',
                        url:   url,
                        async: false,
                        data:  data,
                        beforeSend: function (xhr)
                        {
                            if (xhr && xhr.overrideMimeType)
                            {
                                xhr.overrideMimeType('application/json;charset=utf-8');
                            }
                        },
                        dataType: 'json',
                        success: function (data)
                        {
                            JSONToCSVConvertor(data['rows'], "CCT Tickets", true);
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
                    });
				}
			}
		).navButtonAdd(mainGridPager,
			{
				caption:     '',
				title:       "Refresh the Grid",
				buttonicon : 'ui-icon-refresh',
				onClickButton:function()
				{
					$(mainGrid).trigger("reloadGrid",[{current:true}]);
				}
			}
        ).navButtonAdd(mainGridPager,
            {
                caption:     '',
                title:       "Sort Order",
                buttonicon : 'ui-icon-star',
                onClickButton: function()
                {
                    openTicketSortList();
                }
            }
        );

		// End of stuff for mainGrid

        function JSONToCSVConvertor(JSONData, ReportTitle, ShowLabel)
        {
            // If JSONData is not an object then JSON.parse will parse the JSON string in an Object
            var arrData = typeof JSONData != 'object' ? JSON.parse(JSONData) : JSONData;

            var CSV = '';
            // Set Report title in first row or line

            CSV += ReportTitle + '\r\n\n';

            // This condition will generate the Label/Header
            if (ShowLabel)
            {
                var row = "";

                // This loop will extract the label from 1st index of on array
                for (var index in arrData[0])
                {

                    // Now convert each value to string and comma-seprated
                    row += index + ',';
                }

                row = row.slice(0, -1);

                // Append Label row with line break
                CSV += row + '\r\n';
            }

            // 1st loop is to extract each row
            for (var i = 0; i < arrData.length; i++)
            {
                var row = "";

                // 2nd loop will extract each column and convert it in string comma-seprated
                for (var index in arrData[i])
                {
                    row += '"' + arrData[i][index] + '",';
                }

                row.slice(0, row.length - 1);

                //add a line break after each row
                CSV += row + '\r\n';
            }

            if (CSV == '')
            {
                alert("Invalid data");
                return;
            }

            // Generate a file name
            var fileName = "MyReport_";
            // This will remove the blank-spaces from the title and replace it with an underscore
            fileName += ReportTitle.replace(/ /g,"_");

            // Initialize file format you want csv or xls
            var uri = 'data:text/csv;charset=utf-8,' + escape(CSV);

            // Now the little tricky part.
            // you can use either>> window.open(uri);
            // but this will not work in some browsers
            // or you will not get the correct file extension

            // This trick will generate a temp <a /> tag
            var link = document.createElement("a");
            link.href = uri;

            // Set the visibility hidden so it will not effect on your web-layout
            link.style = "visibility:hidden";
            link.download = fileName + ".csv";

            // This part will append the anchor tag and remove it after automatic click
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }

		//
		// Subgrid: Systems
		//
		function subgrid1(parentRowID, parentRowKey)
		{
			var subGridID1      = parentRowID + "_subgrid1";
			var subGridPagerID1 = parentRowID + "_subgrid1_pager";
			var subGridURL1     = 'ajax_jqgrid_toolbar_open_systems.php?ticket_no=' + parentRowKey;

			subGrid1      = "#" + subGridID1;
			subGridPager1 = "#" + subGridPagerID1;

			ticket_no     = parentRowKey;
			status        = $(mainGrid).jqGrid("getCell", parentRowKey, "status");
			work_activity = $(mainGrid).jqGrid("getCell", parentRowKey, "work_activity");

			var dialog_system_title = ticket_no + ' - ' + status + ' - ' + work_activity;

			$('#' + parentRowID).append('<table id=' + subGridID1 + '></table><div id=' + subGridPagerID1 + ' class=scroll></div>');

			$(subGrid1).jqGrid({
				url:      subGridURL1,
				mtype:    "GET",
				datatype: "json",
                ajaxGridOptions: { contentType: 'application/json; charset=utf-8', cache: false },
				postData:
				{
					action:         '',
					where_clause:   '',
					order_by:       '',
					direction:      'asc',
					what_tickets:   '<?php echo $what_tickets; ?>', // relevant if 'approve'
					what_hostname:  '<?php echo $what_hostname; ?>',
					what_netpin_no: '<?php echo $what_netpin_no; ?>',
                    approve_filter: approve_filter
				},
				colModel: [
					{
						label: 'Ticket Record ID',
						name:  'ticket_no',
						align: 'left',
						width:  120,
						hidden: true,
						search:        true
					},
					{
						key:   true,
						label: 'System Record ID',
						name:  'system_id',
						align: 'left',
						width:  120,
						hidden: true,
						search:        true
					},
					{
						label: 'Server',
						name:  'system_hostname',
						align: 'left',
						width: 120,
						search:        true
					},
					{
						label: 'OS',
						name:  'system_os',
						align: 'left',
						width: 100,
						search:        true
					},
					{
						label: 'Usage',
						name:  'system_usage',
						align: 'left',
						width: 120,
						search:        true
					},
					{
						label: 'Location',
						name: 'system_location',
						align: 'left',
						width: 130,
						search:        true
					},
					{
						label: 'Time Zone',
						name:  'system_timezone_name',
						align: 'left',
						width: 120,
                        hidden: true,
						search:        true
					},
					{
						label: 'Work Status',
						name:  'system_work_status',
						width:  100,
						align: 'left',
						editable: true,
						search:        true
					},
					{
						label: 'Responded',
						name:  'total_contacts_responded',
						align: 'center',
						width:  90,
						search:        true
					},
					{
						label: 'Waiting',
						name: 'total_contacts_not_responded',
						align: 'center',
						width: 90,
						search:        true
					},
					{
						label: 'Respond By',
						name: 'system_respond_by_date',
						align: 'left',
						width: 130,
						search: true,
						searchoptions:
						{
							sopt: ['eq','ne','lt','le','gt','ge'],
							dataInit: function (elem) {
								$(elem).datepicker(
									{
										dateFormat: 'mm/dd/yy',
										changeYear: true,
										changeMonth: true,
										showWeek: true
									}
								);
							}
						}
					},
					{
						label: 'Work Start',
						name: 'system_work_start_date',
						align: 'left',
						width: 150,
						search:        true,
						searchoptions:
						{
							sopt: ['eq','ne','lt','le','gt','ge'],
							dataInit: function (elem)
							{
								$(elem).datetimepicker(
									{
										changeMonth: true,
										changeYear: true,
										showSecond:true,
										dateFormat:'mm/dd/yy',
										timeFormat:'hh:mm',
										hourGrid: 4,
										minuteGrid: 10,
										secondGrid: 10,
										addSliderAccess: true,
										sliderAccessArgs: { touchonly: false }
									}
								);
							}
						}
					},
					{
						label: 'Work End',
						name: 'system_work_end_date',
						align: 'left',
						width: 150,
						search:        true,
						searchoptions:
						{
							sopt: ['eq','ne','lt','le','gt','ge'],
							dataInit: function (elem)
							{
								$(elem).datetimepicker(
									{
										changeMonth: true,
										changeYear: true,
										showSecond:true,
										dateFormat:'mm/dd/yy',
										timeFormat:'hh:mm',
										hourGrid: 4,
										minuteGrid: 10,
										secondGrid: 10,
										addSliderAccess: true,
										sliderAccessArgs: { touchonly: false }
									}
								);
							}
						}
					},
					{
						label: 'Duration',
						name: 'system_work_duration',
						align: 'center',
						width: 95,
						search:        true
					},
					{
						label: 'Maintenance Window',
						name: 'system_osmaint_weekly',
						align: 'left',
						width: 350,
						search:        true
					}
				],
				width:  '100%',
				height: '100%',
				caption: 'Servers',
				rowNum:             15,
				viewrecords:        true,
				altRows:            true,
				viewsortcols:       true,
				loadtext:           'Loading Systems',
				subGrid:            true,
				subGridRowExpanded: subgrid2,
				pager: subGridPager1,
				onSelectRow: function (system_id, selected)
				{
					//alert(' systemGrid event triggered: system_id: ' + system_id);
					// system_id         = $(this).jqGrid("getCell", key, "system_id");
					openServerDialog(dialog_system_title, ticket_no, system_id, subGrid1);
				},
				loadComplete: function()
				{
					var ids = $(this).jqGrid("getDataIDs"), l = ids.length, i, rowid, status;

					for (i = 0; i < l; i++)
					{
						rowid = ids[i];
						colid = 8;

						//
						// get data from some column "ColumnName"
						//
						var ColumnName = $(this).jqGrid("getCell", rowid, "system_work_status");
						//alert(this.id + ' - ' + rowid);

						switch ( ColumnName )
						{
							case 'DRAFT':    // DRAFT    - Magenta         #FF00FF
								$(this).setCell(rowid , colid, "DRAFT",    { color: '#FF00FF'});
								break;
							case 'ACTIVE':   // ACTIVE   - navy            #000080
								$(this).setCell(rowid , colid, "ACTIVE",   { color: '#000080'});
								break;
							case 'FROZEN':   // FROZEN   - dark slate gray #2F4F4F
								$(this).setCell(rowid , colid, "FROZEN",   { color: '#2F4F4F'});
								break;
							case 'WAITING':  // WAITING  - Teal            #008080
								$(this).setCell(rowid , colid, "WAITING",  { color: '#008080'});
								break;
							case 'APPROVED': // APPROVED - dark green      #006400
								$(this).setCell(rowid , colid, "APPROVED", { color: '#006400'});
								break;
							case 'REJECTED': // REJECTED - Maroon          #800000
								$(this).setCell(rowid , colid, "REJECTED", { color: '#800000'});
								break;
							case 'EXEMPT':   // EXEMPT   - orange red      #FF4500
								$(this).setCell(rowid , colid, "EXEMPT",   { color: '#FF4500'});
								break;
							case 'CANCELED': // CANCELED - Indian Red      #CD5C5C
								$(this).setCell(rowid , colid, "CANCELED", { color: '#CD5C5C'});
								break;
							case 'CLOSED':   // CLOSED   - black           #000000
								$(this).setCell(rowid , colid, "CLOSED",   { color: '#000000'});
								break;
							default:
								break;
						}
					}
				}
			});

			// ===========================================
			// ticket_no                      = (hidden)
			// system_id                      = (hidden)
			// system_hostname                = Server
			// system_os                      = OS
			// system_usage                   = Usage
			// system_location                = Location
			// system_timezone_name           = Time Zone
			// system_work_status             = Work Status
			// total_contacts_responded       = Approved
			// total_contacts_not_responded   = Not Approved
			// system_respond_by_date         = Respond By
			// system_work_start_date         = Work Start
			// system_work_end_date           = Work End
			// system_work_duration           = Work Duration
			// system_osmaint_weekly          = Maintenance Window
			//
			jQuery(subGrid1).jqGrid('setLabel', 'ticket_no',                    '', {'text-align': 'left'},   {title: 'Ticket Record ID. (Should be hidden)'});
			jQuery(subGrid1).jqGrid('setLabel', 'system_id',                    '', {'text-align': 'left'},   {title: 'System Record ID. (Should be hidden)'});
			jQuery(subGrid1).jqGrid('setLabel', 'system_hostname',              '', {'text-align': 'left'},   {title: 'Server or hostname work is being done on.'});
			jQuery(subGrid1).jqGrid('setLabel', 'system_os',                    '', {'text-align': 'left'},   {title: 'Server operating system name.'});
			jQuery(subGrid1).jqGrid('setLabel', 'system_usage',                 '', {'text-align': 'left'},   {title: 'Server usage type: [PRODUCTION, DEVELOPMENT, TEST, ...].'});
			jQuery(subGrid1).jqGrid('setLabel', 'system_location',              '', {'text-align': 'left'},   {title: 'Server location: City and State. (i.e. OMAHA, NE)'});
			jQuery(subGrid1).jqGrid('setLabel', 'system_timezone_name',         '', {'text-align': 'left'},   {title: 'Server time zone. (i.e. America/Chicago)'});
			jQuery(subGrid1).jqGrid('setLabel', 'system_work_status',           '', {'text-align': 'left'},   {title: 'Server approval status: [WAITING, APPROVED, REJECTED, CANCELED]'});
			jQuery(subGrid1).jqGrid('setLabel', 'total_contacts_responded',     '', {'text-align': 'center'}, {title: 'Number of contacts who have responded.'});
			jQuery(subGrid1).jqGrid('setLabel', 'total_contacts_not_responded', '', {'text-align': 'center'}, {title: 'Number of contacts who have not responded.'});
			jQuery(subGrid1).jqGrid('setLabel', 'system_respond_by_date',       '', {'text-align': 'left'},   {title: 'Clients need to respond by the end of this day.'});
			jQuery(subGrid1).jqGrid('setLabel', 'system_work_start_date',       '', {'text-align': 'left'},   {title: 'Scheduled Work Start. (Displayed in your time zone.)'});
			jQuery(subGrid1).jqGrid('setLabel', 'system_work_end_date',         '', {'text-align': 'left'},   {title: 'Scheduled Work End. (Displayed in your time zone.)'});
			jQuery(subGrid1).jqGrid('setLabel', 'system_work_duration',         '', {'text-align': 'center'}, {title: 'Length of the work activity. ( days : hours : minutes )'});
			jQuery(subGrid1).jqGrid('setLabel', 'system_osmaint_weekly',        '', {'text-align': 'left'},   {title: 'Server Weekly OS Maintenance Window used to schedule work activity.'});

			jQuery(subGrid1).jqGrid('navGrid', subGridPager1,
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
			).navButtonAdd(subGridPager1,
                {
                    caption:     '',
                    title:       "Excel",
                    buttonicon : 'ui-icon-suitcase',
                    onClickButton:function()
                    {
                        //
                        // Prepare JSON data containing the ticket_no and system_id we want update.
                        //
                        var data = {
                            action:         'excel',
                            ticket_no:      ticket_no,
                            where_clause:   '',
                            order_by:       '',
                            direction:      'asc',
                            what_tickets:   '<?php echo $what_tickets; ?>', // relevant if 'approve'
                            what_hostname:  '<?php echo $what_hostname; ?>',
                            what_netpin_no: '<?php echo $what_netpin_no; ?>'
                        };

                        var url = 'ajax_jqgrid_toolbar_open_systems.php';

                        $.ajax({
                            type: 'GET',
                            url:   url,
                            async: false,
                            data:  data,
                            beforeSend: function (xhr)
                            {
                                if (xhr && xhr.overrideMimeType)
                                {
                                    xhr.overrideMimeType('application/json;charset=utf-8');
                                }
                            },
                            dataType: 'json',
                            success: function (data)
                            {
                                JSONToCSVConvertor(data['rows'], "CCT Systems", true);
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
                        });

                    }
                }
			).navButtonAdd(subGridPager1,
				{
					caption:     '',
					title:       "Refresh the Grid",
					buttonicon : 'ui-icon-refresh',
					onClickButton:function()
					{
						$(subGrid1).trigger("reloadGrid",[{current:true}]);
					}
				}
            ).navButtonAdd(subGridPager1,
                {
                    caption:     '',
                    title:       'Add new server',
                    buttonicon:  'ui-icon-plusthick',
                    onClickButton: function()
                    {
                        var part1 = '<br><input title="Type in the hostname in lowercase please."type=text id=hostname name=hostname size=20 maxlength=20>';
                        var part2 = '<script>document.getElementById("hostname").focus();<\/script>';
                        var button1 = '<input title="Click to add this new server." type="button" value="Okay" onclick="addServer();w2popup.close();">';
                        var button2 = '<input title="Cancel operation." type="button" value="Cancel" onclick="w2popup.close();">';

                        w2popup.open({
                            title     : 'Enter the server hostname',
                            body      : part1 + part2,
                            buttons   : button1 + button2,
                            width     : 300,
                            height    : 135,
                            overflow  : 'hidden',
                            color     : '#333',
                            speed     : '0.1',
                            opacity   : '0.8',
                            modal     : true,
                            showClose : true,
                            showMax   : true,
                            onOpen    : function (event)
                            {
                            },
                            onClose   : function (event)
                            {
                                //alert('subgrid1: ' + subGrid1);
                            }
                        });
                    }
                }
				<?php
				if ($what_tickets == "approve")
				{
				?>
			).navButtonAdd(subGridPager1,
				{
					caption:     '',
					title:       "Approve all server work for my team in this ticket.",
					buttonicon : 'ui-icon-star',
					onClickButton:function()
					{
						var arr = subGridPager1.split('_');
						var ticket_no = arr[1];

						if (!confirm('Are you sure you want to approve all for ' + ticket_no + ' for eveyone in your group?'))
						{
							return;
						}

						var data = {
							'ticket_no': ticket_no
						};

						var url = 'ajax_jqgrid_toolbar_open_approve_all.php';

						$.ajax({
							type: 'POST',
							url:   url,
							async: false,
							dataType: "json",
							data:     JSON.stringify(data),
							beforeSend: function (xhr) {
								if (xhr && xhr.overrideMimeType)
								{
									xhr.overrideMimeType('application/json;charset=utf-8');
								}
							},
							success: function (data)
							{
								alert(data['ajax_message']);
								$(subGrid1).trigger("reloadGrid",[{current:true}]);
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
						})
					}
				}
				<?php
				}  // END: if ($what_tickets == "approve")
				?>
			);
		}

		//
		// Subgrid2: Contacts
		//
		function subgrid2(parentRowID, parentRowKey)
		{
			var subGridID2      = parentRowID + "_subgrid2";
			var subGridPagerID2 = parentRowID + "_subgrid2_pager";
			var subGridURL2     = 'ajax_jqgrid_toolbar_open_contacts.php?system_id=' + parentRowKey;

			//
			// Extract the parent grid information for this subgrid so we can build the grid caption string.
			//
			x = 'A' + parentRowKey;  // Have to prefix 'A' so it will make a string.
			//alert('length: ' + x.length);
			//alert(parentRowID.slice(0, -Math.abs(x.length)));
			parentGrid = '#' + parentRowID.slice(0, -Math.abs(x.length));

			//
			// Retrieve server information for this contact record
			//
			// ticket_no                      = (hidden)
			// system_id                      = (hidden)
			// system_hostname                = Server
			// system_os                      = OS
			// system_usage                   = Usage
			// system_location                = Location
			// system_timezone_name           = Time Zone
			// system_work_status             = Work Status
			// total_contacts_responded       = Approved
			// total_contacts_not_responded   = Not Approved
			// system_respond_by_date         = Respond By
			// system_work_start_date         = Work Start
			// system_work_end_date           = Work End
			// system_work_duration           = Work Duration
			// system_osmaint_weekly          = Maintenance Window
			//
			ticket_no          = $(parentGrid).jqGrid("getCell", parentRowKey, "ticket_no");
			system_hostname    = $(parentGrid).jqGrid("getCell", parentRowKey, "system_hostname");
			system_work_status = $(parentGrid).jqGrid("getCell", parentRowKey, "system_work_status");

			var dialog_contact_title = ticket_no + ' - ' + system_hostname + ' - ' + system_work_status;

			subGrid2      = "#" + subGridID2;
			subGridPager2 = "#" + subGridPagerID2;

			$('#' + parentRowID).append('<table id=' + subGridID2 + '></table><div id=' + subGridPagerID2 + ' class=scroll></div>');

			$(subGrid2).jqGrid({
				url:      subGridURL2,
				mtype:    "GET",
				datatype: "json",
                ajaxGridOptions: { contentType: 'application/json; charset=utf-8', cache: false },
				postData:
				{
					action:         '',
					where_clause:   '',
					order_by:       '',
					direction:      'asc',
					what_tickets:   '<?php echo $what_tickets; ?>',  // relevant if 'approve'
					what_netpin_no: '<?php echo $what_netpin_no; ?>',
                    approve_filter: approve_filter
				},
				colModel: [
					{
						label:  'Ticket No',
						name:   'ticket_no',
						align:  'left',
						width:  50,
						hidden: true
					},
					{
						label:  'System Record ID',
						name:   'system_id',
						align:  'left',
						width:  80,
						hidden: true
					},
					{
						key:    true,
						label:  'Netpin',
						name:   'contact_netpin_no',
						align:  'left',
						width:  150,
						search: true
					},
                    {
                        label:  'Notify Type',
                        name:   'contact_approver_fyi',
                        align:  'left',
                        width:  150,
                        search: true
                    },
					{
						label:  'Respond By',
						name:   'contact_respond_by_date',
						align:  'left',
						width:  150,
						search: true,
						searchoptions:
						{
							sopt: ['eq','ne','lt','le','gt','ge'],
							dataInit: function (elem) {
								$(elem).datepicker(
									{
										dateFormat: 'mm/dd/yy',
										changeYear: true,
										changeMonth: true,
										showWeek: true,
										onSelect: function (dateText, inst)
										{
											//setTimeout(
											//	function ()
											//	{
											//		$(contactGrid)[0].triggerToolbar();
											//	},
											//	100
											//);
										}
									}
								);
							}
						}
					},
					{
						label:  'Response',
						name:   'contact_response_status',
						align:  'left',
						width:  150,
						search: true
					},
					{
						label:  'Response Date',
						name:   'contact_response_date',
						align:  'left',
						width:  150,
						search: true,
						searchoptions:
						{
							sopt: ['eq','ne','lt','le','gt','ge'],
							dataInit: function (elem) {
								$(elem).datepicker(
									{
										dateFormat: 'mm/dd/yy',
										changeYear: true,
										changeMonth: true,
										showWeek: true,
										onSelect: function (dateText, inst)
										{
											//setTimeout(
											//	function ()
											//	{
											//		$(contactGrid)[0].triggerToolbar();
											//	},
											//	100
											//);
										}
									}
								);
							}
						}
					},
					{
						label:  'Responded Name',
						name:   'contact_response_name',
						align:  'left',
						width:  530,
						search: true
					},
					{
						label:  'Page On-Call',
						name:   'contact_send_page',
						align:  'center',
						width:  150,
						search: true
					},
					{
						label:  'Send Group Email',
						name:   'contact_send_email',
						align:  'center',
						width:  150,
						search: true
					}
				],
				width:        '100%',
				height:       '100%',
				caption:      'Contacts',
				rowNum:       15,
				viewrecords:  true,
				altRows:      true,
				viewsortcols: true,
				loadtext:     'Loading contacts',
				subGrid:            true,
				subGridRowExpanded: subgrid3,
				pager: subGridPager2,
				onSelectRow: function (key, selected)
				{
					//alert('parentDirectoryName[#mainGrid_CCT700000001_subgrid1] = ' + parentDirectoryName['#mainGrid_CCT700000001_subgrid1']);
					//alert('parentDirectoryRowID[#mainGrid_CCT700000001_subgrid1] = ' + parentDirectoryRowID['#mainGrid_CCT700000001_subgrid1']);

					//alert('subgrid3 onSelectRow: key = ' + key);

					// subgrid2 onSelectRow key: 17340 and subgrid name: #mainGrid_CCT700000002_subgrid1_325_subgrid2_17340_subgrid3
					//alert('subgrid2 onSelectRow key: ' + key + ' and subgrid name: ' + subGrid);


					system_id         = $(subGrid2).jqGrid("getCell", key, "system_id");
					contact_netpin_no = $(subGrid2).jqGrid("getCell", key, "contact_netpin_no");


					//alert(contact_netpin_no);

					//alert('subgrid2: ticket_no = ' + ticket_no + ' system_id = ' + system_id + ' contact_netpin_no = ' + contact_netpin_no);
					openContactsDialog(dialog_contact_title, ticket_no, system_id, contact_netpin_no, subGrid2);

					// Before the dialog opens the code falls through the end of this function as if it was forked
					// and becomes a separate thread. So you cannot wait here while the dialog box closes so you
					// can refresh the data.

					//$(this).trigger("reloadGrid");

				},
				loadComplete: function()
				{
					var ids = $(this).jqGrid("getDataIDs"), l = ids.length, i, rowid;

					for (i = 0; i < l; i++)
					{
						rowid = ids[i];
						colid = 6;       // column index number from above colModel[]

						//
						// get data from some column "ColumnName"
						//
						var ColumnName = $(this).jqGrid("getCell", rowid, "contact_response_status");

						switch ( ColumnName )
						{
							case 'DRAFT':    // DRAFT    - Magenta         #FF00FF
								$(this).setCell(rowid , colid, "DRAFT",    { color: '#FF00FF'});
								break;
							case 'ACTIVE':   // ACTIVE   - navy            #000080
								$(this).setCell(rowid , colid, "ACTIVE",   { color: '#000080'});
								break;
							case 'FROZEN':   // FROZEN   - dark slate gray #2F4F4F
								$(this).setCell(rowid , colid, "FROZEN",   { color: '#2F4F4F'});
								break;
							case 'WAITING':  // WAITING  - Teal            #008080
								$(this).setCell(rowid , colid, "WAITING",  { color: '#008080'});
								break;
							case 'APPROVED': // APPROVED - dark green      #006400
								$(this).setCell(rowid , colid, "APPROVED", { color: '#006400'});
								break;
							case 'REJECTED': // REJECTED - Maroon          #800000
								$(this).setCell(rowid , colid, "REJECTED", { color: '#800000'});
								break;
							case 'EXEMPT':   // EXEMPT   - orange red      #FF4500
								$(this).setCell(rowid , colid, "EXEMPT",   { color: '#FF4500'});
								break;
							case 'CANCELED': // CANCELED - Indian Red      #CD5C5C
								$(this).setCell(rowid , colid, "CANCELED", { color: '#CD5C5C'});
								break;
							case 'CLOSED':   // CLOSED   - black           #000000
								$(this).setCell(rowid , colid, "CLOSED",   { color: '#000000'});
								break;
							default:
								break;
						}
					}
				}
			});

			jQuery(subGrid2).jqGrid('setLabel', 'system_id',               '', {'text-align': 'left'}, {title: 'System Record ID. (Should be hidden)'});
			jQuery(subGrid2).jqGrid('setLabel', 'contact_netpin_no',       '', {'text-align': 'left'}, {title: 'Contact Netpin number as defined in NET.'});
			jQuery(subGrid2).jqGrid('setLabel', 'contact_approver_fyi',    '', {'text-align': 'left'}, {title: 'Approves work or just receives FYI notification'});
			jQuery(subGrid2).jqGrid('setLabel', 'contact_respond_by_date', '', {'text-align': 'left'}, {title: 'Clients need to respond by the end of this day.'});
			jQuery(subGrid2).jqGrid('setLabel', 'contact_response_status', '', {'text-align': 'left'}, {title: 'Client response: [WAITING, APPROVED, REJECTED, EXEMPT]'});
			jQuery(subGrid2).jqGrid('setLabel', 'contact_response_date',   '', {'text-align': 'left'}, {title: 'This is the date and time the client responded. (Can only respond when ticket is ACTIVE.)'});
			jQuery(subGrid2).jqGrid('setLabel', 'contact_response_name',   '', {'text-align': 'left'}, {title: 'The name of the person who responded.'});
			jQuery(subGrid2).jqGrid('setLabel', 'contact_send_page',       '', {'text-align': 'left'}, {title: 'Does the client want the SA to page the on-call person for this netpin while work is being done?'});
			jQuery(subGrid2).jqGrid('setLabel', 'contact_send_email',      '', {'text-align': 'left'}, {title: 'Does the client want the SA to email the netpin group members while work is being done?'});

			jQuery(subGrid2).jqGrid('navGrid', subGridPager2,
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
			).navButtonAdd(subGridPager2,
				{
					caption:     '',
					title:       "Contact Excel",
					buttonicon : 'ui-icon-suitcase',
					onClickButton:function()
					{
						alert('Contact Excel download');
					}
				}
			).navButtonAdd(subGridPager2,
				{
					caption:     '',
					title:       "Refresh the Grid",
					buttonicon : 'ui-icon-refresh',
					onClickButton:function()
					{
						$(subGrid2).trigger("reloadGrid",[{current:true}]);
					}
				}
			);
		}

		//
		// Subgrid3: Connections
		//
		function subgrid3(parentRowID, parentRowKey)
		{
			var subGridID3      = parentRowID + "_subgrid3";
			var subGridPagerID3 = parentRowID + "_subgrid3_pager";
			var subGridURL3     = 'ajax_jqgrid_toolbar_open_connections.php?rowid=' + parentRowID;

			subGrid3      = "#" + subGridID3;
			subGridPager3 = "#" + subGridPagerID3;

			$('#' + parentRowID).append('<table id=' + subGridID3 + '></table><div id=' + subGridPagerID3 + ' class=scroll></div>');

			$(subGrid3).jqGrid({
				url:      subGridURL3,
				mtype:    "GET",
				datatype: "json",
                ajaxGridOptions: { contentType: 'application/json; charset=utf-8', cache: false },
				postData:
				{
					action:       'get',
					where_clause: '',
					order_by:     '',
					direction:    'asc'
				},
				colModel: [
					{
						key:    true,
						label:  'contact_id',
						name:   'contact_id2',
						align:  'left',
						width:  50,
						hidden: true
					},
					{
						label:  'system_id',
						name:   'system_id2',
						align:  'left',
						width:  80,
						hidden: true
					},
					{
						label:  'Netpin',
						name:   'contact_netpin_no2',
						align:  'left',
						width:  150,
						hidden: true
					},

					{
						label:  'Connection',
						name:   'contact_connection2',
						align:  'left',
						width:  405,
						search: true
					},
					{
						label:  'OS',
						name:   'contact_server_os2',
						align:  'left',
						width:  100,
						search: true
					},
					{
						label:  'Usage',
						name:   'contact_server_usage2',
						align:  'left',
						width:  150,
						search: true
					},
					{
						label:  'Group',
						name:   'contact_work_group2',
						align:  'left',
						width:  150,
						search: true
					},
					{
						label:  'Type',
						name:   'contact_approver_fyi2',
						align:  'left',
						width:  150,
						search: true
					},
					{
						label:  'CSC Banner',
						name:   'contact_csc_banner2',
						align:  'left',
						width:  360,
						search: true
					},
					{
						label:  'App or DB Name',
						name:   'contact_apps_databases2',
						align:  'left',
						width:  240,
						search: true
					}
				],
				width:        '100%',
				height:       '100%',
				caption:      'Connections',
				rowNum:       100,
				viewrecords:  true,
				altRows:      true,
				viewsortcols: true,
				loadtext:     'Loading connections',
				pager:        subGridPager3
			});

			jQuery(subGrid3).jqGrid('setLabel', 'contact_id2',             '', {'text-align': 'left'}, {title: 'Hidden contact_id value in grid.'});
			jQuery(subGrid3).jqGrid('setLabel', 'system_id2',              '', {'text-align': 'left'}, {title: 'Hidden system_id value in grid.'});
			jQuery(subGrid3).jqGrid('setLabel', 'contact_netpin_no2',      '', {'text-align': 'left'}, {title: 'Contact net-pin group number as defined in CSC.'});
			jQuery(subGrid3).jqGrid('setLabel', 'contact_connection2',     '', {'text-align': 'left'}, {title: 'Server connection information as it relates to the server being worked on.'});
			jQuery(subGrid3).jqGrid('setLabel', 'contact_server_os2',      '', {'text-align': 'left'}, {title: 'Connection server operating system.'});
			jQuery(subGrid3).jqGrid('setLabel', 'contact_server_usage2',   '', {'text-align': 'left'}, {title: 'Connection server usage.'});
			jQuery(subGrid3).jqGrid('setLabel', 'contact_work_group2',     '', {'text-align': 'left'}, {title: 'Contact net-pin work group as determined by the CSC banner.'});
			jQuery(subGrid3).jqGrid('setLabel', 'contact_approver_fyi2',   '', {'text-align': 'left'}, {title: 'Contact approver or just FYI only as determined by the CSC banner and work activity.'});
			jQuery(subGrid3).jqGrid('setLabel', 'contact_csc_banner2',     '', {'text-align': 'left'}, {title: 'CSC banner this contact net-pin.'});
			jQuery(subGrid3).jqGrid('setLabel', 'contact_apps_databases2', '', {'text-align': 'left'}, {title: 'Contact supporting this application or database name.'});

			// $(subGrid).jqGrid('addRowData', i + 1, mydata[i]);
		}

	});

	<!-- DIALOG: Ticket -->

    var refresh_grid = '';

	function openTicketDialog(title, ticket_no)
	{
		var url = 'dialog_toolbar_open_tickets.php?ticket_no=' + ticket_no + '&what_tickets=<?php echo $what_tickets; ?>';
		var content = '<iframe src="' + url + '" ' +
			'style="border: none; min-width: 100%; min-height: 100%;">' + '</iframe>';

		var close_button =
				'<a data-toggle="ticket_close" title="Close this dialog box.">' +
				'<button class="btn" onclick="w2popup.close();">Close</button></a>';

		w2popup.open({
			title     : title,
			body      : content,
			buttons   : '<input title="Close Dailog box." type="button" value="Close" onclick="w2popup.close();">',
			width     : 880,
			height    : 685,
			overflow  : 'hidden',
			color     : '#333',
			speed     : '0.1',
			opacity   : '0.8',
			modal     : true,
			showClose : true,
			showMax   : true,
			onOpen    : function (event)
			{

			},
			onClose   : function (event)
			{

				var url = 'ajax_get_status_info.php?ticket_no=' + ticket_no + '&system_id=0';

				//
				// Make a jQuery AJAX call to ajax_get_status_info.php to return the data we want to update in the grids.
				//
				$.ajax({
					type: 'GET',
					url:   url,
					async: false,
					beforeSend: function (xhr)
					{
						if (xhr && xhr.overrideMimeType)
						{
							xhr.overrideMimeType('application/json;charset=utf-8');
						}
					},
					dataType: 'json',
					success: function (data)
					{
						if (data['ajax_status'] == 'REFRESH' || refresh_grid == 'refresh_main_grid')
						{
							$('#mainGrid').trigger("reloadGrid",[{current:true}]);
							return;
						}

						if (data['ajax_status'] != 'SUCCESS')
						{
							alert(data['ajax_message']);
							return;
						}

						$('#mainGrid').setCell(ticket_no, "status",                       data['status']);
						$('#mainGrid').setCell(ticket_no, "total_servers_scheduled",      data['total_servers_scheduled']);
						$('#mainGrid').setCell(ticket_no, "total_servers_waiting",        data['total_servers_waiting']);
						$('#mainGrid').setCell(ticket_no, "total_servers_approved",       data['total_servers_approved']);
						$('#mainGrid').setCell(ticket_no, "total_servers_rejected",       data['total_servers_rejected']);
						$('#mainGrid').setCell(ticket_no, "total_servers_not_scheduled",  data['total_servers_not_scheduled']);
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
				});
			},
			onMax     : function (event) { console.log('max'); },
			onMin     : function (event) { console.log('min'); },
			onKeydown : function (event) { console.log('keydown'); }
		});
	}

	function openTicketSortList()
    {
        var url = 'dialog_open_ticket_sort.php';
        var content = '<iframe src="' + url + '" ' +
            'style="border: none; min-width: 100%; min-height: 100%;">' + '</iframe>';

        w2popup.open({
            title     : 'Select sort order',
            body      : content,
            //buttons   : '<input title="Close Dailog box." type="button" value="Close Me" onclick="w2popup.close();">',
            width     : 575,
            height    : 460,
            overflow  : 'hidden',
            color     : '#333',
            speed     : '0.1',
            opacity   : '0.8',
            modal     : true,
            showClose : true,
            showMax   : true,
            onOpen    : function (event)
            {

            },
            onClose   : function (event)
            {
                //alert('closing');
                $(mainGrid).trigger("reloadGrid",[{current:true}]);

                // Create the event
                //var event = new CustomEvent("name-of-event", { "detail": "Example of an event" });

                // Dispatch/Trigger/Fire the event
                //document.dispatchEvent(event);
            },
            onMax     : function (event) { console.log('max'); },
            onMin     : function (event) { console.log('min'); },
            onKeydown : function (event) { console.log('keydown'); }
        });
    }

	<!-- DIALOG: Server -->

	// openServerDialog(dialog_system_title, ticket_no, system_id, subGrid1);
	function openServerDialog(title, ticket_no, system_id, subGrid)
	{
		var url = 'dialog_toolbar_open_systems.php?system_id=' + system_id +
            '&what_tickets=' + <?php printf("'%s'", $what_tickets); ?> + '&title=' + title;
		var content = '<iframe src="' + url + '" ' +
			'style="border: none; min-width: 100%; min-height: 100%;">' + '</iframe>';

		w2popup.open({
			title     : title,
			body      : content,
			buttons   : '<input title="Close Dailog box." type="button" value="Close" onclick="w2popup.close();">',
			width     : 860,
			height    : 685,
			overflow  : 'hidden',
			color     : '#333',
			speed     : '0.1',
			opacity   : '0.8',
			modal     : true,
			showClose : true,
			showMax   : true,
			onOpen    : function (event)
			{

			},
			onClose   : function (event)
			{
				$(subGrid).trigger("reloadGrid",[{current:true}]);
			},
			onMax     : function (event) { console.log('max'); },
			onMin     : function (event) { console.log('min'); },
			onKeydown : function (event) { console.log('keydown'); }
		});

		var myVar = setInterval(function ()
		{
			if ($('#w2ui-popup').length <= 0)
			{
				//alert('w2popup: closed');
				clearInterval(myVar);
				updateGridAllStatus(ticket_no, system_id, subGrid);
			}
		}, 1000);  // 1000 = 1 second

		//document.getElementById("system_dialog_content").innerHTML = "new content";
	}

	<!-- DIALOG: Contacts -->
	// http://cct7.localhost/dialog_toolbar_open_contacts.php?ticket_no=CCT70000001&system_id=1&contact_netpin_no=51190
	function openContactsDialog(title, ticket_no, system_id, contact_netpin_no, subGrid)
	{
		var url = 'dialog_toolbar_open_contacts.php?ticket_no=' + ticket_no +
			'&system_id=' + system_id + '&contact_netpin_no=' + contact_netpin_no +
            '&what_tickets=' + <?php printf("'%s'", $what_tickets); ?> + '&title=' + title;

		//alert(url);

		var content = '<iframe src="' + url + '" ' +
			'style="border: none; min-width: 100%; min-height: 100%;">' + '</iframe>';

		//var close_button =
		//		'<a data-toggle="ticket_close" title="Close this dialog box.">' +
		//		'<button class="btn" onclick="w2popup.close();">Close</button></a>';

		w2popup.open({
			title     : title,
			body      : content,
			buttons   : '<input title="Close Dailog box." type="button" value="Close" onclick="w2popup.close();">',
			width     : 850,
			height    : 685,
			overflow  : 'hidden',
			color     : '#333',
			speed     : '0.1',
			opacity   : '0.8',
			modal     : true,
			showClose : true,
			showMax   : true,
			onOpen    : function (event)
			{
			},
			onClose   : function (event)
			{
				$(subGrid).trigger("reloadGrid",[{current:true}]);
			},
			onMax     : function (event) { console.log('max'); },
			onMin     : function (event) { console.log('min'); },
			onKeydown : function (event) { console.log('keydown'); }
		});

		var myVar = setInterval(function ()
		{
			if ($('#w2ui-popup').length <= 0)
			{
				//alert('w2popup: closed');
				clearInterval(myVar);
				updateGridAllStatus(ticket_no, system_id, subGrid);
			}
		}, 1000);  // 1000 = 1 second

		//document.getElementById("system_dialog_content").innerHTML = "new content";
	}

    function addServer()
    {
        var hostname = document.getElementById("hostname").value;

        if (hostname.length == 0)
        {
            alert('Hostname cannot be left blank.');

            return;
        }

        //document.getElementById("loader").style.display = "block";

        var url = 'ajax_dialog_toolbar_open_systems.php';

        //
        // Prepare the data that will be sent to ajax_ticket.php
        //
        data = {
            "action":    'add_server',
            "hostname":  hostname,
            "ticket_no": ticket_no
        };

        $.ajax(
            {
                type:     "POST",
                url:      url,
                dataType: "json",
                data:     JSON.stringify(data),
                success:  function(data)
                {
                    //$(".loader").fadeOut("slow");

                    if (data['ajax_message'].length > 0)
                    {
                        alert(data['ajax_message']);
                        return;
                    }

                    jQuery(subGrid1).jqGrid('addRowData', data.row[0].system_id, data.row[0], "first");

                    updateGridAllStatus(data.row[0].ticket_no, data.row[0].system_id, subGrid1);
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

</script>

<script type="text/javascript">
	$(window).load(function()
	{
		$(".loader").fadeOut("slow");
	});

	//
	// Setup a event listener to close w2popup (iframe) windows.
	// This event is sent from the child popup iframe window to
	// the parent (this). The event will instruct the parent to
	// close the w2popup window.
	//
	window.addEventListener('message', function(e) {
		var key = e.message ? 'message' : 'data';
		var data = e[key];

		refresh_grid = e.data;  // refresh_main_grid ?

        $('#mainGrid').trigger("reloadGrid",[{current:true}]);
		//alert('Event fired!');
		w2popup.close();

	},false);
</script>

<body style="background-color: lightgoldenrodyellow">

<form name="f1" method="post" action="toolbar_open.php">

	<div class="loader"></div>

	<center>
		<table border="0" cellpadding="4" cellspacing="4">
            <tr>
                <td align="center">
                    <font size="+1"><b><?php echo $title; ?></b></font>
                </td>
            </tr>
			<?php
			if ($what_tickets == 'approve')
			{
				?>
                <tr>
                    <td align="left">
                        <table border="0" cellpadding="4" cellspacing="4">

                            <tr>
                                <td>
                                    <input value="Remove Approved"
                                           title="Clear servers and tickets as you approve work."
                                           type="button" class="button_gray" name="button"
                                           onclick="button_remove_approved()" id="remove_approved">
                                </td>
                                <td>
                                    <input value="Show All"
                                           title="Show all tickets regardless of your responses."
                                           type="button" class="button_blue" name="button"
                                           onclick="button_show_all()" id="show_all">
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
				<?php
			}
            ?>
			<tr>
				<td align="center">
					<table id="mainGrid"></table>
					<div id="mainGridPager" class=scroll></div>
				</td>
			</tr>
		</table>
	</center>
</form>
<script type="text/javascript">
    function button_remove_approved()
    {
        document.getElementById('remove_approved').className = 'button_blue';
        document.getElementById('show_all').className        = 'button_gray';

        approve_filter = 'remove_approved';  // This is used in subgrids servers and contacts

        $('#mainGrid').jqGrid('setGridParam', {
            postData:
                {
                    action:         'get',
                    where_clause:   '',
                    order_by:       't.ticket_no',
                    direction:      'asc',
                    what_tickets:   '<?php echo $what_tickets; ?>',
                    what_hostname:  '<?php echo $what_hostname; ?>',
                    what_netpin_no: '<?php echo $what_netpin_no; ?>',
                    what_cuid:      '<?php echo $what_cuid; ?>',
                    approve_filter: approve_filter
                }
        }).trigger('reloadGrid');
    }

    function button_show_all()
    {
        document.getElementById('remove_approved').className = 'button_gray';
        document.getElementById('show_all').className        = 'button_blue';

        approve_filter = 'show_all';  // This is used in subgrids servers and contacts

        $('#mainGrid').jqGrid('setGridParam', {
            postData:
                {
                    action:         'get',
                    where_clause:   '',
                    order_by:       't.ticket_no',
                    direction:      'asc',
                    what_tickets:   '<?php echo $what_tickets; ?>',
                    what_hostname:  '<?php echo $what_hostname; ?>',
                    what_netpin_no: '<?php echo $what_netpin_no; ?>',
                    what_cuid:      '<?php echo $what_cuid; ?>',
                    approve_filter: approve_filter
                }
        }).trigger('reloadGrid');
    }
</script>
</body>
</html>
