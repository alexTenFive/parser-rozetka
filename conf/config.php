<?php
define('DIRECTORY_SEPARATOR', '/');
define('LOGS', ROOT.'/logs');
define('XLSX_PATH', ROOT.'/xlsx/');
define('XML_PATH', ROOT.'/xml/');

require_once(ROOT.'/vendor/autoload.php');   

error_reporting(-1);
libxml_use_internal_errors(true);
ini_set('display_errors', 'true');