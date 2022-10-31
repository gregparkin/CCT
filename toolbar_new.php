<?php
/**
 * toolbar_new.php
 *
 * @package   PhpStorm
 * @file      toolbar_new.php
 * @author    gparkin
 * @date      7/19/16
 * @version   7.0
 *
 * @brief     This module is used to create a new CCT work request.
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

//
// URL Arguments
//
$argv = array();
$argc = 0;

//
// Parse QUERY_STRING
//
if (isset($_SERVER['QUERY_STRING']))   // Parse QUERY_STRING if it exists
{
    //printf("<!-- QUERY_STRING = %s -->\n", $_SERVER['QUERY_STRING']);

    //
    // http://www.foo.com/somepath?keyword1=value1&keyword2=value2&keyword3=value3
    //
    // $myQryStr = "first=1&second=Z&third[]=5000&third[]=6000";
    // parse_str($myQryStr, $myArray);
    // echo $myArray['first']; //will output 1
    // echo $myArray['second']; //will output Z
    // echo $myArray['third'][0]; //will output 5000
    // echo $myArray['third'][1]; //will output 6000
    //
    parse_str($_SERVER['QUERY_STRING'], $argv);  // Parses URL parameter options into an array called $this->argv
    $argc = count($argv);                        // Get the argv count. Number of items in the $argv array.
}

$ora = new oracle();
$lib = new library();

$lib->globalCounter();


$lib->debug_start("toolbar_new.html");
date_default_timezone_set('America/Denver');

//
// header() is a PHP function for modifying the HTML header information
// going to the client's browser. Here we tell their browser not to cache
// the page coming in so their browser will always rebuild the page from
// scratch instead of retrieving a copy of one from its cache.
//
// header("Expires: " . gmstrftime("%a, %d %b %Y %H:%M:%S GMT"));
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

//
// QUERY_STRING is actually parsed in the constructor function so programs can know right
// away if there are any argvs available. Below we just show the argv information if present.
//
if (isset($_SERVER['QUERY_STRING']))
{
    printf("<!-- QUERY_STRING = %s -->\n", $_SERVER['QUERY_STRING']);
}

//
// Disable buffering - This is what makes the loading screen work properly.
//
@apache_setenv('no-gzip', 1);
@ini_set('zlib.output_compression', 0);
@ini_set('output_buffering', 'Off');
@ini_set('implicit_flush', 1);

ob_implicit_flush(1); // Flush buffers

for ($i = 0, $level = ob_get_level(); $i < $level; $i++)
{
    ob_end_flush();
}
?>
<!doctype html>
<html lang = "en">
<head>
    <meta charset = "utf-8">
    <title>New Work Request</title>
    <link rel="stylesheet" type="text/css" href="css/calendar.css">

    <script type="text/javascript" src="js/jquery-2.1.4.js"></script>
    <script type="text/javascript" src="js/date_functions.js"></script>

    <!-- w2ui popup -->
    <!-- link rel="stylesheet" type="text/css" href="css/w2ui-1.4.3.css" / -->
    <!-- script type="text/javascript" src="js/w2ui-1.4.3.js"></script -->

    <link rel="stylesheet" type="text/css" href="css/w2ui-1.5.rc1.css" />
    <script type="text/javascript" src="js/w2ui-1.5.rc1.js"></script>

    <script type="text/javascript">
        var erase_once = 0;

        $(window).load(function()
            {
                $(".loader").fadeOut("slow");
            });

        function optionalRemedyCM()
        {
            var remedy_cm = document.getElementById("cm_ticket_no").value;

            if (erase_once == 0 && remedy_cm == '(Optional)')
            {
                document.getElementById("cm_ticket_no").value = '';
                erase_once = 1;
            }
        }
    </script>

    <style>
        .loader
        {
            position:         fixed;
            left:             0px;
            top:              0px;
            width:            100%;
            height:           100%;
            z-index:          9999;
            background:       url('images/page-loader.gif') 50% 50% no-repeat rgb(249,249,249);
        }

        .body_class
        {
            z-index:          3;
            position:         absolute;
            display:          block;
            right:            0px;
            top:              32px;
            bottom:           75px;
            background-color: white;
            margin-left:      auto;
            margin-right:     auto;
            width:            100%;
            color:            black;
            overflow:         auto;
            font-family:      verdana, arial, sans serif;
            font-size:        14px;
        }

        .tab {
            width:            98%;
            height:           450px;
            border:           1px solid silver;
            border-top:       0px;
            display:          none;
            padding:          10px;
            overflow:         auto;
        }

        input[type=text]
        {
            width:            100%;
            padding:          1px 1px;
            font-size:        14px;
            margin:           0px 0;
            box-sizing:       border-box;
            border:           1px solid blue;
            border-radius:    4px;
        }

        /* calendar */
        table.calendar
        {
            border-left:   1px solid #999;
        }

        tr.calendar-row
        {

        }

        td.calendar-day
        {
            min-height:    80px;
            font-size:     11px;
            position:      relative;
        }

        * html div.calendar-day
        {
            height:        80px;
        }

        td.calendar-day:hover
        {
            background:    #eceff5;
        }

        td.calendar-day-np
        {
            background:    #eee;
            min-height:    80px;
        }

        * html div.calendar-day-np
        {
            height:        80px;
        }

        td.calendar-day-head
        {
            background:    #ccc;
            font-weight:   bold;
            text-align:    center;
            width:         50px;
            padding:       5px;
            border-bottom: 1px solid #999;
            border-top:    1px solid #999;
            border-right:  1px solid #999;
        }

        div.day-number
        {
            background:    #999;
            padding:       5px;
            color:         #fff;
            font-weight:   bold;
            float:         right;
            margin:        -5px -5px 0 0;
            width:         20px;
            text-align:    center;
        }

        /* shared */
        td.calendar-day, td.calendar-day-np
        {
            width:         50px;
            padding:       5px;
            border-bottom: 1px solid #999;
            border-right:  1px solid #999;
        }

    </style>
</head>

<?php
if (isset($argv['alert']))
{
    printf("<body class=\"body_class\" onload=\"alert('%s')\">\n", $argv['alert']);
}
else
{
    printf("<body class=\"body_class\">\n");
}

printf("<div class='loader' id='loader'></div>\n");  // spinner

if ($argc == 0 || isset($argv['do']) && $argv['do'] == 'step1')
{
    step1();
}

if (isset($argv['do']) && $argv['do'] == 'step2')
{
    step2();
}

/**
 * @fn    draw_calendar($month, $year)
 *
 * @brief Draw a calendar for a user reference.
 *
 * @param int $month
 * @param int $year
 *
 * @return string
 */
function draw_calendar($month, $year)
{
    date_default_timezone_set($_SESSION['local_timezone_name']);
    $this_month = date('m');
    $this_day   = date('d');
    $this_year  = date('Y');

    switch ( $month )
    {
        case 1:  $title = "January "   . $year; break;
        case 2:  $title = "February "  . $year; break;
        case 3:  $title = "March "     . $year; break;
        case 4:  $title = "April "     . $year; break;
        case 5:  $title = "May "       . $year; break;
        case 6:  $title = "June "      . $year; break;
        case 7:  $title = "July "      . $year; break;
        case 8:  $title = "August "    . $year; break;
        case 9:  $title = "September " . $year; break;
        case 10: $title = "October "   . $year; break;
        case 11: $title = "November "  . $year; break;
        case 12: $title = "December "  . $year; break;
        default: $title = $month . " " . $year; break;
    }

    $calendar = '<table cellpadding="0" cellspacing="0" class="calendar">';
    $calendar .= "<tr><td align='center' colspan='7'><font size='+2'><b>$title</b></font></td></tr>";

    //
    // table headings
    //
    $headings = array('Sun','Mon','Tue','Wed','Thu','Fri','Sat');
    $calendar.= '<tr class="calendar-row"><td class="calendar-day-head">' .
        implode('</td><td class="calendar-day-head">',$headings).'</td></tr>';

    $running_day       = date('w',mktime(0,0,0,$month,1,$year));
    $days_in_month     = date('t',mktime(0,0,0,$month,1,$year));
    $days_in_this_week = 1;
    $day_counter       = 0;
    // $dates_array       = array();

    //
    // row for week one
    //
    $calendar.= '<tr class="calendar-row">';

    //
    // print "blank" days until the first of the current week
    //
    for($x = 0; $x < $running_day; $x++)
    {
        $calendar .= '<td class="calendar-day-np"> </td>';
        $days_in_this_week++;
    }

    /* keep going with days.... */
    for($list_day = 1; $list_day <= $days_in_month; $list_day++)
    {
        $calendar .= '<td class="calendar-day">';

        //
        // add in the day number
        //
        if ($this_month == $month && $this_year == $year && $this_day == $list_day)
        {
            $calendar .= '<div class="day-number" style="color: blue">' . $list_day . '</div>';
        }
        else
        {
            $calendar .= '<div class="day-number">' . $list_day . '</div>';
        }

        /** QUERY THE DATABASE FOR AN ENTRY FOR THIS DAY !!  IF MATCHES FOUND, PRINT THEM !! **/
        $calendar .= str_repeat('<p></p>', 2);

        $calendar .= '</td>';
        if ($running_day == 6)
        {
            $calendar .= '</tr>';
            if (($day_counter + 1) != $days_in_month)
            {
                $calendar .= '<tr class="calendar-row">';
            }
            $running_day = -1;
            $days_in_this_week = 0;
        }
        $days_in_this_week++;
        $running_day++;
        $day_counter++;
    }

    //
    // finish the rest of the days in the week
    //
    if($days_in_this_week < 8)
    {
        for($x = 1; $x <= (8 - $days_in_this_week); $x++)
        {
            $calendar.= '<td class="calendar-day-np"> </td>';
        }
    }

    $calendar.= '</tr>';
    $calendar.= '</table>';

    return $calendar;
}

function setDefaultDate($stop_week, $stop_wday)
{
    $month = date('m');
    $mday  = date('d');
    $year  = date('Y');
    $ndays = date('t', mktime(0, 0, 0, $month, 1, $year));

    switch (date('D'))
    {
        case 'Sun': $wday = 0; break;
        case 'Mon': $wday = 1; break;
        case 'Tue': $wday = 2; break;
        case 'Wed': $wday = 3; break;
        case 'Thu': $wday = 4; break;
        case 'Fri': $wday = 5; break;
        case 'Sat': $wday = 6; break;
        default:    $wday = 0; break;
    }

    $week = 0;

    while ($week <= $stop_week)
    {
        if ($wday == 6)
        {
            ++$week;
            $wday = 0;
        }
        else
        {
            ++$wday;
        }

        if ($mday == $ndays)
        {
            if ($month == 12)
            {
                $month = 1;
                $mday = 1;
                ++$year;
                $ndays = date('t', mktime(0, 0, 0, $month, 1, $year));
            }
            else
            {
                ++$month;
                $mday = 1;
                $ndays = date('t', mktime(0, 0, 0, $month, 1, $year));
            }
        }
        else
        {
            ++$mday;
        }

        if ($week == $stop_week && $wday == $stop_wday)
            break;
    }

    $new_date = $year . '-' . $month . '-' . $mday;  // 2016-07-24
    return date('l, F j, Y', strtotime($new_date));  // Sunday, July 24, 2016
}

/**
 * @fn step1()
 * @brief Gather all the information to generate a new work request.
 * @param $message will contain an error message we want to display if step2() fails.
 * @return null;
 */
function step1($message='')
{
    global $ora, $lib;

    ?>
    <form name="f1" method="post" action="toolbar_new.php?do=step2">

        <input type="hidden" id="cm_start_date"        name="cm_start_date"        value="">
        <input type="hidden" id="cm_end_date"          name="cm_end_date"          value="">
        <input type="hidden" id="cm_duration_computed" name="cm_duration_computed" value="">
        <input type="hidden" id="cm_ipl_boot"          name="cm_ipl_boot"          value="">
        <input type="hidden" id="cm_status"            name="cm_status"            value="">
        <input type="hidden" id="cm_open_closed"       name="cm_open_closed"       value="">
        <input type="hidden" id="cm_close_date"        name="cm_close_date"        value="">
        <input type="hidden" id="cm_owner_first_name"  name="cm_owner_first_name"  value="">
        <input type="hidden" id="cm_owner_last_name"   name="cm_owner_last_name"   value="">
        <input type="hidden" id="cm_owner_cuid"        name="cm_owner_cuid"        value="">
        <input type="hidden" id="cm_owner_group"       name="cm_owner_group"       value="">

        <center>
        <table border="2" cellpadding="2" cellspacing="2" style="border-color: darkred">
            <tr>
                <td align="center">
                    <b>New CCT Work Request Creation</b>
                </td>
            </tr>
            <tr>
                <td>
                    <div id="work_tab">
                        <div id="tabs1" style="width: 100%; height: 29px;"></div>

                        <!-- General -->
                        <div id="wtab1" class="tab" style="width: 1194px; height: 520px;">
                            <table bgcolor="#ECE9D8" width="100%" cellspacing="2" cellpadding="2" style="height: 100%; color: black">
                                <tr>
                                    <td align="right">
                                        <font color="#8f4300"><b>Work Activity:</b></font>
                                    </td>
                                    <td align="left">
                                        <select name="work_activity" style="padding: 2; width: 20em;">
                                            <?php
                                            if ($ora->sql("select work_activity from cct7_work_activities order by work_activity") == false)
                                            {
                                                $lib->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $ora->sql_statement);
                                                $lib->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $ora->dbErrMsg);
                                                $lib->html_stop(__FILE__, __FUNCTION__, __LINE__, "SQL Error: %s. Please contact Greg Parkin", $ora->dbErrMsg);
                                            }

                                            while ($ora->fetch())
                                            {
                                                if ($ora->work_activity == "Patching OS")
                                                    printf("<option value=\"%s\" selected>%s</option>\n", $ora->work_activity, $ora->work_activity);
                                                else
                                                    printf("<option value=\"%s\">%s</option>\n", $ora->work_activity, $ora->work_activity);
                                            }
                                            ?>
                                        </select>
                                        <font color="#8b0000">Work classification is used to tell the contacts what type of work activity this is.</font>
                                    </td>
                                </tr>
                                <tr>
                                    <td align="right">
                                        <font color="#8f4300"><b>Reboot Required:</b></font>
                                    </td>
                                    <td align="left">
                                        <input value="Y" CHECKED type="radio" name="reboot_required">Yes&nbsp;&nbsp;&nbsp;
                                        <input value="N"         type="radio" name="reboot_required">No&nbsp;&nbsp;
                                        <font color="#8b0000">When Yes, any children server contacts for parent servers must be notified.</font>
                                    </td>
                                </tr>
                                <tr>
                                    <td align="right">
                                        <font color="#8f4300"><b>Approvals Required:</b></font>
                                    </td>
                                    <td align="left">
                                        <input value="Y" CHECKED type="radio" name="approvals_required">Yes&nbsp;&nbsp;&nbsp;
                                        <input value="N"         type="radio" name="approvals_required">No&nbsp;&nbsp;
                                        <font color="#8b0000">When No, contacts receive a FYI notification only. No further action is required on their part.</font>
                                    </td>
                                </tr>
                                <tr><!-- Draw the calendars here for the users to reference. -->
                                    <td colspan="2" align="center" valign="middle">
                                        <table border="1">
                                            <tr>
                                                <?php
                                                $month = date('m');
                                                $year  = date('Y');

                                                echo '<td align="center" valign="top">';
                                                echo draw_calendar($month, $year);
                                                echo '</td>';

                                                if ($month == 12)
                                                {
                                                    $month = 1;
                                                    $year++;
                                                }
                                                else
                                                {
                                                    $month++;
                                                }

                                                echo '<td align="center" valign="top">';
                                                echo draw_calendar($month, $year);
                                                echo '</td>';

                                                if ($month == 12)
                                                {
                                                    $month = 1;
                                                    $year++;
                                                }
                                                else
                                                {
                                                    $month++;
                                                }

                                                echo '<td align="center" valign="top">';
                                                echo draw_calendar($month, $year);
                                                echo '</td>';
                                                ?>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                                <?php

                                //
                                // Calculate dates by using today's dates. Give contacts time to resond and approve work.
                                //
                                $email_reminder1_date_char     = setDefaultDate(1, 2);  // Tue
                                $email_reminder2_date_char     = setDefaultDate(1, 3);  // Wed
                                $email_reminder3_date_char     = setDefaultDate(1, 4);  // Thu
                                $respond_by_date_char          = setDefaultDate(1, 5);  // Fri
                                $schedule_start_date_char      = setDefaultDate(2, 1);  // Mon

                                $lib->debug1(__FILE__, __FUNCTION__, __LINE__, "email_reminder1_date_char     = %s", $email_reminder1_date_char);
                                $lib->debug1(__FILE__, __FUNCTION__, __LINE__, "email_reminder2_date_char     = %s", $email_reminder2_date_char);
                                $lib->debug1(__FILE__, __FUNCTION__, __LINE__, "email_reminder3_date_char     = %s", $email_reminder3_date_char);
                                $lib->debug1(__FILE__, __FUNCTION__, __LINE__, "respond_by_date_char          = %s", $respond_by_date_char);
                                $lib->debug1(__FILE__, __FUNCTION__, __LINE__, "schedule_start_date_char      = %s", $schedule_start_date_char);
                                ?>

                                <tr>
                                    <td align="right">
                                        <font color="#8f4300"><b>Email Reminder 1:</b></font>
                                    </td>
                                    <td align="left">
                                        <input type="text" id="email_reminder1_date_char" name="email_reminder1_date_char" value="<?php echo $email_reminder1_date_char; ?>" size="30" style="width: 20em">
                                        <font color="#8b0000">Send email reminder1 to all contacts that have not responded.</font>
                                    </td>
                                </tr>
                                <tr>
                                    <td align="right">
                                        <font color="#8f4300"><b>Email Reminder 2:</b></font>
                                    </td>
                                    <td align="left">
                                        <input type="text" id="email_reminder2_date_char" name="email_reminder2_date_char" value="<?php echo $email_reminder2_date_char; ?>" size="30" style="width: 20em">
                                        <font color="#8b0000">Send email reminder2 to all contacts that have not responded.</font>
                                    </td>
                                </tr>
                                <tr>
                                    <td align="right">
                                        <font color="#8f4300"><b>Email Reminder 3:</b></font>
                                    </td>
                                    <td align="left">
                                        <input type="text" id="email_reminder3_date_char" name="email_reminder3_date_char" value="<?php echo $email_reminder3_date_char; ?>" size="30" style="width: 20em">
                                        <font color="#8b0000">Send email reminder3 to all contacts that have not responded.</font>
                                    </td>
                                </tr>
                                <tr>
                                    <td align="right">
                                        <font color="#8f4300"><b>Respond By:</b></font>
                                    </td>
                                    <td align="left">
                                        <input type="text" id="respond_by_date_char" name="respond_by_date_char" value="<?php echo $respond_by_date_char; ?>" size="30" style="width: 20em">
                                        <font color="#8b0000">This is the date you need contacts to respond by.</font>
                                    </td>
                                </tr>
                                <tr>
                                    <td align="right">
                                        <font color="#8f4300"><b>Schedule Work Start:</b></font>
                                    </td>
                                    <td align="left">
                                        <input type="text" id="schedule_start_date_char" name="schedule_start_date_char" value="<?php echo $schedule_start_date_char; ?>" size="30" style="width: 20em">
                                        <font color="#8b0000">Uses OS Weekly maintenance windows from CSC.</font>
                                    </td>
                                </tr>
                                <tr>
                                    <td align="right">
                                        <font color="#8f4300"><b>Remedy Ticket:</b></font>
                                    </td>
                                    <td align="left">
                                        <input type="text" id="cm_ticket_no" name="cm_ticket_no" value="" size="30"
                                               style="width: 20em;">
                                        <input title="Click to view Remedy CM ticket."
                                               class="view" type="button" name="button" value="View" onclick="viewRemedyCM();">
                                        <input title="Copy text from Remedy ticket to this new CCT ticket."
                                                type="button" name="button" value="Copy To" onclick="copyTo();">
                                    </td>
                                </tr>
                            </table>
                        </div>

                        <!-- Description / Implementation Instructions -->
                        <div id="wtab2" class="tab" style="width: 1194px; height: 520px;">
                            <table bgcolor="#ECE9D8" width="100%" ellspacing="2" cellpadding="2" style="height: 100%; color: black">
                                <tr>
                                    <td width="100%" valign="top">
                                        <fieldset style="PADDING-BOTTOM: 2px; PADDING-LEFT: 2px; PADDING-RIGHT: 2px; PADDING-TOP: 2px; border-color: white">
                                            <legend><font color="#8F4300"><b>Description</b></font></legend>
                                            <textarea rows="11" id="work_description" name="work_description" cols="99"
                                                      style="width: 99%; resize: none; font-size: 14px;"></textarea>
                                        </fieldset>
                                    </td>
                                </tr>
                                <tr>
                                    <td width="100%" valign="top">
                                        <fieldset style="PADDING-BOTTOM: 2px; PADDING-LEFT: 2px; PADDING-RIGHT: 2px; PADDING-TOP: 2px; border-color: white">
                                            <legend><font color="#8F4300"><b>Implementation instructions</b></font></legend>
                                            <textarea rows="11" id="work_implementation" name="work_implementation" cols="99"
                                                      style="width: 99%; resize: none; font-size: 14px;"></textarea>
                                        </fieldset>
                                    </td>
                                </tr>
                            </table>
                        </div>

                        <!-- Backoff Plans -->
                        <div id="wtab3" class="tab" style="width: 1194px; height: 520px;">
                            <table bgcolor="#ECE9D8" width="100%" ellspacing="2" cellpadding="2" style="height: 100%; color: black">
                                <tr>
                                    <td width="100%" valign="top">
                                        <fieldset style="PADDING-BOTTOM: 2px; PADDING-LEFT: 2px; PADDING-RIGHT: 2px; PADDING-TOP: 2px; border-color: white">
                                            <legend><font color="#8F4300"><b>Back Off Plans</b></font></legend>
                                            <textarea rows="26" id="work_backoff_plan" name="work_backoff_plan" cols="99"
                                                      style="width: 99%; resize: none; font-size: 14px;"></textarea>
                                        </fieldset>
                                    </td>
                                </tr>
                            </table>
                        </div>

                        <!-- Business Reason / Impact -->
                        <div id="wtab4" class="tab" style="width: 1194px; height: 520px;">
                            <table bgcolor="#ECE9D8" width="100%" ellspacing="2" cellpadding="2" style="height: 100%; color: black">
                                <tr>
                                    <td width="100%" valign="top">
                                        <fieldset style="PADDING-BOTTOM: 2px; PADDING-LEFT: 2px; PADDING-RIGHT: 2px; PADDING-TOP: 2px; border-color: white">
                                            <legend><font color="#8F4300"><b>Business Reasons</b></font></legend>
                                            <textarea rows="11" id="work_business_reason" name="work_business_reason" cols="99"
                                                      style="width: 99%; resize: none; font-size: 14px;"></textarea>
                                        </fieldset>
                                    </td>
                                </tr>
                                <tr>
                                    <td width="100%" valign="top">
                                        <fieldset style="PADDING-BOTTOM: 2px; PADDING-LEFT: 2px; PADDING-RIGHT: 2px; PADDING-TOP: 2px; border-color: white">
                                            <legend><font color="#8F4300"><b>Impact</b></font></legend>
                                            <textarea rows="11" id="work_user_impact" name="work_user_impact" cols="99"
                                                      style="width: 99%; resize: none; font-size: 14px;"></textarea>
                                        </fieldset>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </td>
            </tr>
        </table>

        <br><br>

        <script type="text/javascript">
            var config1 = {
                tabs: {
                    name: 'tabs1',
                    active: 'wtab1',
                    style: 'background: #ECE9D8',
                    tabs: [
                        { id: 'wtab1', caption: '<b>General</b>' },
                        { id: 'wtab2', caption: '<b>Description / Implementation Instructions</b>' },
                        { id: 'wtab3', caption: '<b>Backoff Plans</b>' },
                        { id: 'wtab4', caption: '<b>Business Reason / Impact</b>' },
                    ],
                    onClick: function (event)
                    {
                        $('#work_tab .tab').hide();
                        $('#work_tab #' + event.target).show();
                    }
                }
            };

            $(function ()
            {
                $('#tabs1').w2tabs(config1.tabs);
                //$('#tab2').show();
                w2ui.tabs1.click('wtab1');
            });
        </script>

        <table border="2" cellpadding="4" cellspacing="4" style="border-color: darkred;" bgcolor="#ECE9D8" >
            <tr>
                <td colspan="4" align="center" bgcolor="white">
                    <b>Select Use Remedy Start/End Window or one of the three server Maintenance Windows defined in CSC</b>
                </td>
            </tr>
            <tr>
                <td align="left">
                    <input value="remedy"         type="radio" name="maintenance_window">Use Remedy Start/End Window
                </td>
                <td align="left">
                    <input value="weekly" CHECKED type="radio" name="maintenance_window">Use Weekly Maintenance Window&nbsp;
                </td>
                <td align="left">
                    <input value="monthly"        type="radio" name="maintenance_window">Use Monthly Maintenance Window&nbsp;
                </td>
                <td align="left">
                    <input value="quarterly"      type="radio" name="maintenance_window">Use Quarterly Maintenance Window&nbsp;
                </td>
            </tr>
        </table>

            <?php
            //
            // CCT 7 Training Session held 07/19/2017 the large group of application people voted
            // to remove the ability to target only certain contacts (NET/SUB) for any work regardless
            // whether they had anything to do with the work or not.
            //
/**
        <br><br><!-- CSC Banner Selections -->

        <table border="2" cellpadding="2" cellspacing="2" style="border-color: darkred;" bgcolor="#ECE9D8" >
            <tr>
                <td colspan="4" align="center" bgcolor="white"><b>List of CSC Banners to pull contacts from.</b></td>
            </tr>
            <tr>
                <td align="center" valign="top"><input type="checkbox" id="cb1" name="cb1" checked></td>
                <td align="left"   valign="top">APPROVER</td>
                <td align="left"   valign="top">PASE</td>
                <td align="left"   valign="top">Applications or Databases Desiring Notification (Not Hosted on this Server)</td>
            </tr>
            <tr>
                <td align="center" valign="top"><input type="checkbox" id="cb2" name="cb2" checked></td>
                <td align="left"   valign="top">APPROVER</td>
                <td align="left"   valign="top">PASE</td>
                <td align="left"   valign="top">Application Support</td>
            </tr>
            <tr>
                <td align="center" valign="top"><input type="checkbox" id="cb3" name="cb3" checked></td>
                <td align="left"   valign="top">APPROVER</td>
                <td align="left"   valign="top">PASE</td>
                <td align="left"   valign="top">Application Utilities, Middleware, Legacy DB, Other (Hosted on this Server)</td>
            </tr>
            <tr>
                <td align="center" valign="top"><input type="checkbox" id="cb4" name="cb4" checked></td>
                <td align="left"   valign="top">APPROVER</td>
                <td align="left"   valign="top">PASE</td>
                <td align="left"   valign="top">Infrastructure</td>
            </tr>
            <tr>
                <td align="center" valign="top"><input type="checkbox" id="cb5" name="cb5" checked></td>
                <td align="left"   valign="top">APPROVER</td>
                <td align="left"   valign="top">PASE</td>
                <td align="left"   valign="top">MiddleWare Support</td>
            </tr>
            <tr>
                <td align="center" valign="top"><input type="checkbox" id="cb6" name="cb6" checked></td>
                <td align="left"   valign="top">APPROVER</td>
                <td align="left"   valign="top">DBA</td>
                <td align="left"   valign="top">Database Support</td>
            </tr>
            <tr>
                <td align="center" valign="top"><input type="checkbox" id="cb7" name="cb7" checked></td>
                <td align="left"   valign="top">APPROVER</td>
                <td align="left"   valign="top">DBA</td>
                <td align="left"   valign="top">Development Database Support</td>
            </tr>
            <tr>
                <td align="center" valign="top"><input type="checkbox" id="cb8" name="cb8" checked></td>
                <td align="left"   valign="top">APPROVER</td>
                <td align="left"   valign="top">OS</td>
                <td align="left"   valign="top">Operating System Support</td>
            </tr>
            <tr>
                <td align="center" valign="top"><input type="checkbox" id="cb9" name="cb9" checked></td>
                <td align="left"   valign="top">FYI</td>
                <td align="left"   valign="top">PASE</td>
                <td align="left"   valign="top">Applications Owning Database (DB Hosted on this Server, Owning App Is Not)</td>
            </tr>
            <tr>
                <td align="center" valign="top"><input type="checkbox" id="cb10" name="cb10" checked></td>
                <td align="left"   valign="top">FYI</td>
                <td align="left"   valign="top">PASE</td>
                <td align="left"   valign="top">Development Support</td>
            </tr>
        </table>

        <br><br>
        <!-- Virtual Host Contacts -->

        <table border="2" cellpadding="2" cellspacing="2" style="border-color: darkred;" bgcolor="#ECE9D8" >
            <tr>
                <td colspan="2" align="center" bgcolor="white"><b>Virtual Host Contacts</b></td>
            </tr>
            <tr>
                <td align="center" valign="top"><input type="checkbox" id="no_virt_cons" name="no_virt_cons"></td>
                <td align="left"   valign="top">
                    Check if you want to exclude virtual server contacts.
                </td>
            </tr>
        </table>
 */
			?>
        <br><br><!-- Server select criteria table -->

        <table border="2" cellpadding="2" cellspacing="2" style="border-color: darkred;">
            <tr>
                <td align="center">
                    <b>Select the servers</b>
                </td>
            </tr>
            <tr>
                <td>
                    <div id="system_tab">
                        <div id="tabs2" style="width: 100%; height: 29px;"></div>

                        <!-- Asset Manager -->
                        <div id="stab1" class="tab" style="width: 1194px; height: 520px;">
                            <table bgcolor="#ECE9D8" width="100%" ellspacing="2" cellpadding="2" style="height: 100%; color: black">
                                <tr>
                                    <td width="33%" align="left">
                                        <fieldset style="PADDING-BOTTOM: 2px; PADDING-LEFT: 2px; PADDING-RIGHT: 2px; PADDING-TOP: 2px; width: 27em; border-color: white">
                                            <legend><font color="#8f4300"><b>Managing Groups</b></font></legend>

                                                <select style="padding: 2; width: 30em; height: 18em; font-size: 12px;"
                                                        name="computer_managing_group[]" multiple="multiple" size="10">
                                                    <?php
                                                    if ($ora->sql("select * from cct7_managing_group order by computer_managing_group") == false)
                                                    {
                                                        $lib->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $ora->sql_statement);
                                                        $lib->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $ora->dbErrMsg);
                                                        $lib->html_stop(__FILE__, __FUNCTION__, __LINE__, "SQL Error: %s. Please contact Greg Parkin", $ora->dbErrMsg);
                                                    }

                                                    while ($ora->fetch())
                                                    {
                                                        printf("<option value=\"%s\">%s</option>\n", $ora->computer_managing_group, $ora->computer_managing_group);
                                                    }
                                                    ?>
                                                </select>

                                        </fieldset>
                                    </td>
                                    <td width="33%" align="left">
                                        <fieldset style="PADDING-BOTTOM: 2px; PADDING-LEFT: 2px; PADDING-RIGHT: 2px; PADDING-TOP: 2px; width: 27em; border-color: white">
                                            <legend><font color="#8f4300"><b>Operating Systems</b></font></legend>

                                                <select style="padding: 2; width: 30em; height: 18em; font-size: 12px;"
                                                        name="computer_os_lite[]" multiple="multiple" size="10">
                                                    <?php
                                                    if ($ora->sql("select * from cct7_os_lite order by computer_os_lite") == false)
                                                    {
                                                        $lib->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $ora->sql_statement);
                                                        $lib->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $ora->dbErrMsg);
                                                        $lib->html_stop(__FILE__, __FUNCTION__, __LINE__, "SQL Error: %s. Please contact Greg Parkin", $ora->dbErrMsg);
                                                    }

                                                    while ($ora->fetch())
                                                    {
                                                        printf("<option value=\"%s\">%s</option>\n", $ora->computer_os_lite, $ora->computer_os_lite);
                                                    }
                                                    ?>
                                                </select>

                                        </fieldset>
                                    </td>
                                    <td width="33%" align="left">
                                        <fieldset style="PADDING-BOTTOM: 2px; PADDING-LEFT: 2px; PADDING-RIGHT: 2px; PADDING-TOP: 2px; width: 27em; border-color: white">
                                            <legend><font color="#8f4300"><b>System Status</b></font></legend>

                                                <select style="padding: 2; width: 30em; height: 18em; font-size: 12px;"
                                                        name="computer_status[]" multiple="multiple" size="10">
                                                    <?php
                                                    if ($ora->sql("select * from cct7_computer_status order by computer_status") == false)
                                                    {
                                                        $lib->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $ora->sql_statement);
                                                        $lib->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $ora->dbErrMsg);
                                                        $lib->html_stop(__FILE__, __FUNCTION__, __LINE__, "SQL Error: %s. Please contact Greg Parkin", $ora->dbErrMsg);
                                                    }

                                                    while ($ora->fetch())
                                                    {
                                                        printf("<option value=\"%s\">%s</option>\n", $ora->computer_status, $ora->computer_status);
                                                    }
                                                    ?>
                                                </select>

                                        </fieldset>
                                    </td>
                                </tr>
                                <!-- Asset Manager - Second Row -->
                                <tr>
                                    <td width="33%" align="left">
                                        <fieldset style="PADDING-BOTTOM: 2px; PADDING-LEFT: 2px; PADDING-RIGHT: 2px; PADDING-TOP: 2px; width: 27em; border-color: white">
                                            <legend><font color="#8f4300"><b>IGS Contracts</b></font></legend>

                                                <select style="padding: 2; width: 30em; height: 18em; font-size: 12px;"
                                                        name="computer_contract[]" multiple="multiple" size="10">
                                                    <?php
                                                    if ($ora->sql("select * from cct7_contract order by computer_contract") == false)
                                                    {
                                                        $lib->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $ora->sql_statement);
                                                        $lib->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $ora->dbErrMsg);
                                                        $lib->html_stop(__FILE__, __FUNCTION__, __LINE__, "SQL Error: %s. Please contact Greg Parkin", $ora->dbErrMsg);
                                                    }

                                                    while ($ora->fetch())
                                                    {
                                                        printf("<option value=\"%s\">%s</option>\n", $ora->computer_contract, $ora->computer_contract);
                                                    }
                                                    ?>
                                                </select>
                                        </fieldset>
                                    </td>
                                    <td width="33%" align="left">
                                        <fieldset style="PADDING-BOTTOM: 2px; PADDING-LEFT: 2px; PADDING-RIGHT: 2px; PADDING-TOP: 2px; width: 27em; border-color: white">
                                            <legend><font color="#8f4300"><b>State and City</b></font></legend>

                                                <select style="padding: 2; width: 30em; height: 18em; font-size: 12px;"
                                                        name="state_and_city[]" multiple="multiple" size="10">
                                                    <?php
                                                    if ($ora->sql("select * from cct7_state_city order by computer_state, computer_city") == false)
                                                    {
                                                        $lib->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $ora->sql_statement);
                                                        $lib->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $ora->dbErrMsg);
                                                        $lib->html_stop(__FILE__, __FUNCTION__, __LINE__, "SQL Error: %s. Please contact Greg Parkin", $ora->dbErrMsg);
                                                    }

                                                    while ($ora->fetch())
                                                    {
                                                        printf("<option value=\"%s:%s\">%s - %s</option>\n", $ora->computer_state, $ora->computer_city, $ora->computer_state, $ora->computer_city);
                                                    }
                                                    ?>
                                                </select>

                                        </fieldset>
                                    </td>
                                    <td width="33%" align="left">
                                        <fieldset style="PADDING-BOTTOM: 2px; PADDING-LEFT: 2px; PADDING-RIGHT: 2px; PADDING-TOP: 2px; width: 27em; border-color: white">
                                            <legend><font color="#8f4300"><b>Miscellaneous</b></font></legend>

                                                <select style="padding: 2; width: 30em; height: 18em; font-size: 12px;"
                                                        name="miscellaneous[]" multiple="multiple" size="10">
                                                    <option value="BCR:GOLD"        >GOLD</option>
                                                    <option value="BCR:NOT-GOLD"    >NOT-GOLD</option>
                                                    <option value="BCR:BLUE"        >BLUE</option>
                                                    <option value="BCR:SILVER"      >SILVER</option>
                                                    <option value="BCR:BRONZE"      >BRONZE</option>
                                                    <option value="BCR:NO-COLOR"    >NO-COLOR</option>
                                                    <option value="SPECIAL:HANDLING">SPECIAL HANDLING</option>
                                                    <?php
                                                    if ($ora->sql("select * from cct7_platform where computer_platform is not null order by computer_platform") == false)
                                                    {
                                                        $lib->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $ora->sql_statement);
                                                        $lib->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $ora->dbErrMsg);
                                                        $lib->html_stop(__FILE__, __FUNCTION__, __LINE__, "SQL Error: %s. Please contact Greg Parkin", $ora->dbErrMsg);
                                                    }

                                                    while ($ora->fetch())
                                                    {
                                                        printf("<option value=\"PLATFORM:%s\">%s</option>\n", $ora->computer_platform, $ora->computer_platform);
                                                    }
                                                    ?>
                                                </select>
                                        </fieldset>
                                    </td>
                                </tr>
                                <tr>
                                    <td align="left" colspan="2">
                                        Where the IP Address Starts with: <input type="text" id="ip_starts_with" name="ip_starts_with" size="20" maxlength="20" style="width: 200px;" />
                                    </td>
                                </tr>
                            </table>
                        </div>

                        <!-- System Lists -->
                        <div id="stab2" class="tab" style="width: 1194px; height: 520px;">
                            <table bgcolor="#ECE9D8" width="100%" ellspacing="2" cellpadding="2" style="height: 100%; color: black">
                                <tr>
                                    <td width="100%" align="left" valigh="top">
                                        <fieldset style="PADDING-BOTTOM: 2px; PADDING-LEFT: 2px; PADDING-RIGHT: 2px; PADDING-TOP: 2px; width: 1130px; border-color: white">
                                            <legend><font color="#8F4300"><b>User Defined System Lists</b></font></legend>
                                            <select name="list_name_id[]" multiple="multiple" size="30" style="width: 98em; font-size: 12px;">
                                                <?php
                                                $query  = "select ";
                                                $query .= "  t.list_name_id as list_name_id, ";
                                                $query .= "  t.list_name    as list_name ";
                                                $query .= "from ";
                                                $query .= "cct7_list_names t, ";
                                                $query .= "(select distinct ";
                                                $query .= "    n.user_cuid ";
                                                $query .= "  from ";
                                                $query .= "    cct7_netpin_to_cuid n, ";
                                                $query .= "    (select net_pin_no from cct7_netpin_to_cuid where user_cuid = '" . $_SESSION['user_cuid'] . "') u ";
                                                $query .= "  where ";
                                                $query .= "    n.net_pin_no = u.net_pin_no) m ";
                                                $query .= "where ";
                                                $query .= "  t.owner_cuid = m.user_cuid ";
                                                $query .= "order by ";
                                                $query .= "  t.list_name";

                                                if ($ora->sql2($query) == false)
                                                {
                                                    $lib->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $ora->sql_statement);
                                                    $lib->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $ora->dbErrMsg);
                                                    $lib->html_stop(__FILE__, __FUNCTION__, __LINE__, "SQL Error: %s. Please contact Greg Parkin", $ora->dbErrMsg);
                                                }
                                                /*
                                                if ($ora->sql2("select " .
                                                        "ln.list_name_id as list_name_id, " .
                                                        "ln.list_name    as list_name " .
                                                        "from " .
                                                        "cct7_list_names    ln, " .
                                                        "cct7_assign_groups ag " .
                                                        "where " .
                                                        "ln.group_name = ag.group_name and ag.login_cuid = '" . $_SESSION['user_cuid'] . "' " .
                                                        "order by ln.list_name") == false)
                                                {
                                                    $lib->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $ora->sql_statement);
                                                    $lib->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $ora->dbErrMsg);
                                                    $lib->html_stop(__FILE__, __FUNCTION__, __LINE__, "SQL Error: %s. Please contact Greg Parkin", $ora->dbErrMsg);
                                                }
                                                */

                                                $x = 0;
                                                while ($ora->fetch())
                                                {
                                                    $x++;
                                                    printf("<option value=\"%d\">%s</option>\n", $ora->list_name_id, $ora->list_name);
                                                }

                                                if ($x == 0)
                                                {
                                                    printf("<option value\"0\">No user system lists defined!</option>\n");
                                                }
                                                ?>
                                            </select>
                                            <center><font size="2" color="purple"><i>You can manage your System lists from the Edit menu.</i></font></center>
                                        </fieldset>
                                    </td>
                                </tr>
                            </table>
                        </div>

                        <!-- Hosts, Applications, Databases -->
                        <div id="stab3" class="tab" style="width: 1194px; height: 520px;">
                            <table bgcolor="#ECE9D8" width="100%" ellspacing="2" cellpadding="2" style="height: 100%; color: black">
                                <tr>
                                    <td width="100%" valign="top">
                                        <fieldset style="PADDING-BOTTOM: 2px; PADDING-LEFT: 2px; PADDING-RIGHT: 2px; PADDING-TOP: 2px; border-color: white">
                                            <legend><font color="#8F4300"><b>Target Hosts, Application Names and Database Names</b></font></legend>
                                            <textarea rows="26" id="target_theses_only" name="target_theses_only" cols="99"
                                                      style="width: 99%; resize: none; font-size: 14px;"></textarea>
                                        </fieldset>
                                    </td>
                                </tr>
                            </table>
                        </div>

                        <!-- Optional Note to Clients -->
                        <div id="stab4" class="tab" style="width: 1194px; height: 520px;">
                            <table bgcolor="#ECE9D8" width="100%" ellspacing="2" cellpadding="2" style="height: 100%; color: black">
                                <tr>
                                    <td width="100%" valign="top">
                                        <fieldset style="PADDING-BOTTOM: 2px; PADDING-LEFT: 2px; PADDING-RIGHT: 2px; PADDING-TOP: 2px; border-color: white">
                                            <legend><font color="#8F4300"><b>Send a optional note to clients about this work.</b></font></legend>
                                            <textarea rows="26" id="note_to_clients" name="note_to_clients" cols="99"
                                                      style="width: 99%; resize: none; font-size: 14px;"></textarea>
                                        </fieldset>
                                    </td>
                                </tr>
                            </table>
                        </div>

                    </div>

                </td>
            </tr>
        </table>
            <br>
            <input type="submit" name="button" value="Submit" onclick="return(remedyCheck());">
            <input type="reset" name="button" value="Reset">
            <br><br>
        </center>

        <script type="text/javascript">
            function remedyCheck()
            {
                if (copy_to)
                    return true;

                alert('Please supply a open Remedy CM ticket in the form above and click the Copy To button. Then click Submit again.');
                return false;  // Cancel submit operation.
            }

            var config2 = {
                tabs: {
                    name: 'tabs2',
                    active: 'stab1',
                    tabs: [
                        { id: 'stab1', caption: '<b>Asset Manager</b>' },
                        { id: 'stab2', caption: '<b>System Lists</b>' },
                        { id: 'stab3', caption: '<b>Hosts, Applications, Databases</b>' },
                        { id: 'stab4', caption: '<b>Optional Note to Clients</b>' }
                    ],
                    onClick: function (event)
                    {
                        $('#system_tab .tab').hide();
                        $('#system_tab #' + event.target).show();
                    }
                }
            };

            $(function ()
            {
                $('#tabs2').w2tabs(config2.tabs);
                //$('#stab1').show();
                w2ui.tabs2.click('stab1');
            });
        </script>

        <script type="text/javascript" src="js/calendar.js"></script>
        <script type="text/javascript">
            calendar.set("email_reminder1_date_char");
            calendar.set("email_reminder2_date_char");
            calendar.set("email_reminder3_date_char");
            calendar.set("respond_by_date_char");
            calendar.set("schedule_start_date_char");
        </script>

        <script type="text/javascript">

            var copy_to = false;  // Do we have valid ticket copied into this CCT ticket?

            //
            // Retrieve the Remedy CM text for this ticket and copy it to our CCT ticket.
            //
            function copyTo()
            {
                var cm_ticket_no = document.getElementById('cm_ticket_no').value;

                if (cm_ticket_no.length == 0)
                {
                    alert('Please enter a Remedy CM ticket number before clicking this button.');
                    return;
                }
                //
                // Prepare the data that will be sent to ajax_ticket.php
                //
                var data = {
                    "cm_ticket_no":         cm_ticket_no
                };

                //
                // Create a JSON string from the selected row of data.
                //
                //var gridData = jQuery(TicketGrid).jqGrid('getRowData', Ticket_id);
                //var postData = JSON.stringify(gridData);
                //var postData = JSON.stringify(data);
                //alert(postData);

                document.getElementById("loader").style.display = "block";

                var url = 'ajax_remedy_cm_copy_to.php';

                $.ajax(
                    {
                        type:     "POST",
                        url:      url,
                        dataType: "json",
                        data:     JSON.stringify(data),
                        success:  function(data)
                        {
                            $(".loader").fadeOut("slow");

                            /*
                             for (var key in data)
                             {
                             var value = data[key];
                             // Use `key` and `value`

                             alert(key + ' = ' + data[key]);
                             }
                             */

                            if (data['ajax_status'] == 'FAILED')
                            {
                                alert(data['ajax_message']);
                                document.getElementById('work_description').innerHTML     = '';
                                document.getElementById('work_implementation').innerHTML  = '';
                                document.getElementById('work_backoff_plan').innerHTML    = '';
                                document.getElementById('work_business_reason').innerHTML = '';
                                document.getElementById('work_user_impact').innerHTML     = '';
                                document.getElementById('cm_start_date').value            = '';
                                document.getElementById('cm_end_date').value              = '';
                                document.getElementById('cm_duration_computed').value     = '';
                                document.getElementById('cm_ipl_boot').value              = '';
                                document.getElementById('cm_status').value                = '';
                                document.getElementById('cm_open_closed').value           = '';
                                document.getElementById('cm_close_date').value            = '';
                                document.getElementById('cm_owner_first_name').value      = '';
                                document.getElementById('cm_owner_last_name').value       = '';
                                document.getElementById('cm_owner_cuid').value            = '';
                                document.getElementById('cm_owner_group').value           = '';

                                copy_to = false;

                                return;
                            }

                            document.getElementById('work_description').innerHTML     = data['work_description'];
                            document.getElementById('work_implementation').innerHTML  = data['work_implementation'];
                            document.getElementById('work_backoff_plan').innerHTML    = data['work_backoff_plan'];
                            document.getElementById('work_business_reason').innerHTML = data['work_business_reason'];
                            document.getElementById('work_user_impact').innerHTML     = data['work_user_impact'];
                            document.getElementById('cm_start_date').value            = data['cm_start_date'];
                            document.getElementById('cm_end_date').value              = data['cm_end_date'];
                            document.getElementById('cm_duration_computed').value     = data['cm_duration_computed'];
                            document.getElementById('cm_ipl_boot').value              = data['cm_ipl_boot'];
                            document.getElementById('cm_status').value                = data['cm_status'];
                            document.getElementById('cm_open_closed').value           = data['cm_open_closed'];
                            document.getElementById('cm_close_date').value            = data['cm_close_date'];
                            document.getElementById('cm_owner_first_name').value      = data['cm_owner_first_name'];
                            document.getElementById('cm_owner_last_name').value       = data['cm_owner_last_name'];
                            document.getElementById('cm_owner_cuid').value            = data['cm_owner_cuid'];
                            document.getElementById('cm_owner_group').value           = data['cm_owner_group'];

                            copy_to = true;

                            alert('Data has been successfully copied.');
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

            //
            // Pull up a dialog box containing the Remedy Ticket
            //
            function viewRemedyCM()
            {
                var cm_ticket_no = document.getElementById('cm_ticket_no').value;
                var ticket_no    = 'NA';

                if (cm_ticket_no.length == 0)
                {
                    alert('Please enter a Remedy CM ticket number before clicking this button.');
                    return;
                }

                // view_remedy_cm.php?action=get&cm_ticket_no=CM0000314570&ticket_no=CCT70034521

                //var url = 'view_remedy_cm.php?ticket=' + cm_ticket_no;
                //
                // http://cct7.localhost/dialog_view_cm_ticket.php?cm_ticket_no=CM0000314574&ticket_no=CM0000314574
                //
                var url = 'dialog_view_cm_ticket.php?cm_ticket_no=' + cm_ticket_no + '&ticket_no=' + ticket_no;
                var content = '<iframe src="' + url + '" ' +
                    'style="border: none; min-width: 100%; min-height: 100%;">' + '</iframe>';

                var close_button =
                        '<a data-toggle="ticket_close" title="Close this dialog box.">' +
                        '<button class="btn" onclick="w2popup.close();">Close</button></a>';

                w2popup.open({
                    title     : 'View Remedy CM Ticket: ' + cm_ticket_no,
                    body      : content,
                    width     : 860,
                    height    : 600,
                    overflow  : 'hidden',
                    color     : '#333',
                    speed     : '0.3',
                    opacity   : '0.8',
                    modal     : false,
                    showClose : true,
                    showMax   : true,
                    onOpen    : function (event)
                    {

                    },
                    onClose   : function (event)
                    {

                    },
                    onMax     : function (event) { console.log('max'); },
                    onMin     : function (event) { console.log('min'); },
                    onKeydown : function (event) { console.log('keydown'); }
                });
            }

            //
            // Setup a event listener to close w2popup (iframe) windows.
            // This event is sent from the child popup iframe window to
            // the parent (this). The event will instruct the parent to
            // close the w2popup window.
            //
            window.addEventListener('message', function(e)
            {
                var key = e.message ? 'message' : 'data';
                var data = e[key];

                w2popup.close();

            },false);

            function validateRemedyTicket()
            {
                var cm_ticket_no = document.getElementById('cm_ticket_no').value;

                if (cm_ticket_no.length == 0)
                {
                    alert('Please enter a Remedy CM ticket number before clicking this button.');
                    return;
                }
                //
                // Prepare the data that will be sent to ajax_ticket.php
                //
                var data = {
                    "cm_ticket_no":         cm_ticket_no
                };

                //
                // Create a JSON string from the selected row of data.
                //
                //var gridData = jQuery(TicketGrid).jqGrid('getRowData', Ticket_id);
                //var postData = JSON.stringify(gridData);
                //var postData = JSON.stringify(data);
                //alert(postData);

                document.getElementById("loader").style.display = "block";

                var url = 'ajax_validate_remedy_ticket.php';

                $.ajax(
                    {
                        type:     "POST",
                        url:      url,
                        dataType: "json",
                        data:     JSON.stringify(data),
                        success:  function(data)
                        {
                            $(".loader").fadeOut("slow");

                            /*
                             for (var key in data)
                             {
                             var value = data[key];
                             // Use `key` and `value`

                             alert(key + ' = ' + data[key]);
                             }
                             */

                            if (data['ajax_status'] == 'FAILED')
                            {
                                alert(data['ajax_message']);
                                return;
                            }

                            alert('Data has been successfully copied.');

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

        <?php
        if (strlen($message) > 0)
        {
            printf("<script>alert('%s');</script>\n", $message);
        }
        ?>
    </form>
    </body>
    </html>
    <?php

    exit();
}

/**
 * @fn     step2()
 * @brief  Validate user data and generate a new CCT work request
 * @return null;
 */
function step2()
{
    global $ora, $lib;

    $lib->debug1(__FILE__, __FUNCTION__, __LINE__, "_POST");
    $lib->debug_r1(__FILE__, __FUNCTION__, __LINE__, $_POST);

    $lib->debug1(__FILE__, __FUNCTION__, __LINE__, "_GET");
    $lib->debug_r1(__FILE__, __FUNCTION__, __LINE__, $_GET);

    $lib->debug1(__FILE__, __FUNCTION__, __LINE__, "_REQUEST");
    $lib->debug_r1(__FILE__, __FUNCTION__, __LINE__, $_REQUEST);

    $lib->debug1(__FILE__, __FUNCTION__, __LINE__, "_SERVER");
    $lib->debug_r1(__FILE__, __FUNCTION__, __LINE__, $_SERVER);

    $lib->debug1(__FILE__, __FUNCTION__, __LINE__, "_SESSION");
    $lib->debug_r1(__FILE__, __FUNCTION__, __LINE__, $_SESSION);

    $tic = new cct7_tickets();
    $sys = new cct7_systems();

    //
    // work_activity
    //
    if (isset($_REQUEST['work_activity']) && strlen($_REQUEST['work_activity']) > 0)
    {
        $tic->work_activity = $_REQUEST['work_activity'];
    }

    //
    // reboot_required
    //
    if (isset($_REQUEST['reboot_required']) && strlen($_REQUEST['reboot_required']) > 0)
    {
        $tic->reboot_required = $_REQUEST['reboot_required'];
        $sys->reboot_required = $_REQUEST['reboot_required'];
    }

    //
    // approvals_required
    //
    if (isset($_REQUEST['approvals_required']) && strlen($_REQUEST['approvals_required']) > 0)
    {
        $tic->approvals_required = $_REQUEST['approvals_required'];
        $sys->approvals_required = $_REQUEST['approvals_required'];
    }

    //
    // The dates below are in the user's time zone as defined in the workstation they are running their browser in.
    // The dates are in the format of "Weekday, 'Month Name' 'Month Day', 'Year' (i.e. Monday, July 25, 2016)
    //
    // The library.php contains two functions called: to_gmt() and gmt_to_format(). Most classes use this library
    // as a base class so you access them pretty much everywhere.
    //
    // Because we are dealing with the date only we will append space followed by 00:00 to the end of each date
    // string and use to_gmt() to return the utime as if it were coming from the Mountain timezone (America/Denver).
    //

    if (isset($_SESSION['local_timezone_name']))
    {
        $tz = $_SESSION['local_timezone_name'];
    }
    else
    {
        $tz = 'America/Denver';
    }

    //
    // email_reminder1_date_char - String format example: Monday, July 25, 2016
    //
    if (isset($_REQUEST['email_reminder1_date_char']) && strlen($_REQUEST['email_reminder1_date_char']) > 0)
    {
        $dt = $_REQUEST['email_reminder1_date_char'] . ' 00:00';
        $tic->email_reminder1_date_char = $dt;
        $tic->email_reminder1_date_num  = $tic->to_gmt($dt, $tz);
    }

    //
    // email_reminder2_date_char - String format example: Monday, July 25, 2016
    //
    if (isset($_REQUEST['email_reminder2_date_char']) && strlen($_REQUEST['email_reminder2_date_char']) > 0)
    {
        $dt = $_REQUEST['email_reminder2_date_char'] . ' 00:00';
        $tic->email_reminder2_date_char = $dt;
        $tic->email_reminder2_date_num  = $tic->to_gmt($dt, $tz);
    }

    //
    // email_reminder3_date_char - String format example: Monday, July 25, 2016
    //
    if (isset($_REQUEST['email_reminder3_date_char']) && strlen($_REQUEST['email_reminder3_date_char']) > 0)
    {
        $dt = $_REQUEST['email_reminder3_date_char'] . ' 00:00';
        $tic->email_reminder3_date_char = $dt;
        $tic->email_reminder3_date_num  = $tic->to_gmt($dt, $tz);
    }

    //
    // respond_by_date_char - String format example: Monday, July 25, 2016
    //
    if (isset($_REQUEST['respond_by_date_char']) && strlen($_REQUEST['respond_by_date_char']) > 0)
    {
        $dt = $_REQUEST['respond_by_date_char'] . ' 00:00';

        $tic->respond_by_date_char = $dt;
        $tic->respond_by_date_num  = $tic->to_gmt($dt, $tz);

        $sys->system_respond_by_date_char = $dt;
        $sys->system_respond_by_date_num  = $sys->to_gmt($dt, $tz);
    }

    //
    // schedule_start_date_char - String format example: Monday, July 25, 2016
    //
    if (isset($_REQUEST['schedule_start_date_char']) && strlen($_REQUEST['schedule_start_date_char']) > 0)
    {
        $dt = $_REQUEST['schedule_start_date_char'] . ' 00:00';
        $tic->schedule_start_date_char = $dt;
        $tic->schedule_start_date_num  = $tic->to_gmt($dt, $tz);

        $sys->schedule_start_date_char = $dt;
        $sys->schedule_start_date_num  = $sys->to_gmt($dt, $tz);
    }

    //
    // Optional Remedy Ticket No.
    //
    if (isset($_REQUEST['cm_ticket_no']) && strlen($_REQUEST['cm_ticket_no']) > 0)
    {
        $tic->cm_ticket_no = $_REQUEST['cm_ticket_no'];
        $tic->copy_to      = isset($_REQUEST['copy_to']) ? $_REQUEST['copy_to'] : '';
    }

    //
    // work_description
    //
    if (isset($_REQUEST['work_description']) && strlen($_REQUEST['work_description']) > 0)
    {
        $tic->work_description = $_REQUEST['work_description'];
    }

    //
    // work_implementation
    //
    if (isset($_REQUEST['work_implementation']) && strlen($_REQUEST['work_implementation']) > 0)
    {
        $tic->work_implementation = $_REQUEST['work_implementation'];
    }

    //
    // work_backoff_plan
    //
    if (isset($_REQUEST['work_backoff_plan']) && strlen($_REQUEST['work_backoff_plan']) > 0)
    {
        $tic->work_backoff_plan = $_REQUEST['work_backoff_plan'];
    }

    //
    // work_business_reason
    //
    if (isset($_REQUEST['work_business_reason']) && strlen($_REQUEST['work_business_reason']) > 0)
    {
        $tic->work_business_reason = $_REQUEST['work_business_reason'];
    }

    //
    // work_user_impact
    //
    if (isset($_REQUEST['work_user_impact']) && strlen($_REQUEST['work_user_impact']) > 0)
    {
        $tic->work_user_impact = $_REQUEST['work_user_impact'];
    }

    if (strlen($tic->work_description) == 0 ||
        strlen($tic->work_implementation) == 0 ||
        strlen($tic->work_backoff_plan) == 0 ||
        strlen($tic->work_business_reason) == 0 ||
        strlen($tic->work_user_impact) == 0)
    {
        $error = "You must document the work description, implementation, back out plan, " .
                 "business reason and user impacts. This information is past onto your contacts " .
                 "so they approve the work request. Click the help icon if the upper right corner " .
                 "if you need assistance.";
        step1($error);
        return;
    }

	$tic->disable_scheduler        = "N";
	$sys->disable_scheduler        = "N";

    //
    // OS Maintenance Window - maintenance_window (remedy, weekly, monthly, quarterly)
    //
	if (isset($_REQUEST['maintenance_window']) && strlen($_REQUEST['maintenance_window']) > 0)
	{
		$tic->maintenance_window = $_REQUEST['maintenance_window'];
		$sys->maintenance_window = $_REQUEST['maintenance_window'];

		if ($_REQUEST['maintenance_window'] == 'remedy')
        {
			$tic->disable_scheduler = "Y";
            $sys->disable_scheduler = "Y";
        }
	}

	$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "disable_scheduler = %s", $tic->disable_scheduler);

	//
    // Add the Remedy CM ticket data that we want to store in the cct7_tickets records
    //
	if (isset($_REQUEST['cm_start_date']) && strlen($_REQUEST['cm_start_date']) > 0)
	{
		$tic->cm_start_date_num = $_REQUEST['cm_start_date'];
		$sys->cm_start_date_num = $_REQUEST['cm_start_date'];
	}

	if (isset($_REQUEST['cm_end_date']) && strlen($_REQUEST['cm_end_date']) > 0)
	{
		$tic->cm_end_date_num = $_REQUEST['cm_end_date'];
		$sys->cm_end_date_num = $_REQUEST['cm_end_date'];
	}

	if (isset($_REQUEST['cm_duration_computed']) && strlen($_REQUEST['cm_duration_computed']) > 0)
	{
		$tic->cm_duration_computed = $_REQUEST['cm_duration_computed'];
		$sys->cm_duration_computed = $_REQUEST['cm_duration_computed'];
	}

	if (isset($_REQUEST['cm_ipl_boot']) && strlen($_REQUEST['cm_ipl_boot']) > 0)
	{
		$tic->cm_ipl_boot = $_REQUEST['cm_ipl_boot'];
	}

	if (isset($_REQUEST['cm_status']) && strlen($_REQUEST['cm_status']) > 0)
	{
		$tic->cm_status = $_REQUEST['cm_status'];
	}

	if (isset($_REQUEST['cm_open_closed']) && strlen($_REQUEST['cm_open_closed']) > 0)
	{
		$tic->cm_open_closed = $_REQUEST['cm_open_closed'];
	}

	if (isset($_REQUEST['cm_close_date']) && strlen($_REQUEST['cm_close_date']) > 0)
	{
		$tic->cm_close_date_num = $_REQUEST['cm_close_date'];
	}

	if (isset($_REQUEST['cm_owner_first_name']) && strlen($_REQUEST['cm_owner_first_name']) > 0)
	{
		$tic->cm_owner_first_name = $_REQUEST['cm_owner_first_name'];
	}

	if (isset($_REQUEST['cm_owner_last_name']) && strlen($_REQUEST['cm_owner_last_name']) > 0)
	{
		$tic->cm_owner_last_name = $_REQUEST['cm_owner_last_name'];
	}

	if (isset($_REQUEST['cm_owner_cuid']) && strlen($_REQUEST['cm_owner_cuid']) > 0)
	{
		$tic->cm_owner_cuid = strtolower($_REQUEST['cm_owner_cuid']);
	}

	if (isset($_REQUEST['cm_owner_group']) && strlen($_REQUEST['cm_owner_group']) > 0)
	{
		$tic->cm_owner_group = $_REQUEST['cm_owner_group'];
	}

	if (isset($_REQUEST['note_to_clients']) && strlen($_REQUEST['note_to_clients']) > 0)
	{
		$tic->note_to_clients = $_REQUEST['note_to_clients'];
	}

    //
    // Identify what CSC Banners to pull contacts from.
    //
	$tic->csc_banner1  = "Y";
	$tic->csc_banner2  = "Y";
	$tic->csc_banner3  = "Y";
	$tic->csc_banner4  = "Y";
	$tic->csc_banner5  = "Y";
	$tic->csc_banner6  = "Y";
	$tic->csc_banner7  = "Y";
	$tic->csc_banner8  = "Y";
	$tic->csc_banner9  = "Y";
	$tic->csc_banner10 = "Y";

    /**
    $tic->csc_banner1  = "N";
    $tic->csc_banner2  = "N";
    $tic->csc_banner3  = "N";
    $tic->csc_banner4  = "N";
    $tic->csc_banner5  = "N";
    $tic->csc_banner6  = "N";
    $tic->csc_banner7  = "N";
    $tic->csc_banner8  = "N";
    $tic->csc_banner9  = "N";
    $tic->csc_banner10 = "N";
    $got_one = false;

    if (isset($_REQUEST['cb1']))
    {
        $tic->csc_banner1 = "Y";
        $got_one = true;
    }

    if (isset($_REQUEST['cb2']))
    {
        $tic->csc_banner2 = "Y";
        $got_one = true;
    }

    if (isset($_REQUEST['cb3']))
    {
        $tic->csc_banner3 = "Y";
        $got_one = true;
    }

    if (isset($_REQUEST['cb4']))
    {
        $tic->csc_banner4 = "Y";
        $got_one = true;
    }

    if (isset($_REQUEST['cb5']))
    {
        $tic->csc_banner5 = "Y";
        $got_one = true;
    }

    if (isset($_REQUEST['cb6']))
    {
        $tic->csc_banner6 = "Y";
        $got_one = true;
    }

    if (isset($_REQUEST['cb7']))
    {
        $tic->csc_banner7 = "Y";
        $got_one = true;
    }

    if (isset($_REQUEST['cb8']))
    {
        $tic->csc_banner8 = "Y";
        $got_one = true;
    }

    if (isset($_REQUEST['cb9']))
    {
        $tic->csc_banner10 = "Y";
        $got_one = true;
    }

    if (isset($_REQUEST['cb10']))
    {
        $tic->csc_banner10 = "Y";
        $got_one = true;
    }

    //
    // If the user turned off all the checkboxes targeting CSC banners, then turn them all back on.
    //
    if ($got_one == false)
    {
        $tic->csc_banner1  = "Y";
        $tic->csc_banner2  = "Y";
        $tic->csc_banner3  = "Y";
        $tic->csc_banner4  = "Y";
        $tic->csc_banner5  = "Y";
        $tic->csc_banner6  = "Y";
        $tic->csc_banner7  = "Y";
        $tic->csc_banner8  = "Y";
        $tic->csc_banner9  = "Y";
        $tic->csc_banner10 = "Y";
    }
    */

    $tic->exclude_virtual_contacts = "N";

    /*
	if (isset($_REQUEST['no_virt_cons']))
	{
		$tic->exclude_virtual_contacts = "Y";
	}
    */

    //
    // Don't include virtual contacts anymore. Too confusing for users.
    //
	$tic->exclude_virtual_contacts = "Y";

	$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "exclude_virtual_contacts = %s", $tic->exclude_virtual_contacts);

    //
    // Variables to hold all the user's server selection criteria.
    //
    $target_these_only       = '';
    $ip_starts_with          = '';
    $computer_managing_group = array();
    $computer_os_lite        = array();
    $computer_status         = array();
    $computer_contract       = array();
    $state_and_city          = array();
    $miscellaneous           = array();
    $system_lists            = array();
    $got_gen_criteria        = false;

    if (isset($_REQUEST['computer_managing_group']) && count($_REQUEST['computer_managing_group']) > 0)
    {
        $got_gen_criteria = true;
        foreach ($_REQUEST['computer_managing_group'] as $group)
        {
            array_push($computer_managing_group, $group);
        }
    }

    if (isset($_REQUEST['computer_os_lite']) && count($_REQUEST['computer_os_lite']) > 0)
    {
        $got_gen_criteria = true;
        foreach ($_REQUEST['computer_os_lite'] as $os_lite)
        {
            array_push($computer_os_lite, $os_lite);
        }
    }

    if (isset($_REQUEST['computer_status']) && count($_REQUEST['computer_status']) > 0)
    {
        $got_gen_criteria = true;
        foreach ($_REQUEST['computer_status'] as $status)
        {
            array_push($computer_status, $status);
        }
    }

    if (isset($_REQUEST['computer_contract']) && count($_REQUEST['computer_contract']) > 0)
    {
        $got_gen_criteria = true;
        foreach ($_REQUEST['computer_contract'] as $contract)
        {
            array_push($computer_contract, $contract);
        }
    }

    if (isset($_REQUEST['state_and_city']) && count($_REQUEST['state_and_city']) > 0)
    {
        $got_gen_criteria = true;
        foreach ($_REQUEST['state_and_city'] as $sc)
        {
            array_push($state_and_city, $sc);
        }
    }

    if (isset($_REQUEST['miscellaneous']) && count($_REQUEST['miscellaneous']) > 0)
    {
        $got_gen_criteria = true;
        foreach ($_REQUEST['miscellaneous'] as $misc)
        {
            array_push($miscellaneous, $misc);
        }
    }

    if (isset($_REQUEST['list_name_id']) && count($_REQUEST['list_name_id']) > 0)
    {
        $got_gen_criteria = true;
        foreach ($_REQUEST['list_name_id'] as $id)
        {
            array_push($system_lists, $id);
        }
    }

    if (isset($_REQUEST['target_theses_only']) && strlen($_REQUEST['target_theses_only']) > 0)
    {
        $got_gen_criteria = true;
        $target_these_only = $_REQUEST['target_theses_only'];
    }

    if (isset($_REQUEST['ip_starts_with']) && strlen($_REQUEST['ip_starts_with']) > 0)
    {
        $got_gen_criteria = true;
        $ip_starts_with = $_REQUEST['ip_starts_with'];
    }

    if ($got_gen_criteria == false)
    {
        $lib->debug1(__FILE__, __FUNCTION__, __LINE__, "No servers were selected.");
        $error = "You did not select any servers for this work request. Click the help icon if the upper right corner if you need assistance.";
        step1($error);
    }

    //
    // Create the ticket and return the new $ticket_no number.
    //
    $ticket_no = $tic->addTicket();

    if ($ticket_no == null)
    {
        step1($tic->error);  // Oops, something is wrong!
        return;
    }

    //
    // Copy the server selection data into the $sys (a.k.a. cct7_systems.php class)
    //
    $sys->target_these_only        = $target_these_only;        // "lxomp11m, lxomt12m";
    $sys->computer_managing_group  = $computer_managing_group;  // array( 'CMP-UNIX', 'SMU');
    $sys->computer_os_lite         = $computer_os_lite;         // array( 'HPUX', 'Linux', 'SunOS' );
    $sys->computer_status          = $computer_status;          // array( 'PRE-PRODUCTION', 'PRODUCTION' );
    $sys->computer_contract        = $computer_contract;        // array( 'IGS FULL CONTRACT UNIX-PROD', 'IGS SUPPORT FS HYPERVISOR (NB)' );
    $sys->state_and_city           = $state_and_city;           // array( 'CO:DENVER', 'NE:OMAHA' );
    $sys->miscellaneous            = $miscellaneous;            // array( 'BCR:GOLD', 'SPECIAL:HANDLING', 'PLATFORM:MIDRANGE' );
    $sys->system_lists             = $system_lists;             // array( '1' );

    $sys->csc_banner1              = $tic->csc_banner1;
    $sys->csc_banner2              = $tic->csc_banner2;
    $sys->csc_banner3              = $tic->csc_banner3;
    $sys->csc_banner4              = $tic->csc_banner4;
    $sys->csc_banner5              = $tic->csc_banner5;
    $sys->csc_banner6              = $tic->csc_banner6;
    $sys->csc_banner7              = $tic->csc_banner7;
    $sys->csc_banner8              = $tic->csc_banner8;
    $sys->csc_banner9              = $tic->csc_banner9;
    $sys->csc_banner10             = $tic->csc_banner10;

    $sys->exclude_virtual_contacts = $tic->exclude_virtual_contacts;

    $sys->disable_scheduler        = $tic->disable_scheduler;

    if (strlen($ip_starts_with) > 0)
    {
        $sys->ip_starts_with = $ip_starts_with;                // like: '151.117'
    }

    //
    // Generate the New Work Schedule from all the user's data.
    //
    if ($sys->newWorkSchedule($ticket_no) == false)
    {
        $tic->delete($ticket_no);  // There was a problem. Cleanup and return error to user.
        step1($tic->error);
        return;
    }

    //
    // Run updateScheduleDates()
    //
    if ($tic->updateScheduleDates($ticket_no) == false)
    {
		$tic->delete($ticket_no);  // There was a problem. Cleanup and return error to user.
		step1($tic->error);
		return;
    }

    //
    // Update the statistics in the ticket. This data was gather when we ran $sys->newWorkSchedule($ticket_no)
    //
    if ($tic->updateRunInformation(
        $ticket_no,
        $sys->cm_start_date,         // Start Date for the Remedy CM Ticket
        $sys->cm_end_date,           // End Date for the Remedy CM Ticket
        $sys->total_servers_not_scheduled,  // Total servers not scheduled
        $sys->servers_not_scheduled,        // List of servers not scheduled.
        $sys->generator_runtime             // Total minutes and seconds the server took to generate the schedule.
        ) == false)
    {
        $tic->delete($ticket_no);  // There was a problem. Cleanup and return error to user.
        step1($tic->error);
        return;
    }

    if ($tic->getTicket($ticket_no) == false)
    {
        $tic->delete($ticket_no);  // There was a problem. Cleanup and return error to user.
        step1($tic->error);
        return;
    }

    //
    // Print out a summary report.
    //
    ?>

    <form name="f1">

    <h2><u>Summary</u></h2>

    <table border="0" cellpadding="2" cellspacing="2">

        <!--                  Ticket: CCT700000001           -->
        <!--                    Date: 07/31/2016             -->
        <!--                   Owner: Greg Parkin            -->
        <!--                  Status: DRAFT                  -->
        <!--           Work Activity: Patching               -->

        <tr>
            <td align="right" valign="top"><b>Ticket:</b></td>
            <td align="left" valign="top">
                <?php printf("%s", $ticket_no); ?>
            </td>
        </tr>
        <tr>
            <td align="right" valign="top"><b>Date:</b></td>
            <td align="left" valign="top">
                <?php printf("%s", $tic->insert_date_char); ?>
            </td>
        </tr>
        <tr>
            <td align="right" valign="top"><b>Owner:</b></td>
            <td align="left" valign="top">
                <?php printf("%s", $tic->owner_name); ?>
            </td>
        </tr>
        <tr>
            <td align="right" valign="top"><b>Status:</b></td>
            <td align="left" valign="top">
                <?php printf("%s", $tic->status); ?>
            </td>
        </tr>
        <tr>
            <td align="right" valign="top"><b>Work Activity:</b></td>
            <td align="left" valign="top">
                <?php printf("%s", $tic->work_activity); ?>
            </td>
        </tr>

        <tr><td colspan="2">&nbsp;</td></tr>

        <!--      Approvals Required: Y                      -->
        <!--         Reboot Required: Y                      -->

        <tr>
            <td align="right" valign="top"><b>Approvals Required:</b></td>
            <td align="left" valign="top">
                <?php printf("%s", $tic->approvals_required); ?>
            </td>
        </tr>
        <tr>
            <td align="right" valign="top"><b>Reboot Required:</b></td>
            <td align="left" valign="top">
                <?php printf("%s", $tic->reboot_required); ?>
            </td>
        </tr>

        <tr><td colspan="2">&nbsp;</td></tr>

        <!--    Scheduler Start Date: 08/03/2016             -->
        <!--         Respond By Date: 08/03/2016             -->
        <!--    Email Reminder Date1: 08/03/2016             -->
        <!--    Email Reminder Date2: 08/03/2016             -->
        <!--    Email Reminder Date3: 08/03/2016             -->

        <tr>
            <td align="right" valign="top"><b>Scheduler Start Date:</b></td>
            <td align="left" valign="top">
                <?php printf("%s", $tic->schedule_start_date_char); ?>
            </td>
        </tr>
        <tr>
            <td align="right" valign="top"><b>Respond By Date:</b></td>
            <td align="left" valign="top">
                <?php printf("%s", $tic->respond_by_date_char); ?>
            </td>
        </tr>
        <tr>
            <td align="right" valign="top"><b>Email Reminder Date1:</b></td>
            <td align="left" valign="top">
                <?php printf("%s", $tic->email_reminder1_date_char); ?>
            </td>
        </tr>
        <tr>
            <td align="right" valign="top"><b>Email Reminder Date2:</b></td>
            <td align="left" valign="top">
                <?php printf("%s", $tic->email_reminder2_date_char); ?>
            </td>
        </tr>
        <tr>
            <td align="right" valign="top"><b>Email Reminder Date3:</b></td>
            <td align="left" valign="top">
                <?php printf("%s", $tic->email_reminder3_date_char); ?>
            </td>
        </tr>

        <tr><td colspan="2">&nbsp;</td></tr>

        <!--      Work Schedule From: 08/03/2016 21:00       -->
        <!--      Work Schedule   To: 08/31/2016 23:59       -->

        <tr>
            <td align="right" valign="top"><b>Work Schedule From:</b></td>
            <td align="left" valign="top">
                <?php
                if ($tic->cm_start_date_num == 0)
                {
					printf("(See Remedy)");
                }
                else
                {
					printf("%s", $tic->cm_start_date_char);
                }
                ?>
            </td>
        </tr>
        <tr>
            <td align="right" valign="top"><b>Work Schedule  To:</b></td>
            <td align="left" valign="top">
                <?php
				if ($tic->cm_end_date_num == 0)
				{
					printf("(See Remedy)");
				}
				else
				{
					printf("%s", $tic->cm_end_date_char);
				}
                ?>
            </td>
        </tr>

        <tr><td colspan="2">&nbsp;</td></tr>

        <!--       Servers Scheduled: 24                     -->
        <!--   Servers Not Scheduled: 3                      -->
        <!--       Servers Not Found: micky, duffy, harry    -->
        <!-- Total Generator Runtime: 0 minutes, 46 seconds  -->

        <tr>
            <td align="right" valign="top"><b>Servers Scheduled:</b></td>
            <td align="left" valign="top">
                <?php printf("%d", $tic->total_servers_scheduled); ?>
            </td>
        </tr>
        <tr>
            <td align="right" valign="top"><b>Servers Not Scheduled:</b></td>
            <td align="left" valign="top">
                <?php printf("%d", $tic->total_servers_not_scheduled); ?>
            </td>
        </tr>
        <tr>
            <td align="right" valign="top"><b>Servers Not Found:</b></td>
            <td align="left" valign="top">
                <?php printf("%s", $tic->servers_not_scheduled); ?>
            </td>
        </tr>
        <tr>
            <td align="right" valign="top"><b>Total Generator Runtime:</b></td>
            <td align="left" valign="top">
                <?php printf("%s", $tic->generator_runtime); ?>
            </td>
        </tr>

        <tr><td colspan="2">&nbsp;</td></tr>

        <!--        Work Description:                        -->
        <!--     Work Implementation:                        -->
        <!--      Work Backoff Plans:                        -->
        <!--   Work Business Reasons:                        -->
        <!--            Work Impacts:                        -->

        <tr>
            <td align="right" valign="top"><b>Work Description:</b></td>
            <td align="left" valign="top">
                <?php printf("%s", $tic->work_description); ?>
            </td>
        </tr>
        <tr>
            <td align="right" valign="top"><b>Work Implementation:</b></td>
            <td align="left" valign="top">
                <?php printf("%s", $tic->work_implementation); ?>
            </td>
        </tr>
        <tr>
            <td align="right" valign="top"><b>Work Backoff Plans:</b></td>
            <td align="left" valign="top">
                <?php printf("%s", $tic->work_backoff_plan); ?>
            </td>
        </tr>
        <tr>
            <td align="right" valign="top"><b>Work Business Reasons:</b></td>
            <td align="left" valign="top">
                <?php printf("%s", $tic->work_business_reason); ?>
            </td>
        </tr>
        <tr>
            <td align="right" valign="top"><b>Work Impacts:</b></td>
            <td align="left" valign="top">
                <?php printf("%s", $tic->work_user_impact); ?>
            </td>
        </tr>

        <tr><td colspan="2">&nbsp;</td></tr>

        <tr>
            <td align="left" colspan="2">
                <input type="button" name="button" value="Activate" onclick="buttonActivate();">
                <input type="button" name="button" value="Delete"   onclick="buttonDelete();">
                <input type="button" name="button" value="Open"     onclick="buttonOpen();">
            </td>
        </tr>
    </table>

    <p>
        The CCT Work Request has been created in DRAFT mode. That means no notifications have been sent out. Click on
        the Activate button place your ticket into ACTIVE mode and CCT send out notifications to all the contacts
        identified for each server in CSC.
    </p>

    <p>
        The Delete button will delete this work request from the database. Since the ticket is currently in DRAFT
        mode, no notifications have been sent out.
    </p>

    <p>
        The Open button just leaves your ticket in DRAFT mode and opens up the work requests you have generated in
        the work groups (netpin's) you belong too. This ticket will appear in that list with a status of DRAFT. You
        can activate your ticket in the open work request screen and delete as well.
    </p>

    <script type="text/javascript">
        function ajaxCall(action)
        {
            var data;

            var ticket_no = '';

            ticket_no = '<?php echo $ticket_no; ?>';

            // where action = 'sendmail'
            var note         = '';
            var email_cc     = '';
            var email_bcc    = '';
            var subject_line = '';
            var message_body = '';

            // where action = 'update'
            var cm_ticket_no         = '';
            var work_description     = '';
            var work_implementation  = '';
            var work_backoff_plan    = '';
            var work_business_reason = '';
            var work_user_impact     = '';

            //
            // Prepare the data that will be sent to ajax_ticket.php
            //
            data = {
                "action":               action,
                "ticket_no":            ticket_no,
                "log_entry":            note,
                "email_cc":             email_cc,
                "email_bcc":            email_bcc,
                "subject_line":         subject_line,
                "message_body":         message_body,
                "cm_ticket_no":         cm_ticket_no,
                "work_description":     work_description,
                "work_implementation":  work_implementation,
                "work_backoff_plan":    work_backoff_plan,
                "work_business_reason": work_business_reason,
                "work_user_impact":     work_user_impact
            };

            // alert(JSON.stringify(data));

            var url = 'ajax_dialog_toolbar_open_tickets.php';

            $.ajax(
                {
                    type:     "POST",
                    url:      url,
                    dataType: "json",
                    data:     JSON.stringify(data),
                    success:  function(data)
                    {
                        /*
                        if (data['ajax_status'] != 'SUCCESS')
                        {
                            alert(data['ajax_message']);
                        }
                        else if (action == 'activate')
                        {
                            alert('Ticket has been activated.');
                        }
                        else if (action == 'delete')
                        {
                            alert('Ticket has been deleted.');
                        }
                        */
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

        function buttonActivate()
        {
            $.when(ajaxCall('activate')).done(function()
            {
                alert('Ticket Activated.');
                window.location= 'toolbar_open.php?what_tickets=group';
            });
        }

        function buttonDelete()
        {
            $.when(ajaxCall('delete')).done(function()
            {
                alert('Ticket has been deleted.');
                window.location= 'home_page.php';
            });
        }

        function buttonOpen()
        {
            window.location = 'toolbar_open.php?what_tickets=group';
        }
    </script>

    <?php
}
?>

</form>
</body>
</html>
