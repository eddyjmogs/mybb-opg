<?php
/**
 * MyBB 1.8
 * Copyright 2014 MyBB Group, All Rights Reserved
 *
 * Website: http://www.mybb.com
 * License: http://www.mybb.com/about/license
 */

define("IN_MYBB", 1);
define('THIS_SCRIPT', 'entregar_recompensas_aventuras.php');
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
$reputacion_align = $_POST["reputacion"];

$reload_js = "<script>window.location.href = window.location.pathname;</script>";

$ficha = null;
$users = null;

if ($user_fid != '') {
    $query_ficha = $db->query(" SELECT * FROM mybb_op_fichas WHERE fid='$user_fid' ");
    while ($q = $db->fetch_array($query_ficha)) { $ficha = $q; }

    $query_users = $db->query(" SELECT * FROM mybb_users WHERE uid='$user_fid' ");
    while ($q = $db->fetch_array($query_users)) { $users = $q; }
}

if ($accion == 'T1' || $accion == 'T2' || $accion == 'T3' || $accion == 'T4') {
    $query_ficha = $db->query(" SELECT * FROM mybb_op_fichas WHERE fid='$ficha_id' ");
    while ($q = $db->fetch_array($query_ficha)) { $ficha = $q; }

    $query_users = $db->query(" SELECT * FROM mybb_users WHERE uid='$ficha_id' ");
    while ($q = $db->fetch_array($query_users)) { $users = $q; }

    $oficios = json_decode($ficha['oficios']);

    $nivelContrabandista = -1;
    $contrabandistaMult = 1;

    $nivelArqueologo = -1;
    $arqueologoExtraXP = 1; // Multiplicador
    $arqueologoExtraNikas = 0; // Sumatorio

    if (isset($oficios->{'Mercader'})) { 
        $nivelContrabandista = $nivelContrabandista = $oficios->{'Mercader'}->{'sub'}->{'Contrabandista'};

    }

    if (isset($oficios->{'Investigador'})) { 
        $nivelArqueologo = $nivelArqueologo = $oficios->{'Investigador'}->{'sub'}->{'Arqueologo'};

    }

    if ($nivelContrabandista == 0) { 
        $contrabandistaMult = 1.25; 
    } else if ($nivelContrabandista == 1) { 
        $contrabandistaMult = 1.50; 
    } else if ($nivelContrabandista == 2) { 
        $contrabandistaMult = 1.75; 
    } else if ($nivelContrabandista == 3) { 
        $contrabandistaMult = 2; 
    } 
    $isContrabandista = $nivelContrabandista > -1;
    $contrabandistaTxt = "<strong><i><u>Nivel de Contrabandista</strong></i></u>: $nivelContrabandista. Multiplicador = $contrabandistaMult.";

    if ($nivelArqueologo == 0) {
        $arqueologoExtraXP = 1.00; 
    } else if ($nivelArqueologo == 1) { 
        $arqueologoExtraXP = 1.10; // 10% extra rango 1
    } else if ($nivelArqueologo == 2) { 
        $arqueologoExtraNikas = 1; 
    }
    $isArqueologo = $nivelArqueologo > -1;
    $arqueologoTxt = "<strong><i><u>Nivel de Arqueologo</strong></i></u>: $nivelArqueologo. Multiplicador XP = $arqueologoExtraXP. Nikas Extra = $arqueologoExtraNikas.";

    $nombre = $ficha['nombre'];

    $nikas = intval($ficha['nika']);
    $berries = intval($ficha['berries']);
    $reputacion = intval($ficha['reputacion']);
    $newpoints = floatval($users['newpoints']);

}

if ($accion == 'T1' && $tid != '' && (is_mod($uid) || is_staff($uid) || is_narra($uid))) {

    
    $newpointsNew = $newpoints + (30.0 * $arqueologoExtraXP);
    $nikasNew = $nikas + 2 + $arqueologoExtraNikas;
    $berriesNew = $berries + (300000 * $contrabandistaMult);
    $reputacionNew = $reputacion + 5;
    
    $repuAlignDb = '';

    if ($reputacion_align == 'positiva') {
        $reputacionAlign = intval($ficha['reputacion_positiva']);
        $reputacionAlignNew = $reputacionAlign + 5;
        $repuAlignDb = ",`reputacion_positiva`='$reputacionAlignNew'";

        $reputacionTxt = "* <strong><i><u>Reputación Positiva</i></u></strong>: $reputacionAlign -> $reputacionAlignNew (+5)";
    } else if ($reputacion_align == 'negativa') {
        $reputacionAlign = intval($ficha['reputacion_negativa']);
        $reputacionAlignNew = $reputacionAlign + 5;
        $repuAlignDb = ",`reputacion_negativa`='$reputacionAlignNew'";

        $reputacionTxt = "* <strong><i><u>Reputación Negativa</i></u></strong>: $reputacionAlign -> $reputacionAlignNew (+5)";
    }
    
    $db->query(" 
        UPDATE `mybb_op_fichas` SET `nika`='$nikasNew', `berries`='$berriesNew',`reputacion`='$reputacionNew' $repuAlignDb WHERE fid='$ficha_id'
    ");

    $db->query(" 
        UPDATE `mybb_users` SET `newpoints`='$newpointsNew' WHERE uid='$ficha_id'
    ");

    header('Content-type: application/json');
    $response = array();
    $timestamp = time();

    $response[0] = array(
        'timestamp' => $timestamp
    );

    $textoLog = "
        <strong><i><u>Moderador</i></u></strong>: $username ($uid) <br>
        <strong><i><u>Usuario</i></u></strong>: $nombre ($ficha_id) <br>
        <strong><i><u>ID del tema</i></u></strong>: $tid <br>
        * <strong><i><u>Berries</i></u></strong>: $berries -> $berriesNew (+300.000 * $contrabandistaMult) <br>
        * <strong><i><u>Experiencia</i></u></strong>: $newpoints -> $newpointsNew (+30) <br>
        * <strong><i><u>Nikas</i></u></strong>: $nikas -> $nikasNew (+2) <br>
        * <strong><i><u>Reputación</i></u></strong>: $reputacion -> $reputacionNew (+5) <br>
        $reputacionTxt <br>
        $contrabandistaTxt <br>
        $arqueologoTxt <br>
    ";

    log_audit($uid, $username, '[Entregas][Autonarrada]', "$textoLog");
    echo json_encode($response); 
    return;
}

if ($accion == 'T2' && $tid != '' && (is_mod($uid) || is_staff($uid) || is_narra($uid))) {

    $newpointsNew = $newpoints + (40.0 * $arqueologoExtraXP);
    $nikasNew = $nikas + 3 + $arqueologoExtraNikas;
    $berriesNew = $berries + (500000 * $contrabandistaMult);
    $reputacionNew = $reputacion + 10;
    
    $repuAlignDb = '';

    if ($reputacion_align == 'positiva') {
        $reputacionAlign = intval($ficha['reputacion_positiva']);
        $reputacionAlignNew = $reputacionAlign + 10;
        $repuAlignDb = ",`reputacion_positiva`='$reputacionAlignNew'";

        $reputacionTxt = "* <strong><i><u>Reputación Positiva</i></u></strong>: $reputacionAlign -> $reputacionAlignNew (+10)";
    } else if ($reputacion_align == 'negativa') {
        $reputacionAlign = intval($ficha['reputacion_negativa']);
        $reputacionAlignNew = $reputacionAlign + 10;
        $repuAlignDb = ",`reputacion_negativa`='$reputacionAlignNew'";

        $reputacionTxt = "* <strong><i><u>Reputación Negativa</i></u></strong>: $reputacionAlign -> $reputacionAlignNew (+10)";
    }
    
    $db->query(" 
        UPDATE `mybb_op_fichas` SET `nika`='$nikasNew', `berries`='$berriesNew',`reputacion`='$reputacionNew' $repuAlignDb WHERE fid='$ficha_id'
    ");

    $db->query(" 
        UPDATE `mybb_users` SET `newpoints`='$newpointsNew' WHERE uid='$ficha_id'
    ");

    header('Content-type: application/json');
    $response = array();
    $timestamp = time();

    $response[0] = array(
        'timestamp' => $timestamp
    );

    $textoLog = "
        <strong><i><u>Moderador</i></u></strong>: $username ($uid) <br>
        <strong><i><u>Usuario</i></u></strong>: $nombre ($ficha_id) <br>
        <strong><i><u>ID del tema</i></u></strong>: $tid <br>
        * <strong><i><u>Berries</i></u></strong>: $berries -> $berriesNew (+500.000 * $contrabandistaMult) <br>
        * <strong><i><u>Experiencia</i></u></strong>: $newpoints -> $newpointsNew (+40) <br>
        * <strong><i><u>Nikas</i></u></strong>: $nikas -> $nikasNew (+3) <br>
        * <strong><i><u>Reputación</i></u></strong>: $reputacion -> $reputacionNew (+10) <br>
        $reputacionTxt <br>
        $contrabandistaTxt <br>
        $arqueologoTxt <br>
    ";

    log_audit($uid, $username, '[Entregas][Autonarrada]', "$textoLog");
    echo json_encode($response); 
    return;
}

if ($accion == 'T3' && $tid != '' && (is_mod($uid) || is_staff($uid) || is_narra($uid))) {

    $newpointsNew = $newpoints + (65.0 * $arqueologoExtraXP);
    $nikasNew = $nikas + 5 + $arqueologoExtraNikas;
    $berriesNew = $berries + (1000000 * $contrabandistaMult);
    $reputacionNew = $reputacion + 20;
    
    $repuAlignDb = '';

    if ($reputacion_align == 'positiva') {
        $reputacionAlign = intval($ficha['reputacion_positiva']);
        $reputacionAlignNew = $reputacionAlign + 20;
        $repuAlignDb = ",`reputacion_positiva`='$reputacionAlignNew'";
        $reputacionTxt = "* <strong><i><u>Reputación Positiva</i></u></strong>: $reputacionAlign -> $reputacionAlignNew (+20)";
    } else if ($reputacion_align == 'negativa') {
        $reputacionAlign = intval($ficha['reputacion_negativa']);
        $reputacionAlignNew = $reputacionAlign + 20;
        $repuAlignDb = ",`reputacion_negativa`='$reputacionAlignNew'";

        $reputacionTxt = "* <strong><i><u>Reputación Negativa</i></u></strong>: $reputacionAlign -> $reputacionAlignNew (+20)";
    }
    
    $db->query(" 
        UPDATE `mybb_op_fichas` SET `nika`='$nikasNew', `berries`='$berriesNew',`reputacion`='$reputacionNew' $repuAlignDb WHERE fid='$ficha_id'
    ");

    $db->query(" 
        UPDATE `mybb_users` SET `newpoints`='$newpointsNew' WHERE uid='$ficha_id'
    ");

    header('Content-type: application/json');
    $response = array();
    $timestamp = time();

    $response[0] = array(
        'timestamp' => $timestamp
    );

    $textoLog = "
        <strong><i><u>Moderador</i></u></strong>: $username ($uid) <br>
        <strong><i><u>Usuario</i></u></strong>: $nombre ($ficha_id) <br>
        <strong><i><u>ID del tema</i></u></strong>: $tid <br>
        * <strong><i><u>Berries</i></u></strong>: $berries -> $berriesNew (+1.000.000 * $contrabandistaMult) <br>
        * <strong><i><u>Experiencia</i></u></strong>: $newpoints -> $newpointsNew (+65) <br>
        * <strong><i><u>Nikas</i></u></strong>: $nikas -> $nikasNew (+5) <br>
        * <strong><i><u>Reputación</i></u></strong>: $reputacion -> $reputacionNew (+20) <br>
        $reputacionTxt <br>
        $contrabandistaTxt <br>
        $arqueologoTxt <br>
    ";

    log_audit($uid, $username, '[Entregas][Autonarrada]', "$textoLog");
    echo json_encode($response); 
    return;
}

if ($accion == 'T4' && $tid != '' && (is_mod($uid) || is_staff($uid) || is_narra($uid))) {

    $newpointsNew = $newpoints + (100.0 * $arqueologoExtraXP);
    $nikasNew = $nikas + 8 + $arqueologoExtraNikas;
    $berriesNew = $berries + (1500000 * $contrabandistaMult);
    $reputacionNew = $reputacion + 30;
    
    $repuAlignDb = '';

    if ($reputacion_align == 'positiva') {
        $reputacionAlign = intval($ficha['reputacion_positiva']);
        $reputacionAlignNew = $reputacionAlign + 30;
        $repuAlignDb = ",`reputacion_positiva`='$reputacionAlignNew'";
        $reputacionTxt = "* <strong><i><u>Reputación Positiva</i></u></strong>: $reputacionAlign -> $reputacionAlignNew (+30)";
    } else if ($reputacion_align == 'negativa') {
        $reputacionAlign = intval($ficha['reputacion_negativa']);
        $reputacionAlignNew = $reputacionAlign + 30;
        $repuAlignDb = ",`reputacion_negativa`='$reputacionAlignNew'";

        $reputacionTxt = "* <strong><i><u>Reputación Negativa</i></u></strong>: $reputacionAlign -> $reputacionAlignNew (+30)";
    }
    
    $db->query(" 
        UPDATE `mybb_op_fichas` SET `nika`='$nikasNew', `berries`='$berriesNew',`reputacion`='$reputacionNew' $repuAlignDb WHERE fid='$ficha_id'
    ");

    $db->query(" 
        UPDATE `mybb_users` SET `newpoints`='$newpointsNew' WHERE uid='$ficha_id'
    ");

    header('Content-type: application/json');
    $response = array();
    $timestamp = time();

    $response[0] = array(
        'timestamp' => $timestamp
    );

    $textoLog = "
        <strong><i><u>Moderador</i></u></strong>: $username ($uid) <br>
        <strong><i><u>Usuario</i></u></strong>: $nombre ($ficha_id) <br>
        <strong><i><u>ID del tema</i></u></strong>: $tid <br>
        * <strong><i><u>Berries</i></u></strong>: $berries -> $berriesNew (+1.500.000 * $contrabandistaMult) <br>
        * <strong><i><u>Experiencia</i></u></strong>: $newpoints -> $newpointsNew (+100) <br>
        * <strong><i><u>Nikas</i></u></strong>: $nikas -> $nikasNew (+8) <br>
        * <strong><i><u>Reputación</i></u></strong>: $reputacion -> $reputacionNew (+30) <br>
        $reputacionTxt <br>
        $contrabandistaTxt <br>
        $arqueologoTxt <br>
    ";

    log_audit($uid, $username, '[Entregas][Autonarrada]', "$textoLog");
    echo json_encode($response); 
    return;
}

if (is_mod($uid) || is_staff($uid) || is_narra($uid)) { 

    eval('$fid = $user_fid;');
    eval('$accion = $user_accion;');
    eval("\$page = \"".$templates->get("staff_entregar_recompensas")."\";");
    output_page($page);
} else {
    eval("\$page = \"".$templates->get("sin_permisos")."\";");
    output_page($page);
}

