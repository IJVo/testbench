<?php

declare(strict_types=1);

class Testbench
{

	public const QUICK = 0;
	public const FINE = 5;
	public const SLOW = 10;

}

if (class_exists(\Kdyby\Doctrine\Connection::class)) { //BC:
	class_alias(\Testbench\Mocks\DoctrineConnectionMock::class, 'Testbench\ConnectionMock');
	class_alias(\Testbench\Mocks\DoctrineConnectionMock::class, 'Testbench\Mocks\ConnectionMock');
	class_alias(\Testbench\Mocks\ControlMock::class, 'Testbench\ControlMock');
	class_alias(\Testbench\Mocks\HttpRequestMock::class, 'Testbench\HttpRequestMock');
	class_alias(\Testbench\Mocks\PresenterMock::class, 'Testbench\PresenterMock');
}
