<?php
/**
 * MyBB 1.8
 * Copyright 2014 MyBB Group, All Rights Reserved
 *
 * Website: http://www.mybb.com
 * License: http://www.mybb.com/about/license
 */

define("IN_MYBB", 1);
define('THIS_SCRIPT', 'salario_staff.php');
require_once "./../../global.php";
require "./../../inc/config.php";
require_once "./../functions/op_functions.php";

global $templates, $mybb;
$uid = $mybb->user['uid'];
$accion = $mybb->get_input('accion');
$accion_post = $_POST["accion_post"];

$username = $mybb->user['username'];
$reload_js = "<script>window.location.href = window.location.pathname;</script>";

$salario = $_POST["salario"];
$staff_id_post = $_POST["staff_id_post"];
$kuros = $_POST["kuros"];
$expe = $_POST["expe"];


if ($staff_id_post != '' && $kuros != '' && (is_mod($uid) || is_staff($uid))) {

    $staff_id_array = preg_split('/(\s*,*\s*)*,+(\s*,*\s*)*/', trim($staff_id_post));
    $nombres_id = "";
    foreach ($staff_id_array as $fid) {
        $fid = trim($fid);

        if ($fid != "") {

            $ficha_mod = null;

            $query_fid = $db->query(" SELECT * FROM mybb_op_fichas WHERE fid='$fid'; ");
            while ($q = $db->fetch_array($query_fid)) {
                $ficha_mod = $q;
            }

            $nombre = $ficha_mod['nombre'];
            $nombres_id .= "$nombre (<a href=\'/op/ficha.php?uid=$fid\'>$fid</a>), ";

            $new_kuros = intval($ficha_mod['kuro']) + $kuros;
            
            // $db->query(" UPDATE `mybb_op_fichas` SET `kuro`='$new_kuros' WHERE fid='$fid'; ");
            log_audit_currency($uid, $username, $fid, '[Kuros][Salario Staff]', 'kuros', $new_kuros);
            // $db->query(" UPDATE `mybb_op_fichas` SET `kuro`=`kuro` + $kuros WHERE fid='$fid'; ");
        }
     
    }

    $nombres_id = rtrim($nombres_id, ", ");

    $fechaHora = date("Y-m-d H:i:s");
    $log = "[Salario Kuros] Kuros entregados ($kuros kuros) por $username ($uid) a las: " . $fechaHora . "</br>IDs de usuarios: " . $staff_id_post . "</br>Nombres: $nombres_id</br>";

    $db->query(" 
        INSERT INTO `mybb_op_audit_consola_mod` (`staff`, `username`, `razon`, `log`) VALUES 
        ('$staff', '$username', '$razon', '$log');
    ");
    
    eval('$log_var = $log;');
    eval('$reload_script = $reload_js;');
}

if ($staff_id_post != '' && $expe != '' && (is_mod($uid) || is_staff($uid))) {
    
    $timeSinceOPGOpened = time() - 1721620800 - 3600; // 3600 por una hora de retraso
    $weekNumber = ceil($timeSinceOPGOpened / 604800);
	

    $staff_id_array = preg_split('/(\s*,*\s*)*,+(\s*,*\s*)*/', trim($staff_id_post));
    $nombres_id = "";
    foreach ($staff_id_array as $fid) {
        $fid = trim($fid);

        if ($fid != "") {

            $query_fid = $db->query(" SELECT * FROM mybb_op_fichas WHERE fid='$fid'; ");
            while ($q = $db->fetch_array($query_fid)) {
                $nombre = $q['nombre'];
                $nombres_id .= "$nombre (<a href=\'/op/ficha.php?uid=$fid\'>$fid</a>), ";
            }

            $doesLimitExistByWeek = false;
            $experienciaSemanal = 0;

            $experiencia_limite_query = $db->query(" SELECT * FROM mybb_op_experiencia_limite WHERE uid='$fid' AND semana='$weekNumber'; ");
            while ($q = $db->fetch_array($experiencia_limite_query)) {
                $doesLimitExistByWeek = true;
                $experienciaSemanal = $q['experiencia_semanal'];
            }

            if ($doesLimitExistByWeek == false) {
                $db->query(" 
                    INSERT INTO `mybb_op_experiencia_limite`(`uid`, `semana`, `experiencia_semanal`) VALUES ('$fid','$weekNumber','100')
                ");
                $db->query(" UPDATE `mybb_users` SET `newpoints`=`newpoints` + 100 WHERE uid='$fid'; ");
            } else {
                $db->query(" 
                    INSERT INTO `mybb_op_experiencia_limite`(`uid`, `semana`, `experiencia_semanal`) VALUES ('$fid','$weekNumber','100')
                ");

                $newExp = 100 - intval($experienciaSemanal);

                $db->query(" UPDATE `mybb_users` SET `newpoints`=`newpoints` + $newExp WHERE uid='$fid'; ");
            }

        }
     
    }

    $nombres_id = rtrim($nombres_id, ", ");

    $fechaHora = date("Y-m-d H:i:s");
    $log = "[Salario Experiencia] Experiencia semanal entregada (100 experiencia) por $username ($uid) a las: " . $fechaHora . "</br>IDs de usuarios: " . $staff_id_post . "</br>Nombres: $nombres_id</br>";


    $db->query(" 
        INSERT INTO `mybb_op_audit_consola_mod` (`staff`, `username`, `razon`, `log`) VALUES 
        ('$staff', '$username', '$razon', '$log');
    ");
    
    eval('$log_var = $log;');
    eval('$reload_script = $reload_js;');
}

if (is_mod($uid) || is_staff($uid)) { 

    $salarios_kuros = '';
    $salarios_expe = '';

    $query_salarios = $db->query("
        SELECT * FROM mybb_op_audit_consola_mod WHERE log LIKE '%[Salario Kuros]%' OR log LIKE '%[Salario Experiencia]%' ORDER BY tiempo DESC LIMIT 30;
    ");
    while ($q = $db->fetch_array($query_salarios)) {
        
        $salarios_kuros .= $q['log'];
        $salarios_kuros .= "</br>";
    }
    // $query_expe = $db->query("
    //     SELECT * FROM mybb_op_audit_consola_mod WHERE log LIKE '%[Salario Experiencia]%' ORDER BY tiempo DESC;
    // ");
    // while ($q = $db->fetch_array($query_expe)) {
        
    //     $salarios_expe .= $q['log'];
    //     $salarios_expe .= "</br>";
    // }
    
    eval("\$page = \"".$templates->get("staff_salario_staff")."\";");
    output_page($page);
} else {
    eval("\$page = \"".$templates->get("sin_permisos")."\";");
    output_page($page);
}
