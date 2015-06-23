<?php

namespace App\AdminModule\Presenters;

use Kdyby\Doctrine\EntityManager;
use Options\Components\OptionsForm\IOptionsFormFactory;
use Options\Components\OptionsMenu\IOptionsMenuFactory;
use Users\Role;

class OptionsPresenter extends BasePresenter
{

	/** @var EntityManager */
	private $em;

	public function __construct(EntityManager $em)
	{
		$this->em = $em;
	}

	public function renderSecurity()
	{
		//TODO: query optimization
		$this->template->roles = $this->em->getRepository(Role::class)->findAll();
	}

	protected function createComponentOptionsMenu(IOptionsMenuFactory $factory)
	{
		return $factory->create();
	}

	protected function createComponentGeneralSettings(IOptionsFormFactory $factory)
	{
		return $factory->create('general');
	}

	protected function createComponentSeoSettings(IOptionsFormFactory $factory)
	{
		return $factory->create('seo');
	}

}
