<?php

namespace Testbench;

trait TNextrasDbal
{

	protected function getConnection()
	{
		$container = \Testbench\ContainerFactory::create(FALSE);
		/** @var Mocks\NextrasDbalConnectionMock $connection */
		$connection = $container->getByType('Nextras\Dbal\Connection');
		if (!$connection instanceof Mocks\NextrasDbalConnectionMock) {
			$serviceNames = $container->findByType('Nextras\Dbal\Connection');
			throw new \LogicException(sprintf(
				'The service %s should be instance of Testbench\Mocks\NextrasDbalConnectionMock, to allow lazy schema initialization.',
				reset($serviceNames)
			));
		}
		/** @var \Nextras\Dbal\Connection $connection */
		$connection = $container->getByType('Nextras\Dbal\Connection');
		return $connection;
	}

}
