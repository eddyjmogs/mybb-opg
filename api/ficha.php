<?php
// ficha.php - Manejo de fichas de personajes
if(!defined('API_LOADED')) exit;

// GET /ficha
if($_SERVER['REQUEST_METHOD']==='GET' && $route==='ficha'){
  $uid = bearer_uid(); if(!$uid) json(['error'=>'No autorizado'],401);
  
  // Obtener UID del parámetro o usar el del usuario autenticado
  $ficha_uid = isset($_GET['uid']) ? (int)$_GET['uid'] : $uid;
  
  $user = get_user($ficha_uid);
  if(!$user) json(['error'=>'Usuario no encontrado'],404);
  
  // Obtener ficha personalizada de One Piece
  $query_ficha = $db->query("SELECT * FROM mybb_op_fichas WHERE fid='$ficha_uid'");
  $ficha_op = $db->fetch_array($query_ficha);
  
  if(!$ficha_op) json(['error'=>'Ficha no encontrada'],404);
  
  // Obtener virtudes y defectos
  $virtudes = [];
  $defectos = [];
  
  $query_virtudes = $db->query("
    SELECT * FROM mybb_op_virtudes 
    INNER JOIN mybb_op_virtudes_usuarios 
    ON mybb_op_virtudes.virtud_id = mybb_op_virtudes_usuarios.virtud_id 
    WHERE mybb_op_virtudes_usuarios.uid='$ficha_uid' AND puntos > 0 
    ORDER BY nombre
  ");
  
  while($v = $db->fetch_array($query_virtudes)){
    $virtudes[] = [
      'id' => $v['virtud_id'],
      'nombre' => $v['nombre'],
      'descripcion' => $v['descripcion'],
      'puntos' => (int)$v['puntos']
    ];
  }
  
  $query_defectos = $db->query("
    SELECT * FROM mybb_op_virtudes 
    INNER JOIN mybb_op_virtudes_usuarios 
    ON mybb_op_virtudes.virtud_id = mybb_op_virtudes_usuarios.virtud_id 
    WHERE mybb_op_virtudes_usuarios.uid='$ficha_uid' AND puntos < 0 
    ORDER BY nombre
  ");
  
  while($d = $db->fetch_array($query_defectos)){
    $defectos[] = [
      'id' => $d['virtud_id'],
      'nombre' => $d['nombre'],
      'descripcion' => $d['descripcion'],
      'puntos' => (int)$d['puntos']
    ];
  }
  
  // Calcular experiencia y nivel
  $experiencia = (int)floor($user['newpoints']);
  $nivel = (int)$ficha_op['nivel'];
  
  // Tabla de experiencia por nivel
  $exp_tabla = [
    1 => [0, 50], 2 => [50, 125], 3 => [125, 225], 4 => [225, 350], 5 => [350, 500],
    6 => [500, 675], 7 => [675, 875], 8 => [875, 1100], 9 => [1100, 1350], 10 => [1350, 1625],
    11 => [1625, 1925], 12 => [1925, 2250], 13 => [2250, 2600], 14 => [2600, 2975], 15 => [2975, 3375],
    16 => [3375, 3800], 17 => [3800, 4250], 18 => [4250, 4725], 19 => [4725, 5225], 20 => [5225, 5750],
    21 => [5750, 6300], 22 => [6300, 6870], 23 => [6870, 7460], 24 => [7460, 8070], 25 => [8070, 8700],
    26 => [8700, 9350], 27 => [9350, 10020], 28 => [10020, 10700], 29 => [10700, 11400], 30 => [11400, 12360],
    31 => [12360, 13340], 32 => [13340, 14350], 33 => [14350, 15390], 34 => [15390, 16450], 35 => [16450, 17530],
    36 => [17530, 18650], 37 => [18650, 19790], 38 => [19790, 20950], 39 => [20950, 22140], 40 => [22140, 23700],
    41 => [23700, 25310], 42 => [25310, 26970], 43 => [26970, 28680], 44 => [28680, 30440], 45 => [30440, 32540]
  ];
  
  // Obtener experiencia mínima y máxima para el nivel actual
  $expMin = $exp_tabla[$nivel][0] ?? 0;
  $expMax = $exp_tabla[$nivel][1] ?? $expMin + 100;
  
  // Calcular estadísticas completas (base + pasivas + extras)
  $vitalidad_extra = (
    (intval($ficha_op['fuerza_pasiva']) * 6) + 
    (intval($ficha_op['resistencia_pasiva']) * 15) + 
    (intval($ficha_op['destreza_pasiva']) * 4) +
    (intval($ficha_op['agilidad_pasiva']) * 3) + 
    (intval($ficha_op['voluntad_pasiva']) * 1) + 
    (intval($ficha_op['punteria_pasiva']) * 2) + 
    (intval($ficha_op['reflejos_pasiva']) * 1)
  );
  
  $energia_extra = 0;
  if (intval($ficha_op['fuerza_pasiva'])) $energia_extra += (intval($ficha_op['fuerza_pasiva']) * 2);
  if (intval($ficha_op['resistencia_pasiva'])) $energia_extra += (intval($ficha_op['resistencia_pasiva']) * 4);
  if (intval($ficha_op['punteria_pasiva'])) $energia_extra += (intval($ficha_op['punteria_pasiva']) * 5);
  if (intval($ficha_op['destreza_pasiva'])) $energia_extra += (intval($ficha_op['destreza_pasiva']) * 4);
  if (intval($ficha_op['agilidad_pasiva'])) $energia_extra += (intval($ficha_op['agilidad_pasiva']) * 5);
  if (intval($ficha_op['reflejos_pasiva'])) $energia_extra += (intval($ficha_op['reflejos_pasiva']) * 1);
  if (intval($ficha_op['voluntad_pasiva'])) $energia_extra += (intval($ficha_op['voluntad_pasiva']) * 1);
  
  $haki_extra = 0;
  if (intval($ficha_op['voluntad_pasiva'])) $haki_extra += (intval($ficha_op['voluntad_pasiva']) * 10);
  
  $vitalidad_completa = intval($ficha_op['vitalidad']) + $vitalidad_extra + intval($ficha_op['vitalidad_pasiva']);
  $energia_completa = intval($ficha_op['energia']) + $energia_extra + intval($ficha_op['energia_pasiva']);
  $haki_completo = intval($ficha_op['haki']) + $haki_extra + intval($ficha_op['haki_pasiva']);
  
  // Calcular reputación con modificadores de virtudes/defectos
  $tiene_mejora_reputacion = false;
  $tiene_don_nadie = false;
  
  foreach($virtudes as $v) {
    if($v['id'] == 'V017') $tiene_mejora_reputacion = true; // Mejora Reputación
  }
  
  foreach($defectos as $d) {
    if($d['id'] == 'D013') $tiene_don_nadie = true; // Don Nadie
  }
  
  if($tiene_mejora_reputacion) {
    $reputacion = intval($ficha_op['reputacion']) * 1.10;
    $reputacionPositiva = intval($ficha_op['reputacion_positiva']) * 1.10;
    $reputacionNegativa = intval($ficha_op['reputacion_negativa']) * 1.10;
  } else if($tiene_don_nadie) {
    $reputacion = intval($ficha_op['reputacion']) * 0.9;
    $reputacionPositiva = intval($ficha_op['reputacion_positiva']) * 0.9;
    $reputacionNegativa = intval($ficha_op['reputacion_negativa']) * 0.9;
  } else {
    $reputacion = intval($ficha_op['reputacion']);
    $reputacionPositiva = intval($ficha_op['reputacion_positiva']);
    $reputacionNegativa = intval($ficha_op['reputacion_negativa']);
  }
  
  // Construir array de avatares solo con valores existentes
  $avatares = [];
  if(!empty($ficha_op['avatar1'])) $avatares[] = $ficha_op['avatar1'];
  if(!empty($ficha_op['avatar2'])) $avatares[] = $ficha_op['avatar2'];
  if(!empty($ficha_op['avatar3'])) $avatares[] = $ficha_op['avatar3'];
  if(!empty($ficha_op['avatar4'])) $avatares[] = $ficha_op['avatar4'];
  
  // Calcular espacios de equipamiento base (igual que en ficha_script2.js línea 2897)
  $espacios_base = 5;
  
  // Obtener técnicas aprendidas agrupadas por estilo/disciplina
  $query_tecnicas = $db->query("
    SELECT mybb_op_tecnicas.*, mybb_op_tec_aprendidas.uid 
    FROM mybb_op_tecnicas 
    INNER JOIN mybb_op_tec_aprendidas 
    ON mybb_op_tecnicas.tid = mybb_op_tec_aprendidas.tid 
    WHERE mybb_op_tec_aprendidas.uid='$ficha_uid'
    ORDER BY mybb_op_tecnicas.rama
  ");
  
  $estilos = [];
  while($tec = $db->fetch_array($query_tecnicas)){
    $key = ($tec['estilo'] == 'Racial') ? 'Racial' : $tec['rama'];
    if(!isset($estilos[$key])) {
      $estilos[$key] = [
        'nombre' => $key,
        'tecnicas' => []
      ];
    }
    $estilos[$key]['tecnicas'][] = [
      'tid' => $tec['tid'],
      'nombre' => $tec['nombre'],
      'descripcion' => nl2br($tec['descripcion']),
      'rama' => $tec['rama'],
      'estilo' => $tec['estilo'],
      'clase' => $tec['clase'] ?? '',
      'tipo' => $tec['tipo'] ?? '',
      'tier' => $tec['tier'] ?? '',
      'categoria' => $tec['categoria'] ?? '',
      'efectos' => nl2br($tec['efectos'] ?? ''),
      'energia' => $tec['energia'] ?? 0,
      'energia_turno' => $tec['energia_turno'] ?? 0,
      'haki' => $tec['haki'] ?? 0,
      'haki_turno' => $tec['haki_turno'] ?? 0,
      'coste_energia' => $tec['coste_energia'] ?? 0,
      'coste_haki' => $tec['coste_haki'] ?? 0,
      'enfriamiento' => $tec['enfriamiento'] ?? '',
      'requisitos' => $tec['requisitos'] ?? ''
    ];
  }
  
  // Obtener inventario
  $query_inventario = $db->query("
    SELECT * FROM mybb_op_objetos 
    INNER JOIN mybb_op_inventario 
    ON mybb_op_objetos.objeto_id = mybb_op_inventario.objeto_id 
    WHERE mybb_op_inventario.uid='$ficha_uid'
    ORDER BY mybb_op_objetos.categoria, mybb_op_objetos.subcategoria, mybb_op_objetos.tier, mybb_op_objetos.nombre
  ");
  
  // Parsear el JSON de equipamiento
  $equipamiento_json = json_decode($ficha_op['equipamiento'] ?? '{}', true);
  $ids_equipados_ropa = $equipamiento_json['ropa'] ?? null;
  $ids_equipados_bolsa = $equipamiento_json['bolsa'] ?? null;
  $ids_equipados_espacios = $equipamiento_json['espacios'] ?? [];
  
  $inventario = [];
  $inventario_por_id = []; // Para búsqueda rápida por ID
  $equipados = [
    'bolsa' => null,
    'ropa' => null,
    'espacios' => []
  ];
  
  while($obj = $db->fetch_array($query_inventario)){
    $subcategoria = strtolower($obj['subcategoria']);
    $subcategoria_formatted = str_replace(' ', '_', $subcategoria);
    $imagen_id = $obj['imagen_id'];
    
    $imagen_nombre = $subcategoria_formatted . "_" . $imagen_id . "_One_Piece_Gaiden_Foro_Rol.jpg";
    if (in_array($subcategoria_formatted, ['cofres', 'akuma_no_mi', 'materiales', 'tecnicas', 'documentos', 'recetas']) || 
        in_array($obj['objeto_id'], ['EANM001', 'LLST001', 'THR001', 'EPA001', 'KMP001', 'VCD001', 'VCZ001'])) {
      $imagen_nombre = $subcategoria_formatted . "_" . $imagen_id . "_One_Piece_Gaiden_Foro_Rol.gif";
    }
    if ($subcategoria_formatted == 'plato') {
      $imagen_nombre = 'alimentos_' . $imagen_id . '_One_Piece_Gaiden_Foro_Rol.gif';
    }
    
    $imagen = '/images/op/iconos/' . $imagen_nombre;
    if (!empty($obj['imagen_avatar'])) {
      $imagen = $obj['imagen_avatar'];
    }
    
    $item_data = [
      'objeto_id' => $obj['objeto_id'],
      'nombre' => $obj['nombre'],
      'apodo' => $obj['apodo'] ?? '',
      'categoria' => $obj['categoria'],
      'subcategoria' => $obj['subcategoria'],
      'tier' => $obj['tier'],
      'imagen' => $imagen,
      'imagen_id' => $obj['imagen_id'],
      'descripcion' => nl2br($obj['descripcion']),
      'cantidad' => (int)$obj['cantidad'],
      'espacios' => (int)($obj['espacios'] ?? 0),
      'berries' => (int)($obj['berries'] ?? 0),
      'dano' => $obj['dano'] ?? '',
      'efecto' => $obj['efecto'] ?? '',
      'bloqueo' => $obj['bloqueo'] ?? '',
      'alcance' => $obj['alcance'] ?? '',
      'requisitos' => $obj['requisitos'] ?? '',
      'escalado' => $obj['escalado'] ?? '',
      'editable' => $obj['editable'] ?? '0',
      'editado' => $obj['editado'] ?? '0',
      'equipado' => (int)($obj['equipado'] ?? 0)
    ];
    
    $inventario[] = $item_data;
    $inventario_por_id[$obj['objeto_id']] = $item_data;
  }
  
  // Construir estructura de equipados basándose en el JSON de equipamiento
  if ($ids_equipados_bolsa && isset($inventario_por_id[$ids_equipados_bolsa])) {
    $equipados['bolsa'] = $inventario_por_id[$ids_equipados_bolsa];
  }
  
  if ($ids_equipados_ropa && isset($inventario_por_id[$ids_equipados_ropa])) {
    $equipados['ropa'] = $inventario_por_id[$ids_equipados_ropa];
  }
  
  foreach ($ids_equipados_espacios as $espacio_data) {
    $objeto_id = $espacio_data['objetoId'] ?? null;
    if ($objeto_id && isset($inventario_por_id[$objeto_id])) {
      $equipados['espacios'][] = $inventario_por_id[$objeto_id];
    }
  }
  
  // Calcular espacios de equipamiento (igual que ficha_script2.js)
  $espacios_adicionales = 0;
  
  // Extraer espacios de bolsa equipada
  if ($equipados['bolsa']) {
    $efecto_bolsa = $equipados['bolsa']['efecto'] ?? '';
    $dano_bolsa = $equipados['bolsa']['dano'] ?? '';
    if (preg_match('/\[otorga\s+(\d+)\s+espacios?\]/i', $efecto_bolsa, $matches)) {
      $espacios_adicionales += (int)$matches[1];
    } elseif (preg_match('/otorga\s+(\d+)\s+espacios?/i', $efecto_bolsa, $matches)) {
      $espacios_adicionales += (int)$matches[1];
    } elseif (preg_match('/\[otorga\s+(\d+)\s+espacios?\]/i', $dano_bolsa, $matches)) {
      $espacios_adicionales += (int)$matches[1];
    } elseif (preg_match('/otorga\s+(\d+)\s+espacios?/i', $dano_bolsa, $matches)) {
      $espacios_adicionales += (int)$matches[1];
    }
  }
  
  // Extraer espacios de ropa equipada
  if ($equipados['ropa']) {
    $efecto_ropa = $equipados['ropa']['efecto'] ?? '';
    $dano_ropa = $equipados['ropa']['dano'] ?? '';
    if (preg_match('/\[otorga\s+(\d+)\s+espacios?\]/i', $efecto_ropa, $matches)) {
      $espacios_adicionales += (int)$matches[1];
    } elseif (preg_match('/otorga\s+(\d+)\s+espacios?/i', $efecto_ropa, $matches)) {
      $espacios_adicionales += (int)$matches[1];
    } elseif (preg_match('/\[otorga\s+(\d+)\s+espacios?\]/i', $dano_ropa, $matches)) {
      $espacios_adicionales += (int)$matches[1];
    } elseif (preg_match('/otorga\s+(\d+)\s+espacios?/i', $dano_ropa, $matches)) {
      $espacios_adicionales += (int)$matches[1];
    }
  }
  
  // Verificar si tiene oficio Aventurero (+4 espacios)
  $oficios_data = json_decode($ficha_op['oficios'] ?? '{}', true);
  $oficio1 = $ficha_op['oficio1'] ?? '';
  $oficio2 = $ficha_op['oficio2'] ?? '';
  if ($oficio1 === 'Aventurero' || $oficio2 === 'Aventurero') {
    $espacios_adicionales += 4;
  }
  
  $espacios_equipamiento_total = $espacios_base + $espacios_adicionales;
  
  // Construir respuesta completa
  $ficha = [
    'uid' => (int)$user['uid'],
    'username' => $user['username'],
    'avatar' => $user['avatar'],
    'avatares' => $avatares,
    'nombre' => $ficha_op['nombre'] ?? '',
    'apodo' => $ficha_op['apodo'] ?? '',
    'faccion' => $ficha_op['faccion'] ?? '',
    'rango' => $ficha_op['rango'] ?? '',
    'fama' => (int)($ficha_op['fama'] ?? 0),
    'nivel' => $nivel,
    'experiencia' => $experiencia,
    'experiencia_nivel' => $expMax,
    'raza' => $ficha_op['raza'] ?? '',
    'edad' => (int)($ficha_op['edad'] ?? 0),
    'sexo' => $ficha_op['sexo'] ?? '',
    'altura' => $ficha_op['altura'] ?? '',
    'apariencia' => $ficha_op['apariencia'] ?? '',
    'personalidad' => $ficha_op['personalidad'] ?? '',
    'historia' => $ficha_op['historia'] ?? '',
    'extra' => $ficha_op['extra'] ?? '',
    'frase' => $ficha_op['frase'] ?? '',
    'equipamiento' => $ficha_op['equipamiento'] ?? '',
    'cronologia' => $ficha_op['cronologia'] ?? '',
    'estadisticas' => [
      'fuerza' => (int)($ficha_op['fuerza'] ?? 0),
      'destreza' => (int)($ficha_op['destreza'] ?? 0),
      'agilidad' => (int)($ficha_op['agilidad'] ?? 0),
      'resistencia' => (int)($ficha_op['resistencia'] ?? 0),
      'punteria' => (int)($ficha_op['punteria'] ?? 0),
      'voluntad' => (int)($ficha_op['voluntad'] ?? 0),
      'reflejos' => (int)($ficha_op['reflejos'] ?? 0),
      'vitalidad' => $vitalidad_completa,
      'vitalidad_actual' => $vitalidad_completa,
      'energia' => $energia_completa,
      'energia_actual' => $energia_completa,
      'haki' => $haki_completo,
      'haki_actual' => $haki_completo
    ],
    'reputacion' => [
      'total' => (int)$reputacion,
      'positiva' => (int)$reputacionPositiva,
      'negativa' => (int)$reputacionNegativa
    ],
    'virtudes' => $virtudes,
    'defectos' => $defectos,
    'akuma' => $ficha_op['akuma_id'] ?? null,
    'oficios' => [
      'primario' => $ficha_op['oficio1'] ?? '',
      'secundario' => $ficha_op['oficio2'] ?? '',
      'terciario' => $ficha_op['oficio3'] ?? '',
      'data' => json_decode($ficha_op['oficios'] ?? '{}', true)
    ],
    'estilos' => array_values($estilos),
    'inventario' => $inventario,
    'equipados' => $equipados,
    'berries' => (int)($ficha_op['berries'] ?? 0),
    'espacios_equipamiento' => $espacios_equipamiento_total,
    'ranuras_implantes' => (int)($ficha_op['ranuras'] ?? 0)
  ];
  
  json(['ficha' => $ficha]);
}
