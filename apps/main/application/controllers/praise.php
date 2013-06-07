<?php
/**
 * @desc 赞列表页面控制器
 * @author lijianwei
 * @date 2012-03-19
 */
class Praise extends  MY_Controller{
	private $uInfo = array();//被访问者用户信息
	private $isDebug = 0; //方便调试
	private $isself = 1;
	private $birthday = 0;
	public function _initialize() {
		//强制登录
		//call_soap("cache", "Memcache", "set",array(get_sessionid()."uid", 1000001035));
	}
	public function __construct() {
		parent::__construct();
		//$this->uid = 1000001035; //方便测试，以后要删除 
		//$this->action_uid = 1000001055; //被访问用户uid
		//$this->checkLogin();  //检测是否登录
		//访问者uid
		$this->uid = $this->getLoginUID();
		//被访问者uid
		$this->action_uid = $this->getActionUID();
		$this->isself = $this->uid == $this->action_uid ? "你" : $this->uInfo['username'];
		$this->load->model("praisemodel", "_praise");
		//$this->birthday = $this->getBirthYear($this->action_uid);
	}
	/**
	*  赞列表首页信息
	*
	*  @author  lijianwei
	*  @date    2012-03-19
	*  @access    public
	*  @param    
	*  @return    
	*/
	public function index(){
		$avatar_url = get_avatar($this->action_uid, 'ss');
		//被访问者头像地址
		$this->assign("avatar_url", $avatar_url);
		//被访问者用户名
		$action_user_name = isset($this->uInfo['username']) ? $this->uInfo['username'] : "";
		$this->assign("action_user_name", $action_user_name);
		if($this->uid == $this->action_uid) {
			$action_index_url = WEB_ROOT . 'main';
			$praise_index_url = mk_url(APP_URL.'/praise');
			$this->assign("isself","你");
		} else {
			$this->assign("isself",$action_user_name);
			$action_index_url = mk_url(APP_URL.'/index/index', array(config_item('domain') => $this->uInfo['dkcode']));
			$praise_index_url = mk_url(APP_URL.'/praise/index',array(config_item('domain') => $this->uInfo['dkcode']));
		}
		$fdfsinfo['host'] = config_item('fastdfs_host');
		$fdfsinfo['port'] = config_item('fastdfs_port');
		$fdfsinfo['group'] = config_item('fastdfs_group');
		$this->assign("fdfsinfo", $fdfsinfo);
		//被访问者主页地址
		$this->assign("action_index_url", $action_index_url);
		//赞字链接地址
		$this->assign("praise_index_url",$praise_index_url);

		//给隐藏域赋值
	   	$login_info['uid'] = $this->uid;
		$login_info['username'] = $this->user['username'];
		$login_info['avatar_url'] = get_avatar($this->action_uid, 's');
		$login_info['url'] = "";
		$this->assign("login_info", $login_info);
		$this->assign("action_uid", $this->action_uid);
		//视频，视频图片url wangying
		$this->assign('video_pic_domain',config_item('video_pic_domain'));
		$this->assign('video_src_domain',config_item('video_src_domain'));

		//显示模板
		$this->display("praise/praise.html");
	}
	/**
	*  获取赞信息
	*
	*  @author  lijianwei
	*  @date    2012-03-19
	*  @access    public
	*  @param    
	*  @return    json
	*/
	public function getData(){
		if($this->isDebug) {
			$test_data ="";
			die($test_data);
		}
		//每页数量
		$size = 30;
		$isEnd = false;
		//@TODO post 安全过滤
		$data = $this->input->post("data");
		//年份
		$year = $data["year"];
		//获取赞的类型   1 别人赞了你的  2 你赞了个人的   3 你赞了网页的
		$type = intval($data["type"]);
		$type = in_array($type, array(1, 2, 3)) ? $type : 1;
		$page = $this->input->post("page");
		$page = intval($page);
		$page = $page > 0 ? $page : 1;
		//取全部数据
		$params = array("uid" => $this->uid, "action_uid" => $this->action_uid, "year" => $year, "type" => $type , "birthday" =>$this->birthday);
		
		//调用核心获取信息流id数组  
		$object_ids = array();
		$object_type = 'topic';
		if ($type ==3) {
			if (strrpos($year, "C")) {    //公元前年份转换
				if (strrpos($year, "万")) {
					$year = (int)rtrim(ltrim($year,"B.C "),"万年");
					$year = "-" . $year * 10000 . "0101000000";
				} elseif (strrpos($year, "亿")) {
					$year = (int)rtrim(ltrim($year,"B.C "),"亿年");
					$year = "-" . $year * 100000000 . "0101000000";
				}else {
					$year = (int)rtrim(ltrim($year,"B.C "),"年");
					$year = "-" . $year ;
				}
			}
			$object_type = 'web_topic';
			$object_ids = call_soap("comlike", "PraiseList", "getWebObjectids" , array('action_uid' => $this->action_uid,"year" =>$year));
		} else {
			if (!in_array(intval(substr($year, 0 , 1)),array(1,2,3,4,5,6,7,8,9))) {
				$this->errorOutput("没有赞信息");
			} else {
				$object_ids = call_soap("comlike", "PraiseList", "getObject_ids", $params);
			}
		}
		if(!count($object_ids)) {
			$this->errorOutput("没有赞信息");
		} 

		//获取信息流内容
		$topics = $this->_praise->getAllTopics($object_ids,$type);
		if(!count($topics)) {
			$this->errorOutput ( "获取信息流失败 " );
		}
		//过滤信息流
		if ($type != 3) {
			
			$uids = array();
			foreach ( $topics as $key => $topic ) {
				$uids[] = ( isset($topic['uid']) ? $topic['uid'] : 0 );
			}
			
			$relations = $this->_praise->getRelations($this->uid, $uids); //获取关系
			
		    $topics = $this->_praise->checkTopicPression ( $this->uid, $topics, $relations);//根据权限过滤信息流
		}
		
		//组装信息流
		$comboTopics = $this->_praise->comboTopics($topics,$object_type);		 
		if(!count($comboTopics)){
			$this->errorOutput("组装信息失败");
		} 
		if($page * $size >= count($comboTopics)){
			$isEnd = true;
		} 
		$comboTopics = array_slice($comboTopics, ($page-1) * $size, $size);
		die(json_encode(array('data' => $comboTopics, 'msg' => '获取信息流成功', 'status' => 1,'isend'=>$isEnd)));
	}
	/**
	*  获取赞页面上部年份列表
	*
	*  @author  lijianwei
	*  @date    2012-03-19
	*  @access    public
	*  @param    
	*  @return    json
	*/
	public function getYears(){
		if($this->isDebug) {
			header("Content-Type: application/json; charset=utf-8");
			$output = array();
			$output["data"] = array();
			for($i = 0; $i< 30; $i++) {
				array_push($output["data"], 2012-$i);
			}
			$output["status"] = 1;
			$output["msg"] = "";
			die(json_encode($output));
		}else{
			//取全部数据
			
			$params = array("uid" => $this->uid, "action_uid" => $this->action_uid);
			$years = call_soap("comlike", "PraiseList", "getYears", $params);
			/*
			//如果设置出生年月，则比较，没有设置就不比较
			if ($this->birthday) {
				$birthyear = date('Y',$this->birthday);
				foreach ( $years as $key => $val ) {
					if ($val < $birthyear) {
						unset ( $years [$key] );
					}
				}
			}
			*/
			if(!count($years)) {
				$this->errorOutput("获取年份失败");
			}
			$this->successOutput($years,"获取年份成功");
		}
	}
	/**
	*  报错处理
	*
	*  @author  lijianwei
	*  @date    2012-03-19
	*  @access    public
	*  @param     string $message  错误信息
	*  @param     array  $data  错误数据
	*  @param     int    $status  状态 0失败信息 1成功信息
	*  @return    json
	*/
	public function errorOutput($message = "", $data = array(), $status = 0) {
		$output = array();
		$output['status'] = $status;
		$output['data'] = $data;
		$output['msg'] = $message;
		$output['isself'] = $this->isself;
		die(json_encode($output));
	}
	/**
	*  报错处理
	*
	*  @author  lijianwei
	*  @date    2012-03-19
	*  @access    public
	*  @param     array  $data  错误数据
	*  @param     string $message  错误信息
	*  @return    json
	*/
	public function successOutput($data = array(), $message = "") {
		$this->errorOutput($message, $data, 1);
	}
	/**
	*  获取被访问用户uid
	*
	*  @author  lijianwei
	*  @date    2012-03-19
	*  @access    public
	*  @param    
	*  @return    int
	*/
	public function getActionUID() {
		$param_name = config_item("domain");
		$dkcode = isset($_GET[$param_name]) ? $_GET[$param_name] : 0;
		if($dkcode && is_numeric($dkcode)) {
			$user_info = call_soap("ucenter", "User", "getUserInfo", array($dkcode, 'dkcode', array('uid', 'dkcode', 'username')));
			if(is_array($user_info) && isset($user_info['uid'])) {
				$this->uInfo = $user_info;
				return $user_info['uid'];
			}
		} else {
			//自己访问自己
			//$user_info = call_soap("ucenter", "User", "getUserInfo", array($this->uid, 'uid', array('uid', 'dkcode', 'username')));
			$user_info = getUserInfo($this->uid);
			if(is_array($user_info) && isset($user_info['uid'])){
				$this->uInfo = $user_info;
				return $this->uid;
			}
		}
		return 0;
	}
	
	private function getBirthYear($uid) {
		$user = call_soap ( 'ucenter', 'User', 'getUserInfo', array ($uid, 'uid', array ('birthday' ) ) );
		return isset($user['birthday'])&& $user['birthday'] ? $user['birthday'] : 0;
	}
	
	
	//测试接口
	public function test(){
		$params = array("uid" => 1000002047, "action_uid" => 1000002047, "type" => 1,"birthday" => 334146516 );
		$years = call_soap("comlike", "PraiseList", "getYears", $params);
		var_export($years);
	}
	
	public function atest() {
		//$a = $this->_praise->getTopic('111111111111111111111111111111111111');
		//var_dump($a);
		$arr = call_soap("comlike", "Index","delObject", array(array('object_id'=>6867,'object_type'=>'topic')));
		var_export($arr);
	}
	
	
}
/* End of file praise.php */
/* Location: ./main/application/controllers/praise.php */