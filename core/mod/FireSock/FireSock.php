<?php

namespace Mod;

use \Lib\CCMS\Request;
use \Mod\FireSock\WebSocketServer;

class FireSock
{
    /** @var WebSocketServer $server */
    private static $server = null;

    public static function hookStartServer(Request $request)
    {
        if (!self::$server instanceof WebSocketServer) {
            self::$server = new WebSocketServer("0.0.0.0", "9000", 2048);
        }
    }

    public static function hookVerifyServer(Request $request)
    {
        //exec("start /b php index.php websocket");
    }

    /** @return boolean */
    public static function isServerRunning()
    {
        return false;
    }
}