<?php

namespace Mod\Collaboration;

use \Mod\Database;
use \Mod\FireSock\ISubscription;
use \Mod\Page;
use \Mod\User;
use \PDO;

class CollaborationSubscription implements ISubscription {
    private $hook = "";
    private $server = null;
    private $user = null;

    private $lastTickTime = 0;
    private $updateTickInterval = 5;

    private $lastArgs = [];

    public function __construct($server, $user, $hook) {
        $this->server = $server;
        $this->user = $user;
        $this->hook = $hook;

        $this->server->send($this->user, "{$hook} ok");
    }

    public function processMessage($message) {
        $args = json_decode($message, true);
        $this->lastArgs = $args;

        if (!isset($this->user->subscriptions["user"])) {
            $this->server->send($this->user, "{$hook} fail");
            return;
        }

        if (!isset($this->user->subscriptions["user"]->userObject) || !$this->user->subscriptions["user"]->userObject) {
            $this->server->send($this->user, "{$hook} fail");
            return;
        }

        if (!$this->user->subscriptions["user"]->userObject->isValidUser()) {
            $this->server->send($this->user, "{$hook} fail");
            return;
        }

        $currentUser = $this->user->subscriptions["user"]->userObject;
        
        // Send message
        if (isset($args["message"])) {
            $now = date("Y-m-d H:i:s", time() - 3600 * 9);
            $stmt = Database::Instance()->prepare("INSERT INTO collab_chat (chat_from, chat_to, chat_body, chat_sent) VALUES (:uid, :to, :msg, :now);");
            $stmt->bindParam(":uid", $currentUser->uid);
            $stmt->bindParam(":to", $args["chat"]);
            $stmt->bindParam(":msg", $args["message"]);
            $stmt->bindParam(":now", $now);
            $stmt->execute();
            // Notify members
            if (substr($args["chat"], 0, 1) == "R") {
                // multiple members
                $rid = substr($args["chat"], 1);
                $stmt = Database::Instance()->prepare("SELECT room_members FROM collab_rooms WHERE room_id=:rid;");
                $stmt->bindParam(":rid", $rid);
                $stmt->execute();$stmt->setFetchMode(PDO::FETCH_ASSOC);
                $room = $stmt->fetchAll()[0];
                if ($room["room_members"] == "*") {
                    $stmt = Database::Instance()->prepare("SELECT uid FROM users;");
                    $stmt->execute();$stmt->setFetchMode(PDO::FETCH_ASSOC);
                    foreach($stmt->fetchAll() as $u) {
                        (new User($u["uid"]))->notify($currentUser, "R" . $rid);
                    }
                } else {
                    $members = explode(";", $room["room_members"]);
                    foreach($members as $m) {
                        (new User($m))->notify($currentUser, "R" . $rid);
                    }
                }
            } else {
                $uid = substr($args["chat"], 1);
                (new User($uid))->notify($currentUser, "U" . $currentUser->uid);
            }
        }
        
        // Add entry
        if (isset($args["entry"])) {
            $stmt = Database::Instance()->prepare("INSERT INTO collab_todo (list_id, todo_label, todo_done) VALUES (:lid, :label, 0);");
            $stmt->bindParam(":lid", $args["list"]);
            $stmt->bindParam(":label", $args["entry"]);
            $stmt->execute();
            // Notify members
            $stmt = Database::Instance()->prepare("SELECT list_participants FROM collab_lists WHERE list_id=:lid;");
            $stmt->bindParam(":lid", $args["list"]);
            $stmt->execute();$stmt->setFetchMode(PDO::FETCH_ASSOC);
            $list = $stmt->fetchAll()[0];
            if ($list["list_participants"] == "*") {
                $stmt = Database::Instance()->prepare("SELECT uid FROM users;");
                $stmt->execute();$stmt->setFetchMode(PDO::FETCH_ASSOC);
                foreach($stmt->fetchAll() as $u) {
                    (new User($u["uid"]))->notify($currentUser, "L" . $args["list"]);
                }
            } else {
                $members = explode(";", $list["list_participants"]);
                foreach($members as $m) {
                    (new User($m))->notify($currentUser, "L" . $args["list"]);
                }
            }
        }
        
        // Check entry
        if (isset($args["check_entry"])) {
            $stmt = Database::Instance()->prepare("UPDATE collab_todo SET todo_done=!todo_done WHERE todo_id=:tid;");
            $stmt->bindParam(":tid", $args["check_entry"]);
            $stmt->execute();
            // Notify members
            $stmt = Database::Instance()->prepare("SELECT list_participants FROM collab_lists WHERE list_id=:lid;");
            $stmt->bindParam(":lid", $args["list"]);
            $stmt->execute();$stmt->setFetchMode(PDO::FETCH_ASSOC);
            $list = $stmt->fetchAll()[0];
            if ($list["list_participants"] == "*") {
                $stmt = Database::Instance()->prepare("SELECT uid FROM users;");
                $stmt->execute();$stmt->setFetchMode(PDO::FETCH_ASSOC);
                foreach($stmt->fetchAll() as $u) {
                    (new User($u["uid"]))->notify($currentUser, "L" . $args["list"]);
                }
            } else {
                $members = explode(";", $list["list_participants"]);
                foreach($members as $m) {
                    (new User($m))->notify($currentUser, "L" . $args["list"]);
                }
            }
        }
        
        // Delete entry
        if (isset($args["delete_entry"])) {
            $stmt = Database::Instance()->prepare("DELETE FROM collab_todo WHERE todo_id=:tid;");
            $stmt->bindParam(":tid", $args["delete_entry"]);
            $stmt->execute();
            // Notify members
            $stmt = Database::Instance()->prepare("SELECT list_participants FROM collab_lists WHERE list_id=:lid;");
            $stmt->bindParam(":lid", $args["list"]);
            $stmt->execute();$stmt->setFetchMode(PDO::FETCH_ASSOC);
            $list = $stmt->fetchAll()[0];
            if ($list["list_participants"] == "*") {
                $stmt = Database::Instance()->prepare("SELECT uid FROM users;");
                $stmt->execute();$stmt->setFetchMode(PDO::FETCH_ASSOC);
                foreach($stmt->fetchAll() as $u) {
                    (new User($u["uid"]))->notify($currentUser, "L" . $args["list"]);
                }
            } else {
                $members = explode(";", $list["list_participants"]);
                foreach($members as $m) {
                    (new User($m))->notify($currentUser, "L" . $args["list"]);
                }
            }
        }
        
        // Add room or list
        if (isset($args["new_type"]) && isset($args["new_id"]) && isset($args["new_members"]) && isset($args["new_name"])) {
            if ($args["new_type"] == "list") {
                $stmt = Database::Instance()->prepare("INSERT INTO collab_lists (list_id, list_name, list_participants) VALUES (:id, :name, :members);");
            } else {
                $stmt = Database::Instance()->prepare("INSERT INTO collab_rooms (room_id, room_name, room_members) VALUES (:id, :name, :members);");
            }
            $stmt->bindParam(":id", $args["new_id"]);
            $stmt->bindParam(":name", $args["new_name"]);
            $stmt->bindParam(":members", $args["new_members"]);
            $stmt->execute();
            // Notify members
            if ($args["new_members"] == "*") {
                $stmt = Database::Instance()->prepare("SELECT uid FROM users;");
                $stmt->execute();$stmt->setFetchMode(PDO::FETCH_ASSOC);
                foreach($stmt->fetchAll() as $u) {
                    (new User($u["uid"]))->notify($currentUser, ($args["new_type"] == "list"?"L":"R") . $args["new_id"]);
                }
            } else {
                $members = explode(";", $args["new_members"]);
                foreach($members as $m) {
                    (new User($m))->notify($currentUser, ($args["new_type"] == "list"?"L":"R") . $args["new_id"]);
                }
            }
        }

        $this->tick(true);
    }

    public function tick($forceUpdate=false) {
        $args = $this->lastArgs;

        if (!isset($this->user->subscriptions["user"])) {
            return;
        }

        $currentUser = $this->user->subscriptions["user"]->userObject;

        // Keepalive
        $stmt = Database::Instance()->prepare("UPDATE users SET collab_lastseen=UTC_TIMESTAMP WHERE uid=:uid;");
        $stmt->bindParam(":uid", $currentUser->uid);
        $stmt->execute();

        // update object
        $update = ["users"=>[],"rooms"=>[],"lists"=>[],"todo"=>[],"chat"=>[],"notifs"=>[]];
        
        $now = time();
        $timeSinceLastTick = $now - $this->lastTickTime;
        $isUpdateTick = $timeSinceLastTick >= $this->updateTickInterval;

        if ($forceUpdate || $isUpdateTick) {
            $this->lastTickTime = $now;
            // User statuses
            $stmt = Database::Instance()->prepare("SELECT uid, collab_lastseen, collab_pageid FROM users;");
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
                $data = ["uid"=>$user["uid"],"online"=>strtotime($user["collab_lastseen"])>strtotime("now")-30,"lastseen"=>$user["collab_lastseen"],"lastseen_informal"=>$ls2,"page_id"=>$user["collab_pageid"],"page_title"=>Page::getTitleFromId($user["collab_pageid"])];

                array_push($update["users"], $data);
            }

            // Room statuses
            $stmt = Database::Instance()->prepare("SELECT room_id, room_name, room_members FROM collab_rooms;");
            $stmt->execute();$stmt->setFetchMode(PDO::FETCH_ASSOC);
            $rooms = $stmt->fetchAll();
            foreach($rooms as $room) {
                
                $numMembers = count(explode(";", $room["room_members"]));
                $numOnline = 0;
                if ($room["room_members"] == "*") {
                    $stmt = Database::Instance()->prepare("SELECT uid FROM users;");
                    $stmt->execute();$stmt->setFetchMode(PDO::FETCH_ASSOC);
                    $numMembers = count($stmt->fetchAll());
                    $stmt = Database::Instance()->prepare("SELECT uid FROM users WHERE collab_lastseen>SUBTIME(UTC_TIMESTAMP, '0:0:10');");
                    $stmt->execute();$stmt->setFetchMode(PDO::FETCH_ASSOC);
                    $numOnline = count($stmt->fetchAll());
                } else {
                    $members = explode(";", $room["room_members"]);
                    if (!in_array($currentUser->uid, $members)) {
                        continue;
                    }
                    $stmt = Database::Instance()->prepare("SELECT uid FROM users WHERE collab_lastseen>SUBTIME(UTC_TIMESTAMP, '0:0:10');");
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
            $stmt = Database::Instance()->prepare("SELECT list_id, list_name, list_participants FROM collab_lists;");
            $stmt->execute();$stmt->setFetchMode(PDO::FETCH_ASSOC);
            $lists = $stmt->fetchAll();
            foreach($lists as $list) {
                if ($list["list_participants"] != "*" && !in_array($currentUser->uid, explode(";", $list["list_participants"]))) {
                    continue;
                }
                
                $stmt = Database::Instance()->prepare("SELECT todo_id FROM collab_todo WHERE list_id=:lid;");
                $stmt->bindParam(":lid", $list["list_id"]);
                $stmt->execute();$stmt->setFetchMode(PDO::FETCH_ASSOC);
                $numTasks = count($stmt->fetchAll());
                $stmt = Database::Instance()->prepare("SELECT todo_id FROM collab_todo WHERE list_id=:lid AND todo_done=1;");
                $stmt->bindParam(":lid", $list["list_id"]);
                $stmt->execute();$stmt->setFetchMode(PDO::FETCH_ASSOC);
                $numComplete = count($stmt->fetchAll());
                
                $data = ["lid"=>$list["list_id"],"name"=>$list["list_name"],"done"=>$numComplete,"tasks"=>$numTasks];
                array_push($update["lists"], $data);
            }
        //}

        // Chat
        if (isset($args["chat"]) && strlen($args["chat"]) == 33) {
            if (substr($args["chat"], 0, 1) == "R") {
                $stmt = Database::Instance()->prepare("SELECT chat_id, chat_from, chat_body, chat_sent FROM collab_chat WHERE chat_to=:cid ORDER BY chat_sent DESC LIMIT 60;");
            } else {
                $otherUid = substr($args["chat"], 1);
                $ucid = "U" . $currentUser->uid;
                $stmt = Database::Instance()->prepare("SELECT chat_id, chat_from, chat_body, chat_sent FROM collab_chat WHERE (chat_to=:cid AND chat_from=:uid) OR (chat_to=:ucid AND chat_from=:ouid) ORDER BY chat_sent DESC LIMIT 60;");
                $stmt->bindParam(":ucid", $ucid);
                $stmt->bindParam(":ouid", $otherUid);
                $stmt->bindParam(":uid", $currentUser->uid);
            }
            $stmt->bindParam(":cid", $args["chat"]);
            $stmt->execute();$stmt->setFetchMode(PDO::FETCH_ASSOC);
            $messages = array_reverse($stmt->fetchAll());
            $currentUser->unnotify($args["chat"]);
            
            foreach ($messages as $message) {
                $stmt = Database::Instance()->prepare("SELECT name FROM users WHERE uid=:uid;");
                $stmt->bindParam(":uid", $message["chat_from"]);
                $stmt->execute();$stmt->setFetchMode(PDO::FETCH_ASSOC);
                $names = $stmt->fetchAll();
                $name = (count($names) == 1?$names[0]["name"]:"Unknown");
                $type = ($message["chat_from"] == $currentUser->uid ? "sent":"received");
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
        if (isset($args["list"]) && strlen($args["list"]) == 32) {
            $stmt = Database::Instance()->prepare("SELECT todo_id, todo_label, todo_done FROM collab_todo WHERE list_id=:lid ORDER BY todo_id ASC;");
            $stmt->bindParam(":lid", $args["list"]);
            $stmt->execute();$stmt->setFetchMode(PDO::FETCH_ASSOC);
            $entries= $stmt->fetchAll();
            
            $currentUser->unnotify("L" . $args["list"]);
            
            foreach ($entries as $entry) {
                $data = ["id"=>$entry["todo_id"],"label"=>$entry["todo_label"],"done"=>$entry["todo_done"]];
                array_push($update["todo"], $data);
            }
        }

        // Get notifications
        $stmt = Database::Instance()->prepare("SELECT collab_notifs FROM users WHERE uid=:uid;");
        $stmt->bindParam(":uid", $currentUser->uid);
        $stmt->execute();$stmt->setFetchMode(PDO::FETCH_ASSOC);
        $results = $stmt->fetchAll();
        if (count($results) > 0) {
            foreach (explode(";", $results[0]["collab_notifs"]) as $notif) {
                if ($notif == "") {
                    continue;
                }
                array_push($update["notifs"], $notif);
            }
        }

        $this->server->send($this->user, "{$this->hook} " . json_encode($update));
        }
    }
}