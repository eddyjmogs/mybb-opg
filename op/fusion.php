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
define('THIS_SCRIPT', 'fusion.php');

require_once "./../global.php";

// Obtener el UID del usuario actual
$uid = $mybb->user['uid'];

// Verificar si el usuario está loggeado
if (!$uid) {
    error_no_permission();
}

$accion = $mybb->get_input('accion');

// Procesar acciones AJAX antes de cargar templates
if ($accion == 'fusionar') {
    // Aquí procesaremos la fusión cuando se implemente
    header('Content-type: application/json');
    
    $base_id = $mybb->get_input('base_id');
    $engaste_id = $mybb->get_input('engaste_id');
    
    // TODO: Implementar lógica de fusión
    echo json_encode(['success' => true, 'message' => 'Fusión completada']);
    exit;
}

if ($accion == 'upgrade_parte') {
    // Limpiar cualquier output buffer previo
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    // Importante: no cargar templates, solo procesar y devolver JSON
    header('Content-type: application/json; charset=utf-8');
    
    $objeto_id = $db->escape_string($mybb->get_input('objeto_id'));
    
    // Obtener información del objeto
    $query_objeto = $db->query("SELECT * FROM mybb_op_objetos WHERE objeto_id='$objeto_id'");
    $objeto = $db->fetch_array($query_objeto);
    
    if (!$objeto) {
        echo json_encode(['error' => 'Objeto no encontrado']);
        exit;
    }
    
    // Verificar que sea una parte y tier < 5 (case insensitive)
    $subcategoria_lower = strtolower(trim($objeto['subcategoria']));
    $tier_actual = intval($objeto['tier']);
    
    if ($subcategoria_lower != 'partes' || $tier_actual >= 5) {
        echo json_encode([
            'error' => 'Este objeto no puede ser upgradeado',
            'debug' => [
                'subcategoria' => $objeto['subcategoria'],
                'subcategoria_lower' => $subcategoria_lower,
                'tier' => $tier_actual,
                'objeto_id' => $objeto_id
            ]
        ]);
        exit;
    }
    
    // Obtener datos de la ficha
    $query_ficha = $db->query("SELECT * FROM mybb_op_fichas WHERE fid='$uid'");
    $ficha = $db->fetch_array($query_ficha);
    
    if (!$ficha) {
        echo json_encode(['error' => 'Ficha no encontrada']);
        exit;
    }
    
    // Definir requisitos según tier
    $requisitos = array(
        1 => array('cantidad' => 3, 'nivel' => 10, 'nikas' => 5),
        2 => array('cantidad' => 5, 'nivel' => 20, 'nikas' => 10),
        3 => array('cantidad' => 5, 'nivel' => 30, 'nikas' => 15),
        4 => array('cantidad' => 5, 'nivel' => 40, 'nikas' => 20)
    );
    
    // $tier_actual ya fue definido arriba
    $req = $requisitos[$tier_actual];
    
    // Verificar nivel
    if (intval($ficha['nivel']) < $req['nivel']) {
        echo json_encode(['error' => 'Nivel insuficiente. Necesitas nivel ' . $req['nivel']]);
        exit;
    }
    
    // Verificar nikas
    if (intval($ficha['nika']) < $req['nikas']) {
        echo json_encode(['error' => 'Nikas insuficientes. Necesitas ' . $req['nikas'] . ' nikas']);
        exit;
    }
    
    // Obtener el registro del inventario
    $query_inventario = $db->query("SELECT * FROM mybb_op_inventario WHERE uid='$uid' AND objeto_id='$objeto_id'");
    $inventario = $db->fetch_array($query_inventario);
    
    if (!$inventario) {
        echo json_encode(['error' => 'No tienes este objeto en tu inventario']);
        exit;
    }
    
    $cantidad_actual = intval($inventario['cantidad']);
    
    if ($cantidad_actual < $req['cantidad']) {
        echo json_encode(['error' => 'No tienes suficientes partes. Necesitas ' . $req['cantidad'] . ', tienes ' . $cantidad_actual]);
        exit;
    }
    
    // Generar el objeto_id del tier superior
    // Extraer prefijo y número
    preg_match('/^([A-Z]+)(\d+)$/', $objeto_id, $matches);
    if (count($matches) != 3) {
        echo json_encode(['error' => 'Formato de ID no válido']);
        exit;
    }
    
    $prefijo = $matches[1];
    $numero = $matches[2];
    
    // Incrementar el número para obtener el tier superior
    $numero_superior = intval($numero) + 1;
    $objeto_id_superior = $prefijo . str_pad($numero_superior, strlen($numero), '0', STR_PAD_LEFT);
    
    // Verificar que el objeto superior existe
    $query_objeto_superior = $db->query("SELECT * FROM mybb_op_objetos WHERE objeto_id='$objeto_id_superior'");
    $objeto_superior = $db->fetch_array($query_objeto_superior);
    
    if (!$objeto_superior || intval($objeto_superior['tier']) != ($tier_actual + 1)) {
        echo json_encode(['error' => 'No existe el tier superior para esta parte']);
        exit;
    }
    
    // Todo validado, proceder con el upgrade
    // 1. Restar las partes del tier inferior (o eliminar si quedan 0)
    $cantidad_restante = $cantidad_actual - $req['cantidad'];
    if ($cantidad_restante <= 0) {
        $db->query("DELETE FROM mybb_op_inventario WHERE uid='$uid' AND objeto_id='$objeto_id'");
    } else {
        $db->query("UPDATE mybb_op_inventario SET cantidad='$cantidad_restante' WHERE uid='$uid' AND objeto_id='$objeto_id'");
    }
    
    // 2. Restar nikas
    $nikas_nuevo = intval($ficha['nika']) - $req['nikas'];
    $db->query("UPDATE mybb_op_fichas SET nika='$nikas_nuevo' WHERE fid='$uid'");
    
    // 3. Agregar el objeto del tier superior (o incrementar si ya existe)
    $query_superior_existente = $db->query("SELECT * FROM mybb_op_inventario WHERE uid='$uid' AND objeto_id='$objeto_id_superior'");
    $superior_existente = $db->fetch_array($query_superior_existente);
    
    if ($superior_existente) {
        $cantidad_nueva = intval($superior_existente['cantidad']) + 1;
        $db->query("UPDATE mybb_op_inventario SET cantidad='$cantidad_nueva' WHERE uid='$uid' AND objeto_id='$objeto_id_superior'");
    } else {
        $db->query("INSERT INTO mybb_op_inventario (uid, objeto_id, cantidad) VALUES ('$uid', '$objeto_id_superior', 1)");
    }
    
    echo json_encode([
        'success' => true, 
        'message' => 'Upgrade completado exitosamente',
        'objeto_nuevo' => $objeto_superior['nombre'],
        'tier_nuevo' => $objeto_superior['tier']
    ]);
    exit;
}

// Obtener el inventario del usuario para la interfaz
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
    $cantidad = intval($q['cantidad']);
    $key = "$objeto_id";
    if (!$objetos[$key]) { $objetos[$key] = array(); }
    array_push($objetos[$key], $q);
    
    // Agregar el objeto_id tantas veces como cantidad tenga
    for ($i = 0; $i < $cantidad; $i++) {
        array_push($objetos_array, $objeto_id);
    }
}

// Convertir a JSON para JavaScript
$objetos_array_json = json_encode($objetos_array);
$objetos_json = json_encode($objetos);

eval("\$page = \"".$templates->get("op_fusion")."\";");
output_page($page);
