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
define('THIS_SCRIPT', 'fichakuro.php');
require_once "./../global.php";
require "./../inc/config.php";
require_once "./functions/op_functions.php";

global $templates, $mybb;

$query_uid = $mybb->get_input('uid'); 
$user_uid = $mybb->user['uid'];
$test = $mybb->get_input('test');
$pestana = $mybb->get_input('pestana');
$razas = [];

$username = $mybb->user['username'];


if (!$query_uid) {
    $query_uid = $mybb->user['uid'];
}

$is_same_user = $uid == $uid_user;

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

$cambiar_avatar1S1 = addslashes($_POST["cambiar_avatar1S1"]);
$cambiar_avatar2S1 = addslashes($_POST["cambiar_avatar2S1"]);
$cambiar_aparienciaS1 = addslashes($_POST["cambiar_aparienciaS1"]);
$cambiar_personalidadS1 = addslashes($_POST["cambiar_personalidadS1"]);
$cambiar_historiaS1 = addslashes($_POST["cambiar_historiaS1"]);
$cambiar_extrasS1 = addslashes($_POST["cambiar_extrasS1"]);
$cambiar_apodoS1 = addslashes($_POST["cambiar_apodoS1"]);
$cambiar_nombreS1 = addslashes($_POST["cambiar_nombreS1"]);
$cambiar_rango1 = addslashes($_POST["cambiar_rangoS1"]);
$cambiar_faccionS1 = addslashes($_POST["cambiar_faccionS1"]);
$cambiar_visibleS1 = addslashes($_POST["cambiar_visibleS1"]);
$cambiar_equipamiento = addslashes($_POST["cambiar_equipamiento"]);

$objetos_html = '';

$edit_objeto_id = $_POST["edit_objeto_id"];
$edit_nombre = addslashes($_POST["edit_nombre"]);
$edit_imagen = addslashes($_POST["edit_imagen"]);

// $ano_edad = 999;

// Función auxiliar para actualizar o insertar en ficha secreta
function updateOrInsertSecret($db, $query_uid, $field, $value) {
    $check_secret = $db->query("SELECT fid FROM `mybb_op_fichas_secret` WHERE `fid`='$query_uid'");
    if ($db->num_rows($check_secret) == 0) {
        // Si no existe, crear registro con valores por defecto y el campo específico
        $default_values = [
            'nombre' => '',
            'apodo' => '',
            'avatar1' => '',
            'avatar2' => '',
            'apariencia' => '',
            'personalidad' => '',
            'historia' => '',
            'extra' => '',
            'faccion' => 'Civil',
            'rango' => 'civil',
            'es_visible' => '0'
        ];
        $default_values[$field] = $value;
        
        $fields = implode('`, `', array_keys($default_values));
        $values = "'" . implode("', '", array_values($default_values)) . "'";
        
        $db->query("INSERT INTO `mybb_op_fichas_secret` (`fid`, `$fields`) VALUES ('$query_uid', $values)");
    } else {
        // Si existe, actualizar
        $db->query("UPDATE `mybb_op_fichas_secret` SET `$field`='$value' WHERE `fid`='$query_uid'");
    }
}

if ($cambiar_avatar1 != '') {
    $db->query(" UPDATE `mybb_op_fichas` SET `avatar3`='$cambiar_avatar1' WHERE `fid`='$query_uid'; ");
}

if ($cambiar_avatar2 != '') {
    $db->query(" UPDATE `mybb_op_fichas` SET `avatar1`='$cambiar_avatar2' WHERE `fid`='$query_uid'; ");
}

if ($cambiar_avatar1S1 != '') {
    updateOrInsertSecret($db, $query_uid, 'avatar1', $cambiar_avatar1S1);
}

if ($cambiar_avatar2S1 != '') {
    updateOrInsertSecret($db, $query_uid, 'avatar2', $cambiar_avatar2S1);
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

if ($cambiar_equipamiento != '') {
    $db->query(" UPDATE `mybb_op_fichas` SET `equipamiento`='$cambiar_equipamiento' WHERE `fid`='$query_uid'; ");
}

if ($cambiar_aparienciaS1 != '') {
    updateOrInsertSecret($db, $query_uid, 'apariencia', $cambiar_aparienciaS1);
}

if ($cambiar_personalidadS1 != '') {
    updateOrInsertSecret($db, $query_uid, 'personalidad', $cambiar_personalidadS1);
}

if ($cambiar_historiaS1 != '') {
    updateOrInsertSecret($db, $query_uid, 'historia', $cambiar_historiaS1);
}

if ($cambiar_extrasS1 != '') {
    updateOrInsertSecret($db, $query_uid, 'extra', $cambiar_extrasS1);
}

if ($cambiar_apodoS1 != '') {
    updateOrInsertSecret($db, $query_uid, 'apodo', $cambiar_apodoS1);
}

if ($cambiar_nombreS1 != '') {
    updateOrInsertSecret($db, $query_uid, 'nombre', $cambiar_nombreS1);
}

if ($cambiar_rango1 != '') {
    updateOrInsertSecret($db, $query_uid, 'rango', $cambiar_rango1);
}

if ($cambiar_faccionS1 != '') {
    updateOrInsertSecret($db, $query_uid, 'faccion', $cambiar_faccionS1);
}

if ($cambiar_visibleS1 != '') {
    updateOrInsertSecret($db, $query_uid, 'es_visible', $cambiar_visibleS1);
}

// Si se recibió una petición AJAX para actualizar datos, devolver respuesta y terminar
$ajax_fields = [
    'cambiar_avatar1', 'cambiar_avatar2', 'cambiar_avatar3', 'cambiar_avatar4', 'cambiar_avatar5',
    'cambiar_apariencia', 'cambiar_personalidad', 'cambiar_historia', 'cambiar_extras', 'cambiar_apodo',
    'cambiar_avatar1S1', 'cambiar_avatar2S1', 'cambiar_aparienciaS1', 'cambiar_personalidadS1', 
    'cambiar_historiaS1', 'cambiar_extrasS1', 'cambiar_apodoS1', 'cambiar_nombreS1', 
    'cambiar_rangoS1', 'cambiar_faccionS1', 'cambiar_visibleS1', 'cambiar_equipamiento'
];

foreach ($ajax_fields as $field) {
    if (!empty($_POST[$field])) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'success', 'message' => 'Datos actualizados correctamente']);
        exit();
    }
}

$is_owner = $mybb->user['uid'] == $mybb->get_input('uid');

$fileVersion = rand();

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



if ($edit_nombre != '' && $edit_imagen != '' && $edit_objeto_id != '') {


    $obj_custom = null;
    $inventario_custom = null;
    $objeto_custom_query = $db->query("SELECT * FROM mybb_op_objetos WHERE objeto_id='$edit_objeto_id'");
    while ($q = $db->fetch_array($objeto_custom_query)) {  $obj_custom = $q; }

    $inventario_custom_query = $db->query("SELECT * FROM mybb_op_inventario WHERE objeto_id='$edit_objeto_id' AND uid='$query_uid'; ");
    while ($q = $db->fetch_array($inventario_custom_query)) {  $inventario_custom = $q; }

    $count_id = 0;
    $objeto_count = $db->query("SELECT count(*) as count FROM mybb_op_objetos WHERE objeto_id LIKE '%$objeto_id-$query_uid%'");
    while ($q = $db->fetch_array($objeto_count)) { $count_id = intval($q['count']) + 1; }
    
    $new_obj_exists = false;

    $already_custom = false;

    if (strpos($edit_objeto_id, '-') != false) {
        $already_custom = true;
        $new_nombre = "$edit_objeto_id";
    } else {
        $new_nombre = "$edit_objeto_id-$query_uid-$count_id";
    }

    $categoria = $obj_custom['categoria']; 
    $subcategoria = $obj_custom['subcategoria'];
    $nombre = $obj_custom['nombre'];
    $tier = $obj_custom['tier'];
    $imagen_id = $obj_custom['imagen_id'];
    $imagen_avatar = $obj_custom['imagen_avatar'];
    $berries = $obj_custom['berries'];
    $cantidadMaxima = $obj_custom['cantidadMaxima'];
    $dano = $obj_custom['dano'];
    $efecto = $obj_custom['efecto'];
    $exclusivo = $obj_custom['exclusivo'];
    $espacios = $obj_custom['espacios'];
    $imagen = $obj_custom['imagen'];
    $desbloquear = $obj_custom['desbloquear'];
    $oficio = $obj_custom['oficio'];
    $nivel = $obj_custom['nivel'];
    $requisitos = $obj_custom['requisitos'];
    $escalado = $obj_custom['escalado'];
    $editable = '1';
    $custom = '1';
    $descripcion = $obj_custom['descripcion'];

    $autor = $inventario_custom['autor'];
    $autor_id = $inventario_custom['autor_id'];
    $obj_inv_oficios = $inventario_custom['oficios'];

    if ($already_custom) {
        $db->query(" UPDATE `mybb_op_inventario` SET `imagen`='$edit_imagen',`apodo`='$edit_nombre',`editado`='1' WHERE objeto_id='$edit_objeto_id' AND uid='$query_uid' ");

    
    } else {        
        $inventarioCantidad = intval($inventario_custom['cantidad']);
    
        if ($inventarioCantidad > 0) {
            $db->query(" INSERT INTO `mybb_op_objetos`(`objeto_id`, `categoria`, `subcategoria`, `nombre`, `tier`, `imagen_id`, `imagen_avatar`, `berries`, `cantidadMaxima`, `dano`, `efecto`, `exclusivo`, `espacios`, `imagen`, `desbloquear`, `oficio`, `nivel`, `requisitos`, `escalado`, `editable`, `custom`, `descripcion`) VALUES 
            ('$new_nombre','$categoria','$subcategoria','$nombre','$tier','$imagen_id','$imagen_avatar','$berries','$cantidadMaxima','$dano','$efecto','$exclusivo','$espacios','$imagen','$desbloquear','$oficio','$nivel','$requisitos','$escalado','$editable','$custom','$descripcion'); ");  
            $db->query(" INSERT INTO mybb_op_inventario(`objeto_id`, `uid`, `cantidad`, `imagen`, `apodo`, `especial`, `editado`) VALUES ('$new_nombre','$query_uid','1','$edit_imagen','$edit_nombre','1','1'); ");
    
            if ($inventarioCantidad > 1) {
                $nueva_cantidad = intval($inventario_custom['cantidad']) - 1;
                $db->query(" 
                    UPDATE `mybb_op_inventario` SET `cantidad`='$nueva_cantidad' WHERE objeto_id='$edit_objeto_id' AND uid='$query_uid'
                ");
            } else if ($inventarioCantidad == 1) {
                $db->query(" DELETE FROM `mybb_op_inventario` WHERE objeto_id='$edit_objeto_id' AND uid='$query_uid'; ");
            }
        }
    }


    
    return;
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

    $has_full_haki = false;
    $has_sin_oficio = false; // D024
    $has_estudioso = false; // V035
    $has_polivalente = false; // V036
    $has_erudito = false; // V028

    // $has_full_haki_query = $db->query(" SELECT * FROM `mybb_op_virtudes_usuarios` WHERE uid='$query_uid' AND virtud_id='V029'; "); 
    $has_sin_oficio_query = $db->query(" SELECT * FROM `mybb_op_virtudes_usuarios` WHERE uid='$query_uid' AND virtud_id='D024'; "); 
    $has_estudioso_query = $db->query(" SELECT * FROM `mybb_op_virtudes_usuarios` WHERE uid='$query_uid' AND virtud_id='V035'; "); 
    $has_polivalente_query = $db->query(" SELECT * FROM `mybb_op_virtudes_usuarios` WHERE uid='$query_uid' AND virtud_id='V036'; "); 
    $has_erudito_query = $db->query(" SELECT * FROM `mybb_op_virtudes_usuarios` WHERE uid='$query_uid' AND virtud_id='V028'; "); 

    // while ($q = $db->fetch_array($has_full_haki_query)) { $has_full_haki = true; }
    while ($q = $db->fetch_array($has_sin_oficio_query)) { $has_sin_oficio = true; }
    while ($q = $db->fetch_array($has_estudioso_query)) { $has_estudioso = true; }
    while ($q = $db->fetch_array($has_polivalente_query)) { $has_polivalente = true; }
    while ($q = $db->fetch_array($has_erudito_query)) { $has_erudito = true; }

    if ($ficha['camino'] == 'Haki') { $has_full_haki = true; }

    $oficio1 = $ficha['oficio1'];
    $oficios = json_decode($ficha['oficios']);

    $nivelOficio1 = $oficios->{$oficio1}->{'nivel'};

    $objeto_vender = $mybb->get_input('objeto'); 

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
        $akuma = null;

        if ($ficha['akuma'] != '') {

            $akuma_nombre = $ficha['akuma'];
            $akuma_subnombre = $ficha['akuma_subnombre'];

            $query_akuma = $db->query(" SELECT * FROM mybb_op_akumas WHERE nombre='$akuma_nombre' AND subnombre='$akuma_subnombre' ");

            while ($q = $db->fetch_array($query_akuma)) { $akuma = $q; $akuma_imagen = $q['imagen'];  }
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
    $ano_edad = 700 - $ficha['edad'];

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

    $query_pets = $db->query(" SELECT * FROM `mybb_op_npcs` WHERE npc_id LIKE '%$query_uid-PET%'; ");
    $query_npcs = $db->query(" SELECT * FROM `mybb_op_npcs` WHERE npc_id LIKE '%$query_uid-NPC%'; ");

    $virtudes = array();
    $virtudes_array = array();
    $defectos = array();
    $defectos_array = array();
    $npcs = array();
    $npcs_array = array();
    $mascotas = array();
    $mascotas_array = array();
    
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

    while ($q = $db->fetch_array($query_pets)) { 
        $npc_id = $q['npc_id'];
        $key = "$npc_id";
        if (!$mascotas[$key]) { $mascotas[$key] = array(); }
        array_push($mascotas[$key], $q);
        array_push($mascotas_array, $npc_id);
    }

    while ($q = $db->fetch_array($query_npcs)) { 
        $npc_id = $q['npc_id'];
        $key = "$npc_id";
        if (!$npcs[$key]) { $npcs[$key] = array(); }
        array_push($npcs[$key], $q);
        array_push($npcs_array, $npc_id);
    }

    $virtudes_array_json = json_encode($virtudes_array);
    $virtudes_json = json_encode($virtudes);

    $defectos_array_json = json_encode($defectos_array);
    $defectos_json = json_encode($defectos);

    $npcs_array_json = json_encode($npcs_array);
    $npcs_json = json_encode($npcs);

    $mascotas_array_json = json_encode($mascotas_array);
    $mascotas_json = json_encode($mascotas);

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
		$expMax = 30001;
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
        ORDER BY `mybb_op_tecnicas`.`tid`,`mybb_op_tecnicas`.`rama`
    ");

    $tec_aprendidas = array();
    $tec_aprendidas['todo'] = array();
    
    while ($tec_aprendida = $db->fetch_array($query_tec_aprendidas)) {
        $tec_aprendida['descripcion'] = nl2br($tec_aprendida['descripcion']);

        if ($tec_aprendida['estilo'] == 'Racial') {
            $key = $tec_aprendida['estilo'];
        } else {
            $key = $tec_aprendida['rama'];
        }


        if (!$tec_aprendidas[$key]) {
            $tec_aprendidas[$key] = array();
        }
        array_push($tec_aprendidas['todo'], $tec_aprendida);
        array_push($tec_aprendidas[$key], $tec_aprendida);
    }
    $tec_aprendidas_json = json_encode($tec_aprendidas);


    /* INVENTARIO */
    $oficios = json_decode($ficha['oficios']);
    $precioVentaPct = 2.00000001;

    if (isset($oficios->{'Mercader'})) {
        
        $precioVentaPct = 1.66666667;

        if (isset($oficios->{'Mercader'}->{'sub'}->{'Comerciante'})) {
            $caminoNivel = $oficios->{'Mercader'}->{'sub'}->{'Comerciante'};

            if ($caminoNivel == 1) { $precioVentaPct = 1.42857143; }
            if ($caminoNivel == 2) { $precioVentaPct = 1.25; }
            if ($caminoNivel == 3) { $precioVentaPct = 1.1111111117; }

        }
        
    }

    if ($accion == 'vender' && $objeto_vender && $is_same_user) {
        $has_objeto = false;
        $objeto_actual_query = $db->query("SELECT * FROM mybb_op_objetos WHERE objeto_id='$objeto_vender'");
        while ($q = $db->fetch_array($objeto_actual_query)) {  $objeto_actual = $q; }

        $inventario_actual = $db->query("SELECT * FROM mybb_op_inventario WHERE uid='$query_uid' AND objeto_id='$objeto_vender'");
        while ($q = $db->fetch_array($inventario_actual)) {
            $has_objeto = true;
            $cantidad = $q['cantidad'];
        }

        if ($has_objeto) {

            if (intval($cantidad) > 1) {
                $nueva_cantidad = intval($cantidad) - 1;
                $db->query(" 
                    UPDATE `mybb_op_inventario` SET `cantidad`='$nueva_cantidad' WHERE objeto_id='$objeto_vender' AND uid='$query_uid'
                ");
            } else if (intval($cantidad) == 1) {
                $db->query(" DELETE FROM `mybb_op_inventario` WHERE objeto_id='$objeto_vender' AND uid='$query_uid'; ");
            }    

            $berries_actuales = intval($ficha['berries']);
            $precioVenta = intval(intval($objeto_actual['berries']) / $precioVentaPct) + 1;
            $nuevos_berries = intval($berries_actuales + $precioVenta);
            $objeto_nombre = $objeto_actual['nombre'];

            log_audit($uid_user, $username, $query_uid, '[Venta]', "Vendido: $objeto_nombre ($objeto_vender): $berries_actuales->$nuevos_berries (Ganancia: $precioVenta).");
            // $db->query(" UPDATE `mybb_op_fichas` SET `berries`='$nuevos_berries' WHERE fid='$query_uid' ");
            log_audit_currency($uid, $username, $uid, '[Venta][Berries]', 'berries', $nuevos_berries);
            $log = "¡Has vendido $objeto_nombre por $precioVenta berries!\nTienes ahora $nuevos_berries berries\n($berries_actuales berries + $precioVenta berries)";

            echo("<script>alert(`$log`);window.location.href = 'https://onepiecegaiden.com/op/inventario.php';</script>");
        }
 
    }

    $query_inventario = $db->query("
        SELECT * FROM `mybb_op_objetos` 
        INNER JOIN `mybb_op_inventario` 
        ON `mybb_op_objetos`.`objeto_id`=`mybb_op_inventario`.`objeto_id` 
        WHERE `mybb_op_inventario`.`uid`='$query_uid'
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

    $ficha_secret1 = null;
    $ficha_secret1_json = '';
    
    $query_ficha_secret1 = $db->query("
        SELECT * FROM mybb_op_fichas_secret WHERE fid='$query_uid' AND secret_number='1'
    ");
    while ($q = $db->fetch_array($query_ficha_secret1)) {

        $q['historia1'] = nl2br($q['historia']);
        $q['apariencia1'] = nl2br($q['apariencia']);
        $q['personalidad1'] = nl2br($q['personalidad']);
        $q['extra1'] = nl2br($q['extra']);
        $q['avatar1'] = nl2br($q['avatar1']);
        $q['avatar2'] = nl2br($q['avatar2']);
        $ficha_secret1 = $q;
        $ficha_secret1_json = json_encode($q);
    }

    /* END INVENTARIO */

    /* HISTORIAL */
    $historial_kuro_array = array();
    $historial_nikas_array = array();
    $historial_berries_array = array();
    $historial_experiencia_array = array();
    $historial_puntos_oficio_array = array();
    
    $query_historial_kuro = $db->query("
        SELECT * FROM `mybb_op_audit_general` WHERE (
            categoria LIKE '%[Post]%' OR 
            categoria LIKE '%[Modificación de kuros]%' OR
            categoria LIKE '%[Tienda Kuros][Kuros]%'
        ) AND user_uid='$query_uid' ORDER BY `mybb_op_audit_general`.`id` DESC;
    ");
    
    $query_historial_experiencia = $db->query("
        SELECT * FROM `mybb_op_audit_general` WHERE (
            categoria LIKE '%[Post]%' OR 
            categoria LIKE '%[Modificación de experiencia]%' OR
            categoria LIKE '%[Recompensa][Experiencia]%' OR
            categoria LIKE '%[Entrenamiento][Experiencia]%' OR
            categoria LIKE '%[Cofre][Experiencia]%' OR
            categoria LIKE '%[Tienda Kuros][Experiencia]%'
        ) AND user_uid='$query_uid' ORDER BY `mybb_op_audit_general`.`id` DESC;
    ");
    
    $query_historial_nikas = $db->query("
        SELECT * FROM `mybb_op_audit_general` WHERE (
            categoria LIKE '%[Modificación de nikas]%' OR
            categoria LIKE '%[Recompensa][Nikas]%' OR
            categoria LIKE '%[Cofre][Nikas]%' OR
            categoria LIKE '%[Tienda Kuros][Nikas]%' OR
            categoria LIKE '%[Mejoras][Límite Nivel]%' OR
            categoria LIKE '%[Mejoras][Kenbun Mejora]%' OR
            categoria LIKE '%[Mejoras][Buso Mejora]%' OR
            categoria LIKE '%[Mejoras][Hao Mejora]%' OR
            categoria LIKE '%[Mejoras][Dominio Akuma Mejora]%' OR
            categoria LIKE '%[Mejoras][Estilo 2 Mejora]%' OR
            categoria LIKE '%[Mejoras][Estilo 3 Mejora]%' OR
            categoria LIKE '%[Mejoras][Estilo 4 Mejora]%' OR
            categoria LIKE '%[Mejoras][Oficio Mejora]%' OR
            categoria LIKE '%[Mejoras][Belica Mejora]%' OR
            categoria LIKE '%[Mejoras][Oficio Espe Mejora]%' OR
            categoria LIKE '%[Mejoras][Belica Espe Mejora]%' OR
            categoria LIKE '%[Creación][Nikas]%'
        ) AND user_uid='$query_uid' ORDER BY `mybb_op_audit_general`.`id` DESC;
    ");
    
    $query_historial_berries = $db->query("
        SELECT * FROM `mybb_op_audit_general` WHERE (
            categoria LIKE '%[Modificación de berries]%' OR
            categoria LIKE '%[Recompensa][Berries]%' OR
            categoria LIKE '%[Tienda][Berries]%' OR
            categoria LIKE '%[Cofre][Berries]%' OR
            categoria LIKE '%[Tienda Kuros][Berries]%' OR
            categoria LIKE '%[Venta][Berries]%' OR
            categoria LIKE '%[Intercambio][Berries]%' OR
            categoria LIKE '%[Crafteo][Berries]%'
        ) AND user_uid='$query_uid' ORDER BY `mybb_op_audit_general`.`id` DESC;
    ");
    
    $query_historial_puntos_oficio = $db->query("
        SELECT * FROM `mybb_op_audit_general` WHERE (
            categoria LIKE '%[Modificación de puntos de oficio]%' OR
            categoria LIKE '%[Entrenamiento][Puntos oficio]%' OR
            categoria LIKE '%[Tienda Kuros][Puntos oficio]%' OR
            categoria LIKE '%[Creación][Puntos oficio]%'
        ) AND user_uid='$query_uid' ORDER BY `mybb_op_audit_general`.`id` DESC;
    ");

    while ($q = $db->fetch_array($query_historial_kuro)) { array_push($historial_kuro_array, $q); }
    while ($q = $db->fetch_array($query_historial_experiencia)) { array_push($historial_experiencia_array, $q); }
    while ($q = $db->fetch_array($query_historial_nikas)) { array_push($historial_nikas_array, $q); }
    while ($q = $db->fetch_array($query_historial_berries)) { array_push($historial_berries_array, $q); }
    while ($q = $db->fetch_array($query_historial_puntos_oficio)) { array_push($historial_puntos_oficio_array, $q); }

    $historial_kuro_json = json_encode($historial_kuro_array);
    $historial_experiencia_json = json_encode($historial_experiencia_array);
    $historial_nikas_json = json_encode($historial_nikas_array);
    $historial_berries_json = json_encode($historial_berries_array);
    $historial_puntos_oficio_json = json_encode($historial_puntos_oficio_array);

    /* END HISTORIAL */

    eval("\$op_ficha_script = \"".$templates->get("op_ficha_script3")."\";");
    eval("\$op_ficha_script2 = \"".$templates->get("op_ficha_script4")."\";");
    eval("\$op_ficha_css = \"".$templates->get("op_ficha_css3")."\";");
    eval("\$op_ficha_portada = \"".$templates->get("op_ficha_portada")."\";");
    eval("\$op_ficha_biografia = \"".$templates->get("op_ficha_biografia")."\";");
    eval("\$op_ficha_belico = \"".$templates->get("op_ficha_belico")."\";");
    eval("\$op_ficha_tecnicas = \"".$templates->get("op_ficha_tecnicas")."\";");
    eval("\$op_ficha_tecnicas2 = \"".$templates->get("op_ficha_tecnicas2")."\";");
    eval("\$op_ficha_inventario = \"".$templates->get("op_ficha_inventario")."\";");
    eval("\$op_ficha_secreto = \"".$templates->get("op_ficha_secreto")."\";");

    eval("\$page = \"".$templates->get("op_ficha3")."\";");
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

