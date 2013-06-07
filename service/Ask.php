<?php
require_once __DIR__ . '/../apps/ask/application/models/askmodel.php';
require_once __DIR__ . '/../apps/ask/application/models/interfacemodel.php';
require_once __DIR__ . '/../apps/ask/application/models/validmodel.php';

use DK\Ask as Ask;

/**
 * 问答接口服务
 *
 * @author xuweihua
 */
class AskService extends DK_Service
{
	function __construct()
	{
		parent::__construct();

		$this->askmodel = new Ask\askmodel();
		$this->inter = new Ask\Interfacemodel();
	}

	/**
	 * 添加问答
	 *
	 * @param array $arr     问答内容数组
	 * @param int   $uid     提问人id
	 * @param int   $type    提问来源 [1 信息流][2 问答][3 网页][4 群组]
	 * @param int   $xid     如果$type为4测xid为群组id
	 *
	 * @return array($flag, $poll)
	 *     如果执行成功$flag为true,$poll是问答id
	 *     如果执行失败$flag为false,$poll是字符串类型的错误(返回给前端用)
	 *
	 * @example
	 *     list($flag, $poll) = service('ask')->addAsk($this->input->get(), $this->uid, 4, $group_id);
	 *
	 *     if ($flag) {
	 *         $id = $poll; //这是问答id,需要存储起来
	 *
	 *         //$data是要交给前端渲染的数组
	 *         $data = service('ask')->getAskData($poll, $this->uid, 1);
	 *         $this->ajaxReturn($data, null, 1, 'json');
	 *     }
	 *     else {
	 *         //这个时候$poll是错误(如:问答标题不能为空！)
	 *         $this->ajaxReturn(null, $poll, 0, 'json');
	 *     }
	 */
	function addAsk($arr, $uid, $type, $xid=0)
	{
		if (!is_array($arr)) {
			throw new Exception('参数$arr必需是一个数组');
		}

		$validmodel = new Ask\Validmodel();
		list($error, $title, $allow, $type_no_use, $multi, $options, $perm, $users) = $validmodel->addAskValid($arr);

		if ($error) {
			return array(false, $error);
		}
		
		if ($type == 4) {
			$perm = $xid;
		}

		$poll = $this->askmodel->addAsk($type, $title, $multi, $allow, $options, $uid, $perm, $users);

		if (!$poll) {
			throw new Exception('添加失败');
		}

		return array(true, $poll['id']);
	}

	/**
	 * 得到问答数据接口
	 *
	 * @param int poll_id 问答id
	 * @param int uid     录前登陆用户id
	 * @param int type    在哪边显示 [1 信息流][2 问答][3 网页][4 群组]
	 * @return array|null 如果问答不存在返回null
	 */
	function getAskData($poll_id, $uid, $type)
	{
		if (!is_numeric($poll_id) || !is_numeric($uid)) {
			throw new Exception('问答id和用户id必需为数字');
		}

		if (!in_array($type, array(1, 2, 3, 4))) {
			throw new Exception('显示类型错误');
		}

		$poll = $this->askmodel->getAsk($poll_id, $uid, 3);

		if ($poll) {
			$data = $this->_buildPollData($poll, $poll['uid']);
			$data['atype'] = 1;
			return $data;
		}
		else {
			return null;
		}
	}

	/**
	 * 删除活动
	 *
	 * @param int poll_id 问答id
	 * @param int uid     用户uid
	 */
	function delAsk($poll_id, $uid)
	{
		if (!is_numeric($poll_id) || !is_numeric($uid)) {
			throw new Exception('问答id和用户id必需为数字');
		}

		$this->askmodel->delAsk($poll_id, $uid);
	}

	/**
	 * 时间线数据接口
	 *
	 * @param int poll_id 问答id
	 * @param int uid     用户id
	 * @return array|null
	 */
	function timelineAskData($poll_id, $uid)
	{
		return $this->getAskData($poll_id, $uid, 1);
	}

	private function _buildPollData($poll, $uid)
	{
		$data = array();
		$data['questionid'] = $poll['id'];
		$data['i_voting'] = $poll['_voted'];
		$data['type'] = $poll['type'];
		$data['dkcode'] = $this->inter->getDkcodeByUid($uid);
		$data['username'] = $this->inter->getUsernameByUid($uid);
		$data['link_url'] = $this->inter->getUserUrl($uid);
		$data['img']  = get_avatar($uid,'s');
		$data['addtime'] = friendlyDate(strtotime($poll['addtime']));
		$data['question'] = htmlspecialchars($poll['title']);
		$data['multi'] = (int)$poll['multi'];
		$data['allow'] = (int)$poll['allow'];
		$data['votes'] = $poll['_votes'];
		$data['access'] = (int)$poll['perm'];
		$data['followed'] = (int)$poll['_followed']; //是不是关注了
		$data['oredit'] = (int)$poll['_edit']; //能不能编辑和删除
		$data['askfriend'] = (int)$poll['_askfriend']; //能不能向好友提问
		$data['is_end'] = $poll['_is_end'];
		$data['optionsNum'] = $poll['_optionsNum'];
		$data['commentsNum'] = $poll['_commentsNum'];
		$data['options'] = array();
        
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
	
}
