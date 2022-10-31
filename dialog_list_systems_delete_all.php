<?php
/**
 * dialog_list_systems_delete_all.php
 *
 * @package   PhpStorm
 * @file      dialog_list_systems_delete_all.php
 * @author    gparkin
 * @date      6/30/16
 * @version   7.0
 *
 * @brief     Dialog box used for approving work request and viewing connection information.
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

$ora = new oracle();

//
// NOTE: It is very important that you do not turn on debugging without writing to a file for server side AJAX code. Any HTML
//       comments coming from functions while writing XML will show up in the XML output and you will get a XML parsing error
//       in the client side program.
//
$lib = new library();  // classes/library.php
$lib->debug_start('dialog_list_systems_delete_all.html');
date_default_timezone_set('America/Denver');

// var url = 'dialog_list_systems_delete_all.php?list_name_id=' + dialog_list_name_id;

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

$query = "select * from cct7_list_names where list_name_id = " . $list_name_id;

if ($ora->sql2($query) == false)
{
    $lib->debug1(__FILE__, __FUNCTION__, __LINE__, "%s - %s", $ora->sql_statement, $ora->dbErrMsg);
    printf("List no longer exists.  list_name_id = %d", $list_name_id);
    exit();
}

if ($ora->fetch() == false)
{
    $lib->debug1(__FILE__, __FUNCTION__, __LINE__,
             "cct7_list_names record where list_name_id = %d no longer exists", $list_name_id);
    printf("List no longer exists.  list_name_id = %d", $list_name_id);
    exit();
}

$list_name = $ora->list_name;

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
    </style>
    <script type="text/javascript" src="js/jquery-2.1.4.js"></script>
    <!-- w2ui popup -->
    <!-- link rel="stylesheet" type="text/css" href="css/w2ui-1.4.3.css" / -->
    <!-- script type="text/javascript" src="js/w2ui-1.4.3.js"></script -->

    <link rel="stylesheet" type="text/css" href="css/w2ui-1.5.rc1.css" />
    <script type="text/javascript" src="js/w2ui-1.5.rc1.js"></script>
    <script type="text/javascript">

        function deleteAll()
        {
            var data;
            var list_name_id = <?php echo $list_name_id; ?>;

            $(".loader").show();

            //
            // Prepare the data that will be sent to ajax_ticket.php
            //
            data = {
                "list_name_id": list_name_id
            };

            var url = 'ajax_dialog_list_systems_delete_all.php';

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

        $(window).load(function()
        {
            $(".loader").fadeOut("slow");
        });
    </script>

</head>
<body>

<form name="f1">
    <table bgcolor="#ECE9D8" width="100%" cellspacing="2" cellpadding="2" style="color: black">
        <tr>
            <td align="center">
                <p>Confirm that you want to remove all servers for list: <?php echo $list_name; ?></p>
            </td>
        </tr>
        <tr>
            <td align="center">
                <a class="green" title="Delete all servers from this list?"
                   href="#" onclick="deleteAll()">Yes</a>
                <a class="red" title="No I do not want to remove any servers. Close this dialog."
                   href="#" onclick="parent.postMessage('close w2popup', '*');">No</a>
            </td>
        </tr>
    </table>
</form>
</body>
</html>
