<?php
define('ROOT', __DIR__);
define('LOGS', __DIR__.'/logs');

require_once(__DIR__.'/vendor/autoload.php');   

use App\db\Tools\DBQuery as DBQuery;

error_reporting(-1);
libxml_use_internal_errors(true);
ini_set('display_errors', 'true');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $parser = new App\Parser\Parser([
        'https://rozetka.com.ua/elektronnie-sigarety-batareinie-mody-atomaizery/c4627066/',
        'https://rozetka.com.ua/zajimnoy-instrument/c2798802/',
    ]);
    
    $products = $parser->parse();
    
    print_r($products);
} else {
    echo '<form method="POST"><input type="submit" value="Parse"></input></form>';
}
