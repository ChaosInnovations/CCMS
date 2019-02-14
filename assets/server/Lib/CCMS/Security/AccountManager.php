<?php

namespace Lib\CCMS\Security;

use \PDO;

class AccountManager
{
    public static function registerNewToken($uid, $ip)
    {
        global $conn;
        
        // Kill other tokens from this uid
        $stmt = $conn->prepare("UPDATE tokens SET forcekill=1 WHERE uid=:uid;");
        $stmt->bindParam(":uid", $uid);
        $stmt->execute();
        AccountManager::removeBadTokens();
        
        $token = "";
        $tokenIsAvailable = false;
        
        while (!$tokenIsAvailable) {
            $token = bin2hex(openssl_random_pseudo_bytes(16));
            
            $stmt = $conn->prepare("SELECT * FROM tokens WHERE tid=:tid;");
            $stmt->bindParam(":tid", $token);
            $stmt->execute();$stmt->setFetchMode(PDO::FETCH_ASSOC);
            
            $tokenIsAvailable = count($stmt->fetchAll()) == 0;
        }
        
        $now = date("Y-m-d", time());
        $end = date("Y-m-d", time()+3600*24*30); // 30-day expiry
        
        $stmt = $conn->prepare("INSERT INTO tokens VALUES (:uid, :tid, :ip, :start, :expire, 0);");
        $stmt->bindParam(":uid", $uid);
        $stmt->bindParam(":tid", $token);
        $stmt->bindParam(":ip", $ip);
        $stmt->bindParam(":start", $now);
        $stmt->bindParam(":expire", $end);
        $stmt->execute();
        
        return $token;
    }
    
    public static function removeBadTokens()
    {
        global $conn, $sqlstat;
        
        if (!$sqlstat) {
            return;
        }
        
        $now = date("Y-m-d", time());
        $stmt = $conn->prepare("DELETE FROM tokens WHERE expire<=:now OR forcekill!=0;");
        $stmt->bindParam(":now", $now);
        $stmt->execute();
    }
    
    public static function validateToken($token, $ip)
    {
        global $conn, $sqlstat, $sqlerr;
        
        AccountManager::removeBadTokens();
        
        if (!$sqlstat) {
            return false;
        }
        
        $now = date("Y-m-d");
        
        $stmt = $conn->prepare("SELECT * FROM tokens WHERE tid=:tid AND source_ip=:ip;");
        $stmt->bindParam(":tid", $token);
        $stmt->bindParam(":ip", $ip);
        $stmt->execute();$stmt->setFetchMode(PDO::FETCH_ASSOC);
        
        return count($stmt->fetchAll()) == 1;
    }
}
