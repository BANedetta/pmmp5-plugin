<?php

$srcPath = __DIR__ . "/src";

$phar = new Phar(__DIR__ . "/virion.phar", 0, "virion.phar");

$phar->buildFromDirectory(__DIR__, '/^(?!.*(build\.php)).*$/');

$autoloadCode = file_get_contents(__DIR__ . "/virion_autoload.php");

$phar->addFromString("autoload.php", $autoloadCode);

$phar->setStub("<?php
	Phar::mapPhar('virion.phar');
	require 'phar://virion.phar/autoload.php';
	__HALT_COMPILER();
?>");