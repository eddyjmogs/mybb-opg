<?php
/**
 * MyBB 1.8
 */

define('IN_MYBB', 1);
define('THIS_SCRIPT', 'isla.php');

// --- DEPURACIÓN TEMPORAL (quítalo al terminar) ---
ini_set('display_errors', '1');               // mostrar (solo mientras depuras)
ini_set('display_startup_errors', '1');
ini_set('log_errors', '1');                   // deja esto en 1 siempre
ini_set('error_log', __DIR__ . '/php-error.log');
error_reporting(E_ALL);
// -----------------------------------------------

require_once __DIR__ . '/../global.php';
require_once __DIR__ . '/functions/op_functions.php';

global $templates, $mybb, $db, $lang;

$uid = (int)$mybb->user['uid'];
$action = $mybb->get_input('action');

// Log todas las peticiones
error_log("[ISLA] Peticion recibida - Action: {$action}, Method: {$_SERVER['REQUEST_METHOD']}, UID: {$uid}");

// Manejo de acción AJAX para guardar
if ($action === 'guardar_isla' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    // Verificar permisos
    if (!is_narra($uid) && !is_staff($uid)) {
        echo json_encode(['success' => false, 'message' => 'No tienes permisos para editar islas']);
        exit;
    }
    
    $isla_id = (int)$mybb->get_input('isla_id', MyBB::INPUT_INT);
    $zonas = $db->escape_string($mybb->get_input('zonas'));
    $comercio = $db->escape_string($mybb->get_input('comercio'));
    $tamano = $db->escape_string($mybb->get_input('tamano'));
    $faccion = $db->escape_string($mybb->get_input('faccion'));
    $gobierno = $db->escape_string($mybb->get_input('gobierno'));
    $habitantes = $db->escape_string($mybb->get_input('habitantes'));
    
    if ($isla_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID de isla inválido']);
        exit;
    }
    
    // Verificar si existe la tabla
    if (!$db->table_exists('op_islas')) {
        echo json_encode(['success' => false, 'message' => 'La tabla op_islas no existe']);
        exit;
    }
    
    // Verificar si existe el registro
    $query = $db->query("SELECT isla_id FROM mybb_op_islas WHERE isla_id = {$isla_id}");
    $existe = $db->fetch_array($query);
    
    if ($existe) {
        // Actualizar
        $db->query("
            UPDATE mybb_op_islas 
            SET zonas = '{$zonas}',
                comercio = '{$comercio}',
                tamano = '{$tamano}',
                faccion = '{$faccion}',
                gobierno = '{$gobierno}',
                habitantes = '{$habitantes}'
            WHERE isla_id = {$isla_id}
        ");
    } else {
        // Insertar
        $db->query("
            INSERT INTO mybb_op_islas (isla_id, zonas, comercio, tamano, faccion, gobierno, habitantes)
            VALUES ({$isla_id}, '{$zonas}', '{$comercio}', '{$tamano}', '{$faccion}', '{$gobierno}', '{$habitantes}')
        ");
    }
    
    echo json_encode(['success' => true, 'message' => 'Isla guardada correctamente']);
    exit;
}

// Manejo de acción AJAX para buscar NPCs
if ($action === 'buscar_npcs' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    // Verificar permisos
    if (!is_narra($uid) && !is_staff($uid)) {
        echo json_encode(['success' => false, 'message' => 'No tienes permisos para gestionar NPCs']);
        exit;
    }
    
    $termino = $db->escape_string(trim($mybb->get_input('termino')));
    
    if (strlen($termino) < 2) {
        echo json_encode(['success' => false, 'message' => 'Escribe al menos 2 caracteres']);
        exit;
    }
    
    // Buscar NPCs por nombre
    $query = $db->query("
        SELECT npc_id, nombre, faccion, avatar1
        FROM mybb_op_npcs 
        WHERE nombre LIKE '%{$termino}%'
        ORDER BY nombre ASC
        LIMIT 20
    ");
    
    $resultados = [];
    while ($npc = $db->fetch_array($query)) {
        $resultados[] = [
            'npc_id' => $npc['npc_id'],
            'nombre' => $npc['nombre'],
            'faccion' => $npc['faccion'],
            'avatar1' => $npc['avatar1']
        ];
    }
    
    echo json_encode(['success' => true, 'npcs' => $resultados]);
    exit;
}

// Manejo de acción AJAX para guardar habitantes
if ($action === 'guardar_habitantes' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    error_log("[ISLA DEBUG] Guardando habitantes - UID: {$uid}");
    
    // Verificar permisos
    if (!is_narra($uid) && !is_staff($uid)) {
        error_log("[ISLA DEBUG] Sin permisos");
        echo json_encode(['success' => false, 'message' => 'No tienes permisos para editar habitantes']);
        exit;
    }
    
    $isla_id = (int)$mybb->get_input('isla_id', MyBB::INPUT_INT);
    $habitantes = $db->escape_string($mybb->get_input('habitantes'));
    
    error_log("[ISLA DEBUG] isla_id: {$isla_id}, habitantes: {$habitantes}");
    
    if ($isla_id <= 0) {
        error_log("[ISLA DEBUG] ID inválido");
        echo json_encode(['success' => false, 'message' => 'ID de isla inválido']);
        exit;
    }
    
    // Verificar si la tabla existe
    if (!$db->table_exists('op_islas')) {
        error_log("[ISLA DEBUG] Tabla op_islas no existe");
        echo json_encode(['success' => false, 'message' => 'La tabla op_islas no existe']);
        exit;
    }
    
    // Verificar si existe el registro
    $query = $db->query("SELECT isla_id FROM mybb_op_islas WHERE isla_id = {$isla_id}");
    $existe = $db->fetch_array($query);
    
    if ($existe) {
        // Actualizar
        error_log("[ISLA DEBUG] Actualizando registro existente");
        $result = $db->query("
            UPDATE mybb_op_islas 
            SET habitantes = '{$habitantes}'
            WHERE isla_id = {$isla_id}
        ");
        error_log("[ISLA DEBUG] UPDATE result: " . ($result ? "OK" : "FAIL"));
    } else {
        // Insertar
        error_log("[ISLA DEBUG] Insertando nuevo registro");
        $result = $db->query("
            INSERT INTO mybb_op_islas (isla_id, habitantes)
            VALUES ({$isla_id}, '{$habitantes}')
        ");
        error_log("[ISLA DEBUG] INSERT result: " . ($result ? "OK" : "FAIL"));
    }
    
    error_log("[ISLA DEBUG] Guardado completado");
    echo json_encode(['success' => true, 'message' => 'Habitantes actualizados correctamente']);
    exit;
}

// Manejo de acción AJAX para crear evento
if ($action === 'crear_evento' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    // Verificar permisos
    if (!is_narra($uid) && !is_staff($uid)) {
        echo json_encode(['success' => false, 'message' => 'No tienes permisos para crear eventos']);
        exit;
    }
    
    $isla_id = (int)$mybb->get_input('isla_id', MyBB::INPUT_INT);
    $titulo = $db->escape_string(trim($mybb->get_input('titulo')));
    $descripcion = $db->escape_string(trim($mybb->get_input('descripcion')));
    $ano = (int)$mybb->get_input('ano', MyBB::INPUT_INT);
    $estacion = $db->escape_string($mybb->get_input('estacion'));
    $dia = (int)$mybb->get_input('dia', MyBB::INPUT_INT);
    
    // Validaciones
    if ($isla_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID de isla inválido']);
        exit;
    }
    
    if (empty($descripcion)) {
        echo json_encode(['success' => false, 'message' => 'La descripción no puede estar vacía']);
        exit;
    }
    
    if ($dia < 1 || $dia > 90) {
        echo json_encode(['success' => false, 'message' => 'El día debe estar entre 1 y 90']);
        exit;
    }
    
    if (!in_array($estacion, ['Primavera', 'Verano', 'Otoño', 'Invierno'])) {
        echo json_encode(['success' => false, 'message' => 'Estación inválida']);
        exit;
    }
    
    // Verificar si existe la tabla
    if (!$db->table_exists('op_isla_eventos')) {
        echo json_encode(['success' => false, 'message' => 'La tabla de eventos no existe']);
        exit;
    }
    
    // Insertar evento
    $fecha_creacion = time();
    $db->query("
        INSERT INTO mybb_op_isla_eventos (isla_id, titulo, descripcion, ano, estacion, dia, staff_uid, fecha_creacion)
        VALUES ({$isla_id}, '{$titulo}', '{$descripcion}', {$ano}, '{$estacion}', {$dia}, {$uid}, {$fecha_creacion})
    ");
    
    $evento_id = $db->insert_id();
    
    // Obtener username para la respuesta
    $username = htmlspecialchars_uni($mybb->user['username']);
    
    echo json_encode([
        'success' => true, 
        'message' => 'Evento creado correctamente',
        'evento' => [
            'evento_id' => $evento_id,
            'dia' => $dia,
            'estacion' => $estacion,
            'ano' => $ano,
            'descripcion' => nl2br(htmlspecialchars_uni($descripcion)),
            'username' => $username
        ]
    ]);
    exit;
}

// Manejo de acción AJAX para editar evento
if ($action === 'editar_evento' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    $evento_id = (int)$mybb->get_input('evento_id', MyBB::INPUT_INT);
    
    if ($evento_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID de evento inválido']);
        exit;
    }
    
    // Verificar si existe la tabla
    if (!$db->table_exists('op_isla_eventos')) {
        echo json_encode(['success' => false, 'message' => 'La tabla de eventos no existe']);
        exit;
    }
    
    // Obtener el evento
    $query_evento = $db->query("SELECT * FROM mybb_op_isla_eventos WHERE evento_id = {$evento_id}");
    $evento = $db->fetch_array($query_evento);
    
    if (!$evento) {
        echo json_encode(['success' => false, 'message' => 'Evento no encontrado']);
        exit;
    }
    
    // UIDs con permisos especiales
    $admin_uids = [850, 268, 279, 23];
    
    // Verificar permisos: el creador o UIDs especiales pueden editar
    if ($evento['staff_uid'] != $uid && !in_array($uid, $admin_uids)) {
        echo json_encode(['success' => false, 'message' => 'Solo el creador puede editar este evento']);
        exit;
    }
    
    $descripcion = $db->escape_string(trim($mybb->get_input('descripcion')));
    $ano = (int)$mybb->get_input('ano', MyBB::INPUT_INT);
    $estacion = $db->escape_string($mybb->get_input('estacion'));
    $dia = (int)$mybb->get_input('dia', MyBB::INPUT_INT);
    
    // Validaciones
    if (empty($descripcion)) {
        echo json_encode(['success' => false, 'message' => 'La descripción no puede estar vacía']);
        exit;
    }
    
    if ($dia < 1 || $dia > 90) {
        echo json_encode(['success' => false, 'message' => 'El día debe estar entre 1 y 90']);
        exit;
    }
    
    if (!in_array($estacion, ['Primavera', 'Verano', 'Otoño', 'Invierno'])) {
        echo json_encode(['success' => false, 'message' => 'Estación inválida']);
        exit;
    }
    
    // Actualizar evento
    $db->query("
        UPDATE mybb_op_isla_eventos
        SET descripcion = '{$descripcion}',
            ano = {$ano},
            estacion = '{$estacion}',
            dia = {$dia}
        WHERE evento_id = {$evento_id}
    ");
    
    echo json_encode([
        'success' => true,
        'message' => 'Evento actualizado correctamente',
        'evento' => [
            'evento_id' => $evento_id,
            'dia' => $dia,
            'estacion' => $estacion,
            'ano' => $ano,
            'descripcion' => nl2br(htmlspecialchars_uni($descripcion))
        ]
    ]);
    exit;
}

// Manejo de acción AJAX para eliminar evento
if ($action === 'eliminar_evento' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    $evento_id = (int)$mybb->get_input('evento_id', MyBB::INPUT_INT);
    
    if ($evento_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID de evento inválido']);
        exit;
    }
    
    // Verificar si existe la tabla
    if (!$db->table_exists('op_isla_eventos')) {
        echo json_encode(['success' => false, 'message' => 'La tabla de eventos no existe']);
        exit;
    }
    
    // Obtener el evento
    $query_evento = $db->query("SELECT * FROM mybb_op_isla_eventos WHERE evento_id = {$evento_id}");
    $evento = $db->fetch_array($query_evento);
    
    if (!$evento) {
        echo json_encode(['success' => false, 'message' => 'Evento no encontrado']);
        exit;
    }
    
    // UIDs con permisos especiales
    $admin_uids = [850, 268, 279, 23];
    
    // Verificar permisos: el creador o UIDs especiales pueden eliminar
    if ($evento['staff_uid'] != $uid && !in_array($uid, $admin_uids)) {
        echo json_encode(['success' => false, 'message' => 'Solo el creador puede eliminar este evento']);
        exit;
    }
    
    // Eliminar evento
    $db->delete_query('op_isla_eventos', "evento_id = {$evento_id}");
    
    echo json_encode([
        'success' => true,
        'message' => 'Evento eliminado correctamente'
    ]);
    exit;
}

// Manejo de acción AJAX para subir mapa
if ($action === 'subir_mapa' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    // Verificar permisos
    if (!is_narra($uid) && !is_staff($uid)) {
        echo json_encode(['success' => false, 'message' => 'No tienes permisos para subir mapas']);
        exit;
    }
    
    $isla_id = (int)$mybb->get_input('isla_id', MyBB::INPUT_INT);
    
    if ($isla_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID de isla inválido']);
        exit;
    }
    
    // Verificar que se subió un archivo
    if (!isset($_FILES['mapa']) || $_FILES['mapa']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'message' => 'No se pudo subir el archivo']);
        exit;
    }
    
    $file = $_FILES['mapa'];
    $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
    
    // Validar tipo de archivo
    if (!in_array($file['type'], $allowed_types)) {
        echo json_encode(['success' => false, 'message' => 'Tipo de archivo no permitido. Solo imágenes JPG, PNG, GIF o WEBP']);
        exit;
    }
    
    // Validar tamaño (máximo 5MB)
    if ($file['size'] > 5 * 1024 * 1024) {
        echo json_encode(['success' => false, 'message' => 'El archivo es demasiado grande. Máximo 5MB']);
        exit;
    }
    
    // Crear directorio si no existe
    $upload_dir = __DIR__ . '/../images/op/islas/' . $isla_id . '/';
    if (!is_dir($upload_dir)) {
        if (!mkdir($upload_dir, 0755, true)) {
            echo json_encode(['success' => false, 'message' => 'No se pudo crear el directorio']);
            exit;
        }
    }
    
    // Generar nombre único para el archivo
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'mapa_' . time() . '_' . uniqid() . '.' . $extension;
    $filepath = $upload_dir . $filename;
    
    // Mover archivo
    if (!move_uploaded_file($file['tmp_name'], $filepath)) {
        echo json_encode(['success' => false, 'message' => 'Error al guardar el archivo']);
        exit;
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Mapa subido correctamente',
        'filename' => $filename,
        'url' => '/images/op/islas/' . $isla_id . '/' . $filename
    ]);
    exit;
}

// Manejo de acción AJAX para eliminar mapa
if ($action === 'eliminar_mapa' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    // Verificar permisos
    if (!is_narra($uid) && !is_staff($uid)) {
        echo json_encode(['success' => false, 'message' => 'No tienes permisos para eliminar mapas']);
        exit;
    }
    
    $isla_id = (int)$mybb->get_input('isla_id', MyBB::INPUT_INT);
    $filename = basename($mybb->get_input('filename'));
    
    if ($isla_id <= 0 || empty($filename)) {
        echo json_encode(['success' => false, 'message' => 'Parámetros inválidos']);
        exit;
    }
    
    // Verificar que el archivo existe
    $filepath = __DIR__ . '/../images/op/islas/' . $isla_id . '/' . $filename;
    
    if (!file_exists($filepath)) {
        echo json_encode(['success' => false, 'message' => 'El archivo no existe']);
        exit;
    }
    
    // Eliminar archivo
    if (!unlink($filepath)) {
        echo json_encode(['success' => false, 'message' => 'No se pudo eliminar el archivo']);
        exit;
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Mapa eliminado correctamente'
    ]);
    exit;
}

// Validar entrada temprano
$isla_id = (int)$mybb->get_input('isla_id', MyBB::INPUT_INT);
if ($isla_id <= 0) {
    error_no_permission();
}

$habitantes = '';
$zonas = $comercio = $tamano = $faccion = $gobierno = $descripcion = '';

// Verificar si existe el foro
$query_forum = $db->query("SELECT fid, description FROM mybb_forums WHERE fid = {$isla_id}");
$forum = $db->fetch_array($query_forum);

if (!$forum) {
    error_no_permission();
}

// Intentar obtener datos de la isla desde mybb_op_islas
$isla = null;
if ($db->table_exists('op_islas')) {
    $query_isla = $db->query("
        SELECT * FROM mybb_op_islas WHERE isla_id = {$isla_id}
    ");
    $isla = $db->fetch_array($query_isla);
}

// Si no existe en op_islas, crear registro vacío
if (!$isla) {
    if ($db->table_exists('op_islas')) {
        $db->query("
            INSERT INTO mybb_op_islas (isla_id, zonas, comercio, tamano, faccion, gobierno, habitantes) 
            VALUES ({$isla_id}, '', '', '', '', '', '')
        ");
    }
    // Crear array con valores por defecto
    $isla = [
        'isla_id' => $isla_id,
        'zonas' => '',
        'comercio' => '',
        'tamano' => '',
        'faccion' => '',
        'gobierno' => '',
        'habitantes' => '',
        'description' => $forum['description'] ?? ''
    ];
} else {
    // Agregar descripción del foro
    $isla['description'] = $forum['description'] ?? '';
}

// Preparación de campos (salida segura si van a HTML)
$zonas       = nl2br($isla['zonas'] ?? '');
$comercio    = nl2br($isla['comercio'] ?? '');
$tamano      = nl2br($isla['tamano'] ?? '');
$faccion     = nl2br($isla['faccion'] ?? '');
$gobierno    = nl2br($isla['gobierno'] ?? '');
$descripcion = nl2br($isla['description'] ?? '');
$habitantes  = trim($isla['habitantes'] ?? '');

// Habitantes destacados
$npcs = [];
if ($habitantes !== '') {
    error_log("[ISLA] Habitantes guardados: {$habitantes}");
    // Los npc_id son strings (ej: "NPC003"), no números
    $ids = array_filter(array_map('trim', explode(',', $habitantes)));
    error_log("[ISLA] IDs parseados: " . implode(',', $ids));
    if (!empty($ids)) {
        // Escapar cada ID y añadir comillas simples para la consulta SQL
        $escaped_ids = array_map(function($id) use ($db) {
            return "'" . $db->escape_string($id) . "'";
        }, $ids);
        $id_list = implode(',', $escaped_ids);
        error_log("[ISLA] SQL IN: {$id_list}");
        $query_npcs = $db->query("SELECT * FROM mybb_op_npcs WHERE npc_id IN ({$id_list})");
        while ($npc = $db->fetch_array($query_npcs)) {
            error_log("[ISLA] NPC encontrado: " . $npc['npc_id'] . " - " . $npc['nombre']);
            $npcs[] = $npc;
        }
        error_log("[ISLA] Total NPCs recuperados: " . count($npcs));
    }
} else {
    error_log("[ISLA] No hay habitantes guardados");
}

// Eventos
$eventos = [];
// Verificar si la tabla existe antes de consultar
if ($db->table_exists('op_isla_eventos')) {
    $query_eventos = $db->query("
        SELECT e.*, u.username
        FROM mybb_op_isla_eventos e
        INNER JOIN mybb_users u ON u.uid = e.staff_uid
        WHERE e.isla_id = {$isla_id}
        ORDER BY e.ano ASC,
                 FIELD(e.estacion,'Primavera','Verano','Otoño','Invierno') ASC,
                 e.dia ASC,
                 e.evento_id ASC
    ");
    while ($ev = $db->fetch_array($query_eventos)) {
        $ev['descripcion'] = nl2br(htmlspecialchars_uni($ev['descripcion']));
        $ev['descripcion_raw'] = htmlspecialchars_uni($ev['descripcion']); // Para edición
        $ev['estacion']    = htmlspecialchars_uni($ev['estacion']);
        $ev['username']    = htmlspecialchars_uni($ev['username']);
        $ev['staff_uid']   = (int)$ev['staff_uid'];
        $eventos[]         = $ev;
    }
}

// Verificar permisos de edición
$puede_editar = (is_narra($uid) || is_staff($uid)) ? 'true' : 'false';
$current_uid = (int)$uid; // UID del usuario actual para comparaciones en JavaScript

// Datos sin procesar para edición
// Usar json_encode para escapar correctamente los saltos de línea para JavaScript
$zonas_raw = json_encode($isla['zonas'] ?? '', JSON_HEX_APOS | JSON_HEX_QUOT);
$comercio_raw = json_encode($isla['comercio'] ?? '', JSON_HEX_APOS | JSON_HEX_QUOT);
$tamano_raw = json_encode($isla['tamano'] ?? '', JSON_HEX_APOS | JSON_HEX_QUOT);
$faccion_raw = json_encode($isla['faccion'] ?? '', JSON_HEX_APOS | JSON_HEX_QUOT);
$gobierno_raw = json_encode($isla['gobierno'] ?? '', JSON_HEX_APOS | JSON_HEX_QUOT);
$habitantes_raw = json_encode($isla['habitantes'] ?? '', JSON_HEX_APOS | JSON_HEX_QUOT);

// Obtener mapas existentes
$mapas = [];
$mapas_dir = __DIR__ . '/../images/op/islas/' . $isla_id . '/';
if (is_dir($mapas_dir)) {
    $files = scandir($mapas_dir);
    foreach ($files as $file) {
        if ($file !== '.' && $file !== '..' && preg_match('/\.(jpg|jpeg|png|gif|webp)$/i', $file)) {
            $mapas[] = [
                'filename' => $file,
                'url' => '/images/op/islas/' . $isla_id . '/' . $file
            ];
        }
    }
}

// Obtener nombre de la isla desde el foro
$nombre = '';
$query_forum_name = $db->query("SELECT name FROM mybb_forums WHERE fid = {$isla_id}");
if ($forum_data = $db->fetch_array($query_forum_name)) {
    $nombre = htmlspecialchars_uni($forum_data['name']);
}

// JSON seguro para el template
$npcs_json    = json_encode($npcs, JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT);
$eventos_json = json_encode($eventos, JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT);
$mapas_json = json_encode($mapas, JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT);

// Render
eval("\$page = \"".$templates->get("op_isla")."\";");
output_page($page);
