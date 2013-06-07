<?php

/**
 * Webpage Follower 网页粉丝关系模型
 * @author shedequan
 */
class WebpageFollowerModel extends DkModel
{

    public function __initialize()
    {
        $this->init_redis();
    }
    
    //获取所有粉丝
    public function getAllFollowers($pageid) {
        return $this->redis->zRevRange('webpage:follower:' . $pageid, 0, -1);
    }
	//获取当前有效粉丝列表 add by boolee 2012/6/30
 	public function getAllValiditionFollowers($web_id) {
        $uids=$this->redis->zRevRange('follower:' . $web_id, 0, -1);
        //循环得到一个webid对应的json数组。
        $return=array();
        foreach ($uids as $uid){
        	$value = $this->redis->hGet('webpage:followingdetail' . $uid, $web_id);
        	$value = json_decode($value,1);
        	$lasttime = $value['action_time'] + $value['expiry_time'] - time();
        	if( $lasttime > 0 || $value['expiry_time'] == -1)
        	$return[] = $uid;
        }
       return $return;
    }
    //获取粉丝
    public function getFollowers($pageid, $offset = 0, $limit = 10) {
        if ($limit == 0) {
            return array();
        }
        $end = $offset + $limit - 1;

        return $this->redis->zRevRange('webpage:follower:' . $pageid, $offset, $end);
    }

    //获取粉丝数
    public function getNumOfFollowers($pageid) {
        return $this->redis->zCard('webpage:follower:' . $pageid);
    }

    //清除网页粉丝
    public function flushFollowers($pageid) {
        return is_numeric($this->redis->delete('webpage:follower:' . $pageid)) ? true : false;
    }

}