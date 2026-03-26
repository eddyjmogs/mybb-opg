<?php
/**
 * MyBB 1.8
 * Copyright 2014 MyBB Group, All Rights Reserved
 *
 * Website: http://www.mybb.com
 * License: http://www.mybb.com/about/license
 */

define("IN_MYBB", 1);
define('THIS_SCRIPT', 'objetos_crear.php');
require_once "./../../global.php";
require "./../../inc/config.php";
require_once "./../functions/op_functions.php";

global $templates, $mybb;
$uid = $mybb->user['uid'];
$username = $mybb->user['username'];
$user_accion = $mybb->get_input('accion'); 
$objeto_id_input = $mybb->get_input('objeto_id'); 
$accion_post = $_POST["accion"];

$objeto_id = trim($_POST["objeto_id"]);
$nombre = trim($_POST["nombre"]);
$categoria = $_POST["categoria"];
$subcategoria = $_POST["subcategoria"];
$tier = $_POST["tier"];
$berries = $_POST["berries"];
$berriesCrafteo = $_POST["berriesCrafteo"];
$cantidadMaxima = $_POST["cantidadMaxima"];

$dano = $_POST["dano"];
$exclusivo = $_POST["exclusivo"];

$espacios = $_POST["espacios"];

$requisitos = $_POST["requisitos"];
$escalado = $_POST["escalado"];

$imagen = $_POST["imagen"];
$descripcion = addslashes($_POST["descripcion"]);

$comerciable = 0;

// $staff = $_POST["staff"];
$staff = $uid;
$razon = $_POST["razon"];
$objeto = null;

$reload_js = "<script>window.location.href = window.location.pathname;</script>";

if ($accion_post == 'Agregar' && $nombre && $objeto_id && $categoria && $descripcion && $staff && (is_mod($uid) || is_staff($uid))) {
    $log = "Nuevo objeto creado de objeto ID $objeto_id ($nombre). \nEl nuevo objeto posee: \noid=$objeto_id,\nnombre=$nombre,\ncategoria=$categoria,\ncalidad=$calidad,\ndano=$dano,\nberries=$berries,\ncantidadMaxima=$cantidadMaxima,\nunidades=$unidades,\nimagen=$imagen,\ndescripcion=$descripcion";

    $db->query(" 
        INSERT INTO `mybb_op_objetos` (`objeto_id`, `nombre`, `categoria`, `subcategoria`, `tier`, `berries`, `berriesCrafteo`, `cantidadMaxima`, `dano`, 
            `exclusivo`, `espacios`, `requisitos`, `escalado`, `imagen`, `descripcion`) VALUES 
        ('$objeto_id','$nombre','$categoria','$subcategoria','$tier','$berries','$berriesCrafteo','$cantidadMaxima','$dano','$exclusivo','$espacios','$requisitos','$escalado','$imagen','$descripcion');
    ");

    $db->query(" 
        INSERT INTO `mybb_op_audit_consola_mod` (`staff`, `username`, `razon`, `log`) VALUES 
        ('$staff', '$username', '$razon', '$log');
    ");

    eval('$log_var = $log;');
    eval('$reload_script = $reload_js;'); 
}

if ($accion_post == 'Remover' && $objeto_id && $nombre && $staff && $razon && (is_mod($uid) || is_staff($uid))) {
    $log = "Remover técnica ID $objeto_id ($nombre). Nuevo ID de la técnica: BORR$objeto_id. Tipo: borrada.";
    
    $db->query(" 
        UPDATE `mybb_op_objetos` SET `objeto_id`='BORR$objeto_id', `categoria`='borrada' WHERE `objeto_id`='$objeto_id';
    ");

    $db->query(" 
        INSERT INTO `mybb_op_audit_consola_mod` (`staff`, `username`, `razon`, `log`) VALUES 
        ('$staff', '$username', '$razon', '$log');
    ");

    eval('$log_var = $log;');
    eval('$reload_script = $reload_js;'); 
}

if ($user_accion && $objeto_id_input) {
    $query_objetos = $db->query("
        SELECT * FROM `mybb_op_objetos` WHERE objeto_id='$objeto_id_input'
    ");
    while ($q = $db->fetch_array($query_objetos)) {
        $objeto = $q;
    }
}

if (is_mod($uid) || is_staff($uid)) { 
    eval('$accion = $user_accion;');
    eval('$oid = $objeto_id;');
    eval("\$page = \"".$templates->get("staff_objetos_crear")."\";");
    output_page($page);
} else {
    $redireccion = "No tienes permisos para ver esta página.";
    eval("\$page = \"".$templates->get("op_redireccion")."\";");
    output_page($page);
}
