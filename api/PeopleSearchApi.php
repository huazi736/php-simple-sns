<?php

class PeopleSearchApi extends DkApi {

    protected $people;

    public function __initialize() {
        $this->people = DKBase::import('PeopleSearch', 'search');
    }

    public function test() {
        $following_info = array('id' => 111111, 'dkcode' => 111111, 'name' => 'avatar111111', 'frd_time' => time());
        $user_info = array('id' => 1111111, 'dkcode' => 1111111, 'name' => 'avatar1111112', 'frd_time' => time());

        $res = $this->makeFriendWithSomeone($user_info, $following_info);

        echo '<pre>';
        print_r(json_decode($res));
        echo '</pre>';
    }

    /**
     * 获取相互关注
     * 
     * Enter description here ...
     * @param int $user_id 当前用户的ID
     * @param string $keyword 搜索的关键词
     * @param int $current_page 搜索的页,初始值为1
     * @param int $limit 显示的条数,初始值为27
     */
    public function getFollowingUserEachOther($user_id=null, $keyword=null, $current_page=1, $limit=27) {//DONE
        return $this->people->getFollowingUserEachOther($user_id, $keyword, $current_page, $limit);
    }

    /**
     * 我关注的人
     * 
     * Enter description here ...
     * @param int $user_id 当前用户的ID
     * @param string $keyword 搜索的关键词
     * @param int $current_page 搜索的页,初始值为1
     * @param int $limit 显示的条数,初始值为27
     */
    public function getFollowingReturnJSON($user_id = null, $keyword=null, $current_page=1, $limit=27) {//DONE
        return $this->people->getFollowingReturnJSON($user_id, $keyword, $current_page, $limit);
    }

    /**
     * 关注我的人（粉丝）
     * 
     * Enter description here ...
     * @param int $user_id 当前用户的ID
     * @param string $keyword 搜索的关键词
     * @param int $current_page 搜索的页,初始值为1
     * @param int $limit 显示的条数,初始值为27
     */
    public function getFollowersReturnJSON($user_id = null, $keyword=null, $current_page=1, $limit=27) {//DONE
        return $this->people->getFollowersReturnJSON($user_id, $keyword, $current_page, $limit);
    }
    
    public function getFriendsReturnArray($user_id = null, $keyword=null, $current_page=1, $limit=27)
    {
        return $this->people->getFriendsReturnArray($user_id, $keyword, $current_page, $limit);        
    }

    /**
     * 好友
     * 
     * Enter description here ...
     * @param int $user_id
     * @param string $keyword
     * @param int $offset
     * @param int $limit
     */
    public function getFriendsReturnJSON($user_id = null, $keyword=null, $current_page=1, $limit=27) {//DONE
        return $this->people->getFriendsReturnJSON($user_id, $keyword, $current_page, $limit);
    }

    /**
     * 添加关注
     * 
     * Enter description here ...
     * @param array $user_data 当前用户信息
     * @param array $following_data 关注用户信息
     */
    public function addFollowing($user_data=array(), $following_data=array(), $is_together_following = false) {//DONE
        return $this->people->addFollowing($user_data, $following_data, $is_together_following);
    }

    /**
     * 删除关注
     * 
     * Enter description here ...
     * @param string $user_id 当前用户ID
     * @param string $following_id 删除关注ID
     */
    public function deleteFollowing($user_id=null, $following_id=null) {//DONE
        return $this->people->deleteFollowing($user_id, $following_id);
    }

    /**
     * 成为朋友
     * 
     * Enter description here ...
     * @param array $user_data 当前用户信息
     * @param array $friend_data 成功朋友信息
     */
    public function makeFriendWithSomeone(array $user_data, array $friend_data) {
        return $this->people->makeFriendWithSomeone($user_data, $friend_data);
    }

    /**
     * 删除朋友
     * 
     * Enter description here ...
     * @param string $user_id 当前用户ID
     * @param string $friend_id 取消朋友ID
     */
    public function deleteFriendById($user_id=null, $friend_id=null) {//DONE
        return $this->people->deleteFriendById($user_id, $friend_id);
    }

    /**
     * 隐藏朋友
     * 
     * Enter description here ...
     * @param array $user_data 用户的相关信息
     * @param array $friend_data 朋友的相关信息
     */
    public function hideFriend(array $user_data, array $friend_data) {//DONE
        return $this->switchFriendHide($user_data, $friend_data);
    }

    /**
     * 隐藏关注
     * 
     * Enter description here ...
     * @param array $user_data 用户的相关信息
     * @param array $following_data 关注对象的相关信息
     * @param boolean $is_together_following 是否为相互关注
     * @param boolean $is_friend 是否为朋友
     */
    public function hideFollowing($user_data = array(), $following_data = array(), $is_together_following = false, $is_friend= false) {//DONE
        return $this->switchFollowingHide($user_data, $following_data, 1, $is_together_following, $is_friend);
    }

    /**
     * 取消隐藏关注
     * 
     * Enter description here ...
     * @param array $user_data 用户的相关信息
     * @param array $following_data 关注对象的相关信息
     * @param boolean $is_together_following 是否为相互关注
     */
    public function unHideFollowing(array $user_data, array $following_data, $is_together_following = false) {//DONE
        return $this->switchFollowingHide($user_data, $following_data, 0, $is_together_following);
    }

    /**
     * 取消隐藏朋友
     * 
     * Enter description here ...
     * @param array $user_data 用户的相关信息
     * @param array $friend_data 朋友的相关信息
     */
    public function unHideFriend(array $user_data, array $friend_data) {//DONE
        return $this->switchFriendHide($user_data, $friend_data, 0);
    }

    private function switchFollowingHide(array $user_data, array $following_data, $type = 1, $is_together_following=false, $is_friend=false) {
        return $this->people->switchFollowingHide($user_data, $following_data, $type, $is_together_following, $is_friend);
    }

    private function switchFriendHide($user_data, $friend_data, $type=1) {
        return $this->people->switchFriendHide($user_data, $friend_data, $type);
    }
    
}