<?php

declare(strict_types=1);

namespace Testbench\Mocks;

class ControlMock extends \Nette\Application\UI\Control
{


	public function link($destination, $args = []): string
	{
		if (!is_array($args)) {
			$args = array_slice(func_get_args(), 1);
		}
		$params = urldecode(http_build_query($args, '', ', '));
		$params = $params ? "($params)" : '';
		return "link|$destination$params";
	}
}
