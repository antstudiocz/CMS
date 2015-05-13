<?php

use Test\PresenterTester;

$container = require __DIR__ . '/../../bootstrap.php';

/**
 * @testCase
 */
class SignPresenter extends Tester\TestCase
{

	private $tester;

	public function __construct(Nette\DI\Container $container)
	{
		$this->tester = new PresenterTester($container, 'Auth:Sign');
	}

	public function testRenderIn()
	{
		$this->tester->testAction('in');
	}

}

(new SignPresenter($container))->run();