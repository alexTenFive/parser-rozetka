<?php

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $proxies = [];
    if (file_exists(ROOT . '/proxies.dat')) {
        foreach (file(ROOT . '/proxies.dat') as $row) {
            $proxies[] = $row;
        }
    }
    include VIEWS_PATH . 'proxy.php';
    exit;
}

$text = isset($_POST['proxies']) ? $_POST['proxies'] : '';

preg_match_all("/\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}:\d{1,6}/", $text, $proxies);

file_put_contents(ROOT."/proxies.dat", "");
foreach ($proxies[0] as $proxy) {
    file_put_contents(ROOT."/proxies.dat", $proxy.PHP_EOL, FILE_APPEND);
}

header("Location: /proxy");