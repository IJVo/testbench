<?php

declare(strict_types=1);
/**
 * Copyright (c) 2016 Ing. Jaroslav Vaculík, IJVo
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

namespace Testbench;

class IJVoLog
{

	/** @var string */
	public static $directory = __DIR__ . '/../log2';

	/** @var string */
	public static $fileLog = 'IJVo_log_test';

	/** @var bool */
	public static $isLogTest = false;


	/**
	 * Logs message to file.
	 * @param  string $mess1
	 * @param  mixed $var1
	 */
	public static function log(string $mess1, $var1 = null)
	{
		if (self::$isLogTest === false) {
			return;
		}

		if (!self::$directory) {
			throw new \LogicException('Logging directory is not specified.');
		}

		@mkdir(self::$directory); // @ - directory may already exist

		if (!is_dir(self::$directory)) {
			throw new \RuntimeException('Logging directory ' . self::$directory . ' is not found or is not directory.');
		}

		if (!is_string($var1)) {
			$var1 = \Tracy\Dumper::toText($var1);
		}
		$var1 = trim($var1);
		$outMess = 'IJVoLog: ' . $mess1 . ': ' . $var1;

		$file = self::$directory . '/' . strtolower(self::$fileLog) . '.log';

		if (!@file_put_contents($file, $outMess . PHP_EOL, FILE_APPEND | LOCK_EX)) { // @ is escalated to exception
			throw new \RuntimeException('Unable to write to log file ' . $file . '. Is directory writable?');
		}
	}
}
