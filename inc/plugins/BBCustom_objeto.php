<?php
/**
 * BBCustom Objeto Plugin
 * Procesa BBCode [objeto=ID]
 */

if (!defined("IN_MYBB")) {
	die("Direct initialization of this file is not allowed.");
}

// Cargar librería de spoilers
require_once MYBB_ROOT . "inc/plugins/lib/spoiler.php";

// Hook para procesar DESPUÉS de que MyBB termine todo el procesamiento
$plugins->add_hook("postbit", "BBCustom_objeto_run");

function BBCustom_objeto_info()
{
	return array(
		"name"			=> "Objeto BBCode",
		"description"	=> "BBCode [objeto=ID] modular para mostrar objetos/items",
		"website"		=> "",
		"author"		=> "Cascabelles",
		"authorsite"	=> "",
		"version"		=> "1.0",
		"codename"		=> "BBCustom_objeto",
		"compatibility"	=> "*"
	);
}

function BBCustom_objeto_activate() {}
function BBCustom_objeto_deactivate() {}

function BBCustom_objeto_run(&$post)
{
	global $db;
	
	static $processed_pids = array();
	$current_pid = isset($post['pid']) ? $post['pid'] : md5($post['message']);
	
	if (in_array($current_pid, $processed_pids)) {
		return;
	}
	
	$message = &$post['message'];
	
	while (preg_match('#\[objeto=(.*?)\]#si', $message, $matches))
	{
		$objeto_id = $matches[1];
		
		// Buscar objeto en base de datos
		$query = $db->query("SELECT * FROM mybb_op_objetos WHERE objeto_id='$objeto_id'");
		$objeto = $db->fetch_array($query);
		
		if (!$objeto) {
			$message = preg_replace('#\[objeto=' . preg_quote($objeto_id, '#') . '\]#si', 
				'<div style="background: #f44336; color: white; padding: 10px; border-radius: 5px;">Objeto no encontrado: ' . $objeto_id . '</div>', 
				$message, 1);
			continue;
		}
		
		// Generar HTML
		$html = BBCustom_objeto_generate_html($objeto, $objeto_id);
		$spoiler = create_info_spoiler($objeto['nombre'], $html);
		
		$message = preg_replace('#\[objeto=' . preg_quote($objeto_id, '#') . '\]#si', $spoiler, $message, 1);
	}
	
	$processed_pids[] = $current_pid;
}

function BBCustom_objeto_generate_html($obj, $objeto_id)
{
	$nombre = $obj['nombre'];
	$apodo = $obj['apodo'];
	$descripcion = $obj['descripcion'];
	$tier = $obj['tier'];
	$imagen_id = $obj['imagen_id'];
	$subcategoria = $obj['subcategoria'];
	
	// Campos opcionales
	$requisitos = $obj['requisitos'];
	$escalado = $obj['escalado'];
	$dano = $obj['dano'];
	$efecto = $obj['efecto'];
	$espacios = isset($obj['espacios']) ? intval($obj['espacios']) : 1;
	
	// Determinar color del tier
	$color_tier = '#faa500'; // Por defecto (Tier 6+)
	switch($imagen_id) {
		case '1': $color_tier = '#808080'; break; // Gris
		case '2': $color_tier = '#4dfe45'; break; // Verde
		case '3': $color_tier = '#457bfe'; break; // Azul
		case '4': $color_tier = '#cf44ff'; break; // Púrpura
		case '5': $color_tier = '#febb46'; break; // Dorado
	}
	
	// Secciones opcionales
	$requisitos_html = $requisitos ? "<div style='background: rgba(255,193,7,0.2); padding: 10px; border-radius: 8px; border-left: 4px solid #ffc107; margin-bottom: 10px;'><strong>Requisitos:</strong> $requisitos</div>" : '';
	$escalado_html = $escalado ? "<div style='background: rgba(33,150,243,0.2); padding: 10px; border-radius: 8px; border-left: 4px solid #2196f3; margin-bottom: 10px;'><strong>Escalado:</strong> $escalado</div>" : '';
	$dano_html = $dano ? "<div style='background: rgba(244,67,54,0.2); padding: 10px; border-radius: 8px; border-left: 4px solid #f44336; margin-bottom: 10px;'><strong>Daño:</strong> $dano</div>" : '';
	$efecto_html = $efecto ? "<div style='background: rgba(156,39,176,0.2); padding: 10px; border-radius: 8px; border-left: 4px solid #9c27b0; margin-bottom: 10px;'><strong>Efecto:</strong> $efecto</div>" : '';
	
	// Mostrar apodo si existe
	$nombre_display = $apodo ? "$apodo<br><span style='font-size: 14px; color: rgba(255,255,255,0.7);'>($nombre)</span>" : $nombre;
	
	return "
		<div style='border-radius: 15px; padding: 20px; margin: 10px 0; box-shadow: 0 8px 32px rgba(0,0,0,0.3); color: white; background: linear-gradient(135deg, #2c2c2c, #1a1a1a); border: 3px solid $color_tier;'>
			<div style='text-align: center; margin-bottom: 20px;'>
				<h3 style='margin: 0; font-size: 22px; font-weight: bold; color: $color_tier; text-shadow: 2px 2px 4px black;'>$nombre_display</h3>
				<div style='margin-top: 8px; font-size: 14px; display: flex; justify-content: center; gap: 15px; flex-wrap: wrap;'>
					<span style='background: rgba(255,255,255,0.1); padding: 5px 12px; border-radius: 15px; border: 1px solid $color_tier;'>ID: $objeto_id</span>
					<span style='background: rgba(255,255,255,0.1); padding: 5px 12px; border-radius: 15px; border: 1px solid $color_tier;'>$subcategoria</span>
					<span style='background: $color_tier; color: black; padding: 5px 12px; border-radius: 15px; font-weight: bold;'>Tier $tier</span>
					<span style='background: rgba(255,255,255,0.1); padding: 5px 12px; border-radius: 15px; border: 1px solid $color_tier;'>Espacios: $espacios</span>
				</div>
			</div>
			
			<div style='background: rgba(0,0,0,0.3); padding: 15px; border-radius: 10px; margin-bottom: 15px;'>
				<h4 style='margin: 0 0 10px 0; color: $color_tier; font-size: 16px;'>📝 Descripción</h4>
				<div style='line-height: 1.6; font-size: 14px; text-align: justify;'>$descripcion</div>
			</div>
			
			$requisitos_html
			$escalado_html
			$dano_html
			$efecto_html
		</div>
	";
}
