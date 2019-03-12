<?php

use App\Converters\{Converter, RozetkaConverter};

$xlsxDir = opendir(XLSX_PATH);

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $filenames = [];

    foreach (glob(XLSX_PATH . '*.xlsx') as $filename) {
            $filenames[] = pathinfo($filename)['basename'];
    }

    include VIEWS_PATH . 'converter.php';
    exit;
}

if (isset($_FILES['filename_u'])) {
    $filename_u = $_FILES['filename_u'];
    if (pathinfo($filename_u['name'])['extension'] === 'xlsx') {
        move_uploaded_file( $filename_u['tmp_name'], XLSX_PATH . $filename_u['name']);
    }

    header('Location: /convert');
}

$filename = isset($_POST['filename']) ? $_POST['filename'] : '';


if (file_exists(XLSX_PATH . $filename)) {
    RozetkaConverter::convert( XLSX_PATH . $filename);
}

$file = XML_PATH . pathinfo($filename)['filename'] . '.xml';
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