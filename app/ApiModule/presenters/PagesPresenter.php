<?php

namespace App\ApiModule\Presenters;

use Doctrine\ORM\AbstractQuery;
use Kdyby\Doctrine\EntityManager;
use Nette;
use Pages\Page;
use Pages\Query\PagesQuery;

class PagesPresenter extends BasePresenter
{

	/** @var EntityManager @inject */
	public $em;

	protected function startup()
	{
		parent::startup();

		if (!$this->user->isLoggedIn()) {
			$this->error("Unauthorized", Nette\Http\IResponse::S401_UNAUTHORIZED);
		}
	}

	public function actionReadAll()
	{
		$query = (new PagesQuery())->withAllAuthors()->withAllCategories();
		$pages = $this->em->getRepository(Page::class)->fetch($query);
		$pages->setFetchJoinCollection(FALSE);
		$this->payload->pages = $pages->getIterator(AbstractQuery::HYDRATE_ARRAY);
		$this->success();
	}

	public function actionRead($id)
	{
		$query = (new PagesQuery())->byId($id)->withAllAuthors()->withAllCategories();
		$pages = $this->em->getRepository(Page::class)->fetch($query);
		$pages->setFetchJoinCollection(FALSE);
		$this->payload->page = $pages->getIterator(AbstractQuery::HYDRATE_ARRAY);
		$this->success();
	}

	public function actionCreate()
	{
		$this->payload->pingBack = $this->request->getPost();
		$this->success();
	}

}
