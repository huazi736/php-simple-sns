<?php
/**
 * 问答模块
 *
 * @author xuweihua jiangfangtao
 */

require_once __DIR__ . '/../models/askmodel.php';
require_once __DIR__ . '/../models/interfacemodel.php';
require_once __DIR__ . '/../models/validmodel.php';

use DK\Ask\Askmodel;
use DK\Ask\Interfacemodel;
use DK\Ask\Validmodel;

/**
 * 回答控制器方法
 */
class Ask extends MY_Controller {

	function _initialize()
	{
		$this->askmodel = new Askmodel();
		$this->inter = new Interfacemodel();
		$this->validmodel = new Validmodel();
	}

	private function common()
	{
		$action_dkcode = $this->action_uid ? $this->action_user['dkcode'] : $this->dkcode;
		$author_username = $this->action_uid ? $this->action_user['username'] : $this->username;
		$author_avatar = $this->action_uid ? get_avatar($this->action_uid, 'ss') : get_avatar($this->uid, 'ss');
		$userInfo = array(
			'author_url'=>mk_url('main/index/profile', array('action_dkcode' => $action_dkcode)),
			'author_avatar'=>$author_avatar,
			'author_username'=>$author_username,
			'ask_url'=>mk_url('ask/ask/index', array('action_dkcode' => $action_dkcode))
		);
		$power = $this->action_uid ? false : true;
		$visitorInfo = array(
			'visitor_uid'=>$this->uid,
			'visitor_username'=>$this->username,
			'visitor_avatar'=>get_avatar($this->uid,'s'),
			'visitor_dkcode'=>$this->dkcode,
			'ask_uid'=>$this->action_uid
		);

		$this->assign('userInfo',$userInfo);
		$this->assign('power',$power);
		$this->assign('visitorInfo',$visitorInfo);
	}

	/**
	 * 问答首页
	 */
	function index()
	{
		$this->common();

		$getmy = (bool)$this->input->get('getmy')?1:0;

		$this->assign('getmy',$getmy);

		$this->display('ask_list.html');
	}

	/**
	 * 问答详细页
	 */
	function detail()
	{
		$this->common();

		$poll_id = (int)$this->input->get('poll_id');

		$this->assign('poll_id', $poll_id);

		$this->display('ask_detail.html');
	}

	/**
	 * 问答列表展示页
	 */
	function listAsk()
	{
		list($getmy, $offset, $length) = $this->validmodel->listAsk();

		$rows = $this->askmodel->listAsk($this->uid, $getmy, $offset, $length+1);

		if (count($rows) > $length) {
			$is_end = 0;
			array_pop($rows);
		}
		else {
			$is_end = 1;
		}

		$data = array();

		foreach ($rows as $row) {
			$tmp = array();
			//只要不是回答
			if ($row['type'] != 2) {
				$tmp = $this->_buildPollData($row['_data'], $row['from_uid']);
				$tmp['type'] = $row['type'];
				$tmp['atype'] = 3;
				$tmp['addtime'] = friendlyDate(strtotime($row['addtime']));

				$data[] = $tmp;

			}
			//如果回答的选项为空则把这条信息隐藏掉
			else if ($row['_options']) {

				$tmp['type'] = $row['type'];
				$tmp['atype'] = 4;
				$tmp['dkcode'] = $this->inter->getDkcodeByUid($row['from_uid']);
				$tmp['username'] = $this->inter->getUsernameByUid($row['from_uid']);
				$tmp['link_url'] = $this->inter->getUserUrl($row['from_uid']);
				$tmp['img']  = get_avatar($row['from_uid'],'s');

				$tmp['poll_id'] = $row['poll_id'];
				$tmp['question'] = htmlspecialchars($row['_data']['title']);
				$tmp['addtime'] = friendlyDate(strtotime($row['addtime']));
				$tmp['options'] = $row['_options'];

				foreach ($tmp['options'] as $key => $val) {
					$tmp['options'][$key]['message'] = htmlspecialchars($val['message']);
				}

				$data[] = $tmp;
			}
		}

		$return = array('status'=>1,'info'=>'success','data'=>$data,'isend'=>$is_end);
		$this->assign('status', 1);
		$this->assign('info','success!');
		$this->assign('data', $return);
		$this->ajaxReturn();
	}

	/**
	 * 添加问答
	 */
	function addAsk()
	{
		$status = 0;
		$msg = null;
		$data = array();
		list($error, $title, $allow, $type, $multi, $options, $perm, $users) = $this->validmodel->addAsk();

		if ($error) {
			$msg = $error;
			goto doReturn;
		}

		$poll = $this->askmodel->addAsk(2, $title, $multi, $allow, $options, $this->uid, $perm, $users);

		if (!$poll) {
			$msg = '添加失败';
			goto doReturn;
		}

		$status = 1;
		$msg = 'success';

		$data = $this->_buildPollData($poll, $poll['uid']);
		$data['atype'] = 3;

		doReturn:
			$this->assign('status',$status);
		$this->assign('info',$msg);
		$this->assign('data', $data);
		$this->ajaxReturn();
	}

	/**
	 * 得到问答
	 */
	function getAsk()
	{
		$status = 0;
		$msg = null;
		$data = null;

		list($error, $poll_id) = $this->validmodel->getAsk($this->uid);

		if ($error) {
			$msg = $error;
		}
		else {
			$status = 1;
			$poll = $this->askmodel->getAsk($poll_id, $this->uid, 10);
			$data = $this->_buildPollData($poll, $poll['uid']);
		}

		doReturn:
		$this->assign('status',$status);
		$this->assign('info',$msg);
		$this->assign('data', $data);
		$this->ajaxReturn();
	}

	private function _buildPollData($poll, $uid)
	{
		$data = array();
		$data['questionid'] = $poll['id'];
		$data['i_voting'] = $poll['_voted'];
		$data['dkcode'] = $this->inter->getDkcodeByUid($uid);
		$data['username'] = $this->inter->getUsernameByUid($uid);
		$data['link_url'] = $this->inter->getUserUrl($uid);
		$data['img']  = get_avatar($uid,'s');
		$data['addtime'] = friendlyDate(strtotime($poll['addtime']));
		$data['question'] = htmlspecialchars($poll['title']);
		$data['multi'] = (int)$poll['multi'];
		$data['allow'] = (int)$poll['allow'];
		$data['votes'] = $poll['_votes'];
		$data['followed'] = (int)$poll['_followed']; //是不是关注了
		$data['oredit'] = (int)$poll['_edit']; //能不能编辑和删除
		$data['askfriend'] = (int)$poll['_askfriend']; //能不能向好友提问
		$data['is_end'] = $poll['_is_end'];
		$data['optionsNum'] = $poll['_optionsNum'];
		$data['commentsNum'] = $poll['_commentsNum'];
		$data['options'] = array();

		switch ($poll['type']) {
		case '2':
			$data['access'] = (int)$poll['perm'];
			break;
		case '4':
			$info = service('Group')->getGroupInfo($poll['perm']);
			$data['access'] = $info ? $info['roomnick'] : '';
			break;
		default:
			$data['access'] = 0;
			break;
		}

		foreach ($poll['_options'] as $option) {
			$voters = array();

			foreach ($option['_voters'] as $voter) {
				$voters[] = array(
					'dkcode' => $this->inter->getDkcodeByUid($voter['uid']),
					'username' => $this->inter->getUsernameByUid($voter['uid']),
					'img' => get_avatar($voter['uid'],'ss')
				);
			}

			$data['options'][] = array(
				'id' => $option['id'],
				'message' => htmlspecialchars($option['message']),
				'poll_id' => $option['poll_id'],
				'votes' => $option['_votes'],
				'selected' => $option['_selected'],
				'dkcode' => $this->inter->getDkcodeByUid($option['uid']),
				'username' => $this->inter->getUsernameByUid($option['uid']),
				'voters' => array(
					'friend' => $voters,
					'otherPerson' => $option['_votes'] - count($option['_voters'])
				)
			);
		}

		return $data;
	}

	/**
	 * 删除问答
	 */
	function delAsk()
	{
		list($error, $poll_id) = $this->validmodel->delAsk($this->uid);

		if ($error) {
			$status = 0;
			$msg = $error;
		}
		else {
			$this->askmodel->delAsk($poll_id,$this->uid);
			$status = 1;
			$msg = '删除问答成功！';
		}

		$this->assign('status',$status);
		$this->assign('info',$msg);
		$this->ajaxReturn();
	}

	/**
	 * 添加问答选项操作
	 */
	function addOption()
	{
		$status = 0;
		$data = array();

		list($error,$poll_id,$message)=$this->validmodel->addOption($this->uid);

		if(!$error){
			$rs = $this->askmodel->addOption($poll_id,$message,$this->uid);
			if($rs){
				$status = 1;
				$msg = 'success';
				$rs['options'] = htmlspecialchars($rs['options']);
				$data = $rs;
			}
			else{
				$msg = '添加问答选项失败！';
				$data = array();
			}
		}
		else{
			$msg = $error;
		}

		$this->assign('status',$status);
		$this->assign('info',$msg);
		$this->assign('data',$data);
		$this->ajaxReturn();
	}

	/**
	 * 得到选项
	 */
	function getOptions()
	{
		$status = 0;
		$data = array();
		$isend = true;
		$votes = 0;
		$num = 0;

		list($error, $poll_id, $offset, $length) = $this->validmodel->getOptions($this->uid);

		if (!$error) {
			$rs = $this->askmodel->getOptions($poll_id, $offset, $length, $this->uid);

			$isend = $rs['is_end'];
			$data = $rs['data'];

			foreach ($data as $key => $val) {
				$voters = array();
				foreach ($val['voters']['friend'] as $key2 => $voter) {
					$voters[] = array(
						'dkcode' => $this->inter->getDkcodeByUid($voter['uid']),
						'username' => $this->inter->getUsernameByUid($voter['uid']),
						'img' => get_avatar($voter['uid'],'ss')
					);
				}

				$data[$key]['voters']['friend'] = $voters;
				$data[$key]['message'] = htmlspecialchars($val['message']);
			}

			$votes = $rs['votes'];
			$num = $rs['optionsNum'];
			if ($data){
				$status = 1;
				$msg = 'success';

			}
			else{
				$msg = '该问答没有选项或者选项已经被删除！';
			}
		}
		else{
			$msg = $error;
		}

		$this->assign('status',$status);
		$this->assign('info',$msg);
		$this->assign('data',$data);
		$this->assign('is_end',$isend);
		$this->assign('votes',$votes);
		$this->assign('optionsNum',$num);
		$this->ajaxReturn();
	}

	/**
	 * 删除选项
	 */
	function delOption()
	{
		$status = 0;
		list($error,$poll_id,$option_id) = $this->validmodel->delOption($this->uid);
		if(!$error){
			list($rs, $votes) = $this->askmodel->delOption($poll_id,$option_id,$this->uid);

			if($rs){
				$status = 1;
				$msg = '答案删除成功！';
			}
			else{
				$msg = '答案删除失败！';
			}
		}
		else{
			$msg = $error;
			$votes = 0;
		}
		$this->assign('status', $status);
		$this->assign('info', $msg);
		$this->assign('votes', (int)$votes);
		$this->ajaxReturn();
	}

	/**
	 * 添加对某个选项的投票
	 */
	function addVote()
	{
		$status = 0;
		$data = array();

		list($error,$poll_id,$option_id) =$this->validmodel->addVote($this->uid);

		if(!$error){
			$rs = $this->askmodel->addVote($poll_id,$option_id,$this->uid);

			if($rs){
				$status = 1;
				$msg = 'success!';
				$data = $rs;
			}
			else{
				$msg = '投票失败！';
			}
		}
		else{
			$msg = $error;
		}

		$this->assign('status',$status);
		$this->assign('info',$msg);
		$this->assign('data',$data);
		$this->ajaxReturn();
	}

	/**
	 * 取消对某个选项的投票
	 */
	function delVote()
	{
		$status = 0;
		$data = false;
		$is_del = 0;

		list($error, $poll_id, $option_id) =$this->validmodel->addVote($this->uid); 

		if (!$error){
			list($data, $is_del) = $this->askmodel->delVote($poll_id,$option_id,$this->uid);

			$status = 1;
			$msg = 'success!';
		}
		else{
			$msg = $error;
		}

		$this->assign('status',$status);
		$this->assign('info',$msg);
		$this->assign('is_del', $is_del);
		$this->assign('data',$data);
		$this->ajaxReturn();
	}

	/**
	 * 取消某人的所有的投票
	 */
	function cancelVote()
	{
		$status = 0;

		list($error,$poll_id)=$this->validmodel->cancelVote($this->uid);

		if (!$error) {
			$rs = $this->askmodel->cancelVote($poll_id,$this->uid);
			if($rs){
				$status = 1;
				$msg = '取消投票成功！';
			}
			else{
				$msg = '取消投票失败！';
			}
		}
		else{
			$msg = $error;
		}

		$this->assign('status',$status);
		$this->assign('info',$msg);
		$this->ajaxReturn();
	}

	/**
	 * 显示投票人
	 */
	function listVoter()
	{
		$status = 1;
		$msg = null;
		$is_end = 1;
		$data = array();

		list($error,$poll_id, $option_id, $length,$offset) = $this->validmodel->listVoter($this->uid);

		if(!$error){
			$voters = $this->askmodel->listVoter($poll_id, $option_id, $offset, $length + 1);
			if (count($voters) < 0) {
				$status = 0;
			}
			if (count($voters) > $length) {
				$is_end = 0;
				array_pop($voters);
			}

			foreach ($voters as $voter) {
				$data[] = array(
					'avatar' => get_avatar($voter['uid']),
					'uid' => $voter['uid'],
					'dkcode' => $this->inter->getDkcodeByUid($voter['uid']),
					'link_url' => $this->inter->getUserUrl($voter['uid']),
					'status' => $this->inter->getRelationStatus($this->uid, $voter['uid']),
					'username' => $this->inter->getUsernameByUid($voter['uid'])
				);
			}
		}
		else{
			$status = 0;
			$msg = $error;
		}

		$this->assign('status', $status);
		$this->assign('is_end', $is_end);
		$this->assign('info', $msg);
		$this->assign('data', $data);
		$this->ajaxReturn();
	}

	/**
	 * 获得好友列表
	 */
	function listFriend()
	{
		list($keyword, $page, $length) = $this->validmodel->listFriend();

		$users = service('PeopleSearch')->getFriendsReturnArray($this->uid, $keyword, $page, $length+1);

		if (count($users) > $length) {
			array_pop($users);
			$is_end = 0;
		}
		else {
			$is_end = 1;
		}

		$friends = array();

		foreach ($users as $user) {
			$friends[] = array(
				'id' => $user['id'],
				'name' => $user['name'],
				'dkcode' => $user['dkcode'],
				'hidden' => 0,
				'face' => get_avatar($user['id'], 'ss'),
			);
		}

		if ($friends) {
			$status = 1;
			$msg = 'success!';
		}
		else{
			$status = 0;
			if ($keyword) {
				$msg = '未能在您的好友中找到:'.$keyword;
			} else {
				$msg = '您还没好友,请多加努力！';
			}
		}

		$this->assign('status', $status);
		$this->assign('info', $msg);
		$this->assign('is_end', $is_end);
		$this->assign('data', $friends);
		$this->ajaxReturn();
	}

	/**
	 * 向好友提问
	 */
	function askFriend()
	{
		$status = 0;

		list($error, $poll_id, $src_uids) = $this->validmodel->askFriend($this->uid);

		if ($error) {
			$msg = $error;
		}
		else {
			$this->askmodel->askFriend($poll_id, $this->uid, $src_uids);

			$status = 1;
			$msg = '向好友提问操作成功！';
		}

		$this->assign('status', $status);
		$this->assign('info', $msg);
		$this->ajaxReturn();
	}

	/*
	 * 添加问答评论
	 */
	function addComment()
	{
		$status = 0;
		$data = array();

		list($error,$poll_id,$message) = $this->validmodel->addComment($this->uid);

		if (!$error) {
			$result = $this->askmodel->addComment($poll_id,$this->uid,$message);

			if($result){
				$status = 1;
				$msg = '帖子发表成功！';
				$data = $result;
				$data['message'] = htmlspecialchars($data['message']);
				foreach ($data['options'] as $key => $val) {
					$data['options'][$key]['message'] = htmlspecialchars($val['message']);
				}
			}
			else{
				$msg = '帖子发表失败！';
			}
		}
		else{
			$msg = $error;
		}

		$this->assign('status',$status);
		$this->assign('info',$msg);
		$this->assign('data',$data);
		$this->ajaxReturn();
	}

	/**
	 * 获得评论列表
	 */
	function listComments()
	{
		$status = 0;
		$data = array();
		$isend = true;

		list($error,$poll_id,$limit,$offset) = $this->validmodel->listComments($this->uid);

		if (!$error) {
			$rs = $this->askmodel->listComments($poll_id,$this->uid,$limit,$offset);
			if($rs){
				foreach ($rs['data'] as $key => $val) {
					$rs['data'][$key]['message'] = htmlspecialchars($val['message']);
				}

				$status = 1;
				$msg = '评论列表加载成功！';  
				$data = $rs['data'];
				$isend = $rs['is_end'];
			}
			else{
				$status = 0;
				$msg = '该问答暂无帖子！';
			}
		}
		else{
			$msg = $error;

		}

		$this->assign('status',$status);
		$this->assign('info',$msg);
		$this->assign('data',$data);
		$this->assign('is_end',$isend);
		$this->ajaxReturn();
	}

	/**
	 * 删除评论
	 */
	function delComment()
	{
		$status = 0;

		list($error,$id,$poll_id) = $this->validmodel->delComment($this->uid);

		if (!$error) {
			$rs = $this->askmodel->delComment($id,$poll_id);
			if($rs){
				$status = 1;
				$msg = '帖子删除成功！';
			}
			else{
				$msg = '帖子删除失败！';
			}
		}
		else{
			$msg = $error;
		}

		$this->assign('status',$status);
		$this->assign('info',$msg);
		$this->ajaxReturn();
	}

	/**
	 * 添加对某个问答的关注
	 */
	function addFollow()
	{
		$status = 0;

		list($error,$poll_id)= $this->validmodel->checkFollow($this->uid);

		if (!$error) {
			$rs = $this->askmodel->addFollow($poll_id,$this->uid);

			if($rs){
				$status = 1;
				$msg = '添加关注成功！';
			}
			else{
				$msg = '添加关注失败！';
			}
		}
		else{
			$msg = $error;
		}

		$this->assign('status',$status);
		$this->assign('info',$msg);
		$this->ajaxReturn();
	}

	/**
	 * 获得关注列表
	 */
	function listFollow()
	{
		$status = 0;
		$data = array();
		$isend = true;

		list($error,$poll_id,$limit,$offset) = $this->validmodel->listFollow($this->uid);

		if(!$error){
			$rs = $this->askmodel->listFollow($poll_id,$this->uid,$limit,$offset);
			$data = $rs['data'];
			$isend = $rs['isend'];
			if($data){
				$status = 1;
				$msg = '加载关注列表成功！';
				$data = $rs;
			}
			else{
				$msg = '暂无数据！';
			}
		}
		else{
			$msg = $error;
		}

		$this->assign('status',$status);
		$this->assign('info',$msg);
		$this->assign('data',$data);
		$this->assign('is_end',$isend);
		$this->ajaxReturn();
	}

	/**
	 * 取消对某个问答的关注
	 */
	function delFollow()
	{
		$status = 0;

		list($error,$poll_id) = $this->validmodel->checkFollow($this->uid);

		if(!$error){
			$res = $this->askmodel->delFollow($poll_id, $this->uid);
			if (!$res) {
				$msg = '取消关注失败!';
			}
			else{
				$status = 1;
				$msg = 'success!';
			}
		}
		else{
			$msg = $error;
		}

		$this->assign('status',$status);
		$this->assign('info',$msg);
		$this->ajaxReturn();
	}

}
