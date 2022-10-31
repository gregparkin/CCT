<?php
/**
 * ajax_home_page_survey.php
 *
 * @package   PhpStorm
 * @file      ajax_home_page_survey.php
 * @author    gparkin
 * @date      3/10/17
 * @version   7.0
 *
 * @brief     NOT CURRENTLY BEING USED!  See: survey.php
 */

set_include_path("/opt/ibmtools/www/cct7/classes");

//
// Class autoloader - Removes the need to add include and require statements
//
if (!function_exists('__autoload'))
{
	require_once('classes/autoloader.php');
}

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

// Prevent caching.
header('Cache-Control: no-cache, must-revalidate');
header('Expires: Mon, 01 Jan 1996 00:00:00 GMT');

// The JSON standard MIME header.
header('Content-type: application/json');

//
// NOTE: It is very important that you do not turn on debugging without writing to a file for server side AJAX code. Any HTML
//       comments coming from functions while writing JSON will show up in the JSON output and you will get a parsing error
//       in the client side program.
//
$lib = new library();        // classes/library.php
$lib->debug_start('ajax_home_page_survey.html');
date_default_timezone_set('America/Denver');

// Read-only stream allows you to read raw data from the request body.
$input = json_decode(file_get_contents("php://input"));

$rating              = '';
$comments            = '';

if (isset($input->{'satisfaction_rating'}))
{
	switch ($input->{'satisfaction_rating'})
	{
		case 1:
			$rating = "Very Satisfied";
			break;
		case 2:
			$rating = "Satisfied";
			break;
		case 3:
			$rating = "Neutral";
			break;
		case 4:
			$rating = "Unsatisfied";
			break;
		case 5:
			$rating = "Very Unsatisfied";
			break;
		case 6:
			$rating = "N/A";
			break;
		default:
			$rating = "Unknown rating (" . $input->{'satisfaction_rating'} . ")";
			break;
	}
}

if (isset($input->{'comments'}))
	$comments             = $input->{'comments'};

$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "satisfaction_rating = %s (%s)", $rating, $input->{'satisfaction_rating'});
$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "comments = %s",            $comments);

$lib->debug1(  __FILE__, __FUNCTION__, __LINE__, "input: ");
$lib->debug_r1(__FILE__, __FUNCTION__, __LINE__, $input);
$lib->debug1(  __FILE__, __FUNCTION__, __LINE__, "_POST: ");
$lib->debug_r1(__FILE__, __FUNCTION__, __LINE__, $_POST);
$lib->debug1(  __FILE__, __FUNCTION__, __LINE__, "_GET: ");
$lib->debug_r1(__FILE__, __FUNCTION__, __LINE__, $_GET);
$lib->debug1(  __FILE__, __FUNCTION__, __LINE__, "_REQUEST: ");
$lib->debug_r1(__FILE__, __FUNCTION__, __LINE__, $_REQUEST);
$lib->debug1(  __FILE__, __FUNCTION__, __LINE__, "_SERVER: ");
$lib->debug_r1(__FILE__, __FUNCTION__, __LINE__, $_SERVER);
$lib->debug1(  __FILE__, __FUNCTION__, __LINE__, "_SESSION: ");
$lib->debug_r1(__FILE__, __FUNCTION__, __LINE__, $_SESSION);

$json = array();

$to        = "gregparkin58@gmail.com";
$to_header = "Greg Parkin <gregparkin58@gmail.com>";

// To send HTML mail, the Content-type header must be set
$headers  = 'MIME-Version: 1.0' . "\r\n";
$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";

// Additional headers
$headers .= 'From: ' . $_SESSION['user_name'] . ' <' . $_SESSION['user_email'] . '>' . "\r\n";;
$headers .= 'To: '   . $to_header . "\r\n";

//$headers .= 'Cc: '   . $email_cc . "\r\n";
//$headers .= 'Bcc: '  . $email_bcc . "\r\n";

$subject_line = "CCT 7 - Feedback Survey";

$message_body  = "<table cellspacing=2 cellpadding=2>";
$message_body .= "<tr>";
$message_body .= "<td align=right><b>User CUID:</b></td>";
$message_body .= "<td align=left>" . $_SESSION['user_cuid'] . "</td>";
$message_body .= "</tr>";
$message_body .= "<tr>";
$message_body .= "<td align=right><b>User Name:</b></td>";
$message_body .= "<td align=left>" . $_SESSION['user_name'] . "</td>";
$message_body .= "</tr>";
$message_body .= "<tr>";
$message_body .= "<td align=right><b>User Email:</b></td>";
$message_body .= "<td align=left>" . $_SESSION['user_email'] . "</td>";
$message_body .= "</tr>";
$message_body .= "<tr>";
$message_body .= "<td align=right><b>User Job Title:</b></td>";
$message_body .= "<td align=left>" . $_SESSION['user_job_title'] . "</td>";
$message_body .= "</tr>";
$message_body .= "<tr>";
$message_body .= "<td align=right><b>User Company:</b></td>";
$message_body .= "<td align=left>" . $_SESSION['user_company'] . "</td>";
$message_body .= "</tr>";
$message_body .= "<tr>";
$message_body .= "<td colspan=2>&nbsp;</td>";
$message_body .= "</tr>";
$message_body .= "<tr>";
$message_body .= "<td align=right><b>Manager CUID:</b></td>";
$message_body .= "<td align=left>" . $_SESSION['manager_cuid'] . "</td>";
$message_body .= "</tr>";
$message_body .= "<tr>";
$message_body .= "<td align=right><b>Manager Name:</b></td>";
$message_body .= "<td align=left>" . $_SESSION['manager_name'] . "</td>";
$message_body .= "</tr>";
$message_body .= "<tr>";
$message_body .= "<td align=right><b>Manager Email:</b></td>";
$message_body .= "<td align=left>" . $_SESSION['manager_email'] . "</td>";
$message_body .= "</tr>";
$message_body .= "<tr>";
$message_body .= "<td align=right><b>Manager Job Title:</b></td>";
$message_body .= "<td align=left>" . $_SESSION['manager_job_title'] . "</td>";
$message_body .= "</tr>";
$message_body .= "<tr>";
$message_body .= "<td align=right><b>Manager Company:</b></td>";
$message_body .= "<td align=left>" . $_SESSION['user_company'] . "</td>";
$message_body .= "</tr>";
$message_body .= "<tr>";
$message_body .= "<td colspan=2>&nbsp;</td>";
$message_body .= "</tr>";
$message_body .= "<tr>";
$message_body .= "<td align=right><b>Satisfaction:</b></td>";
$message_body .= "<td align=left>" . $rating . "</td>";
$message_body .= "</tr>";
$message_body .= "<tr>";
$message_body .= "<td colspan=2>&nbsp;</td>";
$message_body .= "</tr>";
$message_body .= "<tr>";
$message_body .= "<td align=right><b>Comments:</b></td>";
$message_body .= "<td align=left>" . $comments . "</td>";
$message_body .= "</tr>";
$message_body .= "</table>";

mail($to, $subject_line, $message_body, $headers);

$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "Unable to send email: Greg Parkin <gregparkin58@gmail.com");
$json['ajax_status']  = 'SUCCESS';
$json['ajax_message'] = "Thank you, " . $_SESSION['user_first_name'] . "!";
echo json_encode($json);
exit();
