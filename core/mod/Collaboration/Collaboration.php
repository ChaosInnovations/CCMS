<?php

namespace Mod;

use \Lib\CCMS\Response;
use \Lib\CCMS\Request;
use \Lib\CCMS\Utilities;
use \Mod\Database;
use \Mod\Page;
use \Mod\SecureMenu;
use \Mod\SecureMenu\Panel;
use \Mod\SecureMenu\PanelPage;
use \Mod\User;
use \PDO;

class Collaboration
{
    public static function hookVerifyConfiguration(Request $request)
    {
        $db = Database::Instance();

        $stmt = $db->prepare("SHOW TABLES LIKE 'collab_rooms'");
        $stmt->execute();$stmt->setFetchMode(PDO::FETCH_ASSOC);
        if (count($stmt->fetchAll())) {
            return;
        }

        $dbTemplate = file_get_contents(dirname(__FILE__) . "/templates/database.template.sql");

        $stmt = $db->prepare($dbTemplate);
        $stmt->execute();
    }

    public static function hookMenu(Request $request)
    {
        $panelTemplate = file_get_contents(dirname(__FILE__) . "/templates/CollaborationPanelContent.template.html");
        $entryTemplate = file_get_contents(dirname(__FILE__) . "/templates/CollaborationPanelEntry.template.html");
        $listBodyTemplate = file_get_contents(dirname(__FILE__) . "/templates/CollaborationPanelListBody.template.html");
        $chatBodyTemplate = file_get_contents(dirname(__FILE__) . "/templates/CollaborationPanelChatBody.template.html");
        $createBodyTemplate = file_get_contents(dirname(__FILE__) . "/templates/CollaborationPanelCreateBody.template.html");

        $collaborationPanel = new Panel("collaborate", "Collaborate", $panelTemplate, Panel::SLIDE_VERTICAL);

        $stmt = Database::Instance()->prepare("SELECT room_id, room_name FROM collab_rooms ORDER BY room_name ASC;");
        $stmt->execute();$stmt->setFetchMode(PDO::FETCH_ASSOC);
        $rooms = $stmt->fetchAll();
        $roomContent = "";
        foreach ($rooms as $room) {
            $template_vars = [
                'itemType' => "room",
                'itemId' => $room["room_id"],
                'itemName' => $room["room_name"],
                'buttonTitle' => "Open Room",
                'buttonAction' => "collab_showChat('R{$room['room_id']}', '{$room['room_name']}');",
                'buttonIcon' => "<i class=\"fas fa-comments\"></i>",
            ];
            $roomContent .= Utilities::fillTemplate($entryTemplate, $template_vars);
        }
        $roomsPage = new PanelPage("rooms", "Chat Rooms", $roomContent, PanelPage::SIZE_MD);
        $roomsPage->setLeftIcon("secureMenu_hidePage('collaborate-rooms');", "Go Back", "<i class=\"fas fa-arrow-left\"></i>");
        $roomsPage->setRightIcon("collab_startCreate('room');", "New Room", "<i class=\"fas fa-plus\"></i>");
        $collaborationPanel->addPage($roomsPage);

        $stmt = Database::Instance()->prepare("SELECT list_id, list_name FROM collab_lists;");
        $stmt->execute();$stmt->setFetchMode(PDO::FETCH_ASSOC);
        $lists = $stmt->fetchAll();
        $listContent = "";
        foreach($lists as $list) {
            $template_vars = [
                'itemType' => "list",
                'itemId' => $list["list_id"],
                'itemName' => $list["list_name"],
                'buttonTitle' => "Open List",
                'buttonAction' => "collab_showList('{$list['list_id']}', '{$list['list_name']}');",
                'buttonIcon' => "<i class=\"fas fa-clipboard\"></i>",
            ];
            $listContent .= Utilities::fillTemplate($entryTemplate, $template_vars);
        }
        $todosPage = new PanelPage("todos", "To-Do Lists", $listContent, PanelPage::SIZE_MD);
        $todosPage->setLeftIcon("secureMenu_hidePage('collaborate-todos');", "Go Back", "<i class=\"fas fa-arrow-left\"></i>");
        $todosPage->setRightIcon("collab_startCreate('list');", "New List", "<i class=\"fas fa-plus\"></i>");
        $collaborationPanel->addPage($todosPage);

        $stmt = Database::Instance()->prepare("SELECT uid, name FROM users ORDER BY name ASC;");
        $stmt->execute();$stmt->setFetchMode(PDO::FETCH_ASSOC);
        $users = $stmt->fetchAll();
        $peopleContent = "";
        $memberSelection = ""; // Used by Create page
        foreach ($users as $user) {
            $memberSelection .= '<option value="' . $user["uid"] . '">' . $user["name"] . '</option>';  // Used by Create page
            $template_vars = [
                'itemType' => "person",
                'itemId' => $user['uid'],
                'itemName' => $user['name'],
                'buttonTitle' => "Open Chat",
                'buttonAction' => "collab_showChat('U{$user['uid']}', '{$user['name']}');",
                'buttonIcon' => "<i class=\"fas fa-comment\"></i>",
            ];
            $peopleContent .= Utilities::fillTemplate($entryTemplate, $template_vars);
        }
        $peoplePage = new PanelPage("people", "People", $peopleContent, PanelPage::SIZE_MD);
        $peoplePage->setLeftIcon("secureMenu_hidePage('collaborate-people');", "Go Back", "<i class=\"fas fa-arrow-left\"></i>");
        $collaborationPanel->addPage($peoplePage);

        $listPage = new PanelPage("list", "List Name", $listBodyTemplate, PanelPage::SIZE_LG);
        $listPage->setLeftIcon("collab_hideList();", "Go Back", "<i class=\"fas fa-arrow-left\"></i>");
        $collaborationPanel->addPage($listPage);

        $chatPage = new PanelPage("chat", "Chat Name", $chatBodyTemplate, PanelPage::SIZE_LG);
        $chatPage->setLeftIcon("collab_hideChat();", "Go Back", "<i class=\"fas fa-arrow-left\"></i>");
        $collaborationPanel->addPage($chatPage);

        $createContent = Utilities::fillTemplate($createBodyTemplate, ['members' => $memberSelection]);
        $createPage = new PanelPage("create", "Create", $createContent, PanelPage::SIZE_LG);
        $createPage->setLeftIcon("secureMenu_hidePage('collaborate-create');", "Go Back", "<i class=\"fas fa-arrow-left\"></i>");
        $collaborationPanel->addPage($createPage);
        
        SecureMenu::Instance()->addEntry("collaborateTrigger", "Collaborate", "triggerPane('collaborate');", '<i class="fas fa-share-alt"></i>', SecureMenu::HORIZONTAL);
        SecureMenu::Instance()->addPanel($collaborationPanel);
    }

    public static function hookUpdate(Request $request)
    {
        // Keepalive
        $stmt = Database::Instance()->prepare("UPDATE users SET collab_lastseen=UTC_TIMESTAMP WHERE uid=:uid;");
        $stmt->bindParam(":uid", User::$currentUser->uid);
        $stmt->execute();
        
        // Send message
        if (isset($_POST["message"])) {
            $now = date("Y-m-d H:i:s", time() - 3600 * 9);
            $stmt = Database::Instance()->prepare("INSERT INTO collab_chat (chat_from, chat_to, chat_body, chat_sent) VALUES (:uid, :to, :msg, :now);");
            $stmt->bindParam(":uid", User::$currentUser->uid);
            $stmt->bindParam(":to", $_POST["chat"]);
            $stmt->bindParam(":msg", $_POST["message"]);
            $stmt->bindParam(":now", $now);
            $stmt->execute();
            // Notify members
            if (substr($_POST["chat"], 0, 1) == "R") {
                // multiple members
                $rid = substr($_POST["chat"], 1);
                $stmt = Database::Instance()->prepare("SELECT room_members FROM collab_rooms WHERE room_id=:rid;");
                $stmt->bindParam(":rid", $rid);
                $stmt->execute();$stmt->setFetchMode(PDO::FETCH_ASSOC);
                $room = $stmt->fetchAll()[0];
                if ($room["room_members"] == "*") {
                    $stmt = Database::Instance()->prepare("SELECT uid FROM users;");
                    $stmt->execute();$stmt->setFetchMode(PDO::FETCH_ASSOC);
                    foreach($stmt->fetchAll() as $u) {
                        (new User($u["uid"]))->notify("R" . $rid);
                    }
                } else {
                    $members = explode(";", $room["room_members"]);
                    foreach($members as $m) {
                        (new User($m))->notify("R" . $rid);
                    }
                }
            } else {
                $uid = substr($_POST["chat"], 1);
                (new User($uid))->notify("U" . User::$currentUser->uid);
            }
        }
        
        // Add entry
        if (isset($_POST["entry"])) {
            $stmt = Database::Instance()->prepare("INSERT INTO collab_todo (list_id, todo_label, todo_done) VALUES (:lid, :label, 0);");
            $stmt->bindParam(":lid", $_POST["list"]);
            $stmt->bindParam(":label", $_POST["entry"]);
            $stmt->execute();
            // Notify members
            $stmt = Database::Instance()->prepare("SELECT list_participants FROM collab_lists WHERE list_id=:lid;");
            $stmt->bindParam(":lid", $_POST["list"]);
            $stmt->execute();$stmt->setFetchMode(PDO::FETCH_ASSOC);
            $list = $stmt->fetchAll()[0];
            if ($list["list_participants"] == "*") {
                $stmt = Database::Instance()->prepare("SELECT uid FROM users;");
                $stmt->execute();$stmt->setFetchMode(PDO::FETCH_ASSOC);
                foreach($stmt->fetchAll() as $u) {
                    (new User($u["uid"]))->notify("L" . $_POST["list"]);
                }
            } else {
                $members = explode(";", $list["list_participants"]);
                foreach($members as $m) {
                    (new User($m))->notify("L" . $_POST["list"]);
                }
            }
        }
        
        // Check entry
        if (isset($_POST["check_entry"])) {
            $stmt = Database::Instance()->prepare("UPDATE collab_todo SET todo_done=!todo_done WHERE todo_id=:tid;");
            $stmt->bindParam(":tid", $_POST["check_entry"]);
            $stmt->execute();
            // Notify members
            $stmt = Database::Instance()->prepare("SELECT list_participants FROM collab_lists WHERE list_id=:lid;");
            $stmt->bindParam(":lid", $_POST["list"]);
            $stmt->execute();$stmt->setFetchMode(PDO::FETCH_ASSOC);
            $list = $stmt->fetchAll()[0];
            if ($list["list_participants"] == "*") {
                $stmt = Database::Instance()->prepare("SELECT uid FROM users;");
                $stmt->execute();$stmt->setFetchMode(PDO::FETCH_ASSOC);
                foreach($stmt->fetchAll() as $u) {
                    (new User($u["uid"]))->notify("L" . $_POST["list"]);
                }
            } else {
                $members = explode(";", $list["list_participants"]);
                foreach($members as $m) {
                    (new User($m))->notify("L" . $_POST["list"]);
                }
            }
        }
        
        // Delete entry
        if (isset($_POST["delete_entry"])) {
            $stmt = Database::Instance()->prepare("DELETE FROM collab_todo WHERE todo_id=:tid;");
            $stmt->bindParam(":tid", $_POST["delete_entry"]);
            $stmt->execute();
            // Notify members
            $stmt = Database::Instance()->prepare("SELECT list_participants FROM collab_lists WHERE list_id=:lid;");
            $stmt->bindParam(":lid", $_POST["list"]);
            $stmt->execute();$stmt->setFetchMode(PDO::FETCH_ASSOC);
            $list = $stmt->fetchAll()[0];
            if ($list["list_participants"] == "*") {
                $stmt = Database::Instance()->prepare("SELECT uid FROM users;");
                $stmt->execute();$stmt->setFetchMode(PDO::FETCH_ASSOC);
                foreach($stmt->fetchAll() as $u) {
                    (new User($u["uid"]))->notify("L" . $_POST["list"]);
                }
            } else {
                $members = explode(";", $list["list_participants"]);
                foreach($members as $m) {
                    (new User($m))->notify("L" . $_POST["list"]);
                }
            }
        }
        
        // Add room or list
        if (isset($_POST["new_type"]) && isset($_POST["new_id"]) && isset($_POST["new_members"]) && isset($_POST["new_name"])) {
            if ($_POST["new_type"] == "list") {
                $stmt = Database::Instance()->prepare("INSERT INTO collab_lists (list_id, list_name, list_participants) VALUES (:id, :name, :members);");
            } else {
                $stmt = Database::Instance()->prepare("INSERT INTO collab_rooms (room_id, room_name, room_members) VALUES (:id, :name, :members);");
            }
            $stmt->bindParam(":id", $_POST["new_id"]);
            $stmt->bindParam(":name", $_POST["new_name"]);
            $stmt->bindParam(":members", $_POST["new_members"]);
            $stmt->execute();
            // Notify members
            if ($_POST["new_members"] == "*") {
                $stmt = Database::Instance()->prepare("SELECT uid FROM users;");
                $stmt->execute();$stmt->setFetchMode(PDO::FETCH_ASSOC);
                foreach($stmt->fetchAll() as $u) {
                    (new User($u["uid"]))->notify(($_POST["new_type"] == "list"?"L":"R") . $_POST["new_id"]);
                }
            } else {
                $members = explode(";", $_POST["new_members"]);
                foreach($members as $m) {
                    (new User($m))->notify(($_POST["new_type"] == "list"?"L":"R") . $_POST["new_id"]);
                }
            }
        }
        
        // update object
        $update = ["users"=>[],"rooms"=>[],"lists"=>[],"todo"=>[],"chat"=>[],"notifs"=>[]];
        
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
            $data = ["uid"=>$user["uid"],"online"=>strtotime($user["collab_lastseen"])>strtotime("now")-10,"lastseen"=>$user["collab_lastseen"],"lastseen_informal"=>$ls2,"page_id"=>$user["collab_pageid"],"page_title"=>Page::getTitleFromId($user["collab_pageid"])];
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
                if (!in_array(User::$currentUser->uid, $members)) {
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
            if ($list["list_participants"] != "*" && !in_array(User::$currentUser->uid, explode(";", $list["list_participants"]))) {
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
        
        // Chat
        if (strlen($_POST["chat"]) == 33) {
            if (substr($_POST["chat"], 0, 1) == "R") {
                $stmt = Database::Instance()->prepare("SELECT chat_id, chat_from, chat_body, chat_sent FROM collab_chat WHERE chat_to=:cid ORDER BY chat_sent DESC LIMIT 60;");
            } else {
                $otherUid = substr($_POST["chat"], 1);
                $ucid = "U" . User::$currentUser->uid;
                $stmt = Database::Instance()->prepare("SELECT chat_id, chat_from, chat_body, chat_sent FROM collab_chat WHERE (chat_to=:cid AND chat_from=:uid) OR (chat_to=:ucid AND chat_from=:ouid) ORDER BY chat_sent DESC LIMIT 60;");
                $stmt->bindParam(":ucid", $ucid);
                $stmt->bindParam(":ouid", $otherUid);
                $stmt->bindParam(":uid", User::$currentUser->uid);
            }
            $stmt->bindParam(":cid", $_POST["chat"]);
            $stmt->execute();$stmt->setFetchMode(PDO::FETCH_ASSOC);
            $messages = array_reverse($stmt->fetchAll());
            User::$currentUser->unnotify($_POST["chat"]);
            
            foreach ($messages as $message) {
                $stmt = Database::Instance()->prepare("SELECT name FROM users WHERE uid=:uid;");
                $stmt->bindParam(":uid", $message["chat_from"]);
                $stmt->execute();$stmt->setFetchMode(PDO::FETCH_ASSOC);
                $names = $stmt->fetchAll();
                $name = (count($names) == 1?$names[0]["name"]:"Unknown");
                $type = ($message["chat_from"] == User::$currentUser->uid ? "sent":"received");
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
            $stmt = Database::Instance()->prepare("SELECT todo_id, todo_label, todo_done FROM collab_todo WHERE list_id=:lid ORDER BY todo_id ASC;");
            $stmt->bindParam(":lid", $_POST["list"]);
            $stmt->execute();$stmt->setFetchMode(PDO::FETCH_ASSOC);
            $entries= $stmt->fetchAll();
            
            User::$currentUser->unnotify("L" . $_POST["list"]);
            
            foreach ($entries as $entry) {
                $data = ["id"=>$entry["todo_id"],"label"=>$entry["todo_label"],"done"=>$entry["todo_done"]];
                array_push($update["todo"], $data);
            }
        }
        
        // Get notifications
        $stmt = Database::Instance()->prepare("SELECT collab_notifs FROM users WHERE uid=:uid;");
        $stmt->bindParam(":uid", User::$currentUser->uid);
        $stmt->execute();$stmt->setFetchMode(PDO::FETCH_ASSOC);
        foreach (explode(";", $stmt->fetchAll()[0]["collab_notifs"]) as $notif) {
            if ($notif == "") {
                continue;
            }
            array_push($update["notifs"], $notif);
        }
        
        $response = new Response;
        
        $response->setContent(json_encode($update));
        
        return $response;
    }
}