<?php
/**
 * view_remedy_cct.php
 *
 * @package   PhpStorm
 * @file      view_remedy_cct.php
 * @author    gparkin
 * @date      6/29/16
 * @version   7.0
 *
 * @brief     About this module.
 */


// set_include_path(".;C:\PHP\pear\pear;C:\www\cct7\classes;C:\www\cct7\servers;/xxx/www/cct7/classes:/xxx/www/cct7/servers");

ini_set('xdebug.collect_vars',    '5');
ini_set('xdebug.collect_vars',    'on');
ini_set('xdebug.collect_params',  '4');
ini_set('xdebug.dump_globals',    'on');
ini_set('xdebug.dump.SERVER',     'REQUEST_URI');
ini_set('xdebug.show_local_vars', 'on');

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

$ora = new oracle();
$lib = new library();

$lib->debug_start('view_remedy_cm.html');
date_default_timezone_set('America/Denver');

if (isset($_REQUEST['ticket']))
{
    $ticket_no = $_REQUEST['ticket'];
}
else
{
    $ticket_no = "test";
}

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
// away if there are any parms available. Below we just show the parm information if present.
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

<!DOCTYPE HTML>
<html>
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=EmulateIE10">
    <title>Remedy CM - Read Only</title>
    <link rel="stylesheet" type="text/css" media="all" href="css/tabcontent.css" />
    <style type="text/css">
        .button_blue
        {
            border: font-size: 11px;
            width: 80px;
            color: #FFFFFF;
            font-weight: bold;
            font-family: arial;
            background-color: #3333CC;
            text-decoration: none;
            cursor: url('cursors/nbblink2.ani');
        }
        .button_gray
        {
            border: font-size: 11px;
            width: 80px;
            color: #0099CC;
            font-weight: bold;
            font-family: arial;
            background-color: #EEEEEE;
            text-decoration: none;
            cursor: url('cursors/nbblink2.ani');
        }
        #bg_mask4 {
            position: absolute;
            top: 0;
            right: 0;
            bottom: 0;
            left: 0;
            margin: auto;
            margin-top: 0px;
            width: 910px;
            height: 626px;
            z-index: 15;
        }
        #frontlayer4 {
            position: absolute;
            top: 0;
            right: 0;
            bottom: 0;
            left: 0;
            margin: 5px 5px 5px 5px;
            padding : 10px;
            width: 905px;
            height: 621px;
            border: 0px solid black;
            z-index: 20;
        }
        #my_table
        {
            padding: 10px;
            border-radius: 10px;
            top: 10px;
            left: 10px;
            color: rgb(0, 0, 0); /* 136, 136, 136 = #888888 */
            font-family: Verdana, Geneva, sans-serif;
            font-size: 10pt;
            position: absolute;
            opacity: 1;
            box-shadow: 3px 3px 10px #000000;
            /* text-shadow: 1px 1px 0px #000000; */
            /* text-shadow: none !important; */
            /* text-align: center; */
            background-color: rgb(236, 233, 216); /* light tan */
            /*
            ** See: http://www.javascripter.net/faq/rgbtohex.htm for more colors
            ** RGB: 221, 221, 221 = #DDDDDD  light gray
            ** RGB: 236, 233, 216 = #ECE9D8  light tan
            ** RGB: 152, 251, 152 = #98FB98  light green
            ** RGB: 102, 205, 170 = #66CDAA  light blue
            */
        }
        input[disabled="disabled"], select[disabled="disabled"], textarea[disabled="disabled"]
        {
            opacity: 1 !important;
            color: black;
            background: white;
        }
    </style>

    <script type="text/javascript" src="js/tabcontent.js"></script>
    <script type="text/javascript">

        var info = new Array();

        info[0]  = "This is a read only view of the Remedy ticket. Any changes must be made in Remedy.";
        info[1]  = "If this is not the right ticket, you can enter the correct ticket no and press enter.";
        info[2]  = "The Reload button will retrieve the ticket found in the text box to the left of the button.";
        info[3]  = "The Next button takes you to Step3 where you select the servers you will be working on.";
        info[4]  = "Go back to New Work Request - Step1.";

        function sb(what)
        {
            document.getElementById('sb').innerHTML = '<font color=blue>' + info[what] + '</font>';
        }
    </script>
</head>
<body>
<form name="f1" method="get" action="view_remedy_cct.php">
    <?php
    $start_date = '';
    $end_date   = '';
    $tz         = $_SESSION['local_timezone_abbr'];

    $lib->debug1(__FILE__, __FUNCTION__, __LINE__, "ticket_no = (%s)", $ticket_no);

    if ($ora->sql("select * from cct7_tickets where cm_ticket_no = '" . $ticket_no . "'") == false)
    {
        $lib->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $ora->sql_statement);
        $lib->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $ora->dbErrMsg);
        $lib->html_stop(__FILE__, __FUNCTION__, __LINE__, "SQL Error: %s. Please contact Greg Parkin", $ora->dbErrMsg);
    }

    if ($ora->fetch() == false)
    {
        $lib->setErrorMessage = "Unable to pull ticket from cct7_tickets: " . $ticket_no;
    }

    //
    // Dates are stored in Remedy as numbers (GMT). Remedy provide me with the function
    // fn_number_to_date() to convert the numbers to DATE objects.
    //
    $query  =         "select ";
    $query .= sprintf("  fn_number_to_date(cm_create_date, '%s')             as cm_create_date, ",             $tz);
    $query .= sprintf("  fn_number_to_date(cm_start_date, '%s')              as cm_start_date, ",              $tz);
    $query .= sprintf("  fn_number_to_date(cm_end_date, '%s')                as cm_end_date, ",                $tz);
    $query .= sprintf("  fn_number_to_date(cm_close_date, '%s')              as cm_close_date, ",              $tz);
    $query .= sprintf("  fn_number_to_date(cm_last_modified, '%s')           as cm_last_modified, ",           $tz);
    $query .= sprintf("  fn_number_to_date(cm_late_date, '%s')               as cm_late_date, ",               $tz);
    $query .= sprintf("  fn_number_to_date(cm_last_status_change_time, '%s') as cm_last_status_change_time, ", $tz);
    $query .= sprintf("  fn_number_to_date(cm_turn_overdate, '%s')           as cm_turn_overdate ",            $tz);
    $query .= sprintf("from cct7_tickets where cm_ticket_no = '%s'", $ticket_no);

    $lib->debug_sql1(__FILE__, __FUNCTION__, __LINE__, $query);

    if ($ora->sql($query) == false)
    {
        $lib->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $ora->sql_statement);
        $lib->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $ora->dbErrMsg);
        printf("<p>%s %s %d: SQL Error: %s. Please contact Greg Parkin</p>\n", __FILE__, __FUNCTION__, __LINE__, $ora->dbErrMsg);
        exit(1);
    }

    if ($ora->fetch() == false)
    {
        printf("<p>%s %s %d: Cannot putll ticket: %s. Please contact Greg Parkin</p>\n", __FILE__, __FUNCTION__, __LINE__, $ticket_no);
        exit(1);
    }

    $start_date = $ora->start_date;   // Used to compute escalation dates
    $end_date   = $ora->end_date;

    $lib->debug1(__FILE__, __FUNCTION__, __LINE__, "cm_close_date = %s",              $ora->cm_close_date);
    $lib->debug1(__FILE__, __FUNCTION__, __LINE__, "cm_end_date = %s",                $ora->cm_end_date);
    $lib->debug1(__FILE__, __FUNCTION__, __LINE__, "cm_create_date = %s",             $ora->cm_create_date);
    $lib->debug1(__FILE__, __FUNCTION__, __LINE__, "cm_start_date = %s",              $ora->cm_start_date);
    $lib->debug1(__FILE__, __FUNCTION__, __LINE__, "cm_last_modified = %s",           $ora->cm_last_modified);
    $lib->debug1(__FILE__, __FUNCTION__, __LINE__, "cm_late_date = %s",               $ora->cm_late_date);
    $lib->debug1(__FILE__, __FUNCTION__, __LINE__, "cm_last_status_change_time = %s", $ora->cm_last_status_change_time);
    $lib->debug1(__FILE__, __FUNCTION__, __LINE__, "cm_turn_overdate = %s",           $ora->cm_turn_overdate);

    if ($ora->sql(
            "select " .
            "CASE WHEN cm_tested_itv         is not NULL THEN 'checked' END as cm_tested_itv, " .
            "CASE WHEN cm_tested_endtoend    is not NULL THEN 'checked' END as cm_tested_endtoend, " .
            "CASE WHEN cm_tested_development is not NULL THEN 'checked' END as cm_tested_development, " .
            "CASE WHEN cm_tested_user        is not NULL THEN 'checked' END as cm_tested_user, " .
            "CASE WHEN cm_tested_orl         is not NULL THEN 'checked' END as cm_tested_orl, " .
            "CASE WHEN cm_emergency_change   is not NULL THEN 'checked' END as cm_emergency_change, " .
            "CASE WHEN cm_featured_project   is not NULL THEN 'checked' END as cm_featured_project " .
            "from cct7_tickets where cm_ticket_no = '" . $ticket_no . "'") == false)
    {
        $lib->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $ora->sql_statement);
        $lib->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $ora->dbErrMsg);
        printf("<p>%s %s %d: SQL Error: %s. Please contact Greg Parkin</p>\n", __FILE__, __FUNCTION__, __LINE__, $ora->dbErrMsg);
        exit(1);
    }

    if ($ora->fetch() == false)
    {
        printf("<p>%s %s %d: Cannot pull ticket: %s. Please contact Greg Parkin</p>\n", __FILE__, __FUNCTION__, __LINE__, $ticket_no);
        exit(1);
    }
    
    $lib->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "ipl_boot = (%s)", $ora->cm_ipl_boot);
    ?>

    <!-- BEGIN: STEP12 WORK ACTIVITY -->
    <div id="bg_mask4" align="center">
        <div id="frontlayer4" align="center">
            <table id="my_table">
                <tr>
                    <td align="center">
                        <ul id="tickettabs" class="shadetabs">
                            <li><a href="#" rel="ticket1" class="selected">General</a></li>
                            <li><a href="#" rel="ticket2">Description / Implemenation Instructions</a></li>
                            <li><a href="#" rel="ticket3">Backoff Plans</a></li>
                            <li><a href="#" rel="ticket4">Business Reason / Impact</a></li>
                        </ul>
                        <div style="border:1px solid gray; margin-bottom: 1em; padding: 10px">

                            <!-- BEGIN: GENERAL -->
                            <div id="ticket1" class="tabcontent" style="width: 850px; height: 480px;">
                                <table bgcolor="#ECE9D8" width="95%" heigth="100%" cellspacing="0" cellpadding="0">
                                    <tr>
                                        <td width="33%" align="left">
                                            <b>Remedy Ticket</b><br>
                                            <input type=text name="change_id" size="27" style="width: 15em" value="<?php echo $ora->cm_ticket_no ?>"
                                                   disabled="disabled">
                                        </td>
                                        <td width="33%" align="left" size="20">
                                            Start Date &amp; Time<br>
                                            <input type=text name="start_date" size="27" style="width: 15em" value="<?php printf("%s", $ora->cm_start_date); ?>"
                                                   disabled="disabled">
                                        </td>
                                        <td width="34%" align="left">
                                            Approval Status<br>
                                            <input type=text name="status" size="27" style="width: 15em" value="<?php echo $ora->cm_status ?>"
                                                   disabled="disabled">
                                        </td>
                                    </tr>
                                    <tr>
                                        <td width="33%" align="left">
                                            Open/Closed<br>
                                            <input type=text name="open_closed" size="27" style="width: 15em" value="<?php echo $ora->cm_open_closed ?>"
                                                   disabled="disabled">
                                        </td>
                                        <td width="33%" align="left" size="20">
                                            End Date &amp; Time<br>
                                            <input type=text name="end_date" size="27" style="width: 15em" value="<?php printf("%s", $ora->cm_end_date); ?>"
                                                   disabled="disabled">
                                        </td>
                                        <td width="34%" align="left">
                                            Duration (Days:Hrs:Min)<br>
                                            <input type=text name="duration_computed" size="27" style="width: 15em" value="<?php echo $ora->cm_duration_computed ?>"
                                                   disabled="disabled">
                                        </td>
                                    </tr>
                                    <tr>
                                        <td width="33%" align="left">
                                            Closed By<br>
                                            <input type=text name="closed_by" size="27" style="width: 15em" value="<?php echo $ora->cm_closed_by ?>"
                                                   disabled="disabled">
                                        </td>
                                        <td width="33%" align="left" size="20">

                                            Close Date & Time<br>
                                            <input type=text name="close_date" size="27" style="width: 15em" value="<?php printf("%s", $ora->cm_close_date); ?>"
                                                   disabled="disabled">
                                        </td>
                                        <td width="34%" align="left">
                                            Close Code<br>
                                            <input type=text name="close_code" size="27" style="width: 15em" value="<?php echo $ora->cm_close_code ?>"
                                                   disabled="disabled">
                                        </td>
                                    </tr>
                                    <tr>
                                        <td width="100%" colspan="3"><hr></td>
                                    </tr>
                                    <tr>
                                        <td width="33%" align="left">
                                            Owner First Name<br>
                                            <input type=text name="owner_first_name" size="27" style="width: 15em" value="<?php echo $ora->cm_owner_first_name ?>"
                                                   disabled="disabled">
                                        </td>
                                        <td width="33%" align="left" size="20">
                                            Owner Last Name<br>
                                            <input type=text name="owner_last_name" size="27" style="width: 15em" value="<?php echo $ora->cm_owner_last_name ?>"
                                                   disabled="disabled">
                                        </td>
                                        <td width="34%" align="left">
                                            <b>Owner CUID</b><br>
                                            <input type=text name="owner_cuid" size="27" style="width: 15em" value="<?php echo strtolower($ora->cm_owner_cuid) ?>"
                                                   disabled="disabled">
                                        </td>
                                    </tr>
                                    <tr>
                                        <td width="33%" align="left">
                                            Owner Group<br>
                                            <input type=text name="owner_group" size="27" style="width: 15em" value="<?php echo $ora->cm_assign_group ?>"
                                                   disabled="disabled">
                                        </td>
                                        <td width="33%" align="left">
                                            Director<br>
                                            <input type=text name="director" size="27" style="width: 15em" value="<?php echo $ora->cm_director ?>"
                                                   disabled="disabled">
                                        </td>
                                        <td width="34%" align="left">
                                            Manager<br>
                                            <input type=text name="manager" size="27" style="width: 15em" value="<?php echo $ora->cm_manager ?>"
                                                   disabled="disabled">
                                        </td>
                                    </tr>
                                    <tr>
                                        <td width="33%" align="left">
                                            Phone<br>
                                            <input type=text name="phone" size="27" style="width: 15em" value="<?php echo $ora->cm_phone ?>"
                                                   disabled="disabled">
                                        </td>
                                        <td width="33%" align="left">
                                            E-Mail<br>
                                            <input type=text name="email" size="27" style="width: 15em" value="<?php echo $ora->cm_email ?>"
                                                   disabled="disabled">
                                        </td>
                                        <td width="34%" align="left">
                                            Company Name<br>
                                            <input type=text name="company_name" size="27" style="width: 15em" value="<?php echo $ora->cm_company_name ?>"
                                                   disabled="disabled">
                                        </td>
                                    </tr>
                                    <tr>
                                        <td width="33%" align="left">
                                            Pager<br>
                                            <input type=text name="phone" size="27" style="width: 15em" value="<?php echo $ora->cm_phone ?>"
                                                   disabled="disabled">
                                        </td>
                                        <td width="33%" align="left">
                                            PIN<br>
                                            <input type=text name="pin" size="27" style="width: 15em" value="<?php echo $ora->cm_pin ?>"
                                                   disabled="disabled">
                                        </td>
                                        <td width="34%" align="left">&nbsp;</td>
                                    </tr>
                                    <tr>
                                        <td width="100%" colspan="3"><hr></td>
                                    </tr>
                                    <tr>
                                        <td width="33%" align="left">
                                            <b>Category</b><br>
                                            <input type=text name="category" size="27" style="width: 15em"
                                                   value="<?php echo $ora->cm_category ?>"  disabled="disabled">
                                        </td>
                                        <td width="33%" align="left">
                                            <b>Category Type</b><br>
                                            <input type=text name="category_type" size="27" style="width: 15em"
                                                   value="<?php echo $ora->cm_category_type ?>"	 disabled="disabled">
                                        </td>
                                        <td width="34%" align="left">
                                            Tested<br>
                                            <input type=text name="tested" size="27" style="width: 15em"
                                                   value="<?php printf("%s", $ora->cm_tested == 1 ? "Yes" : "No"); ?>"  disabled="disabled">
                                        </td>
                                    </tr>
                                    <tr>
                                        <td width="33%" align="left">
                                            <b>Scheduling Flexibility</b><br>
                                            <input type=text name="scheduling_flexibility" size="27" style="width: 15em"
                                                   value="<?php echo $ora->cm_scheduling_flexibility ?>"  disabled="disabled">
                                        </td>
                                        <td width="33%" align="left">
                                            <b>Risk</b><br>
                                            <input type=text name="risk" size="27" style="width: 15em"
                                                   value="<?php echo $ora->cm_risk ?>"  disabled="disabled">
                                        </td>
                                        <td width="34%" rowspan="2" valign="top" align="left">
                                            <table border="0" cellpadding="0" cellspacing="0" style="border-collapse: collapse" bordercolor="#111111" width="100%">
                                                <tr>
                                                    <td width="54%" align="left">System Tested</td>
                                                    <td width="46%" align="left">
                                                        <input type="checkbox" name="tested_itv" value="ON"  disabled="disabled"
                                                            <?php echo " $ora->cm_tested_itv"; ?>>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td width="54%" align="left">End to End Tested</td>
                                                    <td width="46%" align="left">
                                                        <input type="checkbox" name="test_endtoend" value="ON"  disabled="disabled"
                                                            <?php echo " $ora->cm_test_endtoend"; ?>>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td width="54%" align="left">Development Tested</td>
                                                    <td width="46%" align="left">
                                                        <input type="checkbox" name="tested_development" value="ON"  disabled="disabled"
                                                            <?php echo " $ora->cm_tested_development"; ?>>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td width="54%" align="left">User Tested</td>
                                                    <td width="46%" align="left">
                                                        <input type="checkbox" name="tested_user" value="ON"  disabled="disabled"
                                                            <?php echo " $ora->cm_tested_user"; ?>>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td width="54%" align="left">ORL Tested</td>
                                                    <td width="46%" align="left">
                                                        <input type="checkbox" name="tested_orl" value="ON"  disabled="disabled"
                                                            <?php echo " $ora->cm_tested_orl"; ?>>
                                                    </td>
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td width="33%" valign="top" align="left">
                                            <table border="0" cellpadding="0" cellspacing="0" style="border-collapse: collapse" bordercolor="#111111" width="100%">
                                                <tr>
                                                    <td width="60%" align="left">Emergency Change?</td>
                                                    <td width="40%" align="left">
                                                        <input type="checkbox" name="emergency_change" value="ON"  disabled="disabled"
                                                            <?php echo " $ora->cm_emergency_change"; ?>>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td width="60%" align="left">IR for Featured Project</td>
                                                    <td width="40%" align="left">
                                                        <input type="checkbox" name="ir_for_featured_project" value="ON"  disabled="disabled"
                                                            <?php echo " $ora->cm_featured_project"; ?>>
                                                    </td>
                                                </tr>
                                            </table>
                                        </td>
                                        <td width="33%" align="left">
                                            <table border="0" cellpadding="0" cellspacing="0" style="border-collapse: collapse" bordercolor="#111111" width="100%">
                                                <tr>
                                                    <td width="52%" align="left"><b>IPL/Boot</b></td>
                                                    <td width="48%" align="left">Plan A or B</td>
                                                </tr>
                                                <tr>
                                                    <td width="52%" align="left">
                                                        <input type=text name="scheduling_flexibility" size="27" style="width: 5em"
                                                               value="<?php printf("%s", $ora->cm_ipl_boot == 1 ? "True" : "False"); ?>"  disabled="disabled">
                                                    </td>
                                                    <td width="48%" align="left">
                                                        <input type=text name="plan_a_b" size="27" style="width: 5em"
                                                               value="<?php echo $ora->cm_plan_a_b ?>"  disabled="disabled">
                                                    </td>
                                                </tr>
                                            </table>
                                            <br>
                                        </td>
                                    </tr>
                                </table>
                            </div><!-- END: GENERAL -->

                            <!-- BEGIN: DESCRIPTION IMPLEMENTATION INSTRUCTIONS -->
                            <div id="ticket2" class="tabcontent" style="width: 850px; height: 480px;">
                                <table bgcolor="#ECE9D8" width="95%" heigth="100%" cellspacing="0" cellpadding="0">
                                    <tr>
                                        <td width="100%">
                                            <fieldset style="PADDING-BOTTOM: 2px; PADDING-LEFT: 2px; PADDING-RIGHT: 2px; PADDING-TOP: 2px; border-color: white">
                                                <legend><font color="#8F4300"><b>Description</b></font></legend>
                    <textarea rows="13" name="description" cols="99" style="width: 99%"
                              disabled="disabled"><?php echo $ora->cm_description ?></textarea>
                                            </fieldset>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td width="100%">
                                            <fieldset style="PADDING-BOTTOM: 2px; PADDING-LEFT: 2px; PADDING-RIGHT: 2px; PADDING-TOP: 2px; border-color: white">
                                                <legend><font color="#8F4300"><b>Implementation Instructions</b></font></legend>
                    <textarea rows="13" name="implementation_instructions" cols="99" style="width: 99%"
                              disabled="disabled"><?php echo $ora->cm_implementation_instructions ?></textarea>
                                            </fieldset>
                                        </td>
                                    </tr>
                                </table>
                            </div><!-- END: DESCRIPTION IMPLEMENTATION INSTRUCTIONS -->

                            <!-- BEGIN: BACKOFF PLANS -->
                            <div id="ticket3" class="tabcontent" style="width: 850px; height: 480px;">
                                <table bgcolor="#ECE9D8" width="95%" heigth="100%" cellspacing="0" cellpadding="0">
                                    <tr>
                                        <td width="100%">
                                            <fieldset style="PADDING-BOTTOM: 2px; PADDING-LEFT: 2px; PADDING-RIGHT: 2px; PADDING-TOP: 2px; border-color: white">
                                                <legend><font color="#8F4300"><b>Backoff Plans</b></font></legend>
                    <textarea rows="28" name="backoff_plan" cols="99" style="width: 99%"
                              disabled="disabled"><?php echo $ora->cm_backoff_plan ?></textarea>
                                            </fieldset>
                                        </td>
                                    </tr>
                                </table>
                            </div><!-- END: BACKOFF PLANS -->

                            <!-- BEGIN: BUSINESS REASON -->
                            <div id="ticket4" class="tabcontent" style="width: 850px; height: 480px;">
                                <table bgcolor="#ECE9D8" width="95%" heigth="100%" cellspacing="0" cellpadding="0">
                                    <tr>
                                        <td width="100%">
                                            <fieldset style="PADDING-BOTTOM: 2px; PADDING-LEFT: 2px; PADDING-RIGHT: 2px; PADDING-TOP: 2px; border-color: white">
                                                <legend><font color="#8F4300"><b>Business Reasons</b></font></legend>
                    <textarea rows="13" name="business_reason" cols="99" style="width: 99%"
                              disabled="disabled"><?php echo $ora->cm_business_reason ?></textarea>
                                            </fieldset>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td width="100%">
                                            <fieldset style="PADDING-BOTTOM: 2px; PADDING-LEFT: 2px; PADDING-RIGHT: 2px; PADDING-TOP: 2px; border-color: white">
                                                <legend><font color="#8F4300"><b>Impact</b></font></legend>
                    <textarea rows="13" name="impact" cols="99" style="width: 99%"
                              disabled="disabled"><?php echo $ora->cm_impact ?></textarea>
                                            </fieldset>
                                        </td>
                                    </tr>
                                </table>
                            </div><!-- END: BUSINESS REASON -->
                        </div>
                    </td>
                </tr>
                <tr>
                    <td width="100%" align="center">
                        <input type="button" name="button" value="Activate" onclick="window.close();">
                        <input type="button" name="button" value="Cancel" onclick="window.close();">
                        <input type="button" name="button" value="Freeze" onclick="window.close();">
                        <input type="button" name="button" value="Unfreeze" onclick="window.close();">
                        <input type="button" name="button" value="Close" onclick="window.close();">
                    </td>
                </tr>
            </table>
        </div>
    </div>

    <script type="text/javascript">
        var ticket=new ddtabcontent("tickettabs");
        ticket.setpersist(false);
        ticket.setselectedClassTarget("link"); //"link" or "linkparent"
        ticket.init();
    </script>
</form>
</body>
</html>