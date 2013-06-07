<?php
/**
 * 关注列表
 *
 * 当前程序中包括了关注首页(后期将会调整成网站首页)
 *
 * @author zengmm
 * @date 2012/7/17
 *
 * @history <boolee><2012/7/10> & <lanyangguang><2012/3/1>
 */
class Following extends MY_Controller {

	/**
	 * 常量定义
	 */
	
	// 每页数量
	const LIMIT = 20;

	/**
	 * 网页分类ID
	 *
	 * @var int
	 */
	protected $web_cateid;

	/**
	 * 频道ID
	 *
	 * @var int
	 */
	private $_channel_id;



    /**
     * 构造函数
	 *
	 * @author zengmm
	 * @date 2012/7/17
     */
    public function __construct() {

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

		// 获取网页分类ID
        $this->web_cateid = (int) $this->input->get_post('web_cateid');

        // 获取频道ID
        $this->_channel_id = (int) $this->input->get_post('channel_id');

		// 关注模型
        $this->load->model('followingmodel');
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
	 * 组合关注个人列表中单条记录信息
	 *
	 * @author zengmm
	 * @date 2012/7/20
	 *
	 * @param array $followinglist 关注个人列表
	 *
	 * @return array
	 */
	private function _combineFollowingList($followinglist = array()) {

		if (empty($followinglist)) { return array(); }

		foreach ($followinglist as $k => &$v) {

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

		return $followinglist;
	}

	/**
	 * 组装关注网页列表中单条记录信息
	 *
	 * @author zengmm
	 * @date 2012/7/20
	 *
	 * @param array $followlist 关注网页列表
	 *
	 * @return array
	 */
	private function _combineFollowingWebpage($following_webpage = array()) {

		if (empty($following_webpage)) { return array(); }
		
		foreach ($following_webpage as &$v) {
			// 网页头像
			$v['src'] = get_webavatar($v['aid'], 'mm');
			// 网页链接
			$v['href'] = mk_url('webmain/index/main', array('web_id' => $v['aid']));
			// 网页粉丝链接
			$v['follower_url'] = mk_url('webmain/follower/index', array('web_id'=> $v['aid']));
		}

		return $following_webpage;
	}
    
    /**
     * 生成被访问者的基本信息
     *
	 * @author zengmm
	 * @date 2012/7/17
	 * 
	 * @history <lanyanguang><2012-03-01>
	 *
     * @return array
     */
    private function _getVisitedUserInfo() {
        
        $userinfo = array(
            'uid' => $this->action_uid,
            'avatar' => get_avatar($this->action_uid, 'ss'),
            'username' => $this->action_user['username'],
			// 个人主页的地址
            'url' => mk_url('main/index/profile', array('dkcode' => $this->action_dkcode))
        );

		return $userinfo;
    }

	/**
     * 关注个人列表
	 *
	 * @author zengmm
	 * @date 2012/7/17
     * 
	 * @history lanyanguang 2012-03-01
     */
    public function followingList() {

		// 初始化关注个人列表
		$following = array(
			'total' => 0,
			'data' => array()
		);

        // 被访问者的用户信息
        $userinfo = $this->_getVisitedUserInfo();
        
        // 获得关注数
        $following['total'] = (int) $this->followingmodel->getNumOfFollowings($this->action_uid, $this->is_self, $this->uid);
        
        //获得关注列表
        if ($following['total'] > 0) {

			// 当前参数中的数字1表示分页的页码数
            $following['data'] = $this->followingmodel->getFollowingsWithInfo($this->action_uid, $this->is_self, 1, self::LIMIT, $this->uid);

            $following['data'] = $this->_combineFollowingList($following['data']);
        }

		// 被访问者的端口号
		$this->assign('action_dkcode', $this->action_dkcode);
        
		// 被访问者的基本信息
        $this->assign('userinfo', $userinfo);

        $this->assign('is_self', $this->is_self);

		$this->assign('following', $following);

        $this->display('following/list.html');
    }

	/**
	 * 关注个人列表的滚动分页
	 *
	 * @author zengmm
	 * @date 2012/7/19
	 *
	 * @history <lanyanguang><2012-03-01>
	 */
    public function getFollowingsByPage() {

		// 初始化关注个人列表
		$following = array(
			'total' => 0,
			'data' => array()
		);

		// 初始化是否是最后一页
        $last = TRUE;

		// 获得页码
        $page = (int) $this->input->post("pager");
        $page = !empty($page) ? $page : 1;
        
        // 获得关注数
        $following['total'] = (int) $this->followingmodel->getNumOfFollowings($this->action_uid, $this->is_self, $this->uid);
        
        // 获得关注列表
        if ($following['total'] > 0) {

			if ($following['total'] - $page * self::LIMIT > 0) {
				$last = FALSE;
			}

            $following['data'] = $this->followingmodel->getFollowingsWithInfo($this->action_uid, $this->is_self, $page, self::LIMIT, $this->uid);

			$following['data'] = $this->_combineFollowingList($following['data']);
        }
        
        // 返回json数组   
        $data = array(
			'data' => $following['data'],
			'last' => $last,
			'isSelf' => $this->is_self,
		);

		$this->_ajaxReturn(1, 'success', $data);
    }
	/**
	 * 过期关注个人列表的滚动分页
	 *
	 * @author boolee
	 * @date 2012/7/25
	 */
    public function getInvalidateFollowingsByPage() {
		// 初始化关注个人列表
		$following = array(
			'total' => 0,
			'data' => array()
		);

		// 初始化是否是最后一页
        $last = TRUE;

		// 获得页码
        $page = (int) $this->input->post("pager");
        $page = !empty($page) ? $page : 1;
        
        // 获得关注数
        $following['total'] = (int) $this->followingmodel->getNumOfInvalidateFollowings($this->uid);
        
        // 获得关注列表
        if ($following['total'] > 0) {

			if ($following['total'] - $page * self::LIMIT > 0) {
				$last = FALSE;
			}

            $following['data'] = $this->followingmodel->getInvalidateFollowingsWithInfo($this->action_uid, $this->is_self, $page, self::LIMIT, $this->uid);

			$following['data'] = $this->_combineFollowingList($following['data']);
        }
        
        // 返回json数组   
        $data = array(
			'data' => $following['data'],
			'last' => $last,
			'isSelf' => $this->is_self,
		);

		$this->_ajaxReturn(1, 'success', $data);
    }
	/**
	 * 通过用户名查找关注的用户
	 *
	 * @author zengmm
	 * @date 2012/7/19
	 * 
	 * @history <zengmm><2012-03-01>
	 */
    public function searchFollowingByUserName() {

        // 获得搜索关键字
        $keyword = $this->input->post('keyword');
        
        // 获得页码
        $page = (int) $this->input->post("pager");
        $page = !empty($page) ? $page : 1;

		// 初始化关注个人列表
		$following = array(
			'total' => 0,
			'data' => array()
		);
        
		// 初始化是否是最后一页
        $last = TRUE;
        
        if($keyword != '') {
			// 用户输入的关键字不为空

            $following = $this->followingmodel->getFollowingsByUsername($this->uid, $keyword, $page, self::LIMIT);

        } else {
			// 用户输入关键字后再将其删除

            // 获得关注数
            $following['total'] = (int) $this->followingmodel->getNumOfFollowings($this->action_uid, $this->is_self, $this->uid);

            // 获得关注列表
            if ($following['total']>0) {

                $following['data'] = $this->followingmodel->getFollowingsWithInfo($this->action_uid, $this->is_self, $page, self::LIMIT, $this->uid);
            }
        }

		if ($following['total'] > 0) {

			// 是否最后一页
			if($following['total'] - $page * self::LIMIT > 0) {
				$last = FALSE;
			}

			$following['data'] = $this->_combineFollowingList($following['data']);
		}
	
		//返回json数组   
		$data = array(
			'data' => $following['data'],
			'last' => $last,
			'isSelf' => $this->is_self,
		);

		$this->_ajaxReturn(1, 'success', $data);
    }

    /**
     * 获取个人关注列表中已无效的用户
     * 需要判断当前访问的用户是自己
     * boolee 7/25
     */
    public function invalidFollowingList()
    {
    	//不是自己访问，一律跳转至自己模块
		if( !$this->is_self )
		$this->redirect('main/following/invalidFollowingList');
		
    	// 初始化关注个人列表
		$following = array(
			'total' => 0,
			'data' => array()
		);
		
        // 被访问者的用户信息
        $userinfo = $this->_getVisitedUserInfo();
        
        // 获得过期关注数
        $following['total'] = $this->followingmodel->getNumOfInvalidateFollowings($this->uid);

        //获得过期关注列表
        if ($following['total'] > 0) {

			// 当前参数中的数字1表示分页的页码数
            $following['data'] = $this->followingmodel->getInvalidateFollowingsWithInfo($this->action_uid, $this->is_self, 1, self::LIMIT, $this->uid);

            $following['data'] = $this->_combineFollowingList($following['data']);
        }
		// 被访问者的端口号
		$this->assign('action_dkcode', $this->action_dkcode);
        
		// 被访问者的基本信息
        $this->assign('userinfo', $userinfo);

        $this->assign('is_self', $this->is_self);

		$this->assign('following', $following);

        $this->display('following/invalidlist.html');
    }

	/**
     * 隐藏/显示 关注个人
     *
	 * @author zengmm
	 * @date 2012/7/20
	 *
	 * @history <lanyanguang><2012-03-01>
     */
    public function visibleFollowing() {

        //获得关注对象
        $f_uid = (int) $this->input->post('f_uid');

        //获得可见性
        $visible =  $this->input->post('visible');
        $status = $this->followingmodel->isHiddenFollowing($this->uid, $f_uid);

        if ($visible == 'false') {
            if ($status) {
                $result = true;
            } else {   
                $result = $this->followingmodel->hideFollowing($this->uid, $f_uid);
            }
        } else {
            if (!$status) {
                $result = true;
            } else {  
                $result = $this->followingmodel->unHideFollowing($this->uid, $f_uid);
            }
        }
        if ($result) {
			$this->_ajaxReturn(1, 'success');
        } else {
			$this->_ajaxReturn(0, 'success');
        }
    }

	/**
	 * 关注的网页列表
	 *
	 * @author zengmm
	 * @date 2012/7/19
	 *
	 * @history <lanyanguang><2012-04-24>
	 */
    public function webFollowingList() {

		// 初始化关注网页列表
		$following_webpage = array(
			'total' => 0,
			'data' => array()
		);

        //用户主页信息
        $userinfo = $this->_getVisitedUserInfo();

		// 获取用户关注的分类
		$following_webpage_cate = $this->followingmodel->getWebFollowingCategory($this->action_uid, $this->is_self, $this->_channel_id);
        
        if (empty($this->web_cateid) && empty($this->_channel_id)) {

			// 获取所有关注的网页
			$following_webpage = $this->followingmodel->getFollowingWebpages($this->action_uid, $this->is_self, 0, self::LIMIT, $this->uid);

		} else {

			if ($this->web_cateid) {

				// 根据目录获取关注的网页

				// 获得某网页分类信息
				$cateinfo = $this->followingmodel->get_iid_info($this->web_cateid);
		   
				// 某个分类下关注的网页
				$following_webpage = $this->followingmodel->getWebpagesByWebcate($this->action_uid , $this->web_cateid , $this->is_self, 0, self::LIMIT, $this->uid);

			} elseif ($this->_channel_id) {

				// 根据频道获取关注的网页
				$following_webpage = $this->followingmodel->getWebpagesByChannel($this->uid , $this->_channel_id , TRUE, 0, self::LIMIT, $this->uid);
			}
			
		}

		if ($following_webpage['total'] > 0) {
			$following_webpage['data'] = $this->_combineFollowingWebpage($following_webpage['data']);
		}

		$webcateinfo = array(
			'channelid' => $this->_channel_id,
			'cateid' => $this->web_cateid,
			'name' => isset($cateinfo['iname']) ? $cateinfo['iname'] : '网页'
		);

		$this->assign('webcateinfo', $webcateinfo);

		$this->assign('action_dkcode', $this->action_dkcode);

		$this->assign('following_webpage_cate', $following_webpage_cate);
        
        $this->assign('userinfo', $userinfo);

        $this->assign('is_self', $this->is_self);
        
        $this->assign('outdateurl', mk_url('main/following/webOutDateList'));

        $this->assign('following_webpage', $following_webpage);

        $this->display('following/weblist.html');
    }

	/**
	 * 关注的失效网页列表
	 *
	 * @author boolee
	 * @date 2012/7/20
	 */
    public function webOutDateList() {
    	//只能访问自己的无效关注列表
    	if(!$this->is_self)
    	$this->redirect('main/following/webOutDateList');
    	
		// 初始化关注网页列表
		$following_webpage = array(
			'total' => 0,
			'data' => array()
		);

        //用户主页信息
        $userinfo = $this->_getVisitedUserInfo();

		// 获取用户关注的分类
		$following_webpage_cate = $this->followingmodel->getInvalidFollowingWebcate($this->action_uid);

        if (empty($this->web_cateid)) {
        	// 获取所有失效的关注网页
			$following_webpage=$this->followingmodel->getInvalidWebpage($this->action_uid, $this->is_self, 1, self::LIMIT, $this->uid);
        } else {
			// 根据目录获取关注的网页

			//获得某网页分类信息
			$cateinfo = $this->followingmodel->get_iid_info($this->web_cateid);
		   	
			//某个分类下关注的网页
			$following_webpage = $this->followingmodel->getUnvalidateAttentionWeb($this->action_uid , $this->web_cateid , $this->is_self, 0, self::LIMIT, $this->uid);
		}

		if ($following_webpage['total'] > 0) {
			$following_webpage['data'] = $this->_combineFollowingWebpage($following_webpage['data']);
		}

		$webcateinfo = array(
			'cateid' => $this->web_cateid,
			'name' => isset($cateinfo['iname']) ? $cateinfo['iname'] : '网页'
		);
		$this->assign('webcateinfo', $webcateinfo);

		$this->assign('action_dkcode', $this->action_dkcode);

		$this->assign('following_webpage_cate', $following_webpage_cate);
        
        $this->assign('userinfo', $userinfo);

        $this->assign('is_self', $this->is_self);
        
        $this->assign('returnurl', mk_url('main/following/webFollowinglist'));//返回url

        $this->assign('following_webpage', $following_webpage);

        $this->display('following/webouttimelist.html');
    }
    /**
	 * 关注的失效网页列表滚动分页
	 *
	 * @author boolee
	 * @date 2012/7/20
	 */
    public function webOutDateListByPage() {
    	//自己才有数据
    	if(!$this->is_self)
    	$this->_ajaxReturn(0,'badRequest!','');
    	
		// 初始化关注网页列表
		$following_webpage = array(
			'total' => 0,
			'data' => array()
		);

        //获得页码
        $page = (int) $this->input->post("pager");
        $page = !empty($page) ? $page : 1;
		// 初始化是否是最后一页
		$last = TRUE;

		if (empty($this->web_cateid)) {
			// 获取所有关注的网页
			$following_webpage = $this->followingmodel->getInvalidWebpage($this->action_uid, $this->is_self, $page, self::LIMIT, $this->uid);
		} else {
			// 根据目录获取关注的网页
			$following_webpage = $this->followingmodel->getUnvalidateAttentionWeb($this->action_uid , $this->web_cateid , $this->is_self, $page, self::LIMIT, $this->uid);
		}
        
        if ($following_webpage['total'] > 0) {

			//是否最后一页
        if($following_webpage['total'] - $page * self::LIMIT > 0) {
			$last = FALSE;
		}
			
			$following_webpage['data'] = $this->_combineFollowingWebpage($following_webpage['data']);
		}
        
		//返回json数组   
        $data = array(
			'data' => $following_webpage['data'],
			'last' => $last,
			'isSelf' => $this->is_self,
		);
		$this->_ajaxReturn(1, 'success', $data);
    }
    
	/**
     * 关注网页列表的滚动分页
	 *
	 * @author zengmm
	 * @date 2012/7/20
     * 
	 * @history <lanyangguang><2012-04-24>
     * 
     */
    public function getWebFollowingsByPage() {

		// 初始化关注网页列表
		$following_webpage = array(
			'total' => 0,
			'data' => array()
		);

        //获得页码
        $page = (int) $this->input->post("pager");
        $page = !empty($page) ? $page : 1;

		// 初始化是否是最后一页
		$last = TRUE;

		if (empty($this->web_cateid)) {
			// 获取所有关注的网页
			$following_webpage = $this->followingmodel->getFollowingWebpages($this->action_uid, $this->is_self, self::LIMIT * ($page-1), self::LIMIT, $this->uid);

		} else {
			// 根据目录获取关注的网页
			$following_webpage = $this->followingmodel->getWebpagesByWebcate($this->action_uid , $this->web_cateid , $this->is_self, self::LIMIT * ($page-1), self::LIMIT, $this->uid);
		}
        
        if ($following_webpage['total'] > 0) {

			//是否最后一页
			if ($following_webpage['total'] - $page * self::LIMIT >= 0) {
				$last = FALSE;
			}

			$following_webpage['data'] = $this->_combineFollowingWebpage($following_webpage['data']);
		}
        
		//返回json数组   
        $data = array(
			'data' => $following_webpage['data'],
			'last' => $last,
			'isSelf' => $this->is_self,
		);

		$this->_ajaxReturn(1, 'success', $data);
    }

	/**
	 * 通过网页名查找关注的网页
	 *
	 * @author zengmm
	 * @date 2012/7/20
	 *
	 * @history <lanyanguang><2012-03-01>
	 */
    public function searchWebFollowingByUserName() {

		// 初始化关注网页列表
		$following_webpage = array(
			'total' => 0,
			'data' => array()
		);

        //获得搜索关键字
        $keyword = $this->input->post('keyword');
        
        //获得页码
        $page = (int) $this->input->post("pager");
        $page = !empty($page) ? $page : 1;
        
		// 初始化是否是最后一页
        $last = TRUE;
        
        if(!empty($keyword)) {

            $following_webpage = $this->followingmodel->getWebpagesByUsername($this->uid, $this->web_cateid, $keyword, $page, self::LIMIT);

        } else {

            //某个分类下的关注网页
            $following_webpage = $this->followingmodel->getWebpagesByWebcate($this->action_uid , $this->web_cateid , $this->is_self, self::LIMIT * ($page-1), self::LIMIT, $this->uid);
        }

		if ($following_webpage['total'] > 0) {
			// 是否最后一页
			if($following_webpage['total'] - $page * self::LIMIT > 0) {
				$last = FALSE;
			}

			$following_webpage['data'] = $this->_combineFollowingWebpage($following_webpage['data']);
		}

		//返回json数组   
		$data = array(
			'data' => $following_webpage['data'],
			'last' => $last,
			'isSelf' => $this->is_self,
		);
		
		$this->_ajaxReturn(1, 'success', $data);
    }

	/**
     * 隐藏/显示关注的网页
	 *
	 * @author zengmm
	 * @date 2012/7/20
	 * 
	 * @history <lanyanguang><2012-03-01>
     * 
	 * @return array
     */
    public function visibleWebFollowing() {

        //获得网页ID
        $web_id = (int) $this->input->post('web_id');
        
        //获得可见性
        $visible =  $this->input->post('visible');

        //用户信息
        $info = array(
            'user_id' => $this->uid,
            'web_id' => $web_id
        );
        
        if ($visible == 'false') {

            $result = $this->followingmodel->hideWebFollowing($this->uid, $web_id);
            
            //隐藏网页更新索引
            $this->followingmodel->hidingAUserInWebpage($info);
        } else {
            $result = $this->followingmodel->unHideWebFollowing($this->uid, $web_id);
            
            //隐藏网页更新索引
            $this->followingmodel->unHidingAUserInWebpage($info);
        }

        if ($result) {
			$this->_ajaxReturn('1', 'success');
        } else {
			$this->_ajaxReturn('1', 'error');
        }
    }
 	/**
	 * 隐藏和取消隐藏网页关注分类
	 * @author boolee 2012/8/3
	 */
	function categoryHidden(){
		$iid = $this->input->get_post('iid');
		$is_show = $this->input->get_post('is_show') ? 1 : 0;

		$result = $this->followingmodel->categoryHidden($this->uid, $iid, $is_show);

		if ($result) {
			$this->_ajaxReturn('1', 'success');
        } else {
			$this->_ajaxReturn('1', 'error');
        }
	}
    /**
     *共同关注个人弹出层列表
     */
     function getFollowingsList(){
		$uid1 = $this->input->get_post('uid1');
    	$uid2 = $this->input->get_post('uid2');
    	$list = service('Relation')->getCommonFollowingsInfo($uid1, $uid2);
		$count= count($list);
		$return =array();
		//等到个人名字，链接
		foreach ($list as $id=>$pepole){
			$nowurl	= mk_url('main/index/profile',array('dkcode'=>$pepole['dkcode']));
			$return[]=array('img'=>get_avatar($id),'url'=>$nowurl,'name'=>$pepole['username']);
		}
		 $this->ajaxReturn(array('data'=>$return));
     }

    /**
     *共同好友弹出层列表
     */
    function getFriendsList(){
    	$uid1 = $this->input->get_post('uid1');
    	$uid2 = $this->input->get_post('uid2');
    	$list = service('Relation')->getCommonFriendsInfo($uid1, $uid2);
		$count= count($list);
		$return =array();
		//等到个人名字，链接
		foreach ($list as $id=>$pepole){
			$nowurl	= mk_url('main/index/profile',array('dkcode'=>$pepole['dkcode']));
			$return[]=array('img'=>get_avatar($id),'url'=>$nowurl,'name'=>$pepole['username']);
		}
		$this->ajaxReturn(array('data'=>$return));
    }
    
	/**
     *共同关注网页弹出层列表
     */
    function getWebFollowingsList(){
    	$uid1 = $this->input->get_post('uid1');
    	$uid2 = $this->input->get_post('uid2');
    	$list = service('WebpageRelation')->getCommonFollowingsInfo($uid1, $uid2);
		$count= count($list);
		$return =array();
		//等到个人名字，链接
		foreach ($list as $id=>$pepole){
			$nowurl	= mk_url('webmain/index/main',array('web_id'=>$pepole['aid']));
			$return[]=array('img'=>get_avatar($id),'url'=>$nowurl,'name'=>$pepole['name']);
		}
		$this->ajaxReturn(array('data'=>$return));
    }

    /**
     * 首页右边关注个人的列表
     *
     * @deprecated
     *
     * @author zengmm
     * @date 2012/7/31
     */
    public function newestFollowing()
    {
    	$following = $this->followingmodel->getFollowingsWithInfoByOffset($this->uid, $this->is_self, 0, 7, $this->uid);
        if ($following) {
            foreach ($following as &$v) {
                // $v['id'] 用户UID
                $v['avatar'] = get_avatar($v['id'], 'mm');

                $v['href'] = mk_url('main/index/profile', array('dkcode'=>$v['dkcode']));
            }
        }

        $data = array(
        	'href' => mk_url('main/following/followingList'),
        	'data' => $following
        );

        $this->_ajaxReturn(1, 'success', $data);
    }

    /**
     * 首页右边关注网页列表
     *
     * 根据频道ID获取相应的关注网页
     * @deprecated
     *
     * @author zengmm
     * @date 2012/7/31
     */
    public function newestFollowingWebpage()
    {
    	// 频道ID
    	$channel_id = $this->input->get_post('channel_id');

    	$newestFollowingWebpage = $this->followingmodel->getNewestFollowingWebpage($this->uid, $channel_id, 0, 7);

    	if ($newestFollowingWebpage) {
    		foreach ($newestFollowingWebpage as &$v) {

    			// 网页头像
				$v['src'] = get_webavatar($v['web_uid'], 'mm', $v['aid']);
				// 网页链接
				$v['href'] = mk_url('webmain/index/main', array('web_id' => $v['aid']));
    		}
    	}

    	$data = array(
    		'href' => mk_url('main/following/webFollowinglist', array('channel_id' => $channel_id)),
    		'data' => $newestFollowingWebpage
    	);

    	$this->_ajaxReturn(1, 'success', $data);

    }
}
/* End of file following.php */
/* Location: ./application/controllers/following.php */