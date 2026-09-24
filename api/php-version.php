<?php
header('Content-Type: application/json');
echo json_encode([
    'php_version' => PHP_VERSION,
    'sapi' => php_sapi_name(),
    'extensions' => get_loaded_extensions(),
]);
