<?php

use \Lib\CCMS\Security\User;
use \Lib\CCMS\Security\AccountManager;
use \Lib\CCMS\Utilities;
use \Mod\Mailer;

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
    
	$body = $TEMPLATES["email-newuser"]($_POST["name"], $authuser->name, $baseUrl, Utilities::getconfig("websitetitle"));
	$mail = Mailer::NotifInstance()->compose([[User::normalizeEmail($_POST["email"]), $_POST["name"]]], "Account Created", $body, "");
	
	if (!$mail->send()) {
		return "FALSE";
	}
	
	$email = User::normalizeEmail($_POST["email"]);
	$now = date("Y-m-d");
	$pwd = password_hash("password", PASSWORD_DEFAULT);
    
	$stmt = $conn->prepare("INSERT INTO users VALUES (:uid, :pwd, :email, :name, :now, :perms, '', '', 0, NULL, '', 1, 0);");
	$stmt->bindParam(":uid", $uid);
	$stmt->bindParam(":pwd", $pwd);
	$stmt->bindParam(":email", $email);
	$stmt->bindParam(":name", $_POST["name"]);
	$stmt->bindParam(":now", $now);
	$stmt->bindParam(":perms", $_POST["permissions"]);
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
	$pwd = password_hash("password", PASSWORD_DEFAULT);
	$stmt = $conn->prepare("UPDATE users SET pwd=:pwd WHERE uid=:uid;");
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
	if (!$authuser->authenticate($_POST["cpwd"])) {
		return "FALSE";
	}
	$pwd = password_hash($_POST["npwd"], PASSWORD_DEFAULT);
	$stmt = $conn->prepare("UPDATE users SET pwd=:pwd WHERE uid=:uid;");
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
			Utilities::setconfig("websitetitle", $_POST["websitetitle"]);
		}
		if (isset($_POST["primaryemail"])) {
			Utilities::setconfig("primaryemail", $_POST["primaryemail"]);
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