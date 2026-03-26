<?php
/**
 * BBCustom Akuma
 * Procesa BBCode [akuma=ID]
 * 
 * Muestra los detalles de una Akuma no Mi desde la base de datos
 * Ejemplo: [akuma=gomu_gomu]
 */

if (!defined("IN_MYBB")) {
    die("Direct initialization of this file is not allowed.");
}

// Hook
$plugins->add_hook("postbit", "BBCustom_akuma_run");

function BBCustom_akuma_info()
{
    return array(
        "name"          => "Akuma BBCode",
        "description"   => "BBCode [akuma=ID] para mostrar detalles de Akumas no Mi",
        "website"       => "",
        "author"        => "Cascabelles - Kurosame",
        "authorsite"    => "",
        "version"       => "2.0",
        "codename"      => "BBCustom_akuma",
        "compatibility" => "*"
    );
}

function BBCustom_akuma_activate() {}
function BBCustom_akuma_deactivate() {}

function BBCustom_akuma_run(&$post)
{
    global $db, $parser;
    
    // Protección contra procesamiento múltiple
    static $processed_pids = array();
    $current_pid = isset($post['pid']) ? $post['pid'] : md5($post['message']);
    
    if (in_array($current_pid, $processed_pids)) {
        return;
    }
    
    $message = &$post['message'];
    
    // Procesar [akuma=ID]
    while (preg_match('#\[akuma=(.*?)\]#si', $message, $matches))
    {
        $akuma_id = $matches[1];
        
        // Buscar en la base de datos
        $query = $db->query("SELECT * FROM mybb_op_akumas WHERE akuma_id='$akuma_id' LIMIT 1");
        $akuma = $db->fetch_array($query);
        
        if ($akuma) {
            // Parsear el contenido de detalles (puede contener BBCode)
            $parser_options = array(
                "allow_html" => 1,
                "allow_mycode" => 1,
                "allow_smilies" => 0,
                "allow_imgcode" => 0,
                "allow_videocode" => 0,
                "filter_badwords" => 0
            );
            
            $detalles = $akuma['detalles'];
            $akuma_html = $parser->parse_message($detalles, $parser_options);
            
            // Reemplazar [akuma=ID] con el HTML parseado
            $message = preg_replace(
                '#\[akuma=' . preg_quote($akuma_id, '#') . '\]#si',
                $akuma_html,
                $message,
                1
            );
        } else {
            // Akuma no encontrada - mostrar error
            $message = preg_replace(
                '#\[akuma=' . preg_quote($akuma_id, '#') . '\]#si',
                '[akumainvalida=' . $akuma_id . ']',
                $message,
                1
            );
        }
    }
    
    $processed_pids[] = $current_pid;
}
