<?php
/**
 * dialog_subscriber_group.php
 *
 * @package   PhpStorm
 * @file      dialog_subscriber_group.php
 * @author    gparkin
 * @date      11/21/16
 * @version   7.0
 *
 * @brief     Dialog box used for managing subscriber groups. Actions: add, edit, delete
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
$lib->debug_start('dialog_subscriber_group.html');
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

if (!isset($parm['action']))
{
    $lib->debug1(__FILE__, __FUNCTION__, __LINE__, "Syntax error. Missing action");
    printf("Missing action");
    exit();
}

$action       = $parm['action'];          // add, edit, delete
$group_id     = trim($parm['group_id']);
$group_name   = '';

$ora = new oracle();

if (strlen($group_id) > 0)
{
    $query = sprintf("select * from cct7_subscriber_groups where group_id = '%s'", $group_id);

    if ($ora->sql($query) == false)
    {
        $lib->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $ora->sql_statement);
        $lib->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $ora->dbErrMsg);
        exit();
    }

    if ($ora->fetch() == false)
    {
        $lib->debug1(__FILE__, __FUNCTION__, __LINE__, "Record no longer available for group_id = '%s'", $group_id);
        exit();
    }

    $group_name = $ora->group_name;
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
            background:       url('images/Preloader_93.gif') 50% 50% no-repeat rgb(249,249,249);
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
    <script type="text/javascript" src="js/jquery-2.1.4.js"></script>
    <!-- w2ui popup -->
    <!-- link rel="stylesheet" type="text/css" href="css/w2ui-1.4.3.css" / -->
    <!-- script type="text/javascript" src="js/w2ui-1.4.3.js"></script -->

    <link rel="stylesheet" type="text/css" href="css/w2ui-1.5.rc1.css" />
    <script type="text/javascript" src="js/w2ui-1.5.rc1.js"></script>

    <script type="text/javascript">

        function callAjax(action, group_id)
        {
            $(".loader").show();

            //alert('dialog_subscriber_group, action = ' + action + ', group_id = ' + group_id);

            var data;

            var group_name = document.getElementById('group_name').value;

            //
            // Prepare the data that will be sent to ajax_dialog_subscriber_group.php
            //
            data = {
                "action":            action,
                "group_id":          group_id,
                "group_name":        group_name
            };

            //alert(JSON.stringify(data));

            var url = 'ajax_dialog_subscriber_group.php';

            $.ajax(
                {
                    type:     "POST",
                    url:      url,
                    dataType: "json",
                    data:     JSON.stringify(data),
                    success:  function(data)
                    {
                        $(".loader").fadeOut("slow");

                        if (data['ajax_status'] != 'SUCCESS')
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
            // add, edit, delete

            //alert('buttonClick: ' + ele.value);

            switch ( ele.value )
            {
                case 'Delete':
                    callAjax('delete', '<?php echo $group_id; ?>');
                    break;
                case 'Add':  // Create new list
                    callAjax('add',    '<?php echo $group_id; ?>');
                    break;
                case 'Save': // Save new or changed list name
                    callAjax('save',   '<?php echo $group_id; ?>');
                    break;
                default:
                    alert('No logic coded for this button: ' + ele.value);
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
                <td align="right" valign="middle" width="18%"><b>Group Name:</b></td>
                <td align="left" valign="top" width="72%">
                    <input title="Type in some text you want to identify with this list."
                           style="width: 99%" size="30" maxlength="80"
                           type="text" name="group_name" id="group_name"
                           value="<?php echo $group_name; ?>">
                </td>
            </tr>
            <tr>
                <td align="center" colspan="2">
                    <?php
                        // add, edit, delete
                        if ($action == "delete")
                        {
                            echo "<input title=\"Click to delete this list.\" .
                                class=\"delete\" type=\"button\" name=\"delete\" value=\"Delete\" onclick=\"buttonClick(this);\">";
                        }
                        else if ($action == "add")
                        {
                            echo "<input title=\"Click to create this list.\" .
                                class=\"add\"    type=\"button\" name=\"add\"    value=\"Add\"    onclick=\"buttonClick(this);\">";
                        }
                        else // edit
                        {
                            echo "<input title=\"Click to save this list name.\" .
                                class=\"save\"   type=\"button\" name=\"save\"   value=\"Save\"   onclick=\"buttonClick(this);\">";
                        }
                    ?>
                    <input type="button" value="Cancel" onclick="parent.postMessage('close w2popup', '*');">
                </td>
            </tr>
        </table>
    </div>
</form>
</body>
</html>
