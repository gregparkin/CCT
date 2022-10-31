#!/opt/lampp/bin/php -a
<?php
/**
 * update_cct7_no_changes.php
 *
 * @package   PhpStorm
 * @file      update_cct7_no_changes.php
 * @author    gparkin
 * @date      02/23/2017
 * @version   7.0
 *
 * @brief     To to update cct7_no_changes with new minimal change information.
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

$lib = new library();  // classes/library.php
$lib->debug_start('update_cct7_no_changes.html');
date_default_timezone_set('America/Denver');

$obj = new cct7_no_changes();

$obj->truncate();

$obj->add(
	'No Change',
	'12/31/2016 00:01',
	'01/03/2017 06:00',
	'EOY (US) Holidays');

$obj->add(
	'Minimal Change',
	'01/28/2017 06:00',
	'01/31/2017 06:00',
	'First Monday (30-JAN) of the Month');

$obj->add(
	'Minimal Change',
	'02/25/2017 06:00',
	'02/28/2017 06:00',
	'First Monday (27-FEB) of the Month');

$obj->add(
	'Minimal Change',
	'04/01/2017 06:00',
	'04/04/2017 06:00',
	'First Monday (03-APR) of the Month');

$obj->add(
	'Minimal Change',
	'04/06/2017 21:00',
	'04/09/2017 06:00',
	'FREEZE period April Minor Release: CM313053');

$obj->add(
	'Minimal Change',
	'04/30/2017 06:00',
	'05/02/2017 06:00',
	'First Monday (01-MAY) of the Month');

$obj->add(
	'Minimal Change',
	'05/12/2017 21:00',
	'05/15/2017 06:00',
	'FREEZE period May Major Release: CM312906');

$obj->add(
	'Minimal Change',
	'05/25/2017 00:01',
	'06/06/2017 11:59',
	'Memorial Day (US)');

$obj->add(
	'Minimal Change',
	'05/25/2017 06:00',
	'06/06/2017 06:00',
	'First Monday (05-JUN) of the Month');

$obj->add(
	'Minimal Change',
	'06/08/2017 21:00',
	'06/11/2017 06:00',
	'FREEZE period for June Minor Release: CM313055');

$obj->add(
	'Minimal Change',
	'07/01/2017 06:00',
	'07/05/2017 06:00',
	'First Monday (03-JUL) of the Month');

$obj->add(
	'Minimal Change',
	'07/13/2017 21:00',
	'07/16/2017 06:00',
	'FREEZE period for July Minor Release: CM313058');

$obj->add(
	'Minimal Change',
	'07/29/2017 06:00',
	'08/01/2017 06:00',
	'First Monday (31-JUL) of the Month');

$obj->add(
	'Minimal Change',
	'08/11/2017 21:00',
	'08/14/2017 06:00',
	'FREEZE period for Aug MajorRelease: CM312908');

$obj->add(
	'Minimal Change',
	'08/31/2017 00:01',
	'09/07/2017 11:59',
	'Labor Day (US)');

$obj->add(
	'Minimal Change',
	'09/14/2017 21:00',
	'09/17/2017 06:00',
	'Thur FREEZE period for Sept Minor Release: CM313062');

$obj->add(
	'Minimal Change',
	'09/30/2017 06:00',
	'10/03/2017 06:00',
	'First Monday (02-OCT) of the Month');

$obj->add(
	'Minimal Change',
	'10/12/2017 21:00',
	'10/15/2017 06:00',
	'FREEZE period for Oct Minor Release: CM313066');

$obj->add(
	'Minimal Change',
	'10/28/2017 06:00',
	'10/31/2017 06:00',
	'First Monday (30-OCT) of the Month');

$obj->add(
	'Minimal Change',
	'11/10/2017 21:00',
	'11/13/2017 06:00',
	'FREEZE period for Nov Major Release: CM312910');

$obj->add(
	'Minimal Change',
	'11/18/2017 00:01',
	'11/28/2017 11:59',
	'Thanksgiving (US)');

$obj->add(
	'Minimal Change',
	'12/02/2017 06:00',
	'12/05/2017 06:00',
	'First Monday (04-DEC) of the Month');

