<?php

namespace Lib\CCMS;

class Response
{
    
    protected $content;
    
    public function __construct($content='')
    {
        $this->content = $content;
    }
    
    public function send()
    {
        // Prevent caching
        header("Expires: 0");
        header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
        header("Cache-Control: no-store, no-cache, must-revalidate");
        header("Cache-Control: post-check=0, pre-check=0", false);
        header("Pragma: no-cache");
        
        ob_end_clean();
        ignore_user_abort(true);
        ob_start();
        
        echo $this->content;
        
        $size = ob_get_length();
        header("Content-Length: {$size}");
        ob_end_flush();
        flush();
    }
    
    public function setContent(string $content)
    {
        $this->content = $content;
    }
}