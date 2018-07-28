<?php

function ajax_collab_update() {
	global $conn;
	global $authuser;
	
	// Watchdog keepalive
	$stmt = $conn->prepare("UPDATE users SET collab_status=10 WHERE uid=:uid;");
	$stmt->bindParam(":uid", $authuser->uid);
	$stmt->execute();
	
	// Send message?
	if (isset($_POST["message"])) {
		$now = date("Y-m-d H:i:s", time() - 3600 * 9);
		$stmt = $conn->prepare("INSERT INTO collab_chat (chat_from, chat_to, chat_body, chat_sent) VALUES (:uid, :to, :msg, :now);");
		$stmt->bindParam(":uid", $authuser->uid);
		$stmt->bindParam(":to", $_POST["chat"]);
		$stmt->bindParam(":msg", $_POST["message"]);
		$stmt->bindParam(":now", $now);
		$stmt->execute();
	}
	
	// update object
	$update = ["users"=>[],"rooms"=>[],"lists"=>[],"todo"=>[],"chat"=>[]];
	
	// User statuses
	$stmt = $conn->prepare("SELECT uid, collab_status, collab_pageid FROM users;");
	$stmt->execute();$stmt->setFetchMode(PDO::FETCH_ASSOC);
	$users = $stmt->fetchAll();
	foreach($users as $user) {
		$data = ["uid"=>$user["uid"],"status"=>$user["collab_status"],"page_id"=>$user["collab_pageid"],"page_title"=>page_title($user["collab_pageid"])];
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
			$stmt = $conn->prepare("SELECT uid FROM users WHERE collab_status>0;");
			$stmt->execute();$stmt->setFetchMode(PDO::FETCH_ASSOC);
			$numOnline = count($stmt->fetchAll());
		} else {
			$members = explode(";", $room["room_members"]);
			if (!in_array($authuser->uid, $members)) {
				continue;
			}
			$stmt = $conn->prepare("SELECT uid FROM users WHERE collab_status>0;");
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
			$stmt = $conn->prepare("SELECT chat_id, chat_from, chat_body, chat_sent FROM collab_chat WHERE chat_to=:cid OR (chat_to=:ucid AND chat_from=:ouid) ORDER BY chat_sent DESC LIMIT 60;");
			$stmt->bindParam(":ucid", $ucid);
			$stmt->bindParam(":ouid", $otherUid);
		}
		$stmt->bindParam(":cid", $_POST["chat"]);
		$stmt->execute();$stmt->setFetchMode(PDO::FETCH_ASSOC);
		$messages = array_reverse($stmt->fetchAll());
		
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
	
	return json_encode($update);
}

function ajax_collab_watchdog() {
	global $conn;
	// will run every 2 seconds for 5 minutes (150 times).
	$start = microtime(true);
	set_time_limit(300);
	for ($i = 0; $i < 149; ++$i) {
		$stmt = $conn->prepare("UPDATE users SET collab_status=collab_status-1 WHERE collab_status>0;");
		$stmt->execute();
		time_sleep_until($start + $i + 2);
	}
}

function collab_watchdog($args) {
	global $conn;
	$newdate = date("Y-m-d H:i:00", time()+60-60*60*5); // + 5 minutes
	$stmt = $conn->prepare("INSERT INTO schedule (after, function, args) VALUES (:next, 'collab_watchdog', :args);");
	$stmt->bindParam(":next", $newdate);
	$stmt->bindParam(":args", json_encode($args));
	$stmt->execute();
	// will run every 2 seconds for 1 minute (30 times).
	$start = microtime(true);
	set_time_limit(60);
	for ($i = 0; $i < 29; ++$i) {
		$stmt = $conn->prepare("UPDATE users SET collab_status=collab_status-1 WHERE collab_status>0;");
		$stmt->execute();
		time_sleep_until($start + $i*2 + 2);
	}
}

?>