<?php

class builtin_placeholders {
	public $dependencies = [];
	
	function __construct() {
		global $conn, $sqlstat, $sqlerr;
		global $page;
	}
	
	function getMenu() {
		$menu = "";
		return $menu;
	}
	
	function loginform() {
		global $conn, $sqlstat, $sqlerr;
		global $authuser;
		if ($authuser->uid == null) {
			return urldecode("%3Cform%20id%3D%22loginform%22%20class%3D%22form%22%20onsubmit%3D%22return%20loginSubmission()%3B%22%3E%0A%3Cdiv%20class%3D%22form-group%20has-feedback%20col-xs-10%20col-xs-offset-1%22%3E%0A%3Clabel%20class%3D%22control-label%22%20for%3D%22loginemail%22%3EEmail%3A%3C%2Flabel%3E%0A%3Cinput%20type%3D%22text%22%20id%3D%22loginemail%22%20name%3D%22email%22%20class%3D%22form-control%22%20title%3D%22Email%22%20placeholder%3D%22Email%20Address%22%20oninput%3D%22loginCheckEmail()%3B%22%3E%0A%3Cspan%20class%3D%22glyphicon%20glyphicon-remove%20form-control-feedback%20hidden%22%3E%3C%2Fspan%3E%0A%3Cspan%20class%3D%22glyphicon%20glyphicon-ok%20form-control-feedback%20hidden%22%3E%3C%2Fspan%3E%0A%3C%2Fdiv%3E%0A%3Cdiv%20class%3D%22form-group%20has-feedback%20col-xs-10%20col-xs-offset-1%22%3E%0A%3Clabel%20class%3D%22control-label%22%20for%3D%22loginpass%22%3EPassword%3A%3C%2Flabel%3E%0A%3Cinput%20type%3D%22password%22%20id%3D%22loginpass%22%20name%3D%22pass%22%20class%3D%22form-control%22%20title%3D%22Password%22%20placeholder%3D%22Password%22%20oninput%3D%22loginCheckPass()%3B%22%3E%0A%3Cspan%20class%3D%22glyphicon%20glyphicon-remove%20form-control-feedback%20hidden%22%3E%3C%2Fspan%3E%0A%3Cspan%20class%3D%22glyphicon%20glyphicon-ok%20form-control-feedback%20hidden%22%3E%3C%2Fspan%3E%0A%3C%2Fdiv%3E%0A%3Cdiv%20class%3D%22form-group%20col-xs-10%20col-xs-offset-1%22%3E%0A%3Cinput%20type%3D%22submit%22%20class%3D%22btn%20btn-success%22%20title%3D%22Log%20In%22%20value%3D%22Log%20In%22%3E%0A%3C%2Fdiv%3E%0A%3C%2Fform%3E");
		} else {
			$html = "<h3>You're logged in.</h3>";
			if ($sqlstat) {
				$html .= "<div class=\"list-group\">";
				$stmt = $conn->prepare("SELECT pageid, title FROM content_pages WHERE secure=1 ORDER BY pageid ASC");
				$stmt->execute();$stmt->setFetchMode(PDO::FETCH_ASSOC);
				$pdatas = $stmt->fetchAll();
				foreach ($pdatas as $pd) {
					if ($authuser->permissions->page_viewsecure and !in_array($pd["pageid"], $authuser->permissions->page_viewblacklist)) {
						$title = urldecode($pd["title"]);
						$html .= "<a class=\"list-group-item\" href=\"?p={$pd["pageid"]}\" title=\"{$title}\">{$title}</a>";
					}
				}
				$html .= "</div>";
			}
			return $html;
		}
	}
	
	function queryerr() {
		global $page;
		return $page->queryerr;
	}
	
	function contactform() {
		return urldecode("%3Cscript%3E%0Afunction%20module_builtin_contactus_submit()%20%7B%0A%24(%22%23module_builtin_contactus_form_feedback%22).html(%22Sending...%22)%3B%0Aif%20(%24(%22%23module_builtin_contactus_form_name%22).val()%20!%3D%20%22%22%20%26%26%20%24(%22%23module_builtin_contactus_form_reply%22).val()%20!%3D%20%22%22%20%26%26%20%24(%22%23module_builtin_contactus_form_message%22).val()%20!%3D%20%22%22)%20%7B%0Amodule_ajax(%22contactform%22%2C%20%7B%0Aname%3A%20%24(%22%23module_builtin_contactus_form_name%22).val()%2C%0Areply%3A%20%24(%22%23module_builtin_contactus_form_reply%22).val()%2C%0Amessage%3A%20%24(%22%23module_builtin_contactus_form_message%22).val()%2C%0A%7D%2C%20function(data)%20%7B%0Aif%20(data%20%3D%3D%20%22TRUE%22)%20%7B%0A%24(%22%23module_builtin_contactus_form_feedback%22).html(%22Your%20message%20was%20sent.%22)%3B%0AsetTimeout(function()%7B%24(%22%23module_builtin_contactus_form_feedback%22).html(%22%22)%7D%2C%20800)%3B%0A%7D%20else%20%7B%0A%24(%22%23module_builtin_contactus_form_feedback%22).html(%22Your%20message%20couldn%27t%20be%20sent.%22)%3B%0AsetTimeout(function()%7B%24(%22%23module_builtin_contactus_form_feedback%22).html(%22%22)%7D%2C%20800)%3B%0A%7D%0A%7D)%3B%0A%7D%20else%20%7B%0A%24(%22%23module_builtin_contactus_form_feedback%22).html(%22Your%20message%20couldn%27t%20be%20sent.%20Make%20sure%20you%20have%20completed%20the%20form.%22)%3B%0AsetTimeout(function()%7B%24(%22%23module_builtin_contactus_form_feedback%22).html(%22%22)%7D%2C%20800)%3B%0A%7D%0A%7D%0A%3C%2Fscript%3E%0A%3Cform%20class%3D%22form%22%20onsubmit%3D%22module_builtin_contactus_submit()%3Breturn%20false%3B%22%3E%0A%3Cdiv%20class%3D%22form-group%20col-xs-10%20col-xs-offset-1%22%3E%0A%3Clabel%20class%3D%22control-label%22%20for%3D%22module_builtin_contactus_form_name%22%3EName%3A%3C%2Flabel%3E%0A%3Cinput%20type%3D%22text%22%20id%3D%22module_builtin_contactus_form_name%22%20name%3D%22name%22%20class%3D%22form-control%22%20title%3D%22Name%22%20placeholder%3D%22Name%22%3E%0A%3C%2Fdiv%3E%0A%3Cdiv%20class%3D%22form-group%20col-xs-10%20col-xs-offset-1%22%3E%0A%3Clabel%20class%3D%22control-label%22%20for%3D%22module_builtin_contactus_form_reply%22%3EEmail%20or%20Phone%20Number%3A%3C%2Flabel%3E%0A%3Cinput%20type%3D%22text%22%20id%3D%22module_builtin_contactus_form_reply%22%20name%3D%22reply%22%20class%3D%22form-control%22%20title%3D%22Email%20or%20Phone%20Number%22%20placeholder%3D%22Email%20or%20Phone%20Number%22%3E%0A%3C%2Fdiv%3E%0A%3Cdiv%20class%3D%22form-group%20col-xs-10%20col-xs-offset-1%22%3E%0A%3Clabel%20class%3D%22control-label%22%20for%3D%22module_builtin_contactus_form_message%22%3EMessage%3A%3C%2Flabel%3E%0A%3Ctextarea%20class%3D%22form-control%22%20name%3D%22message%22%20id%3D%22module_builtin_contactus_form_message%22%20title%3D%22Message%22%20placeholder%3D%22Message%22%20rows%3D%224%22%3E%0A%3C%2Ftextarea%3E%0A%3C%2Fdiv%3E%0A%3Cdiv%20class%3D%22form-group%20col-xs-10%20col-xs-offset-1%22%3E%0A%3Cinput%20type%3D%22submit%22%20class%3D%22btn%20btn-info%22%20title%3D%22Send%20Message%22%20value%3D%22Send%20Message%22%3E%0A%3Cp%20class%3D%22form-text%22%20id%3D%22module_builtin_contactus_form_feedback%22%3E%3C%2Fp%3E%0A%3C%2Fdiv%3E%0A%3C%2Fform%3E");
	}
	
	function sitemap() {
		global $conn, $sqlstat, $sqlerr;
		global $authuser;
		if ($sqlstat) {
			$content = "<div>";
			$stmt = $conn->prepare("SELECT pageid, title, secure FROM content_pages ORDER BY pageid ASC;");
			$stmt->execute();$stmt->setFetchMode(PDO::FETCH_ASSOC);
			$pdatas = $stmt->fetchAll();
			foreach ($pdatas as $pd) {
				if ($pd["secure"] == "1" and !$authuser->permissions->page_viewsecure or in_array($pd["pageid"], $authuser->permissions->page_viewblacklist)) {
					continue;
				} else if ($pd["pageid"] == "notfound") {
					continue;
				} else {
					$title = urldecode($pd["title"]);
					$content  .= "<li><a href=\"?p={$pd["pageid"]}\" title=\"{$title}\">{$title}</a></li>";
				}
			}
			$content .= "</ul>";
		} else {
			$content = "";
		}
		return $content;
	}
	
	function pagerevision() {
		global $page;
		return $page->revision;
	}
	
	function ajax_contactform() {
		global $authuser, $mailer;
		if (isset($_POST["name"]) and isset($_POST["reply"]) and isset($_POST["message"])) {
			$subject = "Message from {$_POST["name"]}";
			$htmlbody = "<h1>Message from {$_POST["name"]}</h1><h3>Reply: {$_POST["reply"]}</h3><h4>Message:</h4><p>{$_POST["message"]}</p>";
			$body = "Message from {$_POST["name"]}\n\nReply: {$_POST["reply"]}\n\nMessage:\n\n{$_POST["message"]}";
			$mail = $mailer->compose([["info@penderbus.org"]], $subject, $htmlbody, $body);
			if ($mail->send()) {
				return "TRUE";
			} else {
				return "FALSE";
			}
		} else {
			return "FALSE";
		}
	}
}

?>