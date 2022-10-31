<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<?php
/**
 * <test_dbms.php>
 *
 * @package    CCT
 * @file       test_dbms.php
 * @author     Greg Parkin <gregparkin58@gmail.com>
 * @date       01/30/2015
 * @version    6.1.0
 */
 
//
// Class autoloader - Removes the need to add include and require statements
//
if (!function_exists('__autoload'))
{
	require_once('autoloader.php');
}

$ora = new dbms();
$h = new html();
$h->debug_start('test_dbms.txt');

$h->set_loading_file_on("Test dbms");
$h->set_program("test_dbms.php");
$h->html_top();

if ($ora->sql("select * from aradmin.hpd_helpdesk@remedy_im2 where case_id = 'HD00007593887'") == false)
{
	printf("<p>ERROR: %s</p>\n", $ora->dbErrorMessage);
}

if ($ora->fetch())
{
	printf("<p>Hello: %s</p>\n", $ora->mnet_name);
}


$h->html_bot();
?>
