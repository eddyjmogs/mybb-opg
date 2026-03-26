<?php
/**
 * MyBB 1.8
 * Copyright 2014 MyBB Group, All Rights Reserved
 *
 * Website: http://www.mybb.com
 * License: http://www.mybb.com/about/license
 */

define("IN_MYBB", 1);
define('THIS_SCRIPT', 'islas_modificar.php');
require_once "./../../global.php";
require "./../../inc/config.php";
require_once "./../functions/op_functions.php";

global $templates, $mybb;
$uid = $mybb->user['uid'];
$username = $mybb->user['username'];
$isla_id = $mybb->get_input('isla_id'); 

$isla_id_post = trim($_POST["isla_id"]);
$nuevo_isla_id = trim($_POST["nuevo_isla_id"]);

$nombre = addslashes($_POST["nombre"]);
$gobierno = addslashes($_POST["gobierno"]);
$faccion = addslashes($_POST["faccion"]);
$tamano = addslashes($_POST["tamano"]);
$comercio = addslashes($_POST["comercio"]);
$habitantes = addslashes($_POST["habitantes"]);
$zonas = addslashes($_POST["zonas"]);

$reload_js = "<script>window.location.href = window.location.pathname;</script>";
$isla = null;
$existe = false;

if ($isla_id) {
    $query_islas = $db->query("
        SELECT * FROM `mybb_op_islas` WHERE isla_id='$isla_id'
    ");
    while ($t = $db->fetch_array($query_islas)) {
        $isla = $t;
        $existe = true;
    }
}

if ($isla_id_post && $nuevo_isla_id && $nombre && (is_mod($uid) || is_staff($uid) || is_narra($uid))) {
    $log = "Cambio a isla ID $isla_id ($nombre).";

    if ($existe) {
        $db->query(" 
            UPDATE `mybb_op_islas` SET 
            `nombre`='$nombre',`gobierno`='$gobierno',`faccion`='$faccion',`tamano`='$tamano',`comercio`='$comercio',`habitantes`='$habitantes',`zonas`='$zonas'
            WHERE `isla_id`='$isla_id_post';
        ");
    } else {
        $db->query(" 
            INSERT INTO `mybb_op_islas` 
                (`isla_id`, `nombre`, `gobierno`, `faccion`, `tamano`, `comercio`, `habitantes`, `zonas`) VALUES 
                ('$isla_id_post', '$nombre','$gobierno','$faccion','$tamano','$comercio', '$habitantes', '$zonas'
            );
        ");

    }

    eval('$log_var = $log;');
    eval('$reload_script = $reload_js;');
}

if (is_mod($uid) || is_staff($uid) || is_narra($uid)) { 
    eval("\$page = \"".$templates->get("staff_islas_modificar")."\";");
    output_page($page);
} else {
    eval("\$page = \"".$templates->get("sin_permisos")."\";");
    output_page($page);
}
