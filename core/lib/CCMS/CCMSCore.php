<?php

namespace Lib\CCMS;

use \Lib\CCMS\Request;
use \Lib\CCMS\Response;

class CCMSCore
{
    public function __construct()
    {
    }
    
    public function buildRequest()
    {
        // Returns Request
        return new Request;
    }
    
    public function processRequest(Request $request)
    {
        // Returns Response
        return new Response;
    }
    
    public function dispose()
    {
    }
}