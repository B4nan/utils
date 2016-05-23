<?php

define('WWW_DIR', __DIR__ . '/../src');
define('APP_DIR', __DIR__ . '/../app');
define('VENDOR_DIR', __DIR__ . '/../vendor');

// include composer autoload and other needed classes
require VENDOR_DIR . '/autoload.php';

\Kdyby\TesterExtras\Bootstrap::setup(__DIR__);

function run(\Tester\TestCase $testCase) {
	$testCase->run(isset($_SERVER['argv'][1]) ? $_SERVER['argv'][1] : NULL);
}
