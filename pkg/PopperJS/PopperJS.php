<?php

namespace Package;

use \Package\CCMS\Response;

class PopperJS
{
    public static function hookScript()
    {
        return new Response('<script src="/pkg/PopperJS/popper.min.js"></script>', false);
    }
}