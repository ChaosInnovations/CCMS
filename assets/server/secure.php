<?php

function new_token($uid) {
	global $conn;
	// Kill other tokens from this uid
	$stmt = $conn->prepare("UPDATE tokens SET forcekill=1 WHERE uid=:uid;");
	$stmt->bindParam(":uid", $uid);
	$stmt->execute();
	
	$now = date("Y-m-d", time());
	$end = date("Y-m-d", time()+3600*24*30); // 30-day expiry
	while (true) { // In case the token is already taken
		$token = bin2hex(openssl_random_pseudo_bytes(16));
		$stmt = $conn->prepare("SELECT * FROM tokens WHERE tid=:tid;");
		$stmt->bindParam(":tid", $token);
		$stmt->execute();$stmt->setFetchMode(PDO::FETCH_ASSOC);
		if (count($stmt->fetchAll()) == 0) {
			break;
		}
	}
	$stmt = $conn->prepare("INSERT INTO tokens VALUES (:uid, :tid, :ip, :start, :expire, 0);");
	$stmt->bindParam(":uid", $uid);
	$stmt->bindParam(":tid", $token);
	$stmt->bindParam(":ip", $_SERVER['REMOTE_ADDR']);
	$stmt->bindParam(":start", $now);
	$stmt->bindParam(":expire", $end);
	$stmt->execute();
	return $token;
}

function removeBadTokens() {
	global $conn, $sqlstat;
	if (!$sqlstat) {
		return;
	}
	$now = date("Y-m-d", time());
	$stmt = $conn->prepare("DELETE FROM tokens WHERE expire<=:now OR forcekill=1;");
	$stmt->bindParam(":now", $now);
	$stmt->execute();
}

function ajax_newtoken() {
	global $conn, $sqlstat, $sqlerr;
	if (!$sqlstat) {
		return "FALSE";
	}
	if (!isset($_POST["email"]) or !isset($_POST["password"])) {
		return "FALSE";
	}
	$uid = md5(fix_email($_POST["email"]));
	if (!checkPassword($uid, $_POST["password"])) {
		return "FALSE";
	}
	return new_token($uid);
}

function fix_email($email) {
	$email = strtolower($email);
	$email = preg_replace('/\s+/', '', $email);
	return $email;
}

function load_jsons() {
	global $db_config, $ccms_info;
	$db_config = json_decode(file_get_contents("db-config.json", true));
	$ccms_info = json_decode(file_get_contents("ccms-info.json", true));
}

function getconfig($property) {
	global $conn, $sqlstat, $sqlerr;
	if ($sqlstat) {
		$stmt = $conn->prepare("SELECT * FROM config WHERE property=:property;");
		$stmt->bindParam(":property", $property);
		$stmt->execute();$stmt->setFetchMode(PDO::FETCH_ASSOC);
		$result = $stmt->fetchAll();
		if (count($result) != 1) {
			return "";
		}
		return $result[0]["value"];
	} else {
		return $sqlerr;
	}
}

function setconfig($property, $value) {
	global $conn, $sqlstat, $sqlerr;
	if ($sqlstat) {
		$stmt = $conn->prepare("UPDATE config SET {$property}=:val WHERE 1=1;");
		$stmt->bindParam(":val", $value);
		$stmt->execute();
		return true;
	} else {
		return $sqlerr;
	}
}

function ajax_newuser() {
	global $conn, $sqlstat, $sqlerr;
	global $authuser;
	global $TEMPLATES;
	global $notifMailer;
	global $baseUrl;
	
	if (!$sqlstat) {
		return "FALSE";
	}
	if (!isset($_POST["email"]) or !isset($_POST["name"]) or !isset($_POST["permissions"])) {
		return "FALSE";
	}
	$uid = md5(fix_email($_POST["email"]));
	if (validUser($uid)) {
		// User already exists.
		return "FALSE";
	}
	if (!$authuser->permissions->owner) {
		// Only owners can change users
		return "FALSE";
	}
	$body = $TEMPLATES["email-newuser"]($_POST["name"], $authuser->name, $baseUrl, getconfig("websitetitle"));
	$mail = $notifMailer->compose([[fix_email($_POST["email"]), $_POST["name"]]], "Account Created", $body, "");
	// comment below for testing
	
	if (!$mail->send()) {
		return "FALSE";
	}
	
	$email = fix_email($_POST["email"]);
	$now = date("Y-m-d");
	$pwd = hash("sha512", "password");
	$stmt = $conn->prepare("INSERT INTO users VALUES (:uid, :email, :name, :now, :perms, '', '', 0, NULL, '', 1, 0);");
	$stmt->bindParam(":uid", $uid);
	$stmt->bindParam(":email", $email);
	$stmt->bindParam(":name", $_POST["name"]);
	$stmt->bindParam(":now", $now);
	$stmt->bindParam(":perms", $_POST["permissions"]);
	$stmt->execute();
	$stmt = $conn->prepare("INSERT INTO access VALUES (:uid, :pwd);");
	$stmt->bindParam(":uid", $uid);
	$stmt->bindParam(":pwd", $pwd);
	$stmt->execute();
	return "TRUE";
}

function ajax_removeaccount() {
	global $conn, $sqlstat;
	global $authuser;
	if (!$sqlstat) {
		return "FALSE";
	}
	if (!isset($_POST["uid"]) or !validUser($_POST["uid"])) {
		return "FALSE";
	}
	$uid = $_POST["uid"];
	if (!$authuser->permissions->owner and $authuser->uid != $uid) {
		return "FALSE";
	}
	if ($authuser->permissions->owner and $authuser->uid == $uid and count(usersWithPermissions("owner")) <= 1) {
		return "OWNER";
	}
	$stmt = $conn->prepare("DELETE FROM users WHERE uid=:uid;");
	$stmt->bindParam(":uid", $uid);
	$stmt->execute();
	$stmt = $conn->prepare("DELETE FROM access WHERE uid=:uid;");
	$stmt->bindParam(":uid", $uid);
	$stmt->execute();
	$stmt = $conn->prepare("DELETE FROM tokens WHERE uid=:uid;");
	$stmt->bindParam(":uid", $uid);
	$stmt->execute();
	return "TRUE";
}

function ajax_checkpass() {
	global $conn, $sqlstat, $sqlerr;
	global $authuser;
	if (!$sqlstat) {
		return "FALSE";
	}
	if (!isset($_POST["password"])) {
		return "FALSE";
	}
	$uid = $authuser->uid;
	if (isset($_POST["email"])) {
		$uid = md5(fix_email($_POST["email"]));
	}
	if ($uid == null) {
		return "FALSE";
	}
	if (!checkPassword($uid, $_POST["password"])) {
		return "FALSE";
	}
	return "TRUE";
}

function checkPassword($uid, $password) {
	global $conn, $sqlstat, $sqlerr;
	if (!$sqlstat) {
		return false;
	}
	$pwd = hash("sha512", $password);
	$stmt = $conn->prepare("SELECT pwd FROM access WHERE uid=:uid AND pwd=:pwd;");
	$stmt->bindParam(":uid", $uid);
	$stmt->bindParam(":pwd", $pwd);
	$stmt->execute();$stmt->setFetchMode(PDO::FETCH_ASSOC);
	if (count($stmt->fetchAll()) != 1) {
		return false;
	}
	return true;
}

function ajax_checkuser() {
	if (!$_POST["email"]) {
		return "FALSE";
	}
	if (!validUser(md5(fix_email($_POST["email"])))) {
		return "FALSE";
	}
	return "TRUE";
}

function ajax_resetpwd() {
	global $conn, $sqlstat;
	global $authuser;
	if (!$sqlstat) {
		return "FALSE";
	}
	if (!isset($_POST["uid"]) or !validUser($_POST["uid"])) {
		return "FALSE";
	}
	if (!$authuser->permissions->owner) {
		return "FALSE";
	}
	$pwd = hash("sha512", "password");
	$stmt = $conn->prepare("UPDATE access SET pwd=:pwd WHERE uid=:uid;");
	$stmt->bindParam(":pwd", $pwd);
	$stmt->bindParam(":uid", $_POST["uid"]);
	$stmt->execute();
	return "TRUE";
}
	
function ajax_changepass() {
	global $conn, $sqlstat;
	global $authuser;
	if (!$sqlstat) {
		return "FALSE";
	}
	if (!isset($_POST["cpwd"]) or !isset($_POST["npwd"])) {
		return "FALSE";
	}
	if ($authuser->uid == null) {
		return "FALSE";
	}
	if (!checkPassword($authuser->uid, $_POST["cpwd"])) {
		return "FALSE";
	}
	$pwd = hash("sha512", $_POST["npwd"]);
	$stmt = $conn->prepare("UPDATE access SET pwd=:pwd WHERE uid=:uid;");
	$stmt->bindParam(":uid", $authuser->uid);
	$stmt->bindParam(":pwd", $pwd);
	$stmt->execute();
	echo "TRUE";
}

function ajax_setconfig() {
	global $conn, $sqlstat, $sqlerr;
	global $authuser;
	
	if ($sqlstat) {
		if (isset($_POST["websitetitle"])) {
			setconfig("websitetitle", $_POST["websitetitle"]);
		}
		if (isset($_POST["primaryemail"])) {
			setconfig("primaryemail", $_POST["primaryemail"]);
		}
		return "TRUE";
	} else {
		return "FALSE";
	}
}

function ajax_edituser() {
	global $conn, $sqlstat, $sqlerr;
	global $authuser;
	
	if (!$sqlstat) {
		return "FALSE";
	}
	
	if ($authuser->uid == null) {
		return "FALSE";
	}
	
	if (isset($_POST["name"])) {
		$stmt = $conn->prepare("UPDATE users SET name=:name WHERE uid=:uid;");
		$stmt->bindParam(":name", $_POST["name"]);
		$stmt->bindParam(":uid", $authuser->uid);
		$stmt->execute();
	}
	if (isset($_POST["notify"])) {
		$stmt = $conn->prepare("UPDATE users SET notify=:notify WHERE uid=:uid;");
		$stmt->bindParam(":notify", $_POST["notify"]);
		$stmt->bindParam(":uid", $authuser->uid);
		$stmt->execute();
	}
	if (isset($_POST["permissions"]) and $authuser->permissions->owner) {
		$stmt = $conn->prepare("UPDATE users SET permissions=:new WHERE uid=:uid;");
		$stmt->bindParam(":new", $_POST["permissions"]);
		$stmt->bindParam(":uid", $_POST["uid"]);
		$stmt->execute();
	}
	if (isset($_POST["permviewbl"]) and $authuser->permissions->owner) {
		$stmt = $conn->prepare("UPDATE users SET permviewbl=:new WHERE uid=:uid;");
		$stmt->bindParam(":new", $_POST["permviewbl"]);
		$stmt->bindParam(":uid", $_POST["uid"]);
		$stmt->execute();
	}
	if (isset($_POST["permeditbl"]) and $authuser->permissions->owner) {
		$stmt = $conn->prepare("UPDATE users SET permeditbl=:new WHERE uid=:uid;");
		$stmt->bindParam(":new", $_POST["permeditbl"]);
		$stmt->bindParam(":uid", $_POST["uid"]);
		$stmt->execute();
	}
	return "TRUE";
}

function uidFromToken($token) {
	global $conn, $sqlstat, $sqlerr;
	
	if ($sqlstat) {
		$stmt = $conn->prepare("SELECT * FROM tokens WHERE tid=:tid;");
		$stmt->bindParam(":tid", $token);
		$stmt->execute();$stmt->setFetchMode(PDO::FETCH_ASSOC);
		$tokens = $stmt->fetchAll();
		return $tokens[0]["uid"];
	} else {
		return null;
	}
}

function validToken($token) {
	global $conn, $sqlstat, $sqlerr;
	
	removeBadTokens();
	
	if ($sqlstat) {
		$stmt = $conn->prepare("SELECT * FROM tokens WHERE tid=:tid AND source_ip=:ip AND start<=:now AND expire>:now AND forcekill=0;");
		$now = date("Y-m-d");
		$stmt->bindParam(":tid", $token);
		$stmt->bindParam(":ip", $_SERVER['REMOTE_ADDR']);
		$stmt->bindParam(":now", $now);
		$stmt->execute();$stmt->setFetchMode(PDO::FETCH_ASSOC);
		$tokens = $stmt->fetchAll();
		if (count($tokens) == 1) {
			return true;
		} else {
			return false;
		}
	} else {
		return false;
	}
}

function nameOfUser($uid) {
	global $conn, $sqlstat, $sqlerr;
	
	if ($sqlstat and validUser($uid)) {
		$stmt = $conn->prepare("SELECT name FROM users WHERE uid=:uid;");
		$stmt->bindParam(":uid", $uid);
		$stmt->execute();$stmt->setFetchMode(PDO::FETCH_ASSOC);
		return $stmt->fetchAll()[0]["name"];
	}
}

function validUser($uid) {
	global $conn, $sqlstat, $sqlerr;
	
	if ($sqlstat) {
		$stmt = $conn->prepare("SELECT * FROM users WHERE uid=:uid;");
		$stmt->bindParam(":uid", $uid);
		$stmt->execute();$stmt->setFetchMode(PDO::FETCH_ASSOC);
		$users = $stmt->fetchAll();
		if (count($users) == 1) {
			return true;
		} else {
			return false;
		}
	} else {
		return false;
	}
}

function usersWithPermission($perm) {
	global $conn, $sqlstat, $sqlerr;
	$users = [];
	if ($sqlstat) {
		$stmt = $conn->prepare("SELECT uid, permissions FROM users;");
		$stmt->execute();$stmt->setFetchMode(PDO::FETCH_ASSOC);
		$udata = $stmt->fetchAll();
		foreach ($udata as $u) {		
			if (!(strpos($u["permissions"], $perm) === false)) {
				array_push($users, $u["uid"]);
			}	
		}
	}
	return $users;
}

?>