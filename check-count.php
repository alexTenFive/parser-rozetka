<?php

use App\db\Tools\DBQuery;

$products = DBQuery::raw("SELECT COUNT(*)as c FROM items");
$pagesCount = DBQuery::select('config');

echo json_encode([
    'success' => true,
    'countProducts' => $products[0]['c'],
    'pagesCount' => $pagesCount[0]['pagesCount']
]);
exit;