<?php

require $_SERVER["DOCUMENT_ROOT"]."/assets/server/Lib/Autoloader.php";
$loader = new \Lib\Autoloader;
$loader->register();
$loader->addNamespace("Lib", $_SERVER["DOCUMENT_ROOT"]."/assets/server/Lib");