<?php

namespace Mod;

use \Lib\CCMS\Response;

class JQuery
{
    public static function hookScript()
    {
        return new Response('<script src="/core/mod/JQuery/jquery-3.3.1.min.js"></script>', false);
    }
}