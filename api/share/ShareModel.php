<?php

class ShareModel extends DkModel {
    
     public function __initialize() {
         $this->init_redis();
     }
     
     /**
     * 新增转发
     * 
     * @param string $object_type 分享类型
     * @param integer $first_tid  原作者的信息流编号
     * @param integer $parent_tid  转发的信息流编号
     * @param integer $tid        分享后新增的信息流编号
     * @param array $params       转发需要保存的其他数据，例如uid、头像、内容、dkcode等等
     */
    public function add($object_type, $first_tid, $parent_tid, $tid, $params) {
        $params['tid'] = $tid;
        $params['time'] = time();

        $key = $this->getKey($object_type, $first_tid);
        $flag = $this->redis->hset($key, $tid, json_encode($params));

        if ($flag) {
            $skey = $this->getSetKey($object_type, $tid);
            $flag = $this->redis->sadd($skey, $first_tid);
        }

        //添加分页辅助数据
        if ('topic' == $object_type) {
            $key = $this->getKey('Stat', 'share_paging');
            $this->delList($key, $first_tid);
            $this->redis->lpush($key, $first_tid);
        }

        if ($flag && $first_tid <> $parent_tid && $parent_tid > 0) {
            return $this->share_add($object_type, $parent_tid, $tid, $params);
        }
        return $flag;
    }
    
    /**
     * 新增转发
     * 
     * @param string $object_type 转发类型
     * @param int $first_tid 转发的对象编号
     * @param int $tid 转发后的编号
     * @param string $params 需要保存的值
     * @return boolean
     */
    public function share_add($object_type, $first_tid, $tid, $params){
    	$key = $this->getKey($object_type, $first_tid);
        $flag = $this->redis->hset($key, $tid, json_encode($params));

        if($flag){
            $skey = $this->getSetKey($object_type, $tid);
            return $this->redis->sadd($skey, $first_tid);
        }
        return $flag;
    }
	
	/**
     * 删除list列表
     */
    public function delList($key,$value,$num=1){
    	$this->redis->lrem($key,$value,$num);
    }
    /**
     * 删除转发
     * 
     * @param string $object_type 分享类型
     * @param integer $tid        删除的信息流编号
     */
    public function del($object_type, $tid) {
        $skey = $this->getSetKey($object_type, $tid);
        $members = $this->redis->smembers($skey);
        foreach ($members as $t) {
            $key = $this->getKey($object_type, $t);
            $flag = $this->redis->hdel($key, $tid);
            if (!$flag) {
                return $flag;
            }
            $this->redis->srem($skey, $t);
        }
        return true;
    }

    /**
     * 取转发信息
     * 
     * @param string $object_type 分享类型
     * @param integer $tid  被转发的对象编号
     * @return integer count 总数
     * @return array data 记录数组
     */
    public function get($object_type, $tid) {
        $key = $this->getKey($object_type, $tid);
        $return = array();
        $return['count'] = $this->redis->hlen($key);
        $return['data'] = $this->redis->hgetall($key);
        return $return;
    }
    
    public function smembers($object_type, $tid){
    	$key = $this->getKey($object_type, $tid);
    	return $this->redis->smembers($key);
    }

    /**
     * 分页获得数据
     * 
     * @param string $object_type 转发类型
     * @param integer $tid 被转发的对象编号
     * @param integer $page 当前页
     * @param integer $pagesize 每页数量
     */
    public function getPageList($object_type, $first_tid, $page, $pagesize){
        $key = $this->getKey($object_type, $first_tid);
        $list = $this->redis->hKeys($key);
        sort($list, SORT_NUMERIC);
        $max = $page * $pagesize;
        $min = ($page - 1) * $pagesize;
        $key_array = array();
        for ($i = $min; $i < $max; $i++) {
            if (isset($list[$i])) {
                $key_array[] = $list[$i];
            }
        }
        return $this->redis->hmGet($key, $key_array);
    }

    /**
     * 获取转发数量 
     * 
     * @param string $object_type 分享类型
     * @param integer $tid 被转发的对象编号
     */
    public function getLen($object_type, $tid) {
        $key = $this->getKey($object_type, $tid);
        return $this->redis->hlen($key);
    }

    /**
     * 获得hash的key
     * 
     * @param string $object_type 转发类型
     * @param int $first_tid 转发的对象编号
     */
    protected function getKey($object_type, $first_tid) {
        return "Share:{$object_type}:{$first_tid}";
    }

    /**
     * 获得set的key
     * 
     * @param string $object_type 转发类型
     * @param int $first_tid 转发的对象编号
     */
    protected function getSetKey($object_type, $first_tid) {
        return "Share:Stat:{$object_type}:{$first_tid}";
    }
    
    /**
     * reids hgetAll
     */
    public function hGetAll($key = ''){
    	if(!$key) return false;
    	
    	return $this->redis->hGetAll($key);
    }
    
    
   	public function hKeys($key = ''){
   		if(!$key) return false;
   		return $this->redis->hKeys($key);
   		
   	}
   	
   	public function zRange($key, $limit, $offset){
   		if(!$key) return false;
   		return $this->redis->zRange($key, $limit, $offset);
   	}
    
}