<?php

declare(strict_types=1);

namespace Testbench;

use Tester\Assert;
use Tester\Dumper;

trait TPresenter
{

	/** @var \Nette\Application\IPresenter */
	private $__testbench_presenter;
	private $__testbench_httpCode;
	private $__testbench_exception;
	private $__testbench_ajaxMode = false;


	/**
	 * @param string $destination fully qualified presenter name (module:module:presenter)
	 * @param array $params provided to the presenter usually via URL
	 * @param array $post provided to the presenter via POST
	 *
	 * @throws \Exception
	 */
	protected function check(string $destination, array $params = [], array $post = []): \Nette\Application\IResponse
	{
IJVoLog::log('TPresenter.php - check() -1 $this->__testbench_exception', $this->__testbench_exception);

		$destination = ltrim($destination, ':');
		$pos = strrpos($destination, ':');
		$presenter = substr($destination, 0, $pos);
		$action = substr($destination, $pos + 1) ?: 'default';

		$container = ContainerFactory::create(false);
		$container->removeService('httpRequest');
		$headers = $this->__testbench_ajaxMode ? ['X-Requested-With' => 'XMLHttpRequest'] : [];
		$url = new \Nette\Http\UrlScript($container->parameters['testbench']['url']);
	IJVoLog::log('TPresenter.php - Check() - $url', $url);
	IJVoLog::log('TPresenter.php - Check() - $params', $params);

		$container->addService('httpRequest', new Mocks\HttpRequestMock($url, $params, $post, [], [], $headers));
	IJVoLog::log('TPresenter.php - Check() - $container', $container);

		$presenterFactory = $container->getByType(\Nette\Application\IPresenterFactory::class);
		$this->__testbench_presenter = $presenterFactory->createPresenter($presenter);
		$this->__testbench_presenter->autoCanonicalize = false;
		$this->__testbench_presenter->invalidLinkMode = \Nette\Application\UI\Presenter::INVALID_LINK_EXCEPTION;

		$postCopy = $post;

	IJVoLog::log('TPresenter.php - Check() - $post', $post);

		if (isset($params['do'])) {
			foreach ($post as $key => $field) {
	IJVoLog::log('TPresenter.php - Check() - $post -key', $key);
	IJVoLog::log('TPresenter.php - Check() - $post -field', $field);

				if (is_array($field) && array_key_exists(\Nette\Forms\Form::REQUIRED, $field)) {
					$post[$key] = $field[0];
				}
			}
		}

		/** @var \Kdyby\FakeSession\Session $session */
		$session = $this->__testbench_presenter->getSession();
		$session->setFakeId('testbench.fakeId');
		$session->getSection('Nette\Forms\Controls\CsrfProtection')->token = 'testbench.fakeToken';
		$post = $post + ['_token_' => 'goVdCQ1jk0UQuVArz15RzkW6vpDU9YqTRILjE=']; //CSRF magic! ¯\_(ツ)_/¯

		$request = new \Nette\Application\Request(
						$presenter,
						$post ? 'POST' : 'GET',
						['action' => $action] + $params,
						$post
		);

		IJVoLog::log('TPresenter.php - Check() - $destination', $destination);
		IJVoLog::log('$params', $params);
		IJVoLog::log('$request', $request);

		try {
			$this->__testbench_httpCode = 200;
			$this->__testbench_exception = null;

			IJVoLog::log('TPresenter.php - Check() - __testbench_presenter', $this->__testbench_presenter);

			//**************** RUN ************************************
			$response = $this->__testbench_presenter->run($request);

			IJVoLog::log('TPresenter.php - Check() - $response', $response);

			if (isset($params['do'])) {
				if (preg_match('~(.+)-submit$~', $params['do'], $matches)) {

			IJVoLog::log('TPresenter.php - Check() - preg_match $matches', $matches);

					/** @var \Nette\Application\UI\Form $form */
					$form = $this->__testbench_presenter->getComponent($matches[1]);

	IJVoLog::log('TPresenter.php - Check() - $form', $form);

					foreach ($form->getControls() as $control) {

	IJVoLog::log('TPresenter.php - Check() - $control', $control);

						if (array_key_exists($control->getName(), $postCopy)) {

	IJVoLog::log('TPresenter.php - Check() - $postCopy', $postCopy);

							$subvalues = $postCopy[$control->getName()];

	IJVoLog::log('TPresenter.php - Check() - $subvalues', $subvalues);

							$rq = \Nette\Forms\Form::REQUIRED;
	IJVoLog::log('TPresenter.php - Check() - $rq', $rq);

							if (is_array($subvalues) && array_key_exists($rq, $subvalues) && $subvalues[$rq]) {
								if ($control->isRequired() !== true) {
									Assert::fail("field '{$control->name}' should be defined as required, but it's not");
								}
							}
						}
						if ($control->hasErrors()) {
							$errors = '';
	IJVoLog::log('TPresenter.php - Check() - $control->hasErrors()', $control->hasErrors());
							$counter = 1;
							foreach ($control->getErrors() as $error) {
								$errors .= "  - $error\n";
								$counter++;
							}
							Assert::fail("field '{$control->name}' returned this error(s):\n$errors");
						}
					}
					foreach ($form->getErrors() as $error) {

	IJVoLog::log('TPresenter.php - Check() - $error', $error);

			Assert::fail($error);
					}
				}
			}

			IJVoLog::log('TPresenter.php - Check() End - $response', $response);

			$IsOk = true;  // hack for Nette tester
			Assert::true($IsOk);

			return $response;
		} catch (\Exception $exc) {
			$this->__testbench_exception = $exc;
			$this->__testbench_httpCode = $exc->getCode();

			IJVoLog::log('TPresenter.php - Check() Exception - $exc', $exc);

			throw $exc;
		}
	}


	/**
	 * @param string $destination fully qualified presenter name (module:module:presenter)
	 * @param array $params provided to the presenter usually via URL
	 * @param array $post provided to the presenter via POST
	 *
	 * @throws \Exception
	 */
	protected function checkAction(string $destination, array $params = [], array $post = []): \Nette\Application\Responses\TextResponse
	{
		/** @var \Nette\Application\Responses\TextResponse $response */
		$response = $this->check($destination, $params, $post);
		if (!$this->__testbench_exception) {
			Assert::same(200, $this->getReturnCode());
			Assert::type('Nette\Application\Responses\TextResponse', $response);
			Assert::type('Nette\Application\UI\ITemplate', $response->getSource());

			$html = (string) $response->getSource();
			//DOMDocument doesn't handle HTML tags inside of script tags very well
			$html = preg_replace('~<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>~', '', $html); //http://stackoverflow.com/a/6660315/3135248
			$dom = @\Tester\DomQuery::fromHtml($html);
			Assert::true($dom->has('html'), "missing 'html' tag");
			Assert::true($dom->has('title'), "missing 'title' tag");
			Assert::true($dom->has('body'), "missing 'body' tag");
		}
		return $response;
	}


	/**
	 * @param string $destination
	 * @param string $signal
	 * @param array $params
	 * @param array $post
	 * @param type $isRedir
	 * @return \Nette\Application\IResponse
	 */
	protected function checkSignal(string $destination, string $signal, array $params = [], array $post = [], $isRedir = true): \Nette\Application\IResponse
	{
		return $this->checkRedirect($destination, false, [
								'do' => $signal,
										] + $params, $post, $isRedir);
	}


	/**
	 * @param string $destination
	 * @param string $signal
	 * @param array $params
	 * @param array $post
	 * @return \Nette\Application\Responses\JsonResponse
	 */
	protected function checkAjaxSignal(string $destination, string $signal, array $params = [], array $post = []): \Nette\Application\Responses\JsonResponse
	{
IJVoLog::log('TPresenter.php - checkAjaxSignal() -1A Start $destination', $destination);
IJVoLog::log('TPresenter.php - checkAjaxSignal() -1B Start $signal', $signal);

		$this->__testbench_ajaxMode = true;
		$response = $this->check($destination, [
				'do' => $signal,
						] + $params, $post);
IJVoLog::log('TPresenter.php - checkAjaxSignal() -2 $response', $response);

		Assert::true($this->__testbench_presenter->isAjax());

IJVoLog::log('TPresenter.php - checkAjaxSignal() -3 $this->__testbench_exception', $this->__testbench_exception);

		if (!$this->__testbench_exception) {
			Assert::same(200, $this->getReturnCode());
			Assert::type(\Nette\Application\Responses\JsonResponse::class, $response);
		}
		$this->__testbench_ajaxMode = false;
IJVoLog::log('TPresenter.php - checkAjaxSignal() -9 End $response', $response);

		return $response;
	}


	/**
	 * @param string $destination fully qualified presenter name (module:module:presenter)
	 * @param string $path
	 * @param array $params provided to the presenter usually via URL
	 * @param array $post provided to the presenter via POST
	 * @param bool $isRedir
	 *
	 * @return \Nette\Application\Responses\RedirectResponse
	 * @throws \Exception
	 */
	protected function checkRedirect(string $destination, $path = '/', array $params = [], array $post = [], $isRedir = true)
	{
		IJVoLog::log('TPresenter.php - checkRedirect() - $path', $path);

		/** @var \Nette\Application\Responses\RedirectResponse $response */
		$response = $this->check($destination, $params, $post);

		IJVoLog::log('TPresenter.php - checkRedirect() - $response', $response);

		IJVoLog::log('TPresenter.php - checkRedirect() - $this->__testbench_exception', $this->__testbench_exception);

		if (!($this->__testbench_exception)) {
//			Assert::same(200, $this->getReturnCode());
//			Assert::type('Nette\Application\Responses\RedirectResponse', $response);
//			Assert::same(302, $response->getCode());

			Assert::same(200, $this->getReturnCode());
			if ($isRedir == TRUE) {
				Assert::type(\Nette\Application\Responses\RedirectResponse::class, $response);
				Assert::same(302, $response->getCode());
			} else {
				Assert::type(\Nette\Application\Responses\TextResponse::class, $response);
			}

			if ($path) {
				if (!Assert::isMatching("~^https?://test\.bench{$path}(?(?=\?).+)$~", $response->getUrl())) {
					$path = Dumper::color('yellow') . Dumper::toLine($path) . Dumper::color('white');
					$url = Dumper::color('yellow') . Dumper::toLine($response->getUrl()) . Dumper::color('white');
					$originalUrl = new \Nette\Http\Url($response->getUrl());

	IJVoLog::log('TPresenter.php - checkRedirect() -isMatching - $path', $path);
	IJVoLog::log('TPresenter.php - checkRedirect() -isMatching - $url', $url);
	IJVoLog::log('TPresenter.php - checkRedirect() -isMatching - $originalUrl', $originalUrl);

					Assert::fail(
									str_repeat(' ', strlen($originalUrl->getHostUrl()) - 13) // strlen('Failed: path ') = 13
									. "path $path doesn't match\n$url\nafter redirect"
					);
				}
			}
		}
		return $response;
	}


	/**
	 * @param string $destination fully qualified presenter name (module:module:presenter)
	 * @param array $params provided to the presenter usually via URL
	 * @param array $post provided to the presenter via POST
	 *
	 * @throws \Exception
	 */
	protected function checkJson(string $destination, array $params = [], array $post = []): \Nette\Application\Responses\JsonResponse
	{
		/** @var \Nette\Application\Responses\JsonResponse $response */
		$response = $this->check($destination, $params, $post);
		if (!$this->__testbench_exception) {
			Assert::same(200, $this->getReturnCode());
			Assert::type(\Nette\Application\Responses\JsonResponse::class, $response);
			Assert::same('application/json', $response->getContentType());
		}
		return $response;
	}


	/**
	 * @param string $destination fully qualified presenter name (module:module:presenter)
	 * @param array $scheme what is expected
	 * @param array $params provided to the presenter usually via URL
	 * @param array $post provided to the presenter via POST
	 */
	public function checkJsonScheme(string $destination, array $scheme, array $params = [], array $post = []): void
	{
		$response = $this->checkJson($destination, $params, $post);
		Assert::same($scheme, $response->getPayload());
	}


	/**
	 * @param string $destination fully qualified presenter name (module:module:presenter)
	 * @param string $formName
	 * @param array $post provided to the presenter via POST
	 * @param string|bool $path Path after redirect or FALSE if it's form without redirect
	 *
	 * @throws \Tester\AssertException
	 */
	protected function checkForm(string $destination, string $formName, array $post = [], $path = '/'): \Nette\Application\IResponse
	{
			IJVoLog::log('TPresenter.php - checkForm() - $path', $path);
		if (is_string($path)) {

//			return $this->checkRedirect($destination, $path, [
//									'do' => $formName . '-submit',
//											], $post);

			$chckRedir = $this->checkRedirect($destination, $path, [
									'do' => $formName . '-submit',
											], $post);
			IJVoLog::log('TPresenter.php - checkForm() - $chckRedir', $chckRedir);

			return $chckRedir;

		} elseif (is_bool($path)) {
			/** @var \Nette\Application\Responses\RedirectResponse $response */
			$response = $this->check($destination, [
					'do' => $formName . '-submit',
							], $post);
			if (!$this->__testbench_exception) {
				Assert::same(200, $this->getReturnCode());
				Assert::type(\Nette\Application\Responses\TextResponse::class, $response);
			}
			return $response;
		} else {
			Assert::fail('Path should be string or boolean (probably FALSE).');
		}
	}


	/**
	 * @param string $destination fully qualified presenter name (module:module:presenter)
	 * @param $formName
	 * @param array $post provided to the presenter via POST
	 * @param string|bool $path
	 *
	 * @throws \Exception
	 */
	protected function checkAjaxForm(string $destination, string $formName, array $post = [], $path = false): \Nette\Application\IResponse
	{
		if (is_string($path)) {
			$this->checkForm($destination, $formName, $post, $path);
			Assert::false($this->__testbench_presenter->isAjax());
		}
		$this->__testbench_presenter = null; //FIXME: not very nice, but performance first
		$this->__testbench_ajaxMode = true;
		$response = $this->check($destination, [
				'do' => $formName . '-submit',
						], $post);
		Assert::true($this->__testbench_presenter->isAjax());
		if (!$this->__testbench_exception) {
			Assert::same(200, $this->getReturnCode());
			Assert::type(\Nette\Application\Responses\JsonResponse::class, $response);
		}
		$this->__testbench_presenter = null;
		$this->__testbench_ajaxMode = false;
		return $response;
	}


	/**
	 * @param string $destination fully qualified presenter name (module:module:presenter)
	 * @param array $params provided to the presenter usually via URL
	 * @param array $post provided to the presenter via POST
	 *
	 * @throws \Exception
	 */
	protected function checkRss(string $destination, array $params = [], array $post = []): \Nette\Application\Responses\TextResponse
	{
		/** @var \Nette\Application\Responses\TextResponse $response */
		$response = $this->check($destination, $params, $post);
		if (!$this->__testbench_exception) {
			Assert::same(200, $this->getReturnCode());
			Assert::type(\Nette\Application\Responses\TextResponse::class, $response);
			Assert::type(\Nette\Application\UI\ITemplate::class, $response->getSource());

			$dom = \Tester\DomQuery::fromXml((string) $response->getSource());
			Assert::true($dom->has('rss'), "missing 'rss' element");
			Assert::true($dom->has('channel'), "missing 'channel' element");
			Assert::true($dom->has('title'), "missing 'title' element");
			Assert::true($dom->has('link'), "missing 'link' element");
			Assert::true($dom->has('item'), "missing 'item' element");
		}
		return $response;
	}


	/**
	 * @param string $destination fully qualified presenter name (module:module:presenter)
	 * @param array $params provided to the presenter usually via URL
	 * @param array $post provided to the presenter via POST
	 *
	 * @throws \Exception
	 */
	protected function checkSitemap(string $destination, array $params = [], array $post = []): \Nette\Application\Responses\TextResponse
	{
		/** @var \Nette\Application\Responses\TextResponse $response */
		$response = $this->check($destination, $params, $post);
		if (!$this->__testbench_exception) {
			Assert::same(200, $this->getReturnCode());
			Assert::type(\Nette\Application\Responses\TextResponse::class, $response);
			Assert::type(\Nette\Application\UI\ITemplate::class, $response->getSource());

			$xml = \Tester\DomQuery::fromXml((string) $response->getSource());
			Assert::same('urlset', $xml->getName(), 'root element is');
			$url = $xml->children();
			Assert::same('url', $url->getName(), "child of 'urlset'");
			Assert::same('loc', $url->children()->getName(), "child of 'url'");
		}
		return $response;
	}


	/**
	 * @param \Nette\Security\IIdentity|int $id
	 * @param array|null $roles
	 * @param array|null $data
	 */
	protected function logIn($id = 1, $roles = null, $data = null): \Nette\Security\User
	{
		if ($id instanceof \Nette\Security\IIdentity) {
			$identity = $id;
		} else {
			$identity = new \Nette\Security\Identity($id, $roles, $data);
		}
		/** @var \Nette\Security\User $user */
		$user = ContainerFactory::create(false)->getByType(\Nette\Security\User::class);
		$user->login($identity);
		return $user;
	}


	protected function logOut(): \Nette\Security\User
	{
		/** @var \Nette\Security\User $user */
		$user = ContainerFactory::create(false)->getByType(\Nette\Security\User::class);
		$user->logout();
		return $user;
	}


	protected function isUserLoggedIn(): bool
	{
		/** @var \Nette\Security\User $user */
		$user = ContainerFactory::create(false)->getByType(\Nette\Security\User::class);
		return $user->isLoggedIn();
	}


	/**
	 * @return \Nette\Application\UI\Presenter
	 */
	protected function getPresenter()
	{
		return $this->__testbench_presenter;
	}


	protected function getReturnCode(): int
	{
		return $this->__testbench_httpCode;
	}


	protected function getException(): \Exception
	{
		return $this->__testbench_exception;
	}
}
