<?php

declare(strict_types=1);

namespace Tests\Traits;

require __DIR__ . '/../bootstrap.php';

//require getenv('BOOTSTRAP');

/**
 * @testCase
 */
class MultipleTraits extends \Tester\TestCase
{

	use \Testbench\TCompiledContainer;
	use \Testbench\TComponent;
	use \Testbench\TDoctrine;
	use \Testbench\TNetteDatabase;
	use \Testbench\TPresenter;


	public function testShutUp()
	{
		\Tester\Environment::$checkAssertions = FALSE;
	}
}

(new MultipleTraits)->run();
