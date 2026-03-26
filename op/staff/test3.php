<?php
/**
 * MyBB 1.8
 * Copyright 2014 MyBB Group, All Rights Reserved
 *
 * Website: http://www.mybb.com
 * License: http://www.mybb.com/about/license
 */

define("IN_MYBB", 1);
define('THIS_SCRIPT', 'test3.php');
require_once "./../../global.php";
require "./../../inc/config.php";
require_once "./../functions/op_functions.php";

global $templates, $mybb;
$uid = $mybb->user['uid'];
$username = $mybb->user['username'];
$user_fid = $mybb->get_input('fid'); 
$user_accion = $mybb->get_input('accion');   

if (is_narra($uid) || is_staff($uid)) { 
    eval('$fid = $user_fid;');       // Borrar, solo test
    eval('$accion = $user_accion;'); // Borrar, solo test
    eval("\$page = \"".$templates->get("staff_test3")."\";");
    output_page($page);
} else {
    $mensaje_redireccion = "No tienes acceso para entrar a esta página. ¿Seguro no te perdiste?";
    eval("\$page = \"".$templates->get("op_redireccion")."\";");
    output_page($page);
}