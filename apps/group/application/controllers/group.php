<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/*
 * 群组
 * title :
 * Created on 2012-07-04
 * @author yaohaiqi
 * discription : 群组页面展示控制，无需做群组权限检查的页面
 */
class Group extends MY_Controller
{
	public function __construct(){
		parent::__construct();
		$this->load->model('groupmodel', 'group');
		$this->load->model('membermodel', 'member');
		$this->load->helper('common');
	}
	
	/**
	 * 我的群首页	  
	 */
	public function index()
	{
		//$this->load->library('xhprof');
		//$this->xhprof->open();
		$groups = $this->group->getAllGroups($this->uid, GroupConst::GROUP_TYPE_CUSTOM, true, false);
		$data = array(
			'groups'  => $groups,
		);
		$this->view('index', $data);
		//echo $this->xhprof->close('log',true);
	}
	
	/**
	 * 关系拓展：好友关系，目前这个有问题
	 */
	public function friend(){
//		$this->load->model('userModel', 'account');
//		$fuids = $this->account->getAllFriends($this->uid);
//		if(!empty($fuids) && count($fuids) >= 1){
//			$gid = $this->group->create($this->uid,"好友群",$fuids,GroupConst::GROUP_TYPE_FRIEND);
//		}else{
//			$this->showMessage('好友数量不能少于一个', ErrorCode::CODE_RELATION_MIN);
//		}
//		
//		$this->redirect('group/index/detail', array('gid' => $gid));
	}
	
	/**
	 * 关系拓展：同学关系
	 */
	public function classmate(){
		$this->load->model('userModel', 'account');
		$schools = $this->account->getClassmate($this->uid);
		$count = 0;
		foreach($schools as $v) {
			$count += count($v);
		}
		if($count == 0) {
			$this->showMessage('同学数量不能少于一个，请先到 <a href="'.mk_url('user/userwiki/index', array('dkcode'=>$this->dkcode)).'">用户资料</a> 处填入相关资料！', ErrorCode::CODE_RELATION_MIN, array(), mk_url('user/userwiki/index', array('dkcode'=>$this->dkcode)));
		}
		$this->view('classmate', array('schools' => $schools, 'count' => $count));
	}

	/**
	 * 关系拓展：同事关系
	 */
	public function workmate(){
		$this->load->model('userModel', 'account');
		$workmate = $this->account->getWorkmate($this->uid);
		if(!empty($workmate) && count($workmate['workmate']) >= 1){
			$group = $this->group->getGroupExistByFriends($workmate['company'], $this->uid, GroupConst::GROUP_TYPE_COLLEAGUE);
			if(!empty($group)){
				$gid = $group['gid'];
				$this->group->addMember($gid, $workmate['workmate'], $this->uid);
			}else{
				$gid = $this->group->create($this->uid,$workmate['company'],$workmate['workmate'],GroupConst::GROUP_TYPE_COLLEAGUE);
			}
		}else{
			$this->showMessage('同事数量不能少于一个，请先到 <a href="'.mk_url('user/userwiki/index', array('dkcode'=>$this->dkcode)).'">用户资料</a> 处填入相关资料！', ErrorCode::CODE_RELATION_MIN, array(), mk_url('user/userwiki/index', array('dkcode'=>$this->dkcode)));
		}
		
		$this->redirect('group/index/detail', array('gid' => $gid));
	}
	
	/**
	 * 关系拓展：同行关系
	 */
	public function peer(){
		$this->load->model('userModel', 'account');
		$peer = $this->account->getPeer($this->uid);
		if( !empty($peer) && count($peer['peermate']) >= 1){
			$group = $this->group->getGroupExistByFriends($peer['department'], $this->uid, GroupConst::GROUP_TYPE_PEER);
			if(!empty($group)){
				$gid = $group['gid'];
				$this->group->addMember($gid, $peer['peermate'], $this->uid);
			}else{
				$gid = $this->group->create($this->uid, $peer['department'],$peer['peermate'],GroupConst::GROUP_TYPE_PEER);
			}
		}else{
			$this->showMessage('同行数量不能少于一个，请先到 <a href="'.mk_url('user/userwiki/index', array('dkcode'=>$this->dkcode)).'">用户资料</a> 处填入相关资料！', ErrorCode::CODE_RELATION_MIN, array(), mk_url('user/userwiki/index', array('dkcode'=>$this->dkcode)));
		}
		
		$this->redirect('group/index/detail', array('gid' => $gid));
	}
    
    /**
	 * 关系拓展：亲人关系
	 */
	public function relative(){
		$this->load->model('userModel', 'account');
		$relative = $this->account->getRelative($this->uid);
		$group = $this->group->getGroupExistByFriends('亲人群', $this->uid, GroupConst::GROUP_TYPE_RELATIVE);
		if(!empty($group)){
			$gid = $group['gid'];
			$this->group->addMember($gid, $group['creator'], $this->uid);
		}elseif(count($relative) >= 1){
			$gid = $this->group->create($this->uid,'亲人群',$relative,GroupConst::GROUP_TYPE_RELATIVE);
		}else{
			$this->showMessage('亲人数量不能少于一个，请先到 <a href="'.mk_url('user/userwiki/index', array('dkcode'=>$this->dkcode)).'">用户资料</a> 处填入相关资料！', ErrorCode::CODE_RELATION_MIN, array(), mk_url('user/userwiki/index', array('dkcode'=>$this->dkcode)));
		}
		
        $this->redirect('group/index/detail', array('gid' => $gid));
	}
	
	/**
	 * 关系拓展：粉丝群
	 */
	public function fans(){
		$webid = intval($this->input->get('webid'));
		$web = service('Interest')->get_web_info($webid);
		$web_list = service('Interest')->get_web_homonymy_name($web['name']);
		$uids = array();
		foreach($web_list as $w) {
			$fans = service('WebpageRelation')->getAllFollowers($w['aid']);
			$uids = array_merge($uids, $fans);
		}
		$uids = array_unique($uids);
		if($this->uid == $web['uid'])
			$uids[] = $this->uid;
		if(!in_array($this->uid, $uids)){
			$this->showMessage('您不是该粉丝群成员，请先关注', ErrorCode::CODE_GROUP_MEMEBE_NOT_EXIST, array(), mk_url('webmain/index/main', array('web_id'=>$webid)));
		}elseif(count($uids) >= 1){
			$name = $web['name'].'粉丝群';
			$group = $this->group->getUniqueByName($name,GroupConst::GROUP_TYPE_FANS);
			if(empty($group)){
				$gid = $this->group->create($this->uid,$name,$uids,GroupConst::GROUP_TYPE_FANS);
			}else{
				$gid = $group['gid'];
				$this->group->addMember($gid, $group['creator'], $this->uid);
			}
		}else{
			$this->showMessage('粉丝数量不能少于一个', ErrorCode::CODE_RELATION_MIN, array(), mk_url('webmain/index/main', array('web_id'=>$webid)));
		}
		$this->redirect('group/index/detail', array('gid' => $gid));
	}
	
	/**
	 * 群创建
	 * author: hexin
	 */
	public function add()
	{
		$uids = $this->input->post('uid', true);
		$type = $this->input->post('source_type');
		$type = empty($type)? 'CUSTOM' : $type;
		$name = trim($this->input->post('name', true));
        $group_num = $this->group->group_config_num($this->uid);
        if($group_num['is_exceed']){
            if(empty($uids) || empty($type) || empty($name))
            $this->showMessage('至少添加一个好友!', ErrorCode::CODE_INVALID_POST);
            if(!is_array($uids)) $uids = array(intval($uids));
            $group_member_num = $this->group->group_member_config_num($this->uid);
            if(count($uids) <= $group_member_num['limit_num']){
                $gid = $this->group->create($this->uid, $name, $uids, $type, $this->input->post('description', true), $this->input->post('icon', true), $this->input->post('type', true));

                $this->showMessage(
                    'Success!',
                    ErrorCode::CODE_SUCCESS,
                    array('gid' => $gid),
                    mk_url('group/index/detail', array('gid' => $gid))
                );
            }else{
                //die(json_encode(array('state' => '1' ,'msg' => "success!",'num' => $users['count'],'list' =>$users['list'],'last'=>$users['last'])));
                $this->showMessage('您添加的群成员已经超过了上限!(最多成员：'.$group_member_num['limit_num'].'个成员)', ErrorCode::CODE_GROUP_NUM_EXCEED_THE_LIMIT);
            }
        }else{
            $this->showMessage('您添加的群已经超过了上限!(最多可以创建：'.$group_num['limit_num'].'个群)', ErrorCode::CODE_GROUP_NUM_EXCEED_THE_LIMIT);
            //die(json_encode(array('state' => '1' ,'msg' => "success!",'num' => $users['count'],'list' =>$users['list'],'last'=>$users['last'])));
        }
	}
	
	/**
	 * 创建营销群，群主创建，群成员接到邀请，并确认入群
	 * author: hexin
	 */
	public function addCustom()
	{
		$uids = $this->input->post('uid', true);
		$type = $this->input->post('source_type');
		$type = empty($type)? 'CUSTOM' : $type;
		$name = trim($this->input->post('name', true));

		if(empty($uids) || empty($type) || empty($name))
		$this->showMessage('至少添加一个好友!', ErrorCode::CODE_INVALID_POST);
		if(!is_array($uids)) $uids = array(intval($uids));

		$gid = $this->group->create($this->uid, $name, array(), $type, $this->input->post('description', true), $this->input->post('icon', true), $this->input->post('type', true));
		$this->member->invite($gid, $this->uid, $uids);
		
		$this->showMessage(
			'Success!',
			ErrorCode::CODE_SUCCESS,
			array('gid' => $gid),
			mk_url('group/index/detail', array('gid' => $gid))
		);
	}
	
	/**
	 * 申请加入
	 * author: hexin
	 */
	public function join()
	{
        $gid = intval($this->input->post('gid'));
		$uids = $this->input->post('uid', true);
		if(empty($gid) || empty($uids))
		$this->showMessage('Invalid post!', ErrorCode::CODE_INVALID_POST);
		if(!is_array($uids))
		$uid = array(intval($uids));
		$group = $this->group->getGroup($gid);
		if(empty($group))
		{
			$this->showMessage('Group is not exist!', ErrorCode::CODE_GROUP_NOT_EXIST);
		}
        $uids_exist = $this->group->invite($gid, $group['creator'], $uids);
        if(!empty($uids_exist))
        {
            $this->showMessage(
                "These users '".implode(',', $uids_exist)."' are exist.",
            ErrorCode::CODE_GROUP_MEMBER_EXIST,
            $uids_exist
            );
        }

		$this->showMessage(
			'Success!',
			ErrorCode::CODE_SUCCESS,
			array('gid' => $gid),
			mk_url('group/index/detail', array('gid' => $gid))
		);
	}
	
	/**
	 * 邀请确认页
	 */
	public function confirm()
	{
		$invites = $this->group->invitedGroups($this->uid, 0);
		$this->view('confirm', array('invites' => $invites, 'last' => count($invites) == GroupConst::GROUP_PAGESIZE ? false : true));
	}
	
	public function confirmPage()
	{
		$lastId = intval($this->input->get_post('lastid'));
		$invites = $this->group->invitedGroups($this->uid, $lastId);

		if(!empty($invites)){
			$this->assign('invites', $invites);
			$html = $this->fetch('group/confirm_list');
			$invite = end($invites);
			$lastId = $invite['id'];
			$last = count($invites) == GroupConst::GROUP_PAGESIZE ? false : true;
			$list = $this->group->invitedGroups($this->uid, $lastId);
			if(empty($list)) $last = true;
			else $last = false;
		}else{
			$html = '';
			$lastId = 0;
			$last = true;
		}
		$this->showMessage(
			"success",
			ErrorCode::CODE_SUCCESS,
			array('html' => $html, 'last' => $last)
		);
	}
	
	/**
	 * 接受邀请
	 */
	public function inviteAccept()
	{
		$gid = intval($this->input->post('gid'));
		$id = intval($this->input->post('id'));
		$invite = $this->member->getInvite($gid, $this->uid);
		if(!empty($invite)){
			$this->showMessage(
				"您已是该群成员.",
				ErrorCode::CODE_INVITED_PROCESSED
			);
		}else{
			$this->member->inviteConfirm($id, $this->uid, GroupConst::GROUP_INVITE_ACCEPT);
			$this->showMessage(
				"您已同意本次邀请.",
				ErrorCode::CODE_SUCCESS
			);
		}
	}
	
	/**
	 * 拒绝邀请
	 */
	public function inviteRefuse()
	{
		$gid = intval($this->input->post('gid'));
		$id = intval($this->input->post('id'));
		$invite = $this->member->getInvite($gid, $this->uid);
		if(!empty($invite)){
			$this->showMessage(
				"您已是该群成员.",
				ErrorCode::CODE_INVITED_PROCESSED
			);
		}else{
			$this->member->inviteConfirm($id, $this->uid, GroupConst::GROUP_INVITE_REFUSE);
			$this->showMessage(
				"您已拒绝本次邀请.",
				ErrorCode::CODE_SUCCESS
			);
		}
	}
	
	/**
	 * 创建自定义群步骤中判断群是否存在
	 */
	public function exist(){
		$name = htmlspecialchars(urldecode(trim($this->input->get_post('name'))));
		$gid = $this->group->getGroupByName($this->uid, $name);
		if($gid > 0) {
			$this->redirect('group/index/detail', array('gid'=>$gid));
		} else {
			$this->redirect('group/group/friendList', array('name'=>urlencode($name)));
		}
	}
	
	public function friendList(){
		$name = htmlspecialchars(urldecode(trim($this->input->get_post('name'))));
		$this->load->model('userModel', 'account');
		$users = $this->account->getFriendsByPage($this->uid);
		$this->view('friendList', array('group_name' => $name, 'users' => $users['list'], 'count' => $users['count']));
	}
	
/**
 * 以下代码都是为了相应ajax call，建议单独写在api内，可以用rest方式
 */
	
	/**
	 * 获取自己好友友列表，可分页
	 */
	public function getFriendList()
	{
		$page = intval($this->input->post('pager'));
		$page = $page > 0 ? $page : 1;
		$this->load->model('userModel', 'account');
		$users = $this->account->getFriendsByPage($this->uid, $page);
		die(json_encode(array('state' => '1' ,'msg' => "success!",'num' => $users['count'],'list' =>$users['list'],'last'=>$users['last'])));
	}
	
	/**
	 * 根据名称搜索自己好友列表，可分页
	 */
	public function searchFriendList()
	{
		$page = intval($this->input->post('pager'));
		$page = $page > 0 ? $page : 1;
		$keyword = trim($this->input->post('keyword'));
		$this->load->model('userModel', 'account');
		$users = $this->account->searchFriendsByPage($this->uid, $keyword, $page);
		die(json_encode(array('state' => '1' ,'msg' => "success!",'num' => $users['count'],'list' =>$users['list'],'last'=>$users['last'])));
	}
	
	/**
	 * 获取自己好友不在当前群内的好友列表，可分页
	 */
	public function getFriend(){
		//获得页数
		$gid = intval($this->input->post('gid'));
		$page = intval($this->input->post('pager'));
		$page = $page > 0 ? $page : 1;
		$this->load->model('userModel', 'account');
		$friend = $this->account->getFriendByGroup($gid, $this->uid, $page);

		die(json_encode(array('state' => '1' ,'msg' => "success!",'num' => $friend['NumOfFriends'],'last' =>$friend['last'],'list' =>$friend['list'])));
	}
	
	/**
	 * 根据名称搜索自己好友不在当前群内的好友列表，可分页
	 */
	public function searchFriend(){
		//获得页数
		$gid = intval($this->input->post('gid'));
		$page = intval($this->input->post('pager'));
		$page = $page > 0 ? $page : 1;
		$keyword = trim($this->input->post('keyword'));
		$this->load->model('userModel', 'account');
		$friend = $this->account->searchFriendByGroup($gid, $this->uid, $keyword, $page);
		die(json_encode(array('state' => '1' ,'msg' => "success!",'last' =>$friend['last'],'list' =>$friend['list'])));
	}

	public function getFollower(){
		//获得页数
		$page = intval($this->input->post('pager'));
		$page = $page > 0 ? $page : 1;
		$this->load->model('userModel', 'account');
		$follower = $this->account->getFollowerByGroup($this->action_uid,$page);
		die(json_encode(array('state' => '1' ,'msg' => "success!",'num' => $follower['NumOfFollowers'],'last' =>$follower['last'],'list' =>$follower['list'])));
	}

	public function searchFollower(){
		//获得页数
		$page = intval($this->input->post('pager'));
		$page = $page > 0 ? $page : 1;
		$keyword = $this->input->post('keyword');
		$this->load->model('userModel', 'account');
		$follower = $this->account->searchFollowerByGroup($this->action_uid,$keyword,$page);
		die(json_encode(array('state' => '1' ,'msg' => "success!",'last' =>$follower['last'],'list' =>$follower['list'])));
	}

	public function getFollowing(){
		//获得页数
		$page = intval($this->input->post('pager'));
		$page = $page > 0 ? $page : 1;
		$this->load->model('userModel', 'account');
		$following = $this->account->getFollowingByGroup($this->action_uid,$page);
		die(json_encode(array('state' => '1' ,'msg' => "success!",'num' => $following['NumOfFollowings'],'last' =>$following['last'],'list' =>$following['list'])));
	}

	public function searchFollowing(){
		//获得页数
		$page = intval($this->input->post('pager'));
		$page = $page > 0 ? $page : 1;
		$keyword = $this->input->post('keyword');
		$this->load->model('userModel', 'account');
		$following = $this->account->searchFollowingByGroup($this->action_uid,$keyword,$page);
		die(json_encode(array('state' => '1' ,'msg' => "success!",'last' =>$following['last'],'list' =>$following['list'])));
	}
    
    function test(){
        $gid = $this->group->group_member_config_num(103568136815049);
        var_dump($gid);
    }
}