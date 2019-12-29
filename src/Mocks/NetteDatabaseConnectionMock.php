<?php

declare(strict_types=1);

namespace Testbench\Mocks;

use Nette\Database\Drivers\MySqlDriver;
use Nette\Database\Drivers\PgSqlDriver;

/**
 * @method onConnect(NetteDatabaseConnectionMock $connection)
 */
class NetteDatabaseConnectionMock extends \Nette\Database\Connection implements \Testbench\Providers\IDatabaseProvider
{

	private $__testbenchDatabaseName;


	public function __construct($dsn, $user = null, $password = null, array $options = null)
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
			} catch (\Exception $e) {
				\Tester\Assert::fail($e->getMessage());
			}
		};
		parent::__construct($dsn, $user, $password, $options);
	}


	/** @internal */
	public function __testbenchDatabaseSetup($connection, \Nette\DI\Container $container, $persistent = false)
	{
		$config = $container->parameters['testbench'];
		$this->__testbenchDatabaseName = $config['dbprefix'] . getenv(\Tester\Environment::THREAD);

		$this->__testbenchDatabaseDrop($connection, $container);
		$this->__testbenchDatabaseCreate($connection, $container);

		foreach ($config['sqls'] as $file) {
			\Nette\Database\Helpers::loadFromFile($connection, $file);
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
	 * @param $connection \Nette\Database\Connection
	 */
	public function __testbenchDatabaseCreate($connection, \Nette\DI\Container $container)
	{
		$connection->query("CREATE DATABASE {$this->__testbenchDatabaseName}");
		$this->__testbenchDatabaseChange($connection, $container);
	}


	/**
	 * @internal
	 *
	 * @param $connection \Nette\Database\Connection
	 */
	public function __testbenchDatabaseChange($connection, \Nette\DI\Container $container)
	{
		if ($connection->getSupplementalDriver() instanceof MySqlDriver) {
			$connection->query("USE {$this->__testbenchDatabaseName}");
		} else {
			$this->__testbenchDatabaseConnect($connection, $container, $this->__testbenchDatabaseName);
		}
	}


	/**
	 * @internal
	 *
	 * @param $connection \Nette\Database\Connection
	 */
	public function __testbenchDatabaseDrop($connection, \Nette\DI\Container $container)
	{
		if (!$connection->getSupplementalDriver() instanceof MySqlDriver) {
			$this->__testbenchDatabaseConnect($connection, $container);
		}
		$connection->query("DROP DATABASE IF EXISTS {$this->__testbenchDatabaseName}");
	}


	/**
	 * @internal
	 *
	 * @param $connection \Nette\Database\Connection
	 */
	public function __testbenchDatabaseConnect($connection, \Nette\DI\Container $container, $databaseName = null)
	{
		//connect to an existing database other than $this->_databaseName
		if ($databaseName === null) {
			$dbname = $container->parameters['testbench']['dbname'];
			if ($dbname) {
				$databaseName = $dbname;
			} elseif ($connection->getSupplementalDriver() instanceof PgSqlDriver) {
				$databaseName = 'postgres';
			} else {
				throw new \LogicException('You should setup existing database name using testbench:dbname option.');
			}
		}

		$dsn = preg_replace('~dbname=[a-z0-9_-]+~i', "dbname=$databaseName", $connection->getDsn());

		$dbr = (new \Nette\Reflection\ClassType($connection))->getParentClass(); //:-(
		$params = $dbr->getProperty('params');
		$params->setAccessible(true);
		$params = $params->getValue($connection);

		$options = $dbr->getProperty('options');
		$options->setAccessible(true);
		$options = $options->getValue($connection);

		$connection->disconnect();
		$connection->__construct($dsn, $params[1], $params[2], $options);
		$connection->connect();
	}
}
