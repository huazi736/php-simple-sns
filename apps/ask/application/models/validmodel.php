<?php
namespace DK\Ask;

require_once __DIR__ . '/dbmodel.php';
require_once __DIR__ . '/interfacemodel.php';

/**
 * 数据验证模型
 * @author xuweihua jiangfangtao
 */
class Validmodel extends \DK_Model{

	function __construct() {
		parent::__construct();
		$this->dbmodel = new Dbmodel();
		$this->inter = new Interfacemodel();
	}

	/**
	 * 得到问答详情
	 */
	function getAsk($uid)
	{
		$error = null;

		$poll_id = (int)$this->input->get('poll_id');

		$poll = $this->dbmodel->getPoll($poll_id);

		if (!$poll) {
			$error = '指定的问答不存在或己被删除';
		}
		else if (!$this->canAccessPoll($poll_id, $uid)) {
			$error = '您没有权限进行此操作';
		}

		return array($error, $poll_id);
	}

	/**
	 * 删除问答
	 */
	function delAsk($uid)
	{
		$poll_id = (int)$this->input->get('poll_id');

		$poll = $this->dbmodel->getPoll($poll_id);
		if (!$poll) {
			$error = '该问答不存在或者已经被删除！';

			return array($error, $poll_id);
		}
		else if ($poll['uid'] != $uid) {
			$error = '您没有权限删除';

			return array($error, $poll_id);
		}

		return array(null, $poll_id);
	}

	/**
	 * 添加问答时数据验证
	 */
	function addAsk()
	{
		//解除环境绑定
		return $this->addAskValid($this->input->get());
	}

	/**
	 * 数据验证
	 */
	function addAskValid($arr)
	{
		$error = null;
		$perm = null;
		$options = array();
		$users = array();

		$title = isset($arr['title']) ? $arr['title'] : null;
		$type = isset($arr['type']) ? (int)$arr['type'] : null;
		$allow = (isset($arr['allow']) && $arr['allow']) ? 1 : 0;
		$multi = (isset($arr['multi']) && (int)$arr['multi'] == 0) ? 2:1;
		$permission = isset($arr['permission']) ? $arr['permission'] : null;
		$options_arr = isset($arr['options']) ? (array)$arr['options'] : array();
		$title = trim($title);

		if (strlen($title)<1) {
			$error = '问答标题不能为空！';
			goto doReturn;
		}
		elseif (mb_strlen($title, 'utf-8') > 260) {
			$error = '问答标题不得超过260个字';
			goto doReturn;
		}

		foreach ($options_arr as $k => $val) {
			$val = trim($val);

			//字符串不得超过80个
			if (mb_strlen($val, 'utf-8') > 80) {
				$error = '添加选项单个内容不得超过80个字';
				goto doReturn;
			}

			if (strlen($val)>0) {
				$options[] = $val;
			}
		}

		//出除重复选项
		$options = array_unique($options);

		$perms = array(1, 8, 4, 3);

		if (in_array($permission, $perms)) {
			$perm = $permission;
			$users = array();
		}
		else {
			$perm = -1;
			$users = explode(',', $permission);
			$users = array_filter(array_map('trim', $users));
		}

		doReturn:
			return array($error, $title, $allow, $type, $multi, $options, $perm, $users);
	}

	/**
	 * 问答列表
	 */
	function listAsk()
	{
		$getmy = (bool)$this->input->get('getmy');
		$page = (int)$this->input->get('page');
		if ($page < 1) {
			$page = 1;
		}
		$length = 20;
		$offset = ($page-1) * $length;
		return array($getmy, $offset, $length);
	}

	/**
	 * 添加问答
	 */
	function addOption($uid)
	{
		$error = null;
		$poll_id = (int)$this->input->get('poll_id');
		$message = $this->input->get('message');
		if(!$poll_id || $poll_id <1){
			$error = '非法操作';
			goto doReturn;
		}
		$message = trim($message);
		if(strlen($message) < 1){
			$error = '非法操作';
			goto doReturn;
		}

		$pollInfo = $this->dbmodel->getPoll($poll_id);
		if(!$pollInfo){
			$error = '该问答不存在或者已经被删除！';
			goto doReturn;
		}
		$exists = $this->dbmodel->optionExists($poll_id,$message);
		if($exists){
			$error = '不能存在重复的答案！';
			goto doReturn;
		}

		if (!$this->canAccessPoll($poll_id, $uid)) {
			$error = '您没有权限进行此操作';
			goto doReturn;
		}

		doReturn:
			return array($error,$poll_id,$message);
	}

	/**
	 * 删选项
	 */
	function delOption($uid)
	{
		$poll_id = (int)$this->input->get('poll_id');
		$option_id = (int)$this->input->get('option_id');
		$error = null;

		if (!$poll_id || !$option_id) {
			$error = '非法操作';
			goto doReturn;
		}

		$poll = $this->dbmodel->getPoll($poll_id);

		if (!$poll) {
			$error = '指定的数据不存';
			goto doReturn;
		}
		else if ($poll['uid'] != $uid) {
			$error = '您没有权限进行此操作';
			goto doReturn;
		}

		$option = $this->dbmodel->getOption($poll_id,$option_id);
		if (!$option){
			$error = '该选项已经被删除';
			goto doReturn;
		}

		doReturn:
			return array($error,$poll_id,$option_id);
	}

	/**
	 * 得到选项
	 */
	function getOptions($uid)
	{
		$error = null;

		$poll_id = (int)$this->input->get('poll_id');
		$page = (int)$this->input->get('page');

		if (!$poll_id) {
			$error = '非法操作';
			goto doReturn;
		}

		if ($page < 1) {
			$page = 1;
		}

		if (!$this->canAccessPoll($poll_id, $uid)) {
			$error = '您没有权限进行此操作';
			goto doReturn;
		}

		$length = 10;
		$offset = ($page-1) * $length;

		doReturn:
			return array($error, $poll_id, $offset, $length);
	}

	/**
	 * 添加对问答的投票的数据验证
	 */
	function addVote($uid)
	{
		$error = null;

		$option_id = (int)$this->input->get('option_id');
		$poll_id = (int)$this->input->get('poll_id');

		if (!$option_id || !$poll_id) {
			$error = '非法操作';
			goto doReturn;
		}

		if (!$this->canAccessPoll($poll_id, $uid)) {
			$error = '您没有权限进行此操作';
			goto doReturn;
		}

		doReturn:
			return array($error,$poll_id,$option_id);
	}

	/**
	 * 取消某个对某个问题的所有投票时的数据验证
	 */
	function cancelVote($uid)
	{
		$error = null; 
		$poll_id = (int)$this->input->get('poll_id');

		if (!$poll_id) {
			$error = '非法操作';
			return array($error,$poll_id);
		}

		if (!$this->canAccessPoll($poll_id, $uid)) {
			$error = '您没有权限进行此操作';
			return array($error,$poll_id);
		}

		return array($error,$poll_id);
	}

	/**
	 * 得到列表
	 */
	function listVoter($uid)
	{
		$error = null;
		$length = 20;
		$offset = 0;

		$poll_id = (int)$this->input->get('poll_id');
		$option_id = (int)$this->input->get('option_id');
		$page = (int)$this->input->get('page');

		if(!$poll_id || !$option_id){
			$error = '非法操作';
			goto doReturn;
		}

		if (!$this->canAccessPoll($poll_id, $uid)) {
			$error = '您没有权限进行此操作';
			goto doReturn;
		}

		if ($page < 1) {
			$page = 1;
		}

		$offset = ($page-1) * $length;

		doReturn:
			return array($error, $poll_id, $option_id, $length, $offset);
	}

	/**
	 * 好友列表
	 */
	function listFriend()
	{
		$keyword = $this->input->get('keyword');
		$page = (int)$this->input->get('page');

		if ($page < 1) {
			$page = 1;
		}

		$length = 20;

		return array(trim($keyword), $page, $length);
	}

	/**
	 * 问好友
	 */
	function askFriend($uid)
	{
		$error = null;

		$poll_id = (int)$this->input->get('poll_id');
		$src_uid = $this->input->get('src_uid');

		if(!$poll_id){
			$error = '非法操作';
			goto doReturn;
		}

		if (!$this->canAccessPoll($poll_id, $uid)) {
			$error = '您没有权限进行此操作';
			goto doReturn;
		}

		$src_uid = array_filter(array_map('intval', explode(',',trim($src_uid))));

		if (!$src_uid) {
			$error = '亲，请选择好友';
			goto doReturn;
		}

		if (!$this->inter->isFriends($uid, $src_uid)) {
			$error = '非法操作,请选择好友';
			goto doReturn;
		}

		if (in_array($uid, $src_uid)) {
			$error = '非法操作,不能选择自己';
			goto doReturn;
		}

		doReturn:
			return array($error,$poll_id,$src_uid);
	}

	/**
	 * 添加评论
	 */
	function addComment($uid)
	{
		$error = NULL;

		$poll_id = (int)$this->input->get('frmid');
		$message = $this->input->get('message');
		$message = trim($message);

		if (strlen($message)<1) {
			$error = '请填写评论内容！';
			goto doReturn;
		}

		if (!$poll_id) {
			$error = 'error';
			goto doReturn;
		}

		if (!$this->canAccessPoll($poll_id, $uid)) {
			$error = '您没有权限进行此操作';
			goto doReturn;
		}

		doReturn:
			return array($error,$poll_id,$message);
	}

	/**
	 * 评论列表
	 */
	function listComments($uid)
	{
		$error = null;
		$limit = 10;
		$offset = 0;
		$poll_id = (int)$this->input->get('frmid');
		$nowpage = (int)$this->input->get('page');

		if(!$poll_id){
			$error = '非法操作！';
			goto doReturn;
		}
		if($nowpage <1){
			$nowpage = 1;
		}

		$offset = ($nowpage-1)*$limit;

		if (!$this->canAccessPoll($poll_id, $uid)) {
			$error = '您没有权限进行此操作';
			goto doReturn;
		}

		doReturn:
			return array($error,$poll_id,$limit,$offset);
	}

	/**
	 * 删除评论
	 */
	function delComment($uid)
	{
		$error = null ;

		$id = $this->input->get('id');
		$poll_id = $this->input->get('frmid');

		if(!$id || !$poll_id){
			$error = '非法操作';
			goto doReturn;
		}

		$commentInfo = $this->dbmodel->getComment($poll_id,$id);

		if(!$commentInfo){
			$error = '该帖子不存在或者已被删除！';
			goto doReturn;
		}
		else if ($commentInfo['uid'] != $uid) {
			$error = '您不能删除别人的评论';
			goto doReturn;
		}

		doReturn:
			return array($error,$id,$poll_id);
	}

	/**
	 * 在添加对某问答关注时数据验证
	 */
	function checkFollow($uid)
	{
		$error = null;

		$poll_id = (int)$this->input->get('object_id');

		if(!$poll_id){
			$error = '非法操作';
			goto doReturn;
		}

		if (!$this->canAccessPoll($poll_id, $uid)) {
			$error = '您没有权限进行此操作';
			goto doReturn;
		}

		doReturn:
			return array($error,$poll_id);
	}

	/**
	 * 关注列表
	 */
	function listFollow($uid)
	{
		$error = null;
		$limit = 50;
		$offset = 0;

		$poll_id = (int)$this->input->get('poll_id');
		$nowpage = (int)$this->input->get('page');

		if (!$poll_id) {
			$error = '非法操作';
			goto doReturn;
		}

		if ($nowpage<1) {
			$nowpage = 1;
		}

		$offset = ($nowpage-1)*$limit;

		if (!$this->canAccessPoll($poll_id, $uid)) {
			$error = '您没有权限进行此操作';
			goto doReturn;
		}

		doReturn:
			return array($error,$poll_id,$limit,$offset);
	}

	/**
	 * 判断用户是否有访问问答权限
	 */
	private function canAccessPoll($poll_id, $uid)
	{
		$poll = $this->dbmodel->getPoll($poll_id);

		if (!$poll) {
			return false;
		}

		switch ($poll['type']) {
			//信息流
		case '1' :
			break;
			//问答
		case '2' :
			if ($poll['perm'] == '1') {
				return true;
			}
			else {
				return $this->dbmodel->getAskListHas($uid, $poll_id);
			}
			//网页
		case '3' :
			break;
			//群组
		case '4' :
			return service('Group')->checkGruopMember($poll['perm'], $uid);
		}
	}
}
