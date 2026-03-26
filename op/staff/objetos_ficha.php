<?php
/**
 * MyBB 1.8
 * Copyright 2014 MyBB Group, All Rights Reserved
 *
 * Website: http://www.mybb.com
 * License: http://www.mybb.com/about/license
 */

define("IN_MYBB", 1);
define('THIS_SCRIPT', 'ficha_objetos.php');
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
$objetos = strtoupper($_POST["objetos"]);
// $staff = $_POST["staff"];
$staff = $uid;
$razon = $_POST["razon"];

$reload_js = "<script>window.location.href = window.location.pathname;</script>";

if ($accion && $objetos && $staff && $razon && $ficha_id && (is_mod($uid) || is_staff($uid))) {

    $query_ficha = $db->query("
        SELECT * FROM mybb_op_fichas WHERE fid='$ficha_id'
    ");
    while ($f = $db->fetch_array($query_ficha)) {
        $f_var = $f;
    }

    // split comma delimited string to an array
    $objetos_array = preg_split('/(\s*,*\s*)*,+(\s*,*\s*)*/', trim($objetos));
    $log = "Cambios de objetos para ficha de UID: $ficha_id (" . $f_var['nombre'] . "):\n";
    foreach ($objetos_array as $obj) {
        $clean_obj = trim($obj);
        if ($clean_obj != "") {
            
            $has_objeto = false;
                $inventario_actual = $db->query("SELECT * FROM mybb_op_inventario WHERE uid='$ficha_id' AND objeto_id='$clean_obj'");
                
                while ($q = $db->fetch_array($inventario_actual)) {
                    $has_objeto = true;
                    $cantidad = $q['cantidad'];
                }           

            if ($accion == 'Añadir') {

                /*$has_objeto = false;
                $inventario_actual = $db->query("SELECT * FROM mybb_op_inventario WHERE uid='$ficha_id' AND objeto_id='$clean_obj'");
                
                while ($q = $db->fetch_array($inventario_actual)) {
                    $has_objeto = true;
                    $cantidad = $q['cantidad'];
                }*/

                if ($has_objeto) {
                    $nueva_cantidad = intval($cantidad) + 1;
                    $db->query(" 
                        UPDATE `mybb_op_inventario` SET `cantidad`='$nueva_cantidad' WHERE objeto_id='$clean_obj' AND uid='$ficha_id'
                    ");
                } else {
                    $db->query(" 
                        INSERT INTO `mybb_op_inventario` (`objeto_id`, `uid`, `cantidad`) VALUES 
                        ('$clean_obj', '$ficha_id', '1');
                    ");
                }

                $log_short = "Cambios de objetos para ficha de UID: $ficha_id (" . $f_var['nombre'] . "):\n" . "-- $accion objeto ID: $obj\n";
                $log .= "-- $accion objeto ID: $obj\n";

            } else if ($accion == 'Remover') {
                /*$db->query(" 
                    DELETE FROM `mybb_op_inventario` WHERE objeto_id='$clean_obj' AND uid='$ficha_id'; 
                ");*/
                
                /*$has_objeto = false;
                $inventario_actual = $db->query("SELECT * FROM mybb_op_inventario WHERE uid='$ficha_id' AND objeto_id='$clean_obj'");
                
                while ($q = $db->fetch_array($inventario_actual)) {
                    $has_objeto = true;
                    $cantidad = $q['cantidad'];
                }*/           

                if ($has_objeto) {
                    $nueva_cantidad = intval($cantidad) - 1;
                    if($nueva_cantidad > 0){
                    $db->query(" 
                        UPDATE `mybb_op_inventario` SET `cantidad`='$nueva_cantidad' WHERE objeto_id='$clean_obj' AND uid='$ficha_id'
                    ");
                    
                } else {
                    $db->query(" 
                    DELETE FROM `mybb_op_inventario` WHERE objeto_id='$clean_obj' AND uid='$ficha_id'; 
                ");              
                }
                }
                
                
                $log .= "-- $accion objeto ID: $obj\n";
                $log_short = "Cambios de objetos para ficha de UID: $ficha_id (" . $f_var['nombre'] . "):\n" . "-- $accion objeto ID: $obj\n". $nueva_cantidad;
                
            }
        }
    }

    log_audit($uid, $username, '[Objetos][Ficha]', $log);

    $db->query(" 
        INSERT INTO `mybb_op_audit_consola_mod` (`staff`, `username`, `razon`, `log`) VALUES 
        ('$staff', '$username', '$razon', '$log');
    ");

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
    eval("\$page = \"".$templates->get("staff_objetos_ficha")."\";");
    output_page($page);
} else {
    $redireccion = "No tienes permisos para ver esta página.";
    eval("\$page = \"".$templates->get("op_redireccion")."\";");
    output_page($page);
}

