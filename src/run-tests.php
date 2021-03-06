<?php

declare(strict_types=1);

require __DIR__ . '/Runner.php';
$runner = new \Testbench\Runner;
$vendorDir = $runner->findVendorDirectory();
$testsDir = dirname($vendorDir) . '/tests';

require $vendorDir . '/autoload.php';
$script = array_shift($_SERVER['argv']);
$_SERVER['argv'] = $runner->prepareArguments($_SERVER['argv'], $testsDir);
array_unshift($_SERVER['argv'], $script);

// echo "TESTBENCH edition\n";
require $vendorDir . '/nette/tester/src/tester.php';
