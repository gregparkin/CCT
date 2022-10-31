#!/opt/lampp/bin/php -q
<?php
/**
 * <make_virtual_servers.php>
 *
 * @package    CCT
 * @file       make_virtual_servers.php
 * @author     Greg Parkin <gregparkin58@gmail.com>
 * @date       02/21/2017
 * @version    7.0
 */
 
//
// This script runs cct7_virtual_servers.sql from the sql directory to rebuild the cct7_virtual_servers table.
//
// The SQL script downloads data from Asset Manager using two Oracle views that Joel Noble setup.
// cs_qwestibm_databases@itast
// cs-qwestibm_igscontracts@itast
//

//
// Class autoload function 
//
function __autoload($classname)
{
	require_once('/opt/ibmtools/www/cct7/classes/' . $classname . '.php');
}

$s = new sqlplus();

//
// Download Asset Manager data into cct7_virtual_servers
//
if ($s->sqlplus("/opt/ibmtools/cct7/bin/sql/cct7_virtual_servers.sql") == false)
{
	printf("Problem with %s: %s\n", __FILE__, $s->dbErrMsg);
	exit();
}

printf("\nAll done!\n");
?>
