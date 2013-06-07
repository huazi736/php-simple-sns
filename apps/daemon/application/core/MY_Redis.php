<?php

/**
 * Redis模型，负责从redis中取数据
 * @author 应晓斌
 */
class MY_Redis
{
	/**
	 * Redis 连接
	 * @var Redis
	 */
	protected static $_redis;
	
	private function __construct() {}
	
	/**
	 * 获取一个Redis的连接
	 */
	public static function getInstance()
	{
        if (null === self::$_redis) {
			self::$_redis = new Redis();                
			try {
				$redis_config = config_item('redis');
				self::$_redis->connect($redis_config['host'], $redis_config['port']);
				self::$_redis->auth($redis_config['pwd']);
			} catch (RedisException $e) {
				//@todo: 捕获redis连接失败异常
				die('redis 连接失败');
			}
		}
		
		return self::$_redis;
	}
	
	private function __clone(){}
	
	private function __destruct()
	{
		self::$_redis = null;
	}
}