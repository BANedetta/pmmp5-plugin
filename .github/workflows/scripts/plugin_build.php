<?php

$srcPath = __DIR__ . "/src";
$phar = new Phar(__DIR__ . "/plugin.phar", 0, "plugin.phar");

$phar->buildFromDirectory(__DIR__, '/^(?!.*(scripts|build\.php)).*$/');

$autoloadCode = file_get_contents(__DIR__ . "/plugin_autoload.php");

$phar->addFromString("autoload.php", $autoloadCode);

$phar->setStub("<?php
	Phar::mapPhar('plugin.phar');
	require 'phar://plugin.phar/autoload.php';
	__HALT_COMPILER();
?>");