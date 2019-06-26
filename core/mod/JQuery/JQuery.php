<?php

namespace Mod;

use \Lib\CCMS\Response;

class JQuery
{
    public static function hookLinkScript()
    {
        return new Response('<script src="/core/mod/JQuery/jquery-3.3.1.min.js"></script>', false);
    }
}