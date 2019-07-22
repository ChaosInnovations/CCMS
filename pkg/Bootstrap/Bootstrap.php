<?php

namespace Package;

use \Package\CCMS\Response;

class Bootstrap
{
    public static function hookLinkCss()
    {
        return new Response('<link rel="stylesheet" href="/pkg/Bootstrap/bootstrap-4.3.1/css/bootstrap.min.css" media="all">', false);
    }

    public static function hookScript()
    {
        return new Response('<script src="/pkg/Bootstrap/bootstrap-4.3.1/js/bootstrap.min.js"></script>', false);
    }
}