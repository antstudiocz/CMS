<?php

namespace App\Presenters;

use Articles\Article;
use Articles\ArticleProcess;
use Articles\Query\ArticlesQuery;
use Elastica\Document;
use Elastica\Request;
use Kdyby\Doctrine\EntityManager;
use Latte;
use Nette;
use Tester\FileMock;

class HomepagePresenter extends BasePresenter
{

	/** @var Nette\Application\UI\ITemplateFactory @inject */
	public $templateFactory;

	/** @var EntityManager @inject */
	public $em;

	/** @var ArticleProcess @inject */
	public $articleProcess;

//	/** @var \Kdyby\ElasticSearch\Client @inject */
//	public $elastic;

	public function beforeRender()
	{
		parent::beforeRender();

//		$index = $this->elastic->getIndex('test');
//		if (!$index->exists()) {
//			$index->create();
//		}
//		$type = $index->getType('test');
//		$type->addDocument(new Document(1, ['username' => 'ruflin']));
//		$index->refresh();
//		$query = '{"query":{"query_string":{"query":"ruflin"}}}';
//		$path = $index->getName() . '/' . $type->getName() . '/_search';
//		$response = $this->elastic->request($path, Request::GET, $query);
//		$responseArray = $response->getData();
//		dump($responseArray);

		//What about XSS?
		$textFormDb = '<b>Toto je komponenta vložená z databáze:</b> {control contactForm, param => 42}';

		$template = $this->templateFactory->createTemplate($this);
		$template->setFile(FileMock::create($textFormDb)); //FIXME: lepší použít Latte s StringLoader než file mock ($latte = $template->getLatte();)

//		$template->param = ...
//		$latte = $template->getLatte(); //Latte\Engine
//		$latte->setLoader(new Latte\Loaders\StringLoader);
//		$rendered = $latte->renderToString($textFormDb, $template->getParameters());

		$this->template->text = Nette\Utils\Html::el()->setHtml($template);


		//QueryObject fetch example:
		$query = (new ArticlesQuery())->withAllAuthors();
		$articles = $this->em->getRepository(Article::class)->fetch($query);
		$articles->setFetchJoinCollection(FALSE);
		$articles->applyPaging(0, 100);
		$this->template->articles = $articles;


		//ArticleProcess example:
		$newArticle = new Article();
		$newArticle->setTitle('New Title');
		$newArticle->setBody('New **body**!');
		$this->articleProcess->onPersist[] = function (ArticleProcess $process, Article $article) {
			//this is just example, it's not necessary to use event (but it's prepared for listeners)
			//$this->em->flush($article); //(nevolat vždy, jen za posledním $em->persist)
			//čas na flashmessage atd...
		};
		$this->articleProcess->publish($newArticle);
	}

}
