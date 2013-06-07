<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/*
 * @author xuxuefeng
 * @date 20102/7/5
 * @function user_service interface
 */
class User_service extends CI_Controller {
	
	public function __construct()
	{
		parent::__construct();
		
		$this->load->model("dev_api");
		
		$this->load->model("openapi_userinfomodel");
	}
	public function index()
	{
		 $ra = $this->dev_api->getservice(new self);
		
		 $ra->displayinfo = true;
		 $ra->handle();
	}
	
	//帐户验证
	public function getUserByDkcodeAndPassword($param)
	{
		$list = $this->openapi_userinfomodel->userAuth($param);
		return $list;
	}
	//获取用户信息
	public function getUserInfo($param)
	{
		$userinfo = $this->openapi_userinfomodel->getUserInfo($param);
		return $userinfo;
	}
	
	//批量获取用户信息
	public function getUserInfoBatch($param)
	{
		$userinfo = $this->openapi_userinfomodel->getUserInfoBatch($param);
		return $userinfo;
	}
	
	//获取两个用户间的关系
	public function getRelationForTwoUser($param)
	{
		
		$relations = $this->openapi_userinfomodel->getRelationForTwoUser($param);
		return $relations;
		
	}
	
	//获取单个用户的好友/其他关系用户列表
	public function getRelationUserByDkcode($param)
	{
		$list = $this->openapi_userinfomodel->getRelationUserByDkcode($param);
		return $list;
	}
	
	//获取两个用户的共同好友/其他关系用户列表
	
	public function getOverlapRelationUser($param)
	{
		
		$list = $this->openapi_userinfomodel->getOverlapRelationUser($param);
		return $list;
	}
}
