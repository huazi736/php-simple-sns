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
class Help extends DK_Controller {    
    
    /**
     * 目标用户uid
     * 
     * @var int 
     */

    /**
     * 构造方法
     */
	protected $is_check_login=true;
    function __construct() {
    	
    }
  function index(){
    	//print_r($_SESSION);
    	$uid=isset($_SESSION['uid'])?isset($_SESSION['uid']):0;
    	$userinfo=array();
    	
    	$tem='help/index.html';
    	
    	if($uid==0||empty($uid)||$uid==NULL||!isset($uid)){
    		$tem='help/nologinindex.html';
  //  		print_r("未登录");
    		$this->is_check_login=false;
    		parent::__construct();
    		$userinfo=array('dkcode'=>$this->dkcode,'uid'=>$this->uid,'username'=>$this->username);
       	}else{
       		$tem='help/index.html';
       		$this->is_check_login=true;
       		parent::__construct();
       		$userinfo=array('dkcode'=>$this->dkcode,'uid'=>$this->uid,'username'=>$this->username);
   //    		print_r("已经登录");
       	}
    	$this->load->model ( 'helpermodel', 'helper' );
    	
    	$cats=$this->helper->gettree();

		foreach ($cats as $ck=>$cv){
			$temp=array();
			foreach ($cv['ccats'] as $pk=>$pv){
				//print_r($pv);exit;
				$catid=$pv['id'];
				$temp=$this->helper->getListsByCatid($catid);
				$cats[$ck]['ccats'][$pk]['data']=$temp;
			}
		}
    	$this->assign('userinfo', $userinfo);
    	$this->assign('cats', $cats);
    	
    	$this->display($tem);
    }
    function test(){
    	//print_r($_SESSION);
    	$uid=isset($_SESSION['uid'])?isset($_SESSION['uid']):0;
    	$userinfo=array();
    	 
    	$tem='help/index.html';
    	 
    	if($uid==0||empty($uid)||$uid==NULL||!isset($uid)){
    		$tem='help/index.html';
    		//  		print_r("未登录");
    		$this->is_check_login=false;
    		parent::__construct();
    		$userinfo=array('dkcode'=>$this->dkcode,'uid'=>$this->uid,'username'=>$this->username);
    	}else{
    		$tem='help/index.html';
    		$this->is_check_login=true;
    		parent::__construct();
    		$userinfo=array('dkcode'=>$this->dkcode,'uid'=>$this->uid,'username'=>$this->username);
    		//    		print_r("已经登录");
    	}
    	$this->load->model ( 'helpermodel', 'helper' );
    	 
    	$cats=$this->helper->gettree();
    
    	foreach ($cats as $ck=>$cv){
    		$temp=array();
    		foreach ($cv['ccats'] as $pk=>$pv){
    			//print_r($pv);exit;
    			$catid=$pv['id'];
    			$temp=$this->helper->getListsByCatid($catid);
    			$cats[$ck]['ccats'][$pk]['data']=$temp;
    		}
    	}
    	$this->assign('userinfo', $userinfo);
    	$this->assign('cats', $cats);
    	 
    	$this->display($tem);
    }
    
    

    
    
}
/* End of file api.php */
/* Location: ./application/controllers/api.php */