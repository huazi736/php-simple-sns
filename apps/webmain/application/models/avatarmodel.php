<?php
class AvatarModel extends DK_Model {
     function __construct() {
		parent::__construct();
        // require_once CONFIG_PATH . 'tables.php';
        $this->init_db('interest');
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
    function save_cover($web_id,$path) {
        if(empty($path) || empty($web_id)) return false;
		return $this->db->update('apps_info',array('webcover'=>$path),array('aid'=>$web_id));
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
    function del_cover($web_id) {
        if(empty($web_id)) return false;
		return $this->db->update('apps_info',array('webcover'=>''),array('aid'=>$web_id));
    }
    
}
?>
