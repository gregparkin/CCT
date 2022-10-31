<?php
/**
 * Created by  PhpStorm on 12/9/2015 10:37 AM
 *
 * @package    cct6
 * @file       test_oracle.php
 * @author     Greg Parkin <gregparkin58@gmail.com>
 *             $Date: $ GMT
 *             $Revision: $
 *             $Date$
 *             $Log $
 *             $Source: $
 */

$l = "(DESCRIPTION = (ADDRESS = (PROTOCOL = TCP)(HOST = localhost)(PORT = 1521))(CONNECT_DATA = (SERVER = DEDICATED)(SERVICE_NAME = orcl)))";

//$l = "//127.0.0.1:1521/orcl";

$conn = oci_connect("cct", "f3LzunY8", "localhost/ORCL");

if (!$conn)
{
	printf("ERROR: %s\n", oci_error());
}
else
{
	printf("Connect successful\n");
}
?>
