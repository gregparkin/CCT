#!/opt/lampp/bin/php -q
<?php
/**
 * <make_netpin_to_cuid.php>
 *
 * @package    CCT
 * @file       make_netpin_to_cuid.php
 * @author     Greg Parkin <gregparkin58@gmail.com>
 * @date       02/21/2017
 * @version    7.0
 */
 
//
// The purpose of this routine is to create a fresh copy of Net-Pin's to CUIDs in cct7_netpin_to_cuid
//

$FASTPG = "/opt/ibmtools/cct7/bin/linux32_fastpg.exe";

//
// Class autoload function 
//
function __autoload($classname)
{
	require_once('/opt/ibmtools/www/cct7/classes/' . $classname . '.php');
}

$ora = new oracle(); // classes/dbms.php

//
// STEP1 - Get a list of net-pins from CSC (v_acsys_assign@csc)
//
if ($ora->sql("select distinct netgroup from csc.v_acsys_assign@csc_cct_web order by netgroup") == false)
{
	printf("ERROR: %s - %s\n", $ora->sql_statement, $ora->dbErrMsg);
	exit();
}

$netpins = array();

while ($ora->fetch())
{
	array_push($netpins, $ora->netgroup);
}

//
// STEP2 - Delete the old records
//
if ($ora->sql("delete from cct7_netpin_to_cuid") == false)
{
	printf("ERROR: %s - %s\n", $ora->sql_statement, $ora->dbErrMsg);
	exit();
}

//
// STEP3 - Run FASTPG for each pin and extract the member list (cuids)
//
$high = 50;
$count = 0;

foreach ($netpins as $netpin)
{
	$count++;

	if ($count >= $high)
	{
		$high += 50;
		printf("Added: %d records to lookup table\n", $count);
		$ora->commit();
	}

	//
	// Do a FASTPG lookup to retrieve the oncall person and added it to $pin
	//
	$cmd = sprintf("%s -c -=NETmembers %s 2>&1", $FASTPG, $netpin);
	printf("Running: %s\n", $cmd);
	$fp = popen($cmd, "r");

	// 
	// Is the pipe open?
	//
	if ($fp)
	{
		//
		// /xxx/bin/fastpg.exe -c -=NETmembers 4901
		//
		// FSTPG00I transaction V2.92 rc:0 SLAMAR(P,B) SKANDE6 RBLANCH JMC7776 SCOULTE JEFFERIJ KLAMONT SMITZLA JPFOST RRSMIT2 RAT3593 TJT8245 JJURBAN TWEIR
		//

		//
		// Read one line in from the pipe.
		//
		if (($buffer = fgets($fp, 4096)) !== false)
		{
			// Remove multiple spaces, tabs and newlines if present
			$hold = preg_replace(array('/\s{2,}/', '/[\t\n]/'), ' ', $buffer); 
			// printf("This is what we got back from the pipe: (%s)\n", $hold);

			//
			// (FSTPG00E 200 Unsuccessful group lookup. Unable to find group '0' )
			//
			if (strpos($hold, 'Unsuccessful') > 0)
			{
				printf("%s\n", $hold);
			}
			else
			{
				// Parse data into an array using a space character as a delimiter
				$list = explode(" ", $hold); // Create an array

				$i = 0;

				foreach($list as $cuid)
				{
					$i++;
					$cuid = strtolower($cuid);

					$oncall_primary = "N";
					$oncall_backup  = "N";

					if (strpos($cuid, "(p)") > 0)
					{
						$oncall_primary = "Y";
					}
					else if (strpos($cuid, "(b)") > 0)
					{
						$oncall_backup  = "Y";
					}
					else if (strpos($cuid, "(p,b)") > 0)
					{
						$oncall_primary = "Y";
						$oncall_backup  = "Y";
					}

					//
					// Strip off any (..)
					//
					$list2 = explode("(", $cuid);
					$cuid = trim($list2[0]);

					if (strlen($cuid) == 0)
						continue;

					if ($i > 4)
					{
						$insert  = "insert into cct7_netpin_to_cuid (net_pin_no, user_cuid, oncall_primary, oncall_backup) values ( ";
						$insert .= "'" . $netpin . "', '" . $cuid . "', '" . $oncall_primary . "', '" . $oncall_backup . "' )";
						// printf("%s\n", $insert);

						if ($ora->sql($insert) == false)
						{
							printf("ERROR: %s - %s\n", $ora->sql_statement, $ora->dbErrMsg);
							exit();
						}	
					}
				}
			}
		}

		pclose($fp);
	}
	else
	{
		printf("%s(), %d: %s\n", __FUNCTION__, __LINE__, $cmd);
		return false;
	}
}

$ora->commit();

?>
