<?php
/**
 * 文件存储类
 * 实现文件存储的统一操作
 * @author mawenpei<mawenpei@duankou.com>
 * @date <2012/02/26>
 * 
 */
class Storage
{
	private static $instance;
	
	private $fdfs = null;
	
	protected $tracker;
	protected $storage;
	protected $group;
	
	public function __construct()
	{
		$this->group = config_item('fastdfs_group');		
		if(!class_exists('FastDFS')) return;
		$this->fdfs = new FastDFS();
		$res = $this->fdfs->connect_server(config_item('fastdfs_lan_host'),config_item('fastdfs_port'));
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
	public static function getInstance()
	{
		if(!isset(self::$instance))
		{
			self::$instance = new Storage(config_item('fastdfs_lan_host'),config_item('fastdfs_port'),config_item('fastdfs_group'));
		}
		return self::$instance;
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
	public function uploadSlaveFile($local_filename,$master_filename,$prefix,$file_ext = null, $meta = array())
	{
		if(empty($local_filename) || empty($master_filename) || empty($prefix)) {
			return false;
		}
		$res = $this->fdfs->storage_upload_slave_by_filename($local_filename, $this->group, $master_filename, $prefix, $file_ext, $meta, $this->tracker);
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
	public function uploadSlaveFileByBuff($file_buff,$master_filename,$prefix,$file_ext = null, $meta = array())
	{
		$file_info = $this->fdfs->storage_upload_slave_by_filebuff($file_buff,$this->group,$master_filename,$prefix,$file_ext,$meta,$this->tracker);
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
	public function get_file_url($uid)
	{
		if(empty($uid))
			return false;				
		return 'http://'.config_item('fastdfs_host').'/'.config_item('fastdfs_group').'/'.config_item('fastdfs_masterfile').$uid.'.jpg';
	}
	
	/**
	 * 下载文件
	 * @param string remote_filename 远程文件名
	 * @param string local_filename 本地文件名
	 * 
	 * @return bool 成功返回true失败返回false
	 */
	public function downloadFile($remote_filename,$local_filename)	
	{
		return $this->fdfs->storage_download_file_to_file($this->group,$remote_filename,$local_filename,0,0,$this->tracker);
	}
	
	/**
	 * 下载文件到文件流
	 * @param string remote_filename 远程文件名
	 * 
	 * @return string|false 成功返回文件流,失败返回false
	 */
	public function downloadFileBuff($remote_filename)
	{
		$buff = $this->fdfs->storage_download_file_to_buff($this->group,$remote_filename,0,0,$this->tracker);
		if($buff !== false)
		{
			return $buff;
		}
		return false;
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
		if(!$group or !$name)
			return false;
		$res = $this->fdfs->storage_file_exist($group, $name, $this->tracker);
		if($res) {
			return $res;
		}
		return false;
	}
	
	/**
	 * 删除上传的文件
	 * @param string filename 远程文件名
	 * @return bool 成功返回true失败返回false
	 */
	public function deleteFile($group, $filename)
	{
		if(!$group or !$filename)
		{
			return false;
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

		
	function __destruct()
	{
		$this->fdfs->disconnect_server($this->tracker);
	}
}