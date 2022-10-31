#!/opt/lampp/bin/php -q
<?php
/**
 * <make_applications.php>
 *
 * @package    CCT
 * @file       make_applications.php
 * @author     Greg Parkin <gregparkin58@gmail.com>
 * @date       02/21/2017
 * @version    7.0
 */

//
// This script runs cct7_applications.sql from the sql directory to rebuild the cct7_applications table.
//
// The SQL script downloads data from Asset Manager using two Oracle views that Joel Noble setup.
// cs_qwestibm_applications@itast
// cs-qwestibm_igscontracts@itast
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


      //
      // (FSTPG00E 200 Unsuccessful group lookup. Unable to find group '0' )
      //
$hold = "gparkin(p,b)";
$patterns[0] = '\(p\)';
$patterns[1] = '(b)';
$patterns[2] = '(p,b)';


printf("%s\n", preg_replace($patterns, '', $hold));

?>
