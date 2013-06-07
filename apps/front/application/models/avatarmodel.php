<?php
class AvatarModel extends MY_Model {
     function __construct() {
		parent::__construct();		
        // require_once CONFIG_PATH . 'tables.php';
        // $this->load->database('user');
    }

    /**
     * 保存封面至数据库
     *
     * @author lvxinxin
     * @date 2012-07-10
     * 
     * @param  $path  string  $uid  int
     * @access 
     * @return bool
     */
    function save_cover($uid,$path) {
        if(empty($path) || empty($uid)) return false;
		if(!empty(@$_SESSION['user']['coverurl'])){
			$this->init_storage('avatar');
			$mf = preg_replace('/\/[A-Za-z0-9]*\//is','',$_SESSION['user']['coverurl'],1);
			$this->storage->deleteFile('',$mf);
		}
		return $this->db->update(USERS,array('coverurl'=>$path),array('uid'=>$uid));
    }
	
	
	/**
     * 删除封面
     *
     * @author lvxinxin
     * @date 2012-07-10
     * 
     * @param  $uid  int
     * @access 
     * @return bool
     */
    function del_cover($uid) {
        if(empty($uid)) return false;
		if(!empty(@$_SESSION['user']['coverurl'])){
			$this->init_storage('avatar');
			$mf = preg_replace('/\/[A-Za-z0-9]*\//is','',$_SESSION['user']['coverurl'],1);
			$this->storage->deleteFile('',$mf);
			
		}

		return $this->db->update(USERS,array('coverurl'=>''),array('uid'=>$uid));
    }
    
}
?>
