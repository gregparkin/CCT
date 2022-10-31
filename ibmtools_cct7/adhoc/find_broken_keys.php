#!/xxx/apache/php/bin/php -q
<?php
// 
// find_broken_keys.php
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

$ora = new dbms();
$i = 0;

$system_ids = array();

$ora->sql("select distinct system_id from cct6_contacts");

while ($ora->fetch())
{
	$system_ids[$i] = $ora->system_id;
	$i++;
}

printf("i = %d\n", $i);
$high = 1000;
$i = 0;

foreach ($system_ids as $system_id)
{
	$i++;
	if ($i >= $high)
	{
		$high += 1000;
		printf("Completed scans for %d system_id(s)\n", $i);
	}

	$query = sprintf("select system_id from cct6_systems where system_id = %d", $system_id);

	if ($ora->sql($query) == false)
	{
		printf("%s - %s\n", $ora->sql_statement, $ora->dbErrMsg);
		exit();
	}

	if ($ora->fetch())
	{
		continue;
	}

	printf("%d\n", $system_id);
}

exit();

