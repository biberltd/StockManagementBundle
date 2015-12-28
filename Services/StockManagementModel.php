<?php
/**
 * @author		Can Berkol
 *
 * @copyright   Biber Ltd. (http://www.biberltd.com) (C) 2015
 * @license     GPLv3
 *
 * @date        27.12.2015
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
	 * @param mixed $attribute
	 * @param mixed $stock
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
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
	 * @param mixed $stock
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function deleteStock($stock){
		return $this->deleteStocks(array($stock));
	}

	/**
	 * @param array $collection
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function deleteStocks(array $collection) {
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
	 * @param array|null $filter
	 * @param array|null $sortOrder
	 * @param array|null $limit
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function listStocks(array $filter = null, array $sortOrder = null, array $limit = null){
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
	 * @param mixed $product
	 * @param array|null $sortOrder
	 * @param array|null $limit
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function listStocksOfProduct($product, array $sortOrder = null, array $limit = null){
		/**
		 * @var \BiberLtd\Bundle\ProductManagementBundle\Services\ProductManagementModel $pModel
		 */
		$pModel = $this->kernel->getContainer()->get('productmanagement.model');
		$response = $pModel->getProduct($product);
		if($response->error->exist){
			return $response;
		}
		/**
		 * @var \BiberLtd\Bundle\ProductManagementBundle\Entity\Product
		 */
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
	 * @param mixed $product
	 * @param mixed $supplier
	 * @param array|null $sortOrder
	 * @param array|null $limit
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function listStocksOfProductFromSupplier($product, $supplier, array $sortOrder = null, array $limit = null){
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
		$response = $pModel->getSupplier($supplier);
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
	 * @param mixed $attribute
	 * @param mixed $stock
	 * @param mixed $language
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
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
	 * @param mixed $stock
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
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
	 * @param mixed $attribute
	 * @param mixed $stock
	 * @param mixed $language
	 * @param bool $bypass
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse|bool
	 */
	public function doesStockAttributeValueExist($attribute, $stock, $language, \bool $bypass = false){
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
	 * @param mixed $item
	 * @param bool $bypass
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse|bool
	 */
	public function doesStockExist($item, \bool $bypass = false){
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
	 * @param mixed $stock
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function insertStock($stock){
		return $this->insertStocks(array($stock));
	}

	/**
	 * @param array $collection
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function insertStocks(array $collection){
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
	 * @param mixed $stock
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function updateStock($stock){
		return $this->updateStocks(array($stock));
	}

	/**
	 * @param array $collection
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function updateStocks(array $collection){
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
	 * @param mixed $item
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function deleteSupplier($item){
		return $this->deleteSuppliers(array($item));
	}

	/**
	 * @param array $collection
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function deleteSuppliers(array $collection){
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
	 * @param array|null $filter
	 * @param array|null $sortOrder
	 * @param array|null $limit
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function listSuppliers(array $filter = null, array $sortOrder = null, array $limit = null){
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
	 * @param int $id
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function getStockAttributeValue(\integer $id){
		$timeStamp = time();

		$result = $this->em->getRepository($this->entity['sav']['name'])
			->findOneBy(array('id' => $id));

		if(is_null($result)){
			return new ModelResponse($result, 0, 0, null, true, 'E:D:002', 'Unable to find request entry in database.', $timeStamp, time());
		}

		return new ModelResponse($result, 1, 0, null, false, 'S:D:002', 'Entries successfully fetched from database.', $timeStamp, time());
	}

	/**
	 * @param mixed $supplier
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function getSupplier($supplier){
		$timeStamp = time();
		if($supplier instanceof BundleEntity\Supplier){
			return new ModelResponse($supplier, 1, 0, null, false, 'S:D:002', 'Entries successfully fetched from database.', $timeStamp, time());
		}
		$result = null;
		switch($supplier){
			case is_numeric($supplier):
				$result = $this->em->getRepository($this->entity['sup']['name'])->findOneBy(array('id' => $supplier));
				break;
			case is_string($supplier):
				$result = $this->em->getRepository($this->entity['sup']['name'])->findOneBy(array('url_key' => $supplier));
				break;
		}
		if(is_null($result)){
			return new ModelResponse($result, 0, 0, null, true, 'E:D:002', 'Unable to find request entry in database.', $timeStamp, time());
		}

		return new ModelResponse($result, 1, 0, null, false, 'S:D:002', 'Entries successfully fetched from database.', $timeStamp, time());
	}

	/**
	 * @param array|null $filter
	 * @param array|null $sortOrder
	 * @param array|null $limit
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function listStockAttributeValues(array $filter = null ,array $sortOrder = null, array $limit = null){
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
	 * @param mixed $product
	 * @param array|null $filter
	 * @param array|null $sortOrder
	 * @param array|null $limit
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function listStockAttributeValuesOfProduct($product, array $filter = null, array $sortOrder = null, array $limit = null){
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
	 * @param mixed $product
	 * @param mixed $language
	 * @param array|null $filter
	 * @param array|null $sortOrder
	 * @param array|null $limit
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function listStockAttributeValuesOfProductInLanguage($product, $language, array $filter = null, array $sortOrder = null, array $limit = null){
		$pModel = $this->kernel->getContainer()->get('productmanagement.model');
		$response = $pModel->getProduct($product);
		if($response->error->exist){
			return $response;
		}
		$product= $response->result->set;
		$lModel = $this->kernel->getContainer()->get('multilanguagesupport.model');
		$response = $lModel->getLanguage($language);
		if($response->error->exist){
			return $response;
		}
		$language= $response->result->set;
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
				),
				array(
					'glue'      => 'and',
					'condition' => array('column' => $this->entity['sav']['alias'] . '.language', 'comparison' => '=', 'value' => $language->getId()),
				)
			)
		);
		return $this->listStockAttributeValues($filter, $sortOrder, $limit);
	}
	/**
	 * @param mixed $stock
	 * @param array|null $filter
	 * @param array|null $sortOrder
	 * @param array|null $limit
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function listStockAttributeValuesOfStock($stock, array $filter = null, array $sortOrder = null, array $limit = null){
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
	 * @param mixed $item
	 * @param bool $bypass
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse|bool
	 */
	public function doesSupplierExist($item, \bool $bypass = false){
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
	 * @param mixed $attrVal
	 *
	 * @return array
	 */
	public function insertStockAttributeValue($attrVal){
		return $this->insertStockAttributeValues(array($attrVal));
	}

	/**
	 * @param array $collection
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function insertStockAttributeValues(array $collection){
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
	 * @param mixed $item
	 *
	 * @return array
	 */
	public function insertSupplier($item){
		return $this->insertSuppliers(array($item));
	}

	/**
	 * @param array $collection
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function insertSuppliers(array $collection){
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
	 * @param mixed $data
	 *
	 * @return array
	 */
	public function updateStockAttributeValue($data){
		return $this->updateStockAttributeValues(array($data));
	}

	/**
	 * @param array $collection
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function updateStockAttributeValues(array $collection){
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
	 * @param mixed $item
	 *
	 * @return array
	 */
	public function updateSupplier($item){
		return $this->updateSuppliers(array($item));
	}

	/**
	 * @param array $collection
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse
	 */
	public function updateSuppliers(array $collection){
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
	 * @param mixed $stock
	 * @param int  $quantity
	 * @param bool $bypass
	 *
	 * @return \BiberLtd\Bundle\CoreBundle\Responses\ModelResponse|bool
	 */
	public function doesStockExistWithQuantity($stock, \integer $quantity, \bool $bypass = false){
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