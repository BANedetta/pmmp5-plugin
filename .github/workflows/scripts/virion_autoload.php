<?php

spl_autoload_register(function ($class) {
	$classPath = str_replace("\\", "/", $class) . ".php";
	$file = "phar://virion.phar/" . $classPath;

	if (file_exists($file)) {
		require_once $file;
	}
});