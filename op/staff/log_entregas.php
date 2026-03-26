<?php
/**
 * MyBB 1.8
 * Copyright 2014 MyBB Group, All Rights Reserved
 *
 * Website: http://www.mybb.com
 * License: http://www.mybb.com/about/license
 */

define("IN_MYBB", 1);
define('THIS_SCRIPT', 'log_entregas.php');
require_once "./../../global.php";
require "./../../inc/config.php";
require_once "./../functions/op_functions.php";

global $templates, $mybb;
$uid = $mybb->user['uid'];

if (is_mod($uid) || is_staff($uid) || is_narra($uid)) { 
    $logs = "";
    $query_log = $db->query("
        SELECT * FROM `mybb_op_audit_general`  
        WHERE categoria LIKE '%[Entregas]%'
        ORDER BY `mybb_op_audit_general`.`id` DESC
        LIMIT 200;
    ");
    while ($q = $db->fetch_array($query_log)) {
        $log = $q['log'];
        $id = $q['id'];
        $logs .= "<div id='$id'>$log<div><br>";
    }

    eval('$logs_li = $logs;');
    eval("\$page = \"".$templates->get("staff_log_entregas")."\";");
    output_page($page);
} else {
    eval("\$page = \"".$templates->get("sin_permisos")."\";");
    output_page($page);
}
