<?php

use App\Parser\RozetkaParser;

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    include VIEWS_PATH . 'parser.php';
    exit;
}

$data = $_POST['links'];
preg_match_all("((?:https?:|www\.)[^\s]+)", $data, $links);

$parser = new RozetkaParser($links[0]);

$products = $parser->parse();