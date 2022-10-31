#!/opt/lampp/bin/php -q
<?php
/**
 * <make_computers.php>
 *
 * @package    CCT
 * @file       make_computers.php
 * @author     Greg Parkin <gregparkin58@gmail.com>
 * @date       02/21/2017
 * @version    7.0
 */

//
// This script runs cct7_computers.sql from the sql directory to rebuild the cct7_computers table.
//
// The SQL script downloads data from Asset Manager using two Oracle views that Joel Noble setup.
// cs_qwestibm_computers@itast
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
// Download Asset Manager data into cct7_computers
//
for ($x=1; $x<12; $x++)
{
	$filename = sprintf("/opt/ibmtools/cct7/bin/sql/cct7_computers_part%d.sql", $x);
	
	if ($s->sqlplus($filename) == false)
	{
		printf("Problem with make_computers.php: %s\n", $s->dbErrMsg);
		exit();
	}
}
printf("\nAll done!\n");
?>
?>
