<?php

class usermodel extends MY_Model
{
	
    public function getUserInfo($uid)
    {
        return service('User')->getUserInfo($uid);
    }
    
    public function editUserMobile($uid,$mobile)
    {
        return $this->db->where(array('uid'=>$uid))->update('user_info',array('mobile'=>$mobile));
    }
    
    public function saveUserToRedis($user)
    {
        if(empty($user)) return false;
        
        return $this->redis->hSet('userlist','uid_'. $user['uid'], json_encode($user));
    }
    
    public function saveUserToMongo($collection,$user)
    {
        if(empty($user)) return false;
        return $this->mongodb->insert($collection,$user);
    }
    
    public function getUserInfoByMongo($collection,$where = array()){
    	if(count($where)>0){
    		$ret = $this->mongodb->get($collection);
    	}else{
    		$ret = $this->mongodb->get_where($collection,$where);
    	}
    	
    	return $ret;
    }
    
    
    public function saveUserToMemcache($user)
    {
        if(empty($user)) return false;
        return $this->memcache->set('userlist_uid_'. $user['uid'],$user);
    }
    
    public function getUserInfoByCache($uid,$type='memcache')
    {
        if($type == 'memcache')
        {
            return $this->memcache->get('userlist_uid_' . $uid);
        }
        elseif($type == 'redis')
        {
            return json_decode($this->redis->hGet('userlist','uid_'.$uid),true);
        }
        else
        {
            return null;
        }
    }
}