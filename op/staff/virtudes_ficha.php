<?php
/**
 * MyBB 1.8
 * Copyright 2014 MyBB Group, All Rights Reserved
 *
 * Website: http://www.mybb.com
 * License: http://www.mybb.com/about/license
 */

define("IN_MYBB", 1);
define('THIS_SCRIPT', 'ficha_virtudes.php');
require_once "./../../global.php";
require "./../../inc/config.php";
require_once "./../functions/op_functions.php";

global $templates, $mybb;
$uid = $mybb->user['uid'];
$user_fid = $mybb->get_input('fid'); 
$user_accion = $mybb->get_input('accion'); 

$ficha_id = $_POST["ficha_id"];
$accion = $_POST["accion"];
$virtudes = $_POST["virtudes"];
$staff = $_POST["staff"];
$razon = $_POST["razon"];

$reload_js = "<script>window.location.href = window.location.pathname;</script>";

if ($accion && $virtudes && $staff && $razon && $ficha_id && (is_mod($uid) || is_staff($uid))) {

    $query_ficha = $db->query("
        SELECT * FROM mybb_op_fichas WHERE fid='$ficha_id'
    ");
    while ($f = $db->fetch_array($query_ficha)) {
        $f_var = $f;
    }

    // split comma delimited string to an array
    $virtudes_array = preg_split('/(\s*,*\s*)*,+(\s*,*\s*)*/', trim($virtudes));
    $log = "Cambios de virtudes para ficha de UID: $ficha_id (" . $f_var['nombre'] . "):\n";
    foreach ($virtudes_array as $virtud) {
        $clean_virtud = trim($virtud);
        if ($clean_virtud != "") {

            if ($accion == 'Añadir') {

                $db->query(" 
                    INSERT INTO `mybb_op_virtudes_usuarios` (`virtud_id`, `uid`) VALUES 
                    ('$clean_virtud', '$ficha_id');
                ");

                $log_short = "Cambios de virtudes para ficha de UID: $ficha_id (" . $f_var['nombre'] . "):\n" . "-- $accion virtud ID: $virtud\n";
                $log .= "-- $accion virtud ID: $virtud\n";

            } else if ($accion == 'Remover') {
                $db->query(" 
                    DELETE FROM `mybb_op_virtudes_usuarios` WHERE virtud_id='$clean_virtud' AND uid='$ficha_id'; 
                ");
                $log .= "-- $accion virtud ID: $virtud\n";
                $log_short = "Cambios de virtudes para ficha de UID: $ficha_id (" . $f_var['nombre'] . "):\n" . "-- $accion virtud ID: $virtud\n";
                
            }
        }
    }

    eval('$log_var = $log;');
    eval('$reload_script = $reload_js;');
}

if (is_mod($uid) || is_staff($uid)) { 
    if ($user_fid != '') {
        $query_ficha = $db->query("
            SELECT * FROM mybb_op_fichas WHERE fid='$user_fid'
        ");
        while ($f = $db->fetch_array($query_ficha)) {
            $f_var = $f;
            eval('$ficha = $f_var;');
        }
    }

    eval('$fid = $user_fid;');
    eval('$accion = $user_accion;');
    eval("\$page = \"".$templates->get("staff_virtudes_ficha")."\";");
    output_page($page);
} else {
    $redireccion = "No tienes permisos para ver esta página.";
    eval("\$page = \"".$templates->get("op_redireccion")."\";");
    output_page($page);
}

