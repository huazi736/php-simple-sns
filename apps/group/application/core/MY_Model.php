<?php
/*
 * 群组
 * title :
 * Created on 2012-05-28
 * @author hexin
 */
class MY_Model extends DK_Model
{
	private static $daoInstances = array();
	
    public function __construct()
    {
        parent::__construct();
        $this->init_db('group');
    }
    
	/**
	 * 获取Dao
	 * @param string $dao dao文件名称
	 * @param string $type dao的种类
	 */
	public function getDao($dao, $type = 'db')
	{
		$persistence = $this->config->item('persistence');
		$dao = ucfirst($dao);
		if(isset($type)) {
			$dao = $type . "_" . $dao;
		} else {
			$dao = $persistence . "_" . $dao;
		}
		if(!array_key_exists($dao, self::$daoInstances)) {
			include_once(dirname(__DIR__) . DS . 'models' . DS . 'dao' . DS . 'DaoFactory.php');
			self::$daoInstances[$dao] = DaoFactory::factory($dao);
			
		}
		return self::$daoInstances[$dao];
	}
}

$paths = explode(PATH_SEPARATOR, get_include_path());
$path = dirname(__DIR__) . DS . 'models' . DS . 'dao';
if(!array_search($path, $paths)) {
	spl_autoload_register(function($className) use ($path){
		$paths[] = $path;
		set_include_path(implode(PATH_SEPARATOR, $paths));
		$classPath = explode('_', $className);
		$filename = array_pop($classPath);
		foreach($classPath as &$p) {
			$p = strtolower($p);
		}
		$classPath = $path . DS . implode(DS , $classPath) . DS . $filename . '.php';
		if(file_exists($classPath)) {
			include_once($classPath);
		}
	});
}