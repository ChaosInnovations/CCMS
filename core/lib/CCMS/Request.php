<?php

namespace Lib\CCMS;

class Request
{
    
    protected $endpoint = '';
    protected $isWeb = true;
    protected $cookies = [];
    
    public function __construct(array $server, array $cookies=[], $sapi_name="apache2handler")
    {
        if (substr($sapi_name, 0, 3) == 'cli' || empty($server['REMOTE_ADDR'])) {
            $this->isWeb = false;
            
            global $argv;
            if (isset($argv)) {
                foreach ($argv as $arg) {
                    $e=explode("=",$arg);
                    if(count($e)==2)
                        $_GET[$e[0]]=$e[1];
                    else    
                        $_GET[$e[0]]=0;
                }
            }
        }
        
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
    
    public function getTypedEndpoint()
    {
        return ($this->isWeb ? "web:" : "cli:") . $this->endpoint;
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