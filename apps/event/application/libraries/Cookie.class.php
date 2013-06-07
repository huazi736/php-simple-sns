<?php
class Cookie
{
    // 判断Cookie是否存在
    static function is_set($name) {
        return isset($_COOKIE[config_item('cookie_prefix').$name]);
    }

    // 获取某个Cookie值
    static function get($name) {
        $value   = $_COOKIE[config_item('cookie_prefix').$name];
        $value   =  unserialize(base64_decode($value));
        return $value;
    }

    // 设置某个Cookie值
    static function set($name,$value,$expire='',$path='',$domain='') {
        if($expire=='') {
            $expire =   config_item('cookie_expire');
        }
        if(empty($path)) {
            $path = config_item('cookie_path');
        }
        if(empty($domain)) {
            $domain =   config_item('cookie_domain');
        }
        $expire =   !empty($expire)?    time()+$expire   :  0;
        $value   =  base64_encode(serialize($value));
        setcookie(config_item('cookie_prefix').$name, $value,$expire,$path,$domain);
        $_COOKIE[config_item('cookie_prefix').$name]  =   $value;
    }

    // 删除某个Cookie值
    static function delete($name) {
        Cookie::set($name,'',-3600);
        unset($_COOKIE[config_item('cookie_prefix').$name]);
    }

    // 清空Cookie值
    static function clear() {
        unset($_COOKIE);
    }
}