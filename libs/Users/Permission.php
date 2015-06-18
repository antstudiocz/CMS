<?php

namespace Users;

use Doctrine\ORM\Mapping as ORM;
use Kdyby\Doctrine\Entities\Attributes\Identifier;
use Kdyby\Doctrine\Entities\BaseEntity;
use Nette\InvalidArgumentException;

/**
 * @ORM\Entity
 *
 * @method boolean getCreate()
 * @method boolean getRead()
 * @method boolean getUpdate()
 * @method boolean getDelete()
 */
class Permission extends BaseEntity
{

	use Identifier;

	/**
	 * @ORM\Column(type="boolean", nullable=FALSE, options={"default":"1", "comment":"Permission can be allow or deny rule"})
	 * @var boolean
	 */
	protected $allow = TRUE;

	/**
	 * @ORM\Column(type="boolean", name="`create`", nullable=FALSE, options={"default":"0"})
	 * @var boolean
	 */
	protected $create = FALSE;

	/**
	 * @ORM\Column(type="boolean", name="`read`", nullable=FALSE, options={"default":"0"})
	 * @var boolean
	 */
	protected $read = FALSE;

	/**
	 * @ORM\Column(type="boolean", name="`update`", nullable=FALSE, options={"default":"0"})
	 * @var boolean
	 */
	protected $update = FALSE;

	/**
	 * @ORM\Column(type="boolean", name="`delete`", nullable=FALSE, options={"default":"0"})
	 * @var boolean
	 */
	protected $delete = FALSE;

	/**
	 * @ORM\ManyToOne(targetEntity="Resource", cascade={"persist"})
	 * @ORM\JoinColumn(name="resource_id", referencedColumnName="id")
	 * @var Resource
	 */
	protected $resource;

	/**
	 * @ORM\ManyToOne(targetEntity="Role", cascade={"persist"})
	 * @ORM\JoinColumn(name="role_id", referencedColumnName="id")
	 * @var Role
	 */
	protected $role;

	public function __construct($allow = TRUE)
	{
		if (!is_bool($allow)) {
			throw new InvalidArgumentException(sprintf('Allow flag should be boolean, %s given.', gettype($default)));
		}
		$this->allow = $allow;
	}

	public function setCreate($default = TRUE)
	{
		if (!is_bool($default)) {
			throw new InvalidArgumentException(sprintf('Create flag should be boolean, %s given.', gettype($default)));
		}
		$this->create = $default;
		return $this;
	}

	public function setRead($default = TRUE)
	{
		if (!is_bool($default)) {
			throw new InvalidArgumentException(sprintf('Read flag should be boolean, %s given.', gettype($default)));
		}
		$this->read = $default;
		return $this;
	}

	public function setUpdate($default = TRUE)
	{
		if (!is_bool($default)) {
			throw new InvalidArgumentException(sprintf('Update flag should be boolean, %s given.', gettype($default)));
		}
		$this->update = $default;
		return $this;
	}

	public function setDelete($default = TRUE)
	{
		if (!is_bool($default)) {
			throw new InvalidArgumentException(sprintf('Delete flag should be boolean, %s given.', gettype($default)));
		}
		$this->delete = $default;
		return $this;
	}

}
