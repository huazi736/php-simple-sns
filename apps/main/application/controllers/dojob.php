<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * 关系公共�? *
 * @author        lanyanguang
 * @date           2012/3/19
 * @version       $Id$
 * @description   关注\取消关注 加好友\删除好友�? * @history        <author><time><version><desc>
 */
class dojob extends DK_Controller{    
    
    /**
     * 目标用户uid
     * 
     * @var int 
     */

    /**
     * 构�?方法
     */
protected $is_check_login=false;
    function __construct() {
    	parent::__construct();
    	
    }
    function index(){
    	$this->display('job/index.html');
    }
    function done(){          //在线填写 简历
    	//$list=$this->job->getListsByType();

    	$aid=$this->input->get_post('aid')?$this->input->get_post('aid'):0;
    	$this->load->model ( 'jobmodel', 'job' );
    	
    	$data=array();
    	
    	$result=$this->job->getListsByType($data);
    	$this->assign('aid', $aid);

    	$this->display('about/index.html');
    }


    
    
    

    
    
}
/* End of file api.php */
/* Location: ./application/controllers/api.php */