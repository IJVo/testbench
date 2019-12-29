<?php

declare(strict_types=1);

namespace Testbench\Mocks;

use Nextras\Dbal\Drivers\Mysqli\MysqliDriver;
use Nextras\Dbal\Drivers\Pgsql\PgsqlDriver;

/**
 * @method onConnect(NextrasDbalConnectionMock $connection)
 */
class NextrasDbalConnectionMock extends \Nextras\Dbal\Connection implements \Testbench\Providers\IDatabaseProvider
{

	private $__testbench_database_name;


	public function __construct(array $config)
	{
		$container = \Testbench\ContainerFactory::create(FALSE);
		$this->onConnect[] = function (NextrasDbalConnectionMock $connection) use ($container) {
			if ($this->__testbench_database_name !== NULL) { //already initialized (needed for pgsql)
				return;
			}
			try {
				$config = $container->parameters['testbench'];
				if ($config['shareDatabase'] === TRUE) {
					$registry = new \Testbench\DatabasesRegistry;
					$dbName = $container->parameters['testbench']['dbprefix'] . getenv(\Tester\Environment::THREAD);
					if ($registry->registerDatabase($dbName)) {
						$this->__testbench_database_setup($connection, $container, TRUE);
					} else {
						$this->__testbench_database_name = $dbName;
						$this->__testbench_database_change($connection, $container);
					}
				} else { // always create new test database
					$this->__testbench_database_setup($connection, $container);
				}
			} catch (\Exception $e) {
				\Tester\Assert::fail($e->getMessage());
			}
		};
		parent::__construct($config);
	}


	/** @internal */
	public function __testbench_database_setup($connection, \Nette\DI\Container $container, $persistent = FALSE)
	{
		$config = $container->parameters['testbench'];
		$this->__testbench_database_name = $config['dbprefix'] . getenv(\Tester\Environment::THREAD);

		$this->__testbench_database_drop($connection, $container);
		$this->__testbench_database_create($connection, $container);

		foreach ($config['sqls'] as $file) {
			\Nextras\Dbal\Utils\FileImporter::executeFile($connection, $file);
		}

		if ($persistent === FALSE) {
			register_shutdown_function(function () use ($connection, $container) {
				$this->__testbench_database_drop($connection, $container);
			});
		}
	}


	/**
	 * @internal
	 *
	 * @param $connection \Nextras\Dbal\Connection
	 */
	public function __testbench_database_create($connection, \Nette\DI\Container $container)
	{
		$connection->query("CREATE DATABASE {$this->__testbench_database_name}");
		$this->__testbench_database_change($connection, $container);
	}


	/**
	 * @internal
	 *
	 * @param $connection \Nextras\Dbal\Connection
	 */
	public function __testbench_database_change($connection, \Nette\DI\Container $container)
	{
		if ($connection->getDriver() instanceof MysqliDriver) {
			$connection->query("USE {$this->__testbench_database_name}");
		} else {
			$this->__testbench_database_connect($connection, $container, $this->__testbench_database_name);
		}
	}


	/**
	 * @internal
	 *
	 * @param $connection \Nextras\Dbal\Connection
	 */
	public function __testbench_database_drop($connection, \Nette\DI\Container $container)
	{
		if (!$connection->getDriver() instanceof MysqliDriver) {
			$this->__testbench_database_connect($connection, $container);
		}
		$connection->query("DROP DATABASE IF EXISTS {$this->__testbench_database_name}");
	}


	/**
	 * @internal
	 *
	 * @param $connection \Nextras\Dbal\Connection
	 */
	public function __testbench_database_connect($connection, \Nette\DI\Container $container, $databaseName = NULL)
	{
		//connect to an existing database other than $this->_databaseName
		if ($databaseName === NULL) {
			$dbname = $container->parameters['testbench']['dbname'];
			if ($dbname) {
				$databaseName = $dbname;
			} elseif ($connection->getDriver() instanceof PgsqlDriver) {
				$databaseName = 'postgres';
			} else {
				throw new \LogicException('You should setup existing database name using testbench:dbname option.');
			}
		}


//		$dsn = preg_replace('~dbname=[a-z0-9_-]+~i', "dbname=$databaseName", $connection->getDsn());
//
//		$dbr = (new \Nette\Reflection\ClassType($connection))->getParentClass(); //:-(
//		$params = $dbr->getProperty('params');
//		$params->setAccessible(TRUE);
//		$params = $params->getValue($connection);
//
//		$options = $dbr->getProperty('options');
//		$options->setAccessible(TRUE);
//		$options = $options->getValue($connection);

		$connection->disconnect();
		$connection->__construct(['database' => $databaseName] + $connection->getConfig());
		$connection->connect();
	}
}
