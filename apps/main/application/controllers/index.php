<?php

/**
 * 首页文件
 * @author        chenjiali
 * @date          2012/03/27
 * @version       1.0
 * @description   首页相关功能
 */
class Index extends MY_Controller {
    /**
     * 目标用户是否是本人
     * 
     * @var boolean 
     */

    /**
     * 构造函数
     */
    function __construct() {
        parent::__construct();
        //lanyanguang 如果action_dkcode 存在，但是action_dkcode是有错误的，那么跳转到404
        if ($this->action_dkcode && !$this->action_uid) {
            show_404($page = '', FALSE);
        }
        
        //判断是否本人
        if (!$this->action_uid) {
            $this->action_uid = $this->uid;
            
            //获取生日，家乡，当前住址
            $userinfo = service('User')->getUserInfo($this->dkcode,'dkcode');           
            $this->user['now_addr'] = $userinfo['now_addr']; 
            $this->user['home_addr'] = $userinfo['home_addr'];
            $this->user['birthday'] = $userinfo['birthday'];              
            $this->action_user = $this->user;
            $this->action_dkcode = $this->dkcode;
            $this->is_self = true;            
        } elseif ($this->action_uid == $this->uid) {
            $this->is_self = true;
        }        
        $this->load->model('apimodel');
        $this->load->helper('main');
    }
    
    /**
     * 网站首页，应新需求变化，原先首页变更为个人首页，新首页采用和facebook相同的样式
     * @author hexin
     * @date 2012/07/18
     */
    public function main() {

    	if($dkcode = $this->input->get('dkcode')){
    		$this->redirect('main/index/profile/', array('dkcode' => $dkcode));
    	}

        //视频上传录制 wangying
		$this->config->load("video");

        // start rewrite by zengmm 2012/7/30

        $this->load->model('followingmodel');

        // 获取用户关注的人
        $following = $this->followingmodel->getFollowingsWithInfoByOffset($this->uid, $this->is_self, 0, 7, $this->uid);
        if ($following) {
            foreach ($following as &$v) {
                // $v['id'] 用户UID
                $v['avatar'] = get_avatar($v['id'], 'mm');
            }
        }
        $this->assign('following', $following);       
        
        // end rewrite by zengmm 2012/7/30

        $this->assign('is_main',1); //zhoutl 在个人页
		
		$this->menu_left();

		$this->display('navigation/index');
    }
    
    /**
     * 左侧菜单栏需要的数据，@todo:其他模块如有需要页面和首页保持一致，请自行把数据获取代码拷走，或做统一的公共方法
     * @author: hexin
     * @data: 2012-07-25
     */
    private function menu_left(){
    	//左侧自定义群组数据
		$groups = service('Group')->getGroupsByCustom($this->uid);
		$this->assign('left_groups', $groups);

    	$this->user['avatar'] = get_avatar($this->uid);
		$this->assign('user', $this->user);
		$this->assign('level', service('credit')->getLevel($this->uid));

        // start add by zengmm 2012/7/30
        // 获取用户关注网页的所属频道
        $followingChannel = service('Attention')->getFollowingChannel($this->uid);
        $this->assign('followingChannel', $followingChannel);
        // end add by zengmm 2012/7/30
    }

    /**
     * 个人首页页面
     *
     * @author chenjiali
     * @date   2012/03/27
     * @access public
     * @param 
     */
    function profile() {	
      /* 无效代码，暂时注释
        $data = call_soap('purview', 'SystemPurview', 'getPurviewList', array('module' => 'timeline'));
        $res = json_decode($data['purview']);
        foreach ($res as $key => $value) {
            $result[$key]['id'] = $value->purview;
            $result[$key]['title'] = $value->name;
        }
       */
	   $coverurl = get_cache('cover_'.$this->action_uid);
		if (!empty($coverurl)) {
			// $setting = getConfig('fastdfs','avatar');
			$cover = 'http://' . config_item('fastdfs_domain') . $coverurl;
			$this->assign('iscover', true);
			$this->assign('cover', $cover);
		} else {
			$res = api('Passport')->get_cover($this->action_uid);
			set_cache('cover_'.$this->action_uid,$res);
			if (!empty($res)) {
				// $setting = getConfig('fastdfs','avatar');
				$cover = 'http://' . config_item('fastdfs_domain') . $res;
				$this->assign('iscover', true);
				$this->assign('cover', $cover);
			} else {
				$this->assign('iscover', false);
				$this->assign('cover', '');
			}
		}
        // if($this->is_self){
			// $coverurl = get_cache('cover_'.$this->uid);
			// if (!empty($coverurl)) {
				// $setting = getConfig('fastdfs','avatar');
				// $cover = 'http://' . $setting['host'] . $coverurl;
				// $this->assign('iscover', true);
				// $this->assign('cover', $cover);
			// } else {
				// $res = api('Passport')->get_cover($this->uid);
				// set_cache('cover_'.$this->uid,$res);
				// if (!empty($res)) {
					// $setting = getConfig('fastdfs','avatar');
					// $cover = 'http://' . $setting['host'] . $res;
					// $this->assign('iscover', true);
					// $this->assign('cover', $cover);
				// } else {
					// $this->assign('iscover', false);
					// $this->assign('cover', '');
				// }
			// }
		// }
		// else{
			// $res = api('Passport')->get_cover($this->action_uid);
			// if (!empty($res)) {
				// $setting = getConfig('fastdfs','avatar');
				// $cover = 'http://' . $setting['host'] . $res;
				// $this->assign('iscover', true);
				// $this->assign('cover', $cover);
			// } else {
				// $this->assign('iscover', false);
				// $this->assign('cover', '');
			// }
		// }
        // $cover = get_cover();
        // if ($cover) {
            // $this->assign('iscover', true);
            // $this->assign('cover', $cover);
        // } else {
            // $this->assign('iscover', false);
            // $this->assign('cover', '');
        // }
        
        $this->assign('action_uid', $this->action_uid);
        //获取封面(lvxinxin add) 结束
        //此处还需做权限判断
        $this->action_user['ismarry'] = getIsMarry($this->action_user['ismarry']);
        $this->action_user['birthday'] = (!empty($this->action_user['birthday'])) ? $this->action_user['birthday'] : '';

        //用户资料显示的权限判断
        if (false == $this->is_self) {
            //不是访问自己，需要添加权限判断
           require_once CONFIG_PATH . 'tables.php';
            $this->load->model('singleaccessmodel');
			$is_friend = $this->singleaccessmodel->isFriend($this->uid, $this->action_uid);
			if(false == $is_friend){
				$is_fans = $this->singleaccessmodel->isFans($this->action_uid, $this->uid);
			} else {
				$is_fans = true;
			}
            $isAllowBase = $this->singleaccessmodel->isAllow('base', $this->action_uid, $this->uid, $this->action_uid, $is_friend, $is_fans);
            //$isAllowPrivate = $this->singleaccessmodel->isAllow('private', $this->action_uid, $this->uid, $this->action_uid, $is_friend, $is_fans);

            if (!$isAllowBase) {
                //没有权限看基本信息
                $this->action_user['birthday'] = '';
                $this->action_user['ismarry'] = '';
                $this->action_user['now_addr'] = '';
                $this->action_user['home_addr'] = '';
            }
            /* 私密资料和基本资料合并成一个模块了，所以把私密资料的权限判断放到基本资料里了
             * author hxm date 2012/07/12
            if (!$isAllowPrivate) {
                //没有权限看私密信息
                $this->action_user['ismarry'] = '';
                $this->action_user['now_addr'] = '';
                $this->action_user['home_addr'] = '';
            }
            */
        }         
        //首页用户信息过长截断用...代替
        $this->action_user['now_addr'] = getShotRes($this->action_user['now_addr']);
        $this->action_user['home_addr'] = getShotRes($this->action_user['home_addr']);
        if (isset($this->action_user['now_addr']) && !empty($this->action_user['now_addr'])) {
            $this->action_user['now_addr'] = utf8substr($this->action_user['now_addr'], 0, 12);
        }
        if (isset($this->action_user['home_addr']) && !empty($this->action_user['home_addr'])) {
            $this->action_user['home_addr'] = utf8substr($this->action_user['home_addr'], 0, 12);
        }        
        $this->assign('user', $this->action_user);
        $this->assign('infoUrl', mk_url('user/userwiki/index', array('dkcode'=>$this->action_dkcode)));
        //$this->assign('fdfsinfo', array('host' => $this->config->item('fastdfs_host'), 'group' => $this->config->item('fastdfs_group')));
        //$this->assign('view', json_encode($result));

        //视频上传录制 wangying
		$this->config->load("video");
        $this->assign('videoname', date('YmdHis') . '_' . $this->uid);
        $this->assign('video_upload_url', config_item('video_upload_url'));
        $authcode_url = authcode('video', '', config_item('authcode_key'));
        $this->assign('authcode_url', base64_encode($authcode_url));
        $this->assign('recordurl', config_item('recordurl'));
        $this->assign('video_pic_domain', config_item('video_pic_domain'));
        $this->assign('video_src_domain', config_item('video_src_domain'));
        
        //相册上传显示guzhongbin
        $this->assign('fdfsinfo',config_item('fastdfs_domain'));

        //加关系的部分 lanyg 2012/03/21
        if (!$this->is_self) {
            //不是自己 需要获得用户与目标用户关系
            $relation = $this->apimodel->getRelationStatus($this->uid, $this->action_uid);
            $this->assign('f_uid', $this->action_uid);
            $this->assign('relation', $relation);
            //访问主页记录 boolee 2012/7/26
            service('Relation')->updateFollowTime($this->uid, $this->action_uid);
        }
        $this->assign('action_dkcode', $this->action_dkcode);
        $this->assign('msgurl', mk_url( 'main/msg/index'));
        $this->assign('is_self', $this->is_self);
        //加关系部分结束 lanyg 2012/03/21
        //获取应用区列表
        $this->load->model('appmenumodel');
        $user_app_list = $this->appmenumodel->getAppMenu($this->uid, $this->action_uid);
        
        //初始化用户应用区列表
        $app_list['nomove'] = array();
        $app_list['move'] = array();

        //判断是否为当前用户
        if (is_array($user_app_list)) {
            //发现兴趣及关注模块ID数组
            $app_default = array('interest', 'following');
            foreach ($user_app_list AS $key => $val) {
                $user_app_list[$key]['menu_title'] = trim($val['menu_title']);
                //获取图片地址
                if (!isset($val['group']) && empty($val['group'])) {
                    $user_app_list[$key]['menu_ico'] = MISC_ROOT . $val['menu_ico'];
                } else {
                	$user_app_list[$key]['menu_ico'] = 'http://' . config_item('fastdfs_domain') . DIRECTORY_SEPARATOR .$val['menu_ico'];
                }
                //获取自定义权限
                if (isset($val['userlist_content']) && $val['userlist_content']) {
                    $user_app_list[$key]['userlist_content'] = implode(',', json_decode($val['userlist_content']));
                }

                if (isset($val['weight'])) {
                    $user_app_list[$key]['weight'] = $val['weight'] ? $val['weight'] : 1;
                } else {
                    $user_app_list[$key]['weight'] = 1;
                }

                $sort[$key] = $val['menu_sort'];

                if (!empty($val['releation'])) {
                    foreach ($val['releation'] AS $k => $v) {
                        $user_app_list[$key]['cover'][$k]['name'] = $v['name'];
                        $user_app_list[$key]['cover'][$k]['ico'] = get_avatar($v['id'], 'ss');
                    }
                }
            	if(substr_count($val['menu_url'], '/') < 2){
					$val['menu_url'] = 'main/index/main';
                }
                if (!$this->is_self) {
					$user_app_list[$key]['menu_url'] = mk_url($user_app_list[$key]['menu_url'], array('dkcode'=>$this->action_dkcode));
                    //访问别人主页去掉发现兴趣、消息应用、
                    if (in_array($val['menu_moudle'], array('interest', 'msg', 'ask', 'favorite'))) {
                        unset($sort[$key]);
                        unset($user_app_list[$key]);
                    }
                } else {
                    //必要的应用接口地址加上dkcode;
                    if (in_array(trim($val['menu_moudle']), array('interest', 'follower', 'blog'))) {
                        $user_app_list[$key]['menu_url'] = mk_url($val['menu_url'], array('dkcode'=>$this->action_dkcode));
                    } else {
                    	$user_app_list[$key]['menu_url'] = mk_url($val['menu_url']);
                    }
                }

                //判断是否为发现兴趣及关注模块, 两模块不能移动, 以防显示冲突
                if (in_array($val['menu_moudle'], $app_default)) {
                    //不可移动模块
                    //访问别人主页时去掉发现兴趣应用, 避免出错
                    if (isset($user_app_list[$key]))
                        $app_list['nomove'][] = $user_app_list[$key];
                } else {
                    //可移动模块
                    if (isset($user_app_list[$key]))
                        $app_list['move'][] = $user_app_list[$key];
                }
            }
        }
		
        // 登录者的用户信息
        $login_info['avatar_url'] = get_avatar($this->uid);
        $login_info['uid'] = $this->uid;
        $login_info['username'] = $this->user['username'];
        $login_info['url'] = mk_url( 'main/index/main', array('action_dkcode' => $this->dkcode));

        // 时间线信息头像
        $action_avatar = ($this->is_self) ? $login_info['avatar_url'] : get_avatar($this->action_uid);

		// start add by zengmm 2012/7/13

		// 关注，粉丝，好友数
		$relation_entrance = array();

		$relation_count = service('Relation')->getRelationNums($this->action_uid, $this->uid);

		// 关注数包括个人和网页
		$relation_entrance['following_count'] = $relation_count[$this->action_uid]['following'];

		$relation_entrance['follower_count'] = $relation_count[$this->action_uid]['follower'];

		$relation_entrance['friend_count'] = $relation_count[$this->action_uid]['friend'];

		$this->assign('relation_entrance', $relation_entrance);

		// end add by zengmm 2012/7/13
		
		// 用户等级信息 Devin Yee
		if ($this->is_self) {
			$this->assign('creditInfo', service('credit')->getInfo($this->uid));
		} else {
			$this->assign('creditInfo', service('credit')->getInfo($this->action_uid));
		}

        $this->assign('action_avatar', $action_avatar);
        $this->assign('login_info', $login_info);
        $this->assign('user_app_list', $app_list);
        $this->assign('dkcode', $this->dkcode);
        $this->display('timeline/index');
    }

    /**
     * 获取好友信息的接口
     * 佘德权
     */
    function getFriends() {
        $res = array('status' => 0, 'data' => array());
            $uid = $this->getLoginUID();
           	$this -> load -> model('friendmodel', 'friend');
	        $friends = $this->friend->getAllFriendsByUid($uid);
            if (!empty($friends)) {
                $res['status'] = 1;
                foreach ($friends as $key => $friend) {
                    $friends[$key]['face'] = get_avatar($friend['id'], 'ss');
                }
                $res['data'] = $friends;
            }
        $this->ajaxReturn($res['data'], '', $res['status']);
    }

    //debug
    function testSession() {
//        $this->load->library('Session2');
//        
//        $session = Session2::getInstance();
//        $session['uname'] = 'dequan';
//
//        var_dump($session);
//        exit;
////      
                
        echo $_SESSION->getId();
        echo '<br>';
        
        $_SESSION['uname'][] = 'bob';
//        $_SESSION['age'] = '25';
//        $_SESSION['dkcode'] = '123456';
        
//        $_SESSION->set($_SESSION->getId(), 'uname', 'michael');
//        $res = $_SESSION->get_all($_SESSION->getId());
//        var_dump($res);
//        exit;
        
//        foreach ($_SESSION as $key => $val) {
//            echo $key . ' - ' . $val;
//            echo '<br>';
//        }
//        
        var_dump($_SESSION);
        exit;
        
        echo $this->session2->getId();
        exit;
        
        $session = $this->session2->getInstance();
        $sessionId = $session->getId();
        var_dump($sessionId);
        echo '<br>';
        
        $session->setData('uname', 'jacob', 120);
        $session->setData('dkcode', '123456', 120);
        
        $session['uname'] = 'dequan';
        $session['dkcode'] = '123456';
        
        $res = isset($session['uname']);
        var_dump($res);
        echo '<br>';
        
        $res = $session['uname'];
        var_dump($res);
        echo '<br>';
        
        unset($session['dkcode']);
//        
        echo "<hr>";
        $res = $session->getIterator();
        var_dump($res);
        echo '<br>';
        echo $session->getId();
        echo "<hr>";
        foreach ($session as $key => $val) {
            echo $key . ' - ' . $val;
            echo '<br>';
        }
        echo "<hr>";
        
        $res = $session->getAll();
        var_dump($res);
        echo '<br>';
    }

}
/* End of file index.php */
/* Location: ./application/controllers/index.php */
