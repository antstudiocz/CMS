<?php

namespace Pages\Query;

use Doctrine\ORM\NativeQuery;
use Kdyby;
use Nette\InvalidArgumentException;
use Users\User;

class PagesQuery extends Kdyby\Doctrine\QueryObject
{

	/**
	 * @var array|\Closure[]
	 */
	private $filter = [];

	/**
	 * @var array|\Closure[]
	 */
	private $select = [];

	public function byAuthor(User $user)
	{
		$this->filter[] = function (Kdyby\Doctrine\QueryBuilder $qb) use ($user) {
			$qb->andWhere('authors.id = :user')->setParameter('user', $user->getId());
		};
		return $this;
	}

	public function byId($id)
	{
		if (!is_numeric($id)) {
			throw new InvalidArgumentException;
		}
		$this->filter[] = function (Kdyby\Doctrine\QueryBuilder $qb) use ($id) {
			$qb->andWhere('page.id = :id')->setParameter('id', $id);
		};
		return $this;
	}

	public function withAllAuthors()
	{
		$this->select[] = function (Kdyby\Doctrine\QueryBuilder $qb) {
			// leftJoin, because author is optional (innerJoin otherwise)
			$qb->leftJoin('page.authors', 'authors')->addSelect('authors');
		};
		return $this;
	}

	public function withAllCategories()
	{
		$this->select[] = function (Kdyby\Doctrine\QueryBuilder $qb) {
			// leftJoin, because category is optional (innerJoin otherwise)
			$qb->leftJoin('page.categories', 'categories')->addSelect('categories');
		};
		return $this;
	}

	/**
	 * @param \Kdyby\Persistence\Queryable $repository
	 *
	 * @return \Doctrine\ORM\Query|\Doctrine\ORM\QueryBuilder
	 */
	protected function doCreateQuery(Kdyby\Persistence\Queryable $repository)
	{
		$qb = $this->createBasicDql($repository);
		foreach ($this->select as $modifier) {
			$modifier($qb);
		}
		$qb->addOrderBy('page.createdAt', 'DESC');
		return $qb;
	}

	/**
	 * @param Kdyby\Persistence\Queryable $repository
	 *
	 * @return NativeQuery
	 */
	protected function doCreateCountQuery(Kdyby\Persistence\Queryable $repository)
	{
		$qb = $this->createBasicDql($repository)
			->select('COUNT(page.id) AS total_count');
		return $qb;
	}

	/**
	 * @param Kdyby\Persistence\Queryable|Kdyby\Doctrine\EntityDao $repository
	 *
	 * @return Kdyby\Doctrine\NativeQueryBuilder
	 */
	private function createBasicDql(Kdyby\Persistence\Queryable $repository)
	{
		$qb = $repository->createQueryBuilder('page', 'page.id');
		$qb->andWhere('page.deleted = FALSE');
		foreach ($this->filter as $modifier) {
			$modifier($qb);
		}
		return $qb;
	}

	/**
	 * @param Kdyby\Persistence\Queryable $repository
	 * @param \Iterator $iterator
	 *
	 * @see https://github.com/Kdyby/Doctrine/blob/master/docs/en/optimizing-query-objects.md
	 */
	public function postFetch(Kdyby\Persistence\Queryable $repository, \Iterator $iterator)
	{
//		$ids = array_keys(iterator_to_array($iterator, TRUE));
	}

}
