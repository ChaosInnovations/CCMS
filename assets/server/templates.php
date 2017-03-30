<?php

// TODO: groupings

$TEMPLATES = [

// Always shown
"secure-navbar-start" =>
'
<nav class="navbar navbar-inverse fixed-top secure-nav">
<div class="container-fluid">
<div class="navbar-header">
<button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#snavbar-collapse" aria-expanded="false">
<span class="sr-only">Toggle Navigation</span>
<span class="icon-bar"></span>
<span class="icon-bar"></span>
<span class="icon-bar"></span>
</button>
</div>
<div class="collapse navbar-collapse" id="snavbar-collapse">
<ul class="nav navbar-nav">',

// Always shown
"secure-navbar-button-home" =>
'
<li><a href="./" title="Home"><span class="glyphicon glyphicon-home"></span></a></li>',

// Shown if user can edit
"secure-navbar-button-edit" =>
'
<li><a href="#" title="Edit Page" onclick="showDialog(\'edit\');"><span class="glyphicon glyphicon-pencil"></span></a></li>',

// Shown if user can create pages
"secure-navbar-button-create" =>
'
<li><a href="#" title="New Page" onclick="createPage();"><span class="glyphicon glyphicon-plus"></span></a></li>',

// Shown if user can view or create secure pages
"secure-navbar-dropdown-secure-start" =>
'
<li class="dropdown">
<a href="" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">Secure Pages <span class="caret"></span></a>
<ul class="dropdown-menu">',

"navbar-separator" =>
'
<li role="separator" class="divider"></li>',

// Shown if user can create secure pages
"secure-navbar-dropdown-secure-button-create" =>
'
<li><a href="#" title="New Secure Page" onclick="createSecurePage();"><span class="glyphicon glyphicon-plus"></span></a></li>',

"navbar-dropdown-end" =>
'
</ul></li>',

// Shown if user can manage modules
"secure-navbar-dropdown-modules-start" =>
'
<li class="dropdown">
<a href="" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">Modules <span class="caret"></span></a>
<ul class="dropdown-menu">',

// Shown if user has any admin privileges
"secure-navbar-dropdown-admin-start" =>
'
<li class="dropdown">
<a href="" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">Administration <span class="caret"></span></a>
<ul class="dropdown-menu">',

// Shown if user can manage pages
"secure-navbar-dropdown-admin-button-pages" =>
'
<li><a href="#" title="Manage Pages" onclick="showDialog(\'managepages\');">Page Manager</a></li>',

// Shown if user can manage site
"secure-navbar-dropdown-admin-button-site" =>
'
<li><a href="#" title="Manage Site" onclick="showDialog(\'managesite\');">Site Manager</a></li>',

// Shown if user can manage users
"secure-navbar-dropdown-admin-button-users" =>
'
<li><a href="#" title="Manage Users" onclick="showDialog(\'users\');">Users</a></li>',

// Always shown
"secure-navbar-dropdown-account-start" =>
'
<li class="dropdown">
<a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">Account <span class="caret"></span></a>
<ul class="dropdown-menu">',

// Always shown
"secure-navbar-dropdown-account-button-details" =>
'
<li><a href="#" title="Account Details" onclick="showDialog(\'account\');">Details</a></li>',

// Always shown
"secure-navbar-dropdown-account-button-logout" =>
'
<li><a href="#" title="Log Out" onclick="logout();"><span class="glyphicon glyphicon-log-out"></span></a></li>',

// Always shown
"secure-navbar-button-about" =>
'
<li><a href="#" title="About" onclick="showDialog(\'about\');">About</a></li>',

// Always shown
"secure-navbar-nav-end" =>
'
</ul>',

// Always shown
"secure-navbar-end" =>
'
</div></div></nav>',


//  ________
// /        \
//(  Modals  )
// \________/

// Modal Start

"secure-modal-start" => function ($id, $title) {
	return '
<div class="modal fade" id="' . $id . '" tabindex="-1" role="dialog" aria-labelledby="' . $id . '_title">
<div class="modal-dialog modal-lg" role="document">
<div class="modal-content">
<div class="modal-header">
<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
<h4 class="modal-title" id="' . $id . '_title">Edit Page</h4>
</div>
<div class="modal-body">';
},

// Modal End

"secure-modal-end" =>
'
</div></div></nav>',

// Edit Modal
//============

// Body
"secure-modal-edit-bodyfoot" =>
'
<form class="form-horizontal" role="edit" onsubmit="dialog_edit_save();return false;">
<div class="form-group">
<div class="col-md-offset-2 col-sm-offset-3 col-md-10 col-sm-9">
<input type="submit" class="btn btn-info" title="Save" value="Save">
<span class="dialog_edit_formfeedback_saved hidden">Saved!</span>
<span class="dialog_edit_formfeedback_notsaved hidden">There was an error saving. Check your connection.</span>
</div>
</div>
<div class="form-group has-feedback">
<label class="control-label col-sm-3 col-md-2" for="editname">Page ID:</label>
<div class="col-sm-9 col-md-10">
<input type="text" id="dialog_edit_pageid" name="pageid" class="form-control" title="Page ID" placeholder="Page ID" oninput="dialog_edit_check_pageid();">
<span class="glyphicon glyphicon-remove form-control-feedback hidden"></span>
<span class="glyphicon glyphicon-ok form-control-feedback hidden"></span>
</div>
</div>
<div class="form-group">
<label class="control-label col-sm-3 col-md-2" for="editshort-desc">Page Title:</label>
<div class="col-sm-9 col-md-10">
<input type="text" id="dialog_edit_pagetitle" name="pagetitle" class="form-control" title="Page Title" placeholder="Page Title">
</div>
</div>
<div class="form-group">
<label class="control-label col-sm-3 col-md-2" for="editlong-desc"><code>&lt;head&gt;</code>:</label>
<div class="col-sm-9 col-md-10">
<textarea id="dialog_edit_head" name="head" class="form-control monospace" title="Page Head" placeholder="Page Head" rows="8"></textarea>
</div>
</div>
<div class="form-group">
<label class="control-label col-sm-3 col-md-2" for="editcontact"><code>&lt;body&gt;</code>:</label>
<div class="col-sm-9 col-md-10">
<textarea id="dialog_edit_body" name="body" class="form-control monospace" title="Page Body" placeholder="Page Body" rows="32"></textarea>
</div>
</div>
</form>
</div>' .
// Foot
'
<div class="modal-footer">
<span class="dialog_edit_formfeedback_saved hidden">Saved!</span>
<span class="dialog_edit_formfeedback_notsaved hidden">There was an error saving. Check your connection.</span>
<button type="button" class="btn btn-info" onclick="dialog_edit_save();">Save changes</button>
<button type="button" class="btn btn-danger" onclick="dialog_edit_reset();">Reset Changes</button>
<button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
</div>',

// Script
"secure-modal-edit-script" => function ($pid, $rtitle, $rhead, $rbody) {
	return '
<script>
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

function dialog_edit_check_pageid() {
	module_ajax("ajax_checkpid", {pageid: pageid,
								  check: $("#dialog_edit_pageid").val(),
								  token: Cookies.get("token")}, function(data){
		var fieldparent = $("#dialog_edit_pageid").parent();
		if (data == "TRUE") {
			fieldparent.removeClass("has-error");
			fieldparent.addClass("has-success");
			fieldparent.find(".glyphicon-remove").addClass("hidden");
			fieldparent.find(".glyphicon-ok").removeClass("hidden");
		} else {
			fieldparent.removeClass("has-success");
			fieldparent.addClass("has-error");
			fieldparent.find(".glyphicon-ok").addClass("hidden");
			fieldparent.find(".glyphicon-remove").removeClass("hidden");
		}
	});
}

function dialog_edit_reset() {
	$("#dialog_edit_pageid").val(pageid);
	$("#dialog_edit_pagetitle").val(pagetitle);
	$("#dialog_edit_head").val(head);
	$("#dialog_edit_body").val(body);
}

function dialog_edit_save() {
	module_ajax("ajax_editpage", {pageid: pageid,
								  newpageid: $("#dialog_edit_pageid").val(),
	                              title: encodeURIComponent($("#dialog_edit_pagetitle").val()),
								  head: encodeURIComponent($("#dialog_edit_head").val()),
								  body: encodeURIComponent($("#dialog_edit_body").val()),
								  token: Cookies.get("token")}, function(data){
		if (data == "FALSE") {
			$(".dialog_edit_formfeedback_notsaved").removeClass("hidden");
			setTimeout(function(){$(".dialog_edit_formfeedback_notsaved").addClass("hidden");}, 1500);
		} else if (data == "TRUE") {
			$(".dialog_edit_formfeedback_saved").removeClass("hidden");
			setTimeout(function(){$(".dialog_edit_formfeedback_saved").addClass("hidden");window.location.reload();}, 800);
		} else {
			window.location = "?p="+data;
		}
	});
}
</script>';
},

// Page Manager Modal
//====================


];

?>