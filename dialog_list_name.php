<?php
/**
 * dialog_list_name.php
 *
 * @package   PhpStorm
 * @file      dialog_list_name.php
 * @author    gparkin
 * @date      07/09/16
 * @version   7.0
 *
 * @brief     Dialog box used for adding and edit a server list name. (Used in toolbar_server_lists.php)
 *
 */

//$path = '/usr/lib/pear';
//set_include_path(get_include_path() . PATH_SEPARATOR . $path);

set_include_path("/opt/ibmtools/www/cct7/classes");

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
$lib->debug_start('dialog_add_list_name.html');
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

if (!isset($parm['list_name_id']))
{
    $lib->debug1(__FILE__, __FUNCTION__, __LINE__, "Syntax error. Missing list_name_id");
    printf("Missing list_name_id");
    exit();
}

if (!isset($parm['action']))
{
    $lib->debug1(__FILE__, __FUNCTION__, __LINE__, "Syntax error. Missing action");
    printf("Missing action");
    exit();
}

$action       = $parm['action'];          // remove, add, save
$list_name_id = $parm['list_name_id'];
$list_name    = '';

$ora = new oracle();

if ($list_name_id > 0)
{
    $query = sprintf("select * from cct7_list_names where list_name_id = %d", $list_name_id);

    if ($ora->sql($query) == false)
    {
        $lib->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $ora->sql_statement);
        $lib->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $ora->dbErrMsg);
        exit();
    }

    if ($ora->fetch() == false)
    {
        $lib->debug1(__FILE__, __FUNCTION__, __LINE__, "Record no longer available for list_name_id = %d", $list_name_id);
        exit();
    }

    $list_name = $ora->list_name;
}

?>

<!DOCTYPE html>
<html>
<head>
    <title>CCT Ticket</title>

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

        b {
            font-size: 13px;
        }

        textarea {
            font-size: 12px;
        }

        select {
            font-size: 12px;
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
    <script type="text/javascript" src="js/jquery-2.1.4.js"></script>
    <!-- w2ui popup -->
    <!-- link rel="stylesheet" type="text/css" href="css/w2ui-1.4.3.css" / -->
    <!-- script type="text/javascript" src="js/w2ui-1.4.3.js"></script -->

    <link rel="stylesheet" type="text/css" href="css/w2ui-1.5.rc1.css" />
    <script type="text/javascript" src="js/w2ui-1.5.rc1.js"></script>

    <script type="text/javascript">

        function callAjax(action, list_name_id)
        {
            $(".loader").show();

            var data;

            var list_name = document.getElementById('list_name').value;

            //
            // Prepare the data that will be sent to ajax_dialog_list_name.php
            //
            data = {
                "action":            action,
                "list_name_id":      list_name_id,
                "list_name":         list_name
            };

            var url = 'ajax_dialog_list_name.php';

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

                        // Instruct parent to close this window.
                        parent.postMessage('close w2popup', "*");
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

        function buttonClick(ele)
        {
            switch ( ele.id )
            {
                case 'delete':
                    callAjax('remove', <?php echo $list_name_id; ?>);
                    break;
                case 'add':  // Create new list
                    callAjax('add',    <?php echo $list_name_id; ?>);
                    break;
                case 'save': // Save new or changed list name
                    callAjax('save',   <?php echo $list_name_id; ?>);
                    break;
                default:
                    alert('No logic coded for this button: ' + ele.id);
                    break;
            }
        }
    </script>
    <script type="text/javascript">
        $(window).load(function()
        {
            $(".loader").fadeOut("slow");
        });
    </script>
</head>
<body>
<div class="loader"></div>
<form name="f1">
    <div id="divit">
        <table bgcolor="#ECE9D8" width="100%" cellspacing="6" cellpadding="6" style="color: black">
            <tr>
                <td align="right" valign="middle" width="15%"><b>List Name:</b></td>
                <td align="left" valign="top" width="75%">
                    <input title="Type in some text you want to identify with this list."
                           style="width: 99%" size="30" maxlength="80"
                           type="text" name="list_name" id="list_name"
                           value="<?php echo $list_name; ?>">
                </td>
            </tr>
            <tr>
                <td align="center" colspan="2">
                    <?php
                        if ($action == "remove")
                        {
                            ?>
                            <a class="red" title="Click to remove this list." id="delete"
                               href="#" onclick="buttonClick(this);">Delete</a>
                            <?php
                        }
                        else if ($action == "add")
                        {
                            ?>
							<a class="green" title="Click to create this list." id="add"
                               href="#" onclick="buttonClick(this);">Add</a>
                            <?php
                        }
                        else
                        {
                            ?>
							<a class="green" title="Click to save this list name." id="save"
                               href="#" onclick="buttonClick(this);">Save</a>
                            <?php
                        }
                    ?>
                    <a class="purple" title="Cancel and close this dialog."
                       href="#" onclick="parent.postMessage('close w2popup', '*');">Cancel</a>
                </td>
            </tr>
        </table>
    </div>
</form>
</body>
</html>
