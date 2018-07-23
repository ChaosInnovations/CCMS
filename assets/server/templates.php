<?php

// TODO: groupings

$TEMPLATES = [

// Always shown
"secure-navbar-start" =>
'
<nav class="navbar navbar-expand-lg navbar-dark secure-nav">
<button type="button" class="navbar-toggler" data-toggle="collapse" data-target="#snavbar-collapse" aria-expanded="false">
<span class="navbar-toggler-icon"></span>
</button>
<div class="collapse navbar-collapse" id="snavbar-collapse">
<ul class="navbar-nav mr-auto mt-2 mt-lg-0">',

"navbar-dropdown-end" =>
'
</div></li>',

// Shown if user has any admin privileges
"secure-navbar-dropdown-admin-start" =>
'
<li class="nav-item dropdown">
<a href="" class="nav-link dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">Administration</a>
<div class="dropdown-menu">',

// Shown if user can manage site
"secure-navbar-dropdown-admin-button-site" =>
'
<a class="dropdown-item" href="#" title="Manage Site" onclick="showDialog(\'managesite\');">Site Manager</a>',

// Shown if user can manage users
"secure-navbar-dropdown-admin-button-users" =>
'
<a class="dropdown-item" href="#" title="Manage Users" onclick="showDialog(\'manageusers\');">Users</a>',

// Always shown
"secure-navbar-nav-end" =>
'</div></div>',

// Always shown
"secure-navbar-end" =>
'
</div></nav>',

// Secure menu

"secure-menu" => function ($authuser, $securepages, $availablemodules, $modules) {
	$canChat = true; // temporary chat permission
	
	$securePageListing = '';
	foreach ($securepages as $sp) {
		if (!in_array($sp, $authuser->permissions->page_viewblacklist)) {
			$spd = new Page($sp);
			$securePageListing .= '<a href="?p='.$sp.'">'.$spd->title.'</a><br />
			';
		}
	}
	
	$moduleListing = '';
	foreach($availablemodules as $m) {
		$mc = $modules[$m];
		if (isset($mc->name) && method_exists($mc, "getModal")) {
			$secure .= '<span onclick="showDialog(\'module_'.$m.'\');">'.$mc->name.'</span><br />
			';
		}
	}
	
	return '
<div id="secureMenu" class="secureMenu">
  <div id="secureMenu_trigger" class="secureMenu-icon" title="Secure Menu" onclick="toggleSecureMenu();" style="z-index:10;">
    <span><i class="fas fa-ellipsis-v"></i></span>
  </div>
  <div id="secureMenu_horizontal" class="secureMenu-iconGroup horizontal" style="z-index:9;">
    <div id="secureMenu_option-signout" title="Sign Out" onclick="logout();" class="secureMenu-icon">
      <span><i class="fas fa-sign-out-alt"></i></span>
    </div>
    <div id="secureMenu_option-account" title="Account Details" onclick="showDialog(\'account\');" class="secureMenu-icon">
      <span><i class="fas fa-user-cog"></i></span>
    </div>' . ($canChat ? '
    <div id="secureMenu_option-collabTrigger" title="Collaborate" onclick="triggerPane(\'collab\');" class="secureMenu-icon">
      <span><i class="fas fa-share-alt"></i></span>
    </div>' : '') . '
    <div id="secureMenu_option-home" title="Home" onclick="location.assign(\'./\');" class="secureMenu-icon">
      <span><i class="fas fa-home"></i></span>
    </div>
  </div>
  <div id="secureMenu_vertical" class="secureMenu-iconGroup vertical" style="z-index:9;">' . ($authuser->permissions->admin_managepages ? '
    <div id="secureMenu_option-admin" title="Administration" onclick="showDialog(\'admin\');" class="secureMenu-icon">
      <span><i class="fas fa-cogs"></i></span>
    </div>' : '') . ($authuser->permissions->admin_managesite ? '
    <div id="secureMenu_option-moduleTrigger" title="Modules" onclick="triggerPane(\'module\');" class="secureMenu-icon">
      <span><i class="fas fa-puzzle-piece"></i></span>
    </div>' : '') . ($authuser->permissions->page_viewsecure ? '
    <div id="secureMenu_option-securepageTrigger" title="Secure Pages" onclick="triggerPane(\'securepage\');" class="secureMenu-icon">
      <span><i class="fas fa-lock"></i></span>
    </div>' : '') . ($authuser->permissions->page_create ? '
    <div id="secureMenu_option-newpage" title="New Page" onclick="createPage();" class="secureMenu-icon">
      <span><i class="fas fa-plus"></i></span>
    </div>' : '') . ($authuser->permissions->page_edit ? '
    <div id="secureMenu_option-edit" title="Edit Page" onclick="showDialog(\'edit\');" class="secureMenu-icon">
      <span><i class="fas fa-edit"></i></span>
    </div>' : '') . '
  </div>' . ($canChat ? '
  <div id="secureMenu_pane-collab" class="secureMenu-pane collapsed vertical" style="display:none;">
    <b>Collaborate</b>
    <hr />
    <i>Coming Soon</i>
  </div>' : '') . ($authuser->permissions->admin_managesite ? '
  <div id="secureMenu_pane-module" class="secureMenu-pane collapsed horizontal" style="display:none;">
    <b>Modules</b>
    <hr />
    <div>
		' . $moduleListing . '
      <span onclick="showDialog(\'module_hermes\');">Hermes Transit Management Platform</span><br />
      <span onclick="showDialog(\'module_notices\');">Site Notices</span><br />
    </div>
  </div>' : '') . ($authuser->permissions->page_viewsecure ? '
  <div id="secureMenu_pane-securepage" class="secureMenu-pane collapsed horizontal" style="display:none;">
    <b>Secure Pages</b>
    <hr />' . ($authuser->permissions->page_createsecure ? '
    <span onclick="createSecurePage();"><i class="fas fa-plus"></i></span>
    <hr />' : '') . '
    <div>
		' . $securePageListing . '
    </div>
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

// Edit Modal
//============

// Body
"secure-modal-edit-bodyfoot" =>
'
<div class="modal-body">
<form class="form" role="edit" onsubmit="dialog_edit_save();return false;">
<div class="form-group row">
<div class="offset-md-2 offset-sm-3 col-md-10 col-sm-9">
<input type="submit" class="btn btn-primary" title="Save" value="Save">
<span class="dialog_edit_formfeedback_saved" style="display:none;">Saved!</span>
<span class="dialog_edit_formfeedback_notsaved" style="display:none;">There was an error saving. Check your connection.</span>
</div>
</div>
<div class="form-group row">
<label class="col-form-label col-sm-3 col-md-2" for="dialog_edit_pageid">Page ID:</label>
<div class="input-group col-sm-9 col-md-10">
<input type="text" id="dialog_edit_pageid" name="pageid" class="form-control border-right-0 border-secondary" title="Page ID" placeholder="Page ID" oninput="dialog_edit_check_pageid();">
<div class="input-group-append">
<div class="input-group-text bg-transparent border-left-0 border-secondary">
<i class="fas fa-times" style="display:none;"></i>
<i class="fas fa-check" style="display:none;"></i>
</div>
</div>
</div>
</div>
<div class="form-group row">
<label class="col-form-label col-sm-3 col-md-2" for="dialog_edit_pagetitle">Page Title:</label>
<div class="col-sm-9 col-md-10">
<input type="text" id="dialog_edit_pagetitle" name="pagetitle" class="form-control border-secondary" title="Page Title" placeholder="Page Title">
</div>
</div>
<div class="form-group row">
<label class="col-form-label col-sm-3 col-md-2" for="dialog_edit_head"><code>&lt;head&gt;</code>:</label>
<div class="col-sm-9 col-md-10">
<textarea id="dialog_edit_head" name="head" class="form-control border-secondary" title="Page Head" placeholder="Page Head" rows="8"></textarea>
</div>
</div>
<div class="form-group row">
<label class="col-form-label col-sm-3 col-md-2" for="dialog_edit_body"><code>&lt;body&gt;</code>:</label>
<div class="col-sm-9 col-md-10">
<textarea id="dialog_edit_body" name="body" class="form-control border-secondary" title="Page Body" placeholder="Page Body" rows="32"></textarea>
</div>
</div>
</form>
</div>' .
// Foot
'
<div class="modal-footer">
<span class="dialog_edit_formfeedback_saved" style="display:none;">Saved!</span>
<span class="dialog_edit_formfeedback_notsaved" style="display:none;">There was an error saving. Check your connection.</span>
<button type="button" class="btn btn-primary" onclick="dialog_edit_save();">Save changes</button>
<button type="button" class="btn btn-danger" onclick="dialog_edit_reset();">Reset Changes</button>
<button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
</div>',

// Script
"secure-modal-edit-script" => function ($pid, $rtitle, $rhead, $rbody) {
	return '
var pageid = "' . $pid . '";
var pagetitle = decodeURIComponent("' . $rtitle . '");
var head = decodeURIComponent("' . $rhead . '");
var body = decodeURIComponent("' . $rbody . '");
$("#dialog_edit_pageid").val(pageid);
if (pageid == "home" || pageid == "notfound" || pageid == "secureaccess") {
	$("#dialog_edit_pageid").attr("disabled", "disabled");
}
$("#dialog_edit_pagetitle").val(pagetitle);
$("#dialog_edit_head").val(head);
$("#dialog_edit_body").val(body);
var cm_edit_head = null;
var cm_edit_body = null;

$("#dialog_edit").on("shown.bs.modal", function() {
	if (cm_edit_head == null) {
		cm_edit_head = CodeMirror.fromTextArea(document.getElementById("dialog_edit_head"), {
			lineNumbers: true,
			mode:  "xml"
		});
		cm_edit_body = CodeMirror.fromTextArea(document.getElementById("dialog_edit_body"), {
			lineNumbers: true,
			mode:  "xml"
		});
	}
});

function dialog_edit_check_pageid() {
	module_ajax("checkpid", {pageid: pageid,
								  check: $("#dialog_edit_pageid").val(),
								  token: Cookies.get("token")}, function(data){
		var fieldparent = $("#dialog_edit_pageid").parent();
		if (data == "TRUE") {
			fieldparent.removeClass("has-error");
			fieldparent.addClass("has-success");
			fieldparent.find(".fa-times").hide();
			fieldparent.find(".fa-check").show();
		} else {
			fieldparent.removeClass("has-success");
			fieldparent.addClass("has-error");
			fieldparent.find(".fa-check").hide();
			fieldparent.find(".fa-times").show();
		}
	});
}

function dialog_edit_reset() {
	$("#dialog_edit_pageid").val(pageid);
	$("#dialog_edit_pagetitle").val(pagetitle);
	$("#dialog_edit_head").val(head);
	cm_edit_body.setValue(body);
}

function dialog_edit_save() {
	module_ajax("editpage", {pageid: pageid,
								  newpageid: $("#dialog_edit_pageid").val(),
	                              title: encodeURIComponent($("#dialog_edit_pagetitle").val()),
								  head: encodeURIComponent(cm_edit_head.getValue()),
								  body: encodeURIComponent(cm_edit_body.getValue()),
								  token: Cookies.get("token")}, function(data){
		if (data == "FALSE") {
			$(".dialog_edit_formfeedback_notsaved").show();
			setTimeout(function(){$(".dialog_edit_formfeedback_notsaved").hide();}, 1500);
		} else if (data == "TRUE") {
			$(".dialog_edit_formfeedback_saved").show();
			setTimeout(function(){window.location.reload();}, 800);
		} else {
			window.location = "?p="+data;
		}
	});
}

$(document).keydown(function(event) {
    if (event.ctrlKey || event.metaKey) {
        switch (String.fromCharCode(event.which).toLowerCase()) {
        case "s":
			if ($("#dialog_edit").is(":visible")) {
			    event.preventDefault();
			    dialog_edit_save();
                break;
			}
		case "e":
			if (!$("#dialog_edit").is(":visible")) {
			    event.preventDefault();
			    showDialog("edit");
                break;
			}
		}
    }
});';
},

// Administration Modal
//====================

"secure-modal-admin-pagerow" => function ($page) {
	$pid = $page["pageid"];
	$check = $page["secure"] ? ' checked' : '';
	$secure = '
<input type="checkbox" id="dialog_admin_pages_secure_' . $pid . '" onclick="dialog_admin_pages_togglesecure(\'' . $pid . '\');"' . $check . '>';
	$remove = '
<button class="btn btn-outline-danger" title="Delete Page" onclick="dialog_admin_pages_delete(\'' . $pid . '\');"><i class="fas fa-trash"></i></button>';
	if (in_array($page["pageid"], ["home", "notfound", "secureaccess"])) {
		$secure = '';
		$remove = '';
	}
	$date = date("l, F j, Y", strtotime($page["revision"]));
	return '
<tr><td><a href="?p=' . $pid . '">' . $pid . '</a></td><td>' . urldecode($page["title"]) . '</td><td>' . $date . '</td><td>' . $secure . '</td><td>' . $remove . '</td></tr>';
},

// Body
"secure-modal-admin-bodyfoot" => function($authuser, $pages, $users) {
	global $TEMPLATES;
	global $ccms_info;
	$pagelist = "";
	foreach ($pages as $page) {
		$pagelist .= $TEMPLATES["secure-modal-admin-pagerow"]($page);
	}
	
	$releasedate = date("l, F j, Y", strtotime($ccms_info->release));
	$creationdate = date("l, F j, Y", strtotime(getconfig("creationdate")));
	
	return '
<div class="modal-body">
	<div class="row">
	    <div class="col-3">
		    <div class="nav flex-column nav-pills" id="dialog_admin_tabs" role="tablist" aria-orientation="vertical">
		        <a class="nav-link active" id="dialog_admin_tab_pages" data-toggle="pill" href="#dialog_admin_panel_pages" role="tab" aria-controls="dialog_admin_panel_pages" aria-selected="true">Pages</a>
		        ' . ($authuser->permissions->admin_managesite ? '<a class="nav-link" id="dialog_admin_tab_users" data-toggle="pill" href="#dialog_admin_panel_users" role="tab" aria-controls="dialog_admin_panel_users" aria-selected="false">Users</a>
		        <a class="nav-link" id="dialog_admin_tab_site" data-toggle="pill" href="#dialog_admin_panel_site" role="tab" aria-controls="dialog_admin_panel_site" aria-selected="false">Site</a>':'').'
		        <a class="nav-link" id="dialog_admin_tab_ccms" data-toggle="pill" href="#dialog_admin_panel_ccms" role="tab" aria-controls="dialog_admin_panel_ccms" aria-selected="false">Chaos CMS</a>
		    </div>
	    </div>
	    <div class="col-9">
		    <div class="tab-content" id="dialog_admin_panels">
		        <div class="tab-pane fade show active" id="dialog_admin_panel_pages" role="tabpanel" aria-labelledby="dialog_admin_tab_pages">
					<table class="table table-striped">
						<thead>
							<tr><th>Page ID</th><th>Title</th><th>Last Revision</th><th><i class="fas fa-lock"></i></th><th>Delete</th></tr>
						</thead>
						<tbody>' . $pagelist . '</tbody>
					</table>
				</div>' . ($authuser->permissions->admin_managepages ? '
		        <div class="tab-pane fade" id="dialog_admin_panel_users" role="tabpanel" aria-labelledby="dialog_admin_tab_users">
					Users
				</div>
		        <div class="tab-pane fade" id="dialog_admin_panel_site" role="tabpanel" aria-labelledby="dialog_admin_tab_site">
					Site
				</div>':'').'
		        <div class="tab-pane fade" id="dialog_admin_panel_ccms" role="tabpanel" aria-labelledby="dialog_admin_tab_ccms">
					<dl>
						<div class="row"><dt class="col-12 col-sm-4">Version</dt><dd class="col-12 col-sm-8">' . $ccms_info->version .'</dd></div>
						<div class="row"><dt class="col-12 col-sm-4">Release Date</dt><dd class="col-12 col-sm-8">' . $releasedate . '</dd></div>
						<div class="row"><dt class="col-12 col-sm-4">Author</dt><dd class="col-12 col-sm-8"><a href="mailto:' . $ccms_info->a_email .'" title="' . $ccms_info->a_email .'">' . $ccms_info->author .'</a></dd></div>
						<div class="row"><dt class="col-12 col-sm-4">CCMS Website</dt><dd class="col-12 col-sm-8"><a href="' . $ccms_info->website .'" title="Chaos CMS Website">' . $ccms_info->website .'</a></dd></div>
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

"secure-modal-admin-script" =>
'
function dialog_admin_pages_togglesecure(pid) {
	state = $("#dialog_admin_pages_secure_" + pid).prop("checked");
	module_ajax("securepage", {pid: pid, state: state}, function(data) {
		if (data == "FALSE") {
			$("#dialog_admin-pages_secure_" + pid).prop("checked", !state);
			window.alert("Couldn\'t change secure state.");
		} else if (data == "SPECIAL") {
			window.alert("Can\'t change security of \'home,\' \'notfound,\' or \'secureaccess\' pages!");
		} else {
			console.log(data);
			console.log("Changed security of \'" + pid + ".\'");
		}
	});
}

function dialog_admin_pages_delete(pid) {
	if (!window.confirm("Are you sure you want to permanently delete this page?", "Yes", "No")) {
		return;
	}
	module_ajax("removepage", {pid: pid}, function (data) {
		if (data == "FALSE") {
			window.alert("Couldn\'t delete page.");
		} else if (data == "SPECIAL") {
			window.alert("Can\'t delete \'home,\' \'notfound,\' or \'secureaccess\' pages!");
		} else {
			window.location.reload(true);
		}
	});
}',

// Site Manager Modal
//====================


"secure-modal-site-bodyfoot" => function ($websitetitle, $primaryemail, $secondaryemail) {
// Body
    return '
<div class="modal-body">
	<h4>Site Settings</h4>
	<form onsubmit="dialog_admin_site_save();return false;">
		<div class="form-group row">
			<div class="offset-sm-3 offset-md-2 col-sm-9 col-md-10">
				<input type="submit" class="btn btn-primary" title="Save" value="Save">
			</div>
		</div>
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
			<label class="col-form-label col-sm-3 col-md-2" for="dialog_admin_site_secondaryemail">Secondary Email</label>
			<div class="col-sm-9 col-md-10">
				<input type="text" id="dialog_admin_site_secondaryemail" name="secondaryemail" class="form-control" title="Secondary Email" placeholder="Secondary Email" value="' . $secondaryemail . '">
			</div>
		</div>
		<h4>Page Defaults</h4>
		<div class="form-group row">
			<label class="col-form-label col-sm-3 col-md-2" for="dialog_admin_site_defaulttitle">Default Page Title</label>
			<div class="col-sm-9 col-md-10">
				<input type="text" id="dialog_admin_site_defaulttitle" name="defaulttitle" class="form-control" title="Default Page Title" placeholder="Default Page Title">
			</div>
		</div>
		<div class="form-group row">
			<label class="col-form-label col-sm-3 col-md-2" for="dialog_admin_site_defaulthead">Default Page <code>&lt;head&gt;</code></label>
			<div class="col-sm-9 col-md-10">
				<textarea id="dialog_admin_site_defaulthead" name="defaulthead" class="form-control monospace" title="Default Page Head" placeholder="Default Page Head" rows="8"></textarea>
			</div>
		</div>
		<div class="form-group row">
			<label class="col-form-label col-sm-3 col-md-2" for="dialog_admin_site_defaultbody">Default Page <code>&lt;body&gt;</code></label>
			<div class="col-sm-9 col-md-10">
				<textarea id="dialog_admin_site_defaultbody" name="defaultbody" class="form-control monospace" title="Default Page Body" placeholder="Default Page Body" rows="16"></textarea>
			</div>
		</div>
		<div class="form-group row">
			<label class="col-form-label col-sm-3 col-md-2" for="dialog_admin_site_defaultnav">Default Navigation Header</label>
			<div class="col-sm-9 col-md-10">
				<textarea id="dialog_admin_site_defaultnav" name="defaultnav" class="form-control monospace" title="Default Navigation Header" placeholder="Default Navigation Header" rows="16"></textarea>
			</div>
		</div>
		<div class="form-group row">
			<label class="col-form-label col-sm-3 col-md-2" for="dialog_admin_site_defaultfoot">Default Footer</label>
			<div class="col-sm-9 col-md-10">
				<textarea id="dialog_admin_site_defaultfoot" name="defaultfoot" class="form-control monospace" title="Default Footer" placeholder="Default Footer" rows="16"></textarea>
			</div>
		</div>
	</form>
</div>' . 
// Foot
'
<div class="modal-footer">
    <button type="button" class="btn btn-primary" title="Save" onclick="dialog_admin_site_save();">Save Changes</button>
	<button type="button" class="btn btn-danger" title="Reset" onclick="dialog_admin_site_reset();">Reset Changes</button>
	<button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
</div>';
},

// Script
"secure-modal-site-script" => function ($defaulttitle, $defaulthead, $defaultbody, $defaultnav, $defaultfoot) {
	return '
var defaulttitle = decodeURIComponent("' . $defaulttitle . '");
var defaulthead = decodeURIComponent("' . $defaulthead . '");
var defaultbody = decodeURIComponent("' . $defaultbody . '");
var defaultnav = decodeURIComponent("' . $defaultnav . '");
var defaultfoot = decodeURIComponent("' . $defaultfoot . '");
$("#dialog_admin_site_defaulttitle").val(defaulttitle);
$("#dialog_admin_site_defaulthead").val(defaulthead);
$("#dialog_admin_site_defaultbody").val(defaultbody);
$("#dialog_admin_site_defaultnav").val(defaultnav);
$("#dialog_admin_site_defaultfoot").val(defaultfoot);
var cm_admin_site_head = null;
var cm_admin_site_body = null;
var cm_admin_site_nav = null;
var cm_admin_site_foot = null;

$("#dialog_admin").on("shown.bs.modal", function() {
	if (cm_admin_site_head == null) {
		cm_admin_site_head = CodeMirror.fromTextArea(document.getElementById("dialog_admin_site_defaulthead"), {
			lineNumbers: true,
			mode:  "xml"
		});
		cm_admin_site_body = CodeMirror.fromTextArea(document.getElementById("dialog_admin_site_defaultbody"), {
			lineNumbers: true,
			mode:  "xml"
		});
		cm_admin_site_nav = CodeMirror.fromTextArea(document.getElementById("dialog_admin_site_defaultnav"), {
			lineNumbers: true,
			mode:  "xml"
		});
		cm_admin_site_foot = CodeMirror.fromTextArea(document.getElementById("dialog_admin_site_defaultfoot"), {
			lineNumbers: true,
			mode:  "xml"
		});
	}
});

function dialog_admin_site_save() {
	module_ajax("setconfig", {websitetitle: $("#dialog_admin_site_websitetitle").val(),
	                               primaryemail: $("#dialog_admin_site_primaryemail").val(),
								   secondaryemail: $("#dialog_admin_site_secondaryemail").val(),
								   defaulttitle: encodeURIComponent($("#dialog_admin_site_defaulttitle").val()),
								   defaulthead: encodeURIComponent(cm_admin_site_head.getValue()),
								   defaultbody: encodeURIComponent(cm_admin_site_body.getValue()),
								   defaultnav: encodeURIComponent(cm_admin_site_nav.getValue()),
								   defaultfoot: encodeURIComponent(cm_admin_site_foot.getValue()),
	                               token: Cookies.get("token")}, function(data){
		if (data == "TRUE") {
			window.alert("Website settings saved.");
			window.location.reload(true);
		} else {
			window.alert("Couldn\'t save settings.");
		}
	});
}

function dialog_admin_site_reset() {
	$("#dialog_admin_site_defaulttitle").val(defaulttitle);
	$("#dialog_admin_site_defaulthead").val(defaulthead);
	$("#dialog_admin_site_defaultbody").val(defaultbody);
	$("#dialog_admin_site_defaultnav").val(defaultnav);
	$("#dialog_admin_site_defaultfoot").val(defaultfoot);
}

$(document).keydown(function(event) {
    if (event.ctrlKey || event.metaKey) {
        switch (String.fromCharCode(event.which).toLowerCase()) {
        case "s":
			if ($("#dialog_admin").is(":visible")) {
			    event.preventDefault();
			    dialog_admin_site_save();
                break;
			}
		case "m":
			if (!$("#dialog_admin").is(":visible")) {
			    event.preventDefault();
			    showDialog("admin");
                break;
			}
		}
    }
});';
},

// Users Modal
//=============

"secure-modal-manageusers-usertools" => function ($uid, $email) {
	return '
<button class="btn btn-outline-danger" title="Delete Account" onclick="dialog_admin_users_delete(\'' . $uid . '\');"><i class="fas fa-trash"></i></button>
<button class="btn btn-outline-secondary" title="Reset Password" onclick="dialog_admin_users_reset(\'' . $uid . '\');"><i class="fas fa-sync-alt"></i></button>
<a class="btn btn-outline-secondary" href="mailto:' . $email . '" title="Send Email"><i class="fas fa-envelope"></i></a>';
},

"secure-modal-manageusers-userrow" => function ($user, $uid) {
	global $TEMPLATES;
	$auser = new AuthUser($user["uid"]);
	$permissions = '
<button class="btn btn-secondary" type="button" data-toggle="collapse" data-target="#dialog_admin_users_' . $user["uid"] . '_perms" aria-expanded="false" aria-controls="dialog_admin_users_' . $user["uid"] . '_perms">Show/Hide</button>
<div class="collapse" id="dialog_admin_users_' . $user["uid"] . '_perms">
	<div class="card">
		<form class="form" onsubmit="dialog_admin_users_update(\'' . $user["uid"] . '\');return false;">
			<div class="form-group">
				<label class="col-form-label" for="dialog_admin_users_' . $user["uid"] . '_perms_p">Permissions</label>
				<textarea id="dialog_admin_users_' . $user["uid"] . '_perms_p" name="perm" class="form-control monospace" title="Permissions" placeholder="No Permissions" rows="4">' . $user["permissions"] . '</textarea>
			</div>
			<div class="form-group"' . (!$auser->permissions->page_viewsecure ? ' style="display:none;"' : '')  . '>
				<label class="col-form-label" for="dialog_admin_users_' . $user["uid"] . '_perms_v">View Blacklist</label>
				<textarea id="dialog_admin_users_' . $user["uid"] . '_perms_v" name="view" class="form-control monospace" title="View Blacklist" placeholder="No Restrictions" rows="4">' . $user["permviewbl"] . '</textarea>
			</div>
			<div class="form-group"' . (!$auser->permissions->page_editsecure ? ' style="display:none;"' : '')  . '>
				<label class="col-form-label" for="dialog_admin_users_' . $user["uid"] . '_perms_e">Edit Blacklist</label>
				<textarea id="dialog_admin_users_' . $user["uid"] . '_perms_e" name="edit" class="form-control monospace" title="Edit Blacklist" placeholder="No Restrictions" rows="4">' . $user["permeditbl"] . '</textarea>
			</div>
			<input type="submit" class="btn btn-outline-secondary" title="Update" value="Update">
		</form>
	</div>
</div>';
	$col = '';
	if ($user["uid"] == $uid) {
		$permissions = '<p>owner;</p>';
		$col = ' class="success"';
	}
	$date = date("l, F j, Y", strtotime($user["registered"]));
	$tools = $TEMPLATES["secure-modal-manageusers-usertools"]($user["uid"], $user["email"]);
	return '
<tr' . $col . '><td>' . $user["name"] . '</td><td>' . $user["email"] . '</td><td>' . $permissions . '</td><td>' . $date . '</td><td>' . $tools . '</td></tr>';
},

"secure-modal-manageusers-bodyfoot" => function ($users, $uid) {
	global $TEMPLATES;
// Body
	$userlist = "";
	foreach ($users as $user) {
		$userlist .= $TEMPLATES["secure-modal-manageusers-userrow"]($user, $uid);
	}
    return '
<div class="modal-body">
	<h4>New User</h4>
	<form role="edit" onsubmit="dialog_admin_users_new();return false;">
		<div class="form-group row">
			<label class="col-form-label col-sm-3 col-md-2" for="dialog_admin_users_newemail">Email</label>
			<div class="input-group col-sm-9 col-md-10">
				<input type="text" id="dialog_admin_users_newemail" class="form-control border-right-0 border-secondary" title="Email" placeholder="Email" oninput="dialog_manageusers_check_email();">
				<div class="input-group-append">
					<div class="input-group-text bg-transparent border-left-0 border-secondary">
						<i class="fas fa-times" style="display:none;"></i>
						<i class="fas fa-check" style="display:none;"></i>
					</div>
				</div>
			</div>
		</div>
		<div class="form-group row">
			<label class="col-form-label col-sm-3 col-md-2" for="username">Name</label>
			<div class="col-sm-9 col-md-10">
				<input type="text" id="dialog_admin_users_newname" name="name" class="form-control border-secondary" title="Name" placeholder="Name">
			</div>
		</div>
		<div class="form-group row">
		    <label class="col-form-label col-sm-3 col-md-2">Permissions</label>
			<div class="col-sm-9 col-md-10">
				<div class="row m-0">
					<div class="form-check form-check-inline">
						<input class="form-check-input" type="checkbox" id="dialog_admin_users_permission_owner" value="">
						<label class="form-check-label">Owner</label>
					</div>
				</div>
				<div class="row m-0">
					<div class="form-check form-check-inline">
						<input class="form-check-input" type="checkbox" id="dialog_admin_users_permission_admin_managepages">
						<label class="form-check-label">Manage Pages</label>
					</div>
					<div class="form-check form-check-inline">
						<input class="form-check-input" type="checkbox" id="dialog_admin_users_permission_admin_managesite">
						<label class="form-check-label">Manage Site</label>
					</div>
				</div>
				<div class="row m-0">
					<div class="form-check form-check-inline">
						<input class="form-check-input" type="checkbox" id="dialog_admin_users_permission_page_viewsecure">
						<label class="form-check-label">View Secure Pages</label>
					</div>
					<div class="form-check form-check-inline">
						<input class="form-check-input" type="checkbox" id="dialog_admin_users_permission_page_editsecure">
						<label class="form-check-label">Edit Secure Pages</label>
					</div>
					<div class="form-check form-check-inline">
						<input class="form-check-input" type="checkbox" id="dialog_admin_users_permission_page_createsecure">
						<label class="form-check-label">Create Secure Pages</label>
					</div>
					<div class="form-check form-check-inline">
						<input class="form-check-input" type="checkbox" id="dialog_admin_users_permission_page_deletesecure">
						<label class="form-check-label">Delete Secure Pages</label>
					</div>
				</div>
				<div class="row m-0">
					<div class="form-check form-check-inline">
						<input class="form-check-input" type="checkbox" id="dialog_admin_users_permission_page_edit">
						<label class="form-check-label">Edit Pages</label>
					</div>
					<div class="form-check form-check-inline">
						<input class="form-check-input" type="checkbox" id="dialog_admin_users_permission_page_create">
						<label class="form-check-label">Create Pages</label>
					</div>
					<div class="form-check form-check-inline">
						<input class="form-check-input" type="checkbox" id="dialog_admin_users_permission_page_delete">
						<label class="form-check-label">Delete Pages</label>
					</div>
				</div>
				<div class="row m-0">
					<div class="form-check form-check-inline">
						<input class="form-check-input" type="checkbox" id="dialog_admin_users_permission_toolbar">
						<label class="form-check-label">View Toolbar</label>
					</div>
				</div>
			</div>
		</div>
		<div class="form-group row">
			<div class="offset-sm-3 offset-md-2 col-md-10 col-sm-9">
				<input type="submit" class="btn btn-primary" title="Create" value="Create">
				<span class="dialog_admin_users_formfeedback_added" style="display:none;">User Created!</span>
				<span class="dialog_admin_users_formfeedback_notadded" style="display:none;">There was an error. Check your connection.</span>
			</div>
		</div>
	</form>
	<hr width="90%" />
	<h4>Current Users</h4>
	<table class="table table-striped">
		<thead>
			<tr><th>Name</th><th>Email</th><th>Permissions</th><th>Registered On</th><th>Tools</th></tr>
		</thead>
		<tbody>
' . $userlist . '
		</tbody>
	</table>
</div>' . 
// Foot
'
<div class="modal-footer">
	<button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
</div>';
},

// Script
"secure-modal-manageusers-script" => function () {
	return '
function dialog_admin_users_check_email() {
	module_ajax("checkuser", {email: $("#dialog_admin_users_newemail").val()}, function (data) {
		if (data == "TRUE") {
			$("#dialog_admin_users_newemail").parent().removeClass("has-success");
			$("#dialog_admin_users_newemail").parent().addClass("has-error");
			$("#dialog_admin_users_newemail").parent().find(".fa-check").hide();
			$("#dialog_users_newemail").parent().find(".fa-times").show();
		} else {
			$("#dialog_admin_users_newemail").parent().removeClass("has-error");
			$("#dialog_admin_users_newemail").parent().addClass("has-success");
			$("#dialog_admin_users_newemail").parent().find(".fa-times").hide();
			$("#dialog_admin_users_newemail").parent().find(".fa-check").show();
		}
	});
}

function dialog_admin_users_new() {
	permissions = "";
	permissions += $("#dialog_admin_users_permission_owner").prop("checked") ? "owner;" : "";
	permissions += $("#dialog_admin_users_permission_admin_managepages").prop("checked") ? "admin_managepages;" : "";
	permissions += $("#dialog_admin_users_permission_admin_managesite").prop("checked") ? "admin_managesite;" : "";
	permissions += $("#dialog_admin_users_permission_page_viewsecure").prop("checked") ? "page_viewsecure;" : "";
	permissions += $("#dialog_admin_users_permission_page_editsecure").prop("checked") ? "page_editsecure;" : "";
	permissions += $("#dialog_admin_users_permission_page_createsecure").prop("checked") ? "page_createsecure;" : "";
	permissions += $("#dialog_admin_users_permission_page_deletesecure").prop("checked") ? "page_deletesecure;" : "";
	permissions += $("#dialog_admin_users_permission_page_edit").prop("checked") ? "page_edit;" : "";
	permissions += $("#dialog_admin_users_permission_page_create").prop("checked") ? "page_create;" : "";
	permissions += $("#dialog_admin_users_permission_page_delete").prop("checked") ? "page_delete;" : "";
	permissions += $("#dialog_admin_users_permission_toolbar").prop("checked") ? "toolbar;" : "";
	module_ajax("newuser", {email: $("#dialog_admin_users_newemail").val(),
	                        name: $("#dialog_admin_users_newname").val(),
							permissions: permissions}, function (data) {
		if (data != "TRUE") {
			window.alert("Couldn\'t create account. Is "+$("#dialog_admin_users_newemail").val()+" already in use?");
			return;
		}
		window.alert("Created account with email \\""+$("#dialog_admin_users_newemail").val()+"\\" and password \\"password\\".");
		window.location.reload(true);
	});
}

function dialog_admin_users_update(uid) {
	module_ajax("edituser", {permissions: $("#dialog_admin_users_"+uid+"_perms_p").val(),
	                         permviewbl: $("#dialog_admin_users_"+uid+"_perms_v").val(),
							 permeditbl: $("#dialog_admin_users_"+uid+"_perms_e").val(),
							 uid: uid,
							 token: Cookies.get("token")}, function (data){
		if (data == "TRUE") {
			window.alert("Account updated.");
		} else {
			window.alert("Couldn\'t update this account.");
		}
	});
	
}

function dialog_admin_users_delete(uid) {
	if (window.confirm("Are you sure you want to remove this account?", "Yes", "No")) {
		module_ajax("removeaccount", {uid: uid}, function (data) {
			if (data == "TRUE") {
				window.alert("Account removed.");
				window.location.reload(true);
			} else if (data == "OWNER") {
				window.alert("You can\'t remove your own account if you\'re the only owner of this site!");
			} else {
				window.alert("Account couldn\'t be removed.");
			}
		});
	}
	
}

function dialog_admin_users_reset(uid) {
	module_ajax("resetpwd", {uid: uid}, function (data) {
		if (data == "TRUE") {
			window.alert("Password reset to \\"password\\".");
		} else {
			window.alert("Something went wrong trying to reset a password.");
		}
	});
}';
},

// Account Modal
//===============
"secure-modal-account-bodyfoot" => function($authuser) {
// Body
	return '
<div class="modal-body">
	<h4>Your Profile</h4>
	<form onsubmit="dialog_account_save();return false;">
		<div class="form-group row">
			<label class="col-form-label col-sm-3 col-md-2" for="dialog_account_name">Name</label>
			<div class="col-sm-9 col-md-10">
				<input name="name" title="Name" class="form-control" id="dialog_account_name" type="text" placeholder="Name" value="' . $authuser->name . '" />
			</div>
		</div>
		<div class="form-group row">
			<div class="offset-sm-3 offset-md-2 col-sm-9 col-md-10">
				<button class="btn btn-primary" type="submit">Save</button>
				<span class="dialog_account_formfeedback_saved" style="display:none;">Saved!</span>
				<span class="dialog_account_formfeedback_notsaved" style="display:none;">Couldn\'t save! Check your connection.</span>
			</div>
		</div>
		<div class="form-group row">
			<label class="col-form-label col-sm-3 col-md-2" for="dialog_account_email">Email</label>
			<div class="col-sm-9 col-md-10">
				<p class="form-control-plaintext" id="dialog_account_email">' . $authuser->email . '</p>
			</div>
		</div>
		<div class="form-group row">
			<label class="col-form-label col-sm-3 col-md-2" for="dialog_account_regdate">Registered on</label>
			<div class="col-sm-9 col-md-10">
				<p class="form-control-plaintext" id="dialog_account_regdate">' . $authuser->registerdate . '</p>
			</div>
		</div>
		<div class="form-group row">
			<label class="col-form-label col-sm-3 col-md-2" for="dialog_account_perms">Permissions</label>
			<div class="col-sm-9 col-md-10">
				<p class="form-control-plaintext" id="dialog_account_perms">' . $authuser->rawperms . '</p>
			</div>
		</div>
	</form>
	<h4>Change your Password</h4>
	<form role="edit" onsubmit="dialog_account_changepass();return false;">
		<input autocomplete="username" style="display:none;" id="dialog_account_user" type="text" value="'. $authuser->email .'" />
		<div class="form-group row">
			<label class="col-form-label col-sm-3 col-md-2" for="dialog_account_cpwd">Current Password</label>
			<div class="input-group col-sm-9 col-md-10">
				<input type="password" id="dialog_account_cpwd" autocomplete="current-password" class="form-control border-right-0 border-secondary" title="Password" placeholder="Current Password" oninput="dialog_account_check_cpwd();">
				<div class="input-group-append">
					<div class="input-group-text bg-transparent border-left-0 border-secondary">
						<i class="fas fa-times" style="display:none;"></i>
						<i class="fas fa-check" style="display:none;"></i>
					</div>
				</div>
			</div>
		</div>
		<div class="form-group row">
			<label class="col-form-label col-sm-3 col-md-2" for="dialog_account_npwd">New Password</label>
			<div class="col-sm-9 col-md-10">
				<div class="input-group mb-3">
					<input name="npwd" title="New Password" autocomplete="new-password" class="form-control border-secondary" id="dialog_account_npwd" type="password" placeholder="New Password" oninput="dialog_account_check_npwd();" />
					<span class="input-group-append">
						<button class="btn btn-outline-secondary" type="button" onclick="dialog_account_toggleshownpwd();"><i id="dialog_account_toggleshownpwd_symbol" class="fas fa-eye"></i></button>
					</span>
				</div>
				<span class="dialog_account_formfeedback_badnpwd" style="display:none;">Your password must contain at least 8 characters.</span>
			</div>
		</div>
		<div class="form-group row">
			<div class="offset-sm-3 offset-md-2 col-sm-9 col-md-10">
				<button class="btn btn-primary" type="submit">Change Password</button>
			</div>
		</div>
	</form>
	<h4>Delete your Account</h4>
	<div class="row">
		<div class="offset-sm-3 offset-md-2 col-sm-9 col-md-10">
			<button class="btn btn-danger" onclick="dialog_account_delete(\'' . $authuser->uid . '\');">Delete Account</button>
		</div>
	</div>
</div>' .
// Foot
'
<div class="modal-footer">
	<button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
</div>';
},

"secure-modal-account-script" =>
'
function dialog_account_save() {
	module_ajax("edituser", {name: $("#dialog_account_name").val()}, function (data) {
		if (data == "TRUE") {
			$(".dialog_account_formfeedback_saved").removeClass("hidden");
			setTimeout(function(){$(".dialog_account_formfeedback_saved").addClass("hidden");window.location.reload(true);}, 800);
		} else {
			$(".dialog_account_formfeedback_notsaved").removeClass("hidden");
			setTimeout(function(){$(".dialog_account_formfeedback_notsaved").addClass("hidden");}, 800);
		}
	});
}

function dialog_account_check_cpwd() {
	module_ajax("checkpass", {password: $("#dialog_account_cpwd").val()}, function(data) {
		if (data == "TRUE") {
			$("#dialog_account_cpwd").parent().removeClass("has-error");
			$("#dialog_account_cpwd").parent().addClass("has-success");
			$("#dialog_account_cpwd").parent().find(".fa-times").hide();
			$("#dialog_account_cpwd").parent().find(".fa-check").show();
		} else {
			$("#dialog_account_cpwd").parent().removeClass("has-success");
			$("#dialog_account_cpwd").parent().addClass("has-error");
			$("#dialog_account_cpwd").parent().find(".fa-check").hide();
			$("#dialog_account_cpwd").parent().find(".fa-times").show();
		}
	});
}

function dialog_account_check_npwd() {
	var npwd = $("#dialog_account_npwd").val();
	var conditions = npwd.length >= 8 ? true : false;
	if (conditions) {
		$("#dialog_account_npwd").parent().removeClass("has-error");
		$("#dialog_account_npwd").parent().addClass("has-success");
		$(".dialog_account_formfeedback_badnpwd").hide();
	} else {
		$("#dialog_account_npwd").parent().removeClass("has-success");
		$("#dialog_account_npwd").parent().addClass("has-error");
		$(".dialog_account_formfeedback_badnpwd ").show();
	}
}

function dialog_account_toggleshownpwd() {
	if ($("#dialog_account_npwd").attr("type") == "password") {
		$("#dialog_account_npwd").attr("type", "text");
		$("#dialog_account_toggleshownpwd_symbol").removeClass("fa-eye");
		$("#dialog_account_toggleshownpwd_symbol").addClass("fa-eye-slash");
	} else {
		$("#dialog_account_npwd").attr("type", "password");
		$("#dialog_account_toggleshownpwd_symbol").removeClass("fa-eye-slash");
		$("#dialog_account_toggleshownpwd_symbol").addClass("fa-eye");
	}
}

function dialog_account_changepass() {
	var npwd = $("#dialog_account_npwd").val();
	var conditions = npwd.length >= 8;
	if (conditions) {
		module_ajax("changepass", {cpwd: $("#dialog_account_cpwd").val(), npwd: $("#dialog_account_npwd").val()}, function (data) {
			if (data == "TRUE") {
				$("#dialog_account_cpwd").val("");
				$("#dialog_account_npwd").val("");
				$("#dialog_account_cpwd").parent().removeClass("has-success");
				$("#dialog_account_cpwd").parent().find(".glyphicon-ok").addClass("hidden");
				$("#dialog_account_npwd").parent().removeClass("has-success");
				window.alert("Password changed!");
			} else {
				window.alert("Couldn\'t change password.");
			}
		});
		$(".dialog_account_formfeedback_badnpwd").hide();
	} else {
		$(".dialog_account_formfeedback_badnpwd ").show();
	}
}

function dialog_account_delete(uid) {
	if (window.confirm("Are you sure you want to remove your account?", "Yes", "No")) {
		module_ajax("removeaccount", {uid: uid}, function (data) {
			if (data == "TRUE") {
				window.alert("Account removed.");
				logout();
			} else if (data == "OWNER") {
				window.alert("You can\'t remove your own account if you\'re the only owner of this site!");
			} else {
				window.alert("Account couldn\'t be removed.");
			}
		});
	}	
}

$(document).keydown(function(event) {
    if (event.ctrlKey || event.metaKey) {
        switch (String.fromCharCode(event.which).toLowerCase()) {
		case "a":
			if (!$("#dialog_account").is(":visible")) {
			    event.preventDefault();
			    showDialog("account");
                break;
			}
		}
    }
});',

//   ________
//  /        \
// (  Emails  )
//  \________/

"email-newuser" => function($name, $adminName, $url, $organization) {
	return '
<div>
	<h1>Hi ' . $name . '!</h1>
	<p>' . $adminName . ' created an account for you on the
	<a href="' . $url . '" title="' . $organization . '">' . $organization . ' website.</a>
	Your current password is <b>password</b> so please change it when you log in for the first time.</p>
	<a href="' . $url . '?p=secureaccess">Sign In</a>
</div>
<p>' . $organization . '</p>';
}


];

?>