<?php

declare(strict_types=1);

namespace Tests\Issues;

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';

/**
 * @testCase
 * @see https://github.com/mrtnzlml/testbench/issues/17
 */
class Issue_17 extends \Tester\TestCase
{

	use \Testbench\TPresenter;


	/**
	 * @dataProvider commentFormParameters
	 */
	public function testCommentForm($params, $post, $shouldFail = true)
	{
		if ($shouldFail) {
			Assert::exception(function () use ($params, $post) {
				$this->check('Presenter:default', $params, $post);
			}, \Tester\AssertException::class, "field 'test' returned this error(s):\n  - This field is required.");
		} else {
			$this->check('Presenter:default', $params, $post);
		}
		$errors = $this->getPresenter()->getComponent('form1')->getErrors();
		if ($shouldFail) {
			Assert::same(['This field is required.'], $errors);
		} else {
			Assert::same([], $errors);
		}
	}


	/**
	 * @dataProvider commentFormParametersBetter
	 */
	public function testCommentFormBetter($post, $shouldFail = true, $path = false)
	{
		if ($shouldFail) {
			Assert::exception(function () use ($post, $shouldFail, $path) {
				$this->checkForm('Presenter:default', 'form1', $post, $path);
			}, \Tester\AssertException::class, "field 'test' returned this error(s):\n  - This field is required.");
			$errors = $this->getPresenter()->getComponent('form1')->getErrors();
			Assert::same(['This field is required.'], $errors);
		} else {
			$this->checkForm('Presenter:default', 'form1', $post, $path);
			$errors = $this->getPresenter()->getComponent('form1')->getErrors();
			Assert::same([], $errors);
		}
	}


	public function commentFormParameters()
	{
		return [
				[['do' => 'form1-submit'], ['test' => 'NOT NULL'], false],
				[['do' => 'form1-submit'], ['test' => null], true],
		];
	}


	public function commentFormParametersBetter()
	{
		return [
				[['test' => 'NOT NULL'], false, '/x/y'],
				[['test' => null], true, false],
		];
	}
}

(new Issue_17)->run();
