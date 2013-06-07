<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/*
 * 群组
 * title :
 * Created on 2012-05-28
 * @author hexin
 * discription : 群组页面展示控制，需要最基本的群权限检查
 */
class Index extends MY_Controller
{
	/*
	 * 群组信息
	 */
	private $groupInfo = null;
	/*
	 * 群成员ID集合
	 */
	private $members = array();
	
	public function __construct(){
		parent::__construct();
		$this->load->model('groupmodel', 'group');
		$this->load->model('membermodel', 'member');
		$this->load->helper('common');
		$this->_checkPermission();
	}
	
	/**
	 * 群组权限检查
	 */
	private function _checkPermission(){
		$gid = intval( $this->input->get_post( 'gid' ) );
		$this->isEmpty($gid);
		$this->groupInfo = $this->group->getGroup( $gid );
		$this->members = $this->member->getAllMemberIdsByGroup($gid);
		
		if ( empty( $this->groupInfo ) ) {
			$this->showMessage( 'Group is not exist!', ErrorCode::CODE_GROUP_NOT_EXIST, array(), mk_url('main/index/main'));
//		} elseif ( $this->uid != $this->groupInfo['creator'] ) {
//			$this->showMessage( 'You are not the group master!', ErrorCode::CODE_GROUP_NO_PERMISSION , array(), mk_url('main/index/main'));
		} elseif ( !in_array($this->uid, $this->members) ) {
			$this->showMessage( 'You are not in the group!', ErrorCode::CODE_GROUP_NO_PERMISSION , array(), mk_url('main/index/main'));
		}
	}
	
	/**
	 * 验证输入参数是否为空
	 * @param max $param 验证参数
	 */
	private function isEmpty( $param )
	{
		if ( empty( $param ) ) {
			$this->showMessage( 'Invalid post!', ErrorCode::CODE_INVALID_POST );
		}
	}

	/**
	 * 我的群首页	  
	 */
	public function index()
	{
		//$this->load->library('xhprof');
		//$this->xhprof->open();
		$groups = $this->group->getAllGroups($this->uid, true);
		$data = array(
			'groups'  => isset($groups[GroupConst::GROUP_ROLE_MASTER]) ? $groups[GroupConst::GROUP_ROLE_MASTER] : array(),
			'mygroups'=> isset($groups[GroupConst::GROUP_ROLE_MEMBER]) ? $groups[GroupConst::GROUP_ROLE_MEMBER] : array(),
		);
		$this->view('index', $data);
		//echo $this->xhprof->close('log',true);
	}

	/**
	 * 群组展示页
	 */
	public function detail()
	{
//		xhprof_start();
		//$this->load->library('xhprof');
		//$this->xhprof->open();
		$gid = $this->groupInfo['gid'];
		$group_extend = $this->group->getGroupExtend($gid);
		/*修补数据*/
		if($group_extend['member_counts'] != count($this->members)) {
			$group_extend['member_counts'] = count($this->members);
			$this->group->updateExtend($gid, null, null, null, count($this->members));
		}
		$this->groupInfo = array_merge($group_extend, $this->groupInfo);
		$this->load->model('usermodel', 'account');
		$this->groupInfo['author'] = $this->account->getUserInfo($this->groupInfo['creator']);
		$this->load->model('appmodel', 'app');
		$apps = $this->app->getGroupApps($gid);
        $this->load->model('subgroupmodel', 'subgroup');
        $subgroup = $this->subgroup->getAllGroups($this->uid,$gid);
        /*更新最后访问时间*/
        $this->group->update($this->groupInfo['gid'], $this->groupInfo['name']);
        //wangying
        $this->config->load("video");
		$data = array(
			'gid'=>$gid,
			'authcode'=>sysAuthCode($this->uid),
			'group'=>$this->groupInfo,
			'user'=>$this->user,
			'apps' => $apps,
            'subgroup'=> $subgroup,
		);
		$this->viewDetail($data);
		//echo $this->xhprof->close('log',true);
//		xhprof_end();
	}
	
	/*
	 * 统一输出detail页面
	 */
	private function viewDetail($array)
	{
		$variable = array('group','user','users','name','center','login_uid','login_name','login_avatar','login_url','authcode','gid','apps','subgroup');
		$data = array();
		foreach($variable as $key) {
			if(isset($array[$key])) $data[$key] = $array[$key];
			else $data[$key] = '';
		}
		$this->view('detail', $data);
	}

	/**
	 * 邀请
	 */
	public function invite()
	{
		$uids = $this->input->post('uid', true);
        $gid = $this->input->post('gid', true);
		$this->isEmpty($uids);
		if(!is_array($uids)) $uids = array(intval($uids));
		$group_extend = $this->group->getGroupExtend($this->groupInfo['gid']);
		if( $group_extend['invitation'] == GroupConst::GROUP_OPERATE_MASTER && $this->uid != $this->groupInfo['creator'] )
		{
			$this->showMessage('You are not the group master!', ErrorCode::CODE_GROUP_NO_PERMISSION);
		}
        $group_member = $this->group->group_member_config_num($gid);
        $temp = $group_member['limit_num'] - $group_member['now_num'];
        if(count($uids) <= $temp){
            $uids_exist = $this->member->invite($this->groupInfo['gid'], $this->uid, $uids);
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
                array('gid' => $this->groupInfo['gid']),
                mk_url('group/index/detail', array('gid' => $this->groupInfo['gid']))
            );
        }else{
            $this->showMessage('超出群成员数量限制(最多：'.$group_member['limit_num'].'人,您现在最多可以添加'.$temp.'成员)!', ErrorCode::CODE_GROUP_MEMBER_NUM_EXCEED_THE_LIMIT);
        }
	}

	/**
	 * 退出群
	 */
	public function quit()
	{
		$user = $this->member->getMemberByGroup($this->groupInfo['gid'], $this->uid);
		if($user['position'] == GroupConst::GROUP_ROLE_MASTER)
			$this->showMessage('Yor are master of the group, so you can not quit.', ErrorCode::CODE_GROUP_NO_PERMISSION);

		$this->group->quit($this->groupInfo['gid'], $this->uid);
		
		$this->showMessage(
			'Success!',
			ErrorCode::CODE_SUCCESS,
			null,
			mk_url('main/index/main')
		);
	}

	/**
	 * 群成员列表
	 */
	function member(){
		$users = $this->member->getGroupMembersByPage($this->groupInfo['gid'], 1, 20);
        $num_of_group_member = $this->member->getNumOfGroupMember($this->groupInfo['gid']);
        $num_of_group_member = $num_of_group_member ? $num_of_group_member['0']['num'] : 0;
        $last = $num_of_group_member > 20 ? true : false;
		$data = array(
                    'group'=> $this->groupInfo,
                    'user'=> $this->user,
                    'users'=> $users,
                    'num'=> $num_of_group_member,
                    'last'=> $last,
                    'gid'=> $this->groupInfo['gid']
		);
		$this->view('member', $data);
	}

    function memberbypage(){
        $page = $this->input->post('page',true);
        $page = $page ? $page : 1;
        $num_of_group_member = $this->member->getNumOfGroupMember($this->groupInfo['gid']);
        $num_of_group_member = $num_of_group_member ? $num_of_group_member['0']['num'] : 0;
        $last = $num_of_group_member > 20 ? true : false;
		$users = $this->member->getGroupMembersByPage($this->groupInfo['gid'], $page, 20);
        $this->showMessage(array('msg'=>'success'),ErrorCode::CODE_SUCCESS,array('last' =>$last,'list' =>$users));
	}
}