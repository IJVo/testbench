<?php

declare(strict_types=1);

namespace Tests\Mocks;

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';

/**
 * @testCase
 */
class CustomMocks extends \Tester\TestCase
{

	use \Testbench\TCompiledContainer;


	public function testCustomMocks()
	{
		Assert::type(\Testbench\Mocks\PresenterMock::class, $this->getService(\Testbench\Mocks\PresenterMock::class));
		Assert::type(\Testbench\CustomPresenterMock::class, $this->getService(\Testbench\Mocks\PresenterMock::class));

		Assert::notSame(\Testbench\Mocks\PresenterMock::class, get_class($this->getService(\Testbench\Mocks\PresenterMock::class)));
		Assert::same(\Testbench\CustomPresenterMock::class, get_class($this->getService(\Testbench\Mocks\PresenterMock::class)));
	}
}

(new CustomMocks)->run();
