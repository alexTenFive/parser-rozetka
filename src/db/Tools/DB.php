<?php
namespace App\db\Tools;

use PDO;

class DB
{
    private $pdo;
    private static $db_params;
    private static $instance;

    private function __construct()
    {
        static::$db_params = include(ROOT . '/src/db/config.php');

        $dsn = sprintf("mysql:host=%s;dbname=%s", static::$db_params['host'], static::$db_params['db_name']);
        
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $this->pdo = new PDO($dsn, static::$db_params['db_username'], static::$db_params['db_password'], $options);
        } catch (\PDOException $e) {
            echo sprintf("<b style='font-size: 22px'>Error!</b><br><b>File: %s</b><br><b>Line: %d</b><br><b>Message:</b> %s<hr>", $e->getFile(), $e->getLine(), $e->getMessage());
        }
    }
    
    public static function getConnection(): ?\PDO
    {
        if (empty(static::$instance)) {
            static::$instance = (new static())->pdo;
        }
        
        return static::$instance;
    }
}