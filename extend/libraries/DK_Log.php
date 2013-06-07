<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * CodeIgniter
 *
 * An open source application development framework for PHP 5.1.6 or newer
 *
 * @package		CodeIgniter
 * @author		ExpressionEngine Dev Team
 * @copyright	Copyright (c) 2008 - 2011, EllisLab, Inc.
 * @license		http://codeigniter.com/user_guide/license.html
 * @link		http://codeigniter.com
 * @since		Version 1.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * Logging Class
 *
 * @package		CodeIgniter
 * @subpackage	Libraries
 * @category	Logging
 * @author		ExpressionEngine Dev Team
 * @link		http://codeigniter.com/user_guide/general/errors.html
 */
class DK_Log extends CI_Log {
	/**
	 * Constructor
	 */
	protected $log_type = null;
	protected  $mongo = null;
	protected  $log_app = null;
	protected $app_collections = array(
			1=>'front',	
			2=>'video',	
			3=>'album',	
			4=>'ask',	
	);
	
	public function __construct()
	{
		$config =& get_config();
		if (is_numeric($config['log_threshold']))
		{
			$this->_threshold = $config['log_threshold'];
		}
		
		if ($config['log_date_format'] != '')
		{
			$this->_date_fmt = $config['log_date_format'];
		}else{
			$this->_date_fmt = date('Y-m-d H:i:s');
		}
		
		if ($config['log_app']===1)
		{
			$this->log_app = 1 ;
		}else{
			$this->log_app = 0;
		}
		
		if(is_numeric($config['log_type']) && ($config['log_type']==1 || $config['log_type'] ==2)){
			$this->log_type = $config['log_type'];
		}
		
		if($this->log_type==1){
			$this->log_path = LOG_PATH.APP_NAME.'/';
			if(!is_dir($this->log_path)){
				mkdir($this->log_path, 0777);
			}
		}
		
		if($this->log_type==2){
			$this->mongo = get_mongodb('logs');
		}
		
	}
	/**
	 * 重写CI_Log类的write_log方法，装日志记录到mongodb数据库中
	 * @author zengxm
	 * @date <2012/06/30>
	 */
	public function write_log($level = 'error', $msg, $php_error = FALSE)
	{
		if($this->_threshold <=0){
			return '';
		}
		$level = strtoupper($level);
		$level = $this->fitler_type($level);
		if ( ! isset($this->_levels[$level]) OR ($this->_levels[$level] > $this->_threshold))
		{
			return FALSE;
		}
		
		if($this->log_type ==1){
			return $this->log_text($level ,$msg);
		}
		if($this->log_type == 2){
			return $this->log_mongo($level,$msg);
		}
	}
	
	//选择日志记录到哪个集合中
	private function select_collection($coll){
		$level_coll = '';
		switch ($coll) {
			case 'ERROR':
				$level_coll ='ERROR';
				break;
			case 'DEBUG':
				$level_coll ='DEBUG';
				break;
			case 'INFO':
				$level_coll ='INFO';
				break;
			case 'ALL':
				$level_coll ='ALL';
				break;
			default:'ERROR';
				$level_coll = 'ERROR';
			break;
		}
		return $level_coll;
	}
	
	
	/*记录用户日志
	 * $uid 为用户的uid或是端口号
	 * $msg 为数组，里面可能是模块名，方法名，文件名等
	 * $time为日志记录的时间
	 * 2012-5-28 zengxiangmo
	 */
	public function write_user_log( $uid = '',$msg = array(), $time='',$db="USER"){
		if(!$time){
			$time = date($this->_date_fmt,time());
		}
		$doc = array(
				'uid'=>$uid,
				'msg'=>$msg,
				'time'=>$time,
		);
		if(is_null($this->mongo)){
			$this->mongo = get_mongodb('logs');
		}
		return $this->mongo->insert($db,$doc);
	}
	
	//用文件方式记录
	private  function log_text($level,$msg){

		$msg = print_r($msg,true);
		
		$filepath = $this->log_path.'log-'.date('Y-m-d').'.php';
		$message  = '';

		if ( ! file_exists($filepath))
		{
			$message .= "<"."?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed'); ?".">\n\n";
		}

		if ( ! $fp = @fopen($filepath, FOPEN_WRITE_CREATE))
		{
			return FALSE;
		}

		$message .= $level.' '.date($this->_date_fmt). "\n".$msg."\n\n";

		flock($fp, LOCK_EX);
		fwrite($fp, $message);
		flock($fp, LOCK_UN);
		fclose($fp);

		@chmod($filepath, DIR_WRITE_MODE);
		return TRUE;
	
	}
	
	//用mongodb方试记录
	private function log_mongo($level,$msg){
		if(in_array($level,array('ERROR','DEBUG','INFO','ALL'))){
			$collection = $this->select_collection($level);
			
			if(is_string($msg)){
				$msg = array($msg);
			}
			$ret = $this->mongo->insert($collection,$msg);
			return $ret;
		}
		
	}
	
	private function fitler_type($level){
		
		//$this_threshold 为1时，记录$errors中的错误
		$errors = array('ERROR','PARSING ERROR','CORE ERROR','COMPILE ERROR','USER ERROR');
		
		//$this_threshold 为2时，记录$warings中的错误
		$warnings = array('WARNING','CORE WARNING','COMPILE WARNING','USER WARNING','DEBUG');
		
		//$this_threshold 为3时，记录$notices中的错误
		$notices = array('NOTICE','USER NOTICE','RUNTIME NOTICE','INFO');
		
		$level_error = false;
		if($this->_threshold ==1){
			if(in_array($level, $errors)){
				$level_error = 'ERROR';
			}
		}else if ($this->_threshold ==2){
			if(in_array($level, $warnings)){
				$level_error = 'DEBUG';
			}
		}else if ($this->_threshold ==3){
			if(in_array($level, $notices)){
				$level_error = 'INFO';
			}
		}else if($this->_threshold == 4){
			$level_error = 'ALL';
		}else{
			//return false;
			$level_error = 'ERROR';
		}
		return $level_error;
	}
	
/*记录应用日志
	 * $uid 为用户的uid或是端口号
	 * $msg 为数组，里面可能是模块名，方法名，文件名等
	 * $time为日志记录的时间
	 * 2012-7-24 zengxiangmo
	 */	
public function write_apps_log( $uid = '',$type='',$msg = array(), $time=''){
		if(!$time){
			$time = date($this->_date_fmt,time());
		}
		if(array_key_exists($type, $this->app_collections)){
			$collection = $this->app_collections[$type];
		}else{
			return false;
		}
		$doc = array(
				'uid'=>$uid,
				'type'=>$type,
				'msg'=>$msg,
				'time'=>$time,
		);
		if(($this->log_app)){
			$app_mon = get_mongodb('applogs');
			return $app_mon->insert($collection,$doc);
		}else{
			return false;
		}
	}
	
}	
// END Log Class

/* End of file Log.php */
/* Location: ./system/libraries/Log.php */