<?php
/**
 * BBCustom Consumir
 * Procesa BBCode [consumir=OBJETO_ID]
 * 
 * Consume un objeto del inventario del usuario y muestra:
 * - Información del objeto consumido
 * - Cantidad restante
 * - Tooltip con detalles completos
 * 
 * Al crear el post, convierte [consumir=ID] en [consumido=X]
 * donde X es el contador en la tabla mybb_op_consumir
 */

if (!defined("IN_MYBB")) {
    die("Direct initialization of this file is not allowed.");
}

// Cargar librería de spoilers
require_once MYBB_ROOT . "inc/plugins/lib/spoiler.php";

// Hooks
$plugins->add_hook("postbit", "BBCustom_consumir_run");
$plugins->add_hook('datahandler_post_insert_post_end', 'BBCustom_consumir_newpost');
$plugins->add_hook('datahandler_post_insert_thread_end', 'BBCustom_consumir_newpost');

function BBCustom_consumir_info()
{
    return array(
        "name"          => "Consumir BBCode",
        "description"   => "BBCode [consumir=ID] para consumir objetos del inventario",
        "website"       => "",
        "author"        => "Cascabelles - Kurosame",
        "authorsite"    => "",
        "version"       => "2.0",
        "codename"      => "BBCustom_consumir",
        "compatibility" => "*"
    );
}

function BBCustom_consumir_activate() {}
function BBCustom_consumir_deactivate() {}

function BBCustom_consumir_run(&$post)
{
    global $db;
    
    // Protección contra procesamiento múltiple
    static $processed_pids = array();
    $current_pid = isset($post['pid']) ? $post['pid'] : md5($post['message']);
    
    if (in_array($current_pid, $processed_pids)) {
        return;
    }
    
    $message = &$post['message'];
    $pid = $post['pid'];
    $tid = $post['tid'];
    
    // Procesar [consumido=X] - mostrar el objeto ya consumido
    while (preg_match('#\[consumido=(\d+)\]#si', $message, $matches))
    {
        $counter = $matches[1];
        
        // Buscar en la base de datos
        $query = $db->query("
            SELECT * FROM mybb_op_consumir 
            WHERE pid='$pid' AND tid='$tid' AND counter='$counter'
            LIMIT 1
        ");
        
        $consumir = $db->fetch_array($query);
        
        if ($consumir) {
            $content = $consumir['content'];
        } else {
            $content = '<div style="background: #f44336; color: white; padding: 10px; border-radius: 5px; text-align: center;">
                ⚠️ Objeto consumido no encontrado
            </div>';
        }
        
        // Reemplazar con el contenido guardado
        $message = preg_replace(
            '#\[consumido=' . preg_quote($counter, '#') . '\]#si',
            $content,
            $message,
            1
        );
    }
    
    $processed_pids[] = $current_pid;
}

/**
 * Hook para cuando se crea un nuevo post
 * Consume el objeto del inventario y guarda el HTML
 */
function BBCustom_consumir_newpost(&$data)
{
    global $db;
    
    $uid = $data->post_insert_data['uid'];
    $pid = $data->return_values['pid'];
    $tid = $data->post_insert_data['tid'];
    $username = $data->post_insert_data['username'];
    $message = $data->post_insert_data['message'];
    $consumir_counter = 0;
    
    // Procesar todos los [consumir=ID]
    while (preg_match('#\[consumir=(.*?)\]#si', $message, $matches))
    {
        $objeto_id = $matches[1];
        
        // Verificar si el usuario tiene el objeto
        $query_inv = $db->query("
            SELECT cantidad FROM mybb_op_inventario 
            WHERE uid='$uid' AND objeto_id='$objeto_id'
            LIMIT 1
        ");
        
        $inventario = $db->fetch_array($query_inv);
        
        if (!$inventario) {
            // No tiene el objeto - marcar como inválido
            $message = preg_replace(
                "#\[consumir=" . preg_quote($objeto_id, '#') . "\]#si",
                "[consumibleinvalido=$objeto_id]",
                $message,
                1
            );
            continue;
        }
        
        $cantidad_actual = intval($inventario['cantidad']);
        
        // Obtener datos del objeto
        $query_obj = $db->query("
            SELECT * FROM mybb_op_objetos 
            WHERE objeto_id='$objeto_id'
            LIMIT 1
        ");
        
        $objeto = $db->fetch_array($query_obj);
        
        if (!$objeto) {
            $message = preg_replace(
                "#\[consumir=" . preg_quote($objeto_id, '#') . "\]#si",
                "[consumibleinvalido=$objeto_id]",
                $message,
                1
            );
            continue;
        }
        
        // Restar 1 del inventario
        $cantidad_nueva = $cantidad_actual - 1;
        
        if ($cantidad_nueva >= 1) {
            $db->query("
                UPDATE mybb_op_inventario 
                SET cantidad='$cantidad_nueva' 
                WHERE objeto_id='$objeto_id' AND uid='$uid'
            ");
        } else {
            // Si era el último, eliminar del inventario
            $db->query("
                DELETE FROM mybb_op_inventario 
                WHERE objeto_id='$objeto_id' AND uid='$uid'
            ");
        }
        
        // Quitar del equipamiento si está equipado
        BBCustom_consumir_remove_from_equipment($uid, $tid, $objeto_id, $db);
        
        // Generar HTML del objeto consumido
        $consumir_html = BBCustom_consumir_generate_html($objeto, $username, $cantidad_nueva);
        
        // Incrementar contador
        $consumir_counter += 1;
        
        // Guardar en la base de datos
        $consumir_content = $db->escape_string($consumir_html);
        $db->query("
            INSERT INTO mybb_op_consumir 
            (tid, pid, uid, counter, objeto_id, content) 
            VALUES ('$tid', '$pid', '$uid', '$consumir_counter', '$objeto_id', '$consumir_content')
        ");
        
        // Reemplazar [consumir=ID] con [consumido=X]
        $message = preg_replace(
            "#\[consumir=" . preg_quote($objeto_id, '#') . "\]#si",
            "[consumido=$consumir_counter]",
            $message,
            1
        );
    }
    
    // Si se procesó algún consumible, actualizar el mensaje
    if ($consumir_counter > 0) {
        // Convertir secuencias literales de escape a saltos de línea reales
        $message = str_replace(array('\\r\\n', '\\r', '\\n'), array("\r\n", "\r", "\n"), $message);
        $message_escaped = $db->escape_string($message);
        $db->query("
            UPDATE mybb_posts 
            SET message='$message_escaped' 
            WHERE pid='$pid'
        ");
    }
}

/**
 * Genera el HTML del objeto consumido
 */
function BBCustom_consumir_generate_html($objeto, $username, $cantidad_restante)
{
    $objeto_id = $objeto['objeto_id'];
    $nombre = htmlspecialchars_uni($objeto['nombre']);
    $subcategoria = htmlspecialchars_uni($objeto['subcategoria']);
    $tier = intval($objeto['tier']);
    $descripcion = nl2br(htmlspecialchars_uni($objeto['descripcion']));
    $imagen_id = $objeto['imagen_id'];
    $imagen_avatar = $objeto['imagen_avatar'];
    $requisitos = $objeto['requisitos'];
    $escalado = $objeto['escalado'];
    $dano = $objeto['dano'];
    $efecto = $objeto['efecto'];
    
    // Color según tier
    $tier_colors = array(
        1 => '#808080', // Gris
        2 => '#4dfe45', // Verde
        3 => '#457bfe', // Azul
        4 => '#cf44ff', // Púrpura
        5 => '#febb46', // Dorado
    );
    $color_tier = isset($tier_colors[$tier]) ? $tier_colors[$tier] : '#faa500'; // Naranja por defecto
    
    // Determinar imagen
    $subcategoria_lower = strtolower(str_replace(' ', '_', $subcategoria));
    $img_type = ($subcategoria_lower == 'tecnicas') ? 'gif' : 'jpg';
    $imagen_nombre = "{$subcategoria_lower}_{$imagen_id}_One_Piece_Gaiden_Foro_Rol.{$img_type}";
    $imagen_url = $imagen_avatar ?: "/images/op/iconos/{$imagen_nombre}";
    
    // Secciones opcionales
    $requisitos_html = $requisitos ? "<div style='padding: 8px; border-bottom: 1px solid #444; background: #222;'><strong>Requisitos:</strong> $requisitos</div>" : '';
    $escalado_html = $escalado ? "<div style='padding: 8px; border-bottom: 1px solid #444; background: #222;'><strong>Escalado:</strong> $escalado</div>" : '';
    $dano_html = $dano ? "<div style='padding: 8px; border-bottom: 1px solid #444; background: #222;'><strong>Daño:</strong> $dano</div>" : '';
    $efecto_html = $efecto ? "<div style='padding: 8px; background: #252525; border-top: 2px solid {$color_tier};'><strong>Efecto:</strong> $efecto</div>" : '';
    
    // 1. Bloque de la Imagen (Izquierda)
    $objeto_visual = "
        <div style='text-align: center; flex: 0 0 auto; min-width: 150px; margin: 10px;'>
            <img src='{$imagen_url}' alt='{$nombre}' style='width: 120px; height: 120px; border-radius: 10px; border: 3px solid {$color_tier}; box-shadow: 0 4px 8px rgba(0,0,0,0.4);' />
            <div style='margin-top: 10px; font-weight: bold; color: {$color_tier}; text-shadow: 1px 1px 2px black;'>{$nombre}</div>
        </div>
    ";
    
    // 2. Bloque de la Tabla de Detalles (Derecha)
    $detalles_html = "
        <div style='flex: 1 1 300px; background: #2c3e50; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 8px rgba(0,0,0,0.3);'>
            <div style='background: {$color_tier}; padding: 10px; text-align: center; font-weight: bold; color: white; text-shadow: 1px 1px 2px black;'>
                {$nombre} ({$objeto_id})
            </div>
            {$requisitos_html}
            {$escalado_html}
            <div style='padding: 10px; background: #1a1a1a; color: #ddd; line-height: 1.4;'>{$descripcion}</div>
            {$dano_html}
            {$efecto_html}
            <div style='background: {$color_tier}; padding: 5px; text-align: center; font-size: 11px; color: white; text-shadow: 1px 1px 2px black;'>
                {$subcategoria} - Tier {$tier}
            </div>
        </div>
    ";

    // 3. Contenedor principal del Spoiler (Agrupa la imagen y los detalles con Flexbox)
    $tooltip_content = "
        <div style='display: flex; flex-wrap: wrap; align-items: center; justify-content: center; background: #967e95; padding: 15px; border-radius: 5px; border: 2px solid #222;'>
            {$objeto_visual}
            {$detalles_html}
        </div>
    ";
    
    // 4. Texto General de Consumo (Fuera del spoiler)
    $info_html = "
        <div style='text-align: center; margin: 15px 0;'>
            <strong style='color: #e74c3c;'>{$username}</strong> ha consumido 
            <strong style='color: {$color_tier};'>{$nombre}</strong>
            <br />
            <span style='color: #95a5a6;'>Cantidad restante: <strong style='color: white;'>{$cantidad_restante}</strong></span>
        </div>
    ";
    
    // 5. Configuración del Spoiler
    $spoiler_html = create_custom_spoiler("{$nombre} ha sido consumido", $tooltip_content, array(
        'bg_color' => '#ca3c78', // Color rosado/magenta fijo de la imagen
        'text_color' => 'white',
        'open' => false
    ));
    
    return "<hr style='border: 1px solid #444; margin: 20px 0;' />{$info_html}{$spoiler_html}<hr style='border: 1px solid #444; margin: 20px 0;' />";
}

/**
 * Quita un objeto del equipamiento si está equipado
 */
function BBCustom_consumir_remove_from_equipment($uid, $tid, $objeto_id, $db)
{
    // 1. Quitar del equipamiento del thread (mybb_op_equipamiento_personaje)
    $query = $db->query("
        SELECT equipamiento FROM mybb_op_equipamiento_personaje 
        WHERE uid='$uid' AND tid='$tid'
        LIMIT 1
    ");
    
    $equip_data = $db->fetch_array($query);
    
    if ($equip_data && $equip_data['equipamiento']) {
        $equipamiento = json_decode($equip_data['equipamiento'], true);
        
        if ($equipamiento && BBCustom_consumir_remove_from_json($equipamiento, $objeto_id)) {
            $equipamiento_json = json_encode($equipamiento);
            $equipamiento_escaped = $db->escape_string($equipamiento_json);
            
            $db->query("
                UPDATE mybb_op_equipamiento_personaje 
                SET equipamiento='$equipamiento_escaped' 
                WHERE uid='$uid' AND tid='$tid'
            ");
        }
    }
    
    // 2. Quitar del equipamiento general de la ficha (mybb_op_fichas)
    $query_ficha = $db->query("
        SELECT equipamiento FROM mybb_op_fichas 
        WHERE fid='$uid'
        LIMIT 1
    ");
    
    $ficha_data = $db->fetch_array($query_ficha);
    
    if ($ficha_data && $ficha_data['equipamiento']) {
        $equipamiento_ficha = json_decode($ficha_data['equipamiento'], true);
        
        if ($equipamiento_ficha && BBCustom_consumir_remove_from_json($equipamiento_ficha, $objeto_id)) {
            $equipamiento_ficha_json = json_encode($equipamiento_ficha);
            $equipamiento_ficha_escaped = $db->escape_string($equipamiento_ficha_json);
            
            $db->query("
                UPDATE mybb_op_fichas 
                SET equipamiento='$equipamiento_ficha_escaped' 
                WHERE fid='$uid'
            ");
        }
    }
}

/**
 * Quita un objeto del JSON de equipamiento
 * Retorna true si se modificó, false si no
 */
function BBCustom_consumir_remove_from_json(&$equipamiento, $objeto_id)
{
    $modificado = false;
    
    // Verificar si está en "ropa"
    if (isset($equipamiento['ropa']) && $equipamiento['ropa'] === $objeto_id) {
        $equipamiento['ropa'] = '';
        $modificado = true;
    }
    
    // Verificar si está en "bolsa"
    if (!$modificado && isset($equipamiento['bolsa']) && $equipamiento['bolsa'] === $objeto_id) {
        $equipamiento['bolsa'] = '';
        $modificado = true;
    }
    
    // Verificar si está en "espacios"
    if (!$modificado && isset($equipamiento['espacios']) && is_array($equipamiento['espacios'])) {
        foreach ($equipamiento['espacios'] as $key => $espacio) {
            if (isset($espacio['objetoId']) && $espacio['objetoId'] === $objeto_id) {
                // Eliminar este espacio
                unset($equipamiento['espacios'][$key]);
                $modificado = true;
                break; // Solo quitar la primera ocurrencia
            }
        }
        
        // Reindexar el array de espacios si se eliminó algo
        if ($modificado && !empty($equipamiento['espacios'])) {
            $equipamiento['espacios'] = array_values($equipamiento['espacios']);
        }
    }
    
    return $modificado;
}
