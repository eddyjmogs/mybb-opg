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

if ($accion != '') {
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

    
    $newpointsNew = $newpoints + (50.0 * $arqueologoExtraXP);
    $nikasNew = $nikas + 5 + $arqueologoExtraNikas;
    $berriesNew = $berries + (1000000 * $contrabandistaMult);
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
        * <strong><i><u>Berries</i></u></strong>: $berries -> $berriesNew (+1.000.000 * $contrabandistaMult) <br>
        * <strong><i><u>Experiencia</i></u></strong>: $newpoints -> $newpointsNew (+50) <br>
        * <strong><i><u>Nikas</i></u></strong>: $nikas -> $nikasNew (+5) <br>
        * <strong><i><u>Reputación</i></u></strong>: $reputacion -> $reputacionNew (+10) <br>
        $reputacionTxt <br>
        $contrabandistaTxt <br>
        $arqueologoTxt <br>
    ";

    log_audit($uid, $username, '[Entregas][Aventura Usuario]', "$textoLog");
    echo json_encode($response); 
    return;
}

if ($accion == 'T1Infra' && $tid != '' && (is_mod($uid) || is_staff($uid) || is_narra($uid))) {

    
    $newpointsNew = $newpoints + (50.0 * $arqueologoExtraXP);
    $nikasNew = $nikas + 5 + $arqueologoExtraNikas;
    $berriesNew = $berries + (1000000 * $contrabandistaMult * 1.25);
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
        * <strong><i><u>Berries</i></u></strong>: $berries -> $berriesNew (+1.000.000 * $contrabandistaMult * 1.25) <br>
        * <strong><i><u>Experiencia</i></u></strong>: $newpoints -> $newpointsNew (+50) <br>
        * <strong><i><u>Nikas</i></u></strong>: $nikas -> $nikasNew (+5) <br>
        * <strong><i><u>Reputación</i></u></strong>: $reputacion -> $reputacionNew (+10) <br>
        $reputacionTxt <br>
        $contrabandistaTxt <br>
        $arqueologoTxt <br>
    ";

    log_audit($uid, $username, '[Entregas][Aventura Usuario]', "$textoLog");
    echo json_encode($response); 
    return;
}

if ($accion == 'T2' && $tid != '' && (is_mod($uid) || is_staff($uid) || is_narra($uid))) {

    
    $newpointsNew = $newpoints + (75.0 * $arqueologoExtraXP);
    $nikasNew = $nikas + 10 + $arqueologoExtraNikas;
    $berriesNew = $berries + (5000000 * $contrabandistaMult);
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
        * <strong><i><u>Berries</i></u></strong>: $berries -> $berriesNew (+5.000.000 * $contrabandistaMult) <br>
        * <strong><i><u>Experiencia</i></u></strong>: $newpoints -> $newpointsNew (+75) <br>
        * <strong><i><u>Nikas</i></u></strong>: $nikas -> $nikasNew (+10) <br>
        * <strong><i><u>Reputación</i></u></strong>: $reputacion -> $reputacionNew (+20) <br>
        $reputacionTxt <br>
        $contrabandistaTxt <br>
        $arqueologoTxt <br>
    ";

    log_audit($uid, $username, '[Entregas][Aventura Usuario]', "$textoLog");
    echo json_encode($response); 
    return;
}

if ($accion == 'T2Infra' && $tid != '' && (is_mod($uid) || is_staff($uid) || is_narra($uid))) {

    
    $newpointsNew = $newpoints + (75.0 * $arqueologoExtraXP);
    $nikasNew = $nikas + 10 + $arqueologoExtraNikas;
    $berriesNew = $berries + (5000000 * $contrabandistaMult * 1.25);
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
        * <strong><i><u>Berries</i></u></strong>: $berries -> $berriesNew (+5.000.000 * $contrabandistaMult * 1.25) <br>
        * <strong><i><u>Experiencia</i></u></strong>: $newpoints -> $newpointsNew (+75) <br>
        * <strong><i><u>Nikas</i></u></strong>: $nikas -> $nikasNew (+10) <br>
        * <strong><i><u>Reputación</i></u></strong>: $reputacion -> $reputacionNew (+20) <br>
        $reputacionTxt <br>
        $contrabandistaTxt <br>
        $arqueologoTxt <br>
    ";

    log_audit($uid, $username, '[Entregas][Aventura Usuario]', "$textoLog");
    echo json_encode($response); 
    return;
}

if ($accion == 'T3' && $tid != '' && (is_mod($uid) || is_staff($uid) || is_narra($uid))) {

    
    $newpointsNew = $newpoints + (120.0 * $arqueologoExtraXP);
    $nikasNew = $nikas + 20 + $arqueologoExtraNikas;
    $berriesNew = $berries + (10000000 * $contrabandistaMult);
    $reputacionNew = $reputacion + 50;
    
    $repuAlignDb = '';

    if ($reputacion_align == 'positiva') {
        $reputacionAlign = intval($ficha['reputacion_positiva']);
        $reputacionAlignNew = $reputacionAlign + 50;
        $repuAlignDb = ",`reputacion_positiva`='$reputacionAlignNew'";

        $reputacionTxt = "* <strong><i><u>Reputación Positiva</i></u></strong>: $reputacionAlign -> $reputacionAlignNew (+50)";
    } else if ($reputacion_align == 'negativa') {
        $reputacionAlign = intval($ficha['reputacion_negativa']);
        $reputacionAlignNew = $reputacionAlign + 50;
        $repuAlignDb = ",`reputacion_negativa`='$reputacionAlignNew'";

        $reputacionTxt = "* <strong><i><u>Reputación Negativa</i></u></strong>: $reputacionAlign -> $reputacionAlignNew (+50)";
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
        * <strong><i><u>Berries</i></u></strong>: $berries -> $berriesNew (+10.000.000 * $contrabandistaMult) <br>
        * <strong><i><u>Experiencia</i></u></strong>: $newpoints -> $newpointsNew (+120) <br>
        * <strong><i><u>Nikas</i></u></strong>: $nikas -> $nikasNew (+20) <br>
        * <strong><i><u>Reputación</i></u></strong>: $reputacion -> $reputacionNew (+50) <br>
        $reputacionTxt <br>
        $contrabandistaTxt <br>
        $arqueologoTxt <br>
    ";

    log_audit($uid, $username, '[Entregas][Aventura Usuario]', "$textoLog");
    echo json_encode($response); 
    return;
}

if ($accion == 'T3Infra' && $tid != '' && (is_mod($uid) || is_staff($uid) || is_narra($uid))) {

    
    $newpointsNew = $newpoints + (120.0 * $arqueologoExtraXP);
    $nikasNew = $nikas + 20 + $arqueologoExtraNikas;
    $berriesNew = $berries + (10000000 * $contrabandistaMult * 1.25);
    $reputacionNew = $reputacion + 50;
    
    $repuAlignDb = '';

    if ($reputacion_align == 'positiva') {
        $reputacionAlign = intval($ficha['reputacion_positiva']);
        $reputacionAlignNew = $reputacionAlign + 50;
        $repuAlignDb = ",`reputacion_positiva`='$reputacionAlignNew'";

        $reputacionTxt = "* <strong><i><u>Reputación Positiva</i></u></strong>: $reputacionAlign -> $reputacionAlignNew (+50)";
    } else if ($reputacion_align == 'negativa') {
        $reputacionAlign = intval($ficha['reputacion_negativa']);
        $reputacionAlignNew = $reputacionAlign + 50;
        $repuAlignDb = ",`reputacion_negativa`='$reputacionAlignNew'";

        $reputacionTxt = "* <strong><i><u>Reputación Negativa</i></u></strong>: $reputacionAlign -> $reputacionAlignNew (+50)";
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
        * <strong><i><u>Berries</i></u></strong>: $berries -> $berriesNew (+10.000.000 * $contrabandistaMult * 1.25) <br>
        * <strong><i><u>Experiencia</i></u></strong>: $newpoints -> $newpointsNew (+120) <br>
        * <strong><i><u>Nikas</i></u></strong>: $nikas -> $nikasNew (+20) <br>
        * <strong><i><u>Reputación</i></u></strong>: $reputacion -> $reputacionNew (+50) <br>
        $reputacionTxt <br>
        $contrabandistaTxt <br>
        $arqueologoTxt <br>
    ";

    log_audit($uid, $username, '[Entregas][Aventura Usuario]', "$textoLog");
    echo json_encode($response); 
    return;
}

if ($accion == 'T4' && $tid != '' && (is_mod($uid) || is_staff($uid) || is_narra($uid))) {

    
    $newpointsNew = $newpoints + (150.0 * $arqueologoExtraXP);
    $nikasNew = $nikas + 30 + $arqueologoExtraNikas;
    $berriesNew = $berries + (15000000 * $contrabandistaMult);
    $reputacionNew = $reputacion + 80;
    
    $repuAlignDb = '';

    if ($reputacion_align == 'positiva') {
        $reputacionAlign = intval($ficha['reputacion_positiva']);
        $reputacionAlignNew = $reputacionAlign + 80;
        $repuAlignDb = ",`reputacion_positiva`='$reputacionAlignNew'";

        $reputacionTxt = "* <strong><i><u>Reputación Positiva</i></u></strong>: $reputacionAlign -> $reputacionAlignNew (+80)";
    } else if ($reputacion_align == 'negativa') {
        $reputacionAlign = intval($ficha['reputacion_negativa']);
        $reputacionAlignNew = $reputacionAlign + 80;
        $repuAlignDb = ",`reputacion_negativa`='$reputacionAlignNew'";

        $reputacionTxt = "* <strong><i><u>Reputación Negativa</i></u></strong>: $reputacionAlign -> $reputacionAlignNew (+80)";
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
        * <strong><i><u>Berries</i></u></strong>: $berries -> $berriesNew (+15.000.000 * $contrabandistaMult) <br>
        * <strong><i><u>Experiencia</i></u></strong>: $newpoints -> $newpointsNew (+150) <br>
        * <strong><i><u>Nikas</i></u></strong>: $nikas -> $nikasNew (+30) <br>
        * <strong><i><u>Reputación</i></u></strong>: $reputacion -> $reputacionNew (+80) <br>
        $reputacionTxt <br>
        $contrabandistaTxt <br>
        $arqueologoTxt <br>
    ";

    log_audit($uid, $username, '[Entregas][Aventura Usuario]', "$textoLog");
    echo json_encode($response); 
    return;
}

if ($accion == 'T4Infra' && $tid != '' && (is_mod($uid) || is_staff($uid) || is_narra($uid))) {

    
    $newpointsNew = $newpoints + (150.0 * $arqueologoExtraXP);
    $nikasNew = $nikas + 30 + $arqueologoExtraNikas;
    $berriesNew = $berries + (15000000 * $contrabandistaMult * 1.25);
    $reputacionNew = $reputacion + 80;
    
    $repuAlignDb = '';

    if ($reputacion_align == 'positiva') {
        $reputacionAlign = intval($ficha['reputacion_positiva']);
        $reputacionAlignNew = $reputacionAlign + 80;
        $repuAlignDb = ",`reputacion_positiva`='$reputacionAlignNew'";

        $reputacionTxt = "* <strong><i><u>Reputación Positiva</i></u></strong>: $reputacionAlign -> $reputacionAlignNew (+80)";
    } else if ($reputacion_align == 'negativa') {
        $reputacionAlign = intval($ficha['reputacion_negativa']);
        $reputacionAlignNew = $reputacionAlign + 80;
        $repuAlignDb = ",`reputacion_negativa`='$reputacionAlignNew'";

        $reputacionTxt = "* <strong><i><u>Reputación Negativa</i></u></strong>: $reputacionAlign -> $reputacionAlignNew (+80)";
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
        * <strong><i><u>Berries</i></u></strong>: $berries -> $berriesNew (+15.000.000 * $contrabandistaMult * 1.25) <br>
        * <strong><i><u>Experiencia</i></u></strong>: $newpoints -> $newpointsNew (+150) <br>
        * <strong><i><u>Nikas</i></u></strong>: $nikas -> $nikasNew (+30) <br>
        * <strong><i><u>Reputación</i></u></strong>: $reputacion -> $reputacionNew (+80) <br>
        $reputacionTxt <br>
        $contrabandistaTxt <br>
        $arqueologoTxt <br>
    ";

    log_audit($uid, $username, '[Entregas][Aventura Usuario]', "$textoLog");
    echo json_encode($response); 
    return;
}

if ($accion == 'T5' && $tid != '' && (is_mod($uid) || is_staff($uid) || is_narra($uid))) {

    
    $newpointsNew = $newpoints + (200.0 * $arqueologoExtraXP);
    $nikasNew = $nikas + 40 + $arqueologoExtraNikas;
    $berriesNew = $berries + (25000000 * $contrabandistaMult);
    $reputacionNew = $reputacion + 120;
    
    $repuAlignDb = '';

    if ($reputacion_align == 'positiva') {
        $reputacionAlign = intval($ficha['reputacion_positiva']);
        $reputacionAlignNew = $reputacionAlign + 120;
        $repuAlignDb = ",`reputacion_positiva`='$reputacionAlignNew'";

        $reputacionTxt = "* <strong><i><u>Reputación Positiva</i></u></strong>: $reputacionAlign -> $reputacionAlignNew (+120)";
    } else if ($reputacion_align == 'negativa') {
        $reputacionAlign = intval($ficha['reputacion_negativa']);
        $reputacionAlignNew = $reputacionAlign + 120;
        $repuAlignDb = ",`reputacion_negativa`='$reputacionAlignNew'";

        $reputacionTxt = "* <strong><i><u>Reputación Negativa</i></u></strong>: $reputacionAlign -> $reputacionAlignNew (+120)";
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
        * <strong><i><u>Berries</i></u></strong>: $berries -> $berriesNew (+25.000.000 * $contrabandistaMult) <br>
        * <strong><i><u>Experiencia</i></u></strong>: $newpoints -> $newpointsNew (+200) <br>
        * <strong><i><u>Nikas</i></u></strong>: $nikas -> $nikasNew (+40) <br>
        * <strong><i><u>Reputación</i></u></strong>: $reputacion -> $reputacionNew (+120) <br>
        $reputacionTxt <br>
        $contrabandistaTxt <br>
        $arqueologoTxt <br>
    ";

    log_audit($uid, $username, '[Entregas][Aventura Usuario]', "$textoLog");
    echo json_encode($response); 
    return;
}

if ($accion == 'T5Infra' && $tid != '' && (is_mod($uid) || is_staff($uid) || is_narra($uid))) {

    
    $newpointsNew = $newpoints + (200.0 * $arqueologoExtraXP);
    $nikasNew = $nikas + 40 + $arqueologoExtraNikas;
    $berriesNew = $berries + (25000000 * $contrabandistaMult * 1.25);
    $reputacionNew = $reputacion + 120;
    
    $repuAlignDb = '';

    if ($reputacion_align == 'positiva') {
        $reputacionAlign = intval($ficha['reputacion_positiva']);
        $reputacionAlignNew = $reputacionAlign + 120;
        $repuAlignDb = ",`reputacion_positiva`='$reputacionAlignNew'";

        $reputacionTxt = "* <strong><i><u>Reputación Positiva</i></u></strong>: $reputacionAlign -> $reputacionAlignNew (+120)";
    } else if ($reputacion_align == 'negativa') {
        $reputacionAlign = intval($ficha['reputacion_negativa']);
        $reputacionAlignNew = $reputacionAlign + 120;
        $repuAlignDb = ",`reputacion_negativa`='$reputacionAlignNew'";

        $reputacionTxt = "* <strong><i><u>Reputación Negativa</i></u></strong>: $reputacionAlign -> $reputacionAlignNew (+120)";
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
        * <strong><i><u>Berries</i></u></strong>: $berries -> $berriesNew (+25.000.000 * $contrabandistaMult * 1.25) <br>
        * <strong><i><u>Experiencia</i></u></strong>: $newpoints -> $newpointsNew (+200) <br>
        * <strong><i><u>Nikas</i></u></strong>: $nikas -> $nikasNew (+40) <br>
        * <strong><i><u>Reputación</i></u></strong>: $reputacion -> $reputacionNew (+120) <br>
        $reputacionTxt <br>
        $contrabandistaTxt <br>
        $arqueologoTxt <br>
    ";

    log_audit($uid, $username, '[Entregas][Aventura Usuario]', "$textoLog");
    echo json_encode($response); 
    return;
}


if ($accion == 'T6' && $tid != '' && (is_mod($uid) || is_staff($uid) || is_narra($uid))) {

    
    $newpointsNew = $newpoints + (250.0 * $arqueologoExtraXP);
    $nikasNew = $nikas + 50 + $arqueologoExtraNikas;
    $berriesNew = $berries + (50000000 * $contrabandistaMult);
    $reputacionNew = $reputacion + 160;
    
    $repuAlignDb = '';

    if ($reputacion_align == 'positiva') {
        $reputacionAlign = intval($ficha['reputacion_positiva']);
        $reputacionAlignNew = $reputacionAlign + 160;
        $repuAlignDb = ",`reputacion_positiva`='$reputacionAlignNew'";

        $reputacionTxt = "* <strong><i><u>Reputación Positiva</i></u></strong>: $reputacionAlign -> $reputacionAlignNew (+160)";
    } else if ($reputacion_align == 'negativa') {
        $reputacionAlign = intval($ficha['reputacion_negativa']);
        $reputacionAlignNew = $reputacionAlign + 160;
        $repuAlignDb = ",`reputacion_negativa`='$reputacionAlignNew'";

        $reputacionTxt = "* <strong><i><u>Reputación Negativa</i></u></strong>: $reputacionAlign -> $reputacionAlignNew (+160)";
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
        * <strong><i><u>Berries</i></u></strong>: $berries -> $berriesNew (+50.000.000 * $contrabandistaMult) <br>
        * <strong><i><u>Experiencia</i></u></strong>: $newpoints -> $newpointsNew (+250) <br>
        * <strong><i><u>Nikas</i></u></strong>: $nikas -> $nikasNew (+50) <br>
        * <strong><i><u>Reputación</i></u></strong>: $reputacion -> $reputacionNew (+160) <br>
        $reputacionTxt <br>
        $contrabandistaTxt <br>
        $arqueologoTxt <br>
    ";

    log_audit($uid, $username, '[Entregas][Aventura Usuario]', "$textoLog");
    echo json_encode($response); 
    return;
}

if ($accion == 'T6Infra' && $tid != '' && (is_mod($uid) || is_staff($uid) || is_narra($uid))) {

    
    $newpointsNew = $newpoints + (250.0 * $arqueologoExtraXP);
    $nikasNew = $nikas + 50 + $arqueologoExtraNikas;
    $berriesNew = $berries + (50000000 * $contrabandistaMult * 1.25);
    $reputacionNew = $reputacion + 160;
    
    $repuAlignDb = '';

    if ($reputacion_align == 'positiva') {
        $reputacionAlign = intval($ficha['reputacion_positiva']);
        $reputacionAlignNew = $reputacionAlign + 160;
        $repuAlignDb = ",`reputacion_positiva`='$reputacionAlignNew'";

        $reputacionTxt = "* <strong><i><u>Reputación Positiva</i></u></strong>: $reputacionAlign -> $reputacionAlignNew (+160)";
    } else if ($reputacion_align == 'negativa') {
        $reputacionAlign = intval($ficha['reputacion_negativa']);
        $reputacionAlignNew = $reputacionAlign + 160;
        $repuAlignDb = ",`reputacion_negativa`='$reputacionAlignNew'";

        $reputacionTxt = "* <strong><i><u>Reputación Negativa</i></u></strong>: $reputacionAlign -> $reputacionAlignNew (+160)";
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
        * <strong><i><u>Berries</i></u></strong>: $berries -> $berriesNew (+50.000.000 * $contrabandistaMult * 1.25) <br>
        * <strong><i><u>Experiencia</i></u></strong>: $newpoints -> $newpointsNew (+250) <br>
        * <strong><i><u>Nikas</i></u></strong>: $nikas -> $nikasNew (+50) <br>
        * <strong><i><u>Reputación</i></u></strong>: $reputacion -> $reputacionNew (+160) <br>
        $reputacionTxt <br>
        $contrabandistaTxt <br>
        $arqueologoTxt <br>
    ";

    log_audit($uid, $username, '[Entregas][Aventura Usuario]', "$textoLog");
    echo json_encode($response); 
    return;
}

if ($accion == 'T7' && $tid != '' && (is_mod($uid) || is_staff($uid) || is_narra($uid))) {

    
    $newpointsNew = $newpoints + (300.0 * $arqueologoExtraXP);
    $nikasNew = $nikas + 60 + $arqueologoExtraNikas;
    $berriesNew = $berries + (75000000 * $contrabandistaMult);
    $reputacionNew = $reputacion + 200;
    
    $repuAlignDb = '';

    if ($reputacion_align == 'positiva') {
        $reputacionAlign = intval($ficha['reputacion_positiva']);
        $reputacionAlignNew = $reputacionAlign + 200;
        $repuAlignDb = ",`reputacion_positiva`='$reputacionAlignNew'";

        $reputacionTxt = "* <strong><i><u>Reputación Positiva</i></u></strong>: $reputacionAlign -> $reputacionAlignNew (+200)";
    } else if ($reputacion_align == 'negativa') {
        $reputacionAlign = intval($ficha['reputacion_negativa']);
        $reputacionAlignNew = $reputacionAlign + 200;
        $repuAlignDb = ",`reputacion_negativa`='$reputacionAlignNew'";

        $reputacionTxt = "* <strong><i><u>Reputación Negativa</i></u></strong>: $reputacionAlign -> $reputacionAlignNew (+200)";
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
        * <strong><i><u>Berries</i></u></strong>: $berries -> $berriesNew (+75.000.000 * $contrabandistaMult) <br>
        * <strong><i><u>Experiencia</i></u></strong>: $newpoints -> $newpointsNew (+300) <br>
        * <strong><i><u>Nikas</i></u></strong>: $nikas -> $nikasNew (+60) <br>
        * <strong><i><u>Reputación</i></u></strong>: $reputacion -> $reputacionNew (+200) <br>
        $reputacionTxt <br>
        $contrabandistaTxt <br>
        $arqueologoTxt <br>
    ";

    log_audit($uid, $username, '[Entregas][Aventura Usuario]', "$textoLog");
    echo json_encode($response); 
    return;
}

if ($accion == 'T7Infra' && $tid != '' && (is_mod($uid) || is_staff($uid) || is_narra($uid))) {

    
    $newpointsNew = $newpoints + (300.0 * $arqueologoExtraXP);
    $nikasNew = $nikas + 60 + $arqueologoExtraNikas;
    $berriesNew = $berries + (75000000 * $contrabandistaMult * 1.25);
    $reputacionNew = $reputacion + 200;
    
    $repuAlignDb = '';

    if ($reputacion_align == 'positiva') {
        $reputacionAlign = intval($ficha['reputacion_positiva']);
        $reputacionAlignNew = $reputacionAlign + 200;
        $repuAlignDb = ",`reputacion_positiva`='$reputacionAlignNew'";

        $reputacionTxt = "* <strong><i><u>Reputación Positiva</i></u></strong>: $reputacionAlign -> $reputacionAlignNew (+200)";
    } else if ($reputacion_align == 'negativa') {
        $reputacionAlign = intval($ficha['reputacion_negativa']);
        $reputacionAlignNew = $reputacionAlign + 200;
        $repuAlignDb = ",`reputacion_negativa`='$reputacionAlignNew'";

        $reputacionTxt = "* <strong><i><u>Reputación Negativa</i></u></strong>: $reputacionAlign -> $reputacionAlignNew (+200)";
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
        * <strong><i><u>Berries</i></u></strong>: $berries -> $berriesNew (+75.000.000 * $contrabandistaMult * 1.25) <br>
        * <strong><i><u>Experiencia</i></u></strong>: $newpoints -> $newpointsNew (+300) <br>
        * <strong><i><u>Nikas</i></u></strong>: $nikas -> $nikasNew (+60) <br>
        * <strong><i><u>Reputación</i></u></strong>: $reputacion -> $reputacionNew (+200) <br>
        $reputacionTxt <br>
        $contrabandistaTxt <br>
        $arqueologoTxt <br>
    ";

    log_audit($uid, $username, '[Entregas][Aventura Usuario]', "$textoLog");
    echo json_encode($response); 
    return;
}

if ($accion == 'T8' && $tid != '' && (is_mod($uid) || is_staff($uid) || is_narra($uid))) {

    
    $newpointsNew = $newpoints + (400.0 * $arqueologoExtraXP);
    $nikasNew = $nikas + 80 + $arqueologoExtraNikas;
    $berriesNew = $berries + (100000000 * $contrabandistaMult);
    $reputacionNew = $reputacion + 250;
    
    $repuAlignDb = '';

    if ($reputacion_align == 'positiva') {
        $reputacionAlign = intval($ficha['reputacion_positiva']);
        $reputacionAlignNew = $reputacionAlign + 250;
        $repuAlignDb = ",`reputacion_positiva`='$reputacionAlignNew'";

        $reputacionTxt = "* <strong><i><u>Reputación Positiva</i></u></strong>: $reputacionAlign -> $reputacionAlignNew (+250)";
    } else if ($reputacion_align == 'negativa') {
        $reputacionAlign = intval($ficha['reputacion_negativa']);
        $reputacionAlignNew = $reputacionAlign + 250;
        $repuAlignDb = ",`reputacion_negativa`='$reputacionAlignNew'";

        $reputacionTxt = "* <strong><i><u>Reputación Negativa</i></u></strong>: $reputacionAlign -> $reputacionAlignNew (+250)";
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
        * <strong><i><u>Berries</i></u></strong>: $berries -> $berriesNew (+100.000.000 * $contrabandistaMult) <br>
        * <strong><i><u>Experiencia</i></u></strong>: $newpoints -> $newpointsNew (+400) <br>
        * <strong><i><u>Nikas</i></u></strong>: $nikas -> $nikasNew (+80) <br>
        * <strong><i><u>Reputación</i></u></strong>: $reputacion -> $reputacionNew (+250) <br>
        $reputacionTxt <br>
        $contrabandistaTxt <br>
        $arqueologoTxt <br>
    ";

    log_audit($uid, $username, '[Entregas][Aventura Usuario]', "$textoLog");
    echo json_encode($response); 
    return;
}

if ($accion == 'T8Infra' && $tid != '' && (is_mod($uid) || is_staff($uid) || is_narra($uid))) {

    
    $newpointsNew = $newpoints + (400.0 * $arqueologoExtraXP);
    $nikasNew = $nikas + 80 + $arqueologoExtraNikas;
    $berriesNew = $berries + (100000000 * $contrabandistaMult * 1.25);
    $reputacionNew = $reputacion + 250;
    
    $repuAlignDb = '';

    if ($reputacion_align == 'positiva') {
        $reputacionAlign = intval($ficha['reputacion_positiva']);
        $reputacionAlignNew = $reputacionAlign + 250;
        $repuAlignDb = ",`reputacion_positiva`='$reputacionAlignNew'";

        $reputacionTxt = "* <strong><i><u>Reputación Positiva</i></u></strong>: $reputacionAlign -> $reputacionAlignNew (+250)";
    } else if ($reputacion_align == 'negativa') {
        $reputacionAlign = intval($ficha['reputacion_negativa']);
        $reputacionAlignNew = $reputacionAlign + 250;
        $repuAlignDb = ",`reputacion_negativa`='$reputacionAlignNew'";

        $reputacionTxt = "* <strong><i><u>Reputación Negativa</i></u></strong>: $reputacionAlign -> $reputacionAlignNew (+250)";
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
        * <strong><i><u>Berries</i></u></strong>: $berries -> $berriesNew (+100.000.000 * $contrabandistaMult * 1.25) <br>
        * <strong><i><u>Experiencia</i></u></strong>: $newpoints -> $newpointsNew (+400) <br>
        * <strong><i><u>Nikas</i></u></strong>: $nikas -> $nikasNew (+80) <br>
        * <strong><i><u>Reputación</i></u></strong>: $reputacion -> $reputacionNew (+250) <br>
        $reputacionTxt <br>
        $contrabandistaTxt <br>
        $arqueologoTxt <br>
    ";

    log_audit($uid, $username, '[Entregas][Aventura Usuario]', "$textoLog");
    echo json_encode($response); 
    return;
}

if ($accion == 'T9' && $tid != '' && (is_mod($uid) || is_staff($uid) || is_narra($uid))) {

    
    $newpointsNew = $newpoints + (600.0 * $arqueologoExtraXP);
    $nikasNew = $nikas + 100 + $arqueologoExtraNikas;
    $berriesNew = $berries + (200000000 * $contrabandistaMult);
    $reputacionNew = $reputacion + 300;
    
    $repuAlignDb = '';

    if ($reputacion_align == 'positiva') {
        $reputacionAlign = intval($ficha['reputacion_positiva']);
        $reputacionAlignNew = $reputacionAlign + 300;
        $repuAlignDb = ",`reputacion_positiva`='$reputacionAlignNew'";

        $reputacionTxt = "* <strong><i><u>Reputación Positiva</i></u></strong>: $reputacionAlign -> $reputacionAlignNew (+300)";
    } else if ($reputacion_align == 'negativa') {
        $reputacionAlign = intval($ficha['reputacion_negativa']);
        $reputacionAlignNew = $reputacionAlign + 300;
        $repuAlignDb = ",`reputacion_negativa`='$reputacionAlignNew'";

        $reputacionTxt = "* <strong><i><u>Reputación Negativa</i></u></strong>: $reputacionAlign -> $reputacionAlignNew (+300)";
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
        * <strong><i><u>Berries</i></u></strong>: $berries -> $berriesNew (+200.000.000 * $contrabandistaMult) <br>
        * <strong><i><u>Experiencia</i></u></strong>: $newpoints -> $newpointsNew (+600) <br>
        * <strong><i><u>Nikas</i></u></strong>: $nikas -> $nikasNew (+100) <br>
        * <strong><i><u>Reputación</i></u></strong>: $reputacion -> $reputacionNew (+300) <br>
        $reputacionTxt <br>
        $contrabandistaTxt <br>
        $arqueologoTxt <br>
    ";

    log_audit($uid, $username, '[Entregas][Aventura Usuario]', "$textoLog");
    echo json_encode($response); 
    return;
}

if ($accion == 'T9Infra' && $tid != '' && (is_mod($uid) || is_staff($uid) || is_narra($uid))) {

    
    $newpointsNew = $newpoints + (600.0 * $arqueologoExtraXP);
    $nikasNew = $nikas + 100 + $arqueologoExtraNikas;
    $berriesNew = $berries + (200000000 * $contrabandistaMult * 1.25);
    $reputacionNew = $reputacion + 300;
    
    $repuAlignDb = '';

    if ($reputacion_align == 'positiva') {
        $reputacionAlign = intval($ficha['reputacion_positiva']);
        $reputacionAlignNew = $reputacionAlign + 300;
        $repuAlignDb = ",`reputacion_positiva`='$reputacionAlignNew'";

        $reputacionTxt = "* <strong><i><u>Reputación Positiva</i></u></strong>: $reputacionAlign -> $reputacionAlignNew (+300)";
    } else if ($reputacion_align == 'negativa') {
        $reputacionAlign = intval($ficha['reputacion_negativa']);
        $reputacionAlignNew = $reputacionAlign + 300;
        $repuAlignDb = ",`reputacion_negativa`='$reputacionAlignNew'";

        $reputacionTxt = "* <strong><i><u>Reputación Negativa</i></u></strong>: $reputacionAlign -> $reputacionAlignNew (+300)";
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
        * <strong><i><u>Berries</i></u></strong>: $berries -> $berriesNew (+200.000.000 * $contrabandistaMult * 1.25) <br>
        * <strong><i><u>Experiencia</i></u></strong>: $newpoints -> $newpointsNew (+600) <br>
        * <strong><i><u>Nikas</i></u></strong>: $nikas -> $nikasNew (+100) <br>
        * <strong><i><u>Reputación</i></u></strong>: $reputacion -> $reputacionNew (+300) <br>
        $reputacionTxt <br>
        $contrabandistaTxt <br>
        $arqueologoTxt <br>
    ";

    log_audit($uid, $username, '[Entregas][Aventura Usuario]', "$textoLog");
    echo json_encode($response); 
    return;
}

if ($accion == 'T10' && $tid != '' && (is_mod($uid) || is_staff($uid) || is_narra($uid))) {

    
    $newpointsNew = $newpoints + (750.0 * $arqueologoExtraXP);
    $nikasNew = $nikas + 150 + $arqueologoExtraNikas;
    $berriesNew = $berries + (350000000 * $contrabandistaMult);
    $reputacionNew = $reputacion + 500;
    
    $repuAlignDb = '';

    if ($reputacion_align == 'positiva') {
        $reputacionAlign = intval($ficha['reputacion_positiva']);
        $reputacionAlignNew = $reputacionAlign + 500;
        $repuAlignDb = ",`reputacion_positiva`='$reputacionAlignNew'";

        $reputacionTxt = "* <strong><i><u>Reputación Positiva</i></u></strong>: $reputacionAlign -> $reputacionAlignNew (+500)";
    } else if ($reputacion_align == 'negativa') {
        $reputacionAlign = intval($ficha['reputacion_negativa']);
        $reputacionAlignNew = $reputacionAlign + 500;
        $repuAlignDb = ",`reputacion_negativa`='$reputacionAlignNew'";

        $reputacionTxt = "* <strong><i><u>Reputación Negativa</i></u></strong>: $reputacionAlign -> $reputacionAlignNew (+500)";
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
        * <strong><i><u>Berries</i></u></strong>: $berries -> $berriesNew (+350.000.000 * $contrabandistaMult) <br>
        * <strong><i><u>Experiencia</i></u></strong>: $newpoints -> $newpointsNew (+750) <br>
        * <strong><i><u>Nikas</i></u></strong>: $nikas -> $nikasNew (+150) <br>
        * <strong><i><u>Reputación</i></u></strong>: $reputacion -> $reputacionNew (+500) <br>
        $reputacionTxt <br>
        $contrabandistaTxt <br>
        $arqueologoTxt <br>
    ";

    log_audit($uid, $username, '[Entregas][Aventura Usuario]', "$textoLog");
    echo json_encode($response); 
    return;
}

if ($accion == 'T10Infra' && $tid != '' && (is_mod($uid) || is_staff($uid) || is_narra($uid))) {

    
    $newpointsNew = $newpoints + (750.0 * $arqueologoExtraXP);
    $nikasNew = $nikas + 150 + $arqueologoExtraNikas;
    $berriesNew = $berries + (350000000 * $contrabandistaMult * 1.25);
    $reputacionNew = $reputacion + 500;
    
    $repuAlignDb = '';

    if ($reputacion_align == 'positiva') {
        $reputacionAlign = intval($ficha['reputacion_positiva']);
        $reputacionAlignNew = $reputacionAlign + 500;
        $repuAlignDb = ",`reputacion_positiva`='$reputacionAlignNew'";

        $reputacionTxt = "* <strong><i><u>Reputación Positiva</i></u></strong>: $reputacionAlign -> $reputacionAlignNew (+500)";
    } else if ($reputacion_align == 'negativa') {
        $reputacionAlign = intval($ficha['reputacion_negativa']);
        $reputacionAlignNew = $reputacionAlign + 500;
        $repuAlignDb = ",`reputacion_negativa`='$reputacionAlignNew'";

        $reputacionTxt = "* <strong><i><u>Reputación Negativa</i></u></strong>: $reputacionAlign -> $reputacionAlignNew (+500)";
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
        * <strong><i><u>Berries</i></u></strong>: $berries -> $berriesNew (+350.000.000 * $contrabandistaMult * 1.25) <br>
        * <strong><i><u>Experiencia</i></u></strong>: $newpoints -> $newpointsNew (+750) <br>
        * <strong><i><u>Nikas</i></u></strong>: $nikas -> $nikasNew (+150) <br>
        * <strong><i><u>Reputación</i></u></strong>: $reputacion -> $reputacionNew (+500) <br>
        $reputacionTxt <br>
        $contrabandistaTxt <br>
        $arqueologoTxt <br>
    ";

    log_audit($uid, $username, '[Entregas][Aventura Usuario]', "$textoLog");
    echo json_encode($response); 
    return;
}

if (is_mod($uid) || is_staff($uid) || is_narra($uid)) { 

    eval('$fid = $user_fid;');
    eval('$accion = $user_accion;');
    eval("\$page = \"".$templates->get("staff_entregar_recompensas_aventuras")."\";");
    output_page($page);
} else {
    eval("\$page = \"".$templates->get("sin_permisos")."\";");
    output_page($page);
}

