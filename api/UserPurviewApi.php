<?php

/**
 * @author shedequan
 */
class UserPurviewApi extends DkApi {

    protected $userPurview;

    public function __initialize() {
        $this->userPurview = DKBase::import('UserPurview', 'purview');
    }

    /**
     * 判断用户是否应用的访问权限
     * @param $actorId 访问者用户ID(用于判断用户关系)
     * @param $uid 被访问用户ID
     * @param $menuId 所属菜单ID
     */
    public function checkAppPurview($actorId, $uid, $menu_module) {
        return $this->userPurview->checkAppPurview($actorId, $uid, $menu_module);
    }

    /**
     * 根据博客访问权限获取信息
     *  @author yinyancai 2012/07/12
     *  @param $blogData 博客信息 array('博客ID'=>'博客UID')
     *  @param $my_uid 	   用户ID
     * 	
     */
    public function getBlogPurview($blogData=FALSE, $my_uid=FALSE) {
        return $this->userPurview->getBlogPurview($blogData, $my_uid);
    }

    /**
     * 修改日志权限
     * @author yinyancai
     * @date 2012/08/07
     * @param  $blogId   	日志ID
     * @param  $purview		权限ID (自定义权限为 -1)
     * @param  $permission		自定义权限 用户ID  格式：
     * 							$permission = "1000003210,1000002994,1000003174";
     */
    public function editBlogPurview($blogId = FALSE, $purview = 1, $permission) {
        return $this->userPurview->editBlogPurview($blogId, $purview, $permission);
    }

}