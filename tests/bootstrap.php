<?php

$include = realpath(__DIR__.'/../src');
set_include_path(get_include_path().PATH_SEPARATOR.$include);

spl_autoload_register(function($className) {
	$fileName = stream_resolve_include_path(
		strtr(ltrim($className, '\\'), '\\', '/').'.php'
	);
	
	if ($fileName) {
		require_once $fileName;
	}
});
