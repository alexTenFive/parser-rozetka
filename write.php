<?php
define('ROOT', __DIR__);
define('LOGS', __DIR__.'/logs');

require_once(__DIR__.'/vendor/autoload.php');   

use App\db\Tools\DBQuery as DBQuery;

error_reporting(-1);
libxml_use_internal_errors(true);
ini_set('display_errors', 'true');

$items = DBQuery::select('items');
print_r($items);