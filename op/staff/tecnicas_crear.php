<?php
/**
 * MyBB 1.8
 * Copyright 2014 MyBB Group, All Rights Reserved
 *
 * Website: http://www.mybb.com
 * License: http://www.mybb.com/about/license
 */

define("IN_MYBB", 1);
define('THIS_SCRIPT', 'crear_tecnicas.lphp');
require_once "./../../global.php";
require "./../../inc/config.php";
require_once "./../functions/op_functions.php";

global $templates, $mybb;
$uid = $mybb->user['uid'];
$username = $mybb->user['username'];
$user_accion = $mybb->get_input('accion'); 
$tecnica_id = $mybb->get_input('tecnica_id'); 

$tecnica_id_post = $_POST["tecnica_id"];
$accion_post = $_POST["accion"];

$nombre = addslashes(trim($_POST["nombre"]));
$tid = trim($_POST["tid"]);
$estilo = trim($_POST["estilo"]);
$aldea = trim($_POST["aldea"]);
$categoria = $_POST["categoria"];
$sellos = $_POST["sellos"];
$rango = trim($_POST["rango"]);
$requisitos = $_POST["requisitos"];
$dano = addslashes($_POST["dano"]);
$coste = addslashes($_POST["coste"]);
$clase = addslashes($_POST["clase"]);
$enfriamiento = addslashes($_POST["enfriamiento"]);
$rango = addslashes($_POST["rango"]);
$descripcion = addslashes($_POST["descripcion"]);

// $staff = $_POST["staff"];
$staff = $uid;
$razon = $_POST["razon"];

$reload_js = "<script>window.location.href = window.location.pathname;</script>";

if ($accion_post == 'Agregar' && $nombre && $tid && $estilo && $descripcion && $staff && (is_mod($uid) || is_staff($uid))) {
    $log = "Nueva técnica creada de técnica ID $tid ($nombre). \nLa nueva técnica posee: \ntid=$tid,\nnombre=$nombre,\nestilo=$estilo,\nrango=$rango,\nrequisitos=$requisitos,\dano=$dano,\enfriamiento=$enfriamiento,\ncoste=$coste,\clase=$clase\ndescripcion=$descripcion";

    $db->query(" 
        INSERT INTO `mybb_op_tecnicas` (`tid`, `nombre`, `estilo`, `rango`, `dano`, `coste`, `clase`, `enfriamiento`, `requisitos`, `descripcion`) VALUES 
        ('$tid','$nombre','$estilo','$rango','$dano','$coste','$clase','$enfriamiento','$requisitos','$descripcion');
    ");

    $db->query(" 
        INSERT INTO `mybb_op_audit_consola_mod` (`staff`, `username`, `razon`, `log`) VALUES 
        ('$staff', '$username', '$razon', '$log');
    ");


    eval('$log_var = $log;');
    eval('$reload_script = $reload_js;'); 
}

if ($accion_post == 'Remover' && $tecnica_id_post && $nombre && $staff && $razon && (is_mod($uid) || is_staff($uid))) {
    $log = "Remover técnica ID $tecnica_id_post ($nombre). Nuevo ID de la técnica: BORR$tecnica_id_post. estilo: borrada.";
    
    $db->query(" 
        UPDATE `mybb_op_tecnicas` SET `tid`='BORR$tecnica_id_post', `estilo`='borrada' WHERE `tid`='$tecnica_id_post';
    ");

    $db->query(" 
        INSERT INTO `mybb_op_audit_consola_mod` (`staff`, `username`, `razon`, `log`) VALUES 
        ('$staff', '$username', '$razon', '$log');
    ");

    eval('$log_var = $log;');
    eval('$reload_script = $reload_js;'); 
}

if ($user_accion && $tecnica_id) {
    $query_tecnicas = $db->query("
        SELECT * FROM `mybb_op_tecnicas` WHERE tid='$tecnica_id'
    ");
    while ($t = $db->fetch_array($query_tecnicas)) {
        eval('$tecnica = $t;');
    }
}

if (is_mod($uid) || is_staff($uid)) { 
    eval('$accion = $user_accion;');
    eval('$tid = $tecnica_id;');
    eval("\$page = \"".$templates->get("staff_crear_tecnicas")."\";");
    output_page($page);
} else {
    eval("\$page = \"".$templates->get("sin_permisos")."\";");
    output_page($page);
}
