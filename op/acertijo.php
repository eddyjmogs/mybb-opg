<?php
/**
 * MyBB 1.8
 * Copyright 2014 MyBB Group, All Rights Reserved
 *
 * Website: http://www.mybb.com
 * License: http://www.mybb.com/about/license
 *
 */

// prueba

define("IN_MYBB", 1);
define('THIS_SCRIPT', 'acertijo.php');

require_once "./../global.php";

$password = $_POST["password"];

if ($password != '') {
    header('Content-type: application/json');
    $response = array();
    $timestamp = time();

    $esCorrecta = '0';

    if ($password == 'ODA SENSEI') {
        $esCorrecta = '1';
    }

    $response[0] = array(
        'timestamp' => $timestamp,
        "respuesta_correcta" => $esCorrecta
    );

    echo json_encode($response); 
    return;
}

eval("\$page = \"".$templates->get("op_acertijo")."\";");
output_page($page);
