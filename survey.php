<?php
/**
 * @package    CCT
 * @file       survey.php
 * @author     Greg Parkin <gregparkin58@gmail.com>
 * @date       $Date:  $ GMT
 * @version    $Revision:  $
 *
 * NOT CURRENTLY BEING USED!!!
 *
 */

set_include_path("/opt/ibmtools/www/cct7/classes");

//
// Class autoloader - Removes the need to add include and require statements
//
if (!function_exists('__autoload'))
{
	require_once('classes/autoloader.php');
}

//
// Required to start once in order to retrieve user session information
//
if (session_id() == '')
	session_start();

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

$lib = new library();
date_default_timezone_set('America/Denver');

$lib->globalCounter();

?>
<!DOCTYPE html>
<html>
<head>
    <title>CCT 7.0 Home Page</title>

    <style>
        .my_textarea
        {
            border:  1px solid #999999;
            width:   99%;
            margin:  5px 0;
            padding: 3px;
            resize:  none;
            font-size: 13px;
        }
    </style>

    <script type="text/javascript" src="js/jquery-2.1.4.js"></script>

    <script type="text/javascript">

        function sendFeedback()
        {
            $(".loader").show();

            var data;

            // where action = 'sendmail'
            var satisfaction_rating = document.getElementById('satisfaction_rating').value;
            var comments            = document.getElementById('comments').value;

            //
            // Prepare the data that will be sent to ajax_ticket.php
            //
            data = {
                "satisfaction_rating":  satisfaction_rating,
                "comments":             comments
            };

            //
            // Create a JSON string from the selected row of data.
            //
            //var gridData = jQuery(TicketGrid).jqGrid('getRowData', Ticket_id);
            //var postData = JSON.stringify(gridData);
            //var postData = JSON.stringify(data);
            //alert(postData);

            //alert(JSON.stringify(data));

            var url = 'ajax_home_page_survey.php';

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

                        // Reset comments field
                        document.getElementById('comments').value = '';
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
<p align="center">
    <h2>Please provide any feedback on CCT 7.0 to Greg Parkin</h2>

<table border="0" cellspacing="0" cellpadding="0"
       style="width: 50%; height: 50%; background-color: lightgoldenrodyellow">
    <tr>
        <td align="center">
            <fieldset style="PADDING-BOTTOM: 2px; PADDING-LEFT: 2px; PADDING-RIGHT: 2px; PADDING-TOP: 2px; border-color: white">
                <legend><font color="#8F4300"><b>CCT 7.0 Satisfaction</b></font></legend>
                <table border="0" width="100%">
                    <tr>
                        <td width="33%">
                            <input type="radio" name="satisfaction_rating" id="satisfaction_rating"
                                   value="1">Very Satisfied
                        </td>
                        <td width="33%">
                            <input type="radio" name="satisfaction_rating" id="satisfaction_rating"
                                   value="3">Neutral
                        </td>
                        <td width="33%">
                            <input type="radio" name="satisfaction_rating" id="satisfaction_rating"
                                   value="5">Very Unsatisfied
                        </td>
                    </tr>
                    <tr>
                        <td width="33%">
                            <input type="radio" name="satisfaction_rating" id="satisfaction_rating"
                                   value="2" checked>Satisfied
                        </td>
                        <td width="33%">
                            <input type="radio" name="satisfaction_rating" id="satisfaction_rating"
                                   value="4">Unsatisfied
                        </td>
                        <td width="33%">
                            <input type="radio" name="satisfaction_rating" id="satisfaction_rating"
                                   value="6">N/A
                        </td>
                    </tr>
                </table>
            </fieldset>
        </td>
    </tr>
    <tr>
        <td align="left">
            <fieldset style="PADDING-BOTTOM: 2px; PADDING-LEFT: 2px; PADDING-RIGHT: 2px; PADDING-TOP: 2px; border-color: white">
                <legend><font color="#8F4300"><b>Comments</b></font></legend>
                <textarea class="my_textarea" rows="5" id="comments" name="comments" cols="99" style="width: 99%"></textarea>
            </fieldset>
        </td>
    </tr>
    <tr>
        <td align="center">
            <input type="button" value="Submit" onclick="sendFeedback();">
        </td>
    </tr>
</table>
</p>
</body>
</html>
