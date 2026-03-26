<?php
/**
 * MyBB 1.8
 * Copyright 2014 MyBB Group, All Rights Reserved
 *
 * Website: http://www.mybb.com
 * License: http://www.mybb.com/about/license
 */

define("IN_MYBB", 1);
define('THIS_SCRIPT', 'salario_faccion.php');
require_once "./../../global.php";
require "./../../inc/config.php";
require_once "./../functions/op_functions.php";

global $templates, $mybb;
$uid = $mybb->user['uid'];
$username = $mybb->user['username'];
$reload_js = "<script>window.location.href = window.location.pathname;</script>";

$salario = $_POST["salario"];

if ($salario == '1' && (is_mod($uid) || is_staff($uid))) {

    $salarios = [
        // Marina
        'ReclutaM'        => 10000,
        'SoldadoM'        => 25000,
        'SargentoM'       => 50000,
        'Suboficial'      => 100000,
        'Alferez'         => 250000,
        'Teniente'        => 350000,
        'ComandanteM'     => 500000,
        'Capitan'         => 2000000,
        'Comodoro'        => 5000000,
        'ContraAlmirante' => 10000000,
        'Vicealmirante'   => 25000000,
        'Almirante'       => 50000000,
        'AlmiranteFlota'  => 100000000,
        // CipherPol
        'CP1'             => 25000,
        'CP2'             => 50000,
        'CP3'             => 120000,
        'CP4'             => 250000,
        'CP5'             => 350000,
        'CP6'             => 500000,
        'CP7'             => 1500000,
        'CP8'             => 3000000,
        'CP9'             => 25000000,
        'CPAegis0'        => 50000000,
        // Revolucionario
        'ReclutaR'          => 5000,
        'SoldadoR'          => 10000,
        'SargentoR'         => 25000,
        'AgenteR'           => 50000,
        'Oficial'           => 100000,
        'Mariscal'          => 250000,
        'General'           => 300000,
        'ComandanteAdjunto' => 2000000,
        'ComandanteR'       => 5000000,
        'JefePersonal'      => 10000000,
        'ComandanteSupremo' => 10000000,
    ];

    $rangos = implode("','", array_keys($salarios));
    $query_rango = $db->query(" SELECT * FROM mybb_op_fichas WHERE rango IN ('$rangos'); ");

    while ($q = $db->fetch_array($query_rango)) {
        $user_fid = $q['fid'];
        $faccion  = $q['faccion'];
        $berries  = intval($q['berries']) + $salarios[$q['rango']];
        log_audit_currency($user_uid, $username, $user_fid, "[Salarios][$faccion]", 'berries', $berries);
    }

    // $query_marine_rango1 = $db->query(" SELECT * FROM mybb_op_fichas WHERE rango='ReclutaM'; ");
    // while ($q = $db->fetch_array($query_marine_rango1)) {
    //     $user_fid = $q['fid'];
    //     $faccion = $q['faccion'];
    //     $berries = intval($q['berries']) + 10000;
    //     log_audit_currency($uid, $username, $user_fid, "[Salarios][$faccion]", 'berries', $nikasNuevo);
    // }

    // $query_marine_rango2 = $db->query(" SELECT * FROM mybb_op_fichas WHERE rango='SoldadoM'; ");
    // while ($q = $db->fetch_array($query_marine_rango2)) {
    //     $user_fid = $q['fid'];
    //     $faccion = $q['faccion'];
    //     $berries = intval($q['berries']) + 10000;
    //     log_audit_currency($uid, $username, $user_fid, "[Salarios][$faccion]", 'berries', $nikasNuevo);
    // }

    // // Marina
    // $db->query(" UPDATE `mybb_op_fichas` SET `berries`=`berries` + 10000 WHERE rango='ReclutaM'; ");
    // $db->query(" UPDATE `mybb_op_fichas` SET `berries`=`berries` + 25000 WHERE rango='SoldadoM'; ");
    // $db->query(" UPDATE `mybb_op_fichas` SET `berries`=`berries` + 50000 WHERE rango='SargentoM'; ");
    // $db->query(" UPDATE `mybb_op_fichas` SET `berries`=`berries` + 100000 WHERE rango='Suboficial'; ");
    // $db->query(" UPDATE `mybb_op_fichas` SET `berries`=`berries` + 250000 WHERE rango='Alferez'; ");
    // $db->query(" UPDATE `mybb_op_fichas` SET `berries`=`berries` + 350000 WHERE rango='Teniente'; ");
    // $db->query(" UPDATE `mybb_op_fichas` SET `berries`=`berries` + 500000 WHERE rango='ComandanteM'; ");
    // $db->query(" UPDATE `mybb_op_fichas` SET `berries`=`berries` + 2000000 WHERE rango='Capitan'; ");
    // $db->query(" UPDATE `mybb_op_fichas` SET `berries`=`berries` + 5000000 WHERE rango='Comodoro'; ");
    // $db->query(" UPDATE `mybb_op_fichas` SET `berries`=`berries` + 10000000 WHERE rango='ContraAlmirante'; ");
    // $db->query(" UPDATE `mybb_op_fichas` SET `berries`=`berries` + 25000000 WHERE rango='Vicealmirante'; ");
    // $db->query(" UPDATE `mybb_op_fichas` SET `berries`=`berries` + 50000000 WHERE rango='Almirante'; ");
    // $db->query(" UPDATE `mybb_op_fichas` SET `berries`=`berries` + 100000000 WHERE rango='AlmiranteFlota'; ");

    // // CipherPol
    // $db->query(" UPDATE `mybb_op_fichas` SET `berries`=`berries` + 25000 WHERE rango='CP1'; ");
    // $db->query(" UPDATE `mybb_op_fichas` SET `berries`=`berries` + 50000 WHERE rango='CP2'; ");
    // $db->query(" UPDATE `mybb_op_fichas` SET `berries`=`berries` + 120000 WHERE rango='CP3'; ");
    // $db->query(" UPDATE `mybb_op_fichas` SET `berries`=`berries` + 250000 WHERE rango='CP4'; ");
    // $db->query(" UPDATE `mybb_op_fichas` SET `berries`=`berries` + 350000 WHERE rango='CP5'; ");
    // $db->query(" UPDATE `mybb_op_fichas` SET `berries`=`berries` + 500000 WHERE rango='CP6'; ");
    // $db->query(" UPDATE `mybb_op_fichas` SET `berries`=`berries` + 1500000 WHERE rango='CP7'; ");
    // $db->query(" UPDATE `mybb_op_fichas` SET `berries`=`berries` + 3000000 WHERE rango='CP8'; ");
    // $db->query(" UPDATE `mybb_op_fichas` SET `berries`=`berries` + 25000000 WHERE rango='CP9'; ");
    // $db->query(" UPDATE `mybb_op_fichas` SET `berries`=`berries` + 50000000 WHERE rango='CPAegis0'; ");

    // // Revolucionario
    // $db->query(" UPDATE `mybb_op_fichas` SET `berries`=`berries` + 5000 WHERE rango='ReclutaR'; ");
    // $db->query(" UPDATE `mybb_op_fichas` SET `berries`=`berries` + 10000 WHERE rango='SoldadoR'; ");
    // $db->query(" UPDATE `mybb_op_fichas` SET `berries`=`berries` + 25000 WHERE rango='SargentoR'; ");
    // $db->query(" UPDATE `mybb_op_fichas` SET `berries`=`berries` + 50000 WHERE rango='AgenteR'; ");
    // $db->query(" UPDATE `mybb_op_fichas` SET `berries`=`berries` + 100000 WHERE rango='Oficial'; ");
    // $db->query(" UPDATE `mybb_op_fichas` SET `berries`=`berries` + 250000 WHERE rango='Mariscal'; ");
    // $db->query(" UPDATE `mybb_op_fichas` SET `berries`=`berries` + 300000 WHERE rango='General'; ");
    // $db->query(" UPDATE `mybb_op_fichas` SET `berries`=`berries` + 2000000 WHERE rango='ComandanteAdjunto'; ");
    // $db->query(" UPDATE `mybb_op_fichas` SET `berries`=`berries` + 5000000 WHERE rango='ComandanteR'; ");
    // $db->query(" UPDATE `mybb_op_fichas` SET `berries`=`berries` + 10000000 WHERE rango='JefePersonal'; ");
    // $db->query(" UPDATE `mybb_op_fichas` SET `berries`=`berries` + 10000000 WHERE rango='ComandanteSupremo'; ");

    
    

    $fechaHora = date("Y-m-d H:i:s");

    $log = "[Salario Rangos] Última vez entregados por $username ($uid) a las: " . $fechaHora;

    $db->query(" 
        INSERT INTO `mybb_op_audit_consola_mod` (`staff`, `username`, `razon`, `log`) VALUES 
        ('$staff', '$username', '$razon', '$log');
    ");
    
    eval('$log_var = $log;');
    eval('$reload_script = $reload_js;');
}

if (is_mod($uid) || is_staff($uid)) { 

    $salarios_entregados = '';

    $query_salarios = $db->query("
        SELECT * FROM mybb_op_audit_consola_mod WHERE log LIKE '%[Salario Rangos]%' ORDER BY tiempo DESC LIMIT 10;
    ");
    while ($q = $db->fetch_array($query_salarios)) {
        
        $salarios_entregados .= $q['log'];
        $salarios_entregados .= "</br>";
    }
    
    eval("\$page = \"".$templates->get("staff_salario_faccion")."\";");
    output_page($page);
} else {
    eval("\$page = \"".$templates->get("sin_permisos")."\";");
    output_page($page);
}
