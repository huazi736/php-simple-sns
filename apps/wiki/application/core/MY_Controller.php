<?php
/**
 * 控制器类
 */
class MY_Controller extends DK_Controller
{
	/**
	 * 构造函数
	 */
	public function __construct()
	{
		parent::__construct();
		
		//加载wiki配置
		$this->load->config("wiki");
        //是否要开启错误提示
	    config_item("debug") ? error_reporting(E_ALL) : error_reporting(0);
	    //验证网页是否存在
		$this->check_web_init(); 
		
		set_exception_handler("exception_handler");
		//set_error_handler("error_handler");
		
	    //处理mongo配置
        $mongodb_group = config_item("mongodb_group");
		$mongodb_arr = include  CONFIG_PATH. "mongodb.php";
		$mongo = array(
		   "mongo_host" => $mongodb_arr[$mongodb_group]['host'],
	       "mongo_port" => $mongodb_arr[$mongodb_group]['port'],
	       "mongo_auth" => false,
	       "mongo_user" => $mongodb_arr[$mongodb_group]['user'],
	       "mongo_pwd"	 => $mongodb_arr[$mongodb_group]['pass'],
	       "mongo_db"   => "wiki_duankou",
           "mongo_cursor_timeout" => 5000, //设置查询数据超时时间和增删改safe模式下超时时间
	       "options"  => array(
		      "persist" => "wiki_duankou_persist",
		   )
		 );
		 $this->config->set_item("mongo", $mongo);
		 
		 //处理fastdfs配置
		 $fastdfs_group = config_item("fastdfs_group");
		 $fastdfs_arr = include CONFIG_PATH. "fastdfs.php";
		 $this->config->set_item("fastdfs_host", $fastdfs_arr[$fastdfs_group]['host']);
		 $this->config->set_item("fastdfs_group", $fastdfs_arr[$fastdfs_group]['group']);
	}
	
	
	public function check_web_init() {
		if (empty($this->web_info[0]) && ($_SERVER['REQUEST_METHOD'] == "GET") && !($this->isAjax())) {
			$this->showmessage("网页不存在,系统自动跳转到您的个人首页", 2, mk_url("main/index/main"));
			//$this->redirect("main/index/main");
		}
	}

	/*****************lijianwei  2012-07-05 添加wiki公共控制器方法  start *************************/


	/**
	 * 初始化头部导航
	 * @param  $otherlink  其他导航        类似  array("查看词条", "查看词条1",,,)
	 * @param  $ojb       控制器对象    
	 */
	public function assignHeaderNav($otherlink = array()){
		if($this->web_id && $this->web_info && $this->web_info[0]){
			$avatar = get_webavatar($this->web_id); //网页头像
			$avatar_link = mk_url("webmain/index/main",array("web_id"=>$this->web_id)); //头像连接
			$name = $this->web_info['name'];//网页名称
		}else{
			$avatar = get_avatar($this->uid); //个人头像
			$avatar_link = mk_url('main/index/main', array('dkcode' => $this->dkcode));//头像连接
			$name = $this->username;
		}
		$this->assign("avatar", $avatar);
		$this->assign("avatar_link", $avatar_link);
		$this->assign("name", $name);
		$this->assign("wiki_index_url", mk_url("wiki/wikit/index", array("web_id" => $this->web_id)));
		$this->assign("otherlink", $otherlink);
	}
	/**
	 * 检查是否已经引用词条
	 */
	public function checkMatch() {
		$this->load->model("privmodel", "priv");
		$is_match = $this->priv->check_match($this->web_id, $this->uid);
		$this->assign("is_match", $is_match);
		$this->assign("match_url", mk_url("wiki/webwiki/wiki_match", array("web_id" => $this->web_id)));
	}
	/**
	 * 提示页面
	 * @param string $msg 提示信息
	 * @param int $type  提示类型  1 成功   2错误
	 * @param string  $url  跳转url
	 * @param int $time  间隔多少秒跳转
	 */
	public function showmessage($msg = '', $type = 1, $url = '', $time = 3)
	{
		if(empty($url))
		{
			$url = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : mk_url("wiki/wikit/index", array('web_id' => $this->web_id));
		}
		$this->assign('msg',$msg);
		$this->assign('type',$type);
		$this->assign('url',$url);
		$this->assign('time',$time);
		$this->display('wiki_showmessage.html');
		die;
	}

	/*****************lijianwei  2012-07-05 添加wiki公共控制器方法  end *************************/


	/**
	 * @deprecated 暂时弃用
	 * 返回未关注页面（弹出层）
	 * @author bohailiang
	 * @date   2012/5/21
	 * @access public
	 * @return json字符串
	 */
	public function getFollowWeb($item_id = 0){
		$this->load->model('commonmodel', '_common');
		$result = $this->_common->getwebs($item_id);

		$html = array();
		if($result){
			foreach($result as $key => $value){
				//网页头像
				$webavatar = get_webavatar($value['uid'], 's', $value['aid']);
				$html[] = array('webavatar' => $webavatar, 'webname' => $value['name'], 'followers' => $value['fans_count'], 'webid' => $value['aid']);
			}
		}
		$html = json_encode($html);

		return $html;
	}

	/**
	 * @deprecated 暂时弃用
	 *
	 * 关注选中的网页
	 * @author bohailiang
	 * @date   2012/5/21
	 * @access public
	 */
	public function setfollow(){
		$webid = $this->input->post('wid');
		if(empty($webid) || !is_numeric($webid)){
			$result = array('status' => 0, 'message' => '未知的网页id');
			echo json_encode($result);
			exit;
		}

		$this->load->model('commonmodel', '_common');
		$check = $this->_common->setfollow($this->uid, $webid);
		if(false === $check){
			$result = array('status' => 0, 'message' => '加关注失败');
			echo json_encode($result);
			exit;
		}

		$result = array('status' => 1, 'message' => '加关注成功', 'followers' => $check);
		echo json_encode($result);
		exit;
	}
}
