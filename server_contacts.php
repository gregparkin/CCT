<?php
/**
 * server_contacts.php
 *
 * @package   PhpStorm
 * @file      server_contacts.php
 * @author    gparkin
 * @date      7/1/17
 * @version   7.0
 *
 * @brief     This program replaces the original "Trace Data Sources" tool from CCT 6.
 *            It is located under the Reports menu on the toolbar and is called "Server Contacts".
 *            This program displays all the contact information for a server so people can validate
 *            their contact information and know where they can go to update it.
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
// header() is a PHP function for modifying the HTML header information
// going to the client's browser. Here we tell their browser not to cache
// the page coming in so their browser will always rebuild the page from
// scratch instead of retrieving a copy of one from its cache.
//
// header("Expires: " . gmstrftime("%a, %d %b %Y %H:%M:%S GMT"));
//
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

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

$ora = new oracle();
$con = new cct7_contacts();
$lib = new library();

$lib->debug_start('server_contacts.html');
date_default_timezone_set('America/Denver');

$lib->globalCounter();

?>
	<html>
	<link rel="stylesheet" type="text/css" href="css/jqx.base.css">
	<link rel="stylesheet" type="text/css" href="css/jqx.bootstrap.css">
	<link rel="stylesheet" type="text/css" media="screen" href="css/jquery-ui.css">

	<!--
	  base, black-tie, blitzer, cupertino, dark-hive, dot-luv, eggplant, excite-bike, flick, hot-sneaks,
	  humanity, le-frog, mint-choc, overcast, pepper-grinder, redmond, smoothness, south-street, start,
	  sunny, swanky-purse, trontastic, ui-darkness, ui-lightness, vadar
	-->
	<link id="pagestyle" rel="stylesheet" type="text/css" href="css/themes/humanity/jquery-ui.css">
	<!-- 4.7.1 or 5.1.1 -->
	<link rel="stylesheet" type="text/css" media="screen" href="jqGrid.5.1.1/css/ui.jqgrid.css">
	<link rel="stylesheet" type="text/css" media="all"    href="css/jquery-ui-timepicker-addon.css">

	<!link rel="stylesheet" type="text/css" media="screen" href="css/tabcontent.css">

	<style type="text/css">

		.textalignright
		{
			text-align: right !important;
		}
		.textalignleft
		{
			text-align:left  !important;
		}
		.textalignright div
		{
			padding-right: 5px;
		}
		.textalignleft div
		{
			padding-left: 5px;
		}
		ol, ul
		{
			list-style: none;
		}
		/* Bump up the font-size in the grid */
		/*
		.ui-jqgrid,
		.ui-jqgrid .ui-jqgrid-view,
		.ui-jqgrid .ui-jqgrid-pager,
		.ui-jqgrid .ui-pg-input {
			font-size: 13px;
		}
		*/
		/* Page loading spinner image */
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
	</style>

	<!-- SCRIPTS - Probably don't need all this, but just in case. -->
	<script type="text/javascript" src="js/jquery-2.1.4.js"></script>

	<!-- <script type="text/javascript" src="js/DateTimePicker.js"></script> -->
	<!-- Bootstrap core JavaScript ================================================== -->
	<script type="text/javascript" src="js/bootstrap.js"></script>
	<script type="text/javascript" src="js/jquery-ui.js"></script><!-- must come after bootstrap.min.js -->
	<script type="text/javascript" src="js/jquery.themeswitcher.js"></script>

	<script type="text/javascript" src="js/jqxcore.js"></script><!-- jqWidgets: base code -->
	<script type="text/javascript" src="js/jqxwindow.js"></script>
	<script type="text/javascript" src="js/jqxbuttons.js"></script>
	<script type="text/javascript" src="js/jqxscrollbar.js"></script>
	<script type="text/javascript" src="js/jqxpanel.js"></script>
	<script type="text/javascript" src="js/jqxtabs.js"></script>
	<script type="text/javascript" src="js/jqxcheckbox.js"></script>

	<!-- script type="text/javascript" src="js/jqxmenu.js"></script><!-- jqWidgets: jqxMenu - runs nav menu -->
	<script type="text/javascript" src="js/jstz.js"></script><!-- Determines user's timezone of the PC their using -->
	<script type="text/javascript" src="js/html.js"></script>

	<!-- jqGrid.4.8.2 does not work so don't use it -->
	<!-- jqGrid.4.7.1 works -->
	<script type="text/javascript" src="jqGrid.5.1.1/js/jquery.jqGrid.min.js"></script>
	<script type="text/javascript" src="jqGrid.5.1.1/js/i18n/grid.locale-en.js"></script>
	<!-- <script type="text/javascript" src="js/jquery.blockUI.js"></script> -->

	<script type="text/javascript" src="js/jquery-ui-timepicker-addon.js"></script>
	<script type="text/javascript" src="js/jquery-ui-sliderAccess.js"></script>

	<script type="text/javascript" src="js/detect.js"></script><!-- Detect browser so we can size dialog boxes -->

    <!-- w2ui popup -->
    <!-- link rel="stylesheet" type="text/css" href="css/w2ui-1.4.3.css" / -->
    <!-- script type="text/javascript" src="js/w2ui-1.4.3.js"></script -->

    <link rel="stylesheet" type="text/css" href="css/w2ui-1.5.rc1.css" />
    <script type="text/javascript" src="js/w2ui-1.5.rc1.js"></script>

	<script src="js/go-debug.js"></script>

	<!--[if lt IE 9]>
	<script src="js/IE9.js"></script>
	<![endif]-->

	<script type="text/javascript">

        // ** USAGE ** (returns a boolean true/false if it worked or not)
        // Parameters ( Id_of_element, caretPosition_you_want)

        //setCaretPosition('IDHERE', 10); // example

        function setCaretPosition(elemId, caretPos) {
            var el = document.getElementById(elemId);

            el.value = el.value;
            // ^ this is used to not only get "focus", but
            // to make sure we don't have it everything -selected-
            // (it causes an issue in chrome, and having it doesn't hurt any other browser)

            if (el !== null) {

                if (el.createTextRange) {
                    var range = el.createTextRange();
                    range.move('character', caretPos);
                    range.select();
                    return true;
                }

                else {
                    // (el.selectionStart === 0 added for Firefox bug)
                    if (el.selectionStart || el.selectionStart === 0) {
                        el.focus();
                        el.setSelectionRange(caretPos, caretPos);
                        return true;
                    }

                    else  { // fail city, fortunately this never happens (as far as I've tested) :)
                        el.focus();
                        return false;
                    }
                }
            }
        }
	</script>
	<body>
	<form name="f1" method="post">
		<center><h1>Server Contacts</h1></center>
		<p>
			This tool replaces the original CCT 6 Trace Data Sources tool. It outputs information slightly
			different because of the new way CCT 7 gathers contact information.
		</p>
		<p>
			Use this tool to determine who will receive notifications from CCT and whether they will be
			an Approver or just want to receive FYI notications. CCT gathers contact information from
			three places only; CSC, NET and CCT. CSC contains contact records or CSC banners as we call
			them. These contact records contain NET group pin numbers from the NET tool. <i>(A list of CSC
			banners used by CCT are listed below.)</i> If users don't use CSC or NET but want to receive
			notifications or be in the work approval process, they will setup CCT Subscriber Lists.
		</p>
		<p>
			In the text box below you type in one or more server names and click on the submit
			button. Once the data is displayed, the text box below will clear so you can enter
			more servers and click submit again.
		</p>

		<p align="center">
			<textarea rows="12" id="server_names" name="server_names"
					  style="
						border:  1px solid #999999;
						width:   30%;
						resize:  none;
						font-size: 13px;"
					  onfocus="setCaretPosition('server_names', 0);">
			</textarea>
		</p>

		<p align="center">
			<input type="submit" name="button" value="Submit">
			<input type="reset" name="button" value="Reset">
		</p>
<?php
//
// Notes about this program:
// Allow the server input box to accept a list of servers.
// Have tool output group member lists with the NET pins and Subscriber groups.
// Display a list of CSC banners that they can use and what they mean as far as "approver or fyi only".
//

/**
 * @fn    explodeX($delimiters, $string)
 *
 * @brief Used to split apart a string that has multiple delimiters.
 *
 * @param array  $delimiters
 * @param string $string
 *
 * @return array
 */
function explodeX($delimiters, $string)
{
	return explode( chr( 1 ), str_replace( $delimiters, chr( 1 ), $string ) );
}

/**
 * @fn    getContacts($hostname)
 *
 * @brief This function the CCT contact information $hostname.
 *
 * @param string $hostname
 *
 * @return bool  where true means data was displayed, false means no data found.
 */
function getContacts($hostname)
{
	global $con, $ora, $lib;

	$list = new email_contacts($ora);
	$list_of_netpin_no = array();      // for $list->buildList($list_of_netpin_no)

	$actual_contacts_list = array();  // A hash containing a list of cuids, names and email addresses
	$contact_netpins = array();       // A hash containing a list of netgroups a cuid is found in

	printf("<hr>\n");

	if ($con->traceContactSources($hostname) == false)
		return false;

	//
	// Server    OS     Usage       Location   Time Zone        Maintenance Window  Applications and Databases
	// ========  =====  ==========  =========  ===============  ==================  ==========================
	// lxomp47x  Linux  PRODUCTION  OMAHA, NE  America/Chicago  SUN,SAT 00:00 120
	//
	// Server             = $con->computer_hostname
	// OS                 = $con->computer_oslite
	// Usage              = $con->computer_usage
	// Location           = $con->computer_city, $con->computer_state
	// Time Zone          = $con->computer_timezone
	// Maintenance Window = $con->computer_osmaint_weekly
	// Apps and DBs       = $con->computer_applications

	printf("<table cellspacing='4' cellpadding='4' border='1'>\n");
	printf("<tr>\n");
	printf("  <td><b>Server</b></td>\n");
	printf("  <td><b>OS</b></td>\n");
	printf("  <td><b>Usage</b></td>\n");
	printf("  <td><b>Location</b></td>\n");
	printf("  <td><b>Time Zone</b></td>\n");
	printf("  <td><b>Maintenance Window</b></td>\n");
	printf("</tr>\n");
	printf("<tr>\n");
	printf("  <td align='left' valign='top'>%s</td>\n", $con->computer_hostname);
	printf("  <td align='left' valign='top'>%s</td>\n", $con->computer_os_lite);
	printf("  <td align='left' valign='top'>%s</td>\n", $con->computer_status);
	printf("  <td align='left' valign='top'>%s, %s</td>\n", $con->computer_city, $con->computer_state);
	printf("  <td align='left' valign='top'>%s</td>\n", $con->computer_timezone);
	printf("  <td align='left' valign='top'>%s</td>\n", $con->computer_osmaint_weekly);
	printf("</tr>\n");
	printf("</table><br>\n");

	printf("<table cellspacing='4' cellpadding='4' border='1'>\n");
	printf("<tr>\n");
	printf("  <td><b>Netpin</b></td>\n");
	printf("  <td><b>Connection</b></td>\n");
	printf("  <td><b>Group</b></td>\n");
	printf("  <td><b>Type</b></td>\n");
	printf("  <td><b>CSC Banner</b></td>\n");
	printf("  <td><b>App or DB Name</b></td>\n");
	printf("</tr>\n");

	foreach($con->contacts as $netpin => $contact)
	{
		$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "Netpin: %s", $netpin);

		$contact_netpin_no          = $netpin;
		$contact_connection         = $contact->connections;
		$contact_server_os          = $contact->os_lite;
		$contact_server_usage       = $contact->status;
		$contact_work_group         = $contact->work_groups;
		$contact_approver_fyi       = $contact->notify_type;
		$contact_csc_banner         = $contact->group_name;
		$contact_apps_databases     = $contact->applications;

		$list_of_netpin_no[$netpin] = $contact_approver_fyi;  // For $list->buildList($list_of_netpin_no)

		//
		// Break apart the strings so we can create multiple records out of the data. This is enable
		// us to map the data more effectively in the sub-grid. Below is an example of how we want the
		// data to look in the grid.
		//
		// Server   	Net Pin	Connections	                    OS	    Usage	    Banner	            App / DB	    Notify Type	Approval
		// ------------ ------- ------------------------------- ------- ----------- ------------------- --------------- ----------- --------
		// acmspns01	20178	hhdnp29a->hcdnx11a->hvdnp59a	HPUX	PRODUCTION	Database Support	DB_NEPPROD1	    Approve 	APPROVED
		// acmspns01	20178	hhdnp29a->hcdnx11a->hvdnp59a	HPUX	PRODUCTION	Database Support	DB_CTRLPROD1	Approve 	APPROVED
		// acmspns01	20178	hhdnp29a->hcdnx11a->hvdnp59a	HPUX	PRODUCTION	Database Support	DB_SARMPROD1	Approve 	APPROVED
		// acmspns01	20178	hhdnp29a->hcdnx11a->hvdnp61a	HPUX	PRODUCTION	Database Support	DB_CTRLPROD1	Approve 	APPROVED
		// acmspns01	20178	hhdnp29a->hcdnx11a->hvdnp61a	HPUX	PRODUCTION	Database Support	DB_NEPPROD1	    Approve 	APPROVED
		// acmspns01	20178	hhdnp29a->hcdnx11a->hvdnp61a	HPUX	PRODUCTION	Database Support	DB_CTRLPROD1	Approve 	APPROVED
		// acmspns01	20178	hhdnp29a->hcdnx11a->hvdnp63a	HPUX	PRODUCTION	Database Support	DB_RMPROD1	    Approve 	APPROVED
		// acmspns01	20178	hhdnp29a->hcdnx11a->hvdnp63a	HPUX	PRODUCTION	Database Support	DB_INFOPROD1	Approve 	APPROVED
		//

		$contact_netpin_no          = $netpin;

		$connection     = explode(",", $contact_connection);
		$server_os      = explode(",", $contact_server_os);
		$server_usage   = explode(",", $contact_server_usage);
		$work_group     = explode(",", $contact_work_group);
		$approver_fyi   = explode(",", $contact_approver_fyi);
		$csc_banner     = explode(",", $contact_csc_banner);
		$apps_databases = explode(",", $contact_apps_databases);

		//
		// These counts should all be equal.
		//
		$connection_count    = count($connection);
		$server_os_count     = count($server_os);
		$server_usage_count  = count($server_usage);
		$work_group_count    = count($work_group);
		$approver_fyi_count  = count($approver_fyi);
		$csc_banner_count    = count($csc_banner);
		$apps_database_count = count($apps_databases);

		$lib->debug1(__FILE__, __FUNCTION__, __LINE__,
					  "connections: %d, os: %d, usage: %d, csc_banner: %d, apps_databases: %d",
					  $connection_count, $server_os_count, $server_usage_count,
					  $csc_banner_count, $apps_database_count);

		for ($i=0; $i<$connection_count; $i++)
		{
			$connection_item   = $connection[$i];
			$server_os_item    = '';
			$server_usage_item = '';
			$work_group_item     = '';
			$approver_fyi_item   = '';
			$csc_banner_item     = '';
			$apps_databases_item = '';

			if ($i < $server_os_count)
				$server_os_item = $server_os[$i];

			if ($i < $server_usage_count)
				$server_usage_item = $server_usage[$i];

			if ($i < $work_group_count)
			    $work_group_item = $work_group[$i];

			if ($i < $approver_fyi_count)
			    $approver_fyi_item = $approver_fyi[$i];

			if ($i < $csc_banner_count)
				$csc_banner_item = $csc_banner[$i];

			if ($i < $apps_database_count)
				$apps_databases_item = $apps_databases[$i];

			//
			// SKIP contact and connection where there is no valid NETPIN number.
			//
			if ($contact_netpin_no == '0' || $contact_netpin_no == 'NONE' || $contact_netpin_no == '')
			{
				$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "Skipping insert where contact_netpin_no = %s", $contact_netpin_no);
				continue;
			}

// Netpin  Connection                   Group  Type      CSC Banner           App or DB Name
// ======  ===========================  =====  ========  ===================  ==============
// 17340   lxomp47x, Linux, PRODUCTION  PASE   APPROVER  Application Support  CCT
// Member List as of 07/01/2017 04:03 - Greg Parkin (gparkin), Phil Okunewick (okunew)

// Netpin            = $contact_netpin_no           Netpin
// Connection Server = $connection_item             contact_connection
// Connection OS     = $server_os_item              contact_server_os
// Connection Usage  = $server_usage_item           contact_server_usage
// Contact Group     = $contact_work_group          contact_work_group
// Contact Type      = $contact_approver_fyi        contact_approver_fyi
// CSC Banner        = $csc_banner_item             contact_csc_banner
// App or DB Name    = $apps_databases_item         contact_apps_databases

			printf("<tr>\n");
			printf("<td align='left' valign='top'>%s</td>\n", $contact_netpin_no);
			printf("<td align='left' valign='top'>%s, %s, %s</td>\n",
				   $connection_item, $server_os_item, $server_usage_item);
			printf("<td align='left' valign='top'>%s</td>\n", $contact_work_group);
			printf("<td align='left' valign='top'>%s</td>\n", $contact_approver_fyi);
			printf("<td align='left' valign='top'>%s</td>\n", $csc_banner_item);
			printf("<td align='left' valign='top'>%s</td>\n", $apps_databases_item);
			printf("</tr>\n");

			// int substr_compare ( string $main_str , string $str , int $offset [, int $length [, bool $case_insensitivity = false ]] )
			//
            // main_str
            //
            //     The main string being compared.
            //
            // str
            //
            //     The secondary string being compared.
            //
            // offset
            //
            //     The start position for the comparison. If negative, it starts counting from the
            //     end of the string.
            //
            // length
            //
            //     The length of the comparison. The default value is the largest of the length of
            //     the str compared to the length of main_str less the offset.
            //
            // case_insensitivity
            //
            //     If case_insensitivity is TRUE, comparison is case insensitive.
            //



            /**
            if (substr_compare($contact_netpin_no, "SUB", 0, 3, true) == 0)
            {
				$query  = "select ";
				$query .= "  s.group_id, ";
				$query .= "  m.mnet_cuid, ";
				$query .= "  m.mnet_name, ";
				$query .= "  m.mnet_email ";
				$query .= "from ";
				$query .= "  cct7_subscriber_members s, ";
				$query .= "  cct7_mnet m ";
				$query .= "where ";
				$query .= "  s.group_id= '" . $contact_netpin_no . "' and ";
				$query .= "  m.mnet_cuid = s.member_cuid and ";
				$query .= "  m.mnet_email is not null ";
				$query .= "order by ";
				$query .= "  m.mnet_cuid";

				if ($ora->sql2($query) == false)
				{
					$lib->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $ora->sql_statement);
					$this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $ora->dbErrMsg);
					$lib->error = sprintf("%s %s %d: %s - %s", __FILE__, __FUNCTION__, __LINE__,
										  $ora->sql_statement, $ora->dbErrMsg);
					return false;
				}

				while ($ora->fetch())
                {
					$actual_contacts_list[$ora->mnet_cuid] =
						sprintf("%s|%s", $ora->mnet_name, $ora->mnet_email);

					if (array_key_exists($ora->mnet_cuid, $contact_netpins))
					{
						$hash_of_netpins = $contact_netpins[$ora->mnet_cuid];
						$hash_of_netpins[$contact_netpin_no] = 'Got it!';
						$contact_netpins[$ora->mnet_cuid] = $hash_of_netpins;
					}
					else
					{
						$hash_of_netpins = array();
						$hash_of_netpins[$contact_netpin_no] = 'Got it!';
						$contact_netpins[$ora->mnet_cuid] = $hash_of_netpins;
					}
                }
            }
            else
            {
				$query  = "select ";
				$query .= "  n.net_pin_no, ";
				$query .= "  n.user_cuid, ";
				$query .= "  m.mnet_name, ";
				$query .= "  m.mnet_email, ";
				$query .= "  to_char(n.last_update, 'MM/DD/YYYY HH24:MI') as last_update ";
				$query .= "from ";
				$query .= "  cct7_netpin_to_cuid n, ";
				$query .= "  cct7_mnet m ";
				$query .= "where ";
				$query .= "  n.net_pin_no = '" . $contact_netpin_no . "' and ";
				$query .= "  m.mnet_cuid = n.user_cuid and ";
				$query .= "  m.mnet_email is not null ";
				$query .= "order by ";
				$query .= "  m.mnet_cuid";

				if ($ora->sql2($query) == false)
				{
					$lib->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $ora->sql_statement);
					$this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $ora->dbErrMsg);
					$lib->error = sprintf("%s %s %d: %s - %s", __FILE__, __FUNCTION__, __LINE__,
										  $ora->sql_statement, $ora->dbErrMsg);
					return false;
				}

				while ($ora->fetch())
				{
					$actual_contacts_list[$ora->user_cuid] =
						sprintf("%s|%s", $ora->mnet_name, $ora->mnet_email);

					if (array_key_exists($ora->user_cuid, $contact_netpins))
					{
						$hash_of_netpins = $contact_netpins[$ora->user_cuid];
						$hash_of_netpins[$contact_netpin_no] = 'Got it!';
						$contact_netpins[$ora->user_cuid] = $hash_of_netpins;
					}
					else
					{
						$hash_of_netpins = array();
						$hash_of_netpins[$contact_netpin_no] = 'Got it!';
						$contact_netpins[$ora->user_cuid] = $hash_of_netpins;
					}
				}
            }
            */
		}
	}

	printf("</table>\n");

	$list->buildList($list_of_netpin_no);

	$lib->debug_r1(__FILE__, __FUNCTION__, __LINE__, $list_of_netpin_no);

	//
	// Sort $actual_contacts_list hash array by the key and print it out in a table.
	// Using a hash table removes all the duplicates.
	//
	//ksort($actual_contacts_list);

	printf("<br><table border=1 cellspacing=4 cellpadding=4>\n");
	printf("<tr>\n");
    printf("<td align='center' colspan='4'><b>Actual list of Contacts that will be notified</b>");
	printf("</tr>\n");
	printf("<tr>\n");
	printf("<td><b>CUID</b></td>\n");
	printf("<td><b>Name</b></td>\n");
	printf("<td><b>Email</b></td>\n");
	printf("<td><b>Notify Type</b></td>\n");
	printf("<td><b>NET Groups and CCT Subscriber Groups</b></td>\n");
	printf("</tr>\n");

	//foreach ($actual_contacts_list as $contact_cuid => $string)
    foreach ($list->email_list as $contact_cuid => $string)
	{
        $parts = explode('|', $string);
        $contact_name    = isset($parts[0]) ? $parts[0] : "";
        $contact_email   = isset($parts[1]) ? $parts[1] : "";
        $contact_type    = isset($parts[2]) ? $parts[2] : "";
        $contact_netpins = isset($parts[3]) ? $parts[3] : "";

        printf("<tr>\n");
        printf("<td>%s</td>\n", $contact_cuid);
        printf("<td>%s</td>\n", $contact_name);
        printf("<td>%s</td>\n", $contact_email);
		printf("<td>%s</td>\n", $contact_type);
		printf("<td>%s</td>\n", $contact_netpins);

        /*
        if (array_key_exists($contact_cuid, $contact_netpins))
        {
			$hash_of_netpins = $contact_netpins[$contact_cuid];
			ksort($hash_of_netpins);

			$count = 0;
			printf("<td>\n");

			foreach ($hash_of_netpins as $netpin => $str)
            {
                if ($count == 0)
				{
					printf("%s", $netpin);
					$count += 1;
				}
				else
                {
                    printf(", %s", $netpin);
                }
            }

            printf("</td>\n");
        }
        else
        {
			printf("<td>&nbsp;</td>\n");
        }
        */

        printf("</tr>\n");
	}

	printf("</table>\n");

	return true;
}

// CCT Generated Contacts for hostname: xxx
// Display data about the server...
//
// Contact Name  Email  Phone  Pager  Notify Type - Group  Contact Source  Apps/DBs
// ------------  -----  -----  -----  -------------------  --------------  --------
//
// CCT Subscribers for hostname: xxx
//
// Subscriber  Group Type  Notify Type
// ----------  ----------  -----------
//
//
// CSC Assignment records as of 06/24/2017 04:00 for hostname: lxomp47x
//
// CSC Banners (Group Names)     Application and Database   Net Group   Net Oncall  Net Override  CSC Primary  CSC Backup1 2 3 4
// -------------------------     ------------------------   ---------   ----------  ------------  -----------  ----------- - - -
if (isset($_REQUEST['server_names']) && strlen($_REQUEST['server_names']) > 0)
{
	// Convert any commas to spaces
	$str = str_replace(",", " ", $_REQUEST['server_names']);

	// Remove multiple spaces, tabs and newlines if present
	$server_names = preg_replace(array('/\s{2,}/', '/[\t\n]/'), ' ', $str);

	// Create an array of $systems
	$server_list = explode(" ", $server_names);

	foreach ($server_list as $hostname)
	{
		$host = trim($hostname);

		if (strlen($host) == 0)
			continue;

		if (getContacts($host) == false)
		{
			// No data available!
		}
	}
}

//
// Below is a list of valid CSC banners
//
?>
		<hr>
		<p>
		<table border="2" cellpadding="2" cellspacing="2" style="font-size: 18px; font-family: "Times New Roman", Times, serif;">
		<tr>
			<td align="center" colspan="3" style="color: blue;">
				<b>CCT pulls server contacts from the following CSC banners.</b>
			</td>
		</tr>
		<tr>
			<td align="left">
				<b>Notify Type</b>
			</td>
			<td lign="left">
				<b>Group Type</b>
			</td>
			<td align="left">
				<b>CSC Banner</b>
			</td>
		</tr>
		<tr>
			<td align="left" valign="top">
				APPROVER
			</td>
			<td align="left" valign="top">
				PASE
			</td>
			<td align="left" valign="top">
				Applications or Databases Desiring Notification (Not Hosted on this Server)
			</td>
		</tr>
		<tr>
			<td align="left" valign="top">
				APPROVER
			</td>
			<td align="left" valign="top">
				PASE
			</td>
			<td align="left" valign="top">
				Application Support
			</td>
		</tr>
		<tr>
			<td align="left" valign="top">
				APPROVER
			</td>
			<td align="left" valign="top">
				PASE
			</td>
			<td align="left" valign="top">
				Application Utilities, Middleware, Legacy DB, Other (Hosted on this Server)
			</td>
		</tr>
		<tr>
			<td align="left" valign="top">
				APPROVER
			</td>
			<td align="left" valign="top">
				PASE
			</td>
			<td align="left" valign="top">
				Infrastructure
			</td>
		</tr>
		<tr>
			<td align="left" valign="top">
				APPROVER
			</td>
			<td align="left" valign="top">
				PASE
			</td>
			<td align="left" valign="top">
				MiddleWare Support
			</td>
		</tr>
		<tr>
			<td align="left" valign="top">
				APPROVER
			</td>
			<td align="left" valign="top">
				DBA
			</td>
			<td align="left" valign="top">
				Database Support
			</td>
		</tr>
		<tr>
			<td align="left" valign="top">
				APPROVER
			</td>
			<td align="left" valign="top">
				DBA
			</td>
			<td align="left" valign="top">
				Development Database Support
			</td>
		</tr>
		<tr>
			<td align="left" valign="top">
				APPROVER
			</td>
			<td align="left" valign="top">
				OS
			</td>
			<td align="left" valign="top">
				Operating System Support
			</td>
		</tr>
		<tr>
			<td align="left" valign="top">
				FYI
			</td>
			<td align="left" valign="top">
				PASE
			</td>
			<td align="left" valign="top">
				Applications Owning Database (DB Hosted on this Server, Owning App Is Not)
			</td>
		</tr>
		<tr>
			<td align="left" valign="top">
				FYI
			</td>
			<td align="left" valign="top">
				PASE
			</td>
			<td align="left" valign="top">
				Development Support
			</td>
		</tr>
		</table>
		</p>
	</form>
	</body>
	</html>
