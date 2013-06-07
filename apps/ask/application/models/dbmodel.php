<?php
namespace DK\Ask;

/**
 * 直接操作数据库的模型类(从原askmodel中分离出来)
 *
 * @author xuweihua jiangfangtao
 */
class Dbmodel extends \DK_Model
{
	static private $_cache_poll = array();

	function __construct()
	{
		parent::__construct();

		$this->init_db('ask');
	}

	/**
	 *  添加问答
	 */
	function addPoll($type, $uid, $perm, $title,  $multi, $allow, $addtime)
	{
		$data = array(
			'type' => $type,
			'uid' => $uid,
			'perm' => $perm,
			'title' => $title,
			'allow' => $allow,
			'multi' => $multi,
			'addtime' => $addtime,
		);

		$this->db->insert('ask_polls', $data);

		$data['id'] = $this->db->insert_id();

		return $data;
	}

	/**
	 * 删除问答
	 */
	function delPoll($poll_id,$uid)
	{
		$rs = $this->db->where('id',$poll_id)
			->where('uid',$uid)
			->delete('ask_polls');
		return $rs;
	}

	/**
	 * 得到问题
	 */
	function getPoll($id)
	{
		if (!isset(self::$_cache_poll[$id])) {
			$query = $this->db->select('id,type,uid,perm,title,allow,multi,addtime')
				->where('id', $id)
				->get('ask_polls');

			self::$_cache_poll[$id] = $query->row_array();
		}

		return self::$_cache_poll[$id];
	}

	/**
	 * 获得某人的问答动态列表
	 */
	function getAskList($uid, $offset, $length)
	{
		$rs = $this->db->where('uid',$uid)
			->limit($length, $offset)
			->order_by('id', 'DESC')
			->get('ask_lists')
			->result_array();

		return $rs;
	}

	/**
	 * 取得用户关于特定人的问答动态
	 *
	 * 用户$uid关于$target_uid的动态信息
	 */
	function getAskListByUid($uid, $target_uid, $offset, $length)
	{
		$rs = $this->db->where('uid', $uid)
			->where('from_uid', $target_uid)
			->limit($length, $offset)
			->order_by('id', 'DESC')
			->get('ask_lists')
			->result_array();

		return $rs;
	}

	/**
	 * 查看数据在不在我的列表中
	 */
	function getMyListHas($uid, $from_uid, $poll_id)
	{
		$query = $this->db->where('uid', $uid)
			->where('from_uid', $from_uid)
			->where('poll_id', $poll_id)
			->limit(1)
			->get('ask_lists');

		return $query->row_array();
	}

	/**
	 * 查到我的列表中有没有指定问答
	 */
	function getAskListHas($uid, $poll_id)
	{
		$query = $this->db->where('uid', $uid)
			->where('poll_id', $poll_id)
			->limit(1)
			->get('ask_lists');

		return (bool)$query->row_array();
	}

	/**
	 * 添加记录到用户动态表
	 */
	function addMyList($uid, $from_uid, $type, $poll_id)
	{
		$data = array(
			'uid' => $uid,
			'from_uid' => $from_uid,
			'type' => $type,
			'poll_id' => $poll_id,
			'addtime' => date('Y-m-d H:i:s')
		);

		return $this->db->insert('ask_lists',$data);
	}

	/**
	 * 指量添加记录到用户动态表
	 */
	function addMyLists($uids, $from_uid, $type, $poll_id)
	{
		$time = date('Y-m-d H:i:s');

		$data = array();
		foreach ($uids as $uid) {
			$data[] = array(
				'uid' => $uid,
				'from_uid' => $from_uid,
				'type' => $type,
				'poll_id' => $poll_id,
				'addtime' => $time
			);
		}

		return $this->db->insert_batch('ask_lists', $data);
	}

	/**
	 * 删除列表数据
	 */
	function delMyList($poll_id,$uid)
	{
		$rs = $this->db->where('poll_id',$poll_id)
			->where('uid',$uid)
			->delete('ask_lists');
		return $rs;
	}

	/**
	 * 添加到队列
	 */
	function addPushs($poll_id, $target_uid, $type)
	{
		$this->db->insert('ask_pushs', array(
			'poll_id' => $poll_id,
			'trigger_uid' => $target_uid,
			'type' => $type
		));
	}

	/**
	 * 问答发起人添加问答选项
	 */
	function addOptions(array $poll, $options)
	{
		if (!$options) {
			return array();
		}

		$data = array();
		foreach($options as $option) {
			$data[] = array(
				'poll_id' => $poll['id'], 
				'message' => $option,
				'uid' => $poll['uid'],
				'addtime' => $poll['addtime']
			);
		}

		$this->db->insert_batch('ask_options', $data);

		//insert_id反回第1个id
		$insert_id = $this->db->insert_id();

		foreach ($data as $key => $val) {
			$data[$key]['id'] = $insert_id++;
		}

		return $data;
	}

	/**
	 * 其他用户（非问答发起人）添加问答选项
	 */
	function addOption($poll_id,$message,$uid)
	{
		$data = array(
			'poll_id'=>$poll_id,
			'message'=>$message,
			'uid'=>$uid,
			'addtime'=>date('Y-m-d H:i:s'),
			'cache_votes'=>0
		);

		$rs = $this->db->insert('ask_options',$data);

		return $rs ? $this->db->insert_id() : false;
	}

	/**
	 * 
	 * 获得某个问答的选项数
	 */
	function getOptionsNum($poll_id)
	{
		$sql = 'select count(id) as num from ask_options where poll_id = '.$poll_id;
		$rs = $this->db->query($sql)->result_array();
		return $rs ? (int)$rs[0]['num'] : null;
	}

	/**
	 * 检查答案选项是存已经存在
	 */
	function optionExists($poll_id,$message)
	{
		$query = $this->db->select('id')
			->where('poll_id',$poll_id)
			->where('message',$message)
			->limit(1)
			->get('ask_options');

		$result = $query->row_array();

		return $result ? true : false;
	}

	/**
	 * 删除某个问答的某个选项
	 */
	function delOption($poll_id, $option_id)
	{
		//删除选项的投票
		$this->db->where('poll_id', $poll_id)
			->where('option_id', $option_id)
			->delete('ask_voters');


		$this->db->where('id',$option_id)
			->where('poll_id',$poll_id)
			->delete('ask_options');
	}

	/**
	 * 删除用户添加的投票票数为0的选项
	 */
	function delOptionsByUid($poll_id , $uid)
	{
		$this->db->where('poll_id',$poll_id)
			->where('uid',$uid)
			->where('cache_votes',0)
			->delete('ask_options');
	}

	/**
	 * 删除某个问答的所有选项
	 */
	function delOptions($poll_id,$uid)
	{
		$rs = $this->db->where('poll_id',$poll_id)
			->where('uid',$uid)
			->delete('ask_options');

		return $rs;
	}

	/**
	 * 根据问题id得到选项
	 */
	function getOptions($poll_id, $offset, $length)
	{
		$query = $this->db->select('id,poll_id,message,uid,addtime')
			->where('poll_id', $poll_id)
			->limit($length, $offset)
			->order_by('cache_votes', 'DESC')
			->get('ask_options');

		return $query->result_array();
	}

	/**
	 * 获得某个选项的信息
	 */
	function getOption($poll_id, $option_id)
	{
		return $this->db->where('poll_id',$poll_id)
			->where('id',$option_id)
			->get('ask_options')
			->row_array();
	}

	/**
	 * 得到选项投票人数
	 */
	function getOptionVotes($poll_id, $option_id)
	{
		$query = $this->db->select('count(id) as num')
			->where('poll_id', $poll_id)
			->where('option_id', $option_id)
			->get('ask_voters');

		$row = $query->row_array();

		return $row ? (int)$row['num'] : null;
	}

	/**
	 * 添加对某个选项的投票
	 * @param int $poll_id 问答的id
	 * @param int $options_id 选项id
	 * @param int $uid 投票人的uid
	 */
	function addVote($uid,$poll_id,$option_id)
	{
		//缓护投票数缓存
		$sql = "UPDATE ask_options
			SET cache_votes = cache_votes + 1
			WHERE poll_id = {$poll_id} AND id = {$option_id}";

		$this->db->simple_query($sql);

		//投票
		$data = array(
			'poll_id'=>$poll_id,
			'option_id'=>$option_id,
			'uid'=>$uid,
			'addtime'=>date('Y-m-d H:i:s')
		);

		return $this->db->insert('ask_voters',$data);
	}

	/**
	 * 得到投票人
	 */
	function getVoters($poll_id, $option_id, $offset, $length)
	{
		$query = $this->db->select('poll_id,option_id,uid,addtime')
			->where('poll_id', $poll_id)
			->where('option_id', $option_id)
			->limit($length, $offset)
			->get('ask_voters');

		return $query->result_array();
	}

	/**
	 * 得到用户投票
	 */
	function getVoter($poll_id, $option_id, $uid)
	{
		$query = $this->db->select('poll_id,option_id,uid,addtime')
			->where('poll_id', $poll_id)
			->where('option_id', $option_id)
			->where('uid', $uid)
			->get('ask_voters');

		return $query->row_array();
	}

	
	/**
	 * 获得某个用户对某个问题的所有投票的选项
	 */
	function getVotedOption($poll_id, $uid)
	{
		$sql = "SELECT B.id,B.poll_id,B.message,B.uid,B.addtime FROM ask_voters AS A
			LEFT JOIN ask_options AS B ON A.option_id = B.id
			WHERE A.poll_id = '{$poll_id}' AND A.uid = '{$uid}'";

		$query = $this->db->query($sql);

		return $query->result_array();
	}

	/**
	 * 获得某人已经投过票的选项的id
	 */
	function getVotedOptionId($poll_id,$uid){
		$rs = $this->db->select('option_id')
			->where('poll_id',$poll_id)
			->where('uid',$uid)
			->get('ask_voters')
			->result_array();
		return $rs;
	}

	/**
	 * 获得某个问答的选票数
	 */
	function getAskVotes($poll_id)
	{
		$sql = 'select count(DISTINCT uid) as num from ask_voters where poll_id='.$poll_id;
		$rs = $this->db->query($sql)->row_array();
		if($rs){
			return (int)$rs['num'];
		}
		return null;
	}

	/**
	 * 某人是否已经投过某问答某选项的票票了
	 */
	function hasVoted($poll_id,$option_id,$uid)
	{
		$rs = $this->getVoter($poll_id,$option_id,$uid);
		return $rs ? true : false;
	}

	/**
	 * 某人是否投过某个问答的票
	 */
	function hasVotedAsk($poll_id,$uid)
	{
		$rs = $this->db->where('poll_id',$poll_id)
			->where('uid',$uid)
			->get('ask_voters')
			->result_array();

		return $rs?true:false;
	}

	/**
	 * 得到用户对某个问答的投票数量
	 */
	function getAskVotedNum($poll_id, $uid)
	{
		$sql = "SELECT COUNT(id) AS num FROM ask_voters WHERE poll_id={$poll_id} AND uid = {$uid}";
		$rs = $this->db->query($sql)->row_array();
		if($rs){
			return (int)$rs['num'];
		}
		return null;
	}

	/**
	 * 得到选项投标数
	 */
	function getOptionVotedNum($poll_id , $option_id)
	{
		$sql = "SELECT COUNT(id) AS num FROM ask_voters WHERE poll_id={$poll_id} AND option_id = {$option_id}";
		$rs = $this->db->query($sql)->row_array();
		if($rs){
			return (int)$rs['num'];
		}
		return null;
	}

	/**
	 * 删除某个人关于某个问答的某个选项的投票信息
	 */
	function delVote($uid, $poll_id, $option_id)
	{
		//维护投票数缓存
		$sql = "UPDATE ask_options
			SET cache_votes = cache_votes - 1
			WHERE poll_id = {$poll_id} AND id = {$option_id}";

		$this->db->simple_query($sql);

		//取消投票
		$this->db->where('uid',$uid)
			->where('poll_id',$poll_id)
			->where('option_id',$option_id)
			->delete('ask_voters');

		return $this->db->affected_rows();
	}

	/**
	 * 取消所有投票
	 */
	function cancelVote($poll_id, $uid)
	{
		//维护缓存
		$optionsId = $this->getVotedOptionId($poll_id,$uid);

		if ($optionsId) {
			$options = array();
			foreach($optionsId as $val){
				$options[] = $val['option_id'];
			}

			$sql = "UPDATE ask_options
				SET cache_votes = cache_votes - 1
				WHERE poll_id = {$poll_id} AND id IN (".implode(',', $options).")";

			$this->db->simple_query($sql);
		}

		//取消投票
		$rs = $this->db->where('poll_id',$poll_id)
			->where('uid',$uid)
			->delete('ask_voters');

		return $rs;
	}

	/**
	 * 添加问答评论
	 * @param int $poll_id 问答的id
	 * @param int $uid     评论者的uid
	 * @param string $message 评论内容
	 */
	function addComment($poll_id,$uid,$message)
	{
		$data = array(
			'poll_id'=>$poll_id,
			'uid'=>$uid,
			'message'=>$message,
			'addtime'=>date('Y-m-d H:i:s')
		);

		$rs = $this->db->insert('ask_comments',$data);

		return $rs ? $this->db->insert_id() : false;
	}

	/**
	 * 获得评论列表
	 * @param $poll_id 问答的id
	 */
	function listComments($poll_id,$limit,$offset)
	{
		$rs = $this->db->where('poll_id',$poll_id)
			->limit($limit,$offset)
			->order_by('id','desc')
			->get('ask_comments')
			->result_array();

		return $rs;
	}

	/**
	 * 获取某个问答评论的信息
	 */
	function getComment($poll_id,$comment_id)
	{
		$commentInfo = $this->db->where('id',$comment_id)
			->where('poll_id',$poll_id)
			->get('ask_comments')
			->row_array();

		return $commentInfo;
	}

	/**
	 * 获得某个问答的评论的总数
	 * @param $poll_id 问答的id
	 */
	function getCommentsNum($poll_id)
	{
		$sql = 'select count(id) as num from ask_comments where poll_id='.$poll_id;
		$rs = $this->db->query($sql)->result_array();
		return $rs ? (int)$rs[0]['num'] : 0;
	}

	/** 
	 * 删除评论
	 * @param $id		评论的id
	 * @param $poll_id  问答的id
	 * @return bool
	 */
	function delComment($id, $poll_id)
	{
		$rs = $this->db->where('id',$id)
			->where('poll_id',$poll_id)
			->delete('ask_comments');

		return $rs;
	}

	/**
	 * 添加对某个问答的关注
	 * @param int $poll_id 问答的id
	 * @param int $uid 关注者的uid
	 */
	function addFollow($poll_id,$uid)
	{
		$data = array(
			'poll_id'=>$poll_id,
			'uid'=>$uid,
			'addtime'=>date('Y-m-d H:i:s')
		);

		return $this->db->insert('ask_follows',$data);
	}

	/**
	 * 是否关注问答
	 */
	function hasFollow($poll_id, $uid)
	{
		$query = $this->db->where('poll_id', $poll_id)
			->where('uid', $uid)
			->get('ask_follows');

		$row = $query->row_array();

		return $row ? true : false;
	}

	/**
	 * 取得关注某个问答的所有用户
	 *
	 * @return array(uid1, uid2, ...)
	 */
	function getPollFollowedUid($poll_id)
	{
		$rs = $this->db->select('uid')
			->where('poll_id',$poll_id)
			->get('ask_follows')
			->result_array();

		$uids = array();
		foreach ($rs as $row) {
			$uids[] = $row['uid'];
		}

		return $uids;
	}

	/**
	 * 得到问答关注人
	 */
	function listFollow($poll_id,$length,$offset)
	{
		$rs = $this->db->where('poll_id',$poll_id)
			->limit($length,$offset)
			->get('ask_follows')
			->result_array();

		return $rs;
	}

	/**
	 * 得到关注人数量
	 */
	function getFollowNum($poll_id)
	{
		$sql = 'select count(id) as num from ask_follows where poll_id = '.$poll_id;
		$rs = $this->db->query($sql)->row_array();
		return $rs?$rs['num']:0;
	}

	/**
	 * 取消(删除)对某个问答的关注
	 */
	function delFollow($poll_id,$uid)
	{
		$rs = $this->db->where('poll_id',$poll_id)
			->where('uid',$uid)
			->delete('ask_follows');
		return $rs;
	}
}
