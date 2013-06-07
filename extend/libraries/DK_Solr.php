<?php

class DK_Solr {

    protected static $register = false;
    
    protected $service = null;
    
    private static $configs = null;
    
    private static $spell = null;

    public function __construct($config = '')
    {
        if (self::$configs == null)    self::$configs = $this->getConfig($config);
        
        self::$register = self::$register === false ? spl_autoload_register(array(__CLASS__, "solrLoad")) : true;          
    }

    private static function solrLoad($className) 
    {   
        $path = rtrim(EXTEND_PATH, '/ ') . "/vendor/Solr/" . str_replace("_", "/", $className . ".php");
        
        if (file_exists($path))  include_once $path;
    }

    public function getSolr($flag = 'global')
    {
        static $service = array();
        
        if (!isset(self::$configs[$flag])) return null;
        
        $host = self::$configs[$flag]['host'];
        $path = self::$configs[$flag]['path'];
        $port = self::$configs[$flag]['port'];
       
        if(!isset($service[$flag]))
        {
            $service[$flag] = new Apache_Solr_Service($host, $port, $path, new Apache_Solr_HttpTransport_Curl()); 
        }
        return $service[$flag];
    }
    
    private function getConfig($config)
    {
        if(is_string($config) and !empty($config))
        {
            $configs = include(CONFIG_PATH . 'solr.php');
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
     * 添加一条数据到solr
     * 
     * Enter description here ...
     * @param array $doc 必须是键值对,且solr有此字段. 注:(唯一字段必须有)
     * @param object $solr 必须为DK_Solr::getSolr()中获取的元素
     * @return 成功返回true,错误抛出Apache_Solr_Exception 异常
     */
    public function addDoc($doc = array(), $solr = null)
    {
    	if (!$solr instanceof Apache_Solr_Service) return false;
    	
    	$document = new Apache_Solr_Document();
    	
    	foreach ($doc as $key => $val)
    	{
    		$document->$key = $val;
    	}
		
    	return $this->execute($document, $solr);
    }
     /**
      * 添加多条数据到solr
      * 
      * @param array $doc 必须是键值对,且solr有此字段. 注:(唯一字段必须有)
      * @param object $solr 必须为DK_Solr::getSolr()中获取的元素    
      * @return 成功返回true,错误抛出Apache_Solr_Exception 异常
      */
    public function addDocs($docs = array(), $solr = null)
    {
    	if (!$solr instanceof Apache_Solr_Service) return false; 
    	
    	$documents = array();
    	
        foreach ($docs as $num => $doc) 
        {	
			$documents[$num] = new Apache_Solr_Document();
			
			foreach ($doc as $key => $val)
			{
				$documents[$num]->$key = $val;
			}
		}   
		
		return $this->execute($documents, $solr, 'addDocuments');
    }
    
    public function deleteByQuery($query, $solr=null)
    {
    	if (! $solr instanceof Apache_Solr_Service) return false;

    	return $this->execute($query, $solr, 'deleteByQuery');
    }
    
    public function execute($document, $solr, $method = 'addDocument')
    {
    	$boolean = true;
    	
		try
		{
			$solr->{$method}($document);
			
			$solr->commit();
			
		}catch (Apache_Solr_Exception $e){
                        //如出现异常 可以在此做数据日志记录
			$boolean = false;
		}

		return $boolean;  
    }
    
    public function query($solr, $query, $start = 0, $rows  = 10, $params= array(), $method='GET')
    {
    	if (! $solr instanceof Apache_Solr_Service) return false;
    	
    	try{
    		$response = $solr->search($query, $start, $rows, $params, $method);
    		
    		$result = json_decode($response->getRawResponse());
    		
    	}catch (Apache_Solr_Exception $e){
                //如出现异常 可以在此做数据日志记录
    		$result = false;
    	}
    	return $result;
    }
    
    public function getEmptyJSON()
    {
    	return json_decode('{"response":{"numFound":0,"docs":[]}}');
    }
    
    public function chinese2Pinyin($chinese)
    {
        if(preg_match("#^[a-zA-Z\\s0-9·]*$#", $chinese)) return $chinese;

        preg_match("#^(\\d*)(.*)$#", $chinese, $matches);

    	if (!is_string($chinese) || trim($matches[2]) == "") return $chinese;
    	
    	if (self::$spell == null) self::$spell = new Pinyin_Chinese2Pinyin();
              
    	$spell = $matches[1].self::$spell->getPinyin($matches[2]);
              
        return $spell;
    }
}