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
define('THIS_SCRIPT', 'tirada_rey.php');
require_once "./../global.php";
require "./../inc/config.php";
require_once "./../op/functions/op_functions.php";
global $templates, $mybb;

$uid = $mybb->user['uid'];
$ficha = null;
$ficha_aprobada = false;

if ($uid == '0') {
    $mensaje_redireccion = "Debes estar registrado.";
    eval("\$page = \"".$templates->get("op_redireccion")."\";");
    output_page($page);
    return;
}

if ($g_ficha['muerto'] == '1') {
    $mensaje_redireccion = "Estás muerto, no puedes acceder a esta página.";
    eval("\$page = \"".$templates->get("op_redireccion")."\";");
    output_page($page);
    return;
}

$query_ficha = $db->query(" SELECT * FROM mybb_op_fichas WHERE fid='$uid' "); 
while ($f = $db->fetch_array($query_ficha)) { $ficha = $f; $ficha_aprobada = $f['aprobada_por'] != 'sin_aprobar'; }

$query_users = $db->query(" SELECT * FROM mybb_users WHERE uid='$uid' "); 
while ($q = $db->fetch_array($query_users)) { $experienciaActual = $q['newpoints']; }

if ($ficha == null) {
    $mensaje_redireccion = "Este usuario no ha creado su ficha aún.";
    eval("\$page = \"".$templates->get("op_redireccion")."\";");
    output_page($page);
    return;
}

$username = $mybb->user['username'];
$tirada_real = $_POST["tirada_real"];

$tirada_cofre = $_POST["tirada_cofre"];
$cofre_id = strtoupper($_POST["cofre_id"]);
// $cofre_id = 'CFR001';
// $tirada_cofre = 'true';

$tirada_cofre_resultado = '';

$cofres_abiertos = '0';
$cofre_basico = '0';
$cofre_decente = '0';
$cofre_gigante = '0';
$cofre_cobrizo = '0';
$cofre_argenteo = '0';
$cofre_aureo = '0';
$cofre_epico = '0';
$cofre_legendario = '0';
$cofre_majestuoso = '0';
$cofre_diamantino = '0';
$cofre_jackpot = '0';
$cofre_increase = '0';
$cofre_decrease = '0';

$cofres_abiertos_id = '0';
$cofre_basico_id = '0';
$cofre_decente_id = '0';
$cofre_gigante_id = '0';
$cofre_cobrizo_id = '0';
$cofre_argenteo_id = '0';
$cofre_aureo_id = '0';
$cofre_epico_id = '0';
$cofre_legendario_id = '0';
$cofre_majestuoso_id = '0';
$cofre_diamantino_id = '0';
$cofre_jackpot_id = '0';
$cofre_increase_id = '0';
$cofre_decrease_id = '0';

$cofres_globales_array = array();
$cofres_propios_array = array();

$cofres_globales_query = $db->query(" SELECT * FROM `mybb_op_tirada_cofre` WHERE `uid` <> 850 ORDER BY ID DESC LIMIT 200; ");
$cofres_propios_query = $db->query(" SELECT * FROM `mybb_op_tirada_cofre` WHERE `uid`='$uid' ORDER BY ID DESC LIMIT 50; ");

$cofres_abiertos_query = $db->query(" SELECT count(*) AS total FROM `mybb_op_tirada_cofre` ");
$cofre_basico_query = $db->query(" SELECT count(*) AS total FROM `mybb_op_tirada_cofre` WHERE `tier`='CFR001'; ");
$cofre_decente_query = $db->query(" SELECT count(*) AS total FROM `mybb_op_tirada_cofre` WHERE `tier`='CFR002'; ");
$cofre_gigante_query = $db->query(" SELECT count(*) AS total FROM `mybb_op_tirada_cofre` WHERE `tier`='CFR003'; ");
$cofre_cobrizo_query = $db->query(" SELECT count(*) AS total FROM `mybb_op_tirada_cofre` WHERE `tier`='CFR004'; ");
$cofre_argenteo_query = $db->query(" SELECT count(*) AS total FROM `mybb_op_tirada_cofre` WHERE `tier`='CFR005'; ");
$cofre_aureo_query = $db->query(" SELECT count(*) AS total FROM `mybb_op_tirada_cofre` WHERE `tier`='CFR006'; ");
$cofre_epico_query = $db->query(" SELECT count(*) AS total FROM `mybb_op_tirada_cofre` WHERE `tier`='CFR007'; ");
$cofre_legendario_query = $db->query(" SELECT count(*) AS total FROM `mybb_op_tirada_cofre` WHERE `tier`='CFR008'; ");
$cofre_majestuoso_query = $db->query(" SELECT count(*) AS total FROM `mybb_op_tirada_cofre` WHERE `tier`='CFR009'; ");
$cofre_diamantino_query = $db->query(" SELECT count(*) AS total FROM `mybb_op_tirada_cofre` WHERE `tier`='CFR010'; ");
$cofre_jackpot_query = $db->query(" SELECT count(*) AS total FROM `mybb_op_tirada_cofre` WHERE `objeto_id`='JACKPOT'; ");
$cofre_increase_query = $db->query(" 
    SELECT count(*) AS total FROM `mybb_op_tirada_cofre` 
    WHERE 
    (`tier`='CFR001' AND objeto_id='CFR002') OR 
    (`tier`='CFR002' AND objeto_id='CFR003') OR
    (`tier`='CFR003' AND objeto_id='CFR004') OR 
    (`tier`='CFR004' AND objeto_id='CFR005') OR 
    (`tier`='CFR005' AND objeto_id='CFR006') OR 
    (`tier`='CFR006' AND objeto_id='CFR007') OR 
    (`tier`='CFR007' AND objeto_id='CFR008') OR 
    (`tier`='CFR008' AND objeto_id='CFR009') OR 
    (`tier`='CFR009' AND objeto_id='CFR010'); ");
$cofre_decrease_query = $db->query(" 
    SELECT count(*) AS total FROM `mybb_op_tirada_cofre` 
    WHERE 
    (`tier`='CFR002' AND objeto_id='CFR001') OR 
    (`tier`='CFR003' AND objeto_id='CFR002') OR
    (`tier`='CFR004' AND objeto_id='CFR003') OR 
    (`tier`='CFR005' AND objeto_id='CFR004') OR 
    (`tier`='CFR006' AND objeto_id='CFR005') OR 
    (`tier`='CFR007' AND objeto_id='CFR006') OR 
    (`tier`='CFR008' AND objeto_id='CFR007') OR 
    (`tier`='CFR009' AND objeto_id='CFR008') OR 
    (`tier`='CFR010' AND objeto_id='CFR009'); ");

$cofres_abiertos_id_query = $db->query(" SELECT count(*) AS total FROM `mybb_op_tirada_cofre` WHERE uid='$uid' ");
$cofre_basico_id_query = $db->query(" SELECT count(*) AS total FROM `mybb_op_tirada_cofre` WHERE `tier`='CFR001' AND uid='$uid'; ");
$cofre_decente_id_query = $db->query(" SELECT count(*) AS total FROM `mybb_op_tirada_cofre` WHERE `tier`='CFR002' AND uid='$uid'; ");
$cofre_gigante_id_query = $db->query(" SELECT count(*) AS total FROM `mybb_op_tirada_cofre` WHERE `tier`='CFR003' AND uid='$uid'; ");
$cofre_cobrizo_id_query = $db->query(" SELECT count(*) AS total FROM `mybb_op_tirada_cofre` WHERE `tier`='CFR004' AND uid='$uid'; ");
$cofre_argenteo_id_query = $db->query(" SELECT count(*) AS total FROM `mybb_op_tirada_cofre` WHERE `tier`='CFR005' AND uid='$uid'; ");
$cofre_aureo_id_query = $db->query(" SELECT count(*) AS total FROM `mybb_op_tirada_cofre` WHERE `tier`='CFR006' AND uid='$uid'; ");
$cofre_epico_id_query = $db->query(" SELECT count(*) AS total FROM `mybb_op_tirada_cofre` WHERE `tier`='CFR007' AND uid='$uid'; ");
$cofre_legendario_id_query = $db->query(" SELECT count(*) AS total FROM `mybb_op_tirada_cofre` WHERE `tier`='CFR008' AND uid='$uid'; ");
$cofre_majestuoso_id_query = $db->query(" SELECT count(*) AS total FROM `mybb_op_tirada_cofre` WHERE `tier`='CFR009' AND uid='$uid'; ");
$cofre_diamantino_id_query = $db->query(" SELECT count(*) AS total FROM `mybb_op_tirada_cofre` WHERE `tier`='CFR010' AND uid='$uid'; ");
$cofre_jackpot_id_query = $db->query(" SELECT count(*) AS total FROM `mybb_op_tirada_cofre` WHERE `objeto_id`='JACKPOT' AND uid='$uid'; ");
$cofre_increase_id_query = $db->query(" 
    SELECT count(*) AS total FROM `mybb_op_tirada_cofre` 
    WHERE uid='$uid' AND
    ((`tier`='CFR001' AND objeto_id='CFR002') OR 
    (`tier`='CFR002' AND objeto_id='CFR003') OR 
    (`tier`='CFR003' AND objeto_id='CFR004') OR 
    (`tier`='CFR004' AND objeto_id='CFR005') OR 
    (`tier`='CFR005' AND objeto_id='CFR006') OR 
    (`tier`='CFR006' AND objeto_id='CFR007') OR 
    (`tier`='CFR007' AND objeto_id='CFR008') OR 
    (`tier`='CFR008' AND objeto_id='CFR009') OR 
    (`tier`='CFR009' AND objeto_id='CFR010')); ");
$cofre_decrease_id_query = $db->query(" 
    SELECT count(*) AS total FROM `mybb_op_tirada_cofre` 
    WHERE uid='$uid' AND
    ((`tier`='CFR002' AND objeto_id='CFR001') OR 
    (`tier`='CFR003' AND objeto_id='CFR002') OR
    (`tier`='CFR004' AND objeto_id='CFR003') OR 
    (`tier`='CFR005' AND objeto_id='CFR004') OR 
    (`tier`='CFR006' AND objeto_id='CFR005') OR 
    (`tier`='CFR007' AND objeto_id='CFR006') OR 
    (`tier`='CFR008' AND objeto_id='CFR007') OR 
    (`tier`='CFR009' AND objeto_id='CFR008') OR 
    (`tier`='CFR010' AND objeto_id='CFR009')); ");

while ($q = $db->fetch_array($cofres_abiertos_query)) { $cofres_abiertos = $q['total']; }
while ($q = $db->fetch_array($cofre_basico_query)) { $cofre_basico = $q['total']; }
while ($q = $db->fetch_array($cofre_decente_query)) { $cofre_decente = $q['total']; }
while ($q = $db->fetch_array($cofre_gigante_query)) { $cofre_gigante = $q['total']; }
while ($q = $db->fetch_array($cofre_cobrizo_query)) { $cofre_cobrizo = $q['total']; }
while ($q = $db->fetch_array($cofre_argenteo_query)) { $cofre_argenteo = $q['total']; }
while ($q = $db->fetch_array($cofre_aureo_query)) { $cofre_aureo = $q['total']; }
while ($q = $db->fetch_array($cofre_epico_query)) { $cofre_epico = $q['total']; }
while ($q = $db->fetch_array($cofre_legendario_query)) { $cofre_legendario = $q['total']; }
while ($q = $db->fetch_array($cofre_majestuoso_query)) { $cofre_majestuoso = $q['total']; }
while ($q = $db->fetch_array($cofre_diamantino_query)) { $cofre_diamantino = $q['total']; }

while ($q = $db->fetch_array($cofre_jackpot_query)) { $cofre_jackpot = $q['total']; }
while ($q = $db->fetch_array($cofre_increase_query)) { $cofre_increase = $q['total']; }
while ($q = $db->fetch_array($cofre_decrease_query)) { $cofre_decrease = $q['total']; }

while ($q = $db->fetch_array($cofres_abiertos_id_query)) { $cofres_abiertos_id = $q['total']; }
while ($q = $db->fetch_array($cofre_basico_id_query)) { $cofre_basico_id = $q['total']; }
while ($q = $db->fetch_array($cofre_decente_id_query)) { $cofre_decente_id = $q['total']; }
while ($q = $db->fetch_array($cofre_gigante_id_query)) { $cofre_gigante_id = $q['total']; }
while ($q = $db->fetch_array($cofre_cobrizo_id_query)) { $cofre_cobrizo_id = $q['total']; }
while ($q = $db->fetch_array($cofre_argenteo_id_query)) { $cofre_argenteo_id = $q['total']; }
while ($q = $db->fetch_array($cofre_aureo_id_query)) { $cofre_aureo_id = $q['total']; }
while ($q = $db->fetch_array($cofre_epico_id_query)) { $cofre_epico_id = $q['total']; }
while ($q = $db->fetch_array($cofre_legendario_id_query)) { $cofre_legendario_id = $q['total']; }
while ($q = $db->fetch_array($cofre_majestuoso_id_query)) { $cofre_majestuoso_id = $q['total']; }
while ($q = $db->fetch_array($cofre_diamantino_id_query)) { $cofre_diamantino_id = $q['total']; }

while ($q = $db->fetch_array($cofre_jackpot_id_query)) { $cofre_jackpot_id = $q['total']; }
while ($q = $db->fetch_array($cofre_increase_id_query)) { $cofre_increase_id = $q['total']; }
while ($q = $db->fetch_array($cofre_decrease_id_query)) { $cofre_decrease_id = $q['total']; }

while ($q = $db->fetch_array($cofres_globales_query)) { 
    array_push($cofres_globales_array, $q);
}

while ($q = $db->fetch_array($cofres_propios_query)) { 
    array_push($cofres_propios_array, $q);
}

$cofres_globales_json = json_encode($cofres_globales_array);
$cofres_propios_json = json_encode($cofres_propios_array);

$kuro_accion = $mybb->get_input('kuro_accion'); 

function darObjetoFid($objeto_id, $fid) {
    global $db, $uid;
    $cantidadActual = '0';
    $has_objeto = false;
    $inventario_actual = $db->query("SELECT * FROM mybb_op_inventario WHERE uid='$fid' AND objeto_id='$objeto_id'");
    while ($q = $db->fetch_array($inventario_actual)) {  $has_objeto = true; $cantidadActual = $q['cantidad']; }

    if ($has_objeto) {
        $cantidadNueva = intval($cantidadActual) + 1;
        $db->query(" 
            UPDATE `mybb_op_inventario` SET `cantidad`='$cantidadNueva' WHERE objeto_id='$objeto_id' AND uid='$fid'
        ");
    } else {
        $db->query(" 
            INSERT INTO `mybb_op_inventario` (`objeto_id`, `uid`, `cantidad`) VALUES 
            ('$objeto_id', '$fid', '1');
        ");
    }
}

function darObjeto($objeto_id, $cantidadNueva) {
    global $db, $uid;
    $cantidadActual = '0';
    $has_objeto = false;
    $inventario_actual = $db->query("SELECT * FROM mybb_op_inventario WHERE uid='$uid' AND objeto_id='$objeto_id'");
    while ($q = $db->fetch_array($inventario_actual)) { $has_objeto = true; $cantidadActual = $q['cantidad']; }

    if ($has_objeto) {
        $cantidadNuevaNueva = intval($cantidadActual) + intval($cantidadNueva);
        $db->query(" 
            UPDATE `mybb_op_inventario` SET `cantidad`='$cantidadNuevaNueva' WHERE objeto_id='$objeto_id' AND uid='$uid'
        ");
    } else {
        $db->query(" 
            INSERT INTO `mybb_op_inventario` (`objeto_id`, `uid`, `cantidad`) VALUES 
            ('$objeto_id', '$uid', '1');
        ");
    }
}

function darBerries($berriesNuevo) {
    global $db, $uid, $ficha, $username;
    $berriesActual = intval($ficha['berries']);
    $berries = $berriesActual + $berriesNuevo;
    log_audit($uid, $username, '[Cofre]', "Berries: $berriesActual->$berries (Extra: $berriesNuevo).");
    // $db->query(" UPDATE `mybb_op_fichas` SET `berries`='$berries' WHERE fid='$uid' ");
    log_audit_currency($uid, $username, $uid, '[Cofre][Berries]', 'berries', $berries);
}

function darNikas($nikasNuevo) {
    global $db, $uid, $ficha, $username;

    $nikasActual = intval($ficha['nika']);
    $nikas = $nikasActual + $nikasNuevo;

    log_audit($uid, $username, '[Cofre]', "Nikas: $nikasActual->$nikas (Extra: $nikasNuevo).");
    // $db->query(" UPDATE `mybb_op_fichas` SET `nika`='$nikas' WHERE fid='$uid' ");
    log_audit_currency($uid, $username, $uid, '[Cofre][Nikas]', 'nikas', $nikas);
}

function darExp($expNueva) {
    global $db, $uid, $experienciaActual, $username;

    $expeActual = floatval($experienciaActual);
    $experiencia = $expeActual + $expNueva;
    $experienciaActual = $experiencia; // actualizar para acumular correctamente con llamadas posteriores

    log_audit($uid, $username, '[Cofre]', "Experiencia: $expeActual->$experiencia (Extra: $expNueva).");
    // $db->query(" UPDATE `mybb_users` SET `newpoints`='$experiencia' WHERE uid='$uid' ");
    log_audit_currency($uid, $username, $uid, '[Cofre][Experiencia]', 'experiencia', $experiencia);
}

function darOficio($puntosOficio) {
    global $db, $uid, $ficha, $has_sin_oficio, $experienciaActual, $username;
    
    if ($has_sin_oficio) {
        $expeActual = floatval($experienciaActual);
        $experiencia = $expeActual + ($puntosOficio / 10);
        $experienciaActual = $experiencia; // actualizar para acumular correctamente con llamadas posteriores

        log_audit($uid, $username, '[Cofre]', "Sin Oficio Expe: $expeActual->$experiencia (Extra: $puntosOficio).");
        // $db->query(" UPDATE `mybb_users` SET `newpoints`='$experiencia' WHERE uid='$uid' ");
        log_audit_currency($uid, $username, $uid, '[Cofre][Experiencia]', 'experiencia', $experiencia);
    } else {
        $puntosActual = intval($ficha['puntos_oficio']);
        $puntosOficioNuevo = $puntosActual + $puntosOficio;
        log_audit($uid, $username, '[Cofre]', "Puntos de Oficio: $puntosActual->$puntosOficioNuevo (Extra: $puntosOficio).");
        // $db->query(" UPDATE `mybb_op_fichas` SET `puntos_oficio`='$puntosOficioNuevo' WHERE fid='$uid' ");
        log_audit_currency($uid, $username, $uid, '[Cofre][Puntos oficio]', 'puntos_oficio', $puntosOficioNuevo);
    }
}

if (isset($mybb->input['action']) && $mybb->input['action'] == 'Kuro_accion') {
    if (($uid == '69')) {
    $query_fichas = $db->query(" SELECT * FROM mybb_op_fichas "); 
    while ($f = $db->fetch_array($query_fichas)) { 
        $f_uid = $f['fid'];
        // darObjetoFid("KTC001", $f_uid);
        darObjetoFid("CFR004", $f_uid);
        // darObjetoFid("KTC001", $f_uid);
        }
    }   
}


$has_sin_oficio = false;
$has_sin_oficio_query = $db->query(" SELECT * FROM `mybb_op_virtudes_usuarios` WHERE uid='$uid' AND virtud_id='D024'; "); 
while ($q = $db->fetch_array($has_sin_oficio_query)) { $has_sin_oficio = true; }

if ($tirada_cofre == 'true') {
    header('Content-type: application/json');
    $response = array();
    $timestamp = time();

    // $tirada_random = rand(1, 10);

    $cantidadActual = '0';
    $cantidadExtra = '1';

    $has_objeto = false;
    $inventario_actual = $db->query("SELECT * FROM mybb_op_inventario WHERE uid='$uid' AND objeto_id='$cofre_id'");
    while ($q = $db->fetch_array($inventario_actual)) {  $has_objeto = true; $cantidadActual = $q['cantidad']; }

    // $has_objeto = false;

    $objeto = 'hello'; $objeto_id = 'null';

    if ($has_objeto && 
        ($cofre_id == 'CFR001' || $cofre_id == 'CFR002' || $cofre_id == 'CFR003' || $cofre_id == 'CFR004' || $cofre_id == 'CFR005' || 
         $cofre_id == 'CFR006' || $cofre_id == 'CFR007' || $cofre_id == 'CFR008' || $cofre_id == 'CFR009' || $cofre_id == 'CFR010' || 
         $cofre_id == 'INV001' || $cofre_id == 'INV002' || $cofre_id == 'INV003' || $cofre_id == 'INV004' || $cofre_id == 'INV005' ||
         $cofre_id == 'CFD002' || $cofre_id == 'CFD003' || $cofre_id == 'CFD004' || $cofre_id == 'CRM003' || $cofre_id == 'CFN002' ||
         $cofre_id == 'CFN003' || $cofre_id == 'CFN004' || $cofre_id == 'CFN005' || $cofre_id == 'CFN006' || $cofre_id == 'CFN007' || 
         $cofre_id == 'CFN008' || $cofre_id == 'CFN009' || $cofre_id == 'KTC001' )) {
        
        $cantidadNueva = intval($cantidadActual) - intval($cantidadExtra);

        if ($cantidadNueva == 0) {
            $db->query(" 
                DELETE FROM `mybb_op_inventario` WHERE objeto_id='$cofre_id' AND uid='$uid'
            ");
        } else {
            $db->query(" 
                UPDATE `mybb_op_inventario` SET `cantidad`='$cantidadNueva' WHERE objeto_id='$cofre_id' AND uid='$uid'
            ");
        }

        $cofre = null;
        // $cofre_query = $db->query("
        //     SELECT *
        //     FROM (
        //         SELECT objeto_id, cofre_id, peso, nombre, 
        //             (SELECT SUM(peso) FROM mybb_op_cofres o2 WHERE o2.id <= o1.id  AND cofre_id='$cofre_id') AS cumulative_weight
        //         FROM mybb_op_cofres o1 WHERE cofre_id='$cofre_id'
        //     ) AS cumulative_objects
        //     WHERE cumulative_weight >= (
        //         SELECT FLOOR(RAND() * SUM(peso)) + 1 FROM mybb_op_cofres WHERE cofre_id='$cofre_id'
        //     )
        //     LIMIT 1;
        // ");

        $cofre_random = 1000;
        $cofre_random_query =  $db->query("SELECT FLOOR(1 + RAND() * (SELECT SUM(peso) FROM mybb_op_cofres WHERE cofre_id = '$cofre_id')) as random_cofre_weight;");

        while ($q = $db->fetch_array($cofre_random_query)) { $cofre_random = intval($q['random_cofre_weight']); }

        $cofre_query = $db->query("
            SELECT 
                objeto_id, 
                nombre, 
                tipo, 
                peso, 
                cantidad, 
                cumulative_weight,
                @random_weight AS random_weight
            FROM (
                SELECT 
                    objeto_id, 
                    nombre, 
                    tipo, 
                    peso, 
                    cantidad, 
                    @cumulative_weight := @cumulative_weight + peso AS cumulative_weight
                FROM mybb_op_cofres
                CROSS JOIN (SELECT @cumulative_weight := 0) AS init
                WHERE cofre_id = '$cofre_id'
                ORDER BY cumulative_weight
            ) AS weighted_items
            WHERE cumulative_weight >= $cofre_random 
            ORDER BY weighted_items.cumulative_weight ASC
            LIMIT 1; 
        ");

        while ($q = $db->fetch_array($cofre_query)) { $cofre = $q; }
        $obj_id = $cofre['objeto_id'];
        $obj_cantidad = $cofre['cantidad'];
        
        if ($cofre_id == 'CFR001') { // Cofre Basico

            if ($cofre['tipo'] == 'Objeto') {
                if ($obj_id == 'CFR001X2') { darObjeto('CFR001', '1'); darObjeto('CFR001', '1');}
                else {darObjeto($obj_id, $obj_cantidad);}
            } else if ($cofre['tipo'] == 'Custom') {
                
                if ($obj_id == '5N') { darNikas(5); }
                if ($obj_id == '200KB') { darBerries(200000); }
                if ($obj_id == '20E') { darExp(20); }
                if ($obj_id == '100PO') { darOficio(100); }
                if ($obj_id == '3N100KB50PO10E') { darNikas(3); darBerries(100000); darOficio(50); darExp(10); }
                if ($obj_id == '10N') { darNikas(10); }
                if ($obj_id == '5N200KB100PO20E') { darNikas(5); darBerries(200000); darOficio(100); darExp(20); }
                if ($obj_id == 'CFR001X2') { darObjeto('CFR001', '2');}

            } else if ($cofre['tipo'] == 'Jackpot') {
                if ($obj_id == 'JACKPOT') { darNikas(5); darBerries(200000); darExp(20); darOficio(100); darNikas(3); darBerries(100000); darOficio(50); darExp(10); darNikas(10); darNikas(5); darBerries(200000); darOficio(100); darExp(20); darObjeto('TARM001', '1'); darObjeto('CFD002', '1'); darObjeto('NTC001', '1');  darObjeto('RTO001', '1'); darObjeto('PED003', '1');  darObjeto('TTUN002', '1'); darObjeto('TMJT002', '1'); darObjeto('INV001', '1'); darObjeto('CFR002', '1'); darObjeto('CFR001', '2'); darObjeto('LLST001', '1'); }
            }

            $objeto = $cofre['nombre'];
            $objeto_id = $cofre['objeto_id'];
        }

        if ($cofre_id == 'CFR002') { // Cofre Decente
            if ($cofre['tipo'] == 'Objeto') {
                darObjeto($obj_id, $obj_cantidad);
            } else if ($cofre['tipo'] == 'Custom') {
                
                if ($obj_id == '10N') { darNikas(10); }
                if ($obj_id == '500KB') { darBerries(500000); }
                if ($obj_id == '40E') { darExp(40); }
                if ($obj_id == '250PO') { darOficio(250); }
                if ($obj_id == '5N250KB125PO20E') { darNikas(5); darBerries(250000); darOficio(125); darExp(20); }
                if ($obj_id == '15N') { darNikas(15); }
                if ($obj_id == '10N500KB250PO40E') { darNikas(10); darBerries(500000); darOficio(250); darExp(40); }
                
            } else if ($cofre['tipo'] == 'Jackpot') {
                if ($obj_id == 'JACKPOT') { darNikas(10); darBerries(500000); darExp(40); darOficio(250); darNikas(5); darBerries(250000); darOficio(125); darExp(20); darNikas(15); darNikas(10); darBerries(500000); darOficio(250); darExp(40); darObjeto('TARM001', '1'); darObjeto('TARM002', '1'); darObjeto('CFD002', '1');  darObjeto('NTC002', '1'); darObjeto('RTO002', '1');  darObjeto('BLVIP001', '1'); darObjeto('PED003', '1'); darObjeto('TTUN003', '1'); darObjeto('TMJT003', '1'); darObjeto('INV001', '1'); darObjeto('CFR003', '1'); darObjeto('CFR001', '1'); darObjeto('THR001', '1'); }
            }

            $objeto = $cofre['nombre'];
            $objeto_id = $cofre['objeto_id'];
        }

        if ($cofre_id == 'CFR003') { // Cofre Gigante
            if ($cofre['tipo'] == 'Objeto') {
                darObjeto($obj_id, $obj_cantidad);
            } else if ($cofre['tipo'] == 'Custom') {
                
                if ($obj_id == '15N') { darNikas(15); }
                if ($obj_id == '1MB') { darBerries(1000000); }
                if ($obj_id == '75E') { darExp(75); }
                if ($obj_id == '500PO') { darOficio(500); }
                if ($obj_id == '8N500KB250PO40E') { darNikas(8); darBerries(500000); darOficio(250); darExp(40); }
                if ($obj_id == '25N') { darNikas(25); }
                if ($obj_id == '15N1MB500PO75E') { darNikas(15); darBerries(1000000); darOficio(500); darExp(75); }
                
            } else if ($cofre['tipo'] == 'Jackpot') {
                if ($obj_id == 'JACKPOT') { darNikas(15); darBerries(1000000); darExp(75); darOficio(500); darNikas(8); darBerries(500000); darOficio(250); darExp(40); darNikas(25); darNikas(15); darBerries(1000000); darOficio(500); darExp(75); darObjeto('TARM002', '1'); darObjeto('CFD002', '1'); darObjeto('NTC002', '1');  darObjeto('RTO002', '1'); darObjeto('BLVIP001', '1');  darObjeto('PED003', '1'); darObjeto('TTUN004', '1'); darObjeto('TTUN003', '1'); darObjeto('TMJT004', '1'); darObjeto('INV002', '1'); darObjeto('KSP001', '1'); darObjeto('CFR004', '1'); darObjeto('CFR002', '1'); darObjeto('THR001', '1'); }
            }

            $objeto = $cofre['nombre'];
            $objeto_id = $cofre['objeto_id'];
        }

        if ($cofre_id == 'CFR004') { // Cofre Cobrizo
            if ($cofre['tipo'] == 'Objeto') {
                darObjeto($obj_id, $obj_cantidad);
            } else if ($cofre['tipo'] == 'Custom') {
                
                if ($obj_id == '20N') { darNikas(20); }
                if ($obj_id == '2500KB') { darBerries(2500000); }
                if ($obj_id == '100E') { darExp(100); }
                if ($obj_id == '750PO') { darOficio(750); }
                if ($obj_id == '10N1250KB375PO50E') { darNikas(10); darBerries(1250000); darOficio(375); darExp(50); }
                if ($obj_id == '35N') { darNikas(35); }
                if ($obj_id == '20N2500KB750PO100E') { darNikas(20); darBerries(2500000); darOficio(750); darExp(100); }
                
            } else if ($cofre['tipo'] == 'Jackpot') {
                if ($obj_id == 'JACKPOT') { darNikas(20); darBerries(2500000); darExp(100); darOficio(750); darNikas(10); darBerries(1250000); darOficio(375); darExp(50); darNikas(35); darNikas(20); darBerries(2500000); darOficio(750); darExp(100); darObjeto('TARM002', '1'); darObjeto('TARM003', '1'); darObjeto('VCD001', '1');  darObjeto('CFD003', '1'); darObjeto('NTC003', '1');  darObjeto('RTO003', '1'); darObjeto('BLVIP001', '1'); darObjeto('PED004', '1'); darObjeto('PEESP001', '1'); darObjeto('TTUN005', '1'); darObjeto('TMJT005', '1'); darObjeto('INV002', '1'); darObjeto('KSP001', '1'); darObjeto('CFR005', '1'); darObjeto('CFR003', '1'); darObjeto('THR001', '1'); }
            }

            $objeto = $cofre['nombre'];
            $objeto_id = $cofre['objeto_id'];
        }

        if ($cofre_id == 'CFR005') { // Cofre Argenteo
            if ($cofre['tipo'] == 'Objeto') {
                darObjeto($obj_id, $obj_cantidad);
            } else if ($cofre['tipo'] == 'Custom') {
                
                if ($obj_id == '30N') { darNikas(30); }
                if ($obj_id == '7MB') { darBerries(7000000); }
                if ($obj_id == '125E') { darExp(125); }
                if ($obj_id == '1KPO') { darOficio(1000); }
                if ($obj_id == '15N3500KB500PO70E') { darNikas(15); darBerries(3500000); darOficio(500); darExp(70); }
                if ($obj_id == '45N') { darNikas(45); }
                if ($obj_id == '30N7MB1000PO125E') { darNikas(30); darBerries(7000000); darOficio(1000); darExp(125); }
                
            } else if ($cofre['tipo'] == 'Jackpot') {
                if ($obj_id == 'JACKPOT') { darNikas(30); darBerries(7000000); darExp(125); darOficio(1000); darNikas(15); darBerries(3500000); darOficio(375); darExp(50); darNikas(45); darNikas(30); darBerries(7000000); darOficio(1000); darExp(125); darObjeto('TARM003', '1'); darObjeto('VCD001', '1'); darObjeto('CFD003', '1');  darObjeto('NTC003', '1'); darObjeto('RTO003', '1');  darObjeto('BLVIP001', '1'); darObjeto('PED004', '1'); darObjeto('PEESP001', '1'); darObjeto('TTUN006', '1'); darObjeto('TMJT006', '1'); darObjeto('INV003', '1'); darObjeto('KK0020', '1'); darObjeto('CFR006', '5'); darObjeto('CFR004', '1'); darObjeto('THR001', '1'); }
            }

         $objeto = $cofre['nombre'];
         $objeto_id = $cofre['objeto_id'];
           
        }

        if ($cofre_id == 'CFR006') { // Cofre Aureo
            if ($cofre['tipo'] == 'Objeto') {
                darObjeto($obj_id, $obj_cantidad);
            } else if ($cofre['tipo'] == 'Custom') {
                if ($obj_id == '40N') { darNikas(40); }
                if ($obj_id == '15MB') { darBerries(15000000); }
                if ($obj_id == '150E') { darExp(150); }
                if ($obj_id == '1250PO') { darOficio(1250); }
                if ($obj_id == '20N7500KB625PO75E') { darNikas(20); darBerries(7500000); darOficio(650); darExp(75); }
                if ($obj_id == '55N') { darNikas(55); }
                if ($obj_id == '40N15M1250PO150E') { darNikas(40); darBerries(15000000); darOficio(1250); darExp(150); }
                
            } else if ($cofre['tipo'] == 'Jackpot') {
                if ($obj_id == 'JACKPOT') { darNikas(40); darBerries(15000000); darExp(150); darOficio(1250); darNikas(20); darBerries(7500000); darOficio(650); darExp(75); darNikas(55); darNikas(40); darBerries(15000000); darOficio(1250); darExp(150); darObjeto('CFR005', '1'); darObjeto('CFR007', '1'); darObjeto('THR001', '1');  darObjeto('KK040', '1'); darObjeto('INV003', '1');  darObjeto('TMJT007', '1'); darObjeto('TTUN007', '1'); darObjeto('PEESP001', '1'); darObjeto('PED005', '1'); darObjeto('BLVIP001', '1'); darObjeto('RTO004', '1'); darObjeto('NTC004', '1'); darObjeto('CFD003', '1'); darObjeto('VCZ001', '1'); darObjeto('KMP001', '1'); darObjeto('EPA001', '1'); darObjeto('TARM004', '1'); darObjeto('TARM003', '1'); darObjeto('TAK003', '1');}
            }

            $objeto = $cofre['nombre'];
            $objeto_id = $cofre['objeto_id'];
        }

        if ($cofre_id == 'CFR007') { // Cofre Epico
            if ($cofre['tipo'] == 'Objeto') {
                darObjeto($obj_id, $obj_cantidad);
            } else if ($cofre['tipo'] == 'Custom') {
                
                if ($obj_id == '50N') { darNikas(50); }
                if ($obj_id == '30MB') { darBerries(30000000); }
                if ($obj_id == '200E') { darExp(200); }
                if ($obj_id == '1500PO') { darOficio(1500); }
                if ($obj_id == '25N15M750PO100E') { darNikas(25); darBerries(15000000); darOficio(750); darExp(100); }
                if ($obj_id == '65N') { darNikas(65); }
                if ($obj_id == '50N30MBV1500PO200E') { darNikas(50); darBerries(30000000); darOficio(1500); darExp(200); }
                
            } else if ($cofre['tipo'] == 'Jackpot') {            
                if ($obj_id == 'JACKPOT') { darNikas(50); darBerries(30000000); darExp(200); darOficio(1500); darNikas(25); darBerries(15000000); darOficio(750); darExp(100); darNikas(65); darNikas(50); darBerries(30000000); darOficio(1500); darExp(200); darObjeto('TAK003', '1'); darObjeto('WAZ003', '1'); darObjeto('TARM004', '1'); darObjeto('EPA001', '1'); darObjeto('KMP001', '1');darObjeto('ANM001', '1'); darObjeto('ANM002', '1'); darObjeto('ANM003', '1'); darObjeto('ANM004', '1'); darObjeto('CFD004', '1'); darObjeto('NTC004', '1'); darObjeto('RTO004', '1'); darObjeto('PED005', '1'); darObjeto('PEESP001', '1'); darObjeto('TTUN008', '1'); darObjeto('TMJT008', '1'); darObjeto('INV004', '1'); darObjeto('KK006', '1'); darObjeto('CFR008', '1'); darObjeto('CFR006', '1'); darObjeto('THR001', '1'); }                
            }

            $objeto = $cofre['nombre'];
            $objeto_id = $cofre['objeto_id'];
        }
        
        if ($cofre_id == 'CFR008') { // Cofre Legendario
            if ($cofre['tipo'] == 'Objeto') {
                darObjeto($obj_id, $obj_cantidad);
            } else if ($cofre['tipo'] == 'Custom') {
                
                if ($obj_id == '75N') { darNikas(75); }
                if ($obj_id == '75MB') { darBerries(75000000); }
                if ($obj_id == '250E') { darExp(250); }
                if ($obj_id == '2000PO') { darOficio(2000); }
                if ($obj_id == '40N40M1000PO125E') { darNikas(40); darBerries(40000000); darOficio(1000); darExp(125); }
                if ($obj_id == '85N') { darNikas(85); }
                if ($obj_id == '75N75MB2000PO250E') { darNikas(75); darBerries(75000000); darOficio(2000); darExp(250); }
                
            } else if ($cofre['tipo'] == 'Jackpot') {
                if ($obj_id == 'JACKPOT') { darNikas(75); darBerries(75000000); darExp(250); darOficio(2000); darNikas(40); darBerries(40000000); darOficio(1000); darExp(125); darNikas(85); darNikas(75); darBerries(75000000); darOficio(2000); darExp(250); darObjeto('TAK003', '1'); darObjeto('TAK004', '1'); darObjeto('WAZ003', '1'); darObjeto('WAZ004', '1'); darObjeto('TARM004', '1');darObjeto('TARM005', '1'); darObjeto('ANM003', '1'); darObjeto('ANM004', '1'); darObjeto('ANM005', '1'); darObjeto('CFD004', '1'); darObjeto('NTC005', '1'); darObjeto('RTO005', '1'); darObjeto('EANM001', '1'); darObjeto('PEESP001', '1'); darObjeto('TTUN009', '1'); darObjeto('TMJT009', '1'); darObjeto('INV004', '1'); darObjeto('KK006', '1'); darObjeto('KK080', '1'); darObjeto('CFR009', '1'); darObjeto('CFR007', '1'); darObjeto('THR001', '1'); }
            }

            $objeto = $cofre['nombre'];
            $objeto_id = $cofre['objeto_id'];
        }

        if ($cofre_id == 'CFR009') { // Cofre Majestuoso
            if ($cofre['tipo'] == 'Objeto') {
                darObjeto($obj_id, $obj_cantidad);
            } else if ($cofre['tipo'] == 'Custom') {
                
                if ($obj_id == '100N') { darNikas(100); }
                if ($obj_id == '250MB') { darBerries(250000000); }
                if ($obj_id == '350E') { darExp(350); }
                if ($obj_id == '2500PO') { darOficio(2500); }
                if ($obj_id == '50N125M1250PO175E') { darNikas(50); darBerries(125000000); darOficio(1250); darExp(175); }
                if ($obj_id == '120N') { darNikas(120); }
                if ($obj_id == '100N250MB2500PO350E') { darNikas(100); darBerries(250000000); darOficio(2500); darExp(350); }
                
            } else if ($cofre['tipo'] == 'Jackpot') {
                if ($obj_id == 'JACKPOT') { darNikas(100); darBerries(250000000); darExp(350); darOficio(2500); darNikas(50); darBerries(125000000); darOficio(1250); darExp(175); darNikas(120); darNikas(100); darBerries(250000000); darOficio(2500); darExp(350); darObjeto('TAK004', '1'); darObjeto('TAK005', '1'); darObjeto('WAZ004', '1'); darObjeto('WAZ005', '1'); darObjeto('TARM005', '1');darObjeto('ANM005', '1'); darObjeto('ANM006', '1'); darObjeto('CFD004', '1'); darObjeto('NTC005', '1'); darObjeto('RTO005', '1'); darObjeto('EANM001', '1'); darObjeto('PEESP001', '1'); darObjeto('TTUN010', '1'); darObjeto('INV005', '1'); darObjeto('KK100', '1'); darObjeto('CFR010', '1'); darObjeto('CFR008', '1'); darObjeto('THR001', '1'); }
            }

            $objeto = $cofre['nombre'];
            $objeto_id = $cofre['objeto_id'];
        }

        if ($cofre_id == 'CFR010') { // Cofre Diamantino
            if ($cofre['tipo'] == 'Objeto') {
                darObjeto($obj_id, $obj_cantidad);
            } else if ($cofre['tipo'] == 'Custom') {
                
                if ($obj_id == '150N') { darNikas(150); }
                if ($obj_id == '1000MB') { darBerries(1000000000); }
                if ($obj_id == '500E') { darExp(500); }
                if ($obj_id == '5000PO') { darOficio(5000); }
                if ($obj_id == '150N1000MB500E5000PO') { darNikas(150); darBerries(1000000000); darOficio(5000); darExp(500); }
            
                
            } else if ($cofre['tipo'] == 'Jackpot') {
                if ($obj_id == 'JACKPOT') { darNikas(150); darBerries(1000000000); darExp(500); darOficio(5000); darNikas(150); darBerries(1000000000); darOficio(5000); darExp(500); darObjeto('TAK005', '1'); darObjeto('WAZ005', '1'); darObjeto('ANM006', '1'); darObjeto('EANM001', '1'); darObjeto('TTUN010', '3');darObjeto('TMJT010', '3'); darObjeto('INV005', '1'); darObjeto('KK100', '1'); darObjeto('CFR006', '5'); }
            }

            $objeto = $cofre['nombre'];
            $objeto_id = $cofre['objeto_id'];
        }

        // NARRACOFRES
        if ($cofre_id == 'CFN002') { // Narracofre Decente
            if ($cofre['tipo'] == 'Objeto') {
                darObjeto($obj_id, $obj_cantidad);
            } else if ($cofre['tipo'] == 'Custom') {
                
                if ($obj_id == '10N') { darNikas(10); }
                if ($obj_id == '500KB') { darBerries(500000); }
                if ($obj_id == '5N250KB') { darNikas(5); darBerries(250000);}
                if ($obj_id == '15N') { darNikas(15); }
                if ($obj_id == '10N500KB') { darNikas(10); darBerries(500000);}
                
            } else if ($cofre['tipo'] == 'Jackpot') {
                if ($obj_id == 'JACKPOT') { darNikas(10); darBerries(500000); darNikas(5); darBerries(250000); darNikas(15); darNikas(10); darBerries(500000); darObjeto('TARM001', '1'); darObjeto('TARM002', '1'); darObjeto('CFD002', '1');  darObjeto('NTC002', '1'); darObjeto('RTO002', '1');  darObjeto('BLVIP001', '1'); darObjeto('PED003', '1'); darObjeto('TTUN003', '1'); darObjeto('TMJT003', '1'); darObjeto('INV001', '1'); darObjeto('CFR003', '1'); darObjeto('CFR001', '1'); darObjeto('THR001', '1'); }
            }

            $objeto = $cofre['nombre'];
            $objeto_id = $cofre['objeto_id'];
        }

        if ($cofre_id == 'CFN003') { // Narracofre Gigante
            if ($cofre['tipo'] == 'Objeto') {
                darObjeto($obj_id, $obj_cantidad);
            } else if ($cofre['tipo'] == 'Custom') {
                
                if ($obj_id == '15N') { darNikas(15); }
                if ($obj_id == '1MB') { darBerries(1000000); }
                if ($obj_id == '8N500KB') { darNikas(8); darBerries(500000);}
                if ($obj_id == '25N') { darNikas(25); }
                if ($obj_id == '15N1MB') { darNikas(15); darBerries(1000000);}
                
            } else if ($cofre['tipo'] == 'Jackpot') {
                if ($obj_id == 'JACKPOT') { darNikas(15); darBerries(1000000); darNikas(8); darBerries(500000); darNikas(25); darNikas(15); darBerries(1000000); darObjeto('TARM002', '1'); darObjeto('CFD002', '1'); darObjeto('NTC002', '1');  darObjeto('RTO002', '1'); darObjeto('BLVIP001', '1');  darObjeto('PED003', '1'); darObjeto('TTUN004', '1'); darObjeto('TTUN003', '1'); darObjeto('TMJT004', '1'); darObjeto('INV002', '1'); darObjeto('KSP001', '1'); darObjeto('CFR004', '1'); darObjeto('CFR002', '1'); darObjeto('THR001', '1'); }
            }

            $objeto = $cofre['nombre'];
            $objeto_id = $cofre['objeto_id'];
        }

        if ($cofre_id == 'CFN004') { // Narracofre Cobrizo
            if ($cofre['tipo'] == 'Objeto') {
                darObjeto($obj_id, $obj_cantidad);
            } else if ($cofre['tipo'] == 'Custom') {
                
                if ($obj_id == '20N') { darNikas(20); }
                if ($obj_id == '2500KB') { darBerries(2500000); }
                if ($obj_id == '10N1250KB') { darNikas(10); darBerries(1250000);}
                if ($obj_id == '35N') { darNikas(35); }
                if ($obj_id == '20N2500KB') { darNikas(20); darBerries(2500000);}
                
            } else if ($cofre['tipo'] == 'Jackpot') {
                if ($obj_id == 'JACKPOT') { darNikas(20); darBerries(2500000); darNikas(10); darBerries(1250000); darNikas(35); darNikas(20); darBerries(2500000); darObjeto('TARM002', '1'); darObjeto('TARM003', '1'); darObjeto('VCD001', '1');  darObjeto('CFD003', '1'); darObjeto('NTC003', '1');  darObjeto('RTO003', '1'); darObjeto('BLVIP001', '1'); darObjeto('PED004', '1'); darObjeto('PEESP001', '1'); darObjeto('TTUN005', '1'); darObjeto('TMJT005', '1'); darObjeto('INV002', '1'); darObjeto('KSP001', '1'); darObjeto('CFR005', '1'); darObjeto('CFR003', '1'); darObjeto('THR001', '1'); }
            }

            $objeto = $cofre['nombre'];
            $objeto_id = $cofre['objeto_id'];
        }

        if ($cofre_id == 'CFN005') { // Narracofre Argenteo
            if ($cofre['tipo'] == 'Objeto') {
                darObjeto($obj_id, $obj_cantidad);
            } else if ($cofre['tipo'] == 'Custom') {
                
                if ($obj_id == '30N') { darNikas(30); }
                if ($obj_id == '7MB') { darBerries(7000000); }
                if ($obj_id == '15N3500KB') { darNikas(15); darBerries(3500000);}
                if ($obj_id == '45N') { darNikas(45); }
                if ($obj_id == '30N7MB') { darNikas(30); darBerries(7000000);}
                
            } else if ($cofre['tipo'] == 'Jackpot') {
                if ($obj_id == 'JACKPOT') { darNikas(30); darBerries(7000000); darNikas(15); darBerries(3500000); darNikas(45); darNikas(30); darBerries(7000000); darObjeto('TARM003', '1'); darObjeto('VCD001', '1'); darObjeto('CFD003', '1');  darObjeto('NTC003', '1'); darObjeto('RTO003', '1');  darObjeto('BLVIP001', '1'); darObjeto('PED004', '1'); darObjeto('PEESP001', '1'); darObjeto('TTUN006', '1'); darObjeto('TMJT006', '1'); darObjeto('INV003', '1'); darObjeto('KK0020', '1'); darObjeto('CFR006', '5'); darObjeto('CFR004', '1'); darObjeto('THR001', '1'); }
            }

         $objeto = $cofre['nombre'];
         $objeto_id = $cofre['objeto_id'];
           
        }

        if ($cofre_id == 'CFN006') { // Narracofre Aureo
            if ($cofre['tipo'] == 'Objeto') {
                darObjeto($obj_id, $obj_cantidad);
            } else if ($cofre['tipo'] == 'Custom') {
                if ($obj_id == '40N') { darNikas(40); }
                if ($obj_id == '15MB') { darBerries(15000000); }
                if ($obj_id == '20N7500KB') { darNikas(20); darBerries(7500000);}
                if ($obj_id == '55N') { darNikas(55); }
                if ($obj_id == '40N15M') { darNikas(40); darBerries(15000000);}
                
            } else if ($cofre['tipo'] == 'Jackpot') {
                if ($obj_id == 'JACKPOT') { darNikas(40); darBerries(15000000); darNikas(20); darBerries(7500000); darNikas(55); darNikas(40); darBerries(15000000); darObjeto('CFR005', '1'); darObjeto('CFR007', '1'); darObjeto('THR001', '1');  darObjeto('KK040', '1'); darObjeto('INV003', '1');  darObjeto('TMJT007', '1'); darObjeto('TTUN007', '1'); darObjeto('PEESP001', '1'); darObjeto('PED005', '1'); darObjeto('BLVIP001', '1'); darObjeto('RTO004', '1'); darObjeto('NTC004', '1'); darObjeto('CFD003', '1'); darObjeto('VCZ001', '1'); darObjeto('KMP001', '1'); darObjeto('EPA001', '1'); darObjeto('TARM004', '1'); darObjeto('TARM003', '1'); darObjeto('TAK003', '1');}
            }

            $objeto = $cofre['nombre'];
            $objeto_id = $cofre['objeto_id'];
        }

        if ($cofre_id == 'CFN007') { // Narracofre Epico
            if ($cofre['tipo'] == 'Objeto') {
                darObjeto($obj_id, $obj_cantidad);
            } else if ($cofre['tipo'] == 'Custom') {
                
                if ($obj_id == '50N') { darNikas(50); }
                if ($obj_id == '30MB') { darBerries(30000000); }
                if ($obj_id == '25N15M') { darNikas(25); darBerries(15000000);}
                if ($obj_id == '65N') { darNikas(65); }
                if ($obj_id == '50N30MB') { darNikas(50); darBerries(30000000);}
                
            } else if ($cofre['tipo'] == 'Jackpot') {            
                if ($obj_id == 'JACKPOT') { darNikas(50); darBerries(30000000); darNikas(25); darBerries(15000000); darNikas(65); darNikas(50); darBerries(30000000); darObjeto('TAK003', '1'); darObjeto('WAZ003', '1'); darObjeto('TARM004', '1'); darObjeto('EPA001', '1'); darObjeto('KMP001', '1');darObjeto('ANM001', '1'); darObjeto('ANM002', '1'); darObjeto('ANM003', '1'); darObjeto('ANM004', '1'); darObjeto('CFD004', '1'); darObjeto('NTC004', '1'); darObjeto('RTO004', '1'); darObjeto('PED005', '1'); darObjeto('PEESP001', '1'); darObjeto('TTUN008', '1'); darObjeto('TMJT008', '1'); darObjeto('INV004', '1'); darObjeto('KK006', '1'); darObjeto('CFR008', '1'); darObjeto('CFR006', '1'); darObjeto('THR001', '1'); }                
            }

            $objeto = $cofre['nombre'];
            $objeto_id = $cofre['objeto_id'];
        }
        
        if ($cofre_id == 'CFN008') { // Narracofre Legendario
            if ($cofre['tipo'] == 'Objeto') {
                darObjeto($obj_id, $obj_cantidad);
            } else if ($cofre['tipo'] == 'Custom') {
                
                if ($obj_id == '75N') { darNikas(75); }
                if ($obj_id == '75MB') { darBerries(75000000); }
                if ($obj_id == '40N40M') { darNikas(40); darBerries(40000000);}
                if ($obj_id == '85N') { darNikas(85); }
                if ($obj_id == '75N75MB') { darNikas(75); darBerries(75000000);}
                
            } else if ($cofre['tipo'] == 'Jackpot') {
                if ($obj_id == 'JACKPOT') { darNikas(75); darBerries(75000000); darNikas(40); darBerries(40000000); darNikas(85); darNikas(75); darBerries(75000000); darObjeto('TAK003', '1'); darObjeto('TAK004', '1'); darObjeto('WAZ003', '1'); darObjeto('WAZ004', '1'); darObjeto('TARM004', '1');darObjeto('TARM005', '1'); darObjeto('ANM003', '1'); darObjeto('ANM004', '1'); darObjeto('ANM005', '1'); darObjeto('CFD004', '1'); darObjeto('NTC005', '1'); darObjeto('RTO005', '1'); darObjeto('EANM001', '1'); darObjeto('PEESP001', '1'); darObjeto('TTUN009', '1'); darObjeto('TMJT009', '1'); darObjeto('INV004', '1'); darObjeto('KK006', '1'); darObjeto('KK080', '1'); darObjeto('CFR009', '1'); darObjeto('CFR007', '1'); darObjeto('THR001', '1'); }
            }

            $objeto = $cofre['nombre'];
            $objeto_id = $cofre['objeto_id'];
        }

        // COFRES MEMES Y NO TAN MEMES
        if ($cofre_id == 'KTC001') { // Katacofre Regular
            if ($cofre['tipo'] == 'Objeto') {
                darObjeto($obj_id, $obj_cantidad);
            } else if ($cofre['tipo'] == 'Custom') {
                
                if ($obj_id == '1N') { darNikas(1); }
                if ($obj_id == '1000B') { darBerries(1000); }
                if ($obj_id == '1E') { darExp(1); }
                if ($obj_id == '10PO') { darOficio(10); }
                if ($obj_id == '1N1000B1E10PO') { darNikas(1); darBerries(1000); darOficio(10); darExp(1); }
            
        
                
            } else if ($cofre['tipo'] == 'Jackpot') {
                if ($obj_id == 'JACKPOT') { darNikas(10); darBerries(1000000); darExp(10); darOficio(100); darObjeto('LLST001', '1'); }
            }

            $objeto = $cofre['nombre'];
            $objeto_id = $cofre['objeto_id'];

        }

        // if ($cofre_id == 'KTC002') { // Katacofre Premium
        //     if ($cofre['tipo'] == 'Objeto') {
        //         darObjeto($obj_id, $obj_cantidad);
        //     } else if ($cofre['tipo'] == 'Custom') {
                
        //         if ($obj_id == '50N') { darNikas(50); }
        //         if ($obj_id == '20MB') { darBerries(20000000); }
        //         if ($obj_id == '200E') { darExp(200); }
        //         if ($obj_id == '1000PO') { darOficio(1000); }
        //         if ($obj_id == '200N20MB200E1000PO') { darNikas(50); darBerries(20000000); darOficio(1000); darExp(200); }
                            
        //     } else if ($cofre['tipo'] == 'Jackpot') {
        //         if ($obj_id == 'JACKPOT') { darNikas(100); darBerries(40000000); darExp(400); darOficio(2000); darObjeto('LLST001', '1'); }
        //     }

        //     $objeto = $cofre['nombre'];
        //     $objeto_id = $cofre['objeto_id'];

        // }

        // Caja de Artefacto T1,T2,T3,T4,T5 || CD T2,T3,T4 || Barril Rescatado
        if ($cofre_id == 'INV001' || $cofre_id == 'INV002' || $cofre_id == 'INV003' || $cofre_id == 'INV004' || $cofre_id == 'INV005' ||
            $cofre_id == 'CFD002' || $cofre_id == 'CFD003' || $cofre_id == 'CFD004' || $cofre_id == 'CRM003'
        ) { 
            if ($cofre['tipo'] == 'Objeto') {
                darObjeto($obj_id, $obj_cantidad);
            } 

            $objeto = $cofre['nombre'];
            $objeto_id = $cofre['objeto_id'];
        }


        $response[0] = array(
        'nombre' => $username,
        'tier' => $cofre_id,
        'objeto' => $objeto,
        'objeto_id' => $objeto_id,
        'timestamp' => $timestamp,
        'cofre_random' => $cofre_random
        );

        $db->query(" INSERT INTO `mybb_op_tirada_cofre`(`uid`, `nombre`, `tier`, `objeto`, `objeto_id`, `timestamp`, `cofre_random`) VALUES 
        ('$uid','$username','$cofre_id','$objeto','$objeto_id','$timestamp','$cofre_random')");

        echo json_encode($response); 

    
        return;

    } else {
        return;
    }


}

if (does_ficha_exist($uid) && $ficha_aprobada) {
    $query_inventario = $db->query("
        SELECT * FROM `mybb_op_objetos` 
        INNER JOIN `mybb_op_inventario` 
        ON `mybb_op_objetos`.`objeto_id`=`mybb_op_inventario`.`objeto_id` 
        WHERE `mybb_op_inventario`.`uid`='$uid' AND `mybb_op_objetos`.`subcategoria`='cofres'
    ");

    $objetos = array();
    $objetos_array = array();

    while ($q = $db->fetch_array($query_inventario)) { 
        $objeto_id = $q['objeto_id'];
        $key = "$objeto_id";
        if (!$objetos[$key]) { $objetos[$key] = array(); }
        array_push($objetos[$key], $q);
        array_push($objetos_array, $objeto_id);
    }

    $objetos_array_json = json_encode($objetos_array);
    $objetos_json = json_encode($objetos);

    eval("\$page = \"".$templates->get("op_tirada_cofre")."\";");
    output_page($page);
} else {
    $mensaje_redireccion = "Para acceder a esta página debes tener tu ficha aprobada.";
    eval("\$page = \"".$templates->get("op_redireccion")."\";");
    output_page($page);
}