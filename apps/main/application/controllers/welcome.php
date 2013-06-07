<?php

/**
 * 首页文件
 *
 * @author        chenjiali
 * @date          2012/03/27
 * @version       1.0
 * @description   首页相关功能
 * @history       <author><time><version><desc>
 */
class Welcome extends MY_Controller {
    /**
     * 目标用户是否是本人
     * 
     * @var boolean 
     */
    // private $_self = false;

    /**
     * 构造函数
     */
    function __construct() {
        parent::__construct();
        $this->load->library('Redisdb');
        //判断是否本人
        if (!$this->action_uid) {
            $this->action_uid = $this->uid;
            $this->action_user = $this->user;
            $this->action_dkcode = $this->dkcode;
            $this->_self = true;
        } elseif ($this->action_uid == $this->uid) {
            $this->_self = true;
        }

        $this->load->model('apimodel');
    }

    /**
     * 首页页面
     *
     * @author chenjiali
     * @date   2012/03/27
     * @access public
     * @param 
     */
    function index() {
        $data = call_soap('purview', 'SystemPurview', 'getPurviewList', array('module' => 'timeline'));
        $res = json_decode($data['purview']);
        foreach ($res as $key => $value) {
            $result[$key]['id'] = $value->purview;
            $result[$key]['title'] = $value->name;
        }

        /* //获取封面(lvxinxin add)
          if($this->_self){
          $cover = getCover($this->uid);
          }
          else{
          $cover = getCover($this->action_uid);
          //echo $cover.'--'.$this->action_uid;
          } */
        //获取封面(hujiashan edit)
        $cover = getCover($this->action_uid);
        if ($cover) {
            $this->assign('iscover', true);
            $this->assign('cover', $cover);
        } else {
            $this->assign('iscover', false);
            $this->assign('cover', '');
        }
        
        $this->assign('action_uid', $this->action_uid);
        //获取封面(lvxinxin add) 结束
        //此处还需做权限判断
        $this->action_user['ismarry'] = getIsMarry($this->action_user['ismarry']);
        $this->action_user['birthday'] = (!empty($this->action_user['birthday'])) ? $this->action_user['birthday'] : '';

        //用户资料显示的权限判断
        if (false == $this->_self) {
            //不是访问自己，需要添加权限判断
            require_once APPPATH . 'config/tables.php';
            $this->load->model('singleaccessmodel');
			$is_friend = $this->singleaccessmodel->isFriend($this->uid, $this->action_uid);
			if(false == $is_friend){
				$is_fans = $this->singleaccessmodel->isFans($this->action_uid, $this->uid);
			} else {
				$is_fans = true;
			}
            $isAllowBase = $this->singleaccessmodel->isAllow('base', $this->action_uid, $this->uid, $this->action_uid, $is_friend, $is_fans);
            $isAllowPrivate = $this->singleaccessmodel->isAllow('private', $this->action_uid, $this->uid, $this->action_uid, $is_friend, $is_fans);

            if (!$isAllowBase) {
                //没有权限看基本信息
                $this->action_user['birthday'] = '';
            }
            if (!$isAllowPrivate) {
                //没有权限看私密信息
                $this->action_user['ismarry'] = '';
                $this->action_user['now_addr'] = '';
                $this->action_user['home_addr'] = '';
            }
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
        $this->assign('infoUrl', mk_url('main/userwiki/index&action_dkcode=' . $this->action_dkcode));
        $this->assign('fdfsinfo', array('host' => $this->config->item('fastdfs_host'), 'group' => $this->config->item('fastdfs_group')));
        $this->assign('view', json_encode($result));
        //视频上传录制 wangying
        $this->assign('videoname', date('YmdHis') . '_' . $this->uid);
        $this->assign('video_upload_url', config_item('video_upload_url'));
        $authcode_url = authcode('module=1', '', config_item('authcode_key'));
        $this->assign('authcode_url', base64_encode($authcode_url));
        $this->assign('recordurl', config_item('recordurl'));
        $this->assign('video_pic_domain', config_item('video_pic_domain'));
        $this->assign('video_src_domain', config_item('video_src_domain'));

        //加关系的部分 lanyg 2012/03/21
        if (!$this->_self) {
            //不是自己 需要获得用户与目标用户关系
            $relation = $this->apimodel->getRelationStatus($this->uid, $this->action_uid);
            $this->assign('f_uid', $this->action_uid);
            $this->assign('relation', $relation);
        }
        $this->assign('action_dkcode', $this->action_dkcode);
        $this->assign('msgurl', mk_url('/main/msg/index'));
        $this->assign('is_self', $this->_self);
        //加关系部分结束 lanyg 2012/03/21
        //获取应用区列表
        $user_app_list = json_decode(call_soap('ucenter', 'UserMenuPurview', 'getUserPurviewMenu', array('userid' => $this->action_uid, 'nowuserid' => $this->uid)), true);
        $ctx = stream_context_create(array(
            'http' => array('timeout' => 2)
                ));
        //初始化用户应用区列表
        $app_list['nomove'] = array();
        $app_list['move'] = array();

        //判断是否为当前用户
        if (is_array($user_app_list)) {
            //发现兴趣及关注模块ID数组
            $app_default = array(1, 2);
            foreach ($user_app_list AS $key => $val) {
                $user_app_list[$key]['menu_title'] = trim($val['menu_title']);
                //获取图片地址
                $menu_param = explode('/', $val['menu_ico']);
                if (isset($val['group'])) {
                    $val['group'] = $val['group'];
                } else {
                    $val['group'] = '';
                }
                if (isset($menu_param[0]) && $menu_param[0] == 'img') {
                    $user_app_list[$key]['menu_ico'] = MISC_ROOT . $val['menu_ico'];
                } else {
                    $app_cover_host = config_item("app_cover_host");
                    $user_app_list[$key]['menu_ico'] = 'http://' . $app_cover_host[$val['group']] . '/' . $val['group'] . '/' .$val['menu_ico'];
                }

                //获取自定义权限
                if (isset($val['userlist_content']) && $val['userlist_content']) {
                    $user_app_list[$key]['userlist_content'] = implode(',', json_decode($val['userlist_content']));
                }

                $path_info = parse_url($val['menu_url']);
                if (!isset($path_info['query'])) {
                    $query_str = '?';
                } else if ($path_info['query']) {
                    $query_str = '&';
                }


                //检查数组中是否存在weight.(chenjiali 2012-3-29)
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

                if (!$this->_self) {
                    $user_app_list[$key]['menu_url'] = $user_app_list[$key]['menu_url'] . $query_str . "action_dkcode=" . $this->action_dkcode;
                    //访问别人主页去掉发现兴趣及消息应用
                    if ($val['menu_title'] == 1 || $val['menu_title'] == 11) {
                        unset($sort[$key]);
                        unset($user_app_list[$key]);
                    }
                } else {
                    //必要的应用接口地址加上dkcode;
                    if (in_array(trim($val['menu_id']), array(1, 5, 4, 8))) {
                        $user_app_list[$key]['menu_url'] = $user_app_list[$key]['menu_url'] . $query_str . "action_dkcode=" . $this->action_dkcode;
                    }
                }

                //判断是否为发现兴趣及关注模块, 两模块不能移动, 以防显示冲突
                if (in_array($val['menu_id'], $app_default)) {
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
        $login_info['url'] = mk_url('main/index/index', array('action_dkcode' => $this->dkcode));

        // 时间线信息头像
        $action_avatar = ($this->_self) ? $login_info['avatar_url'] : get_avatar($this->action_uid);
        $this->assign('action_avatar', $action_avatar);
        $this->assign('login_info', $login_info);
        $this->assign('user_app_list', $app_list);
        $this->display('timeline/index');
    }

    /**
     * 获取好友信息的接口
     * 佘德权
     */
    function getFriends() {
        $res = array('status' => 0, 'data' => array());

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $uid = $this->getLoginUID();
            $friends = call_soap('social', 'Social', 'getAllFriendsWithInfo', array('uid' => $uid));
            if (!empty($friends)) {
                $res['status'] = 1;
                foreach ($friends as $key => $friend) {
                    $friends[$key]['face'] = get_avatar($friend['id'], 'ss');
                }
                $res['data'] = $friends;
            }
        }
        die(json_encode($res));
    }

}

/* End of file index.php */
/* Location: ./application/controllers/index.php */
