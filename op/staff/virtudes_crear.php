<?php
/**
 * MyBB 1.8
 * Copyright 2014 MyBB Group, All Rights Reserved
 *
 * Website: http://www.mybb.com
 * License: http://www.mybb.com/about/license
 */

define("IN_MYBB", 1);
define('THIS_SCRIPT', 'virtudes_crear.php');
require_once "./../../global.php";
require "./../../inc/config.php";
require_once "./../functions/op_functions.php";

global $templates, $mybb;
$uid = $mybb->user['uid'];
$username = $mybb->user['username'];
$user_accion = $mybb->get_input('accion'); 
$virtud_id = $mybb->get_input('virtud_id'); 

$virtud_id_post = $_POST["virtud_id"];
$accion_post = $_POST["accion"];

$nombre = trim($_POST["nombre"]);
$puntos = trim($_POST["puntos"]);
$descripcion = addslashes($_POST["descripcion"]);

$reload_js = "<script>window.location.href = window.location.pathname;</script>";

if ($accion_post == 'Agregar' && $nombre && $puntos && $descripcion && (is_mod($uid) || is_staff($uid))) {
    $log = "Nueva virtud creada de ID ($nombre). \n";

    $db->query(" 
        INSERT INTO `mybb_op_virtudes` (`virtud_id`, `nombre`, `puntos`, `descripcion`) VALUES ('$virtud_id', '$nombre','$puntos','$descripcion');
    ");

    // $db->query(" 
    //     INSERT INTO `mybb_op_audit_consola_mod` (`staff`, `username`, `razon`, `log`) VALUES 
    //     ('$staff', '$username', '$razon', '$log');
    // ");

    eval('$log_var = $log;');
    // eval('$reload_script = $reload_js;'); 
}

if ($accion_post == 'Remover' && $virtud_id_post && (is_mod($uid) || is_staff($uid))) {
    $log = "Remover técnica ID $virtud_id_post ($nombre).";
    
    $db->query(" 
        DELETE FROM `mybb_op_virtudes` WHERE `virtud_id`='$virtud_id_post';
    ");

    // $db->query(" 
    //     INSERT INTO `mybb_op_audit_consola_mod` (`staff`, `username`, `razon`, `log`) VALUES 
    //     ('$staff', '$username', '$razon', '$log');
    // ");

    eval('$log_var = $log;');
    eval('$reload_script = $reload_js;'); 
}

if ($user_accion && $virtud_id) {
    $query_virtudes = $db->query("
        SELECT * FROM `mybb_op_virtudes` WHERE virtud_id='$virtud_id'
    ");
    while ($t = $db->fetch_array($query_virtudes)) {
        eval('$virtud = $t;');
    }
}

if (is_mod($uid) || is_staff($uid)) { 
    eval('$accion = $user_accion;');
    eval("\$page = \"".$templates->get("staff_virtudes_crear")."\";");
    output_page($page);
} else {
    eval("\$page = \"".$templates->get("sin_permisos")."\";");
    output_page($page);
}
