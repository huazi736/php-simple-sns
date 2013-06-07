<?php
/**
 * 好友列表
 *
 * @desc 好友首页/好友列表/通过姓名获取好友/好友显示与隐藏等
 *
 * @author zengmm
 * @date 2012/7/25
 *
 * @history <yaohaiqi><2012-03-01>
 */
class Friend extends MY_Controller {

    /**
     * 常量定义
     */
    
    // 每页数量
    const LIMIT = 20;

    /**
     * 构造函数
     */
    function __construct(){

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

        // 好友模型
        $this->load->model('friendmodel');
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
     * 重构框架提供的ajax返回方法
     *
     * @author zengmm
     * @date 2012/7/19
     */
    private function _ajaxReturn($status = 1, $msg = 'success', $data = NULL) {

        if (empty($data)) { $data = ''; }

        $this->ajaxReturn($data, $msg, $status);        
    }

    /**
     * 组合好友列表中单条记录信息
     *
     * @author zengmm
     * @date 2012/7/20
     *
     * @param array $friendlist 关注个人列表
     *
     * @return array
     */
    private function _combineFriendList($friendlist = array()) {

        if (empty($friendlist)) { return array(); }

        foreach ($friendlist as $k => &$v) {

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

        return $friendlist;
    }

    /**
     * 好友列表
     * 
     * @author zengmm
     * @date 2012/7/25
     *
     * @history <yaohaiqi><2012/3/28>
     */
    public function friendlist() {

        // 初始化好友列表
        $friend = array(
            'total' => 0,
            'data' => array()
        );

        $userinfo = array(
            'url' => mk_url('main/index/profile', array('dkcode'=>$this->action_dkcode)),
            'src' => get_avatar($this->action_uid,'ss'),
            'username' => $this->action_user['username'],
            'is_self' => $this->is_self
        );

        //获得好友数
        $numOfFriends = (int) $this->friendmodel->getNumOfFriends($this->action_uid, $this->uid, $this->is_self);
        $friend['total'] = $userinfo['numOfFriends'] = $numOfFriends;

        if ($friend['total'] > 0) {

            // 获取好友列表
            $friend['data'] = $this->friendmodel->getFriendsWithInfo($this->action_uid, $this->uid, $this->is_self, 1, self::LIMIT);

            $friend['data'] = $this->_combineFriendList($friend['data'], $this->uid);

        }
        $this->assign('action_dkcode', $this->action_dkcode);

        $this->assign('userinfo', $userinfo);

        $this->assign('friend', $friend);

        $this->display('friend/list.html');
    }
    
	/**
	 * 个人失效列表
	 * @author boolee 2012/7/27
	 */
    function invalidFriends(){
		if( !$this->is_self )
		$this->redirect('main/friend/invalidFriends');
		
    	// 初始化关注个人列表
		$friends = array(
			'total' => 0,
			'data' => array()
		);
        // 获得过期关注数
        $friends['total'] = $this->friendmodel->getNumOfInvalidateFriends($this->uid);

        //获得过期关注列表
        if ($friends['total'] > 0) {

			// 当前参数中的数字1表示分页的页码数
            $friends['data'] = $this->friendmodel->getInvalidateFriendsWithInfo($this->action_uid, $this->is_self, 1, self::LIMIT, $this->uid);

            $friends['data'] = $this->_combineFriendsList($friends['data']);
        }

        $this->assign('is_self', $this->is_self);
        $this->user['src'] = get_avatar($this->uid,'ss');
		$this->assign('userinfo', $this->user);
		$this->assign('friends', $friends);
        $this->display('friend/invalidlist.html');
	}
	/**
	 * 关注过期好友分页加载
	 * @author boolee 2012/7/30
	 * @return json
	 * */
	function getInvalidateFriendByPage(){
        $friend = array(
            'total' => 0,
            'data' => array()
        );

        //获得页码
        $page = intval($this->input->post('pager'));
        $page = !empty($page) ? $page : 1;

        //获得数量
         $friends['total'] = $this->friendmodel->getNumOfInvalidateFriends($this->uid);

        if ($friends['total'] > 0) {

            //获得好友列表
            $friends['data'] = $this->friendmodel->getInvalidateFriendsWithInfo($this->action_uid, $this->is_self, $page, self::LIMIT, $this->uid);
            $friends['data'] = $this->_combineFriendList($friends['data']);
        }

        //判断是否为最后一页
        $last = ($friends['total'] > $page * self::LIMIT) ? FALSE : TRUE;

        $data = array(
            'last' => $last,
            'isSelf' => $this->is_self,
            'data' => $friends['data']
        );

        $this->_ajaxReturn(1, 'success', $data);
	}
	/**
	 * 组合好友列表中单条记录信息
	 *
	 * @author boolee
	 * @date 2012/7/27
	 *
	 * @param array $friendslist 关注个人列表
	 *
	 * @return array
	 */
	private function _combineFriendsList($friendslist = array()) {

		if (empty($friendslist)) { return array(); }

		foreach ($friendslist as $k => &$v) {

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

		return $friendslist;
	}
    /**
     * 好友列表滚动分页
     *
     * @author zengmm
     * @date 2012/7/25
     *
     * @history <yaohaiqi><2012/3/28>
     */
    public function getFriendByPage(){

        $friend = array(
            'total' => 0,
            'data' => array()
        );

        //获得页码
        $page = intval($this->input->post('pager'));
        $page = !empty($page) ? $page : 1;

        //获得好友数量
        $friend['total'] = (int) $this->friendmodel->getNumOfFriends($this->action_uid, $this->uid, $this->is_self);

        if ($friend['total'] > 0) {

            //获得好友列表
            $friend['data'] = $this->friendmodel->getFriendsWithInfo($this->action_uid, $this->uid, $this->is_self, $page, self::LIMIT);
            $friend['data'] = $this->_combineFriendList($friend['data'], $this->uid);
        }

        //判断是否为最后一页
        $last = ($friend['total'] > $page * self::LIMIT) ? FALSE : TRUE;

        $data = array(
            'last' => $last,
            'isSelf' => $this->is_self,
            'data' => $friend['data']
        );

        $this->_ajaxReturn(1, 'success', $data);
    }

    /**
     * 通过用户名查找好友
     *
     * @author zengmm
     * @date 2012/7/25
     *
     * @history <yaohaiqi><2012/3/28>
     */
    public function searchFriendByName(){

        $friend = array(
            'total' => 0,
            'data' => array()
        );

        $last = TRUE;

        // 获得页码
        $page = intval($this->input->post('pager'));
        $page = !empty($page) ? $page : 1;

        // 获取搜索关键字
        $keyword = $this->input->post('keyword');

        //获得好友列表
        if(!empty($keyword)){

            $friend = $this->friendmodel->getFriendByName($this->uid, $keyword, $page, self::LIMIT);

        }else{

            // 用户输入关键字后再将其删除

            // 好友数
            $friend['total'] = (int) $this->friendmodel->getNumOfFriends($this->action_uid, $this->uid, $this->is_self);

            if ($friend['total'] > 0) {
                // 获得好友列表
                $friend['data'] = $this->friendmodel->getFriendsWithInfo($this->action_uid, $this->uid, $this->is_self, $page, self::LIMIT);

            }
        }

        if ($friend['total'] > 0) {

            // 是否最后一页
            if($friend['total'] - $page * self::LIMIT > 0) {
                $last = FALSE;
            }

            $friend['data'] = $this->_combineFriendList($friend['data'], $this->uid);
        }

        $data = array(
            'last' => $last,
            'isSelf' => $this->is_self,
            'data' => $friend['data']

        );

        $this->_ajaxReturn(1, 'success', $data);

    }

    /**
     * 隐藏或显示好友
     *
     * @author zengmm
     * @date 2012/7/25
     *
     * @history <yaohaiqi><2012/3/28>
     */
    public function hideFriend() {

        // 好友UID
        $f_uid = $this->input->post('f_uid');

        // 是显示操作还是隐藏操作
        $visible = $this->input->post('visible') == 'false' ? FALSE : TRUE;

        // 当前的状态
        $status = $this->friendmodel->hiddenStatus($this->uid, $f_uid);

        if ($visible) {
            // 显示操作
            if ($status) {
                // 当前隐藏状态,设置成显示状态
                $result = $this->friendmodel->unHideFriend($this->uid ,$f_uid);
            } else {
                // 当前是显示状态,无需设置显示
                $result = TRUE;
            }
        } else {
            // 隐藏操作
            if ($status) {
                // 当前隐藏状态,无需设置隐藏
                $result = TRUE;
            } else {
                // 当前显示状态,设置成隐藏状态
                $result = $this->friendmodel->hideFriend($this->uid ,$f_uid);
            }
        }

        if ($result) {
            // 设置成功
            $status = 1;
            $msg = 'success';
        } else {
            // 设置失败
            $status = 0;
            $msg = 'error';
        }

        $this->_ajaxReturn($status, $msg);
    }

	/**
	 * 获取用户最新的好友
	 *
	 * @deprecated
	 *
	 * @author zengmm
	 * @date 2012/7/31
	 */
	public function newestFriend()
	{
		$friend = $this->friendmodel->getNewestFriend($this->uid, 0, 7);

		if ($friend) {

			foreach ($friend as &$v) {
				// 用户头像地址 65*65
				$v['src'] = get_avatar($v['id'], 'mm');

				$v['href'] = mk_url('main/index/profile', array('dkcode' => $v['dkcode']));
			}
		}

		$data = array(
			'href' => mk_url('main/friend/friendlist'),
			'data' => $friend
		);

		$this->_ajaxReturn(1, 'success', $data);
	}
}