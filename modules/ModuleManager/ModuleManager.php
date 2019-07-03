<?php

namespace Mod;

use \Lib\CCMS\Request;
use \Lib\CCMS\Response;
use \Lib\CCMS\Utilities;
use \Mod\ModuleMenu;
use \Mod\SecureMenu;

class ModuleManager {
    public static function hookMenu(Request $request)
    {
        $availablePackageCache = self::getPackageInfoCache();
        $packageCacheUpdated = strtotime($availablePackageCache["checked"]);
        $packageCacheData = $availablePackageCache["packages"];
        
        $sortedModuleManifest = Utilities::getModuleManifest();
        ksort($sortedModuleManifest, SORT_NATURAL);

        $moduleModalEntryTemplate = file_get_contents(dirname(__FILE__) . "/templates/ModuleModalEntry.template.html");
        $moduleModalUpdateEntryTemplate = file_get_contents(dirname(__FILE__) . "/templates/ModuleModalUpdateEntry.template.html");
        $moduleModalNoUpdatesTemplate = file_get_contents(dirname(__FILE__) . "/templates/ModuleModalUpdateEntryNone.template.html");
        $moduleModalNewEntryTemplate = file_get_contents(dirname(__FILE__) . "/templates/ModuleModalNewEntry.template.html");
        $moduleModalNoNewTemplate = file_get_contents(dirname(__FILE__) . "/templates/ModuleModalNewEntryNone.template.html");
        $installButtonTemplate = file_get_contents(dirname(__FILE__) . "/templates/InstallButton.template.html");
        $installButtonOptionTemplate = file_get_contents(dirname(__FILE__) . "/templates/InstallButtonOption.template.html");

        $updateList = "";
        foreach ($availablePackageCache["updates"] as $pkg_name => $pkg_versions) {
            $ref_pkg_version = end($pkg_versions);

            $version_options = "";
            foreach ($pkg_versions as $version_id => $pkg_data) {
                $template_vars = [
                    'version_id' => $version_id,
                    'version_text' => "v" . implode(".", $pkg_data["module_data"]["version"]),
                ];
                $version_options .= Utilities::fillTemplate($installButtonOptionTemplate, $template_vars);
            }

            $template_vars = [
                'pkg_id' => $pkg_name,
                'options' => $version_options,
            ];
            $install_button = Utilities::fillTemplate($installButtonTemplate, $template_vars);

            $template_vars = [
                'name' => $ref_pkg_version["module_data"]["name"],
                'description' => $ref_pkg_version["module_data"]["description"],
                'current_version' => implode(".", $sortedModuleManifest[$pkg_name]["module_data"]["version"]),
                'install_button' => $install_button,
            ];
            $updateList .= Utilities::fillTemplate($moduleModalUpdateEntryTemplate, $template_vars);
        }
        if ($updateList == "") {
            $updateList = $moduleModalNoUpdatesTemplate;
        }

        $moduleList = "";
        foreach ($sortedModuleManifest as $module_name => $module) {
            $dependencyList = "";
            foreach (array_merge($module["dependencies"]["libraries"], $module["dependencies"]["modules"]) as $dependency) {
                $dependencyList .= "{$dependency["name"]}&nbsp;v" . implode(".", $dependency["min_version"]) . "<br />";
            }

            if ($dependencyList === "") {
                $dependencyList = "None";
            }

            $hasUpdate = isset($availablePackageCache["updates"][$module_name]);
            $uA_text = ($hasUpdate ? "&nbsp;<button class=\"btn btn-link\" title=\"Update Available\" style=\"padding: 0;\"><i class=\"fas fa-chevron-circle-up\"></i></button>" : "");

            $uninstall_button = '<button class="btn btn-outline-danger" title="Uninstall Module"><i class="fas fa-trash"></i></button>';
            if ($module["dependencies"]["has_dependent"]) {
                $uninstall_button = '<button class="btn btn-outline-secondary" title="Other modules depend on this" disabled="disabled"><i class="fas fa-trash"></i></button>';
            }

            $template_vars = [
                'ua_icon' => $uA_text,
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
            'updatelist' => $updateList,
            'modulelist' => $moduleList,
            'newlist' => $moduleModalNoNewTemplate,
        ];
        $moduleManagerBody = Utilities::fillTemplate(file_get_contents(dirname(__FILE__) . "/templates/ModuleModalBody.template.html"), $template_vars);
        SecureMenu::Instance()->addModal("dialog_modules", "Manage Modules", $moduleManagerBody, "");
        ModuleMenu::Instance()->addEntry("showDialog('modules');", "Manage Modules");
    }

    public static function hookUninstall(Request $request)
    {

    }

    public static function hookInstall(Request $request)
    {

    }

    public static function getPackageInfoCache() {
        if (!file_exists(dirname(__FILE__) . "/packageInfoCache.json")) {
            return ["checked" => "never", "packages" => []];
        }

        return json_decode(file_get_contents(dirname(__FILE__) . "/packageInfoCache.json"), true);
    }

    public static function hookCheckModules(Request $request) {
        $SRV_URL = "http://ccms.thomasboland.me";
        $VER_URL = $SRV_URL . "/latest.php?mod";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $VER_URL);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
        $out = curl_exec($ch);
        curl_close($ch);

        $data = [
            "checked" => date("Y-m-d H:i:s"),
            "packages" => json_decode($out, true),
        ];

        // Check for new packages and available updates
        $newPackages = [];
        $availableUpdates = [];
        $moduleManifest = Utilities::getModuleManifest();
        foreach ($data["packages"] as $pkg_name => $pkg_versions) {
            if (!isset($moduleManifest[$pkg_name])) {
                $newPackages[$pkg_name] = $pkg_versions;
                continue;
            }

            $curVer = $moduleManifest[$pkg_name]["module_data"]["version"];
            $availableUpdates[$pkg_name] = [];
            foreach ($pkg_versions as $version_name => $pkg_data) {
                // Check if package is newer than current package
                $newVer = $pkg_data["module_data"]["version"];
                $cmp = 8 * ($newVer[0] <=> $curVer[0]);
                $cmp += 4 * ($newVer[1] <=> $curVer[1]);
                $cmp += 2 * ($newVer[2] <=> $curVer[2]);
                $cmp += 1 * ($newVer[3] <=> $curVer[3]);
                if ($cmp <= 0) {
                    continue;
                }

                $availableUpdates[$pkg_name][$version_name] = $pkg_data;
            }
        }

        foreach ($availableUpdates as $pkg_name => $pkg_versions) {
            if (count($pkg_versions) == 0) {
                unset($availableUpdates[$pkg_name]);
                continue;
            }

            uasort($availableUpdates[$pkg_name], function ($a, $b) {
                $cmp = 8 * ($a["module_data"][0] <=> $b["module_data"][0]);
                $cmp += 4 * ($a["module_data"][1] <=> $b["module_data"][1]);
                $cmp += 2 * ($a["module_data"][2] <=> $b["module_data"][2]);
                $cmp += 1 * ($a["module_data"][3] <=> $b["module_data"][3]);
                return $cmp <=> 0;
            });
        }

        foreach ($newPackages as $pkg_name => $pkg_versions) {
            uasort($newPackages[$pkg_name], function ($a, $b) {
                $cmp = 8 * ($a["module_data"][0] <=> $b["module_data"][0]);
                $cmp += 4 * ($a["module_data"][1] <=> $b["module_data"][1]);
                $cmp += 2 * ($a["module_data"][2] <=> $b["module_data"][2]);
                $cmp += 1 * ($a["module_data"][3] <=> $b["module_data"][3]);
                return $cmp <=> 0;
            }, );
        }

        $data["new"] = $newPackages;
        $data["updates"] = $availableUpdates;

        file_put_contents(dirname(__FILE__) . "/packageInfoCache.json", json_encode($data));

        return new Response(json_encode($data));
    }
}