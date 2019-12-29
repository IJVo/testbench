<?php

declare(strict_types=1);

namespace Testbench\Mocks;

use Nette\Http;

class HttpRequestMock extends \Nette\Http\Request
{


	public function __construct(
					Http\UrlScript $url = null,
					$query = null,
					$post = [],
					$files = [],
					$cookies = [],
					$headers = [],
					$method = PHP_SAPI,
					$remoteAddress = '127.0.0.1',
					$remoteHost = '127.0.0.1',
					$rawBodyCallback = null
	)
	{
		$url = $this->prepareUrl($url, $query);
		$params = [
				$url, null, $post, $files, $cookies, $headers, $method, $remoteAddress, $remoteHost, $rawBodyCallback,
		];
		$this->checkParams($params);
		parent::__construct(
						...$params
		);
	}


	private function prepareUrl(Http\UrlScript $url, ?array $query): Http\UrlScript
	{
		$url = $url ?: new Http\UrlScript('http://test.bench/');
		if ($query !== null) {
			$query = http_build_query($query);
			$address = $url->absoluteUrl;
			if ($url->query !== '') {
				$address = str_replace($url->query, $query, $address);
			} else {
				$address .= "?$query";
			}
			$url = new Http\UrlScript($address);
		}
		return $url;
	}


	private function checkParams(array &$params): void
	{
		$reflection = new \ReflectionMethod(parent::class, '__construct');
		if (count($params) > $reflection->getNumberOfParameters()) {
			unset($params[1]);
		}
	}
}
