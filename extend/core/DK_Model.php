<?php

class DK_Model
{
    protected $loader = null;
    
    protected $db = null;
    
    protected $redis = null;
    
    protected $mongodb = null;
    
    protected $httpsqs = null;
    
    protected $storage = null;
    
    protected $memcache = null;

    protected $ci = null;
    
    
    /**
     * 
     * 
     */
    public function __construct()
    {
        $this->ci = get_instance();
        $this->loader = load_class('Loader','core');
    }
    
    protected  function init_db($group = 'default')
    {
        $this->db = $this->loader->database($group,true,true);
        get_dbs($group, $this->db);
    }

    protected function init_redis($group = 'default')
    {
        //$this->redis = $this->loader->redis($group,true);
         $this->redis = get_redis($group);
    }
    
    protected function init_mongodb($group = 'default')
    {
        //$this->mongodb = $this->loader->mongodb($group,true);
        $this->mongodb = get_mongodb($group);
    }
    
    protected function init_memcache($group = 'default')
    {
        //$this->memcache = $this->loader->memcache($group,true);
        $this->memcache = get_memcache($group);
    }
    
    protected function init_htppsqs($group = 'default')
    {
       // $this->memcache = $this->loader->memcache($group,true);
        $this->httpsqs = get_httpsqs($group);
    }
    
    protected function init_storage($group = 'default')
    {
        $this->storage = get_storage($group);
    }


    public function __get($name)
    {
        return $this->ci->$name;
    }
    
}