<?php

require_once __DIR__ . '/../bootstrap.php';

header("Content-Type: application/json");
echo json_encode(['success' => opcache_reset()]);
