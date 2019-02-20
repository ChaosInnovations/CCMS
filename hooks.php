<?php

// Hooks are checked in the order they appear in this file.
// Every matching hook will be called. The first one that returns an
// instance of Response with isFinal set to true stops execution and
// the Response is sent.
// If no hooks are found, will respond with a 404
// Hooks are prefixed by either 'web:' or 'cli:' depending on whether
// run by a web server or by command line interface (i.e. by cron)

$hooks = [
    ['/.*/',                                      "\Mod\Database::hookOpenConnection"],           // Connect to database
    ['/^web:.*/',                                 "\Mod\User::hookAuthenticateFromRequest"],      // Sign in

    ['/^web:\/?api\/checkuser\/?$/i',             "\Mod\User::hookCheckUser"],                    // Check that the username is correct
    ['/^web:\/?api\/checkpass\/?$/i',             "\Mod\User::hookCheckPassword"],                // Check that the password is correct
    ['/^web:\/?api\/newtoken\/?$/i',              "\Mod\User\AccountManager::hookNewToken"],      // Create a new token if authenticated
    ['/^web:\/?api\/user\/new\/?$/i',             "\Mod\User::hookNewUser"],                      // Create a new user
    ['/^web:\/?api\/user\/edit\/?$/i',            "\Mod\User::hookEditUser"],                     // Edit a user
    ['/^web:\/?api\/user\/remove\/?$/i',          "\Mod\User::hookRemoveUser"],                   // Remove a user
    ['/^web:\/?api\/user\/password\/reset\/?$/i', "\Mod\User::hookPasswordReset"],                // Reset password to default
    ['/^web:\/?api\/user\/password\/edit\/?$/i',  "\Mod\User::hookPasswordChange"],               // Change password

    ['/^web:\/?api\/config\/set\/?$/i',           "\Lib\CCMS\Utilities::hookSetConfig"],          // Set configuration

    ['/^web:\/?api\/collab_update\/?$/i',         "\Lib\CCMS\CollabUpdateEndpoint::hook"],        // Collaboration status update

    ['/^web:\/?api\/page\/new\/?$/i',             "\Mod\Page::hookNewPage"],
    ['/^web:\/?api\/page\/remove\/?$/i',          "\Mod\Page::hookRemovePage"],
    ['/^web:\/?api\/page\/edit\/?$/i',            "\Mod\Page::hookEditPage"],
    ['/^web:\/?api\/page\/secure\/?$/i',          "\Mod\Page::hookSecurePage"],
    ['/^web:\/?api\/page\/checkpid\/?$/i',        "\Mod\Page::hookCheckPid"],
    
    ['/^web:\/?api\/contactform\/response\/?$/i', "\Mod\ContactForm::hookFormResponse"],

    ['/(?!.*\.[a-z]*$)^web:.*$/i',                "\Mod\Page::hook"],                             // Capture all remaining hooks that don't have an extension

    ['/^web:.*/',                                 "\Mod\Placeholders::hookEvaluatePlaceholders"], // Evaluate placeholders
];