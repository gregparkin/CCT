<?php
/**
 * @package    CCT
 * @file       dump_env.php
 * @author     Greg Parkin <gregparkin58@gmail.com>
 * @date       $Date:  $ GMT
 * @version    $Revision:  $
 *
 * $Log:  $
 *
 *
 * $Source:  $
 */

//$path = '/usr/lib/pear';
//set_include_path(get_include_path() . PATH_SEPARATOR . $path);

set_include_path("/opt/ibmtools/www/cct7/classes");

?>
<html>
<body>
<pre>
    <?php
    session_start();

    printf("_POST: ");
    print_r($_POST);
    printf("_GET: ");
    print_r($_GET);
    printf("_REQUEST: ");
    print_r($_REQUEST);
    printf("_SERVER: ");
    print_r($_SERVER);
    printf("_SESSION: ");
    print_r($_SESSION);
    ?>

</pre>
</body>
</html>

