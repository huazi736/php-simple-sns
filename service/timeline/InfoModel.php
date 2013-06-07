<?php
/**
* [ Duankou Inc ]
* Created on 2012-3-5
* @author fbbin
* The filename : InfoModel.class.php   2012 02:51:58
*/
class InfoModel extends DkModel
{
	//topic相关的权限
	const ONLY_FRIS = 4;
	const ONLY_FANS = 3;
	const ONLY_SELF = 8;
	const CUSTOM    = -1;
	const OPEN      = 1;
	
	//标识数据来源
	const DATA_FROM_INFO = 1;
	
	//人与人之前的关系
	const FANS = 4;
	const FRIS = 1;
	
	//标识来源于信息流
	private $_from = 1;
	//清除数据时是否清除自己的
	private $_delSelf = true;
	
	public function __initialize()
	{
		// $this->init_redis();
		$this->init_db('user');
	}

	public function test()
	{
		return $this->db->table('user_info')->field('username')->select();
		// $a = $this->db->table('article')->where(array('id'=>34))->limit(1)->select();
	}

	/**
	 * @author fbbin
	 * @desc 添加信息流数据
	 * @param array $data
	 * @param string $to
	 * @param array $relations
	 */
	public function add( $data, $relations = array() )
	{
		if( $data['type'] == 'forward' )
		{
			$info = $this->redis->hGetAll('Topic:' . $data['fid']);
			if( ! $info )
			{
				return false;
			}
			if( $info['permission'] != self::OPEN || $info['permission'] != self::ONLY_SELF)
			{
				$data['forward'] = $info;
				return $this->doSharePush($data, $relations, 'push');
			}
		}
		//仅好友并且是非应用的数据时才需要往自己的INBOX中写数据
		// if( (int)$data['permission'] === self::ONLY_FRIS && (int)$data['from'] === self::DATA_FROM_INFO )
		// {
		// 	$key = $this->getKey( $data['uid'], 'fris', $data['dateline'] );
		// 	$indexKey= $this->getIndexKey( $data['uid'], 'fris' );
		// 	$zaddStatus = $this->redis->zAdd($key, $data['dateline'], $data['tid']);
		// 	if( $zaddStatus !== false )
		// 	{
		// 		$incrStatus = $this->redis->hIncrBy($indexKey, date('Ym'), 1);
		// 		if( $incrStatus === false )
		// 		{
		// 			return false;
		// 		}
		// 	}
		// }
		$this->_from = (int)$data['from'];
		return $this->doPush($data['uid'], $data['tid'], $data['dateline'], $data['permission'], $relations);
	}
	
	/**
	 * @author fbbin
	 * @desc 删除一条信息流数据
	 * @param string $uid
	 * @param string $dest
	 */
	public function delInfo( $tid )
	{
		$info = $this->redis->hGetAll('Topic:'.$tid);
		if( empty($info) )
		{
			return false;
		}
		$relations = array();
		if( (int)$info['permission'] == self::CUSTOM )
		{
			$relations = json_decode($info['relations']);
		}
		if( $info['type'] == 'forward' )
		{
			$topic = $this->redis->hGetAll('Topic:' . $info['fid']);
			if( !$topic )
			{
				return false;
			}
			if( $topic['permission'] != self::OPEN || $topic['permission'] != self::ONLY_SELF)
			{
				$info['forward'] = $topic;
				return $this->doSharePush($info, $relations, 'delpush');
			}
		}
		// if ( (int)$info['permission'] === self::ONLY_FRIS )
		// {
		// 	$infoKey = $this->getKey( $info['uid'], 'fris' );
		// 	$infoIndexKey = $this->getIndexKey( $info['uid'], 'fris' );
		// 	//检测是否存在该TID
		// 	if ( $this->redis->zScore($infoKey, $info['tid']) !== false )
		// 	{
		// 		//删除该TID
		// 		if( $this->redis->zRem($infoKey, $info['tid'] ) !== false  )
		// 		{
		// 			//索引数据值递减
		// 			$this->redis->hIncrBy($infoIndexKey, date('Ym', $info['dateline']), -1);
		// 		}
		// 	}
		// }
		$this->_from = (int)$info['from'];
		return $this->doPush($info['uid'], $info['tid'], $info['dateline'], $info['permission'], $relations, 'delpush');
	}
	
	/**
	 * @author fbbin
	 * @desc 转发信息流权限和分发处理
	 * @param array $data
	 * @param array $relations
	 * @param string $style
	 */
	public function doSharePush( array $data, array $relations, $style = 'push' )
	{
		if( empty($data) )
		{
			return false;
		}
		$social = DKBase::import('Social');
		$shareStatus = true;
		switch (intval($data['forward']['permission']))
		{
			//获取两者的共同好友
			case self::ONLY_FRIS:
				DKBase::import('Friend', 'social', false);
				$relations = array_intersect($social->getAllFriends($data['uid']), $social->getAllFriends($data['forward']['uid']));
				$shareStatus = $this->doSent($data['tid'], $data['dateline'], 'fris', $style, $relations);
			break;
			//获取两者的共同粉丝
			case self::ONLY_FANS:
				DKBase::import('Follower', 'social', false);
				$relations = array_intersect($social->getAllFollowers($data['uid']), $social->getAllFollowers($data['forward']['uid']));
				$shareStatus = $this->doSent($data['tid'], $data['dateline'], 'fans', $style, $relations);
			break;
			//获取自定义情况下的共同关系人
			case self::CUSTOM:
				$relations = array_intersect($social->getAllFriends($data['uid']), $relations);
				foreach( $relations as $value )
				{
					$frisStatus = $this->doSentUser($data['tid'], $data['dateline'], $value, 'fris', $style);
				}
				$shareStatus = $frisStatus;
			break;
		}
		return $shareStatus && $this->doSentUser($data['tid'], $data['dateline'], $data['uid'], 'self', $style);
	}

	/**
	 * @author fbbin
	 * @desc 解除人物关系后调用处理用户的数据
	 * @param int $fromUid
	 * @param int $toUid
	 * @param int $relation 1:好友，4：粉丝
	 */
	public function delRelationsTopic($fromUid, $toUid, $relation = 4)
	{
		//import("social.Action.SocialAction");
		$social = DKBase::import('Social');
		//$relation == self::FRIS && import('social.Model.FriendModel');
		//$relation == self::FANS && import('social.Model.FollowerModel');
		$relationStartTime = $social->getStartTtimeOfUsers($fromUid, $toUid, $relation);
		if ($relationStartTime === false)
		{
			return false;
		}
		$relationStartTime = date('Ym', $relationStartTime);
		$toObjectInfoKeys = $this->redis->keys('Info:'.$toUid.':self:*');
		//对key进行过滤，避免不必要的操作
		$toObjectInfoKeys = array_filter($toObjectInfoKeys, function($key) use($relationStartTime){
			if( substr($key, strrpos($key, ':')+1) >= $relationStartTime )
			{
				return true;
			}
			return false;
		});
		$removeStatus = true;
		switch ( (int)$relation )
		{
			case self::FANS :
				$indexInfoKey = $this->getIndexKey($fromUid, 'fans');
				foreach ($toObjectInfoKeys as $key)
				{
					$date = substr($key, strrpos($key, ':') + 1 );
					$values = $this->redis->zRange($key, 0, -1) ?: array();
					$indexKey = "Info:".$fromUid.":fans:".$date;
					foreach ($values as $val)
					{
						if( $this->redis->zDelete($indexKey, $val) )
						{
							$this->redis->hIncrBy($indexInfoKey, $date, -1);
						}
						else
						{
							$removeStatus = false;
						}
					}
				}
				return $removeStatus;
			break;
			case self::FRIS :
				$indexFrisInfo = $this->getIndexKey($fromUid, 'fris');
				foreach( $toObjectInfoKeys as $key )
				{
					$date = substr($key, strrpos($key, ':') + 1 );
					$outputKey = 'tmp:ruit:'.$fromUid;
					//求出只写给好友的信息数据
					$this->redis->zInter($outputKey, array('Info:'.$toUid.':self:'.$date, 'Info:'.$fromUid.':fris:'.$date), array(1, 1), 'SUM');
					$values = $this->redis->zRange($outputKey, 0, -1) ?: array();
					$indexFriKey = "Info:".$fromUid.":fris:".$date;
					foreach( $values as $val )
					{
						if( $this->redis->zDelete($indexFriKey, $val) )
						{
							$this->redis->hIncrBy($indexFrisInfo, $date, -1);
						}
					}
					$this->redis->setTimeout($outputKey, 5);
				}
				return $removeStatus;
			break;
			default:
				return false;
			break;
		}
	}
	
	/**
	 * @author fbbin
	 * @desc 修改信息实体的权限值
	 * @param intval $tid
	 * @param intval $newPermission
	 * @param arrray $relationslist
	 */
	public function updatePermission($tid, $newPermission = 1, $relationslist = array())
	{
		$topic = $this->redis->hGetAll('Topic:'.$tid);
		if( empty($topic) )
		{
			return false;
		}
		$this->_from = (int)$topic['from'];
		//当权限修改是自己本身时，这时关系的数据量在发生变化，因此需要修改（这里有很多重复的耗性能的操作）
		if( (int)$topic['permission'] == $newPermission )
		{
			switch ($newPermission)
			{
				//修改为公开
				case self::OPEN:
					//修改为粉丝可见
				case self::ONLY_FANS:
					//修改为好友可见
				case self::ONLY_FRIS:
					//修改为仅自己
				case self::ONLY_SELF:
					return true;
					break;
					//修改为自定义
				case self::CUSTOM:
					if( empty($relationslist) )
					{
						unset($topic);
						return false;
					}
					$oldRelations = isset($topic['relations']) ? json_decode($topic['relations']) : array();
					sort($oldRelations);sort($relationslist);
					if( $oldRelations !== $relationslist )
					{
						$intersect = array_intersect($oldRelations, $relationslist);
						if( empty($intersect) )
						{
							//删除以前自定义的全部用户
							$this->doPush($topic['uid'], $topic['tid'], $topic['dateline'], $newPermission, $oldRelations, 'delpush');
							//完全添加新的用户
							$this->doPush($topic['uid'], $topic['tid'], $topic['dateline'], $newPermission, $relationslist, 'push');
							unset($topic,$intersect,$oldRelations,$relationslist);
							return 111000;
						}
						else
						{
							//删除除了两者公共数据以外的用户
							$this->doPush($topic['uid'], $topic['tid'], $topic['dateline'], $newPermission, array_diff($oldRelations, $intersect), 'delpush');
							//添加除了两者公共数据以外的用户
							$this->doPush($topic['uid'], $topic['tid'], $topic['dateline'], $newPermission, array_diff($relationslist, $intersect), 'push');
							unset($topic,$intersect,$oldRelations,$relationslist);
							return 111001;
						}
					}
					else
					{
						return true;
					}
					break;
			}
		}
		else
		{
			$social = DKBase::import('Social');
			DKBase::import('Friend','social', false);
			DKBase::import('Following','social', false);
			DKBase::import('Follower','social', false);
			switch ( $newPermission )
			{
				//修改为公开
				case self::OPEN:
					//修改为粉丝可见
				case self::ONLY_FANS:
					switch ( intval($topic['permission']) )
					{
						case self::OPEN:
							unset($topic);
							return true; //由公开修改为粉丝可见不需要做任何修改
							break;
						case self::CUSTOM:
							$hadSendUsers = isset($topic['relations']) ? json_decode($topic['relations']) : array();
							$followers = $social->getAllFollowers($topic['uid']);
							//清除原先发送的自定义用户
							$this->doSent($tid, $topic['dateline'], 'fris', 'delpush', $hadSendUsers);
							//重新发送给粉丝用户
							$this->doSent($tid, $topic['dateline'], 'fans', 'push', $followers);
							//删除自定义的用户列表
							$this->redis->hDel('Topic:'.$tid, 'relations');
							unset($followers, $hadSendUsers, $topic);
							return 111011;
							break;
						case self::ONLY_FRIS:
							$allFris = $social->getAllFriends($topic['uid']);
							$allFollowers = $social->getAllFollowers($topic['uid']);
							//清除原先发送的好友信息
							$this->doSent($tid, $topic['dateline'], 'fris', 'delpush', $allFris);
							//重新发送给粉丝用户
							$this->doSent($tid, $topic['dateline'], 'fans', 'push', $allFollowers);
							unset($allFris, $allFollowers, $topic);
							return 111012;
							break;
						case self::ONLY_FANS:
							unset($topic);
							return true;
							break;
						case self::ONLY_SELF:
							return $this->doPush($topic['uid'], $topic['tid'], $topic['dateline'], $newPermission);
							break;
					}
					break;
					//修改为自定义
				case self::CUSTOM:
					if( empty($relationslist) )
					{
						unset($topic);
						return false;
					}
					switch ( intval($topic['permission']) )
					{
						case self::OPEN:
							//公开情况下和仅粉丝可见的操作相同
						case self::ONLY_FANS:
							$allFollowers = $social->getAllFollowers($topic['uid']);
							//清除原先发送给粉丝的数据
							$this->doSent($tid, $topic['dateline'], 'fans', 'delpush', $allFollowers);
							//重新发送给自定义的用户
							$this->doSent($tid, $topic['dateline'], 'fris', 'push', $relationslist);
							unset($allFansFris, $topic);
							return 111013;
							break;
						case self::ONLY_FRIS:
							$allFris = $social->getAllFriends($topic['uid']);
							//清除两者差集的部分用户
							$this->doSent($tid, $topic['dateline'], 'fris', 'delpush', array_diff($allFris, $relationslist));
							unset($allFris, $topic);
							return 111014;
							break;
						case self::ONLY_SELF:
							return $this->doPush($topic['uid'], $topic['tid'], $topic['dateline'], $newPermission, $relationslist);
							break;
					}
					break;
					//修改为仅好友可见
				case self::ONLY_FRIS:
					switch ( intval($topic['permission']) )
					{
						case self::OPEN:
							//公开情况下和仅粉丝可见的操作相同
						case self::ONLY_FANS:
							$allFollowers = $social->getAllFollowers($topic['uid']);
							//清除原先发送给粉丝的数据
							$this->doSent($tid, $topic['dateline'], 'fans', 'delpush', $allFollowers);
							$allFris = $social->getAllFriends($topic['uid']);
							//重新发送给好友用户
							$this->doSent($tid, $topic['dateline'], 'fris', 'push', $allFris);
							unset($allFris, $allFollowers, $topic);
							return 111015;
							break;
						case self::CUSTOM:
							$allFris = $social->getAllFriends($topic['uid']);
							$hadSendUsers = isset($topic['relations']) ? json_decode($topic['relations']) : array();
							//增加发送给未在自定义列表中的好友
							$this->doSent($tid, $topic['dateline'], 'fris', 'push', array_diff($allFris, $hadSendUsers));
							//删除自定义的用户列表
							$this->redis->hDel('Topic:'.$tid, 'relations');
							unset($allFris, $hadSendUsers, $topic);
							return 111016;
							break;
						case self::ONLY_SELF:
							return $this->doPush($topic['uid'], $topic['tid'], $topic['dateline'], $newPermission);
							break;
					}
					break;
					//修改为仅自己可见
				case self::ONLY_SELF:
					//标识不要删除self里面自己的数据
					$this->_delSelf = false;
					$delStatus = $this->delInfo( $tid );//删除发送出去的所有数据
					if ( $delStatus && $topic['permission'] == '-1')
					{
						//删除自定义的用户列表
						$this->redis->hDel('Topic:'.$tid, 'relations');
					}
					unset($topic);
					return $delStatus;
					break;
			}
		}
	}
	
	/**
	 * @author fbbin
	 * @desc 获取信息流主题操作KEY
	 * @param string $uid
	 * @param string $dest
	 */
	private function getKey( $uid = '', $dest = 'fans', $time = '')
	{
		$unixTime = $time ?: time();
		switch ($dest)
		{
			case 'fans' : $key = "Info:".$uid.":fans:".date('Ym', $unixTime);break;
			case 'fans_fris' : $key = "Info:".$uid.":fans_fris:".date('Ym', $unixTime);break;
			case 'fans_both' : $key = "Info:".$uid.":fans_both:".date('Ym', $unixTime);break;
			case 'fris' : $key = "Info:".$uid.":fris:".date('Ym', $unixTime);break;
			case 'self' : $key = "Info:".$uid.":self:".date('Ym', $unixTime);break;
		}
		return $key;
	}
	
	/**
	 * @author fbbin
	 * @desc 获取信息流主题操作索引KEY
	 * @param string $uid
	 * @param string $dest
	 */
	private function getIndexKey( $uid = '', $dest = 'fans')
	{
		switch ($dest)
		{
			case 'fans' : $indexKey = "Info:".$uid.":fansInfos";break;
			case 'fans_fris' : $indexKey = "Info:".$uid.":fans_frisInfos";break;
			case 'fans_both' : $indexKey = "Info:".$uid.":fans_bothInfos";break;
			case 'fris' : $indexKey = "Info:".$uid.":frisInfos";break;
			case 'self' : $indexKey = "Info:".$uid.":selfInfos";break;
		}
		return $indexKey;
	}
	
	/**
	 * @author fbbin
	 * @desc 从粉丝或者是好友的INBOX中删除我的某条数据整个过程交由队列处理)
	 * @param string $uid
	 * @param int $tid
	 * @param int $dateliine
	 * @param int $permission
	 * @param array $relations
	 */
	private function doPush($uid, $tid, $dateline, $permission, $relations = array(), $style = 'push')
	{
		DKBase::import('Friend','social', false);
		DKBase::import('Following','social', false);
		DKBase::import('Follower','social', false);
		$pushStatus = true;
		switch ($permission) 
		{
			//自定义
			case self::CUSTOM:
				$social = DKBase::import('Social');
				if( empty($relations) )
				{
					return false;
				}
				//自定义的人员列表是基于好友的，因此不需要做判断
				foreach( $relations as $value )
				{
					$fansStatus = $this->doSentUser($tid, $dateline, $value, 'fris', $style);
				}
				$pushStatus = $fansStatus ;
			break;
			//公开
			case self::OPEN:
				$pushStatus = $this->doSentFans($uid, $tid, $dateline, $style);
			break;
			//粉丝可见
			case self::ONLY_FANS:
				$pushStatus = $this->doSentFans($uid, $tid, $dateline, $style);
			break;
			//仅好友可见
			case self::ONLY_FRIS:
				$pushStatus = $this->doSentFris($uid, $tid, $dateline, $style);
			break;
			//仅自己可见
			case self::ONLY_SELF:
				$pushStatus = true;
			break;
		}
		//此外要记录自己发布的所有数据
		if( $style == 'delpush' && !$this->_delSelf )
		{
			return $pushStatus;
		}
		return $pushStatus && $this->doSentUser($tid, $dateline, $uid, 'self', $style);
	}
	
	/**
	 * @author fbbin
	 * @desc 发送给好友模块
	 * @param string $uid
	 * @param int $tid
	 * @param int $dateline
	 */
	private function doSentFris($uid, $tid, $dateline, $style)
	{
		$social = DKBase::import('Social');
		$relationList = $social->getAllFriends($uid);
		return $this->doSent($tid, $dateline, 'fris', $style, $relationList);
	}
	
	/**
	 * @author fbbin
	 * @desc 发送给粉丝
	 * @param string $uid
	 * @param int $tid
	 * @param int $dateline
	 * @param bool $friends
	 */
	private function doSentFans($uid, $tid, $dateline, $style, $friends = false)
	{
		$social = DKBase::import('Social');
		//取得粉丝中好友的人员
		if( $friends )
		{
			$relationList = $social->getAllFriends($uid);
		}
		//取得纯粉丝人员
		else
		{
			$relationList = $social->getAllFollowers($uid);
		}
		return $this->doSent($tid, $dateline, 'fans', $style, $relationList);
	}
	
	/**
	 * @author fbbin
	 * @desc 发送给互相关注
	 * @param string $uid
	 * @param int $tid
	 * @param int $dateline
	 * @param bool $friends
	 */
	private function doSentFansBoth($uid, $tid, $dateline, $style, $friends = false)
	{
		$social = DKBase::import('Social');
		//取得互相关注中好友人员
		if( $friends )
		{
			$relationList = $social->getAllFriends($uid);
		}
		//取得纯互相关注人员
		else 
		{
			$relationList = $social->getAllBothFollowers($uid);
		}
		return $this->doSent($tid, $dateline, 'fans_both', $style, $relationList);
	}
	
	/**
	 * @author fbbin
	 * @desc 发送给粉丝下面的好友
	 * @param string $uid
	 * @param int $tid
	 * @param int $dateline
	 */
	private function doSentFansFris($uid, $tid, $dateline, $style)
	{
		$social = DKBase::import('Social');
		$relationList = $social->getAllFriends($uid);
		return $this->doSent($tid, $dateline, 'fans_fris', $style, $relationList);
	}
	
	/**
	 * @author fbbin
	 * @desc 执行对象的分发/删除
	 * @param int $tid
	 * @param int $dateline
	 * @param string $style
	 * @param string $object
	 * @param string $style
	 * @param array $relationlist
	 */
	private function doSent($tid, $dateline, $object, $style, $relationlist)
	{
		if( empty($relationlist) )
		{
			return true;
		}
		$dateline = intval($dateline);
		foreach ($relationlist as $value)
		{
			$key = $this->getKey( $value, $object, $dateline );
			$indexKey = $this->getIndexKey( $value, $object );
			if( $style === 'push' )
			{
				if ( $this->redis->zAdd($key, $dateline, $tid) )
				{
					$this->redis->hIncrBy($indexKey, date('Ym', $dateline), 1);
				}
			}
			else if( $style === 'delpush' )
			{
				if ( $this->redis->zDelete($key, $tid) )
				{
					$this->redis->hIncrBy($indexKey, date('Ym', $dateline), -1);
				}
			}
		}
		return true;
	}
	
	/**
	 * @author fbbin
	 * @desc 发送给指定的人
	 * @param string $uid
	 * @param int $tid
	 * @param int $dateline
	 * @param string $type
	 */
	private function doSentUser($tid, $dateline, $touid, $type, $style)
	{
		$dateline = intval($dateline);
		$key = $this->getKey( $touid, $type, $dateline );
		$indexKey = $this->getIndexKey( $touid, $type );
		if($style === 'push')
		{
			$status = $this->redis->zAdd($key, $dateline, $tid);
			$inc = 1;
		}
		else
		{
			$status = $this->redis->zRem($key, $tid);
			$inc = -1;
		}
		if( $status )
		{
			$this->redis->hIncrBy($indexKey, date('Ym', $dateline), $inc);
		}
		return true;
	}
	
	/**
	 * @author fbbin
	 * @desc 异步保存到disk
	 */
	public function __destruct()
	{
		// $this->redis->bgsave();
	}
	
}

?>