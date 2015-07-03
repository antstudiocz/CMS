<?php

require __DIR__ . '/../bootstrap.php';

/**
 * @skip Eshop isn't ready yet...
 * @testCase
 */
class EshopPresenter extends \PresenterTestCase
{

	public function __construct()
	{
		$this->openPresenter('Admin:Eshop:');
	}

	public function setUp()
	{
		$this->logIn(1, 'superadmin'); //TODO: lépe (?)
	}

	public function testRenderDefault()
	{
		$this->checkAction('default');
	}

	public function testRenderDefaultLoggedOut()
	{
		$this->logOut();
		$this->checkRedirect('default', '/auth');
	}

}

(new EshopPresenter())->run();
