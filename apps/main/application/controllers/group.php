<?php
/**
 * @desc           好友
 * @author         yaohaiqi
 * @date            2012-03-01
 * @version        $Id: friend.php 26443 2012-05-29 09:00:27Z yaohq $
 * @description    好友首页\好友列表\ 通过姓名获取好友\好友显示与隐藏等
 * @history         <author><time><version><desc>
 */


class Group extends MY_Controller {
    /**
     * 目标用户是否是本人
     * 
     * @var boolean 
     */
   // private $_self = false;

    /**
     * 构造函数
     */
    function __construct(){
	parent::__construct();
    }
    
    function index() {
	
        $this->display('group/index.html');
    }
}
?>