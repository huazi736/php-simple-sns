<?php

/**
 * Follower 粉丝关系模型
 */
class RelationFollowerModel extends DkModel {

    public function __initialize() {
        $this->init_redis();
    }
    
    /**
     * 获取有效期内粉丝
     * @author boolee 2012/7/3
     * @param  $uid 用户的ID
     * @return array 
     */
    public function getValiditionFollowers($uid,$order = false) {
        $allfollowers = $this->redis->zRevRange('follower:' . $uid, 0, -1);
        //获取粉丝需要多次查询
        $times = array();
        foreach ($allfollowers as $u){
        	$str = $this->redis->hget('following:expiry:' . $u, $uid);
        	if($str) 
        	$times[$u] = $str;
        	$str = '';
        }
        //$times = $this->redis->hMget('following:expiry:' . $uid, $allfollowers)?'':array();
        
        $return = array();
        $ordera = array();
        foreach($times as $uid=>$eachtime){
        	$is_validate = $eachtime + config_item('default_user_follow_expiry')- time();
        	if($is_validate > 0)  //操作时间+系统有效时间>当前时间
        	$return[] = $uid;
        	$ordera[$uid] = $eachtime;
        }
        if($order){
        	arsort($ordera);//对数组由时间排序
        	return array_keys($ordera);
        }else{
        	return $return;
        }   
    }

    public function getAllFollowers($uid) {
        return $this->redis->zRevRange('follower:' . $uid, 0, -1);
    }

    /**
     * 获取用户的粉丝列表
     * 
     * @param int $uid      用户ID
     * @param int $offset   粉丝列表的起始位置
     * @param int $limit    需要获取的粉丝的个数
     */
    public function getFollowers($uid, $offset = 0, $limit = 10) {
        if ($limit == 0) {
            return array();
        }
        $end = $offset + $limit - 1;
        
        return $this->redis->zRevRange('follower:' . $uid, $offset, $end);
    }

    /**
     * 获取两个用户的共同粉丝
     * 
     * @param int $uid1 
     * @param int $uid2
     * @return array  如果两个用户没有共同粉丝或者某个用户没有粉丝将返回一个空的数组
     */
    public function getCommonFollowers($uid1, $uid2) {
        $commonKey = $this->commonFollower($uid1, $uid2);
        return $this->redis->zRevRange($commonKey, 0, -1);
    }

    /**
     * 获取用户的粉丝数
     * 
     * @param int $uid
     */
    public function getNumOfFollowers($uid) {
        return $this->redis->zCard('follower:' . $uid);
    }

    /**
     * 获取两个用户的共同粉丝的个数
     * 
     * @param int $uid1
     * @param int $uid2
     * @return int 两个用户的共同粉丝的个数
     */
    public function getNumOfCommonFollowers($uid1, $uid2) {
        $commonKey = $this->commonFollower($uid1, $uid2);
        return $this->redis->zCard($commonKey);
    }
    
    //获取关注某个的时间
    public function getTimeOfFollowed($uid, $followerId) {
        $time = $this->redis->zScore('follower:' . $uid, $followerId);
        return is_numeric($time) ? $time : false;
    }

    private function commonFollower($uid1, $uid2) {
        $inter_keys = array('follower:' . $uid1, 'follower:' . $uid2);
        
        $output_key = 'tmp:follower:common:' . $uid1 . ':' . $uid2;
        $this->redis->zInter($output_key, $inter_keys, array(1, 1), 'MAX');
        $this->redis->setTimeout($output_key, 300);
        return $output_key;
    }
}