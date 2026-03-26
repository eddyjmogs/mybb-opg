<?php
/**
 * MyBB 1.8
 * Calendario de adviento - Backend/adviento.php
 */

// --- DEPURACIÓN TEMPORAL---
ini_set('display_errors', '1');               // mostrar (solo mientras depuras)
ini_set('display_startup_errors', '1');
ini_set('log_errors', '1');                   // deja esto en 1 siempre
ini_set('error_log', './php-error.log'); // cámbialo a una ruta fuera del webroot
error_reporting(E_ALL);
// -----------------------------------------------

define("IN_MYBB", 1);
define('THIS_SCRIPT', 'adviento.php');
require_once "./../global.php";
require "./../inc/config.php";
require_once "./functions/op_functions.php";

global $templates, $mybb, $db;

$uid = (int)$mybb->user['uid'];
$username = $mybb->user['username'] ?? '';
$action = $mybb->get_input('action');

// ---------- Funciones auxiliares ----------

/**
 * Obtiene el día actual del calendario de adviento según la zona horaria de España
 * @return int Día del mes (1-31) o 0 si estamos fuera del periodo de adviento
 */
function getDiaActualEspana() {
    // Establecer zona horaria de España (CET/CEST)
    $timezone = new DateTimeZone('Europe/Madrid');
    $now = new DateTime('now', $timezone);
    
    $mes = (int)$now->format('n');
    $anio = (int)$now->format('Y');
    
    // Solo permitir diciembre 2025
    if ($mes !== 12 || $anio !== 2025) {
        return 0;
    }
    
    return (int)$now->format('j');
}

function darObjeto($objeto_id) {
    global $db, $uid;
    $cantidadActual = '0';
    $has_objeto = false;
    $inventario_actual = $db->query("SELECT * FROM mybb_op_inventario WHERE uid='$uid' AND objeto_id='$objeto_id'");
    while ($q = $db->fetch_array($inventario_actual)) {  $has_objeto = true; $cantidadActual = $q['cantidad']; }

    if ($has_objeto) {
        $cantidadNueva = intval($cantidadActual) + 1;
        $db->query(" 
            UPDATE `mybb_op_inventario` SET `cantidad`='$cantidadNueva' WHERE objeto_id='$objeto_id' AND uid='$uid'
        ");
    } else {
        $db->query(" 
            INSERT INTO `mybb_op_inventario` (`objeto_id`, `uid`, `cantidad`) VALUES 
            ('$objeto_id', '$uid', '1');
        ");
    }
}

function darExperiencia($user_uid, $cantidad) {
    global $db;
    $user = null;
    $query_user = $db->query(" SELECT * FROM mybb_users WHERE uid='$user_uid' ");
    while ($q = $db->fetch_array($query_user)) { $user = $q; }
    
    if ($user) {
        $old = floatval($user['newpoints']);
        $new = $old + floatval($cantidad);
        $db->query(" UPDATE `mybb_users` SET newpoints='$new' WHERE `uid`='$user_uid';");
        return $new;
    }
    return null;
}

function darPuntosOficio($user_uid, $cantidad) {
    global $db;
    $ficha = null;
    $query_ficha = $db->query(" SELECT * FROM mybb_op_fichas WHERE fid='$user_uid' ");
    while ($q = $db->fetch_array($query_ficha)) { $ficha = $q; }
    
    if ($ficha) {
        $old = intval($ficha['puntos_oficio']);
        $new = $old + intval($cantidad);
        $db->query(" UPDATE `mybb_op_fichas` SET puntos_oficio='$new' WHERE fid='$user_uid' ");
        return $new;
    }
    return null;
}

function darNikas($user_uid, $cantidad) {
    global $db;
    $ficha = null;
    $query_ficha = $db->query(" SELECT * FROM mybb_op_fichas WHERE fid='$user_uid' ");
    while ($q = $db->fetch_array($query_ficha)) { $ficha = $q; }
    
    if ($ficha) {
        $old = intval($ficha['nika']);
        $new = $old + intval($cantidad);
        $db->query(" UPDATE `mybb_op_fichas` SET nika='$new' WHERE fid='$user_uid' ");
        return $new;
    }
    return null;
}

// ---------- Gestión de recompensas ----------
function getRecompensaDia($dia) {
    $recompensas = [
        1  => ['tipo' => 'objeto', 'id' => 'CFD002', 'nombre' => 'Cofre Dial T2'],
        2  => ['tipo' => 'berries', 'cantidad' => 1000000, 'nombre' => '1M de Berries'],
        3  => ['tipo' => 'puntos_oficio', 'cantidad' => 100, 'nombre' => '100 Puntos de oficio'],
        4  => ['tipo' => 'objeto', 'id' => 'CFR003', 'nombre' => 'Cofre Gigante'],
        5  => ['tipo' => 'objeto', 'id' => 'INV002', 'nombre' => 'Cofre Artefacto T2'],
        6  => ['tipo' => 'objeto', 'id' => 'CFR001', 'nombre' => 'Cofre Básico'],
        7  => ['tipo' => 'nikas', 'cantidad' => 10, 'nombre' => '10 Nikas'],
        8  => ['tipo' => 'objeto', 'id' => 'INV001', 'nombre' => 'Cofre Artefacto T1'],
        9  => ['tipo' => 'experiencia', 'cantidad' => 50, 'nombre' => '50 Exp'],
        10 => ['tipo' => 'objeto', 'id' => 'NTC002', 'nombre' => 'Nota de crafteo T2'],
        11 => ['tipo' => 'objeto', 'id' => 'RTO002', 'nombre' => 'Manual de crafteo T2'],
        12 => ['tipo' => 'berries', 'cantidad' => 1000000, 'nombre' => '1M de Berries'],
        13 => ['tipo' => 'puntos_oficio', 'cantidad' => 200, 'nombre' => '200 Puntos de oficio'],
        14 => ['tipo' => 'objeto', 'id' => 'CFR004', 'nombre' => 'Cofre Cobrizo'],
        15 => ['tipo' => 'objeto', 'id' => 'CFR002', 'nombre' => 'Cofre Decente'],
        16 => ['tipo' => 'berries', 'cantidad' => 2000000, 'nombre' => '2M de Berries'],
        17 => ['tipo' => 'objeto', 'id' => 'NTC001', 'nombre' => 'Nota de crafteo T1'],
        18 => ['tipo' => 'objeto', 'id' => 'CFR001', 'nombre' => 'Cofre Básico'],
        19 => ['tipo' => 'objeto', 'id' => 'INV002', 'nombre' => 'Cofre artefacto T2'],
        20 => ['tipo' => 'objeto', 'id' => 'CFR005', 'nombre' => 'Cofre Argénteo'],
        21 => ['tipo' => 'experiencia', 'cantidad' => 100, 'nombre' => '100 Exp'],
        22 => ['tipo' => 'puntos_oficio', 'cantidad' => 200, 'nombre' => '200 Puntos de oficio'],
        23 => ['tipo' => 'objeto', 'id' => 'NTC002', 'nombre' => 'Nota de crafteo T2'],
        24 => ['tipo' => 'experiencia', 'cantidad' => 50, 'nombre' => '50 Exp'],
        25 => ['tipo' => 'objeto', 'id' => 'CFR006', 'nombre' => 'Cofre Aureo'],
        26 => ['tipo' => 'nikas', 'cantidad' => 10, 'nombre' => '10 Nikas'],
        27 => ['tipo' => 'objeto', 'id' => 'CFD002', 'nombre' => 'Cofre Dial T2'],
        28 => ['tipo' => 'objeto', 'id' => 'RTO002', 'nombre' => 'Manual de crafteo T2'],
        29 => ['tipo' => 'objeto', 'id' => 'NTC001', 'nombre' => 'Nota de crafteo T1'],
        30 => ['tipo' => 'puntos_oficio', 'cantidad' => 100, 'nombre' => '100 Puntos de oficio'],
        31 => ['tipo' => 'objeto', 'id' => 'THR001', 'nombre' => 'Tirada de Haki del Rey']
    ];
    
    return isset($recompensas[$dia]) ? $recompensas[$dia] : null;
}

function entregarRecompensa($user_uid, $dia) {
    global $db, $username;
    
    $recompensa = getRecompensaDia($dia);
    if (!$recompensa) {
        return ['success' => false, 'message' => 'Día inválido'];
    }
    
    $resultado = ['success' => true, 'message' => '', 'data' => $recompensa];
    
    switch ($recompensa['tipo']) {
        case 'objeto':
            darObjeto($recompensa['id']);
            $resultado['message'] = "Has recibido: {$recompensa['nombre']}";
            log_audit($user_uid, $username, '[Adviento][Día '.$dia.']', "Objeto entregado: {$recompensa['nombre']} ({$recompensa['id']})");
            break;
            
        case 'berries':
            $ficha = null;
            $query_ficha = $db->query("SELECT * FROM mybb_op_fichas WHERE fid='$user_uid'");
            while ($q = $db->fetch_array($query_ficha)) { $ficha = $q; }
            
            if ($ficha) {
                $old_berries = intval($ficha['berries']);
                $new_berries = $old_berries + intval($recompensa['cantidad']);
                $db->query("UPDATE `mybb_op_fichas` SET `berries`='$new_berries' WHERE fid='$user_uid'");
                $resultado['message'] = "Has recibido: {$recompensa['nombre']}";
                log_audit($user_uid, $username, '[Adviento][Día '.$dia.']', "Berries: $old_berries -> $new_berries (+{$recompensa['cantidad']})");
            }
            break;
            
        case 'experiencia':
            $new_exp = darExperiencia($user_uid, $recompensa['cantidad']);
            if ($new_exp !== null) {
                $resultado['message'] = "Has recibido: {$recompensa['nombre']}";
                log_audit($user_uid, $username, '[Adviento][Día '.$dia.']', "Experiencia entregada: +{$recompensa['cantidad']}");
            }
            break;
            
        case 'puntos_oficio':
            $new_puntos = darPuntosOficio($user_uid, $recompensa['cantidad']);
            if ($new_puntos !== null) {
                $resultado['message'] = "Has recibido: {$recompensa['nombre']}";
                log_audit($user_uid, $username, '[Adviento][Día '.$dia.']', "Puntos de oficio: +{$recompensa['cantidad']}");
            }
            break;
         
        case 'nikas':
            $new_nikas = darNikas($user_uid, $recompensa['cantidad']);
            if ($new_nikas !== null) {
                $resultado['message'] = "Has recibido: {$recompensa['nombre']}";
                log_audit($user_uid, $username, '[Adviento][Día '.$dia.']', "Nikas: +{$recompensa['cantidad']}");
            }
            break;
            
        case 'haki':
            // Registrar que obtuvo la tirada de haki
            $resultado['message'] = "Has recibido: {$recompensa['nombre']}. Contacta con un moderador para realizar tu tirada.";
            log_audit($user_uid, $username, '[Adviento][Día '.$dia.']', "Tirada de Haki del Rey otorgada");
            break;
    }
    
    return $resultado;
}

function marcarDiaAbierto($user_uid, $dia) {
    global $db;
    
    // Verificar si ya se abrió
    $query = $db->query("SELECT * FROM mybb_op_adviento_abiertos WHERE uid='$user_uid' AND dia='$dia' AND anio='2025'");
    $existe = false;
    while ($q = $db->fetch_array($query)) { $existe = true; }
    
    if (!$existe) {
        $db->query("INSERT INTO mybb_op_adviento_abiertos (uid, dia, anio, fecha_apertura) VALUES ('$user_uid', '$dia', '2025', ".time().")");
        return true;
    }
    return false;
}

function verificarDiaAbierto($user_uid, $dia) {
    global $db;
    
    $query = $db->query("SELECT * FROM mybb_op_adviento_abiertos WHERE uid='$user_uid' AND dia='$dia' AND anio='2025'");
    $existe = false;
    while ($q = $db->fetch_array($query)) { $existe = true; }
    
    return $existe;
}

// ---------- Utilidades de seguridad/AJAX ----------
function is_ajax_request(): bool {
    return (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');
}

// ---------- Manejo de acciones AJAX ----------
if ($action === 'abrir_dia' && is_ajax_request() && $_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    $dia = isset($_POST['dia']) ? intval($_POST['dia']) : 0;
    
    if ($dia < 1 || $dia > 31) {
        echo json_encode(['success' => false, 'message' => 'Día inválido']);
        exit;
    }
    
    // Verificar que el día coincida exactamente con el día actual de España
    $diaActual = getDiaActualEspana();
    if ($diaActual === 0) {
        echo json_encode(['success' => false, 'message' => 'El calendario de adviento no está disponible en este momento']);
        exit;
    }
    
    if ($dia !== $diaActual) {
        echo json_encode(['success' => false, 'message' => "Solo puedes abrir el día $diaActual hoy. Este día no está disponible"]);
        exit;
    }
    
    // Verificar si ya abrió este día
    if (verificarDiaAbierto($uid, $dia)) {
        echo json_encode(['success' => false, 'message' => 'Ya has abierto este día anteriormente']);
        exit;
    }
    
    // Marcar día como abierto
    marcarDiaAbierto($uid, $dia);
    
    // Entregar recompensa
    $resultado = entregarRecompensa($uid, $dia);
    
    echo json_encode($resultado);
    exit;
}

if ($action === 'verificar_dias' && is_ajax_request()) {
    header('Content-Type: application/json');
    
    $dias_abiertos = [];
    for ($i = 1; $i <= 31; $i++) {
        if (verificarDiaAbierto($uid, $i)) {
            $dias_abiertos[] = $i;
        }
    }
    
    $diaActual = getDiaActualEspana();
    
    echo json_encode([
        'success' => true, 
        'dias_abiertos' => $dias_abiertos,
        'dia_actual' => $diaActual
    ]);
    exit;
}

eval("\$page = \"".$templates->get('op_adviento')."\";");
output_page($page);