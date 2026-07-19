<?php
$root = dirname(__DIR__);
chdir($root);

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$path = rawurldecode($path);

if ($path === '/' || $path === '') {
    $path = '/index.php';
}

$target = realpath($root . '/' . ltrim($path, '/'));
if ($target === false || !str_starts_with($target, $root) || pathinfo($target, PATHINFO_EXTENSION) !== 'php') {
    http_response_code(404);
    echo 'Not Found';
    exit;
}

require $target;
