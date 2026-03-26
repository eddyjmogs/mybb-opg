<?php
/**
 * MyBB 1.8
 * Copyright 2014 MyBB Group, All Rights Reserved
 *
 * Website: http://www.mybb.com
 * License: http://www.mybb.com/about/license
 */

define("IN_MYBB", 1);
define('THIS_SCRIPT', 'ficha_atributos.php');
require_once "./../../global.php";
require "./../../inc/config.php";
require_once "./../functions/op_functions.php";

global $templates, $mybb;
$uid = $mybb->user['uid'];
$username = $mybb->user['username'];
$user_fid = $mybb->get_input('fid'); 
$reload_js = "<script>window.location.href = window.location.pathname;</script>";

$nombre = $_POST["nombre"];
$berries = $_POST["berries"];
$edad = $_POST["edad"];
$altura = $_POST["altura"];
$peso = $_POST["peso"];
$nika = $_POST["nika"];

$akuma = $_POST["akuma"];

$sexo = $_POST["sexo"];
$faccion = $_POST["faccion"];
$temporada = $_POST["temporada"];
$raza = $_POST["raza"];

$rango = $_POST["rango"];
$fama = $_POST["fama"];

$oficio1 = $_POST["oficio1"];
$oficio2 = $_POST["oficio2"];
$belica1 = $_POST["belica1"];
$belica2 = $_POST["belica2"];
$belicas = $_POST["belicas"];
$oficios = $_POST["oficios"];
$belica1tier = $_POST["belica1tier"];
$belica2tier = $_POST["belica2tier"];

$kenbun = $_POST["kenbun"];
$buso = $_POST["buso"];
$hao = $_POST["hao"];
$hao_chance = $_POST["hao_chance"];

$reputacion = $_POST["reputacion"];
$reputacion_positiva = $_POST["reputacion_positiva"];
$reputacion_negativa = $_POST["reputacion_negativa"];
$wanted_repu = $_POST["wanted_repu"];

$wanted = $_POST["wanted"];
$muerto = $_POST["muerto"];

$puntos_experiencia = $_POST["puntos_experiencia"];
$puntos_estadistica = $_POST["puntos_estadistica"];
$nivel = $_POST["nivel"];

$vitalidad = $_POST["vitalidad"];
$energia = $_POST["energia"];
$haki = $_POST["haki"];

$fuerza = $_POST["fuerza"];
$resistencia = $_POST["resistencia"];
$destreza = $_POST["destreza"];
$voluntad = $_POST["voluntad"];
$punteria = $_POST["punteria"];
$agilidad = $_POST["agilidad"];
$reflejos = $_POST["reflejos"];
$control_akuma = $_POST["control_akuma"];

$fuerza_pasiva = $_POST["fuerza_pasiva"];
$resistencia_pasiva = $_POST["resistencia_pasiva"];
$destreza_pasiva = $_POST["destreza_pasiva"];
$voluntad_pasiva = $_POST["voluntad_pasiva"];
$punteria_pasiva = $_POST["punteria_pasiva"];
$agilidad_pasiva = $_POST["agilidad_pasiva"];
$reflejos_pasiva = $_POST["reflejos_pasiva"];
$control_akuma_pasiva = $_POST["control_akuma_pasiva"];

$staff = $_POST["staff"];
$razon = $_POST["razon"];
$ficha_id = $_POST["ficha_id"];

if ($staff && $razon && $ficha_id && (is_mod($uid) || is_staff($uid))) {

    $query_ficha = $db->query("
        SELECT * FROM mybb_op_fichas WHERE fid='$ficha_id'
    ");
    while ($f = $db->fetch_array($query_ficha)) {
        $f_var = $f;
    }
    $query_usuario = $db->query("
        SELECT * FROM mybb_users WHERE uid='$ficha_id'
    ");
    while ($u = $db->fetch_array($query_usuario)) {
        $u_var = $u;
    }

    $log = "Cambios de atributos para ficha de UID: $ficha_id (" . $f_var['nombre'] . "):\n";

    $db->query(" 
        UPDATE `mybb_op_fichas` SET kenbun='$kenbun', `buso`='$buso', `hao`='$hao' WHERE `fid`='$ficha_id';
    ");

    if ($nombre != $f_var['nombre']) {
        $log .= "-- De ".$f_var['nombre']." a $nombre nombre.\n";
        $db->query(" UPDATE `mybb_op_fichas` SET nombre='$nombre' WHERE `fid`='$ficha_id'; ");
    }

    if ($berries != $f_var['berries']) {
        $log .= "-- De ".$f_var['berries']." a $berries berries.\n";
        $db->query(" UPDATE `mybb_op_fichas` SET berries='$berries' WHERE `fid`='$ficha_id'; ");
    }

    if ($edad != $f_var['edad']) {
        $log .= "-- De ".$f_var['edad']." a $edad edad.\n";
        $db->query(" UPDATE `mybb_op_fichas` SET edad='$edad' WHERE `fid`='$ficha_id'; ");
    }

    if ($altura != $f_var['altura']) {
        $log .= "-- De ".$f_var['altura']." a $altura altura.\n";
        $db->query(" UPDATE `mybb_op_fichas` SET altura='$altura' WHERE `fid`='$ficha_id'; ");
    }

    if ($peso != $f_var['peso']) {
        $log .= "-- De ".$f_var['peso']." a $peso peso.\n";
        $db->query(" UPDATE `mybb_op_fichas` SET peso='$peso' WHERE `fid`='$ficha_id'; ");
    }
    
    if ($nika != $f_var['nika']) {
        $log .= "-- De ".$f_var['nika']." a $nika nika.\n";
        $db->query(" UPDATE `mybb_op_fichas` SET nika='$nika' WHERE `fid`='$ficha_id'; ");
    }

    if ($akuma != $f_var['akuma']) {
        $log .= "-- De ".$f_var['akuma']." a $akuma akuma.\n";
        $db->query(" UPDATE `mybb_op_fichas` SET akuma='$akuma' WHERE `fid`='$ficha_id'; ");
    }

    if ($rango != $f_var['rango']) {
        $log .= "-- De ".$f_var['rango']." a $rango rango.\n";
        $db->query(" UPDATE `mybb_op_fichas` SET rango='$rango' WHERE `fid`='$ficha_id'; ");
    }

    if ($fama != $f_var['fama']) {
        $log .= "-- De ".$f_var['fama']." a $fama fama.\n";
        $db->query(" UPDATE `mybb_op_fichas` SET fama='$fama' WHERE `fid`='$ficha_id'; ");
    }


    if ($sexo != $f_var['sexo']) {
        $log .= "-- De ".$f_var['sexo']." a $sexo sexo.\n";
        $db->query(" UPDATE `mybb_op_fichas` SET sexo='$sexo' WHERE `fid`='$ficha_id'; ");
    }

    if ($faccion != $f_var['faccion']) {
        $log .= "-- De ".$f_var['faccion']." a $faccion faccion.\n";
        $db->query(" UPDATE `mybb_op_fichas` SET faccion='$faccion' WHERE `fid`='$ficha_id'; ");
    }

    if ($temporada != $f_var['temporada']) {
        $log .= "-- De ".$f_var['temporada']." a $temporada temporada.\n";
        $db->query(" UPDATE `mybb_op_fichas` SET temporada='$temporada' WHERE `fid`='$ficha_id'; ");
    }

    if ($raza != $f_var['raza']) {
        $log .= "-- De ".$f_var['raza']." a $raza raza.\n";
        $db->query(" UPDATE `mybb_op_fichas` SET raza='$raza' WHERE `fid`='$ficha_id'; ");
    }
    

    if ($kenbun != $f_var['kenbun']) {
        $log .= "-- De ".$f_var['kenbun']." a $kenbun kenbun.\n";
        $db->query(" UPDATE `mybb_op_fichas` SET berries='$berries' WHERE `fid`='$ficha_id'; ");
    }

    if ($buso != $f_var['buso']) {
        $log .= "-- De ".$f_var['buso']." a $kenbun buso.\n";
        $db->query(" UPDATE `mybb_op_fichas` SET buso='$buso' WHERE `fid`='$ficha_id'; ");
    }

    if ($hao != $f_var['hao']) {
        $log .= "-- De ".$f_var['hao']." a $hao hao.\n";
        $db->query(" UPDATE `mybb_op_fichas` SET hao='$hao' WHERE `fid`='$ficha_id'; ");
    }

    if ($hao_chance != $f_var['hao_chance']) {
        $log .= "-- De ".$f_var['hao_chance']." a $hao_chance hao_chance.\n";
        $db->query(" UPDATE `mybb_op_fichas` SET hao_chance='$hao_chance' WHERE `fid`='$ficha_id'; ");
    }

    if ($wanted != $f_var['wanted']) {
        $log .= "-- De ".$f_var['wanted']." a $wanted wanted.\n";
        $db->query(" UPDATE `mybb_op_fichas` SET wanted='$wanted' WHERE `fid`='$ficha_id'; ");
    }

    
    if ($reputacion != $f_var['reputacion']) {
        $log .= "-- De ".$f_var['reputacion']." a $reputacion reputacion.\n";
        $db->query(" UPDATE `mybb_op_fichas` SET reputacion='$reputacion' WHERE `fid`='$ficha_id'; ");
    }

    if ($reputacion_positiva != $f_var['reputacion_positiva']) {
        $log .= "-- De ".$f_var['reputacion_positiva']." a $reputacion_positiva reputacion_positiva.\n";
        $db->query(" UPDATE `mybb_op_fichas` SET reputacion_positiva='$reputacion_positiva' WHERE `fid`='$ficha_id'; ");
    }

    if ($reputacion_negativa != $f_var['reputacion_negativa']) {
        $log .= "-- De ".$f_var['reputacion_negativa']." a $reputacion_negativa reputacion_negativa.\n";
        $db->query(" UPDATE `mybb_op_fichas` SET reputacion_negativa='$reputacion_negativa' WHERE `fid`='$ficha_id'; ");
    }

    if ($wanted_repu != $f_var['wanted_repu']) {
        $log .= "-- De ".$f_var['wanted_repu']." a $wanted_repu wanted_repu.\n";
        $db->query(" UPDATE `mybb_op_fichas` SET wanted_repu='$wanted_repu' WHERE `fid`='$ficha_id'; ");
    }


    if ($oficio1 != $f_var['oficio1']) {

        //if ($oficio1 == 'sin_oficio') { $oficio1 = ''; }
        $log .= "-- De ".$f_var['oficio1']." a $oficio1 oficio1.\n";
        $db->query(" UPDATE `mybb_op_fichas` SET oficio1='$oficio1' WHERE `fid`='$ficha_id'; ");
    }

    if ($oficio2 != $f_var['oficio2']) {
        //if ($oficio2 == 'sin_oficio') { $oficio2 = ''; }
        $log .= "-- De ".$f_var['oficio2']." a $oficio2 oficio2.\n";
        $db->query(" UPDATE `mybb_op_fichas` SET oficio2='$oficio2' WHERE `fid`='$ficha_id'; ");
    }

    if ($belica1 != $f_var['belica1']) {
        //if ($belica1 == 'sin_disciplina') { $belica1 = ''; }
        $log .= "-- De ".$f_var['belica1']." a $belica1 belica1.\n";
        $db->query(" UPDATE `mybb_op_fichas` SET belica1='$belica1' WHERE `fid`='$ficha_id'; ");
    }

    if ($belica2 != $f_var['belica2']) {
        //if ($belica2 == 'sin_disciplina') { $belica2 = ''; }
        $log .= "-- De ".$f_var['belica2']." a $belica2 belica2.\n";
        $db->query(" UPDATE `mybb_op_fichas` SET belica2='$belica2' WHERE `fid`='$ficha_id'; ");
    }


    if ($belica1tier != $f_var['belica1tier']) {
        $log .= "-- De ".$f_var['belica1tier']." a $belica1tier belica1tier.\n";
        $db->query(" UPDATE `mybb_op_fichas` SET belica1tier='$belica1tier' WHERE `fid`='$ficha_id'; ");
    }

    if ($belica2tier != $f_var['belica2tier']) {
        $log .= "-- De ".$f_var['belica2tier']." a $belica2tier belica2tier.\n";
        $db->query(" UPDATE `mybb_op_fichas` SET belica2tier='$belica2tier' WHERE `fid`='$ficha_id'; ");
    }

    if ($oficios != $f_var['oficios']) {
        $log .= "-- De ".$f_var['oficios']." a $oficios oficios.\n";
        $db->query(" UPDATE `mybb_op_fichas` SET oficios='$oficios' WHERE `fid`='$ficha_id'; ");
    }

    if ($belicas != $f_var['belicas']) {
        $log .= "-- De ".$f_var['belicas']." a $belicas belicas.\n";
        $db->query(" UPDATE `mybb_op_fichas` SET belicas='$belicas' WHERE `fid`='$ficha_id'; ");
    }

    if ($puntos_estadistica != $f_var['puntos_estadistica']) {
        $log .= "-- De ".$f_var['puntos_estadistica']." a $puntos_estadistica puntos_estadistica.\n";
        $db->query(" UPDATE `mybb_op_fichas` SET puntos_estadistica='$puntos_estadistica' WHERE `fid`='$ficha_id'; ");
    }

    if ($nivel != $f_var['nivel']) {
        $log .= "-- De ".$f_var['nivel']." a $nivel nivel.\n";
        $db->query(" UPDATE `mybb_op_fichas` SET nivel='$nivel' WHERE `fid`='$ficha_id'; ");
    }


    if ($muerto != $f_var['muerto']) {
        $log .= "-- De ".$f_var['muerto']." a $muerto muerto.\n";
        $db->query(" UPDATE `mybb_op_fichas` SET muerto='$muerto' WHERE `fid`='$ficha_id'; ");
    }

    if ($puntos_experiencia != $u_var['newpoints']) {
        $log .= "-- De ".$u_var['newpoints']." a $puntos_experiencia experiencia.\n";
        $db->query(" 
            UPDATE `mybb_users` SET newpoints='$puntos_experiencia' WHERE `uid`='$ficha_id';
        "); 
    }

    if ($vitalidad != $f_var['vitalidad']) {
        $log .= "-- De ".$f_var['vitalidad']." a $vitalidad vitalidad.\n";
        $db->query(" UPDATE `mybb_op_fichas` SET vitalidad='$vitalidad' WHERE `fid`='$ficha_id'; ");
    }

    if ($energia != $f_var['energia']) {
        $log .= "-- De ".$f_var['energia']." a $energia energia.\n";
        $db->query(" UPDATE `mybb_op_fichas` SET energia='$energia' WHERE `fid`='$ficha_id'; ");
    }

    if ($haki != $f_var['haki']) {
        $log .= "-- De ".$f_var['haki']." a $haki haki.\n";
        $db->query(" UPDATE `mybb_op_fichas` SET haki='$haki' WHERE `fid`='$ficha_id'; ");
    }

    if ($fuerza != $f_var['fuerza']) {
        $log .= "-- De ".$f_var['fuerza']." a $fuerza fuerza.\n";
        $db->query(" UPDATE `mybb_op_fichas` SET fuerza='$fuerza' WHERE `fid`='$ficha_id'; ");
    }

    if ($resistencia != $f_var['resistencia']) {
        $log .= "-- De ".$f_var['resistencia']." a $resistencia resistencia.\n";
        $db->query(" UPDATE `mybb_op_fichas` SET resistencia='$resistencia' WHERE `fid`='$ficha_id'; ");
    }

    if ($destreza != $f_var['destreza']) {
        $log .= "-- De ".$f_var['destreza']." a $destreza destreza.\n";
        $db->query(" UPDATE `mybb_op_fichas` SET destreza='$destreza' WHERE `fid`='$ficha_id'; ");
    }

    if ($voluntad != $f_var['voluntad']) {
        $log .= "-- De ".$f_var['voluntad']." a $voluntad voluntad.\n";
        $db->query(" UPDATE `mybb_op_fichas` SET voluntad='$voluntad' WHERE `fid`='$ficha_id'; ");
    }

    if ($punteria != $f_var['punteria']) {
        $log .= "-- De ".$f_var['punteria']." a $punteria punteria.\n";
        $db->query(" UPDATE `mybb_op_fichas` SET punteria='$punteria' WHERE `fid`='$ficha_id'; ");
    }

    if ($agilidad != $f_var['agilidad']) {
        $log .= "-- De ".$f_var['agilidad']." a $agilidad agilidad.\n";
        $db->query(" UPDATE `mybb_op_fichas` SET agilidad='$agilidad' WHERE `fid`='$ficha_id'; ");
    }

    if ($reflejos != $f_var['reflejos']) {
        $log .= "-- De ".$f_var['reflejos']." a $reflejos reflejos.\n";
        $db->query(" UPDATE `mybb_op_fichas` SET reflejos='$reflejos' WHERE `fid`='$ficha_id'; ");
    }

    if ($control_akuma != $f_var['control_akuma']) {
        $log .= "-- De ".$f_var['control_akuma']." a $control_akuma control_akuma.\n";
        $db->query(" UPDATE `mybb_op_fichas` SET control_akuma='$control_akuma' WHERE `fid`='$ficha_id'; ");
    }


    if ($fuerza_pasiva != $f_var['fuerza_pasiva']) {
        $log .= "-- De ".$f_var['fuerza_pasiva']." a $fuerza_pasiva fuerza_pasiva.\n";
        $db->query(" UPDATE `mybb_op_fichas` SET fuerza_pasiva='$fuerza_pasiva' WHERE `fid`='$ficha_id'; ");
    }

    if ($resistencia_pasiva != $f_var['resistencia_pasiva']) {
        $log .= "-- De ".$f_var['resistencia_pasiva']." a $resistencia_pasiva resistencia_pasiva.\n";
        $db->query(" UPDATE `mybb_op_fichas` SET resistencia_pasiva='$resistencia_pasiva' WHERE `fid`='$ficha_id'; ");
    }

    if ($destreza_pasiva != $f_var['destreza_pasiva']) {
        $log .= "-- De ".$f_var['destreza_pasiva']." a $destreza_pasiva destreza_pasiva.\n";
        $db->query(" UPDATE `mybb_op_fichas` SET destreza_pasiva='$destreza_pasiva' WHERE `fid`='$ficha_id'; ");
    }

    if ($voluntad_pasiva != $f_var['voluntad_pasiva']) {
        $log .= "-- De ".$f_var['voluntad_pasiva']." a $voluntad_pasiva voluntad_pasiva.\n";
        $db->query(" UPDATE `mybb_op_fichas` SET voluntad_pasiva='$voluntad_pasiva' WHERE `fid`='$ficha_id'; ");
    }

    if ($punteria_pasiva != $f_var['punteria_pasiva']) {
        $log .= "-- De ".$f_var['punteria_pasiva']." a $punteria_pasiva punteria_pasiva.\n";
        $db->query(" UPDATE `mybb_op_fichas` SET punteria_pasiva='$punteria_pasiva' WHERE `fid`='$ficha_id'; ");
    }

    if ($agilidad_pasiva != $f_var['agilidad_pasiva']) {
        $log .= "-- De ".$f_var['agilidad_pasiva']." a $agilidad_pasiva agilidad_pasiva.\n";
        $db->query(" UPDATE `mybb_op_fichas` SET agilidad_pasiva='$agilidad_pasiva' WHERE `fid`='$ficha_id'; ");
    }

    if ($reflejos_pasiva != $f_var['reflejos_pasiva']) {
        $log .= "-- De ".$f_var['reflejos_pasiva']." a $reflejos_pasiva reflejos_pasiva.\n";
        $db->query(" UPDATE `mybb_op_fichas` SET reflejos_pasiva='$reflejos_pasiva' WHERE `fid`='$ficha_id'; ");
    }

    if ($control_akuma_pasiva != $f_var['control_akuma_pasiva']) {
        $log .= "-- De ".$f_var['control_akuma_pasiva']." a $control_akuma_pasiva control_akuma_pasiva.\n";
        $db->query(" UPDATE `mybb_op_fichas` SET control_akuma_pasiva='$control_akuma_pasiva' WHERE `fid`='$ficha_id'; ");
    }



    $db->query(" 
        INSERT INTO `mybb_op_audit_consola_mod` (`staff`, `username`, `razon`, `log`) VALUES 
        ('$staff', '$username', '$razon', '$log');
    ");
    
    eval('$log_var = $log;');
    eval('$reload_script = $reload_js;');
}

if (is_mod($uid) || is_staff($uid)) { 
    if ($user_fid != '') {
        $query_ficha = $db->query("
            SELECT * FROM mybb_op_fichas WHERE fid='$user_fid'
        ");
    
        while ($f = $db->fetch_array($query_ficha)) {
            $f_var = $f;
            eval('$ficha = $f_var;');
        }

        $query_usuario = $db->query("
            SELECT * FROM mybb_users WHERE uid='$user_fid'
        ");
        while ($u = $db->fetch_array($query_usuario)) {
            $puntos_experiencia = $u['newpoints'];
        }
    }

    eval('$fid = $user_fid;');
    eval("\$page = \"".$templates->get("staff_ficha_atributos")."\";");
    output_page($page);
} else {
    eval("\$page = \"".$templates->get("sin_permisos")."\";");
    output_page($page);
}
