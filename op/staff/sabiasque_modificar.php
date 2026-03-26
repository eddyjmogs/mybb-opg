<?php
/**
 * MyBB 1.8
 * Copyright 2014 MyBB Group, All Rights Reserved
 *
 * Website: http://www.mybb.com
 * License: http://www.mybb.com/about/license
 */

define("IN_MYBB", 1);
define('THIS_SCRIPT', 'sabiasque_modificar.php');
require_once "./../../global.php";
require "./../../inc/config.php";
require_once "./../functions/op_functions.php";

global $templates, $mybb;
$uid = $mybb->user['uid'];
$username = $mybb->user['username'];
$sabiasque_id = $mybb->get_input('sabiasque_id'); 

$sabiasque_id_post = trim($_POST["sabiasque_id"]);
$tipo = addslashes($_POST["tipo"]);
$texto = addslashes($_POST["texto"]);
$autor = addslashes($_POST["autor"]);

$reload_js = "<script>window.location.href = window.location.pathname;</script>";
$sabiasque = null;
$existe = false;

$sabiasque_count = '0';
$sabiasques = array();
$query_sabiasque_count = $db->query(" SELECT COUNT(*) as count FROM `mybb_op_sabiasque`; ");
$query_sabiasques = $db->query(" SELECT * FROM `mybb_op_sabiasque`; ");

while ($q = $db->fetch_array($query_sabiasque_count)) {  $sabiasque_count = $q['count'];  }
while ($q = $db->fetch_array($query_sabiasques)) { array_push($sabiasques, $q); }
$sabiasques_json = json_encode($sabiasques); 

if ($sabiasque_id) {
    $query_sabiasque = $db->query("
        SELECT * FROM `mybb_op_sabiasque` WHERE id='$sabiasque_id'
    ");
    while ($t = $db->fetch_array($query_sabiasque)) {
        $sabiasque = $t;
        $existe = true;
    }
}

if ($sabiasque_id_post && $tipo && $texto && (is_mod($uid) || is_staff($uid))) {
    $log = "";


    if ($existe) {
        $db->query(" 
            UPDATE `mybb_op_sabiasque` SET `tipo`='$tipo',`texto`='$texto',`autor`='$autor' WHERE `id`='$sabiasque_id_post';
        ");
    } else {
        $db->query(" 
            INSERT INTO `mybb_op_sabiasque`(`id`, `tipo`, `texto`, `autor`) VALUES ('$sabiasque_id_post','$tipo','$texto','$autor')
        ");
    }

    eval('$log_var = $log;');
    eval('$reload_script = $reload_js;');
}

if (is_mod($uid) || is_staff($uid)) { 
    eval("\$page = \"".$templates->get("staff_sabiasque_modificar")."\";");
    output_page($page);
} else {
    eval("\$page = \"".$templates->get("sin_permisos")."\";");
    output_page($page);
}
