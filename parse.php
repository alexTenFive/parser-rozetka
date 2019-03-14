<?php

require_once __DIR__.'/config.php';

if (isset ($_SERVER['REQUEST_METHOD'])) {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        include VIEWS_PATH . 'parser.php';
        exit;
    }
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $data = isset($_POST['links']) ? $_POST['links'] : '';
        preg_match_all("((?:https?:|www\.)[^\s]+)", $data, $links); 

        execInBackground("php " . __FILE__ . " " . $links[0][0]);
        header('Location: /parse');
        exit;
    }
}

if ((php_sapi_name() === 'cli') && $argc > 1) {
    if (preg_match("((?:https?:|www\.)[^\s]+)", $argv[1])) {
        $parser = new \App\Parser\RozetkaParser($argv[1]);
    
        $products = $parser->parse();
    }
}

function execInBackground($cmd){
    if (substr(php_uname(), 0, 7) == "Windows"){ 
        $stream = popen("start /B ". $cmd, "r");
        $output = fread($stream, 2048);
        file_put_contents(LOGS . '/stream_bash_log.log', $output, FILE_APPEND);
        pclose($stream);  
    } else { 
        exec($cmd . " > /dev/null &");   
    } 
} 