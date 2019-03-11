<?php
namespace App\Converters;

use PhpOffice\PhpSpreadsheet\Exception;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use App\Helpers\Config as Config;
use App\Helpers\SimpleXMLExtended as SimpleXMLExtended;
use App\db\Tools\DBQuery;

class RozetkaConverter extends Converter
{
    private $ignore = [
        'istop',
        'seller',
        'categoryid'
    ];

    protected function _convert(string $pathToXLSX): bool
    {
        try {
            $reader = new Xlsx();
            $spreadsheet = $reader->load($pathToXLSX);

            $sheetData = $spreadsheet->getActiveSheet()->toArray(null, true, true, true);

            $sheetTitles = array_shift($sheetData);
            $sheetTitles = array_map('mb_strtolower', $sheetTitles);

            $sheetData = array_map(function ($item) use ($sheetTitles) {
                $categories = explode(' > ', $item[array_search('category', $sheetTitles)]);
                $item[array_search('category', $sheetTitles)] = $categories[count($categories) - 1];
                return $item;
            }, $sheetData);

            $data = array_map(function ($item) use ($sheetTitles) { return $item[array_search('category', $sheetTitles)]; }, $sheetData);
            $data = array_unique($data);

            $args = "";
            foreach (array_values($data) as $i => $item) {
                if ($i == count($data) - 1) {
                    $args .= "'$item'";
                    break;
                }
                $args .= "'$item', ";
            }

            $categoriesData = DBQuery::raw(sprintf("SELECT * FROM categories c WHERE name in (%s)", $args));

            $parentIds = array_unique(array_map(function ($item) {
                return $item['parent_id'];
            }, $categoriesData));

            foreach ($parentIds as $id) {
                $parentId = $id;
                while ($row = DBQuery::raw(sprintf("SELECT * FROM categories WHERE id IS NOT NULL AND id = %s", $parentId))) {
                    $categoriesData[] = $row[0];
                    $parentId = $row[0]['parent_id'];
                    if (is_null($parentId)) {
                        break;
                    }
                }
            }
        
            $attributesChar = Config::get("yml.attributesCharStart");

            if (!empty($sheetData)) {

                $headerEl = Config::get("yml.main.header", "elements");
                $mainEl = Config::get("yml.main.name", "elements");
                $childrenName = Config::get("yml.main.childrenName", "elements");

                $currentDate = date('Y-m-d H:m');
                $newXML = new SimpleXMLExtended("<?xml version='1.0' encoding='utf-8'?><$headerEl date='$currentDate'><$mainEl></$mainEl></$headerEl>");
                $shopXML = $newXML->{$mainEl}[0];
                $shopXML->addChild('name', 'Rozetka');
                $shopXML->addChild('company', 'My company');
                $shopXML->addChild('platform', 'Yandex.YML for OpenCart (ocStore)');

                $currenciesXML = $shopXML->addChild('currencies');
                $currencyXML = $currenciesXML->addChild('currency');
                $currencyXML->addAttribute('id', Config::get("yml.main.currency.id", "element"));
                $currencyXML->addAttribute('rate', Config::get("yml.main.currency.rate", "element"));

                $shopXML->addChild('categories');
                $categoriesXML = $shopXML->categories[0];
                
                $addedCategoriesIds = [];

                if (! empty($categoriesData)) {
                    foreach ($categoriesData as $categoryData) {
                        if (array_search($categoryData['id'], $addedCategoriesIds) !== false)
                            continue;
                        
                        $categoryXML = $categoriesXML->addChild('category', $categoryData['name']); 
                        $categoryXML->addAttribute('id', $categoryData['id']); 
                        if (! empty($categoryData['parent_id'])) {
                            $categoryXML->addAttribute('parentId', $categoryData['parent_id']); 
                        }
                        $addedCategoriesIds[] = $categoryData['id'];
                    }
                }

                $shopXML->addchild($childrenName);
                $offersXML = $newXML->{$mainEl}[0]->{$childrenName}[0];

                foreach ($sheetData as $row) {
                    $job = $offersXML->addChild(Config::get("yml.child.name", "element"));
                    foreach ($row as $key => $data) {
                        if ($sheetTitles[$key] === 'vendor') {
                            if (empty($data)) {
                                $data = $row[array_search('страна-производитель товара', $sheetTitles)];
                            }
                            $job->{$sheetTitles[$key]} = null;
                            $job->{$sheetTitles[$key]} = $data;
                        } else if (array_search($sheetTitles[$key], $this->ignore) !== false) {
                            continue;
                        } else if ($sheetTitles[$key] === 'category') {
                            $job->{'categoryId'} = null;
                            $job->{'categoryId'} = $row[array_search('categoryid', $sheetTitles)];
                        } else if ($key >= $attributesChar && !empty($data)) {
                            $param = $job->addChild(Config::get("yml.attributesFieldName"), $data);
                            $param->addAttribute('name', $this->mb_ucfirst($sheetTitles[$key], 'utf8'));
                            continue;
                        } else if ($sheetTitles[$key] === 'images') {
                            foreach (explode(';', $data) as $link) {
                                $param = $job->addChild('picture', $link);
                            }
                        } else if ( in_array($sheetTitles[$key], Config::get("yml.child.cdata", [])) ) {
                            $job->{$sheetTitles[$key]} = null;    
                            $job->{$sheetTitles[$key]}->addCData($data);
                        } else if ($key < $attributesChar) {
                            $job->{$sheetTitles[$key]} = null;
                            $job->{$sheetTitles[$key]} = $data;
                        }
                    }
                    $job->addChild('currencyId', Config::get("yml.main.currency.id"));
                }

                $dom = dom_import_simplexml($newXML)->ownerDocument;
                $dom->formatOutput = true;
                $formattedXML = $dom->saveXML();

                $originalPathInfo = pathinfo($pathToXLSX);
                $fp = fopen(XML_PATH . $originalPathInfo['filename'] . ".xml", 'w+');
                fwrite($fp, $formattedXML);
                fclose($fp);
            }

        } catch (Exception $e) {
            //TODO Exceptions representation
            return false;
        }
        return true;
    }

    private function mb_ucfirst($string, $encoding)
    {
        $strlen = mb_strlen($string, $encoding);
        $firstChar = mb_substr($string, 0, 1, $encoding);
        $then = mb_substr($string, 1, $strlen - 1, $encoding);
        return mb_strtoupper($firstChar, $encoding) . $then;
    }
}