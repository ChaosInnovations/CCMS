<?php

// STATE
//  0  Checking versions  :0
//  1  Awaiting download  :[json ver list]
//  2  Downloading        :0-89
//  3  Unpacking          :90-99
//  4  Finishing          :100
//  5  Done/Ready         :0
// -1  Error              :ERR_CODE

// ERROR
//  0  Not run yet
//  1  Bad pkg version
//  2  Bad fopen

// LOGS: install.log

// If ?status then return value of STATE file if it exists, otherwise return -1
// If ?go=[ver] then try download of http://ccms.chaosinnovations.com/pkg/[ver].zip
//   If successful, return 1, close connection, and download in background. Update STATE with progress
//     Update STATE and unpack
//     Update STATE and update install.log
// Otherwise
//   If STATE doesn't exist or STATE is -1
//     Return HTML/JS/AJAX page
//   OTHERWISE
//     Start OOBE


$VERSION = "1.2";
$RELEASE = "June 20, 2019";


//$SRV_URL = "http://ccms.thomasboland.me";
$SRV_URL = "http://localhost/ccms";
function PKG_URL($ver) {global $SRV_URL;return "{$SRV_URL}/pkg/{$ver}/{$ver}.zip";}
$VER_URL = $SRV_URL . "/latest.php";
function CHK_URL($ver) {global $VER_URL;return "{$VER_URL}?valid={$ver}";}
function INF_URL($ver) {global $VER_URL;return "{$VER_URL}?getinfo={$ver}";}

start();

if (isset($_GET["status"])) {
	echo getState();
} else if (isset($_GET["go"])) {
	if (validPkg($_GET["go"])) {
		set_time_limit(0);
		echo "OK";
		$v = $_GET["go"];
		stop();
		doAsyncInstallation($v);
	} else {
		echo "FAIL";
		stop();
		file_put_contents("STATE", "-1:1");
	}
} else if (explode(":", getState())[0] == "-1" and explode(":", getState())[1] == "0") {
	echo installerFrontend();
	file_put_contents("STATE", "0:0");
	stop();
	doAsyncVersionCheck();
} else if(explode(":", getState())[0] == 5) {
	header("Location: /");
	echo "Redirecting you to <a href=\"/.\">index.php</a>";
	stop();
} else {
	echo installerFrontend();
	stop();
}

function start() {
	ob_end_clean();
	header("Connection: close");
	ignore_user_abort(); // optional
	ob_start();
}

function stop() {
	$size = ob_get_length();
	header("Content-Length: $size");
	ob_end_flush(); // Strange behaviour, will not work
	flush();        // Unless both are called !
}

function getState() {
	$state = "-1:0";
	if (file_exists("STATE")) {
		$state = file_get_contents("STATE");
	} else {
		file_put_contents("STATE", $state);
	}
	return $state;
}

function validPkg($verString) {
	return getRemote(CHK_URL($verString))=="TRUE";
}

function doAsyncInstallation($verString) {
	file_put_contents("STATE", "2:0");
	downloadFile(PKG_URL($verString), "pkg.zip", $verString);
	file_put_contents("STATE", "3:90");
	$zip = new ZipArchive;
	$zip->open('pkg.zip');
	$zip->extractTo('./');
	$zip->close();
	file_put_contents("STATE", "4:100");
	unlink("pkg.zip");
	file_put_contents("STATE", "5:0");
}

function downloadFile($url, $path, $ver) {
	$newfname = $path;
    $file = fopen($url, 'rb');
	$d = getRemote(INF_URL($ver));
	$len = json_decode($d)->size;
	$done = 0;
	$per = 0;
    if ($file) {
        $newf = fopen ($newfname, 'wb');
        if ($newf) {
            while(!feof($file)) {
                fwrite($newf, fread($file, 1024 * 8), 1024 * 8);
				$done += 1024*8;
				if($done > $len) {$done = $len;};
				$per = round($done*90/$len, 1);
				file_put_contents("STATE", "2:{$per}");
            }
			fclose($newf);
        } else {
			file_put_contents("STATE", "-1:2");
		}
		fclose($file);
    } else {
		file_put_contents("STATE", "-1:2");
	}
}

function getRemote($query) {
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $query);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
	$out = curl_exec($ch);
	curl_close($ch);
	return $out;
}

function doAsyncVersionCheck() {
	global $VER_URL;
	file_put_contents("STATE", "0:0");
	stop();
	$vers = getRemote($VER_URL);
	file_put_contents("STATE", "1:{$vers}");
}

function installerFrontend() {
	global $VERSION,$RELEASE;
	echo '<!DOCTYPE html>
<html>
	<head>
		<title>Install CCMS</title>
		<style>
.hide {
	display: none;
}
		</style>
	</head>
	<body>
		<h1>Install CCMS</h1>
		<div id="msg">Checking for latest version...</div>
		<div id="detail"></div>
		<p>CCMS Installer version '.$VERSION.' released on '.$RELEASE.'.</p>
		<script>
var $={};$.x=function(){return new XMLHttpRequest();};$.s=function(u,c,m,d,a){a=a===undefined?true:a;var x=$.x();x.open(m,u,a);x.onreadystatechange=function(){if(x.readyState==4){c(x.responseText);}};if(m=="POST"){x.setRequestHeader("Content-type","application/x-www-form-urlencoded");}x.send(d);};$.get=function(q,c,a){$.s(q,c,"GET",null,a);};$.post=function(q,d,c,a){var f=[];for(var k in d){f.push(encodeURIComponent(k)+"="+encodeURIComponent(d[k]));}$.s(q,c,"POST",f.join("&"),a);}

var lastState = -1;
var lastData = null;

function stateUpdate(s) {
	s = s.split(":");
	state = s[0];
	s.shift();
	data = s.join(":");
	if (state != lastState) {
		lastState = state;
		lastData = data;
		stateChanged(state, data);
	} else if (data != lastData) {
		lastData = data;
		dataChanged(data);
	}
}

function stateChanged(state, data) {
	switch (state) {
		case "-1":
			document.getElementById("msg").innerHTML = "Error";
			var msg = "";
			switch (data) {
				case 0:
					break;
				case 1:
					msg = "Bad Package Version!";
					break;
				case 2:
					msg = "Couldn\'t get file!";
					break;
			}
			document.getElementById("detail").innerHTML = msg;
			break;
		case "0":
			document.getElementById("msg").innerHTML = "Checking for latest version...";
			break;
		case "1":
			document.getElementById("msg").innerHTML = "<h3>Ready to download</h3>";
			populateWithLatestVersion(data);
			break;
		case "2":
			document.getElementById("msg").innerHTML = "Downloading...";
			document.getElementById("detail").innerHTML = "<progress value=\'"+data+"\' max=\'100\'></progress>";
			break;
		case "3":
			document.getElementById("msg").innerHTML = "Unpacking...";
			document.getElementById("detail").innerHTML = "<progress value=\'"+data+"\' max=\'100\'></progress>";
			break;
		case "4":
			document.getElementById("msg").innerHTML = "Finishing...";
			document.getElementById("detail").innerHTML = "<progress value=\'"+data+"\' max=\'100\'></progress>";
			break;
		case "5":
			document.getElementById("msg").innerHTML = "Ready!";
			document.getElementById("detail").innerHTML = "If this page doesn\'t automaically refresh, click <a href=\"setup.php\">here.</a>";
			setTimeout(function(){location.reload();}, 1500);
			break;
		default:
			break;
	}
}

function dataChanged(data) {
	switch (lastState) {
		case "2":
			document.getElementById("detail").innerHTML = "<progress value=\'"+data+"\' max=\'100\'></progress>";
			break;
		case "3":
			document.getElementById("detail").innerHTML = "<progress value=\'"+data+"\' max=\'100\'></progress>";
			break;
	}
}

function populateWithLatestVersion(data) {
	var html = "";
	var versionList = JSON.parse(data);
	var latest = versionList["latest"];
	var latestDev = versionList["latest-dev"];
	html += "<h4>Latest Versions:</h4>\n";
	if (latest != null) {
		html += \'<b>Release: </b><button onclick="startDownload(\\\'\' + latest + \'\\\');">\' + latest + \'</button><br />\n\';
	}
	if (latestDev != null) {
		html += \'<b>Development: </b><button onclick="startDownload(\\\'\' + latestDev + \'\\\');">\' + latestDev + \'</button><br />\n\';
	}
	html += \'<button id="verListToggle" onclick="toggleVerList();">Show All Versions</button><br />\n\';
	html += \'<div id="verList" class="hide">\n\';
	html += \'<button id="verListDevToggle" onclick="toggleVerListDev();">Hide Development Versions</button><br />\n\';
	var vers = Object.keys(versionList);
	vers.forEach(function(v, inf){
		inf = versionList[v];
		if (v != "latest" && v != "latest-dev") {
			var type = (inf["dev"]) ? "Development" : "Release";
			html += (inf["dev"]) ? \'<div class="verList-dev">\' : \'<div>\';
			html += \'<b>\' + type + \' (\' + inf["release"] + \'): <button onclick="startDownload(\\\'\' + v + \'\\\');">\' + v + \'</button><br />\n\';
			html += \'</div>\';
		}
	});
	html += \'</div>\';
	document.getElementById("detail").innerHTML = html;
}

function startDownload(ver) {
	$.get("?go="+ver,function(d){});
}

var verListShow=false;
var verListDevShow=true;

function toggleVerList() {
	verListShow = !verListShow;
	document.getElementById("verList").className = (verListShow) ? "" : "hide";
	document.getElementById("verListToggle").innerHTML = (verListShow) ? "Hide All Versions" : "Show All Versions";
}

function toggleVerListDev() {
	verListDevShow = !verListDevShow;
	var vLd = document.getElementsByClassName("verList-dev");
	for (var i=0;i<vLd.length;i++) {
		vLd[i].className = (verListDevShow) ? "verList-dev" : "verList-dev hide";
	}
	document.getElementById("verListDevToggle").innerHTML = (verListDevShow) ? "Hide Development Versions" : "Show Development Versions";
}

setInterval(function(){$.get("?status",stateUpdate);}, 100);
		</script>
	</body>
</html>
';
}

function errorMsg() {
	echo "error";
}


?>