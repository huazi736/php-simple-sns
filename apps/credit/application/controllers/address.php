<?php

class Address extends DK_Controller
{
	public function __construct()
	{
		parent::__construct();
		
		$this->load->model('addressmodel');
	}
	
	public function index()
	{
		//获取用户信息
		$userinfo = array(
				'uid'=>$this->uid,
				'username'=>$this->username,
				'action_username' => $this->username,
				'action_uid'      =>$this->uid,
				'avatar' => get_avatar($this->uid, 's'),
				'url' => mk_url('main/index/main', array('dkcode' => $this->dkcode)),
				'uavatar' => get_avatar($this->uid, 's'),
		);
		$this->assign('userinfo',$userinfo);
		
		$addresses = $this->addressmodel->getAddressesByUID((int)$this->uid);
		$this->assign('addresses', $addresses);
		
		$this->display('credits/address');
	}
	
	public function addAddress()
	{
		$uname = isset($_POST['uname']) ? $_POST['uname'] : '';
		$postcode = isset($_POST['post_code']) ? $_POST['post_code'] : '';
		$areacode = isset($_POST['area_code']) ? $_POST['area_code'] : '';
		$telephone = isset($_POST['telphone']) ? $_POST['telphone'] : '';
		$extension = isset($_POST['extension']) ? $_POST['extension'] : '';
		$mobilephone = isset($_POST['mobile']) ? $_POST['mobile'] : '';
		$province = isset($_POST['province']) ? $_POST['province'] : '';
		$city = isset($_POST['city']) ? $_POST['city'] : '';
		$area = isset($_POST['area']) ? $_POST['area'] : '';
		$street = isset($_POST['street']) ? $_POST['street'] : '';
		$priority = isset($_POST['priority']) ? (int)$_POST['priority'] : 0;
		$time = time();
		
		$this->addressmodel->addAddress((int)$this->uid, $uname, $postcode, $areacode, $telephone, $extension, $mobilephone
					, $province, $city, $area, $street, $priority, $time);
		
		$this->ajaxReturn($time, '添加成功', 1);
	}
	
	public function updateAddress()
	{
		$uname = isset($_POST['uname']) ? $_POST['uname'] : '';
		$time = isset($_POST['time']) ? (int)$_POST['time'] : 0;
		$postcode = isset($_POST['post_code']) ? $_POST['post_code'] : '';
		$areacode = isset($_POST['area_code']) ? $_POST['area_code'] : '';
		$telephone = isset($_POST['telphone']) ? $_POST['telphone'] : '';
		$extension = isset($_POST['extension']) ? $_POST['extension'] : '';
		$mobilephone = isset($_POST['mobile']) ? $_POST['mobile'] : '';
		$province = isset($_POST['province']) ? $_POST['province'] : '';
		$city = isset($_POST['city']) ? $_POST['city'] : '';
		$area = isset($_POST['area']) ? $_POST['area'] : '';
		$street = isset($_POST['street']) ? $_POST['street'] : '';
		$priority = isset($_POST['priority']) ? (int)$_POST['priority'] : 0;
		
		if ($time) {
			$this->addressmodel->updateAddress((int)$this->uid, $time, $uname, $postcode, $areacode, $telephone, $extension, $mobilephone
					, $province, $city, $area, $street, $priority);
			
			$this->ajaxReturn(null, '更新成功', 1);
		} else {
			$this->ajaxReturn(null, '参数不正确', 0);
		}
	}
	
	public function deleteAddress()
	{
		if (isset($_POST['time'])) {
			$time = (int)$_POST['time'];
			$this->addressmodel->deleteAddress((int)$this->uid, $time);
			$this->ajaxReturn(null, '删除成功', 1);
		}
		
		$this->ajaxReturn(null, '参数不正确', 0);
	}
	
	public function setDefaultAddress()
	{
		if (isset($_POST['time'])) {
			$time = isset($_POST['time']) ? (int)$_POST['time'] : 1;
			$this->addressmodel->setPriority((int)$this->uid, $time);
			$this->ajaxReturn(null, '设置成功', 1);
		}
		
		$this->ajaxReturn(null, '设置失败', 0);
	}
}