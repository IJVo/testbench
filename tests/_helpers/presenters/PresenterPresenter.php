<?php
declare(strict_types = 1);

use Nette\Application\UI;

class PresenterPresenter extends Nette\Application\UI\Presenter
{

	/** @persistent */
	public $persistentParameter;


	public function actionJson(): void
	{
		$this->sendResponse(new \Nette\Application\Responses\JsonResponse([
			'string' => [
				1234 => [],
			],
		]));
	}

	public function renderDefault(): void
	{
		$this->template->variable = 'test';
	}

	public function renderFail(): void
	{
		$this->error('', \Nette\Http\IResponse::S500_INTERNAL_SERVER_ERROR);
	}

	public function renderException(): void
	{
		throw new \Latte\CompileException;
	}

	public function renderRedirect(): void
	{
		$this->flashMessage('Because of _fid parameter to the URL...');
		$this->redirect('default');
	}

	public function renderRedirectRss($flashMessage = true): void
	{
		if ($flashMessage) {
			$this->flashMessage('Because of _fid parameter to the URL...');
		}
		$this->redirect('rss');
	}

	public function renderRss(): void
	{
		$this->template->posts = [
			\Nette\Utils\ArrayHash::from([
				'title' => 'title 1',
				'content' => 'content 1',
			]),
			\Nette\Utils\ArrayHash::from([
				'title' => 'title 1',
				'content' => 'content 1',
			]),
		];
	}

	public function renderSitemap(): void
	{
		$this->template->sitemap = [0, 1, 2]; //dumb
	}

	protected function createComponentForm1(): UI\Form
	{
		$form = new UI\Form();
		$form->disableSameSiteProtection();
		$form->addText('test')->setRequired();
		$form->addText('error');
		$form->onSuccess[] = function (UI\Form $form, $values) {
			if (!empty($values->error) && $values->error !== '###') { //scaffold
				$form->addError('Intended error: ' . $values->error);
			}
			$this->flashMessage(json_encode($values));
			$this->redirect('this');
		};
		return $form;
	}

	protected function createComponentForm2(): UI\Form
	{
		$form = new UI\Form();
		$form->disableSameSiteProtection();
		$form->addText('test');
		$form->onSuccess[] = function ($_, $values) {
			$this->flashMessage(json_encode($values));
			$this->redirect('json');
		};
		return $form;
	}

	protected function createComponentForm3(): UI\Form
	{
		$form = new \Nette\Application\UI\Form();
		$form->disableSameSiteProtection();
		$form->addText('test');
		$form->onSuccess[] = function ($_, $values) {
			$this->flashMessage(json_encode($values));
		};
		return $form;
	}

	protected function createComponentForm4(): UI\Form
	{
		$form = new UI\Form();
		$form->disableSameSiteProtection();
		$form->addText('test'); //should be required, but it's not
		$form->onSuccess[] = function ($_, $values) {
			$this->flashMessage(json_encode($values));
			$this->redirect('this');
		};
		return $form;
	}

	protected function createComponentFormWithCheckbox(): UI\Form
	{
		$form = new \Nette\Application\UI\Form();
		$form->disableSameSiteProtection();
		$form->addCheckbox('hello');
		$form->addText('test');
		$form->onSuccess[] = function ($_, $values) {
			$this->flashMessage(json_encode($values));
			$this->redirect('this');
		};
		return $form;
	}

	protected function createComponentAjaxForm(): UI\Form
	{
		$form = new UI\Form();
		$form->disableSameSiteProtection();
		$form->addText('test');
		$form->onSuccess[] = function ($_, $values) {
			$this->flashMessage(json_encode($values));
			if ($this->isAjax()) {
				$this->redrawControl();
			} else {
				$this->redirect('json');
			}
		};
		return $form;
	}

	protected function createComponentCsrfForm(): UI\Form
	{
		$form = new UI\Form();
		$form->disableSameSiteProtection();
		$form->addProtection('CSRF protection applied!');
		$form->addText('test');
		$form->onSuccess[] = function ($_, $values) {
			$this->redirect('this');
		};
		return $form;
	}

	public function handleSignal(): void
	{
		$this->flashMessage('OK');
		$this->redirect('this');
	}

	/** @crossOrigin */
	public function handleAjaxSignal()
	{
		\Testbench\IJVoLog::log('PresenterPresenter.php - handleAjaxSignal - Start $this->persistentParameter', $this->persistentParameter);

		$this->flashMessage('OK');

		if ($this->isAjax()) {

			\Testbench\IJVoLog::log('PresenterPresenter.php - handleAjaxSignal - isAjax');

			$this->sendJson(['ok']);
		} else {

			\Testbench\IJVoLog::log('PresenterPresenter.php - handleAjaxSignal - NOT isAjax');

			$this->redirect('this');
		}
	}

}
