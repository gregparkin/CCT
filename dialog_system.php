<?php
/**
 * dialog_system.php
 *
 * @package   PhpStorm
 * @file      dialog_system.php
 * @author    gparkin
 * @date      6/30/16
 * @version   7.0
 *
 * @brief     Dialog box for working with system and contact records in cct7_systems and cct7_contacts
 *            This module is included in work_request_grid.php
 *
 */

//$path = '/usr/lib/pear';
//set_include_path(get_include_path() . PATH_SEPARATOR . $path);

set_include_path("/opt/ibmtools/www/cct7/classes");

// <button class="btn" onclick="w2ui.tabs.click('tab3')">Set Tab 3 Active</button>

//
// Class autoloader - Removes the need to add include and require statements
//
if (!function_exists('__autoload'))
{
    require_once('classes/autoloader.php');
}

session_start(); // Call to make $_SESSION['...'] data available

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

$parm = array();
$parm_count = 0;

//
// NOTE: It is very important that you do not turn on debugging without writing to a file for server side AJAX code. Any HTML
//       comments coming from functions while writing XML will show up in the XML output and you will get a XML parsing error
//       in the client side program.
//
$lib = new library();  // classes/library.php
$lib->debug_start('dialog_system.html');
date_default_timezone_set('America/Denver');

//
// Parse QUERY_STRING
//
if (isset($_SERVER['QUERY_STRING']))
{
    //
    // $myQryStr = "first=1&second=Z&third[]=5000&third[]=6000";
    //
    // parse_str($_SERVER['QUERY_STRING'], $parm);
    // echo $parm['first']; //will output 1
    // echo $parm['second']; //will output Z
    // echo $parm['third'][0]; //will output 5000
    // echo $parm['third'][1]; //will output 6000
    //
    parse_str($_SERVER['QUERY_STRING'], $parm);
    $parm_count = count($parm);

    $lib->debug1(__FILE__, __FUNCTION__, __LINE__, "QUERY_STRING: %s", $_SERVER['QUERY_STRING']);

    foreach ($parm as $key => $value)
        $lib->debug1(__FILE__, __FUNCTION__, __LINE__, "parm[%s] = %s", $key, $value);
}

if (!isset($parm['system_id']))
{
    $lib->debug1(__FILE__, __FUNCTION__, __LINE__, "Syntax error. Missing system_id");
    printf("Missing system_id");
    exit();
}

?>

<!DOCTYPE html>
<html>
<head>
    <title>W2UI Demo: combo-5</title>

    <script src="js/jquery-2.1.4.js"></script>
    <!-- w2ui popup -->
    <!-- link rel="stylesheet" type="text/css" href="css/w2ui-1.4.3.css" / -->
    <!-- script type="text/javascript" src="js/w2ui-1.4.3.js"></script -->

    <link rel="stylesheet" type="text/css" href="css/w2ui-1.5.rc1.css" />
    <script type="text/javascript" src="js/w2ui-1.5.rc1.js"></script>
    
    <script type="text/javascript">

        function callAjaxSystem(action, system_id)
        {
            var data;
            var array;
            var table;
            var i;
            var index;
            var rowCount;
            var row;
            var cell;

            //alert('callAjaxSystem: action = ' + action + ', system_id = ' + system_id);

            //
            // Prepare the data that will be sent to ajax_ticket.php
            //
            data = {
                "action":          action,
                "system_id":       system_id
            };

            //
            // Create a JSON string from the dialog_system_selected row of data.
            //
            //var gridData = jQuery(systemGrid).jqGrid('getRowData', system_id);
            //var postData = JSON.stringify(gridData);
            //var postData = JSON.stringify(data);
            //alert(postData);

            //alert(JSON.stringify(data));

            var url = 'ajax_contacts.php';
            //alert(url);

            $.ajax(
                {
                    type:     "POST",
                    url:      url,
                    dataType: "json",
                    data:     JSON.stringify(data),
                    success:  function(data)
                    {
                        //print_r(data);

                        if (data['status'] == 'FAILED')
                        {
                            alert(data['message']);
                            return;
                            // closeSystemDialog();
                        }

                        //
                        // data['contacts']
                        // ===========================================================================================
                        //
                        array = typeof  data['contacts'] != 'object' ? JSON.parse(data['contacts']) : data['contacts'];
                        table = document.getElementById('table_system_contacts');

                        for (i = 0; i < array.length; i++)
                        {
                            rowCount = table.rows.length;
                            row      = table.insertRow(rowCount);

                            for (index in array[i])
                            {
                                cell = row.insertCell(-1);
                                cell.innerHTML = array[i][index];
                                cell.style.verticalAlign = "top";
                            }
                        }

                        //
                        // Remove any event rows from last display
                        //
                        table = document.getElementById("table_system_systems");
                        rowCount = table.rows.length;

                        while (rowCount > 1)
                        {
                            table.deleteRow(rowCount -1);
                            --rowCount;
                        }

                        //
                        // Add new rows to table: table_system_systems
                        //
                        array = typeof  data['contacts'] != 'object' ? JSON.parse(data['contacts']) : data['contacts'];
                        table = document.getElementById('table_system_systems');

                        for (i = 0; i < array.length; i++)
                        {
                            rowCount = table.rows.length;
                            row      = table.insertRow(rowCount);

                            for (index in array[i])
                            {
                                cell = row.insertCell(-1);
                                cell.innerHTML = array[i][index];
                                cell.style.verticalAlign = "top";
                            }
                        }

                        //
                        // data['events']
                        // ===========================================================================================
                        //
                        array = typeof  data['events'] != 'object' ? JSON.parse(data['events']) : data['events'];
                        table = document.getElementById('table_system_events');

                        for (i = 0; i < array.length; i++)
                        {
                            rowCount = table.rows.length;
                            row      = table.insertRow(rowCount);

                            for (index in array[i])
                            {
                                cell = row.insertCell(-1);
                                cell.innerHTML = array[i][index];
                                cell.style.verticalAlign = "top";
                            }
                        }

                        //
                        // Remove any event rows from last display
                        //
                        table = document.getElementById("table_system_events");
                        rowCount = table.rows.length;

                        while (rowCount > 1)
                        {
                            table.deleteRow(rowCount -1);
                            --rowCount;
                        }

                        //
                        // Add new rows to table
                        //
                        array = typeof  data['events'] != 'object' ? JSON.parse(data['events']) : data['events'];
                        table = document.getElementById('table_system_events');

                        for (i = 0; i < array.length; i++)
                        {
                            rowCount = table.rows.length;
                            row      = table.insertRow(rowCount);

                            for (index in array[i])
                            {
                                cell = row.insertCell(-1);
                                cell.innerHTML = array[i][index];
                                cell.style.verticalAlign = "top";
                            }
                        }

                        if (data['message'].length > 0)
                            alert(data['message']);
                    },
                    error: function(jqXHR, exception, errorThrown) {
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
</head>
<body>

<style>
    .tab {
        width: 98%;
        height: 450px;
        border: 1px solid silver;
        border-top: 0px;
        display: none;
        padding: 10px;
        overflow: auto;
    }
</style>

<div id="tab-example">
    <div id="tabs" style="width: 100%; height: 29px;"></div>
    <!-- Server -->
    <div id="tab1" class="tab">
        <!-- SERVER INFORMATION -->
        <table border="0" cellspacing="0" style="border-collapse: collapse" width="100%">
            <tr><!-- ROW 1 -->
                <td width="12%" align="right" valign="top"><b>Server Hostname:</b></td>
                <td width="12%" valign="top" align="center"><span id="hostname"></span></td>

                <td width="12%" align="right" valign="top"><b>OS - Status:</b></td>
                <td width="12%" valign="top" align="center"><span id="os_status"></span></td>

                <td width="12%" align="right" valign="top"><b>Managing Group:</b></td>
                <td width="12%" valign="top" align="center"><span id="managing_group"></span></td>

                <td width="12%" align="right" valign="top"><b>Vendor:</b></td>
                <td width="12%" valign="top" align="center"><span id="vendor"></span></td>
            </tr>
            <tr><!-- ROW 2 -->
                <td width="12%" align="right" valign="top"><b>Approval Status:</b></td>
                <td width="12%" valign="top" align="center"><span id="approval_status"></span></td>

                <td width="12%" align="right" valign="top"><b>Platform - Type:</b></td>
                <td width="12%" valign="top" align="center"><span id="platform_type"></span></td>

                <td width="12%" align="right" valign="top"><b>Serial No:</b></td>
                <td width="12%" valign="top" align="center"><span id="serial_no"></span></td>

                <td width="12%" align="right" valign="top"><b>Model No:</b></td>
                <td width="12%" valign="top" align="center"><span id="model_no"></span></td>
            </tr>
            <tr><!-- ROW 3 -->
                <td width="12%" align="right" valign="top"><b>Gold / Special / Server Guard:</b></td>
                <td width="12%" valign="top" align="center"><span id="gold_special_service_guard"></span></td>

                <td width="12%" align="right" valign="top"><b>CMP Contract:</b></td>
                <td width="12%" valign="top" align="center"><span id="ibm_contract"></span></td>

                <td width="12%" align="right" valign="top"><b>Asset Tag:</b></td>
                <td width="12%" valign="top" align="center"><span id="asset_tag"></span></td>

                <td width="12%" align="right" valign="top"><b>Model:</b></td>
                <td width="12%" valign="top" align="center"><span id="model"></span></td>
            </tr>
            <tr><!-- ROW 4 -->
                <td width="12%" align="right" valign="top"><b>Address:</b></td>
                <td width="12%" valign="top" align="center"><span id="address"></span></td>

                <td width="12%" align="right" valign="top"><b>City:</b></td>
                <td width="12%" valign="top" align="center"><span id="city"></span></td>

                <td width="12%" align="right" valign="top"><b>State:</b></td>
                <td width="12%" valign="top" align="center"><span id="state"></span></td>

                <td width="12%" align="right" valign="top"><b>Floor Room / Grid Location:</b></td>
                <td width="12%" valign="top" align="center"><span id="floor_room_grid_location"></span></td>
            </tr>
            <tr><!-- ROW 5 -->
                <td width="12%" align="right" valign="top"><b>Complex Name:</b></td>
                <td width="12%" valign="top" align="center"><span id="complex_name"></span></td>

                <td width="12%" align="right" valign="top"><b>Complex Parent:</b></td>
                <td width="12%" valign="top" align="center"><span id="complex_parent"></span></td>

                <td width="12%" align="right" valign="top"><b>Complex Children:</b></td>
                <td width="12%" valign="top" align="center" colspan="3"><span id="complex_children"></span></td>
            </tr>
            <tr><!-- ROW 6 -->
                <td width="12%" align="right" valign="top"><b>Install Date:</b></td>
                <td width="12%" valign="top" align="center"><span id="install_date"></span></td>

                <td width="12%" align="right" valign="top"><b>Contract Date:</b></td>
                <td width="12%" valign="top" align="center"><span id="contract_date"></span></td>

                <td width="12%" align="right" valign="top"><b>Maintenance Window:</b></td>
                <td width="12%" valign="top" align="center" colspan="3"><span id="osmaint_window"></span></td>
            </tr>
        </table>
    </div>
    <!-- Connections/Contacts -->
    <div id="tab2" class="tab">
        <table border="1" id="table_system_contacts" cellpadding="2" cellspacing="2" width="100%" style="background-color: white">
            <thead>
            <th bgcolor="blue"><font color="white">Netpin/Members</font></th>
            <th bgcolor="blue"><font color="white">Connections</font></th>
            <th bgcolor="blue"><font color="white">OS</font></th>
            <th bgcolor="blue"><font color="white">Status</font></th>
            <th bgcolor="blue"><font color="white">Approval</font></th>
            <th bgcolor="blue"><font color="white">Group Type</font></th>
            <th bgcolor="blue"><font color="white">Notify Type</font></th>
            <th bgcolor="blue"><font color="white">CSC Support Banners (Primary)</font></th>
            <th bgcolor="blue"><font color="white">Apps/DBMS</font></th>
            </thead>
            <tbody id="tbody_system_contacts"></tbody>
        </table>
    </div>
    <!-- Event Log -->
    <div id="tab3" class="tab">
        <!-- Event Log            - data['events']   -->
        <table border="1" id="table_system_events" cellpadding="2" cellspacing="2" width="100%" style="background-color: white">
            <thead style="background-color: darkgreen; color: white;">
            <th bgcolor="darkgreen"><font color="white">Date</font></th>
            <th bgcolor="darkgreen"><font color="white">Who</font></th>
            <th bgcolor="darkgreen"><font color="white">Event</font></th>
            <th bgcolor="darkgreen"><font color="white">Message</font></th>
            </thead>
            <tbody id="tbody_system_events"></tbody>
        </table>
    </div>
</div>

<script type="text/javascript">
    var config = {
        tabs: {
            name: 'tabs',
            active: 'tab1',
            style: 'background: #ECE9D8',
            tabs: [
                { id: 'tab1', caption: 'Server' },
                { id: 'tab2', caption: 'Connections/Contacts' },
                { id: 'tab3', caption: 'Event Log' },
            ],
            onClick: function (event)
            {
                $('#tab-example .tab').hide();
                $('#tab-example #' + event.target).show();
            }
        }
    }

    $(function ()
    {
        callAjaxSystem('get', <?php echo $parm['system_id']; ?>);
        $('#tabs').w2tabs(config.tabs);
        $('#tab2').show();
        w2ui.tabs.click('tab2');
    });
</script>

</body>
</html>

