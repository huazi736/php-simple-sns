<?php
use \Models as Model;
use \Domains as Domain;

/**
 * 活动控制器
 * @author hpw
 * @date 2012/07/09
 */
class Event extends MY_Controller 
{
	public $page_length = 20;
	public $messages_length = 30;
	public $isCreate = false;
	public $reg_url = "#(((http|https)://)?(\w+\.)+(\w+\-*[/%\?=&\.]*)+\s*)#";
	
	protected function _initialize()
	{
		$this->user['avatar'] = get_avatar($this->user['uid']);	
		if ($this->web_id)
		{
			if(isset($this->web_info[0]))
				$this->webinfo = $this->web_info[0];
			if(!isset($this->web_info['uid']))
				$this->error('该网页不存在');
			$this->web_info['avatar'] = get_webavatar($this->web_info['aid']);
		}
		else
			$this->error('该网页不存在');
			
		$this->isCreate = ($this->web_info['uid'] == $this->uid);
		
		$this->assign('webinfo', $this->web_info);
		$this->assign('user', $this->user);
		$this->assign('page', '');
		$this->assign('dev_main_a', url_home($this->user['dkcode']));
		$this->assign('main_a', mk_url('webmain/index/main', array('web_id'=>$this->web_id)));
		$this->assign('is_create',$this->isCreate);
		$this->assign('event_a', mk_url('wevent/event/mylist',array('web_id'=>$this->web_id)));
		$this->assign('create_a', mk_url('wevent/event/create',array('web_id'=>$this->web_id)));
		$this->assign('mylist_a', mk_url('wevent/event/mylist',array('web_id'=>$this->web_id)));
		$this->assign('endlist_a', mk_url('wevent/event/endlist',array('web_id'=>$this->web_id)));
		$this->assign('detail_a', mk_url('wevent/event/detail',array('web_id'=>$this->web_id)));
		$this->assign('doMoreList_url', mk_url('wevent/event/doMoreList',array('web_id'=>$this->web_id)));
	}
	
	/**
	 * 
	 */
	public function index()
	{
		$this->mylist();
	}


	/**
	 * 活动列表
	 */
	public function mylist()
	{
		$event = new Domain\Events($this->web_info, $this->user);
		$row = $event->getEvents(0, 1);
		$counts = $event->getEventCount();
		$this->assign('page', 'mylist');
		$this->assign('counts',$counts);
		$this->_list($row);
	}

	/**
	 * 结束的活动 
	 */
	public function endlist()
	{
		$user_events = new Domain\Events($this->web_info, $this->user);
		$rows = $user_events->getEvents(0, 1, true);
		$counts = $user_events->getEventCount(true);
		$this->assign('counts',$counts);
		$this->assign('page', 'endlist');

		$this->_list($rows);
	}

	private function _list($rows)
	{
		if (empty($rows)) {
			if($this->isCreate)
				$this->display('no_list.tpl');
			else
				$this->display('visitor/no_list.tpl');
		}
		else {
			$this->display('list.tpl');
		}
	}
	
	/**
	 * 活动列表数据(翻页)
	 * 进行|结束活动列表页(他人)
	 */
	public function doMoreList()
	{
		$page = (int)$this->input->post('page');
		$type = $this->input->post('eventType');

		if ($page < 1)
			$page = 1;
		$page--;

		$eventModel = new Domain\Events($this->web_info, $this->user);

		$offset = $page * $this->page_length;

		//比实际每页长度多取一条出来用来判断是否有下一页
		switch ($type)
		{
			default:
			case 'mylist':
				$rows = $eventModel->getEvents($offset, $this->page_length + 1);
				break;
			case 'endlist':
				$rows = $eventModel->getEvents($offset, $this->page_length + 1,true);
				break;
		}
		$hasMore = count($rows) > $this->page_length;

		//将多取的记录弹出
		if ($hasMore) 
			array_pop($rows);

		$groups = array();

		foreach ($rows as $row)
		{
			if ($type=='endlist') {
				$group = date('Y-m', strtotime($row['starttime']));
			}
			else {
				$group = time_group($row['starttime']);
			}

			$row['starttime'] = substr($row['starttime'], 0, 16);
			$row['endtime'] = substr($row['endtime'], 0, 16);

			$groups[$group][] = $row;
		}

		$this->assign('page', $type);

		$return['data'] = array();
		$tpl = 'tpl/list_li.tpl';

		foreach ($groups as $group => $rows) {
			$this->assign('rows', $rows);
			$return['data'][] = array(
				'name' => $group,
				'list' => $this->fetch($tpl),
			);
		}
		$return['isend'] = $hasMore ? 0 : 1;
        $this->ajaxReturn($return);
	}

	/**
	 * 创建活动表单页面
	 */
	public function create()
	{

		header('Cache-Control: no-cache, no-store');
		header('Pragma: no-cache');
		$this->assign('docreate_a', mk_url('wevent/event/docreate',array('web_id'=>$this->web_info['aid'])));
		$form = new Model\DetailForm();
		$token = $form->init_token();

		$this->assign('formToken', $token);

		$this->assign('page', 'create');
		$this->display('create.tpl');
	}

	/**
	 * post
	 * 实际创建活动
	 */
	public function doCreate()
	{
		if (!$this->isCreate)
			$this->error('您没有权限进行此操');

		$form = new Model\DetailForm($this->input->post());
		$token = $this->input->post('eventid');
		$form->set_token($token);
		if (!$form->isValid())
			$this->error($form->errors());

		$data = $form->get_data();
		//获取保存的临时信息
		$tmp_img = $form->get_keep('img');
		$tmp_img_s = $form->get_keep('img_s');
		$tmp_img_b = $form->get_keep('img_b');

		$eventModel = new Domain\Events($this->web_info, $this->user);
		$event = $eventModel->create($data, $tmp_img, $tmp_img_s, $tmp_img_b);
		service_api('Credit', 'activity', array(true));
		
		//进行清理
		if ($tmp_img) {
			@unlink($tmp_img);
			@unlink($tmp_img_s);
			@unlink($tmp_img_b);
		}

		//销毁表单
		$form->destroy();

		$this->redirect('wevent/event/detail', array('id'=>$event['id'],'web_id'=>$this->web_info['aid']));
	}

	/**
	 * 编辑活动表单页面
	 */
	public function edit()
	{

		header('Cache-Control: no-cache, no-store');
		header('Pragma: no-cache');

		$eid = (int)$this->input->get('id');
		
		
		$eventModel = new Domain\Events($this->web_info, $this->user);

		$event = $eventModel->getEvent($eid);

		if (empty($event))
			$this->error('指定活动不存在');

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

		$this->assign('doedit_a', mk_url('wevent/event/doedit',array('web_id'=>$this->web_info['aid'])));

		$form = new Model\DetailForm();

		$token = $form->init_token();

		$form->keep('eid', $eid);

		$this->assign('page', 'edit');
		$this->assign('formToken', $token);
		
		$this->display('edit.tpl');
	}

	/**
	 * 编辑活动保存
	 */
	public function doEdit()
	{
		if (!$this->isCreate)
			$this->error('您没有权限进行此操');	
		
		$token = $this->input->post('eventid');

		$posts = $this->input->post();
		$form = new Model\DetailForm($posts);

		$form->set_token($token);

		if (!$form->isValid()) 
			$this->error($form->errors());
	

		$eid = $form->get_keep('eid');

		$eventModel = new Domain\Events($this->web_info, $this->user);

		$event = $eventModel->getEvent($eid);

		if (empty($event))
			$this->error('指定活动不存在');	
		if($event->row['webid']!=$this->web_info['aid'])
			$this->error('您没有权限进行此操');

		$data = $form->get_data();

		//获取保存的临时信息
		$tmp_img = $form->get_keep('img');
		$tmp_img_s = $form->get_keep('img_s');
		$tmp_img_b = $form->get_keep('img_b');

		$event->edit($data, $tmp_img, $tmp_img_s, $tmp_img_b);

		//进行清理
		if ($tmp_img) {
			@unlink($tmp_img);
			@unlink($tmp_img_s);
			@unlink($tmp_img_b);
		}

		//销毁表单
		$form->destroy();

		$this->redirect('wevent/event/detail', array('id'=>$eid,'web_id'=>$this->web_info['aid']));
	}

	/**
	 * 添动详情
	 */
	public function detail()
	{
		$eid = (int)$this->input->get('id');

		$eventModel = new Domain\Events($this->web_info, $this->user);

		$event = $eventModel->getEvent($eid);

		if (empty($event))
			$this->error('指定的活动不存在');	
			
		//得到所有参与者
		$users = $event->getUsers();

		//当前用户
		$c_user = $event->getUser($this->uid,false);

		/*
		 * 对参与人员以回复结果分组
		 */
		$event_users = array(
			'event_users_sure' => array('type1' => array(), 'type2' => array(), 'num' => 0, 'online' => false),
		);

		if (!$c_user) {
			$c_user = $this->user;
			$c_user['type'] = '-2';
			$c_user['answer'] = '-2';
		}

		//得到参与者数量
		foreach ($users as $user) 
		{
			if($user['answer']!=2)
				continue;
			$event_users['event_users_sure']['num']++;

			//最多10个人
			if ($event_users['event_users_sure']['num'] < 11)
			{
				array_push($event_users['event_users_sure']['type2'], $user);
			}

			$event_users['event_users_sure']['online'] = true;
		}

		$edit_a = mk_url('wevent/event/edit', array('id'=>$eid,'web_id'=>$this->web_info['aid']));

		$replyImg_a = mk_url('wevent/event/replyImg', array('eventid'=>$eid, 'web_id'=>$this->web_info['aid']));

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
		$this->assign('is_create',$this->isCreate);
		$this->assign('current_user',$c_user);
		$this->assign('sessionid', Model\My_Session::session_id());
		$this->assign('edit_a', $edit_a);
		$this->assign('event', $event);
		$this->assign('is_show_users', $event->isShowUsers());
		$this->assign('replyImg_a', $replyImg_a);
		$this->assign('event_users_sure', $event_users['event_users_sure']);
		$this->display('detail.tpl');
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
		$eid = (int)$this->input->post('eventid');
		$state = (int)$this->input->post('status');

		$answerArr = array(0, 2); // [0不参加] [2参加]
		
		if (!$eid || !in_array($state, $answerArr))
		{
			$status = 0;
			$info = '指定活动不存在';
			goto do_echo;
		}

		$d_events = new Domain\Events($this->web_info, $this->user);
		$d_event = $d_events->getEvent($eid);

		if (empty($d_event))
		{
			$status = 0;
			$info = '指定活动不存在';
			goto do_echo;

		}

		$d_user = $d_event->getUser($this->uid);

		if (!empty($d_user)) 
		{
			if ($d_user['type'] == '-1')
			{
				$status = 0;
				$info = '您被禁止参加此活动';
				goto do_echo;
			}
			else 
			{
				$d_user->changeAnswer($state);
				if($state==2)
					service_api('Credit', 'attend', array());
				else
					service_api('Credit', 'cancelAttend', array($this->uid));
			}
		}
		else
		{
			$d_user = $d_event->applyJoin($this->uid);
			service_api('Credit', 'attend', array());

		}
		$status = 1;
		$info = '操作成功';		
		do_echo:
		$return = array();
        $this->ajaxReturn($return, $info, $status);
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
		$eid = (int)$this->input->post('eventid');
		$page = (int)$this->input->post('page') - 1;
		if ($page < 0) 
			$page = 0;
		$return = array();
		if (!$eid)
		{
			do404:
			$status = 0;
			$info = '指定的活动不存在';
			$this->ajaxReturn($return, $info, $status);
		}

		$eventModel = new Domain\Events($this->web_info, $this->user);

		$event = $eventModel->getEvent($eid);

		if (empty($event))
			goto do404;
		
		$offset = $page * $this->messages_length;
		
		$rows = $event->getMessages($offset, $this->messages_length + 1);

		$userIdArr = array();
		foreach ($rows as $row)
		{
			if ($row['uid']) {
				$userIdArr[] = $row['uid'];
			}
		}
		if(!empty($userIdArr))
		{
			$userInfoArr = service_api('User', 'getUserList', array($userIdArr));
			foreach($userInfoArr as $one)
			{
				$userInfo[$one['uid']]['username'] = $one['username']; 
				$userInfo[$one['uid']]['dkcode'] = $one['dkcode']; 
			}
		}
		$hasMore = false;
		if (count($rows) > $this->messages_length)
		{
			$hasMore = true;
			array_pop($rows);
		}

		$status = 1;

		$data = array();

		foreach ($rows as $row)
		{
			if ($row['uid']!=$this->web_info['uid']) 
				$code = mk_url('main/index/main', array('dkcode' => $userInfo[$row['uid']]['dkcode']));
			else 
				$code = mk_url('webmain/index/main', array('web_id' => $this->web_info['aid']));
			$tmp = array(
				'fid' => $row['id'],
				'tid' => $row['id'],
				'action_uid' => $row['uid'],
				'can_del' => ($this->isCreate || $row['uid'] == $this->uid) ? true : false,
				'link' => false,
				'avatar' => $row['uid']==$this->web_info['uid'] ? $this->web_info['avatar']:get_avatar($row['uid']),
				'code' => $code,
				'username' => ($row['uid'] == $this->web_info['uid'] || $row['uid'] == 0) ? $this->web_info['name'] : $userInfo[$row['uid']]['username'],
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

			$data[] = $tmp;
		}

		$return['data'] = $data;
		$return['isend'] = $hasMore ? 0 : 1;

		$this->ajaxReturn($return);
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
		$eid = (int)$this->input->post('eventid');
		$msg = $this->input->post('message');
		$msg = htmlspecialchars($msg);

		$return = array();

		$d_events = new Domain\Events($this->web_info, $this->user);
		$d_event = $d_events->getEvent($eid);

		if (empty($d_event))
		{
			$status = 0;
			$info = '指定的活动不存在';
			$this->ajaxReturn($return, $info, $status);
		}
			
		
		$new_id = $d_event->addMessage(1, $msg);

		$msg = str_replace('&amp;','&',$msg);

 		$msg = preg_replace($this->reg_url, '<a tagrget="_bank" href="\1">\1</a>', $msg);

		if ($this->isCreate) {
			$url = mk_url('webmain/index/main', array('web_id' => $this->web_info['aid']));
			$name = $this->web_info['name'];
		}
		else {
			$url = mk_url('main/index/main', array('dkcode' => $this->dkcode));
			$name = $this->user['username'];
		}

		$data = array(
			'fid' => $new_id,
			'tid' => $new_id,
			'action_uid' => $this->uid,
			'link' => false,
			'avatar' => $this->isCreate ? $this->web_info['avatar'] : get_avatar($this->uid),
			'code' => $url,
			'username' => $name,
			'message' => $msg,
			'addtime' => '刚刚',
			'image' => false,
			'video' => false,
			'can_del' => true,
		);
		$status = 1;
		$return['data'] = $data;

		$this->ajaxReturn($return);
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
		$token = (int)$this->input->post('tokenShareDestinations');
		$hash = $this->input->post('__hash__');

		$eid = (int)$this->input->get('eventid');

		$msg = $this->input->post('distributeAttachIntro');
		$msg = trim($msg);
		$msg = htmlspecialchars($msg);

		$return = array();

		$d_events = new Domain\Events($this->web_info, $this->user);
		$d_event = $d_events->getEvent($eid);

		if (empty($d_event))
		{
			$obj->status = 0;
			$obj->info = '指定的活动不存在';
			goto do_echo;
		}


		if (!isset($_FILES['uploadPhotoFile']) || !Model\ImageModel::isValid($_FILES['uploadPhotoFile']['tmp_name'])) {
			$obj->status = 0;
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
			unset($img);
		}
		catch(Exception $e)
		{
			$obj->status = 0;
			$obj->info = '您上传的图片不合法';

			goto do_echo;
		}
		list($new_id, $img_url) = $d_event->addMessage(2, $msg, $file1);

		@unlink($file1);

		$obj->status = 1;
		$msg = str_replace('&amp;','&',$msg);
		$msg = preg_replace($this->reg_url, '<a tagrget="_bank" href="\1">\1</a>', $msg);

		if ($this->isCreate) {
			$url = mk_url('webmain/index/main', array('web_id' => $this->web_info['aid']));
			$name = $this->web_info['name'];
		}
		else {
			$url = mk_url('main/index/main', array('dkcode' => $this->dkcode));
			$name = $this->user['username'];
		}
		
		$data = array(
			'fid' => $new_id,
			'tid' => $new_id,
			'action_uid' => $this->uid,
			'link' => false,
			'avatar' => $this->isCreate ? $this->web_info['avatar'] : get_avatar($this->uid),
			'code' => $url,
			'username' => $name,
			'addtime' => '刚刚',
			'message' => $msg,
			'image' => $img_url,
			'video' => false,
			'can_del' => true,
		);

		$obj->data = $data;

		do_echo:

		echo '<script>';
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
		$eid = (int)$this->input->post('eventid');
		$rid = (int)$this->input->post('replyid');
		$return = array();
		if (!$eid || !$rid)
		{
			$status = 0;
			$info = '参数错误';
			goto do_echo;
		}

		$eventModel = new Domain\Events($this->web_info, $this->user);

		$event = $eventModel->getEvent($eid);

		if (empty($event))
		{
			$status = 0;
			$info = '活动不存在';
			goto do_echo;
		}

		$message = $event->getMessage($rid);

		if (empty($message))
		{
			$status = 0;
			$info = '留言不存在';
			goto do_echo;
		}

		//检查权限
		if ($event->row['webid']!=$this->web_info['aid'] && $message['uid'] != $this->uid)
		{
			$status = 0;
			$info = '您没有权限删除';
			goto do_echo;
		}

		$message->del($rid);

		$obj = new stdClass();

		$status = 1;
		$info = '删除成功';

		do_echo:

        $this->ajaxReturn($return ,$info, $status);
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
		$eid = (int)$this->input->post('eventid');
		$return = array();

		if (!$eid)
		{
			$status = 0;
			$info = '该活动不存在';
			$this->ajaxReturn($return, $info, $status);
		}
		
		$eventModel = new Domain\Events($this->web_info, $this->user);

		$event = $eventModel->getEvent($eid);
		if (empty($event))
		{
			$status = 0;
			$info = '该活动不存在';
			$this->ajaxReturn($return, $info, $status);
		}

		if (!$this->isCreate || $event->row['webid']!=$this->web_info['aid'])
		{
			$status = 0;
			$info = '您没有权限进行此操';
			$this->ajaxReturn($return, $info, $status);
		}
		
		$event->cancel();
		service_api('Credit', 'activity', array(false));
		$status = 1;
		$return['jump'] = mk_url('wevent/event/mylist',array('web_id'=>$this->web_info['aid']));

        $this->ajaxReturn($return);
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
		$eid = (int)$this->input->post('eventid');
		$return = array();

		$eventModel = new Domain\Events($this->web_info, $this->user);

		$event = $eventModel->getEvent($eid);

		if (empty($event))
		{
			$status = 0;
			$info = '活动不存在';
			$this->ajaxReturn($return, $info, $status);
		}
		
		
		$canAdmin = $this->isCreate;

		//检查当前活列表是否可以显示
		if ($event->isShowUsers())
			$users = $event->getUsers();
		else 
			$users = array();		

		$data = array();

		foreach ($users as $user) {
			if($user['answer']!=2)
				continue;
			$data[] = array(
				'uid' => $user['user_id'],
				'link' => url_home($user['dkcode']),
				'userhead' => get_avatar($user['user_id']),
				'username' => $user['name'],
				'answer' => $user['answer'],
			);
		}
		//参加人数
		
		$return['gonum'] = count($data);       //确定
		$return['allnum'] = $return['gonum'];
		$return['canAdmin'] = $canAdmin;

		$status = 1;

		$info = '';
		$return['data'] = $data;
        $this->ajaxReturn($return, $info, $status);
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
		$token = $this->input->get('eventid');
		$form = new Model\DetailForm();
		$obj = new stdClass;
		$obj->status = 0;
		if (!$form->set_token($token))
			$obj->info = '操作过期,请刷新页面,重新上传!';
		else if ($_FILES['uploadPhotoFile']['error'] != UPLOAD_ERR_OK)
			$obj->info = '上传文件错误,请检查文件是否过大';
		else if (!is_uploaded_file($_FILES['uploadPhotoFile']['tmp_name'])) 
			$obj->info = '文件上传错误';
		else if ($_FILES['uploadPhotoFile']['size'] > 1024 * 1024 * 4) 
			$obj->info = '文件太大';
		else if (!Model\ImageModel::isValid($_FILES['uploadPhotoFile']['tmp_name'])) 
			$obj->info = '您上传的文件不是一个有效的图片';
		else 
		{
			try
			{
				$img = new Model\ImageModel($_FILES['uploadPhotoFile']['tmp_name']);
				$file1 = "tmp/wevent/{$token}.jpg";
				$file2 = "tmp/wevent/{$token}_s.jpg";
				$file3 = "tmp/wevent/{$token}_b.jpg";	
				$img->reSizeToFile(VAR_PATH . $file1, 180, 150, 'jpg');
				$img->reSizeToFile(VAR_PATH . $file2, 112, 92, 'jpg');
				$img->resizeCrop(VAR_PATH . $file3, 90, 60, 'jpg');	
				$form->keep('img', VAR_PATH.$file1);
				$form->keep('img_s', VAR_PATH.$file2);
				$form->keep('img_b',VAR_PATH.$file3);	
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
		echo 'window.parent.sendPhotoComplete('.json_encode($obj).');';
		echo '</script>';
	}

	/**
	 * 禁止用户参加活动
	 *
	 * 相关页面
	 *     活动详情页
	 */
	public function delGuest()
	{
		$eid = $this->input->post('eventid');
		$uid = $this->input->post('uid');

		$userModel = new Domain\Events($this->web_info, $this->user);
		$event = $userModel->getEvent($eid);
		$return = array();
		if (empty($event))
		{
			$status = 0;
			$info = '活动不存在';
			$this->ajaxReturn($return, $info, $status);
		}

		//被操作人
		$user = $event->getUser($uid);

		if (!$this->isCreate)
		{
			$status = 0;
			$info = '您没有管理权限';
			$this->ajaxReturn($return, $info, $status);
		}

		if (empty($user))
		{
			$status = 0;
			$info = '该用户不存在';
			$this->ajaxReturn($return, $info, $status);
		}

		$user->block();
		service_api('Credit', 'cancelAttend', array($uid));
		$status = 1;
		$info = '操作成功';
		$this->ajaxReturn($return, $info, $status);
	}

}
