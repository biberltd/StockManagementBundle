<?php
/**
 * @author		Can Berkol
 *
 * @copyright   Biber Ltd. (http://www.biberltd.com) (C) 2015
 * @license     GPLv3
 *
 * @date        27.12.2015
 */
namespace BiberLtd\Bundle\StockManagementBundle\Entity;
use Doctrine\ORM\Mapping AS ORM;
use BiberLtd\Bundle\CoreBundle\CoreEntity;
/** 
 * @ORM\Entity
 * @ORM\Table(
 *     name="supplier",
 *     options={"charset":"utf8","collate":"utf8_turkish_ci","engine":"innodb"},
 *     uniqueConstraints={@ORM\UniqueConstraint(name="idx_u_supplier_id", columns={"id"})}
 * )
 */
class Supplier extends CoreEntity
{
    /** 
     * @ORM\Id
     * @ORM\Column(type="integer", length=10)
     * @ORM\GeneratedValue(strategy="AUTO")
     * @var int
     */
    private $id;

    /** 
     * @ORM\Column(type="string", length=155, nullable=false)
     * @var string
     */
    private $name;

    /** 
     * @ORM\Column(type="string", length=255, nullable=true)
     * @var string
     */
    private $description;

    /** 
     * @ORM\Column(type="string", length=255, nullable=false)
     * @var string
     */
    private $url_key;

	/**
	 * @param string $url_key
	 *
	 * @return $this
	 */
    public function setUrlKey(string $url_key) {
        if($this->setModified('url_key', $url_key)->isModified()) {
            $this->url_key = $url_key;
        }

        return $this;
    }

	/**
	 * @return string
	 */
    public function getUrlKey() {
        return $this->url_key;
    }

	/**
	 * @param string $name
	 *
	 * @return $this
	 */
    public function setName(string $name) {
        if($this->setModified('name', $name)->isModified()) {
            $this->name = $name;
        }

        return $this;
    }

	/**
	 * @return string
	 */
    public function getName() {
        return $this->name;
    }

	/**
	 * @return int
	 */
    public function getId() {
        return $this->id;
    }

	/**
	 * @param string $description
	 *
	 * @return $this
	 */
    public function setDescription(string $description) {
        if($this->setModified('description', $description)->isModified()) {
            $this->description = $description;
        }

        return $this;
    }

	/**
	 * @return string
	 */
    public function getDescription() {
        return $this->description;
    }
}