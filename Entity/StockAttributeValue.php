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
use BiberLtd\Bundle\CoreBundle\CoreEntity;
use Doctrine\ORM\Mapping AS ORM;

/**
 * @ORM\Entity
 * @ORM\Table(uniqueConstraints={@ORM\UniqueConstraint(name="idxUStockAttributeValueId", columns={"id"})})
 */
class StockAttributeValue extends CoreEntity
{
	/**
	 * @ORM\Id
	 * @ORM\Column(type="integer", length=10, options={"default":"System defined id.","unsigned":true})
	 * @ORM\GeneratedValue(strategy="AUTO")
	 * @var int
	 */
	private $id;

	/**
	 * @ORM\Column(type="string", nullable=false, options={"default":"Value of stock attribute"})
	 * @var string
	 */
	private $value;

	/**
	 * @ORM\Column(type="integer", nullable=false, options={"default":"Custom sort order.","unsigned":true})
	 * @var int
	 */
	private $sort_order;

	/**
	 * @ORM\JoinColumn(name="language", referencedColumnName="id", unique=true, onDelete="CASCADE")
	 * @ORM\OneToOne(targetEntity="BiberLtd\Bundle\MultiLanguageSupportBundle\Entity\Language")
	 * @var \BiberLtd\Bundle\MultiLanguageSupportBundle\Entity\Language
	 */
	private $language;

	/**
	 * 
	 * @ORM\JoinColumn(name="attribute", referencedColumnName="id", unique=true, onDelete="CASCADE")
	 * @ORM\OneToOne(targetEntity="ProductAttribute")
	 * @var \BiberLtd\Bundle\ProductManagementBundle\Entity\ProductAttribute
	 */
	private $attribute;


	/**
	 * @ORM\OneToOne(targetEntity="BiberLtd\Bundle\StockManagementBundle\Entity\Stock")
	 * @ORM\JoinColumn(name="stock", referencedColumnName="id", unique=true, onDelete="CASCADE")
	 * @var \BiberLtd\Bundle\StockManagementBundle\Entity\Stock
	 */
	private $stock;

	/**
	 * @return mixed
	 */
	public function getId(){
		return $this->id;
	}

	/**
	 * @param \BiberLtd\Bundle\ProductManagementBundle\Entity\ProductAttribute $attribute
	 *
	 * @return $this
	 */
	public function setAttribute(\BiberLtd\Bundle\ProductManagementBundle\Entity\ProductAttribute $attribute) {
		if(!$this->setModified('attribute', $attribute)->isModified()) {
			return $this;
		}
		$this->attribute = $attribute;
		return $this;
	}

	/**
	 * @return \BiberLtd\Bundle\ProductManagementBundle\Entity\ProductAttribute
	 */
	public function getAttribute() {
		return $this->attribute;
	}

	/**
	 * @param \BiberLtd\Bundle\MultiLanguageSupportBundle\Entity\Language $language
	 *
	 * @return $this
	 */
	public function setLanguage(\BiberLtd\Bundle\MultiLanguageSupportBundle\Entity\Language $language) {
		if(!$this->setModified('language', $language)->isModified()) {
			return $this;
		}
		$this->language = $language;
		return $this;
	}

	/**
	 * @return \BiberLtd\Bundle\MultiLanguageSupportBundle\Entity\Language
	 */
	public function getLanguage() {
		return $this->language;
	}

	/**
	 * @param \BiberLtd\Bundle\StockManagementBundle\Entity\Stock $stock
	 *
	 * @return $this
	 */
	public function setStock(\BiberLtd\Bundle\StockManagementBundle\Entity\Stock $stock) {
		if(!$this->setModified('stock', $stock)->isModified()) {
			return $this;
		}
		$this->stock = $stock;
		return $this;
	}

	/**
	 * @return \BiberLtd\Bundle\StockManagementBundle\Entity\Stock
	 */
	public function getStock() {
		return $this->stock;
	}

	/**
	 * @param int $sort_order
	 *
	 * @return $this
	 */
	public function setSortOrder(\integer $sort_order) {
		if(!$this->setModified('sort_order', $sort_order)->isModified()) {
			return $this;
		}
		$this->sort_order = $sort_order;
		return $this;
	}

	/**
	 * @return int
	 */
	public function getSortOrder() {
		return $this->sort_order;
	}

	/**
	 * @param string $value
	 *
	 * @return $this
	 */
	public function setValue(\string $value) {
		if(!$this->setModified('value', $value)->isModified()) {
			return $this;
		}
		$this->value = $value;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getValue() {
		return $this->value;
	}
}