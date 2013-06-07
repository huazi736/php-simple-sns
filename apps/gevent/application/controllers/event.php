<?php
use \Models as Model;
use \Domains as Domain;

/**
 * 活动控制器
 *
 * @author hpw
 * @date  2012/07/07
 */

class Event extends MY_Controller 
{
	public $page_length = 20;
	public $messages_length = 30;
	public $gid;
	public $domain;
	public $reg_url = "#(((http|https)://)?(\w+\.)+(\w+\-*[/%\?=&\.]*)+\s*)#";

	protected function _initialize()
	{
		
		$this->gid = intval($this->input->get_post('gid'));
		$this->user['avatar'] = get_avatar($this->uid);
		$this->assign('user', $this->user);
		$this->assign('event_a', mk_url('gevent/event/mylist',array('gid'=>$this->gid)));
		$this->assign('create_a', mk_url('gevent/event/create',array('gid'=>$this->gid)));
		$this->assign('mylist_a', mk_url('gevent/event/mylist',array('gid'=>$this->gid)));
		$this->assign('endlist_a', mk_url('gevent/event/endlist',array('gid'=>$this->gid)));
		$this->assign('detail_a', mk_url('gevent/event/detail',array('gid'=>$this->gid)));
		$this->assign('doMoreList_url', mk_url('gevent/event/doMoreList',array('gid'=>$this->gid)));
		$this->domain = ltrim(DOMAIN,'.');
	}
	
	/**
	 * 
	 */
	public function index()
	{
		$this->groupList();
	}

	/**
	 * 群组活动
	 */
	public function groupList()
	{
		$user_events = new Domain\EventUser(array('user_id'=>$this->uid));
		$rows = $user_events->getGroupEvents(0, 1, $this->gid);
		$this->assign('page', 'grouplist');
		$this->_list($rows);
		
	}

	/**
	 * 我的活动
	 */
	public function myList()
	{
		$user_events = new Domain\EventUser(array('user_id'=>$this->uid));
		$rows = $user_events->getMyEvents(0, 1, $this->gid);

		$this->assign('page', 'mylist');

		$this->_list($rows);
	}

	/**
	 * 结束的活动 
	 */
	public function endList()
	{
		$user_events = new Domain\EventUser(array('user_id'=>$this->uid));
		$rows = $user_events->getMyEvents(0, 1, $this->gid, true);
		$this->assign('page', 'endlist');

		$this->_list($rows);
	}
	
	/**
	 * 结束的活动 
	 */
	public function endGroupList()
	{
		$user_events = new Domain\EventUser(array('user_id'=>$this->uid));
		$rows = $user_events->getGroupEvents(0, 1, $this->gid, true);

		$this->assign('page','endgrouplist');
		$this->_list($rows);
	}
	

	private function _list($rows)
	{
		if(empty($rows))
			$view = $this->fetch('no_list.tpl');
		else
			$view = $this->fetch('list.tpl');
		$status=1;
		$return['data'] = $view;
		$this->ajaxReturn($return, '', $status, 'jsonp');
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
		

		$page = (int)$this->input->get_post('page');
		$type = $this->input->get_post('eventType');
		
		if ($page < 1) {
			$page = 1;
		}
		$page--;

		$userEvents = new Domain\EventUser(array('user_id'=>$this->uid));

		$offset = $page * $this->page_length;

		//比实际每页长度多取一条出来用来判断是否有下一页
		switch ($type) {
		default:
		case 'grouplist':
			$rows = $userEvents->getGroupEvents($offset, $this->page_length + 1, $this->gid);
			break;
		case 'mylist':
			$rows = $userEvents->getMyEvents($offset, $this->page_length + 1, $this->gid);
			break;
		case 'endlist':
			$rows = $userEvents->getMyEvents($offset, $this->page_length + 1, $this->gid, true);
			break;
		case 'endgrouplist':
			$rows = $userEvents->getGroupEvents($offset, $this->page_length + 1, $this->gid, true);
			break;
		}

		$hasMore = count($rows) > $this->page_length;

		//将多取的记录弹出
		if ($hasMore) {
			array_pop($rows);
		}

		$groups = array();
		foreach ($rows as $row)
		{
			if ($type=='endlist' || $type=='endgrouplist') 
				$group = date('Y-m', strtotime($row['starttime']));
			else 
				$group = time_group($row['starttime']);

			$row['url'] = mk_url('gevent/event/detail', array('id'=>$row['id'], 'gid'=>$row['group_id']));

			$row['starttime'] = substr($row['starttime'], 0, 16);
			$row['endtime'] = substr($row['endtime'], 0, 16);
			$row['status'] = strtotime($row['endtime'])>time()?'进行中':'结束';

			$groups[$group][] = $row;
		}
		$this->assign('page', $type);

		$this->assign('data', array());

		
		$tpl = 'tpl/list_li.tpl';

		foreach ($groups as $group => $rows)
		{
			$this->assign('rows', $rows);
			$return['data'][] = array(
				'name' => $group,
				'list' => $this->fetch($tpl),
			);
		}
		$status = 1;
		$return['isend'] = $hasMore ? 0 : 1;
		$this->ajaxReturn($return, '', $status, 'jsonp');
	}

	/**
	 * 创建活动表单页面
	 */
	public function create()
	{
		header('Cache-Control: no-cache, no-store');
		header('Pragma: no-cache');
		$this->assign('docreate_a', mk_url('gevent/event/docreate',array('gid'=>$this->gid)));
		$form = new Model\DetailForm();
		$token = $form->init_token();
		$this->assign('formToken', $token);
		$this->assign('page', 'create');
		$tpl = 'create.tpl';
		$return['data'] =  $this->fetch($tpl);
		$this->ajaxReturn($return, '', '', 'jsonp');
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
		$return = array();
		if (!$form->isValid())
		{
			$status = 0;
			$info = array_values($form->errors());
			$this->ajaxReturn($return, $info, $status);
		}
		
		$data = $form->get_data();

		//获取保存的临时信息
		$tmp_img = $form->get_keep('img');
		$tmp_img_s = $form->get_keep('img_s');
		$tmp_img_b = $form->get_keep('img_b');
		$eventModel = new Domain\Event();

		$event = $eventModel->create($this->uid,$this->gid, $data, $tmp_img, $tmp_img_s, $tmp_img_b);
		service_api('Credit', 'activity', array(true));
		//进行清理
		if ($tmp_img)
		{
			@unlink($tmp_img);
			@unlink($tmp_img_s);
			@unlink($tmp_img_b);
		}

		//销毁表单
		$form->destroy();
		echo '<script>';
		echo 'document.domain="'.$this->domain.'";';
		echo 'window.parent.creatEventComplete(' . $this->getContent($event) . ')';
		echo '</script>';
	}

	/**
	 * 编辑活动表单页面
	 */
	public function edit()
	{
		header('Cache-Control: no-cache, no-store');
		header('Pragma: no-cache');

		$eid = (int)$this->input->get_post('eid');

		$eventModel = new Domain\Event();

		$event = $eventModel->getEvent($eid, $this->gid);

		if (empty($event))
			$this->error('指定的活动不存在');

		$userModel = $eventModel->getUser($this->uid);

		if (!$userModel && !$userModel->canAdmin())
			$this->error('您没有权限进行此操');

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

		$event['img'] = url_fdfs($event['fdfs_group'], $event['fdfs_filename']);
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
		
		$this->assign('event', $event);

		$this->assign('doedit_a', mk_url('gevent/event/doedit',array('gid'=>$this->gid)));

		$form = new Model\DetailForm();

		$token = $form->init_token();

		$form->keep('eid', $eid);

		$this->assign('page', 'edit');
		$this->assign('formToken', $token);
		$status = 1;
		$return['data'] = $this->fetch('edit.tpl');
		$this->ajaxReturn($return, '', $status, 'jsonp');
		
	}

	/**
	 * 编辑活动保存
	 */
	public function doEdit()
	{	
		$token = $this->input->get_post('eventid');

		$posts = $this->input->post();
		$form = new Model\DetailForm($posts);

		$form->set_token($token);
		
		if (!$form->isValid())
			$this->error($form->errors());

		$eid = $form->get_keep('eid');

		$eventModel = new Domain\Event();

		$event = $eventModel->getEvent($eid, $this->gid);
		if ($event['user_id'] != $this->uid)
			$this->error('您没有权限进行此操');	

		if (empty($event))
			$this->error('指定的活动不存在');

		$data = $form->get_data();

		//获取保存的临时信息
		$tmp_img = $form->get_keep('img');
		$tmp_img_s = $form->get_keep('img_s');
		$tmp_img_b = $form->get_keep('img_b');

		$eventModel->edit($data, $tmp_img, $tmp_img_s, $tmp_img_b);

		//进行清理
		if ($tmp_img) {
			@unlink($tmp_img);
			@unlink($tmp_img_s);
			@unlink($tmp_img_b);
		}

		//销毁表单
		$form->destroy();
		echo '<script>';
		echo 'document.domain="'.$this->domain.'";';
		echo 'window.parent.editEventComplete(' . $this->getContent($eid) . ')';
		echo '</script>';
	}

	/**
	 * 活动详情
	 */
	public function detail()
	{
		$eid = (int)$this->input->get('id');

		$eventModel = new Domain\Event();

		$event = $eventModel->getEvent($eid, $this->gid);

		if (empty($event)) 
			$this->error('该活动不存在');

		//当前用户
		$userModel = $eventModel->getUser($this->uid);

		//检测当前用户是否具有活动的管理权限
		$is_admin = (!empty($userModel) && $userModel->canAdmin()) ? true : false;

		if ($userModel)
			$current_user = $userModel;
		else 
		{
			$current_user = $this->user;
			$current_user['type'] = '-1';
		}

		$edit_a = mk_url('gevent/event/edit', array('id'=>$eid,'gid'=>$this->gid));

		$replyImg_a = mk_url('gevent/event/replyImg', array('eid'=>$eid, 'gid'=>$this->gid));

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
		$event['img'] = url_fdfs($event['fdfs_group'], $event['fdfs_filename']);
		$serviceRe = service_api('User', 'getUserInfo', array($event['user_id'], 'uid', array('dkcode','username')));
		$event['create']['url'] = mk_url('main/index/main', array('dkcode'=>$serviceRe['dkcode']));
		$event['create']['username'] = $serviceRe['username'];
		$this->assign('sessionid',Model\My_Session::session_id());
		$this->assign('is_show_users',$is_show_users);
		$this->assign('current_user',$current_user);
		$this->assign('edit_a',$edit_a);
		$this->assign('is_admin',$is_admin);
		$this->assign('event',$event);
		$this->assign('replyImg_a',$replyImg_a);
		$status = 1;
		$return['data'] = $this->fetch('detail.tpl');
		$this->ajaxReturn($return, '',$status, 'jsonp');
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

		$eid = (int)$this->input->get_post('eid');
		$page = (int)$this->input->get_post('page') - 1;
		if ($page < 0)
			$page = 0;

		$eventModel = new Domain\Event();

		$event = $eventModel->getEvent($eid, $this->gid);
		if (empty($event))
			$this->error('该活动不存在');

		$userModel = $eventModel->getUser($this->uid);

		$is_admin =  !empty($userModel) && $userModel->canAdmin();

		$offset = $page * $this->messages_length;

		$rows = $eventModel->getMessages($offset, $this->messages_length + 1);

		$hasMore = false;
		if (count($rows) > $this->messages_length) 
		{
			$hasMore = true;
			array_pop($rows);
		}

		

		$dkcode = array();
		foreach ($rows as $row) {
			$dkcode[$row['user_id']] = null;
		}

		if (!empty($dkcode))
		{
			$tmps = service_api('User','getUserList',array(array_keys($dkcode), array('uid', 'dkcode')));
			foreach ($tmps as $row) 
				$dkcode[$row['uid']] = $row['dkcode'];
		}

		$data = array();
		foreach ($rows as $row) {
			$tmp = array(
				'fid' => $row['id'],
				'tid' => $row['id'],
				'action_uid' => $row['user_id'],
				'can_del' => ($is_admin || $row['user_id'] == $this->uid),
				'link' => false,
				'avatar' => get_avatar($row['user_id']),
				'code' => mk_url('main/index/main', array('dkcode' => $dkcode[$row['user_id']])),
				'username' => $row['username'],
				'message' => $row['message'],
				'addtime' => friendlyDate(strtotime($row['addtime'])),
				'image' => false,
				'video' => false,
			);
			$tmp['message'] = str_replace('&amp;', '&', $tmp['message']);
			$tmp['message'] = preg_replace($this->reg_url, '<a target="_bank" href="\1">\1</a>', $tmp['message']);

			if ($row['type'] == 2) {
				$tmp['image'] = url_fdfs($row['group'], $row['filename']);
			}

			if ($row['type'] == 3) {
				$tmp['video'] = array_combine(array('imgurl', 'videourl'), explode(';', $row['src']));
			}

			$data[] = $tmp;
		}
		$status = 1;
		$return['data'] = $data;
		$return['isend'] = $hasMore ? 0 : 1;
		$this->ajaxReturn($return, '', $status, 'jsonp');
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

		$eid = (int)$this->input->get_post('eventid');
		$msg = $this->input->get_post('message');
		$msg = htmlspecialchars($msg);
		$eventModel = new Domain\Event();
		$event = $eventModel->getEvent($eid, $this->gid);
		$return = array();
		$info = '';
		if (empty($event))
		{
			$info = '该活动不存在';
			$status = 0;
			goto echothere;
		}

		/*$userModel = $eventModel->getUser($this->uid);

		if (empty($userModel))
		{
			$obj->status = 0;
			$obj->data = '您没有参与该活动';
			ajaxReturn($obj);
		}

		*/

		$new_id = $eventModel->addMessage(1, $msg, '', $this->uid);

		$msg = str_replace('&amp;','&',$msg);
		$msg = preg_replace($this->reg_url, '<a tagrget="_bank" href="\1">\1</a>', $msg);
		$data = array(
			'fid' => $new_id,
			'tid' => $new_id,
			'action_uid' => $this->uid,
			'link' => false,
			'avatar' => get_avatar($this->uid),
			'code' => mk_url('main/index/main', array('dkcode' => $this->uid)),
			'username' => $this->user['username'],
			'addtime' => '刚刚',
			'message' => $msg,
			'image' => false,
			'video' => false,
			'can_del' => true,
		);
		$status = 1;
		$return['data'] = $data;
		echothere:
		$this->ajaxReturn($return, $info, $status, 'jsonp');
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
		$obj = new stdClass();
		$eid = (int)$this->input->get('eid');

		$msg = $this->input->get_post('distributeAttachIntro');
		$msg = trim($msg);
		$msg = htmlspecialchars($msg);

		$eventModel = new Domain\Event();
		$event = $eventModel->getEvent($eid, $this->gid);

		if (empty($event))
		{
			$status = 0;
			$obj->info = '该活动不存在';
			goto do_echo;
		}

		/*$userModel = $eventModel->getUser($this->uid);

		if (empty($userModel))
		{
			$obj->status = 0;
			$obj->data = '您没有参与该活动';
			ajaxReturn($obj);
		}

*/
		if (!isset($_FILES['uploadPhotoFile']) || !Model\ImageModel::isValid($_FILES['uploadPhotoFile']['tmp_name']))
		{
			$status = 0;
			$obj->info = '您上传的图片不合法';
			goto do_echo;
		}

		$file1 = tempnam('/tmp', 'reply_img_');

		try{
			$img = new Model\ImageModel($_FILES['uploadPhotoFile']['tmp_name']);


			$img->maybeReSizeToFile($file1, 403, 403, 'jpg');
			unset($img);
		}
		catch(Exception $e)
		{
			$obj->status = 0;
			$obj->info = '您上传的图片不合法';
			goto do_echo;
		}

		list($new_id, $img_url) = $eventModel->addMessage(2, $msg, $file1, $this->uid);

		@unlink($file1);

		$obj->status = 1;
		$msg = str_replace('&amp;','&',$msg);
		$msg = preg_replace($this->reg_url, '<a tagrget="_bank" href="\1">\1</a>', $msg);

		$data = array(
			'fid' => $new_id,
			'tid' => $new_id,
			'action_uid' => $this->uid,
			'link' => false,
			'avatar' => get_avatar($this->uid),
			'code' => mk_url('main/index/main', array('dkcode' => $this->uid)),
			'username' => $this->user['username'],
			'addtime' => '刚刚',
			'message' => $msg,
			'image' => $img_url,
			'video' => false,
			'can_del' => true,
		);

		$obj->data = $data;

		do_echo:

		echo '<script>';
		echo 'document.domain="'.$this->domain.'";';
		echo 'window.parent.sendPhotoComplete(' . json_encode($obj) . ')';
		echo '</script>';
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

		$eid = (int)$this->input->get_post('eventid');
		$rid = (int)$this->input->get_post('replyid');
		$return = array();
		if (!$eid || !$rid)
		{
			$status = 0;
			$info = '参数错误';

			goto do_echo;
		}

		$eventModel = new Domain\Event();

		$event = $eventModel->getEvent($eid, $this->gid);

		if (empty($event))
		{
			$status = 0;
			$info = '活动不存在';
			goto do_echo;
		}

		$messageModel = $eventModel->getMessage($rid);

		if (empty($messageModel)) 
		{
			$status = 0;
			$info = '留言不存在';
			goto do_echo;
		}

		$userModel = $eventModel->getUser($this->uid);

		if (!empty($userModel))
			$isAdmin = $userModel->canAdmin();
			

		//检查权限
		if ($messageModel['user_id'] != $this->uid && !isset($isAdmin)) {
			$status = 0;
			$info = '您没有权限删除';
			goto do_echo;
		}

		$messageModel->del();

		$status = 1;
		$info = '删除成功';

		do_echo:
		$this->ajaxReturn($return, $info, $status, 'jsonp');
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
		$eid = (int)$this->input->get_post('eid');

		$eventModel = new Domain\Event();
		$event = $eventModel->getEvent($eid,$this->gid);

		if (empty($event))
		{
			$status = 0;
			$info = '该活动不存在';
			goto echothere;
		}

		$userModel = $eventModel->getUser($this->uid);

		if (empty($userModel) || !$userModel->canAdmin())
		{
			$status = 0;
			$info = '您没有权限进行此操';
			goto echothere;
		}

		$eventModel->cancel();
		service_api('Credit', 'activity', array(false));
		$status = 1;
		$info = '操作成功';
		$return = array();
		echothere:
		$this->ajaxReturn($return, $info, $status, 'jsonp');
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
		
		$eid = (int)$this->input->get_post('eid');
		$answer = (int)$this->input->get_post('jointype');

		$eventModel = new Domain\Event();
		$event = $eventModel->getEvent($eid, $this->gid);

		$user = $eventModel->getUser($this->uid);
		$canAdmin = (!empty($user) && $user->canAdmin()) ? true : false;
		if (empty($event))
			$this->error('活动不存在');


		//检查当前活列表是否可以显示
		if ($eventModel->isShowUsers())
			$users = $eventModel->getUsers();
		else 
			$users = array();

		$datas = array();
		foreach ($users as $user) {
			if($user['type']<2)
				continue;
			$datas[] = array(
				'uid' => $user['id'],
				'link' => url_home($user['dkcode']),
				'userhead' => get_avatar($user['id']),
				'username' => $user['name'],
				'type' => $user['type'],
			);
		}

		//参加人数
		$return['allnum'] = count($users);
		$return['canAdmin'] = $canAdmin;
		$status = 1;
		$info = '';
		$return['data'] = $datas;
		
		$this->ajaxReturn($return, $info, $status, 'jsonp');
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

		$token = $this->input->get_post('eventid');

		$form = new Model\DetailForm();
		$obj = new stdClass();

		if (!$form->set_token($token))
		{
			$obj->status = 0;
			$obj->info = '口令错误'.var_export($_SESSION['forms'], true);
		}
		else if (!isset($_FILES['uploadPhotoFile']))
		{
			$obj->status = 0;
			$obj->info = '上传名称错误或未上传';
		}
		else if ($_FILES['uploadPhotoFile']['error'] != UPLOAD_ERR_OK)
		{
			$obj->status = 0;
			$obj->info = '上传文件错误,请检查文件是否过大';
		}
		else if (!is_uploaded_file($_FILES['uploadPhotoFile']['tmp_name']))
		{
			$obj->status = 0;
			$obj->info = '文件上传错误';
		}
		else if ($_FILES['uploadPhotoFile']['size'] > 1024 * 1024 * 4)
		{
			$obj->status = 0;
			$obj->info = '文件太大';
		}
		else if (!Model\ImageModel::isValid($_FILES['uploadPhotoFile']['tmp_name']))
		{
			$obj->status = 0;
			$obj->info = '您上传的图片格式我们不支持或不是一个有效的图片';
		}
		else 
		{
			try
			{
				
				$img = new Model\ImageModel($_FILES['uploadPhotoFile']['tmp_name']);
	
				$file1 = "tmp/gevent/{$token}.jpg";
				$file2 = "tmp/gevent/{$token}_s.jpg";
				$file3 = "tmp/gevent/{$token}_b.jpg";

				$img->reSizeToFile(VAR_PATH . $file1, 180, 150, 'jpg');
				$img->reSizeToFile(VAR_PATH . $file2, 112, 92, 'jpg');
				$img->resizeCrop(VAR_PATH . $file3, 90, 60, 'jpg');
	
				$form->keep('img', VAR_PATH.$file1);
				$form->keep('img_s', VAR_PATH.$file2);
				$form->keep('img_b',VAR_PATH.$file3);
	
				//图片很占内存,就地干掉
				unset($img);
	
				$obj->status = 1;
				$obj->eventPhoto = WEB_ROOT.'var'.DS.$file1 . '?' . rand();
				}
			catch(Exception $e)
			{
				$obj->status = 0;
				$obj->info = '您上传的文件不是一个有效的图片';
			}
			
			
		}
		header("Content-Type:text/html; charset=utf-8");
		echo '<script>';
		echo 'document.domain="'.$this->domain.'";';
		echo 'window.parent.sendPhotoComplete('.json_encode($obj).');';
		echo '</script>';
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
		$eid = (int)$this->input->get_post('eid');
		$state = (int)$this->input->get_post('status');
		
		$answerArr = array(1, 2);
		
		if (!$eid || !in_array($state, $answerArr)) 
		{
			$status = 0;
			$info = '操作错误';
			goto do_echo;
		}
		$eventModel = new Domain\Event();
		$event = $eventModel->getEvent($eid, $this->gid);

		if (empty($event)) 
		{
			$status = 0;
			$info = '活动不存在';
			goto do_echo;
		}

		$userModel = $eventModel->getUser($this->uid);
		if (!empty($userModel))
		{
			if($userModel['type']==0)
			{
				$status = 0;
				$info = '你被禁止参加该活动';
				goto do_echo;
			}
			else
			{
				$userModel->changeAnswer($state);
				if($state==2)
					service_api('Credit', 'attend', array());
				else
					service_api('Credit', 'cancelAttend', array($this->uid));
			}
			
		}
		else 
		{
			$d_user = $eventModel->applyJoin($this->uid, $this->gid);
			service_api('Credit', 'attend', array());
		}
		
		$status = 1;
		$info = '操作成功';		
		if($status)
			exit($_REQUEST['callback'].'('.$this->getContent($eid).')');

		do_echo:
		$return = array();
		$this->ajaxReturn($return, $info, $status, 'jsonp');
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
		$eid = $this->input->get_post('eid');
		$answer = $this->input->get_post('jointype');
		$uid = $this->input->get_post('uid');

		$eventModel = new Domain\Event();
		$event = $eventModel->getEvent($eid, $this->gid);

		if (empty($event)) {
			$status = 0;
			$info = '活动不存在';
			goto do_echo;
		}

		//操作人
		$admin = $eventModel->getUser($this->uid);
		//被操作人
		$user = $eventModel->getUser($uid);

		if (empty($admin) || !$admin->canAdmin())
		{
			$status = 0;
			$info = '你无权限';
			goto do_echo;
		}

		if (empty($user)) 
		{
			$status = 0;
			$info = '用户不存在';
			goto do_echo;
		}

		$admin->blockUser($user);
		service_api('Credit', 'cancelAttend', array($uid));
		$status = 1;
		$info = '操作成功';
		do_echo:
		$return = array();
        $this->ajaxReturn($return, $info, $status, 'jsonp');
	}


	protected function getContent($eid)
	{

		$eventModel = new Domain\Event();

		$event = $eventModel->getEvent($eid, $this->gid);

		//当前用户
		$userModel = $eventModel->getUser($this->uid);

		$is_admin =  true ;
		$current_user = $userModel;

		$edit_a = mk_url('gevent/event/edit', array('id'=>$eid, 'gid'=>$this->gid));

		$replyImg_a = mk_url('gevent/event/replyImg', array('eid'=>$eid, 'gid'=>$this->gid));

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
		$event['img'] = url_fdfs($event['fdfs_group'], $event['fdfs_filename']);
		$serviceRe = service_api('User', 'getUserInfo', array($event['user_id'], 'uid', array('dkcode','username')));
		$event['create']['url'] = mk_url('main/index/main', array('dkcode'=>$serviceRe['dkcode']));
		$event['create']['username'] = $serviceRe['username'];
		$this->assign('sessionid',Model\My_Session::session_id());
		$this->assign('is_show_users',$is_show_users);
		$this->assign('current_user',$current_user);
		$this->assign('edit_a',$edit_a);
		$this->assign('is_admin',$is_admin);
		$this->assign('event',$event);
		$this->assign('replyImg_a',$replyImg_a);
		$obj->status = 1;
		$obj->data = $this->fetch('detail.tpl');
		header("Content-Type:text/html; charset=utf-8");
        return json_encode($obj);
		
	}
	

}
