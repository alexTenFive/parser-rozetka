<?php
define('ROOT', __DIR__);
define('LOGS', __DIR__.'/logs');
define('RESULTS', __DIR__.'/result');

require_once(__DIR__.'/vendor/autoload.php');   

use App\db\Tools\DBQuery as DBQuery;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

error_reporting(-1);
libxml_use_internal_errors(true);
ini_set('display_errors', 'true');


$filename = 'parsed_products.xlsx';
$items = DBQuery::select('items');

$spreadsheet = new Spreadsheet();
 
// Set worksheet title
$spreadsheet->getActiveSheet()->setTitle('Parsed products');

$spreadsheet->setActiveSheetIndex(0)
        ->setCellValue('A1', 'ID')
        ->setCellValue('B1', 'SKU')
        ->setCellValue('C1', 'Цена')
        ->setCellValue('D1', 'Категория')
        ->setCellValue('E1', 'Название')
        ->setCellValue('F1', 'Описание')
        ->setCellValue('G1', 'Продавец')
        ->setCellValue('H1', 'В ТОПе?')
        ->setCellValue('I1', 'Атрибуты');

        $attributesHeaders = [];
        $attributesIndex = 'J';

foreach ($items as $id => $item) {
    $categoryName = DBQuery::select('categories', [['id', '=', $item['category_id']]], ['name'])[0]['name'];
    $images = array_map(function ($image) {
        return $image['link'];
    }, DBQuery::select('images', [['product_id', '=', $item['id']]]));
    $images = implode(';', $images);

    $attributes = json_decode($item['attributes'], true);
    if ($attributes !== null || $attributes !== 'null') {
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
    ->setCellValue('D' . $rowNum, (string)$categoryName)
    ->setCellValue('E' . $rowNum, (string)$item['name'])
    ->setCellValue('F' . $rowNum, (string)$item['description'])
    ->setCellValue('G' . $rowNum, (string)$item['seller'])
    ->setCellValue('H' . $rowNum, (string)$item['isOnTop']);    

    if ($attributes !== null || $attributes !== 'null') {
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
$writer->save(RESULTS . '/' . $filename);