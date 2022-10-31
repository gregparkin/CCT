#!/xxx/apache/php/bin/php -q
<?php
// 
// audit_master_approvers.php
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

$ora1 = new dbms();
$ora2 = new dbms();

if ($ora1->sql("select computer_hostname, approver_cuid from cct6_master_approvers order by computer_hostname") == false)
{
	printf("%s - %s\n", $ora1->sql_statement, $ora1->dbErrMsg);
	exit();
}

$computer_hostname = "";
$master_approver_name = "";
$master_approver_email = "";
$manager_name = "";
$manager_email = "";

while ($ora1->fetch())
{
	$computer_hostname = $ora1->computer_hostname;

	if ($ora2->sql("select * from cct6_mnet where mnet_cuid = '" . $ora1->approver_cuid . "'") == false)
	{
		printf("%s - %s\n", $ora2->sql_statement, $ora2->dbErrMsg);
		exit();
	}

	if ($ora2->fetch())
	{
		$master_approver_name = $ora2->mnet_name;
		$master_approver_email = $ora2->mnet_email;
	}
	else
	{
		$master_approver_name = "";
		$master_approver_email = "";
	}

	if (strlen($master_approver_name) > 0)
	{
		if ($ora2->sql("select * from cct6_mnet where mnet_cuid = '" . $ora2->mnet_mgr_cuid . "'") == false)
		{
			printf("%s - %s\n", $ora2->sql_statement, $ora2->dbErrMsg);
			exit();
		}

		if ($ora2->fetch())
		{
			$manager_name = $ora2->mnet_name;
			$manager_email = $ora2->mnet_email;
		}
		else
		{
			$manager_name = "";
			$manager_email = "";
		}
	}
	else
	{
		$manager_name = "";
		$manager_email = "";
	}

	printf("%s|%s|%s|%s|%s\n", $computer_hostname, $master_approver_name, $master_approver_email, $manager_name, $manager_email);
}

exit();


