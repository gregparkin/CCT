#!/xxx/apache/php/bin/php -q
<?php
// 
// template.php
//
// AUTHOR: Greg Parkin
//

//
// Class autoloader - /xxx/www/cct/classes:/xxx/www/cct/servers:/xxx/www/cct/includes
// See: include_paths= in file /xxx/apache/php/php.ini
//
if (!function_exists('__autoload'))
{
	require_once('autoloader.php');
}

$approve_all_list = array();

$approve_all_list['gparkin'] = 'Greg Parkin';

foreach ($approve_all_list as $cuid => $name)
{
	printf("%s, %s\n", $cuid, $name);
}


