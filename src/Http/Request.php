<?php
namespace App\Http;

class Request
{
    public $lastHTTPCode = null;
    public $lastErrorCode = null;
    protected $handler;
    /**
     *
     * @param string $url
     * @return string
     */
    public function makeRequest(string $url)
    {
        $this->handler = $this->_create_handle();
        
        $res = $this->setProxy('CURLPROXY_HTTPS', '192.166.219.46:3128');

        $this->lastErrorCode = null;
        $this->lastHTTPCode = null;
        curl_setopt($this->handler, CURLOPT_URL, $url);
        $response = curl_exec($this->handler);

        if (curl_errno($this->handler)) {
            $this->lastErrorCode = curl_errno($this->handler);
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
    /**
     * If want use proxy for every request, just set it with this.
     *
     * @param string $proxyType
     * @param string $proxyAddr
     */
    public function setProxy(string $proxyType = '', string $proxyAddr = ''): bool
    {
        if ($proxyType && $proxyAddr)
        {
            curl_setopt($this->handler, CURLOPT_PROXY, $proxyAddr);
            curl_setopt($this->handler, CURLOPT_PROXYTYPE, $proxyType);
            curl_setopt($this->handler, CURLOPT_CONNECTTIMEOUT, 20);
            curl_setopt($this->handler, CURLOPT_TIMEOUT, 30);
            return true;
        } else {
            return false;
        }
    }
    /**
     * Create cURL instance.
     */
    private function _create_handle()
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 60);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        return $ch;
    }
}