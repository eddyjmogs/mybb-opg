<?php
if (!defined('API_LOADED')) exit;

// POST /api/index.php?route=new_reply  { tid, message }
if($_SERVER['REQUEST_METHOD']==='POST' && $route==='new_reply'){
  global $db;
  $uid = bearer_uid();
  if(!$uid) json(['error'=>'No autorizado'],401);

  $in = body();
  $tid = (int)($in['tid'] ?? 0);
  $message = trim($in['message'] ?? '');

  if(!$tid) json(['error'=>'ID de hilo requerido'],400);
  if($message === '') json(['error'=>'Mensaje vacío'],400);

  // Obtener hilo
  $thread_q = $db->query("SELECT * FROM mybb_threads WHERE tid='{$tid}'");
  $thread = $db->fetch_array($thread_q);
  if(!$thread) json(['error'=>'Hilo no encontrado'],404);
  if($thread['closed'] && $thread['closed'] != '0') json(['error'=>'Hilo cerrado'],403);

  // Obtener usuario
  $user_q = $db->query("SELECT uid, username FROM mybb_users WHERE uid='{$uid}'");
  $user = $db->fetch_array($user_q);
  if(!$user) json(['error'=>'Usuario no encontrado'],404);

  $now = TIME_NOW;
  $ip = $_SERVER['REMOTE_ADDR'] ?? '';

  // Insertar post
  $pid = $db->insert_query('posts', [
    'tid' => $tid,
    'fid' => (int)$thread['fid'],
    'subject' => $db->escape_string($thread['subject']),
    'message' => $db->escape_string($message),
    'uid' => (int)$user['uid'],
    'username' => $db->escape_string($user['username']),
    'dateline' => $now,
    'visible' => 1,
    'smilieoff' => 0,
    'includesig' => 0,
    'ipaddress' => $db->escape_string($ip),
    'edituid' => 0,
    'edittime' => 0
  ]);

  // Actualizar hilo
  $db->update_query('threads', [
    'replies' => (int)$thread['replies'] + 1,
    'lastpost' => $now,
    'lastposter' => $db->escape_string($user['username']),
    'lastposteruid' => (int)$user['uid']
  ], "tid='{$tid}'");

  // Actualizar foro (datos básicos)
  $db->update_query('forums', [
    'lastpost' => $now,
    'lastposter' => $db->escape_string($user['username']),
    'lastposteruid' => (int)$user['uid']
  ], "fid='{$thread['fid']}'");

  json([
    'ok' => true,
    'pid' => (int)$pid,
    'tid' => $tid,
    'fid' => (int)$thread['fid'],
    'username' => $user['username'],
    'dateline' => $now
  ], 201);
}
