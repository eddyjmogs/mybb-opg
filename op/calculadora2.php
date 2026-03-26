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
define('THIS_SCRIPT', 'calculadora2.php');

require_once "./../global.php";

eval("\$page = \"".$templates->get("op_calculadora2")."\";");
output_page($page);
