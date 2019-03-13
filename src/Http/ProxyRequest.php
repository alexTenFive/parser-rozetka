<?php

namespace App\Http;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;

class ProxyRequest extends Request
{
    private $proxyCounter = 0;
    private $proxiesFile = ROOT.'/proxies.dat';
    private $proxy = PROXY_ADDR;
    private $logger;

    function __construct()
    {
        $this->logger = new Logger('proxy-channel');
        $this->logger->pushHandler(new StreamHandler(LOGS.'/proxy.log', Logger::DEBUG));
    }

    public function makeRequest(string $url)
    {
        $this->handler = $this->_create_handle();
        $this->logger->info("Proxy setting: " . $this->proxy);
        $this->setProxy('CURLPROXY_HTTPS', $this->proxy);

        $this->lastErrorCode = null;
        $this->lastHTTPCode = null;
        curl_setopt($this->handler, CURLOPT_URL, $url);
        $response = curl_exec($this->handler);

        if (curl_errno($this->handler)) {
            $this->lastErrorCode = curl_errno($this->handler);
            
            curl_close($this->handler);
            /**
             * if was an error, then whe check file with proxies
             * and retry the request with different proxy
             */
            if (file_exists($this->proxiesFile)) {
                $lines = file($this->proxiesFile);
                $this->logger->info("Proxy was changed: " . $lines[$this->proxyCounter]);
                $this->proxy = $lines[$this->proxyCounter++];
                return $this->makeRequest($url);
            }
            return false;
        } else {
            $this->lastHTTPCode = (int)curl_getinfo($this->handler, CURLINFO_HTTP_CODE);
            if ($this->lastHTTPCode != 200){
                return;
            }
        }

        curl_close($this->handler);
        return trim($response);
    }
}