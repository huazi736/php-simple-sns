<?php
use \Models as Model;
use \Domains as Domain;

/**
 * 活动控制器
 *
 * @author weihua
 * @version 3 2012/3/15
 */

class Event extends MY_Controller
{
	public $page_length = 20;
	public $messages_length = 30;
	public $user;
	public $uid;
	public $reg_url = "#(((http|https)://)?(\w+\.)+(\w+\-*[/%\?=&\.]*)+\s*)#";
	public $dkcode;

	protected function _initialize()
	{
		$this->user['avatar'] = get_avatar($this->user['uid']);
		if ($this->action_dkcode) {
			if(!service('UserPurview')->checkAppPurview($this->action_uid, $this->uid, 'event')){
				$this->error('该用户没有公开');
			}
			$this->action_user['avatar'] = get_avatar($this->action_user['uid']);
			$this->assign('visitor', $this->action_user);
		}else {
			$this->action_uid = $this->uid;
			$this->assign('visitor', '');
		}
		$this->assign('page', 1);
		$this->assign('user', $this->user);
		$this->assign('isMy', ($this->action_uid == $this->uid));
		if (!$this->action_dkcode) {
			$this->assign('main_a', mk_url('main/index/main'));
			$this->assign('event_a', mk_url('event/event/index'));
			$this->assign('create_a', mk_url('event/event/create'));
			$this->assign('mylist_a', mk_url('event/event/mylist'));
			$this->assign('endlist_a', mk_url('event/event/endlist'));
			$this->assign('detail_a', mk_url('event/event/detail'));
			$this->assign('doMoreList_url', mk_url('event/event/doMoreList'));
		}
		else {
			$this->assign('main_a', mk_url('main/index/main', array('dkcode'=>$this->action_dkcode)));
			$this->assign('event_a', mk_url('event/event/index', array('dkcode'=>$this->action_dkcode)));
			$this->assign('create_a', mk_url('event/event/create', array('dkcode'=>$this->action_dkcode)));
			$this->assign('mylist_a', mk_url('event/event/mylist', array('dkcode'=>$this->action_dkcode)));
			$this->assign('endlist_a', mk_url('event/event/endlist', array('dkcode'=>$this->action_dkcode)));
			$this->assign('detail_a', mk_url('event/event/detail', array('dkcode'=>$this->action_dkcode)));
			$this->assign('doMoreList_url', mk_url('event/event/doMoreList', array('dkcode'=>$this->action_dkcode)));
		}
	}

	/**
	 *
	 */
	public function index()
	{
		$this->mylist();
	}

	/**
	 * 我的活动
	 */
	public function mylist()
	{
		$user_events = new Domain\EventUser(array('uid'=>$this->action_uid));
		$rows = $user_events->getEvents(0, 1, ($this->action_uid != $this->uid));

		$this->assign('page' , 'mylist');
		$this->_list($rows);
	}

	/**
	 * 结束的活动
	 */
	public function endlist()
	{
		$user_events = new Domain\EventUser(array('uid'=>$this->action_uid));
		$rows = $user_events->getEndEvents(0, 1, ($this->action_uid != $this->uid));
		$this->assign('page' , 'endlist');
		$this->_list($rows);
	}

	private function _list($rows)
	{
		if(empty($rows))
		{
			if ($this->action_uid != $this->uid){
				$this->display('visitor/no_list.tpl');
			}else{
				$this->display('no_list.tpl');
			}
		}
		else
		{
			$this->display('list.tpl');
		}
	}

	/**
	 * 创建活动表单页面
	 */
	public function create()
	{
		//header('Expires: -1');
		header('Cache-Control: no-cache, no-store');
		header('Pragma: no-cache');

		$this->assign('docreate_a', mk_url('event/event/docreate'));
		$form = new Model\DetailForm();
		$token = $form->init_token();
		$this->assign('formToken' , $token);
		$this->assign('page' , 'mylist');
		$this->display('create.tpl');
	}

	/**
	 * post
	 * 实际创建活动
	 */
	public function doCreate()
	{
		header('Cache-Control: no-cache, no-store');
		header('Pragma: no-cache');

		$posts = $this->input->post();
		$form = new Model\DetailForm($posts);
		$token = $this->input->post('eventid');
		$form->set_token($token);

		if($error = $form->errors()){
			$this->error($error);
		}
		$data = $form->get_data();

		//获取保存的临时信息
		$tmp_img = $form->get_keep('img');
		$tmp_img_s = $form->get_keep('img_s');
		$tmp_img_b = $form->get_keep('img_b');

		$invite_users = (array)$form->get_keep('invite_users');

		$eventModel = new Domain\Event();

		$event = $eventModel->create(array('uid'=>$this->uid,'username'=>$this->username,'dkcode'=>$this->dkcode), $data, $tmp_img, $tmp_img_s, $tmp_img_b);
		service_api('Credit', 'activity', array(true));
		$eventModel->invite($this->uid, $invite_users);
		//进行清理
		if ($tmp_img) {
			@unlink($tmp_img);
			@unlink($tmp_img_s);
			@unlink($tmp_img_b);
		}

		//销毁表单
		$form->destroy();

		$this->redirect('event/event/detail', array('id'=>$event));
	}

	/**
	 * 编辑活动表单页面
	 */
	public function edit()
	{
		//header('Expires: -1');
		header('Cache-Control: no-cache, no-store');
		header('Pragma: no-cache');

		$eid = (int)$this->input->get('id');

		$eventModel = new Domain\Event();

		$event = $eventModel->getEvent($eid);

		if (empty($event)) {
			$this->error('指定的活动不存在');
		}

		$userModel = $eventModel->getUser($this->uid);

		if (!$userModel && !$userModel->canAdmin()) {
			$this->error('您没有权限进行此操');
		}

		/*
		 * 转换成页面需要的格式
		 * 如:2012-03-06 17:30
		 * 转换至 date:2012-03-06
		 * 和 time: 从今天0时到17:30经过的分钟数
		 */

		$tmp = explode(' ', $event['starttime']);
		$event['startDate'] = $tmp[0];
		$tmp = explode(':', $tmp[1]);
		$event['startTime'] = ((int)$tmp[0] * 60) + (int)$tmp[1];
		$event['startTime2'] = $tmp[0] . ':'. $tmp[1];

		$tmp = explode(' ', $event['endtime']);
		$event['endDate'] = $tmp[0];
		$tmp = explode(':', $tmp[1]);
		$event['endTime'] = ((int)$tmp[0] * 60) + (int)$tmp[1];
		$event['endTime2'] = $tmp[0] . ':' . $tmp[1];

		$users = $eventModel->getUsers(true);

		$admins = array();

		foreach ($users as $user) {
			if ($user['type'] == '2') {
				array_unshift($admins, $user);
			}
			else if ($user['type'] == '1'){
				$admins[] = $user;
			}
		}

		$event['img'] = url_fdfs($event['fdfs_group'],$event['fdfs_filename']);
		$nation = '中国';
		$province = '请选择';
		$city = '请选择';
		$areaArr = explode('/',$event['area']);
		if($areaArr[0]!='-1')
		{
			include APPPATH.DS.'config'.DS.'area.php';
			$area = json_decode($areaJson);
			if(isset($areaArr[0]))
			$nation = $area->$areaArr[0]->area_name;
			if(isset($area->$areaArr[0]->list->$areaArr[1]))
			$province = $area->$areaArr[0]->list->$areaArr[1]->area_name;
			if(isset($area->$areaArr[0]->list->$areaArr[1]->list->$areaArr[2]))
			$city = $area->$areaArr[0]->list->$areaArr[1]->list->$areaArr[2]->area_name;
		}
		$event['area'] =  $nation.' '.$province.' '.$city;


		$this->assign('admins',$admins);
		$this->assign('event', $event);
		$this->assign('event_users',$users);

		$this->assign('doedit_a',mk_url('event/event/doedit',array('eid' => $eid)));
		$this->assign('showadmin',true);

		$form = new Model\DetailForm();

		$token = $form->init_token();

		$form->keep('eid', $eid);
		$this->assign('page','edit');
		$this->assign('formToken',$token);
		$this->display('edit.tpl');
	}

	/**
	 * 编辑活动保存
	 */
	public function doEdit()
	{
		header('Cache-Control: no-cache, no-store');
		header('Pragma: no-cache');
		$token = $this->input->post('eventid');
		$admins = $this->input->post('addAdmin2');
		$admins = array_unique(array_filter(array_map('intval', explode(',', $admins))));

		$posts = $this->input->post();
		$form = new Model\DetailForm($posts);

		$form->set_token($token);

		if($error = $form->errors()){
			$this->error($error);
		}

		$eid = $form->get_keep('eid');
		if(!$eid){
			$eid = $this->input->get_post('eid');
		}
		$eventModel = new Domain\Event();

		$event = $eventModel->getEvent($eid);

		if (empty($event)) {
			$this->error('指定的活动不存在');
		}

		$userModel = $eventModel->getUser($this->uid);

		if (!$userModel || !$userModel->canAdmin()) {
			$this->error('您没有权限进行此操');
		}

		//过虑掉创建者
		$admins = array_diff($admins, array($event['uid']));

		if (count($admins) > 5) {
			$this->error('管理员不能超过5个');
		}

		//不检查自己
		$tmp = array_diff($admins, array($this->uid));

		if (!empty($tmp)) {
			//need add
			if (!users_isBothFollow($this->uid, $tmp)) {
				$this->error('请求错误');
			}
		}

		$data = $form->get_data();

		//获取保存的临时信息
		$tmp_img = $form->get_keep('img');
		$tmp_img_s = $form->get_keep('img_s');
		$tmp_img_b = $form->get_keep('img_b');

		$invite_users = (array)$form->get_keep('invite_users');

		$eventModel->edit($this->user, $data, $tmp_img, $tmp_img_s, $tmp_img_b);

		//设置管理员,先设置管理员
		$eventModel->setAdmins($this->uid, $admins);

		//邀清其它用户参加活动
		$eventModel->invite($this->uid, $invite_users);

		//进行清理
		if ($tmp_img) {
			@unlink($tmp_img);
			@unlink($tmp_img_s);
			@unlink($tmp_img_b);
		}

		//销毁表单
		$form->destroy();
		$this->redirect('event/event/detail', array('id'=>$event['id']));
	}

	/**
	 * 添动详情
	 */
	public function detail()
	{
		$eid = (int)$this->input->get('id');

		$eventModel = new Domain\Event();

		$event = $eventModel->getEvent($eid);

		if (empty($event)) {
			$this->error('指定的活动不存在');
		}

		//得到所有被邀者
		$users = $eventModel->getUsers();
		//当前用户
		$userModel = $eventModel->getUser($this->uid);

		//检测当前用户是否具有活动的管理权限
		$is_admin = (!empty($userModel) && $userModel->canAdmin()) ? true : false;

		//$canReply  = (!empty($userModel) && $userModel->canReply()) ? true : false;

		/*
		 * 对参与人员以回复结果分组
		 */
		$event_users = array(
			'event_users_sure' => array('type1' => array(), 'type2' => array(), 'num' => 0 ),
			'event_users_unknown' => array('type1' => array(), 'type2' => array(), 'num' => 0 )
		);

		$user_num = count($users);

		if ($userModel) {
			$current_user = $userModel;
		}
		else {
			$current_user = $this->user;
			$current_user['type'] = '-2';
			$current_user['answer'] = '-2';
		}

		$create_user = null;
		$admin_users = array();

		foreach ($users as $user) {
			switch ($user['answer']) {
				case '2' : $group = 'event_users_sure'; break;
				case '1' : $group = 'event_users_unknown'; break;
				default: break 2;
			}

			$event_users[$group]['num']++;

			if ($user['type'] > 0 && $user['answer'] == 2) {
				$admin_users['type1'][] = $user;
			}

			if ($user['type'] == 2) {
				$create_user = $user;
			}
			else {
				//最多10个人,多出以省略号显示
				if ($event_users[$group]['num'] < 11) {
					array_push($event_users[$group]['type2'], $user);
				}
			}
		}

		$admin_users['type2'] = array();
		array_unshift($event_users['event_users_sure']['type2'], $create_user);
		$edit_a = mk_url('event/event/edit', array('id'=>$eid));

		$replyImg_a = mk_url('event/event/replyImg', array('eid'=>$eid));

		$is_show_users = $eventModel->isShowUsers();

		$event['starttime'] = substr($event['starttime'], 0, -3);
		$event['endtime'] = substr($event['endtime'], 0, -3);
		$nation = '';
		$province = '';
		$city = '';
		$areaArr = explode('/',$event['area']);
		if($areaArr[0]!='-1')
		{
			include APPPATH.DS.'config'.DS.'area.php';
			$area = json_decode($areaJson);
			if(isset($areaArr[0]))
			$nation = $area->$areaArr[0]->area_name;
			if(isset($area->$areaArr[0]->list->$areaArr[1]))
			$province = $area->$areaArr[0]->list->$areaArr[1]->area_name;
			if(isset($area->$areaArr[0]->list->$areaArr[1]->list->$areaArr[2]))
			$city = $area->$areaArr[0]->list->$areaArr[1]->list->$areaArr[2]->area_name;
		}
		$event['address'] = $nation.$province.$city.$event['address'];
		$event['img'] = url_fdfs($event['fdfs_group'],$event['fdfs_filename']);
		$this->assign('sessionid' , Model\My_Session::session_id());

		$this->assign('is_show_users',$is_show_users);
		$this->assign('current_user', $current_user);
		$this->assign('admin_users', $admin_users);
		$this->assign('edit_a', $edit_a);
		$this->assign('is_admin', $is_admin);
		$this->assign('event',  $event);
		$this->assign('replyImg_a',  $replyImg_a);
		$this->assign('event_users_sure', $event_users['event_users_sure']);
		$this->assign('event_users_unknown',  $event_users['event_users_unknown']);
		$this->assign('authcode',base64_encode(authcode('module=3','',config_item('authcode_key'))));
		$this->assign('edit_event', mk_url('event/event/edit',array('id'=>$eid)));
		$this->display('detail.tpl');
	}

	/**
	 * ajax post
	 * 活动留言
	 *
	 * 相关页面:
	 * 活动详情页
	 */
	public function Info()
	{
		$obj = new stdClass();
		$obj->data = array();
		$obj->info = '';
		$eid = (int)$this->input->post('eventid');
		$page = (int)$this->input->post('page') - 1;
		if ($page < 0) {
			$page = 0;
		}
		if (!$eid) {
			do404:
			$obj->status = false;
			$obj->info = '指定的活动不存在';
			goto doecho;
		}

		$eventModel = new Domain\Event();

		$event = $eventModel->getEvent($eid);

		if (empty($event)) {
			goto do404;
		}

		$userModel = $eventModel->getUser($this->uid);

		$is_admin =  !empty($userModel) && $userModel->canAdmin();

		$offset = $page * $this->messages_length;

		$rows = $eventModel->getMessages($offset, $this->messages_length + 1);

		$hasMore = false;
		if (count($rows) > $this->messages_length) {
			$hasMore = true;
			array_pop($rows);
		}

		$obj->status = true;

		$dkcode = array();
		foreach ($rows as $row) {
			$dkcode[$row['uid']] = null;
		}

		if (!empty($dkcode)) {
			$tmps = api_ucenter_user_getUserList(array_keys($dkcode), array('uid', 'dkcode'));
			foreach ($tmps as $row) {
				$dkcode[$row['uid']] = $row['dkcode'];
			}
		}

		$data = array();
		foreach ($rows as $row) {
			$tmp = array(
				'fid' => $row['id'],
				'tid' => $row['id'],
				'action_uid' => $row['uid'],
				'can_del' => ($is_admin || $row['uid'] == $this->uid) ? true : false,
				'link' => false,
				'avatar' => get_avatar($row['uid']),
				'code' => mk_url('main/index/profile', array('dkcode' => $dkcode[$row['uid']])),
				'username' => $row['username'],
				'message' => $row['message'],
				'addtime' => friendlyDate(strtotime($row['addtime'])),
				'image' => false,
				'video' => false,
			);

			$tmp['message'] = preg_replace($this->reg_url, '<a target="_bank" href="\1">\1</a>', $tmp['message']);

			if ($row['type'] == 2) {
				$tmp['image'] = url_fdfs($row['group'],$row['filename']);
			}

			if ($row['type'] == 3) {
				$tmp['video'] = array_combine(array('imgurl', 'videourl'), explode(';', $row['src']));
			}

			$data[] = $tmp;
		}

		$obj->data = $data;
		$obj->isend = $hasMore ? 0 : 1;
		doecho:
		$data = array('data' => $obj->data ,'isend'=>$obj->isend,'status'=>$obj->status,'info'=>$obj->info);
		$this->ajaxReturn($data);
	}

	/**
	 * ajax post
	 * 回复按钮
	 *
	 * 相关页面:
	 *     活动详情页
	 *     他人活动页
	 */
	public function replyMsg()
	{
		header("Content-Type:text/javascript; charset=utf-8");
		$obj = new stdClass();
		$obj->msg ='';
		$obj->data = array();
		$eid = (int)$this->input->post('eventid');
		$msg = $this->input->post('message');
		$msg = htmlspecialchars($msg);

		$eventModel = new Domain\Event();
		$event = $eventModel->getEvent($eid);

		if (empty($event)) {
			$obj->status = false;
			$obj->msg = '指定的活动不存在';
			goto doecho;
		}

		$new_id = $eventModel->addMessage(1, $msg, '', $this->uid);

		$obj->status = true;
		//		$msg = preg_replace($this->reg_url, '<a tagrget="_bank" href="\1">\1</a>', $msg);
		$data = array(
			'fid' => $new_id,
			'tid' => $new_id,
			'action_uid' => $this->uid,
			'link' => false,
			'avatar' => get_avatar($this->uid),
			'code' => mk_url('main/index/profile', array('dkcode' => $this->dkcode)),
			'username' => $this->user['username'],
			'addtime' => '刚刚',
			'message' => $msg,
			'image' => false,
			'video' => false,
			'can_del' => true,
		);

		$obj->data = $data;
		doecho:
		$this->ajaxReturn($obj->data,$obj->msg,$obj->status);
	}

	/**
	 * iframe post
	 * 活动详情页图片回复
	 *
	 * 相关页面:
	 *     活动详情页
	 *     他人活动页
	 */
	public function replyImg()
	{
		header("Content-Type:text/html; charset=utf-8");

		$token = (int)$this->input->post('tokenShareDestinations');
		$hash = $this->input->post('__hash__');

		$eid = (int)$this->input->get('eid');

		$msg = $this->input->post('distributeAttachIntro');
		$msg = htmlspecialchars($msg);
		$obj = new stdClass();
		$obj->data = array();
		$eventModel = new Domain\Event();
		$event = $eventModel->getEvent($eid);

		if (empty($event)) {
			$obj->status = false;
			$obj->data = '指定的活动不存在';
			goto do_echo;
		}

		if (!isset($_FILES['uploadPhotoFile']) || !Model\ImageModel::isValid($_FILES['uploadPhotoFile']['tmp_name'])) {
			$obj->status = false;
			$obj->info = '您上传的图片不合法';
			goto do_echo;
		}

		$file1 = tempnam('/tmp', 'reply_img_');

		try{
			$img = new Model\ImageModel($_FILES['uploadPhotoFile']['tmp_name']);
			if($_FILES['uploadPhotoFile']['size'] > $img->allowSize() || $_FILES['uploadPhotoFile']['size'] < 1){
				$obj->status = false;
				$obj->info = '您上传的图片大于4M了或文件不正常';
				goto do_echo;
			}

			$img->maybeReSizeToFile($file1, 403, 403, 'jpg');

			//图片很占内存,就地干掉
			unset($img);
		}
		catch(Exception $e)
		{
			$obj->status = false;
			$obj->info = '您上传的图片不合法';
			goto do_echo;
		}

		list($new_id, $img_url) = $eventModel->addMessage(2, $msg, $file1, $this->uid);

		@unlink($file1);

		$obj->status = true;

//		$msg = preg_replace($this->reg_url, '<a tagrget="_bank" href="\1">\1</a>', $msg);

		$data = array(
			'fid' => $new_id,
			'tid' => $new_id,
			'action_uid' => $this->uid,
			'link' => false,
			'avatar' => get_avatar($this->uid),
			'code' => mk_url('main/index/index', array('dkcode' => $this->uid)),
			'username' => $this->user['username'],
			'addtime' => '刚刚',
			'message' => $msg,
			'image' => $img_url,
			'video' => false,
			'can_del' => true,
		);
		$obj->data = $data;
		do_echo:
		echo '<script>window.parent.sendPhotoComplete('.json_encode($obj).')</script>';
	}

	/**
	 * ajax post
	 * 活动页视频回复
	 *
	 * 相关页面
	 *     活动详情页
	 *     他人活动页
	 */
	public function replyVideo()
	{
		header("Content-Type:text/javascript; charset=utf-8");
		$obj = new stdClass();
		$obj->data = array();
		$eid = (int)$this->input->post('eventid');
		$msg = $this->input->post('message');
		$vid = $this->input->post('vid');
		$msg = strip_tags($msg);

		$eventModel = new Domain\Event();
		$event = $eventModel->getEvent($eid);

		if (empty($event)) {
			$obj->status = false;
			$obj->data = '指定的活动不存在';
			goto doecho;
		}

		$userModel = $eventModel->getUser($this->uid);

		if (empty($userModel)) {
			$obj->status = false;
			$obj->data = '您没有参与该活动';
			goto doecho;
		}

		if (!$userModel->canReply()) {
			$obj->status = false;
			$obj->data = '您没有权限进行此操作';
			goto doecho;
		}

		$src = $imgurl . ';' . $videourl;

		$new_id = $eventModel->addMessage(3, $msg, $src, $this->uid);

		$obj->status = true;

		$msg = preg_replace($this->reg_url, '<a tagrget="_bank" href="\1">\1</a>', $msg);

		$data = array(
			'fid' => $new_id,
			'tid' => $new_id,
			'action_uid' => $this->uid,
			'link' => false,
			'avatar' => get_avatar($this->uid),
			'code' => mk_url('main/index/index', array('dkcode' => $this->uid)),
			'username' => $this->user['username'],
			'addtime' => '刚刚',
			'message' => $msg,
			'image' => false,
			'video' => false,
			'can_del' => true,
		);

		$data['video'] = array_combine(array('imgurl', 'videourl'), array($imgurl, $videourl));

		$obj->data = $data;
		doecho:
		$data = array('data' => $obj->data ,'status'=>$obj->status);
		$this->ajaxReturn($data);
	}

	/**
	 * ajax post
	 * 删除回复
	 *
	 * 相关页面
	 *     活动详情页
	 */
	public function replyDel()
	{
		header("Content-Type:text/html; charset=utf-8");
		$obj = new stdClass();
		$obj->data = array();
		$eid = (int)$this->input->post('eventid');
		$rid = (int)$this->input->post('replyid');

		if (!$eid || !$rid) {
			$obj->status = false;
			$obj->info = '参数错误';
			goto doecho;
		}

		$eventModel = new Domain\Event();

		$event = $eventModel->getEvent($eid);

		if (empty($event)) {
			$obj->status = false;
			$obj->info = '活动不存在';
			goto doecho;
		}

		$messageModel = $eventModel->getMessage($rid);

		if (empty($messageModel)) {
			$obj->status = false;
			$obj->info = '留言不存在';
			goto doecho;
		}

		$userModel = $eventModel->getUser($this->uid);

		if (!empty($userModel))
		$isAdmin = $userModel->canAdmin();
			

		//检查权限
		if ($messageModel['uid'] != $this->uid && !isset($isAdmin)) {
			$obj->status = false;
			$obj->info = '您没有权限删除';
			goto doecho;
		}

		$messageModel->del();

		$obj->status = true;
		$obj->info = '删除成功';
		doecho:
		$this->ajaxReturn($obj->data,$obj->info,$obj->status);
	}

	/**
	 * ajax post
	 * 取消活动
	 *
	 * 相关页面
	 *     活动详情页
	 */
	public function cancelEvent()
	{
		header("Content-Type:text/javascript; charset=utf-8");
		$obj = new stdClass();
		$obj->data = array();
		$obj->msg = '';
		$obj->jump = '';
		$eid = (int)$this->input->post('eventid');

		if (!$eid) {
			$obj->status = false;
			$obj->msg = '请求错误';
			goto doecho;
		}

		$eventModel = new Domain\Event();
		$event = $eventModel->getEvent($eid);

		if (empty($event)) {
			$obj->status = false;
			$obj->msg = '活动不存在';
			goto doecho;
		}

		$userModel = $eventModel->getUser($this->uid);

		if (empty($userModel) || !$userModel->canAdmin()) {
			$obj->status = false;
			$obj->msg = '您没有权限进行此操作';
			goto doecho;
		}
		$eventModel->cancel($this->uid);
		service_api('Credit', 'activity', array(false));
		$obj->status = 1;
		$obj->jump = mk_url('event/event/mylist');
		doecho:
		$data = array('data' => $obj->data,'jump'=>$obj->jump);
		$this->ajaxReturn($data,$obj->msg,$obj->status);
	}

	/**
	 * ajax post
	 * 根据请求得到用户列表
	 *
	 * 相关页面
	 *     活动详情页
	 */
	public function getUserListByEventStatus()
	{
		header("Content-Type:text/javascript; charset=utf-8");
		$obj = new stdClass();
		$obj->data = array();
		$eid = (int)$this->input->post('eventid');
		$answer = (int)$this->input->post('jointype');

		//输入转换
		$answer_map = array(
			'4' => '2',
			'8' => '1',
		    '6' => '3'
		    );
		    $answer = isset($answer_map[$answer]) ? $answer_map[$answer] : null;

		    $eventModel = new Domain\Event();
		    $event = $eventModel->getEvent($eid);

		    $user = $eventModel->getUser($this->uid);
		    $canAdmin = ($this->action_uid == $this->uid && !empty($user) && $user->canAdmin()) ? true : false;

		    if (empty($event)) {
		    	$obj->status = false;
		    	$obj->msg = '活动不存在';
		    	goto doecho;
		    }

		    //检查当前活列表是否可以显示
		    if ($eventModel->isShowUsers()) {
		    	$users = $eventModel->getUsers();
		    }
		    else {
		    	$users = array();
		    }

		    $datas = array(
			'2' => array(),
			'1' => array(),
		    );

		    foreach ($users as $user) {
		    	$data = array(
				    'uid' => $user['id'],
				    'link' => url_home($user['dkcode']),
				    'userhead' => get_avatar($user['id']),
				    'username' => $user['name'],
				    'type' => $user['type'],
				    'answer' => $user['answer'],
		    	);
		    	$datas[$user['answer']][] = $data;
		    	$usertype = ($user['type'] == 1 or $user['type'] == 2) ? 3 : 0;
		    	if($usertype){
		    		$datas[$usertype][] = $data;
		    	}
		    }

		    //参加人数
		    $obj->allnum = count($users);           //所有人数
		    $obj->gonum = count($datas['2']);       //确定人数
		    $obj->unkownnum = count($datas['1']);   //未答复人数
		    $obj->managenum = count($datas['3']);   //管理员人数
		    $obj->canAdmin = $canAdmin;

		    $obj->status = 1;

		    $obj->msg = '';

		    if ($answer) {
		    	$obj->data = $datas[$answer];
		    }
		    else {
		    	$obj->data = array_merge($datas['2'], $datas['1']);
		    }
		    doecho:
		    $data = array('data' => $obj->data,'allnum'=>$obj->allnum ,'gonum'=>$obj->gonum,'unkownnum'=>$obj->unkownnum,'canAdmin'=>$obj->canAdmin,'managenum' => $obj->managenum);
		    $this->ajaxReturn($data,$obj->msg,$obj->status);
	}

	/**
	 * iframe post
	 * 上传图片接收
	 *
	 * 相关页面
	 *     创建活动页
	 *     编辑活动页
	 */
	public function addEventPic()
	{
		$obj = new stdClass();
		$obj->data = array();
		$token = $this->input->get('eventid');

		$form = new Model\DetailForm();

		if (!$form->set_token($token)) {
			$obj->status = 0;
			$obj->msg = '口令错误'.var_export($_SESSION['forms'], true);
		}
		else if (!isset($_FILES['uploadPhotoFile'])) {
			$obj->status = 0;
			$obj->msg = '上传名称错误或未上传';
		}
		else if ($_FILES['uploadPhotoFile']['error'] != UPLOAD_ERR_OK){
			$obj->status = 0;
			$obj->msg = '上传文件错误,请检查文件是否过大';
		}
		else if (!is_uploaded_file($_FILES['uploadPhotoFile']['tmp_name'])) {
			$obj->status = 0;
			$obj->msg = '文件上传错误';
		}
		else if ($_FILES['uploadPhotoFile']['size'] > 1024 * 1024 * 4) {
			$obj->status = 0;
			$obj->msg = '文件太大';
		}
		else if (!Model\ImageModel::isValid($_FILES['uploadPhotoFile']['tmp_name'])) {
			$obj->status = 0;
			$obj->msg = '您上传的图片格式我们不支持或不是一个有效的图片';
		}
		else {
			try{
				$img = new Model\ImageModel($_FILES['uploadPhotoFile']['tmp_name']);
				$filepath = VAR_PATH.'tmp/event/';
				if(!file_exists($filepath)){
					if(!@mkdir($filepath)){
						throw new Exception('活动临时目录创建失败!');
					}
				}
				$file1 = $filepath.$token.'.jpg';
				$file2 = $filepath.$token.'_s.jpg';
				$file3 = $filepath.$token.'_b.jpg';

				$img->reSizeToFile($file1, 180, 150, 'jpg');
				$img->reSizeToFile($file2, 112, 92, 'jpg');
				$img->resizeCrop($file3, 90, 60, 'jpg');

				$form->keep('img', $file1);
				$form->keep('img_s',$file2);
				$form->keep('img_b',$file3);

				//图片很占内存,就地干掉
				unset($img);

				$obj->status = 1;
				$obj->eventPhoto = WEB_ROOT.'var/tmp/event/'.$token.'.jpg' . '?' . rand();
			}
			catch(Exception $e)
			{
				$obj->status = 0;
				$obj->msg = '图片上传失败,请检查图片格式或目录权限';
			}
		}
		header("Content-Type:text/html; charset=utf-8");
		echo '<script>window.parent.sendPhotoComplete('.json_encode($obj).');</script>';
	}

	/**
	 * ajax
	 * 得到好友列表
	 *
	 * 创建活动页
	 *     得到得到可选管理员列表
	 *     得到邀请宾客列表
	 *
	 * 编辑活动页
	 *     得到得到可选管理员列表
	 *     得到邀请宾客列表
	 *
	 * 活动详情页
	 *     得到邀请宾客列表
	 */
	public function getEventFollowUserList()
	{
		header("Content-Type:text/json; charset=utf-8");
		$obj = new stdClass();
		$obj->data = array();
		$get_friend = (int)$this->input->get('field');
		$token = $this->input->get('eventid');

		$exist = $checked = $block = array();

		if (is_numeric($token)) {
			$eid = $token;

			$eventModel = new Domain\Event();
			$event = $eventModel->getEvent($eid);

			if (empty($event)) {
				$obj->status = false;
				$obj->msg = '活动不存在';
				goto doecho;
			}

			$rows  = $eventModel->getUsers(true);
			foreach($rows as $row) {
				if ($row['type'] == '-1') {
					$block[] = $row['id'];
				}
				else {
					$exist[] = $row['id'];
				}
			}
		}else {
			$form = new Model\DetailForm();

			if ($form->set_token($token)) {
				$checked = (array)$form->get_keep('invite_users');

				$eid = $form->get_keep('eid');
				if ($eid) {
					$eventModel = new Domain\Event();
					$event = $eventModel->getEvent($eid);

					if (empty($event)) {
						$obj->status = false;
						$obj->msg = '活动不存在';
						goto doecho;
					}

					$rows  = $eventModel->getUsers(true);
					foreach($rows as $row) {
						if ($row['type'] == '-1') {
							$block[] = $row['id'];
						}
						else {
							$exist[] = $row['id'];
						}
					}
				}
			}
		}

		if ($get_friend) {
			$users = service('Relation')->getbothfollowerswithinfo($this->uid, true, 1, 1000);
		}
		else {
			$users = service('Relation')->getFollowersWithInfo($this->uid, 1, 1000);
		}

		$datas = array();

		foreach ($users as $user) {
			$tmp1 = in_array($user['id'], $exist);
			$tmp2 = in_array($user['id'], $checked);
			$tmp3 = in_array($user['id'], $block);

			$tmp = array(
				'id' => $user['id'],
				'dkcode' => $user['dkcode'],
				'name' => $user['name'],
				'face' => get_avatar($user['id']),
				'hidden' => ($tmp1 || $tmp3) ? 1 : 0,
				'checked' => ($tmp1 || $tmp2) ? 1 : 0, 
			);
			if ($get_friend) {
				$tmp['userid'] = $user['id'];
				$tmp['username'] = $user['name'];
				$tmp['avatar'] = get_avatar($user['id']);
				//$tmp['location'] = $user['name'];
				$tmp['location'] = '';

				if (!$tmp3) {
					$datas[] = $tmp;
				}
			}
			else {
				$datas[] = $tmp;
			}
		}

		$obj->status = 1;
		if(count($datas))
		{
			$obj->status = 1;
			$obj->msg = '';
		}
		else
		{
			$obj->status = 0;
			$obj->msg = '您还没有任何粉丝';
		}
		$obj->data = $datas;
		doecho:
		$this->ajaxReturn($obj->data,$obj->msg,$obj->status);
	}

	/**
	 * ajax post
	 * 保存选中的好友列表
	 *
	 * 活动创建页
	 *     保存邀请宾客列表(保存到form)
	 *
	 * 活动编辑页
	 *     保存邀请宾客列表(保存到form)
	 *
	 * 活动详情页
	 *     保存邀请宾客列表(发邀请)
	 */
	public function inviteEventFriend()
	{

		$obj = new stdClass();
		$obj->data = array();
		$token = $this->input->get('eventid');
		$src_uid = $this->input->post('src_uid');
		$src_uid = array_filter(array_map('intval', explode(',', $src_uid)));

		if (!users_isFollower($this->uid, $src_uid)) {
			$obj->status = 0;
			$obj->msg = '非法操作';
			goto doecho;
		}

		//活动详情页提交过来的
		if (is_numeric($token)) {
			$eid = $token;

			$eventModel = new Domain\Event();
			$event = $eventModel->getEvent($eid);

			if (empty($event)) {
				$obj->status = false;
				$obj->msg = '活动不存在';
				goto doecho;
			}

			//验证是否有管理权限
			$userModel = $eventModel->getUser($this->uid);

			if (empty($userModel) || !$userModel->canAdmin()) {
				$obj->status = 0;
				$obj->msg = '非法操作';
				goto doecho;
			}

			//过滤掉己发过邀请的
			$users = $eventModel->getUsers(true, true);

			$invite_users = array_diff($src_uid , $users);

			//邀清其它用户参加活动
			$eventModel->invite($this->uid, $invite_users);

		}
		//创建和编辑页面过来的
		else {
			$form = new Model\DetailForm();

			if ($form->set_token($token)) {
				$form->keep('invite_users', $src_uid);
			}
		}

		$obj->status = 1;
		$obj->msg = '操作成功';
		doecho:
		$this->ajaxReturn($obj->data,$obj->msg,$obj->status);
	}

	/**
	 * ajax post
	 * 被邀请人答复接收
	 *
	 * 相关页面
	 *     活动详情页
	 *     他人活动详情页
	 */
	public function doAnswer()
	{
		$obj = new stdClass();
		$obj->data = array();
		$eid = (int)$this->input->post('eid');
		$state = (int)$this->input->post('status');

		$answerArr = array(0, 2);

		if (!$eid || !in_array($state, $answerArr)) {
			$obj->status = false;
			$obj->msg = '操作错误';
			goto doecho;
		}

		$eventModel = new Domain\Event();
		$event = $eventModel->getEvent($eid);

		if (empty($event)) {
			$obj->status = false;
			$obj->msg = '活动不存在';
			goto doecho;
		}

		$userModel = $eventModel->getUser($this->uid);
		if (!empty($userModel))
		{
			if ($userModel['type'] == '-1')
			{
				$obj->status = 0;
				$obj->msg = '您被禁止参加此活动';
				goto doecho;
			}
			else if ($userModel['answer'] == '1')
			$userModel->answer($state);
			else
			{
				$userModel->changeAnswer($state);
				if($state==2)
				service_api('Credit', 'attend', array());
				else
				{
					service_api('Credit', 'cancelAttend', array($this->uid));
					api_api('Timeline', 'removeMultiItem', array(array(
					'uid' => $this->uid,
					'type' => 'join',
					'index' => $eid , //活动ID
					'ctime' => time() //参加活动时间 没有作用
					)));
				}
			}

		}
		else
		{
			$d_user = $eventModel->applyJoin($this->uid);
			service_api('Credit', 'attend', array());
		}

		if($state)
		{
			$event['img'] = url_fdfs($event['fdfs_group'],$event['fdfs_filename']);
			api_api('Timeline', 'addTimeline',array(array(
			'uid'=>$this->uid,
			'dkcode'=>$this->dkcode,
			'uname'=>$this->username,
			'permission'=>4,
			'from'=>5,
			'type'=>'join',
			'dateline'=>time(),
			'ctime'=>time(),
			'fid'=>$event['id'],
			'title'=>$event['name'],
			'cover'=>$event['img']
			)));
		}

		$obj->status = 1;
		$obj->msg = '操作成功';
		doecho:
		$this->ajaxReturn($obj->data,$obj->msg,$obj->status);
	}

	/**
	 * ajax post
	 * 活动列表数据(翻页)
	 *
	 * 相关页面
	 *     我的活动列表页(他人)
	 *     其它活动列表页(他人)
	 *     己结束活动列表页(他人)
	 */
	public function doMoreList()
	{
		$obj = new stdClass();
		$obj->data = array();
		$page = (int)$this->input->post('page');
		$type = $this->input->post('eventType');

		if ($page < 1) {
			$page = 1;
		}
		$page--;

		$userEvents = new Domain\EventUser(array('uid'=>$this->action_uid));

		$offset = $page * $this->page_length;

		//比实际每页长度多取一条出来用来判断是否有下一页
		switch ($type) {
			default:
			case 'mylist':
				$rows = $userEvents->getEvents($offset, $this->page_length + 1);
				break;
			case 'other':
				$rows = $userEvents->getOtherEvents($offset, $this->page_length + 1);
				break;
			case 'endlist':
				$rows = $userEvents->getEndEvents($offset, $this->page_length + 1);
				break;
		}

		$hasMore = count($rows) > $this->page_length;

		//将多取的记录弹出
		if ($hasMore) {
			array_pop($rows);
		}

		$groups = array();
		foreach ($rows as $row) {
			if ($type=='endlist') {
				$group = date('Y-m', strtotime($row['starttime']));
			}
			else {
				$group = time_group($row['starttime']);
			}
			if ($row['event_type'] == 1) {
				if ($this->action_dkcode) {
					$row['url'] = mk_url('event/event/detail', array('id'=>$row['id'], 'dkcode'=>$this->action_dkcode));
				}
				else {
					$row['url'] = mk_url('event/event/detail', array('id'=>$row['id']));
				}
			}
			else {
				$row['url'] = mk_url('event/event/detail', array('id'=>$row['id'], 'web_id'=>$row['webid']), 'web');
			}

			$row['starttime'] = substr($row['starttime'], 0, 16);
			$row['endtime'] = substr($row['endtime'], 0, 16);

			$groups[$group][] = $row;
		}

		$obj->status = 1;
		$obj->page =  $type;

		$obj->data = array();

		$this->assign("page",$type);
		$tpl = 'tpl/list_li.tpl';

		foreach ($groups as $group => $rows) {
			$this->assign("rows",$rows);
			$obj->data[] = array(
				'name' => $group,
				'list' => $this->fetch($tpl, 'html'),
			);
		}

		$obj->isend = $hasMore ? 0 : 1;
		$data = array('data' => $obj->data ,'isend'=>$obj->isend,'status'=>$obj->status);
		$this->ajaxReturn($data);
	}

	/**
	 * ajax post
	 * 禁止用户参加活动
	 *
	 * 相关页面
	 *     活动详情页
	 */
	public function delGuest()
	{

		$obj = new stdClass();
		$obj->data = array();
		$eid = $this->input->post('eventid');
		$answer = $this->input->post('jointype');
		$uid = $this->input->post('uid');

		$eventModel = new Domain\Event();
		$event = $eventModel->getEvent($eid);

		if (empty($event)) {
			$obj->status = false;
			$obj->msg = '活动不存在';
			goto doecho;
		}

		//操作人
		$admin = $eventModel->getUser($this->uid);
		//被操作人
		$user = $eventModel->getUser($uid);

		if (empty($admin) || !$admin->canAdmin()) {
			$obj->status = false;
			$obj->msg = '您没有管理权限';
			goto doecho;
		}

		if (empty($user)) {
			$obj->status = false;
			$obj->msg = '该用户不存在';
			goto doecho;
		}

		$admin->blockUser($user);
		service_api('Credit', 'cancelAttend', array($uid));
		api_api('Timeline', 'removeMultiItem', array(array(
					'uid' => $uid,
					'type' => 'join',
					'index' => $eid , //活动ID
					'ctime' => time() //参加活动时间 没有作用
		)));
		$obj->status = 1;
		$obj->msg = '操作成功';
		doecho:
		$this->ajaxReturn($obj->data,$obj->msg,$obj->status);
	}
}
