<?php

/**
 * 系统网页所有发表框
 * @author denggang
 * @date   2012/7/2
 */
class publish_tplmodel extends DK_Model {

    function __construct() {
        parent::__construct();
        $this->init_db('system');
    }

    function getTplInfo($app_ids) {
        if (empty($app_ids)) {
            return null;
        }

        // 获取应用对应的发表框
        $this->db->where_in('app_id', $app_ids);
        $this->db->or_where('is_default', 1);
        
        $this->db->where(array('scope' => 1, 'status' => 1));
        $this->db->order_by("is_default", "desc");
        $this->db->order_by("sort", "desc"); 
        return $this->db->get('publish_template')->result_array();

//        $sql = "select `id`, `name`, `icon`, `sign` from publish_template where `app_id`={$imid} 
//				and scope=1 and status=1 order by sort";
//        return $this->db->query($sql)->result_array();
    }

}

?>