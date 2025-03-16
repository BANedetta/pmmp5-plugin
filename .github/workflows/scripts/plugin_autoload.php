<?php

foreach (glob(__DIR__ . "/virions/*.phar") as $pharFile) {
	include_once "phar://" . $pharFile . "/autoload.php";
}

$vendorAutoload = __DIR__ . "/vendor/autoload.php";

if (file_exists($vendorAutoload)) {
	require_once $vendorAutoload;
}