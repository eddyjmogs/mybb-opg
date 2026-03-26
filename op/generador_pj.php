<?php
/**
 * MyBB 1.8
 * Copyright 2014 MyBB Group, All Rights Reserved
 *
 * Websittp://www.mybb.com
 * License: http://www.mybb.com/about/license
 */

define("IN_MYBB", 1);
define('THIS_SCRIPT', 'generadorpj.php');
require_once "./../../global.php";
require "./../../inc/config.php";
require_once "./../functions/op_functions.php";

global $templates, $mybb;
$uid = $mybb->user['uid'];
$username = $mybb->user['username'];
$accion = $mybb->get_input('accion');
$peti_id = $mybb->get_input('peti_id');

$reload_js = "<script>window.location.href = window.location.pathname;</script>";

if ($accion == 'resolver' && $peti_id) {

    $db->query(" 
        UPDATE `mybb_op_avisos` SET `resuelto`=1, `mod_uid`='$uid', `mod_nombre`='$username' WHERE id='$peti_id'
    ");

    eval('$reload_script = $reload_js;');
} else if ($accion == 'borrar' && $peti_id) {
    $db->query("
        DELETE FROM mybb_op_avisos WHERE uid='$peti_id';
    ");
    eval('$reload_script = $reload_js;');
}

if (is_mod($uid) || is_staff($uid) || is_user($uid)) { 

    $query_virtudes = $db->query(" SELECT * FROM `mybb_op_virtudes` WHERE virtud_id LIKE 'V%' ORDER BY `mybb_op_virtudes`.`nombre` ASC; ");
    $query_defectos = $db->query(" SELECT * FROM `mybb_op_virtudes` WHERE virtud_id LIKE 'D%' ORDER BY `mybb_op_virtudes`.`nombre` ASC; ");
    
    $virtudes = array();
    $defectos = array();
    
    while ($virtud = $db->fetch_array($query_virtudes)) { array_push($virtudes, $virtud); }
    while ($defecto = $db->fetch_array($query_defectos)) { array_push($defectos, $defecto); }

    $virtudes_json = json_encode($virtudes);
    $defectos_json = json_encode($defectos);

    $query_objetos = $db->query(" SELECT * FROM `mybb_op_objetos` WHERE custom='0' ORDER BY categoria, subcategoria, tier, nombre ");
    $objetos = array();
    $objetos_array = array();

    while ($q = $db->fetch_array($query_objetos)) { 
        $objeto_id = $q['objeto_id'];
        $key = "$objeto_id";
        if (!$objetos[$key]) { $objetos[$key] = array(); }
        array_push($objetos[$key], $q);
        array_push($objetos_array, $objeto_id);
    }

    $objetos_array_json = json_encode($objetos_array);
    $objetos_json = json_encode($objetos);

    $query_tecnicas = $db->query(" SELECT tid, nombre FROM mybb_op_tecnicas WHERE rama='Espadachín' ORDER BY rama ASC ");
    $tecnicas = array();
    $tecnicas_array = array();

     while ($q = $db->fetch_array($query_tecnicas)) { 
        $tid = $q['tid'];
        $key = "$tid";
        if (!$tecnicas[$key]) { $tecnicas[$key] = array(); }
        array_push($tecnicas[$key], $q);
        array_push($tecnicas_array, $tid);
    }

    $tecnicas_array_json = json_encode($tecnicas_array);
    $tecnicas_json = json_encode($tecnicas);

    $tecnicas_contenido = ''; // Placeholder, ajustar si es necesario

    eval("\$page = \"".$templates->get("staff_generadorpj")."\";");
    output_page($page);
} else {
    $mensaje_redireccion = "No tienes acceso para entrar a esta página. ¿Seguro no te perdiste?";
    eval("\$page = \"".$templates->get("op_redireccion")."\";");
    output_page($page);
}

// $mensaje_redireccion = "No tienes acceso para entrar a esta página. ¿Seguro no te perdiste?";
// eval("\$page = \"".$templates->get("op_redireccion")."\";");
// output_page($page);
