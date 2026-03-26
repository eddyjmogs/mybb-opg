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
define('THIS_SCRIPT', 'audiomanga.php');

require_once "./../global.php";

$tipo = $mybb->get_input('tipo'); 

eval("\$page = \"".$templates->get("op_audiomanga")."\";");
output_page($page);


