<?php

namespace Lib\CCMS;

use \Exception;
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
        $sapi_name = php_sapi_name();
        
        // Returns Request
        return new Request($_SERVER, $_COOKIE, $sapi_name);
    }
    
    public function processRequest(Request $request)
    {
        $response = new Response();
        $response->setFinal(false);
        
        include_once $_SERVER["DOCUMENT_ROOT"]."/hooks.php";
        // Enumerate hooks
        foreach ($hooks as $hook) {
            $hookRegex = $hook[0];
            $hookFunctionName = $hook[1];
            if (!preg_match($hookRegex, $request->getTypedEndpoint())) {
                continue;
            }
            
            $result = null;
            
            try {
                $result = $hookFunctionName($request, $response);
            } catch (Exception $e) {
                $response->append(new Response($e));
            }
            
            if ($result instanceof Response) {
                $response->append($result);
            }
            
            if ($response->isFinal()) {
                break;
            }
        }
        // Returns Response
        return $response;
    }
    
    public function dispose()
    {
    }
}