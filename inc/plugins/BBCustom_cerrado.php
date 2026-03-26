<?php
/**
 * BBCustom Cerrado
 * Procesa BBCode [cerrado]
 * 
 * Marca el thread como cerrado y muestra un mensaje visual
 */

if (!defined("IN_MYBB")) {
    die("Direct initialization of this file is not allowed.");
}

// Hook
$plugins->add_hook("postbit", "BBCustom_cerrado_run");

function BBCustom_cerrado_info()
{
    return array(
        "name"          => "Cerrado BBCode",
        "description"   => "BBCode [cerrado] para cerrar un tema y mostrar aviso",
        "website"       => "",
        "author"        => "Cascabelles - Kurosame",
        "authorsite"    => "",
        "version"       => "2.0",
        "codename"      => "BBCustom_cerrado",
        "compatibility" => "*"
    );
}

function BBCustom_cerrado_activate() {}
function BBCustom_cerrado_deactivate() {}

function BBCustom_cerrado_run(&$post)
{
    global $db;
    
    // Protección contra procesamiento múltiple
    static $processed_pids = array();
    $current_pid = isset($post['pid']) ? $post['pid'] : md5($post['message']);
    
    if (in_array($current_pid, $processed_pids)) {
        return;
    }
    
    $message = &$post['message'];
    
    // Procesar [cerrado]
    while (preg_match('#\[cerrado\]#si', $message))
    {
        $tid = $post['tid'];
        
        // Cerrar el thread en la base de datos
        $db->query("UPDATE mybb_threads SET closed='1' WHERE tid='$tid'");
        
        // HTML del mensaje de cierre
        $cerrado_html = "
            <div style='background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%); 
                        padding: 30px; 
                        border-radius: 12px; 
                        text-align: center; 
                        margin: 20px 0; 
                        border-left: 5px solid #e74c3c;
                        box-shadow: 0 6px 12px rgba(0,0,0,0.3);'>
                <div style='font-size: 48px; margin-bottom: 15px;'>🔒</div>
                <h1 style='color: #ecf0f1; 
                           font-size: 28px; 
                           margin: 0 0 10px 0; 
                           text-shadow: 2px 2px 4px rgba(0,0,0,0.5);'>
                    Este tema ha sido cerrado
                </h1>
                <p style='color: #95a5a6; 
                          font-size: 14px; 
                          margin: 0;'>
                    No se pueden publicar más respuestas
                </p>
            </div>
        ";
        
        // Reemplazar [cerrado] con el HTML
        $message = preg_replace(
            '#\[cerrado\]#si',
            $cerrado_html,
            $message,
            1
        );
    }
    
    $processed_pids[] = $current_pid;
}
