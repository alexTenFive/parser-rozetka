<?php

use App\db\Tools\DBQuery;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

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
        ->setCellValue('E1', 'Картинки')
        ->setCellValue('F1', 'Название')
        ->setCellValue('G1', 'Описание')
        ->setCellValue('H1', 'Продавец')
        ->setCellValue('I1', 'В ТОПе?')
        ->setCellValue('J1', 'Атрибуты');

        $attributesHeaders = [];
        $attributesIndex = 'K';

foreach ($items as $id => $item) {
    $categoryName = DBQuery::select('categories', [['id', '=', $item['category_id']]], ['name'])[0]['name'];
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
    ->setCellValue('D' . $rowNum, (string)$categoryName)
    ->setCellValue('E' . $rowNum, (string)$images)
    ->setCellValue('F' . $rowNum, (string)$item['name'])
    ->setCellValue('G' . $rowNum, (string)$item['description'])
    ->setCellValue('H' . $rowNum, (string)$item['seller'])
    ->setCellValue('I' . $rowNum, (string)$item['isOnTop']); 

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