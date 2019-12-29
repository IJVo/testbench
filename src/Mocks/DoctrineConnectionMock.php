<?php

declare(strict_types=1);

namespace Testbench\Mocks;

use Doctrine\Common;
use Doctrine\DBAL;
use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\DBAL\Platforms\PostgreSqlPlatform;
use Nette\UnexpectedValueException;

class DoctrineConnectionMock extends \Kdyby\Doctrine\Connection implements \Testbench\Providers\IDatabaseProvider
{
	public $onConnect = [];

	private $__testbenchDatabaseName;


	public function onConnect(self $self)
	{
		if (is_array($this->onConnect) || $this->onConnect instanceof \Traversable) {
			foreach ($this->onConnect as $handler) {
				$handler($self);
			}
		} elseif ($this->onConnect !== null) {
			throw new UnexpectedValueException('Property ' . static::class . '::$onConnect must be array or null, ' . gettype($this->onConnect) . ' given.');
		}
	}


	public function connect()
	{
		if (parent::connect()) {
			$this->onConnect($this);
		}
	}


	public function __construct(
					array $params,
					DBAL\Driver $driver,
					DBAL\Configuration $config = null,
					Common\EventManager $eventManager = null
	)
	{
		$container = \Testbench\ContainerFactory::create(false);
		$this->onConnect[] = function (self $connection) use ($container) {
			if ($this->__testbenchDatabaseName !== null) { //already initialized (needed for pgsql)
				return;
			}
			try {
				$config = $container->parameters['testbench'];
				if ($config['shareDatabase'] === true) {
					$registry = new \Testbench\DatabasesRegistry;
					$dbName = $container->parameters['testbench']['dbprefix'] . getenv(\Tester\Environment::THREAD);
					if ($registry->registerDatabase($dbName)) {
						$this->__testbenchDatabaseSetup($connection, $container, true);
					} else {
						$this->__testbenchDatabaseName = $dbName;
						$this->__testbenchDatabaseChange($connection, $container);
					}
				} else { // always create new test database
					$this->__testbenchDatabaseSetup($connection, $container);
				}
			} catch (\Doctrine\Migrations\MigrationException $e) {
				//  do not throw an exception if there are no migrations
				if ($e->getCode() !== 4) {
					\Tester\Assert::fail($e->getMessage());
				}
			} catch (\Exception $e) {
				\Tester\Assert::fail($e->getMessage());
			}
		};
		parent::__construct($params, $driver, $config, $eventManager);
	}


	/** @internal */
	public function __testbenchDatabaseSetup($connection, \Nette\DI\Container $container, $persistent = false)
	{
		$config = $container->parameters['testbench'];
		$this->__testbenchDatabaseName = $config['dbprefix'] . getenv(\Tester\Environment::THREAD);

		$this->__testbenchDatabaseDrop($connection, $container);
		$this->__testbenchDatabaseCreate($connection, $container);

		foreach ($config['sqls'] as $file) {
			\Kdyby\Doctrine\Dbal\BatchImport\Helpers::loadFromFile($connection, $file);
		}

		if ($config['migrations'] === true) {
			if (class_exists(\Nettrine\Migrations\ContainerAwareConfiguration::class)) {
				/** @var \Nettrine\Migrations\ContainerAwareConfiguration $migrationsConfig */
				$migrationsConfig = $container->getByType(\Nettrine\Migrations\ContainerAwareConfiguration::class);
				$migrationsConfig->__construct($connection);
				$migrationsConfig->registerMigrationsFromDirectory($migrationsConfig->getMigrationsDirectory());
				$migration = new \Doctrine\Migrations\Migrator($migrationsConfig);
				$migration->migrate($migrationsConfig->getLatestVersion());
			}
		}

		if ($persistent === false) {
			register_shutdown_function(function () use ($connection, $container) {
				$this->__testbenchDatabaseDrop($connection, $container);
			});
		}
	}


	/**
	 * @internal
	 *
	 * @param $connection \Kdyby\Doctrine\Connection
	 */
	public function __testbenchDatabaseCreate($connection, \Nette\DI\Container $container)
	{
		$connection->exec("CREATE DATABASE {$this->__testbenchDatabaseName}");
		$this->__testbenchDatabaseChange($connection, $container);
	}


	/**
	 * @internal
	 *
	 * @param $connection \Kdyby\Doctrine\Connection
	 */
	public function __testbenchDatabaseChange($connection, \Nette\DI\Container $container)
	{
		if ($connection->getDatabasePlatform() instanceof MySqlPlatform) {
			$connection->exec("USE {$this->__testbenchDatabaseName}");
		} else {
			$this->__testbenchDatabaseConnect($connection, $container, $this->__testbenchDatabaseName);
		}
	}


	/**
	 * @internal
	 *
	 * @param $connection \Kdyby\Doctrine\Connection
	 */
	public function __testbenchDatabaseDrop($connection, \Nette\DI\Container $container)
	{
		if (!$connection->getDatabasePlatform() instanceof MySqlPlatform) {
			$this->__testbenchDatabaseConnect($connection, $container);
		}
		$connection->exec("DROP DATABASE IF EXISTS {$this->__testbenchDatabaseName}");
	}


	/**
	 * @internal
	 *
	 * @param $connection \Kdyby\Doctrine\Connection
	 */
	public function __testbenchDatabaseConnect($connection, \Nette\DI\Container $container, $databaseName = null)
	{
		//connect to an existing database other than $this->_databaseName
		if ($databaseName === null) {
			$dbname = $container->parameters['testbench']['dbname'];
			if ($dbname) {
				$databaseName = $dbname;
			} elseif ($connection->getDatabasePlatform() instanceof PostgreSqlPlatform) {
				$databaseName = 'postgres';
			} else {
				throw new \LogicException('You should setup existing database name using testbench:dbname option.');
			}
		}

		$connection->close();
		$connection->__construct(
						['dbname' => $databaseName] + $connection->getParams(),
						$connection->getDriver(),
						$connection->getConfiguration(),
						$connection->getEventManager()
		);
		$connection->connect();
	}
}
