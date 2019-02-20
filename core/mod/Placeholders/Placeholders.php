<?php

namespace Mod;

use \Lib\CCMS\Request;
use \Lib\CCMS\Response;

class Placeholders
{
    public static function hookEvaluatePlaceholders(Request $request, Response $response)
    {
        global $availablemodules, $modules;

        $content = $response->getContent();
        
        include_once $_SERVER["DOCUMENT_ROOT"]."/core/mod/Placeholders/placeholder_hooks.php";

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
                        $result = $hookFunctionName($args, $func);
                    } catch (Exception $e) {
                        $result = "
                            <script>
                                console.error('Exception in \'{$func}\' in module \'{$module}\':\n{$e}');
                            </script>
                        ";
                    }
                    
                    $content = str_replace($pcode, $result, $content);
                    break;
                }
            }

            $placeholders = [[]];
            preg_match_all("/\{{2}[^\}]+\}{2}/", $content, $placeholders);
        } while ($iterations++ < $maxIterations && count($placeholders[0]));

        $response->setContent($content);
    }
    
    public static function placeholderFallback($args, $func)
    {
        return "
            <script>
                console.warn('No placeholder hooks matched \'{$func}\'!');
            </script>
        ";
    }
}