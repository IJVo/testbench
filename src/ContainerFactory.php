<?php

declare(strict_types=1);

namespace Testbench;

/**
 * @internal
 */
class ContainerFactory
{

	use \Nette\SmartObject;

	private static $container;


	private function __construct()
	{
		//Cannot be initialized
	}


	final public static function create(bool $new = false, array $config = []): \Nette\DI\Container
	{
		if ($new || self::$container === null) {
			$configurator = new \Nette\Configurator();
			$configurator->addParameters($config);

			$configurator->onCompile[] = function (\Nette\Configurator $configurator, \Nette\DI\Compiler $compiler) use ($config) {
				$compiler->addConfig($config);
				$compiler->addExtension('testbench', new TestbenchExtension);
				self::registerAdditionalExtension($compiler, 'fakeSession', new \Kdyby\FakeSession\DI\FakeSessionExtension);
				if (class_exists(\Kdyby\Console\DI\ConsoleExtension::class)) {
					self::registerAdditionalExtension($compiler, 'console', new \Kdyby\Console\DI\ConsoleExtension);
				}
			};

			$configurator->setTempDirectory(Bootstrap::$tempDir); // shared container for performance purposes
			$configurator->setDebugMode(false);

			if (is_callable(Bootstrap::$onBeforeContainerCreate)) {
				call_user_func_array(Bootstrap::$onBeforeContainerCreate, [$configurator]);
			}

			self::$container = $configurator->createContainer();
		}
		return self::$container;
	}


	/**
	 * Register extension if not registered by user.
	 */
	private static function registerAdditionalExtension(\Nette\DI\Compiler $compiler, string $name, $newExtension): void
	{
		$extensions = [];
		$config = $compiler->getConfig();
		foreach ($config['extensions'] ?? [] as $extension) {
			if (is_string($extension)) {
				$extensions[] = $extension;
			} elseif ($extension instanceof \Nette\DI\Statement) {
				$extensions[] = $extension->getEntity();
			}
		}
		if (!in_array(get_class($newExtension), $extensions, true)) {
			$compiler->addExtension($name, $newExtension);
		}
	}


	final public function __clone()
	{
		throw new \Exception('Clone is not allowed');
	}


	final public function __wakeup(): void
	{
		throw new \Exception('Unserialization is not allowed');
	}
}
