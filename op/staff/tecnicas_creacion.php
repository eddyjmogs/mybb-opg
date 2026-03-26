<?php
/**
 * MyBB 1.8
 * Copyright 2014 MyBB Group, All Rights Reserved
 *
 * Website: http://www.mybb.com
 * License: http://www.mybb.com/about/license
 */

define("IN_MYBB", 1);
define('THIS_SCRIPT', 'tecnicas_creacion.php');
require_once "./../../global.php";
require "./../../inc/config.php";
require_once "./../functions/op_functions.php";

global $templates, $mybb;
$uid = $mybb->user['uid'];

$accion = $mybb->get_input('accion');
$tid_input = $mybb->get_input('tid');

$reload_js = "<script>window.location.href = window.location.pathname;</script>";

if ($accion == 'abandonar' && $tid_input) {

    $db->query(" 
        UPDATE `mybb_threads` SET `fid`=85,`closed`=1 WHERE tid=$tid_input
    ");

    eval('$reload_script = $reload_js;');
}

if (is_mod($uid) || is_staff($uid) || is_user($uid)) { 
    $peticiones_li = "";

    // $borrar_a = "$url_page?accion=borrar&peti_id=$pid";

    function print_tecnicas_sin_contestar() {
        global $db;
        $query_tecnicas = $db->query("
            SELECT t.* FROM mybb_threads as t
            INNER JOIN mybb_forums as f ON t.fid = f.fid 
            AND f.fid = 8 AND t.closed = 0
            AND t.uid = t.lastposteruid
            AND t.visible = 1
            AND t.replies = 0
            AND t.tid != 97
            ORDER BY t.lastpost ASC
        ");

        $peticiones_li = "";
        while ($q = $db->fetch_array($query_tecnicas)) {
            $tid = $q['tid'];
            $subject = $q['subject'];
            $subject = "<a href='/showthread.php?tid=$tid' target='_blank'>$subject</a>";

            $username = $q['username'];
            $uid = $q['uid'];
            $nombre = "<a href='/op/ficha.php?uid=$uid' target='_blank'>$username</a>";
            
            $lastpost = date('d/m/Y', intval($q['lastpost']));
            $days_ago = floor(((time() - intval($q['lastpost']))) / 86400);

            $peticiones_li .= "<li>";
            $peticiones_li .= "<strong>Usuario</strong>: [$nombre]<br>";
            $peticiones_li .= "<strong>Título</strong>: $subject<br>";
            $peticiones_li .= "<strong>Fecha</strong>: $lastpost - Hace $days_ago días.<br>";
            $peticiones_li .= "</li><br>";
        }

        if ($peticiones_li != "") {
            return "<h2>Creaciones sin respuesta de moderación:</h2>" . $peticiones_li;
        } else {
            return "";
        }
    }

    function print_tecnicas_a_moderar() {
        global $db;
        $query_tecnicas = $db->query("
            SELECT t.* FROM mybb_threads as t
            INNER JOIN mybb_forums as f ON t.fid = f.fid 
            AND f.fid = 8 AND t.closed = 0
            AND t.uid = t.lastposteruid
            AND t.visible = 1
            AND t.replies > 0
            AND t.tid != 97
            ORDER BY t.lastpost ASC
        ");

        $peticiones_li = "";
        while ($q = $db->fetch_array($query_tecnicas)) {
            $tid = $q['tid'];
            $subject = $q['subject'];
            $subject = "<a href='/showthread.php?tid=$tid' target='_blank'>$subject</a>";

            $username = $q['username'];
            $uid = $q['uid'];
            $nombre = "<a href='/op/ficha.php?uid=$uid' target='_blank'>$username</a>";
            
            $lastpost = date('d/m/Y', intval($q['lastpost']));
            $days_ago = floor(((time() - intval($q['lastpost']))) / 86400);

            $peticiones_li .= "<li>";
            $peticiones_li .= "<strong>Usuario</strong>: [$nombre]<br>";
            $peticiones_li .= "<strong>Título</strong>: $subject<br>";
            $peticiones_li .= "<strong>Fecha</strong>: $lastpost - Hace $days_ago días.<br>";
            $peticiones_li .= "</li><br>";
        }

        if ($peticiones_li != "") {
            return "<h2>Creaciones donde el usuario respondió y está a la espera:</h2>" . $peticiones_li;
        } else {
            return "";
        }
    }
    
    function print_tecnicas_no_moderar() {
        global $db;
        $query_tecnicas = $db->query("
            SELECT t.* FROM mybb_threads as t
            INNER JOIN mybb_forums as f ON t.fid = f.fid 
            AND f.fid = 8 AND t.closed = 0
            AND t.uid != t.lastposteruid
            AND t.visible = 1
            AND t.tid != 97
            ORDER BY t.lastpost DESC
        ");

        $peticiones_li = "";
        while ($q = $db->fetch_array($query_tecnicas)) {
            $tid = $q['tid'];
            $subject = $q['subject'];
            $subject = "<a href='/showthread.php?tid=$tid' target='_blank'>$subject</a>";

            $username = $q['username'];
            $uid = $q['uid'];
            $nombre = "<a href='/op/ficha.php?uid=$uid' target='_blank'>$username</a>";
            
            $lastpost = date('d/m/Y', intval($q['lastpost']));
            $days_ago = floor(((time() - intval($q['lastpost']))) / 86400);

            $peticiones_li .= "<li>";
            $peticiones_li .= "<strong>Usuario</strong>: [$nombre]<br>";
            $peticiones_li .= "<strong>Título</strong>: $subject<br>";
            $peticiones_li .= "<strong>Fecha</strong>: $lastpost - Hace $days_ago días.<br>";
            
            // move tid to 333 and 
            // 333

            // UPDATE `mybb_threads` SET `fid`=[value-2],`closed`=[value-16] WHERE tid='XXX'

            $url_page = "/op/staff/tecnicas_creacion.php";
            $resolver_a = "$url_page?accion=abandonar&tid=$tid";
            $peticiones_li .= "<span><a href='$resolver_a' >Abandonar</a></span>";

            // $peticiones_li .= "<span><a href='$borrar_a' target='_blank'>Borrar</a></span>";
            $peticiones_li .= "</li><br>";
        
        }

        if ($peticiones_li != "") {
            return "<h2>Técnicas que el usuario debe responder. No toca moderar.</h2>" . $peticiones_li;
        } else {
            return "";
        }
    }

    $peticiones_li .= print_tecnicas_sin_contestar();
    $peticiones_li .= print_tecnicas_a_moderar();
    $peticiones_li .= print_tecnicas_no_moderar();

    eval("\$page = \"".$templates->get("staff_tecnicas_creacion")."\";");
    output_page($page);
} else {
    eval("\$page = \"".$templates->get("sin_permisos")."\";");
    output_page($page);
}
