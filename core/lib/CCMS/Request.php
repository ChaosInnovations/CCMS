<?php

namespace Lib\CCMS;

class Request
{
    
    protected $endpoint = '';
    protected $isWeb = true;
    protected $cookies = [];
    
    public function __construct(array $server, array $cookies)
    {
        $url = trim($server["REQUEST_URI"], "/");
        if (strstr($url, '?')) {
            $url = substr($url, 0, strpos($url, '?'));
        }
        $this->endpoint = $url;
        
        $this->cookies = $cookies;
    }
    
    public function getEndpoint()
    {
        return $this->endpoint;
    }
    
    public function isWeb()
    {
        return $this->isWeb;
    }
    
    public function getCookie(string $key, string $default="")
    {
        if (!isset($this->cookies[$key])) {
            return $default;
        }
        
        return $this->cookies[$key];
    }
}