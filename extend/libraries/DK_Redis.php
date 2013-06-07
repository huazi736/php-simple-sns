<?php

class DK_Redis
{
    private static $instance = array();
    
    protected $handler = null;
    protected $options = null;
    
    private function __construct($config='')
    {
        $options = array();
        if(!extension_loaded('redis'))
        {
            //
            exit('redis class not exists');
        }
        
        $options = $this->getConfig($config);

        $this->handler = new Redis();
        $func = $options['persistent'] ? 'pconnect' : 'connect';
        if($options['timeout'] === false)
        {
            $this->handler->$func($options['host'],$options['port']);
        }
        else
        {
            $this->handler->$func($options['host'],$options['port'],$options['timeout']);
        }
        if(!empty($options['auth']))
        {
            $this->handler->auth($options['auth']);
        }
        if(isset($options['db']) && $options['db'] != 0)
        {
            $this->handler->select($options['db']);
        }
    }
    
    public static function getInstance($config='')
    {
        $key = is_string($config) ?  md5($config):md5(serialize($config));
        if(!isset(self::$instance[$key]))
        {
            self::$instance[$key] = new DK_Redis($config);
        }
        return self::$instance[$key];
    }
    
    private function getConfig($config)
    {
        if(is_string($config) and !empty($config))
        {
            $configs = include(CONFIG_PATH . 'redis.php');
            $config = isset($configs[$config]) ? $configs[$config] : false;
        }
        
        if(is_array($config) and count($config)>0)
        {
            foreach($config as $key=>$value)
            {
                $config[$key] = $value;
            }
        }
        return $config;
    }

    /**
     * 
     */
    public function __call($method,$args)
    {
        return call_user_func_array(array($this->handler,$method),$args);
    }
}