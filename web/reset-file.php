<?php

require_once __DIR__ . '/../bootstrap.php';

$path = (isset($_GET['path'])) ? $_GET['path'] : '';

$regex = '#^' . $path . '#';

$fornicated = [];
foreach ($monitor->getFiles() as $file) {
    if (preg_match($regex, $file['full_path'])) {
        if (opcache_invalidate($file['full_path'], true)) {
            $fornicated[] = $file['full_path'];
        }
    }
}

header("Content-Type: application/json");
echo json_encode(['success' => true, 'fornicated' => $fornicated]);
