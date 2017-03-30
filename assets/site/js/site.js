function logout() {
	//Delete token cookie
	document.cookie = "token=; expires=Thu, 01 Jan 1970 00:00:00 UTC path=/";
	window.location.reload(true);
}

function loginCheckEmail() {
	$.get("assets/server/secure.php?checkuser="+$("#loginemail").val(), function(data) {
		if (data == "TRUE") {
			$("#loginemail").parent().removeClass("has-error");
			$("#loginemail").parent().addClass("has-success");
			$("#loginemail").parent().find(".glyphicon-remove").addClass("hidden");
			$("#loginemail").parent().find(".glyphicon-ok").removeClass("hidden");
		} else {
			$("#loginemail").parent().removeClass("has-success");
			$("#loginemail").parent().addClass("has-error");
			$("#loginemail").parent().find(".glyphicon-ok").addClass("hidden");
			$("#loginemail").parent().find(".glyphicon-remove").removeClass("hidden");
		}
	});
	loginCheckPass();
}

function loginCheckPass() {
	$.get("assets/server/secure.php?checkpass="+$("#loginpass").val()+"&user="+$("#loginemail").val(), function(data) {
		if (data == "TRUE") {
			$("#loginpass").parent().removeClass("has-error");
			$("#loginpass").parent().addClass("has-success");
			$("#loginpass").parent().find(".glyphicon-remove").addClass("hidden");
			$("#loginpass").parent().find(".glyphicon-ok").removeClass("hidden");
		} else {
			$("#loginpass").parent().removeClass("has-success");
			$("#loginpass").parent().addClass("has-error");
			$("#loginpass").parent().find(".glyphicon-ok").addClass("hidden");
			$("#loginpass").parent().find(".glyphicon-remove").removeClass("hidden");
		}
	});
}

function loginSubmission() {
	$.get("assets/server/secure.php?newtoken="+$("#loginemail").val()+"&pass="+$("#loginpass").val(), function(data) {
		if (data != "FALSE") {
			var d = new Date(Date.now()+(3600000*24*30));
			document.cookie = "token="+data+"; expires="+d.toUTCString()+"; path=/";
			window.location.reload(true);
		}
	});
	return false;
}

function showDialog(dialog) {
	$("#dialog_"+dialog).modal("show");
}

function createPage() {
	$.post("assets/server/pagegen.php?newpage", {token: Cookies.get("token")}, function(data) {
		console.log(data);
		if (data != "FALSE") {
			window.location = "?p="+data;
		}
	});
}

function createSecurePage() {
	$.post("assets/server/pagegen.php?newpage", {token: Cookies.get("token"), s: true}, function(data) {
		console.log(data);
		if (data != "FALSE") {
			window.location = "?p="+data;
		}
	});
}

/*
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
*/

function dialog_managesite_save() {
	module_ajax("ajax_setconfig", {websitetitle: $("#dialog_managesite_websitetitle").val(),
	                               primaryemail: $("#dialog_managesite_primaryemail").val(),
								   secondaryemail: $("#dialog_managesite_secondaryemail").val(),
								   defaulttitle: encodeURIComponent($("#dialog_managesite_defaulttitle").val()),
								   defaulthead: encodeURIComponent($("#dialog_managesite_defaulthead").val()),
								   defaultbody: encodeURIComponent($("#dialog_managesite_defaultbody").val()),
								   defaultnav: encodeURIComponent($("#dialog_managesite_defaultnav").val()),
								   defaultfoot: encodeURIComponent($("#dialog_managesite_defaultfoot").val()),
	                               token: Cookies.get("token")}, function(data){
		if (data == "TRUE") {
			window.alert("Website settings saved.");
			window.location.reload(true);
		} else {
			window.alert("Couldn't save settings.");
		}
	});
}

function dialog_users_new() {
	email = $("#dialog_users_newemail").val();
	name = $("#dialog_users_newname").val();
	permissions = "";
	permissions += $("#dialog_users_permission_owner").prop("checked") ? "owner;" : "";
	permissions += $("#dialog_users_permission_admin_managepages").prop("checked") ? "admin_managepages;" : "";
	permissions += $("#dialog_users_permission_admin_managesite").prop("checked") ? "admin_managesite;" : "";
	permissions += $("#dialog_users_permission_page_viewsecure").prop("checked") ? "page_viewsecure;" : "";
	permissions += $("#dialog_users_permission_page_editsecure").prop("checked") ? "page_editsecure;" : "";
	permissions += $("#dialog_users_permission_page_createsecure").prop("checked") ? "page_createsecure;" : "";
	permissions += $("#dialog_users_permission_page_deletesecure").prop("checked") ? "page_deletesecure;" : "";
	permissions += $("#dialog_users_permission_page_edit").prop("checked") ? "page_edit;" : "";
	permissions += $("#dialog_users_permission_page_create").prop("checked") ? "page_create;" : "";
	permissions += $("#dialog_users_permission_page_delete").prop("checked") ? "page_delete;" : "";
	permissions += $("#dialog_users_permission_toolbar").prop("checked") ? "toolbar;" : "";
	$.post("assets/server/secure.php?newuser", {
		token: Cookies.get("token"),
		email: email,
		name: name,
		permissions: permissions},
		function (data) {
			if (data == "TRUE") {
				window.alert("Created account with email \""+email+"\" and password \"password\".");
				window.location.reload(true);
			} else {
				window.alert("Couldn't create account. Is "+email+" already in use?");
			}
		}
	);
}

function dialog_users_update(uid) {
	module_ajax("edituser", {permissions: $("#dialog_users_"+uid+"_perms_p").val(),
	                         permviewbl: $("#dialog_users_"+uid+"_perms_v").val(),
							 permeditbl: $("#dialog_users_"+uid+"_perms_e").val(),
							 uid: uid,
							 token: Cookies.get("token")}, function (data){
		if (data == "TRUE") {
			window.alert("Account updated.");
		} else {
			window.alert("Couldn't update this account.");
		}
	});
	
}

function dialog_users_delete(uid) {
	if (window.confirm("Are you sure you want to remove this account?", "Yes", "No")) {
		$.post("assets/server/secure.php?removeaccount", {
			token: Cookies.get("token"),
			uid: uid},
			function (data) {
				if (data == "TRUE") {
					window.alert("Account removed.");
					window.location.reload(true);
				} else if (data == "OWNER") {
					window.alert("You can't remove your own account if you're the only owner on this site!");
				} else {
					window.alert("Account couldn't be removed.");
				}
			}
		);
	}
	
}

function dialog_users_reset(uid) {
	$.post("assets/server/secure.php?resetpwd", {
		token: Cookies.get("token"),
		uid: uid},
		function (data) {
			if (data == "TRUE") {
				window.alert("Password reset to \"password\".");
			} else {
				window.alert("Something went wrong trying to reset a password.");
			}
		}
	);
}

function dialog_account_save() {
	$.post("assets/server/secure.php?edituser",
		{token: Cookies.get("token"),
		name: $("#dialog_account_name").val()},
		function (data) {
			if (data == "TRUE") {
				$(".dialog_account_formfeedback_saved").removeClass("hidden");
				setTimeout(function(){$(".dialog_account_formfeedback_saved").addClass("hidden");window.location.reload(true);}, 800);
			} else {
				$(".dialog_account_formfeedback_notsaved").removeClass("hidden");
				setTimeout(function(){$(".dialog_account_formfeedback_notsaved").addClass("hidden");}, 800);
			}
		}
	);
}

function dialog_account_check_cpwd() {
	$.post("assets/server/secure.php?checkcpwd="+$("#dialog_account_cpwd").val(), {token: Cookies.get("token")}, function(data) {
		if (data == "TRUE") {
			$("#dialog_account_cpwd").parent().removeClass("has-error");
			$("#dialog_account_cpwd").parent().addClass("has-success");
			$("#dialog_account_cpwd").parent().find(".glyphicon-remove").addClass("hidden");
			$("#dialog_account_cpwd").parent().find(".glyphicon-ok").removeClass("hidden");
		} else {
			$("#dialog_account_cpwd").parent().removeClass("has-success");
			$("#dialog_account_cpwd").parent().addClass("has-error");
			$("#dialog_account_cpwd").parent().find(".glyphicon-ok").addClass("hidden");
			$("#dialog_account_cpwd").parent().find(".glyphicon-remove").removeClass("hidden");
		}
	});
}

function dialog_account_check_npwd() {
	var npwd = $("#dialog_account_npwd").val();
	var conditions = npwd.length >= 8 ? true : false;
	if (conditions) {
		$("#dialog_account_npwd").parent().removeClass("has-error");
		$("#dialog_account_npwd").parent().addClass("has-success");
		$(".dialog_account_formfeedback_badnpwd").addClass("hidden");
	} else {
		$("#dialog_account_npwd").parent().removeClass("has-success");
		$("#dialog_account_npwd").parent().addClass("has-error");
		$(".dialog_account_formfeedback_badnpwd ").removeClass("hidden");
	}
}

function dialog_account_toggleshownpwd() {
	if ($("#dialog_account_npwd").attr("type") == "password") {
		$("#dialog_account_npwd").attr("type", "text");
		$("#dialog_account_toggleshownpwd_symbol").removeClass("glyphicon-eye-open");
		$("#dialog_account_toggleshownpwd_symbol").addClass("glyphicon-eye-close");
	} else {
		$("#dialog_account_npwd").attr("type", "password");
		$("#dialog_account_toggleshownpwd_symbol").removeClass("glyphicon-eye-close");
		$("#dialog_account_toggleshownpwd_symbol").addClass("glyphicon-eye-open");
	}
}

function dialog_account_changepass() {
	var npwd = $("#dialog_account_npwd").val();
	var conditions = npwd.length >= 8 ? true : false;
	if (conditions) {
		$.post("assets/server/secure.php?changepass",
			{token: Cookies.get("token"),
			cpwd: $("#dialog_account_cpwd").val(),
			npwd: $("#dialog_account_npwd").val()},
			function (data) {
				if (data == "TRUE") {
					$("#dialog_account_cpwd").val("");
					$("#dialog_account_npwd").val("");
					$("#dialog_account_cpwd").parent().removeClass("has-success");
					$("#dialog_account_cpwd").parent().find(".glyphicon-ok").addClass("hidden");
					$("#dialog_account_npwd").parent().removeClass("has-success");
					window.alert("Password changed!");
				} else {
					window.alert("Couldn't change password.");
				}
			}
		);
		$(".dialog_account_formfeedback_badnpwd").addClass("hidden");
	} else {
		$(".dialog_account_formfeedback_badnpwd ").removeClass("hidden");
	}
}

function module_ajax(func, args, call) {
	$.post("assets/server/?func="+func, args, call);
}