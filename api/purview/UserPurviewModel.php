<?php

class UserPurviewModel extends DkModel {

    public function __initialize() {
        $this->init_db('user');
    }

    /**
     * 判断用户是否应用的访问权限
     * @param $actorId 访问者用户ID(用于判断用户关系)
     * @param $uid 被访问用户ID
     * @param $menu_module 所属模块菜单名称
     */
    public function checkAppPurview($actorId, $uid, $menu_module) {
        if (!$actorId || !$uid || !$menu_module) {
            return false;
        }

        //获取用户菜单权限设置
        $where = array('uid' => $uid, 'menu_module' => $menu_module);
        $setting = $this->db->from('user_menu_purview')->where($where)->get()->row_array();

        $default = '';
        switch ($menu_module) {
            case 'interest':
            case 'praise':
            case 'favorite':
            case 'msg':
                $default = 8;
                break;
        }

        //默认为公开显示权限
        if (empty($setting)) {
            if ($default == 8) {
                if ($actorId == $uid) {
                    return true;
//                    $setting['weight'] = $default;
//                    return $setting;
                }
                return false;
            }

            return true;
        } else {
            if ($setting['weight'] == 0 || $setting['weight'] == 1 || $actorId == $uid) {
                return true;
//                return $setting;
            }

            //获取用户关系
            $relationId = service('Relation')->getRelationWeightWithUser($uid, $actorId);
            //权限判断
            if ($setting['weight'] <= $relationId) {
                //自定义权限
                if ($setting['weight'] == -1) {
                    $arr = json_decode($setting['userlist_content']);
                    return in_array($actorId, $arr) ? true : false;
//                    foreach ($arr as $val) {
//                        if ($actorId == $val) {
//                            return $setting;
//                        }
//                    }
//                    return false;
                } else {
                    return true;
//                    return $setting;
                }
            } else {
                return false;
            }
        }
    }

    /**
     * 根据博客访问权限获取信息
     *  @author yinyancai 2012/07/12
     *  @param $blogData 博客信息 array('博客ID'=>'博客UID')
     *  @param $my_uid 	   用户ID
     * 	
     */
    public function getBlogPurview($blogData=FALSE, $my_uid=FALSE) {
        $this->init_db('blog');
        $result = array();
        if ($blogData && $my_uid) {
            foreach ($blogData as $key => $val) {
                $relation = service('Relation')->getRelationStatus($val, $my_uid);
                //杨顺军,自己查询自己的时候不能获取信息，先注释一下
//                if (!$relation)
//                    continue; //break
                $sql = "select * from blog
						where id = {$key} 
						and (
							CASE WHEN privacy='-1' and privacy_content like '%{$my_uid}%' then 1
							WHEN privacy='3' and {$relation}=4 then 1
							WHEN privacy='3' and {$relation}=10 then 1
							WHEN privacy='4' and {$relation}=10 then 1
							WHEN privacy='1' THEN 1
							ELSE 0 END 
						) = '1'";
                $res = $this->db->query($sql)->result_array();
                empty($res) ? null : $result[$key] = $res;
            }
        }
        return $result;
    }

    /**
     * 修改日志权限
     *  @author yinyancai 2012/08/07
     *  @param $blogId 		日志ID
     *  @param $purview 	 权限ID （自定义权限为 -1）
     *  @param  $permission		自定义权限 用户ID  格式：
     *   						$permission = "1000003210,1000002994,1000003174";
     * 	
     */
    public function editBlogPurview($blogId = FALSE, $purview = 1,$permission = FALSE) 
    {
    	$this->init_db('blog');
        if ($blogId) {
        	$this->db->where('id', $blogId);
        	if($purview == '-1'){
        		$res = $this->db->update('blog', array('privacy' => $purview,'privacy_content'=>$permission));
        	}else{
                $res = $this->db->update('blog', array('privacy' => $purview));
        	}
            return $res;
        }
        return false;
    }

}