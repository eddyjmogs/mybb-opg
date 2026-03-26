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
define('THIS_SCRIPT', 'leido.php');
require_once "./../global.php";
require "./../inc/config.php";
global $templates, $mybb;

$uid = $mybb->user['uid'];
$pid = $mybb->get_input('pid'); 

$db->query(" 
    INSERT INTO `mybb_op_leidos` (`pid`, `uid`) VALUES ('$pid', '$uid');
");

$mensaje_redireccion = "Tema ha sido leído. <a href='/showthread.php?pid=$pid'>Haz click aquí para volver al tema. </a>";
eval("\$page = \"".$templates->get("op_redireccion")."\";");
output_page($page);


// humano, gyojin, gigante, ningyo, skypiean, mink, tontatta, lunarian, oni, variante

// UPDATE `mybb_op_fichas` SET `fuerza_pasiva`='20',`resistencia_pasiva`='25', `puntos_estadistica`='$puntos_estadistica' WHERE `fid`='$fid';
