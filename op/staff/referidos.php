<?php
/**
 * MyBB 1.8
 * Copyright 2014 MyBB Group, All Rights Reserved
 *
 * Website: http://www.mybb.com
 * License: http://www.mybb.com/about/license
 */

define("IN_MYBB", 1);
define('THIS_SCRIPT', 'referidos.php');
require_once "./../../global.php";
require "./../../inc/config.php";
require_once "./../functions/op_functions.php";

global $templates, $mybb;
$uid = $mybb->user['uid'];
$username = $mybb->user['username'];
$user_fid = $mybb->get_input('fid'); 
$accion = $mybb->get_input('accion'); 

$ficha_id = $_POST["ficha_id"];
$ref_id = $_POST["ref_id"];

$ficha = null;
if ($user_fid != '') {
    $query_ficha = $db->query(" SELECT * FROM mybb_op_fichas WHERE fid='$user_fid' ");
    while ($f = $db->fetch_array($query_ficha)) { $ficha = $f; }
}

// SELECT uid, username, referrer, referrals FROM `mybb_users` WHERE uid=10 OR uid=154;
// SELECT uid, username, referrer, referrals from `mybb_users` WHERE referrer=10;

$reload_js = "";

$referidos = '';

if ($accion && $ref_id && $ficha_id && (is_mod($uid) || is_staff($uid))) {
    $log = "[Referidos] Cambios de referidos para ficha de UID: $ficha_id. Acción: $accion. Referido: $ref_id\n";

    if ($accion == 'Añadir') {

        $db->query(" UPDATE `mybb_users` SET `referrer`='$ficha_id' WHERE uid='$ref_id' ");
        $db->query(" UPDATE `mybb_users` SET `referrals`=`referrals` + 1 WHERE uid='$ficha_id' ");

        
    } else if ($accion == 'Remover') {
        $db->query(" UPDATE `mybb_users` SET `referrer`='0' WHERE uid='$ref_id' ");
        $db->query(" UPDATE `mybb_users` SET `referrals`=`referrals` - 1 WHERE uid='$ficha_id' ");
    }

    $db->query(" 
        INSERT INTO `mybb_op_audit_consola_mod` (`staff`, `username`, `razon`, `log`) VALUES 
        ('Staff', '$username', '', '$log');
    ");

    $reload_js = "<script>window.location.href = window.location.pathname;</script>";
    
}

if ($user_fid) {
    $query_referrer = $db->query(" SELECT uid, username, referrer, referrals from `mybb_users` WHERE referrer='$user_fid'; ");
    while ($q = $db->fetch_array($query_referrer)) { 
        $r_uid = $q['uid'];
        $r_username = $q['username'];
        $referidos .= "[<a href='/op/ficha.php?uid=$r_uid'>$r_uid</a>] $r_username<br>";
    }
}

if (is_mod($uid) || is_staff($uid)) { 

    eval("\$page = \"".$templates->get("staff_referidos")."\";");
    output_page($page);
} else {
    eval("\$page = \"".$templates->get("sin_permisos")."\";");
    output_page($page);
}
