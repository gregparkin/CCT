<?php
/**
 * update_all_totals.php
 *
 * @package   PhpStorm
 * @file      doit.php
 * @author    gparkin
 * @date      7/12/2017
 * @version   7.0
 *
 * @brief     Manual job I run to fix all the totals in cct7_tickets and cct7_systems. This also fixes
 *            any system_status values that may be wrong.
 *
 *            This job calls method: updateAllStatuses() found in class/library.php
 */

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

$ora  = new oracle();
$ora2 = new oracle();
$lib  = new library();
$this->debug_start('update_all_totals.html');

$query = "select ticket_no from cct7_tickets order by ticket_no";

if ($ora->sql2($query) == false)
{
	printf("%s\n", $query);
	printf("%s\n", $ora->dbErrMsg);
	exit();
}

while ($ora->fetch())
{
	if ($lib->updateAllStatuses($ora2, $ora->ticket_no) == false)
	{
		printf("%s - Failed: %s\n", $ora->ticket_no, $lib->error);
	}
	else
	{
		printf("%s - Success\n", $ora->ticket_no);
	}
}

$ora->commit();
$ora2->commit();

printf("\nAll Done!\n");

exit();
?>