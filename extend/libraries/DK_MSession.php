<?php

class DK_MSession
{
    private static $instance;
    private $key = 'sess_';
    private $sess_id = null;
    private $mc = null;
    private $timeout = 120;
    
    
    private function __construct()
    {
        $this->init_mc();
    }
    
    public static function getInstance()
    {
        if(!isset(self::$instance))
        {
            self::$instance = new DK_MSession();
        }
        return self::$instance;
    }
    
    /**
     * 
     * 初始化一个memcache对象赋给$this->mc
     */
    public function init_mc()
    {
      	$this->mc = get_memcache('session');
    }
    
    /**
     * 
     * @param string $path
     * @param string $name
     */
    public function open($path,$name)
    {
        $this->init_mc();
    }
    
    /**
     * 
     * 关闭memcache的连接
     */
    public function close()
    {
        return $this->mc->close();
    }
    
    /**
     * 
     * 把SESSION信息写到memcache中
     * @param string $id
     * @param string $data
     */
    public function write($id,$data)
    {
        $key = md5($this->key . $id);		
        return $this->mc->set($key,$data,false,$this->timeout);
    }
    
    /**
     * 
     * 根据session_id从memcache中取得数据
     * @param string $id
     * @return mixd
     */
    public function read($id)
    {
    	$key = $this->key . $id;
    	
        $key = md5($key);
        
        $data = $this->mc->get($key);
		if(!empty($data)){
			return $data;
		}
		else{			
			return '';
		}
        // return $data ? $data : '';
    }
    
    /**
     * 
     * 销毁session
     * @param string $id
     * @return boolean
     */
    public function destroy($id)
    {
        $key = md5($this->key . $id);
        return $this->mc->delete($key);
    }
    
    /**
     * 
     * 回收过期的seesion
     * @param string $maxlifetime
     * @return boolean
     */
    public function gc($maxlifetime)
    {
        return true;
    }
}