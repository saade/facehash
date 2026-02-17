<?php

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

if ($uri === '/cover') {
    require __DIR__ . '/cover.php';
    exit;
}

require __DIR__ . '/demo.php';
