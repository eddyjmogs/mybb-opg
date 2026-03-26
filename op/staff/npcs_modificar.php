<?php
/**
 * MyBB 1.8
 * Copyright 2014 MyBB Group, All Rights Reserved
 *
 * Website: http://www.mybb.com
 * License: http://www.mybb.com/about/license
 */

define("IN_MYBB", 1);
define('THIS_SCRIPT', 'npcs_modificar.php');
require_once "./../../global.php";
require "./../../inc/config.php";
require_once "./../functions/op_functions.php";

global $templates, $mybb;
$uid = $mybb->user['uid'];
$username = $mybb->user['username'];
$npc_id = $mybb->get_input('npc_id'); 

$npc_id_post = trim($_POST["npc_id"]);
$nuevo_npc_id = trim($_POST["nuevo_npc_id"]);

$nombre = trim($_POST["nombre"]);
$apodo = trim($_POST["apodo"]);
$faccion = trim($_POST["faccion"]);
$raza = trim($_POST["raza"]);
$edad = trim($_POST["edad"]);
$altura = trim($_POST["altura"]);
$peso = trim($_POST["peso"]);
$sexo = trim($_POST["sexo"]);
$temporada = trim($_POST["temporada"]);
$nivel = trim($_POST["nivel"]);

$fuerza = trim($_POST["fuerza"]);
$resistencia = trim($_POST["resistencia"]);
$destreza = trim($_POST["destreza"]);
$voluntad = trim($_POST["voluntad"]);
$punteria = trim($_POST["punteria"]);
$agilidad = trim($_POST["agilidad"]);
$reflejos = trim($_POST["reflejos"]);
$control_akuma = trim($_POST["control_akuma"]);

$vitalidad = trim($_POST["vitalidad"]);
$energia = trim($_POST["energia"]);
$haki = trim($_POST["haki"]);

$rango = trim($_POST["rango"]);
$sangre = trim($_POST["sangre"]);
$akuma = trim($_POST["akuma"]);
$avatar1 = trim($_POST["avatar1"]);
$avatar2 = trim($_POST["avatar2"]);

$apariencia = addslashes($_POST["apariencia"]);
$personalidad = addslashes($_POST["personalidad"]);
$historia1 = addslashes($_POST["historia1"]);
$historia2 = addslashes($_POST["historia2"]);
$historia3 = addslashes($_POST["historia3"]);

$estilo_combate = addslashes($_POST["estilo_combate"]);
$buso = addslashes($_POST["buso"]);
$kenbun = addslashes($_POST["kenbun"]);
$hao = addslashes($_POST["hao"]);

// New fields for NPCs (match player ficha fields)
$estilo1 = trim($_POST["estilo1"]);
$estilo2 = trim($_POST["estilo2"]);
$estilo3 = trim($_POST["estilo3"]);
$estilo4 = trim($_POST["estilo4"]);
$belicas = addslashes($_POST["belicas"]); // JSON/text representing disciplinas bélicas
$oficios = addslashes($_POST["oficios"]); // JSON/text for oficios
$estilos = addslashes($_POST["estilos"]); // optional additional estilos listing
$belicas_disponibles = addslashes($_POST["belicas_disponibles"]); // JSON/text of available belicas

// Individual belica and oficio slots (same names as fichas)
$belica1 = trim($_POST['belica1']);
$belica2 = trim($_POST['belica2']);
$belica3 = trim($_POST['belica3']);
$belica4 = trim($_POST['belica4']);
$belica5 = trim($_POST['belica5']);
$belica6 = trim($_POST['belica6']);
$belica7 = trim($_POST['belica7']);
$belica8 = trim($_POST['belica8']);

$oficio1 = trim($_POST['oficio1']);
$oficio2 = trim($_POST['oficio2']);

$extra = addslashes($_POST["extra"]);
$wanted = addslashes($_POST["wanted"]);
$reputacion = addslashes($_POST["reputacion"]);
$notas = addslashes($_POST["notas"]);

$info = addslashes($_POST["info"]);

$usuario = addslashes($_POST["usuario"]);
$etiqueta = addslashes($_POST["etiqueta"]);

$staff = "$uid";
$razon = 'Apertura';

$accion = $mybb->get_input('accion');

// Ensure required columns exist in `mybb_op_npcs`. If they do not, add them.
$columns_to_add = array(
    'estilo1' => 'VARCHAR(255) NULL',
    'estilo2' => 'VARCHAR(255) NULL',
    'estilo3' => 'VARCHAR(255) NULL',
    'estilo4' => 'VARCHAR(255) NULL',
    'belicas' => 'TEXT NULL',
    'oficios' => 'TEXT NULL',
    'estilos' => 'TEXT NULL',
    'belicas_disponibles' => 'TEXT NULL',
    // Individual belica slots like in player fichas
    'belica1' => 'VARCHAR(255) NULL',
    'belica2' => 'VARCHAR(255) NULL',
    'belica3' => 'VARCHAR(255) NULL',
    'belica4' => 'VARCHAR(255) NULL',
    'belica5' => 'VARCHAR(255) NULL',
    'belica6' => 'VARCHAR(255) NULL',
    'belica7' => 'VARCHAR(255) NULL',
    'belica8' => 'VARCHAR(255) NULL',
    // Oficios como en fichas
    'oficio1' => 'VARCHAR(255) NULL',
    'oficio2' => 'VARCHAR(255) NULL'
);
foreach ($columns_to_add as $col => $def) {
    $res = $db->query("SHOW COLUMNS FROM `mybb_op_npcs` LIKE '$col'");
    if ($db->num_rows($res) == 0) {
        $db->query("ALTER TABLE `mybb_op_npcs` ADD `$col` $def AFTER `estilo_combate`");
    }
}

// Also ensure the same fields exist for mascotas and npc_usuarios tables
$other_tables = array('mybb_op_mascotas', 'mybb_op_npcs_usuarios');
foreach ($other_tables as $table) {
    foreach ($columns_to_add as $col => $def) {
        $res = $db->query("SHOW COLUMNS FROM `$table` LIKE '$col'");
        if ($db->num_rows($res) == 0) {
            // Some tables may not have an `estilo_combate` column; add to the end if not present
            $after = 'estilo_combate';
            $res_after = $db->query("SHOW COLUMNS FROM `$table` LIKE '$after'");
            if ($db->num_rows($res_after) == 0) {
                $db->query("ALTER TABLE `$table` ADD `$col` $def");
            } else {
                $db->query("ALTER TABLE `$table` ADD `$col` $def AFTER `$after`");
            }
        }
    }
}

if ($accion == 'borrar_npc') {
    $db->query("DELETE FROM `mybb_op_npcs` WHERE `npc_id` = '$npc_id'");
}

if ($accion == 'borrar_mascota') {
    $db->query("DELETE FROM `mybb_op_mascotas` WHERE `npc_id` = '$npc_id'");
}

if ($accion == 'borrar_npc_usuario') {
    $db->query("DELETE FROM `mybb_op_npcs_usuarios` WHERE `npc_id` = '$npc_id'");
}

if ($accion == 'borrar_npc' || $accion == 'borrar_mascota' || $accion == 'borrar_npc_usuario') {
    echo("<script>alert('NPC borrado $npc_id');window.location.href = 'https://onepiecegaiden.com/op/npcs_modificar.php';</script>");
}

if ($info == '') {
    $info = 'null';
}

$reload_js = "<script>window.location.href = window.location.pathname;</script>";
$npc = null;
$existe = false;



$npcs_count = '0';
$query_count = $db->query(" SELECT COUNT(*) as count FROM `mybb_op_npcs`; ");
while ($q = $db->fetch_array($query_count)) { $npcs_count = $q['count']; }

if ($npc_id) {
    
    $query_npcs = $db->query(" SELECT * FROM `mybb_op_npcs` WHERE npc_id='$npc_id' ");
    while ($t = $db->fetch_array($query_npcs)) { $npc = $t; $existe = true; }

}



if ($npc_id_post && $nuevo_npc_id && $nombre && (is_mod($uid) || is_staff($uid) || is_narra($uid))) {
    $log = "Cambio a npc ID $npc_id ($nombre).";

    if ($existe) {
        
        $db->query(" 
            UPDATE `mybb_op_npcs` SET 
            `npc_id`='$nuevo_npc_id', `nombre`='$nombre',`apodo`='$apodo',`faccion`='$faccion',`raza`='$raza',`edad`='$edad', `altura`='$altura', `peso`='$peso', `sexo`='$sexo', `temporada`='$temporada', `nivel`='$nivel',
            `fuerza`='$fuerza', `resistencia`='$resistencia', `destreza`='$destreza', `voluntad`='$voluntad', `punteria`='$punteria', `agilidad`='$agilidad', `reflejos`='$reflejos', `control_akuma`='$control_akuma',
            `rango`='$rango', `sangre`='$sangre', `akuma`='$akuma', `avatar1`='$avatar1', `avatar2`='$avatar2',
            `apariencia`='$apariencia', `personalidad`='$personalidad', `historia1`='$historia1', `historia2`='$historia2', `historia3`='$historia3', `extra`='$extra', `vitalidad`='$vitalidad', `energia`='$energia', `haki`='$haki', `notas`='$notas',
            `buso`='$buso', `kenbun`='$kenbun', `haoshoku`='$hao', `estilo_combate`='$estilo_combate', `estilo1`='$estilo1', `estilo2`='$estilo2', `estilo3`='$estilo3', `estilo4`='$estilo4', `belicas`='$belicas', `oficios`='$oficios', `estilos`='$estilos', `belicas_disponibles`='$belicas_disponibles', `belica1`='$belica1', `belica2`='$belica2', `belica3`='$belica3', `belica4`='$belica4', `belica5`='$belica5', `belica6`='$belica6', `belica7`='$belica7', `belica8`='$belica8', `oficio1`='$oficio1', `oficio2`='$oficio2', `info`='$info', `wanted`='$wanted', `reputacion`='$reputacion', `etiqueta`='$etiqueta'
            WHERE `npc_id`='$npc_id_post';
        ");
    } else {
        $db->query(" 
            INSERT INTO `mybb_op_npcs` 
                (`npc_id`, `nombre`, `apodo`, `faccion`, `raza`, `edad`, `altura`, `peso`, `sexo`, `temporada`, `nivel`, 
                `fuerza`, `resistencia`, `destreza`, `voluntad`, `punteria`, `agilidad`, `reflejos`, `control_akuma`,
                `rango`, `sangre`, `akuma`, `avatar1`, `avatar2`,
                `apariencia`, `personalidad`, `historia1`, `historia2`, `historia3`, `extra`, `vitalidad`, `energia`, `haki`, `notas`,
                `buso`, `kenbun`, `haoshoku`, `estilo_combate`, `estilo1`, `estilo2`, `estilo3`, `estilo4`, `belicas`, `oficios`, `estilos`, `belicas_disponibles`, `belica1`, `belica2`, `belica3`, `belica4`, `belica5`, `belica6`, `belica7`, `belica8`, `oficio1`, `oficio2`, `info`, `wanted`, `reputacion`, `etiqueta`) VALUES 
                ('$npc_id_post', '$nombre','$apodo','$faccion','$raza','$edad', '$altura', '$peso', '$sexo', '$temporada', '$nivel',
                '$fuerza', '$resistencia', '$destreza', '$voluntad', '$punteria', '$agilidad', '$reflejos', '$control_akuma',
                '$rango', '$sangre', '$akuma', '$avatar1', '$avatar2',
                '$apariencia', '$personalidad', '$historia1', '$historia2', '$historia3', '$extra', '$vitalidad', '$energia', '$haki', '$notas',
                '$buso', '$kenbun', '$haoshoku', '$estilo_combate', '$estilo1', '$estilo2', '$estilo3', '$estilo4', '$belicas', '$oficios', '$estilos', '$belicas_disponibles', '$belica1', '$belica2', '$belica3', '$belica4', '$belica5', '$belica6', '$belica7', '$belica8', '$oficio1', '$oficio2', '$info', '$wanted', '$reputacion','$etiqueta'
            );
        ");

    }

    $db->query(" 
        INSERT INTO `mybb_op_audit_consola_mod` (`staff`, `username`, `razon`, `log`) VALUES 
        ('$staff', '$username', '$razon', '$log');
    ");

    eval('$log_var = $log;');
    eval('$reload_script = $reload_js;');
}

if ($npc_id_post && $nuevo_npc_id && $nombre && $is_npc_user && (is_mod($uid) || is_staff($uid) || is_narra($uid))) {
    $log = "Cambio a NPC Usuario ID $npc_id ($nombre).";

    // Check if exists in users table
    $existe_user = false;
    $q_check = $db->query("SELECT COUNT(*) as c FROM `mybb_op_npcs_usuarios` WHERE npc_id='$npc_id_post'");
    if ($r = $db->fetch_array($q_check)) { if ($r['c'] > 0) { $existe_user = true; } }

    if ($existe_user) {
        $db->query(" 
            UPDATE `mybb_op_npcs_usuarios` SET 
            `nombre`='$nombre', `avatar1`='$avatar1', `usuario`='$usuario', `etiqueta`='$etiqueta',
            `estilo_combate`='$estilo_combate', `estilo1`='$estilo1', `estilo2`='$estilo2', `estilo3`='$estilo3', `estilo4`='$estilo4',
            `belicas`='$belicas', `oficios`='$oficios', `estilos`='$estilos', `belicas_disponibles`='$belicas_disponibles', `belica1`='$belica1', `belica2`='$belica2', `belica3`='$belica3', `belica4`='$belica4', `belica5`='$belica5', `belica6`='$belica6', `belica7`='$belica7', `belica8`='$belica8', `oficio1`='$oficio1', `oficio2`='$oficio2', `extra`='$extra', `info`='$info'
            WHERE `npc_id`='$npc_id_post';
        ");
    } else {
        $db->query(" 
            INSERT INTO `mybb_op_npcs_usuarios` 
                (`npc_id`, `nombre`, `avatar1`, `usuario`, `etiqueta`, `estilo_combate`, `estilo1`, `estilo2`, `estilo3`, `estilo4`, `belicas`, `oficios`, `estilos`, `belicas_disponibles`, `belica1`, `belica2`, `belica3`, `belica4`, `belica5`, `belica6`, `belica7`, `belica8`, `oficio1`, `oficio2`, `extra`, `info`) VALUES 
                ('$npc_id_post', '$nombre','$avatar1','$usuario','$etiqueta','$estilo_combate','$estilo1','$estilo2','$estilo3','$estilo4','$belicas','$oficios','$estilos','$belicas_disponibles','$belica1','$belica2','$belica3','$belica4','$belica5','$belica6','$belica7','$belica8','$oficio1','$oficio2','$extra','$info'
            );
        ");

    }

    $db->query(" 
        INSERT INTO `mybb_op_audit_consola_mod` (`staff`, `username`, `razon`, `log`) VALUES 
        ('$staff', '$username', '$razon', '$log');
    ");

    eval('$log_var = $log;');
    eval('$reload_script = $reload_js;');
} 

if ($npc_id_post && $nuevo_npc_id && $nombre && $is_pet && (is_mod($uid) || is_staff($uid) || is_narra($uid))) {
    $log = "Cambio a mascota ID $npc_id ($nombre).";

    // Check if exists in mascotas table
    $existe_pet = false;
    $q_check = $db->query("SELECT COUNT(*) as c FROM `mybb_op_mascotas` WHERE npc_id='$npc_id_post'");
    if ($r = $db->fetch_array($q_check)) { if ($r['c'] > 0) { $existe_pet = true; } }

    if ($existe_pet) {
        $db->query(" 
            UPDATE `mybb_op_mascotas` SET 
            `nombre`='$nombre', `avatar1`='$avatar1', `usuario`='$usuario', `etiqueta`='$etiqueta',
            `estilo_combate`='$estilo_combate', `estilo1`='$estilo1', `estilo2`='$estilo2', `estilo3`='$estilo3', `estilo4`='$estilo4',
            `belicas`='$belicas', `oficios`='$oficios', `estilos`='$estilos', `belicas_disponibles`='$belicas_disponibles', `belica1`='$belica1', `belica2`='$belica2', `belica3`='$belica3', `belica4`='$belica4', `belica5`='$belica5', `belica6`='$belica6', `belica7`='$belica7', `belica8`='$belica8', `oficio1`='$oficio1', `oficio2`='$oficio2', `extra`='$extra', `info`='$info'
            WHERE `npc_id`='$npc_id_post';
        ");
    } else {
        $db->query(" 
            INSERT INTO `mybb_op_mascotas` 
                (`npc_id`, `nombre`, `avatar1`, `usuario`, `etiqueta`, `estilo_combate`, `estilo1`, `estilo2`, `estilo3`, `estilo4`, `belicas`, `oficios`, `estilos`, `belicas_disponibles`, `belica1`, `belica2`, `belica3`, `belica4`, `belica5`, `belica6`, `belica7`, `belica8`, `oficio1`, `oficio2`, `extra`, `info`) VALUES 
                ('$npc_id_post', '$nombre','$avatar1','$usuario','$etiqueta','$estilo_combate','$estilo1','$estilo2','$estilo3','$estilo4','$belicas','$oficios','$estilos','$belicas_disponibles','$belica1','$belica2','$belica3','$belica4','$belica5','$belica6','$belica7','$belica8','$oficio1','$oficio2','$extra','$info'
            );
        ");

    }

    $db->query(" 
        INSERT INTO `mybb_op_audit_consola_mod` (`staff`, `username`, `razon`, `log`) VALUES 
        ('$staff', '$username', '$razon', '$log');
    ");

    eval('$log_var = $log;');
    eval('$reload_script = $reload_js;');
}

if (is_mod($uid) || is_staff($uid) || is_narra($uid)) { 
    eval("\$page = \"".$templates->get("staff_npcs_modificar")."\";");
    output_page($page);
} else {
    eval("\$page = \"".$templates->get("sin_permisos")."\";");
    output_page($page);
}
