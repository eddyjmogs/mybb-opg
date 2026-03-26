<?php
if (!defined('API_LOADED')) exit;

// GET /api/index.php?route=thread&tid=123&page=1
if($_SERVER['REQUEST_METHOD']==='GET' && $route==='thread'){
  global $db;
  
  $tid = (int)($_GET['tid'] ?? 0);
  $page = max(1, (int)($_GET['page'] ?? 1));
  $per_page = 10;
  
  if(!$tid){
    json(['error'=>'ID de hilo requerido'], 400);
  }
  
  // Obtener información del hilo
  $query_thread = $db->query("
    SELECT t.*, f.name as forum_name
    FROM mybb_threads t 
    LEFT JOIN mybb_forums f ON t.fid = f.fid
    WHERE t.tid='{$tid}'
  ");
  
  $thread = $db->fetch_array($query_thread);
  
  if(!$thread){
    json(['error'=>'Hilo no encontrado'], 404);
  }
  
  // Obtener username del autor del hilo
  $thread_author = 'Usuario Eliminado';
  if($thread['uid']){
    $query_author = $db->query("SELECT username FROM mybb_users WHERE uid='{$thread['uid']}'");
    $author_data = $db->fetch_array($query_author);
    if($author_data){
      $thread_author = $author_data['username'];
    }
  }
  
  // Calcular offset
  $offset = ($page - 1) * $per_page;
  
  // Obtener posts del hilo
  $query_posts = $db->query("
    SELECT p.*, u.username, u.avatar, u.usergroup, u.postnum, u.usertitle
    FROM mybb_posts p 
    LEFT JOIN mybb_users u ON p.uid = u.uid 
    WHERE p.tid='{$tid}' AND p.visible='1'
    ORDER BY p.dateline ASC
    LIMIT {$offset}, {$per_page}
  ");
  
  $posts = [];
  while($post = $db->fetch_array($query_posts)){
    $posts[] = [
      'pid' => (int)$post['pid'],
      'uid' => (int)$post['uid'],
      'username' => $post['username'] ?: 'Usuario Eliminado',
      'avatar' => $post['avatar'] ?: null,
      'usergroup' => (int)$post['usergroup'],
      'postnum' => (int)$post['postnum'],
      'usertitle' => $post['usertitle'],
      'message' => $post['message'],
      'dateline' => (int)$post['dateline'],
      'edittime' => (int)$post['edittime'],
      'edituid' => (int)$post['edituid']
    ];
  }
  
  // Contar total de posts
  $query_count = $db->query("SELECT COUNT(*) as total FROM mybb_posts WHERE tid='{$tid}' AND visible='1'");
  $count_result = $db->fetch_array($query_count);
  $total = (int)$count_result['total'];
  
  json([
    'thread' => [
      'tid' => (int)$thread['tid'],
      'fid' => (int)$thread['fid'],
      'subject' => $thread['subject'],
      'username' => $thread_author,
      'uid' => (int)$thread['uid'],
      'dateline' => (int)$thread['dateline'],
      'views' => (int)$thread['views'],
      'replies' => (int)$thread['replies'],
      'closed' => (int)$thread['closed'],
      'sticky' => (int)$thread['sticky'],
      'forum_name' => $thread['forum_name'] ?: 'Foro'
    ],
    'posts' => $posts,
    'page' => $page,
    'per_page' => $per_page,
    'total' => $total,
    'total_pages' => (int)ceil($total / $per_page)
  ]);
}
