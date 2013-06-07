<?php

class RedeemModel extends DK_Model
{
	private $_db;
	
	public function __construct()
	{
		parent::__construct();
		$this->init_mongodb('credit');
		$this->_db = $this->mongodb->getDbInstance();
	}
		 
	/**
	 * 把兑换记录添加到系统后台
	 * 
	 * @param int $uid
	 * @param string $productId
	 * @param string $productName
	 * @param int $credit
	 */
	public function addNewRedeem($uid, $uname, $productId, $productName, $type, $level, $credit)
	{
		// 从产品表里拿出一件商品
		$status = $this->_db->products->update(array('_id' => new MongoId($productId), 'left' => array('$gte' => 1))
					, array('$inc'=> array('left' => -1)), array('safe' => true));
		if ($status['ok'] && $status['n'] == 1 && $status['updatedExisting']) {
			$p = array('uid' => $uid, 'uname' => $uname, 'pid' => $productId, 'pname' => $productName, 'c' => $credit
					, 'type' => $type, 'lv' => $level
					, 'ctime' => time(), 'dtime' => 0, 'ptime' => 0, 'status' => 1);
			$this->_db->redeems->save($p);
			
			return $p['_id'];
		} else {
			return false;
		}
	}
	
	/**
	 * 订单是否已失效
	 * 
	 * @param string $redeemId
	 */
	public function getRedeemIsValid($redeemId)
	{
		$redeem = $this->_db->redeems->findOne(array('_id' => new MongoId($redeemId)));
		if ($redeem && $redeem['status'] != -1) {
			return true;
		} else {
			return false;
		}
	}
	
	/**
	 * 为实物礼品兑换添加配送信息
	 * 
	 * @param string $redeemId  订单号
	 * @param string $serial  快递号
	 * @param string $deliverName  快递公司名称
	 */
	public function addDeliverInfo($redeemId, $serial, $deliverName)
	{
		$this->_db->redeems->save(array('_id' => new MongoId($redeemId)
					, array('$set' => array('serial' => $serial, 'dname' => $deliverName, 'dtime' => time()))));
	}
	
	/**
	 * 取消兑换
	 * 
	 * @param string $productId
	 */
	public function cancelRedeem($productId)
	{
		 $this->_db->products->update(array('_id' => new MongoId($productId))
					, array('$inc'=> array('left' => 1)));
	}
	
	/**
	 * 完善订单的地址信息
	 * 
	 * @param string $redeemId
	 * @param array $address
	 */
	public function addAddress2RedeemInfo($redeemId, $address)
	{
		$this->_db->redeems->update(array('_id' => new MongoId($redeemId)), array('$set' => array('address' => $address)));
	}
	
	/**
	 *  虚拟物品，游戏，优惠券等暂时这么处理
	 *  
	 * @param int $uid
	 * @param string $uname
	 * @param string $productId
	 * @param string $productName
	 * @param int $type
	 * @param int $level
	 * @param int $credit
	 */
	public function addNewNoDeliveredRedeem($uid, $uname, $productId, $productName, $type, $level, $credit)
	{
		$status = $this->_db->products->update(array('_id' => new MongoId($productId), 'left' => array('$gte' => 1))
				, array('$inc'=> array('left' => -1)), array('safe' => true));
		
		if ($status['ok'] && $status['n'] == 1 && $status['updatedExisting']) {
			$p = array('uid' => $uid, 'uname'=> $uname, 'pname' => $productName, 'type' => $type, 'pid' => $productId
					,'lv' => $level, 'c' => $credit, 'ctime' => time()
					,'ptime' => time(), 'status' => 2);
			$this->_db->redeems->save($p);
			
			return $p['_id'];
		} else {
			return false;
		}
	}
	
	/**
	 * 更改系统后台记录的状态
	 * 
	 * @param string $redeemId
	 * @param int $uid
	 */
	public function confirmRedeem($redeemId, $uid)
	{
		$this->_db->redeems->upate(array('_id' => new MongoId($redeemId), 'uid' => $uid), array('$set' => array('status' => 1)));
	}
	
	/**
	 * 把兑换记录添加到用户兑换记录中
	 * 
	 * @param int $uid
	 * @param string $productId
	 * @param string $productName
	 * @param string $redeemId
	 */
	public function addRedeemHistory2User($uid, $productId, $productType, $productName, $credit, $left, $redeemId)
	{
		$this->_db->redeem_history->update(array('_id' => $uid), array('$push' => array('history' => array('pid' => $productId
				, 'type' => $productType, 'pname' => $productName, 'c' => $credit, 'left' => $left, 'redeemId' => $redeemId, 'time' => time())))
				, array('upsert' => true));
	}
	
	public function getAllByUID($uid)
	{
		$redeem = $this->_db->redeem_history->findOne(array('_id' => $uid));
		if (isset($redeem['history'])) {
			return $redeem['history'];
		} else {
			return array();
		}
	}
	
	public function getRedeemDetail($redeemId)
	{
		return $this->_db->redeems->findOne(array('_id' => new MongoId($redeemId)));
	}
}