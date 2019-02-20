<?php

// Hooks are checked in the order they appear in this file.
// The first matching hook will be called and the returned value
// will replace the matching placeholder tag

$placeholder_hooks = [
    ['/^loginform$/i', "\Mod\User::placeholderLoginForm"],
    ['/^pageid$/i', "\Mod\Page::placeholderPageId"],
    
    ['/^contactform$/i', "\Mod\ContactForm::placeholderForm"],
    
    ['/.*/', "\Mod\Placeholders::placeholderFallback"],
];