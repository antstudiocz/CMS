<?php

use Doctrine\Common\Persistence\ObjectManager;

class ResourcesFixture extends \Doctrine\Common\DataFixtures\AbstractFixture
{

	public function load(ObjectManager $manager)
	{
		$manager->persist($resource = (new \Users\Resource())->setName('Admin:Dashboard'));
		$manager->persist((new \Users\Resource())->setName('Admin:Eshop'));
		$manager->persist((new \Users\Resource())->setName('Admin:Options'));
		$manager->persist((new \Users\Resource())->setName('Admin:Page'));

		$this->addReference('res-ad', $resource);
		$manager->flush();
	}

}
