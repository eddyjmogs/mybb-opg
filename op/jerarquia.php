<?php
/**
 * MyBB 1.8
 * Menú de jerarquias - Backend/jerarquia.php
 */

// --- DEPURACIÓN TEMPORAL---
ini_set('display_errors', '1');          
ini_set('display_startup_errors', '1');
ini_set('log_errors', '1');                 
ini_set('error_log', './php-error.log');
error_reporting(E_ALL);
// -----------------------------------------------

define("IN_MYBB", 1);
define('THIS_SCRIPT', 'jerarquia.php');
require_once "./../global.php";
require "./../inc/config.php";
require_once "./functions/op_functions.php";

global $templates, $mybb, $db;

// $uid = (int)$mybb->user['uid'];
// $username = $mybb->user['username'] ?? '';
// $action = $mybb->get_input('action');

eval("\$page = \"".$templates->get('op_jerarquia')."\";");
output_page($page);