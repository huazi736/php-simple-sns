<?php

class ProductModel extends DK_Model
{
	private $_db;
	
	private $_pageSize = 16;
	
	public function __construct()
	{
		parent::__construct();
		$this->init_mongodb('credit');
		$this->_db = $this->mongodb->getDbInstance();
	}
	
	public function getCount2ProductType($type = 1)
	{
		return $this->_db->products->find(array('type' => $type, 'status' => 1))->count();
	}
	
	public function getFeaturedProducts()
	{
		$products = array();
		foreach($this->_db->products->find(array('type' => $type, 'featured' => 1))->sort(array('credit' => -1, 'time' => -1)) as $product) {
			$products[] = $product;
		}
	
		return $products;
	}
	
	public function getProductsByTypeSort($type = 1, $sort = 1, $page = 1)
	{
		if ($sort == 2) {
			$sortCondition = array('time' => -1);
		} else {
			$sortCondition = array('credit' => $sort);
		}
		$products = array();
		foreach ($this->_db->products->find(array('type' => $type, 'status' => 1))
						->sort($sortCondition)
						->skip(($page - 1) * $this->_pageSize)
						->limit($this->_pageSize) as $product) {
			$products[] = $product;
		}
		
		return $products;
	}
	
	public function getProductDetail($pid)
	{
		return $this->_db->products->findOne(array('_id' => new MongoId($pid), 'status' => 1));
	}

}