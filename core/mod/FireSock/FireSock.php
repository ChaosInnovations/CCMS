<?php

namespace Mod;

use \Lib\CCMS\Request;
use \Lib\CCMS\Response;
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

    public static function hookLongPollToken(Request $request)
    {
        $token = bin2hex(openssl_random_pseudo_bytes(16));
        return new Response($token, true);
    }

    public static function hookLongPoll(Request $request)
    {
        sleep(rand(2,10));
        return new Response('["msg test", "msg test2"]', true);
    }

    public static function hookLongPollInbound(Request $request)
    {
        // Long-polling inbound messages
        return new Response('ok', true);
    }

    /** @return boolean */
    public static function isServerRunning()
    {
        return false;
    }
}