<?php
/**
 * MyBB 1.8
 * Copyright 2014 MyBB Group, All Rights Reserved
 *
 * Website: http://www.mybb.com
 * License: http://www.mybb.com/about/license
 */

define("IN_MYBB", 1);
define('THIS_SCRIPT', 'contar_temas.php');
require_once "./../../global.php";
require "./../../inc/config.php";
require_once "./../functions/op_functions.php";

global $templates, $mybb;
$uid = $mybb->user['uid'];
$username = $mybb->user['username'];


$tipo_tema = isset($_POST['tipo']) ? $_POST['tipo'] : '';
$usuario = isset($_POST['usuario']) ? $_POST['usuario'] : '';
$log = "</br> <div> <table border='1'>
<tr>
  <th>Usuario</th>
  <th>Temas abiertos</th>";



if($tipo_tema != '' && $usuario != '')
{
    $query_temas = $db->query("Select count(*) as 'numero' from `mybb_threads` t where t.tid in (select distinct tid from `mybb_posts` where uid = '$usuario') and t.prefix = '$tipo_tema' and t.closed = 0; ");

   
   while ($q = $db->fetch_array($query_temas)) {
    
    $numero = $q['numero'];

    $log .= "<tr>
      <td>$usuario</td>
      <td>$numero</td>
    </tr>   
  </table></div>";
}
    
}elseif ($tipo_tema == '' && $usuario != ''){

    $query_temas = $db->query("Select prefix, count(*) as 'numero' from `mybb_threads` t where t.tid in (select distinct tid from `mybb_posts` where uid = $usuario) and t.closed = 0 group by prefix;");

    $log .= "<th>Tipo tema</th></tr>";

    while ($q = $db->fetch_array($query_temas)) {
    
        $numero = $q['numero'];
        $tipo_tema = $q['prefix'];
    
        $log .= "<tr>
          <td>$usuario</td>
          <td>$numero</td>
          <td>$tipo_tema</td>
        </tr>";
    }

    $log .= "</table></div>";
}elseif($tipo_tema != '' && $usuario == ''){
    $query_temas = $db->query("select p.uid as 'user', count(distinct p.uid, p.tid) as 'numero' from `mybb_posts` p inner join `mybb_threads` t on p.tid = t.tid where t.prefix = '$tipo_tema' and t.closed = 0 group by p.uid; ");

    while ($q = $db->fetch_array($query_temas)) {
    
        $user = $q['user'];
        $numero = $q['numero'];
    
        $log .= "<tr>
          <td>$user</td>
          <td>$numero</td>
        </tr>";
    }

    $log .= "</table></div>";
}
else{
    $query_temas = $db->query("select p.uid as user, t.prefix, count(distinct p.uid, p.tid) as 'numero' from `mybb_posts` p inner join `mybb_threads` t on p.tid = t.tid where t.closed = 0 group by p.uid, t.prefix;");

    $log .= "<th>Tipo tema</th></tr>";

    while ($q = $db->fetch_array($query_temas)) {
    
        $user = $q['user'];
        $numero = $q['numero'];
        $tipo_tema = $q['prefix'];
    
        $log .= "<tr>
          <td>$user</td>
          <td>$numero</td>
          <td>$tipo_tema</td>
        </tr>";
      
    }

    $log .= "</table></div>";
}


eval('$log_var = $log;');

if (is_mod($uid) || is_staff($uid)) { 
    eval("\$page = \"".$templates->get("staff_contar_temas")."\";");
    output_page($page);
} else {
    $redireccion = "No tienes permisos para ver esta página.";
    eval("\$page = \"".$templates->get("op_redireccion")."\";");
    output_page($page);
}