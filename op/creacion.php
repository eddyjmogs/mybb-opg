<?php
/**
 * MyBB 1.8
 * Copyright 2014 MyBB Group, All Rights Reserved
 *
 * Website: http://www.mybb.com
 * License: http://www.mybb.com/about/license
 */

define("IN_MYBB", 1);
define('THIS_SCRIPT', 'creacion.php');
require_once "./../global.php";
require "./../inc/config.php";
require_once "./functions/op_functions.php";

global $templates, $mybb;

$uid = $mybb->user['uid'];
$ficha = null;
$ficha_existe = false;
$ficha_aprobada = false;
$time_now = time();
$accion = $mybb->get_input("accion"); 
$reload_js = "<script>window.location.href = window.location.href;</script>";

if ($g_ficha['muerto'] == '1') {
    $mensaje_redireccion = "Estás muerto, no puedes acceder a esta página.";
    eval("\$page = \"".$templates->get("op_redireccion")."\";");
    output_page($page);
    return;
}


$query_ficha = $db->query("SELECT * FROM mybb_op_fichas WHERE fid='$uid'");
while ($f = $db->fetch_array($query_ficha)) { 
    $ficha = $f;
    $ficha_aprobada = $f['aprobada_por'] != 'sin_aprobar';
    $ficha_existe = true;
}

function darObjeto($objeto_id) {
    global $db, $uid;
    $cantidadActual = '0';
    $has_objeto = false;
    $inventario_actual = $db->query("SELECT * FROM mybb_op_inventario WHERE uid='$uid' AND objeto_id='$objeto_id'");
    while ($q = $db->fetch_array($inventario_actual)) {  $has_objeto = true; $cantidadActual = $q['cantidad']; }

    if ($has_objeto) {
        $cantidadNueva = intval($cantidadActual) + 1;
        $db->query(" 
            UPDATE `mybb_op_inventario` SET `cantidad`='$cantidadNueva' WHERE objeto_id='$objeto_id' AND uid='$uid'
        ");
    } else {
        $db->query(" 
            INSERT INTO `mybb_op_inventario` (`objeto_id`, `uid`, `cantidad`) VALUES 
            ('$objeto_id', '$uid', '1');
        ");
    }
}

$nikasUsuario = intval($ficha['nika']);
$puntosOficioUsuario = intval($ficha['puntos_oficio']);
$nivel = intval($ficha['nivel']);

if ($accion == 'cancelar') {
    $hasCreacion = false;
    $query_creacion_usuario = $db->query(" SELECT * FROM mybb_op_creacion_usuarios WHERE uid='$uid' ");
    while ($q = $db->fetch_array($query_creacion_usuario)) { $creacion = $q; $hasCreacion = true; }
    
    if ($hasCreacion) {
        $ticket = $creacion['ticket'];
        $nikas = 10000;
        $puntos_oficio = 0;
        $nombre_ticket = '';

        if ($ticket == 'TTUN001') { $nikas = 3; $dias = 3; $nombre_ticket = 'Ticket de Técnica Única T1'; }
        if ($ticket == 'TTUN002') { $nikas = 5; $dias = 5; $nombre_ticket = 'Ticket de Técnica Única T2'; }
        if ($ticket == 'TTUN003') { $nikas = 10; $dias = 7; $nombre_ticket = 'Ticket de Técnica Única T3'; }
        if ($ticket == 'TTUN004') { $nikas = 15; $dias = 10; $nombre_ticket = 'Ticket de Técnica Única T4'; }
        if ($ticket == 'TTUN005') { $nikas = 20; $dias = 13; $nombre_ticket = 'Ticket de Técnica Única T5'; }
        if ($ticket == 'TTUN006') { $nikas = 25; $dias = 17; $nombre_ticket = 'Ticket de Técnica Única T6'; }
        if ($ticket == 'TTUN007') { $nikas = 30; $dias = 22; $nombre_ticket = 'Ticket de Técnica Única T7'; }
        if ($ticket == 'TTUN008') { $nikas = 40; $dias = 28; $nombre_ticket = 'Ticket de Técnica Única T8'; }
        if ($ticket == 'TTUN009') { $nikas = 50; $dias = 36; $nombre_ticket = 'Ticket de Técnica Única T9'; }
        if ($ticket == 'TTUN010') { $nikas = 60; $dias = 45; $nombre_ticket = 'Ticket de Técnica Única T10'; }

        if ($ticket == 'TMJT001') { $nikas = 3; $dias = 2; $nombre_ticket = 'Ticket de Mejora de Técnica T1'; }
        if ($ticket == 'TMJT002') { $nikas = 6; $dias = 4; $nombre_ticket = 'Ticket de Mejora de Técnica T2'; }
        if ($ticket == 'TMJT003') { $nikas = 9; $dias = 6; $nombre_ticket = 'Ticket de Mejora de Técnica T3'; }
        if ($ticket == 'TMJT004') { $nikas = 12; $dias = 8; $nombre_ticket = 'Ticket de Mejora de Técnica T4'; }
        if ($ticket == 'TMJT005') { $nikas = 15; $dias = 10; $nombre_ticket = 'Ticket de Mejora de Técnica T5'; }
        if ($ticket == 'TMJT006') { $nikas = 18; $dias = 12; $nombre_ticket = 'Ticket de Mejora de Técnica T6'; }
        if ($ticket == 'TMJT007') { $nikas = 21; $dias = 14; $nombre_ticket = 'Ticket de Mejora de Técnica T7'; }
        if ($ticket == 'TMJT008') { $nikas = 24; $dias = 16; $nombre_ticket = 'Ticket de Mejora de Técnica T8'; }
        if ($ticket == 'TMJT009') { $nikas = 27; $dias = 18; $nombre_ticket = 'Ticket de Mejora de Técnica T9'; }
        if ($ticket == 'TMJT010') { $nikas = 30; $dias = 20; $nombre_ticket = 'Ticket de Mejora de Técnica T10'; }
    
        if ($ticket == 'NTC001') { $nikas = 4; $dias = 4; $puntos_oficio = 250; $nombre_ticket = 'Notas de Crafteo T1'; }
        if ($ticket == 'NTC002') { $nikas = 7; $dias = 7; $puntos_oficio = 500; $nombre_ticket = 'Notas de Crafteo T2'; }
        if ($ticket == 'NTC003') { $nikas = 14; $dias = 11; $puntos_oficio = 1000; $nombre_ticket = 'Notas de Crafteo T3'; }
        if ($ticket == 'NTC004') { $nikas = 25; $dias = 16; $puntos_oficio = 1500; $nombre_ticket = 'Notas de Crafteo T4'; }
        if ($ticket == 'NTC005') { $nikas = 35; $dias = 21; $puntos_oficio = 2000; $nombre_ticket = 'Notas de Crafteo T5'; }

        if ($ticket == 'RTO001') { $nikas = 5; $dias = 5; $puntos_oficio = 250; $nombre_ticket = 'Manual de Crafteo T1'; }
        if ($ticket == 'RTO002') { $nikas = 10; $dias = 10; $puntos_oficio = 500; $nombre_ticket = 'Manual de Crafteo T2'; }
        if ($ticket == 'RTO003') { $nikas = 20; $dias = 15; $puntos_oficio = 1000; $nombre_ticket = 'Manual de Crafteo T3'; }
        if ($ticket == 'RTO004') { $nikas = 35; $dias = 22; $puntos_oficio = 1500; $nombre_ticket = 'Manual de Crafteo T4'; }
        if ($ticket == 'RTO005') { $nikas = 50; $dias = 30; $puntos_oficio = 2000; $nombre_ticket = 'Manual de Crafteo T5'; }

        $nikasNuevo = $nikasUsuario + $nikas;
        $puntosOficioNuevo = $puntosOficioUsuario + $puntos_oficio;

        if ($puntos_oficio == 0) {
            $db->query(" UPDATE `mybb_op_fichas` SET `nika`='$nikasNuevo' WHERE `fid`='$uid'; ");
            $log = "Creación de $nombre_ticket cancelado :( ...\nTienes ahora $nikasNuevo nikas. ($nikasUsuario + $nikas)";
        } else {
            $db->query(" UPDATE `mybb_op_fichas` SET `nika`='$nikasNuevo',`puntos_oficio`='$puntosOficioNuevo' WHERE `fid`='$uid'; ");
            $log = "Creación de $nombre_ticket cancelada :( ...\nTienes ahora $nikasNuevo nikas ($nikasUsuario + $nikas) y $puntosOficioNuevo puntos de oficio ($puntosOficioUsuario + $puntos_oficio).";
        }

        $db->query(" DELETE FROM mybb_op_creacion_usuarios WHERE uid='$uid' ");

        echo("<script>alert(`$log`); window.location.href = 'https://onepiecegaiden.com/op/creacion.php';</script>");
    }
}

if ($accion == 'crear') {
    $ticket = $mybb->get_input("ticket");
    $nikas = 10000;
    $dias = 10000;
    $puntos_oficio = 0;
    $nivelTicket = 21;

    if ($ticket == 'TTUN001') { $nikas = 3; $dias = 3; $nivelTicket = 1; $nombre_ticket = 'Ticket de Técnica Única T1'; }
    if ($ticket == 'TTUN002') { $nikas = 5; $dias = 5; $nivelTicket = 3; $nombre_ticket = 'Ticket de Técnica Única T2'; }
    if ($ticket == 'TTUN003') { $nikas = 10; $dias = 7; $nivelTicket = 5; $nombre_ticket = 'Ticket de Técnica Única T3'; }
    if ($ticket == 'TTUN004') { $nikas = 15; $dias = 10; $nivelTicket = 7; $nombre_ticket = 'Ticket de Técnica Única T4'; }
    if ($ticket == 'TTUN005') { $nikas = 20; $dias = 13; $nivelTicket = 9; $nombre_ticket = 'Ticket de Técnica Única T5'; }
    if ($ticket == 'TTUN006') { $nikas = 25; $dias = 17; $nivelTicket = 11; $nombre_ticket = 'Ticket de Técnica Única T6'; }
    if ($ticket == 'TTUN007') { $nikas = 30; $dias = 22; $nivelTicket = 13; $nombre_ticket = 'Ticket de Técnica Única T7'; }
    if ($ticket == 'TTUN008') { $nikas = 40; $dias = 28; $nivelTicket = 15; $nombre_ticket = 'Ticket de Técnica Única T8'; }
    if ($ticket == 'TTUN009') { $nikas = 50; $dias = 36; $nivelTicket = 17; $nombre_ticket = 'Ticket de Técnica Única T9'; }
    if ($ticket == 'TTUN010') { $nikas = 60; $dias = 45; $nivelTicket = 19; $nombre_ticket = 'Ticket de Técnica Única T10'; }

    if ($ticket == 'TMJT001') { $nikas = 3; $dias = 2; $nivelTicket = 1; $nombre_ticket = 'Ticket de Mejora de Técnica T1'; }
    if ($ticket == 'TMJT002') { $nikas = 6; $dias = 4; $nivelTicket = 3; $nombre_ticket = 'Ticket de Mejora de Técnica T2'; }
    if ($ticket == 'TMJT003') { $nikas = 9; $dias = 6; $nivelTicket = 5; $nombre_ticket = 'Ticket de Mejora de Técnica T3'; }
    if ($ticket == 'TMJT004') { $nikas = 12; $dias = 8; $nivelTicket = 7; $nombre_ticket = 'Ticket de Mejora de Técnica T4'; }
    if ($ticket == 'TMJT005') { $nikas = 15; $dias = 10; $nivelTicket = 9; $nombre_ticket = 'Ticket de Mejora de Técnica T5'; }
    if ($ticket == 'TMJT006') { $nikas = 18; $dias = 12; $nivelTicket = 11; $nombre_ticket = 'Ticket de Mejora de Técnica T6'; }
    if ($ticket == 'TMJT007') { $nikas = 21; $dias = 14; $nivelTicket = 13; $nombre_ticket = 'Ticket de Mejora de Técnica T7'; }
    if ($ticket == 'TMJT008') { $nikas = 24; $dias = 16; $nivelTicket = 15; $nombre_ticket = 'Ticket de Mejora de Técnica T8'; }
    if ($ticket == 'TMJT009') { $nikas = 27; $dias = 18; $nivelTicket = 17; $nombre_ticket = 'Ticket de Mejora de Técnica T9'; }
    if ($ticket == 'TMJT010') { $nikas = 30; $dias = 20; $nivelTicket = 19; $nombre_ticket = 'Ticket de Mejora de Técnica T10'; }

    if ($ticket == 'NTC001') { $nikas = 4; $dias = 4; $puntos_oficio = 250; $nivelTicket = 1; $nombre_ticket = 'Notas de Crafteo T1'; }
    if ($ticket == 'NTC002') { $nikas = 7; $dias = 7; $puntos_oficio = 500; $nivelTicket = 4; $nombre_ticket = 'Notas de Crafteo T2'; }
    if ($ticket == 'NTC003') { $nikas = 14; $dias = 11; $puntos_oficio = 1000; $nivelTicket = 8; $nombre_ticket = 'Notas de Crafteo T3'; }
    if ($ticket == 'NTC004') { $nikas = 25; $dias = 16; $puntos_oficio = 1500; $nivelTicket = 12; $nombre_ticket = 'Notas de Crafteo T4'; }
    if ($ticket == 'NTC005') { $nikas = 35; $dias = 22; $puntos_oficio = 2000; $nivelTicket = 16; $nombre_ticket = 'Notas de Crafteo T5'; }

    if ($ticket == 'RTO001') { $nikas = 5; $dias = 5; $puntos_oficio = 250; $nivelTicket = 1; $nombre_ticket = 'Manual de Crafteo T1'; }
    if ($ticket == 'RTO002') { $nikas = 10; $dias = 10; $puntos_oficio = 500; $nivelTicket = 4; $nombre_ticket = 'Manual de Crafteo T2'; }
    if ($ticket == 'RTO003') { $nikas = 20; $dias = 15; $puntos_oficio = 1000; $nivelTicket = 8; $nombre_ticket = 'Manual de Crafteo T3'; }
    if ($ticket == 'RTO004') { $nikas = 35; $dias = 22; $puntos_oficio = 1500; $nivelTicket = 12; $nombre_ticket = 'Manual de Crafteo T4'; }
    if ($ticket == 'RTO005') { $nikas = 50; $dias = 30; $puntos_oficio = 2000; $nivelTicket = 16; $nombre_ticket = 'Manual de Crafteo T5'; }


    if ($nikasUsuario >= $nikas && $puntosOficioUsuario >= $puntos_oficio && $nivel >= $nivelTicket) {
        $duracion = $dias * 3600 * 24;
        $timestamp_end = time() + intval($duracion);
        $nikasNuevo = $nikasUsuario - $nikas;
        $puntosOficioNuevo = $puntosOficioUsuario - $puntos_oficio;
        $db->query(" INSERT INTO `mybb_op_creacion_usuarios` (`uid`, `nombre_ticket`, `ticket`, `timestamp_end`, `duracion`, `nikas_costo`) VALUES ('$uid','$nombre_ticket', '$ticket','$timestamp_end','$duracion','$nikas'); ");
        
        if ($puntos_oficio == 0) {
            $db->query(" UPDATE `mybb_op_fichas` SET `nika`='$nikasNuevo' WHERE `fid`='$uid'; ");
            $log = "¡Creación en proceso. Se procesará el $nombre_ticket ($ticket)\nTienes ahora $nikasNuevo nikas. ($nikasUsuario - $nikas)";
        } else {
            $db->query(" UPDATE `mybb_op_fichas` SET `nika`='$nikasNuevo',`puntos_oficio`='$puntosOficioNuevo' WHERE `fid`='$uid'; ");
            $log = "Receta en proceso. Se procesará la $nombre_ticket ($ticket)!\nTienes ahora $nikasNuevo nikas ($nikasUsuario - $nikas) y $puntosOficioNuevo puntos de oficio ($puntosOficioUsuario - $puntos_oficio).";
        }

        $db->query(" INSERT INTO `mybb_op_audit_creacion` (`uid`, `nombre`, `log`) VALUES ('$uid', '$nombre', '$log'); ");
        echo("<script>alert(`$log`);window.location.href = 'https://onepiecegaiden.com/op/creacion.php';</script>");
    }
}

if ($accion == 'reclamar') {
    $hasCreacion = false;
    $creacion = null;
    $query_creacion_usuario = $db->query(" SELECT * FROM mybb_op_creacion_usuarios WHERE uid='$uid' ");
    while ($q = $db->fetch_array($query_creacion_usuario)) { $creacion = $q; $hasCreacion = true; }

    if ($hasCreacion && intval(time()) > intval($creacion['timestamp_end'])) {
        $ticket = $creacion['ticket'];
        $nombre_ticket = $creacion['nombre_ticket'];

        $db->query("DELETE FROM mybb_op_creacion_usuarios WHERE uid='$uid'");
        darObjeto($ticket);
    
        $log = "¡$nombre_ticket ($ticket) reclamado, felicidades!";
        echo("<script>alert('$log');window.location.href = 'https://onepiecegaiden.com/op/creacion.php';</script>");
        
    }
}

if ($ficha_existe == true && $ficha_aprobada == true) {
    
    $query_creacion_usuario2 = $db->query(" SELECT * FROM mybb_op_creacion_usuarios WHERE uid='$uid' ");

    $en_curso = false;
    $completo = false;
    $tiempo_left = 0;

    while ($m = $db->fetch_array($query_creacion_usuario2)) {
        $timestamp_end = intval($m['timestamp_end']);
        $tiempo_left = (($timestamp_end)) * 1000; 
        $duracion = $m['duracion'];
        $ticket = $m['ticket'];
        $nombre_ticket = $m['nombre_ticket'];

        if (time() > ($timestamp_end)) {
            $completo = true;
        } else {
            $en_curso = true;
        }
    }

    if ($en_curso) {
        eval("\$page = \"".$templates->get("op_creacion_en_curso")."\";");
        output_page($page);
    } else {     
        if ($completo) {
            eval("\$page = \"".$templates->get("op_creacion_completo")."\";");
        } else {            
            eval("\$page = \"".$templates->get("op_creacion")."\";");
        }
        output_page($page);
    }

} else {
    $mensaje_redireccion = "Para acceder a esta página debes tener tu ficha aprobada.";
    eval("\$page = \"".$templates->get("op_redireccion")."\";");
    output_page($page);
}

