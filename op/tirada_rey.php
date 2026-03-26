<?php
/**
 * MyBB 1.8
 * Copyright 2014 MyBB Group, All Rights Reserved
 *
 * Website: http://www.mybb.com
 * License: http://www.mybb.com/about/license
 *
 */

define("IN_MYBB", 1);
define('THIS_SCRIPT', 'tirada_rey.php');
require_once "./../global.php";
require "./../inc/config.php";
require_once "./../op/functions/op_functions.php";
global $templates, $mybb;

$uid = $mybb->user['uid'];
$ficha = null;

if ($g_ficha['muerto'] == '1') {
    $mensaje_redireccion = "Estás muerto, no puedes acceder a esta página.";
    eval("\$page = \"".$templates->get("op_redireccion")."\";");
    output_page($page);
    return;
}


$query_ficha = $db->query(" SELECT * FROM mybb_op_fichas WHERE fid='$uid' "); 
while ($f = $db->fetch_array($query_ficha)) { $ficha = $f; }

if ($ficha == null) {
    $mensaje_redireccion = "Este usuario no ha creado su ficha aún.";
    eval("\$page = \"".$templates->get("op_redireccion")."\";");
    output_page($page);
    return;
}

$username = $mybb->user['username'];
$tirada_real = $_POST["tirada_real"];
$tirada_aleatoria = $_POST["tirada_aleatoria"];

$tirada_aleatoria_resultado = '';

$tiradas_totales = '0';
$tiradas_definitiva = '0';
$tiradas_fake_exito = '0';
$tiradas_real_exito = '0';

$tiradas_totales_id = '0';
$tiradas_definitiva_id = '0';
$tiradas_fake_exito_id = '0';
$tiradas_real_exito_id = '0';

$tiradas_aleatorias_array = array();
$tiradas_reales_array = array();

$tiradas_aleatorias_query = $db->query(" SELECT * FROM `mybb_op_tirada_rey` WHERE `real`='0' ORDER BY ID DESC LIMIT 20; ");
$tiradas_reales_query = $db->query(" SELECT * FROM `mybb_op_tirada_rey` WHERE `real`='1' ORDER BY ID DESC LIMIT 200; ");

$tiradas_totales_query = $db->query(" SELECT count(*) AS total FROM `mybb_op_tirada_rey` ");
$tiradas_definitiva_query = $db->query(" SELECT count(*) AS total FROM `mybb_op_tirada_rey` WHERE `real`='1'; ");
$tiradas_fake_exito_query = $db->query(" SELECT count(*) AS total FROM `mybb_op_tirada_rey` WHERE `haki`='1' AND `real`='0'; ");
$tiradas_real_exito_query = $db->query(" SELECT count(*) AS total FROM `mybb_op_tirada_rey` WHERE `haki`='1' AND `real`='1'; ");

$tiradas_totales_id_query = $db->query(" SELECT count(*) AS total FROM `mybb_op_tirada_rey` WHERE uid='$uid' ");
$tiradas_definitiva_id_query = $db->query(" SELECT count(*) AS total FROM `mybb_op_tirada_rey` WHERE `real`='1' AND uid='$uid'; ");
$tiradas_fake_exito_id_query = $db->query(" SELECT count(*) AS total FROM `mybb_op_tirada_rey` WHERE haki='1' AND `real`='1' AND uid='$uid'; ");
$tiradas_real_exito_id_query = $db->query(" SELECT count(*) AS total FROM `mybb_op_tirada_rey` WHERE haki='1' AND `real`='1' AND uid='$uid'; ");

while ($q = $db->fetch_array($tiradas_totales_query)) { $tiradas_totales = $q['total']; }
while ($q = $db->fetch_array($tiradas_definitiva_query)) { $tiradas_definitiva = $q['total']; }
while ($q = $db->fetch_array($tiradas_fake_exito_query)) { $tiradas_fake_exito = $q['total']; }
while ($q = $db->fetch_array($tiradas_real_exito_query)) { $tiradas_real_exito = $q['total']; }

while ($q = $db->fetch_array($tiradas_totales_id_query)) { $tiradas_totales_id = $q['total']; }
while ($q = $db->fetch_array($tiradas_definitiva_id_query)) { $tiradas_definitiva_id = $q['total']; }
while ($q = $db->fetch_array($tiradas_fake_exito_id_query)) { $tiradas_fake_exito_id = $q['total']; }
while ($q = $db->fetch_array($tiradas_real_exito_id_query)) { $tiradas_real_exito_id = $q['total']; }

while ($q = $db->fetch_array($tiradas_aleatorias_query)) { 
    array_push($tiradas_aleatorias_array, $q);
}

while ($q = $db->fetch_array($tiradas_reales_query)) { 
    array_push($tiradas_reales_array, $q);
}

$tiradas_aleatorias_json = json_encode($tiradas_aleatorias_array);
$tiradas_reales_json = json_encode($tiradas_reales_array);

$hao_hao = 0;
$has_chance = false;
$has_nivel = false;
$hao_chance = $ficha['hao_chance'];
$nivel = intval($ficha['nivel']);

$has_full_haki = false;
$has_clan_d = false;
$has_dragon = false;

$has_clan_d_query = $db->query(" SELECT * FROM `mybb_op_virtudes_usuarios` WHERE uid='$uid' AND virtud_id='V027'; ");
// $has_full_haki_query = $db->query(" SELECT * FROM `mybb_op_virtudes_usuarios` WHERE uid='$uid' AND virtud_id='V029'; "); 
$has_dragon_query = $db->query(" SELECT * FROM `mybb_op_virtudes_usuarios` WHERE uid='$uid' AND virtud_id='V060'; "); 

while ($q = $db->fetch_array($has_clan_d_query)) { $has_clan_d = true; }
// while ($q = $db->fetch_array($has_full_haki_query)) { $has_full_haki = true; }
while ($q = $db->fetch_array($has_dragon_query)) { $has_dragon = true; }

if ($ficha['camino'] == 'Haki') { $has_full_haki = true; }



if ($nivel >= 8) {
    $has_nivel = true;
}

if ($ficha['hao'] != '-1') {
    $has_hao = true;
}

if ($ficha['hao_chance'] != '0') {
    $has_chance = true;
}

if ($tirada_aleatoria == 'true') {
    header('Content-type: application/json');
    $response = array();
    $timestamp = time();

    $tipo_haki = '';
    $tirada_random = rand(1, 20);

    if ($has_full_haki && ($has_clan_d || $has_dragon)) {
        $tirada_random = rand(1, 9);
    } else if ($has_full_haki) {
        $tirada_random = rand(1, 10);
    } else if ($has_clan_d || $has_dragon) {
        $tirada_random = rand(1, 19);
    } else {
        $tirada_random = rand(1, 20);
    }

    if ($tirada_random == 1) {
        $tipo_haki = '1';
    } else {
        $tipo_haki = '0';
    }

    $response[0] = array(
        'nombre' => $username,
        'haki' => $tipo_haki,
        'timestamp' => $timestamp
    );

    $db->query(" INSERT INTO `mybb_op_tirada_rey`(`uid`, `nombre`, `haki`, `subnombre`, `real`, `tirada_random`, `timestamp`) VALUES 
        ('$uid','$username','$tipo_haki','','0','$tirada_random','$timestamp')");

    echo json_encode($response); 
    return;
}

if ($tirada_real == 'true') {
    header('Content-type: application/json');
    $response = array();
    $timestamp = time();

    $tipo_haki = '';

    if ($has_full_haki && ($has_clan_d || $has_dragon)) {
        $tirada_random = rand(1, 9);
    } else if ($has_full_haki) {
        $tirada_random = rand(1, 10);
    } else if ($has_clan_d || $has_dragon) {
        $tirada_random = rand(1, 19);
    } else {
        $tirada_random = rand(1, 20);
    }

    //if ($uid == 2){
    //    if ($has_full_haki && ($has_clan_d || $has_dragon)) {
    //        $tirada_random = rand(3, 9);
    //    } else if ($has_full_haki) {
    //        $tirada_random = rand(3, 10);
    //    } else if ($has_clan_d || $has_dragon) {
    //        $tirada_random = rand(3, 19);
    //    } else {
    //        $tirada_random = rand(3, 20);
    //    }
    //}

    if ($tirada_random == 1) {
        $tipo_haki = '1';
    } else {
        $tipo_haki = '0';
    }

    $haki_nivel = '-1';

    if ($tipo_haki == '1') {
        $haki_nivel = '0';
    }

    $response[0] = array(
        'nombre' => $username,
        'haki' => $tipo_haki,
        'timestamp' => $timestamp
    );

    $db->query(" INSERT INTO `mybb_op_tirada_rey`(`uid`, `nombre`, `haki`, `subnombre`, `real`, `tirada_random`, `timestamp`) VALUES 
        ('$uid','$username','$tipo_haki','','1','$tirada_random', '$timestamp')");

    $new_hao_chance = strval(intval($hao_chance) - 1);

    $db->query(" UPDATE `mybb_op_fichas` SET `hao`='$haki_nivel',`hao_chance`='$new_hao_chance' WHERE fid='$uid'; ");

    echo json_encode($response); 
    return;
}

eval("\$page = \"".$templates->get("op_tirada_rey")."\";");
output_page($page);
