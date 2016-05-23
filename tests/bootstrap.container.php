<?php

require __DIR__ . '/bootstrap.php';

// create DI container
$configurator = new Nette\Configurator;
$configurator->setDebugMode(FALSE);
$configurator->setTempDirectory(TEMP_DIR);
$configurator->addParameters(array(
	'wwwDir' => WWW_DIR,
	'appDir' => APP_DIR,
	'tempDir' => TEMP_DIR,
));

if (file_exists(__DIR__ . '/app/config/config.neon')) {
	$configurator->addConfig(__DIR__ . '/../app/config/config.neon');
}
if (file_exists(__DIR__ . '/config/config.neon')) {
	$configurator->addConfig(__DIR__ . '/config/config.neon');
}
if (file_exists(__DIR__ . '/config/config.local.neon')) {
	$configurator->addConfig(__DIR__ . '/config/config.local.neon');
}

$container = $configurator->createContainer();

return $container;
