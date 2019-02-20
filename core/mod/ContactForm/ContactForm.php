<?php

namespace Mod;

use \Lib\CCMS\Request;
use \Lib\CCMS\Response;
use \Mod\Mailer;

class ContactForm
{
    public static function placeholderForm()
    {
        $html = file_get_contents(dirname(__FILE__) . "/ContactForm.template.html");
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