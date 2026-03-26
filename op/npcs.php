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
define('THIS_SCRIPT', 'npcs.php');
require_once "./../global.php";
require "./../inc/config.php";
require_once "./functions/op_functions.php";
global $templates, $mybb;

$query_npcs = $db->query(" SELECT * FROM `mybb_op_npcs` ORDER BY `mybb_op_npcs`.`nombre` ASC ");
$npcs = array();

$get_akuma = $_POST["get_akuma"];
$akuma_uid = $_POST["akuma_uid"];
$npc_input_id = $mybb->get_input('npc_id');

if ($get_akuma == 'true') {
    header('Content-type: application/json');
    $response = array();
    $timestamp = time();
    $akuma_post = null;

    if (substr($akuma_uid, 0, 3) == "???") {
        $response[0] = array(
            'nombre' => '¿?',
            'imagen' => '/images/op/uploads/AkumaMisteriosa_One_Piece_Gaiden_Foro_Rol.png',
            'timestamp' => $timestamp
        );
    } else {
        $query_akuma_post = $db->query(" SELECT * FROM mybb_op_akumas WHERE akuma_id='$akuma_uid' "); 
        while ($f = $db->fetch_array($query_akuma_post)) { $akuma_post = $f; }
    
        $response[0] = array(
            'nombre' => $akuma_post['nombre'],
            'imagen' => $akuma_post['imagen'],
            'timestamp' => $timestamp
        );
    }

    echo json_encode($response); 
    return;
}

while ($npc = $db->fetch_array($query_npcs)) {

    // if (substr($npc['faccion'], 0, 3) == "???") { $npc['faccion'] = '???'; }
    if (substr($npc['raza'], 0, 3) == "???") { $npc['raza'] = '???'; }
    if (substr($npc['altura'], 0, 3) == "???") { $npc['altura'] = '???'; }
    if (substr($npc['peso'], 0, 3) == "???") { $npc['peso'] = '???'; }
    if (substr($npc['sexo'], 0, 3) == "???") { $npc['sexo'] = '???'; }
    if (substr($npc['edad'], 0, 3) == "???") { $npc['edad'] = '???'; }
    if (substr($npc['temporada'], 0, 3) == "???") { $npc['temporada'] = '???'; }
    if (substr($npc['sangre'], 0, 3) == "???") { $npc['sangre'] = '???'; }
    if (substr($npc['rango'], 0, 3) == "???") { $npc['rango'] = '???'; }
    if (substr($npc['extra'], 0, 3) == "???") { $npc['extra'] = '???'; }

    if (substr($npc['nivel'], 0, 3) == "???") { $npc['nivel'] = '?'; }
    if (substr($npc['vitalidad'], 0, 3) == "???") { $npc['vitalidad'] = '???'; }
    if (substr($npc['energia'], 0, 3) == "???") { $npc['energia'] = '???'; }
    if (substr($npc['haki'], 0, 3) == "???") { $npc['haki'] = '???'; }


    if (substr($npc['avatar1'], 0, 3) == "???") { $npc['avatar1'] = '/images/op/misc/AvatarOculto_One_Piece_Gaiden_Foro_Rol.png'; }
    if (substr($npc['avatar2'], 0, 3) == "???") { $npc['avatar2'] = '/images/op/misc/WantePerfilOculto_One_Piece_Gaiden_Foro_Rol.png'; }

    if (substr($npc['apariencia'], 0, 3) == "???") { $npc['apariencia'] = '???'; }
    if (substr($npc['personalidad'], 0, 3) == "???") { $npc['personalidad'] = '???'; }
    if (substr($npc['historia1'], 0, 3) == "???") { $npc['historia1'] = '???'; }
    if (substr($npc['historia2'], 0, 3) == "???") { $npc['historia2'] = '???'; }
    if (substr($npc['historia3'], 0, 3) == "???") { $npc['historia3'] = '???'; }

    if (substr($npc['resistencia'], 0, 3) == "???") { $npc['resistencia'] = '???'; }
    if (substr($npc['fuerza'], 0, 3) == "???") { $npc['fuerza'] = '???'; }
    if (substr($npc['destreza'], 0, 3) == "???") { $npc['destreza'] = '???'; }
    if (substr($npc['agilidad'], 0, 3) == "???") { $npc['agilidad'] = '???'; }
    if (substr($npc['voluntad'], 0, 3) == "???") { $npc['voluntad'] = '???'; }
    if (substr($npc['control_akuma'], 0, 3) == "???") { $npc['control_akuma'] = '???'; }
    if (substr($npc['reflejos'], 0, 3) == "???") { $npc['reflejos'] = '???'; }
    if (substr($npc['punteria'], 0, 3) == "???") { $npc['punteria'] = '???'; }
    if (substr($npc['vitalidad'], 0, 3) == "???") { $npc['vitalidad'] = '???'; }
    if (substr($npc['energia'], 0, 3) == "???") { $npc['energia'] = '???'; }
    if (substr($npc['haki'], 0, 3) == "???") { $npc['haki'] = '???'; }

    if (substr($npc['hao'], 0, 3) == "???") { $npc['hao'] = '???'; }
    if (substr($npc['kenbun'], 0, 3) == "???") { $npc['kenbun'] = '???'; }
    if (substr($npc['buso'], 0, 3) == "???") { $npc['buso'] = '???'; }
    if (substr($npc['estilo_combate'], 0, 3) == "???") { $npc['estilo_combate'] = '???'; }

    array_push($npcs, $npc);
}

$npcs_json = json_encode($npcs);

eval("\$page = \"".$templates->get("op_npcs")."\";");
output_page($page); 

// if ($g_is_staff) {
//     eval("\$page = \"".$templates->get("op_npcs")."\";");
//     output_page($page); 
// } else {
//     $mensaje_redireccion = "No puedes acceder a esta página aún.";
//     eval("\$page = \"".$templates->get("op_redireccion")."\";");
//     output_page($page);
// }


