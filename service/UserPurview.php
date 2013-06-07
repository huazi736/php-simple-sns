<?php

/**
 * 用户应用区权限（暂时）
 */
class UserPurviewService extends DK_Service {

    public function __construct() {
        parent::__construct();
        
        $this->init_db('user');
    }

    /**
     * 判断用户是否应用的访问权限
     * @param $actorId 访问者用户ID(用于判断用户关系)
     * @param $uid 被访问用户ID
     * @param $menuId 所属菜单ID
     */
    public function checkAppPurview($actorId, $uid, $menuId) {
        if (!$actorId || !$uid || !$menuId) {
            return false;
        }
        
        //获取用户菜单权限设置
        $where = array('uid' => $uid, 'menu_id' => $menuId);
        $setting = $this->db->from('user_menu_purview')->where($where)->get()->row_array();

        $default = '';
        switch ($menuId) {
            case 1:
            case 3:
            case 5:
            case 11:
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
	public function getBlogPurview($blogData=FALSE,$my_uid=FALSE)
	{
        $this->init_db('blog');
		$result = array();
		if($blogData && $my_uid)
		{
			foreach($blogData as $key => $val)
			{
				$relation = service('Relation')->getRelationStatus($val, $my_uid);
				if(!$relation)
					continue;	//break
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
				 empty($res)? null:$result[$key]=$res;
			}
		}
		return $result;
	}
	
}