<?php

namespace Mod;

use \Lib\CCMS\Response;

class FontAwesome
{
    public static function hookLinkCss()
    {
        return new Response('<link rel="stylesheet" href="/core/mod/FontAwesome/fontawesome-5.0.13/css/fontawesome-all.min.css" media="all">', false);
    }
}