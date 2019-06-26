<?php

// Set DOCUMENT_ROOT to the correct root document
$_SERVER["DOCUMENT_ROOT"] = dirname(__FILE__, 2);

require_once $_SERVER["DOCUMENT_ROOT"]."/core/lib/CCMS/Autoloader.php";

$loader = new \Lib\CCMS\Autoloader;
$loader->register();
$loader->addNamespace("Lib", $_SERVER["DOCUMENT_ROOT"]."/core/lib");
$loader->addNamespace("Mod", $_SERVER["DOCUMENT_ROOT"]."/core/mod");
$loader->addNamespace("Mod", $_SERVER["DOCUMENT_ROOT"]."/modules");