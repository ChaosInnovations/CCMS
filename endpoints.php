<?php

// Endpoints are checked in the order they appear in this file.
// Every matching endpoint will be called. The first one that returns an
// instance of Response stops execution and the Response is sent.
// If no endpoints are found, will respond with a 404

$endpoints = [
    '/.*/i' => "\Lib\CCMS\Database::hookOpenConnection", // Connect to database
    '/.*/' => "\Lib\CCMS\Security\User::hookAuthenticateFromRequest", // Sign in
    
    '/^\/?api\/checkuser\/?$/i' => "\Lib\CCMS\Security\User::hookCheckUser", // Check that the username is correct
    '/^\/?api\/checkpass\/?$/i' => "\Lib\CCMS\Security\User::hookCheckPassword", // Check that the username is correct
    '/^\/?api\/newtoken\/?$/i' => "\Lib\CCMS\Security\AccountManager::hookNewToken", // Check that the username is correct
    
    '/^\/?api\/collab_update\/?$/i' => "\Lib\CCMS\CollabUpdateEndpoint::hook", // Collaboration status update
    
    '/^(?!.*\.[a-z]*$).*$/i' => "\Lib\CCMS\Page::hook", // Capture all remaining endpoints that don't have an extension
];