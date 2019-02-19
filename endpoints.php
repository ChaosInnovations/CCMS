<?php

// Endpoints are checked in the order they appear in this file.
// Every matching endpoint will be called. The first one that returns an
// instance of Response stops execution and the Response is sent.
// If no endpoints are found, will respond with a 404
// Endpoints are prefixed by either web: or cli: depending on whether
// run by a web server or by command line interface (i.e. by cron)

$endpoints = [
    '/.*/' => "\Mod\Database::hookOpenConnection", // Connect to database
    '/^web:.*/' => "\Lib\CCMS\Security\User::hookAuthenticateFromRequest", // Sign in
    
    '/^web:\/?api\/checkuser\/?$/i' => "\Lib\CCMS\Security\User::hookCheckUser", // Check that the username is correct
    '/^web:\/?api\/checkpass\/?$/i' => "\Lib\CCMS\Security\User::hookCheckPassword", // Check that the password is correct
    '/^web:\/?api\/newtoken\/?$/i' => "\Lib\CCMS\Security\AccountManager::hookNewToken", // Create a new token if authenticated
    '/^web:\/?api\/user\/new\/?$/i' => "\Lib\CCMS\Security\User::hookNewUser", // Create a new user
    '/^web:\/?api\/user\/edit\/?$/i' => "\Lib\CCMS\Security\User::hookEditUser", // Edit a user
    '/^web:\/?api\/user\/remove\/?$/i' => "\Lib\CCMS\Security\User::hookRemoveUser", // Remove a user
    '/^web:\/?api\/user\/password\/reset\/?$/i' => "\Lib\CCMS\Security\User::hookPasswordReset", // Reset password to default
    '/^web:\/?api\/user\/password\/edit\/?$/i' => "\Lib\CCMS\Security\User::hookPasswordChange", // Change password
    
    '/^web:\/?api\/config\/set\/?$/i' => "\Lib\CCMS\Utilities::hookSetConfig", // Set configuration
    
    '/^web:\/?api\/collab_update\/?$/i' => "\Lib\CCMS\CollabUpdateEndpoint::hook", // Collaboration status update
    
    '/^web:\/?api\/page\/new\/?$/i' => "\Mod\Page::hookNewPage",
    '/^web:\/?api\/page\/remove\/?$/i' => "\Mod\Page::hookRemovePage",
    '/^web:\/?api\/page\/edit\/?$/i' => "\Mod\Page::hookEditPage",
    '/^web:\/?api\/page\/secure\/?$/i' => "\Mod\Page::hookSecurePage",
    '/^web:\/?api\/page\/checkpid\/?$/i' => "\Mod\Page::hookCheckPid",
    
    '/(?!.*\.[a-z]*$)^web:.*$/i' => "\Mod\Page::hook", // Capture all remaining endpoints that don't have an extension
];