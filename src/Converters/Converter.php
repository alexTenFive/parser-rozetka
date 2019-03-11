<?php

namespace App\Converters;

use PhpOffice\PhpSpreadsheet\Exception;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use App\Helpers\Config as Config;
use App\Helpers\SimpleXMLExtended as SimpleXMLExtended;

class Converter
{
    protected static $instance = null;

    /**
     * @return Converter
     */
    public static function getInstance()
    {
        if (null === static::$instance) {
            static::$instance = new static();
        }
        return static::$instance;
    }

    public static function convert(string $pathToXLSX): bool
    {
        return static::getInstance()->_convert($pathToXLSX);
    }

    protected function _convert(string $pathToXLSX): bool
    {
        try {

            $reader = new Xlsx();
            $spreadsheet = $reader->load($pathToXLSX);
            $sheetData = $spreadsheet->getActiveSheet()->toArray(null, true, true, true);
            $sheetTitles = array_shift($sheetData);
            if (!empty($sheetData)) {

                $mainEl = Config::get("xml.main.name", "elements");
                $newXML = new SimpleXMLExtended("<?xml version='1.0' encoding='utf-8'?><$mainEl></$mainEl>");

                foreach (Config::get("xml.main.attrs", []) as $attr)
                    $newXML->addAttribute($attr['name'], $attr['value']);

                foreach ($sheetData as $row) {
                    $job = $newXML->addChild(Config::get("xml.child.name", "element"));
                    foreach ($row as $key => $data) {
                        $job->{$sheetTitles[$key]} = null;

                        if( in_array($sheetTitles[$key], Config::get("xml.child.cdata", [])) )
                            $job->{$sheetTitles[$key]}->addCData($data);
                        else
                            $job->{$sheetTitles[$key]} = $data;

                    }
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
}
