<?php
/**
 * MyBB 1.8
 * Copyright 2014 MyBB Group, All Rights Reserved
 *
 * Website: http://www.mybb.com
 * License: http://www.mybb.com/about/license
 *
 * One Piece Gaiden - Sistema de Cartas
 * Página principal del juego de colección de cartas
 */

define("IN_MYBB", 1);
define('THIS_SCRIPT', 'cartas.php');

require_once "./../../Backend/global.php";

// Verificar si el usuario está logueado
if (!$mybb->user['uid']) {
    error_no_permission();
}

// Obtener datos del usuario/ficha si es necesario
$uid = intval($mybb->user['uid']);

// Aquí puedes agregar lógica adicional para:
// - Cargar datos de cartas del usuario desde la base de datos
// - Verificar permisos específicos
// - Inicializar datos del juego

// Variables para el template
$cartas_js = ''; // Aquí iría el contenido del archivo cartas.js
$op_ficha_script = ''; // Script principal del juego

// Cargar el template y mostrar la página
eval("\$page = \"".$templates->get("op_cartas")."\";");
output_page($page);
?>