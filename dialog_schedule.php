<?php
/**
 * dialog_schedule.php
 *
 * @package   PhpStorm
 * @file      dialog_schedule.php
 * @author    gparkin
 * @date      12/07/16
 * @version   7.0
 *
 * @brief     Dialog box used to list server log messages. See toolbar_schedule.php
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

$lib->debug_start('dialog_schedule.html');

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

if (!isset($parm['list']))
{
    $lib->debug1(__FILE__, __FUNCTION__, __LINE__, "Syntax error. Missing list");
    printf("Missing list of system_id's");
    exit();
}

$list = $parm['list'];

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

<form name="f1" method="post" action="#">
    <center>
        <table border="0" cellpadding="2" cellspacing="2" style="border-color: darkred;">
            <tr>
                <td width="100%" valign="top">
                    <b>Log Messages for Selected Servers</b><br>
                    <textarea rows="13" id="log_messages" name="log_messages" cols="125"
                              style="width: 98%; resize: none; font-size: 12px">
                    </textarea>
                </td>
            </tr>
            <tr>
                <td width="100%" valign="top">
                    <b>Additional Paging Text</b><br>
                    <textarea rows="13" id="message" name="message" cols="125"
                              style="width: 98%; resize: none; font-size: 12px"
                              onKeyDown="textCounter(this.form.message,this.form.remLen,600);"
                              onKeyUp="textCounter(this.form.message,this.form.remLen,600);"></textarea><br>
                    <input readonly type="text" name="remLen" size="3" maxlength="3" value="0"> Characters left.
                    <p align="center" style="color: blue;">Pages will only go out to current on-call personal desiring notification.</p>
                </td>
            </tr>
            <tr>
                <td width="100%" align="center" valign="top">
                    <a class="blue" title="Mark as work starting. Page on-call desiring notification and send out email."
                       href="#" onclick="callAjax('starting');">Starting</a>
                    <a class="green" title="Mark work as successfully completed. Page on-call desiring notification and send out email."
                       href="#" onclick="callAjax('success');">Success</a>
                    <a class="red" title="Mark work as failed to complete. Backout was completed. Page on-call desiring notification and send out email.."
                       href="#" onclick="callAjax('failed');">Failed</a>
                    <a class="brown" title="Mark work as canceled. Send email notification to contacts."
                       href="#" onclick="callAjax('canceled');">Canceled</a>
                    <a class="purple" title="Close this dialog box."
                       href="#" onclick="parent.postMessage('close w2popup', '*');">Close</a>
                </td>
            </tr>
        </table>
    </center>

<script type="text/javascript">

    //
    // Add onClick="self.close();" for links that need to close a window
    //
    function textCounter(field, countfield, maxlimit)
    {
        if (field.value.length > maxlimit) // if too long...trim it!
            field.value = field.value.substring(0, maxlimit);
        else
            countfield.value = maxlimit - field.value.length;
    }

    function callAjax(action)
    {
        var data;

        var message = document.getElementById('message').value;

        //
        // Prepare the data that will be sent to ajax_dialog_system_import.php
        //
        data = {
            "action":         action,
            "list_system_id": '<?php echo $list; ?>',
            "message":        message
        };

        // alert(JSON.stringify(data));

        var url = 'ajax_dialog_schedule.php';

        $.ajax(
            {
                type: "POST",
                url: url,
                dataType: "json",
                data: JSON.stringify(data),
                success: function (data)
                {
                    if (data['ajax_status'] != 'SUCCESS')
                    {
                        alert(data['ajax_message']);
                        // Instruct parent to close this window.
                        parent.postMessage('close w2popup', "*");
                    }

                    if (action != 'log')
                    {
                        parent.postMessage('close w2popup', "*");
                    }

                    document.getElementById("log_messages").value = data['ajax_message'];
                },
                error: function (jqXHR, exception, errorThrown)
                {
                    if (jqXHR.status === 0)
                    {
                        alert('Not connect.\n Verfiy Network.');
                    }
                    else if (jqXHR.status == 404)
                    {
                        alert('Requested page not found. [404]');
                    }
                    else if (jqXHR.status == 500)
                    {
                        alert('Internal Server Error [500]');
                    }
                    else if (exception === 'parsererror')
                    {
                        alert('Requested JSON parse failed.' + ' Error code: ' + errorThrown + ' ResponseText: ' + jqXHR.responseText);
                    }
                    else if (exception === 'timeout')
                    {
                        alert('Time out error.');
                    }
                    else if (exception === 'abort')
                    {
                        alert('Ajax request aborted.');
                    }
                    else
                    {
                        alert('Uncaught Error.\n' + jqXHR.responseText);
                    }
                }
            }
        );
    }

    callAjax('log');
    textCounter(document.f1.message,document.f1.remLen,600);

</script>
</form>
</body>
</html>

