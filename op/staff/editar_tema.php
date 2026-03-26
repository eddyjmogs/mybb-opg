<?php
/**
 * MyBB 1.8
 * Copyright 2014 MyBB Group, All Rights Reserved
 *
 * Website: http://www.mybb.com
 * License: http://www.mybb.com/about/license
 */

define("IN_MYBB", 1);
define('THIS_SCRIPT', 'editar_tema.php');
require_once "./../../global.php";
require "./../../inc/config.php";
require_once "./../functions/op_functions.php";

global $templates, $mybb;
$uid = $mybb->user['uid'];
$username = $mybb->user['username'];
$accion = $mybb->get_input('accion');
$tid = addslashes($mybb->get_input('tid'));
$estacion = addslashes($mybb->get_input('estacion'));
$day = addslashes($mybb->get_input('dia'));
$year = addslashes($mybb->get_input('ano'));

$staff = "$uid";
$razon = 'Apertura';


// https://onepiecegaiden.com/op/editar_tema.php?tid=3533&estacion=Primavera&dia=421&ano=725

$thread = null;

if ($tid) {
    $query_thread = $db->query("
        SELECT * FROM `mybb_threads` WHERE tid='$tid'
    ");
    while ($q = $db->fetch_array($query_thread)) {
        // $tecnica = $t;
        $thread = $q;
    }
}

if ($tid && $estacion && $day && $year && (is_mod($uid) || is_staff($uid))) {

    $db->query(" 
        UPDATE `mybb_threads` SET `year`='$year', `estacion`='$estacion',`day`='$day' WHERE `tid`='$tid';
    ");


    $log = "El staff $username ha editado el tema con ID $tid, estableciendo la estación a $estacion, el día a $day y el año a $year.\n";

    $db->query(" 
        INSERT INTO `mybb_op_audit_consola_mod` (`staff`, `username`, `razon`, `log`) VALUES 
        ('$staff', '$username', '$razon', '$log');
    ");

    echo($log);
    // return;
    // echo("<script>alert(`$log`);window.location.href = 'https://onepiecegaiden.com/op/staff/consola_mod.php;</script>");
    return;
} 

if (is_mod($uid) || is_staff($uid)) {
    eval("\$page = \"".$templates->get("staff_editar_tema")."\";");
    output_page($page);
} else {
    $mensaje_redireccion = "No tienes acceso para entrar a esta página. ¿Seguro no te perdiste?";
    eval("\$page = \"".$templates->get("op_redireccion")."\";");
    output_page($page);
}
