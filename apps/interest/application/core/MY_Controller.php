<?php
/**
 * 控制器类文件
 * @author mawenpei<mawenpei@duankou.com>
 * @date <2012/02/14>
 */
class MY_Controller extends DK_Controller
{
	//
	var $module_max	= 16;
    /**
     * 构造函数
     */    
    public function __construct()
    {
    	
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
	
	
	/*
	 * 初使化 频道 数据
	 * 返回  频道id
	 */
	public function init_channel(){
		$this->load->model('interest_categorymodel','category');
		
		
		$module_result	= $this->category->get_apps_main($this->module_max);
		
		/*
		// 如果自己没有选区。自己
		foreach($module_result as $key=>$arr){
			if($arr['is_local']==1){
				if($this->user['cityid']<=0){
					unset($module_result[$key]);
				}
			}
		}
		*/
		
		$imid	= intval($this->input->get_post('imid'));	// 大分类名
		$this->assign( 'module_result' , $module_result);
		if($imid<=0){	// 默认
			$router_url	= $_GET['app'].'/'.$_GET['controller'].'/'.$_GET['action'];
			foreach($module_result as $key=>$arr){
				$strpos_site	=  strpos(trim($arr['channel_url']) , $router_url);
				if($strpos_site!==false && $strpos_site<=5){
					$imid		= $arr['imid'];
					break;
				}
			}
			
			if($imid<=0){
				$big_arr	= current($module_result);
				$imid		= $big_arr['imid'];
			}
		}
		$this->assign('imid', $imid );
		$this->assign('user',$this->user);
		return $imid;
	}
	
	
	
} 