<?php

namespace Lib\CCMS;

use \Lib\CCMS\IEndpoint;
use \Lib\CCMS\Request;
use \Lib\CCMS\Response;

class CCMSCore
{
    public function __construct()
    {
        date_default_timezone_set("UTC");
    }
    
    public function buildRequest()
    {
        // Returns Request
        return new Request($_SERVER, $_COOKIE);
    }
    
    public function processRequest(Request $request)
    {
        if ($request->isWeb()) {
            include_once $_SERVER["DOCUMENT_ROOT"]."/endpoints.php";
            // Enumerate endpoints 
            foreach ($endpoints as $endpointRegex => $endpointClassName) {
                if (!preg_match($endpointRegex, $request->getEndpoint())) {
                    continue;
                }
                $endpointClass = new $endpointClassName;
                if (!$endpointClass instanceof IEndpoint) {
                    continue;
                }
                $result = $endpointClassName::endpointHook($request);
                if ($result instanceof Response) {
                    return $result;
                }
            }
        }
        // Returns Response
        return new Response;
    }
    
    public function dispose()
    {
    }
}