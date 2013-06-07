<?php
/* *
 * 类名：Alipay
 * 功能：支付宝各接口构造类
 * 详细：构造支付宝各接口请求参数
 */


class alipay {
	
	/**
	 *配置文件信息
	 */
	var $aliapy_config;

	function __construct($aliapy_config){
		$this->aliapy_config = $aliapy_config;
	}
	/**
     * 构造即时到帐接口
     * @param $para_temp 请求参数数组
     * @return 表单提交HTML信息
     */
	function create_direct_pay_by_user($para_temp,$button_name) {

		//生成表单提交HTML文本信息
		$html_text = $this->buildForm($para_temp, $this->aliapy_config['alipay_gateway'], "get", $button_name, $this->aliapy_config);

		return $html_text;
	}

	/**
     * 用于防钓鱼，调用接口query_timestamp来获取时间戳的处理函数
	 * 注意：该功能PHP5环境及以上支持，因此必须服务器、本地电脑中装有支持DOMDocument、SSL的PHP配置环境。建议本地调试时使用PHP开发软件
     * return 时间戳字符串
	 */
	function query_timestamp() {
		$url = $this->aliapy_config['alipay_gateway']."service=query_timestamp&partner=".trim($this->aliapy_config['partner']);
		$encrypt_key = "";		

		$doc = new DOMDocument();
		$doc->load($url);
		$itemEncrypt_key = $doc->getElementsByTagName( "encrypt_key" );
		$encrypt_key = $itemEncrypt_key->item(0)->nodeValue;
		
		return $encrypt_key;
	}
	
		/**
     * 生成要请求给支付宝的参数数组
     * @param $para_temp 请求前的参数数组
     * @param $aliapy_config 基本配置信息数组
     * @return 要请求的参数数组
     */
	function buildRequestPara($para_temp) {
		//除去待签名参数数组中的空值和签名参数
		$para_filter = paraFilter($para_temp);

		//对待签名参数数组排序
		$para_sort = argSort($para_filter);

		//生成签名结果
		$mysign = buildMysign($para_sort, trim($this->aliapy_config['key']), strtoupper(trim($this->aliapy_config['sign_type'])));
		
		//签名结果与签名方式加入请求提交参数组中
		$para_sort['sign'] = $mysign;
		$para_sort['sign_type'] = strtoupper(trim($this->aliapy_config['sign_type']));
		
		return $para_sort;
	}

	/**
     * 生成要请求给支付宝的参数数组
     * @param $para_temp 请求前的参数数组
	 * @param $aliapy_config 基本配置信息数组
     * @return 要请求的参数数组字符串
     */
	function buildRequestParaToString($para_temp) {
		//待请求参数数组
		$para = $this->buildRequestPara($para_temp);
		
		//把参数组中所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串，并对参数值做urlencode编码
		$request_data = createLinkstringUrlencode($para);
		
		return $request_data;
	}
	
    /**
     * 构造提交表单HTML数据
     * @param $para_temp 请求参数数组
     * @param $gateway 网关地址
     * @param $method 提交方式。两个值可选：post、get
     * @param $button_name 确认按钮显示文字
     * @return 提交表单HTML文本
     */
	function buildForm($para_temp, $gateway, $method, $button_name) {
		//待请求参数数组
		$para = $this->buildRequestPara($para_temp);
		
		$sHtml = "<form id='alipaysubmit' name='alipaysubmit' action='".$gateway."_input_charset=".trim(strtolower($this->aliapy_config['input_charset']))."' method='".$method."'  target='_blank'>\n";
		while (list ($key, $val) = each ($para)) {
            $sHtml.= "<input type='hidden' name='".$key."' value='".$val."' />\n";
        }

		//submit按钮控件请不要含有name属性
        $sHtml = $sHtml."<input type='submit' id='dk_pay_sub' class='button' value='".$button_name."' /></form>\n";
				
		return $sHtml;
	}
	
	/**
     * 构造模拟远程HTTP的POST请求，获取支付宝的返回XML处理结果
	 * 注意：该功能PHP5环境及以上支持，因此必须服务器、本地电脑中装有支持DOMDocument、SSL的PHP配置环境。建议本地调试时使用PHP开发软件
     * @param $para_temp 请求参数数组
     * @param $gateway 网关地址
	 * @param $aliapy_config 基本配置信息数组
     * @return 支付宝返回XML处理结果
     */
	function sendPostInfo($para_temp, $gateway, $aliapy_config) {
		$xml_str = '';
		
		//待请求参数数组字符串
		$request_data = $this->buildRequestParaToString($para_temp);
		//请求的url完整链接
		$url = $gateway . $request_data;
		//远程获取数据
		$xml_data = getHttpResponse($url,trim(strtolower($this->aliapy_config['input_charset'])));
		//解析XML
		$doc = new DOMDocument();
		$doc->loadXML($xml_data);

		return $doc;
	}


	    /**
     * 针对notify_url验证消息是否是支付宝发出的合法消息
     * @return 验证结果
     */
	function verifyNotify(){
		if(empty($_POST)) {//判断POST来的数组是否为空
			return false;
		}
		else {
			//生成签名结果
			unset($_POST['app']);
			unset($_POST['controller']);
			unset($_POST['action']);
			$mysign = $this->getMysign($_POST);
			//获取支付宝远程服务器ATN结果（验证是否是支付宝发来的消息）
			$responseTxt = 'true';
			if (! empty($_POST["notify_id"])) {$responseTxt = $this->getResponse($_POST["notify_id"]);}
			
			//写日志记录
			//$log_text = "responseTxt=".$responseTxt."\n notify_url_log:sign=".$_POST["sign"]."&mysign=".$mysign.",";
			//$log_text = $log_text.createLinkString($_POST);
			//logResult($log_text);
			
			//验证
			//$responsetTxt的结果不是true，与服务器设置问题、合作身份者ID、notify_id一分钟失效有关
			//mysign与sign不等，与安全校验码、请求时的参数格式（如：带自定义参数等）、编码格式有关
			if (preg_match("/true$/i",$responseTxt) && $mysign == $_POST["sign"]) {
				return true;
			} else {
				return false;
			}
		}
	}
	
    /**
     * 针对return_url验证消息是否是支付宝发出的合法消息
     * @return 验证结果
     */
	function verifyReturn(){
		if(empty($_GET)) {//判断POST来的数组是否为空
			return false;
		}
		else {
			//生成签名结果
			unset($_GET['app']);
			unset($_GET['controller']);
			unset($_GET['action']);
			$mysign = $this->getMysign($_GET);
			//获取支付宝远程服务器ATN结果（验证是否是支付宝发来的消息）
			$responseTxt = 'true';
			if (! empty($_GET["notify_id"])) {$responseTxt = $this->getResponse($_GET["notify_id"]);}
			
			//写日志记录
			//$log_text = "responseTxt=".$responseTxt."\n notify_url_log:sign=".$_GET["sign"]."&mysign=".$mysign.",";
			//$log_text = $log_text.createLinkString($_GET);
			//logResult($log_text);
			
			//验证
			//$responsetTxt的结果不是true，与服务器设置问题、合作身份者ID、notify_id一分钟失效有关
			//mysign与sign不等，与安全校验码、请求时的参数格式（如：带自定义参数等）、编码格式有关
			if (preg_match("/true$/i",$responseTxt) && $mysign == $_GET["sign"]) {
				return true;
			} else {
				return false;
			}
		}
	}
	
    /**
     * 根据反馈回来的信息，生成签名结果
     * @param $para_temp 通知返回来的参数数组
     * @return 生成的签名结果
     */
	function getMysign($para_temp) {
		//除去待签名参数数组中的空值和签名参数
		$para_filter = paraFilter($para_temp);
		
		//对待签名参数数组排序
		$para_sort = argSort($para_filter);
		
		//生成签名结果
		$mysign = buildMysign($para_sort, trim($this->aliapy_config['key']), strtoupper(trim($this->aliapy_config['sign_type'])));
		
		return $mysign;
	}

    /**
     * 获取远程服务器ATN结果,验证返回URL
     * @param $notify_id 通知校验ID
     * @return 服务器ATN结果
     * 验证结果集：
     * invalid命令参数不对 出现这个错误，请检测返回处理中partner和key是否为空 
     * true 返回正确信息
     * false 请检查防火墙或者是服务器阻止端口问题以及验证时间是否超过一分钟
     */
	function getResponse($notify_id) {
		$transport = strtolower(trim($this->aliapy_config['transport']));
		$partner = trim($this->aliapy_config['partner']);
		$veryfy_url = '';
		if($transport == 'https') {
			$veryfy_url = $this->aliapy_config['https_verify_url'];
		}
		else {
			$veryfy_url = $this->aliapy_config['http_verify_url'];
		}
		$veryfy_url = $veryfy_url."partner=" . $partner . "&notify_id=" . $notify_id;
		$responseTxt = getHttpResponse($veryfy_url);
		
		return $responseTxt;
	}
}
?>