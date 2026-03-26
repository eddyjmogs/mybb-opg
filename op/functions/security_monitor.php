<?php
/**
 * MONITOR DE SEGURIDAD - Solo accesible para staff
 */

// Incluir configuración de MyBB
define("IN_MYBB", 1);
require_once('./global.php');
require_once('./../functions/op_functions.php');

global $templates, $mybb, $db;

global $templates, $mybb, $db;

// ============================================
// PÁGINA DE MONITOREO (Solo para staff)
// ============================================

if (isset($_GET['monitor']) && is_staff($mybb->user['uid'])) {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Monitor de Seguridad</title>
        <style>
            body { font-family: Arial; margin: 20px; }
            table { border-collapse: collapse; width: 100%; }
            th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
            th { background-color: #4CAF50; color: white; }
            .danger { background-color: #ffcccc; }
            .warning { background-color: #fff4cc; }
        </style>
    </head>
    <body>
        <h1>🛡️ Monitor de Seguridad</h1>
        
        <h2>IPs con más peticiones (última hora)</h2>
        <table>
            <tr>
                <th>IP</th>
                <th>Peticiones</th>
                <th>Última petición</th>
                <th>Estado</th>
            </tr>
            <?php
            $top_ips = get_top_request_ips(60);
            foreach ($top_ips as $ip_data) {
                $class = '';
                if ($ip_data['count'] > 500) $class = 'danger';
                else if ($ip_data['count'] > 200) $class = 'warning';
                
                echo "<tr class='$class'>";
                echo "<td>{$ip_data['ip']}</td>";
                echo "<td>{$ip_data['count']}</td>";
                echo "<td>{$ip_data['last_request']}</td>";
                echo "<td>" . ($ip_data['count'] > 500 ? '⚠️ ALTO' : ($ip_data['count'] > 200 ? '⚡ MEDIO' : '✅ NORMAL')) . "</td>";
                echo "</tr>";
            }
            ?>
        </table>
        
        <h2>Últimos eventos de seguridad</h2>
        <?php
        $query = $db->query("
            SELECT * FROM mybb_op_security_log 
            ORDER BY timestamp DESC 
            LIMIT 50
        ");
        ?>
        <table>
            <tr>
                <th>Timestamp</th>
                <th>Tipo</th>
                <th>Usuario</th>
                <th>IP</th>
                <th>Endpoint</th>
            </tr>
            <?php
            while ($event = $db->fetch_array($query)) {
                echo "<tr>";
                echo "<td>{$event['timestamp']}</td>";
                echo "<td>{$event['tipo']}</td>";
                echo "<td>{$event['username']} (UID: {$event['uid']})</td>";
                echo "<td>{$event['ip']}</td>";
                echo "<td>{$event['endpoint']}</td>";
                echo "</tr>";
            }
            ?>
        </table>
        
        <h2>Ver log completo</h2>
        <pre style="background: #f4f4f4; padding: 15px; overflow-x: auto;">
        <?php
        $log_file = dirname(__FILE__) . './../functions/fallosnef.log';
        if (file_exists($log_file)) {
            $lines = file($log_file);
            $recent_lines = array_slice($lines, -100); // Últimas 100 líneas
            echo htmlspecialchars(implode('', array_reverse($recent_lines)));
        } else {
            echo "No hay logs todavía. El archivo se creará automáticamente cuando ocurra el primer evento.";
        }
        ?>
        </pre>
    </body>
    </html>
    <?php
    exit;
} else {
    // Si no es staff, redirigir o mostrar error
    echo "Acceso denegado.";
    exit;
}
?>
