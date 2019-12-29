<?php

declare(strict_types=1);

namespace Testbench;

class FakeExtension extends \Nette\DI\CompilerExtension
{

	public static $tested = FALSE;


	public function loadConfiguration()
	{
		\Tester\Assert::same(['xxx' => ['yyy']], $this->getConfig());
		self::$tested = TRUE;
	}
}
