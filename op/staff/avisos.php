<?php
/**
 * MyBB 1.8
 * Copyright 2014 MyBB Group, All Rights Reserved
 *
 * Website: http://www.mybb.com
 * License: http://www.mybb.com/about/license
 */

define("IN_MYBB", 1);
define('THIS_SCRIPT', 'avisos.php');
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
} else if ($action == 'borrar' && $peti_id) {
    $db->query("
        DELETE FROM mybb_op_avisos WHERE uid='$peti_id';
    ");
    eval('$reload_script = $reload_js;');
}

if (is_mod($uid) || is_staff($uid) || is_user($uid)) { 
    $peticiones_li = "";

    // $borrar_a = "$url_page?accion=borrar&peti_id=$pid";

    function print_peticion($nombre_categoria, $categoria, $uid) {
        global $db;
        $query_peticion = $db->query("
            SELECT * FROM mybb_op_avisos
            WHERE resuelto=0 AND categoria='$categoria'
        ");

        $peticiones_li = "";
        while ($q = $db->fetch_array($query_peticion)) {
            $pid = $q['id'];
            $categoria = $q['categoria'];
            $resumen = $q['resumen'];
            $descripcion =  nl2br($q['descripcion']);
            $url = $q['url'];
            $nombre = $q['nombre'];
            $u_uid = $q['uid'];

            $enviado = $q['enviado'];
            $categoria2 = "";
            $givenDate = new DateTime($enviado);
            $currentDate = new DateTime();
            $interval = $currentDate->diff($givenDate);
            $fecha = "Hace " . $interval->days . " días.";
            
            $peticiones_li .= "<li>";
            $peticiones_li .= "[<a target='_blank' href='/op/ficha.php?uid=$u_uid'>$nombre - $u_uid</a>] <br> <strong>Resumen</strong>: $resumen <br> <strong>Descripción</strong>: $descripcion <br> <strong>URL</strong>: <a target='_blank' href='$url'>$url</a><br> <strong>Fecha</strong>: $enviado - $fecha <br>";
            
            if (is_mod($uid) || is_staff($uid)) {
                $url_page = "/op/staff/avisos.php";
                $resolver_a = "$url_page?accion=resolver&peti_id=$pid";
                $peticiones_li .= "<span><a href='$resolver_a' >Resolver</a></span>";
            }
            // $peticiones_li .= "<span><a href='$borrar_a' target='_blank'>Borrar</a></span>";
            $peticiones_li .= "</li><br>";
        
        }

        if ($peticiones_li != "") {
            return "<h3>$nombre_categoria</h3>" . $peticiones_li;
        } else {
            return "";
        }
    }

    $peticiones_li .= print_peticion('Intercambios', 'intercambio', $uid);
    $peticiones_li .= print_peticion('Viajes', 'viaje', $uid);

    eval("\$page = \"".$templates->get("staff_avisos_mod")."\";");
    output_page($page);
} else {
    $mensaje_redireccion = "No tienes acceso para entrar a esta página. ¿Seguro no te perdiste?";
    eval("\$page = \"".$templates->get("op_redireccion")."\";");
    output_page($page);
}
