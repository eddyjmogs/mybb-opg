<?php
/**
 * MyBB Template Exporter
 * Exports all templates from DB to /templates/ directory.
 * Run once from CLI or browser, then add templates/ to git.
 * DELETE or move this file after use — it exposes DB credentials via config.php.
 */

define("IN_MYBB", 1);
define('THIS_SCRIPT', 'export_templates.php');
require_once "./inc/config.php";

$db = new mysqli(
    str_replace('localhost', '127.0.0.1', $config['database']['hostname']),
    $config['database']['username'],
    $config['database']['password'],
    $config['database']['database']
);

if ($db->connect_error) {
    die("DB connection failed: " . $db->connect_error);
}

$prefix = $config['database']['table_prefix'] ?? 'mybb_';

// Load template sets so we can name directories
$sets = [];
$res = $db->query("SELECT sid, title FROM {$prefix}templatesets ORDER BY sid");
while ($row = $res->fetch_assoc()) {
    $sets[$row['sid']] = $row['title'];
}
// Built-in set IDs
$sets[-2] = 'master';
$sets[-1]  = 'custom';

// Export all templates
$res = $db->query("SELECT title, template, sid FROM {$prefix}templates ORDER BY sid, title");

$count = 0;
while ($row = $res->fetch_assoc()) {
    $setName = isset($sets[$row['sid']]) ? $sets[$row['sid']] : 'set_' . $row['sid'];
    // Sanitize directory/file names
    $setName  = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $setName);
    $fileName = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $row['title']) . '.html';

    $dir = __DIR__ . '/templates/' . $setName;
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    file_put_contents($dir . '/' . $fileName, $row['template'] ?? '');
    $count++;
}

echo "Exported {$count} templates to /templates/\n";
