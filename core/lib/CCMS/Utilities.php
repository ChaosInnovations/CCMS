<?php

namespace Lib\CCMS;

use \Lib\CCMS\Utilities;
use \DirectoryIterator;
use \PDO;

class Utilities
{
    public static $module_manifest = null;
    public static function getModuleManifest() {
        if (self::$module_manifest === null) {
            self::$module_manifest = [];

            $moduleDirs = [
                $_SERVER["DOCUMENT_ROOT"] . "/core/lib",
                $_SERVER["DOCUMENT_ROOT"] . "/core/mod",
                $_SERVER["DOCUMENT_ROOT"] . "/modules"
            ];
        
            foreach ($moduleDirs as $searchDir) {
                $dir = new DirectoryIterator($searchDir);
                foreach ($dir as $fileinfo) {
                    if ($fileinfo->isDot()) {
                        continue;
                    }
                    if (!$fileinfo->isDir()) {
                        continue;
                    }
                
                    if (!file_exists($searchDir . "/" . $fileinfo->getFilename() . "/manifest.json")) {
                        continue;
                    }
                
                    if (!is_file($searchDir . "/" . $fileinfo->getFilename() . "/manifest.json")) {
                        continue;
                    }
                
                    $manifest = json_decode(file_get_contents($searchDir . "/" . $fileinfo->getFilename() . "/manifest.json"), true);

                    self::$module_manifest[$fileinfo->getFilename()] = $manifest;
                }
            }
        }

        return self::$module_manifest;
    }

    public static function fillTemplate(string $template, array $vars)
    {
        foreach($vars as $k=>$v){
            $template = str_replace('{'.$k.'}', $v, $template);
        }
        return $template;
    }
}