<?php
define("IN_MYBB", 1);
define('THIS_SCRIPT', 'listado_tecnicas.php');
require_once "./../../global.php";
require "./../../inc/config.php";
require_once "./../functions/op_functions.php";

global $templates, $mybb, $db;
$uid = (int)$mybb->user['uid'];

// --- Formato de salida ---
$format = isset($mybb->input['format']) ? strtolower($mybb->input['format']) : 'txt';

// --- Tabla ---
$table = 'op_tecnicas';

// --- Columnas ---
$cols = [
    'tid', 'nombre', 'estilo', 'clase', 'tier',
    'rama', 'tipo', 'exclusiva', 'energia', 'energia_turno',
    'haki', 'haki_turno', 'enfriamiento', 'efectos',
    'requisitos', 'descripcion'
];

// --- Consulta ---
$query = $db->simple_select($table, '*', '', [
    'order_by' => 'nombre',
    'order_dir' => 'ASC',
]);

// Contadores separados
$count_total = 0;
$count_normales = 0;
$count_unicas = 0;

// Función de escape
$h = function ($v) use ($format) {
    if ($v === null) return '';
    $v = (string)$v;
    return ($format === 'html') ? htmlspecialchars_uni($v) : $v;
};

// === FUNCIÓN PARA DETECTAR TÉCNICAS ÚNICAS Y MEJORAS ===
function detectar_tecnica_unica($tid) {
    $tid = strtoupper(trim($tid));
    $resultado = [
        'es_unica' => false,
        'mejoras' => []
    ];

    // Detecta "XU" al principio, donde X = número (una o más cifras)
    if (preg_match('/^[0-9]+U/i', $tid)) {
        $resultado['es_unica'] = true;

        // Detectar sufijos de mejora
        $sufijo = strtoupper(substr($tid, -3)); // últimos caracteres posibles
        if (str_contains($sufijo, 'M')) $resultado['mejoras'][] = 'Daño/Mitigación';
        if (str_contains($sufijo, 'E')) $resultado['mejoras'][] = 'Energía';
        if (str_contains($sufijo, 'C')) $resultado['mejoras'][] = 'Cooldown';
    }

    return $resultado;
}

// =====================================================
// ===============    SALIDA CSV   =====================
// =====================================================
if ($format === 'csv') {
    $csv_rows = [];
    $csv_header = $cols;
    $csv_rows[] = $csv_header;
    $query_csv = $db->simple_select($table, '*', '', [
        'order_by' => 'nombre',
        'order_dir' => 'ASC',
    ]);
    while ($row_csv = $db->fetch_array($query_csv)) {
        $row_csv['exclusiva'] = (int)$row_csv['exclusiva'];
        $csv_row = [];
        foreach ($cols as $c) {
            $val = array_key_exists($c, $row_csv) ? $row_csv[$c] : '';
            $val = str_replace('"', '""', $val);
            if (strpos($val, ',') !== false || strpos($val, "\n") !== false || strpos($val, '"') !== false) {
                $val = '"' . str_replace('"', '""', $val) . '"';
            }
            $csv_row[] = $val;
        }
        $csv_rows[] = $csv_row;
    }
    $csv_string = '';
    foreach ($csv_rows as $csv_row) {
        $csv_string .= implode(',', $csv_row) . "\r\n";
    }
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: inline; filename="tecnicas.csv"');
    // Añadir BOM UTF-8 para compatibilidad con Excel
    echo "\xEF\xBB\xBF" . $csv_string;
    exit;
}

// =====================================================
// ===============    SALIDA HTML   ====================
// =====================================================

// --- Generar CSV plano para copiar ---
$csv_rows = [];
$csv_header = $cols;
$csv_rows[] = $csv_header;
$query_csv = $db->simple_select($table, '*', '', [
    'order_by' => 'nombre',
    'order_dir' => 'ASC',
]);
while ($row_csv = $db->fetch_array($query_csv)) {
    $row_csv['exclusiva'] = (int)$row_csv['exclusiva'];
    $csv_row = [];
    foreach ($cols as $c) {
        $val = array_key_exists($c, $row_csv) ? $row_csv[$c] : '';
        $val = str_replace('"', '""', $val);
        if (strpos($val, ',') !== false || strpos($val, "\n") !== false || strpos($val, '"') !== false) {
            $val = '"' . str_replace('"', '""', $val) . '"';
        }
        $csv_row[] = $val;
    }
    $csv_rows[] = $csv_row;
}
$csv_string = '';
foreach ($csv_rows as $csv_row) {
    $csv_string .= implode(',', $csv_row) . "\r\n";
}

// Si el template está vacío o se solicita ?format=csv, mostrar solo el CSV plano
if (empty(trim($templates->get("staff_listado_tecnicas"))) || isset($_GET['format']) && $_GET['format'] === 'csv') {
    header('Content-Type: text/plain; charset=UTF-8');
    echo $csv_string;
    exit;
}

while ($row = $db->fetch_array($query)) {
    $count_total++;
    $row['exclusiva'] = (int)$row['exclusiva'];
    $analisis = detectar_tecnica_unica($row['tid']);

    if ($analisis['es_unica']) {
        $count_unicas++;
        $numero = $count_unicas;
        $cabecera = "TÉCNICA ÚNICA #{$numero}";
        $claseExtra = ' unica';
        if (!empty($analisis['mejoras'])) {
            $cabecera .= " (Mejorada en: " . implode(', ', $analisis['mejoras']) . ")";
        }
    } else {
        $count_normales++;
        $numero = $count_normales;
        $cabecera = "TÉCNICA #{$numero}";
        $claseExtra = '';
    }

    $listado_tecnicas_html .= "<div class='tech{$claseExtra}'>\n";
    $listado_tecnicas_html .= "<div class='head'>===== <code class='sep'>{$cabecera}</code> [tid: ".$h($row['tid'])." | nombre: ".$h($row['nombre'])."] =====</div>\n";
    foreach ($cols as $c) {
        $val = array_key_exists($c, $row) ? $row[$c] : '';
        $listado_tecnicas_html .= "<div class='kv'><span class='k'>".$h($c).":</span> <span class='v'>".$h($val)."</span></div>\n";
    }
    $listado_tecnicas_html .= "</div>\n";
}

if ($count_total === 0) {
    $listado_tecnicas_html .= "<p>No se encontraron técnicas en <code>".htmlspecialchars_uni($table)."</code>.</p>";
} else {
    $listado_tecnicas_html .= "<hr><p><b>Técnicas normales:</b> {$count_normales} &nbsp; | &nbsp; <b>Técnicas únicas:</b> {$count_unicas} &nbsp; | &nbsp; <b>Total:</b> {$count_total}</p>";
}

// --- Render ---
if (is_staff($uid)) {
    // Forzar salida directa si la plantilla no muestra $listado_tecnicas_html
    if (strpos($templates->get("staff_listado_tecnicas"), '{\$listado_tecnicas_html}') !== false) {
        eval("$page = \"".$templates->get("staff_listado_tecnicas")."\"");
        output_page($page);
    } else {
        // Salida directa para asegurar visibilidad del botón CSV
        echo $listado_tecnicas_html;
        exit;
    }
} else {
    $mensaje_redireccion = "No tienes acceso para entrar a esta página. ¡Villano!";
    eval("$page = \"".$templates->get("op_redireccion")."\"");
    output_page($page);
}
