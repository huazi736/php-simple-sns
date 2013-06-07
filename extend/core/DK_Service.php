<?php

class DK_Service
{
    protected $loader = null;
    
    protected $db = null;
    
    protected $redis = null;
    
    protected $mongodb = null;
    
    protected $httpsqs = null;
    
    protected $solr = null;
    
    protected $storage = null;
    //protected $ci = null;
    
    /**
     * 
     * 
     */
    public function __construct()
    {
        //$this->ci = get_instance();
        $this->loader = load_extend('Loader','core');
    }
    
    protected  function init_db($group = 'default')
    {
         $this->db = $this->loader->database($group,true,true);
    }

    protected function init_redis($group = 'default')
    {
        //$this->redis = $this->loader->redis($group,true);
        $this->redis = get_redis($group);
    }
    
    protected function init_mongo($group = 'default')
    {
        //$this->mongodb = $this->loader->mongodb($group,true);
        $this->mongodb = get_mongodb($group);
    }
    
    protected function init_memcache($group = 'default')
    {
       // $this->memcache = $this->loader->memcache($group,true);
        $this->memcache = get_memcache($group);
    }
    
    protected function init_htppsqs($group = 'default')
    {
        $this->httpsqs = get_httpsqs($group);
    }
    
    protected function init_solr($group = 'default')
    {
        $this->solr = get_solr($group);
    }
    
    protected function init_storage($group = 'default')
    {
        $this->storage = get_storage($group);
    }
    /**
     * 加载函数库
     * @param array|string $helpers
     */
    protected function helper($helpers)
    {
        static $_helpers = array();
        foreach ($this->_ci_prep_filename($helpers, '_helper') as $helper)
		{
		    if (isset($_helpers[$helper]))
			{
				continue;
			}
			foreach(array(EXTEND_PATH,BASEPATH) as $path)
			{
			    if (file_exists($path.'helpers/'.$helper.'.php'))
				{
					include_once($path.'helpers/'.$helper.'.php');

					$_helpers[$helper] = TRUE;
				}
			}
		}
    }
    
    protected function _ci_prep_filename($filename, $extension)
	{
		if ( ! is_array($filename))
		{
			return array(strtolower(str_replace('.php', '', str_replace($extension, '', $filename)).$extension));
		}
		else
		{
			foreach ($filename as $key => $val)
			{
				$filename[$key] = strtolower(str_replace('.php', '', str_replace($extension, '', $val)).$extension);
			}

			return $filename;
		}
	}
    
//    public function __get($name)
//    {
//        return $this->ci->$name;
//    }
}