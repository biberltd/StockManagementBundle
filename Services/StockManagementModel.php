<?php
/**
 * StockManagementModel Class
 *
 * This class acts as a database proxy model for MemberManagementBundle functionalities.
 *
 * @vendor      BiberLtd
 * @package     Core\Bundles\StockManagementModel
 * @subpackage  Services
 * @name        StockManagementModel
 *
 * @author      Can Berkol
 * @author      Said İmamoğlu
 *
 * @copyright   Biber Ltd. (www.biberltd.com)
 *
 * @version     1.0.5
 * @date        17.11.2014
 *
 * @use         Biberltd\Core\Services
 * @use         Biberltd\Core\CoreModel
 * @use         Biberltd\Core\Services\Encryption
 * @use         BiberLtd\Core\Bundles\StockManagementModel\Entity
 * @use         BiberLtd\Core\Bundles\StockManagementModel\Services
 *
 */

namespace BiberLtd\Core\Bundles\StockManagementBundle\Services;

/** Extends CoreModel */
use BiberLtd\Core\CoreModel;
/** Entities to be used */
use BiberLtd\Core\Bundles\StockManagementBundle\Entity as BundleEntity;
use BiberLtd\Core\Bundles\ProductManagementBundle\Entity as ProductEntity;
/** Core Service */
use BiberLtd\Core\Services as CoreServices;
use BiberLtd\Core\Exceptions as CoreExceptions;

class StockManagementModel extends CoreModel{
    public $entity = array(
        'stock' => array('name' => 'StockManagementBundle:Stock', 'alias' => 'st'),
        'stock_attribute_value' => array('name' => 'StockManagementBundle:StockAttributeValue', 'alias' => 'stav'),
        'supplier' => array('name' => 'StockManagementBundle:Supplier', 'alias' => 'su'),
    );

    /**
     * @name            deleteAllAttributeValuesOfStockAttribute ()
     *                  Deletes all attribute values of a product by attribute.
     *
     * @since           1.0.5
     * @version         1.0.5
     * @author          Can Berkol
     *
     * @use             $this->resetResponse();
     * @use             $this->validateAndGetStock()
     * @use             $this->validateAndGetProductAttribute()
     *
     * @param           mixed           $attribute
     * @param           mixed           $stock
     *
     * @return          array           $response
     */
    public function deleteAllAttributeValuesOfStockAttribute($attribute, $stock){
        $this->resetResponse();
        /** Parameter must be an array */
        $attribute = $this->validateAndGetProductAttribute($attribute);
        $product = $this->validateAndGetStockt($stock);
        $qStr = 'DELETE FROM ' . $this->entity['stock_attribute_value']['name'] . ' ' . $this->entity['stock_attribute_value']['alias']
            . ' WHERE ' . $this->entity['stock_attribute_value']['alias'] . '.attribute = ' . $attribute->getId()
            . ' AND ' . $this->entity['stock_attribute_value']['alias'] . '.stock = ' . $stock->getId();
        $query = $this->em->createQuery($qStr);
        $query->getResult();
        $this->response = array(
            'rowCount' => 0,
            'result' => array(
                'set' => null,
                'total_rows' => 0,
                'last_insert_id' => null,
            ),
            'error' => false,
            'code' => 'msg.success.db.delete',
        );
        return $this->response;
    }
    /**
     * @name        deleteStock ()
     * Deletes an existing item from database.
     *
     * @since            1.0.0
     * @version         1.0.0
     * @author          Said İmamoğlu
     *
     * @use             $this->deleteStocks()
     *
     * @param           mixed $item Entity, id or url key of item
     * @param           string $by
     *
     * @return          mixed           $response
     */
    public function deleteStock($item, $by = 'entity')
    {
        return $this->deleteStocks(array($item), $by);
    }

    /**
     * @name            deleteStocks ()
     * Deletes provided items from database.
     *
     * @since        1.0.0
     * @version         1.0.0
     * @author          Said İmamoğlu
     *
     * @use             $this->createException()
     *
     * @param           array $collection Collection of Stock entities, ids, or codes or url keys
     *
     * @return          array           $response
     */
    public function deleteStocks($collection)
    {
        $this->resetResponse();
        /** Parameter must be an array */
        if (!is_array($collection)) {
            return $this->createException('InvalidParameterValue', 'Array', 'err.invalid.parameter.collection');
        }
        $countDeleted = 0;
        foreach ($collection as $entry) {
            if ($entry instanceof BundleEntity\Stock) {
                $this->em->remove($entry);
                $countDeleted++;
            } else {
                switch ($entry) {
                    case is_numeric($entry):
                        $response = $this->getStock($entry, 'id');
                        break;
                    case is_string($entry):
                        $response = $this->getProductCategory($entry, 'url_key');
                        break;
                }
                if ($response['error']) {
                    $this->createException('EntryDoesNotExist', $entry, 'err.invalid.entry');
                }
                $entry = $response['result']['set'];
                $this->em->remove($entry);
                $countDeleted++;
            }
        }

        if ($countDeleted < 0) {
            $this->response['error'] = true;
            $this->response['code'] = 'err.db.fail.delete';

            return $this->response;
        }
        $this->em->flush();
        $this->response = array(
            'rowCount' => 0,
            'result' => array(
                'set' => null,
                'total_rows' => $countDeleted,
                'last_insert_id' => null,
            ),
            'error' => false,
            'code' => 'scc.db.deleted',
        );
        return $this->response;
    }

    /**
     * @name            listStocks ()
     * Lists stock data from database with given params.
     *
     * @author          Said İmamoğlu
     * @version         1.0.0
     * @since           1.0.0
     *
     * @param           array $filter
     * @param           array $sortOrder
     * @param           array $limit
     * @param           string $queryStr
     *
     * @use             $this->createException()
     * @use             $this->prepareWhere()
     * @use             $this->addLimit()
     *
     * @return          array $this->response
     */
    public function listStocks($filter = null, $sortOrder = null, $limit = null, $queryStr = null)
    {
        $this->resetResponse();
        if (!is_array($sortOrder) && !is_null($sortOrder)) {
            return $this->createException('InvalidSortOrder', '', 'err.invalid.parameter.sortorder');
        }

        $order_str = '';
        $where_str = '';
        $group_str = '';
        $filter_str = '';

        /**
         * Start creating the query.
         *
         * Note that if no custom select query is provided we will use the below query as a start.
         */
        if (is_null($queryStr)) {
            $queryStr = 'SELECT ' . $this->entity['stock']['alias']
                . ' FROM ' . $this->entity['stock']['name'] . ' ' . $this->entity['stock']['alias'];
        }
        /**
         * Prepare ORDER BY section of query.
         */
        if ($sortOrder != null) {
            foreach ($sortOrder as $column => $direction) {
                $order_str .= ' ' . $this->entity['stock']['alias'] . '.' . $column . ' ' . strtoupper($direction) . ', ';
            }
            $order_str = rtrim($order_str, ', ');
            $order_str = ' ORDER BY ' . $order_str . ' ';
        }

        /**
         * Prepare WHERE section of query.
         */
        if ($filter != null) {
            $filter_str = $this->prepareWhere($filter);
            $where_str .= ' WHERE ' . $filter_str;
        }
        $queryStr .= $where_str . $group_str . $order_str;

        $query = $this->em->createQuery($queryStr);

        $query = $this->addLimit($query, $limit);

        /**
         * Prepare & Return Response
         */
        $result = $query->getResult();
        $stocks = array();
        $unique = array();
        foreach ($result as $entry) {
            $id = $entry->getId();
            if (!isset($unique[$id])) {
                $stocks[$id] = $entry;
                $unique[$id] = $entry->getId();
            }
        }

        $total_rows = count($stocks);

        if ($total_rows < 1) {
            $this->response['code'] = 'err.db.entry.notexist';
            return $this->response;
        }
        $newCollection = array();
        foreach ($stocks as $stock) {
            $newCollection[] = $stock;
        }
        unset($stocks, $unique);

        $this->response = array(
            'rowCount' => $this->response['rowCount'],
            'result' => array(
                'set' => $newCollection,
                'total_rows' => $total_rows,
                'last_insert_id' => null,
            ),
            'error' => false,
            'code' => 'scc.db.entry.exist',
        );
        return $this->response;
    }
    /**
     * @name            listStocksOfProduct()
     *                  Lists stock data from database with given params.
     *
     * @author          Can Berkol
     * @version         1.0.3
     * @since           1.0.3
     *
     * @param           mixed       $product
     * @param           array       $sortOrder
     * @param           array       $limit
     *
     * @return          array       $this->response
     */
    public function listStocksOfProduct($product, $sortOrder = null, $limit = null){
        if($product instanceof ProductEntity\Product){
            $product = $product->getId();
        }
        else if(!is_numeric($product)){
            $pModel = $this->kernel->getContainer()->get('productmanagement.model');
            $response = $pModel->getProduct($product, 'sku');
            if($response['error']){
                $this->response = array(
                    'rowCount' => $this->response['rowCount'],
                    'result' => array(
                        'set' => null,
                        'total_rows' => 1,
                        'last_insert_id' => null,
                    ),
                    'error' => true,
                    'code' => 'msg.error.db.entry.notexist',
                );
                return $this->response;
            }
            $product = $response['result']['set'];
            $product = $product->getId();
        }
        $column = $this->entity['stock']['alias'].'.product';
        $condition = array('column' => $column, 'comparison' => 'eq', 'value' => $product);
        $filter[] = array(
            'glue' => 'and',
            'condition' => array(
                array(
                    'glue' => 'and',
                    'condition' => $condition,
                )
            )
        );
        return $this->listStocks($filter, $sortOrder, $limit);
    }
    /**
     * @name            listStocksOfProduct()
     *                  Lists stock data from database with given params.
     *
     * @author          Can Berkol
     * @version         1.0.3
     * @since           1.0.3
     *
     * @param           mixed       $product
     * @param           mixed       $supplier
     * @param           array       $sortOrder
     * @param           array       $limit
     *
     * @return          array       $this->response
     */
    public function listStocksOfProductFromSupplier($product, $supplier, $sortOrder = null, $limit = null){
        $column = $this->entity['stock']['alias'] . '.product';
        $condition = array('column' => $column, 'comparison' => 'eq', 'value' => $product);
        $filter[] = array(
            'glue' => 'and',
            'condition' => array(
                array(
                    'glue' => 'and',
                    'condition' => $condition,
                )
            )
        );
        $column = $this->entity['stock']['alias'] . '.supplier';
        $condition = array('column' => $column, 'comparison' => 'eq', 'value' => $supplier);
        $filter[] = array(
            'glue' => 'and',
            'condition' => array(
                array(
                    'glue' => 'and',
                    'condition' => $condition,
                )
            )
        );
        return $this->listStocks($filter, $sortOrder, $limit);
    }
    /**
     * @name            getAttributeValueOfStock()
     *                  Returns a product's requested attribute's value in given locale.
     *
     * @since           1.0.5
     * @version         1.0.5
     * @author          Can Berkol
     *
     * @use             $this->createException()
     * @use             $this->resetResponse()
     * @use             $this->validateAndGetLocale()
     * @use             $this->validateAndGetProduct()
     * @use             $this->validateAndGetProductAttribute()
     *
     * @param           mixed           $attribute  url_key, id, entity
     * @param           mixed           $product    id, sku
     * @param           mixed           $language   iso code, entity, id
     *
     * @return          mixed           $response
     */
    public function getAttributeValueOfStock($attribute, $product, $language){
        $this->resetResponse();
        $attribute = $this->validateAndGetProductAttribute($attribute);
        $product = $this->validateAndGetProduct($product);
        $language = $this->validateAndGetLocale($language);

        $q_str = 'SELECT DISTINCT ' . $this->entity['stock_attribute_value']['alias'] . ', ' . $this->entity['product_attribute']['alias']
            . ' FROM ' . $this->entity['stock_attribute_value']['name'] . ' ' . $this->entity['product_attribute_values']['alias']
            . ' JOIN ' . $this->entity['stock_attribute_value']['alias'] . '.attribute ' . $this->entity['product_attribute']['alias']
            . ' WHERE ' . $this->entity['stock_attribute_value']['alias'] . '.product = ' . $product->getId()
            . ' AND ' . $this->entity['stock_attribute_value']['alias'] . '.language = ' . $language->getId()
            . ' AND ' . $this->entity['stock_attribute_value']['alias'] . '.attribute = ' . $attribute->getId();

        $query = $this->em->createQuery($q_str);
        $query->setMaxResults(1);
        $query->setFirstResult(0);
        $result = $query->getResult();
        $totalRows = count($result);
        if ($totalRows < 1) {
            $this->response['error'] = true;
            $this->response['code'] = 'msg.error.db.attribute.value.notfound';
            return $this->response;
        }
        /**
         * Prepare & Return Response
         */
        $this->response = array(
            'rowCount' => $this->response['rowCount'],
            'result' => array(
                'set' => $result[0],
                'total_rows' => $totalRows,
                'last_insert_id' => null,
            ),
            'error' => false,
            'code' => 'msg.success.db.entry.exists',
        );
        return $this->response;
    }
    /**
     * @name        getStock ()
     * Returns details of a gallery.
     *
     * @since        1.0.0
     * @version         1.0.0
     * @author          Said İmamoğlu
     *
     * @use             $this->createException()
     * @use             $this->listStocks()
     *
     * @param           mixed $stock id, url_key
     * @param           string $by entity, id, url_key
     *
     * @return          mixed           $response
     */
    public function getStock($stock, $by = 'id')
    {
        $this->resetResponse();
        $by_opts = array('id', 'sku', 'product');
        if (!in_array($by, $by_opts)) {
            return $this->createException('InvalidParameterValue', implode(',', $by_opts), 'err.invalid.parameter.by');
        }
        if (!is_object($stock) && !is_numeric($stock) && !is_string($stock)) {
            return $this->createException('InvalidParameter', 'ProductCategory or numeric id', 'err.invalid.parameter.product_category');
        }
        if (is_object($stock)) {
            if (!$stock instanceof BundleEntity\Stock) {
                return $this->createException('InvalidParameter', 'ProductCategory', 'err.invalid.parameter.product_category');
            }
            /**
             * Prepare & Return Response
             */
            $this->response = array(
                'rowCount' => $this->response['rowCount'],
                'result' => array(
                    'set' => $stock,
                    'total_rows' => 1,
                    'last_insert_id' => null,
                ),
                'error' => false,
                'code' => 'scc.db.entry.exist',
            );
            return $this->response;
        }
        $column = '';
        $filter[] = array(
            'glue' => 'and',
            'condition' => array(
                array(
                    'glue' => 'and',
                    'condition' => array('column' => $this->entity['stock']['alias'] . '.' . $by, 'comparison' => '=', 'value' => $stock),
                )
            )
        );
        $response = $this->listStocks($filter, null, null, null, false);
        if ($response['error']) {
            return $response;
        }
        $collection = $response['result']['set'];
        /**
         * Prepare & Return Response
         */
        $this->response = array(
            'rowCount' => $this->response['rowCount'],
            'result' => array(
                'set' => $collection[0],
                'total_rows' => 1,
                'last_insert_id' => null,
            ),
            'error' => false,
            'code' => 'scc.db.entry.exist',
        );
        return $this->response;
    }
    /**
     * @name            doesStockAtributeValueExist()
     *                  Checks if entry exists in database.
     *
     * @since           1.0.5
     * @version         1.0.5
     *
     * @author          Can Berkol
     *
     * @use             $this->getAttributeValueOfStock()
     *
     * @param           mixed       $attribute      id, url_key
     * @param           mixed       $stock          id, url_key, sku
     * @param           mixed       $language       id, iso_code, url_key
     * @param           bool        $bypass         If set to true does not return response but only the result.
     *
     * @return          mixed       $response
     */
    public function doesStockAttributeValueExist($attribute, $stock, $language, $bypass = false){
        $exist = false;
        $response = $this->getAttributeValueOfStock($attribute, $stock, $language);
        if (!$response['error']) {
            $exist = true;
        }
        if ($bypass) {
            return $exist;
        }
        /**
         * Prepare & Return Response
         */
        $this->response = array(
            'rowCount' => $this->response['rowCount'],
            'result' => array(
                'set' => $exist,
                'total_rows' => 1,
                'last_insert_id' => null,
            ),
            'error' => false,
            'code' => 'msg.success.db.entry.exist',
        );
        return $this->response;
    }
    /**
     * @name        doesStockExist ()
     * Checks if entry exists in database.
     *
     * @since           1.0.0
     * @version         1.0.0
     * @author          Said İmamoğlu
     *
     * @use             $this->getStock()
     *
     * @param           mixed $item id, url_key
     * @param           string $by id, url_key
     *
     * @param           bool $bypass If set to true does not return response but only the result.
     *
     * @return          mixed           $response
     */
    public function doesStockExist($item, $by = 'id', $bypass = false)
    {
        $this->resetResponse();
        $exist = false;

        $response = $this->getStock($item, $by);

        if (!$response['error'] && $response['result']['total_rows'] > 0) {
            $exist = $response['result']['set'];
            $error = false;
        } else {
            $exist = false;
            $error = true;
        }

        if ($bypass) {
            return $exist;
        }
        /**
         * Prepare & Return Response
         */
        $this->response = array(
            'rowCount' => $this->response['rowCount'],
            'result' => array(
                'set' => $exist,
                'total_rows' => 1,
                'last_insert_id' => null,
            ),
            'error' => $error,
            'code' => 'scc.db.entry.exist',
        );
        return $this->response;
    }

    /**
     * @name        insertStock ()
     * Inserts one or more item into database.
     *
     * @since        1.0.1
     * @version         1.0.3
     * @author          Said İmamoğlu
     *
     * @use             $this->insertFiles()
     *
     * @param           array $item Collection of entities or post data.
     *
     * @return          array           $response
     */

    public function insertStock($item)
    {
        $this->resetResponse();
        return $this->insertStocks(array($item));
    }

    /**
     * @name            insertStocks ()
     * Inserts one or more items into database.
     *
     * @since           1.0.1
     * @version         1.0.3
     * @author          Said İmamoğlu
     *
     * @use             $this->createException()
     *
     * @throws          InvalidParameterException
     * @throws          InvalidMethodException
     *
     * @param           array $collection Collection of entities or post data.
     *
     * @return          array           $response
     */

    public function insertStocks($collection)
    {
        $countInserts = 0;
        foreach ($collection as $data) {
            if ($data instanceof BundleEntity\Stock) {
                $entity = $data;
                $this->em->persist($entity);
                $insertedItems[] = $entity;
                $countInserts++;
            } else if (is_object($data)) {
                $entity = new BundleEntity\Stock();
                foreach ($data as $column => $value) {
                    $set = 'set' . $this->translateColumnName($column);
                    switch ($column) {
                        case 'product':
                            $productModel = $this->kernel->getContainer()->get('productmanagement.model');
                            $response = $productModel->getProduct($value);
                            if ($response['error']) {
                                new CoreExceptions\SiteDoesNotExistException($this->kernel, $data);
                            }
                            $entity->$set($response['result']['set']);
                            unset($response, $productModel);
                            break;
                        case 'supplier':
                            $response = $this->getSupplier($value);
                            if ($response['error']) {
                                new CoreExceptions\EntityDoesNotExistException($this->kernel, 'Supplier can not found.');
                            }
                            $entity->$set($response['result']['set']);
                            unset($response);
                            break;
                        case 'date_added':
                        case 'date_updated':
                            new $entity->$set(\DateTime('now', new \DateTimeZone($this->kernel->getContainer()->getParameter('app_timezone'))));
                            break;
                        default:
                            $entity->$set($value);
                            break;
                    }
                }
                $this->em->persist($entity);
                $insertedItems[] = $entity;
                $countInserts++;
            } else {
                new CoreExceptions\InvalidDataException($this->kernel);
            }
        }
        /**
         * Save data.
         */
        if ($countInserts > 0) {
            $this->em->flush();
        }
        /**
         * Prepare & Return Response
         */
        $this->response = array(
            'rowCount' => $this->response['rowCount'],
            'result' => array(
                'set' => $insertedItems,
                'total_rows' => $countInserts,
                'last_insert_id' => $entity->getId(),
            ),
            'error' => false,
            'code' => 'scc.db.insert.done',
        );
        return $this->response;
    }

    /**
     * @name            updateStock()
     * Updates single item. The item must be either a post data (array) or an entity
     *
     * @since           1.0.0
     * @version         1.0.0
     * @author          Said İmamoğlu
     *
     * @use             $this->resetResponse()
     * @use             $this->updateStocks()
     *
     * @param           mixed   $item     Entity or Entity id of a folder
     *
     * @return          array   $response
     *
     */

    public function updateStock($item){
        return $this->updateStocks(array($item));
    }

    /**
     * @name            updateStocks()
     *                  Updates one or more item details in database.
     *
     * @since           1.0.0
     * @version         1.0.4
     *
     * @author          Can Berkol
     * @author          Said İmamoğlu
     *
     * @use             $this->update_entities()
     * @use             $this->createException()
     * @use             $this->listStocks()
     *
     *
     * @throws          InvalidParameterException
     *
     * @param           array   $collection     Collection of item's entities or array of entity details.
     *
     * @return          array   $response
     *
     */

    public function updateStocks($collection)
    {
        $countUpdates = 0;
        foreach ($collection as $data) {
            if ($data instanceof BundleEntity\Stock) {
                $entity = $data;
                $this->em->persist($entity);
                $updatedItems[] = $entity;
                $countUpdates++;
            } else if (is_object($data)) {
                $response = $this->getStock($data->id,'id');
                if ($response['error']) {
                    new CoreExceptions\EntityDoesNotExistException($this->kernel,'Stock not found');
                }
                $oldEntity = $response['result']['set'];

                foreach ($data as $column => $value) {
                    $set = 'set' . $this->translateColumnName($column);
                    switch ($column) {
                        case 'product':
                            $productModel = $this->kernel->getContainer()->get('productmanagement.model');
                            $response = $productModel->getProduct($value);
                            if ($response['error']) {
                                new CoreExceptions\EntityDoesNotExistException($this->kernel, 'Product not found');
                            }
                            $oldEntity->$set($response['result']['set']);
                            unset($response, $productModel);
                            break;
                        case 'supplier':
                            $response = $this->getSupplier($value);
                            if ($response['error']) {
                                new CoreExceptions\EntityDoesNotExistException($this->kernel, 'Supplier can not found.');
                            }
                            $oldEntity->$set($response['result']['set']);
                            unset($response);
                            break;
                        case 'date_added':
                        case 'date_updated':
                            new $oldEntity->$set(\DateTime('now', new \DateTimeZone($this->kernel->getContainer()->getParameter('app_timezone'))));
                            break;
                        case 'id':
                            break;
                        default:
                            $oldEntity->$set($value);
                            break;
                    }
                }
                $this->em->persist($oldEntity);
                $updatedItems[] = $oldEntity;
                $countUpdates++;
            } else {
                new CoreExceptions\InvalidDataException($this->kernel);
            }
        }

        /**
         * Save data.
         */
        if ($countUpdates > 0) {
            $this->em->flush();
        }
        /**
         * Prepare & Return Response
         */
        $this->response = array(
            'rowCount' => $this->response['rowCount'],
            'result' => array(
                'set' => $updatedItems,
                'total_rows' => $countUpdates,
                'last_insert_id' => null,
            ),
            'error' => false,
            'code' => 'scc.db.insert.done',
        );
        return $this->response;
    }

    /**
     * @name        deleteSupplier ()
     * Deletes an existing item from database.
     *
     * @since           1.0.1
     * @version         1.0.1
     * @author          Said İmamoğlu
     *
     * @use             $this->deleteSuppliers()
     *
     * @param           mixed $item Entity, id or url key of item
     * @param           string $by
     *
     * @return          mixed           $response
     */
    public function deleteSupplier($item, $by = 'entity')
    {
        return $this->deleteSuppliers(array($item), $by);
    }

    /**
     * @name            deleteSuppliers ()
     * Deletes provided items from database.
     *
     * @since        1.0.0
     * @version         1.0.0
     * @author          Said İmamoğlu
     *
     * @use             $this->createException()
     *
     * @param           array $collection Collection of Supplier entities, ids, or codes or url keys
     *
     * @return          array           $response
     */
    public function deleteSuppliers($collection)
    {
        $this->resetResponse();
        /** Parameter must be an array */
        if (!is_array($collection)) {
            return $this->createException('InvalidParameterValue', 'Array', 'err.invalid.parameter.collection');
        }
        $countDeleted = 0;
        foreach ($collection as $entry) {
            if ($entry instanceof BundleEntity\Supplier) {
                $this->em->remove($entry);
                $countDeleted++;
            } else {
                switch ($entry) {
                    case is_numeric($entry):
                        $response = $this->getSupplier($entry, 'id');
                        break;
                    case is_string($entry):
                        $response = $this->getProductCategory($entry, 'url_key');
                        break;
                }
                if ($response['error']) {
                    $this->createException('EntryDoesNotExist', $entry, 'err.invalid.entry');
                }
                $entry = $response['result']['set'];
                $this->em->remove($entry);
                $countDeleted++;
            }
        }

        if ($countDeleted < 0) {
            $this->response['error'] = true;
            $this->response['code'] = 'err.db.fail.delete';

            return $this->response;
        }
        $this->em->flush();
        $this->response = array(
            'rowCount' => 0,
            'result' => array(
                'set' => null,
                'total_rows' => $countDeleted,
                'last_insert_id' => null,
            ),
            'error' => false,
            'code' => 'scc.db.deleted',
        );
        return $this->response;
    }

    /**
     * @name            listSuppliers ()
     * Lists supplier data from database with given params.
     *
     * @author          Said İmamoğlu
     * @version         1.0.1
     * @since           1.0.1
     *
     * @param           array $filter
     * @param           array $sortOrder
     * @param           array $limit
     * @param           string $queryStr
     *
     * @use             $this->createException()
     * @use             $this->prepareWhere()
     * @use             $this->addLimit()
     *
     * @return          array $this->response
     */
    public function listSuppliers($filter = null, $sortOrder = null, $limit = null, $queryStr = null)
    {
        $this->resetResponse();
        if (!is_array($sortOrder) && !is_null($sortOrder)) {
            return $this->createException('InvalidSortOrder', '', 'err.invalid.parameter.sortorder');
        }

        $order_str = '';
        $where_str = '';
        $group_str = '';
        $filter_str = '';

        /**
         * Start creating the query.
         *
         * Note that if no custom select query is provided we will use the below query as a start.
         */
        if (is_null($queryStr)) {
            $queryStr = 'SELECT ' . $this->entity['supplier']['alias']
                . ' FROM ' . $this->entity['supplier']['name'] . ' ' . $this->entity['supplier']['alias'];
        }
        /**
         * Prepare ORDER BY section of query.
         */
        if ($sortOrder != null) {
            foreach ($sortOrder as $column => $direction) {
                $order_str .= ' ' . $this->entity['supplier']['alias'] . '.' . $column . ' ' . strtoupper($direction) . ', ';
            }
            $order_str = rtrim($order_str, ', ');
            $order_str = ' ORDER BY ' . $order_str . ' ';
        }

        /**
         * Prepare WHERE section of query.
         */
        if ($filter != null) {
            $filter_str = $this->prepareWhere($filter);
            $where_str .= ' WHERE ' . $filter_str;
        }
        $queryStr .= $where_str . $group_str . $order_str;

        $query = $this->em->createQuery($queryStr);

        $query = $this->addLimit($query, $limit);

        /**
         * Prepare & Return Response
         */
        $result = $query->getResult();
        $stocks = array();
        $unique = array();
        foreach ($result as $entry) {
            $id = $entry->getId();
            if (!isset($unique[$id])) {
                $stocks[$id] = $entry;
                $unique[$id] = $entry->getId();
            }
        }

        $total_rows = count($stocks);

        if ($total_rows < 1) {
            $this->response['code'] = 'err.db.entry.notexist';
            return $this->response;
        }
        $newCollection = array();
        foreach ($stocks as $stock) {
            $newCollection[] = $stock;
        }
        unset($stocks, $unique);

        $this->response = array(
            'rowCount' => $this->response['rowCount'],
            'result' => array(
                'set' => $newCollection,
                'total_rows' => $total_rows,
                'last_insert_id' => null,
            ),
            'error' => false,
            'code' => 'scc.db.entry.exist',
        );
        return $this->response;
    }
    /**
     * @name            getStockAttributeValue ()
     *                  Returns a StockAttributeValue Entry
     *
     * @since           1.0.5
     * @version         1.0.5
     * @author          Can Berkol
     *
     * @use             $this->createException()
     * @use             $this->resetResponse()
     *
     * @param           integer         $id
     *
     * @return          mixed           $response
     */
    public function getStockAttributeValue($id){
        $this->resetResponse();
        if (!is_integer($id)) {
            return $this->createException('InvalidParameter', '$id parameter must hold an integer value.', 'msg.error.invalid.parameter.id');
        }

        $result = $this->em->getRepository($this->entity['stock_attribute_value']['name'])
            ->findOneBy(array('id' => $id));

        $error = true;
        $code = 'msg.error.db.entry.notexist';
        $found = 0;
        if($result instanceof BundleEntity\StockAttributeValue){
            $error = false;
            $code = 'msg.success.db.entity.exists';
            $found = 1;
        }
        if($error){ $result = null; }
        /**
         * Prepare & Return Response
         */
        $this->response = array(
            'rowCount' => $this->response['rowCount'],
            'result' => array(
                'set' => $result,
                'total_rows' => $found,
                'last_insert_id' => null,
            ),
            'error' => $error,
            'code' => $code,
        );

        return $this->response;
    }
    /**
     * @name        getSupplier ()
     * Returns details of a gallery.
     *
     * @since           1.0.1
     * @version         1.0.1
     * @author          Said İmamoğlu
     *
     * @use             $this->createException()
     * @use             $this->listSuppliers()
     *
     * @param           mixed $stock id, url_key
     * @param           string $by entity, id, url_key
     *
     * @return          mixed           $response
     */
    public function getSupplier($stock, $by = 'id')
    {
        $this->resetResponse();
        $by_opts = array('id', 'sku', 'product');
        if (!in_array($by, $by_opts)) {
            return $this->createException('InvalidParameterValue', implode(',', $by_opts), 'err.invalid.parameter.by');
        }
        if (!is_object($stock) && !is_numeric($stock) && !is_string($stock)) {
            return $this->createException('InvalidParameter', 'ProductCategory or numeric id', 'err.invalid.parameter.product_category');
        }
        if (is_object($stock)) {
            if (!$stock instanceof BundleEntity\Supplier) {
                return $this->createException('InvalidParameter', 'ProductCategory', 'err.invalid.parameter.product_category');
            }
            /**
             * Prepare & Return Response
             */
            $this->response = array(
                'rowCount' => $this->response['rowCount'],
                'result' => array(
                    'set' => $stock,
                    'total_rows' => 1,
                    'last_insert_id' => null,
                ),
                'error' => false,
                'code' => 'scc.db.entry.exist',
            );
            return $this->response;
        }
        $column = '';
        $filter[] = array(
            'glue' => 'and',
            'condition' => array(
                array(
                    'glue' => 'and',
                    'condition' => array('column' => $this->entity['supplier']['alias'] . '.' . $by, 'comparison' => '=', 'value' => $stock),
                )
            )
        );
        $response = $this->listSuppliers($filter, null, null, null, false);
        if ($response['error']) {
            return $response;
        }
        $collection = $response['result']['set'];
        /**
         * Prepare & Return Response
         */
        $this->response = array(
            'rowCount' => $this->response['rowCount'],
            'result' => array(
                'set' => $collection[0],
                'total_rows' => 1,
                'last_insert_id' => null,
            ),
            'error' => false,
            'code' => 'scc.db.entry.exist',
        );
        return $this->response;
    }
    /**
     * @name            listStockAttributeValues ()
     *                  List stock attribute values from database based on a variety of conditions.
     *
     * @since           1.0.5
     * @version         1.0.
     * @author          Can Berkol
     *
     * @use             $this->createException()
     *
     * @param           array $filter Multi-dimensional array
     * @param           array $sortOrder Array
     *                                      'column'            => 'asc|desc'
     * @param           array $limit
     *                                      start
     *                                      count
     *
     * @param           string $queryStr If a custom query string needs to be defined.
     *
     * @return          array           $response
     */
    public function listStockAttributeValues($filter = null, $sortOrder = null, $limit = null, $queryStr = null){
        $this->resetResponse();
        if (!is_array($sortOrder) && !is_null($sortOrder)) {
            return $this->createException('InvalidSortOrder', '', 'err.invalid.parameter.sortorder');
        }
        /**
         * Add filter checks to below to set join_needed to true.
         */
        /**         * ************************************************** */
        $order_str = '';
        $where_str = '';
        $group_str = '';
        $filter_str = '';

        /**
         * Start creating the query.
         *
         * Note that if no custom select query is provided we will use the below query as a start.
         */
        if (is_null($queryStr)) {
            $queryStr = 'SELECT ' . $this->entity['stock_attribute_value']['alias']
                . ' FROM ' . $this->entity['stock_attribute_value']['name'] . ' ' . $this->entity['stock_attribute_value']['alias'];
        }
        /**
         * Prepare ORDER BY section of query.
         */
        if ($sortOrder != null) {
            foreach ($sortOrder as $column => $direction) {
                switch ($column) {
                    case 'id':
                    case 'sort_order':
                    case 'date_added':
                        $column = $this->entity['stock_attribute_value']['alias'] . '.' . $column;
                        break;
                }
                $order_str .= ' ' . $column . ' ' . strtoupper($direction) . ', ';
            }
            $order_str = rtrim($order_str, ', ');
            $order_str = ' ORDER BY ' . $order_str . ' ';
        }

        /**
         * Prepare WHERE section of query.
         */
        if ($filter != null) {
            $filter_str = $this->prepareWhere($filter);
            $where_str .= ' WHERE ' . $filter_str;
        }

        $queryStr .= $where_str . $group_str . $order_str;

        $query = $this->em->createQuery($queryStr);

        /**
         * Prepare LIMIT section of query
         */
        if ($limit != null) {
            if (isset($limit['start']) && isset($limit['count'])) {
                /** If limit is set */
                $query->setFirstResult($limit['start']);
                $query->setMaxResults($limit['count']);
            } else {
                new CoreExceptions\InvalidLimitException($this->kernel, '');
            }
        }
        /**
         * Prepare & Return Response
         */
        $result = $query->getResult();

        $attributes = array();
        $unique = array();
        foreach ($result as $entry) {
            $id = $entry->getId();
            if (!isset($unique[$id])) {
                $attributes[] = $entry;
                $unique[$id] = $entry->getId();
            }
        }
        unset($unique);
        $totalRows = count($attributes);
        if ($totalRows < 1) {
            $this->response['code'] = 'err.db.entry.notexist';
            return $this->response;
        }
        $this->response = array(
            'rowCount' => $this->response['rowCount'],
            'result' => array(
                'set' => $attributes,
                'total_rows' => $totalRows,
                'last_insert_id' => null,
            ),
            'error' => false,
            'code' => 'scc.db.entry.exist',
        );
        return $this->response;
    }
    /**
     * @name            listStockAttributeValuesOfStock ()
     *                  List stock attribute values of product from database based on a variety of conditions.
     *
     * @since           1.0.5
     * @version         1.0.5
     * @author          Said İmamoğlu
     *
     * @use             $this->createException()
     *
     * @param           array $stock
     * @param           array $filter Multi-dimensional array
     * @param           array $sortOrder Array
     * @param           array $limit
     *
     * @param           string $queryStr If a custom query string needs to be defined.
     *
     * @return          array           $response
     */
    public function listStockAttributeValuesOfStock($stock, $filter = null, $sortOrder = null, $limit = null, $queryStr = null){
        $product = $this->validateAndGetStock($stock);
        $filter = array();
        $filter[] = array(
            'glue' => 'and',
            'condition' => array(
                array(
                    'glue' => 'and',
                    'condition' => array('column' => $this->entity['stock_attribute_values']['alias'] . '.strock', 'comparison' => '=', 'value' => $stock->getId()),
                )
            )
        );
        return $this->listStockAttributeValues($filter);

    }
    /**
     * @name        doesSupplierExist ()
     * Checks if entry exists in database.
     *
     * @since           1.0.1
     * @version         1.0.1
     * @author          Said İmamoğlu
     *
     * @use             $this->getSupplier()
     *
     * @param           mixed $item id, url_key
     * @param           string $by id, url_key
     *
     * @param           bool $bypass If set to true does not return response but only the result.
     *
     * @return          mixed           $response
     */
    public function doesSupplierExist($item, $by = 'id', $bypass = false)
    {
        $this->resetResponse();
        $exist = false;

        $response = $this->getSupplier($item, $by);

        if (!$response['error'] && $response['result']['total_rows'] > 0) {
            $exist = $response['result']['set'];
            $error = false;
        } else {
            $exist = false;
            $error = true;
        }

        if ($bypass) {
            return $exist;
        }
        /**
         * Prepare & Return Response
         */
        $this->response = array(
            'rowCount' => $this->response['rowCount'],
            'result' => array(
                'set' => $exist,
                'total_rows' => 1,
                'last_insert_id' => null,
            ),
            'error' => $error,
            'code' => 'scc.db.entry.exist',
        );
        return $this->response;
    }
    /**
     * @name            insertStockAttributeValue()
     *                  Inserts one stock attribute value into database.
     *
     * @since           1.0.5
     * @version         1.0.5
     * @author          Can Berkol
     *
     * @use             $this->insertProductAttributeValues()
     *
     * @param           mixed           $attrVal
     *
     * @return          array           $response
     */
    public function insertStockAttributeValue($attrVal){
        return $this->insertStockAttributeValues(array($attrVal));
    }

    /**
     * @name            insertProductAttributes()
     *                  Inserts one or more product attribute values into database.
     *
     * @since           1.0.5
     * @version         1.0.5
     * @author          Can Berkol
     *
     * @use             $this->createException()
     * @use             $this->insertProductAttributeLocalization()
     *
     * @param           array           $collection             Collection of entities or post data.
     *
     * @return          array           $response
     */
    public function insertProductAttributeValues($collection){
        $this->resetResponse();
        /** Parameter must be an array */
        if (!is_array($collection)) {
            return $this->createException('InvalidParameter', 'Array', 'msg.err.invalid.parameter.collection');
        }
        $countInserts = 0;
        $insertedItems = array();
        foreach ($collection as $data) {
            if ($data instanceof BundleEntity\StockAttributeValue) {
                $entity = $data;
                $this->em->persist($entity);
                $insertedItems[] = $entity;
                $countInserts++;
            } else if (is_object($data)) {
                $entity = new BundleEntity\StockAttributeValue;
                if (isset($data->id)) {
                    unset($data->id);
                }
                foreach ($data as $column => $value) {
                    $set = 'set' . $this->translateColumnName($column);
                    switch ($column) {
                        case 'language':
                            $lModel = $this->kernel->getContainer()->get('multilanguagesupport.model');
                            $response = $lModel->getLanguage($value, 'id');
                            if (!$response['error']) {
                                $entity->$set($response['result']['set']);
                            } else {
                                $response = $lModel->getLanguage($value, 'iso_code');
                                if (!$response['error']) {
                                    $entity->$set($response['result']['set']);
                                } else {
                                    new CoreExceptions\EntityDoesNotExist($this->kernel, $value);
                                }
                            }
                            unset($response, $sModel);
                            break;
                        case 'attribute':
                            $response = $this->getProductAttribute($value, 'id');
                            if (!$response['error']) {
                                $entity->$set($response['result']['set']);
                            } else {
                                new CoreExceptions\EntityDoesNotExist($this->kernel, $value);
                            }
                            unset($response, $sModel);
                            break;
                        case 'stock':
                            $response = $this->getStock($value, 'id');
                            if (!$response['error']) {
                                $entity->$set($response['result']['set']);
                            } else {
                                new CoreExceptions\EntityDoesNotExist($this->kernel, $value);
                            }
                            unset($response, $sModel);
                            break;
                        default:
                            $entity->$set($value);
                            break;
                    }
                }
                $this->em->persist($entity);
                $insertedItems[] = $entity;

                $countInserts++;
            } else {
                new CoreExceptions\InvalidDataException($this->kernel);
            }
        }
        if ($countInserts > 0) {
            $this->em->flush();
        }
        /**
         * Prepare & Return Response
         */
        $this->response = array(
            'rowCount' => $this->response['rowCount'],
            'result' => array(
                'set' => $insertedItems,
                'total_rows' => $countInserts,
                'last_insert_id' => $entity->getId(),
            ),
            'error' => false,
            'code' => 'scc.db.insert.done',
        );
        return $this->response;
    }
    /**
     * @name            insertSupplier ()
     * Inserts one or more item into database.
     *
     * @since           1.0.1
     * @version         1.0.1
     * @author          Said İmamoğlu
     *
     * @use             $this->insertFiles()
     *
     * @param           array $item Collection of entities or post data.
     *
     * @return          array           $response
     */

    public function insertSupplier($item)
    {
        $this->resetResponse();
        return $this->insertSuppliers(array($item));
    }

    /**
     * @name            insertSuppliers ()
     * Inserts one or more items into database.
     *
     * @since           1.0.1
     * @version         1.0.1
     * @author          Said İmamoğlu
     *
     * @use             $this->createException()
     *
     * @throws          InvalidParameterException
     * @throws          InvalidMethodException
     *
     * @param           array $collection Collection of entities or post data.
     *
     * @return          array           $response
     */

    public function insertSuppliers($collection)
    {
        $countInserts = 0;
        foreach ($collection as $data) {
            if ($data instanceof BundleEntity\Supplier) {
                $entity = $data;
                $this->em->persist($entity);
                $insertedItems[] = $entity;
                $countInserts++;
            } else if (is_object($data)) {
                $entity = new BundleEntity\Supplier();
                foreach ($data as $column => $value) {
                    $set = 'set' . $this->translateColumnName($column);
                    $entity->$set($value);
                }
                $this->em->persist($entity);
                $insertedItems[] = $entity;
                $countInserts++;
            } else {
                new CoreExceptions\InvalidDataException($this->kernel);
            }
        }
        /**
         * Save data.
         */
        if ($countInserts > 0) {
            $this->em->flush();
        }
        /**
         * Prepare & Return Response
         */
        $this->response = array(
            'rowCount' => $this->response['rowCount'],
            'result' => array(
                'set' => $insertedItems,
                'total_rows' => $countInserts,
                'last_insert_id' => $entity->getId(),
            ),
            'error' => false,
            'code' => 'scc.db.insert.done',
        );
        return $this->response;
    }
    /**
     * @name            updateStockAttributeValue ()
     *                  Updates single stock attribute value. The data must be either a post data (array) or an entity
     *
     * @since           1.0.5
     * @version         1.0.5
     * @author          Can Berkol
     *
     * @use             $this->updateStockAttributeValues()
     *
     * @param           mixed $data entity or post data
     *
     * @return          mixed           $response
     */
    public function updateStockAttributeValue($data)
    {
        return $this->updateStockAttributeValues(array($data));
    }

    /**
     * @name            updateProductAttributeValues ()
     *                  Updates one or more stock details in database.
     *
     * @since           1.0.5
     * @version         1.0.5
     * @author          Can Berkol
     *
     * @use             $this->createException()
     *
     * @param           array $collection Collection of Product entities or array of entity details.
     *
     * @return          array           $response
     */
    public function updateProductAttributeValues($collection)
    {
        $this->resetResponse();
        /** Parameter must be an array */
        if (!is_array($collection)) {
            return $this->createException('InvalidParameter', 'Array', 'err.invalid.parameter.collection');
        }
        $countUpdates = 0;
        $updatedItems = array();
        foreach ($collection as $data) {
            if ($data instanceof BundleEntity\StockAttributeValue) {
                $entity = $data;
                $this->em->persist($entity);
                $updatedItems[] = $entity;
                $countUpdates++;
            } else if (is_object($data)) {
                if (!property_exists($data, 'id') || !is_numeric($data->id)) {
                    return $this->createException('InvalidParameter', 'Each data must contain a valid identifier id, integer', 'err.invalid.parameter.collection');
                }
                if (!property_exists($data, 'date_updated')) {
                    $data->date_updated = new \DateTime('now', new \DateTimeZone($this->kernel->getContainer()->getParameter('app_timezone')));
                }
                if (property_exists($data, 'date_added')) {
                    unset($data->date_added);
                }
                $response = $this->getStockAttributeValue($data->id, 'id');
                if ($response['error']) {
                    return $this->createException('EntityDoesNotExist', 'ProductAttribute with id ' . $data->id, 'err.invalid.entity');
                }
                $oldEntity = $response['result']['set'];

                foreach ($data as $column => $value) {
                    $set = 'set' . $this->translateColumnName($column);
                    switch ($column) {
                        case 'attribute':
                            $pModel = $this->kernel->getContainer()->get('productManagement.model');
                            $response = $pModel->getProductAttribute($value, 'id');
                            if (!$response['error']) {
                                $oldEntity->$set($response['result']['set']);
                            } else {
                                new CoreExceptions\EntityDoesNotExistException($this->kernel, $value);
                            }
                            unset($response, $pModel);
                            break;
                        case 'language':
                            $lModel = $this->kernel->getContainer()->get('multilanguagesupport.model');
                            $response = $lModel->getLanguage($value, 'id');
                            if (!$response['error']) {
                                $oldEntity->$set($response['result']['set']);
                            } else {
                                new CoreExceptions\EntityDoesNotExistException($this->kernel, $value);
                            }
                            unset($response, $lModel);
                            break;
                        case 'product':
                            $pModel = $this->kernel->getContainer()->get('productManagement.model');
                            $response = $pModel->getStock($value, 'id');
                            if (!$response['error']) {
                                $oldEntity->$set($response['result']['set']);
                            } else {
                                new CoreExceptions\EntityDoesNotExistException($this->kernel, $value);
                            }
                            unset($response, $pModel);
                            break;
                        case 'id':
                            break;
                        default:
                            $oldEntity->$set($value);
                            break;
                    }
                    if ($oldEntity->isModified()) {
                        $this->em->persist($oldEntity);
                        $countUpdates++;
                        $updatedItems[] = $oldEntity;
                    }
                }
            } else {
                new CoreExceptions\InvalidDataException($this->kernel);
            }
        }
        if ($countUpdates > 0) {
            $this->em->flush();
        }
        /**
         * Prepare & Return Response
         */
        $this->response = array(
            'rowCount' => $this->response['rowCount'],
            'result' => array(
                'set' => $updatedItems,
                'total_rows' => $countUpdates,
                'last_insert_id' => null,
            ),
            'error' => false,
            'code' => 'scc.db.update.done',
        );
        return $this->response;
    }
    /**
     * @name            updateSupplier()
     * Updates single item. The item must be either a post data (array) or an entity
     *
     * @since           1.0.1
     * @version         1.0.1
     * @author          Said İmamoğlu
     *
     * @use             $this->resetResponse()
     * @use             $this->updateSuppliers()
     *
     * @param           mixed   $item     Entity or Entity id of a folder
     *
     * @return          array   $response
     *
     */

    public function updateSupplier($item)
    {
        $this->resetResponse();
        return $this->updateSuppliers(array($item));
    }

    /**
     * @name            updateSuppliers()
     * Updates one or more item details in database.
     *
     * @since           1.0.1
     * @version         1.0.1
     * @author          Said İmamoğlu
     *
     * @use             $this->update_entities()
     * @use             $this->createException()
     * @use             $this->listSuppliers()
     *
     *
     * @throws          InvalidParameterException
     *
     * @param           array   $collection     Collection of item's entities or array of entity details.
     *
     * @return          array   $response
     *
     */

    public function updateSuppliers($collection)
    {
        $countInserts = 0;
        foreach ($collection as $data) {
            if ($data instanceof BundleEntity\Supplier) {
                $entity = $data;
                $this->em->persist($entity);
                $insertedItems[] = $entity;
                $countInserts++;
            } else if (is_object($data)) {
                $response = $this->getSupplier($data->id,'id');
                if ($response['error']) {
                    return new CoreExceptions\EntityDoesNotExistException($this->kernel,'Supplier not found with : '.$data->id.' id','err.notfound');
                }
                $oldEntity = $response['result']['set'];
                foreach ($data as $column => $value) {
                    if ($column !== 'id') {
                        $set = 'set' . $this->translateColumnName($column);
                        $oldEntity->$set($value);
                    }
                }
                $this->em->persist($oldEntity);
                $insertedItems[] = $oldEntity;
                $countInserts++;
            } else {
                new CoreExceptions\InvalidDataException($this->kernel);
            }
        }
        /**
         * Save data.
         */
        if ($countInserts > 0) {
            $this->em->flush();
        }
        /**
         * Prepare & Return Response
         */
        $this->response = array(
            'rowCount' => $this->response['rowCount'],
            'result' => array(
                'set' => $insertedItems,
                'total_rows' => $countInserts,
                'last_insert_id' => $oldEntity->getId(),
            ),
            'error' => false,
            'code' => 'scc.db.insert.done',
        );
        return $this->response;
    }


    /**
     * @name        doesStockExistWithQuantity ()
     * Checks if stock exists with quantity in database.
     *
     * @since           1.0.2
     * @version         1.0.2
     * @author          Said İmamoğlu
     *
     * @use             $this->listStocks()
     *
     *
     * @param   mixed   product
     * @param   int     quantity
     * @param   string  $by
     * @param   bool    $bypass
     *
     * @return          mixed           $response
     */
    public function doesStockExistWithQuantity($product,$quantity,$by='product' , $bypass = false)
    {
        $this->resetResponse();
        $exist = false;
        $column = $this->getEntityDefinition('stock', 'alias') . '.'.$by;
        $filter = array();
        $filter[] = array(
            'glue' => 'and',
            'condition' => array(
                array(
                    'glue' => 'and',
                    'condition' => array('column' => $column, 'comparison' => '=', 'value' => $product ),
                ),
                array(
                    'glue' => 'and',
                    'condition' => array('column' => $this->getEntityDefinition('stock', 'alias') . '.quantity', 'comparison' => '>=', 'value' => $quantity ),
                ),
            )
        );
        $response = $this->listStocks($filter,array('quantity'=>'desc'),array('start'=>0,'count'=>1));
        if (!$response['error'] && $response['result']['total_rows'] > 0) {
            $exist = true;
            $error = false;
            $code = 'success.stock.available';
        } else {
            $exist = false;
            $error = true;
            $code = 'error.out.of.stock';
        }

        if ($bypass) {
            return $exist;
        }
        /**
         * Prepare & Return Response
         */
        $this->response = array(
            'rowCount' => $this->response['rowCount'],
            'result' => array(
                'set' => $exist,
                'total_rows' => 1,
                'last_insert_id' => null,
            ),
            'error' => $error,
            'code' => $code,
        );
        return $this->response;
    }
    /**
     * @name            validateAndGetStock()
     *                  Validates $stock parameter and returns BiberLtd\Core\Bundles\StockManagementBundle\Entity\Stock if found in database.
     *
     * @since           1.0.5
     * @version         1.0.5
     * @author          Can Berkol
     *
     * @use             $this->createException()
     * @use             $this->getStock()
     *
     * @param           mixed           $stock
     *
     * @return          object          BiberLtd\Core\Bundles\StockManagementBundle\Entity\Stock
     */
    private function validateAndGetStock($stock){
        if (!is_numeric($stock) && !is_string($stock) && !$stock instanceof BundleEntity\Stock) {
            return $this->createException('InvalidParameter', '$stock parameter must hold BiberLtd\\Core\\Bundles\\StockManagementBundle\\Entity\\Stock Entity, string representing url_key or sku, or integer representing database row id', 'msg.error.invalid.parameter.product');
        }
        if ($stock instanceof BundleEntity\Stock) {
            return $stock;
        }
        if (is_numeric($stock)) {
            $response = $this->getStock($stock, 'id');
            if ($response['error']) {
                return $this->createException('EntityDoesNotExist', 'Table: stock, id: ' . $stock, 'msg.error.db.stock.notfound');
            }
            $stock = $response['result']['set'];
        } else if (is_string($stock)) {
            $response = $this->getStock($stock, 'sku');
            if ($response['error']) {
                $response = $this->getStock($stock, 'supplier_sku');
                if ($response['error']) {
                    return $this->createException('EntityDoesNotExist', 'Table : stock, id / sku / supplier_sku: ' .$stock, 'msg.error.db.stock.notfound');
                }
            }
            $stock = $response['result']['set'];
        }

        return $stock;
    }
    /**
     * @name            validateAndGetProductAttribute()
     *                  Validates $attribute parameter and returns BiberLtd\Core\Bundles\ProductManagementBundle\Entity\ProductAttribute if found in database.
     *
     * @since           1.0.5
     * @version         1.0.5
     * @author          Can Berkol
     *
     * @use             $this->createException()
     * @use             $this->getProductAttribute()
     *
     * @param           mixed           $attribute
     *
     * @return          object          BiberLtd\Core\Bundles\ProductManagementBundle\Entity\ProductAttribute
     */
    private function validateAndGetProductAttribute($attribute){
        $pModel = $this->get('productmanagement.model');
        if (!is_numeric($attribute) && !$attribute instanceof ProductEntity\ProductAttribute) {
            return $this->createException('InvalidParameter', '$attribute parameter must hold BiberLtd\\Core\\Bundles\\ProductManagementBundle\\Entity\\ProductAttribute Entity or integer representing database row id', 'msg.error.invalid.parameter.product.attribute');
        }
        /** If no entity is provided as product we need to check if it does exist */
        if (is_numeric($attribute)) {
            $response = $pModel->getProductAttribute($attribute, 'id');
            if ($response['error']) {
                return $this->createException('EntityDoesNotExist', 'Table : product_attributey, id: ' . $attribute,  'msg.error.db.product.attribute.notfound');
            }
            $attribute = $response['result']['set'];
        }
        else if (is_string($attribute)) {
            $response = $pModel->getProductAttribute($attribute, 'url_key');
            if ($response['error']) {
                return $this->createException('EntityDoesNotExist', 'Table : product_attributey, url_key: ' . $attribute,  'msg.error.db.product.attribute.notfound');
            }
            $attribute = $response['result']['set'];
        }
        return $attribute;
    }
}

/**
 * Change Log
 * **************************************
 * v1.0.5                      Can Berkol
 * 17.11.2014
 * **************************************
 * A deleteAllAttributeValuesOfStockAttribute()
 * A doesStockAttributeValueExist()
 * A getAttributeValueOfStock()
 * A insertStockAttributeValue()
 * A insertStockAttributeValues()
 * A listStockAttributeValues()
 * A listStockAttributeValuesOfStock()
 * A updateStockAttributeValue()
 * A updateStockAttributeValues()
 * A validateAndGetStock()
 * A validateAndGetProductAttribute()
 *
 * **************************************
 * v1.0.4                      Can Berkol
 * 22.05.2014
 * **************************************
 * A updateStocks()
 *
 * **************************************
 * v1.0.3                      Can Berkol
 * 20.05.2014
 * **************************************
 * A listStocksOfProduct()
 * A listStocksOfProductFromSupplier(
 *
 * **************************************
 * v1.0.2                      Said İmamoğlu
 * 18.05.2014
 * **************************************
 * A doesStockExistWithQuantity()
 *
 * **************************************
 * v1.0.1                      Said İmamoğlu
 * 21.03.2014
 * **************************************
 * A deleteSupplier()
 * A deleteSuppliers()
 * A doesSupplierExist()
 * A getSupplier()
 * A listSuppliers()
 * A insertSupplier()
 * A insertSuppliers()
 * A updateSupplier()
 * A updateSuppliers()
 * **************************************
 * v1.0.0                      Said İmamoğlu
 * 19.03.2014
 * **************************************
 * A deleteStock()
 * A deleteStocks()
 * A doesStockExist()
 * A getStock()
 * A listStocks()
 * A insertStock()
 * A insertStocks()
 * A updateStock()
 * A updateStocks()
 *
 */
