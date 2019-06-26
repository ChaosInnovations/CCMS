<?php

// Hooks are checked in the order they appear in this file.
// Every matching hook will be called. The first one that returns an
// instance of Response with isFinal set to true stops execution and
// the Response is sent.
// If no hooks are found, will respond with a 404
// Hooks are prefixed by either 'web:' or 'cli:' depending on whether
// run by a web server or by command line interface (i.e. by cron)

// Route positions:
// 100 - 199 : Configuration/verification/service start
// 200 - 299 : Specific-hook routes (i.e API paths)
// 300 - 999 : Specific-catch routes (i.e all from web, all from web without extension)
// 1000+     : Non-specific-catch routes (all requests)

$hooks = [
    ['/.*/',                                       "\Mod\Database::hookOpenConnection"],
    ['/.*/',                                       "\Mod\User::hookVerifyConfiguration"],
    ['/.*/',                                       "\Mod\Collaboration::hookVerifyConfiguration"],
    ['/.*/',                                       "\Mod\FireSock::hookVerifyServer"],
    ['/^web:.*/',                                  "\Mod\User::hookAuthenticateFromRequest"],
  
    ['/^cli:\/?firesock\/?$/',                     "\Mod\FireSock::hookStartServer"],
    ['/^web:\/?api\/firesock\/newlongpoll\/?$/i',  "\Mod\FireSock::hookLongPollToken"],
    ['/^web:\/?api\/firesock\/longpoll\/?$/i',     "\Mod\FireSock::hookLongPoll"],
    ['/^web:\/?api\/firesock\/longpoll-in\/?$/i',  "\Mod\FireSock::hookLongPollInbound"],
    ['/^web:\/?api\/checkuser\/?$/i',              "\Mod\User::hookCheckUser"],
    ['/^web:\/?api\/checkpass\/?$/i',              "\Mod\User::hookCheckPassword"],
    ['/^web:\/?api\/newtoken\/?$/i',               "\Mod\User\AccountManager::hookNewToken"],
    ['/^web:\/?api\/user\/new\/?$/i',              "\Mod\User::hookNewUser"],
    ['/^web:\/?api\/user\/edit\/?$/i',             "\Mod\User::hookEditUser"],
    ['/^web:\/?api\/user\/remove\/?$/i',           "\Mod\User::hookRemoveUser"],
    ['/^web:\/?api\/user\/password\/reset\/?$/i',  "\Mod\User::hookPasswordReset"],
    ['/^web:\/?api\/user\/password\/edit\/?$/i',   "\Mod\User::hookPasswordChange"],
    ['/^web:\/?api\/siteconfiguration\/set\/?$/i', "\Mod\SiteConfiguration::hookSetConfig"],
    ['/^web:\/?api\/collab_update\/?$/i',          "\Mod\Collaboration::hookUpdate"],
    ['/^web:\/?api\/page\/new\/?$/i',              "\Mod\Page::hookNewPage"],
    ['/^web:\/?api\/page\/remove\/?$/i',           "\Mod\Page::hookRemovePage"],
    ['/^web:\/?api\/page\/edit\/?$/i',             "\Mod\Page::hookEditPage"],
    ['/^web:\/?api\/page\/secure\/?$/i',           "\Mod\Page::hookSecurePage"],
    ['/^web:\/?api\/page\/checkpid\/?$/i',         "\Mod\Page::hookCheckPid"],
    ['/^web:\/?api\/contactform\/response\/?$/i',  "\Mod\ContactForm::hookFormResponse"],
    ['/^web:\/?api\/notice\/checknid\/?$/i',       "\Mod\Notice::hookCheckNid"],
    ['/^web:\/?api\/notice\/new\/?$/i',            "\Mod\Notice::hookNew"],
    ['/^web:\/?api\/notice\/delete\/?$/i',         "\Mod\Notice::hookDelete"],
  
    ['/(?!.*\.[a-z]*$)^web:.*$/i',                 "\Mod\Page::hookMain"],
    ['/(?!.*\.[a-z]*$)^web:.*$/i',                 "\Mod\Page::hookMenu"],
    ['/(?!.*\.[a-z]*$)^web:.*$/i',                 "\Mod\Collaboration::hookMenu"],
    ['/(?!.*\.[a-z]*$)^web:.*$/i',                 "\Mod\User::hookMenu"],
    ['/(?!.*\.[a-z]*$)^web:.*$/i',                 "\Mod\Notice::hookMenu"],
    ['/(?!.*\.[a-z]*$)^web:.*$/i',                 "\Mod\ModuleMenu::hookAddToSecureMenu"],
    ['/(?!.*\.[a-z]*$)^web:.*$/i',                 "\Mod\SecureMenu::hookAboutMenu"],
    ['/(?!.*\.[a-z]*$)^web:.*$/i',                 "\Mod\SecureMenu::hook"],
    ['/(?!.*\.[a-z]*$)^web:.*$/i',                 "\Mod\Page::hookClose"],
    ['/(?!.*\.[a-z]*$)^web:.*$/i',                 "\Mod\Placeholders::hookEvaluatePlaceholders"],
];