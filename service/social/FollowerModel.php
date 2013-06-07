<?php

/**
 * Follower 粉丝关系模型
 */
class FollowerModel extends DkModel
{
        
    public function __initialize()
    {
        $this->init_redis();
    }

    public function getAllFollowers($uid) {
        return $this->redis->zRevRange('follower:' . $uid, 0, -1);
    }
    /**
     * 获取有效期内粉丝
     * @author boolee 2012/7/3
     * @param  $uid 用户的ID
     * @param  $order 是否返回按照交互时间的粉丝列表，false：由关注时间排序。true：由交互时间排序
     * @return array 
     */
	public function getAllValiditionFollowers( $uid, $order = false) {
		$allfollowers = $this->redis->zRevRange('follower:' . $uid, 0, -1);
        $times = $this->redis->hMgetall('following:expiry:' . $uid, $allfollowers);
        $return = array();
        $ordera = array();
        foreach($times as $uid=>$eachtime){
        	$is_validate = $eachtime + C('DEFAULT_USER_FOLLOW_EXPIRY')- time();
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