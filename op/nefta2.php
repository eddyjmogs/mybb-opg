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
define('THIS_SCRIPT', 'nefta2.php');

require_once "./../global.php";
require_once "./functions/op_functions.php";

// ====================== CLONADO =========================

// === CONFIG LOG DEDICADO ===
$MIGRA_LOG = __DIR__ . '/migracion_mongo.log'; // extensión .log
ini_set('log_errors', '1');
ini_set('error_log', $MIGRA_LOG);
error_reporting(E_ALL);

// Logger simple
function migra_log($msg) {
    error_log('[MIGRA] ' . $msg);
}

// Ejecutar solo si paso el flag por URL
if (isset($_GET['migrate'])) {
    // Siempre JSON y sin basura previa
    ini_set('display_errors', '0'); // no imprimas warnings en la respuesta
    header('Content-Type: application/json; charset=utf-8');

    // Limpia cualquier salida previa (drena todos los buffers)
    while (ob_get_level()) { ob_end_clean(); }
    ob_start();

    migra_log('=== INICIO MIGRACIÓN ' . date('c') . ' ===');

    try {
        // 1) Comprobar extensión de Mongo
        migra_log('Comprobando extensión MongoDB...');
        if (!extension_loaded('mongodb') || !class_exists('MongoDB\\Driver\\Manager')) {
            migra_log('ERROR: extensión MongoDB no cargada o Manager no disponible.');
            throw new Exception('Extensión MongoDB no disponible en PHP.');
        }
        migra_log('OK: extensión MongoDB cargada.');

        // 2) Conexión a MongoDB
        $mongoUri = "mongodb+srv://migueldocalarea_db_user:OPGNewBD@cluster0.pljqn0x.mongodb.net/?retryWrites=true&w=majority&appName=Cluster0";
        migra_log('Creando Manager con URI SRV...');
        $manager = new MongoDB\Driver\Manager($mongoUri);
        migra_log('Manager creado.');

        // 3) Ping a Mongo
        migra_log('Haciendo ping a Mongo...');
        $pingCmd = new MongoDB\Driver\Command(['ping' => 1]);
        $manager->executeCommand('admin', $pingCmd);
        migra_log('Ping OK.');

        // 4) Namespace destino
        $dbName = 'OPG';
        $collection = 'op_fichaSecreta';
        $namespace = $dbName . '.' . $collection;
        migra_log("Usando namespace destino: $namespace");

        // 5) Contar filas fuente (MySQL)
        migra_log('Consultando total de filas en mybb_op_fichas_secret...');
        $cntRes = $db->query("SELECT COUNT(*) AS c FROM mybb_op_fichas_secret");
        $row = $db->fetch_array($cntRes);
        $total = $row ? (int)$row['c'] : 0;
        migra_log("Total filas encontradas: $total");

        // 6) Obtener datos en lotes
        $pageSize = 500;
        $pages = ($total > 0) ? (int)ceil($total / $pageSize) : 0;
        migra_log("Procesando en $pages páginas de tamaño $pageSize...");

        $insertadosTotal = 0;
        $upsertsTotal   = 0;
        $erroresTotal   = 0;

        for ($page = 0; $page < $pages; $page++) {
            $offset = $page * $pageSize;
            migra_log("Leyendo lote page=$page offset=$offset...");

            $res = $db->query("
                SELECT *
                FROM mybb_op_fichas_secret
                ORDER BY id ASC
                LIMIT {$pageSize} OFFSET {$offset}
            ");

            $bulk = new MongoDB\Driver\BulkWrite(['ordered' => false]);
            $loteCount = 0;

            while ($r = $db->fetch_array($res)) {
                $fid = isset($r['fid']) ? (int)$r['fid'] : 0;
                $secretNumber = isset($r['secret_number']) ? (string)$r['secret_number'] : '1';

                $doc = [
                    '_id'            => (string)$fid . '_' . $secretNumber,
                    'fid'            => $fid,
                    'secret_number'  => $secretNumber,
                    'historia'       => (string)($r['historia'] ?? ''),
                    'apariencia'     => (string)($r['apariencia'] ?? ''),
                    'personalidad'   => (string)($r['personalidad'] ?? ''),
                    'extra'          => (string)($r['extra'] ?? ''),
                    'created_at'     => isset($r['created_at']) ? (int)$r['created_at'] : null,
                    'updated_at'     => isset($r['updated_at']) ? (int)$r['updated_at'] : null,
                ];

                $bulk->update(
                    ['_id' => $doc['_id']],
                    ['$set' => $doc],
                    ['upsert' => true]
                );
                $loteCount++;
            }

            if ($loteCount === 0) {
                migra_log("Lote vacío, nada que escribir.");
                continue;
            }

            // 9) Ejecutar escritura
            migra_log("Ejecutando BulkWrite con $loteCount operaciones...");
            try {
                $result = $manager->executeBulkWrite($namespace, $bulk);

                $insertadosTotal += $result->getInsertedCount();
                $upsertsTotal    += $result->getUpsertedCount();

                migra_log(
                    "Bulk OK: inserted=" . $result->getInsertedCount() .
                    " upserted=" . $result->getUpsertedCount() .
                    " matched=" . $result->getMatchedCount() .
                    " modified=" . $result->getModifiedCount()
                );

                foreach ($result->getWriteErrors() as $e) {
                    $erroresTotal++;
                    migra_log("WriteError: idx=" . $e->getIndex() . " code=" . $e->getCode() . " msg=" . $e->getMessage());
                }
                if ($wce = $result->getWriteConcernError()) {
                    migra_log("WriteConcernError: code=" . $wce->getCode() . " msg=" . $wce->getMessage());
                }
            } catch (Throwable $e) {
                $erroresTotal++;
                migra_log("EXCEPCIÓN en BulkWrite: " . $e->getMessage());
            }
        }

        migra_log("RESUMEN: total=$total inserted=$insertadosTotal upserted=$upsertsTotal errores=$erroresTotal");
        migra_log('=== FIN MIGRACIÓN ' . date('c') . ' ===');

        // Respuesta JSON OK
        ob_clean();
        echo json_encode([
            'ok' => true,
            'message' => 'Migración finalizada',
            'resumen' => [
                'total'     => $total,
                'inserted'  => $insertadosTotal,
                'upserted'  => $upsertsTotal,
                'errores'   => $erroresTotal,
                'namespace' => $namespace
            ],
            'log' => basename($MIGRA_LOG)
        ], JSON_UNESCAPED_UNICODE);
        exit;

    } catch (Throwable $e) {
        migra_log('FALLO GENERAL: ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());

        http_response_code(500);
        ob_clean();
        echo json_encode([
            'ok' => false,
            'error' => $e->getMessage(),
            'file'  => $e->getFile(),
            'line'  => $e->getLine(),
            'log'   => basename($MIGRA_LOG)
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}





// --------------------------------------------------------

$accion = addslashes($_POST["accion"]);
$uid = addslashes($_POST["uid"]);

// $accion = $mybb->get_input('accion');
// $uid = $mybb->get_input('uid');

$fichas = array();

if ($accion == 'uid') {

    header('Content-type: application/json');
    $response = array();
    $timestamp = time();

    $query_fichas = $db->query(" SELECT * FROM mybb_op_fichas WHERE fid='$uid' ");

    $query_tec_aprendidas = $db->query("
        SELECT * FROM `mybb_op_tecnicas` 
        INNER JOIN `mybb_op_tec_aprendidas` 
        ON `mybb_op_tecnicas`.`tid`=`mybb_op_tec_aprendidas`.`tid` 
        WHERE `mybb_op_tec_aprendidas`.`uid`='$uid'
        ORDER BY `mybb_op_tecnicas`.`tid`,`mybb_op_tecnicas`.`clase`
    ");

    $query_inventario = $db->query("
        SELECT * FROM `mybb_op_objetos` 
        INNER JOIN `mybb_op_inventario` 
        ON `mybb_op_objetos`.`objeto_id`=`mybb_op_inventario`.`objeto_id` 
        WHERE `mybb_op_inventario`.`uid`='$uid'
        ORDER BY `mybb_op_objetos`.categoria, `mybb_op_objetos`.subcategoria, `mybb_op_objetos`.tier, `mybb_op_objetos`.nombre
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

    while ($q = $db->fetch_array($query_fichas)) {
        $fichas_json = json_encode($q);
    }

    while ($q = $db->fetch_array($query_tec_aprendidas)) {
        $tecnicas_json = json_encode($q);
    }

    $response[0] = array(
        'ficha' => $fichas_json,
        'tecnicas' => $tecnicas_json,
        'inventario_array' => $objetos_array_json,
        'inventario' => $objetos_json
    );

    echo json_encode($response); 
    return;
}

eval("\$page = \"".$templates->get("op_nefta2")."\";");
output_page($page);
