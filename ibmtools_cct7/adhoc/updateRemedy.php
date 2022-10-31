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

$ora = new dbms();
$tic = new cct6_tickets();

$ora->sql("select cm_ticket_no from cct6_tickets order by cm_ticket_no");

while ($ora->fetch())
{
	if (strncmp($ora->cm_ticket_no, "CM", 2) != 0)
		continue;

	printf("%s - ", $ora->cm_ticket_no);

	if ($tic->updateRemedy($ora->cm_ticket_no) == false)
	{
		printf("%s\n", $tic->error);
	}
	else
	{
		printf("Done!\n");
	}
}

printf("\nFinished!\n");

?>
