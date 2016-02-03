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
 *     name="stock",
 *     options={"charset":"utf8","collate":"utf8_turkish_ci","engine":"innodb"},
 *     indexes={
 *         @ORM\Index(name="idxNStockDateAdded", columns={"date_added"}),
 *         @ORM\Index(name="idxNStockDateUpdated", columns={"date_updated"}),
 *         @ORM\Index(name="idxNStockDateRemoved", columns={"date_removed"})
 *     },
 *     uniqueConstraints={
 *         @ORM\UniqueConstraint(name="idxUStockId", columns={"id"}),
 *         @ORM\UniqueConstraint(name="idxUStockSku", columns={"product","sku"})
 *     }
 * )
 */
class Stock extends CoreEntity
{
    /** 
     * @ORM\Id
     * @ORM\Column(type="integer", length=20)
     * @ORM\GeneratedValue(strategy="AUTO")
     * @var int
     */
    private $id;

    /** 
     * @ORM\Column(type="string", length=155, nullable=false)
     * @var string
     */
    private $sku;

    /** 
     * @ORM\Column(type="string", length=155, nullable=true)
     * @vr string
     */
    private $supplier_sku;

    /** 
     * @ORM\Column(type="integer", length=10, nullable=false, options={"default":0})
     * @var int
     */
    private $quantity;

    /** 
     * @ORM\Column(type="datetime", nullable=false)
     * @var \DateTime
     */
    public $date_added;

    /** 
     * @ORM\Column(type="datetime", nullable=false)
     * @var \DateTime
     */
    public $date_updated;

    /** 
     * @ORM\Column(type="datetime", nullable=true)
     * @var \DateTime
     */
    public $date_removed;

    /** 
     * @ORM\Column(type="decimal", length=8, nullable=true, options={"default":0})
     * @var float
     */
    private $price;

    /** 
     * @ORM\Column(type="decimal", length=8, nullable=true, options={"default":0})
     * @var float
     */
    private $discount_price;

    /** 
     * @ORM\ManyToOne(targetEntity="Product")
     * @ORM\JoinColumn(name="product", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     * @var \BiberLtd\Bundle\ProductManagementBundle\Entity\Product
     */
    private $product;

    /**
     * @ORM\Column(type="integer", nullable=false, options={"default":0,"unsigned":true})
     * @var int
     */
    private $sort_order;
    /** 
     * @ORM\ManyToOne(targetEntity="BiberLtd\Bundle\StockManagementBundle\Entity\Supplier")
     * @ORM\JoinColumn(name="supplier", referencedColumnName="id")
     * @var \BiberLtd\Bundle\StockManagementBundle\Entity\Supplier
     */
    private $supplier;

    /**
     * @param float $discount_price
     *
     * @return $this
     */
    public function setDiscountPrice(float $discount_price) {
        if($this->setModified('discount_price', $discount_price)->isModified()) {
            $this->discount_price = $discount_price;
        }

        return $this;
    }

    /**
     * @return float
     */
    public function getDiscountPrice() {
        return $this->discount_price;
    }

    /**
     * @return int
     */
    public function getId() {
        return $this->id;
    }

    /**
     * @param float $price
     *
     * @return $this
     */
    public function setPrice(float $price) {
        if($this->setModified('price', $price)->isModified()) {
            $this->price = $price;
        }

        return $this;
    }

    /**
     * @return float
     */
    public function getPrice() {
        return $this->price;
    }

    /**
     * @param \BiberLtd\Bundle\ProductManagementBundle\Entity\Product $product
     *
     * @return $this
     */
    public function setProduct(\BiberLtd\Bundle\ProductManagementBundle\Entity\Product $product) {
        if($this->setModified('product', $product)->isModified()) {
            $this->product = $product;
        }

        return $this;
    }

    /**
     * @return \BiberLtd\Bundle\ProductManagementBundle\Entity\Product
     */
    public function getProduct() {
        return $this->product;
    }

    /**
     * @param int $quantity
     *
     * @return $this
     */
    public function setQuantity(int $quantity) {
        if($this->setModified('quantity', $quantity)->isModified()) {
            $this->quantity = $quantity;
        }

        return $this;
    }

    /**
     * @return int
     */
    public function getQuantity() {
        return $this->quantity;
    }

    /**
     * @param string $sku
     *
     * @return $this
     */
    public function setSku(string $sku) {
        if($this->setModified('sku', $sku)->isModified()) {
            $this->sku = $sku;
        }

        return $this;
    }

    /**
     * @return string
     */
    public function getSku() {
        return $this->sku;
    }

    /**
     * @return int
     */
    public function getSortOrder()
    {
        return $this->sort_order;
    }

    /**
     * @param int $sort_order
     *
     * @return $this
     */
    public function setSortOrder(int $sort_order)
    {
        if (!$this->setModified('sort_order', $sort_order)->isModified()) {
            return $this;
        }
        $this->sort_order = $sort_order;
        return $this;
    }

    /**
     * @param \BiberLtd\Bundle\StockManagementBundle\Entity\Supplier $supplier
     *
     * @return $this
     */
    public function setSupplier(\BiberLtd\Bundle\StockManagementBundle\Entity\Supplier $supplier) {
        if($this->setModified('supplier', $supplier)->isModified()) {
            $this->supplier = $supplier;
        }

        return $this;
    }

    /**
     * @return \BiberLtd\Bundle\StockManagementBundle\Entity\Supplier
     */
    public function getSupplier() {
        return $this->supplier;
    }

    /**
     * @param string $supplier_sku
     *
     * @return $this
     */
    public function setSupplierSku(string $supplier_sku) {
        if($this->setModified('supplier_sku', $supplier_sku)->isModified()) {
            $this->supplier_sku = $supplier_sku;
        }

        return $this;
    }

    /**
     * @return mixed
     */
    public function getSupplierSku() {
        return $this->supplier_sku;
    }
}