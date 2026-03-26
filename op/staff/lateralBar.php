<?php
define('IN_MYBB', 1);
define('THIS_SCRIPT', 'index.php');
require_once "./../../global.php";
require "./../../inc/config.php";
require_once "./../functions/op_functions.php";

if(!$mybb->user['uid']) {
    http_response_code(401);
    echo json_encode(['error' => 'No autenticado']);
    exit;
}

// Parámetros (con valores por defecto)
$fid   = (int)$mybb->get_input('fid');
$limit = (int)$mybb->get_input('limit');
$fid   = $fid ?: 369;
$limit = ($limit >= 1 && $limit <= 50) ? $limit : 20;

$query = $db->query("
    SELECT pid, tid, fid, subject, uid, username, dateline
    FROM mybb_posts
    WHERE fid = {$fid}
      AND visible = 1
      AND replyto = 0
      AND subject NOT LIKE 'RE:%'
    ORDER BY dateline DESC
    LIMIT {$limit}
");

$rows = [];
while ($row = $db->fetch_array($query)) {
    $rows[] = [
        'pid'      => (int)$row['pid'],
        'tid'      => (int)$row['tid'],
        'fid'      => (int)$row['fid'],
        'subject'  => (string)$row['subject'],
        'uid'      => (int)$row['uid'],
        'username' => (string)$row['username'],
        'dateline' => (int)$row['dateline'],
        'date_rel' => my_date('relative', (int)$row['dateline']),
        'url'      => $mybb->settings['bburl']."/showthread.php?tid=".(int)$row['tid'],
    ];
}

echo json_encode(['threads' => $rows], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
