<?php

class Redeem extends DK_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->model('redeemmodel');
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
		
		$redeems = $this->redeemmodel->getAllByUID($this->uid);
		
		if (!empty($redeems)) {
			foreach ($redeems  as $key => $redeem) {
				$times[] = $redeem['time'];
			}
			array_multisort($times, SORT_DESC, $redeems);
		}
		
		$this->assign('redeems', $redeems);
		$this->assign('userinfo',$userinfo);
		$this->display('credits/record');
	}
	
	public function verify()
	{
		$productId = isset($_GET['pid']) ? $_GET['pid'] : 0;
		$redeemId = isset($_GET['redeemId']) ? $_GET['redeemId'] : 0;
		
		if ($productId && $redeemId) {
			$this->load->model('productmodel');
			$product = $this->productmodel->getProductDetail($productId);
			
			if (null !== $product) {
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
								
				$this->load->model('addressmodel');
				$addresses = $this->addressmodel->getAddressesByUID((int) $this->uid);
				$config = getConfig('fastdfs', 'credit');
				$product['pic'] = 'http://' . config_item('fastdfs_domain') . '/' . $product['pic'][0];
				$this->assign('product', $product);
				$this->assign('redeemId', $redeemId);
				$this->assign('addresses', $addresses);
				$this->assign('type', $product['type']);
				$this->display('credits/exchange_2.html');
			}
			
		} else {
			
		}
	}
	
	public function doRedeem()
	{
		$productId = isset($_POST['pid']) ? $_POST['pid'] : 0;
		$this->load->model('productmodel');
		$product = $this->productmodel->getProductDetail($productId);
		if (null !== $product) {
			if (service('credit')->validateConstraint($product['condition'], $product['credit'])) {
				if ($product['type'] == 1) {
					$this->assign('product', $product);
			
					$redeemId = $this->redeemmodel->addNewRedeem($this->uid, $this->username, $productId, $product['name']
							, (int)$product['type'], (int)$product['condition'], (int)$product['credit']);
					if ($redeemId) {
						$this->ajaxReturn(mk_url('credit/redeem/verify', array('pid' => $productId, 'redeemId' => strval($redeemId))), '', 1);
					} else {
						// 兑换失败的情况 系统繁忙
						$this->ajaxReturn(null, '没有库存，敬请期待！', 0);
					}
			
				} else {
					// 虚拟物品，游戏兑换，优惠券
					$redeemId = $this->redeemmodel->addNewNoDeliveredRedeem($this->uid, $this->username, $productId
							, $product['name'], (int) $product['type'], (int) $product['condition'], (int) $product['credit']);
					if ($redeemId) {
						// 扣除相应积分
						if (($leftCredits = service('credit')->consume($product['credit'])) !== false) {
			
							// 添加用户的兑换记录
							$this->redeemmodel->addRedeemHistory2User($this->uid, strval($product['_id']), $product['type'], $product['name']
									, $product['credit'], $leftCredits, $redeemId);
			
							$this->ajaxReturn(null, '兑换成功！', 1);
						} else {
							// 把后台中的兑换信息去掉
							$this->redeemmodel->cancelRedeem($productId);
							$this->ajaxReturn(null, '没有足够的可用积分！', 0);
						}
					} else {
						$this->ajaxReturn(null, '兑换失败，系统繁忙，稍后再试！', 0);
					}
				}
			} else {
				$this->ajaxReturn(null, '所需积分或者等级不够！', 0);
			}
		} else {
			$this->ajaxReturn(null, '不存在相应的产品信息！', 0);
		}
		
	}
	
	public function confirm()
	{
		$redeemId = isset($_POST['redeemId']) ? $_POST['redeemId'] : 0;
		$productId = isset($_POST['productId']) ? $_POST['productId'] : 0;
		
		//$redeemId = '4fffd8737f8b9a8416000000';
		//$productId = '4fff8c2a7f8b9a7247000024';
		//$productName = '呢喃';
		
		if ($redeemId && $productId) {
			$address['area_code'] = $_POST['area_code'];
			$address['tel'] = $_POST['tel'];
			$address['extension'] = $_POST['extension'];
			$address['uname'] = $_POST['uname'];
			$address['pcode'] = $_POST['pcode'];
			$address['mob'] = $_POST['mob'];
			$address['province'] = $_POST['province'];
			$address['city'] = $_POST['city'];
			$address['area'] = $_POST['area'];
			$address['street'] = $_POST['street'];
			
			// 判断兑换是否还有效
			if ($this->redeemmodel->getRedeemIsValid($redeemId)) {
				$this->load->model('productmodel');
				$product = $this->productmodel->getProductDetail($productId);
				
				if (($leftCredits = service('credit')->consume($product['credit'])) !== false) {
					// 为订单添加地址信息
					$this->redeemmodel->addAddress2RedeemInfo($redeemId, $address);
				
					// 添加用户的兑换记录
					$this->redeemmodel->addRedeemHistory2User($this->uid, strval($product['_id']), $product['type'], $product['name']
							, $product['credit'], $leftCredits, $redeemId);
				
					$this->ajaxReturn(null, '兑换成功！', 1);
				} else {
					// 把后台中的兑换信息去掉
					$this->redeemmodel->cancelRedeem($productId);
					$this->ajaxReturn(null, '没有足够的可用积分！', 0);
				}
				
			} else {
				// 把后台中的兑换信息去掉
				$this->redeemmodel->cancelRedeem($productId);
				$this->ajaxReturn(null, '兑换已经失效！', 0);
			}
			
		}
	}
	
	public function getRedeemDetail()
	{
		if (isset($_GET['redeemId'])) {
			$redeemId =  $_GET['redeemId'] ;
			$redeem = $this->redeemmodel->getRedeemDetail($redeemId);
			if (null !== $redeem) {
				if (isset($redeem['dtime'])) {
					$redeem['dtime'] = date('Y年m月d日H点i分');
				}
				$this->ajaxReturn($redeem, '', 1);
				
			} else {
				$this->ajaxReturn(null, '未查到相关信息', 0);
			}
		} else {
			$this->ajaxReturn(null, '参数不正确，错误操作', 0);
		}
	}
}