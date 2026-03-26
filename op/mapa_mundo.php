<?php
/**
 * MyBB 1.8
 * Copyright 2014 MyBB Group, All Rights Reserved
 *
 * Website: http://www.mybb.com
 * License: http://www.mybb.com/about/license
 *
 */

define("IN_MYBB", 1);
define('THIS_SCRIPT', 'mapa_mundo.php');
require_once "./../global.php";
require "./../inc/config.php";
global $templates, $mybb;

$mapa = $mybb->get_input('mapa');

if ($mapa == 'NorthBlue') {
    $mapaUrl = '/images/op/uploads/North_Blue.jpg';
} else if ($mapa == 'EastBlue') {
    $mapaUrl = '/images/op/uploads/mapamundo.png';
} else {
    $mapaUrl = '/images/op/uploads/mapamundo.png';
}

eval("\$page = \"".$templates->get("op_mapa_mundo")."\";");
output_page($page);