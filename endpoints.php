<?php

// Endpoints are checked in the order they appear in this file.
// Every matching endpoint that implements IEndpoint will be used. The first one that returns an
// instance of Response stops execution and the Response is sent.
// If no endpoints are found, will respond with a 404

$endpoints = [
    '/.*/i' => "\Lib\CCMS\Security\UserHook", // Sign in
    '/^\/?api\/collab_update\/?$/i' => "\Lib\CCMS\CollabUpdateEndpoint",
    '/^(?!.*\.[a-z]*$).*$/i' => "\Lib\CCMS\PageEndpoint", // Capture all remaining endpoints that don't have an extension
];