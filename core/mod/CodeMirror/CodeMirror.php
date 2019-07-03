<?php

namespace Mod;

use \Lib\CCMS\Response;

class CodeMirror
{
    public static function hookLinkCss()
    {
        return new Response('<link rel="stylesheet" type="text/css" href="/assets/site/js/codemirror/lib/codemirror.css" media="all">', false);
    }

    public static function hookScript()
    {
        return new Response('<script type="text/javascript" src="/core/mod/CodeMirror/codemirror/lib/codemirror.js"></script>
        <script type="text/javascript" src="/core/mod/CodeMirror/codemirror/mode/xml/xml.js"></script>
		<script type="text/javascript" src="/core/mod/CodeMirror/codemirror/mode/html/html.js"></script>
		<script type="text/javascript" src="/core/mod/CodeMirror/codemirror/mode/css/css.js"></script>
		<script type="text/javascript" src="/core/mod/CodeMirror/codemirror/mode/javascript/javascript.js"></script>
        <script type="text/javascript" src="/core/mod/CodeMirror/codemirror/mode/htmlmixed/htmlmixed.js"></script>', false);
    }
}