<?php

namespace Pages\Components\PageForm;

use App\Components\AControl;
use Files\Components\IUploaderFactory;
use Files\File;
use Kdyby\Doctrine\EntityManager;
use Nette;
use Nette\Application\Responses\JsonResponse;
use Nette\Application\UI;
use Nette\Forms\Controls\SubmitButton;
use Nette\Utils\ArrayHash;
use Pages\OpenGraph;
use Pages\Page;
use Pages\PageCategory;
use Pages\PageFacade;
use Pages\Tag;
use Url\Components\RedirectForm\IRedirectFormFactory;
use Url\DuplicateRouteException;
use Url\RedirectFacade;
use Users\User;

/**
 * @method onSave(PageForm $control, Page $entity)
 * @method onPublish(PageForm $control, Page $entity)
 * @method onComplete(PageForm $control)
 * @method onException(PageForm $control, \Exception $exc)
 */
class PageForm extends AControl
{

	/** @var \Closure[] */
	public $onSave = [];

	/** @var \Closure[] */
	public $onPublish = [];

	/** @var \Closure[] */
	public $onComplete = [];

	/** @var \Closure[] */
	public $onException = [];

	/** @var PageFacade */
	private $pageFacade;

	/** @var EntityManager */
	private $em;

	/** @var Page */
	private $editablePage;
	private $edit = TRUE;

	/** @var RedirectFacade */
	private $redirectFacade;

	public function __construct($editablePage, PageFacade $pageFacade, EntityManager $em, RedirectFacade $redirectFacade)
	{
		if ($editablePage === NULL) { //NEW
			$editablePage = new Page;
			$this->edit = FALSE;
		}
		$this->editablePage = $editablePage;
		$this->pageFacade = $pageFacade;
		$this->em = $em;
		$this->redirectFacade = $redirectFacade;
	}

	public function render(array $parameters = NULL)
	{
		if ($parameters) {
			$this->template->parameters = ArrayHash::from($parameters);
		}
		$this->template->showPublish = $this->editablePage->isPublished() ? FALSE : TRUE;
		$this->template->page = $this->editablePage;
		$this->template->edit = $this->edit;
		$this->template->render($this->templatePath ?: __DIR__ . '/PageForm.latte');
	}

	protected function createComponentRedirectForm(IRedirectFormFactory $factory)
	{
		return $factory->create($this->editablePage->getId());
	}

	protected function createComponentPicturesUploader(IUploaderFactory $factory)
	{
		$control = $factory->create(TRUE);

		$control->onSuccess[] = function ($_, File $file, array $result) {
			$this->editablePage->addFile($file);
			$this->em->flush($this->editablePage);
			$this->presenter->sendResponse(new JsonResponse($result));
		};

		$control->onFailed[] = function ($_, array $result) {
			$this->presenter->sendResponse(new JsonResponse($result));
		};

		return $control;
	}

	protected function createComponentFilesUploader(IUploaderFactory $factory)
	{
		$control = $factory->create();

		$control->onSuccess[] = function ($_, File $file, array $result) {
			$this->editablePage->addFile($file);
			$this->em->flush($this->editablePage);
			$this->presenter->sendResponse(new JsonResponse($result));
		};

		$control->onFailed[] = function ($_, array $result) {
			$this->presenter->sendResponse(new JsonResponse($result));
		};

		return $control;
	}

	protected function createComponentPageForm()
	{
		$form = new UI\Form;
		$form->addProtection();
		$form->addText('title', 'Název:')->setRequired('Je zapotřebí vyplnit název stránky.');
		$form->addText('slug', 'URL stránky:');
		$form->addTinyMCE('editor', NULL)
			->setRequired('Je zapotřebí napsat nějaký text.');

		$authors = $this->em->getRepository(User::class)->findPairs('email');
		$user_id = $this->presenter->user->id;
		$form->addMultiSelect('authors', 'Autor:',
			[NULL => 'Bez autora'] + $authors
		)->setDefaultValue(array_key_exists($user_id, $authors) ? $user_id : NULL);

		$form->addMultiSelect('categories', 'Kategorie:',
			[NULL => 'Bez kategorie'] +
			$this->em->getRepository(PageCategory::class)->findPairs('name')
		);

		// ADVANCED:
		$form->addText('tags', 'Štítky:');
		$form->addText('individual_css', 'Individuální CSS třída nebo ID:');

		$form
			->addCheckbox('protected', 'Zaheslovat stránku:')
			->addCondition($form::EQUAL, TRUE)
			->toggle('protected');
		$form->addPassword('password', 'Heslo:');

		// OPTIMIZATION:
		$form->addText('individualTitle', 'Individuální titulek:');
		$form->addTextArea('description', 'Popis stránky (Description):');
		$form->addSelect('index', 'Indexace stránky:', [
			NULL => 'Výchozí',
			'index' => 'Indexovat (index)',
			'noindex' => 'Neindexovat (noindex)',
		]);
		$form->addSelect('follow', 'Sledování odkazů', [
			NULL => 'Výchozí',
			'follow' => 'Sledovat (follow)',
			'nofollow' => 'Nesledovat (nofollow)',
		]);

		// FCBk:
		$form->addText('fcbk_title', 'Individuální titulek příspěvku na Facebooku:');
		$form->addTextArea('fcbk_description', 'Popis stránky v příspěvku na Facebooku:');

		$this->setDefaults($form);
		$form->addSubmit('saveAndRedirect', 'Uložit')->onClick[] = $this->savePageAndRedirect;
		$form->addSubmit('saveAndStay', 'Uložit a zůstat')->onClick[] = $this->savePageAndStay;
		$form->addSubmit('publish', 'Publikovat')->onClick[] = $this->publishPage;
		$form->addSubmit('preview', 'Zobrazit stránku')->onClick[] = function (SubmitButton $sender) {
			$this->savePage($sender, TRUE);
		};
		return $form;
	}

	public function savePageAndRedirect(SubmitButton $sender)
	{
		$this->savePage($sender);
		$this->presenter->redirect('default');
	}

	public function savePageAndStay(SubmitButton $sender)
	{
		$page = $this->savePage($sender);
		if ($page) {
			$this->presenter->redirect('edit', $page->getId());
		}
	}

	private function savePage(SubmitButton $sender, $preview = FALSE)
	{
		try {
			$entity = $this->editablePage;
			$values = $sender->getForm()->getValues();
			$this->fillEntityWithValues($entity, $values);
			$this->pageFacade->onSave[] = function () use ($entity) {
				$this->onSave($this, $entity);
			};
			$this->pageFacade->save($entity, $values);
		} catch (DuplicateRouteException $exc) {
			$this->presenter->flashMessage($exc->getMessage());
			return NULL;
		} catch (\Exception $exc) {
			$this->onException($this, $exc);
			return NULL;
		}
		if ($preview) {
			$this->presenter->redirect(':Pages:Front:Page:preview', $entity->id);
		}
		return $entity;
	}

	public function publishPage(SubmitButton $sender)
	{
		try {
			$entity = $this->editablePage;
			$values = $sender->getForm()->getValues();
			$this->fillEntityWithValues($entity, $values);
			$this->pageFacade->onPublish[] = function () use ($entity) {
				$this->onPublish($this, $entity);
			};
			$this->pageFacade->publish($entity, $values);
		} catch (DuplicateRouteException $exc) {
			$this->presenter->flashMessage($exc->getMessage());
			return;
		} catch (\Exception $exc) {
			$this->onException($this, $exc);
			return;
		}
		$this->presenter->redirect('default');
	}

	private function fillEntityWithValues(Page $entity, ArrayHash $values)
	{
		$entity->setTitle($values->title);
		$entity->setBody($values->editor);
		$entity->setIndividualTitle($values->individualTitle);
		$entity->setDescription($values->description);
		$entity->setIndex($values->index);
		$entity->setFollow($values->follow);
		$entity->setIndividualCss($values->individual_css);
		$entity->setProtected($values->password, $values->protected);

		$entity->clearAuthors();
		if (!in_array(NULL, $values->authors)) {
			foreach ($values->authors as $authorId) {
				/** @var User $authorRef */
				$authorRef = $this->em->getPartialReference(User::class, $authorId);
				$entity->addAuthor($authorRef);
			}
		}

		$entity->clearCategories();
		if (!in_array(NULL, $values->categories)) {
			foreach ($values->categories as $categoryId) {
				/** @var PageCategory $categoryRef */
				$categoryRef = $this->em->getPartialReference(PageCategory::class, $categoryId);
				$entity->addCategory($categoryRef);
			}
		}

		//Save tags and remove old relations:
		$knownTags = [];
		/** @var Tag $tag */
		foreach ($this->em->getRepository(Tag::class)->findAll() as $tag) {
			$knownTags[$tag->getName()] = $tag;
		}

		$newTags = array_filter(array_unique(preg_split('/\s*,\s*/', $values->tags)));
		foreach ($newTags as $tag) {
			if (array_key_exists($tag, $knownTags)) {
				$entity->addTag($knownTags[$tag]);
			} else {
				$tagEntity = (new Tag)->setName($tag);
				$entity->addTag($tagEntity);
			}
		}
		foreach ($knownTags as $key => $tag) {
			if (!in_array($key, $newTags)) {
				$entity->removeTag($tag);
			}
		}

		//Save OG tags:
		$ogs = $this->editablePage->getOpenGraphs();
		$mapping = ['og:title' => 'fcbk_title', 'og:description' => 'fcbk_description'];
		foreach ($mapping as $key => $value) {
			if (array_key_exists($key, $ogs)) {
				$ogs[$key]->content = $values[$value];
				$entity->setOpenGraph($ogs[$key]);
			} else { //new entry
				$og = new OpenGraph($key, $values[$value]);
				$entity->setOpenGraph($og);
			}
		}
	}

	private function setDefaults(UI\Form $form)
	{
		if ($this->editablePage !== NULL) { //EDITING
			$e = $this->editablePage;
			$form->setDefaults([
				'title' => $e->getTitle(),
				'slug' => $e->getUrl() ? $e->getUrl()->getFakePath() : '',
				'editor' => $e->getBody(),
				'authors' => $e->getAuthorsIds(),
				'categories' => $e->getCategoriesIds(),
				'individualTitle' => $e->getIndividualTitle(),
				'description' => $e->getDescription(),
				'index' => $e->getIndex(),
				'follow' => $e->getFollow(),
				'tags' => $e->getTagsString(),
				'individual_css' => $e->getIndividualCss(),
				'protected' => $e->getProtected(),
				'fcbk_title' => $e->getOpenGraphContent('og:title'),
				'fcbk_description' => $e->getOpenGraphContent('og:description'),
			]);
		}
	}

}

interface IPageFormFactory
{
	/**
	 * @param NULL|Page $editablePage
	 *
	 * @return PageForm
	 */
	public function create($editablePage);
}
