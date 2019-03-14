<?php
require_once __DIR__.'/config.php';
// ALYA ROUTER
$uri = trim(parse_url($_SERVER['REQUEST_URI'])['path'], '/');

if ($uri !== '') {
    $params = [];
    if (isset(parse_url($_SERVER['REQUEST_URI'])['query'])) {
        parse_str(parse_url($_SERVER['REQUEST_URI'])['query'], $params);
    }
    require_once $uri . '.php';
    exit;
} else {
    include VIEWS_PATH . '/index.php';
}
