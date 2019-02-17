<?php

namespace Lib\CCMS;

use Lib\CCMS\Request;

interface IEndpoint
{
    // This should return a Response, or false if unable to handle
    public static function endpointHook(Request $request);
}