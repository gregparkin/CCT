#!/opt/lampp/bin/php -q
<?php
/**
 * <run_nightly.php>
 *
 * @package    CCT
 * @file       run_nightly.php
 * @author     Greg Parkin <gregparkin58@gmail.com>
 * @date       02/21/2017
 * @version    7.0
 */

//
// Run once a day using cron.
//

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
  require_once('/opt/ibmtools/www/cct7/classes/autoloader.php');
}

printf("Run Nightly: %s - See individual run logs for details.\n\n", date('m/d/Y h:i'));

RunMe('make_mnet.php',            "making new cct7_mnet");
RunMe('make_csc.php',             "making new cct7_csc");
RunMe('make_computers.php',       "making new cct7_computers");
RunMe('make_applications.php',    "making new cct7_applications");
RunMe('make_computer_status.php', "making new cct7_computer_status");
RunMe('make_contract.php',        "making new cct7_contract");
RunMe('make_databases.php',       "making new cct7_databases");
RunMe('make_managing_group.php',  "making new cct7_managing_group");
RunMe('make_netpin_to_cuid.php',  "making new cct7_netpin_to_cuid");
RunMe('make_os_lite.php',         "making new cct7_os_lite");
RunMe('make_platform.php',        "making new cct7_platform");
RunMe('make_state_city.php',      "making new cct7_state_city");
// RunMe('update_assign_groups.php', "updating cct7_assign_groups");
// RunMe('update_tickets.php',       "updating cct7_tickets");
// RunMe('update_reschedules.php',   "updating reschedules");
// RunMe('spool_escalations.php',    "spooling escalations");
// RunMe('make_net_members.php',     "making new cct7_net_members");
// RunMe('make_virtual_servers.php', "making new cct7_virtual_servers");

/*! @fn RunMe($program, $description)
 *  @brief Run the individual make programs identified by $program
 *  @param $program is the program name
 *  @param $description is description of the program which is used for logging purposes only.
 *  @return void
 */
function RunMe($program, $description)
{
	$file_without_extention = explode(".", $program);
	$logfile = sprintf("/opt/ibmtools/cct7/logs/%s.log", $file_without_extention[0]);

	$start_time = time();

	$fp = fopen($logfile, "w") or die($php_errormsg);
	fprintf($fp, "Starting: %s at %s\n", $program, date("m/d/y G:i:s", $start_time));
	fprintf($fp, "==========================================================================================================================\n");
	fclose($fp);

	printf("\nRunning program: %s - %s\n", $program, $description);
	$cmd = sprintf("/opt/ibmtools/cct7/bin/%s >>%s 2>&1", $program, $logfile);
	exec($cmd);

	$end_time = time();
	$difference = $end_time - $start_time;
	
	if ($start_time == $end_time)
	{
		$duration = "00:00:00";
	}
	else
	{
		$duration = sprintf("%02d%s%02d%s%02d", floor($difference/3600), ':', ($difference/60)%60, ':', $difference%60);
	}

	$fp = fopen($logfile, "a") or die($php_errormsg);
	fprintf($fp, "==========================================================================================================================\n");
	fprintf($fp, " Program: %s\n", $program);
	fprintf($fp, "   START: %s\n", date("m/d/y G:i:s", $start_time));
	fprintf($fp, "     END: %s\n", date("m/d/y G:i:s", $end_time));
	fprintf($fp, "Duration: %s\n", $duration);
	fclose($fp);
}
?>
