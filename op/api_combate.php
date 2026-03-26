<?php
/**
 * API de Combate - Sistema de Combate por Turnos
 * Endpoint para obtener datos de combate de un tema
 */

// Desactivar errores HTML para devolver JSON limpio
ini_set('display_errors', 0);
error_reporting(0);

// Manejador de errores personalizado
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
});

try {

define("IN_MYBB", 1);
require_once "../global.php";

header('Content-Type: application/json; charset=utf-8');

// Verificar autenticación
if (!$mybb->user['uid']) {
    echo json_encode(['error' => 'No autenticado']);
    exit;
}

$action = $mybb->get_input('action');
$tid = $mybb->get_input('tid', MyBB::INPUT_INT);
$uid = $mybb->user['uid'];

switch ($action) {
    case 'get_thread_combat_data':
        getCombatData($db, $tid, $uid);
        break;
    case 'get_character_data':
        $target_uid = $mybb->get_input('target_uid', MyBB::INPUT_INT);
        getCharacterData($db, $tid, $target_uid);
        break;
    case 'get_my_techniques':
        getMyTechniques($db, $uid);
        break;
    case 'get_my_weapons':
        getMyWeapons($db, $uid);
        break;
    case 'check_combat_ready':
        checkCombatReady($db, $tid, $uid);
        break;
    default:
        echo json_encode(['error' => 'Acción no válida']);
}

} catch (Exception $e) {
    echo json_encode([
        'error' => 'Error del servidor',
        'message' => $e->getMessage(),
        'file' => basename($e->getFile()),
        'line' => $e->getLine()
    ]);
    exit;
}

/**
 * Obtiene todos los datos de combate de un tema
 */
function getCombatData($db, $tid, $current_uid) {
    if (!$tid) {
        echo json_encode(['error' => 'TID no proporcionado']);
        return;
    }

    $result = [
        'thread_id' => $tid,
        'current_uid' => $current_uid,
        'participants' => [],
        'npcs' => [],
        'combat_log' => [],
        'my_turns' => 0,
        'my_character' => null
    ];

    // Obtener todos los posts del tema
    $query = $db->query("
        SELECT p.pid, p.uid, p.message, p.dateline, p.username,
               u.username as user_username, u.avatar
        FROM mybb_posts p
        LEFT JOIN mybb_users u ON u.uid = p.uid
        WHERE p.tid = '$tid' AND p.visible = 1
        ORDER BY p.dateline ASC
    ");

    $posts = [];
    $participants = [];
    $npcs = [];
    $combat_actions = [];
    $my_turns = 0;
    $turn_counter = [];

    while ($post = $db->fetch_array($query)) {
        $posts[] = $post;
        
        // Contar turnos por usuario
        if (!isset($turn_counter[$post['uid']])) {
            $turn_counter[$post['uid']] = 0;
        }
        $turn_counter[$post['uid']]++;
        
        if ($post['uid'] == $current_uid) {
            $my_turns++;
        }

        // Detectar [personaje] o [ficha] - personaje del autor del post
        if (preg_match('#\[(personaje|ficha)\]#si', $post['message'])) {
            if (!isset($participants[$post['uid']])) {
                $char_data = getThreadCharacter($db, $tid, $post['uid']);
                if ($char_data) {
                    $participants[$post['uid']] = [
                        'uid' => $post['uid'],
                        'username' => $post['user_username'] ?: $post['username'],
                        'avatar' => $post['avatar'],
                        'character' => $char_data,
                        'first_post' => $post['pid'],
                        'type' => 'player'
                    ];
                }
            }
        }

        // Detectar [personaje=UID] o [ficha=UID] - personaje específico
        if (preg_match_all('#\[(personaje|ficha)=(\d+)\]#si', $post['message'], $matches)) {
            foreach ($matches[2] as $char_uid) {
                if (!isset($participants[$char_uid])) {
                    $char_data = getThreadCharacter($db, $tid, $char_uid);
                    $user_data = getUserBasicInfo($db, $char_uid);
                    if ($char_data) {
                        $participants[$char_uid] = [
                            'uid' => $char_uid,
                            'username' => $user_data['username'],
                            'avatar' => $user_data['avatar'],
                            'character' => $char_data,
                            'first_post' => $post['pid'],
                            'type' => 'player'
                        ];
                    }
                }
            }
        }

        // Detectar [npc=X] - NPCs
        if (preg_match_all('#\[npc=([^\]]+)\]#si', $post['message'], $matches)) {
            foreach ($matches[1] as $npc_id) {
                $npc_key = 'npc_' . md5($npc_id);
                if (!isset($npcs[$npc_key])) {
                    $npc_data = getNpcData($db, $npc_id, $post['uid']);
                    if ($npc_data) {
                        $npcs[$npc_key] = [
                            'npc_id' => $npc_id,
                            'data' => $npc_data,
                            'controller_uid' => $post['uid'],
                            'first_post' => $post['pid'],
                            'type' => 'npc'
                        ];
                    }
                }
            }
        }

        // Detectar técnicas usadas [tecnica=X]
        if (preg_match_all('#\[tecnica=([^\]]+)\]#si', $post['message'], $matches)) {
            foreach ($matches[1] as $tec_id) {
                $combat_actions[] = [
                    'type' => 'technique',
                    'technique_id' => $tec_id,
                    'user_uid' => $post['uid'],
                    'post_id' => $post['pid'],
                    'turn' => $turn_counter[$post['uid']],
                    'dateline' => $post['dateline']
                ];
            }
        }

        // Detectar códigos de vida/energía/haki
        if (preg_match('#\[vida=([^\]]+)\]#si', $post['message'], $match)) {
            $combat_actions[] = [
                'type' => 'vida_change',
                'value' => $match[1],
                'user_uid' => $post['uid'],
                'post_id' => $post['pid'],
                'turn' => $turn_counter[$post['uid']]
            ];
        }
        if (preg_match('#\[energia=([^\]]+)\]#si', $post['message'], $match)) {
            $combat_actions[] = [
                'type' => 'energia_change',
                'value' => $match[1],
                'user_uid' => $post['uid'],
                'post_id' => $post['pid'],
                'turn' => $turn_counter[$post['uid']]
            ];
        }
        if (preg_match('#\[haki=([^\]]+)\]#si', $post['message'], $match)) {
            $combat_actions[] = [
                'type' => 'haki_change',
                'value' => $match[1],
                'user_uid' => $post['uid'],
                'post_id' => $post['pid'],
                'turn' => $turn_counter[$post['uid']]
            ];
        }
    }

    // Obtener datos del personaje del usuario actual
    $my_character = getThreadCharacter($db, $tid, $current_uid);
    if (!$my_character) {
        // Si no hay personaje en el tema, obtener de la ficha principal
        $my_character = getMainCharacter($db, $current_uid);
    }

    $result['participants'] = array_values($participants);
    $result['npcs'] = array_values($npcs);
    $result['combat_log'] = $combat_actions;
    $result['my_turns'] = $my_turns;
    $result['my_character'] = $my_character;
    $result['turn_counter'] = $turn_counter;
    
    // Incluir posts para detección de ataques (solo datos necesarios)
    $posts_for_detection = [];
    foreach ($posts as $post) {
        $posts_for_detection[] = [
            'pid' => $post['pid'],
            'uid' => $post['uid'],
            'message' => $post['message'],
            'dateline' => $post['dateline']
        ];
    }
    $result['posts'] = $posts_for_detection;

    echo json_encode($result);
}

/**
 * Obtiene el personaje de un tema específico
 */
function getThreadCharacter($db, $tid, $uid) {
    $query = $db->query("
        SELECT * FROM mybb_op_thread_personaje 
        WHERE tid = '$tid' AND uid = '$uid'
        LIMIT 1
    ");
    
    if ($char = $db->fetch_array($query)) {
        return calculateFullStats($db, $char, $uid);
    }
    return null;
}

/**
 * Obtiene el personaje principal (ficha actual)
 */
function getMainCharacter($db, $uid) {
    $query = $db->query("
        SELECT * FROM mybb_op_fichas 
        WHERE fid = '$uid'
        LIMIT 1
    ");
    
    if ($char = $db->fetch_array($query)) {
        return calculateFullStats($db, $char, $uid);
    }
    return null;
}

/**
 * Calcula las estadísticas completas incluyendo pasivas y virtudes
 */
function calculateFullStats($db, $char, $uid) {
    // Verificar virtudes
    $virtudes = [];
    $query_virtudes = $db->query("
        SELECT v.* FROM mybb_op_virtudes_usuarios vu
        JOIN mybb_op_virtudes v ON v.virtud_id = vu.virtud_id
        WHERE vu.uid = '$uid'
    ");
    while ($v = $db->fetch_array($query_virtudes)) {
        $virtudes[$v['virtud_id']] = $v;
    }

    $nivel = intval($char['nivel']);
    
    // Stats base + pasivas
    $fuerza_completa = intval($char['fuerza']) + intval($char['fuerza_pasiva']);
    $resistencia_completa = intval($char['resistencia']) + intval($char['resistencia_pasiva']);
    $destreza_completa = intval($char['destreza']) + intval($char['destreza_pasiva']);
    $punteria_completa = intval($char['punteria']) + intval($char['punteria_pasiva']);
    $agilidad_completa = intval($char['agilidad']) + intval($char['agilidad_pasiva']);
    $reflejos_completa = intval($char['reflejos']) + intval($char['reflejos_pasiva']);
    $voluntad_completa = intval($char['voluntad']) + intval($char['voluntad_pasiva']);

    // Calcular extras
    $vitalidad_extra = floor(
        (intval($char['fuerza_pasiva']) * 6) + 
        (intval($char['resistencia_pasiva']) * 15) + 
        (intval($char['destreza_pasiva']) * 4) +
        (intval($char['agilidad_pasiva']) * 3) + 
        (intval($char['voluntad_pasiva']) * 1) + 
        (intval($char['punteria_pasiva']) * 2) + 
        (intval($char['reflejos_pasiva']) * 1)
    );

    $energia_extra = floor(
        (intval($char['destreza_pasiva']) * 4) + 
        (intval($char['agilidad_pasiva']) * 5) + 
        (intval($char['voluntad_pasiva']) * 1) + 
        (intval($char['fuerza_pasiva']) * 2) + 
        (intval($char['resistencia_pasiva']) * 4) + 
        (intval($char['punteria_pasiva']) * 5) + 
        (intval($char['reflejos_pasiva']) * 1)
    );

    $haki_extra = floor(intval($char['voluntad_pasiva']) * 10);

    $vitalidad_completa = intval($char['vitalidad']) + $vitalidad_extra + intval($char['vitalidad_pasiva']);
    $energia_completa = intval($char['energia']) + $energia_extra + intval($char['energia_pasiva']);
    $haki_completo = intval($char['haki']) + $haki_extra + intval($char['haki_pasiva']);

    // Aplicar virtudes
    if (isset($virtudes['V037'])) $vitalidad_completa += $nivel * 10; // Vigoroso 1
    elseif (isset($virtudes['V038'])) $vitalidad_completa += $nivel * 15; // Vigoroso 2
    elseif (isset($virtudes['V039'])) $vitalidad_completa += $nivel * 20; // Vigoroso 3

    if (isset($virtudes['V040'])) $energia_completa += $nivel * 10; // Hiperactivo 1
    elseif (isset($virtudes['V041'])) $energia_completa += $nivel * 15; // Hiperactivo 2

    if (isset($virtudes['V058'])) $haki_completo += $nivel * 5; // Espiritual 1
    elseif (isset($virtudes['V059'])) $haki_completo += $nivel * 10; // Espiritual 2

    return [
        'nombre' => $char['nombre'],
        'nivel' => $nivel,
        'stats' => [
            'fuerza' => $fuerza_completa,
            'resistencia' => $resistencia_completa,
            'destreza' => $destreza_completa,
            'punteria' => $punteria_completa,
            'agilidad' => $agilidad_completa,
            'reflejos' => $reflejos_completa,
            'voluntad' => $voluntad_completa
        ],
        'resources' => [
            'vitalidad' => $vitalidad_completa,
            'energia' => $energia_completa,
            'haki' => $haki_completo
        ],
        'raw' => $char
    ];
}

/**
 * Obtiene información básica de un usuario
 */
function getUserBasicInfo($db, $uid) {
    $query = $db->simple_select("users", "uid, username, avatar", "uid='$uid'");
    return $db->fetch_array($query);
}

/**
 * Obtiene datos de un NPC
 */
function getNpcData($db, $npc_id, $controller_uid) {
    // Buscar NPC por nombre o ID en la tabla principal
    $npc_id_escaped = $db->escape_string($npc_id);
    $query = $db->query("
        SELECT * FROM mybb_op_npcs 
        WHERE npc_id = '$npc_id_escaped' OR nombre LIKE '%$npc_id_escaped%'
        LIMIT 1
    ");
    
    if ($npc = $db->fetch_array($query)) {
        // Estructurar datos en formato compatible con el sistema de combate
        return [
            'npc_id' => $npc['npc_id'],
            'nombre' => $npc['nombre'],
            'nivel' => $npc['nivel'] ?? 1,
            'controller_uid' => $controller_uid,
            'stats' => [
                'fuerza' => $npc['fuerza'] ?? 0,
                'resistencia' => $npc['resistencia'] ?? 0,
                'destreza' => $npc['destreza'] ?? 0,
                'voluntad' => $npc['voluntad'] ?? 0,
                'punteria' => $npc['punteria'] ?? 0,
                'agilidad' => $npc['agilidad'] ?? 0,
                'reflejos' => $npc['reflejos'] ?? 0
            ],
            'resources' => [
                'vitalidad' => $npc['vitalidad'] ?? 100,
                'energia' => $npc['energia'] ?? 100,
                'haki' => $npc['haki'] ?? 0
            ]
        ];
    }
    
    // Si no encuentra en la BD, intentar decodificar si se pasó una URL del generador
    if (filter_var($npc_id, FILTER_VALIDATE_URL) || strpos($npc_id, 'generador') !== false) {
        $url_components = parse_url($npc_id);
        $query_params = [];
        if (isset($url_components['fragment'])) {
            $fragment_parts = explode('?', $url_components['fragment']);
            if (count($fragment_parts) > 1) {
                parse_str($fragment_parts[1], $query_params);
            }
        } elseif (isset($url_components['query'])) {
            parse_str($url_components['query'], $query_params);
        }

        $npc_data = null;
        if (isset($query_params['data']) && !empty($query_params['data'])) {
            $encoded_data = $query_params['data'];
            $candidates = array();
            $candidates[] = base64_decode(urldecode($encoded_data));
            $candidates[] = base64_decode(rawurldecode($encoded_data));
            $candidates[] = base64_decode(urldecode(str_replace(' ', '+', $encoded_data)));
            $candidates[] = base64_decode(str_replace(' ', '+', $encoded_data));

            $base64url = strtr($encoded_data, '-_', '+/');
            $pad = strlen($base64url) % 4;
            if ($pad > 0) {
                $base64url .= str_repeat('=', 4 - $pad);
            }
            $candidates[] = base64_decode(urldecode($base64url));
            $candidates[] = base64_decode($base64url);

            foreach ($candidates as $cand) {
                if ($cand === false || $cand === null) continue;
                $cand = preg_replace('/\xEF\xBB\xBF/', '', $cand);
                $try = json_decode($cand, true);
                if (is_array($try)) { $npc_data = $try; break; }
                $try2 = json_decode(utf8_encode($cand), true);
                if (is_array($try2)) { $npc_data = $try2; break; }
                if (strlen($cand) > 2 && strpos($cand, "\x00") !== false) {
                    $conv = @iconv('UTF-16', 'UTF-8', $cand);
                    if ($conv !== false) {
                        $try3 = json_decode($conv, true);
                        if (is_array($try3)) { $npc_data = $try3; break; }
                    }
                }
            }
        }

        if ($npc_data && is_array($npc_data)) {
            // Mapear datos mínimos para el sistema de combate
            $nombre = isset($npc_data['nombre']) ? $npc_data['nombre'] : 'NPC Sin Nombre';
            $nivel = isset($npc_data['nivel']) ? intval($npc_data['nivel']) : 1;
            $estadisticas = isset($npc_data['estadisticas']) ? $npc_data['estadisticas'] : [];

            $fuerza = isset($estadisticas['FUE']) ? intval($estadisticas['FUE']) : 0;
            $agilidad = isset($estadisticas['AGI']) ? intval($estadisticas['AGI']) : 0;
            $punteria = isset($estadisticas['PUN']) ? intval($estadisticas['PUN']) : 0;
            $destreza = isset($estadisticas['DES']) ? intval($estadisticas['DES']) : 0;
            $resistencia = isset($estadisticas['RES']) ? intval($estadisticas['RES']) : 0;
            $reflejos = isset($estadisticas['REF']) ? intval($estadisticas['REF']) : 0;
            $voluntad = isset($estadisticas['VOL']) ? intval($estadisticas['VOL']) : 0;

            // Recursos calculados igual que en el plugin
            $vitalidad = ($agilidad * 3) + ($destreza * 4) + ($fuerza * 6) + ($punteria * 1) + ($reflejos * 1) + ($resistencia * 15) + ($voluntad * 1);
            $energia = ($agilidad * 4) + ($destreza * 3) + ($fuerza * 1) + ($punteria * 6) + ($reflejos * 1) + ($resistencia * 3) + ($voluntad * 1);
            $haki = ($voluntad * 10);

            return [
                'npc_id' => $npc_id,
                'nombre' => $nombre,
                'nivel' => $nivel,
                'controller_uid' => $controller_uid,
                'stats' => [
                    'fuerza' => $fuerza,
                    'resistencia' => $resistencia,
                    'destreza' => $destreza,
                    'voluntad' => $voluntad,
                    'punteria' => $punteria,
                    'agilidad' => $agilidad,
                    'reflejos' => $reflejos
                ],
                'resources' => [
                    'vitalidad' => $vitalidad,
                    'energia' => $energia,
                    'haki' => $haki
                ]
            ];
        }
    }

    // Si no encuentra, devolver datos básicos
    return [
        'nombre' => $npc_id,
        'npc_id' => $npc_id,
        'nivel' => 1,
        'tipo' => 'unknown',
        'controller_uid' => $controller_uid,
        'stats' => [
            'fuerza' => 0,
            'resistencia' => 0,
            'destreza' => 0,
            'voluntad' => 0,
            'punteria' => 0,
            'agilidad' => 0,
            'reflejos' => 0
        ],
        'resources' => [
            'vitalidad' => 100,
            'energia' => 100,
            'haki' => 0
        ]
    ];
}

/**
 * Obtiene las técnicas del usuario
 */
function getMyTechniques($db, $uid) {
    $techniques = [];
    
    $query = $db->query("
        SELECT t.*
        FROM mybb_op_tecnicas t
        INNER JOIN mybb_op_tec_aprendidas ta ON t.tid = ta.tid
        WHERE ta.uid = '$uid'
        ORDER BY CAST(t.tier AS UNSIGNED) ASC, t.nombre ASC
    ");
    
    while ($tec = $db->fetch_array($query)) {
        $techniques[] = [
            'tid' => $tec['tid'],
            'nombre' => $tec['nombre'],
            'tier' => $tec['tier'],
            'clase' => $tec['clase'],
            'tipo' => $tec['tipo'],
            'estilo' => $tec['estilo'],
            'rama' => $tec['rama'],
            'energia' => intval($tec['energia']),
            'energia_turno' => intval($tec['energia_turno']),
            'haki' => intval($tec['haki']),
            'haki_turno' => intval($tec['haki_turno']),
            'enfriamiento' => intval($tec['enfriamiento']),
            'efectos' => $tec['efectos'],
            'descripcion' => $tec['descripcion']
        ];
    }
    
    // Obtener pasivas del usuario para cálculo de daño
    $pasivas = getPassiveTechniques($db, $uid);
    
    echo json_encode(['techniques' => $techniques, 'pasivas' => $pasivas]);
}

/**
 * Obtiene las técnicas pasivas del usuario
 */
function getPassiveTechniques($db, $uid) {
    $pasivas = [];
    
    $query = $db->query("
        SELECT t.*
        FROM mybb_op_tecnicas t
        INNER JOIN mybb_op_tec_aprendidas ta ON t.tid = ta.tid
        WHERE ta.uid = '$uid'
        AND (t.estilo LIKE '%pasiva%' OR t.clase LIKE '%pasiva%' OR t.tipo LIKE '%pasiva%')
        AND t.nombre NOT LIKE 'senda%'
        ORDER BY CAST(t.tier AS UNSIGNED) ASC, t.nombre ASC
    ");
    
    while ($tec = $db->fetch_array($query)) {
        $pasivas[] = [
            'tid' => $tec['tid'],
            'nombre' => $tec['nombre'],
            'tier' => $tec['tier'],
            'efectos' => $tec['efectos'],
            'rama' => $tec['rama']
        ];
    }
    
    return $pasivas;
}

/**
 * Obtiene datos de un personaje específico
 */
function getCharacterData($db, $tid, $target_uid) {
    $char = getThreadCharacter($db, $tid, $target_uid);
    if (!$char) {
        $char = getMainCharacter($db, $target_uid);
    }
    
    if ($char) {
        echo json_encode(['success' => true, 'character' => $char]);
    } else {
        echo json_encode(['error' => 'Personaje no encontrado']);
    }
}

/**
 * Verifica si el usuario puede usar el sistema de combate
 * Requiere haber usado [fichapersonaje] o [inventario] en un post anterior
 */
function checkCombatReady($db, $tid, $uid) {
    if (!$tid) {
        echo json_encode([
            'ready' => false, 
            'reason' => 'No se detectó el tema. Debes estar en un tema para usar el sistema de combate.'
        ]);
        return;
    }
    
    // Buscar en los posts del tema si hay [fichapersonaje] o [inventario] del usuario
    $query = $db->query("
        SELECT p.pid, p.message 
        FROM mybb_posts p
        WHERE p.tid = '$tid' AND p.uid = '$uid' AND p.visible = 1
        ORDER BY p.dateline ASC
    ");
    
    $hasCharacterSheet = false;
    $hasInventory = false;
    
    while ($post = $db->fetch_array($query)) {
        // Detectar [ficha] o [personaje]
        if (preg_match('#\[(ficha|personaje)\]#si', $post['message'])) {
            $hasCharacterSheet = true;
        }
        // Detectar [inventario]
        if (preg_match('#\[inventario\]#si', $post['message'])) {
            $hasInventory = true;
        }
        
        // Si ya encontramos al menos uno, podemos continuar
        if ($hasCharacterSheet || $hasInventory) {
            break;
        }
    }
    
    if (!$hasCharacterSheet && !$hasInventory) {
        echo json_encode([
            'ready' => false,
            'reason' => 'Debes realizar primero un post narrado utilizando el código [ficha] antes de comenzar un combate bélico. Esto carga tu personaje e inventario en el tema.',
            'hasCharacterSheet' => false,
            'hasInventory' => false
        ]);
        return;
    }
    
    echo json_encode([
        'ready' => true,
        'hasCharacterSheet' => $hasCharacterSheet,
        'hasInventory' => $hasInventory
    ]);
}

/**
 * Obtiene las armas equipadas del usuario en el tema actual
 */
function getMyWeapons($db, $uid) {
    global $mybb;
    $tid = $mybb->get_input('tid', MyBB::INPUT_INT);
    $weapons = [];
    
    // Primero intentar obtener el equipamiento del tema
    $equipamiento_json = null;
    
    if ($tid) {
        $query_equip = $db->query("
            SELECT equipamiento FROM mybb_op_equipamiento_personaje 
            WHERE tid = '$tid' AND uid = '$uid'
            LIMIT 1
        ");
        if ($row = $db->fetch_array($query_equip)) {
            $equipamiento_json = $row['equipamiento'];
        }
    }
    
    // Si no hay equipamiento en el tema, obtener de la ficha principal
    if (!$equipamiento_json) {
        $query_ficha = $db->query("
            SELECT equipamiento FROM mybb_op_fichas 
            WHERE fid = '$uid'
            LIMIT 1
        ");
        if ($row = $db->fetch_array($query_ficha)) {
            $equipamiento_json = $row['equipamiento'];
        }
    }
    
    // Parsear el JSON del equipamiento
    if ($equipamiento_json) {
        $equipamiento_data = json_decode($equipamiento_json, true);
        
        // Obtener los objetos equipados en los espacios
        if (isset($equipamiento_data['espacios']) && is_array($equipamiento_data['espacios'])) {
            foreach ($equipamiento_data['espacios'] as $espacio_id => $objeto_info) {
                if (isset($objeto_info['objetoId']) && !empty($objeto_info['objetoId'])) {
                    $objeto_id = $db->escape_string($objeto_info['objetoId']);
                    
                    // Verificar si es un arma
                    $query_obj = $db->query("
                        SELECT objeto_id, nombre, categoria, subcategoria, tier, 
                               dano, bloqueo, efecto, alcance, escalado, requisitos, imagen
                        FROM mybb_op_objetos 
                        WHERE objeto_id = '$objeto_id' AND categoria = 'armas'
                        LIMIT 1
                    ");
                    
                    if ($item = $db->fetch_array($query_obj)) {
                        $weapons[] = [
                            'objeto_id' => $item['objeto_id'],
                            'nombre' => $item['nombre'],
                            'nombre_base' => $item['nombre'],
                            'tier' => $item['tier'],
                            'categoria' => $item['categoria'],
                            'subcategoria' => $item['subcategoria'],
                            'dano' => $item['dano'],
                            'bloqueo' => $item['bloqueo'],
                            'efecto' => $item['efecto'],
                            'alcance' => $item['alcance'],
                            'escalado' => $item['escalado'],
                            'requisitos' => $item['requisitos'],
                            'imagen' => $item['imagen'],
                            'espacio' => $espacio_id
                        ];
                    }
                }
            }
        }
    }
    
    // Añadir opción "Sin arma" para ataques desarmados
    array_unshift($weapons, [
        'objeto_id' => 'UNARMED',
        'nombre' => 'Desarmado (puños)',
        'nombre_base' => 'Sin arma',
        'tier' => '0',
        'categoria' => 'armas',
        'subcategoria' => 'cuerpo a cuerpo',
        'dano' => '10+[FUEx1]+[DESx0,5] de [Daño contundente]',
        'bloqueo' => '5+[RESx0,5]+[REFx0,3] de [Daño Mitigado]',
        'efecto' => '',
        'alcance' => 'Cuerpo a cuerpo',
        'escalado' => '[Tasa de Acierto = Destreza]',
        'requisitos' => '',
        'imagen' => '',
        'cantidad' => 1
    ]);
    
    echo json_encode(['weapons' => $weapons]);
}
