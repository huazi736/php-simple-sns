<?php

class AddressModel extends DK_Model
{
	private $_db;

	public function __construct()
	{
		parent::__construct();
		$this->init_mongodb('credit');
		$this->_db = $this->mongodb->getDbInstance();
	}

	public function addAddress($uid, $uname, $postcode, $areacode, $telephone, $extension, $mobilephone
					, $province, $city, $area, $street, $priority = 0, $time)
	{
		// 如果是设为首选地址，则先取消现有的首选地址
		if ($priority) {
			$this->cancelPriority($uid);
		}
		return $this->_db->addresses->update(array('_id' => $uid), array('$push' => array('addresses' => array('uname' => $uname, 'pcode' => $postcode
				, 'area_code' => $areacode, 'tel' => $telephone, 'extension' => $extension, 'mob' => $mobilephone, 'province' => $province, 'city' => $city
				, 'area' => $area, 'street' => $street, 'priority' => $priority, 'time' => $time))), array('upsert' => true));
	}

	public function updateAddress($uid, $time, $uname, $postcode, $areacode, $telephone, $extension, $mobilephone
					, $province, $city, $area, $street, $priority = 0)
	{
		if ($priority) {
			$this->cancelPriority($uid);
		}
		
		return $this->_db->addresses->update(array('_id' => $uid, 'addresses.time' => $time), array('$set' => array('addresses.$.priority' => $priority
				, 'addresses.$.uname' => $uname, 'addresses.$.pcode' => $postcode
				, 'addresses.$.area_code' => $areacode, 'addresses.$.tel' => $telephone
				, 'addresses.$.extension' => $extension
				, 'addresses.$.mob' => $mobilephone, 'addresses.$.province' => $province
				, 'addresses.$.city' => $city, 'addresses.$.area' => $area
				, 'addresses.$.street' => $street
				)));
	}

	public function setPriority($uid, $time)
	{
		// 取消当前的首选地址的设置
		$this->cancelPriority($uid);
				
		// 把传进来的地址修改时间对应的地址设为首选地址
		$this->_db->addresses->update(array('_id' => $uid, 'addresses.time' =>(int) $time), array('$set' => array('addresses.$.priority' => 1)));
	}
	
	private function cancelPriority($uid)
	{
		$this->_db->addresses->update(array('_id' => $uid, 'addresses.priority' => 1), array('$set' => array('addresses.$.priority' => 0)));
	}
	
	public function getAddressesByUID($uid)
	{
		if ($addresses = $this->_db->addresses->findOne(array('_id' => $uid), array('addresses'))) {
			return $addresses['addresses'];
		}
		
		return null;
	}
	
	public function deleteAddress($uid, $time)
	{
		$this->_db->addresses->update(array('_id' => $uid), array('$pull' => array('addresses' => array('time' => $time))));
	}
	
	public function getAddressDetail($uid, $time)
	{
		$addresses =  $this->_db->addresses->findOne(array('_id' => $uid));
		if (null !== $addresses) {
			foreach ($addresses['addresses'] as $address) {
				if ($address['time'] == $time) {
					return $address;
				}
			}
		} else {
			return null;
		}
	}
}
