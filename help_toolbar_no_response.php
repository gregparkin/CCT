<?php
/**
 * help_toolbar_no_response.php
 *
 * @package   PhpStorm
 * @file      help_toolbar_no_response.php
 * @author    gparkin
 * @date      4/12/17
 * @version   7.0
 *
 * @brief     Help file for Remedy Attachment utility.
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
<style>
	p {
		font-size: 18px;
	}
</style>
<h3>No Response</h3>
<p style="font-size: 18px;">
    You want to create a report of contacts that have not responded for a ticket you are working on in
    order to

</p>

<br>

<h3>Select CCT Ticket Number</h3>
<p style="font-size: 18px;">
    <img src="help/toolbar_remedy_attachment/remedy_attachment1.png"><br>
    After clicking on the Remedy Attachment icon from the toolbar menu, the this form is displayed.
    You have two ways of telling the utility what CCT ticket you want. You can select from a list of
    available NET Group member tickets that you are a member of, if any exist, or you can type in a
    CCT ticket number that you are interested in. By default, if you type something in the text box and
    also select a ticket, the program will search for what you typed in the text box. If this is not
    what you intended then please click the Reset button and make your selection again.
    <br><br>
    When you are ready, click the button labeled: "Click to Begin Download".
</p>

<h3>No Approved Ready Work Available</h3>
<p style="font-size: 18px;">
    <img src="help/toolbar_remedy_attachment/remedy_attachment2.png"><br>
    If you mis-typed your CCT ticket number (it doesn't exist), or there is no approved ready work
    data available for the ticket, you will see this message.
</p>

<h3>CVS Formatted Download File</h3>
<p style="font-size: 18px;">
    <img src="help/toolbar_remedy_attachment/remedy_attachment3.png"><br>
    In this example I have selected CCT700015055 which references CM0000327704. Notice the filename
    will include the Remedy CM ticket number to make it easier to identify it. At this point you can
    save the file to a folder on your computer or open it and then save it from either Excel or
    LibreOffice Calc.
</p>

<h3>The Spreadsheet Contents</h3>
<p style="font-size: 18px;">
    <img src="help/toolbar_remedy_attachment/remedy_attachment4.png"><br>
    This is what the contents of the spreadsheet will look like. It information is straight forward
    containing all the necessary information that you would want to attach to your Remedy ticket. (In
    this example, you would want to attach this file to Remedy ticket: CM0000327704.
</p>