<?php
/**
 * MyBB 1.8
 * Copyright 2014 MyBB Group, All Rights Reserved
 *
 * Website: http://www.mybb.com
 * License: http://www.mybb.com/about/license
 */

define("IN_MYBB", 1);
define('THIS_SCRIPT', 'modificar_objetos.php');
require_once "./../../global.php";
require "./../../inc/config.php";
require_once "./../functions/op_functions.php";

global $templates, $mybb;
$uid = $mybb->user['uid'];
$username = $mybb->user['username'];
$objeto_id = $mybb->get_input('objeto_id'); 

$objeto_id_old = trim($_POST["objeto_id_old"]);
$objeto_id_post = trim($_POST["objeto_id"]);

$nombre = trim($_POST["nombre"]);
$categoria = $_POST["categoria"];
$subcategoria = $_POST["subcategoria"];
$tier = $_POST["tier"];
$imagen_id = $_POST["imagen_id"];
$imagen_avatar = $_POST["imagen_avatar"];

$berries = $_POST["berries"];
$berriesCrafteo = $_POST["berriesCrafteo"];
$cantidadMaxima = $_POST["cantidadMaxima"];

$dano = $_POST["dano"];
$bloqueo = $_POST["bloqueo"];
$efecto = $_POST["efecto"];
$exclusivo = $_POST["exclusivo"];
$invisible = $_POST["invisible"];
$espacios = $_POST["espacios"];

$crafteo_usuarios = $_POST["crafteo_usuarios"];

$desbloquear = $_POST["desbloquear"];
$oficio = $_POST["oficio"];
$tiempo_creacion = $_POST["tiempo_creacion"];
$nivel = $_POST["nivel"];

$requisitos = $_POST["requisitos"];
$escalado = $_POST["escalado"];
$editable = $_POST["editable"];
$comerciable = $_POST["comerciable"];

$alcance = $_POST["alcance"];
$negro = $_POST["negro"];
$negro_berries = $_POST["negro_berries"];
$fusion_tipo = $_POST["fusion_tipo"];
$engastes = $_POST["engastes"];

$imagen = $_POST["imagen"];
$descripcion = addslashes($_POST["descripcion"]);

// $staff = $_POST["staff"];
// $razon = $_POST["razon"];
$staff = "$uid";
$razon = 'Apertura';

$reload_js = "<script>window.location.href = window.location.pathname;</script>";

$existe = false;
if ($objeto_id_post) {
    $query_objetos = $db->query("
        SELECT * FROM `mybb_op_objetos` WHERE objeto_id='$objeto_id_post'
    ");
    while ($t = $db->fetch_array($query_objetos)) {
        // $tecnica = $t;
        $existe = true;
    }
}


if ($objeto_id_post && $nombre && $categoria && $descripcion && $staff && $razon && (is_mod($uid) || is_staff($uid))) {
    $log = "Cambio a objeto ID $objeto_id_old ($nombre). categoria=$categoria,subcategoria=$subcategoria,tier=$tier,imagen_id=$imagen_id, berries=$berries,cantidadMaxima=$cantidadMaxima,dano=$dano,efecto=$efecto,exclusivo=$exclusivo,invisible=$invisible,espacios=$espacios, desbloquear=$desbloquear,oficio=$oficio,nivel=$nivel,imagen_avatar=$imagen_avatar, requisitos=$requisitos,escalado=$escalado,imagen=$imagen,descripcion=$descripcion,editable=$editable,comerciable=$comerciable";
    // $log = "Cambio a objeto ID $objeto_id_old ($nombre). objeto_id=$objeto_id_post, nombre=$nombre";

    if ($objeto_id_post != $objeto_id_old) {
        $db->query(" 
            UPDATE `mybb_op_inventario` SET `objeto_id`='$objeto_id_post' WHERE objeto_id='$objeto_id_old'
        ");
    }
    
    if ($existe) {
        $db->query(" 
            UPDATE `mybb_op_objetos` SET `objeto_id`='$objeto_id_post', `nombre`='$nombre',`categoria`='$categoria',`subcategoria`='$subcategoria',`tier`='$tier',`imagen_id`='$imagen_id',
                `berries`='$berries',`berriesCrafteo`='$berriesCrafteo',`cantidadMaxima`='$cantidadMaxima',`dano`='$dano',`efecto`='$efecto',`exclusivo`='$exclusivo',`invisible`='$invisible',`espacios`='$espacios',
                `desbloquear`='$desbloquear',`oficio`='$oficio',`nivel`='$nivel',`imagen_avatar`='$imagen_avatar',`crafteo_usuarios`='$crafteo_usuarios',
                `alcance`='$alcance',`negro`='$negro',`negro_berries`='$negro_berries',`fusion_tipo`='$fusion_tipo',`engastes`='$engastes',
                `requisitos`='$requisitos',`escalado`='$escalado',`imagen`='$imagen',`descripcion`='$descripcion',`editable`='$editable',`comerciable`='$comerciable',`tiempo_creacion`='$tiempo_creacion',`bloqueo`='$bloqueo' WHERE `objeto_id`='$objeto_id_old';
        ");
    } else {

        $db->query("
            INSERT INTO `mybb_op_objetos` (`objeto_id`, `nombre`, `categoria`, `subcategoria`, `tier`, `imagen_id`,
                `berries`, `berriesCrafteo`, `cantidadMaxima`, `dano`, `efecto`,`exclusivo`, `invisible`, `espacios`, 
                `desbloquear`,`oficio`,`nivel`,`imagen_avatar`,`crafteo_usuarios`,
                `requisitos`, `escalado`, `imagen`, `descripcion`,`editable`,`comerciable`,`bloqueo`,
                `alcance`, `negro`, `negro_berries`, `fusion_tipo`, `engastes`,
                `tiempo_creacion`) VALUES 
            ('$objeto_id','$nombre','$categoria','$subcategoria','$tier','$imagen_id',
                '$berries','$berriesCrafteo','$cantidadMaxima','$dano','$efecto','$exclusivo','$invisible','$espacios',
                '$desbloquear','$oficio','$nivel','$imagen_avatar','$crafteo_usuarios',
                '$requisitos','$escalado','$imagen','$descripcion','$editable','$comerciable','$bloqueo',
                '$alcance','$negro','$negro_berries','$fusion_tipo','$engastes','$tiempo_creacion');
        ");
    }


    $db->query(" 
        INSERT INTO `mybb_op_audit_consola_mod` (`staff`, `username`, `razon`, `log`) VALUES 
        ('$staff', '$username', '$razon', '$log');
    ");

    eval('$log_var = $log;');
    eval('$reload_script = $reload_js;');
}

if ($objeto_id) {
    $objeto = null;
    $query_objetos = $db->query("
        SELECT * FROM `mybb_op_objetos` WHERE objeto_id='$objeto_id'
    ");
    while ($q = $db->fetch_array($query_objetos)) {
        $objeto = $q;
    }
}

if (is_mod($uid) || is_staff($uid)) { 
    eval("\$page = \"".$templates->get("staff_objetos_modificar")."\";");
    output_page($page);
} else {
    $redireccion = "No tienes permisos para ver esta página.";
    eval("\$page = \"".$templates->get("op_redireccion")."\";");
    output_page($page);
}
