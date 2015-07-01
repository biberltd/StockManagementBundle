<?php
namespace BiberLtd\Bundle\StockManagementBundle\Entity;
use Doctrine\ORM\Mapping AS ORM;
use BiberLtd\Bundle\CoreBundle\CoreEntity;
/** 
 * @ORM\Entity
 * @ORM\Table(
 *     name="stock", 
 *     options={"charset":"utf8","collate":"utf8_turkish_ci","engine":"innodb"}, 
 *     indexes={
 *         @ORM\Index(name="idx_n_stock_date_added", columns={"date_added"}),
 *         @ORM\Index(name="idx_n_stock_date_updated", columns={"date_updated"}),
 *         @ORM\Index(name="idx_n_stock_date_removed", columns={"date_removed"})
 *     }, 
 *     uniqueConstraints={
 *         @ORM\UniqueConstraint(name="idx_u_stock_id", columns={"id"}),
 *         @ORM\UniqueConstraint(name="idx_u_stock_product_sku", columns={"product","sku"})
 *     }
 * )
 */
class Stock extends CoreEntity
{
    /** 
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /** 
     * @ORM\Column(type="string", length=155, nullable=false)
     */
    private $sku;

    /** 
     * @ORM\Column(type="string", length=155, nullable=true)
     */
    private $supplier_sku;

    /** 
     * @ORM\Column(type="integer", length=10, nullable=false)
     */
    private $quantity;

    /** 
     * @ORM\Column(type="datetime", nullable=false)
     */
    public $date_added;

    /** 
     * @ORM\Column(type="datetime", nullable=false)
     */
    public $date_updated;

    /** 
     * @ORM\Column(type="datetime", nullable=true)
     */
    public $date_removed;

    /** 
     * @ORM\Column(type="decimal", length=8, nullable=true)
     */
    private $price;

    /** 
     * @ORM\Column(type="decimal", length=8, nullable=true)
     */
    private $discount_price;

    /** 
     * @ORM\ManyToOne(targetEntity="BiberLtd\Bundle\ProductManagementBundle\Entity\Product")
     * @ORM\JoinColumn(name="product", referencedColumnName="id", onDelete="CASCADE")
     */
    private $product;

    /**
     * @ORM\Column(type="integer", length=10, nullable=false)
     */
    private $sort_order;
    /** 
     * @ORM\ManyToOne(targetEntity="BiberLtd\Bundle\StockManagementBundle\Entity\Supplier")
     * @ORM\JoinColumn(name="supplier", referencedColumnName="id")
     */
    private $supplier;

    /**
     * @name                  setDiscountPrice ()
     *                                         Sets the discount_price property.
     *                                         Updates the data only if stored value and value to be set are different.
     *
     * @author          Can Berkol
     *
     * @since           1.0.0
     * @version         1.0.0
     *
     * @use             $this->setModified()
     *
     * @param           mixed $discount_price
     *
     * @return          object                $this
     */
    public function setDiscountPrice($discount_price) {
        if($this->setModified('discount_price', $discount_price)->isModified()) {
            $this->discount_price = $discount_price;
        }

        return $this;
    }

    /**
     * @name            getDiscountPrice ()
     *                                   Returns the value of discount_price property.
     *
     * @author          Can Berkol
     *
     * @since           1.0.0
     * @version         1.0.0
     *
     * @return          mixed           $this->discount_price
     */
    public function getDiscountPrice() {
        return $this->discount_price;
    }

    /**
     * @name            get İd()
     *                      Returns the value of id property.
     *
     * @author          Can Berkol
     *
     * @since           1.0.0
     * @version         1.0.0
     *
     * @return          mixed           $this->id
     */
    public function getId() {
        return $this->id;
    }

    /**
     * @name                  setPrice ()
     *                                 Sets the price property.
     *                                 Updates the data only if stored value and value to be set are different.
     *
     * @author          Can Berkol
     *
     * @since           1.0.0
     * @version         1.0.0
     *
     * @use             $this->setModified()
     *
     * @param           mixed $price
     *
     * @return          object                $this
     */
    public function setPrice($price) {
        if($this->setModified('price', $price)->isModified()) {
            $this->price = $price;
        }

        return $this;
    }

    /**
     * @name            getPrice ()
     *                           Returns the value of price property.
     *
     * @author          Can Berkol
     *
     * @since           1.0.0
     * @version         1.0.0
     *
     * @return          mixed           $this->price
     */
    public function getPrice() {
        return $this->price;
    }

    /**
     * @name                  setProduct ()
     *                                   Sets the product property.
     *                                   Updates the data only if stored value and value to be set are different.
     *
     * @author          Can Berkol
     *
     * @since           1.0.0
     * @version         1.0.0
     *
     * @use             $this->setModified()
     *
     * @param           mixed $product
     *
     * @return          object                $this
     */
    public function setProduct($product) {
        if($this->setModified('product', $product)->isModified()) {
            $this->product = $product;
        }

        return $this;
    }

    /**
     * @name            getProduct ()
     *                             Returns the value of product property.
     *
     * @author          Can Berkol
     *
     * @since           1.0.0
     * @version         1.0.0
     *
     * @return          mixed           $this->product
     */
    public function getProduct() {
        return $this->product;
    }

    /**
     * @name            setQuantity ()
     *                  Sets the quantity property.
     *                  Updates the data only if stored value and value to be set are different.
     *
     * @author          Can Berkol
     *
     * @since           1.0.0
     * @version         1.0.0
     *
     * @use             $this->setModified()
     *
     * @param           mixed $quantity
     *
     * @return          object                $this
     */
    public function setQuantity($quantity) {
        if($this->setModified('quantity', $quantity)->isModified()) {
            $this->quantity = $quantity;
        }

        return $this;
    }

    /**
     * @name            getQuantity ()
     *                  Returns the value of quantity property.
     *
     * @author          Can Berkol
     *
     * @since           1.0.0
     * @version         1.0.0
     *
     * @return          mixed           $this->quantity
     */
    public function getQuantity() {
        return $this->quantity;
    }

    /**
     * @name                  setSku ()
     *                               Sets the sku property.
     *                               Updates the data only if stored value and value to be set are different.
     *
     * @author          Can Berkol
     *
     * @since           1.0.0
     * @version         1.0.0
     *
     * @use             $this->setModified()
     *
     * @param           mixed $sku
     *
     * @return          object                $this
     */
    public function setSku($sku) {
        if($this->setModified('sku', $sku)->isModified()) {
            $this->sku = $sku;
        }

        return $this;
    }

    /**
     * @name            getSku ()
     *                  Returns the value of sku property.
     *
     * @author          Can Berkol
     *
     * @since           1.0.0
     * @version         1.0.0
     *
     * @return          mixed           $this->sku
     */
    public function getSku() {
        return $this->sku;
    }

    /**
     * @name        getSortOrder ()
     *
     * @author      Said İmamoğlu
     *
     * @since       1.0.0
     * @version     1.0.0
     *
     * @return      mixed
     */
    public function getSortOrder()
    {
        return $this->sort_order;
    }

    /**
     * @name        setSortOrder ()
     *
     * @author      Said İmamoğlu
     *
     * @since       1.0.0
     * @version     1.0.0
     *
     * @param       mixed $sort_order
     *
     * @return      $this
     */
    public function setSortOrder($sort_order)
    {
        if (!$this->setModifiled('sort_order', $sort_order)->isModified()) {
            return $this;
        }
        $this->sort_order = $sort_order;
        return $this;
    }

    /**
     * @name            setSupplier ()
     *                  Sets the supplier property.
     *                  Updates the data only if stored value and value to be set are different.
     *
     * @author          Can Berkol
     *
     * @since           1.0.0
     * @version         1.0.0
     *
     * @use             $this->setModified()
     *
     * @param           mixed $supplier
     *
     * @return          object                $this
     */
    public function setSupplier($supplier) {
        if($this->setModified('supplier', $supplier)->isModified()) {
            $this->supplier = $supplier;
        }

        return $this;
    }

    /**
     * @name            getSupplier ()
     *                  Returns the value of supplier property.
     *
     * @author          Can Berkol
     *
     * @since           1.0.0
     * @version         1.0.0
     *
     * @return          mixed           $this->supplier
     */
    public function getSupplier() {
        return $this->supplier;
    }

    /**
     * @name                  setSupplierSku ()
     *                        Sets the supplier_sku property.
     *                        Updates the data only if stored value and value to be set are different.
     *
     * @author          Can Berkol
     *
     * @since           1.0.0
     * @version         1.0.0
     *
     * @use             $this->setModified()
     *
     * @param           mixed $supplier_sku
     *
     * @return          object                $this
     */
    public function setSupplierSku($supplier_sku) {
        if($this->setModified('supplier_sku', $supplier_sku)->isModified()) {
            $this->supplier_sku = $supplier_sku;
        }

        return $this;
    }

    /**
     * @name            getSupplierSku ()
     *                                 Returns the value of supplier_sku property.
     *
     * @author          Can Berkol
     *
     * @since           1.0.0
     * @version         1.0.0
     *
     * @return          mixed           $this->supplier_sku
     */
    public function getSupplierSku() {
        return $this->supplier_sku;
    }


}