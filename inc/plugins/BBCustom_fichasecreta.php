<?php
/**
 * BBCustom Ficha Secreta
 * Procesa BBCode [fichasecreta]
 * 
 * Muestra la ficha completa del personaje secreto del usuario
 * Similar a [ficha] pero usa el nombre secreto de mybb_op_fichas_secret
 */

if (!defined("IN_MYBB")) {
    die("Direct initialization of this file is not allowed.");
}

// Cargar librería de spoilers
require_once MYBB_ROOT . "inc/plugins/lib/spoiler.php";

// Hook
$plugins->add_hook("postbit", "BBCustom_fichasecreta_run");

function BBCustom_fichasecreta_info()
{
    return array(
        "name"          => "Ficha Secreta BBCode",
        "description"   => "BBCode [fichasecreta] para mostrar ficha con identidad secreta",
        "website"       => "",
        "author"        => "Cascabelles",
        "authorsite"    => "",
        "version"       => "2.1",
        "codename"      => "BBCustom_fichasecreta",
        "compatibility" => "*"
    );
}

function BBCustom_fichasecreta_activate() {}
function BBCustom_fichasecreta_deactivate() {}

function BBCustom_fichasecreta_run(&$post)
{
    global $db;
    
    try {
        // Protección contra procesamiento múltiple
        static $processed_pids = array();
        $current_pid = isset($post['pid']) ? $post['pid'] : md5($post['message']);
        
        if (in_array($current_pid, $processed_pids)) {
            return;
        }
        
        // Verificar que exista el mensaje
        if (!isset($post['message'])) {
            return;
        }
        
        $message = &$post['message'];
        
        // Procesar [fichasecreta]
        while (preg_match('#\[fichasecreta\]#si', $message))
        {
        // Validar datos necesarios
        if (!isset($post['uid']) || !isset($post['tid']) || !isset($post['pid'])) {
            if (function_exists('BBCustom_personajesecreto_log_error')) {
                BBCustom_personajesecreto_log_error('[BBCustom_fichasecreta] Datos de post incompletos', array(
                    'has_uid' => isset($post['uid']),
                    'has_tid' => isset($post['tid']),
                    'has_pid' => isset($post['pid'])
                ));
            }
            // Reemplazar con mensaje de error
            $message = preg_replace(
                '#\[fichasecreta\]#si',
                '<div style="background: #f44336; color: white; padding: 10px; border-radius: 5px; text-align: center;">⚠️ Error: Datos de post incompletos</div>',
                $message,
                1
            );
            break;
        }
        
        $uid = $post['uid'];
        $tid = $post['tid'];
        $pid = $post['pid'];
        
        // ========== OBTENER NOMBRE SECRETO ==========
        static $secret_cache = array();
        $ficha_secreta = null;

        if (array_key_exists($uid, $secret_cache)) {
            $ficha_secreta = $secret_cache[$uid];
        } else {
            $query = $db->query("SELECT * FROM mybb_op_fichas_secret WHERE fid='$uid' AND secret_number=1 LIMIT 1");
            $ficha_secreta = $db->fetch_array($query);
            $secret_cache[$uid] = $ficha_secreta;
        }

        $secret_nombre = 'Personaje Secreto';
        if ($ficha_secreta && isset($ficha_secreta['nombre']) && $ficha_secreta['nombre'] !== null) {
            $nombre_trimmed = trim($ficha_secreta['nombre']);
            if (!empty($nombre_trimmed)) {
                $secret_nombre = $nombre_trimmed;
            }
        }
        
        // Obtener datos completos de la ficha usando función auxiliar
        $ficha_data = BBCustom_fichasecreta_get_ficha_data($uid, $tid, $pid);
        
        // Generar HTML
        $ficha_html = BBCustom_fichasecreta_generate_html(
            $secret_nombre,
            $ficha_data['stats'],
            $ficha_data['equipamiento'],
            $ficha_data['virtudes']
        );
        
        // Reemplazar [fichasecreta] con el HTML
        $message = preg_replace(
            '#\[fichasecreta\]#si',
            $ficha_html,
            $message,
            1
        );
    }
    
    $processed_pids[] = $current_pid;
    
    } catch (Exception $e) {
        // Log del error si existe una función de logging
        if (function_exists('BBCustom_personajesecreto_log_error')) {
            BBCustom_personajesecreto_log_error('[BBCustom_fichasecreta] Exception: ' . $e->getMessage(), array(
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'uid' => isset($post['uid']) ? $post['uid'] : 'unknown'
            ));
        }
    } catch (Error $e) {
        // Log del error si existe una función de logging
        if (function_exists('BBCustom_personajesecreto_log_error')) {
            BBCustom_personajesecreto_log_error('[BBCustom_fichasecreta] Error: ' . $e->getMessage(), array(
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'uid' => isset($post['uid']) ? $post['uid'] : 'unknown'
            ));
        }
    }
}

/**
 * Obtiene todos los datos de la ficha
 */
function BBCustom_fichasecreta_get_ficha_data($uid, $tid, $pid)
{
    global $db;
    
    try {
        $ficha = null;
        $thread_ficha = null;
        
        // Verificar virtudes para bonificaciones
        $virtudes_bonuses = BBCustom_fichasecreta_get_virtudes_bonuses($uid);
        
        // Buscar datos del thread o crear entrada
        $query_personaje = $db->query("
            SELECT * FROM mybb_op_thread_personaje WHERE tid='$tid' AND uid='$uid'
        ");
        
        $thread_ficha = $db->fetch_array($query_personaje);
        
        if (!$thread_ficha) {
            // Obtener de ficha principal
            $query_ficha = $db->query("SELECT * FROM mybb_op_fichas WHERE fid='$uid'");
            $ficha = $db->fetch_array($query_ficha);
            
            if ($ficha && $tid && $pid) {
                // Insertar en thread_personaje
                // NOTA: se usa $tid (id del hilo), no $ficha['fid']
                $db->query("
                    INSERT INTO mybb_op_thread_personaje (tid, pid, uid, nombre,
                        vitalidad, energia, haki, 
                        fuerza, resistencia, destreza, punteria, agilidad, 
                        reflejos, voluntad, control_akuma,
                        fuerza_pasiva, resistencia_pasiva, destreza_pasiva, punteria_pasiva, agilidad_pasiva, 
                        reflejos_pasiva, voluntad_pasiva, control_akuma_pasiva, vitalidad_pasiva, energia_pasiva, haki_pasiva, nivel
                    ) 
                    VALUES ('$tid', '$pid', '$uid', '{$ficha['nombre']}',
                        '{$ficha['vitalidad']}', '{$ficha['energia']}', '{$ficha['haki']}', 
                        '{$ficha['fuerza']}', '{$ficha['resistencia']}', '{$ficha['destreza']}', '{$ficha['punteria']}', '{$ficha['agilidad']}', 
                        '{$ficha['reflejos']}', '{$ficha['voluntad']}', '{$ficha['control_akuma']}',
                        '{$ficha['fuerza_pasiva']}', '{$ficha['resistencia_pasiva']}', '{$ficha['destreza_pasiva']}', '{$ficha['punteria_pasiva']}', '{$ficha['agilidad_pasiva']}', 
                        '{$ficha['reflejos_pasiva']}', '{$ficha['voluntad_pasiva']}', '{$ficha['control_akuma_pasiva']}', '{$ficha['vitalidad_pasiva']}', '{$ficha['energia_pasiva']}', '{$ficha['haki_pasiva']}', '{$ficha['nivel']}'
                    )
                ");
                $thread_ficha = $ficha;
            } else {
                // Log detallado: indica exactamente qué falta
                if (function_exists('BBCustom_personajesecreto_log_error')) {
                    BBCustom_personajesecreto_log_error('[BBCustom_fichasecreta] Sin ficha principal', array(
                        'uid'        => $uid,
                        'tid'        => $tid,
                        'pid'        => $pid,
                        'ficha_encontrada' => ($ficha !== false && $ficha !== null) ? 'SI' : 'NO (falta registro en mybb_op_fichas)',
                    ));
                }
                // Sin ficha principal no hay stats — devolver error descriptivo
                return array(
                    'stats'       => null,
                    'equipamiento'=> null,
                    'virtudes'    => '',
                    'error'       => "No existe registro en mybb_op_fichas para uid=$uid",
                );
            }
        }
        
        if (!$thread_ficha) {
            if (function_exists('BBCustom_personajesecreto_log_error')) {
                BBCustom_personajesecreto_log_error('[BBCustom_fichasecreta] thread_ficha null tras intentos', array(
                    'uid' => $uid, 'tid' => $tid, 'pid' => $pid,
                ));
            }
            return array('stats' => null, 'equipamiento' => null, 'virtudes' => '');
        }
        
        // Calcular estadísticas completas
        $stats = BBCustom_fichasecreta_calc_stats($thread_ficha, $virtudes_bonuses);
        
        // Obtener equipamiento
        $equipamiento = BBCustom_fichasecreta_get_equipamiento($uid, $tid, $pid);
        
        // Obtener virtudes/defectos
        $virtudes_html = BBCustom_fichasecreta_get_virtudes_html($uid);
        
        return array(
            'stats' => $stats,
            'equipamiento' => $equipamiento,
            'virtudes' => $virtudes_html
        );
    } catch (Exception $e) {
        if (function_exists('BBCustom_personajesecreto_log_error')) {
            BBCustom_personajesecreto_log_error('[BBCustom_fichasecreta_get_ficha_data] Exception: ' . $e->getMessage(), array(
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'uid' => $uid
            ));
        }
        return array('stats' => null, 'equipamiento' => null, 'virtudes' => '');
    } catch (Error $e) {
        if (function_exists('BBCustom_personajesecreto_log_error')) {
            BBCustom_personajesecreto_log_error('[BBCustom_fichasecreta_get_ficha_data] Error: ' . $e->getMessage(), array(
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'uid' => $uid
            ));
        }
        return array('stats' => null, 'equipamiento' => null, 'virtudes' => '');
    }
}

/**
 * Calcula bonificaciones de virtudes
 */
function BBCustom_fichasecreta_get_virtudes_bonuses($uid)
{
    global $db;
    
    $bonuses = array(
        'vigoroso' => 0,
        'hiperactivo' => 0,
        'espiritual' => 0
    );
    
    // Vigoroso
    if ($db->fetch_array($db->query("SELECT * FROM mybb_op_virtudes_usuarios WHERE uid='$uid' AND virtud_id='V037'"))) {
        $bonuses['vigoroso'] = 10;
    } else if ($db->fetch_array($db->query("SELECT * FROM mybb_op_virtudes_usuarios WHERE uid='$uid' AND virtud_id='V038'"))) {
        $bonuses['vigoroso'] = 15;
    } else if ($db->fetch_array($db->query("SELECT * FROM mybb_op_virtudes_usuarios WHERE uid='$uid' AND virtud_id='V039'"))) {
        $bonuses['vigoroso'] = 20;
    }
    
    // Hiperactivo
    if ($db->fetch_array($db->query("SELECT * FROM mybb_op_virtudes_usuarios WHERE uid='$uid' AND virtud_id='V040'"))) {
        $bonuses['hiperactivo'] = 10;
    } else if ($db->fetch_array($db->query("SELECT * FROM mybb_op_virtudes_usuarios WHERE uid='$uid' AND virtud_id='V041'"))) {
        $bonuses['hiperactivo'] = 15;
    }
    
    // Espiritual
    if ($db->fetch_array($db->query("SELECT * FROM mybb_op_virtudes_usuarios WHERE uid='$uid' AND virtud_id='V058'"))) {
        $bonuses['espiritual'] = 5;
    } else if ($db->fetch_array($db->query("SELECT * FROM mybb_op_virtudes_usuarios WHERE uid='$uid' AND virtud_id='V059'"))) {
        $bonuses['espiritual'] = 10;
    }
    
    return $bonuses;
}

/**
 * Calcula estadísticas completas
 */
function BBCustom_fichasecreta_calc_stats($ficha, $virtudes_bonuses)
{
    // Stats pasivas
    $fuerza_pasiva = intval($ficha['fuerza_pasiva']);
    $resistencia_pasiva = intval($ficha['resistencia_pasiva']);
    $destreza_pasiva = intval($ficha['destreza_pasiva']);
    $punteria_pasiva = intval($ficha['punteria_pasiva']);
    $agilidad_pasiva = intval($ficha['agilidad_pasiva']);
    $reflejos_pasiva = intval($ficha['reflejos_pasiva']);
    $voluntad_pasiva = intval($ficha['voluntad_pasiva']);
    $control_akuma_pasiva = intval($ficha['control_akuma_pasiva']);
    
    // Calcular recursos extras por pasivas
    $vitalidad_extra = floor(($fuerza_pasiva * 6) + ($resistencia_pasiva * 15) + ($destreza_pasiva * 4) +
        ($agilidad_pasiva * 3) + ($voluntad_pasiva * 1) + ($punteria_pasiva * 2) + ($reflejos_pasiva * 1));
    
    $energia_extra = floor(($destreza_pasiva * 4) + ($agilidad_pasiva * 5) + ($voluntad_pasiva * 1) + 
        ($fuerza_pasiva * 2) + ($resistencia_pasiva * 4) + ($punteria_pasiva * 5) + ($reflejos_pasiva * 1));
    
    $haki_extra = floor($voluntad_pasiva * 10);
    
    // Stats completas
    $nivel = intval($ficha['nivel']);
    
    $stats = array(
        'nivel' => $nivel,
        'fuerza' => intval($ficha['fuerza']) + $fuerza_pasiva,
        'resistencia' => intval($ficha['resistencia']) + $resistencia_pasiva,
        'destreza' => intval($ficha['destreza']) + $destreza_pasiva,
        'punteria' => intval($ficha['punteria']) + $punteria_pasiva,
        'agilidad' => intval($ficha['agilidad']) + $agilidad_pasiva,
        'reflejos' => intval($ficha['reflejos']) + $reflejos_pasiva,
        'voluntad' => intval($ficha['voluntad']) + $voluntad_pasiva,
        'control_akuma' => intval($ficha['control_akuma']) + $control_akuma_pasiva,
        'vitalidad' => intval($ficha['vitalidad']) + $vitalidad_extra + intval($ficha['vitalidad_pasiva']),
        'energia' => intval($ficha['energia']) + $energia_extra + intval($ficha['energia_pasiva']),
        'haki' => intval($ficha['haki']) + $haki_extra + intval($ficha['haki_pasiva'])
    );
    
    // Aplicar bonificaciones de virtudes
    $stats['vitalidad'] += ($virtudes_bonuses['vigoroso'] * $nivel);
    $stats['energia'] += ($virtudes_bonuses['hiperactivo'] * $nivel);
    $stats['haki'] += ($virtudes_bonuses['espiritual'] * $nivel);
    
    return $stats;
}

/**
 * Obtiene equipamiento del thread o ficha
 */
function BBCustom_fichasecreta_get_equipamiento($uid, $tid, $pid)
{
    global $db;
    
    $equipamiento_json = "";
    
    // Buscar en thread
    $query_equipamiento = $db->query("
        SELECT * FROM mybb_op_equipamiento_personaje WHERE tid='$tid' AND uid='$uid'
    ");
    
    $equipamiento = $db->fetch_array($query_equipamiento);
    
    if (!$equipamiento) {
        // Obtener de ficha principal
        $query_ficha = $db->query("SELECT equipamiento FROM mybb_op_fichas WHERE fid='$uid'");
        $ficha = $db->fetch_array($query_ficha);
        $equipamiento_json = $ficha ? $ficha['equipamiento'] : "";
        
        if ($equipamiento_json && $tid && $pid) {
            // Insertar en thread
            $db->query("
                INSERT INTO mybb_op_equipamiento_personaje (tid, pid, uid, equipamiento) 
                VALUES ('$tid', '$pid', '$uid', '$equipamiento_json')
            ");
        }
    } else {
        $equipamiento_json = $equipamiento['equipamiento'];
    }
    
    return json_decode($equipamiento_json, true);
}

/**
 * Obtiene virtudes y defectos en HTML
 */
function BBCustom_fichasecreta_get_virtudes_html($uid)
{
    global $db;
    
    $html = '';
    
    // Virtudes
    $query_virtudes = $db->query("
        SELECT mybb_op_virtudes.nombre, mybb_op_virtudes.descripcion
        FROM mybb_op_virtudes_usuarios
        INNER JOIN mybb_op_virtudes ON mybb_op_virtudes.virtud_id = mybb_op_virtudes_usuarios.virtud_id
        WHERE mybb_op_virtudes_usuarios.uid = $uid AND mybb_op_virtudes_usuarios.virtud_id LIKE 'V%'
        ORDER BY mybb_op_virtudes.nombre ASC
    ");
    
    while ($v = $db->fetch_array($query_virtudes)) {
        $html .= "<span style='color: #008e02; font-weight: bold;'>{$v['nombre']}</span>: {$v['descripcion']}<br>";
    }
    
    $html .= "<br>";
    
    // Defectos
    $query_defectos = $db->query("
        SELECT mybb_op_virtudes.nombre, mybb_op_virtudes.descripcion
        FROM mybb_op_virtudes_usuarios
        INNER JOIN mybb_op_virtudes ON mybb_op_virtudes.virtud_id = mybb_op_virtudes_usuarios.virtud_id
        WHERE mybb_op_virtudes_usuarios.uid = $uid AND mybb_op_virtudes_usuarios.virtud_id LIKE 'D%'
        ORDER BY mybb_op_virtudes.nombre ASC
    ");
    
    while ($d = $db->fetch_array($query_defectos)) {
        $html .= "<span style='color: #c10300; font-weight: bold;'>{$d['nombre']}</span>: {$d['descripcion']}<br>";
    }
    
    return $html;
}

/**
 * Genera el HTML de la ficha secreta
 */
function BBCustom_fichasecreta_generate_html($secret_nombre, $stats, $equipamiento_data, $virtudes_html)
{
    global $db;
    
    if (!$stats) {
        $error_msg = isset($equipamiento_data) ? '' : '';
        // Mostrar mensaje más descriptivo si se propagó el campo 'error'
        $detalle = '';
        return '<div style="background: #f44336; color: white; padding: 10px; border-radius: 5px; text-align: center;">
            ⚠️ No se pudo cargar la ficha: no existe registro de stats en <code>mybb_op_fichas</code> para este usuario.
        </div>';
    }
    
    // Procesar equipamiento
    $slot_bolsa_html = "<span style='color: rgba(255,255,255,0.5); font-size: 12px;'>Vacío</span>";
    $slot_ropa_html = "<span style='color: rgba(255,255,255,0.5); font-size: 12px;'>Vacío</span>";
    $espacios_html = "";
    $espacios_usados = 0;
    
    // Bolsa
    if ($equipamiento_data && isset($equipamiento_data['bolsa']) && !empty($equipamiento_data['bolsa'])) {
        $bolsa_id = $equipamiento_data['bolsa'];
        $bolsa_query = $db->query("SELECT nombre FROM mybb_op_objetos WHERE objeto_id='$bolsa_id'");
        $bolsa_nombre = $bolsa_id;
        if ($b = $db->fetch_array($bolsa_query)) {
            $bolsa_nombre = $b['nombre'];
        }
        $slot_bolsa_html = "
            <div style='display: flex; flex-direction: column; align-items: center;'>
                <div style='font-size: 10px; color: #ffd700; font-weight: bold; margin-bottom: 2px;'>$bolsa_id</div>
                <div style='font-size: 9px; color: white; text-align: center; line-height: 12px;'>$bolsa_nombre</div>
            </div>
        ";
    }
    
    // Ropa
    if ($equipamiento_data && isset($equipamiento_data['ropa']) && !empty($equipamiento_data['ropa'])) {
        $ropa_id = $equipamiento_data['ropa'];
        $ropa_query = $db->query("SELECT nombre FROM mybb_op_objetos WHERE objeto_id='$ropa_id'");
        $ropa_nombre = $ropa_id;
        if ($r = $db->fetch_array($ropa_query)) {
            $ropa_nombre = $r['nombre'];
        }
        $slot_ropa_html = "
            <div style='display: flex; flex-direction: column; align-items: center;'>
                <div style='font-size: 10px; color: #ffd700; font-weight: bold; margin-bottom: 2px;'>$ropa_id</div>
                <div style='font-size: 9px; color: white; text-align: center; line-height: 12px;'>$ropa_nombre</div>
            </div>
        ";
    }
    
    // Espacios
    if ($equipamiento_data && isset($equipamiento_data['espacios']) && is_array($equipamiento_data['espacios'])) {
        foreach ($equipamiento_data['espacios'] as $espacio_id => $objeto_info) {
            if (isset($objeto_info['objetoId'])) {
                $objeto_id = $objeto_info['objetoId'];
                $objeto_query = $db->query("SELECT nombre, espacios FROM mybb_op_objetos WHERE objeto_id='$objeto_id'");
                $objeto_nombre = $objeto_id;
                $objeto_espacios = 1;
                if ($o = $db->fetch_array($objeto_query)) {
                    $objeto_nombre = $o['nombre'];
                    $objeto_espacios = isset($o['espacios']) && is_numeric($o['espacios']) ? intval($o['espacios']) : 1;
                }
                $espacios_html .= "
                    <div class='objeto_equipado' style='background: rgba(255,255,255,0.1); border: 2px solid #4a90e2; border-radius: 8px; padding: 8px; text-align: center; backdrop-filter: blur(5px);'>
                        <div style='font-size: 8px; color: #ffd700; font-weight: bold; margin-bottom: 2px;'>$objeto_id</div>
                        <div style='font-size: 9px; color: white; font-weight: bold; margin-bottom: 2px;'>$objeto_nombre</div>
                        <div style='font-size: 8px; color: rgba(255,255,255,0.8);'>Espacio $espacio_id ($objeto_espacios esp.)</div>
                    </div>
                ";
                $espacios_usados += $objeto_espacios;
            }
        }
    }
    
    if (empty($espacios_html)) {
        $espacios_html = "<div style='grid-column: 1 / -1; text-align: center; color: rgba(255,255,255,0.5); font-style: italic; padding: 20px;'>No hay objetos equipados</div>";
    }
    
    // HTML completo
    $ficha_completa = "
        <div class='ficha_container' style='border-radius: 15px; padding: 20px; margin: 10px 0; box-shadow: 0 8px 32px rgba(0,0,0,0.3); color: white; background-color: #2c2c2c;'>
            
            <!-- PERSONAJE -->
            <div class='ficha_personaje' style='margin-bottom: 30px;'>
                <div class='personaje_header' style='text-align: center; margin-bottom: 20px;'>
                    <h2 style='margin: 0; font-size: 24px; font-weight: bold; text-shadow: 2px 2px 4px rgba(0,0,0,0.5); background: linear-gradient(45deg, #ffd700, #ffed4e); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;'>⚔️ $secret_nombre ⚔️</h2>
                    <div style='margin-top: 5px; font-size: 16px; opacity: 0.9;'>
                        <span style='background: rgba(255,255,255,0.2); padding: 5px 15px; border-radius: 20px; font-weight: bold;'>Nivel {$stats['nivel']}</span>
                    </div>
                </div>
                
                <div style='display: grid; grid-template-columns: repeat(auto-fit, minmax(80px, 1fr)); gap: 10px; margin: 20px 0; text-align: center;'>
                    <div style='background: rgba(255,255,255,0.1); border-radius: 8px; padding: 10px; backdrop-filter: blur(5px);'>
                        <div style='font-size: 18px; font-weight: bold; color: white;'>{$stats['fuerza']}</div>
                        <div style='font-size: 12px; opacity: 0.8;'>FUE</div>
                    </div>
                    <div style='background: rgba(255,255,255,0.1); border-radius: 8px; padding: 10px; backdrop-filter: blur(5px);'>
                        <div style='font-size: 18px; font-weight: bold; color: white;'>{$stats['resistencia']}</div>
                        <div style='font-size: 12px; opacity: 0.8;'>RES</div>
                    </div>
                    <div style='background: rgba(255,255,255,0.1); border-radius: 8px; padding: 10px; backdrop-filter: blur(5px);'>
                        <div style='font-size: 18px; font-weight: bold; color: white;'>{$stats['destreza']}</div>
                        <div style='font-size: 12px; opacity: 0.8;'>DES</div>
                    </div>
                    <div style='background: rgba(255,255,255,0.1); border-radius: 8px; padding: 10px; backdrop-filter: blur(5px);'>
                        <div style='font-size: 18px; font-weight: bold; color: white;'>{$stats['punteria']}</div>
                        <div style='font-size: 12px; opacity: 0.8;'>PUN</div>
                    </div>
                    <div style='background: rgba(255,255,255,0.1); border-radius: 8px; padding: 10px; backdrop-filter: blur(5px);'>
                        <div style='font-size: 18px; font-weight: bold; color: white;'>{$stats['agilidad']}</div>
                        <div style='font-size: 12px; opacity: 0.8;'>AGI</div>
                    </div>
                    <div style='background: rgba(255,255,255,0.1); border-radius: 8px; padding: 10px; backdrop-filter: blur(5px);'>
                        <div style='font-size: 18px; font-weight: bold; color: white;'>{$stats['reflejos']}</div>
                        <div style='font-size: 12px; opacity: 0.8;'>REF</div>
                    </div>
                    <div style='background: rgba(255,255,255,0.1); border-radius: 8px; padding: 10px; backdrop-filter: blur(5px);'>
                        <div style='font-size: 18px; font-weight: bold; color: white;'>{$stats['voluntad']}</div>
                        <div style='font-size: 12px; opacity: 0.8;'>VOL</div>
                    </div>
                </div>
                
                <div style='margin-top: 20px;'>
                    <div style='display: flex; justify-content: space-between; align-items: center; margin: 10px 0; padding: 10px; background: #00aa00; border-radius: 8px;'>
                        <span style='font-weight: bold;'>❤️ Vitalidad:</span>
                        <span style='font-size: 18px; font-weight: bold; color: white; text-shadow: 1px 1px 2px rgba(0,0,0,0.5);'>{$stats['vitalidad']}</span>
                    </div>
                    <div style='display: flex; justify-content: space-between; align-items: center; margin: 10px 0; padding: 10px; background: #ff8800; border-radius: 8px;'>
                        <span style='font-weight: bold;'>⚡ Energía:</span>
                        <span style='font-size: 18px; font-weight: bold; color: white; text-shadow: 1px 1px 2px rgba(0,0,0,0.5);'>{$stats['energia']}</span>
                    </div>
                    <div style='display: flex; justify-content: space-between; align-items: center; margin: 10px 0; padding: 10px; background: #0066ff; border-radius: 8px;'>
                        <span style='font-weight: bold;'>🔮 Haki:</span>
                        <span style='font-size: 18px; font-weight: bold; color: white; text-shadow: 1px 1px 2px rgba(0,0,0,0.5);'>{$stats['haki']}</span>
                    </div>
                </div>
            </div>

            <!-- EQUIPAMIENTO -->
            <div class='ficha_equipamiento' style='margin-bottom: 30px;'>
                <h3 style='text-align: center; color: #4a90e2; font-size: 20px; margin-bottom: 20px; text-shadow: 1px 1px 2px rgba(0,0,0,0.5);'>⚔️ EQUIPAMIENTO</h3>
                
                <div style='display: flex; flex-direction: row; gap: 20px; justify-content: center; margin: 20px 0;'>
                    <div style='display: flex; flex-direction: column; gap: 10px;'>
                        <div style='display: flex; align-items: center; gap: 15px;'>
                            <div style='min-width: 80px; font-weight: bold; color: #ffd700; font-size: 14px;'>BOLSA:</div>
                            <div style='width: 120px; height: 80px; background: rgba(255,255,255,0.1); border: 2px solid #4a90e2; border-radius: 10px; display: flex; align-items: center; justify-content: center; margin-top: 50px;'>
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
            </div>

            <!-- VIRTUDES Y DEFECTOS -->
            <div class='ficha_virtudes'>
                <h3 style='text-align: center; color: #4a90e2; font-size: 20px; margin-bottom: 20px; text-shadow: 1px 1px 2px rgba(0,0,0,0.5);'>✨ VIRTUDES Y DEFECTOS</h3>
                <div style='line-height: 1.6; font-size: 14px; padding: 15px; background: rgba(0,0,0,0.2); border-radius: 10px;'>
                    $virtudes_html
                </div>
            </div>
        </div>
    ";

    $ficha_spoiler = "
        <div class='spoiler' style='border: none; background: transparent; box-shadow: none;'>
            <div class='spoiler_title'>
                <span class='spoiler_button' onclick=\"javascript: if(parentNode.parentNode.getElementsByTagName('div')[1].style.display == 'block'){ parentNode.parentNode.getElementsByTagName('div')[1].style.display = 'none'; this.innerHTML='📋 $secret_nombre'; } else { parentNode.parentNode.getElementsByTagName('div')[1].style.display = 'block'; this.innerHTML='📋 $secret_nombre'; }\" style='background-color: #2c2c2c; color: white; border: none; border-radius: 25px; padding: 12px 25px; font-weight: bold; cursor: pointer; box-shadow: 0 4px 15px rgba(0,0,0,0.2); transition: all 0.3s ease; text-shadow: 1px 1px 2px rgba(0,0,0,0.3);'>📋 $secret_nombre</span>
            </div>
            <div class='spoiler_content' style='display: none; background: transparent; border: none; box-shadow: none; margin-top: 10px;'>$ficha_completa</div>
        </div>
    ";

    return $ficha_spoiler;
}
