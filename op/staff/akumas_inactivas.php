<?php
/**
 * MyBB 1.8
 * Copyright 2014 MyBB Group, All Rights Reserved
 *
 * Website: http://www.mybb.com
 * License: http://www.mybb.com/about/license
 */

define("IN_MYBB", 1);
define('THIS_SCRIPT', 'akumas_inactivas.php');
require_once "./../../global.php";
require "./../../inc/config.php";
require_once "./../functions/op_functions.php";

global $templates, $mybb;
$uid = $mybb->user['uid'];
$username = $mybb->user['username'];

$reload_js = "<script>window.location.href = window.location.pathname;</script>";

$akumas = null;
$last_two_months = time() - (61 * 24 * 3600);

$query_akumas = $db->query(" 
    SELECT 
        akumas.akuma_id, 
        akumas.nombre, 
        akumas.subnombre, 
        akumas.uid,
        fichas.nombre as nombre_usuario,
        (
            SELECT MAX(p.dateline) 
            FROM mybb_posts AS p 
            INNER JOIN mybb_threads AS t ON p.tid = t.tid 
            INNER JOIN mybb_forums AS f ON t.fid = f.fid 
            WHERE p.uid = fichas.fid 
            AND (f.parentlist LIKE '10,%' OR f.parentlist LIKE '%246,%')
        ) AS last_post
    FROM mybb_op_akumas AS akumas
    INNER JOIN mybb_op_fichas AS fichas ON akumas.subnombre = fichas.akuma_subnombre
    WHERE akumas.uid != 1 
    AND akumas.es_npc = 0 
    AND akumas.uid != '0' 
    AND akumas.uid NOT LIKE '%NPC%'
    ORDER BY akumas.akuma_id ASC; ");

$akumas = array();
$akumas_expire_15 = array();
$akumas_expire_30 = array();
$akumas_inactive = array();
$threshold = $last_two_months;
while ($q = $db->fetch_array($query_akumas)) {
    $q['last_post'] = intval($q['last_post']);
    // If no posts, last_post will be 0
    if ($q['last_post'] == 0) { $q['last_post'] = 0; }
    // classify according to threshold
    if ($q['last_post'] <= $threshold) {
        array_push($akumas_inactive, $q);
    } else if ($q['last_post'] <= ($threshold + (15 * 24 * 3600))) {
        array_push($akumas_expire_15, $q);
    } else if ($q['last_post'] <= ($threshold + (30 * 24 * 3600))) {
        array_push($akumas_expire_30, $q);
    }
    array_push($akumas, $q);
}
$akumas_json = json_encode($akumas_inactive);
$akumas_expire_15_json = json_encode($akumas_expire_15);
$akumas_expire_30_json = json_encode($akumas_expire_30);

$query_akumas_asignar = $db->query(" 
    SELECT 
    akumas.akuma_id, akumas.nombre, akumas.subnombre, fichas.fid, fichas.nombre as nombre_usuario
    FROM mybb_op_akumas AS akumas
    INNER JOIN mybb_op_fichas AS fichas ON akumas.uid = fichas.fid
    WHERE fichas.akuma = ''; ");
$akumas_asignar = array();
while ($q = $db->fetch_array($query_akumas_asignar)) { array_push($akumas_asignar, $q); }
$akumas_asignar_json = json_encode($akumas_asignar);

if (is_mod($uid) || is_staff($uid)) { 
    eval("\$page = \"".$templates->get("staff_akumas_inactivas")."\";");
    output_page($page);
} else {
    eval("\$page = \"".$templates->get("sin_permisos")."\";");
    output_page($page);
}
