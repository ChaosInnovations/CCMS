<?php

function ajax_collab_update() {
	global $conn;
	global $authuser;
	
	// Watchdog keepalive
	$stmt = $conn->prepare("UPDATE users SET collab_status=10 WHERE uid=:uid;");
	$stmt->bindParam(":uid", $authuser->uid);
	$stmt->execute();
	
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
	$newdate = date("Y-m-d H:i:00", time()+60*5); // + 5 minutes
	$stmt = $conn->prepare("INSERT INTO schedule (after, function, args) VALUES (:next, 'collab_watchdog', :args);");
	$stmt->bindParam(":next", $newdate);
	$stmt->bindParam(":args", json_encode($args));
	$stmt->execute();
	// will run every 2 seconds for 5 minutes (150 times).
	$start = microtime(true);
	set_time_limit(300);
	for ($i = 0; $i < 149; ++$i) {
		$stmt = $conn->prepare("UPDATE users SET collab_status=collab_status-1 WHERE collab_status>0;");
		$stmt->execute();
		time_sleep_until($start + $i + 2);
	}
}

?>