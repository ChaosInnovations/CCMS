<?php

// Hooks are checked in the order they appear in this file.
// First matching hook will be used

$subscription_hooks = [
    ['user',         "\Mod\User\UserSubscription"],
    ['collabUpdate', "\Mod\Collaboration\CollaborationSubscription"],
];