<?php
/**
 * BBCustom NPC Plugin
 * Procesa BBCode [npc=ID-NPC] o [npc=URL]
 */

if (!defined("IN_MYBB")) {
	die("Direct initialization of this file is not allowed.");
}

// Cargar librería de spoilers
require_once MYBB_ROOT . "inc/plugins/lib/spoiler.php";

// Hook para procesar DESPUÉS de que MyBB termine todo el procesamiento
$plugins->add_hook("postbit", "BBCustom_npc_run");

function BBCustom_npc_info()
{
	return array(
		"name"			=> "NPC BBCode",
		"description"	=> "BBCode [npc=ID] modular para mostrar NPCs",
		"website"		=> "",
		"author"		=> "Cascabelles",
		"authorsite"	=> "",
		"version"		=> "1.0",
		"codename"		=> "BBCustom_npc",
		"compatibility"	=> "*"
	);
}

function BBCustom_npc_activate() {}
function BBCustom_npc_deactivate() {}

function BBCustom_npc_run(&$post)
{
	global $db;
	
	static $processed_pids = array();
	$current_pid = isset($post['pid']) ? $post['pid'] : md5($post['message']);
	
	if (in_array($current_pid, $processed_pids)) {
		return;
	}
	
	$message = &$post['message'];
	
	while (preg_match('#\[npc=(.*?)\]#si', $message, $matches))
	{
		$npc_parameter = $matches[1];
		$npc_html = '';
		
		// Verificar si es URL o ID de base de datos
		if (!filter_var($npc_parameter, FILTER_VALIDATE_URL) && !strpos($npc_parameter, 'generador')) {
			// Es ID de base de datos
			$npc_html = BBCustom_npc_from_db($db, $npc_parameter);
		} else {
			// Es URL del generador
			$npc_html = BBCustom_npc_from_url($db, $npc_parameter);
		}
		
		if (empty($npc_html)) {
			$npc_html = "<div style='background: #f44336; color: white; padding: 10px; border-radius: 5px;'>NPC no encontrado: $npc_parameter</div>";
		}
		
		$message = preg_replace('#\[npc=' . preg_quote($npc_parameter, '#') . '\]#si', $npc_html, $message, 1);
	}
	
	$processed_pids[] = $current_pid;
}

function BBCustom_npc_from_db($db, $npc_id)
{
	$query = $db->query("SELECT * FROM mybb_op_npcs WHERE npc_id='$npc_id'");
	$npc = $db->fetch_array($query);
	
	if (!$npc) {
		return '';
	}
	
	// Procesar campos ocultos (???)
	$fields = ['nombre', 'etiqueta', 'extra', 'vitalidad', 'energia', 'haki', 'fuerza', 'resistencia', 'destreza', 'agilidad', 'voluntad', 'reflejos', 'punteria'];
	foreach ($fields as $field) {
		if (substr($npc[$field], 0, 3) == "???") {
			$npc[$field] = '???';
		}
	}
	
	// Formatear vitalidad, energía y haki si no son ???
	if ($npc['vitalidad'] != '???') $npc['vitalidad'] .= '';
	if ($npc['energia'] != '???') $npc['energia'] .= '';
	if ($npc['haki'] != '???') $npc['haki'] .= '';
	
	$content = "
		<div style='border: 2px solid #8B4513; border-radius: 10px; background: linear-gradient(135deg, #2c2c2c, #1a1a1a); padding: 15px; margin: 10px 0; color: white; box-shadow: 0 4px 12px rgba(0,0,0,0.5);'>
			<div style='text-align: center; margin-bottom: 15px;'>
				<h3 style='color: #ff7b00; font-size: 24px; margin: 0; text-shadow: 2px 2px 4px black;'>{$npc['nombre']}</h3>
				" . (!empty($npc['etiqueta']) && $npc['etiqueta'] != '???' ? "<div style='color: #cccccc; font-size: 14px; font-style: italic; margin-top: 5px;'>{$npc['etiqueta']}</div>" : "") . "
			</div>
			
			<div style='display: flex; justify-content: space-between; margin-bottom: 15px;'>
				<div style='width: 48%;'>
					<div style='background: rgba(255,123,0,0.2); padding: 10px; border-radius: 5px; border-left: 4px solid #ff7b00; margin-bottom: 10px;'>
						<h4 style='margin: 0 0 8px 0; color: #ff7b00; font-size: 16px;'>ESTADÍSTICAS</h4>
						<div style='display: grid; grid-template-columns: 1fr 1fr; gap: 5px; font-size: 13px;'>
							<div><strong>FUE:</strong> {$npc['fuerza']}</div>
							<div><strong>RES:</strong> {$npc['resistencia']}</div>
							<div><strong>DES:</strong> {$npc['destreza']}</div>
							<div><strong>AGI:</strong> {$npc['agilidad']}</div>
							<div><strong>VOL:</strong> {$npc['voluntad']}</div>
							<div><strong>REF:</strong> {$npc['reflejos']}</div>
							<div style='grid-column: span 2;'><strong>PUN:</strong> {$npc['punteria']}</div>
						</div>
					</div>
				</div>
				
				<div style='width: 48%;'>
					<div style='background: rgba(0,150,136,0.2); padding: 10px; border-radius: 5px; border-left: 4px solid #009688; margin-bottom: 5px;'>
						<h4 style='margin: 0 0 5px 0; color: #009688; font-size: 14px;'>VITALIDAD</h4>
						<div style='font-size: 13px; text-align: center;'>{$npc['vitalidad']}</div>
					</div>
					<div style='background: rgba(255,152,0,0.2); padding: 10px; border-radius: 5px; border-left: 4px solid #ff9800; margin-bottom: 5px;'>
						<h4 style='margin: 0 0 5px 0; color: #ff9800; font-size: 14px;'>ENERGÍA</h4>
						<div style='font-size: 13px; text-align: center;'>{$npc['energia']}</div>
					</div>
					<div style='background: rgba(33,150,243,0.2); padding: 10px; border-radius: 5px; border-left: 4px solid #2196f3;'>
						<h4 style='margin: 0 0 5px 0; color: #2196f3; font-size: 14px;'>HAKI</h4>
						<div style='font-size: 13px; text-align: center;'>{$npc['haki']}</div>
					</div>
				</div>
			</div>
			
			" . (!empty($npc['extra']) && $npc['extra'] != '???' ? "
			<div style='background: rgba(139,69,19,0.2); padding: 10px; border-radius: 5px; border-left: 4px solid #8B4513;'>
				<h4 style='margin: 0 0 8px 0; color: #8B4513; font-size: 16px;'>INFORMACIÓN ADICIONAL</h4>
				<div style='font-size: 13px; line-height: 1.4;'>{$npc['extra']}</div>
			</div>
			" : "") . "
		</div>
	";
	
	return create_npc_spoiler($npc['nombre'], $content);
}

function BBCustom_npc_from_url($db, $url)
{
	// Extraer datos de la URL
	$url_components = parse_url($url);
	$query_params = [];
	
	if (isset($url_components['fragment'])) {
		$fragment_parts = explode('?', $url_components['fragment']);
		if (count($fragment_parts) > 1) {
			parse_str($fragment_parts[1], $query_params);
		}
	} elseif (isset($url_components['query'])) {
		parse_str($url_components['query'], $query_params);
	}
	
	if (!isset($query_params['data']) || empty($query_params['data'])) {
		return '';
	}
	
	// Decodificar datos (Base64 + URL encode)
	$encoded_data = $query_params['data'];
	$url_decoded = urldecode($encoded_data);
	$base64_decoded = base64_decode($url_decoded);
	
	if ($base64_decoded === false) {
		return '';
	}
	
	$npc_data = json_decode($base64_decoded, true);
	if (!$npc_data || !is_array($npc_data)) {
		return '';
	}
	
	// Extraer datos del NPC
	$nombre = isset($npc_data['nombre']) ? htmlspecialchars($npc_data['nombre']) : 'NPC Sin Nombre';
	$nivel = isset($npc_data['nivel']) ? intval($npc_data['nivel']) : 1;
	$oficio = isset($npc_data['oficio']) ? htmlspecialchars($npc_data['oficio']) : 'Navegante';
	$disciplina = isset($npc_data['disciplina']) ? htmlspecialchars($npc_data['disciplina']) : 'Combatiente';
	
	// Estadísticas
	$stats = isset($npc_data['estadisticas']) ? $npc_data['estadisticas'] : [];
	$fue = isset($stats['FUE']) ? intval($stats['FUE']) : 0;
	$res = isset($stats['RES']) ? intval($stats['RES']) : 0;
	$des = isset($stats['DES']) ? intval($stats['DES']) : 0;
	$pun = isset($stats['PUN']) ? intval($stats['PUN']) : 0;
	$agi = isset($stats['AGI']) ? intval($stats['AGI']) : 0;
	$ref = isset($stats['REF']) ? intval($stats['REF']) : 0;
	$vol = isset($stats['VOL']) ? intval($stats['VOL']) : 0;
	
	// Calcular recursos
	$vida = ($agi * 3) + ($des * 4) + ($fue * 6) + ($pun * 1) + ($ref * 1) + ($res * 15) + ($vol * 1);
	$energia = ($agi * 4) + ($des * 3) + ($fue * 1) + ($pun * 6) + ($ref * 1) + ($res * 3) + ($vol * 1);
	$haki = ($vol * 10);
	
	// Armas equipadas
	$armas_html = BBCustom_npc_get_armas($db, isset($npc_data['armas']) ? $npc_data['armas'] : []);
	
	// Técnica seleccionada
	$tecnica_html = BBCustom_npc_get_tecnica($db, isset($npc_data['tecnica']) ? $npc_data['tecnica'] : '');
	
	// Virtudes y defectos
	$virtudes_html = BBCustom_npc_get_virtudes($db, isset($npc_data['virtudes']) ? $npc_data['virtudes'] : [], isset($npc_data['defectos']) ? $npc_data['defectos'] : []);
	
	// Descripción
	$descripcion_html = BBCustom_npc_get_descripcion($npc_data);
	
	$content = "
		<div style='border-radius: 15px; padding: 20px; margin: 10px 0; box-shadow: 0 8px 32px rgba(0,0,0,0.3); color: white; background: linear-gradient(135deg, #1a1a2e, #16213e, #0f3460); border: 2px solid #ffd700;'>
			<div style='text-align: center; margin-bottom: 20px;'>
				<div style='margin-top: 8px; font-size: 16px; display: flex; justify-content: center; gap: 20px; flex-wrap: wrap;'>
					<span style='background: rgba(255,215,0,0.2); padding: 6px 15px; border-radius: 20px; font-weight: bold; border: 1px solid #ffd700;'>Nivel $nivel</span>
					<span style='background: rgba(70,130,180,0.2); padding: 6px 15px; border-radius: 20px; font-weight: bold; border: 1px solid #4682b4;'>$oficio</span>
					<span style='background: rgba(220,20,60,0.2); padding: 6px 15px; border-radius: 20px; font-weight: bold; border: 1px solid #dc143c;'>$disciplina</span>
				</div>
			</div>
			
			<div style='display: grid; grid-template-columns: repeat(auto-fit, minmax(90px, 1fr)); gap: 12px; margin: 25px 0; text-align: center;'>
				<div style='background: rgba(255,255,255,0.1); border: 2px solid #ccc; border-radius: 10px; padding: 12px;'><div style='font-size: 20px; font-weight: bold;'>$fue</div><div style='font-size: 11px; font-weight: bold;'>FUE</div></div>
				<div style='background: rgba(255,255,255,0.1); border: 2px solid #ccc; border-radius: 10px; padding: 12px;'><div style='font-size: 20px; font-weight: bold;'>$res</div><div style='font-size: 11px; font-weight: bold;'>RES</div></div>
				<div style='background: rgba(255,255,255,0.1); border: 2px solid #ccc; border-radius: 10px; padding: 12px;'><div style='font-size: 20px; font-weight: bold;'>$des</div><div style='font-size: 11px; font-weight: bold;'>DES</div></div>
				<div style='background: rgba(255,255,255,0.1); border: 2px solid #ccc; border-radius: 10px; padding: 12px;'><div style='font-size: 20px; font-weight: bold;'>$pun</div><div style='font-size: 11px; font-weight: bold;'>PUN</div></div>
				<div style='background: rgba(255,255,255,0.1); border: 2px solid #ccc; border-radius: 10px; padding: 12px;'><div style='font-size: 20px; font-weight: bold;'>$agi</div><div style='font-size: 11px; font-weight: bold;'>AGI</div></div>
				<div style='background: rgba(255,255,255,0.1); border: 2px solid #ccc; border-radius: 10px; padding: 12px;'><div style='font-size: 20px; font-weight: bold;'>$ref</div><div style='font-size: 11px; font-weight: bold;'>REF</div></div>
				<div style='background: rgba(255,255,255,0.1); border: 2px solid #ccc; border-radius: 10px; padding: 12px;'><div style='font-size: 20px; font-weight: bold;'>$vol</div><div style='font-size: 11px; font-weight: bold;'>VOL</div></div>
			</div>
			
			<div style='margin: 25px 0; display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px;'>
				<div style='text-align: center; background: rgba(0,0,0,0.2); padding: 15px; border-radius: 12px; border: 2px solid #ccc;'>
					<div style='font-size: 14px; font-weight: bold; margin-bottom: 8px;'>❤️ VIDA</div>
					<div style='font-size: 12px; font-weight: bold;'>$vida</div>
				</div>
				<div style='text-align: center; background: rgba(0,0,0,0.2); padding: 15px; border-radius: 12px; border: 2px solid #ccc;'>
					<div style='font-size: 14px; font-weight: bold; margin-bottom: 8px;'>⚡ ENERGÍA</div>
					<div style='font-size: 12px; font-weight: bold;'>$energia</div>
				</div>
				<div style='text-align: center; background: rgba(0,0,0,0.2); padding: 15px; border-radius: 12px; border: 2px solid #ccc;'>
					<div style='font-size: 14px; font-weight: bold; margin-bottom: 8px;'>🔮 HAKI</div>
					<div style='font-size: 12px; font-weight: bold;'>$haki</div>
				</div>
			</div>
			
			$armas_html
			$tecnica_html
			$virtudes_html
			$descripcion_html
		</div>
	";
	
	return create_npc_spoiler($nombre, $content);
}

function BBCustom_npc_get_armas($db, $armas_data)
{
	if (empty($armas_data)) {
		return '';
	}
	
	$html = "<div style='margin: 20px 0;'><h4 style='color: #ffd700; font-size: 16px; margin-bottom: 10px;'>⚔️ ARMAS</h4>";
	$armas_nombres = ['Principal', 'Secundaria', 'Terciaria'];
	
	for ($i = 0; $i < 3; $i++) {
		if (isset($armas_data[$i]) && !empty($armas_data[$i])) {
			$arma_id = $armas_data[$i];
			$query = $db->query("SELECT * FROM mybb_op_objetos WHERE objeto_id='$arma_id'");
			
			if ($arma = $db->fetch_array($query)) {
				$nombre = !empty($arma['apodo']) ? $arma['apodo'] : $arma['nombre'];
				$tier = $arma['tier'];
				$dano = $arma['dano'];
				
				$color = '#808080';
				switch($tier) {
					case '2': $color = '#4dfe45'; break;
					case '3': $color = '#457bfe'; break;
					case '4': $color = '#cf44ff'; break;
					case '5': $color = '#febb46'; break;
					default: if (intval($tier) >= 6) { $color = '#faa500'; } break;
				}
				
				$html .= "<div style='margin: 5px 0; padding: 8px; background: rgba(255,255,255,0.1); border: 2px solid $color; border-radius: 8px;'>
					<div style='font-weight: bold; color: $color;'>{$armas_nombres[$i]}: $nombre</div>
					<div style='font-size: 12px; color: rgba(255,255,255,0.8);'>Tier $tier | $dano</div>
				</div>";
			}
		}
	}
	
	return $html . "</div>";
}

function BBCustom_npc_get_tecnica($db, $tecnica_id)
{
	if (empty($tecnica_id)) {
		return '';
	}
	
	$query = $db->query("SELECT * FROM mybb_op_tecnicas WHERE tid='$tecnica_id'");
	if ($tecnica = $db->fetch_array($query)) {
		return "<div style='margin: 20px 0;'>
			<h4 style='color: #ffd700; font-size: 16px; margin-bottom: 10px;'>✨ TÉCNICA</h4>
			<div style='background: rgba(138, 43, 226, 0.2); padding: 8px; border-radius: 8px; border: 1px solid #8a2be2;'>
				<div style='font-weight: bold; color: #dda0dd;'>{$tecnica['nombre']}</div>
				<div style='font-size: 12px; color: rgba(255,255,255,0.8);'>{$tecnica['clase']} | Tier {$tecnica['tier']}</div>
			</div>
		</div>";
	}
	
	return '';
}

function BBCustom_npc_get_virtudes($db, $virtudes_data, $defectos_data)
{
	$html = '';
	
	foreach ($virtudes_data as $v_id) {
		if (empty($v_id) || $v_id === "0") continue;
		if (is_numeric($v_id)) {
			$v_id = 'V' . str_pad($v_id, 3, '0', STR_PAD_LEFT);
		}
		
		$query = $db->query("SELECT * FROM mybb_op_virtudes WHERE virtud_id='$v_id'");
		if ($v = $db->fetch_array($query)) {
			$html .= "<span style='color: #008e02; font-weight: bold;'>{$v['nombre']}</span>: {$v['descripcion']}<br>";
		}
	}
	
	foreach ($defectos_data as $d_id) {
		if (empty($d_id) || $d_id === "0") continue;
		if (is_numeric($d_id)) {
			$d_id = 'D' . str_pad($d_id, 3, '0', STR_PAD_LEFT);
		}
		
		$query = $db->query("SELECT * FROM mybb_op_virtudes WHERE virtud_id='$d_id'");
		if ($d = $db->fetch_array($query)) {
			$html .= "<span style='color: #c10300; font-weight: bold;'>{$d['nombre']}</span>: {$d['descripcion']}<br>";
		}
	}
	
	if (empty($html)) {
		return '';
	}
	
	return "<div style='margin: 20px 0;'>
		<h4 style='color: #ffd700; font-size: 16px; margin-bottom: 10px;'>✨ VIRTUDES Y DEFECTOS</h4>
		<div style='background: rgba(0,0,0,0.2); padding: 10px; border-radius: 8px;'>$html</div>
	</div>";
}

function BBCustom_npc_get_descripcion($npc_data)
{
	$apariencia = isset($npc_data['apariencia']) ? nl2br(htmlspecialchars($npc_data['apariencia'])) : '';
	$personalidad = isset($npc_data['personalidad']) ? nl2br(htmlspecialchars($npc_data['personalidad'])) : '';
	$historia = isset($npc_data['historia']) ? nl2br(htmlspecialchars($npc_data['historia'])) : '';
	
	$html = '';
	if (!empty($apariencia)) {
		$html .= "<div style='margin-bottom: 15px;'><strong style='color: #4a90e2;'>Apariencia:</strong><br>$apariencia</div>";
	}
	if (!empty($personalidad)) {
		$html .= "<div style='margin-bottom: 15px;'><strong style='color: #4a90e2;'>Personalidad:</strong><br>$personalidad</div>";
	}
	if (!empty($historia)) {
		$html .= "<div style='margin-bottom: 15px;'><strong style='color: #4a90e2;'>Historia:</strong><br>$historia</div>";
	}
	
	if (empty($html)) {
		return '';
	}
	
	return "<div style='margin: 20px 0;'>
		<h4 style='color: #ffd700; font-size: 16px; margin-bottom: 10px;'>📖 DESCRIPCIÓN</h4>
		<div style='background: rgba(0,0,0,0.2); padding: 10px; border-radius: 8px; font-size: 13px;'>$html</div>
	</div>";
}
