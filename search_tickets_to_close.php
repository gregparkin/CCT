#!/opt/lampp/bin/php -a
<?php
/**
 * search_tickets_to_close.php
 *
 * @package   PhpStorm
 * @file      doit.php
 * @author    gparkin
 * @date      4/21/2017
 * @version   7.0
 *
 * @brief     Calls method closeActiveTickets() in class cct7_tickets.php to search for tickets to close.
 *
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

$lib = new library();
date_default_timezone_set('America/Denver');

$tic = new cct7_tickets();

if ($tic->closeActiveTickets() == false)
{
    printf("Operation failed\n");
}
else
{
    printf("Operation: successful\n");
}

?>
