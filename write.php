<?php

use App\db\Tools\DBQuery;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use App\Helpers\Config;

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    include VIEWS_PATH . 'writer.php';
    exit;
}

$filename = isset($_POST['filename']) ? $_POST['filename'] . '_' . date("Y-m-d") . '.xlsx' : 'parsed_products_' . date("Y-m-d") . '.xlsx';
$items = DBQuery::select('items');

$spreadsheet = new Spreadsheet();
// Set worksheet title
$spreadsheet->getActiveSheet()->setTitle('Parsed products');

$spreadsheet->setActiveSheetIndex(0)
        ->setCellValue('A1', 'ID')
        ->setCellValue('B1', 'SKU')
        ->setCellValue('C1', 'Price')
        ->setCellValue('D1', 'Category')
        ->setCellValue('E1', 'Images')
        ->setCellValue('F1', 'Name')
        ->setCellValue('G1', 'Description')
        ->setCellValue('H1', 'Seller')
        ->setCellValue('I1', 'IsTop')
        ->setCellValue('J1', 'Vendor')
        ->setCellValue('K1', 'CategoryId');

        $attributesHeaders = [];
        $attributesIndex = Config::get("yml.attributesCharStart");

foreach ($items as $id => $item) {
    $category = DBQuery::select('categories', [['id', '=', $item['category_id']]])[0];
    $categoryId = $category['id'];

    $categoriesList = [];
    $categoriesList[] = $category['name'];

    $parentId = $category['parent_id'];

    while ($row = DBQuery::raw(sprintf("SELECT * FROM categories WHERE id = %s", $parentId))) {
        $parentId = $row[0]['parent_id'];
        $categoriesList[] = $row[0]['name'];
        if (is_null($parentId)) {
            break;
        }
    }

    krsort($categoriesList);

    $category = implode(' > ', $categoriesList);

    $images = array_map(function ($image) {
        return $image['link'];
    }, DBQuery::select('images', [['product_id', '=', $item['id']]]));
    $images = implode(';', $images);

    $attributes = json_decode($item['attributes'], true);
    if ($attributes !== NULL) {
        foreach ($attributes as $key => $value) {

            if ($attributesIndex === 'Z') {
                $attributesIndex = 'AA';
            }
            
            $index = $attributesIndex;

            if (! array_key_exists($key, $attributesHeaders)) {
                $attributesIndex = chr(ord(substr($attributesIndex, strlen($attributesIndex) - 1, 1)) + 1);
                $attributesHeaders[$key] = $index;
            }

    
            
        }
    }
    

    $rowNum = strval($id+2);
    $spreadsheet->getActiveSheet()
    ->setCellValue('A' . $rowNum, (string)$item['id'])
    ->setCellValue('B' . $rowNum, (string)$item['sku'])
    ->setCellValue('C' . $rowNum, (string)$item['price'])
    ->setCellValue('D' . $rowNum, (string)$category)
    ->setCellValue('E' . $rowNum, (string)$images)
    ->setCellValue('F' . $rowNum, (string)$item['name'])
    ->setCellValue('G' . $rowNum, (string)$item['description'])
    ->setCellValue('H' . $rowNum, (string)$item['seller'])
    ->setCellValue('I' . $rowNum, (string)$item['isOnTop']) 
    ->setCellValue('J' . $rowNum, '') 
    ->setCellValue('K' . $rowNum, (string)$categoryId); 

    if ($attributes !== NULL) {
        foreach ($attributes as $key => $value) {
            $spreadsheet->getActiveSheet()->setCellValue($attributesHeaders[$key] . $rowNum, (string)$value);
        }
    }
    

}

foreach ($attributesHeaders as $value => $column) {
    $spreadsheet->getActiveSheet()
    ->setCellValue($column . '1', (string)$value);
}

$writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
$writer->save(XLSX_PATH . '/' . $filename);

$file = XLSX_PATH . '/' . $filename;

if (file_exists($file)) {
    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="'.basename($file).'"');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . filesize($file));
    readfile($file);
    exit;
}