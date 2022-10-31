<?php
/**
* @package    CCT
* @file       autoloader7.php
* @author     Greg Parkin <gregparkin58@gmail.com>
* @date       $Date:  $ GMT
* @version    $Revision:  $
*
* $Log:  $
*
*
* $Source:  $
*/

//
//
// Class autoloader - Removes the need to add include and require statements
//
function __autoload($classname)
{
	ini_set("error_reporting",        "E_ALL & ~E_DEPRECATED & ~E_STRICT");
	ini_set("log_errors",             1);
	ini_set("error_log",              "/opt/ibmtools/cct7/logs/php-error.log");
	ini_set("log_errors_max_len",     0);
	ini_set("report_memleaks",        1);
	ini_set("track_errors",           1);
	ini_set("html_errors",            1);
	ini_set("ignore_repeated_errors", 0);
	ini_set("ignore_repeated_source", 0);

	// ~/cct7/classes/autoloader.php

    require_once($classname . '.php');
    // require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/' . $classname . '.php');
}
?>

