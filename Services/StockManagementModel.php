<?php
/**
 * @vendor      BiberLtd
 * @package     StockManagementBundle
 * @subpackage  Services
 *
 * @name        StockManagementModel
 *
 * @author      Can Berkol
 * @author      Said İmamoğlu
 *
 * @copyright   Biber Ltd. (www.biberltd.com)
 *
 * @version     1.1.1
 * @date        23.07.2015
 */

namespace BiberLtd\Bundle\StockManagementBundle\Services;

/** Extends CoreModel */
use BiberLtd\Bundle\CoreBundle\CoreModel;
/** Entities to be used */
use BiberLtd\Bundle\CoreBundle\Responses\ModelResponse;
use BiberLtd\Bundle\StockManagementBundle\Entity as BundleEntity;
use BiberLtd\Bundle\ProductManagementBundle\Entity as ProductEntity;
/** Core Service */
use BiberLtd\Bundle\CoreBundle\Services as CoreServices;
use BiberLtd\Bundle\CoreBundle\Exceptions as CoreExceptions;

class StockManagementModel extends CoreModel{
	public $entity = array(
		'pa' => array('name' => 'ProductManagementBundle:ProductAttribute', 'alias' => 'pa'),
		's' => array('name' => 'StockManagementBundle:Stock', 'alias' => 's'),
		'sav' => array('name' => 'StockManagementBundle:StockAttributeValue', 'alias' => 'sav'),
		'sup' => array('name' => 'StockManagementBundle:Supplier', 'alias' => 'sup'),
	);

	/**
	 * @name            deleteAllAttributeValuesOfStockAttribute ()
	 *
	 * @since           1.0.6
	 * @version         1.0.6
	 * @author          Can Berkol
	 *
	 * @param           mixed           $attribute
	 * @param           mixed           $stock
	 *
	 * @return          \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function deleteAllAttributeValuesOfStockAttribute($attribute, $stock){
		$timeStamp = time();
		$pModel = $this->get('productmanagement.model');
		$response = $pModel->getProductAttribute($attribute);
		if($response->error->exist){
			return $response;
		}
		$attribute = $response->result->set;
		unset($response);
		$response = $this->getStock($stock);
		if($response->error->exist){
			return $response;
		}
		$stock = $response->result->set;
		unset($response);
		$qStr = 'DELETE FROM ' . $this->entity['sav']['name'] . ' ' . $this->entity['sav']['alias']
			. ' WHERE ' . $this->entity['sav']['alias'] . '.attribute = ' . $attribute->getId()
			. ' AND ' . $this->entity['sav']['alias'] . '.stock = ' . $stock->getId();
		$q = $this->em->createQuery($qStr);
		$result = $q->getResult();
		if($result === false){
			return new ModelResponse(null, 0, 0, null, true, 'E:E:001', 'Unable to delete all or some of the selected entries.', $timeStamp, time());
		}

		return new ModelResponse(null, 0, 0, null, false, 'S:D:001', 'Selected entries have been successfully removed from database.', $timeStamp, time());
	}
	/**
	 * @name            deleteStock ()
	 *
	 * @since           1.0.0
	 * @version         1.0.6
	 *
	 * @author          Can Berkol
	 * @author          Said İmamoğlu
	 *
	 * @use             $this->deleteStocks()
	 *
	 * @param           mixed           $stock
	 *
	 * @return          \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function deleteStock($stock){
		return $this->deleteStocks(array($stock));
	}

	/**
	 * @name            deleteStocks ()
	 *
	 * @since           1.0.0
	 * @version         1.0.6
	 *
	 * @author          Can Berkol
	 * @author          Said İmamoğlu
	 *
	 * @use             $this->createException()
	 *
	 * @param           array           $collection
	 *
	 * @return          \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function deleteStocks($collection) {
		$timeStamp = time();
		if (!is_array($collection)) {
			return $this->createException('InvalidParameterValueException', 'Invalid parameter value. Parameter must be an array collection', 'E:S:001');
		}
		$countDeleted = 0;
		foreach($collection as $entry){
			if($entry instanceof BundleEntity\Stock){
				$this->em->remove($entry);
				$countDeleted++;
			}
			else{
				$response = $this->getStock($entry);
				if(!$response->error->exist){
					$entry = $response->result->set;
					$this->em->remove($entry);
					$countDeleted++;
				}
			}
		}
		if($countDeleted < 0){
			return new ModelResponse(null, 0, 0, null, true, 'E:E:001', 'Unable to delete all or some of the selected entries.', $timeStamp, time());
		}
		$this->em->flush();

		return new ModelResponse(null, 0, 0, null, false, 'S:D:001', 'Selected entries have been successfully removed from database.', $timeStamp, time());
	}

	/**
	 * @name            listStocks ()
	 *
	 * @version         1.0.0
	 * @since           1.0.6
	 *
	 * @author          Can Berkol
	 * @author          Said İmamoğlu
	 *
	 * @param           array       $filter
	 * @param           array       $sortOrder
	 * @param           array       $limit
	 *
	 * @return          \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function listStocks($filter = null, $sortOrder = null, $limit = null){
		$timeStamp = time();
		if (!is_array($sortOrder) && !is_null($sortOrder)) {
			return $this->createException('InvalidSortOrderException', '$sortOrder must be an array with key => value pairs where value can only be "asc" or "desc".', 'E:S:002');
		}
		$oStr = $wStr = $gStr = $fStr = '';

		$qStr = 'SELECT '.$this->entity['s']['alias']
			.' FROM '.$this->entity['s']['name'].' '.$this->entity['s']['alias'];

		if (!is_null($sortOrder)) {
			foreach ($sortOrder as $column => $direction) {
				switch ($column) {
					case 'id':
					case 'sku':
					case 'supplier_sku':
					case 'quantity':
					case 'price':
					case 'discount_price':
					case 'sort_order':
					case 'date_added':
					case 'date_updated':
					case 'date_removed':
						$column = $this->entity['s']['alias'].'.'.$column;
						break;
					default:
						continue;
				}
				$oStr .= ' '.$column.' '.strtoupper($direction).', ';
			}
			$oStr = rtrim($oStr, ', ');
			$oStr = ' ORDER BY '.$oStr.' ';
		}

		if (!is_null($filter)) {
			$fStr = $this->prepareWhere($filter);
			$wStr .= ' WHERE '.$fStr;
		}

		$qStr .= $wStr.$gStr.$oStr;

		$query = $this->em->createQuery($qStr);
		$query = $this->addLimit($query, $limit);

		$result = $query->getResult();

		$totalRows = count($result);
		if ($totalRows < 1) {
			return new ModelResponse(null, 0, 0, null, true, 'E:D:002', 'No entries found in database that matches to your criterion.', $timeStamp, time());
		}
		return new ModelResponse($result, $totalRows, 0, null, false, 'S:D:002', 'Entries successfully fetched from database.', $timeStamp, time());
	}
	/**
	 * @name            listStocksOfProduct()
	 *
	 * @author          Can Berkol
	 * @version         1.0.3
	 * @since           1.0.6
	 *
	 * @param           mixed       $product
	 * @param           array       $sortOrder
	 * @param           array       $limit
	 *
	 * @return          \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function listStocksOfProduct($product, $sortOrder = null, $limit = null){
		$timeStamp = time();
		$pModel = $this->kernel->getContainer()->get('productmanagement.model');
		$response = $pModel->getProduct($product);
		if($response->error->exist){
			return $response;
		}
		$product = $response->result->set;
		unset($response);

		$column = $this->entity['s']['alias'].'.product';
		$condition = array('column' => $column, 'comparison' => 'eq', 'value' => $product->getId());
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
	 *
	 * @author          Can Berkol
	 * @version         1.0.3
	 * @since           1.0.6
	 *
	 * @param           mixed       $product
	 * @param           mixed       $supplier
	 * @param           array       $sortOrder
	 * @param           array       $limit
	 *
	 * @return          \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function listStocksOfProductFromSupplier($product, $supplier, $sortOrder = null, $limit = null){
		$timeStamp = time();
		$pModel = $this->kernel->getContainer()->get('productmanagement.model');
		$response = $pModel->getProduct($product);
		if($response->error->exist){
			return $response;
		}
		$product = $response->result->set;
		$column = $this->entity['s']['alias'] . '.product';
		$condition = array('column' => $column, 'comparison' => 'eq', 'value' => $product->getId());
		$filter[] = array(
			'glue' => 'and',
			'condition' => array(
				array(
					'glue' => 'and',
					'condition' => $condition,
				)
			)
		);
		$column = $this->entity['s']['alias'] . '.supplier';
		$response = $pModel->getSupplier($product);
		if($response->error->exist){
			return $response;
		}
		$supplier = $response->result->set;
		$condition = array('column' => $column, 'comparison' => 'eq', 'value' => $supplier->getId());
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
	 *
	 * @since           1.0.5
	 * @version         1.0.6
	 * @author          Can Berkol
	 *
	 * @param           mixed           $attribute
	 * @param           mixed           $stock
	 * @param           mixed           $language
	 *
	 * @return          \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function getAttributeValueOfStock($attribute, $stock, $language){
		$timeStamp = time();
		$pModel = $this->kernel->getContainer()->get('productmanagement.model');
		$response = $pModel->getProductAttribute($attribute);
		if($response->error->exist){
			return $response;
		}
		$attribute = $response->result->set;
		unset($response);
		$response = $this->getStock($stock);
		if($response->error->exist){
			return $response;
		}
		$stock = $response->result->set;
		unset($response);
		$mModel = $this->kernel->getContainer()->get('multilanguagesupport.model');
		$response = $mModel->getLanguage($language);
		if($response->error->exist){
			return $response;
		}
		$language = $response->result->set;
		unset($response);
		$q_str = 'SELECT DISTINCT ' . $this->entity['sav']['alias'] . ', ' . $this->entity['pa']['alias']
			. ' FROM ' . $this->entity['sav']['name'] . ' ' . $this->entity['sav']['alias']
			. ' JOIN ' . $this->entity['sav']['alias'] . '.attribute ' . $this->entity['pa']['alias']
			. ' WHERE ' . $this->entity['sav']['alias'] . '.stock = ' . $stock->getId()
			. ' AND ' . $this->entity['sav']['alias'] . '.language = ' . $language->getId()
			. ' AND ' . $this->entity['sav']['alias'] . '.attribute = ' . $attribute->getId();

		$query = $this->em->createQuery($q_str);
		$query->setMaxResults(1);
		$query->setFirstResult(0);
		$result = $query->getResult();
		$totalRows = count($result);
		if ($totalRows < 1) {
			return new ModelResponse(null, 0, 0, null, true, 'E:D:002', 'No entries found in database that matches to your criterion.', $timeStamp, time());
		}
		return new ModelResponse($result[0], $totalRows, 0, null, false, 'S:D:002', 'Entries successfully fetched from database.', $timeStamp, time());
	}
	/**
	 * @name            getStock ()
	 *
	 * @since           1.0.0
	 * @version         1.0.6
	 *
	 * @author          Can Berkol
	 * @author          Said İmamoğlu
	 *
	 * @param           mixed       $stock
	 *
	 * @return          \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function getStock($stock){
		$timeStamp = time();
		if($stock instanceof BundleEntity\Stock){
			return new ModelResponse($stock, 1, 0, null, false, 'S:D:002', 'Entries successfully fetched from database.', $timeStamp, time());
		}
		$result = null;
		switch($stock){
			case is_numeric($stock):
				$result = $this->em->getRepository($this->entity['s']['name'])->findOneBy(array('id' => $stock));
				break;
			case is_string($stock):
				$result = $this->em->getRepository($this->entity['s']['name'])->findOneBy(array('url_key' => $stock));
				if(is_null($result)){
					$result = $this->em->getRepository($this->entity['s']['name'])->findOneBy(array('sku' => $stock));
				}
				if(is_null($result)){
					$result = $this->em->getRepository($this->entity['s']['name'])->findOneBy(array('supplier_sku' => $stock));
				}
				break;
		}
		if(is_null($result)){
			return new ModelResponse($result, 0, 0, null, true, 'E:D:002', 'Unable to find request entry in database.', $timeStamp, time());
		}

		return new ModelResponse($result, 1, 0, null, false, 'S:D:002', 'Entries successfully fetched from database.', $timeStamp, time());
	}
	/**
	 * @name            doesStockAttributeValueExist()
	 *
	 * @since           1.0.5
	 * @version         1.0.6
	 *
	 * @author          Can Berkol
	 *
	 * @use             $this->getAttributeValueOfStock()
	 *
	 * @param           mixed       $attribute
	 * @param           mixed       $stock
	 * @param           mixed       $language
	 * @param           bool        $bypass
	 *
	 * @return          \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function doesStockAttributeValueExist($attribute, $stock, $language, $bypass = false){
		$timeStamp = time();
		$exist = false;
		$response = $this->getAttributeValueOfStock($attribute, $stock, $language);
		if ($response->error->exist) {
			if($bypass){
				return $exist;
			}
			$response->result->set = false;
			return $response;
		}

		$exist = true;

		if ($bypass) {
			return $exist;
		}
		return new ModelResponse(true, 1, 0, null, false, 'S:D:002', 'Entries successfully fetched from database.', $timeStamp, time());
	}
	/**
	 * @name        doesStockExist ()
	 *
	 * @since           1.0.0
	 * @version         1.0.6
	 *
	 * @author          Can Berkol
	 * @author          Said İmamoğlu
	 *
	 * @use             $this->getStock()
	 *
	 * @param           mixed       $item
	 * @param           bool        $bypass
	 *
	 * @return          \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function doesStockExist($item, $bypass = false){
		$timeStamp = time();
		$exist = false;

		$response = $this->getStock($item);

		if ($response->error->exist) {
			if($bypass){
				return $exist;
			}
			$response->result->set = false;
			return $response;
		}

		$exist = true;

		if ($bypass) {
			return $exist;
		}
		return new ModelResponse(true, 1, 0, null, false, 'S:D:002', 'Entries successfully fetched from database.', $timeStamp, time());
	}

	/**
	 * @name            insertStock ()
	 *
	 * @since           1.0.1
	 * @version         1.0.6
	 *
	 * @author          Can Berkol
	 * @author          Said İmamoğlu
	 *
	 * @use             $this->insertStocks()
	 *
	 * @param           array           $stock
	 *
	 * @return          \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function insertStock($stock){
		return $this->insertStocks(array($stock));
	}

	/**
	 * @name            insertStocks ()
	 *
	 * @since           1.0.1
	 * @version         1.0.6
	 *
	 * @author          Can Berkol
	 * @author          Said İmamoğlu
	 *
	 * @param           array           $collection
	 *
	 * @return          \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function insertStocks($collection){
		$timeStamp = time();
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
							if ($response->error->exist) {
								return $response;
							}
							$entity->$set($response->result->set);
							unset($response, $productModel);
							break;
						case 'supplier':
							$response = $this->getSupplier($value);
							if ($response->error->exist) {
								return $response;
							}
							$entity->$set($response->result->set);
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
			}
		}
		if($countInserts > 0){
			$this->em->flush();
			return new ModelResponse($insertedItems, $countInserts, 0, null, false, 'S:D:003', 'Selected entries have been successfully inserted into database.', $timeStamp, time());
		}
		return new ModelResponse(null, 0, 0, null, true, 'E:D:003', 'One or more entities cannot be inserted into database.', $timeStamp, time());
	}

	/**
	 * @name            updateStock()
	 *
	 * @since           1.0.0
	 * @version         1.0.6
	 *
	 * @author          Can Berkol
	 * @author          Said İmamoğlu
	 *
	 * @param           mixed   $stock
	 *
	 * @return          \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 *
	 */

	public function updateStock($stock){
		return $this->updateStocks(array($stock));
	}

	/**
	 * @name            updateStocks()
	 *
	 * @since           1.0.0
	 * @version         1.0.6
	 *
	 * @author          Can Berkol
	 * @author          Said İmamoğlu
	 *
	 * @param           array   $collection
	 *
	 * @return          \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 *
	 */
	public function updateStocks($collection){
		$timeStamp = time();
		$countUpdates = 0;
		foreach ($collection as $data) {
			if ($data instanceof BundleEntity\Stock) {
				$entity = $data;
				$this->em->persist($entity);
				$updatedItems[] = $entity;
				$countUpdates++;
			} else if (is_object($data)) {
				$response = $this->getStock($data->id,'id');
				if ($response->error->exist) {
					return $response;
				}
				$oldEntity = $response->result->set;

				foreach ($data as $column => $value) {
					$set = 'set' . $this->translateColumnName($column);
					switch ($column) {
						case 'product':
							$productModel = $this->kernel->getContainer()->get('productmanagement.model');
							$response = $productModel->getProduct($value);
							if ($response->error->exist) {
								return $response;
							}
							$oldEntity->$set($response->result->set);
							unset($response, $productModel);
							break;
						case 'supplier':
							$response = $this->getSupplier($value);
							if ($response->error->exist) {
								return $response;
							}
							$oldEntity->$set($response->result->set);
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
			}
		}

		if($countUpdates > 0){
			$this->em->flush();
			return new ModelResponse($updatedItems, $countUpdates, 0, null, false, 'S:D:004', 'Selected entries have been successfully updated within database.', $timeStamp, time());
		}
		return new ModelResponse(null, 0, 0, null, true, 'E:D:004', 'One or more entities cannot be updated within database.', $timeStamp, time());
	}

	/**
	 * @name            deleteSupplier ()
	 *
	 * @since           1.0.1
	 * @version         1.0.6
	 *
	 * @author          Can Berkol
	 * @author          Said İmamoğlu
	 *
	 * @use             $this->deleteSuppliers()
	 *
	 * @param           mixed           $item
	 *
	 * @return          \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function deleteSupplier($item){
		return $this->deleteSuppliers(array($item));
	}

	/**
	 * @name            deleteSuppliers ()
	 *
	 * @since           1.0.0
	 * @version         1.0.6
	 *
	 * @author          Can Berkol
	 * @author          Said İmamoğlu
	 *
	 * @use             $this->createException()
	 *
	 * @param           array           $collection
	 *
	 * @return          \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function deleteSuppliers($collection){
		$timeStamp = time();
		if (!is_array($collection)) {
			return $this->createException('InvalidParameterValueException', 'Invalid parameter value. Parameter must be an array collection', 'E:S:001');
		}
		$countDeleted = 0;
		foreach($collection as $entry){
			if($entry instanceof BundleEntity\Supplier){
				$this->em->remove($entry);
				$countDeleted++;
			}
			else{
				$response = $this->getSupplier($entry);
				if(!$response->error->exist){
					$entry = $response->result->set;
					$this->em->remove($entry);
					$countDeleted++;
				}
			}
		}
		if($countDeleted < 0){
			return new ModelResponse(null, 0, 0, null, true, 'E:E:001', 'Unable to delete all or some of the selected entries.', $timeStamp, time());
		}
		$this->em->flush();

		return new ModelResponse(null, 0, 0, null, false, 'S:D:001', 'Selected entries have been successfully removed from database.', $timeStamp, time());
	}

	/**
	 * @name            listSuppliers ()
	 *
	 * @version         1.0.1
	 * @since           1.0.6
	 *
	 * @author          Can Berkol
	 * @author          Said İmamoğlu
	 *
	 * @param           array   $filter
	 * @param           array   $sortOrder
	 * @param           array   $limit
	 *
	 * @return          \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function listSuppliers($filter = null, $sortOrder = null, $limit = null){
		$timeStamp = time();
		if (!is_array($sortOrder) && !is_null($sortOrder)) {
			return $this->createException('InvalidSortOrderException', '$sortOrder must be an array with key => value pairs where value can only be "asc" or "desc".', 'E:S:002');
		}
		$oStr = $wStr = $gStr = $fStr = '';

		$qStr = 'SELECT '.$this->entity['s']['alias']
			.' FROM '.$this->entity['s']['name'].' '.$this->entity['s']['alias'];

		if (!is_null($sortOrder)) {
			foreach ($sortOrder as $column => $direction) {
				switch ($column) {
					case 'name':
					case 'url_key':
						$column = $this->entity['sup']['alias'].'.'.$column;
						break;
					default:
						break;
				}
				$oStr .= ' '.$column.' '.strtoupper($direction).', ';
			}
			$oStr = rtrim($oStr, ', ');
			$oStr = ' ORDER BY '.$oStr.' ';
		}

		if (!is_null($filter)) {
			$fStr = $this->prepareWhere($filter);
			$wStr .= ' WHERE '.$fStr;
		}

		$qStr .= $wStr.$gStr.$oStr;

		$query = $this->em->createQuery($qStr);
		$query = $this->addLimit($query, $limit);

		$result = $query->getResult();

		$totalRows = count($result);
		if ($totalRows < 1) {
			return new ModelResponse(null, 0, 0, null, true, 'E:D:002', 'No entries found in database that matches to your criterion.', $timeStamp, time());
		}
		return new ModelResponse($result, $totalRows, 0, null, false, 'S:D:002', 'Entries successfully fetched from database.', $timeStamp, time());
	}
	/**
	 * @name            getStockAttributeValue ()
	 *
	 * @since           1.0.5
	 * @version         1.0.6
	 * @author          Can Berkol
	 *
	 * @use             $this->createException()
	 * @use             $this->resetResponse()
	 *
	 * @param           integer         $id
	 *
	 * @return          \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function getStockAttributeValue($id){
		$timeStamp = time();

		$result = $this->em->getRepository($this->entity['sav']['name'])
			->findOneBy(array('id' => $id));

		if(is_null($result)){
			return new ModelResponse($result, 0, 0, null, true, 'E:D:002', 'Unable to find request entry in database.', $timeStamp, time());
		}

		return new ModelResponse($result, 1, 0, null, false, 'S:D:002', 'Entries successfully fetched from database.', $timeStamp, time());
	}
	/**
	 * @name        getSupplier ()
	 *
	 * @since           1.0.1
	 * @version         1.0.6
	 *
	 * @author          Can Berkol
	 * @author          Said İmamoğlu
	 *
	 * @param           mixed           $supplier
	 *
	 * @return          \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function getSupplier($supplier){
		$timeStamp = time();
		if($supplier instanceof BundleEntity\Supplier){
			return new ModelResponse($supplier, 1, 0, null, false, 'S:D:002', 'Entries successfully fetched from database.', $timeStamp, time());
		}
		$result = null;
		switch($supplier){
			case is_numeric($supplier):
				$result = $this->em->getRepository($this->entity['s']['name'])->findOneBy(array('id' => $supplier));
				break;
			case is_string($supplier):
				$result = $this->em->getRepository($this->entity['s']['name'])->findOneBy(array('url_key' => $supplier));
				break;
		}
		if(is_null($result)){
			return new ModelResponse($result, 0, 0, null, true, 'E:D:002', 'Unable to find request entry in database.', $timeStamp, time());
		}

		return new ModelResponse($result, 1, 0, null, false, 'S:D:002', 'Entries successfully fetched from database.', $timeStamp, time());
	}
	/**
	 * @name            listStockAttributeValues ()
	 *
	 * @since           1.0.5
	 * @version         1.0.6
	 * @author          Can Berkol
	 *
	 * @use             $this->createException()
	 *
	 * @param           array           $filter
	 * @param           array           $sortOrder
	 * @param           array           $limit
	 *
	 * @return          \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function listStockAttributeValues($filter = null, $sortOrder = null, $limit = null){
		$timeStamp = time();
		if (!is_array($sortOrder) && !is_null($sortOrder)) {
			return $this->createException('InvalidSortOrderException', '$sortOrder must be an array with key => value pairs where value can only be "asc" or "desc".', 'E:S:002');
		}
		$oStr = $wStr = $gStr = $fStr = '';

		$qStr = 'SELECT ' . $this->entity['sav']['alias']
			. ' FROM ' . $this->entity['sav']['name'] . ' ' . $this->entity['sav']['alias'];

		if ($sortOrder != null) {
			foreach ($sortOrder as $column => $direction) {
				switch ($column) {
					case 'id':
					case 'sort_order':
					case 'date_added':
						$column = $this->entity['sav']['alias'] . '.' . $column;
						break;
				}
				$oStr .= ' ' . $column . ' ' . strtoupper($direction) . ', ';
			}
			$oStr = rtrim($oStr, ', ');
			$oStr = ' ORDER BY ' . $oStr . ' ';
		}
		/**
		 * Prepare WHERE section of query.
		 */
		if ($filter != null) {
			$fStr = $this->prepareWhere($filter);
			$wStr .= ' WHERE ' . $fStr;
		}
		$qStr .= $wStr . $gStr . $oStr;

		/** @var \Doctrine\ORM\Query $q */
		$q = $this->em->createQuery($qStr);

		if (!empty($limit)) {
			$q->setMaxResults($limit['count']);
			$q->setFirstResult($limit['start']);
		}
		/**
		 * Prepare & Return Response
		 */
		$result = $q->getResult();

		$totalRows = count($result);
		if ($totalRows < 1) {
			return new ModelResponse(null, 0, 0, null, true, 'E:D:002', 'No entries found in database that matches to your criterion.', $timeStamp, time());
		}
		return new ModelResponse($result, $totalRows, 0, null, false, 'S:D:002', 'Entries successfully fetched from database.', $timeStamp, time());

	}
	/**
	 * @name            listStockAttributeValuesOfProduct()
	 *
	 * @since           1.0.9
	 * @version         1.0.9
	 *
	 * @author          Said İmamoğlu
	 *
	 * @param           array           $product
	 * @param           array           $filter
	 * @param           array           $sortOrder
	 * @param           array           $limit
	 *
	 * @return          array           $response
	 */
	public function listStockAttributeValuesOfProduct($product, $filter = null, $sortOrder = null, $limit = null){
		$pModel = $this->kernel->getContainer()->get('productManagement.model');
		$response = $pModel->getProduct($product);
		if($response->error->exist){
			return $response;
		}
		$product= $response->result->set;
		$sopResponse = $this->listStocksOfProduct($product, $sortOrder);
		if ($sopResponse->error->exist) {
			return $sopResponse;
		}
		foreach ($sopResponse->result->set as $sopEntity) {
			$stockIds[] = $sopEntity->getId();
		}
		unset($response);
		$filter[] = array(
			'glue' => 'and',
			'condition' => array(
				array(
					'glue' => 'and',
					'condition' => array('column' => $this->entity['sav']['alias'] . '.stock', 'comparison' => 'in', 'value' => $stockIds),
				)
			)
		);
		return $this->listStockAttributeValues($filter, $sortOrder, $limit);
	}
	/**
	 * @name            listStockAttributeValuesOfStock ()
	 *
	 * @since           1.0.5
	 * @version         1.0.6
	 *
	 * @author          Can Berkol
	 * @author          Said İmamoğlu
	 *
	 * @param           array           $stock
	 * @param           array           $filter
	 * @param           array           $sortOrder
	 * @param           array           $limit
	 *
	 * @return          array           $response
	 */
	public function listStockAttributeValuesOfStock($stock, $filter = null, $sortOrder = null, $limit = null){
		$pModel = $this->kernel->getContainer()->get('productManagement.model');
		$response = $this->getStock($stock);
		if($response->error->exist){
			return $response;
		}
		$stock = $response->result->set;
		unset($response);
		$filter[] = array(
			'glue' => 'and',
			'condition' => array(
				array(
					'glue' => 'and',
					'condition' => array('column' => $this->entity['sav']['alias'] . '.stock', 'comparison' => '=', 'value' => $stock->getId()),
				)
			)
		);
		return $this->listStockAttributeValues($filter, $sortOrder, $limit);
	}
	/**
	 * @name            doesSupplierExist ()
	 *
	 * @since           1.0.1
	 * @version         1.0.6
	 *
	 * @author          Can Berkol
	 * @author          Said İmamoğlu
	 *
	 * @param           mixed       $item
	 * @param           bool        $bypass
	 *
	 * @return          mixed       $response
	 */
	public function doesSupplierExist($item, $bypass = false){
		$timeStamp = time();
		$exist = false;

		$response = $this->getStock($item);

		if ($response->error->exist) {
			if($bypass){
				return $exist;
			}
			$response->result->set = false;
			return $response;
		}

		$exist = true;

		if ($bypass) {
			return $exist;
		}
		return new ModelResponse(true, 1, 0, null, false, 'S:D:002', 'Entries successfully fetched from database.', $timeStamp, time());
	}
	/**
	 * @name            insertStockAttributeValue()
	 *
	 * @since           1.0.5
	 * @version         1.0.6
	 *
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
	 * @name            insertStockAttributeValues()
	 *
	 * @since           1.0.8
	 * @version         1.1.1
	 *
	 * @author          Can Berkol
	 *
	 * @param           array           $collection
	 *
	 * @return          array           $response
	 */
	public function insertStockAttributeValues($collection){
		$timeStamp = time();
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
							$response = $lModel->getLanguage($value);
							if ($response->error->exist) {
								return $response;
							}
							$entity->$set($response->result->set);
							unset($response, $sModel);
							break;
						case 'attribute':
							$pModel = $this->kernel->getContainer()->get('productmanagement.model');
							$response = $pModel->getProductAttribute($value);
							if ($response->error->exist) {
								return $response;
							}
							$entity->$set($response->result->set);
							unset($response, $sModel);
							break;
						case 'stock':
							$response = $this->getStock($value);
							if ($response->error->exist) {
								return $response;
							}
							$entity->$set($response->result->set);
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
			}
		}
		if($countInserts > 0){
			$this->em->flush();
			return new ModelResponse($insertedItems, $countInserts, 0, null, false, 'S:D:003', 'Selected entries have been successfully inserted into database.', $timeStamp, time());
		}
		return new ModelResponse(null, 0, 0, null, true, 'E:D:003', 'One or more entities cannot be inserted into database.', $timeStamp, time());
	}


	/**
	 * @name            insertSupplier()
	 *
	 * @since           1.0.1
	 * @version         1.0.6
	 *
	 * @author          Can Berkol
	 * @author          Said İmamoğlu
	 *
	 * @use             $this->insertSuppliers()
	 *
	 * @param           array           $item
	 *
	 * @return          array           $response
	 */

	public function insertSupplier($item){
		return $this->insertSuppliers(array($item));
	}

	/**
	 * @name            insertSuppliers ()
	 *
	 * @since           1.0.1
	 * @version         1.0.6
	 *
	 * @author          Can Berkol
	 * @author          Said İmamoğlu
	 *
	 * @param           array           $collection
	 *
	 * @return          array           $response
	 */

	public function insertSuppliers($collection){
		$timeStamp = time();
		if (!is_array($collection)) {
			return $this->createException('InvalidParameterValueException', 'Invalid parameter value. Parameter must be an array collection', 'E:S:001');
		}
		$countInserts = 0;
		$insertedItems = array();
		foreach($collection as $data){
			if($data instanceof BundleEntity\Supplier){
				$entity = $data;
				$this->em->persist($entity);
				$insertedItems[] = $entity;
				$countInserts++;
			}
			else if(is_object($data)){
				$entity = new BundleEntity\Supplier();
				foreach($data as $column => $value){
					$set = 'set'.$this->translateColumnName($column);
					switch($column){
						default:
							$entity->$set($value);
							break;
					}
				}
				$this->em->persist($entity);
				$insertedItems[] = $entity;
				$countInserts++;
			}
		}
		if($countInserts > 0){
			$this->em->flush();
			return new ModelResponse($insertedItems, $countInserts, 0, null, false, 'S:D:003', 'Selected entries have been successfully inserted into database.', $timeStamp, time());
		}
		return new ModelResponse(null, 0, 0, null, true, 'E:D:003', 'One or more entities cannot be inserted into database.', $timeStamp, time());
	}
	/**
	 * @name            updateStockAttributeValue ()
	 *
	 * @since           1.0.5
	 * @version         1.0.6
	 *
	 * @author          Can Berkol
	 *
	 * @use             $this->updateStockAttributeValues()
	 *
	 * @param           mixed           $data
	 *
	 * @return          mixed           $response
	 */
	public function updateStockAttributeValue($data){
		return $this->updateStockAttributeValues(array($data));
	}

	/**
	 * @name            updateProductAttributeValues ()
	 *
	 * @since           1.0.5
	 * @version         1.0.6
	 *
	 * @author          Can Berkol
	 *
	 * @use             $this->createException()
	 *
	 * @param           array           $collection
	 *
	 * @return          array           $response
	 */
	public function updateStockAttributeValues($collection){
		$timeStamp = time();
		if (!is_array($collection)) {
			return $this->createException('InvalidParameterValueException', 'Invalid parameter value. Parameter must be an array collection', 'E:S:001');
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
				if ($response->error->exist) {
					return $response;
				}
				$oldEntity = $response->result->set;

				foreach ($data as $column => $value) {
					$set = 'set' . $this->translateColumnName($column);
					switch ($column) {
						case 'attribute':
							$pModel = $this->kernel->getContainer()->get('productManagement.model');
							$response = $pModel->getProductAttribute($value);
							if ($response->error->exist) {
								return $response;
							}
							$oldEntity->$set($response->result->set);
							unset($response, $pModel);
							break;
						case 'language':
							$lModel = $this->kernel->getContainer()->get('multilanguagesupport.model');
							$response = $lModel->getLanguage($value);
							if ($response->error->exist) {
								return $response;
							}
							$oldEntity->$set($response->result->set);
							unset($response, $lModel);
							break;
						case 'stock':
							$response = $this->getStock($value);
							if ($response->error->exist) {
								return $response;
							}
							$oldEntity->$set($response->result->set);
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
			}
		}
		if($countUpdates > 0){
			$this->em->flush();
			return new ModelResponse($updatedItems, $countUpdates, 0, null, false, 'S:D:004', 'Selected entries have been successfully updated within database.', $timeStamp, time());
		}
		return new ModelResponse(null, 0, 0, null, true, 'E:D:004', 'One or more entities cannot be updated within database.', $timeStamp, time());
	}
	/**
	 * @name            updateSupplier()
	 *
	 * @since           1.0.1
	 * @version         1.0.6
	 *
	 * @author          Can Berkol
	 * @author          Said İmamoğlu
	 *
	 * @param           mixed   $item
	 *
	 * @return          array   $response
	 *
	 */
	public function updateSupplier($item){
		return $this->updateSuppliers(array($item));
	}

	/**
	 * @name            updateSuppliers()
	 *
	 * @since           1.0.1
	 * @version         1.0.6
	 *
	 * @author          Can Berkol
	 * @author          Said İmamoğlu
	 *
	 * @param           array   $collection
	 *
	 * @return          array   $response
	 *
	 */
	public function updateSuppliers($collection){
		$timeStamp = time();
		if (!is_array($collection)) {
			return $this->createException('InvalidParameterValueException', 'Invalid parameter value. Parameter must be an array collection', 'E:S:001');
		}
		$countUpdates = 0;
		foreach ($collection as $data) {
			if ($data instanceof BundleEntity\Supplier) {
				$entity = $data;
				$this->em->persist($entity);
				$updatedItems[] = $entity;
				$countUpdates++;
			} else if (is_object($data)) {
				$response = $this->getSupplier($data->id,'id');
				if ($response->error->exist){
					return $response;
				}
				$oldEntity = $response->result->set;
				foreach ($data as $column => $value) {
					if ($column !== 'id') {
						$set = 'set' . $this->translateColumnName($column);
						$oldEntity->$set($value);
					}
				}
				$this->em->persist($oldEntity);
				$updatedItems[] = $oldEntity;
				$countUpdates++;
			}
		}
		if($countUpdates > 0){
			$this->em->flush();
			return new ModelResponse($updatedItems, $countUpdates, 0, null, false, 'S:D:004', 'Selected entries have been successfully updated within database.', $timeStamp, time());
		}
		return new ModelResponse(null, 0, 0, null, true, 'E:D:004', 'One or more entities cannot be updated within database.', $timeStamp, time());
	}

	/**
	 * @name            doesStockExistWithQuantity ()
	 *
	 * @since           1.0.2
	 * @version         1.0.6
	 *
	 * @author          Can Berkol
	 * @author          Said İmamoğlu
	 *
	 * @use             $this->listStocks()
	 *
	 *
	 * @param           mixed   $stock
	 * @param           int     $quantity
	 * @param           bool    $bypass
	 *
	 * @return          mixed   $response
	 */
	public function doesStockExistWithQuantity($stock, $quantity, $bypass = false){
		$timeStamp = time();
		$pModel = $this->get('productmanagement.model');
		$response = $pModel->getStock($stock);
		if($response->error->exist){
			return $response;
		}
		$stock = $response->result->set;
		$column = $this->getEntityDefinition('s', 'alias') . '.stock';
		$filter = array();
		$filter[] = array(
			'glue' => 'and',
			'condition' => array(
				array(
					'glue' => 'and',
					'condition' => array('column' => $column, 'comparison' => '=', 'value' => $stock->getId()),
				),
				array(
					'glue' => 'and',
					'condition' => array('column' => $this->getEntityDefinition('s', 'alias') . '.quantity', 'comparison' => '>=', 'value' => $quantity ),
				),
			)
		);
		$response = $this->listStocks($filter, array('quantity'=>'desc'), array('start'=>0,'count'=>1));
		$exist = false;

		if ($response->error->exist) {
			if($bypass){
				return $exist;
			}
			$response->result->set = false;
			return $response;
		}

		$exist = true;

		if ($bypass) {
			return $exist;
		}
		return new ModelResponse(true, 1, 0, null, false, 'S:D:002', 'Entries successfully fetched from database.', $timeStamp, time());
	}

}

/**
 * Change Log
 * **************************************
 * v1.1.1                      23.07.2015
 * Can Berkol
 * **************************************
 * BF :: insertStockAttributeValues() was returning early response, due to a wrong error check. Fixed.
 *
 * **************************************
 * v1.1.0                      21.07.2015
 * Can Berkol
 * **************************************
 * BF :: update methods fixes.
 *
 * **************************************
 * v1.0.9                      14.07.2015
 * Said İmamoğlu
 * **************************************
 * FR :: listStockAttributeValuesOfProduct() added.
 *
 * **************************************
 * v1.0.8                      13.07.2015
 * Can Berkol
 * **************************************
 * BF :: typo fixed. ->exists to ->exist
 * FR :: insertStockAttributeValues() added.
 *
 * **************************************
 * v1.0.7                      01.07.2015
 * Said İmamoğlu
 * **************************************
 * CR :: Updates and bugfixes compatible with Core 3.3.
 *
 * **************************************
 * v1.0.6                      24.06.2015
 * Can Berkol
 * **************************************
 * CR :: Made compatible with Core 3.3.
 *
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
