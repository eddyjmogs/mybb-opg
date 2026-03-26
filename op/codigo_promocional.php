<?php
/**
 * MyBB 1.8
 * Copyright 2014 MyBB Group, All Rights Reserved
 *
 * Website: http://www.mybb.com
 * License: http://www.mybb.com/about/license
 */

define("IN_MYBB", 1);
define('THIS_SCRIPT', 'codigo_promocional.php');
require_once "./../global.php";
require "./../inc/config.php";
require_once "./functions/op_functions.php";

global $templates, $mybb, $db;
$uid = $mybb->user['uid'];
$nombre = $mybb->user['username'];

if ($g_ficha['muerto'] == '1') {
    $mensaje_redireccion = "Estás muerto, no puedes acceder a esta página.";
    eval("\$page = \"".$templates->get("op_redireccion")."\";");
    output_page($page);
    return;
}


$codigo = addslashes($_POST["codigo"]);
$time_now = time();
$codigo_activo = get_obj_from_query($db->query("
    SELECT * FROM mybb_op_codigos_usuarios WHERE uid='$uid' AND expiracion > $time_now
"));

if ($codigo) {

    $codigo_admin = get_obj_from_query($db->query("
        SELECT * FROM mybb_op_codigos_admin WHERE codigo='$codigo' AND usado='0'
    "));

    if ($codigo_admin) {

        if (intval($codigo_admin['expiracion_codigo']) < time()) {
            $mensaje_redireccion = "El código promocional $codigo ya ha expirado, así que no se puede canjear nunca más.";
        } else {    

            $codigo_usuario = get_obj_from_query($db->query("
                SELECT * FROM mybb_op_codigos_usuarios WHERE uid='$uid' AND codigo='$codigo'
            "));

            if ($codigo_usuario) {
                $mensaje_redireccion = "El código promocional $codigo ya ha sido canjeado anteriormente, no puedes canjearlo otra vez.";
            } else if ($codigo_activo) {
                $mensaje_redireccion = "Ya tienes un código activado que todavía no ha expirado. Debes esperar que expire antes de activar otro código.";
            } else {
                $codigo_exacto = $codigo_admin['codigo'];
                $expiracion = time() + intval($codigo_admin['duracion']);
                $categoria = $codigo_admin['categoria'];

                $db->query(" 
                    INSERT INTO `mybb_op_codigos_usuarios` (`uid`, `nombre`, `codigo`, `categoria`, `expiracion`) VALUES ('$uid','$nombre','$codigo_exacto','$categoria','$expiracion')
                ");

                if ($codigo_admin['uso_unico'] == '1') {
                    $db->query(" 
                        UPDATE `mybb_op_codigos_admin` SET `usado`=1 WHERE codigo='$codigo'
                    ");
                }

                $mensaje_redireccion = "¡Tu código promocional $codigo_exacto ha sido canjeado!";
            }
        }
    } else {
        $mensaje_redireccion = "El código que has envíado es incorrecto, ha expirado o ya ha sido canjeado, qué triste eso :(";
    }

    eval("\$page = \"".$templates->get("op_redireccion")."\";");
    output_page($page);
} else {
    
    if ($uid == '0') {
        $mensaje_redireccion = "Tienes que estar logueado para enviar códigos de promoción.";
        eval("\$page = \"".$templates->get("op_redireccion")."\";");
        output_page($page);
    } else {
    
        $codigos_disponibles_query = $db->query("
            SELECT * FROM mybb_op_codigos_admin as a 
            WHERE a.codigo NOT IN (SELECT codigo FROM mybb_op_codigos_usuarios as u WHERE u.nombre = '$nombre') 
            AND uso_unico = '0' AND usado = '0' AND expiracion_codigo > $time_now
        ");

        $codigos_disponibles = "";

        while ($q = $db->fetch_array($codigos_disponibles_query)) {
            $codigo_admin = $q['codigo'];
            $expiracion_codigo = round((intval($q['expiracion_codigo']) - $time_now) / 86400, 2);
            $duracion = intval($q['duracion']) / 3600;
            $categoria = $q['categoria'];
            $codigos_disponibles .= "<span>Nombre de código: $codigo_admin - Categoría: $categoria - Duración: $duracion horas - Expira en: $expiracion_codigo días</span><br>";
        }

        eval("\$page = \"".$templates->get("op_codigo_promocional")."\";");
        output_page($page);
    }


}