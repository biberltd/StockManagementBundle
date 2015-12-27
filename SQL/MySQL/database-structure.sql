/**
 * @author		Can Berkol
 * @author		Said İmamoğlu
 *
 * @copyright   Biber Ltd. (http://www.biberltd.com) (C) 2015
 * @license     GPLv3
 *
 * @date        27.12.2015
 */
SET FOREIGN_KEY_CHECKS=0;

-- ----------------------------
-- Table structure for stock
-- ----------------------------
DROP TABLE IF EXISTS `stock`;
CREATE TABLE `stock` (
  `id` int(20) unsigned NOT NULL AUTO_INCREMENT COMMENT 'System given idç',
  `product` int(15) unsigned DEFAULT NULL COMMENT 'Product that stock belongs to.',
  `sku` varchar(155) COLLATE utf8_turkish_ci NOT NULL COMMENT 'SKU.',
  `supplier_sku` varchar(255) COLLATE utf8_turkish_ci DEFAULT NULL COMMENT 'Supplier sku.',
  `quantity` int(10) unsigned NOT NULL COMMENT 'Quantity at hand.',
  `date_added` datetime NOT NULL COMMENT 'Date added.',
  `date_updated` datetime NOT NULL COMMENT 'Date updated.',
  `date_removed` datetime DEFAULT NULL COMMENT 'Date removed.',
  `supplier` int(10) unsigned DEFAULT NULL,
  `price` decimal(8,2) unsigned DEFAULT NULL COMMENT 'Most up to date price.',
  `discount_price` decimal(8,2) unsigned DEFAULT '0.00' COMMENT 'Most uptodate discounted price.',
  `sort_order` int(10) unsigned NOT NULL DEFAULT '1' COMMENT 'Stock custom (view) sort order.',
  PRIMARY KEY (`id`),
  UNIQUE KEY `idxUStockId` (`id`) USING BTREE,
  KEY `idxNStockDateAdded` (`date_added`) USING BTREE,
  KEY `idxNStockDateUpdated` (`date_updated`) USING BTREE,
  KEY `idxNStockDateRemoved` (`date_removed`) USING BTREE,
  KEY `idxFProductOfStock` (`product`) USING BTREE,
  KEY `idxFSupplierOfStock` (`supplier`) USING BTREE,
  CONSTRAINT `idxFProductOfStock` FOREIGN KEY (`product`) REFERENCES `product` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `idxFSupplierOfStock` FOREIGN KEY (`supplier`) REFERENCES `supplier` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_turkish_ci ROW_FORMAT=COMPACT;

-- ----------------------------
-- Table structure for stock_attribute_value
-- ----------------------------
DROP TABLE IF EXISTS `stock_attribute_value`;
CREATE TABLE `stock_attribute_value` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'System given id.',
  `value` text COLLATE utf8_turkish_ci NOT NULL COMMENT 'Value of attribute.',
  `language` int(10) unsigned NOT NULL COMMENT 'Language of the attribute value.',
  `attribute` int(10) unsigned NOT NULL COMMENT 'Attribute that value belongs to.',
  `stock` int(15) unsigned NOT NULL COMMENT 'Stock that attribute belongs to.',
  `sort_order` int(10) unsigned NOT NULL DEFAULT '1' COMMENT 'Custom sort order.',
  PRIMARY KEY (`id`),
  UNIQUE KEY `idxUStockAttributeValueId` (`id`) USING BTREE,
  UNIQUE KEY `idxUStockAttributeValue` (`language`,`attribute`,`stock`) USING BTREE,
  KEY `idxFStockAttributeValueLanguage` (`language`) USING BTREE,
  KEY `idxFStockAttributeValueAttribute` (`attribute`) USING BTREE,
  KEY `idxFStockAttributeValueAttributeStock` (`stock`) USING BTREE,
  CONSTRAINT `idxFStockAttributeValueAttribute` FOREIGN KEY (`attribute`) REFERENCES `product_attribute` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `idxFStockAttributeValueAttributeStock` FOREIGN KEY (`stock`) REFERENCES `stock` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `idxFStockAttributeValueLanguage` FOREIGN KEY (`language`) REFERENCES `language` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_turkish_ci ROW_FORMAT=COMPACT;

-- ----------------------------
-- Table structure for supplier
-- ----------------------------
DROP TABLE IF EXISTS `supplier`;
CREATE TABLE `supplier` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'System given id.',
  `name` varchar(155) COLLATE utf8_turkish_ci NOT NULL COMMENT 'Name of supplier.',
  `description` varchar(255) COLLATE utf8_turkish_ci DEFAULT NULL COMMENT 'Description of supplier.',
  `url_key` varchar(255) COLLATE utf8_turkish_ci NOT NULL COMMENT 'Url key of supplier.',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8 COLLATE=utf8_turkish_ci ROW_FORMAT=COMPACT;
