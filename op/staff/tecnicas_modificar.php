<?php
/**
 * MyBB 1.8
 * Copyright 2014 MyBB Group, All Rights Reserved
 *
 * Website: http://www.mybb.com
 * License: http://www.mybb.com/about/license
 */

define("IN_MYBB", 1);
define('THIS_SCRIPT', 'modificar_tecnicas.php');
require_once "./../../global.php";
require "./../../inc/config.php";
require_once "./../functions/op_functions.php";

global $templates, $mybb;
$uid = $mybb->user['uid'];
$username = $mybb->user['username'];
$tecnica_id = $mybb->get_input('tecnica_id'); 

$tecnica_id_post = trim($_POST["tecnica_id"]);
$nombre = trim($_POST["nombre"]);
$tid = trim($_POST["tid"]);
$clase = addslashes($_POST["clase"]);
$rama = addslashes($_POST["rama"]);
$tipo = addslashes($_POST["tipo"]);
$tier = addslashes($_POST["tier"]);
$estilo = trim($_POST["estilo"]);
$energia = trim($_POST["energia"]);
$energia_turno = trim($_POST["energia_turno"]);
$haki = trim($_POST["haki"]);
$haki_turno = trim($_POST["haki_turno"]);
$requisitos = trim($_POST["requisitos"]);
$efectos = addslashes($_POST["efectos"]);
$enfriamiento = addslashes($_POST["enfriamiento"]);
$descripcion = addslashes($_POST["descripcion"]);

// $staff = trim($_POST["staff"]);
$razon = trim($_POST["razon"]);
$staff = "$uid";
// $razon = 'Apertura';

$reload_js = "<script>window.location.href = window.location.pathname;</script>";
$tecnica = null;
$existe = false;

if ($tecnica_id) {
    $query_tecnicas = $db->query("
        SELECT * FROM `mybb_op_tecnicas` WHERE tid='$tecnica_id'
    ");
    while ($t = $db->fetch_array($query_tecnicas)) {
        $tecnica = $t;
    }
}

if ($tecnica_id_post) {
    $query_tecnicas = $db->query("
        SELECT * FROM `mybb_op_tecnicas` WHERE tid='$tecnica_id_post'
    ");
    while ($t = $db->fetch_array($query_tecnicas)) {
        $tecnica = $t;
        $existe = true;
    }
}


if ($tecnica_id && $tecnica_id_post && $nombre && $tid && $staff && $razon && (is_mod($uid) || is_staff($uid))) {
    $log = "Cambio a técnica ID $tid ($nombre). \nLos cambios son: \ntid=$tid,\nnombre=$nombre,\nestilo=$estilo,\nclase=$clase,\r rama=$rama,\nrequisitos=$requisitos,\nefectos=$efectos,\nenfriamiento=$enfriamiento,\n\haki=$haki,\haki_turno=$haki_turno,\energia=$energia,\energia_turno=$energia_turno,\nclase=$clase,\ndescripcion=$descripcion";

    $tid_old = $tecnica['tid'];
    $nombre_old = $tecnica['nombre'];
    // $version_old = $tecnica['version'];
    $estilo_old = $tecnica['estilo'];
    $rango_old = $tecnica['rango'];
    $coste_old = $tecnica['coste'];
    $clase_old = $tecnica['clase'];
    $rama_old = $tecnica['rama'];
    $requisitos_old = $tecnica['requisitos'];
    $descripcion_old = $tecnica['descripcion'];

    $version_nueva = intval($version_old) + 1;

    // $db->query(" 
    //     INSERT INTO `mybb_op_tecnicas_version` 
    //     (`tid`, `tid_old`, `version`, `nombre`, `tipo`, `aldea`, `categoria`, `sellos`, `rango`, `puntuacion`, `coste`, `clase`, `requisito`, `descripcion`)
    //     VALUES ('$tid', '$tid_old', '$version_old', '$nombre_old', '$tipo_old', '$aldea_old', '$categoria_old', '$sellos_old', '$rango_old', '$puntuacion_old', '$coste_old', '$clase_old', '$requisito_old', '$descripcion_old')
    // ");


    if ($existe) {
        $db->query(" 
            UPDATE `mybb_op_tecnicas` SET `tid`='$tid',`nombre`='$nombre',`estilo`='$estilo',`clase`='$clase',`rama`='$rama',`tier`='$tier',
            `efectos`='$efectos',`energia`='$energia',`energia_turno`='$energia_turno',`haki`='$haki',`haki_turno`='$haki_turno',`tipo`='$tipo',
            `enfriamiento`='$enfriamiento',`requisitos`='$requisitos',`descripcion`='$descripcion' WHERE `tid`='$tecnica_id';
        ");
    } else {

        $db->query(" 
            INSERT INTO `mybb_op_tecnicas` (`tid`, `nombre`, `estilo`, `clase`, `rama`, `efectos`, `tipo`, `energia`, `energia_turno`, `haki`,
            `haki_turno`, `enfriamiento`, `requisitos`, `descripcion`, `tier`) VALUES ('$tid','$nombre','$estilo','$clase','$rama','$efectos','$tipo',
            '$energia','$energia_turno','$haki','$haki_turno','$enfriamiento','$requisitos','$descripcion', '$tier');
        ");
    }
    
    if ($tecnica_id_post != $tid) {
        $db->query(" 
            UPDATE `mybb_op_tec_aprendidas` SET `tid`='$tid' WHERE tid='$tecnica_id_post'
        ");
    }

    $db->query(" 
        INSERT INTO `mybb_op_audit_consola_mod` (`staff`, `username`, `razon`, `log`) VALUES 
        ('$staff', '$username', '$razon', '$log');
    ");

    // Escapar el log para JavaScript
    $log_escaped = str_replace(["\r", "\n"], ["", "\\n"], addslashes($log));
    
    // Redirigir con el mensaje de log
    echo "<script>
        alert('$log_escaped');
        window.location.href = 'https://onepiecegaiden.com/op/staff/tecnicas_modificar.php';
    </script>";
    exit();
}

if (is_mod($uid) || is_staff($uid)) { 
    // eval('$tid = $tecnica_id;');
    eval("\$page = \"".$templates->get("staff_modificar_tecnicas")."\";");
    output_page($page);
} else {
    eval("\$page = \"".$templates->get("sin_permisos")."\";");
    output_page($page);
}
