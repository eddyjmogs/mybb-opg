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
define('THIS_SCRIPT', 'registro_virtudes.php');
require_once "./../global.php";
require "./../inc/config.php";
require_once "./functions/op_functions.php";
global $templates, $mybb;

$registro = "";

$query_virtudes = $db->query(" SELECT * FROM `mybb_op_virtudes` WHERE virtud_id LIKE 'V%' ORDER BY `mybb_op_virtudes`.`nombre` ASC; ");
$query_defectos = $db->query(" SELECT * FROM `mybb_op_virtudes` WHERE virtud_id LIKE 'D%' ORDER BY `mybb_op_virtudes`.`nombre` ASC; ");

$virtudes = array();
$defectos = array();

while ($virtude = $db->fetch_array($query_virtudes)) { array_push($virtudes, $virtude); }
while ($defecto = $db->fetch_array($query_defectos)) { array_push($defectos, $defecto); }

$virtudes_json = json_encode($virtudes);
$defectos_json = json_encode($defectos);

eval("\$page = \"".$templates->get("op_registro_virtudes")."\";");
output_page($page);
