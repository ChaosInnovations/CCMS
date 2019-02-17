<?php

namespace Lib\CCMS;

class Response
{
    
    protected $content;
    
    public function send()
    {
        echo $this->content;
    }
    
    public function setContent(string $content)
    {
        $this->content = $content;
    }
}