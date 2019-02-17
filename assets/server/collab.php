<?php

use \Lib\CCMS\Page;

function notify($uid, $what) {
	global $conn, $authuser, $notifMailer, $TEMPLATES; // authuser is sender
	if ($authuser->uid == $uid) {
		return;
	}
	$recvuser = new User($uid);
	$what .= ";";
	$stmt = $conn->prepare("UPDATE users SET collab_notifs = CONCAT(`collab_notifs`,:what) WHERE uid=:uid;");
	$stmt->bindParam(":what", $what);
	$stmt->bindParam(":uid", $uid);
	$stmt->execute();
	
	if ($recvuser->online || !$recvuser->notify) {
		// Don't email if online already or has disabled email notifications/within notification cooldown.
		return;
	}
	
	// Reset recipient's notification cooldown
	$stmt = $conn->prepare("UPDATE users SET last_notif=UTC_TIMESTAMP WHERE uid=:uid;");
	$stmt->bindParam(":uid", $recvuser->uid);
	$stmt->execute();
	
	$nType = substr($what, 0, 1);
	
	if ($nType == "R" || $nType == "U") {
		// Chat
		$rn = "";
		if ($nType == "U") {
			$rn = "you";
		}
		else
		{
			$rid = substr($what, 1, strlen($what)-2);
			$stmt = $conn->prepare("SELECT room_name FROM collab_rooms WHERE room_id=:rid;");
			$stmt->bindParam(":rid", $rid);
			$stmt->execute();$stmt->setFetchMode(PDO::FETCH_ASSOC);
			$rn = $stmt->fetchAll()[0]["room_name"];
		}
		$body = $TEMPLATES["email-notif-chat"]($authuser->name, $rn);
		$oldFrom = $notifMailer->from;
		$notifMailer->from = $authuser->name;
		$mail = $notifMailer->compose([[$recvuser->email, $recvuser->name]], "{$authuser->name} sent a message", $body, "");
		$mail->send();
		$notifMailer->from = $oldFrom;
	}
	
	//$body = $TEMPLATES["email-notif-{$type}"]();
	//$mail = $notifMailer->compose([[$email, $name]], $subject, $body, "");
	//$mail->send()
	
}

function unnotify($uid, $what) {
	global $conn;
	$what .= ";";
	$stmt = $conn->prepare("UPDATE users SET collab_notifs = REPLACE(`collab_notifs`,:what,'') WHERE uid=:uid;");
	$stmt->bindParam(":what", $what);
	$stmt->bindParam(":uid", $uid);
	$stmt->execute();
}

?>