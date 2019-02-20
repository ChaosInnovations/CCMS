<?php

namespace Mod;

use \Lib\CCMS\Request;
use \Lib\CCMS\Response;
use \Mod\Mailer;

class ContactForm
{
    public static function placeholderForm()
    {
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
        module_ajax("contactform/response", {name: $("#module_builtin_contactus_form_name").val(),
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
    
    public static function hookFormResponse(Request $request)
    {
        // api/contactform/response
        if (!isset($_POST["name"]) ||
		    !isset($_POST["reply"]) ||
			!isset($_POST["message"])) {
			return new Response("FALSE");
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
		
		$mail = Mailer::Instance()->compose([[Mailer::Instance()->username]], "Message from {$_POST["name"]}", $htmlbody, $body);
		if (!$mail->send()) {
			return new Response("FALSE");
		}
		
		return new Response("TRUE");
    }
}