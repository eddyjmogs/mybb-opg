<?php
/**
 * MyBB 1.8
 * Copyright 2014 MyBB Group, All Rights Reserved
 *
 * Website: http://www.mybb.com
 * License: http://www.mybb.com/about/license
 */

define("IN_MYBB", 1);
define('THIS_SCRIPT', 'entregar_recompensas_narradores.php');
require_once "./../../global.php";
require "./../../inc/config.php";
require_once "./../functions/op_functions.php";

global $templates, $mybb;
$uid = $mybb->user['uid'];
$username = $mybb->user['username'];
$user_fid = $mybb->get_input('fid'); 
$user_accion = $mybb->get_input('accion');   

$ficha_id = $_POST["ficha_id"];
$accion = $_POST["accion"];
$tid = $_POST["tid"];
$usuarios = $_POST["usuarios"];
$nivel_narrador = $_POST["nivel_narrador"];

$reload_js = "<script>window.location.href = window.location.pathname;</script>";

$ficha = null;
$users = null;

if ($user_fid != '') {
    $query_ficha = $db->query(" SELECT * FROM mybb_op_fichas WHERE fid='$user_fid' ");
    while ($q = $db->fetch_array($query_ficha)) { $ficha = $q; }

    $query_users = $db->query(" SELECT * FROM mybb_users WHERE uid='$user_fid' ");
    while ($q = $db->fetch_array($query_users)) { $users = $q; }
}

if ($accion != '') {
    $query_ficha = $db->query(" SELECT * FROM mybb_op_fichas WHERE fid='$ficha_id' ");
    while ($q = $db->fetch_array($query_ficha)) { $ficha = $q; }

    $query_users = $db->query(" SELECT * FROM mybb_users WHERE uid='$ficha_id' ");
    while ($q = $db->fetch_array($query_users)) { $users = $q; }


    $nombre = $ficha['nombre'];

    $nikas = intval($ficha['nika']);
    $newpoints = floatval($users['newpoints']);

}

function darObjeto($objeto_id, $cantidadNueva, $fid) {
    global $db;
    $cantidadActual = '0';
    $has_objeto = false;
    $inventario_actual = $db->query("SELECT * FROM mybb_op_inventario WHERE uid='$fid' AND objeto_id='$objeto_id'");
    while ($q = $db->fetch_array($inventario_actual)) { $has_objeto = true; $cantidadActual = $q['cantidad']; }

    if ($has_objeto) {
        $cantidadNuevaNueva = intval($cantidadActual) + intval($cantidadNueva);
        $db->query("
            UPDATE `mybb_op_inventario` SET `cantidad`='$cantidadNuevaNueva' WHERE objeto_id='$objeto_id' AND uid='$fid'
        ");
    } else {
        $db->query(" 
            INSERT INTO `mybb_op_inventario` (`objeto_id`, `uid`, `cantidad`) VALUES 
            ('$objeto_id', '$fid', '1');
        ");
    }
}

$narradorMult = 0;
if ($nivel_narrador == 'Aprendiz') { $narradorMult = 0; }
if ($nivel_narrador == 'Estudioso') { $narradorMult = 1; }
if ($nivel_narrador == 'Ilustre') { $narradorMult = 2; }
if ($nivel_narrador == 'Erudito') { $narradorMult = 3; }

$usuariosMult = (intval($usuarios) - 1) / 10;
$usuariosPct = (intval($usuarios) - 1) * 10;
$usuariosPctTxt = "$usuariosPct%";

$tidUrl = '';

if ($tid != '') {
    $tidUrl = "<a href='https://onepiecegaiden.com/showthread.php?tid=$tid' target='_blank'>$tid</a>";
    $tidUrl = addslashes($tidUrl);
}

if ($accion == 'T1' && $tid != '' && (is_mod($uid) || is_staff($uid) || is_narra($uid))) {

    $newpointsNew = $newpoints + 50.0;
    // $newpointsNew = $newpointsNew + (15 * $narradorMult);

    $nikasNew = $nikas + 6;
    $nikasNew = $nikasNew + (1 * $narradorMult);

    if ($narradorMult == 1) {
        $newpointsNew = $newpointsNew + 15;
    } else if ($narradorMult == 2) {
        $newpointsNew = $newpointsNew + 30;
    } else if ($narradorMult == 3) {
        $newpointsNew = $newpointsNew + 65;
    }

    $newpointsNew = $newpointsNew + round(50.0 * $usuariosMult);
    $nikasNew = $nikasNew + round(6 * $usuariosMult);
  
    $db->query(" UPDATE `mybb_op_fichas` SET `nika`='$nikasNew' WHERE fid='$ficha_id' ");
    $db->query(" UPDATE `mybb_users` SET `newpoints`='$newpointsNew' WHERE uid='$ficha_id' ");

    header('Content-type: application/json');
    $response = array();
    $timestamp = time();

    $response[0] = array(
        'timestamp' => $timestamp
    );

    $textoLog = "
        <strong><i><u>Moderador</i></u></strong>: $username ($uid) <br>
        <strong><i><u>Usuario</i></u></strong>: $nombre ($ficha_id) <br>
        <strong><i><u>ID del tema</i></u></strong>: $tidUrl <br>
        * <strong><i><u>Experiencia</i></u></strong>: $newpoints -> $newpointsNew <br>
        * <strong><i><u>Nikas</i></u></strong>: $nikas -> $nikasNew <br>
        * <strong><i><u>Cofre</i></u></strong>: Decente <br>
        * <strong><i><u>Usuarios Multiplicador</i></u></strong>: $usuarios ($usuariosPctTxt) <br>
        * <strong><i><u>Nivel Narrador</i></u></strong>: $nivel_narrador <br>
    ";
    darObjeto("CFR002", '1', $ficha_id);

    log_audit($uid, $username, '[Entregas][Aventura Narradores]', "$textoLog");
    echo json_encode($response); 
    return;
}

if ($accion == 'T2' && $tid != '' && (is_mod($uid) || is_staff($uid) || is_narra($uid))) {

    $newpointsNew = $newpoints + 75.0;
    // $newpointsNew = $newpointsNew + (15 * $narradorMult);

    $nikasNew = $nikas + 12;
    $nikasNew = $nikasNew + (1 * $narradorMult);

    if ($narradorMult == 1) {
        $newpointsNew = $newpointsNew + 10;
    } else if ($narradorMult == 2) {
        $newpointsNew = $newpointsNew + 25;
    } else if ($narradorMult == 3) {
        $newpointsNew = $newpointsNew + 55;
    }

    $newpointsNew = $newpointsNew + round(75.0 * $usuariosMult);
    $nikasNew = $nikasNew + round(12 * $usuariosMult);
    
    $db->query(" UPDATE `mybb_op_fichas` SET `nika`='$nikasNew' WHERE fid='$ficha_id' ");
    $db->query(" UPDATE `mybb_users` SET `newpoints`='$newpointsNew' WHERE uid='$ficha_id' ");

    header('Content-type: application/json');
    $response = array();
    $timestamp = time();

    $response[0] = array(
        'timestamp' => $timestamp
    );

    $textoLog = "
        <strong><i><u>Moderador</i></u></strong>: $username ($uid) <br>
        <strong><i><u>Usuario</i></u></strong>: $nombre ($ficha_id) <br>
        <strong><i><u>ID del tema</i></u></strong>: $tidUrl <br>
        * <strong><i><u>Experiencia</i></u></strong>: $newpoints -> $newpointsNew <br>
        * <strong><i><u>Nikas</i></u></strong>: $nikas -> $nikasNew <br>
        * <strong><i><u>Cofre</i></u></strong>: Gigante <br>
        * <strong><i><u>Usuarios Multiplicador</i></u></strong>: $usuarios ($usuariosPctTxt) <br>
        * <strong><i><u>Nivel Narrador</i></u></strong>: $nivel_narrador <br>
    ";
    darObjeto("CFR003", "1", $ficha_id);

    log_audit($uid, $username, '[Entregas][Aventura Narradores]', "$textoLog");
    echo json_encode($response); 
    return;
}

if ($accion == 'T3' && $tid != '' && (is_mod($uid) || is_staff($uid) || is_narra($uid))) {

    $newpointsNew = $newpoints + 120.0;
    // $newpointsNew = $newpointsNew + (15 * $narradorMult);

    $nikasNew = $nikas + 22;
    // $nikasNew = $nikasNew + (1 * $narradorMult);

    if ($narradorMult == 1) {
        $newpointsNew = $newpointsNew + 20;
        $nikasNew = $nikasNew + 2;
    } else if ($narradorMult == 2) {
        $newpointsNew = $newpointsNew + 35;
        $nikasNew = $nikasNew + 3;
    } else if ($narradorMult == 3) {
        $newpointsNew = $newpointsNew + 50;
        $nikasNew = $nikasNew + 4;
    }

    $newpointsNew = $newpointsNew + round(120.0 * $usuariosMult);
    $nikasNew = $nikasNew + round(22 * $usuariosMult);
    
    $db->query(" UPDATE `mybb_op_fichas` SET `nika`='$nikasNew' WHERE fid='$ficha_id' ");
    $db->query(" UPDATE `mybb_users` SET `newpoints`='$newpointsNew' WHERE uid='$ficha_id' ");

    header('Content-type: application/json');
    $response = array();
    $timestamp = time();

    $response[0] = array(
        'timestamp' => $timestamp
    );

    $textoLog = "
        <strong><i><u>Moderador</i></u></strong>: $username ($uid) <br>
        <strong><i><u>Usuario</i></u></strong>: $nombre ($ficha_id) <br>
        <strong><i><u>ID del tema</i></u></strong>: $tidUrl <br>
        * <strong><i><u>Experiencia</i></u></strong>: $newpoints -> $newpointsNew <br>
        * <strong><i><u>Nikas</i></u></strong>: $nikas -> $nikasNew <br>
        * <strong><i><u>Cofre</i></u></strong>: x2 Gigante <br>
        * <strong><i><u>Usuarios Multiplicador</i></u></strong>: $usuarios ($usuariosPctTxt) <br>
        * <strong><i><u>Nivel Narrador</i></u></strong>: $nivel_narrador <br>
    ";
    darObjeto("CFR003", "1", $ficha_id);
    darObjeto("CFR003", "1", $ficha_id);

    log_audit($uid, $username, '[Entregas][Aventura Narradores]', "$textoLog");
    echo json_encode($response); 
    return;
}

if ($accion == 'T4' && $tid != '' && (is_mod($uid) || is_staff($uid) || is_narra($uid))) {

    $newpointsNew = $newpoints + 150.0;
    // $newpointsNew = $newpointsNew + (15 * $narradorMult);

    $nikasNew = $nikas + 34;
    // $nikasNew = $nikasNew + (1 * $narradorMult);

    if ($narradorMult == 1) {
        $newpointsNew = $newpointsNew + 20;
        $nikasNew = $nikasNew + 1;
    } else if ($narradorMult == 2) {
        $newpointsNew = $newpointsNew + 30;
        $nikasNew = $nikasNew + 2;
    } else if ($narradorMult == 3) {
        $newpointsNew = $newpointsNew + 40;
        $nikasNew = $nikasNew + 3;
    }

    $newpointsNew = $newpointsNew + round(150.0 * $usuariosMult);
    $nikasNew = $nikasNew + round(34 * $usuariosMult);
    
    $db->query(" UPDATE `mybb_op_fichas` SET `nika`='$nikasNew' WHERE fid='$ficha_id' ");
    $db->query(" UPDATE `mybb_users` SET `newpoints`='$newpointsNew' WHERE uid='$ficha_id' ");

    header('Content-type: application/json');
    $response = array();
    $timestamp = time();

    $response[0] = array(
        'timestamp' => $timestamp
    );

    $textoLog = "
        <strong><i><u>Moderador</i></u></strong>: $username ($uid) <br>
        <strong><i><u>Usuario</i></u></strong>: $nombre ($ficha_id) <br>
        <strong><i><u>ID del tema</i></u></strong>: $tidUrl <br>
        * <strong><i><u>Experiencia</i></u></strong>: $newpoints -> $newpointsNew <br>
        * <strong><i><u>Nikas</i></u></strong>: $nikas -> $nikasNew <br>
        * <strong><i><u>Cofre</i></u></strong>: x1 Cobrizo <br>
        * <strong><i><u>Usuarios Multiplicador</i></u></strong>: $usuarios ($usuariosPctTxt) <br>
        * <strong><i><u>Nivel Narrador</i></u></strong>: $nivel_narrador <br>
    ";
    darObjeto("CFR004", "1", $ficha_id);

    log_audit($uid, $username, '[Entregas][Aventura Narradores]', "$textoLog");
    echo json_encode($response); 
    return;
}

if ($accion == 'T5' && $tid != '' && (is_mod($uid) || is_staff($uid) || is_narra($uid))) {

    $newpointsNew = $newpoints + 200.0;
    // $newpointsNew = $newpointsNew + (15 * $narradorMult);

    $nikasNew = $nikas + 45;
    // $nikasNew = $nikasNew + (1 * $narradorMult);

    if ($narradorMult == 1) {
        $newpointsNew = $newpointsNew + 20;
        $nikasNew = $nikasNew + 1;
    } else if ($narradorMult == 2) {
        $newpointsNew = $newpointsNew + 35;
        $nikasNew = $nikasNew + 2;
    } else if ($narradorMult == 3) {
        $newpointsNew = $newpointsNew + 50;
        $nikasNew = $nikasNew + 3;
    }

    $newpointsNew = $newpointsNew + round(200.0 * $usuariosMult);
    $nikasNew = $nikasNew + round(45 * $usuariosMult);
    
    $db->query(" UPDATE `mybb_op_fichas` SET `nika`='$nikasNew' WHERE fid='$ficha_id' ");
    $db->query(" UPDATE `mybb_users` SET `newpoints`='$newpointsNew' WHERE uid='$ficha_id' ");

    header('Content-type: application/json');
    $response = array();
    $timestamp = time();

    $response[0] = array(
        'timestamp' => $timestamp
    );

    $textoLog = "
        <strong><i><u>Moderador</i></u></strong>: $username ($uid) <br>
        <strong><i><u>Usuario</i></u></strong>: $nombre ($ficha_id) <br>
        <strong><i><u>ID del tema</i></u></strong>: $tidUrl <br>
        * <strong><i><u>Experiencia</i></u></strong>: $newpoints -> $newpointsNew <br>
        * <strong><i><u>Nikas</i></u></strong>: $nikas -> $nikasNew <br>
        * <strong><i><u>Cofre</i></u></strong>: x2 Cobrizo <br>
        * <strong><i><u>Usuarios Multiplicador</i></u></strong>: $usuarios ($usuariosPctTxt) <br>
        * <strong><i><u>Nivel Narrador</i></u></strong>: $nivel_narrador <br>
    ";
    darObjeto("CFR004", "1", $ficha_id);
    darObjeto("CFR004", "1", $ficha_id);

    log_audit($uid, $username, '[Entregas][Aventura Narradores]', "$textoLog");
    echo json_encode($response); 
    return;
}

if ($accion == 'T6' && $tid != '' && (is_mod($uid) || is_staff($uid) || is_narra($uid))) {

    $newpointsNew = $newpoints + 250.0;
    // $newpointsNew = $newpointsNew + (15 * $narradorMult);

    $nikasNew = $nikas + 56;
    // $nikasNew = $nikasNew + (1 * $narradorMult);

    if ($narradorMult == 1) {
        $newpointsNew = $newpointsNew + 15;
        $nikasNew = $nikasNew + 1;
    } else if ($narradorMult == 2) {
        $newpointsNew = $newpointsNew + 30;
        $nikasNew = $nikasNew + 2;
    } else if ($narradorMult == 3) {
        $newpointsNew = $newpointsNew + 50;
        $nikasNew = $nikasNew + 3;
    }

    $newpointsNew = $newpointsNew + round(250.0 * $usuariosMult);
    $nikasNew = $nikasNew + round(56 * $usuariosMult);
    
    $db->query(" UPDATE `mybb_op_fichas` SET `nika`='$nikasNew' WHERE fid='$ficha_id' ");
    $db->query(" UPDATE `mybb_users` SET `newpoints`='$newpointsNew' WHERE uid='$ficha_id' ");

    header('Content-type: application/json');
    $response = array();
    $timestamp = time();

    $response[0] = array(
        'timestamp' => $timestamp
    );

    $textoLog = "
        <strong><i><u>Moderador</i></u></strong>: $username ($uid) <br>
        <strong><i><u>Usuario</i></u></strong>: $nombre ($ficha_id) <br>
        <strong><i><u>ID del tema</i></u></strong>: $tidUrl <br>
        * <strong><i><u>Experiencia</i></u></strong>: $newpoints -> $newpointsNew <br>
        * <strong><i><u>Nikas</i></u></strong>: $nikas -> $nikasNew <br>
        * <strong><i><u>Cofre</i></u></strong>: x1 Argénteo <br>
        * <strong><i><u>Usuarios Multiplicador</i></u></strong>: $usuarios ($usuariosPctTxt) <br>
        * <strong><i><u>Nivel Narrador</i></u></strong>: $nivel_narrador <br>
    ";
    darObjeto("CFR005", "1", $ficha_id);

    log_audit($uid, $username, '[Entregas][Aventura Narradores]', "$textoLog");
    echo json_encode($response); 
    return;
}

if ($accion == 'T7' && $tid != '' && (is_mod($uid) || is_staff($uid) || is_narra($uid))) {

    $newpointsNew = $newpoints + 300.0;
    // $newpointsNew = $newpointsNew + (15 * $narradorMult);

    $nikasNew = $nikas + 67;
    // $nikasNew = $nikasNew + (1 * $narradorMult);

    if ($narradorMult == 1) {
        $newpointsNew = $newpointsNew + 20;
        $nikasNew = $nikasNew + 1;
    } else if ($narradorMult == 2) {
        $newpointsNew = $newpointsNew + 50;
        $nikasNew = $nikasNew + 2;
    } else if ($narradorMult == 3) {
        $newpointsNew = $newpointsNew + 85;
        $nikasNew = $nikasNew + 3;
    }

    $newpointsNew = $newpointsNew + round(300.0 * $usuariosMult);
    $nikasNew = $nikasNew + round(67 * $usuariosMult);
    
    $db->query(" UPDATE `mybb_op_fichas` SET `nika`='$nikasNew' WHERE fid='$ficha_id' ");
    $db->query(" UPDATE `mybb_users` SET `newpoints`='$newpointsNew' WHERE uid='$ficha_id' ");

    header('Content-type: application/json');
    $response = array();
    $timestamp = time();

    $response[0] = array(
        'timestamp' => $timestamp
    );

    $textoLog = "
        <strong><i><u>Moderador</i></u></strong>: $username ($uid) <br>
        <strong><i><u>Usuario</i></u></strong>: $nombre ($ficha_id) <br>
        <strong><i><u>ID del tema</i></u></strong>: $tidUrl <br>
        * <strong><i><u>Experiencia</i></u></strong>: $newpoints -> $newpointsNew <br>
        * <strong><i><u>Nikas</i></u></strong>: $nikas -> $nikasNew <br>
        * <strong><i><u>Cofre</i></u></strong>: x2 Argénteos <br>
        * <strong><i><u>Usuarios Multiplicador</i></u></strong>: $usuarios ($usuariosPctTxt) <br>
        * <strong><i><u>Nivel Narrador</i></u></strong>: $nivel_narrador <br>
    ";
    darObjeto("CFR005", "1", $ficha_id);
    darObjeto("CFR005", "1", $ficha_id);

    log_audit($uid, $username, '[Entregas][Aventura Narradores]', "$textoLog");
    echo json_encode($response); 
    return;
}

if ($accion == 'T8' && $tid != '' && (is_mod($uid) || is_staff($uid) || is_narra($uid))) {

    $newpointsNew = $newpoints + 400.0;
    // $newpointsNew = $newpointsNew + (15 * $narradorMult);

    $nikasNew = $nikas + 88;
    // $nikasNew = $nikasNew + (1 * $narradorMult);

    if ($narradorMult == 1) {
        $newpointsNew = $newpointsNew + 0;
        $nikasNew = $nikasNew + 1;
    } else if ($narradorMult == 2) {
        $newpointsNew = $newpointsNew + 10;
        $nikasNew = $nikasNew + 2;
    } else if ($narradorMult == 3) {
        $newpointsNew = $newpointsNew + 30;
        $nikasNew = $nikasNew + 3;
    }

    $newpointsNew = $newpointsNew + round(400.0 * $usuariosMult);
    $nikasNew = $nikasNew + round(88 * $usuariosMult);
    
    $db->query(" UPDATE `mybb_op_fichas` SET `nika`='$nikasNew' WHERE fid='$ficha_id' ");
    $db->query(" UPDATE `mybb_users` SET `newpoints`='$newpointsNew' WHERE uid='$ficha_id' ");

    header('Content-type: application/json');
    $response = array();
    $timestamp = time();

    $response[0] = array(
        'timestamp' => $timestamp
    );

    $textoLog = "
        <strong><i><u>Moderador</i></u></strong>: $username ($uid) <br>
        <strong><i><u>Usuario</i></u></strong>: $nombre ($ficha_id) <br>
        <strong><i><u>ID del tema</i></u></strong>: $tidUrl <br>
        * <strong><i><u>Experiencia</i></u></strong>: $newpoints -> $newpointsNew <br>
        * <strong><i><u>Nikas</i></u></strong>: $nikas -> $nikasNew <br>
        * <strong><i><u>Cofre</i></u></strong>: x1 Áureo <br>
        * <strong><i><u>Usuarios Multiplicador</i></u></strong>: $usuarios ($usuariosPctTxt) <br>
        * <strong><i><u>Nivel Narrador</i></u></strong>: $nivel_narrador <br>
    ";
    darObjeto("CFR006", "1", $ficha_id);


    log_audit($uid, $username, '[Entregas][Aventura Narradores]', "$textoLog");
    echo json_encode($response); 
    return;
}

if ($accion == 'T9' && $tid != '' && (is_mod($uid) || is_staff($uid) || is_narra($uid))) {

    $newpointsNew = $newpoints + 560.0;
    // $newpointsNew = $newpointsNew + (15 * $narradorMult);

    $nikasNew = $nikas + 109;
    // $nikasNew = $nikasNew + (1 * $narradorMult);

    if ($narradorMult == 1) {
        $newpointsNew = $newpointsNew + 20;
        $nikasNew = $nikasNew + 1;
    } else if ($narradorMult == 2) {
        $newpointsNew = $newpointsNew + 40;
        $nikasNew = $nikasNew + 2;
    } else if ($narradorMult == 3) {
        $newpointsNew = $newpointsNew + 60;
        $nikasNew = $nikasNew + 3;
    }

    $newpointsNew = $newpointsNew + round(560.0 * $usuariosMult);
    $nikasNew = $nikasNew + round(109 * $usuariosMult);
    
    $db->query(" UPDATE `mybb_op_fichas` SET `nika`='$nikasNew' WHERE fid='$ficha_id' ");
    $db->query(" UPDATE `mybb_users` SET `newpoints`='$newpointsNew' WHERE uid='$ficha_id' ");

    header('Content-type: application/json');
    $response = array();
    $timestamp = time();

    $response[0] = array(
        'timestamp' => $timestamp
    );

    $textoLog = "
        <strong><i><u>Moderador</i></u></strong>: $username ($uid) <br>
        <strong><i><u>Usuario</i></u></strong>: $nombre ($ficha_id) <br>
        <strong><i><u>ID del tema</i></u></strong>: $tidUrl <br>
        * <strong><i><u>Experiencia</i></u></strong>: $newpoints -> $newpointsNew <br>
        * <strong><i><u>Nikas</i></u></strong>: $nikas -> $nikasNew <br>
        * <strong><i><u>Cofre</i></u></strong>: x1 Épico <br>
        * <strong><i><u>Usuarios Multiplicador</i></u></strong>: $usuarios ($usuariosPctTxt) <br>
        * <strong><i><u>Nivel Narrador</i></u></strong>: $nivel_narrador <br>
    ";
    darObjeto("CFR007", "1", $ficha_id);


    log_audit($uid, $username, '[Entregas][Aventura Narradores]', "$textoLog");
    echo json_encode($response); 
    return;
}

if ($accion == 'T10' && $tid != '' && (is_mod($uid) || is_staff($uid) || is_narra($uid))) {

    $newpointsNew = $newpoints + 730.0;
    // $newpointsNew = $newpointsNew + (15 * $narradorMult);

    $nikasNew = $nikas + 160;
    // $nikasNew = $nikasNew + (1 * $narradorMult);

    if ($narradorMult == 1) {
        $newpointsNew = $newpointsNew + 20;
        $nikasNew = $nikasNew + 1;
    } else if ($narradorMult == 2) {
        $newpointsNew = $newpointsNew + 45;
        $nikasNew = $nikasNew + 2;
    } else if ($narradorMult == 3) {
        $newpointsNew = $newpointsNew + 70;
        $nikasNew = $nikasNew + 3;
    }

    $newpointsNew = $newpointsNew + round(730.0 * $usuariosMult);
    $nikasNew = $nikasNew + round(160 * $usuariosMult);
    
    $db->query(" UPDATE `mybb_op_fichas` SET `nika`='$nikasNew' WHERE fid='$ficha_id' ");
    $db->query(" UPDATE `mybb_users` SET `newpoints`='$newpointsNew' WHERE uid='$ficha_id' ");

    header('Content-type: application/json');
    $response = array();
    $timestamp = time();

    $response[0] = array(
        'timestamp' => $timestamp
    );

    $textoLog = "
        <strong><i><u>Moderador</i></u></strong>: $username ($uid) <br>
        <strong><i><u>Usuario</i></u></strong>: $nombre ($ficha_id) <br>
        <strong><i><u>ID del tema</i></u></strong>: $tidUrl <br>
        * <strong><i><u>Experiencia</i></u></strong>: $newpoints -> $newpointsNew <br>
        * <strong><i><u>Nikas</i></u></strong>: $nikas -> $nikasNew <br>
        * <strong><i><u>Cofre</i></u></strong>: x1 Legendario <br>
        * <strong><i><u>Usuarios Multiplicador</i></u></strong>: $usuarios ($usuariosPctTxt) <br>
        * <strong><i><u>Nivel Narrador</i></u></strong>: $nivel_narrador <br>
    ";
    darObjeto("CFR008", "1", $ficha_id);


    log_audit($uid, $username, '[Entregas][Aventura Narradores]', "$textoLog");
    echo json_encode($response); 
    return;
}
// if ($accion == 'T4' && $tid != '' && (is_mod($uid) || is_staff($uid) || is_narra($uid))) {

//     $newpointsNew = $newpoints + 150.0;
//     $newpointsNew = $newpointsNew + (20 * $narradorMult);
//     $nikasNew = $nikas + 34;
//     $nikasNew = $nikasNew + (1 * $narradorMult);
//     $berriesNew = $berries + 7500000;
//     $berriesNew = $berriesNew + (1500000 * $narradorMult);

//     $newpointsNew = $newpointsNew * $usuariosMult;
//     $nikasNew = $nikasNew * $usuariosMult;
    
//     $db->query(" UPDATE `mybb_op_fichas` SET `nika`='$nikasNew' WHERE fid='$ficha_id' ");
//     $db->query(" UPDATE `mybb_users` SET `newpoints`='$newpointsNew' WHERE uid='$ficha_id' ");

//     header('Content-type: application/json');
//     $response = array();
//     $timestamp = time();

//     $response[0] = array(
//         'timestamp' => $timestamp
//     );

//     $textoLog = "
//         <strong><i><u>Moderador</i></u></strong>: $username ($uid) <br>
//         <strong><i><u>Usuario</i></u></strong>: $nombre ($ficha_id) <br>
//         <strong><i><u>ID del tema</i></u></strong>: $tid <br>
//         * <strong><i><u>Experiencia</i></u></strong>: $newpoints -> $newpointsNew <br>
//         * <strong><i><u>Nikas</i></u></strong>: $nikas -> $nikasNew <br>
//         * <strong><i><u>Cofre</i></u></strong>: Cobrizo <br>
//     ";
//     darObjeto("CFR004", $ficha_id);

//     log_audit($uid, $username, '[Entregas][Aventura Narradores]', "$textoLog");
//     echo json_encode($response); 
//     return;
// }

// if ($accion == 'T5' && $tid != '' && (is_mod($uid) || is_staff($uid) || is_narra($uid))) {

//     $newpointsNew = $newpoints + 200.0;
//     $newpointsNew = $newpointsNew + (20 * $narradorMult);
//     $nikasNew = $nikas + 45;
//     $nikasNew = $nikasNew + (1 * $narradorMult);
//     $berriesNew = $berries + 12500000;
//     $berriesNew = $berriesNew + (2500000 * $narradorMult);

//     $newpointsNew = $newpointsNew * $usuariosMult;
//     $nikasNew = $nikasNew * $usuariosMult;
    
//     $db->query(" UPDATE `mybb_op_fichas` SET `nika`='$nikasNew' WHERE fid='$ficha_id' ");
//     $db->query(" UPDATE `mybb_users` SET `newpoints`='$newpointsNew' WHERE uid='$ficha_id' ");

//     header('Content-type: application/json');
//     $response = array();
//     $timestamp = time();

//     $response[0] = array(
//         'timestamp' => $timestamp
//     );

//     $textoLog = "
//         <strong><i><u>Moderador</i></u></strong>: $username ($uid) <br>
//         <strong><i><u>Usuario</i></u></strong>: $nombre ($ficha_id) <br>
//         <strong><i><u>ID del tema</i></u></strong>: $tid <br>
//         * <strong><i><u>Experiencia</i></u></strong>: $newpoints -> $newpointsNew <br>
//         * <strong><i><u>Nikas</i></u></strong>: $nikas -> $nikasNew <br>
//         * <strong><i><u>Cofre</i></u></strong>: x2 Cobrizo <br>
//     ";
//     darObjeto("CFR004", $ficha_id);
//     darObjeto("CFR004", $ficha_id);

//     log_audit($uid, $username, '[Entregas][Aventura Narradores]', "$textoLog");
//     echo json_encode($response); 
//     return;
// }


// if ($accion == 'T6' && $tid != '' && (is_mod($uid) || is_staff($uid) || is_narra($uid))) {

//     $newpointsNew = $newpoints + 250.0;
//     $newpointsNew = $newpointsNew + (20 * $narradorMult);
//     $nikasNew = $nikas + 56;
//     $nikasNew = $nikasNew + (1 * $narradorMult);
//     $berriesNew = $berries + 25000000;
//     $berriesNew = $berriesNew + (5000000 * $narradorMult);

//     $newpointsNew = $newpointsNew * $usuariosMult;
//     $nikasNew = $nikasNew * $usuariosMult;
    
//     $db->query(" UPDATE `mybb_op_fichas` SET `nika`='$nikasNew' WHERE fid='$ficha_id' ");
//     $db->query(" UPDATE `mybb_users` SET `newpoints`='$newpointsNew' WHERE uid='$ficha_id' ");

//     header('Content-type: application/json');
//     $response = array();
//     $timestamp = time();

//     $response[0] = array(
//         'timestamp' => $timestamp
//     );

//     $textoLog = "
//         <strong><i><u>Moderador</i></u></strong>: $username ($uid) <br>
//         <strong><i><u>Usuario</i></u></strong>: $nombre ($ficha_id) <br>
//         <strong><i><u>ID del tema</i></u></strong>: $tid <br>
//         * <strong><i><u>Experiencia</i></u></strong>: $newpoints -> $newpointsNew <br>
//         * <strong><i><u>Nikas</i></u></strong>: $nikas -> $nikasNew <br>
//         * <strong><i><u>Cofre</i></u></strong>: Argénteo <br>
//     ";
//     darObjeto("CFR005", $ficha_id);

//     log_audit($uid, $username, '[Entregas][Aventura Narradores]', "$textoLog");
//     echo json_encode($response); 
//     return;
// }





// if ($accion == 'T7' && $tid != '' && (is_mod($uid) || is_staff($uid) || is_narra($uid))) {

//     $newpointsNew = $newpoints + 300.0;
//     $newpointsNew = $newpointsNew + (20 * $narradorMult);
//     $nikasNew = $nikas + 67;
//     $nikasNew = $nikasNew + (1 * $narradorMult);
//     $berriesNew = $berries + 37500000;
//     $berriesNew = $berriesNew + (5000000 * $narradorMult);

//     $newpointsNew = $newpointsNew * $usuariosMult;
//     $nikasNew = $nikasNew * $usuariosMult;
    
//     $db->query(" UPDATE `mybb_op_fichas` SET `nika`='$nikasNew' WHERE fid='$ficha_id' ");
//     $db->query(" UPDATE `mybb_users` SET `newpoints`='$newpointsNew' WHERE uid='$ficha_id' ");

//     header('Content-type: application/json');
//     $response = array();
//     $timestamp = time();

//     $response[0] = array(
//         'timestamp' => $timestamp
//     );

//     $textoLog = "
//         <strong><i><u>Moderador</i></u></strong>: $username ($uid) <br>
//         <strong><i><u>Usuario</i></u></strong>: $nombre ($ficha_id) <br>
//         <strong><i><u>ID del tema</i></u></strong>: $tid <br>
//         * <strong><i><u>Experiencia</i></u></strong>: $newpoints -> $newpointsNew <br>
//         * <strong><i><u>Nikas</i></u></strong>: $nikas -> $nikasNew <br>
//         * <strong><i><u>Cofre</i></u></strong>: x2 Argénteo <br>
//     ";
//     darObjeto("CFR005", $ficha_id);
//     darObjeto("CFR005", $ficha_id);

//     log_audit($uid, $username, '[Entregas][Aventura Narradores]', "$textoLog");
//     echo json_encode($response); 
//     return;
// }



// if ($accion == 'T8' && $tid != '' && (is_mod($uid) || is_staff($uid) || is_narra($uid))) {

//     $newpointsNew = $newpoints + 400.0;
//     $newpointsNew = $newpointsNew + (20 * $narradorMult);
//     $nikasNew = $nikas + 88;
//     $nikasNew = $nikasNew + (1 * $narradorMult);
//     $berriesNew = $berries + 50000000;
//     $berriesNew = $berriesNew + (10000000 * $narradorMult);

//     $newpointsNew = $newpointsNew * $usuariosMult;
//     $nikasNew = $nikasNew * $usuariosMult;
    
//     $db->query(" UPDATE `mybb_op_fichas` SET `nika`='$nikasNew' WHERE fid='$ficha_id' ");
//     $db->query(" UPDATE `mybb_users` SET `newpoints`='$newpointsNew' WHERE uid='$ficha_id' ");

//     header('Content-type: application/json');
//     $response = array();
//     $timestamp = time();

//     $response[0] = array(
//         'timestamp' => $timestamp
//     );

//     $textoLog = "
//         <strong><i><u>Moderador</i></u></strong>: $username ($uid) <br>
//         <strong><i><u>Usuario</i></u></strong>: $nombre ($ficha_id) <br>
//         <strong><i><u>ID del tema</i></u></strong>: $tid <br>
//         * <strong><i><u>Experiencia</i></u></strong>: $newpoints -> $newpointsNew <br>
//         * <strong><i><u>Nikas</i></u></strong>: $nikas -> $nikasNew <br>
//         * <strong><i><u>Cofre</i></u></strong>: Áureo <br>
//     ";
//     darObjeto("CFR006", $ficha_id);

//     log_audit($uid, $username, '[Entregas][Aventura Narradores]', "$textoLog");
//     echo json_encode($response); 
//     return;
// }


// if ($accion == 'T9' && $tid != '' && (is_mod($uid) || is_staff($uid) || is_narra($uid))) {

//     $newpointsNew = $newpoints + 560.0;
//     $newpointsNew = $newpointsNew + (20 * $narradorMult);
//     $nikasNew = $nikas + 109;
//     $nikasNew = $nikasNew + (1 * $narradorMult);
//     $berriesNew = $berries + 100000000;
//     $berriesNew = $berriesNew + (20000000 * $narradorMult);

//     $newpointsNew = $newpointsNew * $usuariosMult;
//     $nikasNew = $nikasNew * $usuariosMult;
    
//     $db->query(" UPDATE `mybb_op_fichas` SET `nika`='$nikasNew' WHERE fid='$ficha_id' ");
//     $db->query(" UPDATE `mybb_users` SET `newpoints`='$newpointsNew' WHERE uid='$ficha_id' ");

//     header('Content-type: application/json');
//     $response = array();
//     $timestamp = time();

//     $response[0] = array(
//         'timestamp' => $timestamp
//     );

//     $textoLog = "
//         <strong><i><u>Moderador</i></u></strong>: $username ($uid) <br>
//         <strong><i><u>Usuario</i></u></strong>: $nombre ($ficha_id) <br>
//         <strong><i><u>ID del tema</i></u></strong>: $tid <br>
//         * <strong><i><u>Experiencia</i></u></strong>: $newpoints -> $newpointsNew <br>
//         * <strong><i><u>Nikas</i></u></strong>: $nikas -> $nikasNew <br>
//         * <strong><i><u>Cofre</i></u></strong>: Épico <br>
//     ";
//     darObjeto("CFR007", $ficha_id);

//     log_audit($uid, $username, '[Entregas][Aventura Narradores]', "$textoLog");
//     echo json_encode($response); 
//     return;
// }


// if ($accion == 'T10' && $tid != '' && (is_mod($uid) || is_staff($uid) || is_narra($uid))) {

//     $newpointsNew = $newpoints + 730.0;
//     $newpointsNew = $newpointsNew + (20 * $narradorMult);
//     $nikasNew = $nikas + 160;
//     $nikasNew = $nikasNew + (1 * $narradorMult);
//     $berriesNew = $berries + 175000000;
//     $berriesNew = $berriesNew + (40000000 * $narradorMult);

//     $newpointsNew = $newpointsNew * $usuariosMult;
//     $nikasNew = $nikasNew * $usuariosMult;
    
//     $db->query(" UPDATE `mybb_op_fichas` SET `nika`='$nikasNew' WHERE fid='$ficha_id' ");
//     $db->query(" UPDATE `mybb_users` SET `newpoints`='$newpointsNew' WHERE uid='$ficha_id' ");

//     header('Content-type: application/json');
//     $response = array();
//     $timestamp = time();

//     $response[0] = array(
//         'timestamp' => $timestamp
//     );

//     $textoLog = "
//         <strong><i><u>Moderador</i></u></strong>: $username ($uid) <br>
//         <strong><i><u>Usuario</i></u></strong>: $nombre ($ficha_id) <br>
//         <strong><i><u>ID del tema</i></u></strong>: $tid <br>
//         * <strong><i><u>Experiencia</i></u></strong>: $newpoints -> $newpointsNew <br>
//         * <strong><i><u>Nikas</i></u></strong>: $nikas -> $nikasNew <br>
//         * <strong><i><u>Cofre</i></u></strong>: Legendario <br>
//     ";
//     darObjeto("CFR008", $ficha_id);

//     log_audit($uid, $username, '[Entregas][Aventura Narradores]', "$textoLog");
//     echo json_encode($response); 
//     return;
// }


if (is_mod($uid) || is_staff($uid) || is_narra($uid)) {

    eval('$fid = $user_fid;');
    eval('$accion = $user_accion;');

    eval("\$page = \"".$templates->get("staff_entregar_recompensas_narradores")."\";");
    output_page($page);
} else {
    eval("\$page = \"".$templates->get("sin_permisos")."\";");
    output_page($page);
}

