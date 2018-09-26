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
// If ?setup then save setup data
// If ?go=[ver] then try download of http://ccms.chaosinnovations.com/pkg/[ver].zip
//   If successful, return 1, close connection, and download in background. Update STATE with progress
//     Update STATE and unpack
//     Update STATE and update install.log
// Otherwise
//   If STATE doesn't exist or STATE is -1
//     Return HTML/JS/AJAX page
//   OTHERWISE
//     Start OOBE


$VERSION = "1.1";
$RELEASE = "June 1, 2018";


//$SRV_URL = "http://ccms.chaosinnovations.com";
$SRV_URL = "http://localhost/ccms";
function PKG_URL($ver) {global $SRV_URL;return "{$SRV_URL}/pkg/{$ver}/{$ver}.zip";}
$VER_URL = $SRV_URL . "/latest.php";
function CHK_URL($ver) {global $VER_URL;return "{$VER_URL}?valid={$ver}";}
function INF_URL($ver) {global $VER_URL;return "{$VER_URL}?getinfo={$ver}";}

start();

if (isset($_GET["status"])) {
	echo getState();
} else if (isset($_GET["setup"])) {
	echo saveSetup();
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
} else if (explode(":", getState())[0] == "5") {
	echo setupFrontend();
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

function setupFrontend() {
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
		<h1>Setup CCMS</h1>
		<div id="step1">
		<h2>Step <span id="step">1</span> of 4: <span id="stepname">Database</span></h2>
		<div id="stepProgress"><progress value="1" max="4"></progress></div>
		<div id="formErrors"></div>
		<form id="setupForm" onsubmit="return false;">
			<div id="pg1frm">
				<b>Host: </b><input id="db-host" type="text" placeholder="Host" value="localhost"></input><br />
				<b>Username: </b><input id="db-user" type="text" placeholder="Username" value=""></input><br />
				<b>Password: </b><input id="db-pass" type="text" placeholder="Password" value=""></input><br />
				<b>Database: </b><input id="db-db" type="text" placeholder="Database" value=""></input><br />
				<button onclick="gotoPage(\'2\');">Next</button>
			</div>
			<div id="pg2frm" class="hide">
				<p>Only SMTP is currently supported.</p>
				<b>From: </b><input id="em-from" type="text" placeholder="From" value=""></input><br />
				<b>Host: </b><input id="em-host" type="text" placeholder="Host" value=""></input><br />
				<b>Username/Email Address: </b><input id="em-user" type="text" placeholder="Username" value=""></input><br />
				<b>Password: </b><input id="em-pass" type="text" placeholder="Password" value=""></input><br />
				<button onclick="gotoPage(\'1\');">Previous</button>
				<button onclick="gotoPage(\'3\');">Next</button>
			</div>
			<div id="pg3frm" class="hide">
				<b>Website Title: </b><input id="st-name" type="text" placeholder="Title" value="New Website"></input><br />
				<button onclick="gotoPage(\'2\');">Previous</button>
				<button onclick="gotoPage(\'4\');">Next</button>
			</div>
			<div id="pg4frm" class="hide">
			    <b>Name: </b><input id="admin-name" type="text" placeholder="Admin Name" value=""></input><br />
				<b>Email Address: </b><input id="admin-email" type="text" placeholder="Admin Email" value=""></input><br />
				<b>Password: </b><input id="admin-pass" type="text" placeholder="Admin Password" value=""></input><br />
				<button onclick="gotoPage(\'3\');">Previous</button>
				<button onclick="processForm();">Finish</button>
			</div>
		</form>
		</div>
		<script>

function gotoPage(page) {
	document.getElementById("pg1frm").className = "hide";
	document.getElementById("pg2frm").className = "hide";
	document.getElementById("pg3frm").className = "hide";
	document.getElementById("pg4frm").className = "hide";
	document.getElementById("pg"+page+"frm").className = "";
	document.getElementById("step").innerHTML = page;
	document.getElementById("stepname").innerHTML = ["","Database","Email","Website","Administrator Account"][page];
	document.getElementById("stepProgress").innerHTML = \'<progress value="\'+page+\'" max="4"></progress>\';
	return false;
}

var $={};$.x=function(){return new XMLHttpRequest();};$.s=function(u,c,m,d,a){a=a===undefined?true:a;var x=$.x();x.open(m,u,a);x.onreadystatechange=function(){if(x.readyState==4){c(x.responseText);}};if(m=="POST"){x.setRequestHeader("Content-type","application/x-www-form-urlencoded");}x.send(d);};$.get=function(q,c,a){$.s(q,c,"GET",null,a);};$.post=function(q,d,c,a){var f=[];for(var k in d){f.push(encodeURIComponent(k)+"="+encodeURIComponent(d[k]));}$.s(q,c,"POST",f.join("&"),a);}

function raiseError(code) {
	// Error codes:
	//  0   :
	//  1** : Missing form data
	//  101 : Please provide a database host.
	//  102 : Please provide a database username.
	//  103 : Please provide a database password.
	//  104 : Please provide a database name.
	//  105 : Please provide an email host.
	//  106 : Please provide an email username.
	//  107 : Please provide an email password.
	//  108 : Please provide a website title.
	//  109 : Please provide an administrator email.
	//  110 : Please provide an administrator password.
	//  2** : Invalid credentials
	//  5** : Success
	//  501 : Form OK, Redirecting
	var err = {
		// 1** : Missing form data
		101 : "Please provide a database host",
		// 5** : Success
		501 : "Setup complete! If this page doesn\'t automaically redirect, click <a href=\"./\">here.</a>"
	};
	document.getElementById("formErrors").innerHTML = err[code];
}

function processForm() {
	// 1. Evaluate data
	// 1.0 Get data
	var data = {"db":{"host":null,"user":null,"pass":null,"data":null},"email":{"host":null,"user":null,"pass":null},"title":null,"admin":{"name":null,"email":null,"pass":null}};
	data["db"]["host"] = document.getElementById("db-host").value;
	data["db"]["user"] = document.getElementById("db-user").value;
	data["db"]["pass"] = document.getElementById("db-pass").value;
	data["db"]["data"] = document.getElementById("db-db").value;
	data["email"]["from"] = document.getElementById("em-from").value;
	data["email"]["host"] = document.getElementById("em-host").value;
	data["email"]["user"] = document.getElementById("em-user").value;
	data["email"]["pass"] = document.getElementById("em-pass").value;
	data["title"] = document.getElementById("st-name").value;
	data["admin"]["name"] = document.getElementById("admin-name").value;
	data["admin"]["email"] = document.getElementById("admin-email").value;
	data["admin"]["pass"] = document.getElementById("admin-pass").value;
	// 1.1 Check fields are non-empty - do later
	// 1.2 Check database credentials - do later	
	// 1.3 Check email formatting - do later
	var dataJson = JSON.stringify(data);
	// 2 Save setup
	$.post("?setup", {"data":dataJson}, function(d){
		// 3 Redirect to index.php (should do a self-check and if OK, delete setup.php and STATE)	
		if (d == "OK") {
			raiseError(501);
			setTimeout(function(){location = "./";}, 1500);
		}
	});
	return false;
}
		</script>
	</body>
</html>
';
}

// Form processing:
// 1. Evaluate data
// 1.1 Check that fields are non-empty
// 1.2 Check database credentials
// 1.3 Check email credentials
// 2. Write db-config.json
// 3. Write mail-config.json
// 4. Drop any existing database tables
// 5. Create default db tables
// 6. Populate `config` table
// 7. Populate `users` and `access` table
// 8. Send confirmation email to admin email
// 9. Redirect to index.php (should do a self-check and if OK, delete setup.php and STATE)

function saveSetup() {
	if (!isset($_POST["data"])) {
		return "ERR";
	}
	$data = json_decode($_POST["data"]);
	$dbConfig = [];
	$mailConfig = [];
	// {"db":{"host":null,"user":null,"pass":null,"data":null},"email":{"host":null,"user":null,"pass":null},"title":null,"admin":{"name":null,"email":null,"pass":null}};
	
	// 2 Save setup
	// 2.1 Write db-config.json
	$dbConfig["host"] = $data->db->host;
	$dbConfig["user"] = $data->db->user;
	$dbConfig["pass"] = $data->db->pass;
	$dbConfig["database"] = $data->db->data;
	file_put_contents("./assets/server/db-config.json", json_encode($dbConfig));
	// 2.2 Write mail-config.json
	$mailConfig = $data->email;
	file_put_contents("./assets/server/mail-config.json", json_encode($mailConfig));
	// 2.3 Setup database
	$conn = null;
	$sqlstat = true;
	try {
		$conn = new PDO("mysql:host=" . $data->db->host . ";dbname=" . $data->db->data, $data->db->user, $data->db->pass);
		$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	} catch(PDOException $e) {
		$sqlstat = false;
	}
	if (!$sqlstat) {
		return "ERR";
	}
	// 2.3.1 Drop any existing database tables
	$stmt = $conn->prepare("SHOW TABLES;");
	$stmt->execute();$stmt->setFetchMode(PDO::FETCH_ASSOC);
	$tbls = $stmt->fetchAll();
	foreach($tbls as $tbl) {
		$tblName = $tbl["Tables_in_" . $data->db->data];
		$conn->exec("DROP TABLE `" . $tblName . "`;");
	}
	// 2.3.2 Create default db tables
	$conn->exec("CREATE TABLE `config` (`websitetitle` text NOT NULL,`primaryemail` text NOT NULL,`secondaryemail` text,`creationdate` date DEFAULT NULL,`defaulthead` mediumtext,`defaulttitle` text,`defaultbody` longtext,`defaultnav` mediumtext,`defaultfoot` mediumtext) ENGINE=InnoDB DEFAULT CHARSET=latin1;");
	$conn->exec("CREATE TABLE `users` (`uid` char(32) NOT NULL,`email` tinytext NOT NULL,`name` tinytext NOT NULL,`registered` date NOT NULL,`permissions` text NOT NULL,`permviewbl` text NOT NULL,`permeditbl` text NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=latin1;ALTER TABLE `users` ADD UNIQUE KEY `uid` (`uid`);");
	$conn->exec("CREATE TABLE `access` (`uid` char(32) NOT NULL,`pwd` char(128) NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=latin1;ALTER TABLE `access` ADD UNIQUE KEY `uid` (`uid`);");
	$conn->exec("CREATE TABLE `tokens` (`uid` char(32) NOT NULL,`tid` char(32) NOT NULL,`source_ip` varchar(45) NOT NULL,`start` date NOT NULL,`expire` date NOT NULL,`forcekill` tinyint(1) NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=latin1;ALTER TABLE `tokens` ADD UNIQUE KEY `tid` (`tid`);");
	$conn->exec("CREATE TABLE `content_pages` (`pageid` varchar(255) NOT NULL,`title` text NOT NULL,`head` longtext NOT NULL,`body` longtext NOT NULL,`navmod` mediumtext NOT NULL,`footmod` mediumtext NOT NULL,`secure` tinyint(1) NOT NULL,`revision` date NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=latin1;ALTER TABLE `content_pages` ADD UNIQUE KEY `pageid` (`pageid`);");
	$conn->exec("CREATE TABLE `schedule` (`index` INT NOT NULL AUTO_INCREMENT, after DATETIME DEFAULT CURRENT_TIMESTAMP, function TINYTEXT, args TEXT, UNIQUE KEY (`index`));");
	// 2.3.3 Populate `config` and `content_pages` tables
	$stmt = $conn->prepare("INSERT INTO `config` VALUES (:name, :email, NULL, '" . date("Y-m-d") . "', NULL, NULL, NULL, NULL, NULL);");
	$stmt->bindParam(":name", $data->title);
	$stmt->bindParam(":email", $data->admin->email);
	$stmt->execute();
	$conn->exec("INSERT INTO `content_pages` VALUES ('home', 'Home%20Page', '', 'Empty%3Cbr%20%2F%3E%3Ca%20href%3D%22%3Fp%3Dsecureaccess%22%3ESign%20in%3C%2Fa%3E', '', '', 0, '2017-01-29'),('notfound', 'Page%20not%20found!', '', 'Page not Found: %7B%7Bqueryerr%7D%7D', '', '', 0, '2017-01-29'),('secureaccess', 'Secure%20Access%20Portal', '', '%7B%7Bloginform%7D%7D', '', '', 0, '2017-01-29');");
	// 2.3.4 Populate `users` and `access` tables
	$uid = md5($data->admin->email);
	$pwdHash = hash("sha512", $data->admin->pass);
	$stmt = $conn->prepare("INSERT INTO `users` VALUES (:uid, :email, :name, '" . date("Y-m-d") . "', 'owner;', '', '');");
	$stmt->bindParam(":uid", $uid);
	$stmt->bindParam(":email", $data->admin->email);
	$stmt->bindParam(":name", $data->admin->name);
	$stmt->execute();
	$stmt = $conn->prepare("INSERT INTO `access` VALUES (:uid, :pwd);");
	$stmt->bindParam(":uid", $uid);
	$stmt->bindParam(":pwd", $pwdHash);
	$stmt->execute();
	// 2.4 Send confirmation email to admin email
	return "OK";
}

function errorMsg() {
	echo "error";
}


?>