<?php

// Endpoints are checked in the order they appear in this file.
// Every matching endpoint will be called. The first one that returns an
// instance of Response stops execution and the Response is sent.
// If no endpoints are found, will respond with a 404
// Endpoints are prefixed by either web: or cli: depending on whether
// run by a web server or by command line interface (i.e. by cron)

$endpoints = [
    '/.*/' => "\Lib\CCMS\Database::hookOpenConnection", // Connect to database
    '/^web:.*/' => "\Lib\CCMS\Security\User::hookAuthenticateFromRequest", // Sign in
    
    '/^web:\/?api\/checkuser\/?$/i' => "\Lib\CCMS\Security\User::hookCheckUser", // Check that the username is correct
    '/^web:\/?api\/checkpass\/?$/i' => "\Lib\CCMS\Security\User::hookCheckPassword", // Check that the password is correct
    '/^web:\/?api\/newtoken\/?$/i' => "\Lib\CCMS\Security\AccountManager::hookNewToken", // Create a new token if authenticated
    
    '/^web:\/?api\/collab_update\/?$/i' => "\Lib\CCMS\CollabUpdateEndpoint::hook", // Collaboration status update
    
    '/^web:(?!.*\.[a-z]*$).*$/i' => "\Mod\Page::hook", // Capture all remaining endpoints that don't have an extension
];