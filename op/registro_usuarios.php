<?php
/**
 * MyBB 1.8
 * Copyright 2014 MyBB Group, All Rights Reserved
 *
 * Website: http://www.mybb.com
 * License: http://www.mybb.com/about/license
 *
 */

define("IN_MYBB", 1);
define('THIS_SCRIPT', 'registro_usuarios.php');
require_once "./../global.php";
require "./../inc/config.php";
require_once "./functions/op_functions.php";
global $templates, $mybb;

// $query_ficha = $db->query(" SELECT * FROM `mybb_op_fichas` ORDER BY `mybb_op_fichas`.`nombre` ASC; ");

$query_ficha = $db->query(" 
    SELECT f.*, u.avatar as avatar FROM `mybb_op_fichas` as f 
    INNER JOIN mybb_users as u ON f.fid = u.uid
    WHERE f.faccion != 'Staff'
    ORDER BY f.`nombre`;
");


$npcs = array();
$npcs_activos = array();
$timestamp = time() - (14 * 24 * 3600);
        
function queryUsersTimestamp() {
    global $db, $timestamp;

    return $db->query("
        SELECT * FROM (SELECT DISTINCT fichas.fid, fichas.nombre, p.username FROM mybb_posts as p 
        INNER JOIN mybb_threads as t ON p.tid = t.tid 
        INNER JOIN mybb_forums as f ON t.fid = f.fid 
        INNER JOIN mybb_op_fichas as fichas ON p.uid = fichas.fid 
        WHERE fichas.faccion != 'Staff' AND p.dateline > $timestamp
        AND f.parentlist LIKE '10,%'
        ORDER BY p.username) t;
    ");
}

$queryUsers = queryUsersTimestamp();

while ($npc = $db->fetch_array($queryUsers)) {
    array_push($npcs_activos, $npc);
}

while ($npc = $db->fetch_array($query_ficha)) {
    array_push($npcs, $npc);
}

$npcs_json = json_encode($npcs);
$npcs_activos_json = json_encode($npcs_activos);


eval("\$page = \"".$templates->get("op_registro_usuarios")."\";");
output_page($page);
