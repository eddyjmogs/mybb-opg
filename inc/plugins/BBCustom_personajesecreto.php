<?php
/**
 * BBCustom Personaje Secreto
 * Procesa BBCode [personajesecreto]
 * 
 * Modifica el avatar, nombre, apodo y rango del post
 * usando los datos de la ficha secreta del usuario
 */

if (!defined("IN_MYBB")) {
    die("Direct initialization of this file is not allowed.");
}

// Función de logging de errores
function BBCustom_personajesecreto_log_error($message, $context = array())
{
    try {
        $log_dir = MYBB_ROOT . 'inc/plugins/logs';
        $log_file = $log_dir . '/error_personajesecreto.txt';
        
        // Crear directorio si no existe
        if (!is_dir($log_dir)) {
            @mkdir($log_dir, 0755, true);
        }
        
        $timestamp = date('Y-m-d H:i:s');
        
        // JSON encode con manejo de errores
        $context_str = '';
        if (!empty($context)) {
            $json = @json_encode($context, JSON_PARTIAL_OUTPUT_ON_ERROR | JSON_UNESCAPED_UNICODE);
            if ($json !== false) {
                $context_str = ' | Context: ' . $json;
            } else {
                $context_str = ' | Context: [JSON encode failed]';
            }
        }
        
        $log_entry = "[{$timestamp}] {$message}{$context_str}\n";
        
        @file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
    } catch (Exception $e) {
        // Silenciar errores de logging para no causar problemas adicionales
    } catch (Error $e) {
        // Silenciar errores de logging para no causar problemas adicionales
    }
}

// Hook temprano: escribe faccionsecreta en BD ANTES de que functions_post.php la lea
$plugins->add_hook("postbit_start", "BBCustom_personajesecreto_prewrite");
// Hook de display: modifica los campos visuales del post
$plugins->add_hook("postbit", "BBCustom_personajesecreto_run");

function BBCustom_personajesecreto_info()
{
    return array(
        "name"          => "Personaje Secreto BBCode",
        "description"   => "BBCode [personajesecreto] para mostrar identidad secreta en posts",
        "website"       => "",
        "author"        => "Cascabelles",
        "authorsite"    => "",
        "version"       => "2.1",
        "codename"      => "BBCustom_personajesecreto",
        "compatibility" => "*"
    );
}

function BBCustom_personajesecreto_activate() {}
function BBCustom_personajesecreto_deactivate() {}

// Escribe faccionsecreta en mybb_posts antes de que build_postbit() ejecute sus queries
function BBCustom_personajesecreto_prewrite(&$post)
{
    global $db;

    try {
        if (!isset($post['message']) || stripos($post['message'], '[personajesecreto]') === false) {
            return;
        }

        $pid = isset($post['pid']) ? intval($post['pid']) : 0;
        $uid = isset($post['uid']) ? intval($post['uid']) : 0;
        if ($pid <= 0 || $uid <= 0) {
            return;
        }

        static $prewrite_cache = array();
        if (isset($prewrite_cache[$pid])) {
            return; // ya escrito en esta request
        }
        $prewrite_cache[$pid] = true;

        $query = $db->query("
            SELECT faccion FROM mybb_op_fichas_secret
            WHERE fid='{$uid}' AND secret_number='1'
            LIMIT 1
        ");
        $ficha = $db->fetch_array($query);

        if ($ficha) {
            $secret_faccion = isset($ficha['faccion']) ? trim($ficha['faccion']) : '';
            $db->query("UPDATE mybb_posts SET faccionsecreta='" . $db->escape_string($secret_faccion) . "' WHERE pid='{$pid}'");
        }
    } catch (Exception $e) {
        BBCustom_personajesecreto_log_error('prewrite Exception: ' . $e->getMessage(), array('pid' => isset($pid) ? $pid : 'unknown'));
    } catch (Error $e) {
        BBCustom_personajesecreto_log_error('prewrite Error: ' . $e->getMessage(), array('pid' => isset($pid) ? $pid : 'unknown'));
    }
}

function BBCustom_personajesecreto_run(&$post)
{
    global $db;
    
    try {
        // Verificar si el mensaje contiene [personajesecreto]
        if (!isset($post['message']) || stripos($post['message'], '[personajesecreto]') === false) {
            return;
        }
        
        $tid = isset($post['tid']) ? intval($post['tid']) : 0;
        $pid = isset($post['pid']) ? intval($post['pid']) : 0;
        $uid = isset($post['uid']) ? intval($post['uid']) : 0;
        if ($uid <= 0) {
            BBCustom_personajesecreto_log_error('UID inválido o no encontrado', array('uid' => $uid));
            return;
        }
            
        // Cache estático para evitar múltiples consultas
        static $secret_cache = array();
        $ficha_secreta = null;
        
        if (array_key_exists($uid, $secret_cache)) {
            $ficha_secreta = $secret_cache[$uid];
        } else {
            $query = $db->query("
                SELECT * FROM mybb_op_fichas_secret 
                WHERE fid='$uid' AND secret_number='1' 
                LIMIT 1
            ");
            $ficha_secreta = $db->fetch_array($query);
            $secret_cache[$uid] = $ficha_secreta;
        }

        // Si no existe ficha secreta, mostrar error
        if (!$ficha_secreta) {
            BBCustom_personajesecreto_log_error('Ficha secreta no encontrada', array('uid' => $uid));
            
            $error_html = '<div style="background: linear-gradient(135deg, #c0392b 0%, #e74c3c 100%); 
                                       color: white; 
                                       font-weight: bold; 
                                       padding: 15px; 
                                       border-radius: 8px; 
                                       text-align: center; 
                                       margin: 10px 0;
                                       box-shadow: 0 4px 6px rgba(0,0,0,0.3);">
                ⚠️ No tienes una ficha secreta configurada
            </div>';
            
            $post['message'] = preg_replace('#\[personajesecreto\]#si', $error_html, $post['message'], 1);
            return;
        }

        // Obtener datos de la ficha secreta de forma segura (compatible con PHP 8.1+ y valores NULL)
        $secret_nombre = isset($ficha_secreta['nombre']) && $ficha_secreta['nombre'] !== null ? trim($ficha_secreta['nombre']) : '';
        $secret_apodo = isset($ficha_secreta['apodo']) && $ficha_secreta['apodo'] !== null ? trim($ficha_secreta['apodo']) : '';
        $secret_rango = isset($ficha_secreta['rango']) && $ficha_secreta['rango'] !== null ? trim($ficha_secreta['rango']) : '';
        $secret_avatar = isset($ficha_secreta['avatar1']) && $ficha_secreta['avatar1'] !== null ? trim($ficha_secreta['avatar1']) : '';
        $secret_avatar_alt = isset($ficha_secreta['avatar2']) && $ficha_secreta['avatar2'] !== null ? trim($ficha_secreta['avatar2']) : '';
        
        // Usar avatar alternativo si el principal está vacío
        if (empty($secret_avatar) && !empty($secret_avatar_alt)) {
            $secret_avatar = $secret_avatar_alt;
        }
        
        // Si no hay avatar secreto, usar el avatar normal del post
        if (empty($secret_avatar) && !empty($post['avatar'])) {
            $secret_avatar = $post['avatar'];
        }
        
        // Preparar valores seguros para htmlspecialchars_uni
        $username_safe = isset($post['username']) ? $post['username'] : '';
        $apodo_safe = isset($post['apodo']) ? $post['apodo'] : '';
        $rango_safe = isset($post['rango']) ? $post['rango'] : '';
        
        // Activar identidad secreta en el post
        // Estas variables son usadas por los templates de MyBB
        $post['secret_identity_active'] = true;
        $post['secret_identity_nombre'] = !empty($secret_nombre) ? htmlspecialchars_uni($secret_nombre) : htmlspecialchars_uni($username_safe);
        $post['secret_identity_apodo'] = !empty($secret_apodo) ? htmlspecialchars_uni($secret_apodo) : htmlspecialchars_uni($apodo_safe);
        $post['secret_identity_rango'] = !empty($secret_rango) ? htmlspecialchars_uni($secret_rango) : htmlspecialchars_uni($rango_safe);
        $post['secret_identity_avatar'] = !empty($secret_avatar) ? htmlspecialchars_uni($secret_avatar) : '';
        
        // Eliminar el BBCode del mensaje (ya procesado)
        $post['message'] = preg_replace('#\[personajesecreto\]#si', '', $post['message']);
        
    } catch (Exception $e) {
        BBCustom_personajesecreto_log_error('Exception: ' . $e->getMessage(), array(
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'uid' => isset($uid) ? $uid : 'unknown'
        ));
        // No propagar el error, solo registrarlo
    } catch (Error $e) {
        BBCustom_personajesecreto_log_error('Error: ' . $e->getMessage(), array(
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'uid' => isset($uid) ? $uid : 'unknown'
        ));
        // No propagar el error, solo registrarlo
    }
}
