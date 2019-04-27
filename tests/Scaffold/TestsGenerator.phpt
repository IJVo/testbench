<?php

namespace Tests\Scaffold;

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';

/**
 * @testCase
 */
class TestsGenerator extends \Tester\TestCase
{

	private $output;

	public function setUp()
	{
		$this->output = __DIR__ . '/../_temp/scaffold';
		$scaffold = new \Testbench\Scaffold\TestsGenerator;
		$scaffold->generateTests($this->output);
	}

	public function testPresentersOutput()
	{
		\Tester\Environment::lock('TestsGenerator', __DIR__ . '/../_temp'); // musí zde být lock kvuli setUp, ktery vytvari slozku _temp/scaffold
		Assert::matchFile(__DIR__ . '/Presenter.expected', file_get_contents($this->output . '/PresenterPresenter.phpt'));
		Assert::matchFile(
			__DIR__ . '/ModulePresenter.expected',
			file_get_contents($this->output . '/ModuleModule/PresenterPresenter.phpt')
		);
		Assert::matchFile(
			__DIR__ . '/ScaffoldPresenter.expected',
			file_get_contents($this->output . '/ScaffoldPresenter.phpt')
		);
	}

	public function testSupportFiles()
	{
		\Tester\Environment::lock('TestsGenerator', __DIR__ . '/../_temp'); // musí zde být lock kvuli setUp, ktery vytvari slozku _temp/scaffold
		Assert::true(is_dir($this->output . '/_temp'));
	}

	public function testNeon()
	{
		\Tester\Environment::lock('TestsGenerator', __DIR__ . '/../_temp'); // musí zde být lock kvuli setUp, ktery vytvari slozku _temp/scaffold
		Assert::matchFile(__DIR__ . '/Neon.expected', file_get_contents($this->output . '/tests.neon'));
	}

}

(new TestsGenerator)->run();
