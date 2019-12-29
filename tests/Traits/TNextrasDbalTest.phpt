<?php

declare(strict_types=1);

namespace Tests\Traits;

//use Nette\Database\Drivers\MySqlDriver;
use Nextras\Dbal\Drivers\Mysqli\MysqliDriver;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';

//require getenv('BOOTSTRAP');

/**
 * @testCase
 */
class TNextrasDbalTest extends \Tester\TestCase
{

	use \Testbench\TCompiledContainer;
	use \Testbench\TNextrasDbal;


	public function testLazyConnection()
	{
		$container = $this->getContainer();
		$db = $container->getByType('Nextras\Dbal\Connection');
		$db->onConnect[] = function () {
			Assert::fail('\Nextras\Dbal\Connection::$onConnect event should not be called if you do NOT need database');
		};
		\Tester\Environment::$checkAssertions = FALSE;
	}


	public function testContext()
	{
		Assert::type('Nextras\Dbal\Connection', $this->getConnection());
	}


	public function testDatabaseCreation()
	{
		/** @var \Nextras\Dbal\Connection $connection */
		$connection = $this->getConnection();
		$returnActualDatabaseName = function () use ($connection) { //getDriver is performing first connect (behaves lazy)
			$configConn = $connection->getConfig();
			return $configConn['database'];
		};
		if ($connection->getDriver() instanceof MysqliDriver) {
			Assert::match('information_schema', $returnActualDatabaseName());
			Assert::match('_testbench_' . getenv(\Tester\Environment::THREAD), $connection->query('SELECT DATABASE();')->fetchField());
		} else {
			Assert::same('postgres', $returnActualDatabaseName());
		}
	}


	public function testDatabaseSqls()
	{
		/** @var \Nextras\Dbal\Connection $connection */
		$connection = $this->getConnection();
		$result = $connection->query('SELECT * FROM table_1')->fetchAll();
		$result = \Nette\Utils\Arrays::associate($result, 'id=');
		$returnActualDatabaseName = function () use ($connection) { //getDriver is performing first connect (behaves lazy)
			$configConn = $connection->getConfig();
			return $configConn['database'];
		};

		Assert::same([
				1 => ['id' => 1, 'column_1' => 'value_1', 'column_2' => 'value_2'],
				['id' => 2, 'column_1' => 'value_1', 'column_2' => 'value_2'],
				['id' => 3, 'column_1' => 'value_1', 'column_2' => 'value_2'],
						], $result);

		if ($connection->getDriver() instanceof MysqliDriver) {
			Assert::match('information_schema', $returnActualDatabaseName());
		} else {
			Assert::same('_testbench_' . getenv(\Tester\Environment::THREAD), $returnActualDatabaseName());
		}
	}


//	public function testDatabaseConnectionReplacementInApp()
//	{
//		/** @var \Nette\Database\Context $context */
//		$context = $this->getService(\Nette\Database\Context::class);
//		new \NdbComponentWithDatabaseAccess($context); //tests inside
//		//app is not using onConnect from Testbench but it has to connect to the mock database
//	}


//	public function testConnectionMockSetup()
//	{
//		/** @var \Testbench\Mocks\NextrasDbalConnectionMock $connection */
//		$connection = $this->getService(\Testbench\Mocks\NextrasDbalConnectionMock::class);
//
//		$dbr = (new \Nette\Reflection\ClassType($connection))->getParentClass(); //:-(
//		$params = $dbr->getProperty('config');
//		$params->setAccessible(TRUE);
//		$params = $params->getValue($connection);
//
//		Assert::count(3, $params);
//		if ($connection->getDriver() instanceof MySqliDriver) {
//			Assert::match('information_schema', $params['database']);
//		} else {
//			Assert::match('postgres', $params['database']);
//		}
//	}
}

(new TNextrasDbalTest)->run();
