<?php
/**
 * MyBB 1.8
 * Copyright 2014 MyBB Group, All Rights Reserved
 *
 * Website: http://www.mybb.com
 * License: http://www.mybb.com/about/license
 *
 */

define("IN_MYBB", 1);
define('THIS_SCRIPT', 'registro_akumas.php');
require_once "./../global.php";
require "./../inc/config.php";
require_once "./functions/op_functions.php";
global $templates, $mybb;

$registro = "";

$akumas = $db->query(" SELECT * FROM `mybb_op_akumas` ORDER BY `mybb_op_akumas`.`nombre` ASC ");
$akuma_magica_query = $db->query(" SELECT * FROM `mybb_op_akumas` WHERE ocupada = 0 ORDER BY RAND() LIMIT 1 ");

$counter = 0;

$akuma_magica = '';
$akuma_magica_descripcion = '';

while ($q = $db->fetch_array($akumas)) {
    $counter += 1;
    $akuma_id = $q['akuma_id'];
    $nombre = $q['nombre'];
    $categoria = $q['categoria'];
    $tier = $q['tier'];
    $descripcion = $q['descripcion'];
    $registro .= "<span>Akuma ID: $akuma_id - Nombre: $nombre - Categoría: $categoria - Tier: $tier<br>Descripción: $descripcion</span><br><br>";
}

while ($q = $db->fetch_array($akuma_magica_query)) { 
    $akuma_magica = $q['nombre'];
    $akuma_magica_descripcion = $q['descripcion'];
    $akuma_categoria = $q['categoria'];
    $akuma_tier = $q['tier'];

}

eval("\$page = \"".$templates->get("op_registro_akumas")."\";");
output_page($page);



