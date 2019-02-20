<?php

namespace Mod;

use \Lib\CCMS\Request;
use \Lib\CCMS\Response;
use \Lib\CCMS\Utilities;
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

        $template_vars = [
            "name" => $_POST["name"],
            "reply" => $_POST["reply"],
            "message" => $_POST["message"],
        ];

        $htmlbody = Utilities::fillTemplate(file_get_contents(dirname(__FILE__) . "/ContactFormEmail.template.html"), $template_vars);
        $altbody = Utilities::fillTemplate(file_get_contents(dirname(__FILE__) . "/ContactFormEmail.template.txt"), $template_vars);

        $mail = Mailer::Instance()->compose([[Mailer::Instance()->username]], "Message from {$_POST["name"]}", $htmlbody, $altbody);
        if (!$mail->send()) {
            return new Response("FALSE");
        }

        return new Response("TRUE");
    }
}