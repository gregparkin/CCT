<?php
/**
 * dialog_list_systems_import.php
 *
 * @package   PhpStorm
 * @file      dialog_list_systems_import.php
 * @author    gparkin
 * @date      09/01/16
 * @version   7.0
 *
 * @brief     Dialog box used to import servers into server lists. See toolbar_server_list.php
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
date_default_timezone_set('America/Denver');

$ora = new oracle();

$lib->debug_start('dialog_system_import.html');

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

if (!isset($parm['list_name_id']))
{
    $lib->debug1(__FILE__, __FUNCTION__, __LINE__, "Syntax error. Missing list_name_id");
    printf("Missing list_name_id");
    exit();
}

$list_name_id = $parm['list_name_id'];

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
    <style>
        .loader
        {
            position:         fixed;
            left:             0px;
            top:              0px;
            width:            100%;
            height:           100%;
            z-index:          9999;
            background:       url('images/gears_animated.gif') 50% 50% no-repeat rgb(249,249,249);
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

    <script type="text/javascript">

        function callAjaxSystemImport()
        {
            var data;

            var computer_managing_group = new Array();
            var computer_os_lite        = new Array();
            var computer_status         = new Array();
            var computer_contract       = new Array();
            var state_and_city          = new Array();
            var miscellaneous           = new Array();
            var target_theses_only      = '';
            var ip_starts_with          = '';

            var i;

            if ((x = document.getElementById("computer_managing_group")) != null)
            {
                for (i=0; i<x.options.length; i++)
                {
                    if (x.options[i].selected == true)
                    {
                        computer_managing_group.push(x.options[i].value);
                    }
                }
            }

            if ((x = document.getElementById("computer_os_lite")) != null)
            {
                for (i=0; i<x.options.length; i++)
                {
                    if (x.options[i].selected == true)
                    {
                        computer_os_lite.push(x.options[i].value);
                    }
                }
            }

            if ((x = document.getElementById("computer_status")) != null)
            {
                for (i=0; i<x.options.length; i++)
                {
                    if (x.options[i].selected == true)
                    {
                        computer_status.push(x.options[i].value);
                    }
                }
            }

            if ((x = document.getElementById("computer_contract")) != null)
            {
                for (i=0; i<x.options.length; i++)
                {
                    if (x.options[i].selected == true)
                    {
                        computer_contract.push(x.options[i].value);
                    }
                }
            }

            if ((x = document.getElementById("state_and_city")) != null)
            {
                for (i=0; i<x.options.length; i++)
                {
                    if (x.options[i].selected == true)
                    {
                        state_and_city.push(x.options[i].value);
                    }
                }
            }

            if ((x = document.getElementById("miscellaneous")) != null)
            {
                for (i=0; i<x.options.length; i++)
                {
                    if (x.options[i].selected == true)
                    {
                        miscellaneous.push(x.options[i].value);
                    }
                }
            }

            target_theses_only = document.getElementById("target_theses_only").value;
            ip_starts_with     = document.getElementById("ip_starts_with").value;

            //
            // Prepare the data that will be sent to ajax_dialog_system_import.php
            //
            data = {
                "list_name_id":            <?php echo $list_name_id; ?>,
                "computer_managing_group": computer_managing_group,
                "computer_os_lite":        computer_os_lite,
                "computer_status":         computer_status,
                "computer_contract":       computer_contract,
                "state_and_city":          state_and_city,
                "miscellaneous":           miscellaneous,
                "target_these_only":       target_theses_only,
                "ip_starts_with":          ip_starts_with
            };

            //alert(JSON.stringify(data));

            var url = 'ajax_dialog_system_list_import.php';

            $.ajax(
                {
                    type:     "POST",
                    url:      url,
                    dataType: "json",
                    data:     JSON.stringify(data),
                    success:  function(data)
                    {
                        alert(data['ajax_message']);

                        // Instruct parent to close this window.
                        parent.postMessage('close w2popup', "*");
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

        function clearForm(myFormElement)
        {

            var elements = myFormElement.elements;

            myFormElement.reset();

            for (i=0; i<elements.length; i++)
            {

                field_type = elements[i].type.toLowerCase();

                switch(field_type)
                {
                    case "text":
                    case "password":
                    case "textarea":
                    case "hidden":

                        elements[i].value = "";
                        break;

                    case "radio":
                    case "checkbox":
                        if (elements[i].checked)
                        {
                            elements[i].checked = false;
                        }
                        break;

                    case "select-one":
                    case "select-multi":
                        elements[i].selectedIndex = -1;
                        break;

                    default:
                        break;
                }
            }
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

<form name="f1" method="post" action="#">
<div id="tab-example">
    <div id="tabs" style="width: 100%; height: 29px;"></div>
    <center>
    <table border="2" cellpadding="2" cellspacing="2" style="border-color: darkred;">
        <tr>
            <td>
                <div id="system_tab">
                    <div id="tabs2" style="width: 100%; height: 29px;"></div>
                    <!-- Asset Manager -->
                    <div id="stab1" class="tab" style="width: 1194px; height: 485px;">
                        <table bgcolor="#ECE9D8" width="100%" ellspacing="2" cellpadding="2" style="height: 100%; color: black">
                            <tr>
                                <td width="33%" align="left">
                                    <fieldset style="PADDING-BOTTOM: 2px; PADDING-LEFT: 2px; PADDING-RIGHT: 2px; PADDING-TOP: 2px; width: 27em; border-color: white">
                                        <legend><font color="#8f4300"><b>Managing Groups</b></font></legend>
                                            <select style="padding: 2; width: 30em; height: 16em; font-size: 12px;"
                                                    id="computer_managing_group"
                                                    name="computer_managing_group[]" multiple="multiple" size="8">
                                                <?php
                                                if ($ora->sql("select * from cct7_managing_group order by computer_managing_group") == false)
                                                {
                                                    $lib->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $ora->sql_statement);
                                                    $lib->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $ora->dbErrMsg);
                                                    $lib->html_stop(__FILE__, __FUNCTION__, __LINE__, "SQL Error: %s. Please contact Greg Parkin", $ora->dbErrMsg);
                                                }

                                                while ($ora->fetch())
                                                {
                                                    printf("<option value=\"%s\">%s</option>\n", $ora->computer_managing_group, $ora->computer_managing_group);
                                                }
                                                ?>
                                            </select>
                                    </fieldset>
                                </td>
                                <td width="33%" align="left">
                                    <fieldset style="PADDING-BOTTOM: 2px; PADDING-LEFT: 2px; PADDING-RIGHT: 2px; PADDING-TOP: 2px; width: 27em; border-color: white">
                                        <legend><font color="#8f4300"><b>Operating Systems</b></font></legend>
                                            <select style="padding: 2; width: 30em; height: 16em; font-size: 12px;"
                                                    id="computer_os_lite"
                                                    name="computer_os_lite[]" multiple="multiple" size="8">
                                                <?php
                                                if ($ora->sql("select * from cct7_os_lite order by computer_os_lite") == false)
                                                {
                                                    $lib->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $ora->sql_statement);
                                                    $lib->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $ora->dbErrMsg);
                                                    $lib->html_stop(__FILE__, __FUNCTION__, __LINE__, "SQL Error: %s. Please contact Greg Parkin", $ora->dbErrMsg);
                                                }

                                                while ($ora->fetch())
                                                {
                                                    printf("<option value=\"%s\">%s</option>\n", $ora->computer_os_lite, $ora->computer_os_lite);
                                                }
                                                ?>
                                            </select>
                                    </fieldset>
                                </td>
                                <td width="33%" align="left">
                                    <fieldset style="PADDING-BOTTOM: 2px; PADDING-LEFT: 2px; PADDING-RIGHT: 2px; PADDING-TOP: 2px; width: 27em; border-color: white">
                                        <legend><font color="#8f4300"><b>System Status</b></font></legend>
                                            <select style="padding: 2; width: 30em; height: 16em; font-size: 12px;"
                                                    id="computer_status"
                                                    name="computer_status[]" multiple="multiple" size="8">
                                                <?php
                                                if ($ora->sql("select * from cct7_computer_status order by computer_status") == false)
                                                {
                                                    $lib->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $ora->sql_statement);
                                                    $lib->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $ora->dbErrMsg);
                                                    $lib->html_stop(__FILE__, __FUNCTION__, __LINE__, "SQL Error: %s. Please contact Greg Parkin", $ora->dbErrMsg);
                                                }

                                                while ($ora->fetch())
                                                {
                                                    printf("<option value=\"%s\">%s</option>\n", $ora->computer_status, $ora->computer_status);
                                                }
                                                ?>
                                            </select>
                                    </fieldset>
                                </td>
                            </tr>
                            <!-- Asset Manager - Second Row -->
                            <tr>
                                <td width="33%" align="left">
                                    <fieldset style="PADDING-BOTTOM: 2px; PADDING-LEFT: 2px; PADDING-RIGHT: 2px; PADDING-TOP: 2px; width: 27em; border-color: white">
                                        <legend><font color="#8f4300"><b>IGS Contracts</b></font></legend>
                                            <select style="padding: 2; width: 30em; height: 16em; font-size: 12px;"
                                                    id="computer_contract"
                                                    name="computer_contract[]" multiple="multiple" size="8">
                                                <?php
                                                if ($ora->sql("select * from cct7_contract order by computer_contract") == false)
                                                {
                                                    $lib->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $ora->sql_statement);
                                                    $lib->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $ora->dbErrMsg);
                                                    $lib->html_stop(__FILE__, __FUNCTION__, __LINE__, "SQL Error: %s. Please contact Greg Parkin", $ora->dbErrMsg);
                                                }

                                                while ($ora->fetch())
                                                {
                                                    printf("<option value=\"%s\">%s</option>\n", $ora->computer_contract, $ora->computer_contract);
                                                }
                                                ?>
                                            </select>
                                    </fieldset>
                                </td>
                                <td width="33%" align="left">
                                    <fieldset style="PADDING-BOTTOM: 2px; PADDING-LEFT: 2px; PADDING-RIGHT: 2px; PADDING-TOP: 2px; width: 27em; border-color: white">
                                        <legend><font color="#8f4300"><b>State and City</b></font></legend>
                                            <select style="padding: 2; width: 30em; height: 16em; font-size: 12px;"
                                                    id="state_and_city"
                                                    name="state_and_city[]" multiple="multiple" size="8">
                                                <?php
                                                if ($ora->sql("select * from cct7_state_city order by computer_state, computer_city") == false)
                                                {
                                                    $lib->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $ora->sql_statement);
                                                    $lib->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $ora->dbErrMsg);
                                                    $lib->html_stop(__FILE__, __FUNCTION__, __LINE__, "SQL Error: %s. Please contact Greg Parkin", $ora->dbErrMsg);
                                                }

                                                while ($ora->fetch())
                                                {
                                                    printf("<option value=\"%s:%s\">%s - %s</option>\n", $ora->computer_state, $ora->computer_city, $ora->computer_state, $ora->computer_city);
                                                }
                                                ?>
                                            </select>
                                    </fieldset>
                                </td>
                                <td width="33%" align="left">
                                    <fieldset style="PADDING-BOTTOM: 2px; PADDING-LEFT: 2px; PADDING-RIGHT: 2px; PADDING-TOP: 2px; width: 27em; border-color: white">
                                        <legend><font color="#8f4300"><b>Miscellaneous</b></font></legend>
                                            <select style="padding: 2; width: 30em; height: 16em; font-size: 12px;"
                                                    id="miscellaneous"
                                                    name="miscellaneous[]" multiple="multiple" size="8">
                                                <option value="BCR:GOLD"        >GOLD</option>
                                                <option value="BCR:NOT-GOLD"    >NOT-GOLD</option>
                                                <option value="BCR:BLUE"        >BLUE</option>
                                                <option value="BCR:SILVER"      >SILVER</option>
                                                <option value="BCR:BRONZE"      >BRONZE</option>
                                                <option value="BCR:NO-COLOR"    >NO-COLOR</option>
                                                <option value="SPECIAL:HANDLING">SPECIAL HANDLING</option>
                                                <?php
                                                if ($ora->sql("select * from cct7_platform where computer_platform is not null order by computer_platform") == false)
                                                {
                                                    $lib->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $ora->sql_statement);
                                                    $lib->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $ora->dbErrMsg);
                                                    $lib->html_stop(__FILE__, __FUNCTION__, __LINE__, "SQL Error: %s. Please contact Greg Parkin", $ora->dbErrMsg);
                                                }

                                                while ($ora->fetch())
                                                {
                                                    printf("<option value=\"PLATFORM:%s\">%s</option>\n", $ora->computer_platform, $ora->computer_platform);
                                                }
                                                ?>
                                            </select>
                                    </fieldset>
                                </td>
                            </tr>
                            <tr>
                                <td align="left" colspan="2">
                                    Where the IP Address Starts with: <input type="text" id="ip_starts_with" name="ip_starts_with" size="20" maxlength="20" style="width: 200px;" />
                                </td>
                            </tr>
                        </table>
                    </div>
                    <!-- Hosts, Applications, Databases -->
                    <div id="stab2" class="tab" style="width: 1194px; height: 485px;">
                        <table bgcolor="#ECE9D8" width="100%" ellspacing="2" cellpadding="2" style="height: 100%; color: black">
                            <tr>
                                <td width="100%" valign="top">
                                    <fieldset style="PADDING-BOTTOM: 2px; PADDING-LEFT: 2px; PADDING-RIGHT: 2px; PADDING-TOP: 2px; border-color: white">
                                        <legend><font color="#8F4300"><b>Target Hosts, Application Names and Database Names</b></font></legend>
                                        <textarea rows="33" id="target_theses_only" name="target_theses_only" cols="99" style="width: 99%"></textarea>
                                    </fieldset>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
            </td>
        </tr>
    </table>
    <br>
    <a class="green" title="Import from asset manager or add the servers you have manually typed."
       href="#" onclick="callAjaxSystemImport();">Add</a>
    <a class="brown" title="Reset (clear) all selections and server names you may have typed."
       href="#" onclick="clearForm(f1);">Reset</a>
    <a class="purple" title="Cancel and close this dialog."
       href="#" onclick="parent.postMessage('close w2popup', '*');">Cancel</a>
    </center>
</div>

<script type="text/javascript">
    var config2 = {
        tabs: {
            name: 'tabs2',
            active: 'stab1',
            style: 'background: #ECE9D8',
            tabs: [
                { id: 'stab1', caption: '<b>Asset Manager</b>' },
                { id: 'stab2', caption: '<b>Hosts, Applications, Databases</b>' }
            ],
            onClick: function (event)
            {
                $('#system_tab .tab').hide();
                $('#system_tab #' + event.target).show();
            }
        }
    };

    $(function ()
    {
        $('#tabs2').w2tabs(config2.tabs);
        //$('#stab1').show();
        w2ui.tabs2.click('stab1');
    });
</script>
</form>
</body>
</html>

