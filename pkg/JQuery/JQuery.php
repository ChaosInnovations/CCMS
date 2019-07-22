<?php

namespace Package;

use \Package\CCMS\Response;

class JQuery
{
    public static function hookScript()
    {
        return new Response('<script src="/pkg/JQuery/jquery-3.3.1.min.js"></script>', false);
    }
}