<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

Testbench\Bootstrap::setup(__DIR__ . '/_temp', function (Nette\Configurator $configurator) {
	$configurator->addParameters([
			'appDir' => __DIR__ . '/../src',
			'testsDir' => __DIR__,
			'tempDir' => __DIR__ . '/_temp',
	]);
	$configurator->setTempDirectory(__DIR__ . '/_temp');

	# bootstrap.php pro testy
	if (getenv(\Tester\Environment::RUNNER)) {
		# Running by Tester (e.g. vendor/bin/tester tests/MyTest.phpt)
		$configurator->setDebugMode(FALSE);
	} elseif (PHP_SAPI === 'cli') {
		# Running as ordinary CLI script (e.g. php tests/MyTest.phpt)
		$configurator->setDebugMode(FALSE);
	} else {
		# Browser
		$configurator->setDebugMode(TRUE);
		$configurator->enableDebugger();
	}

	$configurator->addConfig(__DIR__ . '/tests.neon');
	if (file_exists($localConfig = __DIR__ . '/tests.local.neon')) {
		$configurator->addConfig($localConfig);
	}
});
