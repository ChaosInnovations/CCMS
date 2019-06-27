<?php

namespace Mod;

use \Lib\CCMS\Request;
use \Lib\CCMS\Utilities;
use \Mod\ModuleMenu;
use \Mod\SecureMenu;

class ModuleManager {
    public static function hookMenu(Request $request) {
        $moduleModalEntryTemplate = file_get_contents(dirname(__FILE__) . "/templates/ModuleModalEntry.template.html");

        $moduleList = "";

        $sortedModuleManifest = Utilities::getModuleManifest();
        ksort($sortedModuleManifest, SORT_NATURAL);

        foreach ($sortedModuleManifest as $module_name => $module) {
            $dependencyList = "";
            foreach (array_merge($module["dependencies"]["libraries"], $module["dependencies"]["modules"]) as $dependency) {
                $dependencyList .= "{$dependency["name"]}&nbsp;v" . implode(".", $dependency["min_version"]) . "<br />";
            }

            if ($dependencyList === "") {
                $dependencyList = "None";
            }

            $uninstall_button = '<button class="btn btn-outline-danger" title="Uninstall Module"><i class="fas fa-trash"></i></button>';
            if ($module["dependencies"]["has_dependent"]) {
                $uninstall_button = "";
            }

            $template_vars = [
                'name' => $module["module_data"]["name"],
                'description' => $module["module_data"]["description"],
                'version' => implode(".", $module["module_data"]["version"]),
                'release' => date("F j, Y", strtotime($module["module_data"]["release_date"])),
                'authors' => "<b>Name:</b>&nbsp;{$module["module_data"]["author"]["name"]}<br /><b>Email:</b>&nbsp;{$module["module_data"]["author"]["email"]}<br /><b>Website:</b>&nbsp;{$module["module_data"]["author"]["website"]}<br />",
                'dependencies' => $dependencyList,
                'uninstall_button' => $uninstall_button,
            ];
            $moduleList .= Utilities::fillTemplate($moduleModalEntryTemplate, $template_vars);
        }

        $template_vars = [
            'modulelist' => $moduleList,
        ];
        $moduleManagerBody = Utilities::fillTemplate(file_get_contents(dirname(__FILE__) . "/templates/ModuleModalBody.template.html"), $template_vars);
        SecureMenu::Instance()->addModal("dialog_modules", "Manage Modules", $moduleManagerBody, "");
        ModuleMenu::Instance()->addEntry("showDialog('modules');", "Manage Modules");
    }
}