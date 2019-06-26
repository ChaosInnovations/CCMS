<?php

namespace Mod;

use \Lib\CCMS\Response;

class PopperJS
{
    public static function hookScript()
    {
        return new Response('<script src="/core/mod/PopperJS/popper.min.js"></script>', false);
    }
}