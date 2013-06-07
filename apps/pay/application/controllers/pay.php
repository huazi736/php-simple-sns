<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 支付引入
 * @author        linzhencheng
 * @date          2012/07/09
 * @version       1.0
 * @description   支付页面相关功能
 */

class Pay extends DK_Controller 
{
	
	
    public function __construct()
    {
        parent::__construct();
        $this->load->library('alipay');
        $this->load->helper('pays');
    }

	/**
	 * 支付首页
	 * @author        linzhencheng
	 * @date          2012/07/09
	 * @version       1.0
	 * @description   支付页面首页
	 */

	public function index()
	{	
		if(!isset($_GET['type']) && $_GET['type'] !== 'ads'){			
			$this->redirect('ads/ad/index');
		}
		if(isset($_GET['fee'])){
			if (is_numeric($_GET['fee']))
			{
        		$this->assign('fee',$_GET['fee']);
				$data['fee'] = number_format($_GET['fee'], 2, '.','');
			}
			else
			{
				die('错误的充值金额！');
			}
		}else{
        	$this->assign('fee','10');
		}

		if(isset($_GET['orderid']))
		{
			service('Ads')->delete($_GET['orderid'],$this->uid);
		}
		
		$this->display( 'pay.html' );
	}
	

	/**
	 * 支付宝支付引入
	 * @author        linzhencheng
	 * @date          2012/07/09
	 * @version       1.0
	 * @description   支付宝支付功能
	 */
	
	public function paytoalipay()
	{
		
		if (is_numeric($_POST['fee']))
		{
			$fee = number_format($_POST['fee'], 2, '.','');
		}
		else
		{
			die('错误的充值金额！');
		}
		
		$data = array();
		$data['orderid'] = $this->uid.date('Ymdhis');
		$data['content']    = '广告充值';
		$data['description']    = '支付宝广告充值';
		
		$parameter = array(
				"service"			=> "create_direct_pay_by_user",
				"payment_type"		=> "1",
				
				"partner"			=> trim($this->alipay->aliapy_config['partner']),
				"_input_charset"	=> trim(strtolower($this->alipay->aliapy_config['input_charset'])),
				"seller_email"		=> trim($this->alipay->aliapy_config['seller_email']),
				"return_url"		=> trim($this->alipay->aliapy_config['return_url']),
				"notify_url"		=> trim($this->alipay->aliapy_config['notify_url']),
				
				"out_trade_no"		=> $data['orderid'],
				"subject"			=> $data['content'],
				"body"				=> $data['description'],
				"total_fee"			=> $fee,
				
				"paymethod"			=> trim($this->alipay->aliapy_config['paymethod']),
				"defaultbank"		=> trim($this->alipay->aliapy_config['defaultbank']),
				
				"anti_phishing_key"	=> '',
				"exter_invoke_ip"	=> get_client_ip(),
				
				"show_url"			=> '',
				"extra_common_param"=> '',
				
				"royalty_type"		=> '',
				"royalty_parameters"=> ''
		);


		$html_form = $this->alipay->create_direct_pay_by_user($parameter,'确定付款');	
        $this->assign('html_form',$html_form);		
        $this->assign('fee',$fee);	
        $this->assign('back_url',mk_url('pay/pay/index',array('type'=>'ads','orderid'=>$data['orderid'],'fee'=>$_POST['fee'])));
		$this->display ('paytoalipay.html');
	}
	

	
	/**
	 * 支付宝网银支付引入
	 * @author        linzhencheng
	 * @date          2012/07/09
	 * @version       1.0
	 * @description   网银支付功能
	 */
	
	
	public function paytobank()
	{
		if (is_numeric($_POST['fee']))
		{
			$fee = number_format($_POST['fee'], 2, '.','');
		}
		else
		{
			die('错误的充值金额！');
		}

		$data = array();
		$data['orderid'] = $this->uid.date('Ymdhis');
		$data['content']    = '广告充值';
		$data['description']    = '支付宝网银广告充值';

		
		$pay_bank = isset($_POST['pay_bank'])?trim($_POST['pay_bank']):'ICBCB2C';
		
		$parameter = array(
				"service"			=> "create_direct_pay_by_user",
				"payment_type"		=> "1",
				
				"partner"			=> trim($this->alipay->aliapy_config['partner']),
				"_input_charset"	=> trim(strtolower($this->alipay->aliapy_config['input_charset'])),
				"seller_email"		=> trim($this->alipay->aliapy_config['seller_email']),
				"return_url"		=> trim($this->alipay->aliapy_config['return_url']),
				"notify_url"		=> trim($this->alipay->aliapy_config['notify_url']),
				
				"out_trade_no"		=> $data['orderid'],
				"subject"			=> $data['content'],
				"body"				=> $data['content'],
				"total_fee"			=> $fee,
				
				"paymethod"			=> 'bankPay',
				"defaultbank"		=> $pay_bank,
				
				"anti_phishing_key"	=> '',
				"exter_invoke_ip"	=> get_client_ip(),
				
				"show_url"			=> '',
				"extra_common_param"=> '',
				
				"royalty_type"		=> '',
				"royalty_parameters"=> ''
		);


		$html_form = $this->alipay->create_direct_pay_by_user($parameter,'确定付款');	
        $this->assign('html_form',$html_form);	
        $this->assign('fee',$fee);		
        $this->assign('pay_bank',$pay_bank);	
        $this->assign('back_url',mk_url('pay/pay/index',array('type'=>'ads','orderid'=>$data['orderid'],'fee'=>$_POST['fee'])));
		$this->display ('paytobank.html');
		
	}
	
	
	/**
	 * 财付通支付 
	 * @author        linzhencheng
	 * @date          2012/07/09
	 * @version       1.0
	 * @description   财付通支付功能
	 */
	
	public function paytotenpay()
	{
		require_once (APPPATH."libraries/tenpay/RequestHandler.class.php");
		require_once (CONFIG_PATH."tenpay.php");
		$this->load->helper('pays');

		if (is_numeric($_POST['fee']))
		{
			$fee = number_format($_POST['fee'], 2, '.','');
			$total_fee = $fee * 100;
		}
		else
		{
			die('错误的充值金额！');
		}


		$data = array();
		$data['orderid'] = $this->uid.date('Ymdhis');
		$data['content']    = '广告充值';
		$data['description']    = '财付通广告充值';

		
		/* 创建支付请求对象 */
		$reqHandler = new RequestHandler();
		$reqHandler->init();
		$reqHandler->setKey($config['key']);
		$reqHandler->setGateUrl($config['tenpay_gateway']);
		
		//----------------------------------------
		//设置支付参数 
		//----------------------------------------
		$reqHandler->setParameter("partner", $config['partner']);
		$reqHandler->setParameter("out_trade_no", $data['orderid']);
		$reqHandler->setParameter("total_fee", $total_fee);  //总金额
		$reqHandler->setParameter("return_url",  $config['return_url']);
		$reqHandler->setParameter("notify_url", $config['notify_url']);
		$reqHandler->setParameter("body", $data['content']);
		$reqHandler->setParameter("bank_type", "DEFAULT");  	  //银行类型，默认为财付通		
		
		//用户ip
		$reqHandler->setParameter("spbill_create_ip", get_client_ip());	//客户端IP
		$reqHandler->setParameter("fee_type", "1");               				//币种
		$reqHandler->setParameter("subject",$data['description']);          	//商品名称，（中介交易时必填）		
		
		//系统可选参数
		$reqHandler->setParameter("sign_type", $config['sign_type']);  	 	  			//签名方式，默认为MD5，可选RSA
		$reqHandler->setParameter("service_version", $config['service_version']); 	  	//接口版本号
		$reqHandler->setParameter("input_charset", $config['input_charset']);   	  	//字符集
		$reqHandler->setParameter("sign_key_index", $config['sign_key_index']);    	  	//密钥序号
		
		$reqHandler->getRequestURL();
       	
		$params = $reqHandler->getAllParameters();
		
		$sHtml = "<form id='tenpaysubmit' name='tenpaysubmit' action='".$reqHandler->getGateUrl()."' method='POST'  target='_blank'>\n";
		
        foreach($params as $key=>$val)
        {
        	$sHtml.= "<input type='hidden' name='".$key."' value='".$val."' />\n";
        }
        
        $sHtml = $sHtml."<input type='submit' id='dk_pay_sub' class='button' value='确定支付'></form>\n";
        
        $this->assign('html_form',$sHtml);
        $this->assign('back_url',mk_url('pay/pay/index',array('type'=>'ads','orderid'=>$data['orderid'],'fee'=>$_POST['fee'])));
        $this->assign('fee',$fee);
		$this->display ('paytotenpay.html');
		
	}


	
	
	/**
	 * 支付宝支付返回结果
	 * @author        linzhencheng
	 * @date          2012/07/09
	 * @version       1.0
	 * @description   网银支付功能
	 */	
	
	
	public function alipayresult()
	{
		$this->load->helper('pays');
		$this->load->library('alipay');
		$result = $this->alipay->verifyReturn();
		
		//获取支付宝的通知返回参数，可参考技术文档中页面跳转同步通知参数列表
	    $out_trade_no	= $_GET['out_trade_no'];	//获取订单号
	    $trade_no		= $_GET['trade_no'];		//获取支付宝交易号
	    $total_fee		= $_GET['total_fee'];		//获取总价格
	    $buyer_email	= $_GET['buyer_email'];		//获取支付宝账号
		    
    	$data = array();
		$data['orderid'] = $out_trade_no;
		$data['tradeid'] = $trade_no;
		$data['uid'] = $this->uid;
		$data['number'] = $buyer_email;
		$data['type'] = 2;
		$data['content']    = '广告充值';
		$data['description']    = '支付宝广告充值';
		$data['money'] = $total_fee;
		$data['dateline'] = time();
		
		if($result)
		{
		    if($_GET['trade_status'] == 'TRADE_FINISHED' || $_GET['trade_status'] == 'TRADE_SUCCESS') {
				
		    	$msg = '充值成功！';
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
    			$msg = '支付失败！';
    			//验证成功但交易失败
		    	$data['status'] = '4';
		    	service('Ads')->paid($data);
		    	logResult('广告定单'.$out_trade_no.'交易号'.$trade_no.'验证成功但交易失败');
		    	
    		}
    		
		}else{
    		$msg = '您支付失败或没有发生支付！';
    		//验证失败
		    $data['status'] = '3';
		    service('Ads')->paid($data);
		    logResult('广告定单'.$out_trade_no.'交易号'.$trade_no.'验证失败');
    	}
    	
        $this->assign('msg',$msg);
		$this->display ( 'result.html' );
	}

	
	
	
	
	/**
	 * 财付通支付返回结果
	 * @author        linzhencheng
	 * @date          2012/07/09
	 * @version       1.0
	 * @description   网银支付功能
	 */	
	public function tenpayresult()
	{
		require_once (APPPATH."libraries/tenpay/ResponseHandler.class.php");
		require_once (CONFIG_PATH."tenpay.php");

		$resHandler = new ResponseHandler();
		$resHandler->setKey($config['key']);
		
		//商户订单号
		$out_trade_no = $resHandler->getParameter("out_trade_no");
		//财付通订单号
		$transaction_id = $resHandler->getParameter("transaction_id");
		
		if($resHandler->isTenpaySign())
		{
			
			//金额,以分为单位
			$total_fee = $resHandler->getParameter("total_fee");
			//如果有使用折扣券，discount有值，total_fee+discount=原请求的total_fee
			$discount = $resHandler->getParameter("discount");
			//支付结果
			$trade_state = $resHandler->getParameter("trade_state");
			//交易模式,1即时到账
			$trade_mode = $resHandler->getParameter("trade_mode");
    		
			if("1" == $trade_mode ) {
				if( "0" == $trade_state){ 
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
					$data['money'] = $total_fee/100;
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
			
				} else {
					//当做不成功处理
					$msg = '财付通即时到帐支付失败！';
					logResult('广告定单'.$out_trade_no.'交易号'.$transaction_id.'财付通即时到帐支付失败');
				}
			}elseif( "2" == $trade_mode  ) {
				if( "0" == $trade_state) {
				
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
					$data['description']    = '财付通中介担保广告充值';
					$data['money'] = $total_fee;
					$data['dateline'] = time();
					$data['status'] = '1';
					
					$msg = '财付通中介担保支付成功！';
					//------------------------------
					//处理业务完毕
					//------------------------------	
				
				} else {
					//当做不成功处理
					$msg = '财付通中介担保支付失败！';
					logResult('广告定单'.$out_trade_no.'交易号'.$transaction_id.'财付通中介担保支付失败');
				}
			}
		}else{
    		//验证失败
		    $msg = '您支付失败或没有发生支付！';
		    logResult('广告定单'.$out_trade_no.'交易号'.$transaction_id.'财付通认证签名失败');
    	}
	
        $this->assign('msg',$msg);
        
		$this->display ( 'result.html' );
	}	
}


/* End of file pay.php */
/* Location: ./controllers/pay.php */