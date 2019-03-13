<?php
use App\db\Tools\DBQuery;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pagesCount = DBQuery::select('config');

    echo json_encode([
        'success' => true,
        'countProducts' => $pagesCount[0]['productsCount'],
        'pagesCount' => $pagesCount[0]['pagesCount']
    ]);
}
