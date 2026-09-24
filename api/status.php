<?php
header('Content-Type: application/json');
echo json_encode([
    'status' => 'ok',
    'timestamp' => date('c'),
    'deployment' => 'vercel',
    'php_version' => PHP_VERSION,
]);
