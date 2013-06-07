<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Welcome extends MY_Controller 
{
	
	/**
	 * 首页	  
	 */
	public function index()
	{	
		//Smarty模板使用范例	
		
		//$this->assign('content','这里是内容');
		//set_session('abc','123456');
		//echo get_session('abc');
		//$this->display('index');
	}	
	
	/**
	 * 判断登录
	 */
	public function login()
	{		
		$this->checkLogin();
	}
	public function doLogin()
	{
		parent::doLogin();
		echo 'success';
	}
	
	/**
	 * 模型使用范例
	 */
	public function model()
	{
		$this->load->model('usermodel','user');
		$result = $this->user->getInfo();
		print_r($result);
		print_r($this->user->getCount());
		$this->user->updateInfo();
	}
	
	// 测试
	public function test()
	{
		// 获取配置fdfs的配置表;
		// echo config_item('fastdfs_host');
		// 测试页面;
		$this->soap_log();
		$this->display('test/test.html');
	}	
	
	
	// 测式图片
	public function testimage(){
		$this->load->library('image');
		
		// 获得图片的数据
		// $result = $this->image->getImageAttr('4.jpg');
		// test_arr($result);
		
		$result = $this->image->resize('4.jpg','5.jpg',100,100);	// 生成缩略图
		echo $result ==true ? '成功' : '失败';
	}
	
	
	
	// 测式session 
	function test_session(){
		set_session('abc','123456');
		echo get_session('abc');
	}
	
	// 测式模板
	function test_template(){
		$this->assign('header','这里是头部');
		$this->assign('content','这里是内容');
		$this->assign('footer','这里是尾部');
		$this->assign('test3','test3 333');
		
		$this->display('index.html');
		
	}
	
	// 测式缓存
	function test_cache(){
		$this->load->library('mycache');
		$this->mycache->set('ssstttvvv','ttttt');
		echo $this->mycache->get('ssstttvvv');
		
	}
	
	
	
	
	
}

