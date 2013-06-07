<?php
/**
 * 分布式文件系统
 * @author weihua
 * @date 2 2012/3/6
 *  
 */

class FdfsModel {
	
	private $fdfs;
	private static $instance = null;
	private $tracker = null;
	private $storage = null;
	private $group = null;
	
	function __construct()
	{
		//for test
		if (!class_exists('FastDFS', false)) return;

		$this->fdfs = new FastDFS();
		$host = config_item('fastdfs_host');
		$port = config_item('fastdfs_port');
		$res = $this->fdfs->connect_server($host,$port);
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
	
	// 获取对象 ;
	public static function getInstance()
	{
		if(self::$instance === null)
		{
			self::$instance = new self();
		}
		return self::$instance;
	}
	
	/**
	 * 上传文件,通过文件名的方式
	 * 
	 * @param	(string)$filename	(必要的)文件存放位置
	 * @param	(array)	$file_ext	文件的后缀名,不包含点'.'
	 * @param	(array)	$meta		文件的附加信息,数组格式,array('hight'=>'350px','author'=>'bobo');
	 * @param	(string)$group		文件组名
	 *
	 * @return	Array				返回包含文件组名和文件名的数组,array('group_name'=>'ab','filename'=>'kajdsf');
	 */
	function upload_filename($filename = null, $file_ext = null, $meta = array(), $group = null)
	{
		//for test
		if (!class_exists('FastDFS', false)) return array('group_name'=>'', 'filename'=>'');

		if(empty($filename)){
			return 'no file name';
		}
		$res = $this->fdfs->storage_upload_by_filename($filename, $file_ext, $meta, $group, $this->tracker);
		if($res){
			// return C('fastdfs_host');
			return  $res;
		}else{
			// $error = $this->fdfs->get_last_error_info();
			// log
			return $this->fdfs->get_last_error_info();
		}
	}
	
	/**
	 * 上传文件,通过文件流的方式
	 * 
	 * @param	$filebuff			(必要的)文件流
	 * @param	(string)$file_ext	文件的后缀名,不包含点'.'
	 * @param	(array)	$meta		文件的附加信息,数组格式,array('hight'=>'350px','author'=>'bobo');
	 * @param	(string)$group		文件组名
	 *
	 * @return	Array				返回包含文件组名和文件名的数组,array('group_name'=>'ab','filename'=>'kajdsf');
	 */
	function upload_filebuff($filebuff = null, $file_ext = null, $meta = array(), $group = null)
	{
		if(empty($filebuff)){
			return false;
		}
		$res = $this->fdfs->storage_upload_by_filebuff($filebuff, $file_ext, $meta, $group, $this->tracker);
		if($res){
			return $res;
		}else{
			// $error = $this->fdfs->get_last_error_info();
			// log
			return false;
		}
	}
	
	/**
	 * 上传从文件
	 * 
	 * @param	$filename		从文件名
	 * @param	$group			主文件组名
	 * @param	$masterfile		主文件名
	 * @param	$prefixname		从文件的标识符; 例如,主文件为abc.jpg,从文件需要大图,添加'_b',则$prefixname = '_b';
	 * @param	$file_ext		从文件后缀名
	 * @param	$meta			文件的附加信息,数组格式,array('hight'=>'350px','author'=>'bobo');
	 * 
	 * @return	Array			返回包含文件组名和文件名的数组,array('group_name'=>'ab','filename'=>'kajdsf');
	 */
	function upload_slave_filename($filename, $group, $masterfile, $prefixname, $file_ext = null, $meta = array())
	{
		//for test
		if (!class_exists('FastDFS', false)) return array('group_name'=>'', 'filename'=>'');

		if(empty($filename) || empty($masterfile) || empty($prefixname)) {
			return false;
		}
		$res = $this->fdfs->storage_upload_slave_by_filename($filename, $group, $masterfile, $prefixname, $file_ext, $meta, $this->tracker);
		if($res){
			return $res;
		}else{
			// $error = $this->fdfs->get_last_error_info();
			// log
			return false;
		}
	}
	
	/**
	 * 下载文件到本地服务器
	 * 
	 * @param	$group		文件组名
	 * @param	$filename	文件名
	 * @param	$localfile	
	 *
	 * @return				成功返回true,失败返回false
	 */
	function download_filename($group = null, $filename = null, $localfile = null)
	{
		if(!$group or !$filename or !$localfile){
			return false;
		}
		
		$res = $this->fdfs->storage_download_file_to_file($group, $filename, $localfile);
		
		if($res){
			return $res;
		}else{
			// $error = $this->fdfs->get_last_error_info();
			// log
			return false;
		}
	}
	
	/**
	 * 下载文件流
	 * 
	 * @param	$group		文件组名
	 * @param	$filename	文件名
	 *
	 * @return				成功返回文件流,失败返回false
	 * 
	 */
	function download_filebuff($group = null, $filename = null)
	{
		if(!$group or !$filename)
		{
			return false;
		}
		$res = $this->fdfs->storage_download_file_to_buff($group, $filename);
		
		if($res){
			return $res;
		}else{
			// $error = $this->fdfs->get_last_error_info();
			// log
			return false;
		}
	}
	
	/**
	 *  获取文件地址 
	 * 
	 */
	public function get_file_url($group, $filename, $prefix=null)
	{
		if (!$group || !$filename) {
			return '/misc/img/default/event.jpg';
		}

		if ($prefix) {
			$tmp = explode('.', $filename);

			return 'http://'.config_item('fastdfs_host')."/{$group}/{$tmp[0]}{$prefix}.{$tmp[1]}";
		}
		else {
			return 'http://'.config_item('fastdfs_host')."/{$group}/{$filename}";
		}
	}
	
	/**
	 * 删除文件
	 *
	 * @param	$group		文件组名
	 * @param	$filename	文件名
	 *
	 * @return	bool		成功返回true,失败返回false;
	 */
	function delete_filename($group = null, $filename = null)
	{
		if(!$group or !$filename)
		{
			return false;
		}
		$res = $this->fdfs->storage_delete_file($group, $filename, $this->tracker);
		if($res){
			return $res;
		}else{
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
