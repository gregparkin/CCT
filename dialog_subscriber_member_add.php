<?php
/**
 * dialog_subscriber_member_add.php
 *
 * @package   PhpStorm
 * @file      dialog_subscriber_member_add.php
 * @author    gparkin
 * @date      11/25/16
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
$ora = new oracle();

$lib->debug_start('dialog_subscriber_member_add.html');
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

if (!isset($parm['group_id']))
{
    $lib->debug1(__FILE__, __FUNCTION__, __LINE__, "Syntax error. Missing group_id");
    printf("Missing group_id");
    exit();
}

$group_id = $parm['group_id'];

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

        function callAjaxMemberAdd()
        {
            var data;

            var member_list = document.getElementById("member_list").value;

            //
            // Prepare the data that will be sent to ajax_dialog_system_import.php
            //
            data = {
                "action":                  'add',
                "group_id":                '<?php echo $group_id; ?>',
                "member_list":             member_list
            };

            // alert(JSON.stringify(data));

            var url = 'ajax_dialog_subscriber_members.php';

            $.ajax(
                {
                    type:     "POST",
                    url:      url,
                    dataType: "json",
                    data:     JSON.stringify(data),
                    success:  function(data)
                    {
                        if (data['ajax_status'] != 'SUCCESS')
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

    <center>
        <table border="2" cellpadding="2" cellspacing="2" style="border-color: darkred;">
            <tr>
                <td width="100%" valign="top">
                    <fieldset style="PADDING-BOTTOM: 2px; PADDING-LEFT: 2px; PADDING-RIGHT: 2px; PADDING-TOP: 2px; border-color: white">
                        <legend><font color="#8F4300"><b>Enter one or more CUIDs that have valid email addresses.</b></font></legend>
                        <textarea rows="10" id="member_list" name="member_list" cols="99"
                                  style="width: 98%; resize: none; font-size: 18px;"></textarea>
                    </fieldset>
                </td>
            </tr>
        </table>
        <br>
        <input type="button" name="button" value="Okay"
               title="Lookup matching CUIDs in MNET that have valid email addresses." onclick="callAjaxMemberAdd();">&nbsp;&nbsp;
        <input type="reset" name="button" value="Reset"
               title="Reset selections.">&nbsp;&nbsp;
        <input type="button" name="button" value="Cancel"
               title="Cancel and exit dialog." onclick="parent.postMessage('close w2popup', '*');">
    </center>

</form>
</body>
</html>

