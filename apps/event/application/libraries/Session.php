<?php

/**
 * Session 会话
 * @author shedequan
 */
class Session {

    protected static $_session;
    protected static $_cache_handler;

    public static function getInstance() {
        if (null === self::$_session) {
			self::$_session = new Session();
            
            require_cache(APPPATH . 'libraries' . DS . 'CacheMemcache.php');
            self::$_cache_handler = new CacheMemcache();
		}
		return self::$_session;
    }

    public function getData($sessionId, $key) {
        $key = $sessionId . $key;
        return self::$_cache_handler->get($key);
    }
    
    public function setData($sessionId, $key, $data, $ttl = null) {
        $key = $sessionId . $key;
        return self::$_cache_handler->setData($key, $data, $ttl);
    }
    
    public function delData($sessionId, $key) {
        $key = $sessionId . $key;
        return self::$_cache_handler->rmData($key);
    }

    // ---------------------
    
    static function get_id() {
        return md5(microtime() . mt_rand());
    }
    
}

