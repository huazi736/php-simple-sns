<?php

class Index extends MY_Controller {

    /**
     * 构造函数
     */
    function __construct() {
        parent::__construct();
        $this->load->model('apimodel');
        $this->load->helper('webmain');
    }

    /**
     * 首页页面
     *
     * @author chenjiali
     * @date   2012/03/27
     * @access public
     * @param 
     */
    function main() {
        //lanyanguang web_id 不存在或者错误的404
        if (!$this->web_info[0]) {
            show_404($page = '', FALSE);
        }
        
    	//加关系的部分2012/7/14 boolee
    	if (!$this->is_self) {
            //获得用户与目标网页关系 modify by boolee
            $relation = $this->apimodel->cheackWebFollowing($this->uid, $this->web_id);
            $lasttime=($relation['action_time']+$relation['expiry_time'])-time();//剩余时间
            if(!isset($relation['expiry_time'])){
            	$ftime['follow']=2;//未关注
            }elseif($relation['expiry_time']==-1){
            	$ftime['follow']=6;//永久关注
            }elseif($lasttime>0){
            	$ftime['follow']=4;//关注有效
            }else{
            	$ftime['follow']=8;//关注过期
            }
            $ftime['dtype']	='d';
            //关注有效，值为自定义值，否则系统提供
            if($ftime['follow'] == 4){
            	$ftime['lastday']=ceil($lasttime/86400);//剩余天数
            }elseif($ftime['follow'] == 8){
            	$ftime['lastday']=ceil($relation['expiry_time']/86400);
            }else{
            	$ftime['lastday']=ceil(config_item('default_follow_expiry_time')/86400);//剩余天数	
            }
            $this->assign('ftime', $ftime);
        }
        //---------------------网页粉丝数，链接,boolee 2012/7/16 start--------------------------------------
            $web_fans['num']        = service('WebpageRelation')->getNumOfFollowers( $this->web_id );
            $web_fans['fanslisturl']= mk_url('webmain/follower/index', array('web_id'=>$this->web_id));
            $this->assign('web_fans', $web_fans);
        //---------------------网页粉丝数，链接end----------------------------------------------------------
        
        $this->assign('f_uid', $this->action_uid);
        $this->assign('web_info', $this->web_info);
        
        // 网页是否在删除中
       	$web_delete = service('Interest')->get_display_web_info($this->web_id);
       	$web_delete = json_decode($web_delete,true);
        $is_web_delete 	= (isset($web_delete[$this->web_id]) && $web_delete[$this->web_id] ) ? 1 : 0; // 1为删除，0为未删除
		$this->assign('is_web_delete',$is_web_delete);
        
        
        //zhoutianliang 07.14
             $this->assign('action_uid',$this->action_uid);  
        //end
        
        //显示网页资料区
        if($this->web_info['imid']) $main_categary = service("interest")->get_category_main($this->web_info['imid']);

        $create_time = date("Y年m月d日",strtotime($this->web_info['create_time']));
        $create_info = service("user")->getUserInfo($this->web_info['uid'], 'uid', array("dkcode","username"));
        
        //查询网页是否已经引用网页资料  add by lijianwei 2012-07-04 start
        /*
        $this->mdb = get_mongodb("default");
        $this->mdb->switch_db("wiki_duankou"); //切换到wiki_duankou
        $web_info = $this->mdb->where(array("web_id" => $this->web_id))->limit(1)->get("wiki_web_info");
        $is_have_wiki = false;
        if($web_info && isset($web_info[0]['item_id']) && $web_info[0]['item_id']) $is_have_wiki = true;
        $this->assign("is_have_wiki", $is_have_wiki);
        */
        //查询网页是否已经引用网页资料  add by lijianwei 2012-07-04 end
        
        $this->assign("wiki_url",mk_url("wiki/webwiki/index",array("web_id"=>$this->web_id)));
        $this->assign("category_name",isset($main_categary[0]['imname']) ? $main_categary[0]['imname'] : "无分类");
        $this->assign("create_time",$create_time);
        $this->assign("create_name",$create_info['username']);
        $this->assign("create_url",mk_url("main/index/main",array("dkcode"=>$create_info['dkcode'])));
        $this->assign("is_display",$this->web_info['is_info']);

        $this->assign("infoUrl", mk_url("wiki/webwiki/index", array("web_id" => $this->web_id)));
        $this->assign('serverUrl', $this->config->item('base_url'));
        $this->assign('user', $this->user);
        $this->assign('is_self', $this->is_self);
        $this->assign('webId', $this->web_id);
		
         $coverurl = get_cache('webcover_'.$this->web_id);
		if (!empty($coverurl)) {
			// $setting = getConfig('fastdfs','avatar');
			$cover = 'http://' . config_item('fastdfs_domain') . $coverurl;
			$this->assign('iscover', true);
			$this->assign('cover', $cover);
		} else {
			$res = api('Interest')->get_web_cover($this->web_id);
			set_cache('webcover_'.$this->web_id,$res[0]['webcover']);
			if (!empty($res[0]['webcover'])) {
				// $setting = getConfig('fastdfs','avatar');
				$cover = 'http://' . config_item('fastdfs_domain') . $res[0]['webcover'];
				$this->assign('iscover', true);
				$this->assign('cover', $cover);
			} else {
				$this->assign('iscover', false);
				$this->assign('cover', '');
			}
		}
		// if($this->is_self){
			// if ($this->web_info['webcover']) {
				// $setting = getConfig('fastdfs','avatar');
				// $cover = 'http://' . $setting['host'] . $this->web_info['webcover'];
				// $this->assign('iscover', true);
				// $this->assign('cover', $cover);
			// } else {
				// $this->assign('iscover', false);
				// $this->assign('cover', '');
			// }
		// }
		// else{
			// $res = api('Interest')->get_web_cover($this->web_id);
			// if (!empty($res[0]['webcover'])) {
				// $setting = getConfig('fastdfs','avatar');
				// $cover = 'http://' . $setting['host'] . $res[0]['webcover'];
				// $this->assign('iscover', true);
				// $this->assign('cover', $cover);
			// } else {
				// $this->assign('iscover', false);
				// $this->assign('cover', '');
			// }
		// }
        

        // 获取应用区列表
		$this->load->model('appmenumodel');
		$user_app_list = $this->appmenumodel->getAppMenu($this->web_id);
        
        // 获取网页应用ID
        $app_ids = $this->appmenumodel->getAppIds($this->web_id);
        
        // 加载网页发表框列表
        $this->load->model('publish_tplmodel');
        $web_tpl = $this->publish_tplmodel->getTplInfo($app_ids);
        
        //判断是否为当前用户
        if (is_array($user_app_list)) {
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
                    $user_app_list[$key]['menu_ico'] = 'http://' . config_item('fastdfs_domain') . DIRECTORY_SEPARATOR . $val['group'] . DIRECTORY_SEPARATOR . $val['menu_ico'];
                }
            	if(substr_count($val['menu_url'], '/') < 2){
					$user_app_list[$key]['menu_url'] = 'webmain/index/main';
                }
                $user_app_list[$key]['menu_url'] = mk_url($user_app_list[$key]['menu_url'], array('web_id'=>$this->web_id));
                if (!empty($val['releation'])) {
                    foreach ($val['releation'] AS $k => $v) {
                        $user_app_list[$key]['cover'][$k]['name'] = $v['name'];
                        $user_app_list[$key]['cover'][$k]['ico'] = get_avatar($v['id'], 'ss');
                    }
                }
            }
        }

        // 登录者的用户信息
        $login_info['avatar_url'] = get_avatar($this->uid);
        $login_info['uid'] = $this->uid;
        $login_info['username'] = $this->user['username'];
        if ($this->is_self) {
//            $login_info['url'] = mk_url(APP_URL . '/index/index', array('web_id' => $this->web_id));
            $login_info['url'] = mk_url('webmain/index/main', array('web_id' => $this->web_id));
        } else {
            $login_info['url'] =  mk_url('main/index/main', array('dkcode'=>$this->dkcode)) ;
        }
        $this->assign('webAvatar', get_webavatar($this->web_id,'s'));

        // 时间线信息头像
        $action_avatar = ($this->is_self) ? $login_info['avatar_url'] : get_avatar($this->action_uid);


        //视频上传录制 wangying
		$this->config->load("video");
        $this->assign('videoname', date('YmdHis') . '_' . $this->uid);
        $authcode_url = authcode('video', '', config_item('authcode_key'));
        $this->assign('video_upload_url', config_item('video_upload_url'));
        $this->assign('authcode_url', base64_encode($authcode_url));
        $this->assign('recordurl', config_item('recordurl'));

        // 加载发表框模板
        foreach ($web_tpl as $key => $tpl) {        
            $filename = APPPATH . 'views/timeline/publish_templates/' . $tpl['sign'] . '.html';
            $content = '';
            if (file_exists($filename)) {
                $content = $this->view->fetch('timeline/publish_templates/' . $tpl['sign']);
            }
            $tpl['template'] = $content;
            $web_tpl[$key] = $tpl;
        }
        $this->assign('web_tpl', $web_tpl);
        
        $this->assign('fdfsinfo', config_item('fastdfs_domain'));
        $this->assign('user_app_list', $user_app_list);
        $this->assign('action_avatar', $action_avatar);
        $this->assign('login_info', $login_info);
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
            $friends = service('Relation')->getAllFriendsWithInfo($uid);
            if (!empty($friends)) {
                $res['status'] = 1;
                foreach ($friends as $key => $friend) {
                    $friends[$key]['face'] = get_avatar($friend['id'], 'ss');
                }
                $res['data'] = $friends;
            }
         $this->ajaxReturn($res['data'], '', $res['status']);
    }

}

/* End of file index.php */
/* Location: ./application/controllers/index.php */