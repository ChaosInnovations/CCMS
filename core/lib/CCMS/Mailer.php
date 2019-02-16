<?php

namespace Lib\CCMS;

use \Lib\PHPMailer\PHPMailer;

class Mailer {
	
	public $host;
	public $port;
	public $SMTPAuth = true;
	public $username;
	public $password;
	public $SMTPSecure = "ssl";
	public $from;
	
	function __construct() {
		
	}
	
	function compose($to, $subject, $htmlbody, $body="", $attachments=[], $cc=[], $bcc=[]) {
		$mail = new PHPMailer(false);
		$mail->isSMTP();
		$mail->Host = $this->host;
		//$mail->Port = $this->port;
		$mail->SMTPAuth = $this->SMTPAuth;
		$mail->Username = $this->username;
		$mail->Password = $this->password;
		//$mail->SMTPSecure = $this->SMTPSecure;
		$mail->setFrom($this->username, $this->from);
		foreach ($to as $recipient) {
			if (count($recipient) == 2) {
				$mail->addAddress($recipient[0], $recipient[1]);
			} else {
				$mail->addAddress($recipient[0]);
			}
		}
		foreach ($cc as $ccrep) {
			$mail->addCC($ccrep);
		}
		foreach ($bcc as $bccrep) {
			$mail->addBCC($bccrep);
		}
		foreach ($attachments as $attach) {
			if (count($attach) == 2) {
				$mail->addAttachment($attach[0], $attach[1]);
			} else {
				$mail->addAttachment($attach[0]);
			}
		}
		$mail->isHTML(true);
		$mail->Subject = $subject;
		$mail->Body = $htmlbody;
		$mail->AltBody = $body;
		return $mail;
	}
	
}