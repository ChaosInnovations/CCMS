<?php

namespace Lib\CCMS;

use \Lib\CCMS\IEndpoint;
use \Lib\CCMS\Page;
use \Lib\CCMS\Response;
use \Lib\CCMS\Request;

class PageEndpoint implements IEndpoint
{
    public static function endpointHook(Request $request)
    {
        global $conn;
        
        $pageid = $request->getEndpoint();
        
        if (!Page::pageExists($pageid)) {
            $pageid = "_default/notfound";
        }
        
        $stmt = $conn->prepare("UPDATE users SET collab_pageid=:pid WHERE uid=:uid;");
        $stmt->bindParam(":pid", $pageid);
        $stmt->bindParam(":uid", $authuser->uid);
        $stmt->execute();
        
        $page = new Page($pageid);
        
        $response = new Response;
        
        $response->setContent($page->getContent());
        
        return $response;
    }
}