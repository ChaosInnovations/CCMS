<?php

use \Lib\CCMS\Security\User;
use \Lib\CCMS\Security\AccountManager;

function ajax_newtoken() {
	global $conn, $sqlstat, $sqlerr;
	if (!$sqlstat) {
		return "FALSE";
	}
	if (!isset($_POST["email"]) or !isset($_POST["password"])) {
		return "FALSE";
	}
	$uid = User::uidFromEmail($_POST["email"]);
	if (!checkPassword($uid, $_POST["password"])) {
		return "FALSE";
	}
	return AccountManager::registerNewToken($uid, $_SERVER["REMOTE_ADDR"]);
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
	$uid = User::uidFromEmail($_POST["email"]);
	if ((new User($uid))->isValidUser()) {
		// User already exists.
		return "FALSE";
	}
	if (!$authuser->permissions->owner) {
		// Only owners can change users
		return "FALSE";
	}
    
	$body = $TEMPLATES["email-newuser"]($_POST["name"], $authuser->name, $baseUrl, getconfig("websitetitle"));
	$mail = $notifMailer->compose([[User::normalizeEmail($_POST["email"]), $_POST["name"]]], "Account Created", $body, "");
	
	if (!$mail->send()) {
		return "FALSE";
	}
	
	$email = User::normalizeEmail($_POST["email"]);
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
	if (!isset($_POST["uid"]) or !(new User($_POST["uid"]))->isValidUser()) {
		return "FALSE";
	}
	$uid = $_POST["uid"];
	if (!$authuser->permissions->owner and $authuser->uid != $uid) {
		return "FALSE";
	}
	if ($authuser->permissions->owner and $authuser->uid == $uid and User::numberOfOwners() <= 1) {
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
		$uid = User::uidFromEmail($_POST["email"]);
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
	if (!User::userFromEmail($_POST["email"])->isValidUser()) {
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
	if (!isset($_POST["uid"]) or !(new User($_POST["uid"]))->isValidUser()) {
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
	if (!$authuser->isValidUser()) {
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
	
	if (!$authuser->isValidUser()) {
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

?>