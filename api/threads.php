<?php
if (!defined('API_LOADED')) exit;

// GET /api/index.php?route=threads&fid=2&page=1
if($_SERVER['REQUEST_METHOD']==='GET' && $route==='threads'){
  global $db;
  
  $fid = (int)($_GET['fid'] ?? 2);
  $page = max(1, (int)($_GET['page'] ?? 1));
  $per_page = 20;
  
  // Verificar que el foro existe
  $query_forum = $db->query("SELECT * FROM mybb_forums WHERE fid='{$fid}'");
  $forum = $db->fetch_array($query_forum);
  
  if(!$forum){
    json(['error'=>'Foro no encontrado'], 404);
  }
  
  // Obtener subforos (si existen)
  $query_subforums = $db->query("
    SELECT f.*, 
      (SELECT COUNT(*) FROM mybb_threads WHERE fid=f.fid AND visible='1') as threads_count,
      (SELECT COUNT(*) FROM mybb_posts p 
       INNER JOIN mybb_threads t ON p.tid=t.tid 
       WHERE t.fid=f.fid AND p.visible='1') as posts_count
    FROM mybb_forums f 
    WHERE f.pid='{$fid}' AND f.active='1'
    ORDER BY f.disporder ASC
  ");
  
  $subforums = [];
  while($subforum = $db->fetch_array($query_subforums)){
    // Obtener último post del subforo
    $query_lastpost = $db->query("
      SELECT t.lastpost, t.lastposter, t.lastposteruid, t.subject, t.tid
      FROM mybb_threads t
      WHERE t.fid='{$subforum['fid']}' AND t.visible='1'
      ORDER BY t.lastpost DESC
      LIMIT 1
    ");
    $lastpost = $db->fetch_array($query_lastpost);
    
    $subforums[] = [
      'fid' => (int)$subforum['fid'],
      'name' => $subforum['name'],
      'description' => $subforum['description'],
      'threads' => (int)$subforum['threads_count'],
      'posts' => (int)$subforum['posts_count'],
      'icon' => $subforum['icon'] ? "https://onepiecegaiden.com/{$subforum['icon']}" : null,
      'lastpost' => $lastpost ? [
        'tid' => (int)$lastpost['tid'],
        'subject' => $lastpost['subject'],
        'timestamp' => (int)$lastpost['lastpost'],
        'username' => $lastpost['lastposter'],
        'uid' => (int)$lastpost['lastposteruid']
      ] : null
    ];
  }
  
  // Calcular offset
  $offset = ($page - 1) * $per_page;
  
  // Obtener hilos del foro
  $query = $db->query("
    SELECT t.*, u.username 
    FROM mybb_threads t 
    LEFT JOIN mybb_users u ON t.uid = u.uid 
    WHERE t.fid='{$fid}' AND t.visible='1' 
    ORDER BY t.lastpost DESC 
    LIMIT {$offset}, {$per_page}
  ");
  
  $threads = [];
  while($thread = $db->fetch_array($query)){
    $threads[] = [
      'tid' => (int)$thread['tid'],
      'fid' => (int)$thread['fid'],
      'subject' => $thread['subject'],
      'username' => $thread['username'] ?: 'Usuario Eliminado',
      'uid' => (int)$thread['uid'],
      'dateline' => (int)$thread['dateline'],
      'lastpost' => (int)$thread['lastpost'],
      'lastposter' => $thread['lastposter'],
      'lastposteruid' => (int)$thread['lastposteruid'],
      'views' => (int)$thread['views'],
      'replies' => (int)$thread['replies'],
      'closed' => (int)$thread['closed'],
      'sticky' => (int)$thread['sticky'],
      'icon' => $thread['icon'] ? "https://onepiecegaiden.com/{$thread['icon']}" : null,
    ];
  }
  
  // Contar total de hilos
  $query_count = $db->query("SELECT COUNT(*) as total FROM mybb_threads WHERE fid='{$fid}' AND visible='1'");
  $count_result = $db->fetch_array($query_count);
  $total = (int)$count_result['total'];
  
  json([
    'forum' => [
      'fid' => (int)$forum['fid'],
      'name' => $forum['name'],
      'description' => $forum['description']
    ],
    'subforums' => $subforums,
    'threads' => $threads,
    'page' => $page,
    'per_page' => $per_page,
    'total' => $total,
    'total_pages' => ceil($total / $per_page)
  ]);
}
