<?php

declare(strict_types=1);

namespace ModuleModule;

use Nette;

class PresenterPresenter extends Nette\Application\UI\Presenter
{


	public function renderDefault(): void
	{
		$this->template->variable = 'test';
	}
}
