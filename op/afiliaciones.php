<?php
/**
 * MyBB 1.8
 * Copyright 2014 MyBB Group, All Rights Reserved
 *
 * Website: http://www.mybb.com
 * License: http://www.mybb.com/about/license
 */

// --- DEPURACIÓN TEMPORAL---
// ini_set('display_errors', '1');               // mostrar (solo mientras depuras)
// ini_set('display_startup_errors', '1');
// ini_set('log_errors', '1');                   // deja esto en 1 siempre
// ini_set('error_log', './php-error.log'); // cámbialo a una ruta fuera del webroot
// error_reporting(E_ALL);
// -----------------------------------------------

define("IN_MYBB", 1);
define('THIS_SCRIPT', 'afiliaciones.php');
require_once "./../global.php";
require "./../inc/config.php";
require_once "./functions/op_functions.php";

global $templates, $mybb;
// $uid = $mybb->user['uid'];
// $username = $mybb->user['username'];
// $user_fid = $mybb->get_input('fid'); 
// $user_accion = $mybb->get_input('accion'); 

// $ficha_id = $_POST["ficha_id"];
// $accion = $_POST["accion"];
// $tid = $_POST["tid"];

eval("\$page = \"".$templates->get('op_afiliaciones')."\";");
output_page($page);