<?php

// Hooks are checked in the order they appear in this file.
// The first matching hook will be called and the returned value
// will replace the matching placeholder tag

$placeholder_hooks = [
    ['/^loginform$/', "\Mod\User::placeholderLoginForm"],
    ['/.*/', "\Mod\Placeholders::placeholderFallback"],
];