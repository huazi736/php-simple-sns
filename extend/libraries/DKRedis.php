<?php
/**
 * Redis 操作，支持 Master/Slave 的负载集群
 * Created on 2012-7-6
 * @author fbbin
 */

class DKRedis
{
    // 服务器连接句柄、只支持一台 Master以有多台 Slave
    private static $_linkHandle = array('master'=>null, 'slave'=>array());

    // 对象单例
    private static $_instance = array();

    // 配置项
    private static $_conf = array();

    /**
     * 构造函数
     * @author fbbin
     * @param configs  服务器配置参数
     */
    public function __construct( $configs = array() )
    {
        //控制只能一台master
        $countMaster = 0;
        foreach ($configs as $identify => $config)
        {
            $countMaster == 1 && $config['ismaster'] = false;
            $initStatus = self::_initConnect( $config );
            if( $initStatus === false )
            {
                unset(self::$_conf[$identify]);
                continue;
            }
            if( isset($config['ismaster']) && $config['ismaster'] )
            {
                $countMaster = 1;
            }
        }
    }

    /**
     * 连接服务器,注意：这里使用长连接，提高效率，但不会自动关闭
     * @author fbbin
     * @param array $config Redis服务器配置
     * @return boolean
     */
    private static function _initConnect( array $config )
    {
        // default port
        !isset($config['port']) && $config['port'] = 6379;
        // 设置 Master 连接
        if( isset($config['ismaster']) && $config['ismaster'] )
        {
            self::$_linkHandle['master'] = new Redis();
            try {
                $ret = self::$_linkHandle['master']->pconnect($config['host'], $config['port'], $config['timeout']);
				self::$_linkHandle['master']->select($config['db']);
                isset($config['auth']) && self::$_linkHandle['master']->auth($config['auth']);
            } catch (Exception $e) 
            {
                echo $e->getMessage();//日志记录
                return false;
            }
        }
        else
        {
            // 多个 Slave 连接
            $identified = md5($config['host'].$config['port']);
            self::$_linkHandle['slave'][$identified] = new Redis();
            try {
                $ret = self::$_linkHandle['slave'][$identified]->pconnect($config['host'], $config['port'], $config['timeout']);
				self::$_linkHandle['slave'][$identified]->select($config['db']);
                isset($config['auth']) && self::$_linkHandle['slave'][$identified]->auth($config['auth']);
            } catch (Exception $e) 
            {
                unset($this->_linkHandle['slave'][$identified]);
                echo $e->getMessage();//日志记录
                return false;
            }
        }
        return $ret;
    }

    /**
     * 解析配置信息
     * @author fbbin
     * @return boolean
     */
    private static function parseConfig()
    {
        $configs = array();
        if( self::$_conf )
        {
            return self::$_conf;
        }
        $configFilePath =  CONFIG_PATH . 'redis.php';
        if (file_exists( $configFilePath ))
        {
            $configs = include $configFilePath;
        }
        if( empty($configs) )
        {
            $configs = array(array('host'=>'192.168.12.205', 'port'=>6379, 'ismaster'=>true, 'db'=>0, 'weight'=>100));
        }
        foreach ($configs as $key => $value)
        {
            $configs[$key]['weight'] = isset($value['weight']) ? $value['weight'] : 1;
			$configs[$key]['db'] = isset($value['db']) ? $value['db'] : 1;
        }
        return self::$_conf = $configs;
    }

    /**
     * 关闭连接
     * @author fbbin
     * @param int $flag 关闭选择 0:关闭 Master 1:关闭 Slave 2:关闭所有
     * @return boolean
     */
    public function close( $flag = 2 )
    {
        switch( $flag )
        {
            // 关闭 Master
            case 0:
                $this->getRedis()->close();
            break;
            // 关闭 Slave
            case 1:
                foreach(self::$_linkHandle['slave'] as $handler)
                {
                    $handler->close();
                }
            break;
            // 关闭所有
            case 2:
                $this->getRedis()->close();
                foreach(self::$_linkHandle['slave'] as $handler)
                {
                    $handler->close();
                }
            break;
        }
        return true;
    }

    /**
     * 取得redis类实例
     * @author fbbin
     * @static
     * @access public
     * @return mixed | object
     */
    public static function getInstance()
    {
        $configs = self::parseConfig();
        $identify = md5(json_encode($configs));
        if( !isset(self::$_instance[$identify]) || !(self::$_instance[$identify] instanceof self) )
        {
            self::$_instance[$identify] = new self($configs);
        }
        return self::$_instance[$identify];
    }

    /**
     * 清空当前数据库
     * @author fbbin
     * @return boolean
     */
    public function clear()
    {
        return $this->getRedis()->flushDB();
    }

    /**
     * 得到 Redis 原始对象可以有更多的操作
     * @author fbbin
     * @param boolean $param 返回服务器的类型 true:返回Master false:返回Slave;string:返回指定配置的redis
     * @param boolean $slaveOne 返回的Slave选择 true:负载均衡随机返回一个Slave选择 false:返回所有的Slave选择
     * @return redis object
     */
    public function getRedis($param = true, $slaveOne = true)
    {
        //返回指定配置的redis组
        if( is_string($param) )
        {
            $configs = self::$_conf;
            if( isset($configs[$param]) )
            {
                return self::$_linkHandle['slave'][md5($configs[$param]['host'].$configs[$param]['port'])];
            }
            return NULL;
        }
        // 只返回 Master
        elseif( $param )
        {
            return self::$_linkHandle['master'];
        }
        // 返回Slave
        else
        {
            return $slaveOne ? self::_getSlaveRedis() : self::$_linkHandle['slave'];
        }
    }

    /**
     * 根据weight得到 Redis Slave 服务器句柄
     * @author fbbin
     * @return redis object
     */
    private static function _getSlaveRedis()
    {
        // 就一台 Slave 机直接返回
        if( count(self::$_linkHandle['slave']) < 2 )
        {
            return array_shift(self::$_linkHandle['slave']);
        }
        // 根据 weight 得到 Slave 的句柄
        return self::$_linkHandle['slave'][self::_getServerByWeight()];
    }
    
    /**
     * 根据 weight 选择列表服务器
     * @author fbbin
     * @return string
     */
    private static function _getServerByWeight()
    {
        $services = self::$_conf;
        $weight = 0;
        $tempData = array();
        foreach ($services as $item)
        {
            //过滤掉主服务器
            if( isset($item['ismaster']) && $item['ismaster'] )
            {
                continue;
            }
            $weight += $item['weight'];
            for ($i = 0; $i < $item['weight']; $i++)
            {
                $tempData[] = $item;
            }
        }
        $index = rand(0, $weight-1);
        return md5($tempData[$index]['host'].$tempData[$index]['port']);
    }

    // 防止被clone
    private function __clone(){}

}

?>


