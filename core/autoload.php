<?php

require_once $_SERVER["DOCUMENT_ROOT"]."/core/lib/Autoloader.php";

$loader = new \Lib\Autoloader;
$loader->register();
$loader->addNamespace("Lib", $_SERVER["DOCUMENT_ROOT"]."/core/lib");
$loader->addNamespace("Mod", $_SERVER["DOCUMENT_ROOT"]."/core/mod");
$loader->addNamespace("Mod", $_SERVER["DOCUMENT_ROOT"]."/modules");