<?php
/*
 * 留言控制器  
 * @author zhoutianliang
 */

class Leavemsg extends DK_Controller {
	private static $TYPE = 'leave';
	private static $PAGE = 10;
	public function __construct() {
		parent::__construct();
		
	}
	public function main() {
// 		if(!$this->isPermissions(12,$this->uid,$this->action_uid ? $this->action_uid : $this->uid)) {
// 			//return ;
// 		}
		if($this->action_uid) {
            $userinfo        = array(
					          'uid'=>$this->uid,
					          'username'=>$this->username,
            		          'action_username' => $this->action_user['username'],
            		          'action_uid'      => $this->action_uid,
							  'avatar' => get_avatar($this->action_uid, 's'),
							  'url' => mk_url('main/index/main', array('dkcode' => $this->action_dkcode)),
            		          'uavatar' => get_avatar($this->uid, 's'),
			             ); 
		}else {

			$userinfo = array(
					          'uid'=>$this->uid,
					          'username'=>$this->username,
					          'action_username' => $this->username,
					          'action_uid'      =>$this->uid,
							  'avatar' => get_avatar($this->uid, 's'),
							  'url' => mk_url('main/index/main', array('dkcode' => $this->dkcode)),
					          'uavatar' => get_avatar($this->uid, 's'),
			             );

		} //获取用户信息
		$contactpeople = $this->getRecentlyContact();
		$this->assign('contactpeople',$contactpeople);
		$this->assign('userinfo',$userinfo);
		$this->display('leavemsg/index.html');
	}
	/**
	 * 添加留言  添加回复 
	 */
	public function add_leave() {
		if($this->isAjax()) {
			$data=array();
			$error = '';
			if(isset($_POST['action_uid']) && $_POST['action_uid'] ) {
				$data['src_uid'] = $_POST['action_uid'];
				$data['object_id'] = $this->dkcode;
			}else {
				$error = '发表失败,参数不正确';
			}
			$data['object_type'] = self::$TYPE;
			$data['uid'] = $this->uid;
		    $data['username'] = $this->username;
			$data['usr_ip']   = get_client_ip();
			$data['dateline'] = time();
			if(isset($_POST['fid']) && $_POST['fid']) {
				$data['fid']  = $_POST['fid'];
			}
			if(isset($_POST['content'])) {
				$data['content'] = htmlspecialchars($_POST['content']);
				$this->load->helper('main');
				$data['content'] = faceReplace($data['content']);
			}else {
				$error = '发表失败,参数不正确'; 
			}
			if($error || !empty($error)) {
				die(json_encode(array('msg'=>$error,'status'=>0)));
			}else {
				//-----------------------------关注操作时间接口start 李波2012/ 7/3----------------
				//call_soap( 'social','Social','updateFollowTime',array($this->uid,$_POST['action_uid']) );
				service('Relation')->updateFollowTime($this->uid,$_POST['action_uid']);
				//-------------------------------------关注操作时间接口end------------------------
				
				// 留言 send notice
				if($this->uid != $_POST['action_uid'] && !isset($_POST['fid'])) {   
				    service('Notice')->add_notice('1',$this->uid,(int)$_POST['action_uid'],'dk','dk_leavecomment',
						array('name'=>$data['content'],'url'=>mk_url("main/leavemsg/main")));
				}
				if($this->uid != $_POST['action_uid'] && isset($_POST['fid'])) {
					service('Notice')->add_notice('1',$this->uid,(int)$_POST['action_uid'],'dk','dk_leave_reply',
							array('name'=>$data['content'],'url'=>mk_url("main/leavemsg/main",array('dkcode'=>$this->dkcode))));
				}
				//end send notice
				$this->load->model('leavemsgmodel');
				$result = $this->leavemsgmodel->insert($data,true);
				if($result) {
					$datas = $this->leavemsgmodel->read(array('id'=>$result),true);
					$datas['dateline'] = friendlyDate($datas['dateline']);
					$relation = $this->isMutualFollowing($this->uid,$datas['uid']);
					$datas['sendmsg'] = ($relation==6 || $relation==10) ? true :false;
					die(json_encode(array('msg'=>'留言成功','status'=>1,'id'=>$result,'data'=>$datas)));
				}else {
					die(json_encode(array('msg'=>'留言失败','status'=>0)));
				}
			}
		}else {
			die(json_encode(array('msg'=>'操作失败','status'=>0)));
		}
    }
	/* public function reply_leave() {
		
	} */
    /**
     *删除留言 
     */
	public function del_leave() {
		if($this->isAjax()) {
			$error = '';
			$where = array();
			if(isset($_POST['uid']) && $_POST['uid']) {
				if($this->uid!=$_POST['uid']) {
					die(json_encode(array('msg'=>'删除失败','status'=>0)));
				}
			}else {
				die(json_encode(array('msg'=>'删除失败','status'=>0)));
			}
			if(isset($_POST['id'])&&$_POST['id']){
				$where['id'] = $_POST['id'];
				$where['object_type'] = self::$TYPE;
			}else {
				$error = '删除失败';
			}
			if($error || !empty($error)) {
				die(json_encode(array('msg'=>$error,'status'=>0)));
			} else {
				$this->load->model('leavemsgmodel');
				$result = $this->leavemsgmodel->del($where);
				if($result) {
					die(json_encode(array('msg'=>'删除成功','status'=>1)));
				}else {
					die(json_encode(array('msg'=>'删除失败','status'=>0)));
				}
			}
		}else {
			die(json_encode(array('msg'=>'操作失败','status'=>0)));
		}
		
	}
	/**
	 *读取留言
	 */
	public function read_leave() {
		if($this->isAjax()) {
			$where = array();
			//过滤不必要的字段
			$filter_field = array('object_type','usr_ip','is_private','is_delete','notes','fid');
            
			if(isset($_POST['action_uid']) && $_POST['action_uid']) {
				$where['src_uid'] = $_POST['action_uid'];
				$where['object_type'] = self::$TYPE;
				$where['fid'] = 0;
			}
			if(isset($_POST['page']) && $_POST['page']) {
				$limit = abs(intval($_POST['page']));
			}else {
				$limit = 1;
			}
			if(empty($where)) {
				die(json_encode(array('msg'=>'操作失败','status'=>0)));
			}
			$this->load->model('leavemsgmodel');
			$count  = $this->leavemsgmodel->returnCount($where);
			$result = $this->leavemsgmodel->read($where,false,self::$PAGE,($limit * self::$PAGE) - self::$PAGE);
			if(empty($result)) {
				die(json_encode(array('msg'=>'没有留言','status'=>1)));
			}else {
				$tmp = array();
				foreach ($result as $key=>$one) {
					$tmp = $this->leavemsgmodel->read(array('fid'=>$one['id']));
					if(!empty($tmp)) {
						foreach ($tmp as $k=>$v) {
							foreach ($filter_field as $f=>$fv) {
								if(isset($tmp[$k][$fv])) {
									unset($tmp[$k][$fv]);
								} //过滤不要的字段
							}
							$tmp[$k]['dateline'] = friendlyDate($tmp[$k]['dateline']);
							$tmp[$k]['headpic'] = get_avatar($v['uid'],'m');
						}
					}
                    foreach ($filter_field as $f=>$fv) {
                    	if(isset($result[$key][$fv])) {
                    		unset($result[$key][$fv]);
                    	} //过滤不要的字段
                    }
                    $result[$key]['dateline'] = friendlyDate($result[$key]['dateline']);
				    $result[$key]['reply'] = $tmp;
				    $result[$key]['headpic'] = get_avatar($one['uid'],'m');
				    $relation = $this->isMutualFollowing($this->uid,$one['uid']);
				    $result[$key]['sendmsg'] = ($relation==6 || $relation==10) ? true :false;
				}
				$page = $limit * self::$PAGE < $count  ? true :false; 
				die(json_encode(array('data'=>$result,'status'=>1,'page'=>$page)));
			}
		}else {
			die(json_encode(array('msg'=>'操作失败','status'=>0)));
		}
	}
	//最近联系
	private  function getRecentlyContact() {
		$this->load->model('leavemsgmodel');
		
		if($this->action_dkcode) {
		
		    $result =  $this->leavemsgmodel->recentlyContact('uid,username,object_id',self::$PAGE ,array('uid !='=>$this->action_uid,'src_uid'=>$this->action_uid,'object_type'=>self::$TYPE));
		
		}else {
		    $result =  $this->leavemsgmodel->recentlyContact('uid,username,object_id',self::$PAGE ,array('src_uid'=>$this->uid,'uid !='=>$this->uid,'object_type'=>self::$TYPE));

		}
		$uid = $this->action_uid ? $this->action_uid : $this->uid;
		if($result) {
			foreach($result as $key=>$one) {
				$result[$key]['headpic'] = get_avatar($one['uid'],'m');
				$result[$key]['url']     = mk_url('main/index/main', array('dkcode' => $one['object_id']));
				$result[$key]['friends'] = count($this->commonFriends($uid,$one['uid']));
			}
		}
		return $result;
	}
	private function filter_field() {
		
	} 
	/**
	 * 获取访问该页面的用户与被访问者的共同好友
	 * @param int $uid 被 访问者UID
	 * @param int $authorid 作者UID
	 */
	private function commonFriends($uid,$action_uid) {
		return service('Relation')->getCommonFriends($uid,$action_uid);
	}
	private function isMutualFollowing($uid,$action_uid) {
		if($uid==$action_uid) return false;
		return service('Relation')->getRelationStatus($uid,$action_uid);
	}
	private function send_inform () {
		
	}
	
	
}