<?php
namespace App\Helpers;

use Noodlehaus\Config as ConfigProvider;

class Config
{
    static private $instance = null;
    private $provider = null;
    private static function getInstance()
    {
        if (self::$instance == null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    private function __clone(){}
    private function __construct()
    {
        $this->provider = new ConfigProvider(  ROOT . DIRECTORY_SEPARATOR . 'conf'  );
    }
    public static function get(string $key, $default = null)
    {
        return Config::getInstance()->_get( $key, $default);
    }
    private function _get(string $key, $default = null)
    {
        return $this->provider->get($key, $default) ;
    }
}