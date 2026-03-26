<?php
/**
 * MyBB 1.8
 * Copyright 2014 MyBB Group, All Rights Reserved
 *
 * Website: http://www.mybb.com
 * License: http://www.mybb.com/about/license
 */

define("IN_MYBB", 1);
define('THIS_SCRIPT', 'tecnicas_aprender.php');
require_once "./../../global.php";
require "./../../inc/config.php";
require_once "./../functions/op_functions.php";

global $templates, $mybb;
$uid = $mybb->user['uid'];
$user_fid = $mybb->get_input('fid'); 
$user_accion = $mybb->get_input('accion'); 

$ficha_id = $_POST["ficha_id"];
$accion = $_POST["accion"];
$tecnicas = $_POST["tecnicas"];
$staff = $_POST["staff"];
$razon = $_POST["razon"];

$reload_js = "<script>window.location.href = window.location.pathname;</script>";

// Logica de añadir o remover técnicas para aprender de una ficha como Staff
if ($accion && $tecnicas && $staff && $razon && $ficha_id && (is_mod($uid) || is_staff($uid))) {

    $query_ficha = $db->query("
        SELECT * FROM mybb_op_fichas WHERE fid='$ficha_id'
    ");
    while ($f = $db->fetch_array($query_ficha)) {
        $f_var = $f;
    }

    // split comma delimited string to an array
    $tecnicas_array = preg_split('/(\s*,*\s*)*,+(\s*,*\s*)*/', trim($tecnicas));
    $log = "Cambios de técnicas para entrenar para ficha de UID: $ficha_id (" . $f_var['nombre'] . "):\n";
    foreach ($tecnicas_array as $tec) {
        $clean_tec = trim($tec);
        if ($clean_tec != "") {

            if ($accion == 'Añadir') {
                $db->query(" 
                    INSERT INTO `mybb_op_tec_para_aprender` (`tid`, `uid`) VALUES 
                    ('$clean_tec', '$ficha_id');
                ");
                $log_short = "Cambios de técnicas para aprender para ficha de UID: $ficha_id (" . $f_var['nombre'] . "):\n" . "-- $accion técnica ID: $tec\n";
                $log .= "-- $accion técnica ID: $tec\n";

                $db->query(" 
                    INSERT INTO `mybb_op_audit_consola_tec_mod` (`staff`, `razon`, `log`) VALUES 
                    ('$staff', '$razon', '$log_short');
                ");

            } else if ($accion == 'Remover') {
                $db->query(" 
                    DELETE FROM `mybb_op_tec_para_aprender` WHERE tid='$clean_tec' AND uid='$ficha_id'; 
                ");
                $log .= "-- $accion técnica ID: $tec\n";
                $log_short = "Cambios de técnicas para aprender para ficha de UID: $ficha_id (" . $f_var['nombre'] . "):\n" . "-- $accion técnica ID: $tec\n";

                $db->query(" 
                    INSERT INTO `mybb_op_audit_consola_tec_mod` (`staff`, `razon`, `log`) VALUES 
                    ('$staff', '$razon', '$log_short'); 
                ");      
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
    eval("\$page = \"".$templates->get("staff_tecnicas_aprender")."\";");
    output_page($page);
} else {
    eval("\$page = \"".$templates->get("sin_permisos")."\";");
    output_page($page);
}

