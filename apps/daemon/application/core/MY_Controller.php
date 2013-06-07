<?php
/**
 * 控制器类文件
 * @author mawenpei<mawenpei@duankou.com>
 * @date <2012/02/14>
 */
class MY_Controller extends DK_Controller
{
	//
	
    /**
     * 构造函数
     */    
    public function __construct()
    {
    	 $this->is_check_login = false;
    	parent::__construct();
    	
    }    
	
    
	function sysAuthCode($txt, $operation = 'ENCODE', $key = '!@#$%^&*1QAZ2WSX') {
		$key = $key ? $key : 'HZYEYAOMAI2011';
		$txt = $operation == 'ENCODE' ? (string) $txt : str_replace(array('*', '-', '.'), array('+', '/', '='), base64_decode($txt));
		$len = strlen($key);
		$code = '';
		for ($i = 0; $i < strlen($txt); $i++) {
			$k = $i % $len;
			$code .= $txt[$i] ^ $key[$k];
		}
		$code = $operation == 'DECODE' ? $code : str_replace(array('+', '/', '='), array('*', '-', '.'), base64_encode($code));
		return $code;
	}
	
	
} 