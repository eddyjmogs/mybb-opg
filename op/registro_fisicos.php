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
define('THIS_SCRIPT', 'registro_fisico.php');
require_once "./../global.php";
require "./../inc/config.php";
require_once "./functions/op_functions.php";
global $templates, $mybb;

$registro = "";

$fichas = $db->query("
    SELECT * FROM mybb_op_fichas WHERE fisico_de_pj != '' ORDER BY `mybb_op_fichas`.`fisico_de_pj` ASC
");

while ($q = $db->fetch_array($fichas)) {
    $fid = $q['fid'];
    $nombre = $q['nombre'];
    $fisico_de_pj = $q['fisico_de_pj'];
    $registro .= "<span>$fisico_de_pj - <a href='/op/ficha.php?&uid=$fid'>$nombre</a></span><br>";
}

eval("\$page = \"".$templates->get("op_registro_de_fisicos")."\";");
output_page($page);



