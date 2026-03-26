<?php
/**
 * BBCustom Recursos
 * Procesa BBCode [recursos="vida"/"energia"/"haki"]
 * 
 * Muestra los recursos actuales vs máximos:
 * VidaActual/VidaMáxima | EnergíaActual/EnergíaMáxima | HakiActual/HakiMáximo
 * 
 * Los valores máximos se obtienen automáticamente de la ficha del usuario
 */

if (!defined("IN_MYBB")) {
    die("Direct initialization of this file is not allowed.");
}

// Cargar librería de spoilers
require_once MYBB_ROOT . "inc/plugins/lib/spoiler.php";

// Hook para procesar DESPUÉS de MyBB
$plugins->add_hook("postbit", "BBCustom_recursos_run");

function BBCustom_recursos_info()
{
    return array(
        "name"          => "Recursos BBCode",
        "description"   => "BBCode [recursos] para mostrar Vida/Energía/Haki actuales vs máximos unificado",
        "website"       => "",
        "author"        => "Cascabelles",
        "authorsite"    => "",
        "version"       => "1.0",
        "codename"      => "BBCustom_recursos",
        "compatibility" => "*"
    );
}

function BBCustom_recursos_activate() {}
function BBCustom_recursos_deactivate() {}

function BBCustom_recursos_run(&$post)
{
    global $mybb, $db;
    
    // Protección contra procesamiento múltiple
    static $processed_pids = array();
    $current_pid = isset($post['pid']) ? $post['pid'] : md5($post['message']);
    
    if (in_array($current_pid, $processed_pids)) {
        return;
    }
    
    $message = &$post['message'];
    $user_uid = $post['uid'];
    $pid = $post['pid'];
    
    // Procesar BBCode [recursos="vida"/"energia"/"haki"]
    while (preg_match('#\[recursos=["\']?([0-9]+)/([0-9]+)/([0-9]+)["\']?\]#si', $message, $matches))
    {
        $vida_actual = intval($matches[1]);
        $energia_actual = intval($matches[2]);
        $haki_actual = intval($matches[3]);
        
        // Obtener los valores máximos de la ficha del usuario
        $ficha_data = BBCustom_recursos_get_ficha($user_uid, $db);
        
        if (!$ficha_data) {
            // Si no hay ficha, mostrar error
            $html = '<div style="background: #f44336; color: white; padding: 10px; border-radius: 5px; text-align: center;">
                ⚠️ No se encontró ficha para mostrar recursos
            </div>';
            
            $message = preg_replace(
                '#\[recursos=["\']?' . preg_quote($matches[1], '#') . '/' . preg_quote($matches[2], '#') . '/' . preg_quote($matches[3], '#') . '["\']?\]#si',
                $html,
                $message,
                1
            );
            continue;
        }
        
        // Calcular valores máximos (igual que en BBCustom_ficha.php)
        $stats = BBCustom_recursos_calc_stats($ficha_data, $user_uid, $db);
        
        $vida_max = $stats['vitalidad'];
        $energia_max = $stats['energia'];
        $haki_max = $stats['haki'];
        
        // Validar que los valores actuales no excedan los máximos
        if ($vida_actual > $vida_max) $vida_actual = $vida_max;
        if ($energia_actual > $energia_max) $energia_actual = $energia_max;
        if ($haki_actual > $haki_max) $haki_actual = $haki_max;
        
        // Calcular porcentajes para las barras
        $vida_percent = ($vida_max > 0) ? round(($vida_actual / $vida_max) * 100) : 0;
        $energia_percent = ($energia_max > 0) ? round(($energia_actual / $energia_max) * 100) : 0;
        $haki_percent = ($haki_max > 0) ? round(($haki_actual / $haki_max) * 100) : 0;
        
        // Generar HTML de las barras de recursos
        $recursos_html = BBCustom_recursos_generate_html(
            $vida_actual, $vida_max, $vida_percent,
            $energia_actual, $energia_max, $energia_percent,
            $haki_actual, $haki_max, $haki_percent,
            $pid
        );
        
        // Reemplazar el BBCode con el HTML
        $message = preg_replace(
            '#\[recursos=["\']?' . preg_quote($matches[1], '#') . '/' . preg_quote($matches[2], '#') . '/' . preg_quote($matches[3], '#') . '["\']?\]#si',
            $recursos_html,
            $message,
            1
        );
    }
    
    $processed_pids[] = $current_pid;
}

/**
 * Obtiene los datos de la ficha del usuario
 */
function BBCustom_recursos_get_ficha($uid, $db)
{
    $query = $db->query("SELECT * FROM mybb_op_fichas WHERE fid='$uid' LIMIT 1");
    return $db->fetch_array($query);
}

/**
 * Calcula las estadísticas completas (igual que en BBCustom_ficha.php)
 */
function BBCustom_recursos_calc_stats($ficha, $uid, $db)
{
    // Calcular stats pasivas
    $vitalidad_extra = (
        (intval($ficha['fuerza_pasiva']) * 6) + 
        (intval($ficha['resistencia_pasiva']) * 15) + 
        (intval($ficha['destreza_pasiva']) * 4) +
        (intval($ficha['agilidad_pasiva']) * 3) + 
        (intval($ficha['voluntad_pasiva']) * 1) + 
        (intval($ficha['punteria_pasiva']) * 2) + 
        (intval($ficha['reflejos_pasiva']) * 1)
    );

    $energia_extra = (
        (intval($ficha['fuerza_pasiva']) * 2) +
        (intval($ficha['resistencia_pasiva']) * 4) +
        (intval($ficha['punteria_pasiva']) * 5) +
        (intval($ficha['destreza_pasiva']) * 4) +
        (intval($ficha['agilidad_pasiva']) * 5) +
        (intval($ficha['reflejos_pasiva']) * 1) +
        (intval($ficha['voluntad_pasiva']) * 1)
    );

    $haki_extra = (intval($ficha['voluntad_pasiva']) * 10);

    $vitalidad = intval($ficha['vitalidad']) + $vitalidad_extra + intval($ficha['vitalidad_pasiva']);
    $energia = intval($ficha['energia']) + $energia_extra + intval($ficha['energia_pasiva']);
    $haki = intval($ficha['haki']) + $haki_extra + intval($ficha['haki_pasiva']);

    // Aplicar bonos de virtudes
    $nivel = intval($ficha['nivel']);
    
    // V037, V038, V039: Vigoroso 1, 2, 3
    $vigoroso_query = $db->query("
        SELECT virtud_id FROM mybb_op_virtudes_usuarios 
        WHERE uid='$uid' AND virtud_id IN ('V037', 'V038', 'V039')
    ");
    while ($v = $db->fetch_array($vigoroso_query)) {
        if ($v['virtud_id'] == 'V037') $vitalidad += $nivel * 10;
        if ($v['virtud_id'] == 'V038') $vitalidad += $nivel * 15;
        if ($v['virtud_id'] == 'V039') $vitalidad += $nivel * 20;
    }

    // V040, V041: Hiperactivo 1, 2
    $hiperactivo_query = $db->query("
        SELECT virtud_id FROM mybb_op_virtudes_usuarios 
        WHERE uid='$uid' AND virtud_id IN ('V040', 'V041')
    ");
    while ($v = $db->fetch_array($hiperactivo_query)) {
        if ($v['virtud_id'] == 'V040') $energia += $nivel * 10;
        if ($v['virtud_id'] == 'V041') $energia += $nivel * 15;
    }

    // V058, V059: Espiritual 1, 2
    $espiritual_query = $db->query("
        SELECT virtud_id FROM mybb_op_virtudes_usuarios 
        WHERE uid='$uid' AND virtud_id IN ('V058', 'V059')
    ");
    while ($v = $db->fetch_array($espiritual_query)) {
        if ($v['virtud_id'] == 'V058') $haki += $nivel * 5;
        if ($v['virtud_id'] == 'V059') $haki += $nivel * 10;
    }

    return array(
        'vitalidad' => $vitalidad,
        'energia' => $energia,
        'haki' => $haki
    );
}

/**
 * Genera el HTML de las barras de recursos
 */
function BBCustom_recursos_generate_html($vida_actual, $vida_max, $vida_percent, 
                                         $energia_actual, $energia_max, $energia_percent,
                                         $haki_actual, $haki_max, $haki_percent,
                                         $pid)
{
    // Colores según el porcentaje
    $vida_color = BBCustom_recursos_get_color($vida_percent);
    $energia_color = BBCustom_recursos_get_color($energia_percent);
    $haki_color = BBCustom_recursos_get_color($haki_percent);
    
    $html = '
    <div style="background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%); 
                padding: 15px; 
                border-radius: 10px; 
                margin: 10px 0; 
                box-shadow: 0 4px 6px rgba(0,0,0,0.3);">
        
        <!-- Vida -->
        <div style="margin-bottom: 10px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 5px;">
                <span style="color: #e74c3c; font-weight: bold; font-size: 14px;">❤️ VIDA</span>
                <span style="color: white; font-weight: bold; font-size: 14px;">'.$vida_actual.' / '.$vida_max.'</span>
            </div>
            <div style="background: #1a1a1a; 
                        border-radius: 10px; 
                        height: 20px; 
                        overflow: hidden; 
                        border: 2px solid #2c3e50;">
                <div style="background: '.$vida_color.'; 
                            width: '.$vida_percent.'%; 
                            height: 100%; 
                            transition: width 0.3s ease;
                            box-shadow: 0 0 10px rgba(231, 76, 60, 0.5);"></div>
            </div>
        </div>
        
        <!-- Energía -->
        <div style="margin-bottom: 10px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 5px;">
                <span style="color: #f39c12; font-weight: bold; font-size: 14px;">⚡ ENERGÍA</span>
                <span style="color: white; font-weight: bold; font-size: 14px;">'.$energia_actual.' / '.$energia_max.'</span>
            </div>
            <div style="background: #1a1a1a; 
                        border-radius: 10px; 
                        height: 20px; 
                        overflow: hidden; 
                        border: 2px solid #2c3e50;">
                <div style="background: '.$energia_color.'; 
                            width: '.$energia_percent.'%; 
                            height: 100%; 
                            transition: width 0.3s ease;
                            box-shadow: 0 0 10px rgba(243, 156, 18, 0.5);"></div>
            </div>
        </div>
        
        <!-- Haki -->
        <div>
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 5px;">
                <span style="color: #9b59b6; font-weight: bold; font-size: 14px;">🔮 HAKI</span>
                <span style="color: white; font-weight: bold; font-size: 14px;">'.$haki_actual.' / '.$haki_max.'</span>
            </div>
            <div style="background: #1a1a1a; 
                        border-radius: 10px; 
                        height: 20px; 
                        overflow: hidden; 
                        border: 2px solid #2c3e50;">
                <div style="background: '.$haki_color.'; 
                            width: '.$haki_percent.'%; 
                            height: 100%; 
                            transition: width 0.3s ease;
                            box-shadow: 0 0 10px rgba(155, 89, 182, 0.5);"></div>
            </div>
        </div>
        
    </div>
    
    <script>
        // Actualizar los avatares del post si existen
        var post = document.getElementById("post_'.$pid.'");
        if (post) {
            var vidaAvatar = post.querySelector(".personaje_vida2");
            if (vidaAvatar) vidaAvatar.innerText = "'.$vida_actual.' / '.$vida_max.'";
            
            var energiaAvatar = post.querySelector(".personaje_energia2");
            if (energiaAvatar) energiaAvatar.innerText = "'.$energia_actual.' / '.$energia_max.'";
            
            var hakiAvatar = post.querySelector(".personaje_haki2");
            if (hakiAvatar) hakiAvatar.innerText = "'.$haki_actual.' / '.$haki_max.'";
            
            var subVida = post.querySelector(".subBarraVida");
            if (subVida) subVida.style.width = "'.$vida_percent.'%";
            
            var subEnergia = post.querySelector(".subBarraEnergia");
            if (subEnergia) subEnergia.style.width = "'.$energia_percent.'%";
            
            var subHaki = post.querySelector(".subBarraHaki");
            if (subHaki) subHaki.style.width = "'.$haki_percent.'%";
        }
    </script>';
    
    return $html;
}

/**
 * Devuelve el color según el porcentaje de recursos
 */
function BBCustom_recursos_get_color($percent)
{
    if ($percent >= 70) {
        return 'linear-gradient(90deg, #27ae60 0%, #2ecc71 100%)'; // Verde
    } else if ($percent >= 40) {
        return 'linear-gradient(90deg, #f39c12 0%, #f1c40f 100%)'; // Amarillo
    } else if ($percent >= 20) {
        return 'linear-gradient(90deg, #e67e22 0%, #d35400 100%)'; // Naranja
    } else {
        return 'linear-gradient(90deg, #c0392b 0%, #e74c3c 100%)'; // Rojo
    }
}
