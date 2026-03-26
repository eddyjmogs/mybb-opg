<?php
/**
 * MyBB 1.8
 * Copyright 2014 MyBB Group, All Rights Reserved
 *
 * Website: http://www.mybb.com
 * License: http://www.mybb.com/about/license
 */

define("IN_MYBB", 1);
define('THIS_SCRIPT', 'virtudes_modificar.php');
require_once "./../../global.php";
require "./../../inc/config.php";
require_once "./../functions/op_functions.php";

global $templates, $mybb;
$uid = $mybb->user['uid'];
$username = $mybb->user['username'];
$virtud_id = $mybb->get_input('virtud_id'); 

$virtud_id_old = trim($_POST["virtud_id_old"]);
$virtud_id_post = trim($_POST["virtud_id"]);

$nombre = trim($_POST["nombre"]);
$puntos = trim($_POST["puntos"]);
$descripcion = addslashes($_POST["descripcion"]);

$reload_js = "<script>window.location.href = window.location.pathname;</script>";
$virtud = null;

if ($virtud_id) {
    $query_virtudes = $db->query("
        SELECT * FROM `mybb_op_virtudes` WHERE virtud_id='$virtud_id'
    ");
    while ($t = $db->fetch_array($query_virtudes)) {
        $virtud = $t;
    }
}

if ($virtud_id && $virtud_id_post && $nombre && $descripcion && (is_mod($uid) || is_staff($uid))) {
    $log = "Cambio a virtud ID $virtud_id_old ($nombre). $puntos. $descripcion.";

    $db->query(" 
        UPDATE `mybb_op_virtudes` SET `virtud_id`='$virtud_id_post',`nombre`='$nombre',`puntos`='$puntos',`descripcion`='$descripcion' WHERE `virtud_id`='$virtud_id_old';
    ");

    // $db->query(" 
    //     INSERT INTO `mybb_op_audit_consola_mod` (`staff`, `username`, `razon`, `log`) VALUES 
    //     ('$staff', '$username', '$razon', '$log');
    // ");

    $db->query(" 
        INSERT INTO `mybb_op_audit_consola_mod` (`staff`, `username`, `razon`, `log`) VALUES 
        ('$username', '$username', 'Cambios', '$log');
    ");

    eval('$log_var = $log;');
    eval('$reload_script = $reload_js;');
}

if (is_mod($uid) || is_staff($uid)) { 
    eval("\$page = \"".$templates->get("staff_virtudes_modificar")."\";");
    output_page($page);
} else {
    eval("\$page = \"".$templates->get("sin_permisos")."\";");
    output_page($page);
}
