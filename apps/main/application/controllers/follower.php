<?php

/**
 * 粉丝列表
 *
 * 粉丝列表/滚动分页/按用户名搜索
 * 
 * @author zengmm
 * @date 2012/7/24
 *
 * @history <yaohaiqi><2012-03-01>
 */
class Follower extends MY_Controller {

	/**
	 * 常量定义
	 */
	
	// 每页数量
	const LIMIT = 20;

    /**
     * 构造函数
     */
    public function __construct(){

		parent::__construct();

		// 访问者是用户自己
		if (empty($this->action_uid) || ($this->action_uid == $this->uid)) {

			$this->is_self = TRUE;

			// 被访问者的用户UID和DKCODE
			$this->action_uid = $this->uid;
			$this->action_dkcode = $this->dkcode;

			// 被访问者基本信息
			$this->action_user = $this->user;

		}

		// 粉丝模型
		$this->load->model('followermodel');
	}

	/**
	 * 调试
	 *
	 * @author zengmm
	 * @date 2012/7/17
	 */
	private function _dump($data, $is_die = TRUE)
	{
		echo '<pre>';
		print_r($data);
		echo '<pre>';

		if ($is_die) { die(); }
	}

	/**
	 * 重写框架提供的ajax返回方法
	 *
	 * @author zengmm
	 * @date 2012/7/19
	 */
	private function _ajaxReturn($status = 1, $msg = 'success', $data = NULL) {

		if (empty($data)) { $data = ''; }

		$this->ajaxReturn($data, $msg, $status);		
	}

	/**
	 * 组合粉丝列表中单条记录信息
	 *
	 * @author zengmm
	 * @date 2012/7/20
	 *
	 * @param array $followerlist 关注个人列表
	 *
	 * @return array
	 */
	private function _combineFollowerList($followerlist = array()) {

		if (empty($followerlist)) { return array(); }

		foreach ($followerlist as $k => &$v) {

			// 用户头像地址 65*65
			$v['src'] = get_avatar($v['id'], 'mm');

			$v['href'] = mk_url('main/index/profile', array('dkcode' => $v['dkcode']));
			
			// 关注数URL
			$v['following_url'] = mk_url('main/following/followingList', array('dkcode'=>$v['dkcode']));
			// 粉丝数URL
			$v['follower_url'] = mk_url('main/follower/index', array('dkcode'=>$v['dkcode']));
			// 好友数URL
			$v['friend_url'] = mk_url('main/friend/friendlist', array('dkcode'=>$v['dkcode']));
			
			// 取得共同关注个人、网页，共同好友等信息
			//$v['display'] = $this->_getRelationList( $v );
		}

		return $followerlist;
	}
    
    /**
     * 粉丝列表
     * 
     * @author zengmm
     * @date 2012/7/24
     * 
     * @history <yaohaiqi><2012/3/28>
     * 	
     */
    public function index() {

    	// 初始化粉丝列表
		$follower = array(
			'total' => 0,
			'data' => array()
		);

    	//当前主页用户信息
        $userinfo = array(
            'url' => mk_url('main/index/profile', array('dkcode' => $this->action_dkcode)),
            'src' => get_avatar($this->action_uid,'ss'),
            'username' => $this->action_user['username'],
            'is_self' => $this->is_self
        );

    	//获得粉丝数量
        $numOfFollowers = (int) $this->followermodel->getnumOfFollowers($this->action_uid);
        $follower['total'] = $userinfo['numOfFollowers'] = $numOfFollowers;

        if ($follower['total']) {

        	//获得粉丝列表
        	$follower['data'] = $this->followermodel->getFollowersWithInfo($this->action_uid, 1, self::LIMIT, $this->uid);
        	$follower['data'] = $this->_combineFollowerList($follower['data'], $this->uid);
        }

		$this->assign('action_dkcode', $this->action_dkcode);

        $this->assign('userinfo', $userinfo);

        $this->assign('follower',$follower);

        $this->display('follower/list.html');
    }
 
    /**
     * 粉丝列表滚动分页
     *
     * @author zengmm
     * @date 2012/7/24
     * 
     * @history <yaohaiqi><2012/3/28>
     */
    public function getfollowerBypage() {

    	// 初始化粉丝列表
		$follower = array(
			'total' => 0,
			'data' => array()
		);

        //获得页码
        $page = (int) $this->input->post("pager");
        $page = !empty($page) ? $page : 1;

        //获得粉丝总数量
        $follower['total'] = (int) $this->followermodel->getnumOfFollowers($this->action_uid);

        if ($follower['total'] > 0) {

        	// 获取网页粉丝
        	$follower['data'] = $this->followermodel->getFollowersWithInfo($this->action_uid, $page, self::LIMIT, $this->uid);
        	$follower['data'] = $this->_combineFollowerList($follower['data'], $this->uid);
        }

        //判断是否为最后一页
        $last = ($follower['total'] > $page * self::LIMIT) ? FALSE : TRUE;

		$data = array(
			'last' => $last,
			'isSelf' => false, // 用于粉丝无隐藏功能
			'data' => $follower['data']
		);

		$this->_ajaxReturn(1, 'success', $data);
    }
    
    /**
     * 通过姓名查找粉丝
     * 
     * @author zengmm
     * @date 2012/7/24
     *
     * @history <yaohaiqi><2012/3/28>
     *
     */
    public function searchFollowerByName() {

    	// 初始化粉丝列表
		$follower = array(
			'total' => 0,
			'data' => array()
		);

        $last = TRUE;

        // 获得页码
        $pager = intval($this->input->post('pager'));
        $page =  $pager ? $pager : 1;

        // 获取搜索关键字
        $keyword = $this->input->post('keyword');

        // 获得粉丝列表
        if($keyword != ''){

            $follower = $this->followermodel->getFollowersByName($this->action_uid ,$keyword ,$page, self::LIMIT, $this->uid);

        }else{

        	// 获取粉丝数
            $follower['total'] = (int) $this->followermodel->getnumOfFollowers($this->action_uid);

            if ($follower['total'] > 0) {
            	$follower['data'] = $this->followermodel->getFollowersWithInfo($this->action_uid ,$page, self::LIMIT, $this->uid);
            }
        }

        if($follower['total'] > 0) {

            //判断是否为最后一页
            $last = ($follower['total'] > $page * self::LIMIT) ? FALSE : TRUE;

            $follower['data'] = $this->_combineFollowerList($follower['data'], $this->uid);
        }

		$data = array(
			'last' => $last,
			'isSelf' => false, // 用于粉丝无隐藏功能
			'data' => $follower['data']
		);

		$this->_ajaxReturn(1, 'success', $data);
    }
}