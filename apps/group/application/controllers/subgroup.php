<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/*
 * 群组
 * title :
 * Created on 2012-07-04
 * @author yaohaiqi
 * discription : 子群页面展示控制
 */
class Subgroup extends MY_Controller
{
	/*
	 * 群组信息
	 */
	private $groupInfo = null;
	/*
	 * 子群信息
	 */
	private $subGroupInfo = null;
	/*
	 * 子群成员ID集合
	 */
	private $members = array();
	
	public function __construct(){
		parent::__construct();
		$this->load->model('groupmodel', 'group');
        $this->load->model('membermodel', 'member');
		$this->load->model('subgroupmodel', 'subgroup');
		$this->load->model('submembermodel', 'submember');
		$this->load->helper('common');
		$this->_checkPermission();
	}
	
	/**
	 * 群组权限检查
	 * author: hexin 2012-07-14
	 */
	private function _checkPermission(){
		$sid = intval($this->input->get_post('sid'));
		if($sid){
		$this->subGroupInfo = $this->subgroup->getGroup( $sid );
		$this->members = $this->submember->getAllMemberIdsByGroup($sid);
		$gid = $this->subGroupInfo['gid'];
		$this->groupInfo = $this->group->getGroup( $gid );
		$this->assign('group', $this->groupInfo);
		$this->assign('subgroup', $this->subGroupInfo);
		}
	}
	
	public function index()
	{
        //$sid子群id
        $sid = $this->subGroupInfo['sid'];
        //子群数量
        $temp = $this->submember->getNumOfGroupMember($sid);
        $num = $temp ? $temp['0']['num'] : $this->showMessage('没有该子群', ErrorCode::CODE_RELATION_MIN);
        $this->load->model('usermodel', 'account');
		$this->subGroupInfo['author'] = $this->account->getUserInfo($this->subGroupInfo['creator']);
        $user = $this->submember->getMemberByGroup($sid, $this->uid);
        //子群成员
        $member = $this->member->getAllMembersBySubGroup($sid,1);
        $data = array(
        	'nowuser'=> $user,
        	'sid'=>	$sid,
            'gid'=>$this->subGroupInfo['gid'],
			'name'=> $this->subGroupInfo['name'],
			'num'=> $num,
            'members'=> $member,
            'last'=> $num < 24 ? true : false,
        	'subgroup' => $this->subGroupInfo,
		);
        $this->view( 'index', $data );
	}

	/**
	 * 群子创建
	 */
	public function add()
	{
        //$uids成员uid、$gid群id、$name群名称、$icon群图标、$discription群简介
        $uids = $this->input->post('uid', true);
		$gid = intval($this->input->post('gid',true));
		$name = trim($this->input->post('name',true));
        $icon = trim($this->input->post('icon',true));
        if($icon){
            $icon = explode('misc',$icon);
            $icon = substr($icon['1'],1);
        }else{
            $icon = 'img/group/icon/subgroup/1.png';
        }
        $discription = trim($this->input->post('discription',true));
        $subgroup_num = $this->subgroup->subgroup_config_num($this->uid,$gid);
        if($subgroup_num['is_exceed']){
            //添加登陆者
            array_push($uids,$this->uid);
            $subgroup_member_num = $this->subgroup->subgroup_member_config_num($this->uid);
            if(count($uids) <= $subgroup_member_num['limit_num']){
                if(empty($uids) || empty($gid) || empty($name)||empty($this->uid)){
                    $this->showMessage('至少要有一个好友!', ErrorCode::CODE_INVALID_POST);
                }
                if(!is_array($uids)){
                    $uids = array(intval($uids));
                }
                //创建子群
                $sid = $this->subgroup->create($gid, $this->uid, $name, $uids, $discription, $icon);
                $this->showMessage(array('msg'=>'success'),ErrorCode::CODE_SUCCESS,array('sid' =>$sid));
            }else{
                $this->showMessage('超出子群成员限制(最多：'.$subgroup_member_num['limit_num'].'成员)!',ErrorCode::CODE_SUBGROUP_MEMBER_NUM_EXCEED_THE_LIMIT);
            }
        }else{
            $this->showMessage('超出可创建子群个数限制(最多：'.$subgroup_num['limit_num'].'子群)!',ErrorCode::CODE_SUBGROUP_NUM_EXCEED_THE_LIMIT);
        }
	}
    
   	/**
    * 群成员
    */
    public function groupallmember(){
        //$gid群id、$page页码
       	$gid = intval($this->input->post('gid',true));
        $page = $this->input->post('pager') ? intval($this->input->post('pager',true)) : 1;
        //群成员数量
        $num = $this->member->getNumOfGroupMember($gid);
        //群成员不包括自己
        $group = $this->member->getAllMembersExceptSelfByGroup($gid,$this->uid,$page);
        $last = $num['0']['num'] < $page * 25 ? true : false;
        $this->showMessage(array('msg'=>'success'),ErrorCode::CODE_SUCCESS,array('last' =>$last,'list' =>$group));
    }
    
    /**
    * 创建子群搜索
    */
    public function searchbygroup(){
        //$gid群id、$page页码
       	$gid = intval($this->input->post('gid',true));
        $page = $this->input->post('pager') ? intval($this->input->post('pager',true)) : 1;
        $keyword = trim($this->input->post('keyword',true));
        if($keyword != ''){
            //群成员数量
            $num = $this->submember->NumOfGroupMember($gid,$this->uid,$page,$keyword);
            //群成员不包括自己
            $group = $this->submember->searchMembersExceptSelfByGroup($gid,$this->uid,$page,$keyword,25);
            $last = $num < $page * 25 ? true : false;
        }else{
            //群成员数量
            $num = $this->member->getNumOfGroupMember($gid);
            //群成员不包括自己
            $group = $this->member->getAllMembersExceptSelfByGroup($gid,$this->uid,$page);
            $last = $num['0']['num'] < $page * 25 ? true : false;
        }
        $this->showMessage(array('msg'=>'success'),ErrorCode::CODE_SUCCESS,array('last' =>$last,'list' =>$group));
    }
    
    /**
    * 添加子群搜索
    */
    public function searchlastbygroup(){
        //$gid群id、$page页码
       	$gid = intval($this->input->post('gid',true));
        $sid = intval($this->input->post('sid',true));
        $page = $this->input->post('page') ? intval($this->input->post('page',true)) : 1;
        $keyword = trim($this->input->post('keyword',true));
        if($keyword != ''){
            //群成员数量
            $num = $this->submember->LastNumOfGroupMember($gid,$sid,$page,$keyword);
            //群成员不包括自己
            $group = $this->submember->searchDifferenceGroupAndSubGroup($gid,$sid,$page,$keyword);
            $last = $num < $page * 20 ? true : false;
        }else{
            //群成员数量
            $num = $this->member->getNumOfGroupMember($gid);
            //剩余群成员
            $group = $this->member->getDifferenceGroupAndSubGroup($gid,$sid,$page);
            $last = $num['0']['num'] < $page * 20 ? true : false;
        }
        $this->showMessage(array('msg'=>'success'),ErrorCode::CODE_SUCCESS,array('last' =>$last,'list' =>$group));
    }
        
        /**
         * 邀请时显示剩余群成员
         */
    function groupmember(){
        //$gid群id、$page页码、$sid子群sid
        $gid = intval($this->input->post('gid',true));
        $sid = intval($this->input->post('sid',true));
        $page = $this->input->post('page') ? intval($this->input->post('page',true)) : 1;
        //群成员数量
        $num = $this->member->getNumOfGroupMember($gid);
        //剩余群成员
        $group = $this->member->getDifferenceGroupAndSubGroup($gid,$sid,$page);
        $last = $num['0']['num'] < $page * 25 ? true : false;
        $this->showMessage(array('msg'=>'success'),ErrorCode::CODE_SUCCESS,array('last' =>$last,'list' =>$group));
    }

    /**
     * 子群成员（翻页）
     */
    function subgroupmember(){
        //$page页码、$sid子群id
        $sid = intval($this->input->post('sid',true));
        $page = $this->input->post('page') ? intval($this->input->post('page',true)) : 1;
        $subgroup = '';
        $user = $this->submember->getMemberByGroup($sid, $this->uid);
        //子群成员数量
        $num = $this->submember->getNumOfGroupMember($sid);
        //子群成员
        $temp = $this->member->getAllMembersBySubGroup($sid,$page);
        //print_r($temp);
        if($temp){
            foreach ($temp as $key => $value) {
                $subgroup .= '<li><div class="group_member_list"><a href="'.$value['href'].'" class="group_member_list_l"><img src="'.$value['avatar'].'"></a><div class="group_member_list_r group_kickout_son"><p><a href="'.$value['href'].'" class="group_username">'.$value['name'].'</a></p>';
                if($user['position'] == 1 && $value['uid'] != $user['uid']){
                    $subgroup .= '<a uid="'.$value['uid'].'" gid="'.$value['sid'].'"  href="javascript:void(0)" class="group_kickout">踢出此群</a>';
                }
                $subgroup .= '</div></div></li>';
            }
        }
        $last = $num['0']['num'] < $page * 24 ? true : false;
        $this->showMessage(array('msg'=>'success'),ErrorCode::CODE_SUCCESS,array('last' =>$last,'list' =>$subgroup));
    }

    /**
	 * 添加子群成员
	 * author: yaohaiqi
	 */
	public function join()
	{        
        //$sid子群、添加成员uid
        $sid = intval($this->input->post('sid',true));
		$uids = $this->input->post('uid');
        //判断数据是否为空
		if(empty($sid) || empty($uids)){
            $this->showMessage('Invalid post!', ErrorCode::CODE_INVALID_POST);
        }
        //判断是否为数组
		if(!is_array($uids)){
            $uids = array(intval($uids));
        }
        $subgroup_member_num = $this->subgroup->subgroup_member_config_num($sid);
        $temp = $subgroup_member_num['limit_num'] - $subgroup_member_num['now_num'];
        if(count($uids) <= $temp){
            //获取子群信息
            $group = $this->subgroup->getGroup($sid);
            if(empty($group)){
                $this->showMessage('Group is not exist!', ErrorCode::CODE_GROUP_NOT_EXIST);
            }
            //添加子群成员
            $this->member->addSubGroupMembers($sid, 1, array_unique($uids));
            $this->showMessage(array('msg'=>'success'),ErrorCode::CODE_SUCCESS,array('sid' =>$sid));
        }else{
            $this->showMessage('人员超出限制(最多：'.$subgroup_member_num['limit_num'].'人)!', ErrorCode::CODE_SUBGROUP_MEMBER_NUM_EXCEED_THE_LIMIT);
        }
	}
	
	/**
	 * 踢出用户操作
	 * @author Huifeng Yao
	 */
	public function remove()
	{
		/**
		 * @var int $subgid 子群组ID
		 * @var int $uid 	用户ID
		 */
		$sid = intval( $this->input->post( 'gid' ) );
		$uid = intval( $this->input->post( 'uid' ) );
		$this->isEmpty( $sid );
		$this->isEmpty( $uid );
		
		// 获取用户信息
		$user = $this->submember->getMemberByGroup( $sid, $uid );
		
		// 判断用户是否在该子群
		if ( empty( $user ) ) {
			$this->showMessage( '他/她不属于该子群', ErrorCode::CODE_GROUP_MEMEBE_NOT_EXIST );
		}
		
		// 判断被删除用户是否为管理员
		if ( $user['position'] == GroupConst::GROUP_ROLE_MASTER ) {
			$this->showMessage( '你不能踢出管理员', ErrorCode::CODE_GROUP_NO_PERMISSION );
		} else {
			// 删除群组和用户之间的关系
			$result = $this->subgroup->kickOut( $sid, $uid );
			
			// 获取群组信息
			$subgroup_info = $this->subgroup->getGroup( $sid );
			
			if ( $result ) {
				// 删除成功，发送消息给被删除的用户
				service( 'Notice' )->add_notice( '1', $uid, $uid, 'group', 'group_out_sub', array( 'name' => $subgroup_info['name'], 'url'=>'#' ) );
				
				$this->showMessage( null, ErrorCode::CODE_SUCCESS );
			} else {
				// 未知原因删除失败
				$this->showMessage( '未知原因踢出失败', ErrorCode::CODE_INVALID_POST );
			}
		}
	}
	
	
	/**
	 * 解散子群操作
	 * @author Huifeng Yao
	 */
	public function disband()
	{
		/**
		 * 前端统一使用gid作为传入参数
		 * @var int $subgid 子群组ID
		 */
		$sid = intval( $this->input->post( 'gid' ) );
	
		$this->isEmpty( $sid );
	
		// 获取所有子群成员信息
		$members = $this->submember->getAllMembersByGroups( $sid );
		
		// 获取子群信息
		$subgroup_info = $this->subgroup->getGroup( $sid );
		
		// 解散子群操作
		$result = $this->subgroup->disband( $sid );
	
		if ( $result ) {
			// 循环向子群成员发送群组解散的信息
			foreach ( $members as $member ) {
				service( 'Notice' )->add_notice( '1', $member['uid'], $member['uid'], 'group', 'group_dismiss_sub', array( 'name' => $subgroup_info['name'], 'url'=>'#' ) );
			}
			
			$this->showMessage( null, ErrorCode::CODE_SUCCESS );
		} else {
			// 未知原因删除失败
			$this->showMessage( '未知原因解散失败', ErrorCode::CODE_INVALID_POST );
		}
	}
	
	/**
	 * 退出群
	 * 
	 * @author Huifeng Yao
	 */
	public function quit()
	{
		/**
		 * 获取子群id并查询信息
		 * @var unknown_type
		 */
		$sid = intval($this->input->get_post('sid'));
		$this->isEmpty($sid);
		$subGroupInfo = $this->subgroup->getGroup( $sid );
		
		/**
		 * 获取当前用户在子群中的信息
		 *
		 * @var unknown_type
		 */
		$user = $this->submember->getMemberByGroup( $sid, $this->uid );
		
		if ( $user ['position'] == GroupConst::GROUP_ROLE_MASTER ) {
			$this->showMessage( '你是管理员，不能退出子群', ErrorCode::CODE_GROUP_NO_PERMISSION );
		}
		
		$this->subgroup->quit( $subGroupInfo ['sid'], $this->uid);
		
		$this->showMessage( 'Success!', ErrorCode::CODE_SUCCESS, null, mk_url( 'main/index/main' ) );
	}
	
	/**
	 * 验证输入参数是否为空
	 * @author Huifeng Yao
	 * @param unknown_type $param 验证参数
	 */
	private function isEmpty( $param )
	{
		if ( empty( $param ) ) {
			$this->showMessage( '无效的提交', ErrorCode::CODE_INVALID_POST );
		}
	}
}