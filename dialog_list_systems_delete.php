<?php
/**
 * dialog_list_systems_delete.php
 *
 * @package   PhpStorm
 * @file      dialog_list_systems_delete.php
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
$lib->debug_start('dialog_list_systems_delete.html');
date_default_timezone_set('America/Denver');

// var url = 'dialog_list_systems_delete.php?list_name_id=' + dialog_list_name_id;

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
        .my_textarea
        {
            border:  1px solid #999999;
            width:   99%;
            margin:  5px 0;
            padding: 3px;
            resize:  none;
        }

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
    </style>
    <script type="text/javascript" src="js/jquery-2.1.4.js"></script>
    <!-- w2ui popup -->
    <!-- link rel="stylesheet" type="text/css" href="css/w2ui-1.4.3.css" / -->
    <!-- script type="text/javascript" src="js/w2ui-1.4.3.js"></script -->

    <link rel="stylesheet" type="text/css" href="css/w2ui-1.5.rc1.css" />
    <script type="text/javascript" src="js/w2ui-1.5.rc1.js"></script>

    <script type="text/javascript">

        function callAjaxTicket())
        {
            $(".loader").show();

            var data;

            //
            // Prepare the data that will be sent to ajax_ticket.php
            //
            data = {
                "list_name_id": <?php echo $list_name_id; ?>
            };

            var url = 'ajax_dialog_system_list_delete.php';

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
    </script>
</head>
<body>
<div class="loader"></div>
<form name="f1">
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
    b {
        font-size: 13px;
    }
    textarea {
        font-size: 12px;
    }
    select {
        font-size: 12px;
    }
</style>

    <table bgcolor="#ECE9D8" width="100%" cellspacing="2" cellpadding="2" style="color: black">
        <tr>
            <td align="center">
                <p>Confirm that you want to remove this list.</p>
            </td>
        </tr>
        <tr>
            <td align="center">
                <input type="button" value="Delete" onclick="callAjaxTicket();">
            </td>
        </tr>
    </table>

</form>
</body>
</html>
