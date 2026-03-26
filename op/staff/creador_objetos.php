<?php
define("IN_MYBB", 1);
define('THIS_SCRIPT', 'creador_objetos.php');
require_once "./../../global.php";
require "./../../inc/config.php";
require_once "./../functions/op_functions.php";

global $templates, $mybb, $db;
$uid = (int)$mybb->user['uid'];

// --- Parámetros opcionales ---
$format = isset($mybb->input['format']) ? strtolower((string)$mybb->input['format']) : 'html';
$limit  = isset($mybb->input['limit'])  ? max(1, min(500, (int)$mybb->input['limit'])) : 300;
$offset = isset($mybb->input['offset']) ? max(0, (int)$mybb->input['offset']) : 0;
$prefix = '';
if (!empty($mybb->input['prefix'])) {
    $prefix = preg_replace('/[^A-Za-z]/', '', (string)$mybb->input['prefix']);
}

// --- WHERE ---
$where ="
    categoria='armas'
    AND objeto_id REGEXP '^[A-Za-z]+'
    AND objeto_id NOT LIKE '%-%'
    AND objeto_id NOT LIKE '%WAZ%'
    AND objeto_id NOT LIKE '%MT%'
";
if ($prefix !== '') {
    $where .= " AND objeto_id REGEXP '^".$db->escape_string($prefix).".*'";
}

// --- Consulta ---
$table = TABLE_PREFIX . "op_objetos"; // ← portabilidad
$sql = "
    SELECT id, objeto_id, nombre, categoria, tier, alcance, efecto, dano, bloqueo, escalado, requisitos
    FROM {$table}
    WHERE {$where}
    ORDER BY objeto_id ASC
    LIMIT {$offset}, {$limit}
";
$query = $db->query($sql);

// --- Helper de parseo (válido para daño y bloqueo) ---
/**
 * Parsea cadenas tipo:
 * "50+[AGIx1,7]+[DESx0,7] de [Daño cortante]"
 * Devuelve:
 *  ['base'=>'50','tipo'=>'Daño cortante','scalings'=>[['param'=>'AGI','valor'=>'1,7'], ...]]
 */
function parse_stats_str($str)
{
    $out = ['base'=>'', 'tipo'=>'', 'scalings'=>[]];
    if (!is_string($str) || $str === '') return $out;

    $s = trim($str);

    // Base numérica al inicio
    if (preg_match('/^\s*(\d+)/u', $s, $m)) {
        $out['base'] = $m[1];
    }

    // Tipo al final: "de [texto]"
    if (preg_match('/de\s*\[(.*?)\]\s*$/iu', $s, $m)) {
        $out['tipo'] = trim($m[1]);
    }

    // Escalados: [AGI x 1,7] / [DESx0.7] / [FUE x2] / [VOLx1,25] / [RESx...] / [PUNx...]
    if (preg_match_all('/\[\s*(AGI|DES|FUE|VOL|RES|PUN)\s*x\s*([\d\.,]+)\s*\]/iu', $s, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $mm) {
            $out['scalings'][] = [
                'param' => strtoupper($mm[1]),
                'valor' => trim($mm[2])
            ];
        }
    }

    return $out;
}

// --- Construir items ---
$items = [];
while ($row = $db->fetch_array($query)) {
    $items[] = [
        'id'            => (int)$row['id'],
        'objeto_id'     => (string)$row['objeto_id'],
        'nombre'        => (string)$row['nombre'],
        'categoria'     => (string)$row['categoria'],
        'tier'          => (string)$row['tier'],
        'alcance'       => (string)$row['alcance'],
        'efecto'        => (string)$row['efecto'],
        'dano'          => (string)$row['dano'],
        'bloqueo'       => (string)$row['bloqueo'],
        'escalado'      => (string)$row['escalado'],
        'requisitos'    => (string)$row['requisitos']
    ];
}

// --- JSON ---
if ($format === 'json') {
    if (!is_staff($uid)) {
        header('Content-Type: application/json; charset=utf-8', true, 403);
        echo json_encode(['error' => 'No autorizado'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: max-age=60, public');
    echo json_encode([
        'count'  => count($items),
        'limit'  => $limit,
        'offset' => $offset,
        'prefix' => $prefix,
        'items'  => $items,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

// --- Filas HTML ---
$op_objetos_rows = '';
foreach ($items as $obj) {
    $id             = (int)$obj['id'];
    $objeto_id      = htmlspecialchars_uni($obj['objeto_id']);
    $nombre         = htmlspecialchars_uni($obj['nombre']);
    $categoria      = htmlspecialchars_uni($obj['categoria']);
    $tier           = htmlspecialchars_uni($obj['tier']);
    $alcance        = htmlspecialchars_uni($obj['alcance']);
    $efecto         = htmlspecialchars_uni($obj['efecto']);
    $dano_raw       = (string)$obj['dano'];
    $bloqueo_raw    = (string)$obj['bloqueo'];
    $escalado       = htmlspecialchars_uni($obj['escalado']);
    $requisitos     = htmlspecialchars_uni($obj['requisitos']);

    // Parseo de daño y bloqueo
    $parsed_dano     = parse_stats_str($dano_raw);
    $parsed_bloqueo  = parse_stats_str($bloqueo_raw);

    $dano_base   = htmlspecialchars_uni($parsed_dano['base']);
    $tipo_dano   = htmlspecialchars_uni($parsed_dano['tipo']);
    $bloqueo_base= htmlspecialchars_uni($parsed_bloqueo['base']);
    $tipo_bloqueo= htmlspecialchars_uni($parsed_bloqueo['tipo']);

    // Escalados (2 para cada uno; añade más si necesitas)
    $esc1_param = $esc1_valor = $esc2_param = $esc2_valor = '';
    if (!empty($parsed_dano['scalings'][0])) {
        $esc1_param = htmlspecialchars_uni($parsed_dano['scalings'][0]['param']);
        $esc1_valor = htmlspecialchars_uni($parsed_dano['scalings'][0]['valor']);
    }
    if (!empty($parsed_dano['scalings'][1])) {
        $esc2_param = htmlspecialchars_uni($parsed_dano['scalings'][1]['param']);
        $esc2_valor = htmlspecialchars_uni($parsed_dano['scalings'][1]['valor']);
    }

    $esc1b_param = $esc1b_valor = $esc2b_param = $esc2b_valor = '';
    if (!empty($parsed_bloqueo['scalings'][0])) {
        $esc1b_param = htmlspecialchars_uni($parsed_bloqueo['scalings'][0]['param']);
        $esc1b_valor = htmlspecialchars_uni($parsed_bloqueo['scalings'][0]['valor']);
    }
    if (!empty($parsed_bloqueo['scalings'][1])) {
        $esc2b_param = htmlspecialchars_uni($parsed_bloqueo['scalings'][1]['param']);
        $esc2b_valor = htmlspecialchars_uni($parsed_bloqueo['scalings'][1]['valor']);
    }

    $dano_vis     = htmlspecialchars_uni($dano_raw);
    $bloqueo_vis  = htmlspecialchars_uni($bloqueo_raw);

    $op_objetos_rows .= "
        <tr>
        <td class=\"op-table__nombre\">{$nombre}</td>
        <td class=\"op-table__tier\">{$tier}</td>

        <td class=\"op-table__dano-base\">{$dano_base}</td>
        <td class=\"op-table__esc1p\">{$esc1_param}</td>
        <td class=\"op-table__esc1v\">{$esc1_valor}</td>
        <td class=\"op-table__esc2p\">{$esc2_param}</td>
        <td class=\"op-table__esc2v\">{$esc2_valor}</td>
        <td class=\"op-table__tipo-dano\">{$tipo_dano}</td>

        <td class=\"op-table__bloqueo-base\">{$bloqueo_base}</td>
        <td class=\"op-table__esc1bp\">{$esc1b_param}</td>
        <td class=\"op-table__esc1bv\">{$esc1b_valor}</td>
        <td class=\"op-table__esc2bp\">{$esc2b_param}</td>
        <td class=\"op-table__esc2bv\">{$esc2b_valor}</td>
        <td class=\"op-table__tipo-bloqueo\">{$tipo_bloqueo}</td>

        <td class=\"op-table__alcance\">{$alcance}</td>
        <td class=\"op-table__efecto\">{$efecto}</td>
        <td class=\"op-table__escalado\">{$escalado}</td>
        <td class=\"op-table__requisitos\">{$requisitos}</td>
        </tr>
    ";
}

if (is_staff($uid)) {
    eval("\$page = \"".$templates->get("staff_creador_objetos")."\";");
    output_page($page);
} else {
    $mensaje_redireccion = "No tienes acceso para entrar a esta página. Villano!";
    eval("\$page = \"".$templates->get("op_redireccion")."\";");
    output_page($page);
}
?>