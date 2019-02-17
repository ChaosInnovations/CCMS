<?php

namespace Lib\CCMS\Security;

use \Lib\CCMS\IEndpoint;
use \Lib\CCMS\Security\User;
use \Lib\CCMS\Security\AccountManager;
use \Lib\CCMS\Response;
use \Lib\CCMS\Request;

class UserHook implements IEndpoint
{
    public static function endpointHook(Request $request)
    {
        $token = $request->getCookie("token");
        
        if (AccountManager::validateToken($token, $_SERVER["REMOTE_ADDR"])) {
            User::$currentUser = User::userFromToken($token);
            return;
        }
        
        User::$currentUser = new User(null);
        
        setcookie("token", "0", 1);
    }
}