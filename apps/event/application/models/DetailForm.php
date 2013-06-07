<?php
namespace Models;

/**
 * 活动详情表单
 *
 * @author weihua
 * @version 3 2012/3/15
 */
class DetailForm
{
	protected $_data;

	/**
	 * 表单name => 内部键名 映射
	 */
	protected $_eles = array(
		'eventName'  => 'name',    //活动名称
		'eventPlace' => 'address',   //活动地点
		'area'       => 'area',       //区域
		'eventInfo'  => 'detail',          //活动详情
		'showattend' => 'is_show_users'    //是否显示活动参与人
	);

	/*
	 * 表单验证错误
	 */
	protected $_errors;

	public function __construct($data=null)
	{
		if ($data) {
			$this->set_data($data);
		}
	}

	protected $token;

	/**
	 *  初史化一个token
	 *
	 *  return string token
	 */
	public function init_token()
	{
		$this->token = 'token_'.dechex(time() + rand(1000, 9999));

		if (!is_array($_SESSION['forms'])) {
			$_SESSION['forms'] = array();
		}

		$_SESSION['forms'][$this->token] = array();

		return $this->token;
	}

	/**
	 * 设置当前表单token
	 * 如果token不存在将返回false
	 *
	 * @return bool
	 */
	public function set_token($token)
	{
		if (!isset($_SESSION['forms'][$token])) {
			return false;
		}

		$this->token = $token;

		return true;
	}

	/**
	 * 销毁表单
	 * 此函数需要token
	 */
	public function destroy()
	{
		unset($_SESSION['forms'][$this->token]);
	}

	/**
	 * 保持数据到表单
	 * (非持久保存)(当前使用session保存,以后需要独立出来)
	 *
	 * 此函数需要token
	 */
	public function keep($ele, $val)
	{
		$_SESSION['forms'][$this->token][$ele] = $val;
	}

	/**
	 * 得到保持的数据
	 * 此函数需要token
	 */
	public function get_keep($ele=null)
	{
		if ($ele) {
			if (isset($_SESSION['forms'][$this->token][$ele])) {
				return $_SESSION['forms'][$this->token][$ele];
			}
			else {
				return null;
			}
		}

		return $_SESSION['forms'][$this->token];
	}

	/**
	 * 映射(设置)数据到表单
	 *
	 * @param array k=>v $data
	 */
	public function set_data($data)
	{
		$this->_data = array();

		foreach ($this->_eles as $ele => $key) {
			$this->_data[$key] = isset($data[$ele]) ? htmlspecialchars(trim($data[$ele])) : null;
		}

		if (!isset($data['startDate'])) { $data['startDate'] = 0; }
		if (!isset($data['startTime'])) { $data['startTime'] = 0; }
		if (!isset($data['endDate'])) { $data['endDate'] = 0; }
		if (!isset($data['endTime'])) { $data['endTime'] = 0; }
		if (!isset($data['nation'])) { $data['nation'] = 1; }

		//$starttime = $this->_data['startDate'] . ' ' . $this->_data['startTime'];
		$starttime = strtotime($data['startDate']) +  60 * (int)$data['startTime'];
		
		$this->_data['area'] = (int)$data['nation'].'/'.(int)$data['province'].'/'.(int)$data['city'];

		$this->_data['starttime'] = date('Y-m-d H:i:s', $starttime);

		//$endtime = $this->_data['endDate'] . ' ' . $this->_data['endTime'];
		$endtime = strtotime($data['endDate']) + 60 * (int)$data['endTime'];

		$this->_data['endtime'] = date('Y-m-d H:i:s', $endtime);

		$this->_data['is_show_users'] = (int)$this->_data['is_show_users'];
	}

	/**
	 * 得到表单数据
	 */
	public function get_data()
	{
		return $this->_data;
	}

	/**
	 * 得到表单验证错误
	 */
	public function errors()
	{
		return $this->_errors;
	}

	/**
	 * 验证表单数据
	 *
	 * @return bool
	 */
	public function isValid()
	{
		if (!$this->token) {
			$this->_errors['token'] = 'token口令错误';
		}

		$st = strtotime($this->_data['starttime']);

		if ($st == 0) {
			$this->_errors['starttime'] = "开始时间不能为空";
		}

		$et = strtotime($this->_data['endtime']);

		if ($et < $st) {
			$this->_errors['endtime'] = "结束时间必需大于开始时间";
		}
		else if ($et < $_SERVER['REQUEST_TIME']) {
			$this->_errors['endtime'] = "活动结束时间不能小于当前时间";
		}

		if (mb_strlen($this->_data['name'], 'utf-8') > 50) {
			$this->_errors['name'] = "活动名称最多只能有50个字";
		}

		if (mb_strlen($this->_data['address'], 'utf-8') > 15) {
			$this->_errors['address'] = "活动地点最多只能有15个字";
		}

		if (mb_strlen($this->_data['detail'], 'utf-8') > 1000) {
			$this->_errors['detail'] = "活动详情最多只能有1000个字";
		}

		return empty($this->_errors);
	}
}
