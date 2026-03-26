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
define('THIS_SCRIPT', 'cartas.php');

require_once "./../global.php";

eval("\$op_cartas_css = \"".$templates->get("op_cartas_css")."\";");
eval("\$page = \"".$templates->get("op_cartas")."\";");
output_page($page);
