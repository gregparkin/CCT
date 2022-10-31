<?php
/**
 * dialog_open_ticket_sort.php
 *
 * @package   PhpStorm
 * @file      dialog_open_ticket_sort.php
 * @author    gparkin
 * @date      7/9/17
 * @version   7.0
 *
 * @brief     Used to prompt users for sort order in the toolbar_open.php grid.
 *            Data is copied to $_SESSION['ticket_sort_order'] cache.
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
$lib->debug_start('dialog_open_ticket_sort.html');
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
?>

<!DOCTYPE html>
<html>
<head>
    <title>Open Ticket - Sort Order</title>
    <script src="js/jquery-2.1.4.js"></script>
    <!-- w2ui popup -->
    <!-- link rel="stylesheet" type="text/css" href="css/w2ui-1.4.3.css" / -->
    <!-- script type="text/javascript" src="js/w2ui-1.4.3.js"></script -->

    <link rel="stylesheet" type="text/css" href="css/w2ui-1.5.rc1.css" />
    <script type="text/javascript" src="js/w2ui-1.5.rc1.js"></script>
</head>
<body>
<center>
<table border="0" cellspacing="4" cellpadding="4">
    <tr>
        <td align="center" colspan="2">
            <h2>Choose your column sort order.</h2>
        </td>
    </tr>
    <tr>
        <td align="center" valign="top">
            <select id="sbOne" multiple="multiple" size="11" style="width: 200px;">
                <option value="cm_ticket_no asc"        >CM Ticket # (ascend)</option>
                <option value="cm_ticket_no desc"       >CM Ticket # (decend)</option>
                <option value="ticket_no asc"           >CCT Ticket # (ascend)</option>
                <option value="ticket_no desc"          >CCT Ticket # (decend)</option>
                <option value="work_activity asc"       >Work Activity (ascend)</option>
                <option value="work_activity desc"      >Work Activity (decend)</option>
                <option value="status asc"              >Status (ascend)</option>
                <option value="status desc"             >Status (decend)</option>
                <option value="insert_date asc"         >Create Date (ascend)</option>
                <option value="insert_date desc"        >Create Date (decend)</option>
                <option value="owner_name asc"          >Ticket Owner (ascend)</option>
                <option value="owner_name desc"         >Ticket Owner (decend)</option>
                <option value="schedule_start_date asc" >Schedule From (ascend)</option>
                <option value="schedule_start_date desc">Schedule From (decend)</option>
                <option value="schedule_end_date asc"   >Schedule To (ascend)</option>
                <option value="schedule_end_date desc"  >Schedule To (decend)</option>
                <option value="approvals_required asc"  >Approve (ascend)</option>
                <option value="approvals_required desc" >Approve (decend)</option>
                <option value="reboot_required asc"     >Reboots (ascend)</option>
                <option value="reboot_required desc"    >Reboots (decend)</option>
                <option value="respond_by_date asc"     >Respond (ascend)</option>
                <option value="respond_by_date desc"    >Respond (decend)</option>
            </select>
        </td>
        <td align="center" valign="top">
            <select id="sbTwo" multiple="multiple" size="11" style="width: 200px;">
            </select>
        </td>
    </tr>
    <tr>
        <td align="center" colspan="2">
            <input type="button" id="left" value="<" />
            <input type="button" id="right" value=">" />
            <input type="button" id="leftall" value="<<" />
            <input type="button" id="rightall" value=">>" />
            <input type="button" id="list" value="Okay" onclick="callAjax();">
            <input type="button" id="bye" value="Cancel" onclick="parent.postMessage('close w2popup', '*');">
        </td>
    </tr>
</table>
</center>

<script type="text/javascript">
    $(function ()
    {
        function moveItems(origin, dest)
        {
            $(origin).find(':selected').appendTo(dest);
        }

        function moveAllItems(origin, dest)
        {
            $(origin).children().appendTo(dest);
        }

        $('#left').click(function ()
        {
            moveItems('#sbTwo', '#sbOne');
        });

        $('#right').on('click', function ()
        {
            moveItems('#sbOne', '#sbTwo');
        });

        $('#leftall').on('click', function ()
        {
            moveAllItems('#sbTwo', '#sbOne');
        });

        $('#rightall').on('click', function ()
        {
            moveAllItems('#sbOne', '#sbTwo');
        });
    });

    function callAjax()
    {
        //alert('callAjax');

        var data;

        var x = document.getElementById("sbTwo");  // From <select> statement above.
        var list = '';
        var i;

        //alert(x.length);

        for (i = 0; i < x.length; i++)
        {
            //alert(x.options[i].value);

            if (list.length == 0)
                list = 't.' + x.options[i].value;
            else
                list = list + ",t." + x.options[i].value;
        }

        //alert(list);

        //
        // Prepare the data that will be sent to ajax_ticket.php
        //
        data = {
            "sort_list": list
        };

        var url = 'ajax_dialog_open_ticket_sort.php';

        $.ajax(
            {
                type:     "POST",
                url:      url,
                dataType: "json",
                data:     JSON.stringify(data),
                success:  function(data)
                {
                    if (data['ajax_status'] == 'FAILED')
                    {
                        alert(data['ajax_message']);
                        return;
                    }

                    parent.postMessage('close w2popup', '*');
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

</body>
</html>

