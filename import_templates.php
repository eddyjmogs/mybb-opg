<?php
/**
 * MyBB Template Importer
 * Reads all .html files from /templates/ and updates the DB.
 * Run manually or triggered by a file watcher (watch_templates.sh).
 */

define('IMPORT_PASSWORD', 'changeme');

if (PHP_SAPI !== 'cli') {
    $given = $_GET['password'] ?? $_POST['password'] ?? '';
    if (!hash_equals(IMPORT_PASSWORD, $given)) {
        http_response_code(403);
        die('Forbidden');
    }
}

define("IN_MYBB", 1);
define('THIS_SCRIPT', 'import_templates.php');
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

// Build set name → sid map
$sets = [
    'master' => -2,
    'custom' => -1,
];
$res = $db->query("SELECT sid, title FROM {$prefix}templatesets");
while ($row = $res->fetch_assoc()) {
    $key = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $row['title']);
    $sets[$key] = (int)$row['sid'];
}

$updated = 0;
$skipped = 0;

$templateDirs = glob(__DIR__ . '/templates/*', GLOB_ONLYDIR);
foreach ($templateDirs as $dir) {
    $setName = basename($dir);

    if (!array_key_exists($setName, $sets)) {
        echo "Warning: no DB set found for directory '{$setName}', skipping.\n";
        $skipped++;
        continue;
    }

    $sid = $sets[$setName];

    foreach (glob($dir . '/*.html') as $file) {
        $title    = basename($file, '.html');
        $content  = file_get_contents($file);
        $title    = $db->real_escape_string($title);
        $content  = $db->real_escape_string($content);

        // Update if exists, insert if not
        $check = $db->query("SELECT tid FROM {$prefix}templates WHERE title = '{$title}' AND sid = {$sid}");
        if ($check->num_rows > 0) {
            $db->query("UPDATE {$prefix}templates SET template = '{$content}', dateline = " . time() . " WHERE title = '{$title}' AND sid = {$sid}");
        } else {
            $db->query("INSERT INTO {$prefix}templates (title, template, sid, version, dateline) VALUES ('{$title}', '{$content}', {$sid}, '1800', " . time() . ")");
        }

        $updated++;
    }
}

echo "Imported {$updated} templates." . ($skipped ? " Skipped {$skipped} unknown directories." : '') . "\n";
