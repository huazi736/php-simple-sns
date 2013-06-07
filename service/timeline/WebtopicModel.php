<?php
/**
* [ Duankou Inc ]
* Created on 2012-3-5
* @author fbbin
* The filename : WebtopicModel.class.php 03:47:50
*/

class WebtopicModel extends RedisModel {

	private $tid = Null;
	private $allowTypes = array('album', 'info', 'forward', 'video', 'wiki','event');
	private $staticTypes = array('uinfo');

	//标识来源于百科
	const TOPIC_FROM_WIKI = 5;
	const TOPIC_WIKI_TYPE = 'wiki';
	const TOPIC_FROM_APP = 4;

	/**
	 * @author fbbin
	 * @desc 网页信息流数据的写入
	 * @param array $data
	 * @param string $type
	 */
	public function addWebtopic( array $data )
	{
		$data = array_merge($data, array('tid'=>$this->_inc(),'hot'=>0, 'highlight'=>0));
		//来自于百科的信息流默认最大化显示
		if( $data['type'] == self::TOPIC_WIKI_TYPE )
		{
			$data['highlight'] = 1;$data['from'] = self::TOPIC_FROM_WIKI;
		}
		$data['dateline'] = strtotime($data['dateline']);
		if( $this->_redis->hMset($this->getWebtopicKey(), $data) === false ) {
			return false;
		}
		//DUMP KEYS
		$this->_redis->zAdd('dump:webtopic', $data['dateline'], $this->tid);
		//添加映射关系
		if( $data['type'] !== 'info' )
		{
			$this->_redis->hSet($this->getMapKey($data['type']), $data['fid'], $data['tid']);
		}
		//数据返回设置开始
		$data['type'] === 'forward' && ($data['forward'] = $this->_parseWebtopic($data['fid']));
		//格式化相册地址信息显示
		$data['type'] === 'album' && ($data['picurl'] = json_decode($data['picurl']));
		//返回时间友好显示
		$friendlyTime = $data['ctime']==date('YmdHis', $data['dateline'])?$data['dateline']:$data['ctime'];

		$data['friendly_time'] = makeFriendlyTime($friendlyTime);
		$data['friendly_line'] = makeFriendlyTime($data['dateline']);
		$data['dateline'] = date('YmdHis',$data['dateline']);
		$data['ymd'] = parseTime($data['ctime']);
		return $data;
	}

	/**
	 * @author fbbin
	 * @desc 生成即将写入的实体数据的唯一主键
	 */
	private function _inc()
	{
		$this->tid = $this->_redis->incr('WebtopicID');
		return $this->tid;
	}

	/**
	 * @desc 处理转发数据
	 * @author fbbin
	 * @param int $tid
	 */
	private function _parseWebtopic( $tid )
	{
		$data = $this->_redis->hGetAll( $this->getWebtopicKey($tid) ) ?: false;
		if( $data !== false )
		{
			//转发相册数据处理
			if( $data['type'] == 'album' )
			{
				$data['picurl'] = json_decode($data['picurl']);
			}
			//转发活动数据处理
			elseif ($data['type'] == 'event')
			{
				$data['starttime'] = friendlyDate($data['starttime']);
			}
		}
		return $data;
	}

	/**
	 * @author fbbin
	 * @desc 获取实体数据的唯一主键
	 */
	private function getWebtopicKey( $tid = '' )
	{
		if( empty($tid) )
		{
			return "Webtopic:".$this->tid;
		}
		return "Webtopic:".$tid;
	}

	/**
	 * @author fbbin
	 * @desc 获取fid和实体TID映射关系
	 * @param int $fid
	 * @param string $type
	 */
	private function getMapKey($type)
	{
		return 'Webmap:'.strtolower($type);
	}

	/**
	 * @author fbbin
	 * @desc 获取完整的信息实体
	 * @param int $fid
	 * @param string $type
	 */
	public function getWebtopic($tid, $type)
	{
		if( ! $tid  )
		{
			return false;
		}
		if( $type && in_array($type, $this->allowTypes))
		{
			$tid = $this->getTidByMap($tid, $type);
		}
		return $this->_parseWebtopic( $tid )  ?: array();
	}

	/**
	 * @auther fbbin
	 * @desc 更新信息实体的字段数据
	 * @param array $data
	 */
	public function updateWebtopic( $data )
	{
		$fields = $this->strictCheckFields($data, true);
		if( !$fields || array_intersect(array_keys($data), $fields) !== array_keys($data) )
		{
			return false;
		}
		if( $data['fid'] )
		{
			$topicKey = $this->getWebtopicKey( $this->getTidByMap($data['fid'], $data['type']) );
			unset($data['fid'],$data['type']);
		}
		else
		{
			return false;
		}
		if( $this->_redis->exists($topicKey) === false )
		{
			return false;
		}
		//更新字段数据
		if( $this->_redis->hMset($topicKey, $data) === false )
		{
			return false;
		}
		unset($data, $topicKey, $fields);
		return true;
	}

	/**
	 * @author fbbin
	 * @desc 更改信息实体中特殊键的值
	 * @param int $tid
	 * @param string $key
	 * @param int $value
	 */
	public function updateSpecialKey($tid, $key, $value)
	{
		$allowKeys = array('ctime', 'highlight', 'hot');
		if( ! in_array($key, $allowKeys) )
		{
			return false;
		}
		$topickey = $this->getWebtopicKey($tid);
		if($this->_redis->exists($topickey) === false)
		{
			return false;
		}
		if( $key != 'hot' )
		{
			return $this->_redis->hSet($topickey, $key, $value) === 0 ? true : false;
		}
		else 
		{
			return $this->_redis->hIncrBy($topickey, $key, intval($value) ) ? true : false;
		}
	}

	/**
	 * @author fbbin
	 * @desc 严格检测传入的字段信息
	 * @param array $data
	 */
	public function strictCheckFields( $data = '', $retrunFields = false )
	{
		if( ! in_array($data['type'], array_merge($this->staticTypes, $this->allowTypes) ) ) {
			return false;
		}
		$commonKeys = array('uid','dkcode','uname','pid','type','from','dateline','ctime');
		$fields = array(
			'album'=>array('fid','title','content','photonum','timedesc','picurl','url','note'),
			'info'=>array('content','timedesc','title'),
			'wiki'=>array('content','timedesc'),
			'event'=>array('fid','title','photo','url','starttime','timedesc'),
			'forward'=>array('fid','title','content',),
			'video'=>array('fid','title','content','width','height','videourl','imgurl','url','timedesc'),
			'uinfo'=>array('fid','content','subtype','info','timedesc'),
		);
		$keys = array_merge($fields[$data['type']], $commonKeys);
		if( $retrunFields ) {
			return $keys;
		}
		sort($keys);
		$dataFields = array_keys($data);
		sort($dataFields);
		if( $dataFields === $keys) {
			if( array_intersect($commonKeys, array_keys(array_filter($data))) != $commonKeys ) {
				return false;
			}
			return true;
		}
		return false;
	}

	/**
	 * @author fbbin
	 * @desc 特殊类型检测
	 * @param string $type
	 */
	public function checkSpecialType( $type = '' )
	{
		return in_array($type, $this->staticTypes);
	}

	/**
	 * @auther fbbin
	 * @desc 根据映射关系来获取TID
	 * @param int $fid
	 * @param string $type
	 */
	public function getTidByMap($fid, $type)
	{
		$tid = $this->_redis->hGet($this->getMapKey($type), $fid);
		return $tid ?: false;
	}

	/**
	 * @author fbbin
	 * @desc 删除网页上的一条信息实体
	 * @param string/int $tid
	 */
	public function delWebtopic( $tid = '' )
	{
		if( empty($tid) )
		{
			return false;
		}
		$infos = $this->_redis->hMGet($this->getWebtopicKey($tid), array('fid', 'type'));
		//删除映射关系
		if( $infos['type'] != 'info' )
		{
			$this->_redis->hDel( $this->getMapKey($infos['type']), $infos['fid'] );
		}
		unset($infos);
		return $this->_redis->del( $this->getWebtopicKey( $tid ) );
	}

	/**
	 * @author fbbin
	 * @desc 异步保存到disk
	 */
	public function __destruct()
	{
		$this->_redis->bgsave();
	}

}
