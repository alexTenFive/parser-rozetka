<?php
define('ROOT', __DIR__);

define('DIRECTORY_SEPARATOR', '/');
define('LOGS', ROOT.'/logs');
define('XLSX_PATH', ROOT.'/xlsx/');
define('XML_PATH', ROOT.'/xml/');
define('VIEWS_PATH', ROOT.'/views/');

require_once(ROOT.'/vendor/autoload.php');   

error_reporting(-1);
libxml_use_internal_errors(true);
ini_set('display_errors', 'true');