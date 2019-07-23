<?php

// Basically a super thinned down version of ModuleManager with all dependencies built in,
// and only capable of selecting CCMSIndex packages for installation.
// CCMSIndex packages are just dependency lists for all packages to install in a release.

class Response
{
    protected $content = "";
    protected $final = true;
    
    public function __construct($content='', $final=true)
    {
        $this->content = $content;
        $this->final = $final;
    }
    
    public function send($buffer=true)
    {
        if (!$buffer) {
            echo $this->content;
            return;
        }
	
        ob_end_clean();
        ignore_user_abort(true);
        ob_start();
        
        echo $this->content;
        
        $size = ob_get_length();
        header("Content-Length: {$size}");
        ob_end_flush();
        flush();
    }
}

class ModuleManager {
    public static function hookInstall()
    {
        if (!isset($_POST["ver_id"])) {
            // fail
            return new Response("Failed: missing arguments.");
        }

        $pkg_id = "CCMSIndex";
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
        $packageInstallList = [$pkg_id => ["ver_id" => $ver_id, "version" => $availablePackageCache["packages"][$pkg_id][$ver_id]["module_data"]["version"]]];
        $newPackageAdded = true;
        while ($newPackageAdded) {
            $newPackageAdded = false;
            foreach ($packageInstallList as $new_pkg_id => $listEntry) {
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
            // add entry to state
            $state['package_states'][$new_pkg_id] = [
                'state' => 'download-pending',
                'ver_id' => $listEntry['ver_id'],
                'pkg_size' => $availablePackageCache["packages"][$new_pkg_id][$listEntry["ver_id"]]['pkg_size'], // Package size in Bytes
                'done_size' => 0, // Downloaded size in Bytes
                'down_speed' => 0, // Download speed in Bytes/second
			];
			file_put_contents($state_path, json_encode($state));
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
                if (is_dir(dirname(__FILE__) . "\/pkg\/" . $new_pkg_id)) {
                    self::rrmdir(dirname(__FILE__) . "\/pkg\/" . $new_pkg_id);
                }
                self::rcopy($dest_dir, dirname(__FILE__));
            }
            
            $state['package_states'][$new_pkg_id]['state'] = "finish";
        }

		// Enter production mode
		unlink(dirname(__FILE__) . "/.htaccess");
        copy(dirname(__FILE__) . "/.htaccess.production", dirname(__FILE__) . "/.htaccess");

		// Cleanup
        $state['global_state'] = "clean";
        file_put_contents($state_path, json_encode($state));

        self::rrmdir(dirname(__FILE__)."/pkg_staging");
        self::hookCheckModules();

        $state['global_state'] = "finish";
        file_put_contents($state_path, json_encode($state));

        // Wait a second so that client sees the 'finish' state
        sleep(1);

        unlink($state_path);
        unlink(dirname(__FILE__) . "/packageInfoCache.json");

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
            return ["checked" => "never", "packages" => [], "new" => []];
        }

        return json_decode(file_get_contents(dirname(__FILE__) . "/packageInfoCache.json"), true);
    }

    public static function hookCheckModules() {
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
        foreach ($data["packages"] as $pkg_name => $pkg_versions) {
            $newPackages[$pkg_name] = $pkg_versions;
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

        file_put_contents(dirname(__FILE__) . "/packageInfoCache.json", json_encode($data));

        return new Response(json_encode($data));
    }
}

if (isset($_GET["updateRepo"])) {
	ModuleManager::hookCheckModules()->send();
	die();
}
if (isset($_GET["install"])) {
    ModuleManager::hookInstall()->send();
    die();
}
if (isset($_GET["delete"])) {
    unlink(__FILE__);
    (new Response("done"))->send();
    die();
}

?>
<!doctype HTML>
<html>
    <head>
        <title>Install CCMS</title>
        <style>
*, ::after, ::before {
    box-sizing: border-box;
}

html {
    font-family: sans-serif;
    line-height: 1.15;
    -webkit-text-size-adjust: 100%;
    -webkit-tap-highlight-color: transparent;
}

:root {
    --blue: #007bff;
    --indigo: #6610f2;
    --purple: #6f42c1;
    --pink: #e83e8c;
    --red: #dc3545;
    --orange: #fd7e14;
    --yellow: #ffc107;
    --green: #28a745;
    --teal: #20c997;
    --cyan: #17a2b8;
    --white: #fff;
    --gray: #6c757d;
    --gray-dark: #343a40;
    --primary: #007bff;
    --secondary: #6c757d;
    --success: #28a745;
    --info: #17a2b8;
    --warning: #ffc107;
    --danger: #dc3545;
    --light: #f8f9fa;
    --dark: #343a40;
    --breakpoint-xs: 0;
    --breakpoint-sm: 576px;
    --breakpoint-md: 768px;
    --breakpoint-lg: 992px;
    --breakpoint-xl: 1200px;
    --font-family-sans-serif: -apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,"Helvetica Neue",Arial,"Noto Sans",sans-serif,"Apple Color Emoji","Segoe UI Emoji","Segoe UI Symbol","Noto Color Emoji";
    --font-family-monospace: SFMono-Regular,Menlo,Monaco,Consolas,"Liberation Mono","Courier New",monospace;
}

body {
    margin: 2rem;
    font-family: -apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,"Helvetica Neue",Arial,"Noto Sans",sans-serif,"Apple Color Emoji","Segoe UI Emoji","Segoe UI Symbol","Noto Color Emoji";
    font-size: 1rem;
    font-weight: 400;
    line-height: 1.5;
    color: #212529;
    text-align: left;
    background-color: #fff;
}

.table-responsive {
    display: block;
    width: 100%;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
}

table {
    display: table;
    border-collapse: collapse;
    border-spacing: 2px;
    border-color: grey;
}

.table {
    width: 100%;
    margin-bottom: 1rem;
    color: #212529;
}

thead {
    display: table-header-group;
    vertical-align: middle;
    border-color: inherit;
}

tr {
    display: table-row;
    vertical-align: inherit;
    border-color: inherit;
}

th {
    display: table-cell;
    vertical-align: inherit;
    font-weight: bold;
    text-align: -internal-center;
}

td {
    display: table-cell;
    vertical-align: inherit;
}

.table td, .table th {
    padding: .75rem;
    vertical-align: top;
    border-top: 1px solid #dee2e6;
}

.table thead th {
    vertical-align: bottom;
    border-bottom: 2px solid #dee2e6;
}

tbody {
    display: table-row-group;
    vertical-align: middle;
    border-color: inherit;
}

button {
    border-radius: 0;
}

button, select {
    margin: 0;
    font-family: inherit;
    font-size: inherit;
    line-height: inherit;
}

button {
    overflow: visible;
}

button, select {
    text-transform: none;
}

[type=button], [type=reset], [type=submit], button {
    -webkit-appearance: button;
}

.btn {
    display: inline-block;
    font-weight: 400;
    color: #212529;
    text-align: center;
    vertical-align: middle;
    -webkit-user-select: none;
    -moz-user-select: none;
    -ms-user-select: none;
    user-select: none;
    background-color: transparent;
    border: 1px solid transparent;
    padding: .375rem .75rem;
    font-size: 1rem;
    line-height: 1.5;
    border-radius: .25rem;
    transition: color .15s ease-in-out,background-color .15s ease-in-out,border-color .15s ease-in-out,box-shadow .15s ease-in-out;
}

.btn-primary {
    color: #fff;
    background-color: #007bff;
    border-color: #007bff;
}

.btn-group-sm>.btn, .btn-sm {
    padding: .25rem .5rem;
    font-size: .875rem;
    line-height: 1.5;
    border-radius: .2rem;
}

button:not(:disabled) {
    cursor: pointer;
}

@keyframes spinner-border {
  to { transform: rotate(360deg); }
}

.spinner-border {
    display: inline-block;
    width: 2rem;
    height: 2rem;
    vertical-align: text-bottom;
    border: .25em solid currentColor;
    border-right-color: transparent;
    border-radius: 50%;
    -webkit-animation: spinner-border .75s linear infinite;
    animation: spinner-border .75s linear infinite;
}

.spinner-border-sm {
    width: 1rem;
    height: 1rem;
    border-width: .2em;
}

.input-group {
    position: relative;
    display: -ms-flexbox;
    display: flex;
    -ms-flex-wrap: wrap;
    flex-wrap: wrap;
    -ms-flex-align: stretch;
    align-items: stretch;
    width: 100%;
}

select {
    word-wrap: normal;
}

option {
    font-weight: normal;
    display: block;
    white-space: pre;
    min-height: 1.2em;
    padding: 0px 2px 1px;
}

.input-group-append {
    display: -ms-flexbox;
    display: flex;
}

.input-group-append {
    margin-left: -1px;
}

.btn-success {
    color: #fff;
    background-color: #28a745;
    border-color: #28a745;
}

.input-group-append .btn {
    position: relative;
    z-index: 2;
}

.input-group-sm>.input-group-append>.btn {
    padding: .25rem .5rem;
    font-size: .875rem;
    line-height: 1.5;
    border-radius: .2rem;
}

.input-group>.input-group-append>.btn {
    border-top-left-radius: 0;
    border-bottom-left-radius: 0;
}

.progress {
    display: -ms-flexbox;
    display: flex;
    height: 1rem;
    overflow: hidden;
    font-size: .75rem;
    background-color: #e9ecef;
    border-radius: .25rem;
}

.progress-bar {
    display: -ms-flexbox;
    display: flex;
    -ms-flex-direction: column;
    flex-direction: column;
    -ms-flex-pack: center;
    justify-content: center;
    color: #fff;
    text-align: center;
    white-space: nowrap;
    background-color: #007bff;
    transition: width .6s ease;
}

.progress-bar-striped {
    background-image: linear-gradient(45deg,rgba(255,255,255,.15) 25%,transparent 25%,transparent 50%,rgba(255,255,255,.15) 50%,rgba(255,255,255,.15) 75%,transparent 75%,transparent);
    background-size: 1rem 1rem;
}
        </style>
    </head>
    <body>
        <p>Last checked repository <span id="pkgmgr_repo_last_checked">never</span>&nbsp;
            <button id="pkgmgr_repo_check_button" class="btn btn-sm btn-primary" style="border-radius: 0;padding-top:2px;padding-bottom:2px;" onclick="pkgmgr_check();">Check now</button>
        </p>
        <div class="table-responsive">
            <table id="modulemanager_available_table" class="table table-hover">
                <thead>
                    <tr><th>Package Name</th><th>Description</th><th>Authors</th><th>Dependencies</th><th style="min-width:200px;"></th></tr>
                </thead>
                <tbody id="pkgmgr_available_tablebody"><tr colspan="5"><td>No packages found</td></tbody>
            </table>
        </div>
        <span id="pkgmgr_progress_state"></span>
        <div class="table-responsive">
            <table id="modulemanager_progress_table" class="table table-hover">
                <thead>
                    <tr><th>Package</th><th style="width:300px;max-width:300px">Status</th><th style="width:200px;max-width:200px;min-width:100px;"></th></tr>
                </thead>
                <tbody id="pkgmgr_progress_tablebody"><tr colspan="3"><td>Nothing installing right now</td></tr></tbody>
            </table>
        </div>
        <script>
var $={};$.x=function(){return new XMLHttpRequest();};$.s=function(u,c,m,d,a){a=a===undefined?true:a;var x=$.x();x.open(m,u,a);x.onreadystatechange=function(){if(x.readyState==4){c(x.responseText);}};if(m=="POST"){x.setRequestHeader("Content-type","application/x-www-form-urlencoded");}x.send(d);};$.get=function(q,c,a){$.s(q,c,"GET",null,a);};$.post=function(q,d,c,a){var f=[];for(var k in d){f.push(encodeURIComponent(k)+"="+encodeURIComponent(d[k]));}$.s(q,c,"POST",f.join("&"),a);}

var pkgmgr_state_interval_id = null;
var pkgmgr_installing = false;

function pkgmgr_do_update() {
    if (pkgmgr_installing) {
        alert("There's already a package being installed.");
        return;
    }
    pkgmgr_installing = true;
	pkg_ver_id = document.getElementById("update-CCMSIndex-version").value;
	$.post("?install", {ver_id:pkg_ver_id}, function(d){
        if (d.startsWith("Failed: ")) {
            pkgmgr_installing = false;
            alert("Server says:\n"+d);
            return;
        }
        pkgmgr_state_interval_id = setInterval(function(){
            $.get("/install_state.json", function(data) {
				if (data.length == 0 || data.substr(0, 1) != "{") {
					return;
				}
				state = JSON.parse(data);
                var pretty_state = "";
                switch (state['global_state']) {
                    case "start":
                        pretty_state = "Starting";
                        break;
                    case "check-dependencies":
                        pretty_state = "Checking Dependencies";
                        break;
                    case "download":
                        pretty_state = "Downloading Packages";
                        break;
                    case "unpack":
                        pretty_state = "Unpacking Packages";
                        break;
                    case "install":
                        pretty_state = "Installing Packages";
                        break;
                    case "clean":
                        pretty_state = "Cleaning Up";
                        break;
                    case "finish":
                        pretty_state = "Finished! Redirecting in 5 seconds...";
                        break;
                    default:
                        pretty_state = "Something went wrong";
                }

                var table = "";

                for (pkg_id in state['package_states']) {
                    pkg_state = state['package_states'][pkg_id];
                    var pretty_pkg_state = "";

                    var pretty_done_size = pkgmgr_pretty_filesize(pkg_state["done_size"]);
                    var pretty_pkg_size = pkgmgr_pretty_filesize(pkg_state["pkg_size"]);
                    var pretty_down_speed = pkgmgr_pretty_filesize(pkg_state["down_speed"]);

                    switch (pkg_state['state']) {
                        case "download-pending":
                            pretty_pkg_state = "Download Pending";
                            break;
                        case "download":
                            pretty_pkg_state = "Downloading: " + Math.round(100*pkg_state["done_size"]/pkg_state["pkg_size"], 1) + "%<br />";
                            pretty_pkg_state += "" + pretty_done_size + "/" + pretty_pkg_size + " " + pretty_down_speed + "/s";
                            break;
                        case "unpack-pending":
                            pretty_pkg_state = "Unpack Pending";
                            break;
                        case "unpack":
                            pretty_pkg_state = "Unpacking";
                            break;
                        case "install-pending":
                            pretty_pkg_state = "Install Pending";
                            break;
                        case "install":
                            pretty_pkg_state = "Installing";
                            break;
                        case "finish":
                            pretty_pkg_state = "Done";
                            break;
                        default:
                            pretty_pkg_state = "Something went wrong";
                    }
                    table += "<tr><td>" + pkg_id + " " + pkg_state['ver_id'] + "</td>";
                    table += "<td>" + pretty_pkg_state + "</td>";
                    table += "<td><div class=\"progress\"><div class=\"progress-bar" + (pkg_state['state'] == "download" ? "" : " progress-bar-striped") + "\" role=\"progressbar\" style=\"width: " + (100*pkg_state["done_size"]/pkg_state["pkg_size"]) + "%\" aria-valuenow=\"" + (100*pkg_state["done_size"]/pkg_state["pkg_size"]) + "\" aria-valuemin=\"0\" aria-valuemax=\"100\"></div></div></td></tr>";
                }

                document.getElementById("pkgmgr_progress_tablebody").innerHTML = table;
                document.getElementById("pkgmgr_progress_state").textContent = pretty_state;

                if (state['global_state'] == "finish") {
                    clearInterval(pkgmgr_state_interval_id);
                    pkgmgr_check();
                    pkgmgr_installing = false;
                    setTimeout(pkgmgr_install_done, 5000);
                }
            });
        }, 100);
    });
}

function pkgmgr_install_done() {
    $.get("?delete", function() {
        window.location.href = "/";
    });
}

function pkgmgr_pretty_filesize(bytes, decimals=1) {
    if (bytes === 0) return '0 Bytes';

    const k = 1024;
    const dm = decimals < 0 ? 0 : decimals;
    const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];

    const i = Math.floor(Math.log(bytes) / Math.log(k));

    return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + sizes[i];
}

function pkgmgr_check() {
    document.getElementById("pkgmgr_repo_check_button").innerHTML = "<span class=\"spinner-border spinner-border-sm\" role=\"status\" aria-hidden=\"true\"></span>&nbsp;Checking...";
	document.getElementById("pkgmgr_repo_check_button").disabled = true;
	$.get("?updateRepo",pkgmgr_update_lists);
}

function pkgmgr_update_lists(data) {
    if (data.startsWith("Failed: ")) {
        document.getElementById("pkgmgr_repo_check_button").innerHTML = "Check now";
        document.getElementById("pkgmgr_repo_check_button").disabled = false;
        alert("Server says:\n"+data);
        return;
    }
    try {
        pkg_repo = JSON.parse(data);
        var b = pkg_repo["checked"].split(/\D+/);
        last_checked =  new Date(Date.UTC(b[0], --b[1], b[2], b[3], b[4], b[5], b[6]));
        last_checked_str = last_checked.toLocaleDateString('default', {month: 'long', day: 'numeric', year: 'numeric'}) + " at ";
        h = last_checked.getHours();
        m = last_checked.getMinutes();
        s = last_checked.getSeconds();
        ap = h < 12 ? "am" : "pm";
        h = h % 12;
        h = h==0?"12":""+h;
        m = m<10?"0"+m:""+m;
        s = s<10?"0"+s:""+s;
        last_checked_str += h + ":" + m + ":" + s + ap;

        pkg_versions = pkg_repo["new"]["CCMSIndex"];
		pkg_version_keys = Object.keys(pkg_versions);
		ref_pkg_version = pkg_versions[pkg_version_keys[pkg_version_keys.length-1]];

		dependencyList = "";
		for (dependency of ref_pkg_version["dependencies"]["libraries"].concat(ref_pkg_version["dependencies"]["modules"])) {
			dependencyList += dependency["name"] + "&nbsp;v" + dependency["min_version"].join(".") + "<br />";
		}

		if (dependencyList === "") {
			dependencyList = "None";
		}

		version_options = "";
		for (version_id of pkg_version_keys) {
			version_options += "<option value=\"" + version_id + "\">v" + pkg_versions[version_id]["module_data"]["version"].join(".") + "</option>";
		}

		install_button = "<div class=\"input-group input-group-sm\"><select id=\"update-CCMSIndex-version\">" + version_options + "</select>";
		install_button += "<div class=\"input-group-append\"><button class=\"btn btn-success\" style=\"border-radius: 0;\" onclick=\"pkgmgr_do_update();\">";
		install_button += "<i class=\"fas fa-download\"></i>&nbsp;Install</button></div></div>";

		authors = "<b>Name:</b>&nbsp;" + ref_pkg_version["module_data"]["author"]["name"] + "<br />";
		authors += "<b>Email:</b>&nbsp;" + ref_pkg_version["module_data"]["author"]["email"] + "<br />";
		authors += "<b>Website:</b>&nbsp;" + ref_pkg_version["module_data"]["author"]["website"] + "<br />";

		newList = "<tr><td>" + ref_pkg_version["module_data"]["name"] + "</td>";
		newList += "<td>" + ref_pkg_version["module_data"]["description"] + "</td>";
		newList += "<td>" + authors + "</td><td>" + dependencyList + "</td><td>" + install_button + "</td></tr>";

        document.getElementById("pkgmgr_repo_last_checked").textContent = last_checked_str;
        document.getElementById("pkgmgr_available_tablebody").innerHTML = newList;
    } catch (err) {
        alert(err + "\n\nReceived bad repository data:\n\n" + data.substring(0, 80) + "...");
    }
    document.getElementById("pkgmgr_repo_check_button").innerHTML = "Check now";
    document.getElementById("pkgmgr_repo_check_button").disabled = false;
}
        </script>
    </body>
</html>