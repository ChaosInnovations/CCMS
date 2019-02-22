<?php

use \Lib\CCMS\Utilities;
use \Mod\Database;
use \Mod\Page;
use \Mod\User;

// TODO: groupings

$TEMPLATES = [

//  ____________
// /            \
//(  Admin Menu  )
// \____________/

"secure-menu" => function() {
    global $TEMPLATES;

    $canChat = true; // temporary chat permission

    return '
<div id="secureMenu" class="secureMenu">
  <div id="secureMenu_trigger" class="secureMenu-icon" title="Secure Menu" onclick="toggleSecureMenu();" style="z-index:10;">
    <span class="notif-badge">0</span>
    <span><i class="fas fa-ellipsis-v"></i></span>
  </div>
  <div id="secureMenu_horizontal" class="secureMenu-iconGroup horizontal" style="z-index:9;">
    <div id="secureMenu_option-signout" title="Sign Out" onclick="logout();" class="secureMenu-icon">
      <span><i class="fas fa-sign-out-alt"></i></span>
    </div>
    <div id="secureMenu_option-account" title="Account Details" onclick="showDialog(\'account\');" class="secureMenu-icon">
      <span class="notif-badge">1</span>
      <span><i class="fas fa-user-cog"></i></span>
    </div>' . ($canChat ? '
    <div id="secureMenu_option-collabTrigger" title="Collaborate" onclick="triggerPane(\'collab\');" class="secureMenu-icon">
      <span class="notif-badge">0</span>
      <span><i class="fas fa-share-alt"></i></span>
    </div>' : '') . '
    <div id="secureMenu_option-home" title="Home" onclick="location.assign(\'/\');" class="secureMenu-icon">
      <span><i class="fas fa-home"></i></span>
    </div>
  </div>
  <div id="secureMenu_vertical" class="secureMenu-iconGroup vertical" style="z-index:9;">' . (User::$currentUser->permissions->admin_managepages ? '
    <div id="secureMenu_option-admin" title="Administration" onclick="showDialog(\'admin\');" class="secureMenu-icon">
      <span><i class="fas fa-cogs"></i></span>
    </div>' : '') . (User::$currentUser->permissions->admin_managesite ? '
    <div id="secureMenu_option-moduleTrigger" title="Modules" onclick="triggerPane(\'module\');" class="secureMenu-icon">
      <span><i class="fas fa-puzzle-piece"></i></span>
    </div>' : '') . (User::$currentUser->permissions->page_viewsecure ? '
    <div id="secureMenu_option-securepageTrigger" title="Secure Pages" onclick="triggerPane(\'securepage\');" class="secureMenu-icon">
      <span><i class="fas fa-lock"></i></span>
    </div>' : '') . (User::$currentUser->permissions->page_create ? '
    <div id="secureMenu_option-newpage" title="New Page" onclick="createPage();" class="secureMenu-icon">
      <span><i class="fas fa-plus"></i></span>
    </div>' : '') . (User::$currentUser->permissions->page_edit ? '
    <div id="secureMenu_option-edit" title="Edit Page" onclick="showDialog(\'edit\');" class="secureMenu-icon">
      <span><i class="fas fa-edit"></i></span>
    </div>' : '') . '
  </div>' . ($canChat ? '
  <div id="secureMenu_pane-collab" class="secureMenu-pane collapsed vertical" style="display:none;">
    ' . $TEMPLATES["secure-collab-pane"]() . '
  </div>' : '') . '
  <script>
    var secureMenuVisible = false;
    var secureMenuLastPane = "";
    var secureMenuPane = "";
    function toggleSecureMenu() {
      secureMenuVisible = !secureMenuVisible;
      if (secureMenuVisible) {
        $("#secureMenu_horizontal").addClass("visible");
        $("#secureMenu_vertical").addClass("visible");
        $("#secureMenu_trigger").addClass("active");
        triggerPane(secureMenuLastPane);
      } else {
        secureMenuLastPane = secureMenuPane;
        triggerPane(secureMenuPane);
        $("#secureMenu_horizontal").removeClass("visible");
        $("#secureMenu_vertical").removeClass("visible");
        $("#secureMenu_trigger").removeClass("active");
      }
    }
    function triggerPane(id) {
      $(".secureMenu-pane").addClass("collapsed");
      $("#secureMenu_option-collabTrigger").removeClass("active");
      $("#secureMenu_option-moduleTrigger").removeClass("active");
      $("#secureMenu_option-securepageTrigger").removeClass("active");
      setTimeout(function(){
        if (id!="collab" || secureMenuPane=="") $("#secureMenu_pane-collab").hide();
        if (id!="module" || secureMenuPane=="") $("#secureMenu_pane-module").hide();
        if (id!="securepage" || secureMenuPane=="") $("#secureMenu_pane-securepage").hide();
      }, 500);
      if (id == secureMenuPane) {
        secureMenuPane = "";
        return;
      }
      secureMenuPane = id;
      $("#secureMenu_option-"+id+"Trigger").addClass("active");
      $("#secureMenu_pane-"+id).show();
      $("#secureMenu_pane-"+id).removeClass("collapsed");
    }
  </script>
</div>';
},

//  _____________
// /             \
//(  Collab Pane  )
// \_____________/

"secure-collab-pane-userlist" => function() {
    $list = '';

    // User statuses
    $stmt = Database::Instance()->prepare("SELECT uid, name, collab_lastseen, collab_pageid FROM users ORDER BY name ASC;");
    $stmt->execute();$stmt->setFetchMode(PDO::FETCH_ASSOC);
    $users = $stmt->fetchAll();

    foreach ($users as $user) {
        $list .= '
<div id="secureMenu_collab-person-' . $user["uid"] . '" class="collab-person">
    <div class="status '.(strtotime($user["collab_lastseen"])>strtotime("now")-10?'online':'offline'). '"></div>
    <div class="info">
        ' . ($user["uid"]==User::$currentUser->uid?'<b>':'') . $user["name"] . ($user["uid"]==User::$currentUser->uid?'</b>':'') . '<br />
        <small><i><span class="page"><a href="/' . $user["collab_pageid"] . '" title="' . Page::getTitleFromId($user["collab_pageid"]) . '">' . Page::getTitleFromId($user["collab_pageid"]) . '</a></span></i></small>
    </div>
    <button class="collab-chat" title="Open Chat" onclick="collab_showChat(\'U' . $user["uid"] . '\', \'' . $user["name"] . '\');">
        <i class="fas fa-comment"></i>
        <span class="notif-badge">0</span>
    </button>
</div>';
    }

    return $list;
},

"secure-collab-pane-roomlist" => function() {
    $list = '';

    // Room statuses
    $stmt = Database::Instance()->prepare("SELECT room_id, room_name, room_members FROM collab_rooms ORDER BY room_name ASC;");
    $stmt->execute();$stmt->setFetchMode(PDO::FETCH_ASSOC);
    $rooms = $stmt->fetchAll();

    foreach ($rooms as $room) {

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

        $list .= '
<div id="secureMenu_collab-room-' . $room["room_id"] . '" class="collab-person">
    <div class="status offline"><div class="status online" style="height:' . $numOnline*100/$numMembers . '%;"></div></div>
    <div class="info">
        ' . $room["room_name"] . '<br />
        <small><i><span class="online">' . $numOnline . '/' .$numMembers . ' online</span></i></small>
    </div>
    <button class="collab-chat" title="Open Room" onclick="collab_showChat(\'R' . $room["room_id"] . '\', \'' . $room["room_name"] . '\');">
        <i class="fas fa-comments"></i>
        <span class="notif-badge">0</span>
    </button>
</div>';

    }

    return $list;
},

"secure-collab-pane-todolist" => function() {
    $todolist = '';

    // Todo statuses
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

        $todolist .= '
<div id="secureMenu_collab-list-' . $list["list_id"] . '" class="collab-person">
    <div class="status incomplete"><div class="status complete" style="height:' . ($numTasks>0?$numComplete*100/$numTasks:0) . '%;"></div></div>
    <div class="info">
        ' . $list["list_name"] . '<br />
        <small><i><span class="done">' . $numComplete . '/' .$numTasks . ' finished</span></i></small>
    </div>
    <button class="collab-chat" title="Open List" onclick="collab_showList(\'' . $list["list_id"] . '\', \'' . $list["list_name"] . '\');">
        <i class="fas fa-clipboard-list"></i>
        <span class="notif-badge">0</span>
    </button>
</div>';
    }

    return $todolist;
},

"secure-collab-pane-createMemberList" => function() {
    $list = '';

    $stmt = Database::Instance()->prepare("SELECT uid, name FROM users ORDER BY name ASC;");
    $stmt->execute();$stmt->setFetchMode(PDO::FETCH_ASSOC);
    $users = $stmt->fetchAll();
    foreach($users as $user) {
        $list .= '<option value="' . $user["uid"] . '">' . $user["name"] . '</option>';
    }

    return $list;
},

"secure-collab-pane" => function () {
    global $TEMPLATES;
    return '
<b>Collaborate</b>
<hr />
<button class="collab-lg" onclick="collab_showPage(\'rooms\');" title="Chat Rooms">
    <i class="fas fa-comments"></i>
    <span id="secureMenu_notif-rooms" class="notif-badge">0</span>
</button><br />
<button class="collab-lg" onclick="collab_showPage(\'todo\');" title="To-Do Lists">
    <i class="fas fa-clipboard-list"></i>
    <span id="secureMenu_notif-todo" class="notif-badge">0</span>
</button><br />
<button class="collab-lg" onclick="collab_showPage(\'people\');" title="People and Direct Messages">
    <i class="fas fa-users"></i>
    <span id="secureMenu_notif-people" class="notif-badge">0</span>
</button><br />
<div id="secureMenu_collab-rooms" class="collab-page">
    <button class="collab-back" onclick="collab_hidePage(\'rooms\');" title="Go Back"><i class="fas fa-arrow-left"></i></button>
    <button class="collab-new" onclick="collab_startCreate(\'room\');" title="New Room"><i class="fas fa-plus"></i></button>
    <b>Chat Rooms</b>
    <hr />
    ' . $TEMPLATES["secure-collab-pane-roomlist"]() . '
</div>
<div id="secureMenu_collab-todo" class="collab-page">
    <button class="collab-back" onclick="collab_hidePage(\'todo\');" title="Go Back"><i class="fas fa-arrow-left"></i></button>
    <button class="collab-new" onclick="collab_startCreate(\'list\');" title="New List"><i class="fas fa-plus"></i></button>
    <b>To-Do Lists</b>
    <hr />
    ' . $TEMPLATES["secure-collab-pane-todolist"]() . '
</div>
<div id="secureMenu_collab-people" class="collab-page">
    <button class="collab-back" onclick="collab_hidePage(\'people\');" title="Go Back"><i class="fas fa-arrow-left"></i></button>
    <b>People</b>
    <hr />
    ' . $TEMPLATES["secure-collab-pane-userlist"]() . '
</div>
<div id="secureMenu_collab-list" class="collab-page">
    <div class="title">
        <b>List Name</b>
        <hr />
    </div>
    <button class="collab-back" onclick="collab_hideList();" title="Go Back"><i class="fas fa-arrow-left"></i></button>
    <div class="todos">
    </div>
    <div class="composer">
        <form onsubmit="collab_addTodo();return false;">
            <div class="input-group">
                <input type="text" id="secureMenu_collab-todo-newentry" class="form-control" placeholder="New Item..." aria-label="New Item">
                <div class="input-group-append">
                    <button class="btn btn-outline-secondary" type="submit"><i class="fas fa-plus"></i></button>
                </div>
            </div>
        </form>
    </div>
</div>
<div id="secureMenu_collab-chat" class="collab-page">
    <div class="title">
        <b>Chat Name</b>
        <hr />
    </div>
    <button class="collab-back" onclick="collab_hideChat();" title="Go Back"><i class="fas fa-arrow-left"></i></button>
    <div class="messages">
    </div>
    <div class="composer">
        <form onsubmit="collab_sendMessage();return false;">
            <div class="input-group">
                <input type="text" id="secureMenu_collab-chat-newmessage" class="form-control" placeholder="Message..." aria-label="Message">
                <div class="input-group-append">
                    <button class="btn btn-outline-secondary" type="submit"><i class="fab fa-telegram-plane"></i></button>
                </div>
            </div>
        </form>
    </div>
</div>
<div id="secureMenu_collab-create" class="collab-page">
    <button class="collab-back" onclick="collab_hidePage(\'create\');" title="Go Back"><i class="fas fa-arrow-left"></i></button>
    <b>Create</b>
    <hr />
    <form onsubmit="collab_create();return false;">
        <div class="form-group">
            <select class="form-control" id="secureMenu_collab-create-type">
                <option value="room" selected>New Room</option>
                <option value="list">New List</option>
            </select>
        </div>
        <div class="form-group">
            <label for="secureMenu_collab-create-members">Participants</label>
            <select multiple class="form-control" id="secureMenu_collab-create-members">
                <option value="*" selected>Everyone</option>
                ' . $TEMPLATES["secure-collab-pane-createMemberList"]() . '
            </select>
        </div>
        <div class="form-group">
            <label for="secureMenu_collab-create-name">Name</label>
            <input type="text" class="form-control" id="secureMenu_collab-create-name" placeholder="Name">
        </div>
        <button type="submit" title="Create" class="collab-btn">Create</button>
    </form>
</div>
<script>
    function collab_showPage(id) {
        $("#secureMenu_collab-" + id).addClass("out");
        if (id == "rooms" || id == "todo" || id == "people") {
            $("#secureMenu_pane-collab").addClass("expanded-sm");
        }
        if (id == "chat" || id == "create") {
            $("#secureMenu_pane-collab").removeClass("expanded-sm");
            $("#secureMenu_pane-collab").addClass("expanded-lg");
        }
    }
    function collab_hidePage(id) {
        $("#secureMenu_collab-" + id).removeClass("out");
        if (id == "rooms" || id == "todo" || id == "people") {
            $("#secureMenu_pane-collab").removeClass("expanded-sm");
        }
        if (id == "chat" || id == "create") {
            $("#secureMenu_pane-collab").removeClass("expanded-lg");
            $("#secureMenu_pane-collab").addClass("expanded-sm");
        }
    }
    function collab_startCreate(type) {
        $("#secureMenu_collab-create-type").val(type);
        collab_showPage("create");
    }
    function collab_md5(d){result = M(V(Y(X(d),8*d.length)));return result.toLowerCase()};function M(d){for(var _,m="0123456789ABCDEF",f="",r=0;r<d.length;r++)_=d.charCodeAt(r),f+=m.charAt(_>>>4&15)+m.charAt(15&_);return f}function X(d){for(var _=Array(d.length>>2),m=0;m<_.length;m++)_[m]=0;for(m=0;m<8*d.length;m+=8)_[m>>5]|=(255&d.charCodeAt(m/8))<<m%32;return _}function V(d){for(var _="",m=0;m<32*d.length;m+=8)_+=String.fromCharCode(d[m>>5]>>>m%32&255);return _}function Y(d,_){d[_>>5]|=128<<_%32,d[14+(_+64>>>9<<4)]=_;for(var m=1732584193,f=-271733879,r=-1732584194,i=271733878,n=0;n<d.length;n+=16){var h=m,t=f,g=r,e=i;f=md5_ii(f=md5_ii(f=md5_ii(f=md5_ii(f=md5_hh(f=md5_hh(f=md5_hh(f=md5_hh(f=md5_gg(f=md5_gg(f=md5_gg(f=md5_gg(f=md5_ff(f=md5_ff(f=md5_ff(f=md5_ff(f,r=md5_ff(r,i=md5_ff(i,m=md5_ff(m,f,r,i,d[n+0],7,-680876936),f,r,d[n+1],12,-389564586),m,f,d[n+2],17,606105819),i,m,d[n+3],22,-1044525330),r=md5_ff(r,i=md5_ff(i,m=md5_ff(m,f,r,i,d[n+4],7,-176418897),f,r,d[n+5],12,1200080426),m,f,d[n+6],17,-1473231341),i,m,d[n+7],22,-45705983),r=md5_ff(r,i=md5_ff(i,m=md5_ff(m,f,r,i,d[n+8],7,1770035416),f,r,d[n+9],12,-1958414417),m,f,d[n+10],17,-42063),i,m,d[n+11],22,-1990404162),r=md5_ff(r,i=md5_ff(i,m=md5_ff(m,f,r,i,d[n+12],7,1804603682),f,r,d[n+13],12,-40341101),m,f,d[n+14],17,-1502002290),i,m,d[n+15],22,1236535329),r=md5_gg(r,i=md5_gg(i,m=md5_gg(m,f,r,i,d[n+1],5,-165796510),f,r,d[n+6],9,-1069501632),m,f,d[n+11],14,643717713),i,m,d[n+0],20,-373897302),r=md5_gg(r,i=md5_gg(i,m=md5_gg(m,f,r,i,d[n+5],5,-701558691),f,r,d[n+10],9,38016083),m,f,d[n+15],14,-660478335),i,m,d[n+4],20,-405537848),r=md5_gg(r,i=md5_gg(i,m=md5_gg(m,f,r,i,d[n+9],5,568446438),f,r,d[n+14],9,-1019803690),m,f,d[n+3],14,-187363961),i,m,d[n+8],20,1163531501),r=md5_gg(r,i=md5_gg(i,m=md5_gg(m,f,r,i,d[n+13],5,-1444681467),f,r,d[n+2],9,-51403784),m,f,d[n+7],14,1735328473),i,m,d[n+12],20,-1926607734),r=md5_hh(r,i=md5_hh(i,m=md5_hh(m,f,r,i,d[n+5],4,-378558),f,r,d[n+8],11,-2022574463),m,f,d[n+11],16,1839030562),i,m,d[n+14],23,-35309556),r=md5_hh(r,i=md5_hh(i,m=md5_hh(m,f,r,i,d[n+1],4,-1530992060),f,r,d[n+4],11,1272893353),m,f,d[n+7],16,-155497632),i,m,d[n+10],23,-1094730640),r=md5_hh(r,i=md5_hh(i,m=md5_hh(m,f,r,i,d[n+13],4,681279174),f,r,d[n+0],11,-358537222),m,f,d[n+3],16,-722521979),i,m,d[n+6],23,76029189),r=md5_hh(r,i=md5_hh(i,m=md5_hh(m,f,r,i,d[n+9],4,-640364487),f,r,d[n+12],11,-421815835),m,f,d[n+15],16,530742520),i,m,d[n+2],23,-995338651),r=md5_ii(r,i=md5_ii(i,m=md5_ii(m,f,r,i,d[n+0],6,-198630844),f,r,d[n+7],10,1126891415),m,f,d[n+14],15,-1416354905),i,m,d[n+5],21,-57434055),r=md5_ii(r,i=md5_ii(i,m=md5_ii(m,f,r,i,d[n+12],6,1700485571),f,r,d[n+3],10,-1894986606),m,f,d[n+10],15,-1051523),i,m,d[n+1],21,-2054922799),r=md5_ii(r,i=md5_ii(i,m=md5_ii(m,f,r,i,d[n+8],6,1873313359),f,r,d[n+15],10,-30611744),m,f,d[n+6],15,-1560198380),i,m,d[n+13],21,1309151649),r=md5_ii(r,i=md5_ii(i,m=md5_ii(m,f,r,i,d[n+4],6,-145523070),f,r,d[n+11],10,-1120210379),m,f,d[n+2],15,718787259),i,m,d[n+9],21,-343485551),m=safe_add(m,h),f=safe_add(f,t),r=safe_add(r,g),i=safe_add(i,e)}return Array(m,f,r,i)}function md5_cmn(d,_,m,f,r,i){return safe_add(bit_rol(safe_add(safe_add(_,d),safe_add(f,i)),r),m)}function md5_ff(d,_,m,f,r,i,n){return md5_cmn(_&m|~_&f,d,_,r,i,n)}function md5_gg(d,_,m,f,r,i,n){return md5_cmn(_&f|m&~f,d,_,r,i,n)}function md5_hh(d,_,m,f,r,i,n){return md5_cmn(_^m^f,d,_,r,i,n)}function md5_ii(d,_,m,f,r,i,n){return md5_cmn(m^(_|~f),d,_,r,i,n)}function safe_add(d,_){var m=(65535&d)+(65535&_);return(d>>16)+(_>>16)+(m>>16)<<16|65535&m}function bit_rol(d,_){return d<<_|d>>>32-_}
    function collab_create() {
        var type = $("#secureMenu_collab-create-type").val();
        var members = $("#secureMenu_collab-create-members").val().join(";");
        if (members.substr(0, 1) == "*") {
            members = "*";
        }
        var name = $("#secureMenu_collab-create-name").val();
        var id = collab_md5(Date());
        collab_hidePage("create");
        $("#secureMenu_collab-create-members").val("*");
        $("#secureMenu_collab-create-name").val("");
        if (type == "room") {
            var rHtml = "<div id=\"secureMenu_collab-room-" + id + "\" class=\"collab-person\">";
            rHtml += "<div class=\"status offline\"><div class=\"status online\" style=\"height:50%;\"></div></div>";
            rHtml += "<div class=\"info\">" + name + "<br /><small><i><span class=\"online\"></span></i></small></div>";
            rHtml += "<button class=\"collab-chat\" title=\"Open Room\" onclick=\"collab_showChat(\'R" + id + "\',\'" + name + "\');\"><i class=\"fas fa-comments\"></i></button>";
            $("#secureMenu_collab-rooms").append(rHtml);
            collab_showChat("R"+id,name);
        } else {
            var tHtml = "<div id=\"secureMenu_collab-list-" + id + "\" class=\"collab-person\">";
            tHtml += "<div class=\"status incomplete\"><div class=\"status complete\" style=\"height:50%;\"></div></div>";
            tHtml += "<div class=\"info\">" + name + "<br /><small><i><span class=\"done\"></span></i></small></div>";
            tHtml += "<button class=\"collab-chat\" title=\"Open List\" onclick=\"collab_showList(\'" + id + "\',\'" + name + "\');\"><i class=\"fas fa-clipboard-list\"></i></button>";
            $("#secureMenu_collab-todo").append(tHtml);
            collab_showList(id,name);
        }

        module_ajax("collab_update", {chat:collab_chat,list:collab_list,new_id:id,new_type:type,new_members:members,new_name:name}, collab_update_callback);
    }
    var collab_chat = null;
    var collab_list = null;
    function collab_showChat(id, name) {
        $("#secureMenu_collab-chat .messages").html("");
        $("#secureMenu_collab-chat .title b").text(name);
        collab_chat = id;
        collab_update();
        collab_showPage("chat");
    }
    function collab_hideChat() {
        collab_hidePage("chat");
        collab_chat = null;
    }
    function collab_showList(id, name) {
        $("#secureMenu_collab-list .todos").html("");
        $("#secureMenu_collab-list .title b").text(name);
        collab_list = id;
        collab_update();
        collab_showPage("list");
    }
    function collab_hideList() {
        collab_hidePage("list");
        collab_list = null;
    }
    function collab_sendMessage() {
        var msg = $("#secureMenu_collab-chat-newmessage").val();
        if (msg == "") {
            return;
        }
        $("#secureMenu_collab-chat-newmessage").val("");
        module_ajax("collab_update", {chat:collab_chat,list:collab_list,message:msg}, collab_update_callback);
    }
    function collab_addTodo() {
        var label = $("#secureMenu_collab-todo-newentry").val();
        if (label == "") {
            return;
        }
        $("#secureMenu_collab-todo-newentry").val("");
        module_ajax("collab_update", {chat:collab_chat,list:collab_list,entry:label}, collab_update_callback);
    }
    function collab_check(id) {
        module_ajax("collab_update", {chat:collab_chat,list:collab_list,check_entry:id}, collab_update_callback);
    }
    function collab_deleteTodo(id) {
        module_ajax("collab_update", {chat:collab_chat,list:collab_list,delete_entry:id}, collab_update_callback);
        $("#secureMenu_collab-todo-"+id)[0].remove();
    }
    $(document).ready(function() {setInterval(collab_update, 3000);collab_update();});
    function collab_update() {
        module_ajax("collab_update", {chat:collab_chat,list:collab_list}, collab_update_callback);
    }
    function collab_update_callback(data) {
        var data = JSON.parse(data);
        for (user in data["users"]) {
            var u = data["users"][user];
            if (u.online) {
                $("#secureMenu_collab-person-" + u.uid + " .status").removeClass("offline");
                $("#secureMenu_collab-person-" + u.uid + " .status").addClass("online");
                $("#secureMenu_collab-person-" + u.uid + " .page").html("<a href=\"/"+u.page_id+"\" title=\""+u.page_title+"\">"+u.page_title+"</a>");
            } else {
                $("#secureMenu_collab-person-" + u.uid + " .status").removeClass("online");
                $("#secureMenu_collab-person-" + u.uid + " .status").addClass("offline");
                $("#secureMenu_collab-person-" + u.uid + " .page").html("<a href=\"/"+u.page_id+"\" title=\""+u.page_title+"\">Last seen "+u.lastseen_informal+"</a>");
            }
            $("#secureMenu_collab-person-" + u.uid + " .notif-badge").text(0);
        }
        for (room in data["rooms"]) {
            var r = data["rooms"][room];
            var percent = r.on*100/r.members;
            $("#secureMenu_collab-room-" + r.rid + " .status > .online").height("" + percent + "%");
            $("#secureMenu_collab-room-" + r.rid + " .info .online").html("" + r.on + "/" + r.members + " online");
            $("#secureMenu_collab-room-" + r.rid + " .notif-badge").text(0);
        }
        for (list in data["lists"]) {
            var l = data["lists"][list];
            var percent = l.done*100/l.tasks;
            $("#secureMenu_collab-list-" + l.lid + " .status > .complete").height("" + percent + "%");
            $("#secureMenu_collab-list-" + l.lid + " .info .done").html("" + l.done + "/" + l.tasks + " finished");
            $("#secureMenu_collab-list-" + l.lid + " .notif-badge").text(0);
        }
        var scrollChat = false;
        for (msg in data["chat"]) {
            var m = data["chat"][msg];
            if ($("#secureMenu_collab-chat-"+m.id).length == 0) {
                var msgHtml = "<div id=\"secureMenu_collab-chat-"+m.id+"\" class=\""+m.type+"\" title=\""+m.sent+"\">";
                msgHtml    += "<p></p><span class=\"from\">Sent "+m.sent_informal+" by "+m.from+"</span></div>";
                $("#secureMenu_collab-chat .messages").append(msgHtml);
                $("#secureMenu_collab-chat-"+m.id+" p").text(m.body);
                scrollChat = true;
            } else {
                $("#secureMenu_collab-chat-"+m.id+" .from").html("Sent "+m.sent_informal+" by "+m.from);
            }
        }
        if (scrollChat) {
            $("#secureMenu_collab-chat .messages").animate({scrollTop:$("#secureMenu_collab-chat .messages")[0].scrollHeight}, 500);
        }

        for (entry in data["todo"]) {
            var e = data["todo"][entry];
            if ($("#secureMenu_collab-todo-"+e.id).length == 0) {
                var eHtml = "<div id=\"secureMenu_collab-todo-"+e.id+"\" class=\"collab-todo\">";
                eHtml += "<div class=\"form-check\">";
                eHtml += "<input class=\"form-check-input\" type=\"checkbox\" onclick=\"collab_check(\'" + e.id + "\');\">";
                eHtml += "<label class=\"form-check-label\"></label></div>";
                eHtml += "<button class=\"collab-delete\" title=\"Delete\" onclick=\"collab_deleteTodo(\'" + e.id + "\');\"><i class=\"fas fa-trash\"></i></button>";
                $("#secureMenu_collab-list .todos").append(eHtml);
                $("#secureMenu_collab-todo-"+e.id+" label").text(e.label);
            }
            $("#secureMenu_collab-todo-"+e.id+" .form-check-input")[0].checked = e.done == 1;
        }
        var totalNotifs = data["notifs"].length;
        var roomNotifs = 0;
        var todoNotifs = 0;
        var userNotifs = 0;
        $(".notif-badge").hide();
        if (totalNotifs > 0) {
            $("#secureMenu_trigger > .notif-badge").text(totalNotifs);
            $("#secureMenu_trigger > .notif-badge").show();
        } else {
            $("#secureMenu_trigger > .notif-badge").hide();
        }
        for (notif in data["notifs"]) {
            var n = data["notifs"][notif].toLowerCase();
            var t = n.substr(0, 1);
            var i = n.substr(1);
            switch (t) {
                case "p":
                    $("#secureMenu_option-account > .notif-badge").show();
                    break;
                case "u":
                    userNotifs++;
                    $("#secureMenu_collab-person-" + i + " .notif-badge").text(parseInt($("#secureMenu_collab-person-" + i + " .notif-badge").text())+1);
                    $("#secureMenu_collab-person-" + i + " .notif-badge").show();
                    break;
                case "l":
                    todoNotifs++;
                    $("#secureMenu_collab-list-" + i + " .notif-badge").text(parseInt($("#secureMenu_collab-list-" + i + " .notif-badge").text())+1);
                    $("#secureMenu_collab-list-" + i + " .notif-badge").show();
                    break;
                case "r":
                    roomNotifs++;
                    $("#secureMenu_collab-room-" + i + " .notif-badge").text(parseInt($("#secureMenu_collab-room-" + i + " .notif-badge").text())+1);
                    $("#secureMenu_collab-room-" + i + " .notif-badge").show();
                    break;
                default:
                    break;
            }
        }
        if (roomNotifs + todoNotifs + userNotifs > 0) {
            $("#secureMenu_option-collabTrigger > .notif-badge").text(roomNotifs + todoNotifs + userNotifs);
            $("#secureMenu_option-collabTrigger > .notif-badge").show();
        }
        if (roomNotifs > 0) {
            $("#secureMenu_notif-rooms").text(roomNotifs);
            $("#secureMenu_notif-rooms").show();
        }
        if (todoNotifs > 0) {
            $("#secureMenu_notif-todo").text(todoNotifs);
            $("#secureMenu_notif-todo").show();
        }
        if (userNotifs > 0) {
            $("#secureMenu_notif-people").text(userNotifs);
            $("#secureMenu_notif-people").show();
        }
    }
</script>';
},

//  ________
// /        \
//(  Modals  )
// \________/

// Modal Start

"secure-modal-start" => function ($id, $title, $size) {
    return '
<div class="modal fade" id="' . $id . '" tabindex="-1" role="dialog" aria-labelledby="' . $id . '_title">
<div class="modal-dialog modal-' . $size . '" role="document">
<div class="modal-content">
<div class="modal-header">
<h4 class="modal-title" id="' . $id . '_title">' . $title . '</h4>
<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
</div>';
},

// Modal End

"secure-modal-end" =>
'
</div></div></div>',

// Administration Modal
//====================

// Body
"secure-modal-admin-bodyfoot" => function() {
    global $TEMPLATES;

    $ccms_info = parse_ini_file($_SERVER["DOCUMENT_ROOT"] . "/core/ccms-info.ini");

    $websitetitle = Utilities::getconfig("websitetitle");
    $primaryemail = Utilities::getconfig("primaryemail");
    $secondaryemail = Utilities::getconfig("secondaryemail");

    $releasedate = date("l, F j, Y", strtotime($ccms_info["release"]));
    $creationdate = date("l, F j, Y", strtotime(Utilities::getconfig("creationdate")));

    return '
<div class="modal-body">
    <div class="row">
        <div class="col-12 col-md-3 mb-3">
            <div class="nav flex-md-column flex-row nav-pills" id="dialog_admin_tabs" role="tablist" aria-orientation="vertical">
                ' . (User::$currentUser->permissions->admin_managesite ? '<a class="nav-link flex-sm-fill text-center text-md-left" id="dialog_admin_tab_site" data-toggle="pill" href="#dialog_admin_panel_site" role="tab" aria-controls="dialog_admin_panel_site" aria-selected="false">Site</a>':'').'
                <a class="nav-link flex-sm-fill text-center text-md-left" id="dialog_admin_tab_ccms" data-toggle="pill" href="#dialog_admin_panel_ccms" role="tab" aria-controls="dialog_admin_panel_ccms" aria-selected="false">Chaos CMS</a>
            </div>
        </div>
        <div class="col-12 col-md-9">
            <div class="tab-content" id="dialog_admin_panels">' . (User::$currentUser->permissions->admin_managesite ? '
                <div class="tab-pane fade" id="dialog_admin_panel_site" role="tabpanel" aria-labelledby="dialog_admin_tab_site">
                    <form onsubmit="dialog_admin_site_save();return false;">
                        <div class="form-group row">
                            <label class="col-form-label col-sm-3 col-md-2" for="dialog_admin_site_websitetitle">Website Title</label>
                            <div class="col-sm-9 col-md-10">
                                <input type="text" id="dialog_admin_site_websitetitle" name="websitetitle" class="form-control" title="Website Title" placeholder="Website Title" value="' . $websitetitle . '">
                            </div>
                        </div>
                        <div class="form-group row">
                            <label class="col-form-label col-sm-3 col-md-2" for="dialog_admin_site_primaryemail">Primary Email</label>
                            <div class="col-sm-9 col-md-10">
                                <input type="text" id="dialog_admin_site_primaryemail" name="primaryemail" class="form-control" title="Primary Email" placeholder="Primary Email" value="' . $primaryemail . '">
                            </div>
                        </div>
                        <div class="form-group row">
                            <div class="offset-sm-3 offset-md-2 col-sm-9 col-md-10">
                                <input type="submit" class="btn btn-primary" title="Save" value="Save">
                            </div>
                        </div>
                    </form>
                </div>':'').'
                <div class="tab-pane fade" id="dialog_admin_panel_ccms" role="tabpanel" aria-labelledby="dialog_admin_tab_ccms">
                    <dl>
                        <div class="row"><dt class="col-12 col-sm-4">Version</dt><dd class="col-12 col-sm-8">' . $ccms_info["version"] .'</dd></div>
                        <div class="row"><dt class="col-12 col-sm-4">Release Date</dt><dd class="col-12 col-sm-8">' . $releasedate . '</dd></div>
                        <div class="row"><dt class="col-12 col-sm-4">Author</dt><dd class="col-12 col-sm-8"><a href="mailto:' . $ccms_info["a_email"] .'" title="' . $ccms_info["a_email"] .'">' . $ccms_info["author"] .'</a></dd></div>
                        <div class="row"><dt class="col-12 col-sm-4">CCMS Website</dt><dd class="col-12 col-sm-8"><a href="' . $ccms_info["website"] .'" title="Chaos CMS Website">' . $ccms_info["website"] .'</a></dd></div>
                        <div class="row"><dt class="col-12 col-sm-4">Website created</dt><dd class="col-12 col-sm-8">' . $creationdate .'</dd></div>
                    </dl>
                </div>
            </div>
        </div>
    </div>
</div>' .
// Foot
'
<div class="modal-footer">
    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
</div>';
},

"secure-modal-admin-script" => function() {
    return '
function dialog_admin_site_save() {
    module_ajax("api/config/set", {websitetitle: $("#dialog_admin_site_websitetitle").val(),
                                   primaryemail: $("#dialog_admin_site_primaryemail").val(),
                                   token: Cookies.get("token")}, function(data){
        if (data == "TRUE") {
            window.alert("Website settings saved.");
            window.location.reload(true);
        } else {
            window.alert("Couldn\'t save settings.");
        }
    });
}';
},

];