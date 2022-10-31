#!/opt/lampp/bin/php -q
<?php
/**
 * cleanup_subscribers.php
 *
 * @package   PhpStorm
 * @file      cleanup_subscribers.php
 * @author    gparkin
 * @date      07/20/2017
 * @version   7.0
 *
 *
 */

//
// Called once when a user signs into CCT.
//
ini_set('xdebug.collect_vars',    '5');
ini_set('xdebug.collect_vars',    'on');
ini_set('xdebug.collect_params',  '4');
ini_set('xdebug.dump_globals',    'on');
ini_set('xdebug.dump.SERVER',     'REQUEST_URI');
ini_set('xdebug.show_local_vars', 'on');

set_include_path("/opt/ibmtools/www/cct7/classes");

ini_set('memory_limit', '4048M');

//
// Class autoloader - Removes the need to add include and require statements
//
if (!function_exists('__autoload'))
{
	require_once('classes/autoloader.php');
}

$lib = new library();  // classes/library.php
$lib->debug_start('cleanup_subscribers.html');
date_default_timezone_set('America/Denver');

//$lib->html_dump();
$ora1 = new oracle();
$ora2 = new oracle();

parse_str(implode('&', array_slice($argv, 1)), $_GET);
//print_r($argv);

$error_message = '';

//
// Set the timezone to GMT and get the current time.
// Substract the 30 hours from the date and return UTIME.
//
$date = new DateTime();
$date->setTimezone(new DateTimeZone('GMT'));
$date->setTimestamp(time());

$date->sub(new DateInterval('PT30H'));
$thirty_hours_ago = $date->format('U');

$date->sub(new DateInterval('PT48H'));
$forty_eight_hours_ago = $date->format('U');

//
// Cleanup the Subscriber lists by removing any invalid groups owners and member cuids that are no
// longer found in cct7_mnet.
//
// *** WARNING *** If cct7_mnet is empty the entire subscriber list will be removed!
//
// Get the record count of cct7_mnet to make sure it is not empty!
//
$ora1->sql2("select count(*) as total_records from cct7_mnet");
$ora1->fetch();
$total_records = $ora1->total_records;

//
// Make sure we have at least 90,000 records in the cct7_mnet table before doing anything!
//
if ($total_records > 90000)  // Average record count in cct7_mnet: 95499
{
	printf("\nRemoving obsolete subscriber groups owners and subscriber members not found in cct7_mnet\n");

	if ($ora1->sql2("select * from cct7_subscriber_groups order by group_name") == false)
	{
		printf("%s - %s\n", $ora1->sql_statement, $ora1->dbErrMsg);
		exit();
	}

	while ($ora1->fetch())
	{
		$query = sprintf(
			"select * from cct7_mnet where mnet_cuid = '%s'", $ora1->owner_cuid);

		if ($ora2->sql2($query) == false)
		{
			printf("%s - %s\n", $ora1->sql_statement, $ora1->dbErrMsg);
			exit();
		}

		if ($ora2->fetch())
		{
			printf("Subscriber Group has been validated: %s\n", $ora1->group_name);
		}
		else
		{
			printf("Deleting Subscriber Group owned by %s - %s\n", $ora1->owner_cuid, $ora1->group_name);
			$query = sprintf("delete cct7_subscriber_groups where owner_cuid = '%s'", $ora1->owner_cuid);

			if ($ora2->sql2($query) == false)
			{
				printf("%s - %s\n", $ora1->sql_statement, $ora1->dbErrMsg);
				exit();
			}
		}
	}

	if ($ora2->sql2("delete from cct7_subscriber_groups where owner_cuid is null") == false)
	{
		printf("%s - %s\n", $ora2->sql_statement, $ora2->dbErrMsg);
		exit();
	}

	$ora2->commit();

	//
	// Delete obsolete subscriber members
	//
	if ($ora2->sql2("select * from cct7_subscriber_members order by member_cuid") == false)
	{
		printf("%s - %s\n", $ora2->sql_statement, $ora2->dbErrMsg);
		exit();
	}

	$member_list = array();

	while ($ora1->fetch())
	{
		$query = sprintf(
			"select * from cct7_mnet where mnet_cuid = '%s'", $ora1->member_cuid);

		if ($ora2->sql2($query) == false)
		{
			printf("%s - %s\n", $ora2->sql_statement, $ora2->dbErrMsg);
			exit();
		}

		if ($ora2->fetch())
		{
			printf("Subscriber member has been validated: %s\n", $ora1->member_cuid);
		}
		else
		{
			printf("Deleting subscriber member: %s\n", $ora1->member_cuid);
			$member_list[$ora1->member_cuid] = $ora1->member_name;
		}
	}

	if ($ora2->sql2("delete from cct7_subscriber_members where member_cuid is null") == false)
	{
		printf("%s - %s\n", $ora2->sql_statement, $ora2->dbErrMsg);
		exit();
	}

	$ora2->commit();

	$update_tickets = array();

	//
    // Remove obsolete subscriber records from any ACTIVE tickets.
    //
	$query  = "select distinct ";
    $query .= "  s.ticket_no          as ticket_no, ";
    $query .= "  c.contact_netpin_no  as contact_netpin_no ";
    $query .= "from ";
    $query .= "  cct7_tickets t, ";
    $query .= "  cct7_systems s, ";
	$query .= "  cct7_contacts c ";
    $query .= "where ";
    $query .= "  c.contact_netpin_no like 'SUB%' and ";
    $query .= "  s.system_id = c.system_id and ";
    $query .= "  t.ticket_no = s.ticket_no and ";
    $query .= "  t.status = 'ACTIVE' ";
    $query .= "order by ";
    $query .= "  c.contact_netpin_no";

	if ($ora1->sql2($query) == false)
    {
        printf("%s - %s\n", $ora1->sql_statement, $ora1->dbErrMsg);
        exit();
    }

    while ($ora1->fetch())
    {
        $ticket_no         = $ora1->ticket_no;
        $contact_netpin_no = $ora1->contact_netpin_no;

        printf("%s - %s ", $contact_netpin_no);

        $query  = "select * from cct7_subscriber_groups where ";
        $query .= sprintf("group_id = '%s'", $contact_netpin_no);

        printf("%s ", $query);

		if ($ora2->sql2($query) == false)
		{
			printf("%s - %s\n", $ora2->sql_statement, $ora2->dbErrMsg);
			exit();
		}

		if ($ora2->fetch())
        {
            printf("OKAY!\n");
            continue;
        }
        else
        {
            // Remove the records from cct7_contacts
            printf("NOT FOUND in cct7_subscriber_groups\n");

            $query =
                sprintf("delete from cct7_contacts where contact_netpin_no = '%s'",
                        $contact_netpin_no);

			if ($ora2->sql2($query) == false)
			{
				printf("%s - %s\n", $ora2->sql_statement, $ora2->dbErrMsg);
				exit();
			}

			$ora2->commit();

            if (array_key_exists($contact_netpin_no, $update_tickets))
            {
                $str = $update_tickets[$ticket_no];
                $str .= ", " . $contact_netpin_no;
				$update_tickets[$ticket_no] = $str;
            }
            else
            {
				$update_tickets[$ticket_no] = $contact_netpin_no;
            }
        }
    }

    $ora1->commit();
	$ora2->commit();

    //
	// Rebuild the statuses.
    //
    foreach ($update_tickets as $ticket_no => $pins)
    {
        printf("Cleanup on %s - %s\n", $ticket_no, $pins);

        $lib->updateAllStatuses($ora2, $ticket_no);
    }
}

echo "\nAll Done!\n\n";