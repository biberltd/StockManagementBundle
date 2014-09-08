<?php
namespace BiberLtd\Bundle\StockManagementBundle\Entity;
use Doctrine\ORM\Mapping AS ORM;

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
class Stock
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer", length=20)
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
    private $date_added;

    /**
     * @ORM\Column(type="datetime", nullable=false)
     */
    private $date_updated;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $date_removed;

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
     * @ORM\ManyToOne(targetEntity="BiberLtd\Bundle\StockManagementBundle\Entity\Supplier")
     * @ORM\JoinColumn(name="supplier", referencedColumnName="id")
     */
    private $supplier;
}