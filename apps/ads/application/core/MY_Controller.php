<?php

/**
 * 控制器类文件
 * @author hujiashan
 * @date <2012/07/05>
 */

class MY_Controller  extends DK_Controller{

	
	 protected $system_config = null;
	 /**
     * 构造函数
     */   
	public function __construct(){
		parent::__construct();
		
		$this->check_sys();
	}
	
	/**
	 * 
	 *读取系统信息
	 */
	public function check_sys(){
		
		$this->load->model('adsetupmodel','adsetup', TRUE);
		$this->system_config = $this->adsetup->get_system_config();
		if($this->system_config){
			if($this->system_config['close'] == 1){
				$log = $this->system_config['closereason'] ? $this->system_config['closereason'] : '对不起，广告模块暂不能使用！谢谢';
				$this->error($log);	
			}
		}else{
			$this->error('对不起，广告模块暂不能使用！谢谢');
		}
		
	}

}


?>