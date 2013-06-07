<?php

class DK_Memcache
{
    private static $instance = array();
    
    protected $handler = null;
    protected $options = null;
    
    private function __construct($config='')
    {
        $options = array();
        if(!extension_loaded('memcache'))
        {
            exit('memcache class not exists');
        }
        $options = $this->getConfig($config);
		
        if(isset($options['host'])){
        	$hosts = explode(',', $options['host']);
        }
        
    	if(isset($options['port'])){
        	$ports = explode(',', $options['port']);
        }
        
        if(isset($options['timeout'])){
        	$timeouts = explode(',', $options['timeout']);
        }
         
        if(isset($options['persistent'])){
        	$persistents = explode(',', $options['persistent']);
        }
		
        //判断有多少个主机数
        $hostnum =  count($hosts);
        $this->handler = new Memcache();
    	if($hosts && $hostnum > 0){
        	foreach($hosts as $index => $host){
        		//查找是否设置了对应主机的端口号
        		 $port = isset($ports[$index]) ? $ports[$index] : 11211;
        		 
        		 //查找对应主机是长联接还是短联接 (如果没有设置，则返回true)
        		 $pers = isset($persistents[$index])  ? $persistents[$index] : true;
        		 
        		 //查找对应主机是否设置了联接的超时时间
        		 $timeout = (isset($timeouts[$index])  && ($timeouts[$index] >0) ) ? $timeouts[$index]  : 300 ;
				 
        		 //添加memcache联接信息到memcache联接池中
        		 $this->addServer($host, $port,$pers,$timeout);
        	}
        }else{
        	log_message('error',array('memcache没有配置项'));
        }
        
    }
    
    
    /**
     * 
     * 得到一个memcache对象
     * @param array $config
     * @return object
     */
    public static function getInstance($config='')
    {
        $key = is_string($config) ?  md5($config):md5(serialize($config));
        if(!isset(self::$instance[$key]))
        {
            self::$instance[$key] = new DK_Memcache($config);
        }
        return self::$instance[$key];
    }
    
    /**
     * 
     * @param string|array $config
     * @return array
     */
    private function getConfig($config)
    {
        if(is_string($config) and !empty($config))
        {
            $configs = include(CONFIG_PATH . 'memcache.php');
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
     * 通过魔术方法__call执行memcache对象的方法
     * @param string $method
     * @param array $method 
     * @return mixd
     */
    public function __call($method,$args)
    {
        return call_user_func_array(array($this->handler,$method),$args);
    }
    
    /**
     * 向连接池中添加Memcache服务器
     * @param string $host : 主机
     * @param int $port : 端口
     * @param int $weight : 超时时间
     * @return bool
     */
    public function addServer($host, $port = 11211,$pers=true,$timeout=300,$weight = 10) {
        return $this->handler->addServer($host, $port, $pers, $weight,$timeout);
    }
}