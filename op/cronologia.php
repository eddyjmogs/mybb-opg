<?php
define('IN_MYBB', 1);

// DEPURACIÓN TEMPORAL - mostrar todos los errores (puedes quitarlo en prod)
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

require_once "./../global.php";
require "./../inc/config.php";
require_once "./functions/op_functions.php";

if(!defined('THIS_SCRIPT')) {
    define('THIS_SCRIPT', 'cronologia.php');
}

add_breadcrumb('Cronología', 'cronologia.php');

/**
 * Calendario personalizado
 * - Años: >= 725
 * - Temporadas: Primavera, Verano, Otoño, Invierno
 * - 90 días por temporada
 * - Época: 725-Primavera-Día 1
 */

// Temporadas permitidas y orden
$SEASONS = [
    'primavera' => 'Primavera',
    'verano'    => 'Verano',
    'otono'     => 'Otoño',
    'invierno'  => 'Invierno',
];
$SEASON_KEYS = array_keys($SEASONS); // ['primavera','verano','otono','invierno']

// Inputs: y (año >= 725), t (temporada en slug)
$y = (int)$mybb->get_input('y', MyBB::INPUT_INT);
$t = strtolower((string)$mybb->get_input('t')); // slug temporada

if($y < 725) { $y = 725; }
if(!isset($SEASONS[$t])) {
    // valor por defecto
    $t = 'primavera';
}

// Constantes del sistema
$DAYS_PER_SEASON = 90;
$DAYS_PER_YEAR   = $DAYS_PER_SEASON * 4; // 360
$WEEK_LENGTH     = 7;

// Índice de temporada (0..3)
$season_index = array_search($t, $SEASON_KEYS, true);

// Función: weekday inicial de una temporada
$epoch_year = 725;
$epoch_season_index = 0; // primavera
$epoch_weekday = 1;

$offset_days = (($y - $epoch_year) * $DAYS_PER_YEAR) + ($season_index * $DAYS_PER_SEASON);
// weekday = (epoch_weekday + offset_days) ciclando 1..7
$start_dow = (($epoch_weekday - 1 + ($offset_days % $WEEK_LENGTH)) % $WEEK_LENGTH) + 1;

// Construcción de celdas
$cells = [];

// Huecos iniciales antes del día 1
for($i = 1; $i < $start_dow; $i++) {
    $cells[] = ['day' => '', 'today' => false];
}

// Días 1..90
for($d = 1; $d <= $DAYS_PER_SEASON; $d++) {
    $cells[] = [
        'day' => $d,
        'today' => false // Sin "hoy" real en calendario ficticio; puedes resaltar otro criterio si quieres
    ];
}

// Completar hasta múltiplo de 7
while(count($cells) % $WEEK_LENGTH !== 0) {
    $cells[] = ['day' => '', 'today' => false];
}

// Grid HTML
$grid_html = '';
foreach($cells as $cell) {
    $classes = 'gc-cell';
    if(!empty($cell['today'])) {
        $classes .= ' gc-today';
    }
    $day_label = $cell['day'] !== '' ? (int)$cell['day'] : '';
    $grid_html .= '<div class="'.$classes.'"><div class="gc-daynum">'.$day_label.'</div><div class="gc-events"></div></div>';
}

// Navegación temporada anterior / siguiente
function prev_season($y, $season_index) {
    if($season_index > 0) {
        return [$y, $season_index - 1];
    }
    // si estamos en la primera temporada del año
    $new_year = max(725, $y - 1);
    $new_season = 3; // invierno
    // evitar bajar de 725/primavera en navegación "prev"
    if($y == 725 && $season_index == 0) {
        $new_year = 725; $new_season = 0;
    }
    return [$new_year, $new_season];
}
function next_season($y, $season_index) {
    if($season_index < 3) {
        return [$y, $season_index + 1];
    }
    return [$y + 1, 0]; // pasa al año siguiente, primavera
}

list($prev_y, $prev_si) = prev_season($y, $season_index);
list($next_y, $next_si) = next_season($y, $season_index);

// Slugs de temporada para URLs
$prev_t = $SEASON_KEYS[$prev_si];
$next_t = $SEASON_KEYS[$next_si];

// Título
$season_title = $SEASONS[$t];
$title_text = $season_title.' '.$y;

// Toolbar / calendario HTML
$calendar_html = '
<div class="gc-wrap season-'.$t.'">
    <div class="gc-wrap">
    <div class="gc-toolbar">
        <a class="gc-btn" href="cronologia.php?y='.$prev_y.'&t='.$prev_t.'" rel="prev" aria-label="Temporada anterior">&larr;</a>
        <div class="gc-title">'.htmlspecialchars_uni($title_text).'</div>
        <a class="gc-btn" href="cronologia.php?y='.$next_y.'&t='.$next_t.'" rel="next" aria-label="Temporada siguiente">&rarr;</a>
        <div class="gc-selects" style="margin-left:.5rem; display:flex; gap:.5rem;">
        <form method="get" action="cronologia.php" class="gc-form" style="display:flex; gap:.5rem; align-items:center;">
            <label for="gc-y" class="sr-only">Año</label>
            <input id="gc-y" name="y" type="number" min="725" value="'.$y.'" style="height:32px; padding:.25rem .5rem; width:95px;" />
            <label for="gc-t" class="sr-only">Temporada</label>
            <select id="gc-t" name="t" style="height:32px; padding:.25rem .5rem;">
            <option value="primavera"'.($t==='primavera'?' selected':'').'>Primavera</option>
            <option value="verano"'.($t==='verano'?' selected':'').'>Verano</option>
            <option value="otono"'.($t==='otono'?' selected':'').'>Otoño</option>
            <option value="invierno"'.($t==='invierno'?' selected':'').'>Invierno</option>
            </select>
            <button class="gc-btn" type="submit">Ir</button>
        </form>
        </div>
    </div>
    <div class="gc-grid">'.$grid_html.'</div>
    </div>
</div>
';

// Hook opcional
$plugins->run_hooks('cronologia_page_start');

global $templates, $headerinclude, $header, $footer;
$cronologia = '';
eval('$cronologia = "'.$templates->get('op_cronologia').'";');

output_page($cronologia);
