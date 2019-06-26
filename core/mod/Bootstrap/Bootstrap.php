<?php

namespace Mod;

use \Lib\CCMS\Response;

class Bootstrap
{
    public static function hookLinkCss()
    {
        return new Response('<link rel="stylesheet" href="/core/mod/Bootstrap/bootstrap-4.1.1/css/bootstrap.min.css" media="all">', false);
    }

    public static function hookScript()
    {
        return new Response('<script src="/core/mod/Bootstrap/bootstrap-4.1.1/js/bootstrap.min.js"></script>', false);
    }
}