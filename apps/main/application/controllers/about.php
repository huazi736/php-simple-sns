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
class About extends DK_Controller{    
    
    /**
     * 目标用户uid
     * 
     * @var int 
     */

    /**
     * 构造方法
     */
protected $is_check_login=false;
    function __construct() {
    	parent::__construct();
    }
    function index(){
    	$this->load->model ( 'articlemodel', 'article' );
    	$list=$this->article->getListsByType('about');
    	
    	$aid=$this->input->get_post('aid')?$this->input->get_post('aid'):0;

    	if($aid>count($list)-1){
    		exit('参数错误');
    	}
    	$this->assign('aid', $aid);

    	$this->assign('list', $list);
    	$this->display('about/index.html');
    }
	function test(){
    	
		
    }
    
    
    
}
/* End of file api.php */
/* Location: ./application/controllers/api.php */