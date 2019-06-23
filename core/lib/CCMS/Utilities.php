<?php

namespace Lib\CCMS;

use \Lib\CCMS\Utilities;
use \PDO;

class Utilities
{
    public static function fillTemplate(string $template, array $vars)
    {
        foreach($vars as $k=>$v){
            $template = str_replace('{'.$k.'}', $v, $template);
        }
        return $template;
    }
}