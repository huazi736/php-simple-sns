<?php

!defined('DS') && define('DS', DIRECTORY_SEPARATOR);
!defined('EXT') && define('EXT', '.php');
!defined('__ROOT__') && define('__ROOT__', dirname(dirname(dirname(__FILE__))));
!defined('API_ROOT') && define('API_ROOT', __ROOT__ . '/api');
/**
 * 运行级别
 * 与DKBase::throwError()有关
 * 1 development 开发环境
 * 2 test        测试环境
 * 4 product     生产环境
 */
define('RUN_LEVEL', 1);

/**
 * DKBase 基类
 *
 * @author fbbin
 */
class DKBase
{

    private static $_instance = array();

	/**
	 * DKApi操作信息
	 * @var array
	 */
	protected static $infos = array();

	/**
	 * 最近的出错信息
	 * @var string
	 */
	protected static $infoMessage = '';

	/**
	 * 最近的出错编码
	 * @var integer
	 */
	protected static $infoCode = 0;

    /**
     * 自动变量设置
     * @access public
     * @param $name 属性名称
     * @param $value  属性值
     */
    public function __set($name ,$value)
    {
        if(property_exists($this,$name))
        {
            $this->$name = $value;
        }
    }

    /**
     * 自动变量获取
     * @access public
     * @param $name 属性名称
     * @return mixed
     */
    public function __get($name)
    {
        return isset($this->$name) ? $this->$name : null;
    }

    /**
     * 模型文件导入
     * @access public
     * @param $name 属性名称
     * @return mixed
     */
    static public function import( $className = '', $path = '', $returnObj = true, $params = array() )
    {
        if ( $path )
        {
            $path = strtolower($path) . DS;
            $className .= 'Model';
        }
        else
        {
            $className .= 'Api';
        }
        $filePath = API_ROOT. DS . $path . ucfirst($className) . EXT;
        if ( file_exists(realpath($filePath)) )
        {
            include_once $filePath;
            if( $returnObj )
            {
                return self::instance( $className, $params );
            }
        } else {
            $data = array('path'=>$filePath);
            return DKBase::status(false, 'import_file_not_exists', 1002, $data);
        }
        return false;
    }

    /**
     * 取得对象实例
     * @param string $class 对象类名
     * @param string $method 类的静态方法名
     * @return object
     */
    static public function instance($class, $params = array())
    {
        $identify = $class.md5(json_encode($params));
        if( !isset(self::$_instance[$identify]) )
        {
            if( class_exists($class) )
            {
                $o = new $class($params);
                self::$_instance[$identify] = $o;
            } else {
                $data = array('class'=>$class);
                return DKBase::status(false, 'class_not_exists', 1001, $data);
            }
        }
        return self::$_instance[$identify];
    }

    /**
     * 加载函数库
     * @param array|string $helpers
     */
    static public function helper($helpers)
    {
        static $_helpers = array();
        foreach (self::_ci_prep_filename($helpers, '_helper') as $helper)
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
    
    static private function _ci_prep_filename($filename, $extension)
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

	/**
	 * 封装返回数据
	 *
	 * @param mixed
	 * @param string
	 * @param integer
	 * @param array
	 * @return mixed
	 */
	public final static function status($ret, $message='', $code=0, $data=array()) {
		if(!$ret && RUN_LEVEL < 4) {
			if(RUN_LEVEL < 2) throw new DkException($message, $code, $data);
			$info = array(
				'ret' => $ret,
				'message' => $message,
				'code' => $code,
				'data' => $data
			);
			array_unshift(self::$infos, $data);
            log_message('error', $info);
		}
		self::$infoMessage = $message;
		self::$infoCode = $code;
		return $ret;
	}

	/**
	 * 获取最后的信息
	 * @return string
	 */
	public static function getMessage() {
		return self::$infoMessage;
	}

	/**
	 * 获取最后的代码
	 * @return integer
	 */
	public static function getCode() {
		return self::$infoCode;
	}

	/**
	 * 获取所有信息
	 * @return array
	 */
	public static function getInfos($index = null) {
		$infos = self::$infos;
		if(!empty($infos) && array_key_exists($index, $infos)) {
			return $infos[$index];
		} else {
			return $infos;
		}
	}

}

/**
 * DkApi 基类
 *
 * @author fbbin
 */
class DkApi extends DKBase
{

    /**
     * 构造函数
     */
    final public function __construct()
    {
       if( method_exists($this, '__initialize') )
       {
            $this->__initialize();
       }
    }

}

/**
 * DkModel 模型基类
 *
 * @author fbbin
 */
class DkModel extends DKBase
{
    protected $loader = null;
    
    protected $db = null;
    
    protected $redis = null;
    
    protected $mongodb = null;
    
    protected $httpsqs = null;
    
    protected $solr = null;
    
    protected $storage = null;
    
    protected $memcache = null;

    /**
    * construct
     */
    final public function __construct()
    {
        if( method_exists($this, '__initialize') )
        {
            $this->__initialize();
        }
    }
    
    protected function init_db($group = 'user', $mode = 'mysql')
    {
        if( $mode == 'custom' )
		{
            static $_db = array();
            if( isset($_db[$group]) )
            {
                $params = $_db[$group];
            }
            else
            {
                 $configFile = __ROOT__ . DS . 'config' . DS . 'database' . EXT;
                if( file_exists($configFile) )
                {
                    include_once $configFile;
                    if( isset($db[$group]) ) {
                        $_db = $db;
                        $params = $_db[$group];
                        unset($db);
                    }
                } else {
                    return DKBase::status(false, 'unknow_db_config', 1003);
                }
            }
            $this->db = DKBase::import('common', 'common', true, $params);
		} 
        else
        {
            $this->loader = load_extend('Loader','core');
            $this->db = $this->loader->database($group, true, true);
        }
    }

    protected function init_redis($group = 'default')
    {
        $this->redis = get_redis($group);
    }
    
    protected function init_mongo($group = 'default')
    {
        $this->mongodb = get_mongodb($group);
    }
    
    protected function init_memcache($group = 'default')
    {
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

}

/**
 * DkException 异常处理类
 *
 * @author fbbin
 * 
 */
class DkException extends Exception {

	/**
	 * 参与数据
	 * @var array
	 */
	protected $data;

	/**
	 * construct function
	 */
	public function __construct($message, $code=0, $data=array(), Exception $previous = NULL) {
		parent::__construct($message, $code, $previous);
		$this->data = $data;
	}

	/**
	 * 获取data数据
	 * @return string
	 */
	public function getData() {
		return $this->data;
	}

}
