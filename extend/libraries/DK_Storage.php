<?php
/**
 * 文件存储类
 * 实现文件存储的统一操作
 * @author mawenpei<mawenpei@duankou.com>
 * @date <2012/02/26>
 *
 */
class DK_Storage
{
	private static $instance = array();

	private $fdfs = null;

	private $options;

	protected $tracker;
	protected $storage;
	protected $group;

	public function __construct($config='')
	{
		if(!class_exists('FastDFS'))
		{
			exit('FastDFS class not exists');
		}
		$this->options = $this->getConfig($config);
		$this->group = $this->options['group'];
		$this->fdfs = new FastDFS();
		$res = $this->fdfs->connect_server($this->options['host'],$this->options['port']);
		if($res)
		{
			$this->tracker = $res;
		}
		else
		{
			$this->tracker = $this->fdfs->tracker_get_connection();
		}
		$this->storage = $this->fdfs->tracker_query_storage_store($this->group, $this->tracker);
	}

	/**
	 * 获取存储类的实例
	 * @param host 存储服务器的IP地址
	 * @param port 存储服务器的端口
	 * @param group 存储服务器的分组
	 */
	public static function getInstance($config='')
	{
		$key = is_string($config) ?  md5($config):md5(serialize($config));
		if(!isset(self::$instance[$key]))
		{
			self::$instance[$key] = new DK_Storage($config);
		}
		return self::$instance[$key];
	}

	/**
	 * 获取配置文件
	 */
	private function getConfig($config)
	{
		if(is_string($config) and !empty($config))
		{
			$configs = include(CONFIG_PATH . 'fastdfs.php');
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
	 * 上传文件
	 * @param string local_filename 本地文件名
	 * @param string file_ext 文件扩展名,不包括(.)符号
	 * @param array  meta 文件元数据
	 * @return array|string 成功返回文件信息数组,失败返回错误信息
	 */
	public function uploadFile($local_filename,$file_ext = null, $meta = array())
	{
		$file_info = $this->fdfs->storage_upload_by_filename($local_filename,$file_ext,$meta,$this->group,$this->tracker);
		if(is_array($file_info))
		{
			return $file_info;
		}
		return $this->fdfs->get_last_error_info();
	}

	/**
	 * 上传文件,通过文件流
	 * @param string file_buff 文件流
	 * @param string file_ext 文件扩展名,不包括(.)符号
	 * @param array  meta 文件元数据
	 * @return array|string 成功返回文件信息数组,失败返回错误信息
	 */
	public function uploadFileByBuff($file_buff,$file_ext = null, $meta = array())
	{
		$file_info = $this->fdfs->storage_upload_by_filebuff($file_buff,$file_ext,$meta,$this->group,$this->tracker);
		if(is_array($file_info))
		{
			return $file_info;
		}
		return $this->fdfs->get_last_error_info();
	}

	/**
	 * 上传从属文件
	 * @param string local_filename 本地文件名
	 * @param string master_filename 主文件名
	 * @param string prefix 从文件后缀
	 * @param string file_ext 文件扩展名
	 * @param array meta 文件元数据
	 *
	 * @return array|string 成功返回文件信息数组,失败返回错误信息
	 */
	public function uploadSlaveFile($local_filename,$master_filename,$prefix,$file_ext = null, $meta = array(), $group_name = '')
	{
		if (empty($group_name)) {
			$group_name = $this->group;
		}
		if(empty($local_filename) || empty($master_filename) || empty($prefix)) {
			return false;
		}
		$res = $this->fdfs->storage_upload_slave_by_filename($local_filename, $group_name, $master_filename, $prefix, $file_ext, $meta, $this->tracker);
		if($res){
			return $res;
		}else{
		 $error = $this->fdfs->get_last_error_info();
		 return $error;
			// log
		 // return false;
		}
	}

	/**
	 * 上传从属文件,通过文件流
	 * @param string master_filename 主文件名
	 * @param string prefix 从文件后缀
	 * @param string file_ext 文件扩展名
	 * @param array meta 文件元数据
	 *
	 * @return array|string 成功返回文件信息数组,失败返回错误信息
	 */
	public function uploadSlaveFileByBuff($file_buff,$master_filename,$prefix,$file_ext = null, $meta = array(), $group_name = '')
	{
		if (empty($group_name)) {
			$group_name = $this->group;
		}
		$file_info = $this->fdfs->storage_upload_slave_by_filebuff($file_buff,$group_name,$master_filename,$prefix,$file_ext,$meta,$this->tracker);
		if(is_array($file_info))
		{
			return $file_info;
		}
		return $this->fdfs->get_last_error_info();
	}

	/**
	 *  获取文件地址
	 *
	 */
	//	public function get_file_url($mfile,$size)
	//	{
	//		if(empty($mfile))
	//			return false;
	//
	//		return 'http://'.$this->options['host'].'/'.$this->options['group'].'/'.$mfile.'_'.$size.'.jpg?v='.time();
	//	}

	/**
	 * 获取FastDFS中可访问的URL路径
	 *
	 * @param string filename 文件名
	 * @param string group 组名
	 * @param string prefix 从文件后缀
	 */
	public function get_file_url($filename, $group = '',$prefix='')
	{
		if (empty($filename))
		return false;

		if (empty ($group)) {
			$group = $this->options['group'];
		}
		if(!empty($prefix)){
			$filename = $this->getSlaveFilename($filename,$prefix);
		}

		return 'http://' . config_item('fastdfs_domain'). '/' . $group . '/' . $filename . '?v=' . time();
	}

	/**
	 * 下载文件
	 * @param string remote_filename 远程文件名
	 * @param string local_filename 本地文件名
	 * @param string prefix 从文件后缀
	 *
	 * @return bool 成功返回true失败返回false
	 */
	public function downloadFile($remote_filename,$local_filename = null, $group_name = '',$prefix='')
	{
		if (empty($group_name)) {
			$group_name = $this->group;
		}

		$remote_filename = $this->getSlaveFilename($remote_filename,$prefix);

		$res = $this->fdfs->storage_download_file_to_file($group_name,$remote_filename,$local_filename,0,0,$this->tracker);
		if ($res) {
			return $res;
		} else {
			return 'Error NO: '. fastdfs_get_last_error_no() . ', Error Info: ' . fastdfs_get_last_error_info();
		}
	}

	/**
	 * 下载文件到文件流
	 * @param string remote_filename 远程文件名
	 *
	 * @param string prefix 从文件后缀
	 *
	 * @return string|false 成功返回文件流,失败返回false
	 */
	public function downloadFileBuff($remote_filename, $group_name = '',$prefix='')
	{
		if (empty($group_name)) {
			$group_name = $this->group;
		}

		$remote_filename = $this->getSlaveFilename($remote_filename,$prefix);

		$buff = $this->fdfs->storage_download_file_to_buff($group_name,$remote_filename,0,0,$this->tracker);
		if($buff !== false)
		{
			return $buff;
		} else {
			return 'Error NO: '. fastdfs_get_last_error_no() . ', Error Info: ' . fastdfs_get_last_error_info();
		}
	}

	/**
	 * 判断文件是否存在
	 *
	 * @param	$group		文件组名
	 * @param	$name		文件名
	 *
	 * @return	bool		文件存在返回true,不存在返回false;
	 */
	function file_exist($group = null, $name = null)
	{
		if(empty($name)) return false;
		if(empty($group)) $group = $this->group;

		$res = $this->fdfs->storage_file_exist($group, $name, $this->tracker);
		if($res) {
			return $res;
		}
		return false;
	}

	/**
	 * 删除上传的文件
	 * @param string filename 远程文件名
	 * @param string prefix 从文件后缀
	 * @return bool 成功返回true失败返回false
	 *
	 */
	public function deleteFile($group = null, $filename,$prefix='')
	{
		if(empty($filename)) return false;
		if(empty($group)) $group = $this->group;

		if(!empty($prefix)){
			$filename = $this->getSlaveFilename($filename,$prefix);
		}
		$res = $this->fdfs->storage_delete_file($group, $filename, $this->tracker);
		if($res) {
			return $res;
		} else {
			// $error = $this->fdfs->get_last_error_info();
			// log
			return false;
		}
	}

	/**
	 * 根据扩展后缀获取从文件名
	 */
	protected function getSlaveFilename($filename,$prefix='')
	{
		if(!empty($prefix))
		{
			$ext = '.' . strtolower(pathinfo($filename,PATHINFO_EXTENSION));
			$slave = $prefix . $ext;
			$filename = str_replace($ext, $slave, $filename);
		}

		return $filename;
	}


	function __destruct()
	{
		$this->fdfs->disconnect_server($this->tracker);
	}
}