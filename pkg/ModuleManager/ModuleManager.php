<?php

namespace Package;

use \Package\CCMS\Request;
use \Package\CCMS\Response;
use \Package\CCMS\Utilities;
use \Package\ModuleMenu;
use \Package\SecureMenu;

class ModuleManager {
    public static function hookMenu(Request $request)
    {
        if (!User::$currentUser->permissions->owner) {
            return;
        }

        $availablePackageCache = self::getPackageInfoCache();
        $packageCacheUpdated = strtotime($availablePackageCache["checked"]);
        $packageCacheData = $availablePackageCache["packages"];
        
        $sortedModuleManifest = Utilities::getPackageManifest();
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
            $uA_text = ($hasUpdate ? "&nbsp;<button class=\"btn btn-link\" title=\"Update Available\" style=\"padding: 0;\" onclick=\"$('#pkgmgr_tab_updates').tab('show');\"><i class=\"fas fa-chevron-circle-up\"></i></button>" : "");

            $uninstall_button = "<button class=\"btn btn-outline-danger\" title=\"Uninstall Module\" onclick=\"pkgmgr_do_uninstall('{$module_name}');\"><i class=\"fas fa-trash\"></i></button>";
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

        $newList = "";
        foreach ($availablePackageCache["new"] as $pkg_name => $pkg_versions) {
            $ref_pkg_version = end($pkg_versions);

            $dependencyList = "";
            foreach (array_merge($ref_pkg_version["dependencies"]["libraries"], $ref_pkg_version["dependencies"]["modules"]) as $dependency) {
                $dependencyList .= "{$dependency["name"]}&nbsp;v" . implode(".", $dependency["min_version"]) . "<br />";
            }

            if ($dependencyList === "") {
                $dependencyList = "None";
            }

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
                'authors' => "<b>Name:</b>&nbsp;{$ref_pkg_version["module_data"]["author"]["name"]}<br /><b>Email:</b>&nbsp;{$ref_pkg_version["module_data"]["author"]["email"]}<br /><b>Website:</b>&nbsp;{$ref_pkg_version["module_data"]["author"]["website"]}<br />",
                'dependencies' => $dependencyList,
                'install_button' => $install_button,
            ];
            $newList .= Utilities::fillTemplate($moduleModalNewEntryTemplate, $template_vars);
        }
        if ($newList == "") {
            $newList = $moduleModalNoNewTemplate;
        }

        if ($availablePackageCache["checked"] != "never") {
            $availablePackageCache["checked"] = date('F j, Y \a\t g:i:sa', strtotime($availablePackageCache["checked"])-7*3600);
        }

        $template_vars = [
            'lastchecked' => $availablePackageCache["checked"],
            'updatelist' => $updateList,
            'modulelist' => $moduleList,
            'newlist' => $newList,
        ];
        $moduleManagerBody = Utilities::fillTemplate(file_get_contents(dirname(__FILE__) . "/templates/ModuleModalBody.template.html"), $template_vars);
        SecureMenu::Instance()->addModal("dialog_modules", "Manage Modules", $moduleManagerBody, "");
        ModuleMenu::Instance()->addEntry("showDialog('modules');", "Manage Modules");
    }

    public static function hookUninstall(Request $request)
    {
        // SPECIAL FUNCTION THAT FLUSHES EARLY, RUNS IN BACKGROUND, THEN ENDS EXECUTION

        if (!User::$currentUser->permissions->owner) {
            return new Response("Failed: not permitted.");
        }

        if (!isset($_POST["pkg_id"])) {
            // fail
            return new Response("Failed: missing arguments.");
        }

        $pkg_id = $_POST["pkg_id"];

        $availablePackageCache = self::getPackageInfoCache();

        if (!isset($availablePackageCache["installed"][$pkg_id])) {
            return new Response("Failed: package {$pkg_id} is not installed.");
        }

        if ($availablePackageCache["installed"][$pkg_id]["dependencies"]["has_dependent"]) {
            return new Response("Failed: package {$pkg_id} has dependant packages.");
        }

        $state = [
            'global_state' => "start",
        ];
        $state_path = dirname(__FILE__)."/install_state.json";
        if (is_file($state_path)) {
            return new Response("Failed: package (un)installation already in progress.");
        }
        file_put_contents($state_path, json_encode($state));

        (new Response("Uninstalling package '{$pkg_id}'..."))->send();
        
        // Prevent timeout
        set_time_limit(0);

        $state['global_state'] = "uninstall";
        file_put_contents($state_path, json_encode($state));

        // Enter maintenance mode
        Utilities::setMaintenanceMode(true);

        // Uninstall packages (delay placeholder)
        if (is_dir($_SERVER["DOCUMENT_ROOT"] . "\/pkg\/" . $pkg_id)) {
            if (file_exists($_SERVER["DOCUMENT_ROOT"] . "\/pkg\/" . $pkg_id . "/uninstall.php")) {
                include($_SERVER["DOCUMENT_ROOT"] . "\/pkg\/" . $pkg_id . "/uninstall.php");
            } else {
                self::rrmdir($_SERVER["DOCUMENT_ROOT"] . "\/pkg\/" . $pkg_id);
            }
        }

        // Exit maintenance mode
        Utilities::setMaintenanceMode(false);

        self::hookCheckModules();

        $state['global_state'] = "finish";
        file_put_contents($state_path, json_encode($state));

        // Wait a second so that client sees the 'finish' state
        sleep(1);

        unlink($state_path);

        global $core;
        $core->dispose();
        die();
    }

    public static function hookInstall(Request $request)
    {
        // SPECIAL FUNCTION THAT FLUSHES EARLY, RUNS IN BACKGROUND, THEN ENDS EXECUTION

        if (!User::$currentUser->permissions->owner) {
            return new Response("Failed: not permitted.");
        }

        if (!isset($_POST["pkg_id"]) || !isset($_POST["ver_id"])) {
            // fail
            return new Response("Failed: missing arguments.");
        }

        $pkg_id = $_POST["pkg_id"];
        $ver_id = $_POST["ver_id"];

        $availablePackageCache = self::getPackageInfoCache();

        if (!isset($availablePackageCache["packages"][$pkg_id])) {
            // fail
            return new Response("Failed: package {$pkg_id} is not available.");
        }

        if (!isset($availablePackageCache["packages"][$pkg_id][$ver_id])) {
            // fail
            return new Response("Failed: version {$ver_id} of package {$pkg_id} is not available.");
        }

        $state = [
            'global_state' => "start",
            'package_states' => [],
        ];
        $state_path = dirname(__FILE__)."/install_state.json";
        if (is_file($state_path)) {
            return new Response("Failed: package (un)installation already in progress.");
        }
        file_put_contents($state_path, json_encode($state));

        (new Response("Installing version '{$ver_id}' of package '{$pkg_id}'..."))->send();
        
        // Prevent timeout
        set_time_limit(0);

        // Also add dependencies to list
        $state['global_state'] = "check-dependencies";
        file_put_contents($state_path, json_encode($state));
        $packageInstallList = [$_POST["pkg_id"] => ["ver_id" => $_POST["ver_id"], "version" => $availablePackageCache["packages"][$pkg_id][$ver_id]["module_data"]["version"]]];
        $newPackageAdded = true;
        while ($newPackageAdded) {
            $newPackageAdded = false;
            foreach ($packageInstallList as $new_pkg_id => $listEntry) {
                if (isset($availablePackageCache["installed"][$new_pkg_id]) &&
                    isset($availablePackageCache["installed"][$new_pkg_id]) &&
                    self::compareVersion($availablePackageCache["installed"][$new_pkg_id]["module_data"]["version"], $listEntry["version"]) >= 0) {
                    // package already installed so skip.
                    continue;
                }

                if (!isset($availablePackageCache["packages"][$new_pkg_id])) {
                    // fail
                    return new Response("Failed: package {$new_pkg_id} is not available.");
                }
        
                if (!isset($availablePackageCache["packages"][$new_pkg_id][$listEntry["ver_id"]])) {
                    // fail
                    return new Response("Failed: version {$listEntry["ver_id"]} of package {$new_pkg_id} is not available.");
                }

                $new_pkg_data = $availablePackageCache["packages"][$new_pkg_id][$listEntry["ver_id"]];

                foreach (array_merge($new_pkg_data["dependencies"]["libraries"], $new_pkg_data["dependencies"]["modules"]) as $dependency) {
                    $dependency_pkg_id = $dependency["name"];
                    $dependency_min_ver = $dependency["min_version"];
                    if (isset($packageInstallList[$dependency_pkg_id])) {
                        $cur_min_ver = $packageInstallList[$dependency_pkg_id]["version"];
                        $cmp = 8 * ($dependency_min_ver[0] <=> $cur_min_ver[0]);
                        $cmp += 4 * ($dependency_min_ver[1] <=> $cur_min_ver[1]);
                        $cmp += 2 * ($dependency_min_ver[2] <=> $cur_min_ver[2]);
                        $cmp += 1 * ($dependency_min_ver[3] <=> $cur_min_ver[3]);
                        if ($cmp > 0) {
                            // TODO: Better way to get version id?
                            $packageInstallList[$dependency_pkg_id]["ver_id"] = implode(".", $dependency["min_version"]);
                            $packageInstallList[$dependency_pkg_id]["version"] = $dependency["min_version"];
                            echo "a";
                            $newPackageAdded = true;
                        }
                        continue;
                    }
                    $packageInstallList[$dependency_pkg_id] = ["ver_id" => implode(".", $dependency["min_version"]), "version" => $dependency["min_version"]];
                    $newPackageAdded = true;
                }
            }
        }

        // Remove dependencies that are already installed from the list
        foreach ($packageInstallList as $new_pkg_id => $listEntry) {
            if (isset($availablePackageCache["installed"][$new_pkg_id]) &&
                isset($availablePackageCache["installed"][$new_pkg_id]) &&
                self::compareVersion($availablePackageCache["installed"][$new_pkg_id]["module_data"]["version"], $listEntry["version"]) >= 0) {
                // package already installed so remove from list.
                unset($packageInstallList[$new_pkg_id]);
                continue;
            }
            // add entry to state
            $state['package_states'][$new_pkg_id] = [
                'state' => 'download-pending',
                'ver_id' => $listEntry['ver_id'],
                'pkg_size' => $availablePackageCache["packages"][$new_pkg_id][$listEntry["ver_id"]]['pkg_size'], // Package size in Bytes
                'done_size' => 0, // Downloaded size in Bytes
                'down_speed' => 0, // Download speed in Bytes/second
            ];
        }

        // Wait a second so that client sees this state
        sleep(1);

        // Download dependency packages
        if (!file_exists(dirname(__FILE__)."/pkg_staging")) {
            mkdir(dirname(__FILE__)."/pkg_staging");
        }

        $state['global_state'] = "download";
        file_put_contents($state_path, json_encode($state));

        $SRV_URL = "http://ccms.thomasboland.me";

        foreach ($packageInstallList as $new_pkg_id => $listEntry) {
            $pkg_url = $SRV_URL . "/mod_pkg/{$new_pkg_id}/{$listEntry["ver_id"]}/package.ccmspkg";
            $dest_dir = dirname(__FILE__)."/pkg_staging/{$new_pkg_id}_{$listEntry["ver_id"]}";
            $dest_path = $dest_dir . ".ccmspkg";

            $pkg_data = $availablePackageCache["packages"][$new_pkg_id][$listEntry["ver_id"]];
            self::downloadFile($pkg_url, $dest_path, $pkg_data["pkg_size"], $new_pkg_id, $state_path, $state);
            $state['package_states'][$new_pkg_id]['state'] = "unpack-pending";
            file_put_contents($state_path, json_encode($state));
        }

        $state['global_state'] = "unpack";
        file_put_contents($state_path, json_encode($state));

        // Unpack dependency packages
        foreach ($packageInstallList as $new_pkg_id => $listEntry) {
            $pkg_url = $SRV_URL . "/mod_pkg/{$new_pkg_id}/{$listEntry["ver_id"]}/package.ccmspkg";
            $dest_dir = dirname(__FILE__)."/pkg_staging/{$new_pkg_id}_{$listEntry["ver_id"]}";
            $dest_path = $dest_dir . ".ccmspkg";

            $state['package_states'][$new_pkg_id]['state'] = "unpack";
            file_put_contents($state_path, json_encode($state));

            $zip = new \ZipArchive;
            $zip->open($dest_path);
            $zip->extractTo($dest_dir . "/");
            $zip->close();

            unlink($dest_path);

            $state['package_states'][$new_pkg_id]['state'] = "install-pending";
            file_put_contents($state_path, json_encode($state));
        }

        $state['global_state'] = "install";
        file_put_contents($state_path, json_encode($state));

        // Enter maintenance mode
        Utilities::setMaintenanceMode(true);

        // Install packages (delay placeholder)
        foreach ($packageInstallList as $new_pkg_id => $listEntry) {
            $pkg_url = $SRV_URL . "/mod_pkg/{$new_pkg_id}/{$listEntry["ver_id"]}/package.ccmspkg";
            $dest_dir = dirname(__FILE__)."/pkg_staging/{$new_pkg_id}_{$listEntry["ver_id"]}";
            $dest_path = $dest_dir . ".ccmspkg";

            $state['package_states'][$new_pkg_id]['state'] = "install";
            file_put_contents($state_path, json_encode($state));

            if (file_exists($dest_dir . "/install.php")) {
                // Use install script
                include($dest_dir . "/install.php");
            } else {
                // Just remove any matching directories then copy $dest_dir/*.* to DOCUMENT_ROOT/
                if (is_dir($_SERVER["DOCUMENT_ROOT"] . "\/pkg\/" . $new_pkg_id)) {
                    self::rrmdir($_SERVER["DOCUMENT_ROOT"] . "\/pkg\/" . $new_pkg_id);
                }
                self::rcopy($dest_dir, $_SERVER["DOCUMENT_ROOT"]);
            }
            
            $state['package_states'][$new_pkg_id]['state'] = "finish";
        }

        // Exit maintenance mode
        Utilities::setMaintenanceMode(false);

        $state['global_state'] = "clean";
        file_put_contents($state_path, json_encode($state));

        self::rrmdir(dirname(__FILE__)."/pkg_staging");
        self::hookCheckModules();

        $state['global_state'] = "finish";
        file_put_contents($state_path, json_encode($state));

        // Wait a second so that client sees the 'finish' state
        sleep(1);

        unlink($state_path);

        global $core;
        $core->dispose();
        die();
    }

    public static function rcopy($src,$dst)
    {
        $dir = opendir($src);
        if (!is_dir($dst)) {
            mkdir($dst);
        }
        while(false !== ($file = readdir($dir))) {
            if (($file != '.') && ($file != '..') && ($file != 'install.php')) {
                if (is_dir($src . '/' . $file)) {
                    self::rcopy($src . '/' . $file, $dst . '/' . $file);
                }
                else {
                    copy($src . '/' . $file, $dst . '/' . $file);
                }
            }
        }
        closedir($dir);
    }

    function rrmdir($dir)
    { 
        if (is_dir($dir)) { 
            $objects = scandir($dir); 
            foreach ($objects as $object) { 
                if ($object != "." && $object != "..") { 
                    if (is_dir($dir."/".$object)) {
                        self::rrmdir($dir."/".$object);
                    } else {
                        unlink($dir."/".$object); 
                    }
                } 
            }
            rmdir($dir); 
        } 
    }

    public static function compareVersion($versionA, $versionB) {
        $cmp = 8 * ($versionA[0] <=> $versionB[0]);
        $cmp += 4 * ($versionA[1] <=> $versionB[1]);
        $cmp += 2 * ($versionA[2] <=> $versionB[2]);
        $cmp += 1 * ($versionA[3] <=> $versionB[3]);
        return ($cmp <=> 0);
    }

    public static function downloadFile($url, $path, $size, $pkg_id, $state_path, &$state) {
        $state['package_states'][$pkg_id]['state'] = "download";
        file_put_contents($state_path, json_encode($state));
        $newfname = $path;
        $file = fopen($url, 'rb');
        $done = 0;
        $per = 0;
        $start_time = microtime(true);
        if ($file) {
            $newf = fopen ($newfname, 'wb');
            if ($newf) {
                while(!feof($file)) {
                    fwrite($newf, fread($file, 1024 * 8), 1024 * 8);
                    $done += 1024*8;
                    if ($done > $size) {$done = $size;}
                    $speed = $done / (microtime(true) - $start_time);
                    $state['package_states'][$pkg_id]['done_size'] = $done;
                    $state['package_states'][$pkg_id]['down_speed'] = $speed;
                    file_put_contents($state_path, json_encode($state));
                }
                fclose($newf);
            } else {
                $state['package_states'][$pkg_id]['state'] = "fail";
                file_put_contents($state_path, json_encode($state));
            }
            fclose($file);
        } else {
            $state['package_states'][$pkg_id]['state'] = "fail";
            file_put_contents($state_path, json_encode($state));
        }
    }

    public static function getPackageInfoCache() {
        if (!file_exists(dirname(__FILE__) . "/packageInfoCache.json")) {
            return ["checked" => "never", "packages" => []];
        }

        return json_decode(file_get_contents(dirname(__FILE__) . "/packageInfoCache.json"), true);
    }

    public static function hookCheckModules(Request $request=null) {
        if (!User::$currentUser->permissions->owner) {
            return new Response("Failed: not permitted.");
        }

        $SRV_URL = "http://ccms.thomasboland.me";
        $VER_URL = $SRV_URL . "/latest.php?mod";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $VER_URL);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
        $out = curl_exec($ch);
        curl_close($ch);

        $data = [
            "checked" => date(DATE_ATOM),
            "packages" => json_decode($out, true),
        ];

        // Check for new packages and available updates
        $newPackages = [];
        $availableUpdates = [];
        $moduleManifest = Utilities::getPackageManifest();
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
                $cmp = 8 * ($a["module_data"]["version"][0] <=> $b["module_data"]["version"][0]);
                $cmp += 4 * ($a["module_data"]["version"][1] <=> $b["module_data"]["version"][1]);
                $cmp += 2 * ($a["module_data"]["version"][2] <=> $b["module_data"]["version"][2]);
                $cmp += 1 * ($a["module_data"]["version"][3] <=> $b["module_data"]["version"][3]);
                return -($cmp <=> 0);
            });
        }

        foreach ($newPackages as $pkg_name => $pkg_versions) {
            uasort($newPackages[$pkg_name], function ($a, $b) {
                $cmp = 8 * ($a["module_data"]["version"][0] <=> $b["module_data"]["version"][0]);
                $cmp += 4 * ($a["module_data"]["version"][1] <=> $b["module_data"]["version"][1]);
                $cmp += 2 * ($a["module_data"]["version"][2] <=> $b["module_data"]["version"][2]);
                $cmp += 1 * ($a["module_data"]["version"][3] <=> $b["module_data"]["version"][3]);
                return -($cmp <=> 0);
            });
        }

        $data["new"] = $newPackages;
        $data["updates"] = $availableUpdates;
        ksort($moduleManifest, SORT_NATURAL);
        $data["installed"] = $moduleManifest;

        file_put_contents(dirname(__FILE__) . "/packageInfoCache.json", json_encode($data));

        return new Response(json_encode($data));
    }
}