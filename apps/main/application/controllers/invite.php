<?php
if (! defined ( 'BASEPATH' ))
	exit ( 'No direct script access allowed' );
/**
 * 请求类
 * Enter description here ...
 * @author gefeichao
 * @date   2012-02-23
 */
class Invite extends MY_Controller {
	function __construct() {
		parent::__construct ();
		define('UID', $this->uid);
	}

	/**
	 * 获取可能认识的人列表 （之间有熟人关系）
	 * @author gefeichao
	 * @access public
	 * @date 2012-03-16
	 * @param $uid 当前用户uid
	 */
	function mayfriend($page =1){
		$myarrays = array();
		$this->load->model('mayknowmodel', 'mayknow', TRUE);
		$uid=$this->uid;
		//获取可能认识的人列表
		$arrays = $this->mayknow->getMayKnowInfo(UID,$page,4);
		$count = array_pop($arrays);
		
		if(!$count) $more =0;
		($count > ($page *4)) ? $more =1 : $more =0;
		$str = '';
		foreach ($arrays as $value) {
			$str[] = $value['uid'];
		}
		//获得用户信息
		$user = service('User')->getUserList($str);
		
		foreach ($arrays as $value) {
			$str=array();
			foreach($user as $item){
				if($value['uid'] == $item['uid']){
					$value['dkcode']=$item['dkcode'];		//获取端口号
					$value['name']=$item['username'];		//获取用户名称
					break;
				}
			}
			$value['avatarurl']=get_avatar($value['uid']);
			$str = array_values($value['same_friend_info']);
			if(count($str) == 0 || !$str[0]) continue;
			$value['sum']=count($value['same_friend_info']);
			if($value['sum']==1){
				$value['sum']="共同朋友 <a href='".mk_url('main/index/main', array('dkcode' => $str[0]['dkcode']))."'>".$str[0]['name']."</a>";
			}else if ($value['sum']==2){
				$value['sum']="<a href='".mk_url('main/index/main', array('dkcode' => $str[0]['dkcode']))."'>".$str[0]['name']."</a> 和<a href='".mk_url('main/index/main', array('dkcode' => $str[1]['dkcode']))."'> ".$str[1]['name']."</a> 是共同朋友";
			}else{
				$i=$value['sum']-1;
				$value['sum']="<a href='". mk_url('main/index/main', array('dkcode' => $str[0]['dkcode']))."'>".$str[0]['name']."</a> 和<a class='shareNum' href='javascript:void(0);'>其他 ".$i." 位共同朋友</a>";
			}
			$value['userpath'] = mk_url('main/index/main', array('dkcode' => $value['dkcode']));
			$myarrays[]=$value;
		}
		return array($myarrays,$more,$count);
	}

	/**
	 * 异步加载可能认识的人列表
	 */
	function load_list(){
		$page = $this->input->post('page')? $this->input->post('page') : 1;
		$myarrays = $this->mayfriend($page);
		$data['state']=1;
		$data['more']=$myarrays[1];
		$data['message']=$myarrays[0];
		$data = array('state' =>$data['state'],'next_page' => $data['more'],'pageCount'=>$myarrays[2],'data' => $data['message']);
		$this->ajaxReturn($data,'',1,'json');
	}
	
	/**
	 * 获取好友请求列表数据
	 * @author gefeichao
	 * @date 2012-03-16
	 * @param $uid		登录uid
	 * @param $page		页码 【1开始】
	 * @param $limit	每页条数
	 * @return array
	 * */
	function list_apply($uid = null, $page =1, $limit = 10){
		$valarray = array();
		if(!$uid)
			return false;
		//获取请求数据代码
		$applist = service("Relation")->getReceivedFriendRequests($uid, $page, $limit);
		if(!$applist)
			return false;
			$str = '';
		foreach ($applist as $value) {
			$str[] = $value['uid'];
		}
		//获得信息
		$user = service("User")->getUserList($str);
		foreach ($applist as $v) {
			foreach($user as $item){
				if($v['uid'] == $item['uid']){
					$v['dkcode']=$item['dkcode'];		//获取端口号
					$v['username']=$item['username'];		//获取用户名称
				}
			}
			//获得好友用户头像id
			$v['avatarurl']=get_avatar($v['uid']);
			//格式化时间
			$v['dateline']=friendlyDate($v['ctime']);
			$v['userpath'] = mk_url('main/index/main', array('dkcode' => $v['dkcode']));
			$valarray[]=$v;
		}
		return $valarray;
	}
	 
	/**
	 * 取得好友请求列表
	 *
	 * @author gefeichao
	 * @date 2012-03-16
	 * @access public
	 * @param $uid 使用者uid
	 * @return 好友请求信息
	 */
	function get_friend_apply(){
		
		$list=$this->mayfriend();
		$this->assign ('avatar', get_avatar($this->uid));
		$this->assign ('user',$this->user);
		$this-> assign('friend_lists',$list[0]);
		$this-> assign('more', $list[1]);
		$this-> display('request/all_request.html');
	}

	/**
	 * 异步加载请求列表
	 * @author gefeichao
	 * @date 2012-03-16
	 * @access public
	 */
	function show_invite(){
		$uid=$this->uid;$more = true;
		$page = $this->input->post('page') ? $this->input->post('page') : 1;
		$limit = 10;
		$pagesize = $page * $limit;
		$messages=$this->list_apply($uid,$page,$limit);
		
		if($messages){
			$countapp = service('Relation')->getNumOfReceivedFriendRequests($uid);
			if($countapp > $pagesize){
				$more = false;
			}
			$state = 1;
		}else{
			$messages = "";
			$state = 0;
		}
		$data = array('status' =>$state, 'isend' => $more, 'data' => $messages);
		$this->ajaxReturn($data,'',$state,'json');
	}


	/**
	 * 取得弹出层的数据
	 * @author gefeichao
	 * @date 2012-03-16
	 * @return array
	 */
	function show_friendinvite(){

		$result=$this->list_apply($this->uid,1,5);
		$str="";
		if($result){
			foreach ($result as $r){
				$str.="<li class='clearfix' rid=".$r['uid'].">";
				$str.="<span class='picHead'><a href='".mk_url('main/index/main', array('dkcode' => $r['dkcode'])) ."'><img src='".$r['avatarurl']."'/></a></span>";
				$str.="<span class='friendInfo'><a href='".mk_url('main/index/main', array('dkcode' => $r['dkcode']))."'><strong>".$r['username']."</strong></a><br>".$r['dateline']."</span>";
				$str.="<span class='addView'>";
				$str.=" <span class='btnBlue' name='reqFriend'><i class='friend'></i><a href='javascript:void(0);'>加好友</a></span> "; 
				$str.="<span class='btnGray' name='reqIgnore'><a href='javascript:void(0);'>忽略请求</a></span>
					 </span></li>";
			}
		}else{
			$str.="<li class='not-request-list'>没有可显示的请求列表</li>";
		}
		$data = array('state' => '1', 'data' => $str);
		$this->ajaxReturn($data,'',1,'jsonp');
	}

	/**
	 * 忽略好友
	 * @param $uid 对方用户uid
	 * @return bool
	 */
	function ignore_friend(){
		
		$pagesize = 10;
		$to_uid = $this->input->post('fr_uid');
		//去除关系
		$bool = service("Relation")->deleteFriendRequest(UID, $to_uid);
		if($bool){
			$num = 1;
			//未读请求计数 减1
			service("Notice")->setting(UID, 'editinvite');
		}else	$num = 0;
		
		$result=$this->list_apply(UID,1,$pagesize);
		$str= $more = "";
		if($result){
			$countapp = service('Relation')->getNumOfReceivedFriendRequests(UID);
			$more = ($countapp > $pagesize) ? false : true;
			foreach ($result as $r){
				$str.="<li class='clearfix' rid=".$r['uid'].">";
				$str.="<span class='picHead'><a href='".mk_url('main/index/main', array('dkcode' => $r['dkcode'])) ."'><img src='".$r['avatarurl']."'/></a></span>";
				$str.="<span class='friendInfo'><a href='".mk_url('main/index/main', array('dkcode' => $r['dkcode'])) ."'><strong>".$r['username']."</strong></a><br>".$r['dateline']."</span>";
				$str.="<span class='addView'>";
				$str.=" <span class='btnBlue' name='reqFriend'><i class='friend'></i><a href='javascript:void(0);'>加好友</a></span> "; 
				$str.="<span class='btnGray' name='reqIgnore'><a href='javascript:void(0);'>忽略请求</a></span>
					 </span></li>";
			}
		}else{
			$str.="<li class='clearfix'><span style='padding:6px 8px 4px;'>没有可显示的请求列表</span></li>";
		}
		$data = array('state' => $num, 'isend' => $more, 'data' =>$str);
		$this->ajaxReturn($data,'',$num,'json');

	}


	/**
	 * 获取用户与某人共同好友列表
	 * @author gefeichao
	 * @param $to_uid
	 * @return array
	 */
	function  getCommonFriends(){
		$uid = $this->uid;
		$to_uid = $this->input->post('f_uid');
		
		//获取共同好友列表
		$cf = service("Relation")->getCommonFriends(UID, $to_uid);
		$str="<ul>";
		$user = service("User")->getUserList($cf);
		foreach ($cf as $c){
			foreach($user as $item){
				if($c == $item['uid']){
					$dkcode=$item['dkcode'];		//获取端口号
					$name=$item['username'];		//获取用户名称
					break;
				}
			}
			//uid、头像、名字、端口号
			$avatar=get_avatar($c);
			$id=$c;
			$str .="<li class='clearfix'>
						 <a href='".mk_url('main/index/main', array('dkcode' => $dkcode))."' target='_parent'><img height='50' width='50' alt='头像' src='$avatar'></a>
						 <a href='".mk_url('main/index/main', array('dkcode' => $dkcode))."' target='_parent'>$name</a>
						<div class='statusBox'>
										<div class='dropWrap dropMenu'>
											<div class='triggerBtn'><i class='friend'></i><span>好友</span><s></s></div>
										<div class='dropList'>
											<ul class='dropListul checkedUl'>
													<li rid='". $id ."'><a class='itemAnchor delFriend' href='javascript:void(0);'><span>删除好友</span></a></li>
											</ul>
										</div>
									</div>
						</div>
			 		</li> ";
			
		}
		$str .="</ul>";
		$result = array('state'=> 1,
						'msg'  => '数据获取失败!',
						'data' => $str);
		$this->ajaxReturn($result,'',1,'json');
	}
}
?>