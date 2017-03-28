<?php

if (substr($_SERVER["PHP_SELF"], -strlen("secure.php")) == "secure.php") {
	include "mail.php";
	load_jsons();
	$conn = null;
	$sqlstat = true;
	$sqlerr = "";
	try {
		$conn = new PDO("mysql:host=" . $db_config->host . ";dbname=" . $db_config->database, $db_config->user, $db_config->pass);
		$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	} catch(PDOException $e) {
		$sqlstat = false;
		$sqlerr = $e;
	}
	if (isset($_POST["token"]) and validToken($_POST["token"])) {
		$authuser = new AuthUser($_POST["token"]);
	} else {
		setcookie("token", "0", 1);
		$authuser = new AuthUser(null);
	}
	$mailer = new Mailer();
	$mailer->host = $mail_config->host;
	$mailer->username = $mail_config->user;
	$mailer->password = $mail_config->pass;
	$mailer->from = $mail_config->from;
	if (isset($_GET["newuser"])) {
		if ($sqlstat) {
			$uid = md5($_POST["email"]);
			if ($authuser->permissions->owner and !validUser($uid)) {
				$mail = $mailer->compose([[$_POST["email"], $_POST["name"]]], "Account Created", "<div><h1>Hi {$_POST["name"]}!</h1><p>{$authuser->name} created an account for you on the <a href='http://penderbus.ca/' title='Pender Island Community Bus'>Pender Island Community Bus website.</a> Your current password is <b>password</b> so please change it when you log in for the first time.</p><a href='http://penderbus.ca/?p=secureaccess'>Sign In</a></div><p>Pender Island Community Bus Team</p>", "", [], [], ["info@penderbus.ca"]);
				if ($mail->send()) {
					$now = date("Y-m-d");
					$pwd = hash("sha512", "password");
					$stmt = $conn->prepare("INSERT INTO users (uid, email, name, registered, permissions) VALUES (:uid, :email, :name, :now, :perm);");
					$stmt2 = $conn->prepare("INSERT INTO access (uid, pwd) VALUES (:uid, :pwd);");
					$stmt->bindParam(":uid", $uid);
					$stmt->bindParam(":email", $_POST["email"]);
					$stmt->bindParam(":name", $_POST["name"]);
					$stmt->bindParam(":now", $now);
					$stmt->bindParam(":perm", $_POST["permissions"]);
					$stmt2->bindParam(":uid", $uid);
					$stmt2->bindParam(":pwd", $pwd);
					$stmt->execute();
					$stmt2->execute();
					echo "TRUE";
				} else {
					echo "FALSE";
				}
			} else {
				echo "FALSE";
			}
		} else {
			echo "FALSE";
		}
	} else if (isset($_GET["resetpwd"])) {
		if ($sqlstat and $authuser->permissions->owner and validUser($_POST["uid"])) {
			$pwd = hash("sha512", "password");
			$stmt = $conn->prepare("UPDATE access SET pwd=:pwd WHERE uid=:uid;");
			$stmt->bindParam(":pwd", $pwd);
			$stmt->bindParam(":uid", $uid);
			$stmt->execute();
			echo "TRUE";
		} else {
			echo "FALSE";
		}
	} else if (isset($_GET["removeaccount"])) {
		if ($sqlstat and validUser($_POST["uid"])) {
			if ($authuser->permissions->owner or $_POST["uid"] == $authuser->uid) {
				if (($_POST["uid"] == $authuser->uid and count(usersWithPermission("owner")) > 1) or $_POST["uid"] != $authuser->uid) {
					$stmt = $conn->prepare("DELETE FROM users WHERE uid=:uid;");
					$stmt2 = $conn->prepare("DELETE FROM access WHERE uid=:uid;");
					$stmt3 = $conn->prepare("DELETE FROM tokens WHERE uid=:uid;");
					$stmt->bindParam(":uid", $_POST["uid"]);
					$stmt2->bindParam(":uid", $_POST["uid"]);
					$stmt3->bindParam(":uid", $_POST["uid"]);
					$stmt->execute();
					$stmt2->execute();
					$stmt3->execute();
					echo "TRUE";
				} else {
					echo "OWNER";
				}
			} else {
				echo "FALSE";
			}
		} else {
			echo "FALSE";
		}
	} else if (isset($_GET["checkuser"])) {
		if (validUser(md5($_GET["checkuser"]))) {
			echo "TRUE";
		} else {
			echo "FALSE";
		}
	} else if (isset($_GET["checkpass"]) and isset($_GET["user"])) {
		if ($sqlstat) {
			$uid = md5($_GET["user"]);
			$pwd = hash("sha512", $_GET["checkpass"]);
			$stmt = $conn->prepare("SELECT pwd FROM access WHERE uid=:uid AND pwd=:pwd;");
			$stmt->bindParam(":uid", $uid);
			$stmt->bindParam(":pwd", $pwd);
			$stmt->execute();$stmt->setFetchMode(PDO::FETCH_ASSOC);
			$valid = count($stmt->fetchAll()) == 1;
			if ($valid) {
				echo "TRUE";
			} else {
				echo "FALSE";
			}
		}
	} else if (isset($_GET["checkcpwd"])) {
		if ($sqlstat) {
			$pwd = hash("sha512", $_GET["checkcpwd"]);
			$stmt = $conn->prepare("SELECT pwd FROM access WHERE uid=:uid and pwd=:pwd;");
			$stmt->bindParam(":uid", $authuser->uid);
			$stmt->bindParam(":pwd", $pwd);
			$stmt->execute();$stmt->setFetchMode(PDO::FETCH_ASSOC);
			if (count($stmt->fetchAll()) == 1) {
				echo "TRUE";
			}
		} else {
			echo "FALSE";
		}
	} else if (isset($_GET["changepass"]) and isset($_POST["cpwd"]) and isset($_POST["npwd"])) {
		if ($sqlstat) {
			$pwd = hash("sha512", $_POST["cpwd"]);
			$stmt = $conn->prepare("SELECT pwd FROM access WHERE uid=:uid and pwd=:pwd;");
			$stmt->bindParam(":uid", $authuser->uid);
			$stmt->bindParam(":pwd", $pwd);
			$stmt->execute();$stmt->setFetchMode(PDO::FETCH_ASSOC);
			if (count($stmt->fetchAll()) == 1) {
				$pwd = hash("sha512", $_POST["npwd"]);
				$stmt = $conn->prepare("UPDATE access SET pwd=:pwd WHERE uid=:uid;");
				$stmt->bindParam(":uid", $authuser->uid);
				$stmt->bindParam(":pwd", $pwd);
				$stmt->execute();
				echo "TRUE";
			} else {
				echo "FALSE";
			}
		} else {
			echo "FALSE";
		}
	} else if (isset($_GET["edituser"]) and isset($_POST["name"])) {
		if ($sqlstat and $authuser->token != null) {
			$stmt = $conn->prepare("UPDATE users SET name=:name WHERE uid=:uid;");
			$stmt->bindParam(":name", $_POST["name"]);
			$stmt->bindParam(":uid", $authuser->uid);
			$stmt->execute();
			echo "TRUE";
		} else {
			echo "FALSE2";
		}
	} else if (isset($_GET["newtoken"]) and isset($_GET["pass"])) {
		if ($sqlstat) {
			$now = date("Y-m-d", time());
			$stmt = $conn->prepare("DELETE FROM tokens WHERE expire<=:now OR forcekill=1;");
			$stmt->bindParam(":now", $now);
			$stmt->execute();
			$uid = md5($_GET["newtoken"]);
			$pwd = hash("sha512", $_GET["pass"]);
			$stmt = $conn->prepare("SELECT pwd FROM access WHERE uid=:uid AND pwd=:pwd;");
			$stmt->bindParam(":uid", $uid);
			$stmt->bindParam(":pwd", $pwd);
			$stmt->execute();$stmt->setFetchMode(PDO::FETCH_ASSOC);
			$valid = count($stmt->fetchAll()) == 1;
			if ($valid) {
				$now = date("Y-m-d", time());
				$end = date("Y-m-d", time()+3600*24*30);
				while (true) {
					$token = bin2hex(openssl_random_pseudo_bytes(16));
					$stmt = $conn->prepare("SELECT * FROM tokens WHERE tid=:tid;");
					$stmt->bindParam(":tid", $token);
					$stmt->execute();$stmt->setFetchMode(PDO::FETCH_ASSOC);
					if (count($stmt->fetchAll()) == 0) {
						break;
					}
				}
				$stmt = $conn->prepare("INSERT INTO tokens (uid, tid, start, expire, forcekill) VALUES (:uid, :tid, :start, :expire, 0);");
				$stmt->bindParam(":uid", $uid);
				$stmt->bindParam(":tid", $token);
				$stmt->bindParam(":start", $now);
				$stmt->bindParam(":expire", $end);
				$stmt->execute();
				echo $token;
			} else {
				echo "FALSE";
			}
		}
	} else {
		echo "FALSE";
	}
}

function load_jsons() {
	global $db_config, $mail_config, $ccms_info;
	$db_config = json_decode(file_get_contents("db-config.json", true));
	$mail_config = json_decode(file_get_contents("mail-config.json", true));
	$ccms_info = json_decode(file_get_contents("ccms-info.json", true));
}

function getconfig($property) {
	global $conn, $sqlstat, $sqlerr;
	if ($sqlstat) {
		$stmt = $conn->prepare("SELECT * FROM config;");
		$stmt->execute();$stmt->setFetchMode(PDO::FETCH_ASSOC);
		return $stmt->fetchAll()[0][$property];
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
		if (isset($_POST["secondaryemail"])) {
			setconfig("secondaryemail", $_POST["secondaryemail"]);
		}
		if (isset($_POST["defaulttitle"])) {
			setconfig("defaulttitle", $_POST["defaulttitle"]);
		}
		if (isset($_POST["defaulthead"])) {
			setconfig("defaulthead", $_POST["defaulthead"]);			
		}
		if (isset($_POST["defaultbody"])) {
			setconfig("defaultbody", $_POST["defaultbody"]);
		}
		if (isset($_POST["defaultnav"])) {
			setconfig("defaultnav", $_POST["defaultnav"]);
		}
		if (isset($_POST["defaultfoot"])) {
			setconfig("defaultfoot", $_POST["defaultfoot"]);
		}
		return "TRUE";
	} else {
		return "FALSE";
	}
}

function edituser() {
	global $conn, $sqlstat, $sqlerr;
	global $authuser;
	
	if ($sqlstat and $authuser->token != null) {
		if (isset($_POST["name"])) {
			
		}
		if (isset($_POST["permissions"]) and $authuser->permissions->owner) {
			$stmt = $conn->prepare("UPDATE users SET permissions=:new WHERE uid=:uid;");
			$stmt->bindParam(":new", $_POST["permissions"]);
			$stmt->bindParam(":uid", $_POST["uid"]);
			$stmt->execute();
			return "TRUE";
		}
		if (isset($_POST["permviewbl"]) and $authuser->permissions->owner) {
			$stmt = $conn->prepare("UPDATE users SET permviewbl=:new WHERE uid=:uid;");
			$stmt->bindParam(":new", $_POST["permviewbl"]);
			$stmt->bindParam(":uid", $_POST["uid"]);
			$stmt->execute();
			return "TRUE";
		}
		if (isset($_POST["permissions"]) and $authuser->permissions->owner) {
			$stmt = $conn->prepare("UPDATE users SET permeditbl=:new WHERE uid=:uid;");
			$stmt->bindParam(":new", $_POST["permeditbl"]);
			$stmt->bindParam(":uid", $_POST["uid"]);
			$stmt->execute();
			return "TRUE";
		}
	} else {
		return "FALSE";
	}
}

class UserPermissions {
	public $owner = false;
	public $admin_managepages = false;
	public $admin_managesite = false;
	public $page_createsecure = false;
	public $page_editsecure = false;
	public $page_deletesecure = false;
	public $page_viewsecure = false;
	public $page_create = false;
	public $page_edit = false;
	public $page_delete = false;
	public $toolbar = false;
	public $page_viewblacklist = [];
	public $page_editblacklist = [];
}

class AuthUser {
	
	public $token = null;
	public $name = "User";
	public $email = "";
	public $uid = "";
	public $registerdate = "";
	public $rawperms = "";
	public $permissions = null;
		
	function __construct($token) {
		global $conn, $sqlstat, $sqlerr;
		
		$this->permissions = new UserPermissions();
		$this->token = $token;
		if ($token != null and $sqlstat) {
			$stmt = $conn->prepare("SELECT * FROM tokens WHERE tid=:tid;");
			$stmt->bindParam(":tid", $token);
			$stmt->execute();$stmt->setFetchMode(PDO::FETCH_ASSOC);
			$uid = $stmt->fetchAll()[0]["uid"];
			
			$stmt = $conn->prepare("SELECT * FROM users WHERE uid=:uid;");
			$stmt->bindParam(":uid", $uid);
			$stmt->execute();$stmt->setFetchMode(PDO::FETCH_ASSOC);
			$udata = $stmt->fetchAll();
			
			$this->uid = $uid;
			$this->email = $udata[0]["email"];
			$this->name = $udata[0]["name"];
			$this->registerdate = date("l, F j, Y", strtotime($udata[0]["registered"]));
			$rawperm = $udata[0]["permissions"];
			$this->rawperms = $rawperm;
			
			$this->permissions->owner = !(strpos($rawperm, "owner;") === false);
			$this->permissions->admin_managesite = (!(strpos($rawperm, "admin_managesite;") === false) or $this->permissions->owner);
			$this->permissions->admin_managepages = (!(strpos($rawperm, "admin_managepages;") === false) or $this->permissions->admin_managesite);
			$this->permissions->page_createsecure = (!(strpos($rawperm, "page_createsecure;") === false) or $this->permissions->admin_managepages);
			$this->permissions->page_editsecure = (!(strpos($rawperm, "page_editsecure;") === false) or $this->permissions->page_createsecure);
			$this->permissions->page_deletesecure = (!(strpos($rawperm, "page_deletesecure;") === false) or $this->permissions->admin_managepages);
			$this->permissions->page_viewsecure = (!(strpos($rawperm, "page_viewsecure;") === false) or $this->permissions->page_editsecure);
			$this->permissions->page_create = (!(strpos($rawperm, "page_create;") === false) or $this->permissions->page_createsecure);
			$this->permissions->page_edit = (!(strpos($rawperm, "page_edit;") === false) or $this->permissions->page_editsecure);
			$this->permissions->page_delete = (!(strpos($rawperm, "page_delete;") === false) or $this->permissions->page_deletesecure);
			$this->permissions->toolbar = (!(strpos($rawperm, "toolbar;") === false) or 
										   $this->permissions->owner or
										   $this->permissions->admin_managesite or
										   $this->permissions->admin_managepages or
										   $this->permissions->page_createsecure or
										   $this->permissions->page_editsecure or
										   $this->permissions->page_deletesecure or
										   $this->permissions->page_create or
										   $this->permissions->page_edit or
										   $this->permissions->page_delete);
			//Also need to read blacklists
			$this->permissions->page_viewblacklist = explode(";", $udata[0]["permviewbl"]);
			$this->permissions->page_editblacklist = explode(";", $udata[0]["permeditbl"]);
		}
	}
	
}

function validToken($token) {
	global $conn, $sqlstat, $sqlerr;
	
	if ($sqlstat) {
		$stmt = $conn->prepare("SELECT * FROM tokens WHERE tid=:tid AND start<=:now AND expire>:now AND forcekill=0;");
		$now = date("Y-m-d");
		$stmt->bindParam(":tid", $token);
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