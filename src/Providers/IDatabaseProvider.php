<?php

declare(strict_types=1);

namespace Testbench\Providers;

/**
 * This interface is not stable yet. In fact it's really bad design and it needs refactor (stay tuned).
 */
interface IDatabaseProvider
{


	/**
	 * Perform complete database setup (should drop and create database, import sqls, run migrations).
	 * Register shutdown function only if it's not persistent setup.
	 */
	function __testbenchDatabaseSetup($connection, \Nette\DI\Container $container, $persistent = false);


	/**
	 * Drop database.
	 * This function uses internal '__testbenchDatabaseName'. Needs refactor!
	 */
	function __testbenchDatabaseDrop($connection, \Nette\DI\Container $container);


	/**
	 * Create new database.
	 * This function uses internal '__testbenchDatabaseName'. Needs refactor!
	 */
	function __testbenchDatabaseCreate($connection, \Nette\DI\Container $container);


	/**
	 * Connect to the database.
	 * This function uses internal '__testbenchDatabaseName'. Needs refactor!
	 */
	function __testbenchDatabaseConnect($connection, \Nette\DI\Container $container, $databaseName = null);


	/**
	 * Change database as quickly as possible (USE in MySQL, connect in PostgreSQL).
	 * This function uses internal '__testbenchDatabaseName'. Needs refactor!
	 */
	function __testbenchDatabaseChange($connection, \Nette\DI\Container $container);
}
