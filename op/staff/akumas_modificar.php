<?php
/**
 * MyBB 1.8
 * Copyright 2014 MyBB Group, All Rights Reserved
 *
 * Website: http://www.mybb.com
 * License: http://www.mybb.com/about/license
 */

define("IN_MYBB", 1);
define('THIS_SCRIPT', 'akumas_modificar.php');
require_once "./../../global.php";
require "./../../inc/config.php";
require_once "./../functions/op_functions.php";

global $templates, $mybb;
$uid = $mybb->user['uid'];
$username = $mybb->user['username'];
$akuma_id = $mybb->get_input('akuma_id'); 

$akuma_id_post = trim($_POST["akuma_id"]);
$nuevo_akuma_id = trim($_POST["nuevo_akuma_id"]);
$nombre = trim($_POST["nombre"]);
$subnombre = trim($_POST["subnombre"]);
$tier = trim($_POST["tier"]);
$categoria = trim($_POST["categoria"]);
$ocupada = $_POST["ocupada"];
$es_oculta = $_POST["es_oculta"];
$es_npc = $_POST["es_npc"];
$uid_post = $_POST["uid"];
$imagen = trim($_POST["imagen"]);
$descripcion = addslashes($_POST["descripcion"]);
$detalles = addslashes($_POST["detalles"]);

$dominio1 = addslashes($_POST["dominio1"]);
$dominio2 = addslashes($_POST["dominio2"]);
$dominio3 = addslashes($_POST["dominio3"]);
$pasiva1 = addslashes($_POST["pasiva1"]);
$pasiva2 = addslashes($_POST["pasiva2"]);
$pasiva3 = addslashes($_POST["pasiva3"]);
$reservas = addslashes($_POST["reservas"]);
$reservasFecha = addslashes($_POST["reservasFecha"]);

$reload_js = "<script>window.location.href = window.location.pathname;</script>";
$akuma = null;

if ($akuma_id) {
    $query_akumas = $db->query("
        SELECT * FROM `mybb_op_akumas` WHERE akuma_id='$akuma_id'
    ");
    while ($t = $db->fetch_array($query_akumas)) {
        $akuma = $t;
    }
}

if ($akuma_id_post && $nuevo_akuma_id && $nombre && $tier && $categoria && $descripcion && (is_mod($uid) || is_staff($uid))) {
    $log = "Cambio a akuma ID $akuma_id ($nombre).";

    $db->query(" 
        UPDATE `mybb_op_akumas` SET `akuma_id`='$nuevo_akuma_id',`nombre`='$nombre',`subnombre`='$subnombre',`categoria`='$categoria',`tier`='$tier',
            `descripcion`='$descripcion', `ocupada`='$ocupada', `imagen`='$imagen', `uid`='$uid_post', `es_npc`='$es_npc', `es_oculta`='$es_oculta', 
            `detalles`='$detalles',
            `pasiva1`='$pasiva1',`pasiva2`='$pasiva2',`pasiva3`='$pasiva3',`dominio1`='$dominio1',`dominio2`='$dominio2',`dominio3`='$dominio3', 
            `reservas`='$reservas', `reservasFecha`='$reservasFecha' WHERE `akuma_id`='$akuma_id_post';
    ");

    // $db->query(" 
    //     INSERT INTO `mybb_op_audit_consola_mod` (`staff`, `username`, `razon`, `log`) VALUES 
    //     ('$staff', '$username', '$razon', '$log');
    // ");

    eval('$log_var = $log;');
    eval('$reload_script = $reload_js;');
}

if (is_mod($uid) || is_staff($uid)) { 
    eval("\$page = \"".$templates->get("staff_akumas_modificar")."\";");
    output_page($page);
} else {
    eval("\$page = \"".$templates->get("sin_permisos")."\";");
    output_page($page);
}
