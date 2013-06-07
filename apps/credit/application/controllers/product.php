<?php


class Product extends DK_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->model('productmodel');
	}
	
	public function index()
	{
		$type = isset($_GET['type']) ? (int)$_GET['type'] : 1;
		$count2ProductType = $this->productmodel->getCount2ProductType($type);
		$this->assign('productCount', $count2ProductType);
		$this->assign('type', $type);
		$labels = array('', '实物礼品', '虚拟物品', '游戏兑换', '优惠券');
		$this->assign('label', $labels[$type]);
		
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
				
		$this->assign('imgHost', 'http://' . config_item('fastdfs_domain') . '/');
		$this->assign('userinfo',$userinfo);
		$this->display('credits/exchange');
	}
	
	
	public function sort()
	{
		$sort = isset($_POST['sortType']) ? (int)$_POST['sortType'] : 1;
		$type = isset($_POST['type']) ? (int)$_POST['type'] : 1;
		$page = isset($_POST['page']) ? (int)$_POST['page'] : 1;
		$this->ajaxReturn($this->productmodel->getProductsByTypeSort($type, $sort, $page));
	}
	
	public function view()
	{
		$productId = isset($_GET['pid']) ? $_GET['pid'] : 0;
		if (empty($productId)) {
			//$this->redirect(mk_url('credit/credit/index'));
		}
		
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
		$product = $this->productmodel->getProductDetail($productId);
		$this->assign('pid', $productId);
		$this->assign('type', $product['type']);
		
		
		$config = getConfig('fastdfs', 'credit');
		$product['pic'] = 'http://' . config_item('fastdfs_domain') . '/'. $product['pic'][0];
		$this->assign('product', $product);
		$this->display('credits/detail');
	}
}

