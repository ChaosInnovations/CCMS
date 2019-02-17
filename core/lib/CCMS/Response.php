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