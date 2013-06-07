<?php


class Redisdb{
	
	private $redis;
	
	function __construct()
	{
		$this->redis 	= new Redis();
		$redis_arr 		= config_item("redis");
		try {
			$this->redis->connect($redis_arr['host'],$redis_arr['port']);
			if($redis_arr['pwd']!='' ) $this->redis->auth($redis_arr['pwd']);
		} catch (RedisException $e) {
			
		}
	}
	
	
	/**
	 * 设置  建值
	 * $key   	建
	 * $val		值
	 * $time 	时间    0 不限时间
	 * return 0 失败    1 成功
	 * */
	public function set($key,$val,$time=0){

		if($time<0)	$time = abs($time);
		if($time==0){
			return $this->redis->set($key,$val);
		}else{
			return $this->redis->setx($key,$val,$time);
		}
	}
	
	
	/**
	 * 获得值
	 * $key  建		// 可以是数组，也可以是值
	 * 值
	 * **/
	public function get($key){

		if(is_array($key)){
			//$key	= array('key1','key2','key3');
			// return $this->redis->mget($key1,$key2,$key3);
			foreach($key as $key_val){
				if($key_val!=''){
					$arr[$key_val]	= $this->redis->get($key_val);
				}
			}
			return $arr;
		}else{
			return $this->redis->get($key);
		}
	}
	
	
	
	// 获得多个 建     值如    “test:category:*”
	public function gets($key){
		return $this->redis->getKeys($key);
	}
	
	
	/**
	 * 删除值
	 * $key		建
	 * return  0,1
	 * **/
	public function delete($key){
		return $this->redis->delete($key);
		
	}
	
	
	/**
	 * 判断key 是否存再
	 * return 0,1
	 * **/
	public function exists($key){
		return $this->redis->exists($key);
	}
	
	
	
}