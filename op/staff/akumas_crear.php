<?php
/**
 * MyBB 1.8
 * Copyright 2014 MyBB Group, All Rights Reserved
 *
 * Website: http://www.mybb.com
 * License: http://www.mybb.com/about/license
 */

define("IN_MYBB", 1);
define('THIS_SCRIPT', 'akumas_crear.lphp');
require_once "./../../global.php";
require "./../../inc/config.php";
require_once "./../functions/op_functions.php";

global $templates, $mybb;
$uid = $mybb->user['uid'];
$username = $mybb->user['username'];
$user_accion = $mybb->get_input('accion'); 
$akuma_id = $mybb->get_input('akuma_id'); 

$accion_post = $_POST["accion"];
$akuma_id_post = $_POST["akuma_id"];
$nombre = trim($_POST["nombre"]);
$subnombre = trim($_POST["subnombre"]);
$categoria = trim($_POST["categoria"]);
$tier = trim($_POST["tier"]);
$ocupada = $_POST["ocupada"];
$imagen = trim($_POST["imagen"]);

$dominio1 = trim($_POST["dominio1"]);
$dominio2 = trim($_POST["dominio2"]);
$dominio3 = trim($_POST["dominio3"]);

$pasiva1 = trim($_POST["pasiva1"]);
$pasiva2 = trim($_POST["pasiva2"]);
$pasiva3 = trim($_POST["pasiva3"]);

$descripcion = addslashes($_POST["descripcion"]);

$reload_js = "<script>window.location.href = window.location.pathname;</script>";

if ($accion_post == 'Agregar' && $nombre && $categoria && $tier && $descripcion && (is_mod($uid) || is_staff($uid))) {
    $log = "Nueva akuma creada de ID ($nombre). \nLa nueva akuma posee: \nnombre=$nombre,\ncategoria=$categoria\ntier=$tier,\ndescripcion=$descripcion";

    $db->query(" 
        INSERT INTO `mybb_op_akumas` (`akuma_id`, `nombre`, `subnombre`, `categoria`, `tier`, `descripcion`, `ocupada`, `imagen`) VALUES ('$akuma_id_post', '$nombre','$subnombre','$categoria','$tier','$descripcion', '$ocupada', '$imagen');
    ");

    // $db->query(" 
    //     INSERT INTO `mybb_op_audit_consola_mod` (`staff`, `username`, `razon`, `log`) VALUES 
    //     ('$staff', '$username', '$razon', '$log');
    // ");

    eval('$log_var = $log;');
    // eval('$reload_script = $reload_js;'); 
}

if ($accion_post == 'Remover' && $akuma_id_post && $nombre && (is_mod($uid) || is_staff($uid))) {
    $log = "Remover técnica ID $akuma_id_post ($nombre).";
    
    $db->query(" 
        DELETE FROM `mybb_op_akumas` WHERE `akuma_id`='$akuma_id_post';
    ");

    // $db->query(" 
    //     INSERT INTO `mybb_op_audit_consola_mod` (`staff`, `username`, `razon`, `log`) VALUES 
    //     ('$staff', '$username', '$razon', '$log');
    // ");

    eval('$log_var = $log;');
    eval('$reload_script = $reload_js;'); 
}

if ($user_accion && $akuma_id) {
    $query_akumas = $db->query("
        SELECT * FROM `mybb_op_akumas` WHERE akuma_id='$akuma_id'
    ");
    while ($t = $db->fetch_array($query_akumas)) {
        eval('$akuma = $t;');
    }
}

if (is_mod($uid) || is_staff($uid)) { 
    eval('$accion = $user_accion;');
    eval("\$page = \"".$templates->get("staff_akumas_crear")."\";");
    output_page($page);
} else {
    eval("\$page = \"".$templates->get("sin_permisos")."\";");
    output_page($page);
}
