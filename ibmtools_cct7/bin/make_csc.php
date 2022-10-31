#!/opt/lampp/bin/php -q
<?php
/**
 * <make_csc.php>
 *
 * @package    CCT
 * @file       make_csc.php
 * @author     Greg Parkin <gregparkin58@gmail.com>
 * @date       02/21/2017
 * @version    7.0
 */
 
//
// The purpose of this routine is to download a fresh copy of the CSC database view for CCT.
// The data is placed into a new table called cct7_csc every night using crontab.
//
// STEP 1
// The first step is to copy down CSC table: csc.v_acsys_assign@csc_cct_web to new_cct7_csc. We then
// run a bunch of alter's to fixup the table the way we want it. We also add some new fields.
//
// STEP 2
// The function get_maintwin() is called to format all the maintenance windows from CSC into
// a useable format that CCT can use. The formatted maintenances windows are then stored in
// the cct7_csc record into some new formatted maintenance window fields.
//
// STEP 3
// The function_get_oncall() runs Net-Tool's FASTPG to retrieve the current oncall person
// for a netgroup pin. The oncall person is then looked up in MNET to see if they have a
// email address. If the email address is not good we do another FASTPG lookup to get a list
// of all member callout people in Net-Tool. We then go through that list to see if we can
// find a person who as a email address. The contact cuid is placed in the cct_csc_oncall
// field for all netgroup pins matching the pin we are working with.
//
// STEP 4
// The last step in this process is to run cct7_csc_part2.sql which drops our previous nights
// backup copy of cct7_csc_backup and then we rename the current cct7_csc to cct7_csc_backup.
// Then we rename our new created new_cct7_csc table to cct7_csc and rebuild the indexes.

//
// Class autoload function 
//
function __autoload($classname)
{
	require_once('/opt/ibmtools/www/cct7/classes/' . $classname . '.php');
}

$s = new sqlplus();

//
// STEP1 - Download CSC data to new_cct7_csc
//

if ($s->sqlplus("/opt/ibmtools/cct7/bin/sql/cct7_csc_part1.sql") == false)
{
	printf("%s %d: Problem STEP1: %s\n", __FILE__, __LINE__, $s->dbErrMsg);
	exit();
}

//
// STEP2 - Format all maintenance windows in new_cct7_csc
//
if (get_maintwin() == false)
{
	printf("%s %d: Problem STEP1: %s\n", __FILE__, __LINE__, $s->dbErrMsg);
	exit();
}

//
// STEP3 - Run FASTPG to get the oncall contact and update new_cct7_csc.cct_csc_oncall records
//         The on-call person is no longer used in CCT7
//
//if (get_oncall() == false)
//{
//	printf("%s %d: Problem STEP1: %s\n", __FILE__, __LINE__, $s->dbErrMsg);
//	exit();
//}

//
// STEP4 - Backup previous nights cct7_csc to cct7_csc_backup and rename new_cct7_csc to cct7_csc
//
if ($s->sqlplus("/opt/ibmtools/cct7/bin/sql/cct7_csc_part2.sql") == false)
{
	printf("%s %d: Problem STEP1: %s\n", __FILE__, __LINE__, $s->dbErrMsg);
	exit();
}

printf("\nAll done!\n");

exit();

/*! @fn get_maintwin()
 *  @brief Get all the maintenance window information from new_cct7_csc
 *  @return true or false, where true means success 
 */
function get_maintwin()
{
	$lib = new library();            // classes/library.php
	$ora1 = new oracle();            // classes/oracle.php
	$ora2 = new oracle();            // classes/oracle.php
	$f = new maintwin_formatter();   // classes/maintwin_formatter.php

	$query  = "select distinct ";
	$query .= "  lastid, ";
	$query .= "  cct_csc_osmaint_weekly, ";
	$query .= "  cct_csc_osmaint_monthly, ";
	$query .= "  cct_csc_osmaint_quarterly ";
	$query .= "from ";
	$query .= "  new_cct7_csc";

	if ($ora1->sql2($query) == false)
	{
		printf("%s - %s\n", $ora1->sql_statement, $ora1->dbErrMsg);
   		return false;
	}

	$count = 0;
	$high = 500;

	while ($ora1->fetch())
	{
		$cct_csc_format_weekly = $f->format($ora1->cct_csc_osmaint_weekly);
		$cct_csc_format_monthly = $f->format($ora1->cct_csc_osmaint_monthly);
		$cct_csc_format_quarterly = $f->format($ora1->cct_csc_osmaint_quarterly);

		$update  = "update new_cct7_csc set ";

		$lib->makeUpdateCHAR($update, "cct_csc_format_weekly",    $cct_csc_format_weekly,     true);
		$lib->makeUpdateCHAR($update, "cct_csc_format_monthly",   $cct_csc_format_monthly,    true);
		$lib->makeUpdateCHAR($update, "cct_csc_format_quarterly", $cct_csc_format_quarterly, false);

		$update .= " where lastid = " . $ora1->lastid;

		if ($ora2->sql($update) == false)
		{
			printf("%s - %s\n", $ora1->sql_statement, $ora1->dbErrMsg);
    		return false;
		}

		$count++;

		if ($count >= $high)
		{
			printf("Committed after %d updates in cct7_csc\n", $count);
			$high += 500;
			$ora2->commit();
		}
	}

	$ora2->commit();
	printf("Committed after %d updates in new_cct7_csc\n", $count);
	printf("\nEnd of STEP 2 - OS Maintenance Window updates\n");

	$ora1->logoff();
	$ora2->logoff();

	return true;
}

// /xxx/bin/fastpg.exe
//
// [-/]?         Display help.
// [-/]?s        Display run time switch information.
// [-/]c         Turn on return code display (debugging).
// [-/]d         Turn on debugging.
// [-/]e         Set paging/e-mail type as e-mail.
// [-/]e l       Set e-mail type to local.
// [-/]e p       Set paging/e-mail type as page.
// [-/]e user    Set the to user to 'user'.
// [-/]Ffile     Set paging/e-mail message text to data inside 'file'.
// [-/]f user    Set the from user to 'user'.
// [-/]g         Set the paging type to a group page.
// [-/]hc        Set the name of the paging destination to notification.
// [-/]hp        Set the name of the paging destination to notification.
// [-/]hf        Set the name of the paging destination to infogate.
// [-/]h host    Set the name of the paging destination to 'host'.
// [-/]itran     Set the transaction id to 'tran'.
// [-/]i         Set the paging type to an individual page.
// [-/]j group   Return the current on-call for group 'group'.
// [-/]lmailexe  Set the local mail execution file name to 'mailexe'.
// [-/]l mailexe Set the local mail execution file name to 'mailexe'.
// [-/]mmsg      Set the message text to 'msg'.
// [-/]m msg     Set the message text to 'msg'.
// [-/]n         Don't send the page/e-mail.
// [-/]rrecv     Set the receiver name to 'recv'.
// [-/]r recv    Set the receiver name to 'recv'.
// [-/]ssend     Set the sender name to 'send'.
// [-/]s subject Set the e-mail subject to 'subject'.
// [-/]tg        Set the paging/e-mail type to a group page.
// [-/]te        Set the paging/e-mail type to e-mail.
// [-/]ti        Set the paging/e-mail type to an individual page.
// [-/]t recv    Set the receiver name to 'recv'.
// [-/]u tran    Set the transaction id to 'tran'.
// [-/]v         Display the program version number and exit.
// [-/]v-        Display the program version number.
// [-/]usubject  Set the e-mail subject to 'subject'.
// [-/]zhost     Set the host name of the paging destination to 'host'.
// [-/]z host    Set the host name of the paging destination to 'host'.
// [-/]1file     Set the debug standard output to 'file'.
// [-/]2file     Set the debug error output to 'file'.
// [-/]3file     Set both the debug error and standard output to 'file'.
// -=tran        Set the transaction name to 'tran'.

/**
 * @fn get_oncall()
 *
 * @brief Get all the oncall information using the linux32_fastpg.exe program (Net-Tool)
 *
 * @return true or false, where true means success
 */
function get_oncall()
{
	$FASTPG = "/opt/ibmtools/cct7/bin/linux32_fastpg.exe";
	$ora = new oracle();
	$pins = array();

	$query  = "select distinct ";
	$query .= "  cct_csc_netgroup ";
	$query .= "from ";
	$query .= "  new_cct7_csc ";
	$query .= "where ";
	$query .= "  length(cct_csc_netgroup) > 1 ";
	$query .= "order by ";
	$query .= "  cct_csc_netgroup";

	if ($ora->sql($query) == false)
	{
		printf("%s - %s\n", $ora->sql_statement, $ora->dbErrMsg);
		return false;
	}

	$high = 50;
	$count = 0;

	while ($ora->fetch())
	{
		$count++;

		if ($count >= $high)
		{
			$high += 50;
			printf("Added: %d records to lookup table\n", $count);
		}

		$cct_csc_netgroup = trim($ora->cct_csc_netgroup);
		printf("%.4d Processing netgroup pin: %s - ", $count, $cct_csc_netgroup);

		if (strlen($cct_csc_netgroup) == 0)
		{
			printf("cct_csc_netgroup is empty. SKIPPING\n");
			continue;
		}

		//
		// Do we have this net-pin in our $pin array?
		//
		if (!array_key_exists($cct_csc_netgroup, $pins))
		{
			//
			// Do a FASTPG lookup to retrieve the oncall person and added it to $pin
			//
			$cmd = sprintf("%s -p 1929 -j %s 2>&1", $FASTPG, $cct_csc_netgroup);
			printf("Running: %s\n", $cmd);
			$fp = popen($cmd, "r");

			// 
			// Is the pipe open?
			//
			if ($fp)
			{
				// Sample output from: fastpg.exe -p 1929 -j 17340
				//
				// FSTPG00I 17340 JEFFERIJ
				//

				//
				// Read one line in from the pipe.
				//
				if (($buffer = fgets($fp, 4096)) !== false)
				{
					// Remove multiple spaces, tabs and newlines if present
					$hold = preg_replace(array('/\s{2,}/', '/[\t\n]/'), ' ', $buffer); 
					// printf("This what we got back from the pipe: (%s)\n", $hold);

					if (strpos($hold, 'Unsuccessful') === true)
					{
						printf("%s(), %d: %s\n", __FUNCTION__, __LINE__, $hold);
						break;
					}

					// Parse data into an array using a space character as a delimiter
					$list = explode(" ", $hold); // Create an array

					$fastpg_msg = isset($list[0]) ? $list[0] : "";
					$fastpg_pin = isset($list[1]) ? $list[1] : "";
					$fastpg_oncall = isset($list[2]) ? strtolower($list[2]) : "";

					// printf("Adding: pins[%s] = '%s'\n", $cct_csc_netgroup, $fastpg_oncall);

					// This is a associated array (hash table)
					$pins[$cct_csc_netgroup] = $fastpg_oncall;
				}

				pclose($fp);
			}
			else
			{
				printf("%s(), %d: %s\n", __FUNCTION__, __LINE__, $cmd);
				return false;
			}
		}
	} // END: while ($ora->fetch())

	//
	// Check to see if each oncall person has a valid email address
	//
	printf("\nChecking to see if each oncall cuid has a valid email address\n");

	foreach ($pins as $cct_csc_netgroup => $oncall_cuid)
	{
		$query = sprintf("select mnet_email from cct7_mnet where lower(mnet_cuid) = lower('%s') and mnet_email is not null", $oncall_cuid);

		if ($ora->sql($query) == false)
		{
			printf("%s - %s\n", $ora->sql_statement, $ora->dbErrMsg);
			return false;
		}

		//
		// Got a email address from mnet?
		//
		if ($ora->fetch() == false)
		{
			printf("pin: %s, oncall cuid: %s - no email address\n", $cct_csc_netgroup, $oncall_cuid);

			//
			// Run FASTPG again to retrieve a list of CUID's found for this netgroup. 
			//
			$cmd = sprintf("%s -p 1929 -C -=NETmembers \"-R%s\" 2>&1", $FASTPG, $cct_csc_netgroup);
			printf("Running: %s\n", $cmd);

			//
			// Sample FASTPG output: fastpg.exe -p 1929 -C -=NETmembers "-R17340"
			// FSTPG00I transaction V2.92 rc:0 JEFFERIJ(P,B) NAREVAL RXCOOK5 AA43964 MKERL LEIENDEB POKUNEW GPARKIN JWPOPE
			//
			$fp = popen($cmd, "r");

			// 
			// Is the pipe open?
			//
			if ($fp)
			{
				if (($buffer = fgets($fp, 4096)) !== false)
				{
					// Remove multiple spaces, tabs and newlines if present
					$hold = preg_replace(array('/\s{2,}/', '/[\t\n]/'), ' ', $buffer); 

					if (strpos($hold, 'Unsuccessful') === true)
					{
						printf("%s(), %d: %s\n", __FUNCTION__, __LINE__, $hold);
						break;
					}

					// FSTPG00I transaction V2.92 rc:0 JEFFERIJ(P,B) NAREVAL RXCOOK5 AA43964 MKERL LEIENDEB POKUNEW GPARKIN JWPOPE

					// Parse data into an array using a space character as a delimiter
					$list = explode(":", $hold); // Create an array

					if (count($list) > 1)
					{
						printf("%s\n", $list[1]);
						$list2 = explode(" ", $list[1]);
						$okay = false;

						foreach ($list2 as $item)
						{
							if ($item == "0")
								continue;

							$list3 = explode("(", $item);
							$cuid = strtolower($list3[0]);

							// Check to see if this contact has a valid email address
							$query = sprintf("select mnet_email from cct7_mnet where lower(mnet_cuid) = lower('%s') and mnet_email is not null", $cuid);

							if ($ora->sql($query) == false)
							{
								printf("%s - %s\n", $ora->sql_statement, $ora->dbErrMsg);
								return false;
							}

							if ($ora->fetch())
							{
								// foreach ($pins as $cct_csc_netgroup => $oncall_cuid)
								printf("Changing netgroup %s oncall from %s to %s\n", $cct_csc_netgroup, $oncall_cuid, $cuid);
								$pins[$cct_csc_netgroup] = $cuid;
								$okay = true;
								break;
							}
						}

						if ($okay == false)
						{
							printf("Changing netgroup %s oncall from %s to invalid\n", $cct_csc_netgroup, $oncall_cuid);
							$pins[$cct_csc_netgroup] = 'invalid';
						}
					}
					else
					{
						printf("Cannot parse list: %s\n", $hold);
						printf("Changing netgroup %s oncall from %s to invalid\n", $cct_csc_netgroup, $oncall_cuid);
						$pins[$cct_csc_netgroup] = 'invalid';
					}
				}

				pclose($fp);
			} // END: if ($fp)
		} // END: if ($ora->fetch() == false)
	} // END: foreach ($pins as $cct_csc_netgroup => $oncall_cuid)

	printf("\nUpdating table: new_cct7_csc column: cct_csc_oncall\n");
	$high = 100;
	$count = 0;

	foreach ($pins as $cct_csc_netgroup => $oncall_cuid)
	{
		if ($oncall_cuid == 'invalid')
			continue;

		$count++;

		if ($count > $high)
		{
			printf("Committed after %d updates in new_cct7_csc\n", $count);
			$high += 100;
			$ora->commit();
		}

		$update  = "update new_cct7_csc set ";
		$update .= "  cct_csc_oncall = '" . $oncall_cuid . "' ";
		$update .= "where ";
		$update .= "  cct_csc_netgroup = '" . $cct_csc_netgroup . "'";

		if ($ora->sql($update) == false)
		{
			printf("%s - %s\n", $ora->sql_statement, $ora->dbErrMsg);
			return false;
		}
	}

	$ora->commit();
	printf("Committed after %d updates in new_cct7_csc\n", $count);
	printf("\nEnd of STEP 3 - Oncall updates\n");
	return true;
}
?>
