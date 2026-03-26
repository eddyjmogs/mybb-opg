<?php
/**
 * MyBB 1.8
 * Copyright 2014 MyBB Group, All Rights Reserved
 *
 * Website: http://www.mybb.com
 * License: http://www.mybb.com/about/license
 */

define("IN_MYBB", 1);
define('THIS_SCRIPT', 'peticiones_usuario.php');
require_once "./../global.php";
require "./../inc/config.php";
require_once "./functions/op_functions.php";

global $templates, $mybb;
$uid = $mybb->user['uid'];
$username = $mybb->user['username'];
$user_id = $mybb->get_input('user_id');

$reload_js = "<script>window.location.href = window.location.pathname;</script>";

$peticiones_li = "";

$url_staff = "";

if ($user_id != "" && is_staff($uid)) {
    $uid = $user_id;
}

if ($user_id == "messi" && is_staff($uid)) {
    $uid = 'messi';
}

function print_peticion($uid) {
    global $db;

    if ($uid == 'messi') {
        $query_peticion = $db->query("
            SELECT * FROM mybb_op_peticiones
            ORDER BY enviado DESC
            LIMIT 100;
        ");
    } else {
        $query_peticion = $db->query("
            SELECT * FROM mybb_op_peticiones
            WHERE uid='$uid'
            ORDER BY enviado DESC
            LIMIT 100;
        ");
    }



    $peticiones_li = "";
    while ($q = $db->fetch_array($query_peticion)) {
        $pid = $q['id'];
        $categoria = $q['categoria'];
        $categoria2 = "";
        $resumen = $q['resumen'];
        $descripcion =  nl2br($q['descripcion']);
        $url = $q['url'];
        $nombre = $q['nombre'];
        $u_uid = $q['uid'];

        $enviado = $q['enviado'];
        $categoria2 = "";
        $givenDate = new DateTime($enviado);
        $currentDate = new DateTime();
        $interval = $currentDate->diff($givenDate);
        $fecha = "Hace " . $interval->days . " días.";

        if ($categoria == 'ficha') { $categoria2 = "Ajustes de Ficha y Recursos"; }
        if ($categoria == 'tema') { $categoria2 = "Petición de Narración"; }
        if ($categoria == 'combate') { $categoria2 = "Moderación de Combate"; }
        if ($categoria == 'tecnica') { $categoria2 = "Técnicas, Akumas y Estilos"; }
        if ($categoria == 'programacion') { $categoria2 = "Errores de Programación"; }

        
        $peticiones_li .= "<li>";
        $peticiones_li .= "<strong>Categoría</strong>: $categoria2 <br> <strong>Resumen</strong>: $resumen <br> <strong>Descripción</strong>: $descripcion <br> <strong>URL</strong>: <a target='_blank' href='$url'>$url</a><br> <strong>Fecha</strong>: $enviado - $fecha <br>";

        $peticiones_li .= "</li><br>";
    
    }

    return "$peticiones_li <br>";
}


if ($uid != "0") {
    $peticiones_li .= print_peticion($uid);

    eval("\$page = \"".$templates->get("staff_peticiones_mod")."\";");
    output_page($page);
} else {
    $mensaje_redireccion = "No tienes acceso para entrar a esta página. ¿Seguro no te perdiste?";
    eval("\$page = \"".$templates->get("op_redireccion")."\";");
    output_page($page);
}