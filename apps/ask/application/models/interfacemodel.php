<?php 
namespace Dk\Ask;

/**
 * @description ask模块调用的外部接口模型类 (从原来helper等文件迁移过来)
 * @author jiangfangtao(jiangfangtao@duankou.com)
 * @date 2012/06/25
 */
class Interfacemodel extends \DK_Model{

	/**
	 * 用户信息缓存
	 */
	static private $_cache_userinfo = array();

	/**
	 * 判断是否好友关系
	 */
	function isFriend($uid1, $uid2) {
		return service('Relation')->isFriend($uid1, $uid2);
	}

	/**
	 * 批量判断好友关系
	 */
	function isFriends($uid, $uids)
	{
		$re = service('Relation')->checkMultiRelation($uid, $uids, 'friend');

		foreach ($re as $val) {
			if ($val != 1) {
				return false;
			}
		}

		return true;
	}

	/**
	 * 判断关注过他
	 */
	function isFollowed($uid1, $uid2)
	{
		return service('Relation')->isFollowing($uid1, $uid2);
	}

	/**
	 * 获取好友列表
	 */
	function getFriendList($uid)
	{
		return service('Relation')->getAllFriends($uid);
	}

	/**
	 * 获取好友列表   详细信息
	 */
	function getAllFriendsWithInfo($uid)
	{
		return service('Relation')->getAllFriendsWithInfo($uid);
	}

	/**
	 * 获取我关注的人列表
	 */
	function getFollowingList($uid)
	{
		return service('Relation')->getAllFollowings($uid);
	}

	/**
	 *
	 * 获取我关注的人列表  包含用户详细信息
	 */
	function getAllFollowingsWithInfo($uid)
	{
		return service('Relation')->getAllFollowingsWithInfo($uid);
	}

	/**
	 *
	 * 批量返回用户信息
	 * @param string | array $uids  用户ID '1,2,3,4' array('1','2','3')
	 * @param array $return_fields  返回字段
	 * @param int $index
	 * @param int $size
	 * @param 返回数据
	 */
	function getUserList($uids, $return_fields = array('uid', 'username', 'dkcode'), $index = 0, $size = 0)
	{
		return service('User')->getUserList($uids , $return_fields , $index , $size);
	}

	/**
	 * 获取某两个人之间的关系
	 */
    function getRelationStatus($uid, $uid2){
    	return service('Relation')->getRelationStatus($uid,$uid2);
	}
	/**
	 * 获取与多个目标用户的关系状态
	 */
	function getMultiRelationStatus($uid, $touid)
	{
		return service('Relation')->getMultiRelationStatus($uid, $touid);
	}

	/**
	 * 回答问答时产生关系
	 *
	 * @param int $uid      回答人
	 * @param int $src_uid  提问人
	 */
	function addVoteToRelation($uid, $src_uid)
	{
		service('Relation')->updateFollowTime($uid, $src_uid);
	}

	/**
	 * 问好友动作通知
	 */
	function askFriendSendNotice($poll, $uid, $src_uid)
	{
		$this->_sendNotice($poll, $uid, $src_uid, 'ask', 'ask_you');
	}

	/**
	 * 回答问答时发送通知
	 */
	function addVoteSendNotice($poll, $uid, $followers)
	{
		$this->_sendNotice($poll, $uid, $followers, 'ask', 'ask_commentyoufollow');
	}

	/**
	 * 添评论时给相关人员发送通知
	 */
	function addCommentSendNotice($poll, $uid, $followers)
	{
		$this->_sendNotice($poll, $uid, $followers, 'ask', 'ask_commentyoureply');
	}

	/**
	 * 发送通知
	 */
	private function _sendNotice($poll, $uid, $touid, $atype='ask', $stype)
	{
		if (empty($touid)) {
			return;
		}

		$touid = array_filter($touid, function($val) use($uid) {
			return ($val != $uid);
		});

		$data = array(
			'name' => $poll['title'],
			'url' => mk_url('ask/ask/detail', array(
				'poll_id' => $poll['id'],
				'from' => 'notice',
				'dkcode' => $this->getDKcodeByUid($poll['uid'])
			))
		);

		service('Notice')->add_notice(1, $uid, $touid, $atype, $stype, $data);
	}

	
	/**
	 * 提出问答时添加到时间线
	 */
	function addAskToTimeline(array $poll, array $options)
	{
		$data = array(
		    'type' => 'ask',
		    'uid' => $poll['uid'],
            'fid' => $poll['id'],
            'uname' => $this->getUsernameByUid($poll['uid']),
	        'title' => htmlspecialchars($poll['title']),
            'permission' => $poll['perm'],
            'dateline' => strtotime($poll['addtime']),
	        'dkcode' =>  $this->getDkcodeByUid($poll['uid']),
	     );

		return service('Timeline')->addTimeLine($data);
	}

	/**
	 * 回答问题时线时间线发送动态
	 */
	function addVoteToTimeline($uid, array $poll, array $options)
	{
		$_options = array();
		foreach ($options as $option) {
			$_options[] = $option['message'];
		}

		$time = time();

		$data = array(
			'type' => 'answer',
			'from' => 5,
			'permission' => 4,
			'fid' => $poll['id'],
			'title' => $poll['title'],
			'uid' => $uid,
			'dkcode' => $this->getDkcodeByUid($uid),
			'uname' => $this->getUsernameByUid($uid),
			'dateline' => $time,
			'ctime' => $time,
			'answers' => $_options
		);

		api('Timeline')->addTimeLine($data);
	}

	/**
	 * 取消了所有问答时时间线动态
	 */
	function delVoteToTimeline($uid, $poll_id)
	{
		$data = array(
			'uid' => $uid,
			'type' => 'answer',
			//问题ID
			'index' => $poll_id,
			//回答时间
			'ctime' => time(),
		);

		api('Timeline')->removeMultiItem($data);
	}

	/**
	 * 删除时间线里的问答
	 *
	 * @param fid 问答ID
	 * @param key 要更新的key名称
	 * @param value 要更新的key值
	 * @param type
	 */
	function delAskToTimeline($id, $uid)
	{
		return service('Timeline')->removeTimeline($id, $uid, 'ask');
	}

	/**
	 * 得到用户信息,带缓存
	 */
	function getUserInfo($uid)
	{
		if (!isset(self::$_cache_userinfo[$uid])) {
			self::$_cache_userinfo[$uid] = service('User')->getUserInfo($uid);
		}

		return self::$_cache_userinfo[$uid];
	}

	/**
	 * 得到dkcode,带缓存
	 */
	function getDkcodeByUid($uid)
	{
		$info = $this->getUserInfo($uid);

		if (!$info) {
			return null;
		}

		return $info['dkcode'];
	}

	/**
	 * 得到用户名,带缓存
	 */
	function getUsernameByUid($uid)
	{
		$info = $this->getUserInfo($uid);

		if (!$info) {
			return null;
		}

		return $info['username'];
	}

	function getUserUrl($uid)
	{
		return  mk_url('main/index/main', array('dkcode' => $this->getDkcodeByUid($uid)));
	}

	/**
	 * 积分:提问
	 */
	function credit_ask()
	{
		service('credit')->ask();
	}

	/**
	 * 积分:删除
	 */
	function credit_del()
	{
		service('credit')->ask(false);
	}

}


