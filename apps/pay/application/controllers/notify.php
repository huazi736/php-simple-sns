<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 支付异步通知
 * @author        linzhencheng
 * @date          2012/07/09
 * @version       1.0
 * @description   支付页面相关功能
 */

class Notify extends CI_Controller 
{
	
    public function __construct()
    {   
        parent::__construct();
    }

	/**
	 * 支付宝支付通知结果
	 * @author        linzhencheng
	 * @date          2012/07/09
	 * @version       1.0
	 * @description   网银支付功能
	 */	
	
	
	public function alipaynotify()
	{
		$this->load->helper('pays');
		$this->load->library('alipay');
		$result = $this->alipay->verifyNotify();
		
		$out_trade_no	= $_POST['out_trade_no'];	//获取订单号
	    $trade_no		= $_POST['trade_no'];		//获取支付宝交易号
	    $total_fee		= $_POST['total_fee'];		//获取总价格
	    $buyer_email	= $_POST['buyer_email'];	//获取支付宝账号
		
    	$data = array();
		$data['orderid'] = $out_trade_no;
		$data['tradeid'] = $trade_no;
		$data['uid'] = $this->uid;
		$data['number'] = $buyer_email;
		$data['type'] = 2;
		$data['content']    = '支付宝广告充值';
		$data['description']    = '支付宝广告充值';
		$data['money'] = $total_fee;
		$data['dateline'] = time();
		
		if($result)
		{
		    if($_POST['trade_status'] == 'TRADE_FINISHED' || $_GET['trade_status'] == 'TRADE_SUCCESS') {
				//支付成功
		    	$data['status'] = '1';
			    $re = service('Ads')->pay($data);
				if(!$re)
				{
					logResult('广告定单'.$out_trade_no.'已成功充值'.$total_fee.'元');
				}
		    	
    		}
    		else
    		{
	    		//验证成功但交易失败
			   	$data['status'] = '4';
			   	service('Ads')->paid($data);
		    	logResult('广告定单'.$out_trade_no.'交易号'.$trade_no.'验证成功但交易失败');
    		}

    		exit('success');
		}else{
    		//验证失败
			$data['status'] = '3';
			service('Ads')->paid($data);
			logResult('广告定单'.$_POST['out_trade_no'].'交易号'.$_POST['trade_no'].'验证失败');

    		exit('fail');
    	}	
	}
	
	
	/**
	 * 财付通支付通知结果
	 * @author        linzhencheng
	 * @date          2012/07/09
	 * @version       1.0
	 * @description   网银支付功能
	 */	
	
	
	public function tenpaynotify()
	{
		require_once (CONFIG_PATH."tenpay.php");
		require_once (APPPATH."libraries/tenpay/RequestHandler.class.php");
		require_once (APPPATH."libraries/tenpay/ResponseHandler.class.php");
		require_once (APPPATH."libraries/tenpay/client/ClientResponseHandler.class.php");
		require_once (APPPATH."libraries/tenpay/client/TenpayHttpClient.class.php");
		   	
    	/* 创建支付应答对象 */
    	$resHandler = new ResponseHandler();
		$resHandler->setKey($config['key']);
		
		$orderid = $resHandler->getParameter("out_trade_no");
		$this->load->helper('pays');

		//判断签名
		if($resHandler->isTenpaySign()) {
	
			//通知id
			$notify_id = $resHandler->getParameter("notify_id");
			//通过通知ID查询，确保通知来至财付通
			//创建查询请求
			$queryReq = new RequestHandler();
			$queryReq->init();
			$queryReq->setKey($config['key']);
			$queryReq->setGateUrl("https://gw.tenpay.com/gateway/simpleverifynotifyid.xml");
			$queryReq->setParameter("partner", $partner);
			$queryReq->setParameter("notify_id", $notify_id);
			
			//通信对象
			$httpClient = new TenpayHttpClient();
			$httpClient->setTimeOut(5);
			//设置请求内容
			$httpClient->setReqContent($queryReq->getRequestURL());
			//后台调用
			if($httpClient->call()) {
				//设置结果参数
					$queryRes = new ClientResponseHandler();
					$queryRes->setContent($httpClient->getResContent());
					$queryRes->setKey($config['key']);
				
				if($resHandler->getParameter("trade_mode") == "1"){
				//判断签名及结果（即时到帐）
				//只有签名正确,retcode为0，trade_state为0才是支付成功
				if($queryRes->isTenpaySign() && $queryRes->getParameter("retcode") == "0" && $resHandler->getParameter("trade_state") == "0") {
						//取结果参数做业务处理
						$out_trade_no = $resHandler->getParameter("out_trade_no");
						//财付通订单号
						$transaction_id = $resHandler->getParameter("transaction_id");
						//金额,以分为单位
						$total_fee = $resHandler->getParameter("total_fee");
						//如果有使用折扣券，discount有值，total_fee+discount=原请求的total_fee
						$discount = $resHandler->getParameter("discount");
						//------------------------------
						//处理业务开始
						//------------------------------
						$data = array();
						$data['orderid'] = $out_trade_no;
						$data['tradeid'] = $transaction_id;
						$data['uid'] = $this->uid;
						$data['number'] = '';
						$data['type'] = 5;
						$data['content']    = '广告充值';
						$data['description']    = '财付通即时到帐广告充值';
						$data['money'] = $total_fee;
						$data['dateline'] = time();
						$data['status'] = '1';
						
						$msg = '财付通即时到帐支付成功！';
						
						$re = service('Ads')->pay($data);
						if(!$re)
						{
							logResult('广告定单'.$out_trade_no.'已成功充值'.$total_fee.'元');
						}
						
						//------------------------------
						//处理业务完毕
						//------------------------------
						echo "success";
						
					} else {
						logResult("即时到帐后台回调失败");
					   echo "fail";
					}
				}elseif ($resHandler->getParameter("trade_mode") == "2") {
			  		  //判断签名及结果（中介担保）
					//只有签名正确,retcode为0，trade_state为0才是支付成功
					if($queryRes->isTenpaySign() && $queryRes->getParameter("retcode") == "0" ) 
					{
						//取结果参数做业务处理
						 $out_trade_no = $resHandler->getParameter("out_trade_no");
						//财付通订单号
						 $transaction_id = $resHandler->getParameter("transaction_id");
						//金额,以分为单位
						 $total_fee = $resHandler->getParameter("total_fee");
						//如果有使用折扣券，discount有值，total_fee+discount=原请求的total_fee
						 $discount = $resHandler->getParameter("discount");
						
						//------------------------------
						//处理业务开始
						//------------------------------
						
						//处理数据库逻辑
						//注意交易单不要重复处理
						//注意判断返回金额
			
						switch ($resHandler->getParameter("trade_state")) {
								case "0":	//付款成功
									$data = array();
									$data['orderid'] = $out_trade_no;
									$data['tradeid'] = $transaction_id;
									$data['uid'] = $this->uid;
									$data['number'] = '';
									$data['type'] = 5;
									$data['content']    = '广告充值';
									$data['description']    = '财付通中介担保广告充值';
									$data['money'] = $total_fee;
									$data['dateline'] = time();
									$data['status'] = '1';
												
									$re = service('Ads')->pay($data);
									if(!$re)
									{
										logResult('广告定单'.$out_trade_no.'已成功充值'.$total_fee.'元');
									}
									
									break;
								default:
									//nothing to do
									break;
							}
						//------------------------------
						//处理业务完毕
						//------------------------------
						echo "success";
					} else{
						logResult("中介担保后台回调失败");
						echo "fail";
					 }
				  }
		}else{
		//通信失败
			echo "fail";
		 } 
	   } else {
	  	 	logResult("财付通认证签名失败");
	   }
    		
	}

}
