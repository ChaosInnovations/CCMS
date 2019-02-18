<?php

use \Lib\CCMS\Security\User;
use \Mod\Database;
use \Mod\Mailer;

class builtin_placeholders {
	public $dependencies = [];
	
	function place_loginform() {
		if (User::$currentUser->uid == null) {
			$html = '
<form id="loginform" class="form" onsubmit="return loginSubmission();">
	<div class="form-group">
		<label class="col-form-label" for="loginemail">Email Address</label>
		<div class="input-group">
			<input type="email" id="loginemail" autocomplete="email" class="form-control border-right-0 border-secondary" title="Email" placeholder="Email Address" oninput="loginCheckEmail();">
			<div class="input-group-append">
				<div class="input-group-text bg-transparent border-left-0 border-secondary">
					<i class="fas fa-times" style="display:none;"></i>
					<i class="fas fa-check" style="display:none;"></i>
				</div>
			</div>
		</div>
	</div>
	<div class="form-group">
		<label class="col-form-label" for="loginpass">Current Password</label>
		<div class="input-group">
			<input type="password" id="loginpass" autocomplete="current-password" class="form-control border-right-0 border-secondary" title="Password" placeholder="Password" oninput="loginCheckPass();">
			<div class="input-group-append">
				<div class="input-group-text bg-transparent border-left-0 border-secondary">
					<i class="fas fa-times" style="display:none;"></i>
					<i class="fas fa-check" style="display:none;"></i>
				</div>
			</div>
		</div>
	</div>
	<div class="form-group">
		<input id="loginbutton" type="submit" class="btn btn-success" title="Log In" value="Log In">
	</div>
</form>
<script>
var isLoggingIn = false;

function loginCheckEmail() {
	module_ajax("checkuser", {email: $("#loginemail").val()}, function (data) {
		if (data == "TRUE") {
			$("#loginemail").parent().removeClass("has-error");
			$("#loginemail").parent().addClass("has-success");
			$("#loginemail").parent().find(".fa-times").hide();
			$("#loginemail").parent().find(".fa-check").show();
		} else {
			$("#loginemail").parent().removeClass("has-success");
			$("#loginemail").parent().addClass("has-error");
			$("#loginemail").parent().find(".fa-check").hide();
			$("#loginemail").parent().find(".fa-times").show();
		}
	});
	loginCheckPass();
}

function loginCheckPass() {
	module_ajax("checkpass", {email: $("#loginemail").val(), password: $("#loginpass").val()}, function(data) {
		if (data == "TRUE") {
			$("#loginpass").parent().removeClass("has-error");
			$("#loginpass").parent().addClass("has-success");
			$("#loginpass").parent().find(".fa-times").hide();
			$("#loginpass").parent().find(".fa-check").show();
			loginSubmission();
		} else {
			$("#loginpass").parent().removeClass("has-success");
			$("#loginpass").parent().addClass("has-error");
			$("#loginpass").parent().find(".fa-check").addClass("hidden");
			$("#loginpass").parent().find(".fa-times").removeClass("hidden");
		}
	});
}

function loginSubmission() {
	if (isLoggingIn) {
		return false;
	}
	isLoggingIn = true;
	$("#loginbutton")[0].disabled = true;
	module_ajax("newtoken", {email: $("#loginemail").val(), password: $("#loginpass").val()}, function(data) {
		if (data != "FALSE") {
			var d = new Date(Date.now()+(3600000*24*30));
			document.cookie = "token="+data+"; expires="+d.toUTCString()+"; path=/";
			if (window.location.search.indexOf("?n") == -1) {
				window.location.reload(true);
			} else {
				var url = BASE_URL + "/" + window.location.search.substr(window.location.search.indexOf("?n")+3);
				window.location.assign(url);
			}
		} else {
			$("#loginbutton")[0].disabled = false;
			isLoggingIn = false;
		}
	});
	return false;
}
</script>';
			return $html;
		} else {
			$html = "<h5 class=\"card-title\">You're logged in.</h5>";
			$html .= "<h6 class=\"card-subtitle mb-2 text-muted\">You now have access to these pages:</h6>";
            $html .= "<div class=\"list-group\">";
            $stmt = Database::Instance()->prepare("SELECT pageid, title FROM content_pages WHERE secure=1 AND pageid NOT LIKE '_default/%' ORDER BY pageid ASC;");
            $stmt->execute();$stmt->setFetchMode(PDO::FETCH_ASSOC);
            $pdatas = $stmt->fetchAll();
            foreach ($pdatas as $pd) {
                if (User::$currentUser->permissions->page_viewsecure and !in_array($pd["pageid"], User::$currentUser->permissions->page_viewblacklist)) {
                    $title = urldecode($pd["title"]);
                    $html .= "<a class=\"list-group-item list-group-item-action\" href=\"/{$pd["pageid"]}\" title=\"{$title}\">{$title}</a>";
                }
            }
            $html .= "</div>";
			return $html;
		}
	}
	
	function place_queryerr() {
		return $_SERVER['REQUEST_URI'];
	}
	
	function place_contactform() {
		$html = '
<form onsubmit="module_builtin_contactus_submit();return false;">
	<div class="row">
	<div class="form-group col-10 offset-1">
		<label class="control-label" for="module_builtin_contactus_form_name">Name:</label>
		<input type="text" id="module_builtin_contactus_form_name" name="name" class="form-control" title="Name" placeholder="Name">
	</div>
	<div class="form-group col-10 offset-1">
		<label class="control-label" for="module_builtin_contactus_form_reply">Email or Phone Number:</label>
		<input type="text" id="module_builtin_contactus_form_reply" name="reply" class="form-control" title="Email or Phone Number" placeholder="Email or Phone Number">
	</div>
	<div class="form-group col-10 offset-1">
		<label class="control-label" for="module_builtin_contactus_form_message">Message:</label>
		<textarea class="form-control" name="message" id="module_builtin_contactus_form_message" title="Message" placeholder="Message" rows="4"></textarea>
	</div>
		<div class="form-group col-10 offset-1">
		<input type="submit" class="btn btn-info" title="Send Message" value="Send Message">
		<p class="form-text" id="module_builtin_contactus_form_feedback"></p>
	</div>
	</div>
</form>
<script>
function module_builtin_contactus_submit() {
	$("#module_builtin_contactus_form_feedback").html("Sending...");
	if ($("#module_builtin_contactus_form_name").val() != "" && $("#module_builtin_contactus_form_reply").val() != "" && $("#module_builtin_contactus_form_message").val() != "") {
		module_ajax("contactform", {name: $("#module_builtin_contactus_form_name").val(),
									reply: $("#module_builtin_contactus_form_reply").val(),
									message: $("#module_builtin_contactus_form_message").val()}, function(data) {
			if (data == "TRUE") {
				$("#module_builtin_contactus_form_feedback").html("Your message was sent.");
			} else {
				$("#module_builtin_contactus_form_feedback").html("Your message couldn\'t be sent.");
			}
			setTimeout(function(){$("#module_builtin_contactus_form_feedback").html("")}, 10000);
		});
	} else {
		$("#module_builtin_contactus_form_feedback").html("Your message couldn\'t be sent. Make sure you have completed the form.");
		setTimeout(function(){$("#module_builtin_contactus_form_feedback").html("")}, 10000);
	}
}
</script>';
		return $html;
		return urldecode("");
	}
	
	function place_sitemap() {
        $content = "<ul>";
        $stmt = Database::Instance()->prepare("SELECT pageid, title, secure FROM content_pages WHERE pageid NOT LIKE '_default/%' ORDER BY pageid ASC;");
        $stmt->execute();$stmt->setFetchMode(PDO::FETCH_ASSOC);
        $pdatas = $stmt->fetchAll();
        foreach ($pdatas as $pd) {
            if ($pd["secure"] == "1" and !User::$currentUser->permissions->page_viewsecure or in_array($pd["pageid"], User::$currentUser->permissions->page_viewblacklist)) {
                continue;
            } else {
                $title = urldecode($pd["title"]);
                $content  .= "<li><a href=\"/{$pd["pageid"]}\" title=\"{$title}\">{$title}</a></li>";
            }
        }
        $content .= "</ul>";
        
		return $content;
	}
	
	function place_pagerevision() {
		global $page;
		return $page->revision;
	}
	
	function ajax_contactform() {
		if (!isset($_POST["name"]) ||
		    !isset($_POST["reply"]) ||
			!isset($_POST["message"])) {
			return "FALSE";
		}
		
		$htmlbody  = "<h2>Message from {$_POST["name"]}</h2>";
		$htmlbody .= "<h4>Reply: {$_POST["reply"]}</h4>";
		$htmlbody .= "<p><strong>Message:</strong></p>";
		$htmlbody .= "<p>{$_POST["message"]}</p>";
		$htmlbody .= "<small>This message was sent using the online Contact form.</small>";
		
		$body  = "Message from {$_POST["name"]}\n";
		$body .= "Reply: {$_POST["reply"]}\n";
		$body .= "================================\n\n";
		$body .= "Message:\n";
		$body .= "{$_POST["message"]}\n\n";
		$body .= "This message was send using the online Contact form.";
		
		$mail = Mailer::Instance()->compose([[$mailer->user]], "Message from {$_POST["name"]}", $htmlbody, $body);
		if (!$mail->send()) {
			return "FALSE";
		}
		
		return "TRUE";
	}
}

?>