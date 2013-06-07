<?php

class WebpageModel extends DkModel {

    public function __initialize() {
        $this->init_db('user');
    }

    public function test() {
        return 'hello webpage model';
    }
    
    /**
     * 设置网页应用区菜单的封面
     *
     * @author yangshunjun
     * @date 2012/7/5
     *
     * @param int $webid 网页id
     * @param int $menuid 应用菜单ID
     * @param string $imgpath 菜单图片地址
     * @param string $group FASTDFS的分组
     *
     * @return boolean
     */
    public function setAppMenuCover($webid, $menuid, $imgpath, $group = '') {
    	
        if (empty($webid) || empty($menuid)) {
            return false;
        }
		
        $where['web_id'] = $webid;
        $where['menu_id'] = $menuid;
		
        $data = array_merge($where, array('menu_ico' => $imgpath, 'group' => $group));
        
        $nums = $this->db->where($where)->get('user_web_menu')->num_rows();

        if($nums){
        	// 更新应用区菜单的封面
       		$this->db->update('user_web_menu', $data, $where);
        } else {
        	$this->db->insert('user_web_menu', $data);
        	
        }
        return true;
    }

}