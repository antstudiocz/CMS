<?php

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

class PermissionsFixture extends AbstractFixture implements DependentFixtureInterface
{

	public function load(ObjectManager $manager)
	{
		$p = (new \Users\Permission)->setCreate()->setRead()->setUpdate()->setDelete();
		$p->setResource($this->getReference('res-ad'));
		$p->setRole($this->getReference('admin-role'));
		$manager->persist($p);

		$manager->flush();
	}

	public function getDependencies()
	{
		return [
			\RolesFixture::class,
			\ResourcesFixture::class,
		];
	}

}
