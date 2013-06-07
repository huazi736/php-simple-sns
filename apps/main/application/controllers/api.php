<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * 关系控制器
 *
 * 关注个人/取消关注 关注网页/取消关注 加好友/删除好友
 *
 * @author zengmm
 * @date 2012/7/25
 *
 * @history <lanyanguang><2012/3/19>
 */
class Api extends DK_Controller {

    /**
     * 常量定义
     */

    // 已关注的关系状态值(暂时只用于关注网页)
    const FOLLOWED = 4;
    
    /**
     * 目标用户UID
     * 
     * @var int
     */
    private $_fid = 0;

    /**
     * 目标用户名
     *
     * @var string
     */
    private $_fname = '';

    /**
     * 目标用户的端口号
     *
     * @var int
     */
    private $_fcode = 0;

    /**
     * 网页ID
     *
     * @var int
     */
    private $_webid = 0;

    /**
     * 网页信息
     *
     * @var array
     */
    private $_webinfo = array();


    /**
     * 构造方法
     */
    public function __construct() {

        parent::__construct();

        $this->load->model('apimodel');

        $this->load->model('immodel');
    }

    /**
     * 重写框架提供的ajax返回方法
     *
     * @author zengmm
     * @date 2012/7/25
     */
    private function _ajaxReturn($status = 1, $msg = 'success', $data = NULL) {

        if (empty($data)) { $data = ''; }

        $this->ajaxReturn($data, $msg, $status);        
    }

    /**
     * 初始化目标用户
     *
     * @author zengmm
     * @date 2012/7/25
     */
    private function _initTargetUser()
    {        
        //获取关注目标用户的uid
        $this->_fid = (int) $this->input->get_post('f_uid');

        //检查关注目标用户的uid合法性
        $userinfo = $this->apimodel->getUserInfo($this->_fid);
        if (empty($userinfo)) {
            $relation = $this->apimodel->getRelationStatus($this->uid, $this->_fid);
            $this->_ajaxReturn(0, '用户ID不合法!');
        }

        $this->_fname = $userinfo['username'];

        $this->_fcode = $userinfo['dkcode'];
    }

    /**
     * 初始化目标网页
     *
     * @author zengmm
     * @date 2012/7/25
     */
    private function _initTargetWebpage()
    {
        // 获得webid ($this->web_id继承自DK_Controller)
        $this->_webid = $this->web_id ? $this->web_id : intval($this->input->get_post('web_id'));
        
        // 网页ID不存在
        if (!$this->_webid) {
            $this->_ajaxReturn(0, '网页ID不存在!');
        }
        
        // 检查关注目标网页的合法性
        $this->_webinfo = $this->apimodel->getWebInfo($this->_webid);
        if (empty($this->_webinfo)) {
            $this->_ajaxReturn(0, '网页ID不合法!');
        }

        // 不能关注自己创建的网页
        if ($this->_webinfo['uid'] == $this->uid) {
            $this->_ajaxReturn(0, '不能关注自己的网页!');
        }
    }

    /**
     * 关注个人
     *
     * @author zengmm
     * @date 2012/7/25
     *
     * @history <lanyanguang><2012/3/8>
     */
    public function addFollow() {

        // 初始化目标用户
        $this->_initTargetUser();
        
        // 添加用户关注
        $result = (int) $this->apimodel->follow($this->uid, $this->_fid);

        if ($result > 0) {

            // 关注成功

            // 添加关注操作产生的积分
            service('credit')->follow();
			
            // 更新可能认识的人数据
            $this->load->model('mayknowmodel');
            $this->mayknowmodel->updateIndex($this->uid, $this->_fid);
            
            // 更新搜索引擎索引
            service('RelationIndexSearch')->addAFansForOne($this->_fid);

			if ($result == 6) {

                // 相互关注更新WEBIM通讯录

                $currentUser = array(
                    'uid' => $this->uid,
                    'username' => $this->username
                );

                $targetUser = array(
                    'uid' => $this->_fid,
                    'username' => $this->_fname
                );

				$this->immodel->addImFollow(json_encode($currentUser), json_encode($targetUser));
			}
            
            // 发送通知
            $this->apimodel->sendNotice(1, $this->uid, $this->_fid, 'dk', 'dk_guanzhu');

            // 返回操作后的关系状态
            $this->_ajaxReturn(1, 'success', array('relation'=>$result));
            
        }else{

            // 加关注失败

			$result = abs($result);

			if ($result == 1) {
				$msg = '您的关注人数已达上限!';
			} else {
				$msg = '操作失败!';
			}

            // 返回操作后的关系状态
            $this->_ajaxReturn(0, $msg, array('relation'=>$result));
        }
    }
	
    /**
     * 取消关注个人
     *
     * @author zengmm
     * @date 2012/7/26
     *
     * @history <lanyanguang><2012/3/8>
     */
    public function unFollow() {

        // 初始化目标用户
        $this->_initTargetUser();

        // 取得操作前的关系状态值, 因为在好友邀请已发送的情况下, 需要做通知数减1.
        $relation = $this->apimodel->getRelationStatus($this->uid, $this->_fid);

        //获取建立关系的时间,删除后则无法获取(4表示获取成为粉丝的时间)
        $relationStart = service('Relation')->getStartTtimeOfUsers($this->uid, $this->_fid, 4);

        // 取消关注(删除分发信息已封装在接口中)
        $result = (int) $this->apimodel->unfollow($this->uid, $this->_fid);
        
        if ($result > 0) {            
            
            // 更新搜索引擎索引
            service('RelationIndexSearch')->removeAFansForOne($this->_fid);
            
            // 更新WEBIM通讯录
            $currentUser = array(
                'uid' => $this->uid,
                'username' => $this->username
            );

            $targetUser = array(
                'uid' => $this->_fid,
                'username' => $this->_fname
            );

            $this->immodel->delImFollow(json_encode($currentUser), json_encode($targetUser));

            // 从个人时间线移除上移除用户关注他人的记录
            if($relationStart) {
                api('Timeline')->removeMultiItem(array(
                    'uid' => $this->uid,
                    'type' => 'social',
                    'field'=>'follows',
                    //关注对象的ID
                    'index' => $this->_fid,
                    //关注时间
                    'ctime' => $relationStart,
                ));
            }
            
            //在好友邀请已发送的情况下，需要做通知数减1.
            if($relation == 8) {
                // 已发好友请求, 取消已关注的用户A, 用户A未读请求计数减1
                service('Notice')->setting($this->uid, 'editinvite');
            }

            $this->_ajaxReturn(1, 'success', array('relation'=>$result));

        } else {

            $this->_ajaxReturn(0, '操作失败!', array('relation'=>$result));
        }
    }

    /**
     * 加好友
     *
     * @author zengmm
     * @date 2012/7/31
     *
     * @history <lanyanguang><2012/3/21>
     */
    function addFriend() {

        // 初始化目标用户
        $this->_initTargetUser();

        //加为好友
        $result = (int) $this->apimodel->addFriend($this->uid, $this->_fid);
        
        if ($result > 0 ) {

            if($result == 8) {

                //发送好友请求统计 只是发送邀请
                service('Notice')->setting($this->uid, 'addinvite');

                // 发送通知
                $this->apimodel->sendNotice(1, $this->uid, $this->_fid, 'dk', 'dk_addfriend');
                
            } elseif ($result == 10) {

                // 目标用户已发送好友请求, 直接成为好友

                // 加好友成功, 奖励积分
                service('credit')->friend($this->uid, $this->_fid);

                
                // 当前用户的未读请求计数减1
                service('Notice')->setting($this->uid, 'editinvite');

                // 更新WEBIM通讯录
                $currentUser = array(
                    'uid' => $this->uid,
                    'username' => $this->username
                );

                $targetUser = array(
                    'uid' => $this->_fid,
                    'username' => $this->_fname
                );

                $this->immodel->addImFriend(json_encode($currentUser), json_encode($targetUser));

                //发送通知
                $this->apimodel->sendNotice(1, $this->uid, $this->_fid, 'dk', 'dk_confirmfriend');
                
            }

            $this->_ajaxReturn(1, 'success', array('relation' => $result));

        } else {

            $result = abs($result);

            if ($result == 4) {
                // 对方已取消关注
                $msg = '您和' . $this->_fname . '已取消互相关注，不能加为好友!';
            } else {
                $msg = '操作失败!';
            }

            $this->_ajaxReturn(0, $msg, array('relation' => $result));
        }
    }

    /**
     * 删除好友
     *
     * @author zengmm
     * @date 2012/7/31
     *
     * @history <lanyanguang><2012/3/8>
     */
    function delFriend() {

        // 初始化目标用户
        $this->_initTargetUser();

        //获取成为好友时间(1表示获取成为好友的时间)
        $relationStart = service('Relation')->getStartTtimeOfUsers($this->uid, $this->_fid, 1);

        //删除好友
        $result = (int) $this->apimodel->deleteFriend($this->uid, $this->_fid);

        if ($result > 0) {

            // 更新WEBIM的通讯录
            $currentUser = array(
                'uid' => $this->uid,
                'username' => $this->username
            );

            $targetUser = array(
                'uid' => $this->_fid,
                'username' => $this->_fname
            );

            $this->immodel->delImFriend(json_encode($currentUser), json_encode($targetUser));

            // 从个人时间线上删除加好友的记录

            if($relationStart) {

                // 删除当前用户时间线上的加好友记录
                api('Timeline')->removeMultiItem(array(
                    'uid' => $this->uid,
                    'type' => 'social',
                    'field'=>'friends',
                    //关注对象的ID
                    'index' => $this->_fid,
                    //关注时间
                    'ctime' => $relationStart,
                ));

                // 删除好友时间线上当前用户加好友的记录
                api('Timeline')->removeMultiItem(array(
                    'uid' => $this->_fid,
                    'type' => 'social',
                    'field'=>'friends',
                    //关注对象的ID
                    'index' => $this->uid,
                    //关注时间
                    'ctime' => $relationStart,
                ));
            }

            // 取消对用户的关注(删除好友的附带操作)
            $is_unfollowing = $this->input->get_post('unFollow');
            if (!empty($is_unfollowing)) {
                $this->unFollow();
            }

            $this->_ajaxReturn(1, 'success', array('relation' => $result));

        } else {

            $this->_ajaxReturn(0, '操作失败!', array('relation' =>  abs($result)));
        }
    }

    /**
     * 关注网页
     *
     * @author zengmm
     * @date 2012/7/26
     *
     * @history <lanyanguang><2012/6/28> & <boolee>
     */
    public function addWebFollow() {  
        
        // 初始化目标网页
    	$this->_initTargetWebpage();
        
        //检查是否已关注过目标用户
        $result =  $this->apimodel->isWebFollowing($this->uid, $this->_webid);
        if ($result) {
            $this->_ajaxReturn(0, '您已关注过该网页了');
        }

        // 当前时间初始化
        $time = time();
        
        // 网页时效
        $expiry_time=$this->input->get_post('expiry_time');
		$expiry_time=$expiry_time ? $expiry_time : config_item('default_follow_expiry_time');
		
        // 关注网页($result网页的粉丝数)
        $result = $this->apimodel->webFollow($this->uid, $this->_webid, $time, $expiry_time);

        if ($result > 0) {

        	// 添加成功

            // 获得积分
        	service('credit')->followWeb();

            // @deprecated
        	//发送存储时间线动态  boolee 2012/7/14
			// $data = array(
			// 	'uid' => $this->uid,
			// 	'uname' => $this->username,
			// 	'dkcode' => $this->dkcode,
			// 	'permission' => 4,
			// 	'from' => 1,
			// 	'type' => 'social',
			// 	'dateline' => $time,
			// 	'ctime' => $time,
			// 	'obj_name' => $this->_webinfo['name'],		//网页名字
			// 	'obj_code' => $this->_webinfo['aid'],		//网页id
			// 	'obj_uid'  => $this->_webinfo['uid'],		//网页用户uid
			// 	'union'    =>'web'
			// );
			// api('Timeline')->addTimeline($data);

            // @deprecated
            //更新可能认识的网页数据 addby lanyanguang 2012-05-10
            // $iid = $this->apimodel->getWebIid($this->web_id);
            // $this->load->model('mayknowmodel');
            // $this->mayknowmodel->updateWebIndex($iid, $this->uid, $this->web_id);
  
            // 关注的网页所属分类
            $this->apimodel->addAttention($this->uid, $this->_webid, $result, $time, $expiry_time);
            
            // 关注的网页索引更新
            $data = array(
                'uid' => $this->uid,
                'user_name' => $this->username,
                'user_dkcode' => $this->dkcode,
                'web_id' => $this->_webid,
                'following_time' => $time,
                'fans_count' => $result,
            );

            $this->apimodel->addAFansToWeb($data);
            
            // 发送通知
            $data = array(
                'name' => $this->_webinfo['name'],
                'url' => mk_url('webmain/index/main', array('web_id' => $this->_webid))
            );
            $this->apimodel->sendNotice($this->_webid, $this->uid, $this->_webinfo['uid'], 'web', 'dk_guanzhu_web', $data);

            $data = array(
                'relation' => self::FOLLOWED,
                'days' => ceil(config_item('default_follow_expiry_time')/86400), // 时效的剩余天数
                'type' => 'd'
            );
			
            $this->_ajaxReturn(1, 'success', $data);
            
        }else{

        	if (abs($result) == 1) {
				// 关注网页已达上限200
				$msg = '您的关注网页数已达上限!';
			} else {
				$msg = '操作失败!';
			}

            $this->_ajaxReturn(0, $msg);
        }
    }

    /**
     * 取消网页的关注
     *
     * @author zengmm
     * @date 2012/7/30
     *
     * @history <lanyanguang><2012/04/24>
     */
    public function unWebFollow() {
        
        // 初始化目标网页
        $this->_initTargetWebpage();

        // 获取网页所属的频道ID
        $channel_id = $this->_webinfo['imid'];

        // 网页部分信息流数据清除(需要在取消网页关注之前操作, 此处需要再次调整)
        service('WebTimeline')->delAttentionWeb($this->uid, $this->_webid, array($channel_id));

        //取消网页关注
        $result = $this->apimodel->unWebFollow($this->uid, $this->_webid);

        if ($result !== false) {

            // 删除用户对网页的关注记录(mysql数据库)
            $this->apimodel->delAttention($this->uid, $this->_webid, $result);
            
            // 关注网页索引更新
            $this->apimodel->deleteUserOfWeb($this->_webid, $this->uid);
            
            $this->_ajaxReturn(1, 'success', array('relation' => 2, 'days' => 0, 'type' => 'd'));

        } else {

            $this->_ajaxReturn(0, '操作失败!');
        }
    }

 	/**
     * 修改关注网页时间
     * 
     * @author	boolee
     * @date    2012/6/26
     *
     * @return  $expiry_time 关注时间 false 操作失败
     */
    function updateWebFollowTime() {

        // 初始化目标网页
        $this->_initTargetWebpage();

        // 时效天数
        $days = (int) $this->input->get_post('days');

        if(!$this->_webid || !$days) { $this->_ajaxReturn(0, '参数不完整!'); }
        
        if($days == -1){
            // 永久关注
        	$expiry_time = -1;
        }else{
        	$expiry_time = $days * 86400;
        }
        
        // 初始化当前时间
        $action_time = time();
        
    	//更改对保存redis的关注时间
    	$r1 = $this->apimodel->updateFollowTime($this->uid, $this->_webid, $action_time, $expiry_time);

    	//更改对保存interest下的mysql的关注时间
    	$r2 = $this->apimodel->updateAttentionTime($this->uid, $this->_webid, $action_time, $expiry_time);

    	if ($r1 && $r2){

            $data = array(
                'relation' => ($days == -1 ? 6 : 4), // 4表示已关注, 6表示永久关注
                'days' => ($days == -1 ? config_item('default_follow_expiry_time')/86400 : $days),
                'type' => 'd'
            );

            $this->_ajaxReturn(1, 'success', $data);

    	}else{
    		$this->_ajaxReturn(0, '参数不完整!');
    	}
    } 
}
/* End of file api.php */
/* Location: ./application/controllers/api.php */