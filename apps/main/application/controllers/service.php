<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * 关系公共类
 *
 * @author        lanyanguang
 * @date           2012/3/19
 * @version       $Id$
 * @description   关注\取消关注 加好友\删除好友等
 * @history        <author><time><version><desc>
 */
class Service extends MY_Controller {    
    
    /**
     * 目标用户uid
     * 
     * @var int 
     */

    /**
     * 构造方法
     */
	protected $is_check_login = false;
    function __construct() {
    	parent::__construct();
    }
    function index(){
    	
    	$this->display('service/agreement_main.html');
    }
	function agreement_main(){
		$this->display('service/agreement_main.html');
    }
    function principle(){
		$this->assign('adurl',mk_url('main/service/agreement_ad'));
    	$this->display('service/principle.html');
    }
    function privacy_statement(){
    	$this->display('service/privacy_statement.html');
    }   
    function terms(){
		$this->assign('adurl',mk_url('main/service/agreement_ad'));
    	$this->display('service/terms.html');
    }
    function agreement_ad(){
    	$this->display('service/agreement_ad.html');
    }
}
/* End of file api.php */
/* Location: ./application/controllers/api.php */