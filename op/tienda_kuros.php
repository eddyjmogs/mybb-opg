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
define('THIS_SCRIPT', 'tienda_nika.php');

global $templates, $mybb, $db;

require_once "./../global.php";
require_once "./functions/op_functions.php";

$uid = $mybb->user['uid'];
$username = $mybb->user['username'];
$accion = $_POST["accion"];
$comprasTotal = 0;
$listaDeCompras = $_POST["listaDeCompras"];
$listaDeComprasKeys = $_POST["listaDeComprasKeys"];
$ficha = null;

if ($g_ficha['muerto'] == '1') {
    $mensaje_redireccion = "Estás muerto, no puedes acceder a esta página.";
    eval("\$page = \"".$templates->get("op_redireccion")."\";");
    output_page($page);
    return;
}

$query_ficha = $db->query("SELECT * FROM mybb_op_fichas WHERE fid='$uid'");
while ($q = $db->fetch_array($query_ficha)) { $ficha = $q; }
$kuros = $ficha['kuro'];

$query_objetos = $db->query(" SELECT * FROM `mybb_op_kuros` ORDER BY berries ASC");
$objetos = array();
$objetos_array = array();

while ($q = $db->fetch_array($query_objetos)) { 
    $objeto_id = $q['objeto_id'];
    $key = "$objeto_id";
    if (!$objetos[$key]) { $objetos[$key] = array(); }
    array_push($objetos[$key], $q);
    array_push($objetos_array, $objeto_id);
}

$objetos_array_json = json_encode($objetos_array);
$objetos_json = json_encode($objetos);

if ($accion == 'comprar' && $uid != '0') {

    $should_cancel = false;
    $logObj = "";

    foreach ($listaDeComprasKeys as $objKey) {
        if (intval($listaDeCompras[$objKey]['cantidad']) <= 0) { $should_cancel = true; }
    }
    if ($should_cancel == true) { return; }

    foreach ($listaDeComprasKeys as $objKey) {
        $comprasTotal += (intval($objetos[$objKey][0]['berries']) * $listaDeCompras[$objKey]['cantidad']);
    }

    if (intval($kuros) >= $comprasTotal && $comprasTotal != 0) {
        foreach ($listaDeComprasKeys as $objKey) {

        
            $cantidadExtra = $listaDeCompras[$objKey]['cantidad'];
            if ($objKey != 'NIKAS' && $objKey != 'BERRIES' && $objKey != 'OFICIOS') {
                
                $logObj .= "$objKey: $cantidadExtra;";
                darObjeto($objKey, $cantidadExtra);
    
            } else {
    
                if ($objKey == 'NIKAS') {
                    darNikas($cantidadExtra);
                    $logObj .= "Nikas: $cantidadExtra;";
                }
    
                if ($objKey == 'BERRIES') {
                    darBerries(intval(intval($cantidadExtra) * 50000));
                    $logObj .= "Berries: $cantidadExtra;";
                }

                if ($objKey == 'OFICIOS') {
                    darOficios(intval(intval($cantidadExtra) * 100));
                    $logObj .= "Puntos de Oficio: $cantidadExtra;";
                }
            }
         
        }
    
        $nuevosKuros = intval($kuros) - intval($comprasTotal);
        log_audit($uid, $username, '[Kuros]', "Kuros: $kuros->$nuevosKuros (Gasto: $comprasTotal). $logObj");
        // $db->query(" UPDATE `mybb_op_fichas` SET kuro='$nuevosKuros' WHERE `fid`='$uid'; ");

        log_audit_currency($uid, $username, $uid, '[Tienda Kuros][Kuros]', 'kuros', $nuevosKuros);
    }

    return;
}

function darObjeto($objeto_id, $cantidadExtra) {
    global $db, $uid;
    $cantidadActual = '0';

    $has_objeto = false;
    $inventario_actual = $db->query("SELECT * FROM mybb_op_inventario WHERE uid='$uid' AND objeto_id='$objeto_id'");
    while ($q = $db->fetch_array($inventario_actual)) {  $has_objeto = true; $cantidadActual = $q['cantidad']; }

    if ($has_objeto) {
        $cantidadNueva = intval($cantidadActual) + intval($cantidadExtra);
        $db->query(" 
            UPDATE `mybb_op_inventario` SET `cantidad`='$cantidadNueva' WHERE objeto_id='$objeto_id' AND uid='$uid'
        ");
    } else {
        $db->query(" 
            INSERT INTO `mybb_op_inventario` (`objeto_id`, `uid`, `cantidad`) VALUES 
            ('$objeto_id', '$uid', '$cantidadExtra');
        ");
    }
}

function darBerries($berriesNuevo) {
    global $db, $uid, $ficha, $username;
    $berries = intval($ficha['berries']) + $berriesNuevo;
    // $db->query(" UPDATE `mybb_op_fichas` SET `berries`='$berries' WHERE fid='$uid' ");

    log_audit_currency($uid, $username, $uid, '[Tienda Kuros][Berries]', 'berries', $berries);
}

function darNikas($nikasNuevo) {
    global $db, $uid, $ficha, $username;
    $nikas = intval($ficha['nika']) + $nikasNuevo;
    // $db->query(" UPDATE `mybb_op_fichas` SET `nika`='$nikas' WHERE fid='$uid' ");

    log_audit_currency($uid, $username, $uid, '[Tienda Kuros][Nikas]', 'nikas', $nikas);
}

function darOficios($oficiosNuevo) {
    global $db, $uid, $ficha, $username;
    $oficios = intval($ficha['puntos_oficio']) + $oficiosNuevo;
    // $db->query(" UPDATE `mybb_op_fichas` SET `puntos_oficio`='$oficios' WHERE fid='$uid' ");

    log_audit_currency($uid, $username, $uid, '[Tienda Kuros][Oficios]', 'puntos_oficio', $oficios);
}


function darExp($expNueva) {
    global $db, $uid, $experienciaActual, $username;
    $experiencia = floatval($experienciaActual) + $expNueva;
    // $db->query(" UPDATE `mybb_users` SET `newpoints`='$experiencia' WHERE uid='$uid' ");
    log_audit_currency($uid, $username, $uid, '[Tienda Kuros][Experiencia]', 'experiencia', $experiencia);
}


$reload_js = "";


$cofre1_count = 0;
$cofre2_count = 0;
$cofre3_count = 0;
$cofre4_count = 0;
$cofre5_count = 0;

$cofre1_count_query = $db->query("SELECT
  SUM(CAST(
    SUBSTRING(
      log,
      LOCATE('CFR001: ', log) + 8,
      LOCATE(';', log, LOCATE('CFR001: ', log)) - (LOCATE('CFR001: ', log) + 8)
    ) AS UNSIGNED
  )) AS total_cofres
FROM mybb_op_audit_general
WHERE categoria = '[Kuros]'
  AND log LIKE '%CFR001: %'
  AND tiempo >= NOW() - INTERVAL 1 MONTH
  AND user_uid = '$uid'");
while ($q = $db->fetch_array($cofre1_count_query)) { $cofre1_count = $q['total_cofres']; }

$cofre2_count_query = $db->query("SELECT
  SUM(CAST(
    SUBSTRING(
      log,
      LOCATE('CFR002: ', log) + 8,
      LOCATE(';', log, LOCATE('CFR002: ', log)) - (LOCATE('CFR002: ', log) + 8)
    ) AS UNSIGNED
  )) AS total_cofres
FROM mybb_op_audit_general
WHERE categoria = '[Kuros]'
  AND log LIKE '%CFR002: %'
  AND tiempo >= NOW() - INTERVAL 1 MONTH
  AND user_uid = '$uid'");
while ($q = $db->fetch_array($cofre2_count_query)) {  $cofre2_count = $q['total_cofres']; }

$cofre3_count_query = $db->query("SELECT
  SUM(CAST(
    SUBSTRING(
      log,
      LOCATE('CFR003: ', log) + 8,
      LOCATE(';', log, LOCATE('CFR003: ', log)) - (LOCATE('CFR003: ', log) + 8)
    ) AS UNSIGNED
  )) AS total_cofres
FROM mybb_op_audit_general
WHERE categoria = '[Kuros]'
  AND log LIKE '%CFR003: %'
  AND tiempo >= NOW() - INTERVAL 1 MONTH
  AND user_uid = '$uid'");
while ($q = $db->fetch_array($cofre3_count_query)) {  $cofre3_count = $q['total_cofres']; }     

$cofre4_count_query = $db->query("SELECT
  SUM(CAST(
    SUBSTRING(
      log,
      LOCATE('CFR004: ', log) + 8,
      LOCATE(';', log, LOCATE('CFR004: ', log)) - (LOCATE('CFR004: ', log) + 8)
    ) AS UNSIGNED
  )) AS total_cofres
FROM mybb_op_audit_general
WHERE categoria = '[Kuros]'
  AND log LIKE '%CFR004: %'
  AND tiempo >= NOW() - INTERVAL 1 MONTH
  AND user_uid = '$uid'");
while ($q = $db->fetch_array($cofre4_count_query)) {  $cofre4_count = $q['total_cofres']; }     

$cofre5_count_query = $db->query("SELECT
  SUM(CAST(
    SUBSTRING(
      log,
      LOCATE('CFR005: ', log) + 8,
      LOCATE(';', log, LOCATE('CFR005: ', log)) - (LOCATE('CFR005: ', log) + 8)
    ) AS UNSIGNED
  )) AS total_cofres
FROM mybb_op_audit_general
WHERE categoria = '[Kuros]'
  AND log LIKE '%CFR005: %'
  AND tiempo >= NOW() - INTERVAL 1 MONTH
  AND user_uid = '$uid'");
while ($q = $db->fetch_array($cofre5_count_query)) {  $cofre5_count = $q['total_cofres']; }         



if (does_ficha_exist($uid)) {

    // $mensaje_redireccion = "Tienda de Kuros cerrada temporalmente. Mientras tanto, toca ahorrar que esos cofres no se compran solos.";
    // eval("\$page = \"".$templates->get("op_redireccion")."\";");
    // output_page($page);

    eval("\$page = \"".$templates->get("op_tienda_kuros")."\";");
    output_page($page);
} else {
    $mensaje_redireccion = "Este usuario no ha creado su ficha aún.";
    eval("\$page = \"".$templates->get("op_redireccion")."\";");
    output_page($page);
}