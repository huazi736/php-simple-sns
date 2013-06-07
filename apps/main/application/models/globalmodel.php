<?php
if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * 区分应用层
 * Enter description here ...
 * @author liuGC
 * @access public
 * @dateline 2012/03/24
 * @version 1.0
 * @description <author><access><dateline><version><description>
 *
 */

class GlobalModel extends MY_Model
{
	//当前应用类型
	private static $current_application_number = 0;
	//应用标题
	private static $title = array('人名', '网页', '状态', '图片', 
									'相册', '视频', '博客', '问答', '活动');
	//处理方法类,处理方法只能有1个参数,且为遍历数组中的一个元素
	private static $util_class = 'SearchHelper';
	//当前用户的ID
	private static $current_user_id = null;
	//返回的结果
	private static $result = array();
	//返回搜索总量
	private static $total = 0;
	//处理数据用到一些额外参数
	private static $params = array('solr_key' => 'list', 'val_key' => 'type', 'title_key' => 'label');
	//修改键值为统一的键名 array(更新的键名=>数组[便利数组中的键多个用逗号隔开])array('id'=>array('user_id','blog_id'), 'name'=>array('user_name', 'blog_name'))
	private static $update_keys = array();
	//添加外键数据 array(添加的键名=>array(方法参数(便利数组存在的键名)=>方法名))
	private static $add_keys=array();
	//删除内键数据 array(便利数组中的键名,多个用逗号隔开)
	private static $delete_keys=array();
	/**
	 * 处理solr数据
	 * Enter description here ...
	 * @param array $solr_result  solr搜索中返回的结果
	 * @param int $application_number 当前应用的类型
	 * @return 成功返回在每个应用最前面插入标题的数组,失败返回空;
	 */
	public static function parseSolrResult($solr_result = array(), $application_number = 0)
	{
		if (isset($solr_result[self::$params['solr_key']]) && count($solr_result[self::$params['solr_key']]) > 0)
		{
			self::$total = isset($solr_result['total']) ? $solr_result['total'] : 0 ;
			self::getCurrentApplicationNumber($application_number);
			foreach ($solr_result[self::$params['solr_key']] as $val)
			{   
				if (is_object($val))
				{
					$val = (array)$val;
				}else if (!is_array($val)){
					return $val.' is not a array';
				}
				if (self::$current_application_number + 1 <= $val[self::$params['val_key']])
				{//add application name
					array_push(
								self::$result, 
							   	array(self::$params['title_key'] => self::getTitle($val[self::$params['val_key']] - 1),
							   		  'title'=>true, 'category'=>$val['type'])
							   );
					self::getCurrentApplicationNumber($val[self::$params['val_key']]);
				}	
				self::$result[] = self::updateData($val);
			}
		}
	}
	
	public static function getTotal()
	{
		return self::$total;
	}
	
	/**
	 * 是否为最后一页
	 * Enter description here ...
	 * @param int $current_page 当前页码
	 * @param int $limit 显示多少页
	 * @param int $remainder 第一页显示了多少
	 */
	public static function isLastPage($current_page = 1, $limit = 10, $remainder = 0)
	{
		return self::$total <= $current_page*$limit + $remainder ? true : false ;
	}
	
	/**
	 * 获取应用名称
	 * Enter description here ...
	 * @param int $key_num 数组中的位置
	 */
	private static function getTitle($key_num = null)
	{
		return isset(self::$title[$key_num]) ? self::$title[$key_num] : null;
	}
	
	/**
	 * 获取当前应用的编号
	 * Enter description here ...
	 * @param int $application_number 应用编号
	 */
	private static function getCurrentApplicationNumber($application_number)
	{
		$application_number = intval($application_number);
		if ($application_number > self::$current_application_number)
		{
			self::$current_application_number = $application_number;
		}
	}
	  
	/**
	 * 获取处理后的信息数组
	 * Enter description here ...
	 * @param boolean $clear 是否清理处理后的信息;
	 */
	public static function getResultByArray($clear = true)
	{
		$result = self::$result;
		if ($clear){
			self::clearGlobalProperities();
		}
		return $result;
	}
	
	/**
	 * 设置各个应用的标题名称(按排列顺序写)
	 * Enter description here ...
	 * @param unknown_type $title
	 */
	public static function setTitle($title = array())
	{
		self::$title = $title;
	}
	
	/**
	 * 
	 * Enter description here ...
	 * @param unknown_type $key
	 * @param unknown_type $value
	 */
	public static function setParameter($key, $value)
	{
		self::$params[$key] = $value;
	}
	
	/**
	 * 修改二维数组
	 * Enter description here ...
	 * @param array $data 修改的二维数组
	 */
	private static function updateData($data=array())
	{
		foreach ($data as $key => $val)
		{	
			foreach (self::$add_keys as $k => $v )
			{   
				if (array_key_exists($key, $v))
				{ 
					$method = $v[$key];
					$data[$k] = class_exists(self::$util_class) && method_exists(self::$util_class, $method) ? 
										self::getInstance()->$method($data): 
										$method($data);
				}
			}
		}

		//更换
		foreach (self::$update_keys as $key => $val)
		{  
			foreach($val as $v)
			{
				if (array_key_exists($v, $data))
				{
					$data[$key] = $data[$v];
				}
			}
		}
			
		//删除
		foreach (self::$delete_keys as $delete_key)
		{
			if (array_key_exists($delete_key, $data))
			{
				unset($data[$delete_key]);
			}
		}

		return $data;
	}
	
	/**
	 * 获取工具类的实例
	 * Enter description here ...
	 */
	public static function getInstance()
	{
		static $instance=null;
		if ($instance == null)
		{   
			$instance = self::$current_user_id != null ? new self::$util_class(self::$current_user_id) : new self::$util_class();
		}
		return $instance;
	}
	
	/**
	 * 设置当前用户ID
	 * Enter description here ...
	 * @param string $current_user_id 当前用户的ID
	 */
	public static function setCurrentUserID($current_user_id)
	{
		self::$current_user_id = $current_user_id;
	}
	
	/**
	 * 实例化工具处理类,默认的为SearcherHelper
	 * Enter description here ...
	 * @param string $class_name 
	 */
	public static function setUtilClass($class_name)
	{
		self::$util_class = $class_name;
	}
	
	/**
	 * 修改数组中键名
	 * Enter description here ...
	 * @param string $key 新的名称
	 * @param array $value 所在此范围的键都将被修改
	 */
	public static function setUpdateKeys($key, $value=array())
	{
		self::$update_keys[$key] = $value;
	}
	
	/**
	 * 添加新增的键
	 * Enter description here ...
	 * @param string $key 新增的键名
	 * @param array $value 新增键名所用的方法(键值对)
	 */
	public static function setAddKeys($key, $value=array())
	{
		self::$add_keys[$key] = $value;
	}
	
	/**
	 * 设置需删除的键值
	 * Enter description here ...
	 * @param array $value 被删除的键值;需为一维数组
	 */
	public static function setDeleteKeys($value=array())
	{
		self::$delete_keys=$value;
	}
	
	/**
	 * 清空属性值
	 * Enter description here ...
	 */
	public static function clearGlobalProperities()
	{
		if(count(self::$result) > 0){
			self::$result = array();
		}
		if (count(self::$add_keys) > 0) {
			self::$add_keys = array();
		}
		if (count(self::$delete_keys) > 0){
			self::$delete_keys = array();
		}
		if (count(self::$update_keys) > 0){
			self::$update_keys = array();
		}
	}
}
?>
