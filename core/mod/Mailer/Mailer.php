<?php

namespace Mod;

use \Lib\PHPMailer\PHPMailer;
use \Mod\SiteConfiguration;

class Mailer {
	
    // Singleton pattern
    private static $instance = null;
    
    public static function Instance()
    {
        if (!self::$instance instanceof Mailer) {
            self::$instance = new static();
            self::$instance->host     = SiteConfiguration::getconfig("email_primary_host");
            self::$instance->username = SiteConfiguration::getconfig("email_primary_user");
            self::$instance->password = SiteConfiguration::getconfig("email_primary_pass");
            self::$instance->from     = SiteConfiguration::getconfig("email_primary_from");
        }
        
        return self::$instance;
    }
    
    // Singleton pattern (legacy notification mailer
    private static $notifInstance = null;
    
    public static function NotifInstance()
    {
        if (!self::$notifInstance instanceof Mailer) {
            self::$notifInstance = new static();
            self::$notifInstance->host     = SiteConfiguration::getconfig("email_notifs_host");
            self::$notifInstance->username = SiteConfiguration::getconfig("email_notifs_user");
            self::$notifInstance->password = SiteConfiguration::getconfig("email_notifs_pass");
            self::$notifInstance->from     = SiteConfiguration::getconfig("email_notifs_from");
        }
        
        return self::$notifInstance;
    }
    
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