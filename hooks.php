<?php

// Hooks are checked in the order they appear in this file.
// Every matching hook will be called. The first one that returns an
// instance of Response with isFinal set to true stops execution and
// the Response is sent.
// If no hooks are found, will respond with a 404
// Hooks are prefixed by either 'web:' or 'cli:' depending on whether
// run by a web server or by command line interface (i.e. by cron)

$hooks = [
    ['/.*/',                                       "\Mod\Database::hookOpenConnection"],            // (done) Connect to database
    ['/.*/',                                       "\Mod\User::hookVerifyConfiguration"],           // (done) Verify database configuration
    ['/.*/',                                       "\Mod\Collaboration::hookVerifyConfiguration"],  // (done) Verify database configuration
  
    ['/^cli:\/?firesock\/?$/',                     "\Mod\FireSock::hookStartServer"],               // (done) Start WS Server
    ['/.*/',                                       "\Mod\FireSock::hookVerifyServer"],              // (done) Check that WS Server is running.
    ['/^web:\/?api\/firesock\/newlongpoll\/?$/i',  "\Mod\FireSock::hookLongPollToken"],             // (done) FireSock long-polling fallback request token
    ['/^web:\/?api\/firesock\/longpoll\/?$/i',     "\Mod\FireSock::hookLongPoll"],                  // (done) FireSock long-polling fallback for outbound data (pushes)
    ['/^web:\/?api\/firesock\/longpoll-in\/?$/i',  "\Mod\FireSock::hookLongPollInbound"],           // (done) FireSock long-polling fallback for inbound data
  
    ['/^web:.*/',                                  "\Mod\User::hookAuthenticateFromRequest"],       // (done) Sign in
  
    ['/^web:\/?api\/checkuser\/?$/i',              "\Mod\User::hookCheckUser"],                     // (done) Check that the username is correct
    ['/^web:\/?api\/checkpass\/?$/i',              "\Mod\User::hookCheckPassword"],                 // (done) Check that the password is correct
    ['/^web:\/?api\/newtoken\/?$/i',               "\Mod\User\AccountManager::hookNewToken"],       // (done) Create a new token if authenticated
    ['/^web:\/?api\/user\/new\/?$/i',              "\Mod\User::hookNewUser"],                       // (done) Create a new user
    ['/^web:\/?api\/user\/edit\/?$/i',             "\Mod\User::hookEditUser"],                      // (done) Edit a user
    ['/^web:\/?api\/user\/remove\/?$/i',           "\Mod\User::hookRemoveUser"],                    // (done) Remove a user
    ['/^web:\/?api\/user\/password\/reset\/?$/i',  "\Mod\User::hookPasswordReset"],                 // (done) Reset password to default
    ['/^web:\/?api\/user\/password\/edit\/?$/i',   "\Mod\User::hookPasswordChange"],                // (done) Change password
  
    ['/^web:\/?api\/siteconfiguration\/set\/?$/i', "\Mod\SiteConfiguration::hookSetConfig"],        // (done) Set configuration
  
    ['/^web:\/?api\/collab_update\/?$/i',          "\Mod\Collaboration::hookUpdate"],               // (done) Collaboration status update
  
    ['/^web:\/?api\/page\/new\/?$/i',              "\Mod\Page::hookNewPage"],                       // (done)
    ['/^web:\/?api\/page\/remove\/?$/i',           "\Mod\Page::hookRemovePage"],                    // (done)
    ['/^web:\/?api\/page\/edit\/?$/i',             "\Mod\Page::hookEditPage"],                      // (done)
    ['/^web:\/?api\/page\/secure\/?$/i',           "\Mod\Page::hookSecurePage"],                    // (done)
    ['/^web:\/?api\/page\/checkpid\/?$/i',         "\Mod\Page::hookCheckPid"],                      // (done)
      
    ['/^web:\/?api\/contactform\/response\/?$/i',  "\Mod\ContactForm::hookFormResponse"],           // (done)
  
    ['/^web:\/?api\/notice\/checknid\/?$/i',       "\Mod\Notice::hookCheckNid"],                    // (done)
    ['/^web:\/?api\/notice\/new\/?$/i',            "\Mod\Notice::hookNew"],                         // (done)
    ['/^web:\/?api\/notice\/delete\/?$/i',         "\Mod\Notice::hookDelete"],                      // (done)
  
    ['/(?!.*\.[a-z]*$)^web:.*$/i',                 "\Mod\Page::hookMain"],                          // (done) Open and write page for remaining hooks (without extensions)
  
                                                                                                    // SecureMenu hooks
    ['/(?!.*\.[a-z]*$)^web:.*$/i',                 "\Mod\Page::hookMenu"],                          // (done) Page module menu entries
    ['/(?!.*\.[a-z]*$)^web:.*$/i',                 "\Mod\Collaboration::hookMenu"],                 // (done) Collaboration menu, and last-visited page update
    ['/(?!.*\.[a-z]*$)^web:.*$/i',                 "\Mod\User::hookMenu"],                          // (done)
  
    ['/(?!.*\.[a-z]*$)^web:.*$/i',                 "\Mod\Notice::hookMenu"],                        // (done) Notice module menu hook
  
    ['/(?!.*\.[a-z]*$)^web:.*$/i',                 "\Mod\ModuleMenu::hookAddToSecureMenu"],         // (done)
    ['/(?!.*\.[a-z]*$)^web:.*$/i',                 "\Mod\SecureMenu::hookAboutMenu"],               // (done)
 
    ['/(?!.*\.[a-z]*$)^web:.*$/i',                 "\Mod\SecureMenu::hook"],                        // (done) Display secure menu
 
    ['/(?!.*\.[a-z]*$)^web:.*$/i',                 "\Mod\Page::hookClose"],                         // (done) Close page
    ['/(?!.*\.[a-z]*$)^web:.*$/i',                 "\Mod\Placeholders::hookEvaluatePlaceholders"],  // (done) Evaluate placeholders on page
];