<?php
/**
 * toolbar_schedule.php
 *
 * @package   PhpStorm
 * @file      doit.php
 * @author    gparkin
 * @date      12/06/16
 * @version   7.0
 *
 * @brief     Bring up the group Work Schedule
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
$lib->debug_start('toolbar_schedule.html');
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
$what_tickets   = "all";

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
// Open all scheduled servers or just the group scheduled servers. [ all, group ]
//
if (array_key_exists('what_tickets', $param))
{
	$what_tickets = $param['what_tickets'];
}

$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "what_tickets: %s",   $what_tickets);

if ($what_tickets == "group")
    $title = "Group Ready Work";
else
    $title = "All Ready Work";

//
// Setup grid captions
//
$caption_tickets = "all";

if ($what_tickets == "all")
{
	$caption_tickets = "Scheduled Work - (All)";
}
else
{
	$caption_tickets = "Scheduled Work - (Group)";
	$what_tickets = "group";
}

//
// Set choose_date to today's date.
//
$dt = new DateTime("now");
$choose_date = $dt->format('l, F j, Y');  // i.e. Tuesday, July 25, 2017

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

    .button_green
    {
        border: font-size: 11px;
        width: 10em;
        color: #00FF00;
        font-weight: bold;
        font-family: arial;
        background-color: #d3d3d3;
        text-decoration: none;
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

    .button {
        display: inline-block;
        text-align: center;
        vertical-align: middle;
        padding: 4px 80px;
        border: 1px solid #1a4283;
        border-radius: 8px;
        background: #307df6;
        background: -webkit-gradient(linear, left top, left bottom, from(#307df6), to(#1a4283));
        background: -moz-linear-gradient(top, #307df6, #1a4283);
        background: linear-gradient(to bottom, #307df6, #1a4283);
        -webkit-box-shadow: #3a96ff 4px 4px 5px 0px;
        -moz-box-shadow: #3a96ff 4px 4px 5px 0px;
        box-shadow: #3a96ff 4px 4px 5px 0px;
        text-shadow: #0f274d 3px 2px 0px;
        font: normal normal bold 20px arial;
        color: #ffffff;
        text-decoration: none;
    }
    .button:hover,
    .button:focus {
        border: 1px solid ##1e4e9a;
        background: #3a96ff;
        background: -webkit-gradient(linear, left top, left bottom, from(#3a96ff), to(#1f4f9d));
        background: -moz-linear-gradient(top, #3a96ff, #1f4f9d);
        background: linear-gradient(to bottom, #3a96ff, #1f4f9d);
        color: #ffffff;
        text-decoration: none;
    }
    .button:active {
        background: #1a4283;
        background: -webkit-gradient(linear, left top, left bottom, from(#1a4283), to(#1a4283));
        background: -moz-linear-gradient(top, #1a4283, #1a4283);
        background: linear-gradient(to bottom, #1a4283, #1a4283);
    }

</style>

<link rel="stylesheet" type="text/css" media="screen" href="css/cct-ui.css">

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

	$(document).ready(function ()
	{
		var masterGridID      = 'masterGrid';
		var masterGridPagerID = 'masterGridPager';
		var masterGrid        = '#' + masterGridID;
		var masterGridPager   = '#' + masterGridPagerID;

		// System ID
		// ===========
		// s.system_id

		// Remedy Ticket   CCT Ticket   Work Activity    Hostname           Status                OS           Usage
		// =============   ===========  =============    =================  ====================  ===========  ==============
		// t.cm_ticket_no  t.ticket_no  t.work_activity  s.system_hostname  s.system_work_status  s.system_os  s.system_usage

		// Ticket Owner  Work Start                Work End                Duration                Reboot
		// ============  ========================  ======================  ======================  =================
		// t.owner_name  s.system_work_start_date  s.system_work_end_date  s.system_work_duration  t.reboot_required

		/*
		 $row['system_id']              = $ora->system_id;
		 $row['cm_ticket_no']           = $ora->cm_ticket_no;
		 $row['work_activity']          = $ora->work_activity;
		 $row['system_hostname']        = $ora->system_hostname;
		 $row['system_work_status']     = $ora->system_work_status;
		 $row['system_os']              = $ora->system_os;
		 $row['system_usage']           = $ora->system_usage;
		 $row['owner_name']             = $ora->owner_name;
		 $row['system_work_start_date'] = $lib->gmt_to_format($ora->system_work_start_date, 'm/d/Y H:i', $tz);
		 $row['system_work_end_date']   = $lib->gmt_to_format($ora->system_work_end_date,   'm/d/Y H:i', $tz);
		 $row['system_work_duration']   = $ora->system_work_duration;
		 $row['reboot_required']        = $ora->reboot_required;

		 */
		$(masterGrid).jqGrid({
			url:      'ajax_jqgrid_schedule.php?action=get&what_tickets=<?php echo $what_tickets; ?>',
			mtype:    "GET",
			datatype: "json",
			postData:
			{
				action:        'get',
				where_clause:  '',
				order_by:      '',
				direction:     'asc',
                filter_button: 'today'
			},
			colModel: [
				{
					key:           true,
					label:         'Unique Record ID',
					name:          'system_id',
					align:         'left',
					width:         120,
					hidden:        true
				},
				{
					label:         'Remedy CM #',
					name:          'cm_ticket_no',
					align:         'left',
					width:         120
				},
				{
					label:         'CCT Ticket #',
					name:          'ticket_no',
					align:         'left',
					width:         120,
					search:        true,
					searchoptions: { sopt: ['eq','bw','bn','cn','nc','ew','en'] }
				},
				{
					label:         'Work Activity',
					name:          'work_activity',
					width:         120,
					align:         'left',
					search:        true
				},
				{
					label:         'Hostname',
					name:          'system_hostname',
					width:         135,
					align:         'left',
					search:        true
				},
				{
					label:         'Status',
					name:          'system_work_status',
					width:         120,
					align:         'left',
					search:        true,
					stype:         "select",
					searchoptions:
					{
						value:     "APPROVED:APPROVED;CANCELED:CANCELED;FAILED:FAILED;REJECTED:REJECTED;STARTING:STARTING;SUCCESS:SUCCESS",
						defaultValue: "APPROVED"
					}
				},
				{
					label:         'OS',
					name:          'system_os',
					width:         120,
					align:         'left',
					search:        true
				},
				{
					label:         'Usage',
					name:          'system_usage',
					width:         150,
					align:         'left',
					search:        true
				},
				{
					label:         'Ticket Owner',
					name:          'owner_name',
					width:         270,
					align:         'left',
					search:        true,
					searchoptions:
					{
						value:     "ALL:ALL;<?php printf("%s;%s", $_SESSION['user_name'], $_SESSION['user_name']); ?>",
						defaultValue: "<?php printf("%s", $_SESSION['user_name']); ?>"
					}
				},
				{
					label:         'Work Start',
					name:          'system_work_start_date',
					width:         150,
					align:         'left',
					search:        true,
					searchoptions:
					{
						sopt: ['eq','ne','lt','le','gt','ge'],
						dataInit: function (elem)
						{
							$(elem).datepicker(
								{
									dateFormat: 'mm/dd/yy',
									changeYear:  true,
									changeMonth: true,
									showWeek:    true,
                                    showButtonPanel: true
								}
							);
						}
					}
				},
				{
					label:         'Work End',
					name:          'system_work_end_date',
					width:         150,
					align:         'left',
					search:        true,
					searchoptions:
					{
						sopt: ['eq','ne','lt','le','gt','ge'],
						dataInit: function (elem)
						{
							$(elem).datepicker(
								{
									dateFormat:  'mm/dd/yy',
									changeYear:  true,
									changeMonth: true,
									showWeek:    true,
                                    showButtonPanel: true
								}
							);
						}
					}
				},
				{
					label:         'Duration',
					name:          'system_work_duration',
					width:         85,
					align:         'left',
					search:        true,
				},
				{
					label:         'Reboots',
					name:          'reboot_required',
					width:         70,
					align:         'center',
					search:        true,
					stype:         "select",
					searchoptions:
					{
						value:     "All:A;Yes:Y;No:N",
						defaultValue: "A"
					}
				}
			],
			caption:      '<?php echo $caption_tickets; ?>',
			width:        '100%',
			height:       '100%',
			rowNum:       20,
			viewrecords:  true,
			altRows:      true,
			viewsortcols: true,
			loadtext:     'Loading Schedule...',
			imgpath:      'images',
			multiselect:  true,
			pager:        masterGridPager,
			onSelectRow: function (key, selected)
			{

			},
			loadComplete: function()
			{
				var ids = $(this).jqGrid("getDataIDs"), l = ids.length, i, rowid;

				for (i = 0; i < l; i++)
				{
					rowid = ids[i];
					colid = 6;       // column index number from above colModel[]

					//alert('i=' + i + ', rowid=' + rowid);
					//
					// get data from some column "ColumnName"
					//
					var ColumnName = $(this).jqGrid("getCell", rowid, "system_work_status");

					// "APPROVED:APPROVED;CANCELED:CANCELED;FAILED:FAILED;REJECTED:REJECTED;STARTING:STARTING;SUCCESS:SUCCESS",
					switch ( ColumnName )
					{
						case 'APPROVED':
						case 'READY':
							$(this).setCell(rowid , colid, "READY",    { color: '#000080'});  // Navy Blue
							break;
						case 'CANCELED':
							$(this).setCell(rowid , colid, "CANCELED", { color: '#FF4500'});  // Orange Red
							break;
						case 'FAILED':
							$(this).setCell(rowid , colid, "FAILED",   { color: '#CD5C5C'});  // Indian Red
							break;
						case 'REJECTED':
							$(this).setCell(rowid , colid, "REJECTED", { color: '#C70039'});  // Red
							break;
						case 'STARTING':
							$(this).setCell(rowid , colid, "STARTING", { color: '#006400'});  // Dark Green
							break;
						case 'SUCCESS':
							$(this).setCell(rowid , colid, "SUCCESS",  { color: '#2F4F4F'});  // Dark Slate Gray
							break;
						default:
							//alert('Unknown column name: ' + ColumnName);
							break;
					}
				}
			}
		});

		jQuery(masterGrid).jqGrid('setLabel', 'system_id',              '', {'text-align': 'center'}, {title: 'Key (hidden)'});
		jQuery(masterGrid).jqGrid('setLabel', 'cm_ticket_no',           '', {'text-align': 'center'}, {title: 'Associated Remedy CM Ticket.'});
		jQuery(masterGrid).jqGrid('setLabel', 'ticket_no',              '', {'text-align': 'left'},   {title: 'CCT ticket number.'});
		jQuery(masterGrid).jqGrid('setLabel', 'work_activity',          '', {'text-align': 'center'}, {title: 'Work activity description.'});
		jQuery(masterGrid).jqGrid('setLabel', 'system_hostname',        '', {'text-align': 'center'}, {title: 'Server name where work will be performed.'});
		jQuery(masterGrid).jqGrid('setLabel', 'system_work_status',     '', {'text-align': 'left'},   {title: 'Current server work status for this server.'});
		jQuery(masterGrid).jqGrid('setLabel', 'system_os',              '', {'text-align': 'center'}, {title: 'Server operating system.'});
		jQuery(masterGrid).jqGrid('setLabel', 'system_usage',           '', {'text-align': 'center'}, {title: 'System Usage.'});
		jQuery(masterGrid).jqGrid('setLabel', 'owner_name',             '', {'text-align': 'left'},   {title: 'CCT ticket owner.'});
		jQuery(masterGrid).jqGrid('setLabel', 'system_work_start_date', '', {'text-align': 'center'}, {title: 'Work start date and time.'});
		jQuery(masterGrid).jqGrid('setLabel', 'system_work_end_date',   '', {'text-align': 'center'}, {title: 'Work end date and time.'});
		jQuery(masterGrid).jqGrid('setLabel', 'system_work_duration',   '', {'text-align': 'left'},   {title: 'Calculated work duration.'});
		jQuery(masterGrid).jqGrid('setLabel', 'reboot_required',        '', {'text-align': 'left'},   {title: 'Will the server be rebooted?'});

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
                        { "field": "ticket_no", "op": "ew", "data": "" }
                    ]
                };

		jQuery(masterGrid).jqGrid('navGrid', masterGridPager,
			{ add: false, edit: false, del: false, refresh: false },  // options
			{}, // edit options
			{}, // add options
			{}, // del options
			{
				// Search
				closeOnEscape:  true,
				multipleSearch: true,
				multipleGroup:  true,
                tmpLabel:       'Search by template:&nbsp;',
                tmplNames:      [ "CCT Ticket No.", "Remedy CM No." ],
                tmplFilters:    [ search_template2, search_template1 ]
			}
		).navButtonAdd(masterGridPager,
			{
				caption:     '',
				title:       "Contact Excel",
				buttonicon : 'ui-icon-suitcase',
				onClickButton:function()
				{
                    var url = 'ajax_jqgrid_schedule.php?action=excel&what_tickets=<?php echo $what_tickets; ?>';

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
                            JSONToCSVConvertor(data['rows'], "<?php echo $caption_tickets; ?>", true);
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
	});

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

	function changeDialog()
    {
        //
        // Grab the list of checked list_system_id's
        //
        var list;
        list = jQuery(masterGrid).jqGrid('getGridParam', 'selarrrow');

        if (list.length == 0)
        {
            alert('Please select one or more servers before clicking on this button.');
            return;
        }

        var url = 'dialog_schedule.php?list=' + list;
        var content = '<iframe src="' + url + '" ' +
            'style="border: none; min-width: 100%; min-height: 100%;">' + '</iframe>';

        w2popup.open({
            title     : 'Change Status and Send Notifications',
            body      : content,
            width     : 850,
            height    : 685,
            overflow  : 'hidden',
            color     : '#333',
            speed     : '0.3',
            opacity   : '0.8',
            modal     : true,
            showClose : true,
            showMax   : true,
            onOpen    : function (event)
            {

            },
            onClose   : function (event)
            {
                jQuery(masterGrid).jqGrid('resetSelection');  // Un-check the boxes.
                $('#masterGrid').trigger("reloadGrid",[{current:true}]);
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

<form name="f1" method="post" action="toolbar_schedule.php">

	<div class="loader"></div>

	<center>
		<table border="0" cellpadding="4" cellspacing="4">
            <tr>
                <td align="center">
                    <font size="+1"><b><?php echo $title; ?></b></font>
                </td>
            </tr>
            <tr>
                <td align="left">
                    <table border="0" cellpadding="4" cellspacing="4">
                        <tr>
                            <td>
                                <input type="button" class="button_gray" name="button" value="Yesterday"
                                    onclick="button_yesterday()" id="yesterday">
                            </td>
                            <td>
                                <input type="button" class="button_blue" name="button" value="Today"
                                    onclick="button_today()" id="today">
                            </td>
                            <td>
                                <input type="button" class="button_gray" name="button" value="Tomorrow"
                                    onclick="button_tomorrow()" id="tomorrow">
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
			<tr>
				<td align="center">
					<table id="masterGrid"></table>
					<div id="masterGridPager" class=scroll></div>
				</td>
			</tr>
			<tr>
				<td align="center">
                    <a class="button" title="Select one or more servers, then click this button to open dialog box."
                       href="#" onclick="changeDialog();">Change Status and Send Notifications</a>
				</td>
			</tr>
		</table>
	</center>

</form>
</body>
<script type="text/javascript">
    function button_yesterday()
    {
        document.getElementById('yesterday').className = 'button_blue';
        document.getElementById('today').className     = 'button_gray';
        document.getElementById('tomorrow').className  = 'button_gray';

        $('#masterGrid').jqGrid('setGridParam', {
            postData:
                {
                    action:         'get',
                    where_clause:   '',
                    order_by:       '',
                    direction:      'asc',
                    filter_button:  'yesterday'
                }
        }).trigger('reloadGrid');
    }

    function button_today()
    {
        document.getElementById('yesterday').className = 'button_gray';
        document.getElementById('today').className     = 'button_blue';
        document.getElementById('tomorrow').className  = 'button_gray';

        $('#masterGrid').jqGrid('setGridParam', {
            postData:
                {
                    action:         'get',
                    where_clause:   '',
                    order_by:       '',
                    direction:      'asc',
                    filter_button:  'today'
                }
        }).trigger('reloadGrid');
    }

    function button_tomorrow()
    {
        document.getElementById('yesterday').className = 'button_gray';
        document.getElementById('today').className     = 'button_gray';
        document.getElementById('tomorrow').className  = 'button_blue';

        $('#masterGrid').jqGrid('setGridParam', {
            postData:
                {
                    action:         'get',
                    where_clause:   '',
                    order_by:       '',
                    direction:      'asc',
                    filter_button:  'tomorrow'
                }
        }).trigger('reloadGrid');
    }
</script>
</html>
