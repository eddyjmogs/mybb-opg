<?php
/**
 * MyBB 1.8
 * Copyright 2014 MyBB Group, All Rights Reserved
 *
 * Website: http://www.mybb.com
 * License: http://www.mybb.com/about/license
 *
 */

// --- DEPURACIÓN TEMPORAL---
ini_set('display_errors', '1');               // mostrar (solo mientras depuras)
ini_set('display_startup_errors', '1');
ini_set('log_errors', '1');                   // deja esto en 1 siempre
ini_set('error_log', './php-error.log'); // cámbialo a una ruta fuera del webroot
error_reporting(E_ALL);
// -----------------------------------------------

define("IN_MYBB", 1);
define('THIS_SCRIPT', 'viajes.php');
require_once "./../global.php";
require "./../inc/config.php";
require_once "./functions/op_functions.php";
global $templates, $mybb;

$uid = $mybb->user['uid'];
$username = $mybb->user['username'];
$ficha = null;
$ficha_existe = false;
$ficha_aprobada = false;

// if ($uid == '17') {
//     $uid = '225';
// }

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


$modo_vista_input = $mybb->get_input('modo_vista'); 
$modo_vista = ($modo_vista_input && ($g_is_staff) || $mybb->user['uid'] == $modo_vista_input);
if ($modo_vista) {
    $uid = $modo_vista_input;
}
// Si estamos en modo_vista, obtener también el nombre de usuario correspondiente
if ($modo_vista) {
    $query_user = $db->query("SELECT username FROM mybb_users WHERE uid='".intval($uid)."' LIMIT 1");
    if ($uq = $db->fetch_array($query_user)) { $username = $uq['username']; }
}

// Valor por defecto para plantillas JS
$reclamar_npc_id_js = '';

$query_ficha = $db->query(" SELECT * FROM mybb_op_fichas WHERE fid='$uid' "); 
while ($f = $db->fetch_array($query_ficha)) { $ficha = $f; $ficha_aprobada = $f['aprobada_por'] != 'sin_aprobar'; $ficha_existe = true; }

if ($ficha == null || $ficha_aprobada == false) {
    $mensaje_redireccion = "Para acceder a esta página debes tener tu ficha aprobada.";
    eval("\$page = \"".$templates->get("op_redireccion")."\";");
    output_page($page);
    return;
}

$has_sin_oficio = false; // D024
$has_estudioso = false; // V035
$has_polivalente = false; // V036
$has_erudito = false; // V028
                        
$has_sin_oficio_query =  $db->query(" SELECT * FROM `mybb_op_virtudes_usuarios` WHERE uid='$uid' AND virtud_id='D024'; "); 
$has_estudioso_query =   $db->query(" SELECT * FROM `mybb_op_virtudes_usuarios` WHERE uid='$uid' AND virtud_id='V035'; "); 
$has_polivalente_query = $db->query(" SELECT * FROM `mybb_op_virtudes_usuarios` WHERE uid='$uid' AND virtud_id='V036'; "); 
$has_erudito_query =     $db->query(" SELECT * FROM `mybb_op_virtudes_usuarios` WHERE uid='$uid' AND virtud_id='V028'; "); 

// Asegurar existencia de tabla para crafteos de NPCs (creación segura si no existe)
$db->query("CREATE TABLE IF NOT EXISTS `mybb_op_crafteo_npcs` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `uid` INT NOT NULL,
    `npc_id` VARCHAR(64) NOT NULL,
    `objeto_id` VARCHAR(64) NOT NULL,
    `nombre` VARCHAR(255) NOT NULL,
    `material_id` VARCHAR(64) DEFAULT NULL,
    `timestamp_end` INT NOT NULL,
    `duracion` INT NOT NULL,
    `costo` INT NOT NULL,
    PRIMARY KEY (`id`),
    INDEX (`uid`),
    INDEX (`npc_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

$log = 'hello';

if ($has_sin_oficio) {
    $mensaje_redireccion = "Aquellos que poseen el defecto 'Sin Oficio' no tienen la capacidad de ganar puntos de oficio. Haber estudiao.";
    eval("\$page = \"".$templates->get("op_redireccion")."\";");
    output_page($page);
    return;
}

while ($q = $db->fetch_array($has_sin_oficio_query)) { $has_sin_oficio = true; }
while ($q = $db->fetch_array($has_estudioso_query)) { $has_estudioso = true; }
while ($q = $db->fetch_array($has_polivalente_query)) { $has_polivalente = true; }
while ($q = $db->fetch_array($has_erudito_query)) { $has_erudito = true; }

function darObjeto2($objeto_id, $uid, $username, $oficios) {
    global $db;

    // conteo para generar sufijo único
    $count_id = 0;
    $inventario_actual = $db->query("SELECT count(*) as count FROM mybb_op_objetos WHERE objeto_id LIKE '".$db->escape_string($objeto_id)."-".$db->escape_string($uid)."%'");
    while ($q = $db->fetch_array($inventario_actual)) { $count_id = intval($q['count']) + 1; }

    // obtener objeto base
    $obj_custom = null;
    $objeto_custom_query = $db->query("SELECT * FROM mybb_op_objetos WHERE objeto_id='".$db->escape_string($objeto_id)."'");
    while ($q = $db->fetch_array($objeto_custom_query)) {  $obj_custom = $q; }

    if (!$obj_custom) { // nada que copiar
        return;
    }

    $new_objeto_id = $db->escape_string($objeto_id) . '-' . $db->escape_string($uid) . '-' . intval($count_id);

    // Sanitizar/normalizar campos
    $categoria      = $db->escape_string($obj_custom['categoria']);
    $subcategoria   = $db->escape_string($obj_custom['subcategoria']);
    $nombre         = $db->escape_string($obj_custom['nombre']);
    $tier           = intval($obj_custom['tier']);
    $imagen_id      = $db->escape_string($obj_custom['imagen_id']);
    $imagen_avatar  = $db->escape_string($obj_custom['imagen_avatar']);
    $berries        = intval($obj_custom['berries']);
    $cantidadMaxima = intval($obj_custom['cantidadMaxima']);
    $dano           = $db->escape_string($obj_custom['dano']);
    $bloqueo        = $db->escape_string($obj_custom['bloqueo']);
    $alcance        = $db->escape_string($obj_custom['alcance']);
    $efecto         = $db->escape_string($obj_custom['efecto']);
    $exclusivo      = 1;
    $invisible      = 1;
    $espacios       = intval($obj_custom['espacios']);
    $imagen         = $db->escape_string($obj_custom['imagen']);
    $desbloquear    = intval($obj_custom['desbloquear']);
    $oficio         = $db->escape_string($obj_custom['oficio']);
    $nivel          = $db->escape_string($obj_custom['nivel']);
    $requisitos     = $db->escape_string($obj_custom['requisitos']);
    $escalado       = $db->escape_string($obj_custom['escalado']);
    $editable       = 1;
    $custom         = 1;
    $descripcion    = $db->escape_string($obj_custom['descripcion']);
    $negro          = 1;

    // insertar en inventario
    $db->query("INSERT INTO `mybb_op_inventario` (`objeto_id`, `uid`, `cantidad`, `autor`, `autor_uid`, `oficios`, `especial`) VALUES ('".$db->escape_string($new_objeto_id)."', '".intval($uid)."', '1', '".$db->escape_string($username)."', '".intval($uid)."', '".$db->escape_string($oficios)."', '1');");

    // insertar objeto 'custom' - escapamos todos los campos de texto
    $insert_sql = "INSERT INTO `mybb_op_objetos`(`objeto_id`, `categoria`, `subcategoria`, `nombre`, `tier`, `imagen_id`, `imagen_avatar`, `berries`, `cantidadMaxima`, `dano`, `efecto`, `bloqueo`, `alcance`, `exclusivo`, `invisible`, `espacios`, `imagen`, `desbloquear`, `oficio`, `nivel`, `requisitos`, `escalado`, `editable`, `custom`, `descripcion`, `negro`) VALUES ('".
        $db->escape_string($new_objeto_id)."', '".$categoria."', '".$subcategoria."', '".$nombre."', '".$tier."', '".$imagen_id."', '".$imagen_avatar."', '".$berries."', '".$cantidadMaxima."', '".$dano."', '".$efecto."', '".$bloqueo."', '".$alcance."', '".$exclusivo."', '".$invisible."', '".$espacios."', '".$imagen."', '".$desbloquear."', '".$oficio."', '".$nivel."', '".$requisitos."', '".$escalado."', '".$editable."', '".$custom."', '".$descripcion."', '".$negro."');";

    $db->query($insert_sql);

    // Validación post-inserción: si bloqueo/alcance quedan vacíos, registrar en audit para revisar
    $check = $db->query("SELECT bloqueo, alcance FROM mybb_op_objetos WHERE objeto_id='".$db->escape_string($new_objeto_id)."'");
    $row = $db->fetch_array($check);
    if (!$row || $row['bloqueo'] === '' || $row['alcance'] === '') {
        $log = "Copy incomplete for $objeto_id -> $new_objeto_id bloqueo:" . ($row['bloqueo'] ?? 'NULL') . " alcance:" . ($row['alcance'] ?? 'NULL');
        $db->query("INSERT INTO `mybb_op_audit_crafteo` (`uid`, `nombre`, `log`) VALUES ('".intval($uid)."', '".$db->escape_string($username)."', '".$db->escape_string($log)."');");
    }
    
}

function darObjeto($objeto_id) {
    global $db, $uid, $username, $ficha, $oficios;
    $cantidadActual = '0';
    $has_objeto = false;
    $inventario_actual = $db->query("SELECT * FROM mybb_op_inventario WHERE uid='$uid' AND objeto_id='$objeto_id'");
    while ($q = $db->fetch_array($inventario_actual)) {  $has_objeto = true; $cantidadActual = $q['cantidad']; }

    // Asegurar que tenemos la estructura de oficios del usuario disponible
    if (!isset($oficios) || empty($oficios)) {
        $oficios = json_decode($ficha['oficios']);
    }

    // Obtener información del objeto para deducir oficio y si es custom/especial
    $obj_row = null;
    $obj_q = $db->query("SELECT oficio, custom FROM mybb_op_objetos WHERE objeto_id='".$db->escape_string($objeto_id)."' LIMIT 1");
    if ($obj_q) { $obj_row = $db->fetch_array($obj_q); }
    $oficio_name = $obj_row ? $obj_row['oficio'] : '';
    $especial = ($obj_row && intval($obj_row['custom']) == 1) ? 1 : 0;

    // Construir JSON con la estructura esperada: {"Oficio": {"sub": {...}, "nivel": X}}
    $oficios_obj = array();
    if ($oficio_name != '') {
        $nivel = isset($oficios->{$oficio_name}->{'nivel'}) ? intval($oficios->{$oficio_name}->{'nivel'}) : 0;
        $sub = isset($oficios->{$oficio_name}->{'sub'}) ? (array)$oficios->{$oficio_name}->{'sub'} : array();
        $oficios_obj[$oficio_name] = array("sub" => $sub, "nivel" => $nivel);
    }
    $oficios_json = $db->escape_string(json_encode($oficios_obj, JSON_UNESCAPED_UNICODE));

    if ($has_objeto) {
        $cantidadNueva = intval($cantidadActual) + 1;
        $db->query(" 
            UPDATE `mybb_op_inventario` SET `cantidad`='$cantidadNueva' WHERE objeto_id='$objeto_id' AND uid='$uid'
        ");

        // Si faltan metadatos, rellenarlos (solo si están vacíos)
        $db->query("UPDATE `mybb_op_inventario` SET `autor`='".$db->escape_string($username)."', `autor_uid`='".intval($uid)."', `oficios`='$oficios_json', `especial`='".intval($especial)."' WHERE objeto_id='".$db->escape_string($objeto_id)."' AND uid='".intval($uid)."' AND (autor='' OR autor IS NULL OR oficios='' OR oficios IS NULL)");
    } else {
        $db->query(" 
            INSERT INTO `mybb_op_inventario` (`objeto_id`, `uid`, `cantidad`, `autor`, `autor_uid`, `oficios`, `especial`) VALUES 
            ('".$db->escape_string($objeto_id)."', '".intval($uid)."', '1', '".$db->escape_string($username)."', '".intval($uid)."', '$oficios_json', '".intval($especial)."');
        ");
    }
}

function quitarObjeto($objeto_id) {
    global $db, $uid;
    $cantidadActual = '0';
    $has_objeto = false;
    $inventario_actual = $db->query("SELECT * FROM mybb_op_inventario WHERE uid='$uid' AND objeto_id='$objeto_id'");
    while ($q = $db->fetch_array($inventario_actual)) { $has_objeto = true; $cantidadActual = $q['cantidad']; }

    if ($has_objeto && intval($cantidadActual) >= 2) {
        $cantidadNueva = intval($cantidadActual) - 1;
        $db->query(" 
            UPDATE `mybb_op_inventario` SET `cantidad`='$cantidadNueva' WHERE objeto_id='$objeto_id' AND uid='$uid'
        ");
    } else {
        $db->query(" 
            DELETE FROM `mybb_op_inventario` WHERE objeto_id='$objeto_id' AND uid='$uid'
        ");
    }
}

$accion = $_POST['accion'];
$objetoIdPost = $_POST['objetoId'];
$recetaIdPost = $_POST['recetaId'];

$oficio1 = $ficha['oficio1'];

if ($oficio1 == '') {
    $mensaje_redireccion = "Para acceder a esta página debes tener un oficio.";
    eval("\$page = \"".$templates->get("op_redireccion")."\";");
    output_page($page);
    return;
}

$nikas = intval($ficha['nika']);
$berries = intval($ficha['berries']);
$puntosOficio = intval($ficha['puntos_oficio']);
$nivel = intval($ficha['nivel']);
$oficio2 = $ficha['oficio2'];

$oficio1_db = '';
$oficio2_db = '';
$oficio1_espe1_db = '';
$oficio1_espe2_db = '';
$oficio2_espe1_db = '';
$oficio2_espe2_db = '';
$oficios = json_decode($ficha['oficios']);


function getDescuento($nivel) {
    if ($nivel == 1) {
        return 0.95;
    } else if ($nivel == 2) {
        return 0.90;
    } else if ($nivel == 3) {
        return 0.80;
    } else {
        return 1.0;
    }
}

function getTiempoCreacion($oficio) {
    global $db, $uid, $ficha;

    $nivelMax = 0;

    $oficios = json_decode($ficha['oficios']);

    if ($oficio == 'Cocinero' || $oficio == 'Chef' || $oficio == 'Aprovisionador') {

        if (isset($oficios->{'Cocinero'}->{'sub'}->{'Chef'})) {
            if ($oficios->{'Cocinero'}->{'sub'}->{'Chef'} > $nivelMax) {
                $nivelMax = $oficios->{'Cocinero'}->{'sub'}->{'Chef'};
            }  
        }

    }

    if ($oficio == 'Artesano' || $oficio == 'Herrero' || $oficio == 'Modista') {


        if (isset($oficios->{'Artesano'}->{'sub'}->{'Herrero'})) {
            if ($oficios->{'Artesano'}->{'sub'}->{'Herrero'} > $nivelMax) {
                $nivelMax = $oficios->{'Artesano'}->{'sub'}->{'Herrero'};
            }  
        }

    }

    if ($oficio == 'Médico' || $oficio == 'Doctor' || $oficio == 'Farmacólogo') {

        if (isset($oficios->{'Médico'}->{'sub'}->{'Farmacólogo'})) {
            if ($oficios->{'Médico'}->{'sub'}->{'Farmacólogo'} > $nivelMax) {
                $nivelMax = $oficios->{'Médico'}->{'sub'}->{'Farmacólogo'};
            }  
        }

    }

    if ($oficio == 'Carpintero' || $oficio == 'Astillero' || $oficio == 'Constructor') {

        if (isset($oficios->{'Carpintero'}->{'sub'}->{'Constructor'})) {
            if ($oficios->{'Carpintero'}->{'sub'}->{'Constructor'} > $nivelMax) {
                $nivelMax = $oficios->{'Carpintero'}->{'sub'}->{'Constructor'};
            }  
        }

    }

    if ($oficio == 'Inventor' || $oficio == 'Ingeniero' || $oficio == 'Biólogo') {

        if (isset($oficios->{'Inventor'}->{'sub'}->{'Ingeniero'})) {
            if ($oficios->{'Inventor'}->{'sub'}->{'Ingeniero'} > $nivelMax) {
                $nivelMax = $oficios->{'Inventor'}->{'sub'}->{'Ingeniero'};
            }  
        }

    }

    return getDescuento($nivelMax);

}

function getBerriesCosto($oficio) {
    global $db, $uid, $ficha;

    $nivelMax = 0;

    $oficios = json_decode($ficha['oficios']);

    if ($oficio == 'Cocinero' || $oficio == 'Chef' || $oficio == 'Aprovisionador') {

        if (isset($oficios->{'Cocinero'}->{'sub'}->{'Aprovisionador'})) {
            if ($oficios->{'Cocinero'}->{'sub'}->{'Aprovisionador'} > $nivelMax) {
                $nivelMax = $oficios->{'Cocinero'}->{'sub'}->{'Aprovisionador'};
            }  
        }

    }

    if ($oficio == 'Artesano' || $oficio == 'Herrero' || $oficio == 'Modista') {

        if (isset($oficios->{'Artesano'}->{'sub'}->{'Modista'})) {
            if ($oficios->{'Artesano'}->{'sub'}->{'Modista'} > $nivelMax) {
                $nivelMax = $oficios->{'Artesano'}->{'sub'}->{'Modista'};
            }  
        }

    }

    if ($oficio == 'Médico' || $oficio == 'Doctor' || $oficio == 'Farmacólogo') {

        if (isset($oficios->{'Médico'}->{'sub'}->{'Doctor'})) {
            if ($oficios->{'Médico'}->{'sub'}->{'Doctor'} > $nivelMax) {
                $nivelMax = $oficios->{'Médico'}->{'sub'}->{'Doctor'};
            }  
        }

    }

    if ($oficio == 'Carpintero' || $oficio == 'Astillero' || $oficio == 'Constructor') {

        if (isset($oficios->{'Carpintero'}->{'sub'}->{'Astillero'})) {
            if ($oficios->{'Carpintero'}->{'sub'}->{'Astillero'} > $nivelMax) {
                $nivelMax = $oficios->{'Carpintero'}->{'sub'}->{'Astillero'};
            }  
        }

    }

    if ($oficio == 'Inventor' || $oficio == 'Ingeniero' || $oficio == 'Biólogo') {

        if (isset($oficios->{'Inventor'}->{'sub'}->{'Biólogo'})) {
            if ($oficios->{'Inventor'}->{'sub'}->{'Biólogo'} > $nivelMax) {
                $nivelMax = $oficios->{'Inventor'}->{'sub'}->{'Biólogo'};
            }  
        }

    }

    return getDescuento($nivelMax);

}

function getTiempoCreacionVirtud($has_estudioso, $has_erudito) {
  
    if ($has_estudioso) {
        return 0.8;
    } else if ($has_erudito) {
        return 0.9; 
    } else {
        return 1.0;
    }

}

function getRecolectorDescuento() {
    global $ficha;
    
    $oficios = json_decode($ficha['oficios']);
    
    $descuentoRecolector = 1.0;
    $descuentoMayorista = getMayoristaDescuentoBerries();
    
    // Recolector nivel 1+ ofrece 10% de descuento
    if (isset($oficios->{'Recolector'})) {
        $nivelRecolector = $oficios->{'Recolector'}->{'nivel'};
        
        if ($nivelRecolector >= 1) {
            $descuentoRecolector = 0.9; // 10% de descuento
        }
    }
    
    // Usar el mejor descuento disponible (el número más pequeño es mejor descuento)
    return min($descuentoRecolector, $descuentoMayorista);
}

function getRecolectorNivel() {
    global $ficha;
    
    $oficios = json_decode($ficha['oficios']);
    
    if (isset($oficios->{'Recolector'})) {
        return $oficios->{'Recolector'}->{'nivel'};
    }
    
    return 0;
}

function getAgresteNivel() {
    global $ficha;
    
    $oficios = json_decode($ficha['oficios']);
    
    // Buscar Agreste como especialización en Recolector
    if (isset($oficios->{'Recolector'}->{'sub'}->{'Agreste'})) {
        return $oficios->{'Recolector'}->{'sub'}->{'Agreste'};
    }
    
    return 0;
}

function getMayoristaNivel() {
    global $ficha;
    
    $oficios = json_decode($ficha['oficios']);
    
    // Buscar Mayorista como especialización en Recolector
    if (isset($oficios->{'Recolector'}->{'sub'}->{'Mayorista'})) {
        return $oficios->{'Recolector'}->{'sub'}->{'Mayorista'};
    }
    
    return 0;
}

function getMayoristaDescuentoBerries() {
    $nivel = getMayoristaNivel();
    
    if ($nivel == 3) {
        return 0.80; // 20% de descuento
    } else if ($nivel == 2) {
        return 0.85; // 15% de descuento
    }
    
    return 1.0; // Sin descuento en nivel 1 o sin Mayorista
}

function getMayoristaReduccionTiempo() {
    $nivel = getMayoristaNivel();
    
    if ($nivel == 3) {
        return 0.80; // 20% menos de tiempo
    } else if ($nivel == 2) {
        return 0.85; // 15% menos de tiempo
    } else if ($nivel == 1) {
        return 0.90; // 10% menos de tiempo
    }
    
    return 1.0; // Sin reducción
}

function getReduccionTiempoTotal() {
    // Mayorista sustituye cualquier otro beneficio si ofrece mejor reducción
    $reduccionMayorista = getMayoristaReduccionTiempo();
    
    // Si Mayorista no está activo (1.0), no hay reducción de tiempo desde oficios
    return $reduccionMayorista;
}

$oficio1_nivel = $oficios->{$oficio1}->{'nivel'};
// Filtro global: permitir si el UID está en la lista CSV (ignorando espacios)
$cond_uid = "FIND_IN_SET('".$db->escape_string($uid)."', REPLACE(crafteo_usuarios, ' ', '')) > 0";

// Helper para el check de crafteo vacío o NULL
$cond_libre = "(crafteo_usuarios='' OR crafteo_usuarios IS NULL)";

if (isset($oficios->{$oficio1}->{'espe1'})) {
    $oficio1_espe1 = $oficios->{$oficio1}->{'espe1'};
    $oficio1_espe1_nivel = $oficios->{$oficio1}->{'sub'}->{$oficio1_espe1};
    $oficio1_espe1_db = " || (oficio='".$db->escape_string($oficio1_espe1)."' AND nivel <= ".(int)$oficio1_espe1_nivel." AND $cond_libre) ";
}

if (isset($oficios->{$oficio1}->{'espe2'})) {
    $oficio1_espe2 = $oficios->{$oficio1}->{'espe2'};
    $oficio1_espe2_nivel = $oficios->{$oficio1}->{'sub'}->{$oficio1_espe2};
    $oficio1_espe2_db = " || (oficio='".$db->escape_string($oficio1_espe2)."' AND nivel <= ".(int)$oficio1_espe2_nivel." AND $cond_libre) ";
}

if ($oficio2 != '') {
    $oficio2_nivel = $oficios->{$oficio2}->{'nivel'};
    $oficio2_db = " || (oficio='".$db->escape_string($oficio2)."' AND nivel <= ".(int)$oficio2_nivel." AND $cond_libre) ";

    if (isset($oficios->{$oficio2})) {

        if (isset($oficios->{$oficio2}->{'espe1'})) {
            $oficio2_espe1 = $oficios->{$oficio2}->{'espe1'};
            $oficio2_espe1_nivel = $oficios->{$oficio2}->{'sub'}->{$oficio2_espe1};
            $oficio2_espe1_db = " || (oficio='".$db->escape_string($oficio2_espe1)."' AND nivel <= ".(int)$oficio2_espe1_nivel." AND $cond_libre) ";
        }

        if (isset($oficios->{$oficio2}->{'espe2'})) {
            $oficio2_espe2 = $oficios->{$oficio2}->{'espe2'};
            $oficio2_espe2_nivel = $oficios->{$oficio2}->{'sub'}->{$oficio2_espe2};
            $oficio2_espe2_db = " || (oficio='".$db->escape_string($oficio2_espe2)."' AND nivel <= ".(int)$oficio2_espe2_nivel." AND $cond_libre) ";
        }

    }
}

// Agregar Recolector y sus especializaciones si existen (Mayorista, Agreste, etc.)
$recolector_db = '';
$recolector_espe1_db = '';
$recolector_espe2_db = '';

if (isset($oficios->{'Recolector'})) {
    $recolector_nivel = $oficios->{'Recolector'}->{'nivel'};
    $recolector_db = " || (oficio='Recolector' AND nivel <= ".(int)$recolector_nivel." AND $cond_libre) ";
    
    if (isset($oficios->{'Recolector'}->{'espe1'})) {
        $recolector_espe1 = $oficios->{'Recolector'}->{'espe1'};
        $recolector_espe1_nivel = $oficios->{'Recolector'}->{'sub'}->{$recolector_espe1};
        $recolector_espe1_db = " || (oficio='".$db->escape_string($recolector_espe1)."' AND nivel <= ".(int)$recolector_espe1_nivel." AND $cond_libre) ";
    }
    
    if (isset($oficios->{'Recolector'}->{'espe2'})) {
        $recolector_espe2 = $oficios->{'Recolector'}->{'espe2'};
        $recolector_espe2_nivel = $oficios->{'Recolector'}->{'sub'}->{$recolector_espe2};
        $recolector_espe2_db = " || (oficio='".$db->escape_string($recolector_espe2)."' AND nivel <= ".(int)$recolector_espe2_nivel." AND $cond_libre) ";
    }
}

if ($accion == 'cancelar') {
    $hasCrafteo = false;
    $query_crafteo_usuario = $db->query(" SELECT * FROM mybb_op_crafteo_usuarios WHERE uid='$uid' ");
    while ($q = $db->fetch_array($query_crafteo_usuario)) { $crafteo = $q; $hasCrafteo = true; }
    
    if ($hasCrafteo) {

        $berriesNuevo = $berries + intval($crafteo['costo']);

        $db->query(" UPDATE `mybb_op_fichas` SET `berries`='$berriesNuevo' WHERE `fid`='$uid'; ");
        log_audit_currency($uid, $username, $uid, '[Crafteo][Berries]', 'berries', $berriesNuevo);
        if ($crafteo['material_id']) {
            darObjeto($crafteo['material_id']);
        }

        $db->query(" DELETE FROM mybb_op_crafteo_usuarios WHERE uid='$uid' ");

    }
}

// Cancelar crafteo de NPC (propietario)
if ($accion == 'cancelarNpc') {
    $npcIdPost = $db->escape_string($_POST['npcId']);
    $hasCrafteoNpc = false;
    $query_crafteo_npc = $db->query(" SELECT * FROM mybb_op_crafteo_npcs WHERE uid='$uid' AND npc_id='$npcIdPost' ");
    while ($q = $db->fetch_array($query_crafteo_npc)) { $crafteoNpc = $q; $hasCrafteoNpc = true; }

    if ($hasCrafteoNpc) {
        $berriesNuevo = $berries + intval($crafteoNpc['costo']);
        $db->query(" UPDATE `mybb_op_fichas` SET `berries`='$berriesNuevo' WHERE `fid`='$uid'; ");
        log_audit_currency($uid, $username, $uid, '[Crafteo NPC][Berries]', 'berries', $berriesNuevo);
        if ($crafteoNpc['material_id']) {
            darObjeto($crafteoNpc['material_id']);
        }
        $db->query(" DELETE FROM mybb_op_crafteo_npcs WHERE uid='$uid' AND npc_id='$npcIdPost' ");
    }
}

if ($accion == 'craftear') {
    $actor = isset($_POST['actor']) ? $db->escape_string($_POST['actor']) : 'player';
    $npcIdPost = isset($_POST['npcId']) ? $db->escape_string($_POST['npcId']) : '';

    // Si actor es 'player' mantenemos la lógica actual pero permitimos crafteos simultáneos con NPCs
    if ($actor === 'player') {
        // Permitir múltiples crafteos simultáneos por diferentes actores
        $objeto = null;
        $material_tier = $_POST['materialTier'];
        $materialNombre = $_POST['materialNombre'];
        $materialId = $_POST['materialId'];
        $material_pct = 1;

        $query_objeto = $db->query(" SELECT * FROM `mybb_op_objetos` WHERE objeto_id='$objetoIdPost' AND custom='0'; ");  
        while ($q = $db->fetch_array($query_objeto)) { $objeto = $q; }

        if ($material_tier != '') {
            $material_pct = $material_pct - (floatval($material_tier) / 10);
        }

        $berriesCosto = intval($objeto['berriesCrafteo']);
        $tiempoCreacion = $objeto['tiempo_creacion'];
        $nombreObjeto = $objeto['nombre'];
        $oficioObjeto = $objeto['oficio'];

        // Aplicar descuentos: especialización, material, y Recolector/Mayorista (el mejor)
        $berriesCosto = $berriesCosto * getBerriesCosto($oficioObjeto) * $material_pct * getRecolectorDescuento();

        // Permitir elegir cantidad solo si el usuario tiene el oficio 'Recolector' en oficio1, oficio2 o especialización
        $tieneRecolector = false;
        if ($oficio1 === 'Recolector' || $oficio2 === 'Recolector' || isset($oficios->{'Recolector'})) {
            $tieneRecolector = true;
        }
        if ($tieneRecolector) {
            $recolectorNivel = getRecolectorNivel();
            $agresteNivel = getAgresteNivel();
            $cantidadMaxima = ($recolectorNivel >= 2) ? 2 : 1;
            if ($agresteNivel == 3) {
                $cantidadMaxima = 10;
            } else if ($agresteNivel == 2) {
                $cantidadMaxima = 5;
            } else if ($agresteNivel == 1) {
                $cantidadMaxima = 3;
            }
            $cantidadCraftear = isset($_POST['cantidadCraftear']) ? intval($_POST['cantidadCraftear']) : 1;
            if ($cantidadCraftear < 1) {
                $cantidadCraftear = 1;
            } else if ($cantidadCraftear > $cantidadMaxima) {
                $cantidadCraftear = $cantidadMaxima;
            }
        } else {
            $cantidadCraftear = 1;
        }
        $costoTotal = $berriesCosto * $cantidadCraftear;

        if ($berries - $costoTotal >= 0) {
            // Permitir múltiples crafteos simultáneos
            $db->query("START TRANSACTION");
            
            $berriesNuevo = $berries - $costoTotal;
            $duracion = floatval($tiempoCreacion) * 3600.0 * getTiempoCreacion($oficioObjeto);
            $duracion = $duracion * getTiempoCreacionVirtud($has_estudioso, $has_erudito) * $material_pct * getReduccionTiempoTotal();
            $duracion = floor($duracion);
            $timestamp_end = time() + intval($duracion);
            
            for ($i = 0; $i < $cantidadCraftear; $i++) {
                $db->query(" INSERT INTO `mybb_op_crafteo_usuarios` (`uid`, `objeto_id`, `nombre`, `material_id`, `timestamp_end`, `duracion`, `costo`) VALUES ('$uid','$objetoIdPost', '$nombreObjeto','$materialId','$timestamp_end','$duracion','$berriesCosto'); ");
            }
            
            $db->query(" UPDATE `mybb_op_fichas` SET `berries`='$berriesNuevo' WHERE `fid`='$uid'; ");
            log_audit_currency($uid, $username, $uid, '[Crafteo][Berries]', 'berries', $berriesNuevo);
            if ($material_tier != '') {
                quitarObjeto($materialId);
            }
            
            $mensajeCantidad = ($cantidadCraftear > 1) ? " (x$cantidadCraftear unidades)" : "";
            $log = "¡Crafteo en proceso. Se está creando el $nombreObjeto$mensajeCantidad ($objetoIdPost)\nTienes ahora $berriesNuevo berries. ($berries - $costoTotal)";
            $db->query(" INSERT INTO `mybb_op_audit_crafteo` (`uid`, `nombre`, `log`) VALUES ('$uid', '$nombre', '$log'); ");
            
            // Confirmar la transacción
            $db->query("COMMIT");

            header('Content-type: application/json');
            echo json_encode(array('success' => true, 'message' => 'Crafteo iniciado', 'actor' => 'player'));
            exit;

        }

    } else if ($actor === 'npc' && $npcIdPost != '') {
        // Crafteo mediante NPC. Verificar que NPC pertenece al usuario y tiene oficios compatibles
        // Buscar por username o por uid en caso de que la tabla guarde el id en vez del nombre
        $query_npc_user = $db->query(" SELECT n.npc_id, n.oficios, n.nombre AS npc_nombre FROM mybb_op_npcs AS n WHERE n.npc_id='".$db->escape_string($npcIdPost)."' AND (n.npc_id LIKE '".intval($uid)."-%' OR EXISTS (SELECT 1 FROM mybb_op_npcs_usuarios nu WHERE nu.npc_id = n.npc_id AND (nu.usuario='".$db->escape_string($username)."' OR nu.usuario='".intval($uid)."'))) LIMIT 1 ");
        $npc_row = $db->fetch_array($query_npc_user);
        if (!$npc_row) {
            header('Content-type: application/json');
            echo json_encode(array('error' => 'NPC no encontrado o no te pertenece.'));
            exit;
        }

        // Cargar objeto
        $objeto = null;
        $material_tier = $_POST['materialTier'];
        $materialNombre = $_POST['materialNombre'];
        $materialId = $_POST['materialId'];
        $material_pct = 1;

        $query_objeto = $db->query(" SELECT * FROM `mybb_op_objetos` WHERE objeto_id='$objetoIdPost' AND custom='0'; ");  
        while ($q = $db->fetch_array($query_objeto)) { $objeto = $q; }

        if (!$objeto) {
            header('Content-type: application/json');
            echo json_encode(array('error' => 'Objeto inválido.'));
            exit;
        }

        if ($material_tier != '') {
            $material_pct = $material_pct - (floatval($material_tier) / 10);
        }

        $berriesCosto = intval($objeto['berriesCrafteo']);
        $tiempoCreacion = $objeto['tiempo_creacion'];
        $nombreObjeto = $objeto['nombre'];
        $oficioObjeto = $objeto['oficio'];

        // Verificar que el NPC puede craftear ese oficio (nivel y especializaciones, especializaciones cuentan +1)
        $npc_oficios = json_decode($npc_row['oficios']);
        $puede = false;
        // Si NPC tiene el oficio principal
        if (isset($npc_oficios->{$oficioObjeto}) && intval($npc_oficios->{$oficioObjeto}->{'nivel'}) >= intval($objeto['nivel'])) {
            $puede = true;
        } else {
            // comprobar especializaciones del NPC (sumando +1 al nivel)
            foreach ($npc_oficios as $main => $data) {
                if (isset($data->sub) && is_object($data->sub)) {
                    foreach ($data->sub as $sub => $lvl) {
                        if ($sub === $oficioObjeto && (intval($lvl) + 1) >= intval($objeto['nivel'])) {
                            $puede = true;
                            break 2;
                        }
                    }
                }
            }
        }
        if (!$puede) {
            header('Content-type: application/json');
            echo json_encode(array('error' => 'Este NPC no tiene el oficio o nivel requerido para craftear este objeto.'));
            exit;
        }

        // Aplicar descuentos en base al NPC (similar a getBerriesCosto pero usando npc_oficios)
        function getBerriesCostoNPC($oficio, $npc_oficios) {
            $nivelMax = 0;
            if ($oficio == 'Cocinero' || $oficio == 'Chef' || $oficio == 'Aprovisionador') {
                if (isset($npc_oficios->{'Cocinero'}->{'sub'}->{'Aprovisionador'}) && $npc_oficios->{'Cocinero'}->{'sub'}->{'Aprovisionador'} > $nivelMax) $nivelMax = $npc_oficios->{'Cocinero'}->{'sub'}->{'Aprovisionador'};
            }
            if ($oficio == 'Artesano' || $oficio == 'Herrero' || $oficio == 'Modista') {
                if (isset($npc_oficios->{'Artesano'}->{'sub'}->{'Modista'}) && $npc_oficios->{'Artesano'}->{'sub'}->{'Modista'} > $nivelMax) $nivelMax = $npc_oficios->{'Artesano'}->{'sub'}->{'Modista'};
            }
            if ($oficio == 'Médico' || $oficio == 'Doctor' || $oficio == 'Farmacólogo') {
                if (isset($npc_oficios->{'Médico'}->{'sub'}->{'Doctor'}) && $npc_oficios->{'Médico'}->{'sub'}->{'Doctor'} > $nivelMax) $nivelMax = $npc_oficios->{'Médico'}->{'sub'}->{'Doctor'};
            }
            if ($oficio == 'Carpintero' || $oficio == 'Astillero' || $oficio == 'Constructor') {
                if (isset($npc_oficios->{'Carpintero'}->{'sub'}->{'Astillero'}) && $npc_oficios->{'Carpintero'}->{'sub'}->{'Astillero'} > $nivelMax) $nivelMax = $npc_oficios->{'Carpintero'}->{'sub'}->{'Astillero'};
            }
            if ($oficio == 'Inventor' || $oficio == 'Ingeniero' || $oficio == 'Biólogo') {
                if (isset($npc_oficios->{'Inventor'}->{'sub'}->{'Biólogo'}) && $npc_oficios->{'Inventor'}->{'sub'}->{'Biólogo'} > $nivelMax) $nivelMax = $npc_oficios->{'Inventor'}->{'sub'}->{'Biólogo'};
            }
            if ($nivelMax == 1) return 0.95;
            if ($nivelMax == 2) return 0.90;
            if ($nivelMax == 3) return 0.80;
            return 1.0;
        }

        function getTiempoCreacionNPC($oficio, $npc_oficios) {
            $nivelMax = 0;
            if ($oficio == 'Cocinero' || $oficio == 'Chef' || $oficio == 'Aprovisionador') {
                if (isset($npc_oficios->{'Cocinero'}->{'sub'}->{'Chef'}) && $npc_oficios->{'Cocinero'}->{'sub'}->{'Chef'} > $nivelMax) $nivelMax = $npc_oficios->{'Cocinero'}->{'sub'}->{'Chef'};
            }
            if ($oficio == 'Artesano' || $oficio == 'Herrero' || $oficio == 'Modista') {
                if (isset($npc_oficios->{'Artesano'}->{'sub'}->{'Herrero'}) && $npc_oficios->{'Artesano'}->{'sub'}->{'Herrero'} > $nivelMax) $nivelMax = $npc_oficios->{'Artesano'}->{'sub'}->{'Herrero'};
            }
            if ($oficio == 'Médico' || $oficio == 'Doctor' || $oficio == 'Farmacólogo') {
                if (isset($npc_oficios->{'Médico'}->{'sub'}->{'Farmacólogo'}) && $npc_oficios->{'Médico'}->{'sub'}->{'Farmacólogo'} > $nivelMax) $nivelMax = $npc_oficios->{'Médico'}->{'sub'}->{'Farmacólogo'};
            }
            if ($oficio == 'Carpintero' || $oficio == 'Astillero' || $oficio == 'Constructor') {
                if (isset($npc_oficios->{'Carpintero'}->{'sub'}->{'Constructor'}) && $npc_oficios->{'Carpintero'}->{'sub'}->{'Constructor'} > $nivelMax) $nivelMax = $npc_oficios->{'Carpintero'}->{'sub'}->{'Constructor'};
            }
            if ($oficio == 'Inventor' || $oficio == 'Ingeniero' || $oficio == 'Biólogo') {
                if (isset($npc_oficios->{'Inventor'}->{'sub'}->{'Ingeniero'}) && $npc_oficios->{'Inventor'}->{'sub'}->{'Ingeniero'} > $nivelMax) $nivelMax = $npc_oficios->{'Inventor'}->{'sub'}->{'Ingeniero'};
            }
            if ($nivelMax == 1) return 0.95;
            if ($nivelMax == 2) return 0.90;
            if ($nivelMax == 3) return 0.80;
            return 1.0;
        }

        // Aplicar descuentos del NPC (material_pct y posible ajustes de mayorista no aplican automáticamente)
        $berriesCosto = $berriesCosto * getBerriesCostoNPC($oficioObjeto, $npc_oficios) * $material_pct;

        // Cantidad: por ahora NPC craftea solo 1 por defecto (si quieres permitirlo, se puede extender similar a recolector)
        $cantidadCraftear = 1;
        $costoTotal = $berriesCosto * $cantidadCraftear;

        // Verificar que el jugador puede pagar el costo de iniciar el crafteo del NPC
        if ($berries - $costoTotal >= 0) {
            // Permitir múltiples crafteos simultáneos por diferentes actores
            $db->query("START TRANSACTION");

            $berriesNuevo = $berries - $costoTotal;
            $duracion = floatval($tiempoCreacion) * 3600.0 * getTiempoCreacionNPC($oficioObjeto, $npc_oficios);
            $duracion = $duracion * 1.0 * $material_pct; // No aplicamos virtudes del jugador al NPC
            $duracion = floor($duracion);
            $timestamp_end = time() + intval($duracion);

            for ($i = 0; $i < $cantidadCraftear; $i++) {
                $db->query(" INSERT INTO `mybb_op_crafteo_npcs` (`uid`, `npc_id`, `objeto_id`, `nombre`, `material_id`, `timestamp_end`, `duracion`, `costo`) VALUES ('".intval($uid)."','".$db->escape_string($npcIdPost)."','".$db->escape_string($objetoIdPost)."','".$db->escape_string($nombreObjeto)."','".$db->escape_string($materialId)."','".intval($timestamp_end)."','".intval($duracion)."','".intval($berriesCosto)."'); ");
            }

            $db->query(" UPDATE `mybb_op_fichas` SET `berries`='$berriesNuevo' WHERE `fid`='$uid'; ");
            log_audit_currency($uid, $username, $uid, '[Crafteo NPC][Berries]', 'berries', $berriesNuevo);
            if ($material_tier != '') {
                quitarObjeto($materialId);
            }

            $log = "El NPC {$npc_row['npc_nombre']} está creando $nombreObjeto ($objetoIdPost). Costo: $costoTotal berries. Usuario ahora $berriesNuevo";
            $db->query(" INSERT INTO `mybb_op_audit_crafteo` (`uid`, `nombre`, `log`) VALUES ('$uid', 'NPC_CRAFTEO', '".$db->escape_string($log)."'); ");
            $db->query("COMMIT");

            header('Content-type: application/json');
            echo json_encode(array('success' => true, 'message' => 'Crafteo NPC iniciado', 'actor' => 'npc', 'npcId' => $npcIdPost));
            exit;

        }

    }
}

if ($accion == 'reclamar') {

    header('Content-type: application/json');
    $response = array();

    $npcIdPost = isset($_POST['npcId']) ? $db->escape_string($_POST['npcId']) : '';

    if ($npcIdPost != '') {
        // Reclamar crafteo(s) completados del NPC específico
        $crafteosNpc = array();
        $query_crafteo_npc = $db->query(" SELECT * FROM mybb_op_crafteo_npcs WHERE uid='$uid' AND npc_id='".$db->escape_string($npcIdPost)."' ");
        while ($q = $db->fetch_array($query_crafteo_npc)) { array_push($crafteosNpc, $q); }

        if (count($crafteosNpc) > 0) {
            // Comprobar cuáles están completados (timestamp_end <= time)
            $completos = array_filter($crafteosNpc, function($it) { return intval($it['timestamp_end']) <= time(); });
            if (count($completos) > 0) {
                // Obtener oficios del NPC y mapearlos a la estructura de jugador { "Oficio": { "sub": {...}, "nivel": X } }
                $npc_meta = null;
                $query_npc_meta = $db->query("SELECT oficios FROM mybb_op_npcs WHERE npc_id='".$db->escape_string($npcIdPost)."' LIMIT 1");
                if ($q = $db->fetch_array($query_npc_meta)) { $npc_meta = $q; }

                $npc_oficios_obj = array();
                if ($npc_meta && !empty($npc_meta['oficios'])) {
                    $npc_oficios_raw = json_decode($npc_meta['oficios']);
                    foreach ($npc_oficios_raw as $oname => $odata) {
                        $nivel = 0;
                        $sub = array();
                        if (is_object($odata)) {
                            if (isset($odata->nivel)) $nivel = intval($odata->nivel);
                            if (isset($odata->sub) && is_object($odata->sub)) $sub = (array)$odata->sub;
                        } else if (is_numeric($odata)) {
                            $nivel = intval($odata);
                        }
                        $npc_oficios_obj[$oname] = array('sub' => $sub, 'nivel' => $nivel);
                    }
                }

                $npc_oficios_json_raw = json_encode($npc_oficios_obj, JSON_UNESCAPED_UNICODE);

                foreach ($completos as $it) {
                    // Pasamos la estructura (sin escapar) a darObjeto2; la función hará el escape al insertarlo
                    darObjeto2($it['objeto_id'], $uid, $username, $npc_oficios_json_raw);
                }

                // Borrar solo los completados
                $db->query("DELETE FROM mybb_op_crafteo_npcs WHERE uid='$uid' AND npc_id='".$db->escape_string($npcIdPost)."' AND timestamp_end <= ".time());
                $log = "Crafteos del NPC $npcIdPost reclamados.";
            } else {
                $log = "No hay crafteos completados para este NPC.";
            }
        } else {
            $log = "No hay crafteos para este NPC.";
        }

    } else {
        // Reclamar crafteo(s) del usuario como antes
        $crafteos = array();
        $hasCrafteo = false;
        $query_crafteo_usuario = $db->query(" SELECT * FROM mybb_op_crafteo_usuarios WHERE uid='$uid' ");
        while ($q = $db->fetch_array($query_crafteo_usuario)) { 
            array_push($crafteos, $q);
            $hasCrafteo = true; 
        }

        if ($hasCrafteo && count($crafteos) > 0) {
            $objetoIdReclamar = $crafteos[0]['objeto_id'];
            $nombreObjeto = $crafteos[0]['nombre'];
            $cantidadReclamada = count($crafteos);

            $db->query("DELETE FROM mybb_op_crafteo_usuarios WHERE uid='$uid'");
            
            // Entregar todos los crafteos completados
            for ($i = 0; $i < $cantidadReclamada; $i++) {
                darObjeto2($objetoIdReclamar, $uid, $username, $ficha['oficios']);
            }
        
            $mensajeCantidad = ($cantidadReclamada > 1) ? " (x$cantidadReclamada unidades)" : "";
            $log = "¡$nombreObjeto$mensajeCantidad ($objetoIdReclamar) reclamado, felicidades!";  
        } else {
            $log = "¡Ya reclamaste este crafteo, o hubo un error de doble click. Cuidadito!";  
        }

    }
    
    $response[0] = array('log' => $log);
    echo json_encode($response); 
    return;

}

if ($accion == 'desbloquear') {
    $query_objeto = $db->query(" SELECT * FROM `mybb_op_objetos` WHERE objeto_id='$objetoIdPost' AND custom='0'; "); 

    
    while ($q = $db->fetch_array($query_objeto)) { 

        $id = $q['objeto_id'];
        $nombre = $q['nombre'];
        $pt_desbloquear = intval($q['desbloquear']);

        if ($puntosOficio - $pt_desbloquear >= 0) {
            $puntosOficioNuevo = $puntosOficio - $pt_desbloquear;
            $db->query(" UPDATE `mybb_op_fichas` SET `puntos_oficio`='$puntosOficioNuevo' WHERE `fid`='$uid'; ");
            $db->query(" INSERT INTO `mybb_op_inventario_crafteo` (`objeto_id`, `nombre`, `uid`, `desbloqueado`) VALUES ('$id', '$nombre', '$uid', '1'); ");
        }
    
    }
}

if ($accion == 'desbloquearReceta') {
    $query_objeto = $db->query(" SELECT * FROM `mybb_op_objetos` WHERE objeto_id='$objetoIdPost' AND custom='0'; "); 

    while ($q = $db->fetch_array($query_objeto)) { 

        $id = $q['objeto_id'];
        $nombre = $q['nombre'];
        quitarObjeto($recetaIdPost);
        $db->query(" INSERT INTO `mybb_op_inventario_crafteo` (`objeto_id`, `nombre`, `uid`, `desbloqueado`) VALUES ('$id', '$nombre', '$uid', '1'); ");

    }
}

// Filtro global: permitir si el UID está en la lista CSV (ignorando espacios)
$cond_uid = "FIND_IN_SET('".$db->escape_string($uid)."', REPLACE(crafteo_usuarios, ' ', '')) > 0";

// Helper para el check de crafteo vacío o NULL
$cond_libre = "(crafteo_usuarios='' OR crafteo_usuarios IS NULL)";

$objetos = array();
$objetos_array = array();

// Endpoint AJAX: devolver objetos filtrados por oficios del actor (player o npc)
if ($accion == 'get_objects_for_actor') {
    $actor = isset($_POST['actor']) ? $_POST['actor'] : '';
    $resp_objetos = array();
    $resp_objetos_array = array();

    // Determinar oficios de referencia
    $oficios_ref = json_decode($ficha['oficios'], true);
    if ($actor === 'npc' && !empty($_POST['npcId'])) {
        $npcId = $db->escape_string($_POST['npcId']);
        $qnpc = $db->query("SELECT * FROM mybb_op_npcs WHERE npc_id='".$npcId."' LIMIT 1");
        if ($rn = $db->fetch_array($qnpc)) {
            if (!empty($rn['oficios'])) {
                $decoded = json_decode($rn['oficios'], true);
                if (is_array($decoded)) { $oficios_ref = $decoded; }
            }
        }
    }

    // Fetch all objetos custom=0 and filter in PHP according to $oficios_ref
    $qobj = $db->query("SELECT * FROM mybb_op_objetos WHERE custom=0");
    while ($r = $db->fetch_array($qobj)) {
        $include = false;
        $obj_oficio = $r['oficio'];
        $obj_nivel = intval($r['nivel']);

        // Allow if explicitly allowed by per-object crafteo_usuarios
        $craf_users = str_replace(' ', '', $r['crafteo_usuarios']);
        if ($craf_users !== '') {
            $parts = explode(',', $craf_users);
            if (in_array((string)$uid, $parts)) { $include = true; }
        }

        if (!$include) {
            foreach ($oficios_ref as $main => $data) {
                // data can be object or array
                $nivel_main = 0;
                $subs = array();
                if (is_array($data)) {
                    $nivel_main = isset($data['nivel']) ? intval($data['nivel']) : 0;
                    $subs = isset($data['sub']) ? $data['sub'] : array();
                } elseif (is_object($data)) {
                    $nivel_main = isset($data->nivel) ? intval($data->nivel) : 0;
                    $subs = isset($data->sub) ? (array)$data->sub : array();
                }

                if ($main === $obj_oficio && $nivel_main >= $obj_nivel) { $include = true; break; }
                if (!empty($subs) && isset($subs[$obj_oficio]) && intval($subs[$obj_oficio]) >= $obj_nivel) { $include = true; break; }
            }
        }

        if ($include) {
            // Para NPCs, excluir objetos con ID que empiece por números (ej. 352MI001)
            if ($actor === 'npc' && preg_match('/^\d/', $r['objeto_id'])) {
                continue;
            }
            $key = $r['objeto_id'];
            if (!isset($resp_objetos[$key])) { $resp_objetos[$key] = array(); }
            $resp_objetos[$key][] = $r;
            $resp_objetos_array[] = $r['objeto_id'];
        }
    }

    header('Content-Type: application/json');
    echo json_encode(array('objetos' => $resp_objetos, 'objetos_array' => $resp_objetos_array));
    exit;

}

// Ramas base (oficio1 siempre existe)
$base_oficio1 = "(oficio='".$db->escape_string($oficio1)."' AND nivel <= ".(int)$oficio1_nivel." AND $cond_libre)";

// Si hay oficio2, añade la rama
$base_oficio2 = (!empty($oficio2)
  ? " OR (oficio='".$db->escape_string($oficio2)."' AND nivel <= ".(int)$oficio2_nivel." AND $cond_libre)"
  : ""
);

// Normaliza las ramas de especialización por si venían con '||' inicial
$oficio1_espe1_db = preg_replace('/^\s*\|\|\s*/', ' OR ', (string)$oficio1_espe1_db);
$oficio1_espe2_db = preg_replace('/^\s*\|\|\s*/', ' OR ', (string)$oficio1_espe2_db);
$oficio2_espe1_db = preg_replace('/^\s*\|\|\s*/', ' OR ', (string)$oficio2_espe1_db);
$oficio2_espe2_db = preg_replace('/^\s*\|\|\s*/', ' OR ', (string)$oficio2_espe2_db);
$recolector_db = preg_replace('/^\s*\|\|\s*/', ' OR ', (string)$recolector_db);
$recolector_espe1_db = preg_replace('/^\s*\|\|\s*/', ' OR ', (string)$recolector_espe1_db);
$recolector_espe2_db = preg_replace('/^\s*\|\|\s*/', ' OR ', (string)$recolector_espe2_db);

// Limpiar variables vacías para evitar errores SQL
if (trim($oficio1_espe1_db) === '') $oficio1_espe1_db = '';
if (trim($oficio1_espe2_db) === '') $oficio1_espe2_db = '';
if (trim($oficio2_espe1_db) === '') $oficio2_espe1_db = '';
if (trim($oficio2_espe2_db) === '') $oficio2_espe2_db = '';
if (trim($recolector_db) === '') $recolector_db = '';
if (trim($recolector_espe1_db) === '') $recolector_espe1_db = '';
if (trim($recolector_espe2_db) === '') $recolector_espe2_db = '';

// Consulta final
$query_objetos = $db->query("
  SELECT *
  FROM `mybb_op_objetos`
  WHERE custom=0 AND (
      $base_oficio1
      $base_oficio2
      $oficio1_espe1_db
      $oficio1_espe2_db
      $oficio2_espe1_db
      $oficio2_espe2_db
      $recolector_db
      $recolector_espe1_db
      $recolector_espe2_db
      OR $cond_uid
    );
");


// echo(" SELECT * FROM `mybb_op_objetos` WHERE custom='0' AND ((oficio='$oficio1' AND nivel <= $oficio1_nivel) $oficio2_db $oficio1_espe1_db $oficio1_espe2_db $oficio2_espe1_db $oficio2_espe2_db $uid_crafteo); ");
// 
// echo("SELECT * FROM `mybb_op_objetos` WHERE custom='0' AND ((oficio='$oficio1' AND nivel <= $oficio1_nivel) $oficio2_db $oficio1_espe1_db $oficio1_espe2_db $oficio2_espe1_db $oficio2_espe2_db);");

// Mostrar TODOS los materiales del usuario (kits de crafteo) independientemente del oficio
// porque el usuario podría craftear objetos de diferentes oficios según sus especializaciones
$query_materiales = $db->query(" SELECT i.objeto_id, o.nombre, o.categoria, o.subcategoria, o.tier, i.cantidad, i.uid FROM mybb_op_inventario AS i
    JOIN mybb_op_objetos AS o ON i.objeto_id = o.objeto_id
     
    WHERE i.uid='$uid' AND categoria='Materiales'; "); 
// WHERE i.uid='$uid' AND categoria='Materiales' AND (subcategoria='$oficio1' OR subcategoria='$oficio2'); ");
$query_inventario = $db->query(" SELECT * FROM `mybb_op_inventario_crafteo` WHERE uid='$uid' "); 
$query_objetos_recetas = $db->query(" SELECT * FROM `mybb_op_inventario` WHERE uid='$uid' AND (objeto_id LIKE '%MJR00%') "); 

$objetos = array();
$objetos_array = array();

$materiales = array();
$materiales_array = array();

$inventario = array();
$inventario_array = array();

$objetos_recetas = array();
$objetos_recetas_array = array();


while ($q = $db->fetch_array($query_objetos)) { 
    $objeto_id = $q['objeto_id'];
    $key = "$objeto_id";
    if (!$objetos[$key]) { $objetos[$key] = array(); }
    array_push($objetos[$key], $q);
    array_push($objetos_array, $objeto_id);
}

while ($q = $db->fetch_array($query_materiales)) { 
    $objeto_id = $q['objeto_id'];
    $key = "$objeto_id";
    if (!$materiales[$key]) { $materiales[$key] = array(); }
    array_push($materiales[$key], $q);
    array_push($materiales_array, $objeto_id);
}

while ($q = $db->fetch_array($query_inventario)) { 
    $objeto_id = $q['objeto_id'];
    $key = "$objeto_id";
    if (!$inventario[$key]) { $inventario[$key] = array(); }
    array_push($inventario[$key], $q);
    array_push($inventario_array, $objeto_id);
}

while ($q = $db->fetch_array($query_objetos_recetas)) { 
    $objeto_id = $q['objeto_id'];
    $key = "$objeto_id";
    if (!$objetos_recetas[$key]) { $objetos_recetas[$key] = array(); }
    array_push($objetos_recetas[$key], $q);
    array_push($objetos_recetas_array, $objeto_id);
}

// Obtener NPCs del usuario (si existen en mybb_op_npcs_usuarios) y combinar con datos de mybb_op_npcs
$npcs = array();
$npcs_array = array();
$query_npcs_usuario = $db->query("SELECT nu.npc_id AS npc_key, n.* FROM mybb_op_npcs_usuarios nu JOIN mybb_op_npcs n ON nu.npc_id = n.npc_id WHERE (nu.usuario='".$db->escape_string($username)."' OR nu.usuario='".intval($uid)."')");
while ($q = $db->fetch_array($query_npcs_usuario)) {
    $npc_key = $q['npc_key'];
    $key = "$npc_key";
    if (!$npcs[$key]) { $npcs[$key] = array(); }
    array_push($npcs[$key], $q);
    array_push($npcs_array, $npc_key);
}

// Además, algunos NPCs están registrados solo en `mybb_op_npcs` y su id incluye el uid como prefijo (ej. '276-NPC001').
// Añadir esos NPCs también si coinciden con el uid actual.
$query_npcs_by_prefix = $db->query("SELECT * FROM mybb_op_npcs WHERE npc_id LIKE '".intval($uid)."-%'");
while ($q = $db->fetch_array($query_npcs_by_prefix)) {
    $npc_key = $q['npc_id'];
    $key = "$npc_key";
    if (!$npcs[$key]) { $npcs[$key] = array(); }
    array_push($npcs[$key], $q);
    array_push($npcs_array, $npc_key);
}

$objetos_array_json = json_encode($objetos_array);
$objetos_json = json_encode($objetos);
$materiales_array_json = json_encode($materiales_array);
$materiales_json = json_encode($materiales);
$inventario_array_json = json_encode($inventario_array);
$inventario_json = json_encode($inventario);
$objetos_recetas_array_json = json_encode($objetos_recetas_array);
$objetos_recetas_json = json_encode($objetos_recetas);

$npcs_array_json = json_encode($npcs_array);
$npcs_json = json_encode($npcs);

// Estado de crafteos activos: usuario y NPCs
$player_crafteo_in_progress = false;
$query_player_crafteo = $db->query("SELECT COUNT(*) as count FROM mybb_op_crafteo_usuarios WHERE uid='".intval($uid)."' AND timestamp_end > ".time());
if ($q = $db->fetch_array($query_player_crafteo)) { $player_crafteo_in_progress = intval($q['count']) > 0; }

$npcs_crafteando = array();
$query_npcs_crafteando = $db->query("SELECT npc_id FROM mybb_op_crafteo_npcs WHERE uid='".intval($uid)."' AND timestamp_end > ".time()." GROUP BY npc_id");
while ($q = $db->fetch_array($query_npcs_crafteando)) { $npcs_crafteando[] = $q['npc_id']; }

$player_crafteo_json = json_encode($player_crafteo_in_progress);
$npcs_crafteando_json = json_encode($npcs_crafteando);

if ($ficha_existe == true && $ficha_aprobada == true && $has_sin_oficio == false) {
    // Recolectar crafteos del usuario y de NPCs para decidir la plantilla a mostrar.
    $query_creacion_usuario2 = $db->query(" SELECT * FROM mybb_op_crafteo_usuarios WHERE uid='$uid' ");
    $query_creacion_npcs = $db->query(" SELECT * FROM mybb_op_crafteo_npcs WHERE uid='$uid' ");

    $en_curso = false;
    $completo = false;
    $tiempo_left = 0;
    $cantidad_crafteo = 0;

    $earliest_end = PHP_INT_MAX;
    $display = null; // will hold ['type' => 'user'|'npc', 'objeto_id'=>..., 'nombre'=>..., 'duracion'=>..., 'timestamp_end'=>..., 'cantidad'=>..., 'npc_id'=> ...]

    while ($m = $db->fetch_array($query_creacion_usuario2)) {
        $timestamp_end = intval($m['timestamp_end']);
        $duracion = $m['duracion'];
        $objeto_id = $m['objeto_id'];
        $nombre_objeto = $m['nombre'];
        $cantidad_crafteo++;

        if (time() > ($timestamp_end)) {
            $completo = true;
        } else {
            $en_curso = true;
            if ($timestamp_end < $earliest_end) {
                $earliest_end = $timestamp_end;
                $display = array('type' => 'user', 'objeto_id' => $objeto_id, 'nombre' => $nombre_objeto, 'duracion' => $duracion, 'timestamp_end' => $timestamp_end, 'cantidad' => $cantidad_crafteo, 'npc_id' => '');
            }
        }
    }

    // Revisar crafteos de NPCs
    while ($n = $db->fetch_array($query_creacion_npcs)) {
        $timestamp_end = intval($n['timestamp_end']);
        $duracion = $n['duracion'];
        $objeto_id = $n['objeto_id'];
        $nombre_objeto = $n['nombre'];
        $npc_owner = $n['npc_id'];

        if (time() > ($timestamp_end)) {
            $completo = true;
        } else {
            $en_curso = true;
            if ($timestamp_end < $earliest_end) {
                $earliest_end = $timestamp_end;
                $display = array('type' => 'npc', 'objeto_id' => $objeto_id, 'nombre' => $nombre_objeto, 'duracion' => $duracion, 'timestamp_end' => $timestamp_end, 'cantidad' => 1, 'npc_id' => $npc_owner);
            }
        }
    }

    // Si hay crafteo en curso y no estamos en modo_vista, mostrar la página de progreso (la craft más cercana a terminar)
    if ($en_curso && !$modo_vista && $display) {
        $cantidad_crafteo = $display['cantidad'];
        $nombre_objeto = $display['nombre'];
        $objeto_id = $display['objeto_id'];
        $duracion = $display['duracion'];
        $tiempo_left = ($display['timestamp_end']) * 1000;
        // para reclamaciones por NPC
        $reclamar_npc_id = $display['npc_id'];

        // Preparar versión JSON segura para uso en plantillas JS
        // Emitir un literal JS válido (por ejemplo: "npc_1" o "" ) para evitar tokens sin comillas
        $reclamar_npc_id_js = isset($reclamar_npc_id) ? json_encode($reclamar_npc_id) : json_encode("");

        $tpl_tmp = $templates->get("op_crafteo_en_curso");
        $tpl_tmp = str_replace('${', '___DOLLAR_CURLY___', $tpl_tmp);
        eval("\$page = \"".$tpl_tmp."\";");
        $page = str_replace('___DOLLAR_CURLY___', '${', $page);
        output_page($page);
    } else {
        if ($completo) {
            // Si hay algún completo, mostrar la pantalla de '¡Completado!' y pasar npcId si corresponde (priorizar user si hay ambos)
            $reclamar_npc_id = '';
            // Comprobar si hay crafteos completados por usuario
            $query_comp_user = $db->query(" SELECT * FROM mybb_op_crafteo_usuarios WHERE uid='$uid' AND timestamp_end <= ".time()." LIMIT 1");
            if ($qc = $db->fetch_array($query_comp_user)) {
                $objeto_id = $qc['objeto_id'];
                $nombre_objeto = $qc['nombre'];
                $cantidad_crafteo = 1;
                // el template reclamará sin npcId (por defecto)
            } else {
                // buscar crafteos completados por NPC
                $query_comp_npc = $db->query(" SELECT * FROM mybb_op_crafteo_npcs WHERE uid='$uid' AND timestamp_end <= ".time()." LIMIT 1");
                if ($qn = $db->fetch_array($query_comp_npc)) {
                    $objeto_id = $qn['objeto_id'];
                    $nombre_objeto = $qn['nombre'];
                    $cantidad_crafteo = 1;
                    $reclamar_npc_id = $qn['npc_id'];
                }
            }

            // valor seguro para insertar en JS (literal JS mediante json_encode)
            $reclamar_npc_id_js = isset($reclamar_npc_id) ? json_encode($reclamar_npc_id) : json_encode("");

            $tpl_tmp = $templates->get("op_crafteo_completo");
            $tpl_tmp = str_replace('${', '___DOLLAR_CURLY___', $tpl_tmp);
            eval("\$page = \"".$tpl_tmp."\";");
            $page = str_replace('___DOLLAR_CURLY___', '${', $page);
        } else {
            $reclamar_npc_id_js = isset($reclamar_npc_id) ? json_encode($reclamar_npc_id) : json_encode("");
            $tpl_tmp = $templates->get("op_crafteo");
            $tpl_tmp = str_replace('${', '___DOLLAR_CURLY___', $tpl_tmp);
            eval("\$page = \"".$tpl_tmp."\";");
            $page = str_replace('___DOLLAR_CURLY___', '${', $page);
        }
        output_page($page);
    }

} else {
    $mensaje_redireccion = "Para acceder a esta página debes tener un oficio.";
    eval("\$page = \"".$templates->get("op_redireccion")."\";");
    output_page($page);
}

