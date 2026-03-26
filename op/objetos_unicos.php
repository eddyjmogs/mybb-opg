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
define('THIS_SCRIPT', 'objetos.php');

global $templates, $mybb, $db;

require_once "./../global.php";
require_once "./functions/op_functions.php";

$uid = $mybb->user['uid'];
$s_uid = $mybb->user['uid'];

$query_objetos = $db->query(" SELECT * FROM `mybb_op_objetos` WHERE custom='0' ORDER BY categoria, subcategoria, tier, nombre ");
$objetos = array();
$objetos_array = array();

while ($q = $db->fetch_array($query_objetos)) { 
    $objeto_id = $q['objeto_id'];
    $key = "$objeto_id";
    if (!$objetos[$key]) { $objetos[$key] = array(); }
    array_push($objetos[$key], $q);
    array_push($objetos_array, $objeto_id);
}

$objetos_array_json = json_encode($objetos_array);
$objetos_json = json_encode($objetos);

eval("\$page = \"".$templates->get("op_objetos_unicos")."\";");
output_page($page);


