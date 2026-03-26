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
define('THIS_SCRIPT', 'akumas.php');
require_once "./../global.php";
require "./../inc/config.php";
require_once "./functions/op_functions.php";
global $templates, $mybb;


$uid = $mybb->user['uid'];
$registro = "";

$query_akumas = $db->query(" SELECT * FROM `mybb_op_akumas` ORDER BY `mybb_op_akumas`.`nombre` ASC ");
$ficha = null;
$counter = 0;

$akumas = array();

$akuma_aleatoria = $_POST["akuma_aleatoria"];
$get_ficha = $_POST["get_ficha"];
$get_npc = $_POST["get_npc"];
$uid_post = $_POST["uid_post"];

$query_ficha = $db->query(" SELECT * FROM mybb_op_fichas WHERE fid='$uid' "); 
while ($f = $db->fetch_array($query_ficha)) { $ficha = $f; }

while ($akuma = $db->fetch_array($query_akumas)) {
    array_push($akumas, $akuma);
}

$akumas_json = json_encode($akumas);

$akuma_magica_query = $db->query(" SELECT * FROM `mybb_op_akumas` WHERE ocupada = 0 ORDER BY RAND() LIMIT 1 ");
$akuma_magica_query2 = $db->query(" SELECT * FROM `mybb_op_akumas` WHERE ocupada = 0 ORDER BY RAND() LIMIT 1 ");

// SELECT * FROM `mybb_op_akumas` WHERE ocupada=0 AND (tier=5 OR tier=6) AND akuma_id!='gasu_gasu' AND akuma_id!='hito_buda' AND akuma_id != 'kage_kage' AND akuma_id != 'soru_soru' AND akuma_id != 'inu_kyubi' AND akuma_id != 'hebi_yama' AND akuma_id != 'mori_mori' AND akuma_id != 'numa_numa' AND akuma_id != 'shiku_shiku' AND akuma_id != 'yuki_yuki' ORDER BY RAND() LIMIT 1;

$akuma_magica = '';
$akuma_magica2 = '';
$akuma_magica_descripcion = '';

while ($q = $db->fetch_array($akuma_magica_query)) { 
    $akuma_magica = $q['nombre'];
    $akuma_magica_descripcion = $q['descripcion'];
    $akuma_categoria = $q['categoria'];
    $akuma_tier = $q['tier'];
}

while ($q = $db->fetch_array($akuma_magica_query2)) { 
    $akuma_magica2 = $q['nombre'];
}


if ($get_ficha == 'true') {
    header('Content-type: application/json');
    $response = array();
    $timestamp = time();
    $ficha_post = null;
    $user_post = null;

    $query_ficha_post = $db->query(" SELECT * FROM mybb_op_fichas WHERE fid='$uid_post' "); 
    $query_user_post = $db->query(" SELECT * FROM mybb_users WHERE uid='$uid_post' "); 

    while ($q = $db->fetch_array($query_ficha_post)) { $ficha_post = $q; }
    while ($q = $db->fetch_array($query_user_post)) { $user_post = $q; }

    if ($user_post['avatar'] == '') {
        $user_post['avatar'] = '/images/default_avatar.png';
    }

    $response[0] = array(
        'avatar' => $user_post['avatar'],
        'nombre' => $ficha_post['nombre'],
        'timestamp' => $timestamp
    );

    echo json_encode($response); 
    return;
}


if ($get_npc == 'true') {
    header('Content-type: application/json');
    $response = array();
    $timestamp = time();
    $ficha_post = null;

    $query_ficha_post = $db->query(" SELECT * FROM mybb_op_npcs WHERE npc_id='$uid_post' "); 

    while ($f = $db->fetch_array($query_ficha_post)) { $ficha_post = $f; }

    if ($ficha_post == null) {
        $response[0] = array(
            'nombre' => '???',
            'avatar' => '/images/op/misc/WantePerfilOculto_One_Piece_Gaiden_Foro_Rol.png',
            'timestamp' => $timestamp
        );
    } else {
        $response[0] = array(
            'avatar' => $ficha_post['avatar2'],
            'nombre' => $ficha_post['nombre'],
            'timestamp' => $timestamp
        );
    }

    echo json_encode($response); 
    return;
}


eval("\$page = \"".$templates->get("op_akuma")."\";");
output_page($page);
