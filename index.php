<?php
require_once __DIR__.'/config.php';
// ALYA ROUTER
$uri = trim(parse_url($_SERVER['REQUEST_URI'])['path'], '/');

if ($uri !== '') {
    require_once $uri . '.php';
    exit;
}