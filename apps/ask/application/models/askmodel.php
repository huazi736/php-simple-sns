<?php
namespace DK\Ask;

use \Exception;

if (file_exists('/usr/local/php/bin/php')) {
	define('PHPBIN', '/usr/local/php/bin/php');
}
else if (file_exists('/home/web/program/php/bin/php')) {
	define('PHPBIN', '/home/web/program/php/bin/php');
}
else if (file_exists('/yym/web/program/php/bin/php')) {
	define('PHPBIN', '/yym/web/program/php/bin/php');
}
else {
	throw new Exception('未找到php cli安装位置');
}

require_once __DIR__ . '/interfacemodel.php';
require_once __DIR__ . '/dbmodel.php';


/**
 * 回答控制器model
 */
class Askmodel extends \DK_Model {

	function __construct() {
		parent::__construct();

		$this->inter = new Interfacemodel();
		$this->dbmodel = new Dbmodel();
	}

	/**
	 * 添加问答
	 */
	function addAsk($type, $title, $multi, $allow, $options, $uid, $perm, $users)
	{
		$addtime = date('Y-m-d H:i:s');

		//添加问答
		$r_poll = $this->dbmodel->addPoll($type, $uid, $perm, $title, $multi, $allow, $addtime);
		$r_poll['multi'] = $multi == 1?1:0;
		$comment_num = $this->dbmodel->getCommentsNum($r_poll['id']);
		//添加选项
		$r_options = $this->dbmodel->addOptions($r_poll, $options);

		//发起者默认关注
		$this->dbmodel->addFollow($r_poll['id'], $uid);

		//积分
		$this->inter->credit_ask();

		foreach ($r_options as $key => $option) {
			$r_options[$key]['_selected'] = 0;
			$r_options[$key]['_voters'] = array();
			$r_options[$key]['_votes'] = 0;
		}

		$r_poll['_options'] = $r_options;
		$r_poll['_votes'] = 0;
		$r_poll['_voted'] = 0;

		$r_poll['_optionsNum'] = count($options);
		$r_poll['_commentsNum'] = $comment_num;
		$r_poll['_is_end'] = $r_poll['_optionsNum']>3?false:true;
		$r_poll['_followed'] = 1; //是不是关注了
		$r_poll['_edit'] = 1; //能不能编辑和删除
		$r_poll['_askfriend'] = 1; //能不能向好友提问

		if ($type == 2) {
			//插入时间线
			$this->inter->addAskToTimeline($r_poll, $r_options);

			//为自定义权限作特殊处理
			if ($perm == -1) {
				$this->dbmodel->addMyList($uid, $uid, 1, $r_poll['id']);
				if ($users) {
					$this->dbmodel->addMyLists($users, $uid, 1, $r_poll['id']);
					$this->inter->askFriendSendNotice($r_poll, $uid, $users);
				}
			}
			else {
				//推送
				$this->_notifyPush($r_poll['id'], $r_poll['uid'], 1);
			}
		}

		return $r_poll;
	}

	/**
	 * 通知推送
	 *
	 * @param int $poll_id     要推送的问答id
	 * @param int $trigger_uid 解发通知的用户
	 * @param int $type        触发类型[1 提出问答][2 回答][3 删除]
	 */
	private function _notifyPush($poll_id, $trigger_uid, $type)
	{
		$r_poll = $this->dbmodel->getPoll($poll_id);

		switch ($type) {
		case 1 :
			$this->dbmodel->addMyList($trigger_uid, $trigger_uid, 1, $r_poll['id']);

			if (in_array($r_poll['perm'], array(1, 3,4))) {
				$this->dbmodel->addPushs($poll_id, $trigger_uid, $type);
			}
			break;

		case 2:
			//如果自己的列表中有这个问答则说明以前推送过
			$row = $this->dbmodel->getMyListHas($trigger_uid, $trigger_uid, $r_poll['id']);
			if (!$row) {
				$this->dbmodel->addMyList($trigger_uid, $trigger_uid, 2, $r_poll['id']);

				if (in_array($r_poll['perm'], array(1, 3))) {
					$this->dbmodel->addPushs($poll_id, $trigger_uid, $type);
				}
			}
			break;

		case 3:
			//立刻删除本人的动态
			$this->dbmodel->delMyList($poll_id, $trigger_uid);

			$this->dbmodel->addPushs($poll_id, $trigger_uid, $type);
			break;

		default:
			throw new Exception("_notifyPush type:{$type} undefined");
		}

		exec(PHPBIN . " " . __DIR__ . "/../push.php &");
	}

	/**
	 * 得到问答
	 */
	function getAsk($id, $uid, $limit)
	{
		$r_poll = $this->dbmodel->getPoll($id);

		if (!$r_poll) {
			return null;
		}
		$r_poll['multi'] = $r_poll['multi'] == 1 ? 1:0;
		$options = $this->dbmodel->getOptions($id, 0, $limit+1);

		if (count($options) > $limit) {
			$is_end = 0;
			array_pop($options);
		}
		else {
			$is_end = 1;
		}
		$r_poll['_is_end'] = $is_end;

		foreach ($options as $key => $option) {
			$voters = $this->dbmodel->getVoters($r_poll['id'], $option['id'], 0, 3);

			$voter = $this->dbmodel->getVoter($r_poll['id'], $option['id'], $uid);

			//当前用户如果投过票则他总是排在第1位
			if ($voter) {
				foreach ($voters as $_key => $_voter) {
					if ($_voter['uid'] == $voter['uid']) {
						unset($voters[$_key]);
						break;
					}
				}
				array_unshift($voters, $voter);
				if (count($voters) > 3) {
					array_pop($voters);
				}
			}

			$options[$key]['_selected'] = $voter ? 1 : 0;
			$options[$key]['_voters'] = $voters;
			$options[$key]['_votes'] = $this->dbmodel->getOptionVotes($r_poll['id'], $option['id']);
		}

		$r_poll['_voted'] = $this->dbmodel->getAskVotedNum($r_poll['id'], $uid);

		$r_poll['_options'] = $options;
		$r_poll['_votes'] = $this->dbmodel->getAskVotes($r_poll['id']);

		$r_poll['_followed'] = $this->dbmodel->hasFollow($r_poll['id'],$uid); //是不是关注了
		$r_poll['_edit'] = ($r_poll['uid'] == $uid); //能不能编辑和删除

		$r_poll['_optionsNum'] = $this->dbmodel->getOptionsNum($r_poll['id']);
		$r_poll['_commentsNum'] = $this->dbmodel->getCommentsNum($r_poll['id']);

		//能不能向好友提问
		if (($r_poll['uid'] == $uid) || ($r_poll['perm'] == 1)) {
			$r_poll['_askfriend'] = 1;
		}
		else {
			$r_poll['_askfriend'] = 0;
		}

		return $r_poll;
	}

	function delAsk($poll_id,$uid)
	{
		//时间线
		$num = $this->dbmodel->getAskVotedNum($poll_id, $uid);
		if ($num > 0) {
			$this->inter->delVoteToTimeline($uid, $poll_id);
		}

		//通知删除动作
		$this->_notifyPush($poll_id, $uid, 3);

		//删除时间线
		$this->inter->delAskToTimeline($poll_id, $uid);

		//积分
		$this->inter->credit_del();

		return true;
	}

	/**
	 * 得到投票人列表
	 */
	function listVoter($poll_id, $option_id, $offset, $length)
	{
		$voters = $this->dbmodel->getVoters($poll_id, $option_id, $offset, $length);

		return $voters;
	}

	/**
	 * 列出当前用户的问答动态   类型[1 提问][2 回答][3 提问并回答]
	 *
	 * @paran int  $uid          用户uid
	 * @paran bool $onlyMy       只看给定用户自己的动态
	 */
	function listAsk($uid, $onlyMy, $offset, $length)
	{
		if ($onlyMy) {
			$rows = $this->dbmodel->getAskListByUid($uid, $uid, $offset, $length);
		}
		else {
			$rows = $this->dbmodel->getAskList($uid, $offset, $length);
		}

		foreach ($rows as $key => $value) {
			if ($value['type'] != 2) {
				$rows[$key]['_data'] = $this->getAsk($value['poll_id'], $uid, 3);
			}
			else{
				$data = $this->dbmodel->getPoll($value['poll_id']);
				$options = $this->dbmodel->getVotedOption($value['poll_id'], $value['from_uid']);
				$rows[$key]['_data'] = $data;
				$rows[$key]['_options'] = $options;
			}
		}

		return $rows;
	}

	/**
	 * 其他用户添加问答选项
	 */
	function addOption($poll_id, $message, $uid)
	{
		$poll = $this->dbmodel->getPoll($poll_id);

		//将选项答案添加到数据库中
		$option_id = $this->dbmodel->addOption($poll_id,$message,$uid);

		//将回答的动态存入到动态表中
		if ($option_id) {
			$data = $this->addVote($poll_id, $option_id, $uid);

			$data['options'] = $message;
			$data['id'] = $option_id;

			return $data;
		}

		return false;
	}

	/**
	 * 得到选项
	 */
	function getOptions($poll_id, $offset, $length, $uid)
	{
		$options = $this->dbmodel->getOptions($poll_id,$offset,$length+1);

		if (count($options) > $length) {
			$is_end = false;
			array_pop($options);
		}
		else {
			$is_end = true;
		}

		$data = array();
		foreach ($options as $key => $option) {
			$voters = $this->dbmodel->getVoters($poll_id, $option['id'], 0, 4);
			$voter = $this->dbmodel->getVoter($poll_id, $option['id'], $uid);
			//当前用户如果投过票则他总是排在第1位
			if ($voter) {
				foreach ($voters as $_key => $_voter) {
					if ($_voter['uid'] == $voter['uid']) {
						unset($voters[$_key]);
						break;
					}
				}
				array_unshift($voters, $voter);
			}
			$option['votes'] = $this->dbmodel->getOptionVotes($poll_id, $option['id']);
			$data[] = array(
				'id' => $option['id'],
				'message' => $option['message'],
				'poll_id' => $option['poll_id'],
				'votes' => $option['votes'],
				'selected' => $voter ? 1 : 0,
				'voters' => array(
					'friend' => $voters,
					'otherPerson' => $option['votes'] - count($voters)
				)
			);
		}

		$num = $this->dbmodel->getOptionsNum($poll_id);

		$votes = $this->dbmodel->getAskVotes($poll_id);
		return array('data'=>$data,'is_end'=>$is_end,'votes'=>$votes,'optionsNum'=>$num);
	}

	/**
	 * 删除选项
	 */
	function delOption($poll_id, $option_id, $uid)
	{
		$row = $this->dbmodel->getPoll($poll_id);

		//只有提问发起人才能删
		if ($row && $row['uid'] != $uid) {
			return array(false, 0);
		}

		$this->dbmodel->delOption($poll_id, $option_id);

		//更新时间上的数据
		if ($row['type'] == 2) {
		    $num = $this->dbmodel->getAskVotedNum($poll_id, $uid);
			if ($num == 0) {
				$this->inter->delVoteToTimeline($uid, $poll_id);
			} else {
				//给时间线发送动态
				$options = $this->dbmodel->getVotedOption($poll_id, $uid);
				$this->inter->addVoteToTimeline($uid, $row, $options); 
			}
		}

		$votes = $this->dbmodel->getAskVotes($poll_id);

		return array(true, $votes);
	}

	/**
	 * 添加对某个选项的投票
	 * @param int $poll_id 问答的id
	 * @param int $option_id 选项id
	 * @param int $uid 投票人的uid
	 */
	function addVote($poll_id, $option_id, $uid)
	{
		$poll = $this->dbmodel->getPoll($poll_id);

		if (!$poll) {
			return false;
		}

		switch ($poll['multi']) {
			//单选
		case 2 :
			$hasVotedAsk = $this->dbmodel->hasVotedAsk($poll_id,$uid);
			if ($hasVotedAsk) {
				$this->dbmodel->cancelVote($poll_id,$uid);
			}
			$this->dbmodel->addVote($uid,$poll_id,$option_id);
			break;

			//多选
		case 1:
			$hasVoted = $this->dbmodel->hasVoted($poll_id,$option_id,$uid);
			if (!$hasVoted) {
				$this->dbmodel->addVote($uid,$poll_id,$option_id);
			}
			break;
		}

		//检查是否是第1次投票
		$row = $this->dbmodel->getMyListHas($uid, $uid, $poll_id);
		if (!$row) {
			//发送通知
			$followers = $this->dbmodel->getPollFollowedUid($poll_id);
			$this->inter->addVoteSendNotice($poll, $uid, $followers);

			//回答问答时产生关系
			$this->inter->addVoteToRelation($uid, $poll['uid']);
		}

		if ($poll['type'] == '2') {
			//给时间线发送动态
			$options = $this->dbmodel->getVotedOption($poll_id, $uid);
			$this->inter->addVoteToTimeline($uid, $poll, $options); 

			//推送到相关人员
			$this->_notifyPush($poll_id, $uid, 2);
		}

		$data = array(
			'votes' => $this->dbmodel->getAskVotes($poll_id),
			'dkcode' => $this->inter->getDkcodeByUid($uid),
			'username' => $this->inter->getUsernameByUid($uid),
			'img' =>get_avatar($uid,'ss'),
		);

		return $data;
	}

	/**
	 * 删除投票
	 */
	function delVote($poll_id, $option_id, $uid)
	{
		$poll = $this->dbmodel->getPoll($poll_id);

		//删除投票
		$chang = $this->dbmodel->delVote($uid,$poll_id,$option_id);

		if ($chang && $poll['type'] == '2') {
			//维护时间线动态
			$num = $this->dbmodel->getAskVotedNum($poll_id, $uid);
			if ($num == 0) {
				$this->inter->delVoteToTimeline($uid, $poll_id);
			} else {
				//给时间线发送动态
				$options = $this->dbmodel->getVotedOption($poll_id, $uid);
				$this->inter->addVoteToTimeline($uid, $poll, $options); 
			}
		}

		//查看当前选项投票数
		$optionVotes = $this->dbmodel->getOptionVotes($poll_id,$option_id);

		$is_del = 0;

		//如果票数为0且选项是自己添加的则删除选项
		if ($optionVotes == 0) {
			$option = $this->dbmodel->getOption($poll_id, $option_id);
			if (($poll['uid'] != $uid) && $option['uid'] == $uid) {
				$this->dbmodel->delOption($poll_id, $option_id);
				$is_del = 1;
			}
		}

		$data = array(
			'votes' => $this->dbmodel->getAskVotes($poll_id),
			'dkcode' => $this->inter->getDkcodeByUid($uid),
			'username' => $this->inter->getUsernameByUid($uid),
			'img'=>get_avatar($uid,'ss')
		);

		return array($data, $is_del);
	}

	/**
	 * 取消所有投票
	 */
	function cancelVote($poll_id,$uid)
	{
		$num = $this->dbmodel->getAskVotedNum($poll_id, $uid);

		if ($num > 0) {
			$poll = $this->dbmodel->getPoll($poll_id);

			if ($poll['type'] == '2') {
				//时间线
				$this->inter->delVoteToTimeline($uid, $poll_id);

				//删除我自己添加的投票数为0的项
				if ($poll['uid'] != $uid){
					$this->dbmodel->delOptionsByUid($poll_id , $uid);
				}
			}

			//取消投票
			$this->dbmodel->cancelVote($poll_id, $uid);
		}

		return true;
	}

	/**
	 * 向好友提问
	 */
	function askFriend($poll_id, $uid, $src_uids)
	{
		$poll = $this->dbmodel->getPoll($poll_id);

		if (!$poll) {
			return false;
		}

		$this->dbmodel->addMyLists($src_uids, $uid, 1, $poll_id);

		$this->inter->askFriendSendNotice($poll, $uid, $src_uids);

		return true;
	}

	/**
	 * 添加评论
	 */
	function addComment($poll_id,$uid,$message)
	{
		$rs = $this->dbmodel->addComment($poll_id,$uid,$message);

		if ($rs){
			$data = array(
				'id'=>$rs,
				'uid'=>$uid,
				'message'=>$message,
				'username'=>$this->inter->getUsernameByUid($uid),
				'dkcode'=>$this->inter->getDkcodeByUid($uid),
				'img'=>get_avatar($uid,'s'),
				'options'=>$this->dbmodel->getVotedOption($poll_id,$uid),
			);

			//发送通知
			$followers = $this->dbmodel->getPollFollowedUid($poll_id);
			$poll = $this->dbmodel->getPoll($poll_id);
			$this->inter->addCommentSendNotice($poll, $uid, $followers);

			return $data;
		}

		return false;
	}

	/**
	 * 评论列表
	 */
	function listComments($poll_id,$uid,$limit,$offset)
	{
		$data = $this->dbmodel->listComments($poll_id,$limit,$offset);
		$num = $this->dbmodel->getCommentsNum($poll_id);
		$max = $offset+$limit;
		$isend = $num > $max?false:true;

		if ($data) {
			foreach ($data as $value) {
				$rs['ordel'] = 0;
				if ($value['uid'] == $uid) {
					$rs['ordel'] = 1;
				}
				$rs['id'] = $value['id'];
				$rs['uid'] = $value['uid'];
				$rs['message'] = $value['message'];
				$rs['img'] = get_avatar($value['uid']);
				$rs['dateline'] = friendlyDate(strtotime($value['addtime']));
				$rs['dkou'] = $this->inter->getDkcodeByUid($value['uid']);
				$rs['username'] = $this->inter->getUsernameByUid($value['uid']);
				$rs['options'] = $this->dbmodel->getVotedOption($poll_id,$value['uid']);

				$result[] = $rs;
			}

			return array('data'=>$result,'is_end'=>$isend);
		}

		return false;
	}

	/**
	 * 删除某个评论
	 */
	function delComment($id,$poll_id)
	{
		return $this->dbmodel->delComment($id,$poll_id);
	}

	/**
	 * 获得某个问答的评论数
	 */
	function getCommentsNum($poll_id)
	{
		return $this->dbmodel->getCommentsNum($poll_id);
	}

	/**
	 * 添加关注
	 */
	function addFollow($poll_id,$uid)
	{
		return $this->dbmodel->addFollow($poll_id,$uid);
	}

	/**
	 * 得到关注人列表
	 */
	function listFollow($poll_id,$uid,$limit,$offset)
	{
		$followNum = $this->dbmodel->getFollowNum($poll_id);
		$isend = $followNum > $limit? FALSE :  TRUE;
		$rs = $this->dbmodel->listFollow($poll_id,$limit,$offset);
		if($rs){
			foreach($rs as $value){
				$touid[] = $value['uid'];
			}
			$toinfos = $this->inter->getMultiRelationStatus($uid,$touid);
			foreach ($rs as $list) {
				$list['status'] = $toinfos['u'.$list['uid']];
				$list['avatar'] = get_avatar($list["uid"]);
				$list['dkou'] = $this->inter->getDkcodeByUid($list['uid']);
				$list['link_url'] = mk_url('main/index/index', array('action_dkcode' => $list['dkou']));
				$data[] = $list;
			}
			return array('data'=>$data,'is_end'=>$isend);
		}
		return false;
	}

	/**
	 * 取消对某个问题的关注
	 * @param int $poll_id 问答的id
	 * @param int $uid 取消问答的用户的uid
	 * @return bool
	 */
	function delFollow($poll_id, $uid)
	{
		return $this->dbmodel->delFollow($poll_id,$uid);
	}
}
