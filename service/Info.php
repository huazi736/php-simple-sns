<?php



class InfoService extends DK_Service {
    const DEFAULT_PERMISION_PUBLIC = 1;
    const DEFAULT_INFO_FROM = 2;

    protected $info = NULL;
    protected $topic = NULL;

    // ================ Info Model Attributes ================
    //topic相关的权限
	const ONLY_FRIS = 4;
	const ONLY_FANS = 3;
	const ONLY_SELF = 8;
	const CUSTOM    = -1;
	const OPEN      = 1;
	
	//标识数据来源
//	const DATA_FROM_INFO = 1;
	
	//人与人之前的关系
	const FANS = 4;
	const FRIS = 1;
	
	//标识来源于信息流
	private $_from = 1;
	//清除数据时是否清除自己的
	private $_delSelf = true;
	
    // ================ Topic Model Attributes ================
    
    //实体的操作权限
//	const ONLY_FRIS = 4;
//	const ONLY_FANS = 3;
//	const CUSTOM = -1; 
//	const OPEN = 1;

    //数据来源
    const DATA_FROM_INFO = 1;
    const DATA_FROM_APPS = 2;

    private $tid = Null;
    private $allowTypes = array('event', 'blog', 'album', 'ask', 'link', 'forward', 'social', 'info', 'video');
    private $staticTypes = array('uinfo');

    protected $_timeline;
    protected $_relation;
    protected $_util;

    public function __construct() {
        parent::__construct();
        
        $this->init_redis();
        $this->helper('timeline');
    }
    
    public function test() {
        return 'info test';
    }


    // ================================
    // Info Action
    // ================================

//    public function __construct() {
////        $this->info = new InfoModel();
////        $this->topic = new TopicModel();
//        $this->_timeline = service('Timeline');
//        service('Relation') = service('Relation');
//        $this->_util = service('Util');
//        
//    }
//    

    /**
     * @author fbbin
     * @desc 信息发送给我的粉丝或者是好友
     * @param array $data
     * @param array $relations
     */
    public function infoToRelations($data = array(), $relations = array()) {
        if (!isset($data['permission'])) {
            $data['permission'] = self::DEFAULT_PERMISION_PUBLIC; //默认权限
        }
        if (!isset($data['from'])) {
            $data['from'] = self::DEFAULT_INFO_FROM; //非来源于信息流
        }
        return $this->_addInfo($data, $relations);
    }

    /**
     * @auther fbbin
     * @desc 更改信息实体的数据
     * @param array $data
     */
    public function updateTopic($data) {
        return $this->_updateTopic($data);
        //return $this->topic->updateTopic($data);
    }

    /**
     * @author fbbin
     * @desc 更改信息实体的热度值
     * @param string/int $tid
     * @param int $inc
     */
    public function updateHot($tid, $inc = 1) {
        //return $this->topic->updateHot($tid, $inc);
    }

    /**
     * @author fbbin
     * @desc 更改信息实体是否突出显示
     * @param int $tid
     * @param int $value
     */
    public function updateHighlight($tid = '', $value = 1) {
//        if (!$tid || !$value) {
//            return false;
//        }
//        return $this->topic->updateHighlight($tid, $value);
    }

    /**
     * @author fbbin
     * @desc 添加信息流数据
     * @param array $data
     * @param array $relations
     */
    private function _addInfo($data = array(), $relations = array()) {
        if (!$this->_strictCheckFields($data)) {
            return false;
        }

        $res = $this->_addTopic($data, $relations);
        if ($res === false) {
            return false;
        }
        $status = $this->_add(array_merge(array('tid' => $res['tid']), $data), $relations);
        return $status ? $res : false;
    }

    /**
     * @author fbbin
     * @desc 删除一条信息流数据
     * @param int $tid 信息实体ID || 应用FID
     * @param string $type
     */
    public function delInfo($tid = '', $type = '') {
        //映射关系删除实体
        if (!empty($type)) {
            $tid = $this->_getTidByMap($tid, $type);
        }
        if (!$tid) {
            return false;
        }
        //删除信息流个人数据列表TID
        if ($this->_delInfo($tid) === false) {
            return false;
        }
        
        //调用时间线删除数据接口
        service('Timeline')->_deletePoint($tid);

        //删除信息流数据实体
        if ($this->_delTopic($tid) === false) {
            return false;
        }
        return true;
    }

    /**
     * @author fbbin
     * @desc 根据映射关系返回一条完整的信息实体
     * @param string $fid
     * @param string $type
     */
    public function getTopicByMap($fid, $type) {
        return $this->_getTopicByFidAndType($fid, $type);
    }

    // ================================
    // Info Model
    // ================================

	/**
	 * @author fbbin
	 * @desc 添加信息流数据
	 * @param array $data
	 * @param string $to
	 * @param array $relations
	 */
	public function _add( $data, $relations = array() )
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
				return $this->_doSharePush($data, $relations, 'push');
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
		return $this->_doPush($data['uid'], $data['tid'], $data['dateline'], $data['permission'], $relations);
	}
	
	/**
	 * @author fbbin
	 * @desc 删除一条信息流数据
	 * @param string $uid
	 * @param string $dest
	 */
	public function _delInfo( $tid )
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
				return $this->_doSharePush($info, $relations, 'delpush');
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
		return $this->_doPush($info['uid'], $info['tid'], $info['dateline'], $info['permission'], $relations, 'delpush');
	}
	
	/**
	 * @author fbbin
	 * @desc 转发信息流权限和分发处理
	 * @param array $data
	 * @param array $relations
	 * @param string $style
	 */
	public function _doSharePush( array $data, array $relations, $style = 'push' )
	{
		if( empty($data) )
		{
			return false;
		}
		$shareStatus = true;
		switch (intval($data['forward']['permission']))
		{
			//获取两者的共同好友
			case self::ONLY_FRIS:
				$relations = array_intersect(service('Relation')->getAllFriends($data['uid']), service('Relation')->getAllFriends($data['forward']['uid']));
				$shareStatus = $this->_doSent($data['tid'], $data['dateline'], 'fris', $style, $relations);
			break;
			//获取两者的共同粉丝
			case self::ONLY_FANS:
				$relations = array_intersect(service('Relation')->getAllFollowers($data['uid']), service('Relation')->getAllFollowers($data['forward']['uid']));
				$shareStatus = $this->_doSent($data['tid'], $data['dateline'], 'fans', $style, $relations);
			break;
			//获取自定义情况下的共同关系人
			case self::CUSTOM:
				$relations = array_intersect(service('Relation')->getAllFriends($data['uid']), $relations);
				foreach( $relations as $value )
				{
					$frisStatus = $this->_doSentUser($data['tid'], $data['dateline'], $value, 'fris', $style);
				}
				$shareStatus = $frisStatus;
			break;
		}
		return $shareStatus && $this->_doSentUser($data['tid'], $data['dateline'], $data['uid'], 'self', $style);
	}

	/**
	 * @author fbbin
	 * @desc 解除人物关系后调用处理用户的数据
	 * @param int $fromUid
	 * @param int $toUid
	 * @param int $relation 1:好友，4：粉丝
	 */
	public function _delRelationsTopic($fromUid, $toUid, $relation = 4)
	{
		//import("social.Action.SocialAction");
		//$relation == self::FRIS && import('social.Model.FriendModel');
		//$relation == self::FANS && import('social.Model.FollowerModel');
        $relationStartTime = service('Relation')->getStartTtimeOfUsers($fromUid, $toUid, $relation);
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
				$indexInfoKey = $this->_getIndexKey($fromUid, 'fans');
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
				$indexFrisInfo = $this->_getIndexKey($fromUid, 'fris');
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
	public function _updatePermission($tid, $newPermission = 1, $relationslist = array())
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
							$this->_doPush($topic['uid'], $topic['tid'], $topic['dateline'], $newPermission, $oldRelations, 'delpush');
							//完全添加新的用户
							$this->_doPush($topic['uid'], $topic['tid'], $topic['dateline'], $newPermission, $relationslist, 'push');
							unset($topic,$intersect,$oldRelations,$relationslist);
							return 111000;
						}
						else
						{
							//删除除了两者公共数据以外的用户
							$this->_doPush($topic['uid'], $topic['tid'], $topic['dateline'], $newPermission, array_diff($oldRelations, $intersect), 'delpush');
							//添加除了两者公共数据以外的用户
							$this->_doPush($topic['uid'], $topic['tid'], $topic['dateline'], $newPermission, array_diff($relationslist, $intersect), 'push');
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
							$followers = service('Relation')->getAllFollowers($topic['uid']);
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
							$allFris = service('Relation')->getAllFriends($topic['uid']);
							$allFollowers = service('Relation')->getAllFollowers($topic['uid']);
							//清除原先发送的好友信息
							$this->_doSent($tid, $topic['dateline'], 'fris', 'delpush', $allFris);
							//重新发送给粉丝用户
							$this->_doSent($tid, $topic['dateline'], 'fans', 'push', $allFollowers);
							unset($allFris, $allFollowers, $topic);
							return 111012;
							break;
						case self::ONLY_FANS:
							unset($topic);
							return true;
							break;
						case self::ONLY_SELF:
							return $this->_doPush($topic['uid'], $topic['tid'], $topic['dateline'], $newPermission);
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
							$allFollowers = service('Relation')->getAllFollowers($topic['uid']);
							//清除原先发送给粉丝的数据
							$this->_doSent($tid, $topic['dateline'], 'fans', 'delpush', $allFollowers);
							//重新发送给自定义的用户
							$this->_doSent($tid, $topic['dateline'], 'fris', 'push', $relationslist);
							unset($allFansFris, $topic);
							return 111013;
							break;
						case self::ONLY_FRIS:
							$allFris = service('Relation')->getAllFriends($topic['uid']);
							//清除两者差集的部分用户
							$this->_doSent($tid, $topic['dateline'], 'fris', 'delpush', array_diff($allFris, $relationslist));
							unset($allFris, $topic);
							return 111014;
							break;
						case self::ONLY_SELF:
							return $this->_doPush($topic['uid'], $topic['tid'], $topic['dateline'], $newPermission, $relationslist);
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
							$allFollowers = service('Relation')->getAllFollowers($topic['uid']);
							//清除原先发送给粉丝的数据
							$this->_doSent($tid, $topic['dateline'], 'fans', 'delpush', $allFollowers);
							$allFris = service('Relation')->getAllFriends($topic['uid']);
							//重新发送给好友用户
							$this->_doSent($tid, $topic['dateline'], 'fris', 'push', $allFris);
							unset($allFris, $allFollowers, $topic);
							return 111015;
							break;
						case self::CUSTOM:
							$allFris = service('Relation')->getAllFriends($topic['uid']);
							$hadSendUsers = isset($topic['relations']) ? json_decode($topic['relations']) : array();
							//增加发送给未在自定义列表中的好友
							$this->_doSent($tid, $topic['dateline'], 'fris', 'push', array_diff($allFris, $hadSendUsers));
							//删除自定义的用户列表
							$this->redis->hDel('Topic:'.$tid, 'relations');
							unset($allFris, $hadSendUsers, $topic);
							return 111016;
							break;
						case self::ONLY_SELF:
							return $this->_doPush($topic['uid'], $topic['tid'], $topic['dateline'], $newPermission);
							break;
					}
					break;
					//修改为仅自己可见
				case self::ONLY_SELF:
					//标识不要删除self里面自己的数据
					$this->_delSelf = false;
					$delStatus = $this->_delInfo( $tid );//删除发送出去的所有数据
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
	private function _getKey( $uid = '', $dest = 'fans', $time = '')
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
	private function _getIndexKey( $uid = '', $dest = 'fans')
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
	private function _doPush($uid, $tid, $dateline, $permission, $relations = array(), $style = 'push')
	{
		$pushStatus = true;
		switch ($permission) 
		{
			//自定义
			case self::CUSTOM:
				if( empty($relations) )
				{
					return false;
				}
				//自定义的人员列表是基于好友的，因此不需要做判断
				foreach( $relations as $value )
				{
					$fansStatus = $this->_doSentUser($tid, $dateline, $value, 'fris', $style);
				}
				$pushStatus = $fansStatus ;
			break;
			//公开
			case self::OPEN:
				$pushStatus = $this->_doSentFans($uid, $tid, $dateline, $style);
			break;
			//粉丝可见
			case self::ONLY_FANS:
				$pushStatus = $this->_doSentFans($uid, $tid, $dateline, $style);
			break;
			//仅好友可见
			case self::ONLY_FRIS:
				$pushStatus = $this->_doSentFans($uid, $tid, $dateline, $style);
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
		return $pushStatus && $this->_doSentUser($tid, $dateline, $uid, 'self', $style);
	}
	
	/**
	 * @author fbbin
	 * @desc 发送给好友模块
	 * @param string $uid
	 * @param int $tid
	 * @param int $dateline
	 */
	private function _doSentFris($uid, $tid, $dateline, $style)
	{
		$relationList = service('Relation')->getAllFriends($uid);
		return $this->_doSent($tid, $dateline, 'fris', $style, $relationList);
	}
	
	/**
	 * @author fbbin
	 * @desc 发送给粉丝
	 * @param string $uid
	 * @param int $tid
	 * @param int $dateline
	 * @param bool $friends
	 */
	private function _doSentFans($uid, $tid, $dateline, $style, $friends = false)
	{
		//取得粉丝中好友的人员
		if( $friends )
		{
			$relationList = service('Relation')->getAllFriends($uid);
		}
		//取得纯粉丝人员
		else
		{
			$relationList = service('Relation')->getAllFollowers($uid);
		}
		return $this->_doSent($tid, $dateline, 'fans', $style, $relationList);
	}
	
	/**
	 * @author fbbin
	 * @desc 发送给互相关注
	 * @param string $uid
	 * @param int $tid
	 * @param int $dateline
	 * @param bool $friends
	 */
	private function _doSentFansBoth($uid, $tid, $dateline, $style, $friends = false)
	{
		//取得互相关注中好友人员
		if( $friends )
		{
			$relationList = service('Relation')->getAllFriends($uid);
		}
		//取得纯互相关注人员
		else 
		{
			$relationList = service('Relation')->getAllBothFollowers($uid);
		}
		return $this->_doSent($tid, $dateline, 'fans_both', $style, $relationList);
	}
	
	/**
	 * @author fbbin
	 * @desc 发送给粉丝下面的好友
	 * @param string $uid
	 * @param int $tid
	 * @param int $dateline
	 */
	private function _doSentFansFris($uid, $tid, $dateline, $style)
	{
		$relationList = service('Relation')->getAllFriends($uid);
		return $this->_doSent($tid, $dateline, 'fans_fris', $style, $relationList);
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
	private function _doSent($tid, $dateline, $object, $style, $relationlist)
	{
		if( empty($relationlist) )
		{
			return true;
		}
		$dateline = intval($dateline);
		foreach ($relationlist as $value)
		{
			$key = $this->_getKey( $value, $object, $dateline );
			$indexKey = $this->_getIndexKey( $value, $object );
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
	private function _doSentUser($tid, $dateline, $touid, $type, $style)
	{
		$dateline = intval($dateline);
		$key = $this->_getKey( $touid, $type, $dateline );
		$indexKey = $this->_getIndexKey( $touid, $type );
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

    // ================================
    // Topic Model
    // ================================

    /**
     * @author fbbin, xwsoul
     * @desc 信息流/时间线主题数据的写入
     * @param array $data
     * @param string $type
     */
    public function _addTopic($data, $relations = array()) {
        //topic类型
        $type = $data['type'];
        $uid = $data['uid'];
        $date = date('Ymd');
        //更新成为好友/添加关注的信息
        if ($type === 'social') {
            $socialMapKey = $this->_getMapKey($type) . ':' . $uid;
            //这里会改变$data
            $isOldSocialTopic = $this->_isOldSocialTopic($socialMapKey, $date, $data);
            if ($isOldSocialTopic === true) {
                return $data;
            } else if ($isOldSocialTopic === null) {
                return false;
            }
        }
        $data = array_merge($data, array('tid' => $this->_inc(), 'hot' => 0, 'highlight' => 0));
        if ($this->_checkSpecialType($type)) {
            $data['hot'] = -1;
            $data['highlight'] = 1;
            $data['dateline'] = time();
        }
        //保存自定义的用户数据
        ((int) $data['permission'] == self::CUSTOM) && ($data['relations'] = json_encode($relations));
        //存储信息
        if ($this->redis->hMset($this->_getTopicKey(), $data) === false) {
            return false;
        }
        //DUMP KEYS
        $this->redis->zAdd('dump:topic', $data['dateline'], $this->tid);
        //添加映射关系
        if ($type !== 'info') {
            if ($type == 'forward')
                $this->redis->hSet($this->_getMapKey($type), $data['tid'], $data['fid']);
            else if ($type == 'social')
                $this->redis->hSet($socialMapKey, $date, $data['tid']);
            else
                $this->redis->hSet($this->_getMapKey($type), $data['fid'], $data['tid']);
        }
        //设置返回转发数据
        $type === 'forward' && ($data['forward'] = $this->_parserTopic($data['fid']));
        //格式化相册地址信息显示
        $type === 'album' && ($data['picurl'] = json_decode($data['picurl']));
        //时间友好显示
        $data['friendly_time'] = date('Ymd', $data['ctime']) == date('Ymd', $data['dateline']) ? '刚刚' : makeFriendlyTime($data['ctime']);
        return $data;
    }

    /**
     * 添加社交类的Topic
     * @param string
     * @param integer
     * @param array
     * @return bool
     */
    protected function _isOldSocialTopic($socialMapKey, $date, &$data) {
        $friends = array();
        $follows = array();
        /**
         * 数据不全
         */
        if (!($data['friend_name'] && $data['friend_uid'] && $data['friend_code'])
                && !($data['follow_name'] && $data['follow_uid'] && $data['follow_code'])) {
            return null;
        }
        //当日的 topic 已存在
        $tid = $this->redis->hGet($socialMapKey, $date);
        if ($tid) {
            $rs = $this->_getTopicByTid($tid);
            if ($rs) {
                $friends = json_decode($rs['friends'], true);
                $follows = json_decode($rs['follows'], true);
            }
        }
        //json 格式的数据
        if ($data['friend_uid']) {
            $friends[$data['friend_uid']] = array(
                'name' => $data['friend_name'],
                'code' => $data['friend_code']
            );
        } else if ($data['follow_uid']) {
            $follows[$data['follow_uid']] = array(
                'name' => $data['follow_name'],
                'code' => $data['follow_code']
            );
        }
        //因为不是返回data,所以需要复制给rs
        $rs['friends'] = $data['friends'] = json_encode($friends);
        $rs['follows'] = $data['follows'] = json_encode($follows);
        unset(
                $data['friend_name'], $data['friend_uid'], $data['friend_code'], $data['follow_name'], $data['follow_uid'], $data['follow_code']
        );
        //是否更新数据
        if ($tid) {
            $time = time();
            $this->redis->hMSet($this->_getTopicKey($tid), array(
                'friends' => $rs['friends'],
                'follows' => $rs['follows'],
                'ctime' => $time
            ));
            $data = $rs;
            
            //Update timeline point
            service('Timeline')->_updatePoint($tid, $time);
            
            return true;
        } else {
            //注销数据
            return false;
        }
    }

    /**
     * @author fbbin
     * @desc 更新信息实体的字段数据
     * @param array $data
     */
    public function _updateTopic($data, $relations = array()) {
        $fields = $this->_strictCheckFields($data, true);
        if (!$fields || count($data) < 3 || array_intersect(array_keys($data), $fields) !== array_keys($data)) {
            return false;
        }
        if ($data['fid']) {
            $topicKey = $this->_getTopicKey($this->_getTidByMap($data['fid'], $data['type']));
            unset($data['fid']);
        } else {
            return false;
        }
        if ($this->redis->exists($topicKey) === false) {
            return false;
        }
        //设置关系数据
        isset($data['relations']) && $data['relations'] = json_encode($relations);
        //更新字段数据
        if ($this->redis->hMset($topicKey, $data) === false) {
            return false;
        }
        unset($data, $topicKey);
        return true;
    }

    /**
     * @author fbbin
     * @desc 根据映射关系返回一条完整的信息实体
     * @param string $fid
     * @param string $type
     */
    public function _getTopicByFidAndType($fid, $type) {
        if (!$fid || !$type) {
            return false;
        }
        return $this->_getTopicByTid($this->_getTidByMap($fid, $type));
    }

    /**
     * @author fbbin
     * @desc 根据tid来获取信息实体
     * @param int $tid
     */
    public function _getTopicByTid($tid) {
        if (!$tid) {
            return false;
        }
        $topic = $this->_parserTopic($tid);
        if (!$topic) {
            return array();
        }
        return $topic;
    }

    /**
     * @author fbbin
     * @desc 特殊类型检测
     * @param string $type
     */
    public function _checkSpecialType($type = '') {
        return in_array($type, $this->staticTypes);
    }

    /**
     * @author fbbin
     * @desc 生成即将写入的实体数据的唯一主键
     */
    private function _inc() {
        $this->tid = $this->redis->incr('TopicID');
        return $this->tid;
    }

    /**
     * @desc 处理一条实体数据
     * @author fbbin
     * @param int $tid
     */
    private function _parserTopic($tid) {
        $data = $this->redis->hGetAll($this->_getTopicKey($tid)) ? : false;
        if ($data !== false) {
            //相册数据处理
            if ($data['type'] == 'album') {
                $data['picurl'] = json_decode($data['picurl']);
            }
            //活动数据处理
            elseif ($data['type'] == 'event') {
                $data['starttime'] = friendlyDate($data['starttime']);
            }
            //自定义关系数据处理
            ((int) $data['permission'] == self::CUSTOM) && ($data['relations'] = json_decode($data['relations']));
        }
        return $data;
    }

    /**
     * @author fbbin
     * @desc 获取实体数据的唯一主键
     */
    private function _getTopicKey($tid = '') {
        if (empty($tid)) {
            return "Topic:" . $this->tid;
        }
        return "Topic:" . $tid;
    }

    /**
     * @author fbbin
     * @desc 获取fid和实体TID映射关系
     * @param int $fid
     * @param string $type
     */
    private function _getMapKey($type) {
        return 'Map:' . strtolower($type);
    }

    /**
     * @author fbbin
     * @desc 更改信息实体中特殊键的值
     * @param int $tid
     * @param string $key
     * @param int $value
     */
    public function _updateSpecialKey($tid, $key, $value) {
        $allowKeys = array('ctime', 'highlight', 'hot', 'relations', 'permission');
        if (!in_array($key, $allowKeys)) {
            return false;
        }
        $topickey = $this->_getTopicKey($tid);
        if ($this->redis->exists($topickey) === false) {
            return false;
        }
        if ($key != 'hot') {
            return $this->redis->hSet($topickey, $key, $value) === 0 ? true : false;
        } else {
            return $this->redis->hIncrBy($topickey, $key, intval($value)) ? true : false;
        }
    }

    /**
     * @author fbbin
     * @desc 严格检测传入的字段信息
     * @param array $data
     */
    public function _strictCheckFields($data = '', $retrunFields = false) {
        if (!in_array($data['type'], array_merge($this->staticTypes, $this->allowTypes))) {
            return false;
        }
        $commonKeys = array('uid', 'dkcode', 'uname', 'from', 'type', 'permission', 'dateline', 'ctime');
        $fields = array(
            'blog' => array('fid', 'title', 'action', 'fname', 'furl', 'nameurl', 'content', 'url'),
            'album' => array('fid', 'title', 'content', 'photonum', 'picurl', 'url', 'note'),
            'info' => array('content', 'title'),
            'ask' => array('fid', 'title'),
            'event' => array('fid', 'title', 'photo', 'url', 'starttime'),
            'forward' => array('fid', 'title', 'content',),
            'social' => array('friend_name', 'friend_uid', 'friend_code', 'follow_name', 'follow_uid', 'follow_code'),
            'video' => array('fid', 'title', 'content', 'width', 'height', 'videourl', 'imgurl', 'url'),
            'uinfo' => array('fid', 'content', 'subtype', 'info'),
        );
        $keys = array_merge($fields[$data['type']], $commonKeys);
        if ($retrunFields) {
            return $keys;
        }
        sort($keys);
        $dataFields = array_keys($data);
        sort($dataFields);
        if ($dataFields === $keys) {
            if (array_intersect($commonKeys, array_keys(array_filter($data))) != $commonKeys) {
                return false;
            }
            return true;
        }
        return false;
    }

    /**
     * @author fbbin
     * @desc 删除一条信息实体
     * @param string/int $tid
     */
    public function _delTopic($tid = '') {
        if (empty($tid)) {
            return false;
        }
        $infos = $this->redis->hMGet($this->_getTopicKey($tid), array('fid', 'tid', 'type'));
        //删除映射关系
        if ($infos['type'] != 'info') {
            if ($infos['type'] == 'forward') {
                $this->redis->hDel($this->_getMapKey($infos['type']), $infos['tid']);
            } else {
                $this->redis->hDel($this->_getMapKey($infos['type']), $infos['fid']);
            }
        }
        unset($infos);
        return $this->redis->del($this->_getTopicKey($tid));
    }

    /**
     * @auther fbbin
     * @desc 根据映射关系来获取TID
     * @param int $fid
     * @param int $uid
     * @param string $type
     */
    public function _getTidByMap($fid, $type) {
        $tid = $this->redis->hGet($this->_getMapKey($type), $fid);
        return $tid ? : false;
    }

    /**
     * @author fbbin
     * @desc 异步保存到disk
     */
    public function __destruct() {
        $this->redis->bgsave();
    }

}