<?php

use App\Parser\RozetkaParser;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $parser = new RozetkaParser([
        'https://rozetka.com.ua/elektronnie-sigarety-batareinie-mody-atomaizery/c4627066/',
        'https://rozetka.com.ua/zajimnoy-instrument/c2798802/',
    ]);
    
    $products = $parser->parse();
    
    print_r($products);
} else {
    echo '<form method="POST"><input type="submit" value="Parse"></input></form>';
}
