<?php

namespace Pages;

use Doctrine\ORM\Mapping as ORM;
use Kdyby\Doctrine\Entities\MagicAccessors;
use Knp\DoctrineBehaviors\Model\Translatable\Translation;
use Localization\ILocaleAware;

/**
 * @ORM\Entity
 * @ORM\Table(
 *      indexes={
 *          @ORM\Index(columns={"title"}, flags={"fulltext"}),
 *          @ORM\Index(columns={"body"}, flags={"fulltext"}),
 *          @ORM\Index(columns={"title", "body"}, flags={"fulltext"})
 *      }
 * )
 *
 * @method setTitle(string $title)
 * @method string getTitle()
 * @method setIndividualTitle(string $title)
 * @method string getIndividualTitle()
 * @method setDescription(string $description)
 * @method string getDescription()
 * @method setBody(string $body)
 * @method string getBody()
 */
class PageTranslation implements ILocaleAware
{

	use MagicAccessors;
	use Translation;

	/**
	 * @ORM\Column(type="text", options={"comment":"Title of the article"})
	 * @var string
	 */
	protected $title;

	/**
	 * @ORM\Column(type="text", nullable=TRUE, options={"comment":"Meta title of the article"})
	 * @var string
	 */
	protected $individualTitle = NULL;

	/**
	 * @ORM\Column(type="text", nullable=TRUE, options={"comment":"Meta description of the article"})
	 * @var string
	 */
	protected $description = NULL;

	/**
	 * @ORM\Column(type="text", options={"comment":"Body of the article"})
	 * @var string
	 */
	protected $body;

}
