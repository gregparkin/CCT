<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<?php
/**
 * <connections2.php>
 *
 * @package    CCT
 * @file       connections2.php
 * @author     Greg Parkin <gregparkin58@gmail.com>
 * @date       10/11/2013
 * @version    6.0.0
 */

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

$l   = new library();      // classes/library.php   Using phone_clean()
$ora = new oracle();

// where trace_data_source.php?hostname=<hostname>
$hostname = "hcdnx11a";
$reboot = "Y";

if (isset($_REQUEST['hostname']))
	$hostname = $_REQUEST['hostname'];
	
if (isset($_REQUEST['reboot']))
	$reboot = "Y";

?>
<html>
<body>

<h1 align="center"><u>Connections2</u></h1>

<?php
if (!isset($l->parm['no_header_footer']))
{
?>
	<table border="0" cellpadding="4" cellspacing="3" style="border-collapse: collapse" bordercolor="#111111" width="100%">
	<tr>
		<td align="right" width="8%">
    		<font size="4"><b>Hostname:</b></font>
	    </td>
    	<td width="92%">
    		<input type="text" name="hostname" size=20 maxlength=40 value="<?php echo $hostname ?>" />
        </td>
    </tr>
    <tr>
    	<td align="right"width="8%">&nbsp;   	
        </td>
        <td width="92%">
        	<input type="checkbox" name="reboot" value="ON" <?php printf("%s", $reboot == "Y" ? "checked" : ""); ?> />Reboot?
        </td>
    </tr>
    <tr>
    	<td>&nbsp;</td>
        <td>
	        <input type="submit" name="button" value="Okay" />
    	</td>
	</tr>
	</table>  
    <hr />  
<?php
}

$children_server = array();
$contacts = array();
$cluster_list = array();

//
// If we have a hostname then retrieve the data.
//
if (strlen($hostname) > 0)
{
	// 1. Get the cct6_computers server record
	$query  = "select ";
	$query .= "  computer_hostname, ";
	$query .= "  computer_lastid, ";
	$query .= "  computer_status, ";
	$query .= "  computer_os_lite, ";
	$query .= "  computer_complex, ";
	$query .= "  computer_complex_lastid, ";
	$query .= "  computer_complex_name, ";
	$query .= "  computer_complex_parent_name, ";
	$query .= "  computer_complex_child_names ";
	$query .= "from ";
	$query .= "  cct6_computers ";
	$query .= "where ";
	$query .= "  computer_hostname = lower('" . $hostname . "')";
	
	if ($ora->sql($query) == false)
	{

		$error = sprintf("%s - %s", $ora->sql_statement, $ora->dbErrMsg);
		$l->debug1(__FILE__, __FUNCTION__, __LINE__, "%s - %s", $ora->sql_statement, $ora->dbErrMsg);
		$l->html_stop(__FILE__, __FUNCtION__, __LINE__, "%s", $error);
	}
	
	if ($ora->fetch() == false)
	{
		$error = sprintf("Computer record not found for: %s", $hostname);
		printf("<p>Computer record not found for: %s</p>\n", $hostname);
		$l->html_stop(__FILE__, __FUNCtION__, __LINE__, "%s", $error);
	}
	
	printf("<pre>\n");
	
	$node = new data_node();
	$node->computer_lastid   = $ora->computer_lastid;
	$node->computer_hostname = $ora->computer_hostname;
	$node->computer_status   = $ora->computer_status;
	
	if ($ora->computer_complex == "Y" && strlen($ora->computer_os_lite) == 0)
	{
		$node->computer_os_lite = "COMPLEX";
	}
	else
	{
		$node->computer_os_lite = $ora->computer_os_lite;
	}
	
	$node->computer_complex             = $ora->computer_complex;
	$node->computer_complex_lastid      = $ora->computer_complex_lastid;
	$node->computer_complex_name        = $ora->computer_complex_name;
	$node->computer_complex_parent_name = $ora->computer_complex_parent_name;
	$node->computer_complex_child_names = $ora->computer_complex_child_names;
	$node->connections                  = $ora->computer_hostname;
	$node->contacts                     = NULL;
	$node->connections                  = connectionString(
		$ora->computer_complex_name, 
		$ora->computer_complex_parent_name, 
		$ora->computer_hostname);
			
	$children_server[$ora->computer_hostname] = $node;

	childrenServers($ora->computer_complex_child_names);
	
	if ($reboot == "Y")
	{
		//
		// Add virtual servers (vmware)
		//
		foreach ($children_server as $child)
		{
			$query  = "select connected_name from cct6_virtual_servers where name = upper('" . $child->computer_hostname . "')";
						
			if ($ora->sql($query) == false)
			{
				$l->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $ora->sql_statement);
				$l->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $ora->dbErrMsg);
				$l->error = sprintf("SQL syntax error in file: %s, line: %d. Please contact Greg Parkin", __FILE__, __LINE__);
				exit();
			}
			
			if ($ora->fetch())
			{
				// Record the cluster name
				$cluster_list[$ora->connected_name] = true;
			}
		}
		
		//
		// Add any new vmware servers to our main server list
		//
		foreach($cluster_list as $cluster_name => $val)
		{
			$l->debug1(__FILE__, __FUNCTION__, __LINE__, "Adding vmware servers for cluster name: %s", $cluster_name);
			
			$query = "select distinct " .
				"c.computer_lastid, " .
				"to_char(c.computer_last_update, 'MM/DD/YYYY HH24:MI'), " .
				"to_char(c.computer_install_date, 'MM/DD/YYYY HH24:MI'), " .
				"c.computer_systemname, " .
				"c.computer_hostname, " .
				"c.computer_operating_system, " .
				"c.computer_os_lite, " .
				"c.computer_status, " .
				"c.computer_status_description, " .
				"c.computer_description, " .
				"c.computer_nature, " .
				"c.computer_platform, " .
				"c.computer_type, " .
				"c.computer_clli, " .
				"c.computer_clli_fullname, " .
				"c.computer_timezone, " .
				"c.computer_building, " .
				"c.computer_address, " .
				"c.computer_city, " .
				"c.computer_state, " .
				"c.computer_floor_room, " .
				"c.computer_grid_location, " .
				"c.computer_lease_purchase, " .
				"c.computer_serial_no, " .
				"c.computer_asset_tag, " .
				"c.computer_model_category, " .
				"c.computer_model_no, " .
				"c.computer_model, " .
				"c.computer_model_mfg, " .
				"c.computer_cpu_type, " .
				"c.computer_cpu_count, " .
				"c.computer_cpu_speed, " .
				"c.computer_memory_mb, " .
				"c.computer_ip_address, " .
				"c.computer_domain, " .
				"c.computer_hostname_domain, " .
				"c.computer_dmz, " .
				"c.computer_ewebars_title, " .
				"c.computer_ewebars_status, " .
				"c.computer_backup_format, " .
				"c.computer_backup_nodename, " .
				"c.computer_backup_program, " .
				"c.computer_backup_server, " .
				"c.computer_netbackup, " .
				"c.computer_complex, " .
				"c.computer_complex_lastid, " .
				"c.computer_complex_name, " .
				"c.computer_complex_parent_name, " .
				"c.computer_complex_child_names, " .
				"c.computer_complex_partitions, " .
				"c.computer_service_guard, " .
				"c.computer_os_group_contact, " .
				"c.computer_cio_group, " .
				"c.computer_managing_group, " .
				"c.computer_contract, " .
				"c.computer_contract_ref, " .
				"c.computer_contract_status, " .
				"c.computer_contract_status_type, " .
				"to_char(c.computer_contract_date, 'MM/DD/YYYY HH24:MI'), " .
				"c.computer_ibm_supported, " .
				"c.computer_gold_server, " .
				"c.computer_slevel_objective, " .
				"c.computer_slevel_score, " .
				"c.computer_slevel_colors, " .
				"c.computer_special_handling, " .
				"c.computer_applications, " .
				"c.computer_osmaint_weekly, " .
				"c.computer_osmaint_monthly, " .
				"c.computer_osmaint_quarterly, " .
				"c.computer_csc_os_banners, " .
				"c.computer_csc_pase_banners, " .
				"c.computer_csc_dba_banners, " .
				"c.computer_csc_fyi_banners, " .
				"c.computer_disk_array_alloc_kb, " .
				"c.computer_disk_array_used_kb, " .
				"c.computer_disk_array_free_kb, " .
				"c.computer_disk_local_alloc_kb, " .
				"c.computer_disk_local_used_kb, " .
				"c.computer_disk_local_free_kb " .
				"from cct6_computers c";
				
			$query .= ", cct6_virtual_servers v ";
			$query .= "where ";
			$query .= "  v.connected_name = '" . $cluster_name . "' and ";
			$query .= "  v.connection_type = 'ESX SERVER TO VMWARE CLUSTER' and ";
			$query .= "  c.computer_lastid = v.lastid ";
			$query .= "order by ";
			$query .= "  c.computer_hostname";
			
			//$this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $query);
	
			if ($ora->sql($query) == false)
			{
				$l->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $ora->sql_statement);
				$l->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $ora->dbErrMsg);
				$l->error = sprintf("SQL syntax error in file: %s, line: %d. Please contact Greg Parkin", __FILE__, __LINE__);
				exit();
			}
				
			while ($ora->fetch())
			{
				$node = new data_node();
				$node->computer_lastid   = $ora->computer_lastid;
				$node->computer_hostname = $ora->computer_hostname;
				$node->computer_status   = $ora->computer_status;
				
				if ($ora->computer_complex == "Y" && strlen($ora->computer_os_lite) == 0)
				{
					$node->computer_os_lite = "COMPLEX";
				}
				else
				{
					$node->computer_os_lite = $ora->computer_os_lite;
				}
		
				$node->computer_complex             = $ora->computer_complex;
				$node->computer_complex_lastid      = $ora->computer_complex_lastid;
				$node->computer_complex_name        = $ora->computer_complex_name;
				$node->computer_complex_parent_name = $ora->computer_complex_parent_name;
				$node->computer_complex_child_names = $ora->computer_complex_child_names;
				
				//$node->connections = connectionString($ora->computer_complex_name, $ora->computer_complex_parent_name, $ora->computer_hostname);
				//$node->connections = connectionString($ora->computer_complex_name, $cluster_name, $ora->computer_hostname);
				
				$node->connections = $cluster_name . "->" . $ora->computer_hostname;
	
				$node->contacts = NULL;
				
				$children_server[$ora->computer_hostname] = $node;				
			}
		}
	}
	
	foreach ($children_server as $child)
	{
		//printf("\n%s - %s - %s\n", $child->computer_lastid, $child->connections, $child->computer_status);
		//printContacts($child->computer_lastid);
		getContacts($child);
	}
	
	printf("%s\n", $hostname);
	?>
    	<table border="1">
        <tr>
        	<td><b>Netpin/Members</b></td>
            <td><b>Connections</b></td>
            <td><b>OS</b></td>
            <td><b>Status</b></td>
            <td><b>Approval</b></td>
            <td><b>Group Types</b></td>
            <td><b>Notify Type</b></td>
            <td><b>CSC Support Banners (Primary)</b></td>
            <td><b>Apps/DBMS</b></td>
        </tr>
    <?php
	
	$count = 0;
	
	foreach($contacts as $netpin => $contact)
	{
		printf("<tr>\n");
		printf("<td valign=\"top\"><font size=\"2\"><b>%s</b>", $netpin);
		
		$query = "select * from cct6_netpin_to_cuid where net_pin_no = " . $netpin . " order by user_cuid";
		
		$l->debug1(__FILE__, __FUNCTION__, __LINE__, "<hr>");
		$l->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $query);
		$l->debug1(__FILE__, __FUNCTION__, __LINE__, "is_numeric(%s) = %s", $netpin, is_numeric($netpin) ? "true" : "false");
		
		if (is_numeric($netpin) && $ora->sql($query))
		{
			while ($ora->fetch())
			{
				$l->debug1(__FILE__, __FUNCTION__, __LINE__, "cuid: %s", $ora->user_cuid);
				
				printf("<br>%s", $ora->user_cuid);
				
				if ($ora->oncall_primary == "Y")
					printf("(P)");
				
				if ($ora->oncall_backup == "Y")
					printf("(B)");
			}
		}
		
		if (is_numeric($netpin) && $netpin == 0)
			printf("<br>%s", $contact->primary_cuid);
		
		printf("</font></td>\n");
		
		printf("<td valign=\"top\"><font size=\"2\">%s</font></td>\n", $contact->connections);
		printf("<td valign=\"top\"><font size=\"2\">%s</font></td>\n", $contact->os_lite);
		printf("<td valign=\"top\"><font size=\"2\">%s</font></td>\n", $contact->status);
		printf("<td valign=\"top\"><font size=\"2\">%s</font></td>\n", strlen($contact->work_groups) == 0 ? "&nbsp;" : "WAITING");
		printf("<td valign=\"top\"><font size=\"2\">%s</font></td>\n", $contact->work_groups);
		printf("<td valign=\"top\"><font size=\"2\">%s</font></td>\n", $contact->notify_type);
		printf("<td valign=\"top\"><font size=\"2\">%s</font></td>\n", $contact->group_name);
		printf("<td valign=\"top\"><font size=\"2\">%s</font></td>\n", $contact->applications);
		printf("</tr>\n");
	}
	
	printf("</table></pre>\n");
}

function getContacts($child)
{
	global $ora, $h, $l, $contacts;
	
	$query = "select " .
				"cct_csc_netgroup, " .
				"cct_app_acronym, " .
				"cct_csc_userid_1, " .
				"cct_csc_userid_2, " .
				"cct_csc_userid_3, " .
				"cct_csc_userid_4, " .
				"cct_csc_userid_5, " .
				"cct_csc_group_name, " .
				"cct_csc_oncall " .
			"from " .
				"cct6_csc " .
			"where " .
				"lastid = " . $child->computer_lastid . " and ( " .
				"cct_csc_group_name = 'MiddleWare Support' or " .
				"cct_csc_group_name = 'Development Support' or " .
				"cct_csc_group_name = '! Operating System Support' or " .
				"cct_csc_group_name = '! Database Support' or " .
				"cct_csc_group_name = '! Development Database Support' or " .
				"cct_csc_group_name = 'Application Support' or " .
				"cct_csc_group_name = 'Infrastructure' or " .
				"cct_csc_group_name = 'Application Utilities, Middleware, Legacy DB, Other (Hosted on this Server)' or " .
				"cct_csc_group_name = 'Applications or Databases Desiring Notification (Not Hosted on this Server)' or " .
				"cct_csc_group_name = 'Applications Owning Database (DB Hosted on this Server, Owning App Is Not)' ) " .
			"order by cct_csc_group_name";
			
	if ($ora->sql($query) == false)
	{
		printf("<p>%s - %s</p>\n", $ora->sql_statement, $ora->dbErrMsg);
		exit();
	}
	
	$count = 0;
	
	while ($ora->fetch())
	{
		$count++;
			
		//
		// Figure out what contact to use for each group based upon CMP policy rules.
		//		
		$notify_type = "";
		$group_type  = "";
		$group_name  = "";
		
		if ($ora->cct_csc_group_name == 'Development Support')
		{
			$notify_type = 'FYI'; 
			$group_type  = 'PASE';
			$group_name  = $ora->cct_csc_group_name;
		}
		else if ($ora->cct_csc_group_name == 'MiddleWare Support')
		{
			$notify_type = 'APPROVER';
			$group_type  = 'PASE';
			$group_name  = $ora->cct_csc_group_name;
		}			
		else if ($ora->cct_csc_group_name == '! Operating System Support')
		{
			$notify_type = 'APPROVER';
			$group_type  = 'OS';			
			$group_name  = 'Operating System Support';	
		}
		else if ($ora->cct_csc_group_name == '! Database Support')
		{
			$notify_type = 'APPROVER';
			$group_type  = 'DBA';	
			$group_name  = 'Database Support';		
		}
		else if ($ora->cct_csc_group_name == '! Development Database Support')
		{
			$notify_type = 'FYI';
			$group_type  = 'DBA';	
			$group_name  = 'Development Database Support';		
		}
		else if ($ora->cct_csc_group_name == 'Application Support')
		{
			$notify_type = 'APPROVER';
			$group_type  = 'PASE';	
			$group_name  = $ora->cct_csc_group_name;		
		}
		else if ($ora->cct_csc_group_name == 'Infrastructure')
		{
			$notify_type = 'APPROVER';
			$group_type  = 'PASE';		
			$group_name  = $ora->cct_csc_group_name;	
		}
		else if ($ora->cct_csc_group_name == 'Application Utilities, Middleware, Legacy DB, Other (Hosted on this Server)')
		{
			$notify_type = 'APPROVER'; 
			$group_type  = 'PASE';	
			$group_name  = $ora->cct_csc_group_name;		
		}
		else if ($ora->cct_csc_group_name == 'Applications or Databases Desiring Notification (Not Hosted on this Server)')
		{
			$notify_type = 'FYI'; 
			$group_type  = 'PASE';	
			$group_name  = $ora->cct_csc_group_name;		
		}
		else if ($ora->cct_csc_group_name = 'Applications Owning Database (DB Hosted on this Server, Owning App Is Not)')
		{
			$notify_type = 'APPROVER';
			$group_type  = 'PASE';
			$group_name  = $ora->cct_csc_group_name;			
		}
		else
		{
			$this->debug4(__FILE__, __FUNCTION__, __LINE__, "NO MATCH FOR: %s", $ora->cct_csc_group_name);
			$group_name = "no match";
		}
		
		if (array_key_exists($ora->cct_csc_netgroup, $contacts))
		{
			$node = $contacts[$ora->cct_csc_netgroup];
		}
		else
		{
			$node = new data_node();
			$contacts[$ora->cct_csc_netgroup] = $node;  // global contacts variable
			$node->connections  = "";
			$node->status       = "";
			$node->os_lite      = "";
			$node->group_name   = "";
			$node->applications = "";
			$node->work_groups  = "";
			$node->notify_type  = "";
		}	
		
		if (strlen($node->connections) == 0 && strlen($child->connections) > 0)
		{
			$node->connections = $child->connections;
		}
		else
		{
			$node->connections .= ",<br>" . $child->connections;
		}
		
		if (strlen($node->status) == 0 && strlen($child->computer_status) > 0)
		{
			$node->status = $child->computer_status;
		}
		else
		{
			$node->status .= ",<br>" . $child->computer_status;
		}
		
		if (strlen($node->os_lite) == 0)
		{
			$node->os_lite = $child->computer_os_lite;
		}
		else
		{
			$node->os_lite .= ",<br>" . $child->computer_os_lite;
		}
		
		if (strlen($node->group_name) == 0 && strlen($group_name) > 0)
		{
			$node->group_name = $group_name . "(" . $ora->cct_csc_userid_1 . ")";
		}
		else
		{
			$node->group_name .= ",<br>" . $group_name . "(" . $ora->cct_csc_userid_1 . ")";
		}
		
		if (strlen($node->applications) == 0 && strlen($ora->cct_app_acronym) > 0)
		{
			$node->applications = $ora->cct_app_acronym;
		}
		else
		{
			$node->applications .= ",<br>" . $ora->cct_app_acronym;
		}
		
		//
		// Figure out group and type
		//
		// $node->work_groups = "";
		// $node->notify_type = "";
		//
		// $notify_type = 'APPROVER';
		// $group_type = 'PASE';
		
		$oflag = false;
		$pflag = false;
		$dflag = false;
		
		if      ($group_type == "OS")
			$oflag = true;
		else if ($group_type == "PASE")
			$pflag = true;
		else if ($group_type == "DBA")
			$dflag = true;
		
		if (preg_match("/OS/", $node->work_groups))
			$oflag = true;	

		if (preg_match("/PASE/", $node->work_groups))
			$pflag = true;
			
		if (preg_match("/DBA/", $node->work_groups))
			$dflag = true;
			
		$work_groups = "";
		
		if ($oflag)
		{
			$work_groups = "OS";
		}
		
		if ($pflag)
		{
			if (strlen($work_groups) > 0)
				$work_groups .= "_PASE";
			else
				$work_groups  = "PASE";
		}
		
		if ($dflag)
		{
			if (strlen($work_groups) > 0)
				$work_groups .= "_DBA";
			else
				$work_groups  = "DBA";
		}
		
		$node->work_groups = $work_groups;
		
		if (strlen($node->notify_type) == 0)
        {
            $node->notify_type = $notify_type;
        }
		else if ($node->notify_type == "FYI")
        {
            $node->notify_type = $notify_type;
        }
	}
	
	if ($count == 0)
	{
		if (array_key_exists('NONE', $contacts))
		{
			$node = $contacts[$ora->cct_csc_netgroup];
		}
		else
		{
			$node = new data_node();
			$contacts['NONE']   = $node;  // global contacts variable
			$node->connections  = "";
			$node->status       = "";
			$node->os_lite      = "";
			$node->group_name   = "";
			$node->applications = "";
			$node->work_groups  = "";
			$node->notify_type  = "";
		}	
		
		if (strlen($node->connections) == 0 && strlen($child->connections) > 0)
		{
			$node->connections = $child->connections;
		}
		else
		{
			$node->connections .= ",<br>" . $child->connections;
		}
		
		if (strlen($node->status) == 0 && strlen($child->computer_status) > 0)
		{
			$node->status = $child->computer_status;
		}
		else
		{
			$node->status .= ",<br>" . $child->computer_status;
		}
		
		if (strlen($node->os_lite) == 0)
		{
			$node->os_lite = $child->computer_os_lite;
		}
		else
		{
			$node->os_lite .= ",<br>" . $child->computer_os_lite;
		}
		
		if (strlen($node->group_name) == 0)
		{
			$node->group_name = "NONE";
		}
		else
		{
			$node->group_name .= ",<br>NONE";
		}
		
		if (strlen($node->applications) == 0 && strlen($ora->cct_app_acronym) > 0)
		{
			$node->applications = $ora->cct_app_acronym;
		}
		else
		{
			$node->applications .= ",<br>" . $ora->cct_app_acronym;
		}
	}
}

function printContacts($lastid)
{
	global $ora, $h, $l;
	
	$query = "select " .
				"cct_csc_netgroup, " .
				"cct_app_acronym, " .
				"cct_csc_userid_1, " .
				"cct_csc_userid_2, " .
				"cct_csc_userid_3, " .
				"cct_csc_userid_4, " .
				"cct_csc_userid_5, " .
				"cct_csc_group_name, " .
				"cct_csc_oncall " .
			"from " .
				"cct6_csc " .
			"where " .
				"lastid = " . $lastid . " and ( " .
				"cct_csc_group_name = 'MiddleWare Support' or " .
				"cct_csc_group_name = 'Development Support' or " .
				"cct_csc_group_name = '! Operating System Support' or " .
				"cct_csc_group_name = '! Database Support' or " .
				"cct_csc_group_name = '! Development Database Support' or " .
				"cct_csc_group_name = 'Application Support' or " .
				"cct_csc_group_name = 'Infrastructure' or " .
				"cct_csc_group_name = 'Application Utilities, Middleware, Legacy DB, Other (Hosted on this Server)' or " .
				"cct_csc_group_name = 'Applications or Databases Desiring Notification (Not Hosted on this Server)' or " .
				"cct_csc_group_name = 'Applications Owning Database (DB Hosted on this Server, Owning App Is Not)' ) " .
			"order by cct_csc_group_name";
			
	if ($ora->sql($query) == false)
	{
		printf("<p>%s - %s</p>\n", $ora->sql_statement, $ora->dbErrMsg);
		exit();
	}
	
	while ($ora->fetch())
	{
		printf("%s - %s - %s - %s\n",
			$lastid, $ora->cct_csc_netgroup, $ora->cct_csc_group_name, $ora->cct_app_acronym);
	}
}

function childrenServers($computer_complex_child_names)
{
	global $children_server;
	global $ora;
	global $h;
	global $l;
	
	$str = str_replace(",", " ", $computer_complex_child_names);                  // Convert any commas to spaces
	$complex_children = preg_replace(array('/\s{2,}/', '/[\t\n]/'), ' ', $str);   // Remove multiple spaces, tabs and newlines if present	
	$systems = explode(" ", $complex_children);                                   // Create an array of $systems
	
	//print_r($systems);
		
	foreach ($systems as $system)
	{
		if (array_key_exists($system, $children_server))
			continue;
			
		$query  = "select ";
		$query .= "  computer_hostname, ";
		$query .= "  computer_lastid, ";
		$query .= "  computer_status, ";
		$query .= "  computer_os_lite, ";
		$query .= "  computer_complex, ";
		$query .= "  computer_complex_lastid, ";
		$query .= "  computer_complex_name, ";
		$query .= "  computer_complex_parent_name, ";
		$query .= "  computer_complex_child_names ";
		$query .= "from ";
		$query .= "  cct6_computers ";
		$query .= "where ";
		$query .= "  computer_hostname = lower('" . $system . "')";
		
		if ($ora->sql($query) == false)
		{
			$l->debug1(__FILE__, __FUNCTION__, __LINE__, "%s - %s", $ora->sql_statement, $ora->dbErrMsg);
			$l->html_stop();
			exit();
		}
		
		if ($ora->fetch() == false)
		{
			continue;
		}	
		
		$node = new data_node();
		$node->computer_lastid = $ora->computer_lastid;
		$node->computer_hostname = $ora->computer_hostname;
		$node->computer_status = $ora->computer_status;
		$node->computer_os_lite = $ora->computer_os_lite;
		$node->computer_complex = $ora->computer_complex;
		$node->computer_complex_lastid = $ora->computer_complex_lastid;
		$node->computer_complex_name = $ora->computer_complex_name;
		$node->computer_complex_parent_name = $ora->computer_complex_parent_name;
		$node->computer_complex_child_names = $ora->computer_complex_child_names;
		
		$node->connections = connectionString($ora->computer_complex_name, $ora->computer_complex_parent_name, $ora->computer_hostname);

		$node->contacts = NULL;
		
		$children_server[$ora->computer_hostname] = $node;
		
		// Recursive 
		
		if (strlen($ora->computer_complex_child_names) > 0)
			childrenServers($ora->computer_complex_child_names);
	}
}

function connectionString($complex, $parent, $hostname)
{
	$connection = "";
	
	if (strlen($complex) > 0)
		$connection = $complex;
		
	if (strlen($parent) > 0)
	{
		if (strlen($connection) > 0)
		{
			$connection = $connection . "->" . $parent;
		}
		else
		{
			$connection = $parent;
		}
	}
	
	if (strlen($hostname) > 0)
	{
		if (strlen($connection) > 0)
		{
			$connection = $connection . "->" . $hostname;
		}
		else
		{
			$connection = $hostname;
		}
	}
	
	return $connection;
}

