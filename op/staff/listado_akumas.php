<?php
/**
 * Listado RAW de akumas desde mybb_op_akumas
 * Uso: sube este archivo a la raíz del foro y visita /list_akumas.php
 * Opcional: /list_akumas.php?format=html para ver en HTML simple.
 */

define("IN_MYBB", 1);
define('THIS_SCRIPT', 'listado_akumas.php');
require_once "./../../global.php";
require "./../../inc/config.php";
require_once "./../functions/op_functions.php";

// Config formato
$format = (isset($_GET['format']) && $_GET['format'] === 'html') ? 'html' : 'txt';
if ($format === 'txt') {
    header('Content-Type: text/plain; charset=UTF-8');
} else {
    header('Content-Type: text/html; charset=UTF-8');
}

// Tabla con prefijo dinámico
$table = 'op_akumas';

// Columnas esperadas (según tu estructura y en orden)
$cols = [
    'akuma_id', 'nombre', 'subnombre', 'categoria', 'tier',
    'descripcion', 'uid', 'es_npc', 'es_oculta', 'ocupada',
    'imagen', 'detalles', 'dominio1', 'dominio2', 'dominio3',
    'pasiva1', 'pasiva2', 'pasiva3', 'reservas', 'reservasFecha'
];

// Consulta (ordena por nombre y subnombre para consistencia)
$query = $db->simple_select($table, '*', '', [
    'order_by' => 'nombre, subnombre',
    'order_dir' => 'ASC'
]);

$count = 0;

// Escape para HTML si hace falta
$h = function ($v) use ($format) {
    if ($v === null) return '';
    $v = (string)$v;
    return ($format === 'html') ? htmlspecialchars($v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') : $v;
};

if ($format === 'html') {
    echo "<!doctype html><meta charset='utf-8'><title>Akumas</title>";
    echo "<style>
        body{font:14px system-ui,Arial,sans-serif;line-height:1.4;padding:16px;color:#222;}
        .item{border-top:2px solid #999;margin-top:16px;padding-top:12px;}
        .head{font-weight:700;margin-bottom:6px;}
        .kv{margin:2px 0;}
        .k{color:#555;}
        .v{white-space:pre-wrap;}
        code.sep{color:#999;}
    </style>";
}

while ($row = $db->fetch_array($query)) {
    $count++;

    // Normaliza valores esperados
    // 'ocupada' es tinyint(1): lo convertimos a int para que sea claro en raw.
    if (isset($row['ocupada'])) {
        $row['ocupada'] = (int)$row['ocupada'];
    }
    // 'es_npc' y 'es_oculta' son varchar que suelen ser '0'/'1'; las dejamos tal cual para "raw".
    // 'tier' es int(5); lo forzamos a int si viene como string.
    if (isset($row['tier'])) {
        $row['tier'] = is_numeric($row['tier']) ? (int)$row['tier'] : $row['tier'];
    }

    if ($format === 'txt') {
        // Separador de akuma
        echo "===== AKUMA #{$count} [akuma_id: {$row['akuma_id']} | nombre: {$row['nombre']}";
        if (!empty($row['subnombre'])) echo " ({$row['subnombre']})";
        echo "] =====\n";
        foreach ($cols as $c) {
            $val = array_key_exists($c, $row) ? $row[$c] : '';
            echo $c . ': ' . $h($val) . "\n";
        }
        echo "\n"; // línea en blanco entre ítems
    } else {
        echo "<div class='item'>";
        $head = "===== <code class='sep'>AKUMA #{$count}</code> [akuma_id: ".$h($row['akuma_id'])." | nombre: ".$h($row['nombre']);
        if (!empty($row['subnombre'])) $head .= " (".$h($row['subnombre']).")";
        $head .= "] =====";
        echo "<div class='head'>{$head}</div>";
        foreach ($cols as $c) {
            $val = array_key_exists($c, $row) ? $row[$c] : '';
            echo "<div class='kv'><span class='k'>{$h($c)}:</span> <span class='v'>{$h($val)}</span></div>";
        }
        echo "</div>";
    }
}

if ($count === 0) {
    if ($format === 'txt') {
        echo "No se encontraron akumas en {$table}.\n";
    } else {
        echo "<p>No se encontraron akumas en <code>{$h($table)}</code>.</p>";
    }
}

// Render por plantilla solo en HTML
if (is_staff($uid)) {
    eval("\$page = \"".$templates->get("staff_listado_akumas")."\";");
    output_page($page);
} else {
    $mensaje_redireccion = "No tienes acceso para entrar a esta página. Villano!";
    eval("\$page = \"".$templates->get("op_redireccion")."\";");
    output_page($page);
}