<?php

namespace Mod;

use \Lib\CCMS\Request;
use \Lib\CCMS\Response;
use \Lib\CCMS\Utilities;

class Placeholders
{
    public static function hookEvaluatePlaceholders(Request $request, Response $response)
    {
        global $availablemodules, $modules;

        $content = $response->getContent();

        $placeholder_hooks = [];

        foreach (Utilities::getModuleManifest() as $module_name => $module_manifest) {
            if (!isset($module_manifest["module_placeholders"])) {
                continue;
            }
        
            if (!isset($module_manifest["module_placeholders"]["placeholders"])) {
                continue;
            }
        
            $placeholders = $module_manifest["module_placeholders"]["placeholders"];
        
            foreach ($placeholders as $placeholder) {
                array_push($placeholder_hooks, [$placeholder["hook"], $placeholder["target"]]);
            }
        }

        array_push($placeholder_hooks, ['/.*/', "\Mod\Placeholders::placeholderFallback"]);

        $placeholders = [[]];
        $iterations = 0;
        $maxIterations = 4;
        do {
            foreach ($placeholders[0] as $pcode) {
                $pcode_trim = trim($pcode, "{}");
                $funcparts = explode(":", $pcode_trim, 2);
                $func = $funcparts[0];
                $args = [];
                if (count($funcparts) == 2) {
                    $args = explode(";", $funcparts[1]);
                }
                
                // Enumerate hooks
                foreach ($placeholder_hooks as $hook) {
                    $hookRegex = $hook[0];
                    $hookFunctionName = $hook[1];

                    if (!preg_match($hookRegex, $func)) {
                        continue;
                    }
                    
                    $result = "";
                    
                    try {
                        $result = $hookFunctionName($args, $func, $request);
                    } catch (Exception $e) {
                        $template_vars = [
                            "function" => $func,
                            "description" => $e,
                        ];
                        $result = Utilities::fillTemplate(file_get_contents(dirname(__FILE__) . "/PlaceholderException.template.html"), $template_vars);
                    }
                    
                    $content = str_replace($pcode, $result, $content);
                    break;
                }
            }

            $placeholders = [[]];
            preg_match_all("/\{{2}[^\}]+\}{2}/", $content, $placeholders);
        } while ($iterations++ <= $maxIterations && count($placeholders[0]));

        $response->setContent($content);
    }
    
    public static function placeholderFallback($args, $func)
    {
        $template_vars = [
            "function" => $func,
        ];
        $result = Utilities::fillTemplate(file_get_contents(dirname(__FILE__) . "/PlaceholderFallback.template.html"), $template_vars);
        return $result;
    }
}