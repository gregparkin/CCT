<?php
/**
 * @package    CCT
 * @file       test.php
 * @author     Greg Parkin <gregparkin58@gmail.com>
 * @date       $Date: 2015/09/10 23:06:46 $ GMT
 * @version    $Revision: 1.2 $
 *
 * $Log: test.php,v $
 * Revision 1.2  2015/09/10 23:06:46  GREG
 * Menu was not displaying correctly.
 *
 * Revision 1.1.1.1  2015/09/10 19:37:15  GREG
 * Initial checkin.
 *
 *
 * $Source: C:/cvs_repository/cct7/scripts/test.php,v $
 */

$ball = array();
$list = array();

$list['c1'] = 'item1';
$list['c2'] = 'item2';
$list['c3'] = 'item3';
$list['c4'] = 'item4';
$list['c5'] = 'item5';

$ball[] = array_map('utf8_encode', $list);

$list['c1'] = 'ball1';
$list['c2'] = 'ball2';
$list['c3'] = 'ball3';
$list['c4'] = 'ball4';
$list['c5'] = 'ball5';

$ball[] = array_map('utf8_encode', $list);

echo json_encode($ball);

$request = "action=get_data&member_cuid=gparkin&member_name=Greg+Parkin = (action=get_data&member_cuid=gparkin&member_name=Greg+Parkin)";

$input = json_decode($request); // $input = json_decode($request, true);

print_r($input);
