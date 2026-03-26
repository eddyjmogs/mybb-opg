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
define('THIS_SCRIPT', 'fichanefta.php');
require_once "./../global.php";
require "./../inc/config.php";
require_once "./functions/op_functions.php";

global $templates, $mybb;

$user_uid = $mybb->user['uid'];
$query_uid = $mybb->get_input('uid'); 
$test = $mybb->get_input('test');
$razas = [];

$cambiar_avatar1 = addslashes($_POST["cambiar_avatar1"]);
$cambiar_avatar2 = addslashes($_POST["cambiar_avatar2"]);
$cambiar_avatar3 = addslashes($_POST["cambiar_avatar3"]);
$cambiar_avatar4 = addslashes($_POST["cambiar_avatar4"]);
$cambiar_avatar5 = addslashes($_POST["cambiar_avatar5"]);

$cambiar_apariencia = addslashes($_POST["cambiar_apariencia"]);
$cambiar_personalidad = addslashes($_POST["cambiar_personalidad"]);
$cambiar_historia = addslashes($_POST["cambiar_historia"]);
$cambiar_extras = addslashes($_POST["cambiar_extras"]);
$cambiar_apodo = addslashes($_POST["cambiar_apodo"]);

if ($cambiar_avatar1 != '') {
    $db->query(" UPDATE `mybb_op_fichas` SET `avatar3`='$cambiar_avatar1' WHERE `fid`='$query_uid'; ");
}

if ($cambiar_avatar2 != '') {
    $db->query(" UPDATE `mybb_op_fichas` SET `avatar1`='$cambiar_avatar2' WHERE `fid`='$query_uid'; ");
}

if ($cambiar_avatar3 != '') {
    $db->query(" UPDATE `mybb_op_fichas` SET `avatar2`='$cambiar_avatar3' WHERE `fid`='$query_uid'; ");
}

if ($cambiar_avatar4 != '') {
    $db->query(" UPDATE `mybb_users` SET `avatar`='$cambiar_avatar4' WHERE `uid`='$query_uid'; ");
}

if ($cambiar_avatar5 != '') {
    $db->query(" UPDATE `mybb_op_fichas` SET `avatar4`='$cambiar_avatar5' WHERE `fid`='$query_uid'; ");
}

if ($cambiar_apariencia != '') {
    $db->query(" UPDATE `mybb_op_fichas` SET `apariencia`='$cambiar_apariencia' WHERE `fid`='$query_uid'; ");
}

if ($cambiar_personalidad != '') {
    $db->query(" UPDATE `mybb_op_fichas` SET `personalidad`='$cambiar_personalidad' WHERE `fid`='$query_uid'; ");
}

if ($cambiar_historia != '') {
    $db->query(" UPDATE `mybb_op_fichas` SET `historia`='$cambiar_historia' WHERE `fid`='$query_uid'; ");
}

if ($cambiar_extras != '') {
    $db->query(" UPDATE `mybb_op_fichas` SET `extra`='$cambiar_extras' WHERE `fid`='$query_uid'; ");
}

if ($cambiar_apodo != '') {
    $db->query(" UPDATE `mybb_op_fichas` SET `apodo`='$cambiar_apodo' WHERE `fid`='$query_uid'; ");
}

$is_owner = $mybb->user['uid'] == $mybb->get_input('uid');

$ficha_existe = false;
$ficha_staff = false;
$aprobada = false;
$should_see_private = $query_uid == $user_id || is_staff($user_id) || is_peti_mod($user_id);
// $should_see_private = $query_uid == '39';

$query_ficha = $db->query("
    SELECT * FROM mybb_op_fichas WHERE fid='$query_uid'
");

while ($f = $db->fetch_array($query_ficha)) {
    $aprobada = $f['aprobada_por'] != 'sin_aprobar';
    $ficha_existe = true;
    $ficha_staff = $f['faccion'] == 'Staff' && !$g_is_staff;
}

$query_razas = $db->query(" SELECT * FROM mybb_op_razas ");
while ($q = $db->fetch_array($query_razas)) {
    $q['caracteristicas'] = nl2br($q['caracteristicas']);
    array_push($razas, $q);
}
$razas_json = json_encode($razas);

// En el archivo PHP que procesa las actualizaciones de la ficha
if (isset($_POST['cambiar_banda_sonora'])) {
    $bandaSonora = $db->escape_string($_POST['cambiar_banda_sonora']);

    // Puedes añadir validación para asegurarte de que es un enlace de YouTube
    if (empty($bandaSonora) || preg_match('/(youtube.com|youtu.be)/', $bandaSonora)) {
        $db->query("UPDATE mybb_op_fichas SET banda_sonora = '{$bandaSonora}' WHERE fid = '{$query_uid}'");
        echo "success";
        exit;
    } else {
        echo "error";
        exit;
    }
}

// Página de ficha
if ($ficha_existe == true && !$ficha_staff && ((is_mod($user_uid) || is_staff($user_uid)) || ($mybb->user['uid'] == $user_uid))) {
    $query_usuario = $db->query("
        SELECT * FROM mybb_users WHERE uid='$query_uid'
    ");

    $experiencia_limite = null;

    $query_experiencia_limite = $db->query(" SELECT * FROM `mybb_op_experiencia_limite` WHERE uid='$query_uid' ORDER BY id DESC LIMIT 1; ");
    while ($q = $db->fetch_array($query_experiencia_limite)) {
        $experiencia_limite = $q;
    }

    $usuario = null;
    $avatar = null;
    $ficha = null;

    while ($u = $db->fetch_array($query_usuario)) {
        $avatar = $u['avatar'];

        if ($avatar == '') {
            $avatar = '/images/default_avatar.png';
        }

        if (substr($avatar, 0, 18) == './uploads/avatars/') {
            $newAvatar = substr($avatar, 1);
            $db->query(" UPDATE `mybb_users` SET `avatar`='$newAvatar' WHERE `uid`='$query_uid'; ");
        } 

        // if (substr($u['avatar'], 0, 18) == './uploads/avatars/') {
        //     $avatar = './.' . $u['avatar'];
        // } else {
        //     $avatar = '/images/default_avatar.png';
        // }
        $usuario = $u;
    }
    
    $query_ficha2 = $db->query("
        SELECT * FROM mybb_op_fichas WHERE fid='$query_uid'
    ");

    while ($q = $db->fetch_array($query_ficha2)) {
        $ficha = $q;

        $akuma_imagen = '';

        if ($ficha['akuma'] != '') {

            $akuma = $ficha['akuma'];
            $akuma_subnombre = $ficha['akuma_subnombre'];

            $query_akuma = $db->query(" SELECT * FROM mybb_op_akumas WHERE nombre='$akuma' AND subnombre='$akuma_subnombre' ");

            while ($q = $db->fetch_array($query_akuma)) { $akuma_imagen = $q['imagen'];  }
        }

        $vitalidad_extra = 0;
		$energia_extra = 0;
		$haki_extra = 0;
        $vitalidad_virtud = 0;
        $energia_virtud = 0;

        $sum_stats = intval($ficha['fuerza']) + intval($ficha['resistencia']) + intval($ficha['reflejos']) + 
        intval($ficha['punteria']) + intval($ficha['voluntad']) + intval($ficha['agilidad']) +
        intval($ficha['destreza']);

        $vitalidad_extra = ((intval($ficha['fuerza_pasiva']) * 6) + (intval($ficha['resistencia_pasiva']) * 15) + (intval($ficha['destreza_pasiva']) * 4) +
            (intval($ficha['agilidad_pasiva']) * 3) + (intval($ficha['voluntad_pasiva']) * 1) + (intval($ficha['punteria_pasiva']) * 1) + (intval($ficha['reflejos_pasiva']) * 1));

        if (intval($ficha['fuerza_pasiva'])) {
            $energia_extra += (intval($ficha['fuerza_pasiva']) * 1);
        }

        if (intval($ficha['resistencia_pasiva'])) {
            $energia_extra += (intval($ficha['resistencia_pasiva']) * 3);
        }

        if (intval($ficha['punteria_pasiva'])) {
            $energia_extra += (intval($ficha['punteria_pasiva']) * 6);
        }

        if (intval($ficha['destreza_pasiva'])) {
            $energia_extra += (intval($ficha['destreza_pasiva']) * 3);
        }

        if (intval($ficha['agilidad_pasiva'])) {
            $energia_extra += (intval($ficha['agilidad_pasiva']) * 4);
        }

        if (intval($ficha['reflejos_pasiva'])) {
            $energia_extra += (intval($ficha['reflejos_pasiva']) * 1);
        }

        if (intval($ficha['voluntad_pasiva'])) {
            $energia_extra += (intval($ficha['voluntad_pasiva']) * 1);
            $haki_extra += (intval($ficha['voluntad_pasiva']) * 10);
        }

		$vitalidad_completa = intval($ficha['vitalidad']) + $vitalidad_extra + intval($ficha['vitalidad_pasiva']); 
		$energia_completa = intval($ficha['energia']) + $energia_extra + intval($ficha['energia_pasiva']); 
		$haki_completo = intval($ficha['haki']) + $haki_extra + intval($ficha['haki_pasiva']); 

        $fuerza_completa = intval($ficha['fuerza']) + intval($ficha['fuerza_pasiva']);
		$resistencia_completa = intval($ficha['resistencia']) + intval($ficha['resistencia_pasiva']);
		$destreza_completa = intval($ficha['destreza']) + intval($ficha['destreza_pasiva']);
		$punteria_completa = intval($ficha['punteria']) + intval($ficha['punteria_pasiva']);
        $agilidad_completa = intval($ficha['agilidad']) + intval($ficha['agilidad_pasiva']);
        $reflejos_completa = intval($ficha['reflejos']) + intval($ficha['reflejos_pasiva']);
        $voluntad_completa = intval($ficha['voluntad']) + intval($ficha['voluntad_pasiva']);
    }

    $faccion = $ficha['faccion'];
    $historia = nl2br($ficha['historia']);
    $apariencia = nl2br($ficha['apariencia']);
    $personalidad = nl2br($ficha['personalidad']);
    $extra = nl2br($ficha['extra']);
    $frase = nl2br($ficha['frase']);
    $rasgos_positivos = nl2br($ficha['rasgos_positivos']);
    $rasgos_negativos = nl2br($ficha['rasgos_negativos']);
    $puntos_estadistica = intval($ficha['puntos_estadistica']);
    $fama = $ficha['fama'];

    // zona privada, solo para admins
    if ($should_see_private) {

    }

    if ($faccion == 'Pirata') {
        $faccionColor = '#ff0000';  
        $romboColor = '#ff0000';
        $borderTagColor = '#ff0000';
        $rangoColor = 'linear-gradient(42deg, #950000 20%, #ff0000 50%, #950000 80%)';
        $borderColor = '#f63030';
        $borderPillColor = '#fd0202';
    }

    if ($faccion == 'Marina') {
        $faccionColor = '#00bafc';  
        $romboColor = '#0039ed';
        $borderTagColor = '#00bafc';
        $rangoColor = 'linear-gradient(42deg, #002282 20%, #00b8fa 50%, #002282 80%)';
        $borderColor = '#0055bb';
        $borderPillColor = '#0038c7';
    }

    if ($faccion == 'CipherPol') {
        $faccionColor = '#08002c';  
        $romboColor = '#6534aa';
        $borderTagColor = '#08002c';
        $rangoColor = 'linear-gradient(42deg, #1b1424 20%, #9577ba 50%, #1b1424 80%)';
        $borderColor = '#ac30d9';
        $borderPillColor = '#861fac';
    }

    if ($faccion == 'Cazadores') {
        $faccionColor = '#00c200';  
        $romboColor = '#00ab00';
        $borderTagColor = '#00c200';
        $rangoColor = 'linear-gradient(42deg, #0f2313 20%, #46af70 50%, #0f2313 80%)';
        $borderColor = '#00d506';
        $borderPillColor = '#007400';
    }

    if ($faccion == 'Revolucionario') {
        $faccionColor = '#be9d6f';  
        $romboColor = '#7d6452';
        $borderTagColor = '#be9d6f';
        $rangoColor = 'linear-gradient(42deg, #4e3e2c 20%, #e9c696 50%, #4e3e2c 80%)';
        $borderColor = '#9d8771';
        $borderPillColor = '#937e67';
    }

    if ($faccion == 'Civil') {
        $faccionColor = '#ff0283';  
        $romboColor = '#c6005c';
        $borderTagColor = '#ff0283';
        $rangoColor = 'linear-gradient(42deg, #950044 20%, #f40277 50%, #950044 80%)';
        $borderColor = '#e0428d';
        $borderPillColor = '#c30041';
    }

    $query_virtudes = $db->query("
        SELECT * FROM `mybb_op_virtudes` 
        INNER JOIN `mybb_op_virtudes_usuarios` 
        ON `mybb_op_virtudes`.`virtud_id`=`mybb_op_virtudes_usuarios`.`virtud_id` 
        WHERE `mybb_op_virtudes_usuarios`.`uid`='$query_uid' AND puntos > 0 order by nombre;
    ");

    $query_defectos = $db->query("
        SELECT * FROM `mybb_op_virtudes` 
        INNER JOIN `mybb_op_virtudes_usuarios` 
        ON `mybb_op_virtudes`.`virtud_id`=`mybb_op_virtudes_usuarios`.`virtud_id` 
        WHERE `mybb_op_virtudes_usuarios`.`uid`='$query_uid' AND puntos < 0 order by nombre;
    ");

    $virtudes = array();
    $virtudes_array = array();
    $defectos = array();
    $defectos_array = array();
    
    while ($q = $db->fetch_array($query_virtudes)) { 
        $virtud_id = $q['virtud_id'];
        $key = "$virtud_id";
        if (!$virtudes[$key]) { $virtudes[$key] = array(); }
        array_push($virtudes[$key], $q);
        array_push($virtudes_array, $virtud_id);
    }

    while ($q = $db->fetch_array($query_defectos)) { 
        $virtud_id = $q['virtud_id'];
        $key = "$virtud_id";
        if (!$defectos[$key]) { $defectos[$key] = array(); }
        array_push($defectos[$key], $q);
        array_push($defectos_array, $virtud_id);
    }

    $virtudes_array_json = json_encode($virtudes_array);
    $virtudes_json = json_encode($virtudes);

    $defectos_array_json = json_encode($defectos_array);
    $defectos_json = json_encode($defectos);

    $experiencia = intval(floor($usuario['newpoints']));
    $nivel = $ficha['nivel'];

	$nivelPorcentaje = 100; 
	$expMax = 50;
    $expRem = 0;

    if ($virtudes['V017']) { // Mejora Reputacion
        $reputacion = intval($ficha['reputacion']) * 1.10;
        $reputacionPositiva = intval($ficha['reputacion_positiva']) * 1.10;
        $reputacionNegativa = intval($ficha['reputacion_negativa']) * 1.10;
    } else if ($defectos['D013']) { // Don Nadie
        $reputacion = intval($ficha['reputacion']) * 0.9;
        $reputacionPositiva = intval($ficha['reputacion_positiva']) * 0.9;
        $reputacionNegativa = intval($ficha['reputacion_negativa']) * 0.9;
    } else {
        $reputacion = intval($ficha['reputacion']);
        $reputacionPositiva = intval($ficha['reputacion_positiva']);
        $reputacionNegativa = intval($ficha['reputacion_negativa']);
    }

    if ($virtudes['V037']) { // Vigoroso 1 2 3
        $vitalidad_completa += intval($nivel) * 10; 
    } else if ($virtudes['V038']) {
        $vitalidad_completa += intval($nivel) * 15; 
    } else if ($virtudes['V039']) {
        $vitalidad_completa += intval($nivel) * 20; 
    }

    if ($virtudes['V040']) { // Hiperactivo 1 2
        $energia_completa += intval($nivel) * 10; 
    } else if ($virtudes['V041']) {
        $energia_completa += intval($nivel) * 15; 
    } 

    if ($virtudes['V058']) { // Espiritual 1 2
        $haki_completo += intval($nivel) * 5; 
    } else if ($virtudes['V059']) {
        $haki_completo += intval($nivel) * 10; 
    } 

    $reputacionDiff = $reputacionPositiva - $reputacionNegativa;
    $reputacionPerc = 50;

    if ($reputacion != 0) {
        $reputacionPerc = round((($reputacionPositiva / $reputacion) * 100));
    }

    if ($reputacionPerc >= 0 && $reputacionPerc <= 20) {
        $reputacionImagen = 'ReputacionNegativa';
    } else if ($reputacionPerc > 20 && $reputacionPerc <= 40) {
        $reputacionImagen = 'ReputacionNeutralMala';
    } else if ($reputacionPerc > 40 && $reputacionPerc < 60) {
        $reputacionImagen = 'ReputacionNeutral';
    } else if ($reputacionPerc >= 60 && $reputacionPerc < 80) {
        $reputacionImagen = 'ReputacionNeutralBuena';
    } else if ($reputacionPerc >= 80 && $reputacionPerc <= 100) {
        $reputacionImagen = 'ReputacionPositiva';
    } else {
        $reputacionImagen = 'ReputacionNeutral';
    }

    if ($reputacion >= 5000 && $reputacionPerc >= 80) {
        $fama = 'Héroe';
    } else if ($reputacion >= 5000 && $reputacionPerc <= 20) {
        $fama = 'Calamidad';
    } else if ($reputacion >= 5000) {
        $fama = 'Leyenda';
    } else if ($reputacion >= 3001 && $reputacionPerc >= 80) {
        $fama = 'Paladín';
    } else if ($reputacion >= 3001 && $reputacionPerc <= 20) {
        $fama = 'Pesadilla';
    } else if ($reputacion >= 3001) {
        $fama = 'Ícono';
    } else if ($reputacion >= 1501 && $reputacionPerc >= 80) {
        $fama = 'Santo';
    } else if ($reputacion >= 1501 && $reputacionPerc <= 20) {
        $fama = 'Infame';
    } else if ($reputacion >= 1501) {
        $fama = 'Famoso';
    } else if ($reputacion >= 601 && $reputacionPerc >= 80) {
        $fama = 'Justiciero';
    } else if ($reputacion >= 601 && $reputacionPerc <= 20) {
        $fama = 'Criminal';
    } else if ($reputacion >= 601) {
        $fama = 'Popular';
    } else if ($reputacion >= 301 && $reputacionPerc >= 80) {
        $fama = 'Noble';
    } else if ($reputacion >= 301 && $reputacionPerc <= 20) {
        $fama = 'Delincuente';
    } else if ($reputacion >= 301) {
        $fama = 'Aspirante';
    } else if ($reputacion >= 101 && $reputacionPerc >= 80) {
        $fama = 'Honorable';
    } else if ($reputacion >= 101 && $reputacionPerc <= 20) {
        $fama = 'Forajido';
    } else if ($reputacion >= 101) {
        $fama = 'Rumor';
    } else if ($reputacion >= 51) {
        $fama = 'Novato';
    } else if ($reputacion >= 26) {
        $fama = 'Iniciado';
    } else {
        $fama = 'Desconocido';
    }


    $rango = $ficha['rango'];

    if ($faccion == 'Marina' && $rango == 'SargentoM' && $reputacion >= 51 && intval($nivel) >= 11) {
        $rango = 'Suboficial';
    } else if ($faccion == 'Marina' && $rango == 'SoldadoRaso' && $reputacion >= 51 && intval($nivel) >= 8) {
        $rango = 'SargentoM';
    } else if ($faccion == 'Marina' && $rango == 'ReclutaM' && $reputacion >= 26 && intval($nivel) >= 4) {
        $rango = 'SoldadoRaso';
    }

    if ($faccion == 'CipherPol' && $rango == 'CipherPol6' && $reputacion >= 101 && intval($nivel) >= 19) {
        $rango = 'CipherPol7';
    } else if ($faccion == 'CipherPol' && $rango == 'CipherPol5' && $reputacion >= 101 && intval($nivel) >= 16) {
        $rango = 'CipherPol6';
    } else if ($faccion == 'CipherPol' && $rango == 'CipherPol4' && $reputacion >= 51 && intval($nivel) >= 13) {
        $rango = 'CipherPol5';
    } else if ($faccion == 'CipherPol' && $rango == 'CipherPol3' && $reputacion >= 51 && intval($nivel) >= 10) {
        $rango = 'CipherPol4';
    } else if ($faccion == 'CipherPol' && $rango == 'CipherPol2' && $reputacion >= 26 && intval($nivel) >= 7) {
        $rango = 'CipherPol3';
    } else if ($faccion == 'CipherPol' && $rango == 'CipherPol1' && $reputacion >= 26 && intval($nivel) >= 4) {
        $rango = 'CipherPol2';
    } 

    if ($faccion == 'Revolucionario' && $rango == 'SargentoR' && $reputacion >= 51 && intval($nivel) >= 11) {
        $rango = 'Agente';
    } else if ($faccion == 'Revolucionario' && $rango == 'Soldado' && $reputacion >= 51 && intval($nivel) >= 8) {
        $rango = 'SargentoR';
    } else if ($faccion == 'Revolucionario' && $rango == 'ReclutaR' && $reputacion >= 26 && intval($nivel) >= 4) {
        $rango = 'Soldado';
    }

    if ($rango != $ficha['rango']) { $db->query(" UPDATE `mybb_op_fichas` SET `rango`='$rango' WHERE `fid`='$query_uid'; "); }
    if ($fama != $ficha['fama']) { $db->query(" UPDATE `mybb_op_fichas` SET `fama`='$fama' WHERE `fid`='$query_uid'; "); }

    if ($experiencia >= 0 && $experiencia < 50 && $nivel == '1') {
        $expMin = 0;
		$expMax = 50;
	} else if ($experiencia >= 50 && $experiencia < 125 && $nivel == '2') {
        $expMin = 50;
		$expMax = 125;
	} else if ($experiencia >= 125 && $experiencia < 225 && $nivel == '3') {
        $expMin = 125;
		$expMax = 225;
	} else if ($experiencia >= 225 && $experiencia < 350 && $nivel == '4') {
        $expMin = 225;
		$expMax = 350;
	} else if ($experiencia >= 350 && $experiencia < 500 && $nivel == '5') {
        $expMin = 350;
		$expMax = 500;
	} else if ($experiencia >= 500 && $experiencia < 675 && $nivel == '6') {
        $expMin = 500;
		$expMax = 675;
	} else if ($experiencia >= 675 && $experiencia < 875 && $nivel == '7') {
        $expMin = 675;
		$expMax = 875;
	} else if ($experiencia >= 875 && $experiencia < 1100 && $nivel == '8') {
        $expMin = 875;
		$expMax = 1100;
	} else if ($experiencia >= 1100 && $experiencia < 1350 && $nivel == '9') {
        $expMin = 1100;
		$expMax = 1350;
	} else if ($experiencia >= 1350 && $experiencia < 1625 && $nivel == '10') {
        $expMin = 1350;
		$expMax = 1625;
	} else if ($experiencia >= 1625 && $experiencia < 1925 && $nivel == '11') {
        $expMin = 1625;
		$expMax = 1925;
	} else if ($experiencia >= 1925 && $experiencia < 2250 && $nivel == '12') {
        $expMin = 1925;
		$expMax = 2250;
	} else if ($experiencia >= 2250 && $experiencia < 2600 && $nivel == '13') {
        $expMin = 2250;
		$expMax = 2600;
	} else if ($experiencia >= 2600 && $experiencia < 2975 && $nivel == '14') {
        $expMin = 2600;
		$expMax = 2975;
	} else if ($experiencia >= 2975 && $experiencia < 3375 && $nivel == '15') {
        $expMin = 2975;
		$expMax = 3375;
	} else if ($experiencia >= 3375 && $experiencia < 3800 && $nivel == '16') {
        $expMin = 3375;
		$expMax = 3800;
	} else if ($experiencia >= 3800 && $experiencia < 4250 && $nivel == '17') {
        $expMin = 3800;
		$expMax = 4250;
	} else if ($experiencia >= 4250 && $experiencia < 4725 && $nivel == '18') {
        $expMin = 4250;
		$expMax = 4725;
	} else if ($experiencia >= 4725 && $experiencia < 5225 && $nivel == '19') {
        $expMin = 4725;
		$expMax = 5225;
	} else if ($experiencia >= 5225 && $experiencia < 5750 && $nivel == '20') {
        $expMin = 5225;
		$expMax = 5750;
	} else if ($experiencia >= 5750 && $experiencia < 6300 && $nivel == '21') {
        $expMin = 5750;
		$expMax = 6300;
	} else if ($experiencia >= 6300 && $experiencia < 6870 && $nivel == '22') {
        $expMin = 6300;
		$expMax = 6870;
	} else if ($experiencia >= 6870 && $experiencia < 7460 && $nivel == '23') {
        $expMin = 6870;
		$expMax = 7460;
	} else if ($experiencia >= 7460 && $experiencia < 8070 && $nivel == '24') {
        $expMin = 7460;
		$expMax = 8070;
	} else if ($experiencia >= 8070 && $experiencia < 8700 && $nivel == '25') {
        $expMin = 8070;
		$expMax = 8700;
	} else if ($experiencia >= 8700 && $experiencia < 9350 && $nivel == '26') {
        $expMin = 8700;
		$expMax = 9350;
	} else if ($experiencia >= 9350 && $experiencia < 10020 && $nivel == '27') {
        $expMin = 9350;
		$expMax = 10020;
	} else if ($experiencia >= 10020 && $experiencia < 10700 && $nivel == '28') {
        $expMin = 10020;
		$expMax = 10700;
	} else if ($experiencia >= 10700 && $experiencia < 11400 && $nivel == '29') {
        $expMin = 10700;
		$expMax = 11400;
	} else if ($experiencia >= 11400 && $experiencia < 12120 && $nivel == '30') {
        $expMin = 11400;
		$expMax = 12120;
	} else if ($experiencia >= 12120 && $experiencia < 12860 && $nivel == '31') {
        $expMin = 12120;
		$expMax = 12860;
	} else if ($experiencia >= 12860 && $experiencia < 13620 && $nivel == '32') {
        $expMin = 12860;
		$expMax = 13620;
	} else if ($experiencia >= 13620 && $experiencia < 14400 && $nivel == '33') {
        $expMin = 13620;
		$expMax = 14400;
	} else if ($experiencia >= 14400 && $experiencia < 15200 && $nivel == '34') {
        $expMin = 14400;
		$expMax = 15200;
	} else if ($experiencia >= 15200 && $experiencia < 16020 && $nivel == '35') {
        $expMin = 15200;
		$expMax = 16020;
	} else if ($experiencia >= 16020 && $experiencia < 16860 && $nivel == '36') {
        $expMin = 16020;
		$expMax = 16860;
	} else if ($experiencia >= 16860 && $experiencia < 17720 && $nivel == '37') {
        $expMin = 16860;
		$expMax = 17720;
	} else if ($experiencia >= 17720 && $experiencia < 18600 && $nivel == '38') {
        $expMin = 17720;
		$expMax = 18600;
	} else if ($experiencia >= 18600 && $experiencia < 19500 && $nivel == '39') {
        $expMin = 18600;
		$expMax = 19500;
	} else if ($experiencia >= 19500 && $experiencia < 20425 && $nivel == '40') {
        $expMin = 19500;
		$expMax = 20425;
	} else if ($experiencia >= 20425 && $experiencia < 21375 && $nivel == '41') {
        $expMin = 20425;
		$expMax = 21375;
	} else if ($experiencia >= 21375 && $experiencia < 22350 && $nivel == '42') {
        $expMin = 21375;
		$expMax = 22350;
	} else if ($experiencia >= 22350 && $experiencia < 23350 && $nivel == '43') {
        $expMin = 22350;
		$expMax = 23350;
	} else if ($experiencia >= 23350 && $experiencia < 24375 && $nivel == '44') {
        $expMin = 23350;
		$expMax = 24375;
	} else if ($experiencia >= 24375 && $experiencia < 25425 && $nivel == '45') {
        $expMin = 24375;
		$expMax = 25425;
	} else if ($experiencia >= 25425 && $experiencia < 26500 && $nivel == '46') {
        $expMin = 25425;
		$expMax = 26500;
	} else if ($experiencia >= 26500 && $experiencia < 27600 && $nivel == '47') {
        $expMin = 26500;
		$expMax = 27600;
	} else if ($experiencia >= 27600 && $experiencia < 28750 && $nivel == '48') {
        $expMin = 27600;
		$expMax = 28750;
	} else if ($experiencia >= 28750 && $experiencia < 30000 && $nivel == '49') {
        $expMin = 28750;
		$expMax = 30000;
	} else if ($experiencia >= 30000 && $nivel == '50') {
        $expMin = 30000;
		$expMax = 30000;
	} 

    $expRem = $expMax - $experiencia; 

    $nivelPorcentaje = floor(($experiencia - $expMin) / ($expMax - $expMin) * 100);
    // $nivelPorcentaje = floor((($expMax - $expMin) * ($experiencia - $expMin)) / 100);

    if ($nivelPorcentaje == 0) { $nivelPorcentaje = 1; }

    $limite_nivel = intval($ficha['limite_nivel']);


    $raza = $ficha['raza'];

    if ($experiencia >= 30000 && $nivel == '49' && $limite_nivel > 49) {
        $nivel = 50;
        $puntos_estadistica += 20;
        $db->query("  UPDATE `mybb_op_fichas` SET `nivel`='$nivel',`puntos_estadistica`='$puntos_estadistica' WHERE `fid`='$query_uid'; ");
        if ($raza == 'Skypian') { $db->query("  UPDATE `mybb_op_fichas` SET `energia_pasiva`=`energia_pasiva` + 5 WHERE `fid`='$query_uid'; "); }
    } else if ($experiencia >= 28750 && $nivel == '48' && $limite_nivel > 48) {
        $nivel = 49;
        $puntos_estadistica += 10;
        $db->query("  UPDATE `mybb_op_fichas` SET `nivel`='$nivel',`puntos_estadistica`='$puntos_estadistica' WHERE `fid`='$query_uid'; ");
        if ($raza == 'Skypian') { $db->query("  UPDATE `mybb_op_fichas` SET `energia_pasiva`=`energia_pasiva` + 5 WHERE `fid`='$query_uid'; "); }
    } else if ($experiencia >= 27600 && $nivel == '47' && $limite_nivel > 47) {
        $nivel = 48;
        $puntos_estadistica += 10;
        $db->query("  UPDATE `mybb_op_fichas` SET `nivel`='$nivel',`puntos_estadistica`='$puntos_estadistica' WHERE `fid`='$query_uid'; ");
        if ($raza == 'Skypian') { $db->query("  UPDATE `mybb_op_fichas` SET `energia_pasiva`=`energia_pasiva` + 5 WHERE `fid`='$query_uid'; "); }
    } elseif ($experiencia >= 26500 && $nivel == '46' && $limite_nivel > 46) {
        $nivel = 47;
        $puntos_estadistica += 10;
        $db->query("  UPDATE `mybb_op_fichas` SET `nivel`='$nivel',`puntos_estadistica`='$puntos_estadistica' WHERE `fid`='$query_uid'; ");
        if ($raza == 'Skypian') { $db->query("  UPDATE `mybb_op_fichas` SET `energia_pasiva`=`energia_pasiva` + 5 WHERE `fid`='$query_uid'; "); }
    } else if ($experiencia >= 25425 && $nivel == '45' && $limite_nivel > 45) {
        $nivel = 46;
        $puntos_estadistica += 10;
        $db->query("  UPDATE `mybb_op_fichas` SET `nivel`='$nivel',`puntos_estadistica`='$puntos_estadistica' WHERE `fid`='$query_uid'; ");
        if ($raza == 'Skypian') { $db->query("  UPDATE `mybb_op_fichas` SET `energia_pasiva`=`energia_pasiva` + 5 WHERE `fid`='$query_uid'; "); }
    } else if ($experiencia >= 24375 && $nivel == '44' && $limite_nivel > 44) {
        $nivel = 45;
        $puntos_estadistica += 10;
        $db->query("  UPDATE `mybb_op_fichas` SET `nivel`='$nivel',`puntos_estadistica`='$puntos_estadistica' WHERE `fid`='$query_uid'; ");
        if ($raza == 'Skypian') { $db->query("  UPDATE `mybb_op_fichas` SET `energia_pasiva`=`energia_pasiva` + 5 WHERE `fid`='$query_uid'; "); }
    } else if ($experiencia >= 23350 && $nivel == '43' && $limite_nivel > 43) {
        $nivel = 44;
        $puntos_estadistica += 10;
        $db->query("  UPDATE `mybb_op_fichas` SET `nivel`='$nivel',`puntos_estadistica`='$puntos_estadistica' WHERE `fid`='$query_uid'; ");
        if ($raza == 'Skypian') { $db->query("  UPDATE `mybb_op_fichas` SET `energia_pasiva`=`energia_pasiva` + 5 WHERE `fid`='$query_uid'; "); }
    } else if ($experiencia >= 22350 && $nivel == '42' && $limite_nivel > 42) {
        $nivel = 43;
        $puntos_estadistica += 10;
        $db->query("  UPDATE `mybb_op_fichas` SET `nivel`='$nivel',`puntos_estadistica`='$puntos_estadistica' WHERE `fid`='$query_uid'; ");
        if ($raza == 'Skypian') { $db->query("  UPDATE `mybb_op_fichas` SET `energia_pasiva`=`energia_pasiva` + 5 WHERE `fid`='$query_uid'; "); }
    } else if ($experiencia >= 21375 && $nivel == '41' && $limite_nivel > 41) {
        $nivel = 42;
        $puntos_estadistica += 10;
        $db->query("  UPDATE `mybb_op_fichas` SET `nivel`='$nivel',`puntos_estadistica`='$puntos_estadistica' WHERE `fid`='$query_uid'; ");
        if ($raza == 'Skypian') { $db->query("  UPDATE `mybb_op_fichas` SET `energia_pasiva`=`energia_pasiva` + 5 WHERE `fid`='$query_uid'; "); }
    } else if ($experiencia >= 20425 && $nivel == '40' && $limite_nivel > 40) {
        $nivel = 41;
        $puntos_estadistica += 10;
        $db->query("  UPDATE `mybb_op_fichas` SET `nivel`='$nivel',`puntos_estadistica`='$puntos_estadistica' WHERE `fid`='$query_uid'; ");
        if ($raza == 'Skypian') { $db->query("  UPDATE `mybb_op_fichas` SET `energia_pasiva`=`energia_pasiva` + 5 WHERE `fid`='$query_uid'; "); }
    } else if ($experiencia >= 19500 && $nivel == '39' && $limite_nivel > 39) {
        $nivel = 40;
        $puntos_estadistica += 20;
        $db->query("  UPDATE `mybb_op_fichas` SET `nivel`='$nivel',`puntos_estadistica`='$puntos_estadistica' WHERE `fid`='$query_uid'; ");
        if ($raza == 'Skypian') { $db->query("  UPDATE `mybb_op_fichas` SET `energia_pasiva`=`energia_pasiva` + 5 WHERE `fid`='$query_uid'; "); }
    } else if ($experiencia >= 18600 && $nivel == '38' && $limite_nivel > 38) {
        $nivel = 39;
        $puntos_estadistica += 10;
        $db->query("  UPDATE `mybb_op_fichas` SET `nivel`='$nivel',`puntos_estadistica`='$puntos_estadistica' WHERE `fid`='$query_uid'; ");
        if ($raza == 'Skypian') { $db->query("  UPDATE `mybb_op_fichas` SET `energia_pasiva`=`energia_pasiva` + 5 WHERE `fid`='$query_uid'; "); }
    } else if ($experiencia >= 17720 && $nivel == '37' && $limite_nivel > 37) {
        $nivel = 38;
        $puntos_estadistica += 10;
        $db->query("  UPDATE `mybb_op_fichas` SET `nivel`='$nivel',`puntos_estadistica`='$puntos_estadistica' WHERE `fid`='$query_uid'; ");
        if ($raza == 'Skypian') { $db->query("  UPDATE `mybb_op_fichas` SET `energia_pasiva`=`energia_pasiva` + 5 WHERE `fid`='$query_uid'; "); }
    } else if ($experiencia >= 16860 && $nivel == '36' && $limite_nivel > 36) {
        $nivel = 37;
        $puntos_estadistica += 10;
        $db->query("  UPDATE `mybb_op_fichas` SET `nivel`='$nivel',`puntos_estadistica`='$puntos_estadistica' WHERE `fid`='$query_uid'; ");
        if ($raza == 'Skypian') { $db->query("  UPDATE `mybb_op_fichas` SET `energia_pasiva`=`energia_pasiva` + 5 WHERE `fid`='$query_uid'; "); }
    } else if ($experiencia >= 16020 && $nivel == '35' && $limite_nivel > 35) {
        $nivel = 36;
        $puntos_estadistica += 10;
        $db->query("  UPDATE `mybb_op_fichas` SET `nivel`='$nivel',`puntos_estadistica`='$puntos_estadistica' WHERE `fid`='$query_uid'; ");
        if ($raza == 'Skypian') { $db->query("  UPDATE `mybb_op_fichas` SET `energia_pasiva`=`energia_pasiva` + 5 WHERE `fid`='$query_uid'; "); }
    } else if ($experiencia >= 15200 && $nivel == '34' && $limite_nivel > 34) {
        $nivel = 35;
        $puntos_estadistica += 10;
        $db->query("  UPDATE `mybb_op_fichas` SET `nivel`='$nivel',`puntos_estadistica`='$puntos_estadistica' WHERE `fid`='$query_uid'; ");
        if ($raza == 'Skypian') { $db->query("  UPDATE `mybb_op_fichas` SET `energia_pasiva`=`energia_pasiva` + 5 WHERE `fid`='$query_uid'; "); }
    } else if ($experiencia >= 14400 && $nivel == '33' && $limite_nivel > 33) {
        $nivel = 34;
        $puntos_estadistica += 10;
        $db->query("  UPDATE `mybb_op_fichas` SET `nivel`='$nivel',`puntos_estadistica`='$puntos_estadistica' WHERE `fid`='$query_uid'; ");
        if ($raza == 'Skypian') { $db->query("  UPDATE `mybb_op_fichas` SET `energia_pasiva`=`energia_pasiva` + 5 WHERE `fid`='$query_uid'; "); }
    } else if ($experiencia >= 13620 && $nivel == '32' && $limite_nivel > 32) {
        $nivel = 33;
        $puntos_estadistica += 10;
        $db->query("  UPDATE `mybb_op_fichas` SET `nivel`='$nivel',`puntos_estadistica`='$puntos_estadistica' WHERE `fid`='$query_uid'; ");
        if ($raza == 'Skypian') { $db->query("  UPDATE `mybb_op_fichas` SET `energia_pasiva`=`energia_pasiva` + 5 WHERE `fid`='$query_uid'; "); }
    } else if ($experiencia >= 12860 && $nivel == '31' && $limite_nivel > 31) {
        $nivel = 32;
        $puntos_estadistica += 10;
        $db->query("  UPDATE `mybb_op_fichas` SET `nivel`='$nivel',`puntos_estadistica`='$puntos_estadistica' WHERE `fid`='$query_uid'; ");
        if ($raza == 'Skypian') { $db->query("  UPDATE `mybb_op_fichas` SET `energia_pasiva`=`energia_pasiva` + 5 WHERE `fid`='$query_uid'; "); }
    } else if ($experiencia >= 12120 && $nivel == '30' && $limite_nivel > 30) {
        $nivel = 31;
        $puntos_estadistica += 10;
        $db->query("  UPDATE `mybb_op_fichas` SET `nivel`='$nivel',`puntos_estadistica`='$puntos_estadistica' WHERE `fid`='$query_uid'; ");
        if ($raza == 'Skypian') { $db->query("  UPDATE `mybb_op_fichas` SET `energia_pasiva`=`energia_pasiva` + 5 WHERE `fid`='$query_uid'; "); }
    } else if ($experiencia >= 11400 && $nivel == '29' && $limite_nivel > 29) {
        $nivel = 30;
        $puntos_estadistica += 20;
        $db->query("  UPDATE `mybb_op_fichas` SET `nivel`='$nivel',`puntos_estadistica`='$puntos_estadistica' WHERE `fid`='$query_uid'; ");
        if ($raza == 'Skypian') { $db->query("  UPDATE `mybb_op_fichas` SET `energia_pasiva`=`energia_pasiva` + 5 WHERE `fid`='$query_uid'; "); }
    } else if ($experiencia >= 10700 && $nivel == '28' && $limite_nivel > 28) {
        $nivel = 29;
        $puntos_estadistica += 10;
        $db->query("  UPDATE `mybb_op_fichas` SET `nivel`='$nivel',`puntos_estadistica`='$puntos_estadistica' WHERE `fid`='$query_uid'; ");
        if ($raza == 'Skypian') { $db->query("  UPDATE `mybb_op_fichas` SET `energia_pasiva`=`energia_pasiva` + 5 WHERE `fid`='$query_uid'; "); }
    } else if ($experiencia >= 10020 && $nivel == '27' && $limite_nivel > 27) {
        $nivel = 28;
        $puntos_estadistica += 10;
        $db->query("  UPDATE `mybb_op_fichas` SET `nivel`='$nivel',`puntos_estadistica`='$puntos_estadistica' WHERE `fid`='$query_uid'; ");
        if ($raza == 'Skypian') { $db->query("  UPDATE `mybb_op_fichas` SET `energia_pasiva`=`energia_pasiva` + 5 WHERE `fid`='$query_uid'; "); }
    } else if ($experiencia >= 9350 && $nivel == '26' && $limite_nivel > 26) {
        $nivel = 27;
        $puntos_estadistica += 10;
        $db->query("  UPDATE `mybb_op_fichas` SET `nivel`='$nivel',`puntos_estadistica`='$puntos_estadistica' WHERE `fid`='$query_uid'; ");
        if ($raza == 'Skypian') { $db->query("  UPDATE `mybb_op_fichas` SET `energia_pasiva`=`energia_pasiva` + 5 WHERE `fid`='$query_uid'; "); }
    } else if ($experiencia >= 8700 && $nivel == '25' && $limite_nivel > 25) {
        $nivel = 26;
        $puntos_estadistica += 10;
        $db->query("  UPDATE `mybb_op_fichas` SET `nivel`='$nivel',`puntos_estadistica`='$puntos_estadistica' WHERE `fid`='$query_uid'; ");
        if ($raza == 'Skypian') { $db->query("  UPDATE `mybb_op_fichas` SET `energia_pasiva`=`energia_pasiva` + 5 WHERE `fid`='$query_uid'; "); }
    } else if ($experiencia >= 8070 && $nivel == '24' && $limite_nivel > 24) {
        $nivel = 25;
        $puntos_estadistica += 10;
        $db->query("  UPDATE `mybb_op_fichas` SET `nivel`='$nivel',`puntos_estadistica`='$puntos_estadistica' WHERE `fid`='$query_uid'; ");
        if ($raza == 'Skypian') { $db->query("  UPDATE `mybb_op_fichas` SET `energia_pasiva`=`energia_pasiva` + 5 WHERE `fid`='$query_uid'; "); }
    } else if ($experiencia >= 7460 && $nivel == '23' && $limite_nivel > 23) {
        $nivel = 24;
        $puntos_estadistica += 10;
        $db->query("  UPDATE `mybb_op_fichas` SET `nivel`='$nivel',`puntos_estadistica`='$puntos_estadistica' WHERE `fid`='$query_uid'; ");
        if ($raza == 'Skypian') { $db->query("  UPDATE `mybb_op_fichas` SET `energia_pasiva`=`energia_pasiva` + 5 WHERE `fid`='$query_uid'; "); }
    } else if ($experiencia >= 6870 && $nivel == '22' && $limite_nivel > 22) {
        $nivel = 23;
        $puntos_estadistica += 10;
        $db->query("  UPDATE `mybb_op_fichas` SET `nivel`='$nivel',`puntos_estadistica`='$puntos_estadistica' WHERE `fid`='$query_uid'; ");
        if ($raza == 'Skypian') { $db->query("  UPDATE `mybb_op_fichas` SET `energia_pasiva`=`energia_pasiva` + 5 WHERE `fid`='$query_uid'; "); }
    } else if ($experiencia >= 6300 && $nivel == '21' && $limite_nivel > 21) {
        $nivel = 22;
        $puntos_estadistica += 10;
        $db->query("  UPDATE `mybb_op_fichas` SET `nivel`='$nivel',`puntos_estadistica`='$puntos_estadistica' WHERE `fid`='$query_uid'; ");
        if ($raza == 'Skypian') { $db->query("  UPDATE `mybb_op_fichas` SET `energia_pasiva`=`energia_pasiva` + 5 WHERE `fid`='$query_uid'; "); }
    } else if ($experiencia >= 5775 && $nivel == '20' && $limite_nivel > 20) {
        $nivel = 21;
        $puntos_estadistica += 10;
        $db->query("  UPDATE `mybb_op_fichas` SET `nivel`='$nivel',`puntos_estadistica`='$puntos_estadistica' WHERE `fid`='$query_uid'; ");
        if ($raza == 'Skypian') { $db->query("  UPDATE `mybb_op_fichas` SET `energia_pasiva`=`energia_pasiva` + 5 WHERE `fid`='$query_uid'; "); }
    } else if ($experiencia >= 5225 && $nivel == '19') {
        $nivel = 20;
        $puntos_estadistica += 20;
        $db->query("  UPDATE `mybb_op_fichas` SET `nivel`='$nivel',`puntos_estadistica`='$puntos_estadistica' WHERE `fid`='$query_uid'; ");
        if ($raza == 'Skypian') { $db->query("  UPDATE `mybb_op_fichas` SET `energia_pasiva`=`energia_pasiva` + 5 WHERE `fid`='$query_uid'; "); }
    } else if ($experiencia >= 4725 && $nivel == '18') {
        $nivel = 19;
        $puntos_estadistica += 10;
        $db->query("  UPDATE `mybb_op_fichas` SET `nivel`='$nivel',`puntos_estadistica`='$puntos_estadistica' WHERE `fid`='$query_uid'; ");
        if ($raza == 'Skypian') { $db->query("  UPDATE `mybb_op_fichas` SET `energia_pasiva`=`energia_pasiva` + 5 WHERE `fid`='$query_uid'; "); }
    } else if ($experiencia >= 4250 && $nivel == '17') {
        $nivel = 18;
        $puntos_estadistica += 10;
        $db->query("  UPDATE `mybb_op_fichas` SET `nivel`='$nivel',`puntos_estadistica`='$puntos_estadistica' WHERE `fid`='$query_uid'; ");
        if ($raza == 'Skypian') { $db->query("  UPDATE `mybb_op_fichas` SET `energia_pasiva`=`energia_pasiva` + 5 WHERE `fid`='$query_uid'; "); }
    } else if ($experiencia >= 3800 && $nivel == '16') {
        $nivel = 17;
        $puntos_estadistica += 10;
        $db->query("  UPDATE `mybb_op_fichas` SET `nivel`='$nivel',`puntos_estadistica`='$puntos_estadistica' WHERE `fid`='$query_uid'; ");
        if ($raza == 'Skypian') { $db->query("  UPDATE `mybb_op_fichas` SET `energia_pasiva`=`energia_pasiva` + 5 WHERE `fid`='$query_uid'; "); }
    } else if ($experiencia >= 3375 && $nivel == '15') {
        $nivel = 16;
        $puntos_estadistica += 10;
        $db->query("  UPDATE `mybb_op_fichas` SET `nivel`='$nivel',`puntos_estadistica`='$puntos_estadistica' WHERE `fid`='$query_uid'; ");
        if ($raza == 'Skypian') { $db->query("  UPDATE `mybb_op_fichas` SET `energia_pasiva`=`energia_pasiva` + 5 WHERE `fid`='$query_uid'; "); }
    } else if ($experiencia >= 2975 && $nivel == '14') {
        $nivel = 15;
        $puntos_estadistica += 10;
        $db->query("  UPDATE `mybb_op_fichas` SET `nivel`='$nivel',`puntos_estadistica`='$puntos_estadistica' WHERE `fid`='$query_uid'; ");
        if ($raza == 'Skypian') { $db->query("  UPDATE `mybb_op_fichas` SET `energia_pasiva`=`energia_pasiva` + 5 WHERE `fid`='$query_uid'; "); }
    } else if ($experiencia >= 2600 && $nivel == '13') {
        $nivel = 14;
        $puntos_estadistica += 10;
        $db->query("  UPDATE `mybb_op_fichas` SET `nivel`='$nivel',`puntos_estadistica`='$puntos_estadistica' WHERE `fid`='$query_uid'; ");
        if ($raza == 'Skypian') { $db->query("  UPDATE `mybb_op_fichas` SET `energia_pasiva`=`energia_pasiva` + 5 WHERE `fid`='$query_uid'; "); }
    } else if ($experiencia >= 2250 && $nivel == '12') {
        $nivel = 13;
        $puntos_estadistica += 10;
        $db->query("  UPDATE `mybb_op_fichas` SET `nivel`='$nivel',`puntos_estadistica`='$puntos_estadistica' WHERE `fid`='$query_uid'; ");
        if ($raza == 'Skypian') { $db->query("  UPDATE `mybb_op_fichas` SET `energia_pasiva`=`energia_pasiva` + 5 WHERE `fid`='$query_uid'; "); }
    } else if ($experiencia >= 1925 && $nivel == '11') {
        $nivel = 12;
        $puntos_estadistica += 10;
        $db->query("  UPDATE `mybb_op_fichas` SET `nivel`='$nivel',`puntos_estadistica`='$puntos_estadistica' WHERE `fid`='$query_uid'; ");
        if ($raza == 'Skypian') { $db->query("  UPDATE `mybb_op_fichas` SET `energia_pasiva`=`energia_pasiva` + 5 WHERE `fid`='$query_uid'; "); }
    } else if ($experiencia >= 1625 && $nivel == '10') {
        $nivel = 11;
        $puntos_estadistica += 10;
        $db->query("  UPDATE `mybb_op_fichas` SET `nivel`='$nivel',`puntos_estadistica`='$puntos_estadistica' WHERE `fid`='$query_uid'; ");
        if ($raza == 'Skypian') { $db->query("  UPDATE `mybb_op_fichas` SET `energia_pasiva`=`energia_pasiva` + 5 WHERE `fid`='$query_uid'; "); }
    } else if ($experiencia >= 1350 && $nivel == '9') {
        
        $nivel = 10;
        $puntos_estadistica += 20;
        $db->query("  UPDATE `mybb_op_fichas` SET `nivel`='$nivel',`puntos_estadistica`='$puntos_estadistica' WHERE `fid`='$query_uid'; ");
        if ($raza == 'Skypian') { $db->query("  UPDATE `mybb_op_fichas` SET `energia_pasiva`=`energia_pasiva` + 5 WHERE `fid`='$query_uid'; "); }
    } else if ($experiencia >= 1100 && $nivel == '8') {
        $nivel = 9;
        $puntos_estadistica += 10;
        $db->query("  UPDATE `mybb_op_fichas` SET `nivel`='$nivel',`puntos_estadistica`='$puntos_estadistica' WHERE `fid`='$query_uid'; ");
        if ($raza == 'Skypian') { $db->query("  UPDATE `mybb_op_fichas` SET `energia_pasiva`=`energia_pasiva` + 5 WHERE `fid`='$query_uid'; "); }
    } else if ($experiencia >= 875 && $nivel == '7') {
        $nivel = 8;
        $puntos_estadistica += 10;
        $db->query("  UPDATE `mybb_op_fichas` SET `nivel`='$nivel',`puntos_estadistica`='$puntos_estadistica' WHERE `fid`='$query_uid'; ");
        if ($raza == 'Skypian') { $db->query("  UPDATE `mybb_op_fichas` SET `energia_pasiva`=`energia_pasiva` + 5 WHERE `fid`='$query_uid'; "); }
    } else if ($experiencia >= 675 && $nivel == '6') {
        $nivel = 7;
        $puntos_estadistica += 10;
        $db->query("  UPDATE `mybb_op_fichas` SET `nivel`='$nivel',`puntos_estadistica`='$puntos_estadistica' WHERE `fid`='$query_uid'; ");
        if ($raza == 'Skypian') { $db->query("  UPDATE `mybb_op_fichas` SET `energia_pasiva`=`energia_pasiva` + 5 WHERE `fid`='$query_uid'; "); }
    } else if ($experiencia >= 500 && $nivel == '5') {
        $nivel = 6;
        $puntos_estadistica += 10;
        $db->query("  UPDATE `mybb_op_fichas` SET `nivel`='$nivel',`puntos_estadistica`='$puntos_estadistica' WHERE `fid`='$query_uid'; ");
        if ($raza == 'Skypian') { $db->query("  UPDATE `mybb_op_fichas` SET `energia_pasiva`=`energia_pasiva` + 5 WHERE `fid`='$query_uid'; "); }
    } else if ($experiencia >= 350 && $nivel == '4') {
        $nivel = 5;
        $puntos_estadistica += 10;
        $db->query("  UPDATE `mybb_op_fichas` SET `nivel`='$nivel',`puntos_estadistica`='$puntos_estadistica' WHERE `fid`='$query_uid'; ");
        if ($raza == 'Skypian') { $db->query("  UPDATE `mybb_op_fichas` SET `energia_pasiva`=`energia_pasiva` + 5 WHERE `fid`='$query_uid'; "); }
    } else if ($experiencia >= 225 && $nivel == '3') {
        $nivel = 4;
        $puntos_estadistica += 10;
        $db->query("  UPDATE `mybb_op_fichas` SET `nivel`='$nivel',`puntos_estadistica`='$puntos_estadistica' WHERE `fid`='$query_uid'; ");
        if ($raza == 'Skypian') { $db->query("  UPDATE `mybb_op_fichas` SET `energia_pasiva`=`energia_pasiva` + 5 WHERE `fid`='$query_uid'; "); }
    } else if ($experiencia >= 125 && $nivel == '2') {
        $nivel = 3;
        $puntos_estadistica += 10;
        $db->query("  UPDATE `mybb_op_fichas` SET `nivel`='$nivel',`puntos_estadistica`='$puntos_estadistica' WHERE `fid`='$query_uid'; ");
        if ($raza == 'Skypian') { $db->query("  UPDATE `mybb_op_fichas` SET `energia_pasiva`=`energia_pasiva` + 5 WHERE `fid`='$query_uid'; "); }
    } else if ($experiencia >= 50 && $nivel == '1') {
        $nivel = 2;
        $puntos_estadistica += 10;
        $db->query(" UPDATE `mybb_op_fichas` SET `nivel`='$nivel',`puntos_estadistica`='$puntos_estadistica' WHERE `fid`='$query_uid'; ");
        if ($raza == 'Skypian') { $db->query("  UPDATE `mybb_op_fichas` SET `energia_pasiva`=`energia_pasiva` + 5 WHERE `fid`='$query_uid'; "); }
    } 

    $query_tec_aprendidas = $db->query("
        SELECT * FROM `mybb_op_tecnicas` 
        INNER JOIN `mybb_op_tec_aprendidas` 
        ON `mybb_op_tecnicas`.`tid`=`mybb_op_tec_aprendidas`.`tid` 
        WHERE `mybb_op_tec_aprendidas`.`uid`='$query_uid'
        ORDER BY `mybb_op_tecnicas`.`tid`,`mybb_op_tecnicas`.`clase`
    ");

    $tec_aprendidas = array();
    
    while ($tec_aprendida = $db->fetch_array($query_tec_aprendidas)) {
        $tec_aprendida['descripcion'] = nl2br($tec_aprendida['descripcion']);
        $key = $tec_aprendida['clase'];

        if (!$tec_aprendidas[$key]) {
            $tec_aprendidas[$key] = array();
        }
        array_push($tec_aprendidas[$key], $tec_aprendida);
    }
    $tec_aprendidas_json = json_encode($tec_aprendidas);

    eval("\$op_ficha_script = \"".$templates->get("op_ficha_script2")."\";");
    eval("\$op_ficha_css = \"".$templates->get("op_ficha_css2")."\";");
    eval("\$page = \"".$templates->get("op_ficha2")."\";");
    output_page($page);
    // Página de creación de ficha
} else if (($ficha_existe == false || $f['aprobada_por'] == 'guardada') && $mybb->user['uid'] == $query_uid) {
    
    $query_virtudes = $db->query(" SELECT * FROM `mybb_op_virtudes` WHERE virtud_id LIKE 'V%' ORDER BY `mybb_op_virtudes`.`nombre` ASC; ");
    $query_defectos = $db->query(" SELECT * FROM `mybb_op_virtudes` WHERE virtud_id LIKE 'D%' ORDER BY `mybb_op_virtudes`.`nombre` ASC; ");
    
    $virtudes = array();
    $defectos = array();
    
    while ($virtud = $db->fetch_array($query_virtudes)) { array_push($virtudes, $virtud); }
    while ($defecto = $db->fetch_array($query_defectos)) { array_push($defectos, $defecto); }

    $virtudes_json = json_encode($virtudes);
    $defectos_json = json_encode($defectos);

    eval("\$op_ficha_crear_script = \"".$templates->get("op_ficha_crear_script")."\";");
    eval("\$page = \"".$templates->get("op_ficha_crear")."\";");
    output_page($page);
} else if ($ficha_existe == true && $mybb->user['uid'] == $user_uid && $mybb->user['uid'] != 0) {
    $mensaje_redireccion = "Tu ficha está en moderación.";
    eval("\$page = \"".$templates->get("op_redireccion")."\";");
    output_page($page); 
} else {
    $mensaje_redireccion = "Este usuario no ha creado su ficha aún.";
    eval("\$page = \"".$templates->get("op_redireccion")."\";");
    output_page($page);
}

