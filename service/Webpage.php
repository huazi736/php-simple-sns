<?php

/**
 * 网页信息接口
 * @author shedequan
 */
class WebpageService extends DK_Service {
    
    public function __construct() {
        parent::__construct();
        
        $this->init_db('user');
        $this->init_redis('user');
    }

	/**
	 * 设置网页应用区菜单的封面
	 *
	 * @author zengmingming
	 * @date 2012/7/5
	 *
	 * @param int $webid 网页id
     * @param int $menuid 应用菜单ID
     * @param string $imgpath 菜单图片地址
	 * @param string $group FASTDFS的分组
	 *
	 * @return boolean
	 */
	public function setAppMenuCover($webid, $menuid, $imgpath, $group = '')
	{
		if (empty($webid) || empty($menuid)) { return FALSE; }

		$where['web_id'] = $webid;
        $where['menu_id'] = $menuid;

        $data['menu_ico'] = $imgpath;
		$data['group'] = $group;

		// 更新应用区菜单的封面
        $this->db->update('user_web_menu', $data, $where);

		return TRUE;
	}
    
}