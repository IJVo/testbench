<?php

declare(strict_types=1);

namespace Testbench;

trait TDoctrine
{


	protected function getEntityManager(): \Kdyby\Doctrine\EntityManager
	{
		$container = ContainerFactory::create(false);
		/** @var Mocks\DoctrineConnectionMock $connection */
		$connection = $container->getByType(\Doctrine\DBAL\Connection::class);
		if (!$connection instanceof Mocks\DoctrineConnectionMock) {
			$serviceNames = $container->findByType(\Doctrine\DBAL\Connection::class);
			throw new \LogicException(sprintf(
											'The service %s should be instance of ' . \Testbench\Mocks\DoctrineConnectionMock::class . ', to allow lazy schema initialization.',
											reset($serviceNames)
			));
		}
		return $container->getByType(\Kdyby\Doctrine\EntityManager::class);
	}
}
