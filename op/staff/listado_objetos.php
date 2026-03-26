<?php
/**
 * Listado RAW de objetos desde mybb_op_objetos
 * Solo muestra OBJETOS ORIGINALES y NO renderiza plantilla al final.
 */

define("IN_MYBB", 1);
define('THIS_SCRIPT', 'listado_objetos.php');
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

// Tabla (MyBB aplicará el prefijo)
$table = 'op_objetos';

// Columnas esperadas
$cols = [
    'id', 'objeto_id', 'categoria', 'subcategoria', 'nombre',
    'tier', 'imagen_id', 'imagen_avatar', 'berries', 'cantidadMaxima',
    'dano', 'bloqueo', 'efecto', 'alcance', 'exclusivo',
    'invisible', 'espacios', 'imagen', 'desbloquear', 'desbloqueado',
    'oficio', 'nivel', 'tiempo_creacion', 'requisitos', 'escalado',
    'editable', 'custom', 'descripcion', 'comerciable', 'crafteo_usuarios',
    'negro', 'negro_berries', 'fusion_tipo', 'engastes', 'fecha_creacion'
];

// Filtro SQL para mostrar SOLO ORIGINALES:
// No empieza por dígito, no contiene '-', no empieza por WAZ/MT
$where = "objeto_id NOT REGEXP '^[0-9]' 
          AND objeto_id NOT LIKE '%-%' 
          AND UPPER(objeto_id) NOT LIKE 'WAZ%' 
          AND UPPER(objeto_id) NOT LIKE 'MT%'";

// Consulta
$query = $db->simple_select($table, '*', $where, [
    'order_by' => 'objeto_id, nombre',
    'order_dir' => 'ASC'
]);

// Contadores
$count_total = 0;
$count_original = 0;

// Escapador
$h = function ($v) use ($format) {
    if ($v === null) return '';
    $v = (string)$v;
    return ($format === 'html') ? htmlspecialchars($v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') : $v;
};

if ($format === 'html') {
    echo "<!doctype html><meta charset='utf-8'><title>Listado de Objetos (Originales)</title>";
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
    $count_total++;

    // Normalización de tipos
    foreach (['id','tier','imagen_id','berries','cantidadMaxima','invisible','espacios','desbloquear','desbloqueado','nivel','tiempo_creacion','editable','custom','comerciable','negro','negro_berries','engastes'] as $intCol) {
        if (isset($row[$intCol]) && is_numeric($row[$intCol])) {
            $row[$intCol] = (int)$row[$intCol];
        }
    }
    if (isset($row['exclusivo'])) {
        $row['exclusivo'] = (int)$row['exclusivo'];
    }

    // Doble verificación en PHP (por si el motor ignora el WHERE en algún caso)
    $objeto_id_upper = strtoupper(trim($row['objeto_id']));
    $is_creado = (
        preg_match('/^[0-9]/', $objeto_id_upper) ||
        strpos($objeto_id_upper, '-') !== false ||
        str_starts_with($objeto_id_upper, 'WAZ') ||
        str_starts_with($objeto_id_upper, 'MT')
    );
    if ($is_creado) {
        // Saltar cualquier objeto creado que se colara
        continue;
    }

    $count_original++;
    $tipo = "OBJETO ORIGINAL #{$count_original}";
    $headNombre = !empty($row['nombre']) ? " | nombre: {$row['nombre']}" : '';

    if ($format === 'txt') {
        echo "===== {$tipo} [id: {$row['id']} | objeto_id: {$row['objeto_id']}{$headNombre}] =====\n";
        foreach ($cols as $c) {
            $val = array_key_exists($c, $row) ? $row[$c] : '';
            echo $c . ': ' . $h($val) . "\n";
        }
        echo "\n";
    } else {
        echo "<div class='item'>";
        $head = "===== <code class='sep'>{$tipo}</code> [id: ".$h($row['id'])." | objeto_id: ".$h($row['objeto_id']);
        if (!empty($row['nombre'])) $head .= " | nombre: ".$h($row['nombre']);
        $head .= "] =====";
        echo "<div class='head'>{$head}</div>";
        foreach ($cols as $c) {
            $val = array_key_exists($c, $row) ? $row[$c] : '';
            echo "<div class='kv'><span class='k'>{$h($c)}:</span> <span class='v'>{$h($val)}</span></div>";
        }
        echo "</div>";
    }
}

if ($count_total === 0) {
    if ($format === 'txt') {
        echo "No se encontraron objetos en {$table}.\n";
    } else {
        echo "<p>No se encontraron objetos en <code>{$h($table)}</code>.</p>";
    }
} else {
    if ($format === 'txt') {
        echo "=== Totales ===\n";
        echo "Objetos originales: {$count_original}\n";
        echo "Total (tras filtro): {$count_total}\n";
    } else {
        echo "<hr><p><b>Objetos originales:</b> {$count_original} &nbsp; | &nbsp; <b>Total (tras filtro):</b> {$count_total}</p>";
    }
}

// Importante: NO renderizar plantilla MyBB; finalizar aquí
exit;
