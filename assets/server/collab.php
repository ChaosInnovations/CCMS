<?php

function notify($uid, $what) {
	global $conn, $authuser; // authuser is sender
	if ($authuser->uid == $uid) {
		return;
	}
	$recvuser = new AuthUser($uid);
	$what .= ";";
	$stmt = $conn->prepare("UPDATE users SET collab_notifs = CONCAT(`collab_notifs`,:what) WHERE uid=:uid;");
	$stmt->bindParam(":what", $what);
	$stmt->bindParam(":uid", $uid);
	$stmt->execute();
	
	if ($authuser->online) {
		// Don't email if online already
		return;
	}
	
	$nType = substr($what, 0, 1);
	
	if ($nType == "R" || $nType == "U") {
		// Chat
		$body = $TEMPLATES["email-notif-chat"]($authuser->name, $recvuser->name);
		$mail = $notifMailer->compose([[$recvuser->email, $recvuser->name]], "{$recvuser->name} sent a message", $body, "");
		$mail->send()
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

function ajax_collab_update() {
	global $conn;
	global $authuser;
	
	// Keepalive
	$stmt = $conn->prepare("UPDATE users SET collab_lastseen=UTC_TIMESTAMP WHERE uid=:uid;");
	$stmt->bindParam(":uid", $authuser->uid);
	$stmt->execute();
	
	// Send message
	if (isset($_POST["message"])) {
		$now = date("Y-m-d H:i:s", time() - 3600 * 9);
		$stmt = $conn->prepare("INSERT INTO collab_chat (chat_from, chat_to, chat_body, chat_sent) VALUES (:uid, :to, :msg, :now);");
		$stmt->bindParam(":uid", $authuser->uid);
		$stmt->bindParam(":to", $_POST["chat"]);
		$stmt->bindParam(":msg", $_POST["message"]);
		$stmt->bindParam(":now", $now);
		$stmt->execute();
		// Notify members
		if (substr($_POST["chat"], 0, 1) == "R") {
			// multiple members
			$rid = substr($_POST["chat"], 1);
			$stmt = $conn->prepare("SELECT room_members FROM collab_rooms WHERE room_id=:rid;");
			$stmt->bindParam(":rid", $rid);
			$stmt->execute();$stmt->setFetchMode(PDO::FETCH_ASSOC);
			$room = $stmt->fetchAll()[0];
			if ($room["room_members"] == "*") {
				$stmt = $conn->prepare("SELECT uid FROM users;");
				$stmt->execute();$stmt->setFetchMode(PDO::FETCH_ASSOC);
				foreach($stmt->fetchAll() as $u) {
					notify($u["uid"], "R" . $rid);
				}
			} else {
				$members = explode(";", $room["room_members"]);
				foreach($members as $m) {
					notify($m, "R" . $rid);
				}
			}
		} else {
			$uid = substr($_POST["chat"], 1);
			notify($uid, "U" . $authuser->uid);
		}
	}
	
	// Add entry
	if (isset($_POST["entry"])) {
		$stmt = $conn->prepare("INSERT INTO collab_todo (list_id, todo_label, todo_done) VALUES (:lid, :label, 0);");
		$stmt->bindParam(":lid", $_POST["list"]);
		$stmt->bindParam(":label", $_POST["entry"]);
		$stmt->execute();
		// Notify members
		$stmt = $conn->prepare("SELECT list_participants FROM collab_lists WHERE list_id=:lid;");
		$stmt->bindParam(":lid", $_POST["list"]);
		$stmt->execute();$stmt->setFetchMode(PDO::FETCH_ASSOC);
		$list = $stmt->fetchAll()[0];
		if ($list["list_participants"] == "*") {
			$stmt = $conn->prepare("SELECT uid FROM users;");
			$stmt->execute();$stmt->setFetchMode(PDO::FETCH_ASSOC);
			foreach($stmt->fetchAll() as $u) {
				notify($u["uid"], "L" . $_POST["list"]);
			}
		} else {
			$members = explode(";", $list["list_participants"]);
			foreach($members as $m) {
				notify($m, "L" . $_POST["list"]);
			}
		}
	}
	
	// Check entry
	if (isset($_POST["check_entry"])) {
		$stmt = $conn->prepare("UPDATE collab_todo SET todo_done=!todo_done WHERE todo_id=:tid;");
		$stmt->bindParam(":tid", $_POST["check_entry"]);
		$stmt->execute();
		// Notify members
		$stmt = $conn->prepare("SELECT list_participants FROM collab_lists WHERE list_id=:lid;");
		$stmt->bindParam(":lid", $_POST["list"]);
		$stmt->execute();$stmt->setFetchMode(PDO::FETCH_ASSOC);
		$list = $stmt->fetchAll()[0];
		if ($list["list_participants"] == "*") {
			$stmt = $conn->prepare("SELECT uid FROM users;");
			$stmt->execute();$stmt->setFetchMode(PDO::FETCH_ASSOC);
			foreach($stmt->fetchAll() as $u) {
				notify($u["uid"], "L" . $_POST["list"]);
			}
		} else {
			$members = explode(";", $list["list_participants"]);
			foreach($members as $m) {
				notify($m, "L" . $_POST["list"]);
			}
		}
	}
	
	// Delete entry
	if (isset($_POST["delete_entry"])) {
		$stmt = $conn->prepare("DELETE FROM collab_todo WHERE todo_id=:tid;");
		$stmt->bindParam(":tid", $_POST["delete_entry"]);
		$stmt->execute();
		// Notify members
		$stmt = $conn->prepare("SELECT list_participants FROM collab_lists WHERE list_id=:lid;");
		$stmt->bindParam(":lid", $_POST["list"]);
		$stmt->execute();$stmt->setFetchMode(PDO::FETCH_ASSOC);
		$list = $stmt->fetchAll()[0];
		if ($list["list_participants"] == "*") {
			$stmt = $conn->prepare("SELECT uid FROM users;");
			$stmt->execute();$stmt->setFetchMode(PDO::FETCH_ASSOC);
			foreach($stmt->fetchAll() as $u) {
				notify($u["uid"], "L" . $_POST["list"]);
			}
		} else {
			$members = explode(";", $list["list_participants"]);
			foreach($members as $m) {
				notify($m, "L" . $_POST["list"]);
			}
		}
	}
	
	// Add room or list
	if (isset($_POST["new_type"]) && isset($_POST["new_id"]) && isset($_POST["new_members"]) && isset($_POST["new_name"])) {
		if ($_POST["new_type"] == "list") {
			$stmt = $conn->prepare("INSERT INTO collab_lists (list_id, list_name, list_participants) VALUES (:id, :name, :members);");
		} else {
			$stmt = $conn->prepare("INSERT INTO collab_rooms (room_id, room_name, room_members) VALUES (:id, :name, :members);");
		}
		$stmt->bindParam(":id", $_POST["new_id"]);
		$stmt->bindParam(":name", $_POST["new_name"]);
		$stmt->bindParam(":members", $_POST["new_members"]);
		$stmt->execute();
		// Notify members
		if ($_POST["new_members"] == "*") {
			$stmt = $conn->prepare("SELECT uid FROM users;");
			$stmt->execute();$stmt->setFetchMode(PDO::FETCH_ASSOC);
			foreach($stmt->fetchAll() as $u) {
				notify($u["uid"], ($_POST["new_type"] == "list"?"L":"R") . $_POST["new_id"]);
			}
		} else {
			$members = explode(";", $_POST["new_members"]);
			foreach($members as $m) {
				notify($m, ($_POST["new_type"] == "list"?"L":"R") . $_POST["new_id"]);
			}
		}
	}
	
	// update object
	$update = ["users"=>[],"rooms"=>[],"lists"=>[],"todo"=>[],"chat"=>[],"notifs"=>[]];
	
	// User statuses
	$stmt = $conn->prepare("SELECT uid, collab_lastseen, collab_pageid FROM users;");
	$stmt->execute();$stmt->setFetchMode(PDO::FETCH_ASSOC);
	$users = $stmt->fetchAll();
	foreach($users as $user) {
		$elapsed = strtotime("now") - strtotime($user["collab_lastseen"]);
		$elapsed_minutes = floor($elapsed / 60);
		$elapsed_hours = floor($elapsed_minutes / 60);
		$elapsed_days = floor($elapsed_hours / 24);
		$elapsed_weeks = floor($elapsed_days / 7);
		$ls2 = "a moment ago";
		if ($elapsed >= 60) {
			$ls2 = "" . $elapsed_minutes . " minute" . ($elapsed_minutes==1?"":"s") . " ago";
		}
		if ($elapsed_minutes >= 60) {
			$ls2 = "" . $elapsed_hours . " hour" . ($elapsed_hours==1?"":"s") . " ago";
		}
		if ($elapsed_hours >= 24) {
			$ls2 = "" . $elapsed_days . " day" . ($elapsed_days==1?"":"s") . " ago";
		}
		if ($elapsed_days >= 7) {
			$ls2 = "" . $elapsed_weeks . " week" . ($elapsed_weeks==1?"":"s") . " ago";
		}
		if ($elapsed_weeks >= 100) {
			$ls2 = "a long time ago";
		}
		$data = ["uid"=>$user["uid"],"online"=>strtotime($user["collab_lastseen"])>strtotime("now")-10,"lastseen"=>$user["collab_lastseen"],"lastseen_informal"=>$ls2,"page_id"=>$user["collab_pageid"],"page_title"=>page_title($user["collab_pageid"])];
		array_push($update["users"], $data);
	}
	
	// Room statuses
	$stmt = $conn->prepare("SELECT room_id, room_name, room_members FROM collab_rooms;");
	$stmt->execute();$stmt->setFetchMode(PDO::FETCH_ASSOC);
	$rooms = $stmt->fetchAll();
	foreach($rooms as $room) {
		
		$numMembers = count(explode(";", $room["room_members"]));
		$numOnline = 0;
		if ($room["room_members"] == "*") {
			$stmt = $conn->prepare("SELECT uid FROM users;");
			$stmt->execute();$stmt->setFetchMode(PDO::FETCH_ASSOC);
			$numMembers = count($stmt->fetchAll());
			$stmt = $conn->prepare("SELECT uid FROM users WHERE collab_lastseen>SUBTIME(UTC_TIMESTAMP, '0:0:10');");
			$stmt->execute();$stmt->setFetchMode(PDO::FETCH_ASSOC);
			$numOnline = count($stmt->fetchAll());
		} else {
			$members = explode(";", $room["room_members"]);
			if (!in_array($authuser->uid, $members)) {
				continue;
			}
			$stmt = $conn->prepare("SELECT uid FROM users WHERE collab_lastseen>SUBTIME(UTC_TIMESTAMP, '0:0:10');");
			$stmt->execute();$stmt->setFetchMode(PDO::FETCH_ASSOC);
			$users = $stmt->fetchAll();
			foreach ($users as $user) {
				if(in_array($user["uid"], $members)) {
					$numOnline++;
				}
			}
		}
		
		$data = ["rid"=>$room["room_id"],"name"=>$room["room_name"],"on"=>$numOnline,"members"=>$numMembers];
		array_push($update["rooms"], $data);
	}
	
	// List statuses
	$stmt = $conn->prepare("SELECT list_id, list_name, list_participants FROM collab_lists;");
	$stmt->execute();$stmt->setFetchMode(PDO::FETCH_ASSOC);
	$lists = $stmt->fetchAll();
	foreach($lists as $list) {
		if ($list["list_participants"] != "*" && !in_array($authuser->uid, explode(";", $list["list_participants"]))) {
			continue;
		}
		
		$stmt = $conn->prepare("SELECT todo_id FROM collab_todo WHERE list_id=:lid;");
		$stmt->bindParam(":lid", $list["list_id"]);
		$stmt->execute();$stmt->setFetchMode(PDO::FETCH_ASSOC);
		$numTasks = count($stmt->fetchAll());
		$stmt = $conn->prepare("SELECT todo_id FROM collab_todo WHERE list_id=:lid AND todo_done=1;");
		$stmt->bindParam(":lid", $list["list_id"]);
		$stmt->execute();$stmt->setFetchMode(PDO::FETCH_ASSOC);
		$numComplete = count($stmt->fetchAll());
		
		$data = ["lid"=>$list["list_id"],"name"=>$list["list_name"],"done"=>$numComplete,"tasks"=>$numTasks];
		array_push($update["lists"], $data);
	}
	
	// Chat
	if (strlen($_POST["chat"]) == 33) {
		if (substr($_POST["chat"], 0, 1) == "R") {
			$stmt = $conn->prepare("SELECT chat_id, chat_from, chat_body, chat_sent FROM collab_chat WHERE chat_to=:cid ORDER BY chat_sent DESC LIMIT 60;");
		} else {
			$otherUid = substr($_POST["chat"], 1);
			$ucid = "U" . $authuser->uid;
			$stmt = $conn->prepare("SELECT chat_id, chat_from, chat_body, chat_sent FROM collab_chat WHERE (chat_to=:cid AND chat_from=:uid) OR (chat_to=:ucid AND chat_from=:ouid) ORDER BY chat_sent DESC LIMIT 60;");
			$stmt->bindParam(":ucid", $ucid);
			$stmt->bindParam(":ouid", $otherUid);
			$stmt->bindParam(":uid", $authuser->uid);
		}
		$stmt->bindParam(":cid", $_POST["chat"]);
		$stmt->execute();$stmt->setFetchMode(PDO::FETCH_ASSOC);
		$messages = array_reverse($stmt->fetchAll());
		unnotify($authuser->uid, $_POST["chat"]);
		
		foreach ($messages as $message) {
			$stmt = $conn->prepare("SELECT name FROM users WHERE uid=:uid;");
			$stmt->bindParam(":uid", $message["chat_from"]);
			$stmt->execute();$stmt->setFetchMode(PDO::FETCH_ASSOC);
			$names = $stmt->fetchAll();
			$name = (count($names) == 1?$names[0]["name"]:"Unknown");
			$type = ($message["chat_from"] == $authuser->uid ? "sent":"received");
			$sent = date("g:ia F j, Y", strtotime($message["chat_sent"]));
			$elapsed = (time() - 3600 * 9) - strtotime($message["chat_sent"]);
			$elapsed_minutes = floor($elapsed / 60);
			$elapsed_hours = floor($elapsed_minutes / 60);
			$elapsed_days = floor($elapsed_hours / 24);
			$elapsed_weeks = floor($elapsed_days / 7);
			$sent2 = "just now";
			if ($elapsed >= 60) {
				$sent2 = "" . $elapsed_minutes . " minute" . ($elapsed_minutes==1?"":"s") . " ago";
			}
			if ($elapsed_minutes >= 60) {
				$sent2 = "" . $elapsed_hours . " hour" . ($elapsed_hours==1?"":"s") . " ago";
			}
			if ($elapsed_hours >= 24) {
				$sent2 = "" . $elapsed_days . " day" . ($elapsed_days==1?"":"s") . " ago";
			}
			if ($elapsed_days >= 7) {
				$sent2 = "" . $elapsed_weeks . " week" . ($elapsed_weeks==1?"":"s") . " ago";
			}
			$data = ["type"=>$type,"id"=>$message["chat_id"],"body"=>$message["chat_body"],"from"=>$name,"sent"=>$sent,"sent_informal"=>$sent2];
		    array_push($update["chat"], $data);
		}
	}
	
	// Todo list
	if (strlen($_POST["list"]) == 32) {
		$stmt = $conn->prepare("SELECT todo_id, todo_label, todo_done FROM collab_todo WHERE list_id=:lid ORDER BY todo_id ASC;");
		$stmt->bindParam(":lid", $_POST["list"]);
		$stmt->execute();$stmt->setFetchMode(PDO::FETCH_ASSOC);
		$entries= $stmt->fetchAll();
		
		unnotify($authuser->uid, "L" . $_POST["list"]);
		
		foreach ($entries as $entry) {
			$data = ["id"=>$entry["todo_id"],"label"=>$entry["todo_label"],"done"=>$entry["todo_done"]];
		    array_push($update["todo"], $data);
		}
	}
	
	// Get notifications
	$stmt = $conn->prepare("SELECT collab_notifs FROM users WHERE uid=:uid;");
	$stmt->bindParam(":uid", $authuser->uid);
	$stmt->execute();$stmt->setFetchMode(PDO::FETCH_ASSOC);
	foreach (explode(";", $stmt->fetchAll()[0]["collab_notifs"]) as $notif) {
		if ($notif == "") {
			continue;
		}
		array_push($update["notifs"], $notif);
	}
	$defaultPasswordHash = hash("sha512", "password");
	$stmt = $conn->prepare("SELECT uid FROM access WHERE uid=:uid AND pwd=:pwd;");
	$stmt->bindParam(":uid", $authuser->uid);
	$stmt->bindParam(":pwd", $defaultPasswordHash);
	$stmt->execute();$stmt->setFetchMode(PDO::FETCH_ASSOC);
	if (count($stmt->fetchAll()) == 1) {
		array_push($update["notifs"], "pwd");
	}
	
	return json_encode($update);
}

?>