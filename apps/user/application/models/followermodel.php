<?php
  /**
 * @desc            粉丝
 * @author          yaohaiqi
 * @date             2012-03-01
 * @version         $Id: followermodel.php 10943 2012-03-29 09:12:05Z yaohq $
 * @description     粉丝首页\粉丝列表\ 通过姓名获取粉丝等
 * @history          <author><time><version><desc>
 */
class FollowerModel extends MY_Model {
        
        /**
        * 获取粉丝信息
        * @param type $uid 主页用户的ID
        * @param type $offset 页码
        * @param type $limit 每页数量
        * @return type array(id,name,dkcode,src,href)
        * @return type $id 粉丝用户uid
        * @return type $name 粉丝用户姓名
        * @return type $dkcode 粉丝用户dkcode
        * @return type $src 粉丝用户页面头像url
        * @return type $href 进入粉丝用户url
        */
        public function getFollowersWithInfo($uid ,$offset = 1,$limit = 27) {
            $result = service('Relation')->getFollowersWithInfo($uid,$offset,$limit);
            foreach($result as $k => $v){
                $result[$k]['src'] = get_avatar($v['id'],'mm');
                $result[$k]['href'] = mk_url('main/index/main', array('dkcode' => $v['dkcode']));
            }
            return $result;
	}
        
        /**
        * 通过姓名获取粉丝
        * @param type $uid 主页用户的ID
        * @param type $keyword 姓名（关键字）
        * @param type $limit 每页数量
        * @return type array(id,name,dkcode)
        * @return type $id 粉丝用户uid
        * @return type $name 粉丝用户姓名
        * @return type $dkcode 粉丝用户dkcode
        */
        public function getFollowersByName($uid ,$keyword = '',$page = 1,$limit = 27) {
            $resuslt = service("PeopleSearch")->getFollowersReturnJSON($uid ,$keyword ,$page,$limit);
            $resuslt = json_decode($resuslt, true);
            return  $resuslt;
	}
        
        /**
        * 获取粉丝数量
        * @param type $uid 主页用户的ID
        * @return type int
        */
        public function getNumOfFollowers($uid) {
            return service('Relation')->getNumOfFollowers($uid);
	 } 
}
?>