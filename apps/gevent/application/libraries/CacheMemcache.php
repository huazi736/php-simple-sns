<?php

/**
 * @desc Memcache
 * @author shedequan
 * @version 1.0
 */
class CacheMemcache {

    private $connection = NULL;         //Memcache资源句柄
    private $group = NULL;              //通过实例化指定的组

    //private $servers = array('192.168.12.183:11211');

    const DEFAULT_GROUP = 'default';    //分组的默认名称
    const EXPIRE = 120;                 //键值的默认过期时间
    const GROUP_EXPIRE = 600;           //分组的默认过期时间

    /**
     * 初始化Memcache服务器
     */
    public function __construct($group = NULL) {
        // 对实例化指定的组名进行大小写统一
        $group = $group ? strtolower($group) : $group;
        if ($group === strtolower(self::DEFAULT_GROUP)) {
            $group = NULL;
        }
        $this->group = $group;

        if (!$this->checked()) {
            throw new Exception('_NOT_SUPPERT_ : Memcache');
        }
        if ($this->connection === NULL) {
            $this->connection = new Memcache();
            // 根据配置信息初始化Memcache服务器
			$configs = require CONFIG_PATH.'memcache.php';
            $configs = $configs["default"];
            $this->addServer($configs['host'], $configs['port']);
        }
    }

    /**
     * 向连接池中添加Memcache服务器
     * @param string $host : 主机
     * @param int $port : 端口
     * @param int $weight : 超时时间
     * @return bool
     */
    public function addServer($host, $port = 11211, $weight = 10) {
        return $this->connection->addServer($host, $port, TRUE, $weight);
    }

    public function setData($key, $data, $ttl = 0, $group = '') {
        if (empty($key)) {
            return false;
        }

        if (empty($group)) {
            return $this->set($key, $data, $ttl);
        }
        return $this->set($key, $data, $ttl, $group);
    }

    public function getData($key, $group = '') {
        if (empty($key)) {
            return false;
        }

        if (empty($group)) {
            return $this->get($key);
        }
        return $this->get($key, $group);
    }

    public function rmData($key = '', $group = '') {
        if (empty($key)) {
            if (empty($group)) {
                // 如果需要禁止删除默认组及其成员，开启下面的注释，返回false
                return false;
                // 删除默认组及其成员
                return $this->rm(false, false);
            }
            // 删除指定组及其成员
            return $this->rm($group, false);
        } else {
            if (empty($group)) {
                // 删除默认组中指定的成员
                return $this->rm($key);
            }
            // 删除指定组中指定的成员
            return $this->rm($key, $group);
        }
    }

    /**
     * 获取
     * 获取默认组的Key      get('key1');
     * 获取指定组的Key      get('key2', 'group1');     
     * 获取组中的值         get('group1', false);
     * @param type $key : 键
     * @param type $group : 键所在的组；为false时，表示获取键名为$key的组
     * @return type 
     */
    public function get($key, $group = self::DEFAULT_GROUP) {
        if ($group !== false && $this->group) {
            $group = $this->group;
        }

        $key = $group ? $group . '_' . $key : $key;
        return $this->connection->get($key);
    }

    /**
     * 存储
     * 创建默认组中的Key    set('key1', 'value', 10);
     * 创建指定组中的Key    set('key2', 'value2', 10, 'group1');
     * 创建指定的组值       set('group1', 'group_keys', 10, false);
     * @param type $key : 键
     * @param type $data : 值 
     * @param type $ttl : 过期时间
     * @param type $group : 键所在的组；为false时，表示创建键名为$key的组
     * @return bool
     */
    public function set($key, $data, $ttl = self::EXPIRE, $group = self::DEFAULT_GROUP) {
        if ($group !== false && $this->group) {
            $group = $this->group;
        }

        if ($group) {
            $groupKeys = $this->get($group, false);
            if ($groupKeys && is_array($groupKeys)) {
                if (!in_array($key, $groupKeys)) {
                    array_push($groupKeys, $key);
                }
                $this->connection->set($group, $groupKeys, 0, self::GROUP_EXPIRE);
            } else {
                $this->connection->set($group, array($key), 0, self::GROUP_EXPIRE);
            }
        }
        $key = $group ? $group . '_' . $key : $key;
        return $this->connection->set($key, $data, 0, $ttl);
    }

    /**
     * 删除
     * 删除默认组的Key      rm('key1');
     * 删除指定组的Key      rm('key2', 'group1');
     * 删除指定的组         rm('group2', false);
     * 删除默认的组         rm(false, false);
     * （当$key,$group都为false时，表示删除默认组及其成员）
     * @param type $key : 键
     * @param type $group : 键所在的组；为false时，表示创建键名为$key的组
     * @return bool 
     */
    public function rm($key, $group = self::DEFAULT_GROUP) {
        if ($group !== false && $this->group) {
            $group = $this->group;
        }

        // 判定要删除的是否为默认组
        $key = ($key === false && $group === false) ? self::DEFAULT_GROUP : $key;

        $groupKey = $group ? $group : $key;
        $groupKeys = $this->get($groupKey, false);
        if ($group) {
            // 删除组中指定的Key
            if (in_array($key, $groupKeys)) {
                $res = $this->connection->delete($group . '_' . $key);
                if ($res) {
                    // 更新组
                    unset($groupKeys[array_search($key, $groupKeys)]);
                    return $this->connection->set($group, $groupKeys, 0, self::GROUP_EXPIRE);
                }
            }
        } else {
            // 删除组
            if (is_array($groupKeys)) {
                foreach ($groupKeys as $itemKey) {
                    $this->connection->delete($key . '_' . $itemKey);
                }
                return $this->connection->delete($key);
            }
        }
        return false;
    }

    /**
     * 清除
     * @return bool 
     */
    public function clean() {
        return $this->connection->flush();
    }

    /**
     * 检测环境是否支持
     * @return bool
     */
    public function checked() {
        if (!extension_loaded('memcache')) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * 释放资源
     */
    public function __destruct() {
        unset($this->connection);
    }

}

?>