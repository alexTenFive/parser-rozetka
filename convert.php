<?php

use App\Helpers\RozetkaConverter;

$xlsxDir = opendir(XLSX_PATH);
while (false !== ($file = readdir($xlsxDir))) {
    if (preg_match('/^.+\.xlsx/i', $file)) {
        RozetkaConverter::convert( XLSX_PATH . $file);
    }
}