<?php

// Endpoints are checked in the order they appear in this file.
// Every matching endpoint will be called. The first one that returns an
// instance of Response stops execution and the Response is sent.
// If no endpoints are found, will respond with a 404

$endpoints = [
    '/.*/i' => "\Lib\CCMS\Security\User::hook", // Sign in
    '/^\/?api\/collab_update\/?$/i' => "\Lib\CCMS\CollabUpdateEndpoint::hook",
    '/^(?!.*\.[a-z]*$).*$/i' => "\Lib\CCMS\Page::hook", // Capture all remaining endpoints that don't have an extension
];