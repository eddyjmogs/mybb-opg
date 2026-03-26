<?php
if(!defined("IN_MYBB"))
{
	die("Direct initialization of this file is not allowed.");
}

// Función de logging temprana (definida antes de cualquier require)
function BBCustom_ficha_log_error($message, $context = array())
{
	$log_dir = MYBB_ROOT . "inc/plugins/logs";
	$log_file = $log_dir . "/error_bb_ficha.log";
	
	if (!is_dir($log_dir)) {
		@mkdir($log_dir, 0755, true);
	}
	
	$timestamp = date('Y-m-d H:i:s');
	$context_str = empty($context) ? '' : ' | Context: ' . json_encode($context);
	$log_line = "[$timestamp] $message$context_str\n";
	
	@file_put_contents($log_file, $log_line, FILE_APPEND | LOCK_EX);
	
	if (file_exists($log_file) && filesize($log_file) > 5 * 1024 * 1024) {
		$backup_file = $log_dir . "/error_bb_ficha_" . date('Ymd_His') . ".log";
		@rename($log_file, $backup_file);
	}
}

// Log de carga del plugin
BBCustom_ficha_log_error("Plugin BBCustom_ficha iniciando carga");

// Cargar librería de spoilers reutilizable
$spoiler_lib = MYBB_ROOT."inc/plugins/lib/spoiler.php";
if (file_exists($spoiler_lib)) {
	require_once $spoiler_lib;
	BBCustom_ficha_log_error("Librería spoiler.php cargada correctamente");
} else {
	BBCustom_ficha_log_error("ERROR: No se encontró librería spoiler.php", array('path' => $spoiler_lib));
}

// Hook para procesar DESPUÉS de que MyBB termine todo el procesamiento
$plugins->add_hook("postbit", "BBCustom_ficha_run");

BBCustom_ficha_log_error("Hooks registrados correctamente");

function BBCustom_ficha_info()
{
	return array(
		"name"			=> "Ficha BBCode",
		"description"	=> "BBCode [ficha] modular",
		"website"		=> "",
		"author"		=> "Cascabelles",
		"authorsite"	=> "",
		"version"		=> "1.0",
		"codename"		=> "BBCustom_ficha",
		"compatibility"	=> "*"
	);
}

function BBCustom_ficha_activate()
{
	BBCustom_ficha_log_error("Plugin BBCustom_ficha ACTIVADO");
}

function BBCustom_ficha_deactivate()
{
	BBCustom_ficha_log_error("Plugin BBCustom_ficha DESACTIVADO");
}

function BBCustom_ficha_run(&$post)
{
	global $db, $mybb;
	
	// Evitar procesar si ya fue procesado
	static $processed_pids = array();
	$current_pid = isset($post['pid']) ? $post['pid'] : md5($post['message']);
	
	if (in_array($current_pid, $processed_pids)) {
		return;
	}
	
	// Trabajar con la referencia al mensaje del post
	$message = &$post['message'];
	
	// Log del mensaje antes de procesar
	BBCustom_ficha_log_error("BBCustom_ficha_run INVOCADO (postbit)", array(
		'message_length' => strlen($message),
		'message_preview' => substr($message, 0, 200),
			'tiene_ficha_bbcode' => preg_match('#\[ficha(?:=(?:\d+|\d{2}/\d{2}/\d{4}))?\]#si', $message) ? 'SI' : 'NO',
		'post_uid' => isset($post['uid']) ? $post['uid'] : 'N/A',
		'post_tid' => isset($post['tid']) ? $post['tid'] : 'N/A',
		'post_pid' => isset($post['pid']) ? $post['pid'] : 'N/A'
	));
	
	while(preg_match('#\[ficha(?:=(\d+|\d{2}/\d{2}/\d{4}))?\]#si', $message, $matches))
	{
		try {
			$uid = $post['uid'];
			$tid = $post['tid'];
			$pid = $post['pid'];
			$param = isset($matches[1]) && $matches[1] !== '' ? $matches[1] : null;

			// Detectar si el parámetro es una fecha (DD/MM/YYYY) o un nivel (número)
			$nivel_fijo = null;
			$fecha_fija = null;
			if ($param !== null) {
				if (preg_match('#^\d{2}/\d{2}/\d{4}$#', $param)) {
					// Convertir DD/MM/YYYY → YYYY-MM-DD
					$partes = explode('/', $param);
					$fecha_fija = $partes[2] . '-' . $partes[1] . '-' . $partes[0];
				} else {
					$nivel_fijo = intval($param);
				}
			}
			
			// Log de inicio
			BBCustom_ficha_log_error("Iniciando procesamiento [ficha]", array(
				'uid' => $uid,
				'tid' => $tid,
				'pid' => $pid,
				'nivel_fijo' => $nivel_fijo,
				'fecha_fija' => $fecha_fija,
				'match_raw' => $matches[0]
			));
			
			// Obtener ficha del thread o ficha general
			$ficha_data = BBCustom_ficha_get_data($db, $uid, $tid, $pid, $nivel_fijo, $fecha_fija);
			
			if (!$ficha_data) {
				BBCustom_ficha_log_error("No se encontró ficha", array(
					'uid' => $uid,
					'tid' => $tid,
					'nivel_fijo' => $nivel_fijo,
					'fecha_fija' => $fecha_fija
				));
				$message = preg_replace('#\[ficha(?:=(?:\d+|\d{2}/\d{2}/\d{4}))?\]#si', '<div style="background: #f44336; color: white; padding: 10px; margin: 10px; border-radius: 5px;">No existe ficha</div>', $message, 1);
				continue;
			}
			
			// Calcular estadísticas completas
			$stats = BBCustom_ficha_calc_stats($db, $uid, $ficha_data);
			
			// Generar HTML
			$ficha_html = BBCustom_ficha_generate_html($db, $uid, $tid, $stats);
			
			BBCustom_ficha_log_error("HTML de ficha generado", array(
				'html_preview' => substr($ficha_html, 0, 500)
			));
			
			// Crear spoiler usando librería reutilizable
			$spoiler = create_character_spoiler($stats['nombre'], $ficha_html);
			
			BBCustom_ficha_log_error("Spoiler completo generado", array(
				'spoiler_preview' => substr($spoiler, 0, 1000),
				'spoiler_length' => strlen($spoiler)
			));
			
// Reemplazar directamente [ficha] con el HTML generado
		$message = preg_replace('#\[ficha(?:=(?:\d+|\d{2}/\d{2}/\d{4}))?\]#si', $spoiler, $message, 1);
		
		BBCustom_ficha_log_error("Ficha procesada exitosamente", array(
			'nombre' => $stats['nombre'],
			'uid' => $uid,
			'message_length_after' => strlen($message),
			'message_preview_after' => substr($message, 0, 200)
			));
			
		} catch (Exception $e) {
			BBCustom_ficha_log_error("EXCEPCIÓN CAPTURADA: " . $e->getMessage(), array(
				'uid' => isset($uid) ? $uid : 'N/A',
				'tid' => isset($tid) ? $tid : 'N/A',
				'nivel_fijo' => isset($nivel_fijo) ? $nivel_fijo : 'N/A',
				'fecha_fija' => isset($fecha_fija) ? $fecha_fija : 'N/A',
				'file' => $e->getFile(),
				'line' => $e->getLine(),
				'trace' => $e->getTraceAsString()
			));
			$error = '<div style="background: #f44336; color: white; padding: 10px; margin: 10px; border-radius: 5px;">ERROR: ' . htmlspecialchars($e->getMessage()) . '</div>';
			$message = preg_replace('#\[ficha(?:=(?:\d+|\d{2}/\d{2}/\d{4}))?\]#si', $error, $message, 1);
		}
	}
	
	// Marcar como procesado
	$processed_pids[] = $current_pid;
}

// Hook para reemplazar placeholders al final del procesado
function BBCustom_ficha_get_data($db, $uid, $tid, $pid, $nivel_fijo = null, $fecha_fija = null)
{
	BBCustom_ficha_log_error('get_data INICIO', array('uid' => $uid, 'tid' => $tid, 'pid' => $pid, 'nivel_fijo' => $nivel_fijo, 'fecha_fija' => $fecha_fija));

	// Si se especifica una fecha fija, obtener desde el historial de auditoría por fecha
	if ($fecha_fija !== null) {
		$fecha_safe = $db->escape_string($fecha_fija);
		$sql = "SELECT * FROM mybb_audit_op_fichas WHERE fid='$uid' AND DATE(Fecha) = '$fecha_safe' ORDER BY Fecha DESC LIMIT 1";
		BBCustom_ficha_log_error('get_data: consultando audit por fecha_fija', array('sql' => $sql));
		$query = $db->query($sql);
		$ficha = $db->fetch_array($query);
		BBCustom_ficha_log_error('get_data: resultado audit por fecha', array(
			'encontrado' => $ficha ? 'SI' : 'NO',
			'nombre' => isset($ficha['nombre']) ? $ficha['nombre'] : 'N/A',
			'Fecha' => isset($ficha['Fecha']) ? $ficha['Fecha'] : 'N/A'
		));
		return $ficha ?: null;
	}

	// Si se especifica un nivel fijo, obtener desde el historial de auditoría por nivel
	if ($nivel_fijo !== null) {
		$nivel_fijo = intval($nivel_fijo);
		$sql = "SELECT * FROM mybb_audit_op_fichas WHERE fid='$uid' AND nivel='$nivel_fijo' ORDER BY Fecha DESC LIMIT 1";
		BBCustom_ficha_log_error('get_data: consultando audit por nivel_fijo', array('sql' => $sql));
		$query = $db->query($sql);
		$ficha = $db->fetch_array($query);
		BBCustom_ficha_log_error('get_data: resultado audit', array(
			'encontrado' => $ficha ? 'SI' : 'NO',
			'nombre' => isset($ficha['nombre']) ? $ficha['nombre'] : 'N/A',
			'Fecha' => isset($ficha['Fecha']) ? $ficha['Fecha'] : 'N/A'
		));
		return $ficha ?: null;
	}

	// Intentar obtener ficha del thread
	$sql_thread = "SELECT * FROM mybb_op_thread_personaje WHERE tid='$tid' AND uid='$uid'";
	BBCustom_ficha_log_error('get_data: buscando en thread_personaje', array('sql' => $sql_thread));
	$query = $db->query($sql_thread);
	if ($row = $db->fetch_array($query)) {
		BBCustom_ficha_log_error('get_data: encontrada en thread_personaje', array('nombre' => isset($row['nombre']) ? $row['nombre'] : 'N/A'));
		return $row;
	}
	BBCustom_ficha_log_error('get_data: no encontrada en thread_personaje, buscando en op_fichas');
	
	// Obtener ficha general
	$sql_ficha = "SELECT * FROM mybb_op_fichas WHERE fid='$uid'";
	BBCustom_ficha_log_error('get_data: consultando op_fichas', array('sql' => $sql_ficha));
	$query = $db->query($sql_ficha);
	$ficha = $db->fetch_array($query);
	
	if (!$ficha) {
		BBCustom_ficha_log_error('get_data: no encontrada en op_fichas', array('uid' => $uid));
		return null;
	}
	BBCustom_ficha_log_error('get_data: encontrada en op_fichas', array('nombre' => isset($ficha['nombre']) ? $ficha['nombre'] : 'N/A', 'campos' => array_keys($ficha)));
	
	// Guardar en thread_personaje si hay tid y pid
	if ($tid && $pid) {
		$db->query("INSERT INTO mybb_op_thread_personaje (tid, pid, uid, nombre, nivel,
			fuerza, resistencia, destreza, punteria, agilidad, reflejos, voluntad,
			fuerza_pasiva, resistencia_pasiva, destreza_pasiva, punteria_pasiva, agilidad_pasiva, reflejos_pasiva, voluntad_pasiva,
			vitalidad, energia, haki, vitalidad_pasiva, energia_pasiva, haki_pasiva) 
			VALUES ('$tid', '$pid', '$uid', '{$ficha['nombre']}', '{$ficha['nivel']}',
			'{$ficha['fuerza']}', '{$ficha['resistencia']}', '{$ficha['destreza']}', '{$ficha['punteria']}', '{$ficha['agilidad']}', '{$ficha['reflejos']}', '{$ficha['voluntad']}',
			'{$ficha['fuerza_pasiva']}', '{$ficha['resistencia_pasiva']}', '{$ficha['destreza_pasiva']}', '{$ficha['punteria_pasiva']}', '{$ficha['agilidad_pasiva']}', '{$ficha['reflejos_pasiva']}', '{$ficha['voluntad_pasiva']}',
			'{$ficha['vitalidad']}', '{$ficha['energia']}', '{$ficha['haki']}', '{$ficha['vitalidad_pasiva']}', '{$ficha['energia_pasiva']}', '{$ficha['haki_pasiva']}')");
	}
	
	return $ficha;
}

function BBCustom_ficha_calc_stats($db, $uid, $data)
{
	$nivel = intval($data['nivel']);
	
	// Stats base + pasiva
	$stats = array(
		'nombre' => $data['nombre'],
		'nivel' => $nivel,
		'fuerza' => intval($data['fuerza']) + intval($data['fuerza_pasiva']),
		'resistencia' => intval($data['resistencia']) + intval($data['resistencia_pasiva']),
		'destreza' => intval($data['destreza']) + intval($data['destreza_pasiva']),
		'punteria' => intval($data['punteria']) + intval($data['punteria_pasiva']),
		'agilidad' => intval($data['agilidad']) + intval($data['agilidad_pasiva']),
		'reflejos' => intval($data['reflejos']) + intval($data['reflejos_pasiva']),
		'voluntad' => intval($data['voluntad']) + intval($data['voluntad_pasiva'])
	);
	
	// Calcular recursos base
	$stats['vitalidad'] = intval($data['vitalidad']) + intval($data['vitalidad_pasiva']) + 
		floor((intval($data['fuerza_pasiva']) * 6) + (intval($data['resistencia_pasiva']) * 15) + (intval($data['destreza_pasiva']) * 4) +
		(intval($data['agilidad_pasiva']) * 3) + (intval($data['punteria_pasiva']) * 2) + (intval($data['reflejos_pasiva']) * 1) + (intval($data['voluntad_pasiva']) * 1));
	
	$stats['energia'] = intval($data['energia']) + intval($data['energia_pasiva']) + 
		floor((intval($data['destreza_pasiva']) * 4) + (intval($data['agilidad_pasiva']) * 5) + (intval($data['fuerza_pasiva']) * 2) + 
		(intval($data['resistencia_pasiva']) * 4) + (intval($data['punteria_pasiva']) * 5) + (intval($data['reflejos_pasiva']) * 1) + (intval($data['voluntad_pasiva']) * 1));
	
	$stats['haki'] = intval($data['haki']) + intval($data['haki_pasiva']) + floor(intval($data['voluntad_pasiva']) * 10);
	
	// Bonos de virtudes
	$virtudes = array('V037' => 10, 'V038' => 15, 'V039' => 20); // Vigoroso 1,2,3
	foreach ($virtudes as $vid => $bonus) {
		if (BBCustom_ficha_has_virtue($db, $uid, $vid)) {
			$stats['vitalidad'] += $nivel * $bonus;
			break;
		}
	}
	
	$virtudes = array('V040' => 10, 'V041' => 15); // Hiperactivo 1,2
	foreach ($virtudes as $vid => $bonus) {
		if (BBCustom_ficha_has_virtue($db, $uid, $vid)) {
			$stats['energia'] += $nivel * $bonus;
			break;
		}
	}
	
	$virtudes = array('V058' => 5, 'V059' => 10); // Espiritual 1,2
	foreach ($virtudes as $vid => $bonus) {
		if (BBCustom_ficha_has_virtue($db, $uid, $vid)) {
			$stats['haki'] += $nivel * $bonus;
			break;
		}
	}
	
	return $stats;
}

function BBCustom_ficha_has_virtue($db, $uid, $virtud_id)
{
	$query = $db->query("SELECT COUNT(*) as count FROM mybb_op_virtudes_usuarios WHERE uid='$uid' AND virtud_id='$virtud_id'");
	$row = $db->fetch_array($query);
	return $row['count'] > 0;
}

function BBCustom_ficha_generate_html($db, $uid, $tid, $stats)
{
	$virtudes = BBCustom_ficha_get_virtudes($db, $uid);
	$equipamiento_html = BBCustom_ficha_get_equipamiento($db, $uid, $tid);
	
	return "<div style='border-radius: 15px; padding: 20px; margin: 10px 0; box-shadow: 0 8px 32px rgba(0,0,0,0.3); color: white; background-color: #2c2c2c;'>
		<div style='text-align: center; margin-bottom: 20px;'>
			<h2 style='margin: 0; font-size: 24px; font-weight: bold; text-shadow: 2px 2px 4px rgba(0,0,0,0.5); background: linear-gradient(45deg, #ffd700, #ffed4e); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;'>⚔️ {$stats['nombre']} ⚔️</h2>
			<div style='margin-top: 5px;'><span style='background: rgba(255,255,255,0.2); padding: 5px 15px; border-radius: 20px; font-weight: bold;'>Nivel {$stats['nivel']}</span></div>
		</div>
		
		<div style='display: grid; grid-template-columns: repeat(auto-fit, minmax(80px, 1fr)); gap: 10px; margin: 20px 0; text-align: center;'>
			<div style='background: rgba(255,255,255,0.1); border-radius: 8px; padding: 10px;'><div style='font-size: 18px; font-weight: bold;'>{$stats['fuerza']}</div><div style='font-size: 12px; opacity: 0.8;'>FUE</div></div>
			<div style='background: rgba(255,255,255,0.1); border-radius: 8px; padding: 10px;'><div style='font-size: 18px; font-weight: bold;'>{$stats['resistencia']}</div><div style='font-size: 12px; opacity: 0.8;'>RES</div></div>
			<div style='background: rgba(255,255,255,0.1); border-radius: 8px; padding: 10px;'><div style='font-size: 18px; font-weight: bold;'>{$stats['destreza']}</div><div style='font-size: 12px; opacity: 0.8;'>DES</div></div>
			<div style='background: rgba(255,255,255,0.1); border-radius: 8px; padding: 10px;'><div style='font-size: 18px; font-weight: bold;'>{$stats['punteria']}</div><div style='font-size: 12px; opacity: 0.8;'>PUN</div></div>
			<div style='background: rgba(255,255,255,0.1); border-radius: 8px; padding: 10px;'><div style='font-size: 18px; font-weight: bold;'>{$stats['agilidad']}</div><div style='font-size: 12px; opacity: 0.8;'>AGI</div></div>
			<div style='background: rgba(255,255,255,0.1); border-radius: 8px; padding: 10px;'><div style='font-size: 18px; font-weight: bold;'>{$stats['reflejos']}</div><div style='font-size: 12px; opacity: 0.8;'>REF</div></div>
			<div style='background: rgba(255,255,255,0.1); border-radius: 8px; padding: 10px;'><div style='font-size: 18px; font-weight: bold;'>{$stats['voluntad']}</div><div style='font-size: 12px; opacity: 0.8;'>VOL</div></div>
		</div>
		
		<div style='margin-top: 20px;'>
			<div style='display: flex; justify-content: space-between; padding: 10px; background: #00aa00; border-radius: 8px; margin: 5px 0;'><span>❤️ Vitalidad:</span><span style='font-size: 18px; font-weight: bold;'>{$stats['vitalidad']}</span></div>
			<div style='display: flex; justify-content: space-between; padding: 10px; background: #ff8800; border-radius: 8px; margin: 5px 0;'><span>⚡ Energía:</span><span style='font-size: 18px; font-weight: bold;'>{$stats['energia']}</span></div>
			<div style='display: flex; justify-content: space-between; padding: 10px; background: #0066ff; border-radius: 8px; margin: 5px 0;'><span>🔮 Haki:</span><span style='font-size: 18px; font-weight: bold;'>{$stats['haki']}</span></div>
		</div>
		
		$equipamiento_html
		
		<div style='margin-top: 20px;'>
			<h3 style='text-align: center; color: #4a90e2;'>✨ VIRTUDES Y DEFECTOS</h3>
			<div style='padding: 15px; background: rgba(0,0,0,0.2); border-radius: 10px;'>$virtudes</div>
		</div>
	</div>";
}

function BBCustom_ficha_get_equipamiento($db, $uid, $tid)
{
	global $post;
	$pid = isset($post['pid']) ? $post['pid'] : 0;
	
	// Buscar equipamiento del thread
	$equipamiento_json = '';
	$query = $db->query("SELECT equipamiento FROM mybb_op_equipamiento_personaje WHERE tid='$tid' AND uid='$uid'");
	
	if ($row = $db->fetch_array($query)) {
		$equipamiento_json = $row['equipamiento'];
	} else {
		// Si no existe, copiar de la ficha general
		$query_ficha = $db->query("SELECT equipamiento FROM mybb_op_fichas WHERE fid='$uid'");
		if ($ficha = $db->fetch_array($query_ficha)) {
			$equipamiento_json = $ficha['equipamiento'];
			// Insertar en thread
			if ($tid && $pid) {
				$db->query("INSERT INTO mybb_op_equipamiento_personaje (tid, pid, uid, equipamiento) 
					VALUES ('$tid', '$pid', '$uid', '$equipamiento_json')");
			}
		}
	}
	
	// Procesar JSON
	$equipamiento_data = json_decode($equipamiento_json, true);
	
	// Inicializar slots
	$slot_bolsa_html = "<span style='color: rgba(255,255,255,0.5); font-size: 12px;'>Vacío</span>";
	$slot_ropa_html = "<span style='color: rgba(255,255,255,0.5); font-size: 12px;'>Vacío</span>";
	$espacios_html = "";
	$espacios_usados = 0;
	
	// Procesar bolsa
	if ($equipamiento_data && isset($equipamiento_data['bolsa']) && !empty($equipamiento_data['bolsa'])) {
		$bolsa_id = $equipamiento_data['bolsa'];
		$query = $db->query("SELECT nombre FROM mybb_op_objetos WHERE objeto_id='$bolsa_id'");
		if ($obj = $db->fetch_array($query)) {
			$slot_bolsa_html = "
				<div style='display: flex; flex-direction: column; align-items: center;'>
					<div style='font-size: 10px; color: #ffd700; font-weight: bold; margin-bottom: 2px;'>$bolsa_id</div>
					<div style='font-size: 9px; color: white; text-align: center; line-height: 12px;'>{$obj['nombre']}</div>
				</div>";
		}
	}
	
	// Procesar ropa
	if ($equipamiento_data && isset($equipamiento_data['ropa']) && !empty($equipamiento_data['ropa'])) {
		$ropa_id = $equipamiento_data['ropa'];
		$query = $db->query("SELECT nombre FROM mybb_op_objetos WHERE objeto_id='$ropa_id'");
		if ($obj = $db->fetch_array($query)) {
			$slot_ropa_html = "
				<div style='display: flex; flex-direction: column; align-items: center;'>
					<div style='font-size: 10px; color: #ffd700; font-weight: bold; margin-bottom: 2px;'>$ropa_id</div>
					<div style='font-size: 9px; color: white; text-align: center; line-height: 12px;'>{$obj['nombre']}</div>
				</div>";
		}
	}
	
	// Procesar espacios
	if ($equipamiento_data && isset($equipamiento_data['espacios']) && is_array($equipamiento_data['espacios'])) {
		foreach ($equipamiento_data['espacios'] as $espacio_id => $objeto_info) {
			if (isset($objeto_info['objetoId'])) {
				$objeto_id = $objeto_info['objetoId'];
				$query = $db->query("SELECT nombre, espacios FROM mybb_op_objetos WHERE objeto_id='$objeto_id'");
				if ($obj = $db->fetch_array($query)) {
					$espacios_obj = isset($obj['espacios']) && is_numeric($obj['espacios']) ? intval($obj['espacios']) : 1;
					$espacios_html .= "
						<div style='background: rgba(255,255,255,0.1); border: 2px solid #4a90e2; border-radius: 8px; padding: 8px; text-align: center;'>
							<div style='font-size: 8px; color: #ffd700; font-weight: bold; margin-bottom: 2px;'>$objeto_id</div>
							<div style='font-size: 9px; color: white; font-weight: bold; margin-bottom: 2px;'>{$obj['nombre']}</div>
							<div style='font-size: 8px; color: rgba(255,255,255,0.8);'>Espacio $espacio_id ($espacios_obj esp.)</div>
						</div>";
					$espacios_usados += $espacios_obj;
				}
			}
		}
	}
	
	if (empty($espacios_html)) {
		$espacios_html = "<div style='grid-column: 1 / -1; text-align: center; color: rgba(255,255,255,0.5); font-style: italic; padding: 20px;'>No hay objetos equipados</div>";
	}
	
	return "
		<div style='margin: 30px 0;'>
			<h3 style='text-align: center; color: #4a90e2; font-size: 20px; margin-bottom: 20px;'>⚔️ EQUIPAMIENTO</h3>
			
			<div style='display: flex; flex-direction: row; gap: 20px; justify-content: center; margin: 20px 0;'>
				<div style='display: flex; flex-direction: column; gap: 10px;'>
					<div style='display: flex; align-items: center; gap: 15px;'>
						<div style='min-width: 80px; font-weight: bold; color: #ffd700; font-size: 14px;'>BOLSA:</div>
						<div style='width: 120px; height: 80px; background: rgba(255,255,255,0.1); border: 2px solid #4a90e2; border-radius: 10px; display: flex; align-items: center; justify-content: center;'>
							$slot_bolsa_html
						</div>
					</div>
					<div style='display: flex; align-items: center; gap: 15px;'>
						<div style='min-width: 80px; font-weight: bold; color: #ffd700; font-size: 14px;'>ROPA:</div>
						<div style='width: 120px; height: 80px; background: rgba(255,255,255,0.1); border: 2px solid #4a90e2; border-radius: 10px; display: flex; align-items: center; justify-content: center;'>
							$slot_ropa_html
						</div>
					</div>
				</div>
				
				<div style='flex: 1; min-width: 300px;'>
					<div style='text-align: center; margin-bottom: 15px;'>
						<h4 style='margin: 0; color: #4a90e2; font-size: 16px;'>ESPACIOS DE EQUIPAMIENTO</h4>
					</div>
					<div style='display: grid; grid-template-columns: repeat(auto-fit, minmax(120px, 1fr)); gap: 10px; max-height: 200px; overflow-y: auto; padding: 10px; background: rgba(0,0,0,0.2); border-radius: 10px;'>
						$espacios_html
					</div>
					<div style='margin-top: 10px; text-align: center; font-size: 12px; color: #4a90e2;'>
						<strong>Espacios utilizados: $espacios_usados</strong>
					</div>
				</div>
			</div>
		</div>";
}

function BBCustom_ficha_get_virtudes($db, $uid)
{
	$html = '';
	
	$query = $db->query("SELECT v.nombre, v.descripcion FROM mybb_op_virtudes_usuarios vu 
		INNER JOIN mybb_op_virtudes v ON v.virtud_id = vu.virtud_id 
		WHERE vu.uid = '$uid' AND vu.virtud_id LIKE 'V%' ORDER BY v.nombre");
	
	while ($row = $db->fetch_array($query)) {
		$html .= "<span style='color: #008e02; font-weight: bold;'>{$row['nombre']}</span>: {$row['descripcion']}<br>";
	}
	
	$html .= "<br>";
	
	$query = $db->query("SELECT v.nombre, v.descripcion FROM mybb_op_virtudes_usuarios vu 
		INNER JOIN mybb_op_virtudes v ON v.virtud_id = vu.virtud_id 
		WHERE vu.uid = '$uid' AND vu.virtud_id LIKE 'D%' ORDER BY v.nombre");
	
	while ($row = $db->fetch_array($query)) {
		$html .= "<span style='color: #c10300; font-weight: bold;'>{$row['nombre']}</span>: {$row['descripcion']}<br>";
	}
	
	return $html ?: 'Sin virtudes ni defectos';
}