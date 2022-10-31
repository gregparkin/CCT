<?php
/**
 * dialog_netpin_override_detail.php
 *
 * @package   PhpStorm
 * @file      dialog_netpin_override_detail.php
 * @author    gparkin
 * @date      07/23/2017
 * @version   7.0
 *
 * @brief     Called by toolbar_override_netpins.php to add one or more members to cct7_override_members
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

$lib->debug_start('dialog_netpin_override_detail.html');

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

//
// cct7_override_members
// member_id|NUMBER|0|NOT NULL|PK: Unique record ID
// netpin_id|NUMBER|0||FK: cct7_override_netpins.netpin_id
// create_date|NUMBER|0||Date record was created. (GMT unix timestamp)
// create_cuid|VARCHAR2|20||CUID of person who created this record
// create_name|VARCHAR2|200||Name of person who created this record
// member_cuid|VARCHAR2|20||CUID of person who will receive notifications
// member_name|VARCHAR2|200||Name of person who will receive notifications
//

if (!isset($parm['netpin_id']))
{
    $lib->debug1(__FILE__, __FUNCTION__, __LINE__, "Syntax error. Missing netpin_id");
    printf("Missing netpin_id");
    exit();
}

$netpin_id = $parm['netpin_id'];

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

        function callAjaxAddMembers()
        {
            var data;
            var netpin_id               =  <?php echo $netpin_id; ?>;
            var target_theses_only      = '';

            target_theses_only = document.getElementById("target_theses_only").value;

            //
            // Prepare the data that will be sent to ajax_dialog_system_import.php
            //
            data = {
                "netpin_id":               netpin_id,
                "target_these_only":       target_theses_only
            };

            //alert(JSON.stringify(data));

            var url = 'ajax_dialog_netpin_override_add.php';

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

        function clearForm()
        {
            document.getElementById("target_theses_only").value = '';
        }
    </script>
</head>
<body>

<style>
    .my_textarea
    {
        border:  1px solid #999999;
        width:   98%;
        margin:  5px 0;
        padding: 3px;
        resize:  none;
        font-size: 13px;
    }
    }
</style>

<form name="f1" method="post" action="#">
    <center>
        <table bgcolor="#ECE9D8" width="100%" cellspacing="2" cellpadding="2" style="height: 100%; color: black">
            <tr>
                <td width="100%" valign="top">
                    <fieldset style="PADDING-BOTTOM: 2px; PADDING-LEFT: 2px; PADDING-RIGHT: 2px; PADDING-TOP: 2px; border-color: white">
                        <legend><font color="#8F4300"><b>Type in one or more member CUID's</b></font></legend>
                        <textarea rows="10" class="my_textarea" id="target_theses_only" name="target_theses_only" cols="20"></textarea>
                    </fieldset>
                </td>
            </tr>
        </table>
        <br>
        <a class="green" title="Add these member cuids to the override list."
           href="#" onclick="callAjaxAddMembers();">Add</a>
        <a class="brown" title="Reset (clear) all selections you may have typed."
           href="#" onclick="clearForm();">Reset</a>
        <a class="purple" title="Cancel and close this dialog."
           href="#" onclick="parent.postMessage('close w2popup', '*');">Cancel</a>
    </center>
</form>
<script type="text/javascript">
    document.getElementById("target_theses_only").focus();
</script>
</body>
</html>

