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
define('THIS_SCRIPT', 'en_construccion.php');
require_once "./../global.php";
require "./../inc/config.php";
global $templates, $mybb;

$mensaje_redireccion = "Upsss. Esta página está en construcción.";
eval("\$page = \"".$templates->get("op_redireccion")."\";");
output_page($page);




