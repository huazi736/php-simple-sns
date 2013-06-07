<?php

/**
 * Webpage Following 网页关注关系模型
 * @author shedequan
 */
class WebpageFollowingModel extends DkModel {
    
    public function __initialize() {
        $this->init_redis();
    }
    
    //添加关注
    public function follow($uid, $pageid, $action_time, $expiry_time) {
        $time = time();
        if ($this->redis->zAdd('webpage:follower:' . $pageid, $time, $uid)) {
        	if($action_time && $expiry_time){
	        	//添加关注时间
	        	$data=array(
	        							'action_time'=>$action_time,
	        							'expiry_time'=>$expiry_time
	        	);
	        	$this->redis->hSet('webpage:followingdetail:'.$uid,$pageid,json_encode($data));
        	}
            return $this->redis->zAdd('webpage:following:' . $uid, $time, $pageid) ? true : false;
        }
        return false;
    }

    //取消关注
    public function unFollow($uid, $pageid) {
        $r1 = $this->redis->zDelete('webpage:following:' . $uid, $pageid);
        $r2 = $this->redis->zDelete('webpage:follower:' . $pageid, $uid);
        $r3 = $this->redis->zDelete('webpage:following:hidden:' . $uid, $pageid);
        $r4 = $this->redis->hDel('webpage:followingdetail:' . $uid, $pageid);
        return ($r1 || $r3) && $r2;
    }
	//修改关注时间
	public function updateFollowTime($uid, $pageid, $action_time, $expiry_time) {
		$data=array('action_time'=>$action_time,'expiry_time'=>$expiry_time);
        $re=$this->redis->hSet('webpage:followingdetail:' . $uid, $pageid,json_encode($data));
        if($re === 0){		//覆盖旧值时候返回0，先插入时返回1
        	return true;
        }else{
        	return false;
        }
    }
    
    //获取关注网页的时间
    public function getTimeOfFollow($uid, $pageid) {
        $time = $this->redis->zScore('webpage:following:' . $uid, $pageid);
        return is_numeric($time) ? $time : false;
    }
    
    //隐藏关注
    public function hideFollowing($uid, $pageid) {
        $exists = $this->redis->exists('webpage:following:' . $uid);
        if (!$exists) {
            return false;
        }

        $time = $this->redis->zScore('webpage:following:' . $uid, $pageid);
        if (is_numeric($time)) {
            $r1 = $this->redis->zAdd('webpage:following:hidden:' . $uid, $time, $pageid);
            $r2 = $this->redis->zDelete('webpage:following:' . $uid, $pageid);
            return $r1 && $r2;
        }
        return false;
    }

    //取消隐藏关注
    public function unHideFollowing($uid, $pageid) {
        $exists = $this->redis->exists('webpage:following:hidden:' . $uid);
        if (!$exists) {
            return false;
        }

        $time = $this->redis->zScore('webpage:following:hidden:' . $uid, $pageid);
        if (is_numeric($time)) {
            $r1 = $this->redis->zAdd('webpage:following:' . $uid, $time, $pageid);
            $r2 = $this->redis->zDelete('webpage:following:hidden:' . $uid, $pageid);
            return $r1 && $r2;
        }
        return false;
    }

    //是否关注
    public function isFollowing($uid, $pageid) {
        $r1 = is_numeric($this->redis->zScore('webpage:following:' . $uid, $pageid));
        $r2 = is_numeric($this->redis->zScore('webpage:following:hidden:' . $uid, $pageid));
        return $r1 || $r2;
    }
	//返回关注时效时间 boolee
    public function getFollowingTime($uid, $pageid) {
        return $this->redis->hGet('webpage:followingdetail:'.$uid,$pageid);
    }
    //核对用户对不同网页的关注
    public function checkUserFollowings($uid,$web_ids){
    	if(!$uid || !$web_ids)
    	return false;
    	return $this->redis->hMget('webpage:followingdetail:'.$uid,$web_ids);
    } 
    //获取所有关注
    public function getAllFollowings($uid, $self = true) {
        if ($self) {
            $unionKey = $this->unionFollowing($uid);
            $res = $this->redis->zRevRange($unionKey, 0, -1);
        } else {
            $res = $this->redis->zRevRange('webpage:following:' . $uid, 0, -1);
        }
        return $res;
    }

    //获取关注
    public function getFollowings($uid, $self = true, $offset = 0, $limit = 10) {
        if ($limit <= 0) {
            return array();
        }
        $end = $offset + $limit - 1;

        if ($self) {
            $unionKey = $this->unionFollowing($uid);
            $res = $this->redis->zRevRange($unionKey, $offset, $end);
        } else {
            $res = $this->redis->zRevRange('webpage:following:' . $uid, $offset, $end);
        }
        return $res;
    }

    //获取关注数量
    public function getNumOfFollowings($uid, $self = true) {
        if ($self) {
            return $this->redis->zCard('webpage:following:' . $uid) + $this->redis->zCard('webpage:following:hidden:' . $uid);
        }
        return $this->redis->zCard('webpage:following:' . $uid);
    }
    /**
     * 获取个人对网页失效关注数目
     * @author boolee 2012/7/21
     * @param type $uid 用户ID
     * @return int
     */
	public function getNumOfUnValidateFollowings($uid, $self = true){
		if ($self) {
            $re = $this->redis->hgetall('webpage:followingdetail:'.$uid);
            $return = 0;
            foreach ($re as $web_id=> $content){
            	$content = json_decode($content, 1);
            	$lasttime = $content['action_time'] + $content['expiry_time'] - time();
        		if( $lasttime < 0 && $content['expiry_time']!=-1)
        		$return++;
            }
        }
        return $return;
	} 
	/**
     * 取得个人失效网页web_ids
     * @author boolee 2012/7/21
     * @param type $uid 用户ID
     * @return array('num',array('web_ids'))
     */
	public function getUnValidateFollowings($uid, $is_self = true, $page= null, $limit=null){
		if ($is_self) {
            $re = $this->redis->hgetall('webpage:followingdetail:'.$uid);
            $return = array();
            if(isset($page) && $limit){//此处是offset为0也有效
            	$start = $page-1 * $limit;
            	$end   = $page * $limit;
            	$index = 0;       //当前索引，用于取出数据条数
            	foreach ($re as $web_id => $content){
            		$content = json_decode($content, 1);
            		$lasttime = $content['action_time'] + $content['expiry_time'] - time();
		       		if( $lasttime < 0 && $content['expiry_time']!=-1){  //失效有效
		       			if($start <= $index && $index < $end){
		       				$return[] = $web_id;
		       			}
		       			$index++;
		       		}elseif($index >= $end){
		       			break;
		       		}
            	}
            }else{ //取得所有数据
            	foreach ($re as $web_id=> $content){
	            	$content = json_decode($content, 1);
	            	$lasttime = $content['action_time'] + $content['expiry_time'] - time();
	        		if( $lasttime < 0)
	        		$return[] = $web_id;
            	}
            }
        }
        return $return;
	} 
    /**
     * 生成所有关注（公开+隐藏）集合
     * @param type $uid 用户ID
     * @return string
     */
    private function unionFollowing($uid) {
        $union_keys = array('webpage:following:' . $uid, 'webpage:following:hidden:' . $uid);

        $output_key = 'webpage:tmp:following:union:' . $uid;
        $this->redis->zUnion($output_key, $union_keys, array(1, 1), 'SUM');
        $this->redis->setTimeout($output_key, 300);
        return $output_key;
    }
	/**
     * 获取个人对某个网页关注时间
     * @author boolee 2012/7/16
     * @param type $uid
     * @param type $web_id
     * @return type 
     */
    public function getWebExpiry($uid, $web_id) {
        return $this->redis->hget('webpage:followingdetail:'.$uid, $web_id);
    }
}