<?php
  /**
   * @desc 好友
   * @author yaohaiqi
   * @date 2012-03-02
   */
class FriendModel extends MY_Model { 
        
        /**
        * 获取用户的好友数量
        * @param type $uid 用户的ID
        * @return type 
        */
        public function getNumOfFriends($self, $uid, $login_uid) {
            return service('Relation')->getNumOfFriends($uid, $self, $login_uid);
        }
        
        /**
         * 用户隐藏某个好友，使这个好友在别人查看其好友列表时不可见
        * 1为隐藏，0为非隐藏
        * @param int $uid
        * @param int $friendId
        */
        public function hideFriend($uid, $friendId) {
            return service('Relation')->hideFriend($uid, $friendId);
        }
        /**
         * 取消隐藏
         */
        public function unHideFriend($uid, $friendId) {
            return service('Relation')->unHideFriend($uid, $friendId); 
        }
        
        public function HiddenStatus($uid, $friendId) {
           return service('Relation')->isHiddenFriend($uid, $friendId); 
        }
        
        /**
         *获取指定用户的全部好友
         * @param $uid 指定用户的id
         */
        public function getAllFriendsByUid($uid){
         $friends = service('Relation')->getAllFriendsWithInfo($uid);
         return $friends;
        }
 
}
?>