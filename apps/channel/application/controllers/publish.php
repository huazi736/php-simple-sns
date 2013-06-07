<?php

/**
 * Publish controller
 * @author shedequan
 */
class Publish extends MY_Controller {
    
    public function __construct() {
        parent::__construct();
        $this->load->helper('channel');
    }

    public function loadPostbox() {
		// 所有者鉴权
		$webOwner = $this->web_info['uid'];
		$tpl = $this->input->get('page');
		
		if ($webOwner !== $this->uid) {
			if($tpl){
				return $this->ajaxReturn('','not_page_ownner',0,'jsonp');
			}
		}
		$this->assign('user',$this->user);
		if($tpl){
			$contents= $this->fetch('publish_templates/'.$tpl);
			return $this->ajaxReturn($contents,'operation_success',1,'jsonp');
		} else {
			return $this->ajaxReturn('','operation_fail',0,'jsonp');
		} 
		//$contents = '';
		/* if ($tpl) {
		 ob_start();
		include_once APPPATH.'views/publish_templates/'.$tpl.'.html';
		$contents = ob_get_clean();
		}
		echo dump(L('page_success'), true, array('data'=>$contents)); */
	}
    
}