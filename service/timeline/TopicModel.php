<?php
/**
* [ Duankou Inc ]
* Created on 2012-3-5
* @author fbbin
* The filename : TopicModel.class.php   ����03:47:50
*/

class TopicModel extends DkModel
{

	//实体的操作权限
	const PER_FRIS = 4;
	const PER_FANS = 3;
	const PER_CUSTOM = -1;
	const PER_OPEN = 1;

	//数据来源
	const TOPIC_FROM_INFO = 1;
	const TOPIC_FROM_APPS = 2;

	private $tid = Null;
	/**
	 * 允许写入的数据类型
	 * @var array
	 */
	private $allowTypes = array(
		'event', 'blog', 'album', 'ask', 'link',
		'forward', 'info', 'video', 'sharevideo', 'change',
		'social', 'answer', 'join'
	);
	/**
	 * 复合数据类型: 多个数据保存在一个字段中
	 * @var array
	 */
	private $multiDataTypes = array('social', 'answer', 'join');
	private $staticTypes = array('uinfo');

	public function __initialize() {
		$this->init_redis();
	}

	/**
	 * 获取符合数据类型存储周期(Map映射的依据)
	 *
	 * @param string
	 * @param integer
	 * @return string
	 */
	protected function getMultiMapKey($type, $time) {
		switch ($type) {
			default:
				$format = 'Ym';
				break;
		}
		return date($format, $time);
	}

	/**
	 * @author fbbin, xwsoul
	 * @desc 信息流/时间线主题数据的写入
	 * @param array $data
	 * @param string $type
	 */
	public function addTopic( $data, $relations = array() ) {
		//topic类型
		$type = $data['type'];
		$uid = $data['uid'];
		$date = $this->getMultiMapKey($type, $data['ctime']);
		//复合数据类型: 多个数据保存在一个字段中
		$multiDataTypes = $this->multiDataTypes;
		//更新成为好友/添加关注的信息
		if( in_array($type, $multiDataTypes)) {
			//获取MapKey
			$multiDataMapKey = $this->getMapKey($type).':'.$uid;
			//这里会改变$data
			$exists = $this->exists($multiDataMapKey, $date, $data);
			//如果是数据已存在或操作失败则不继续Action中接下来的操作(已存在的情况下:方法内部完成时间轴更新)
			if($exists === true || $exists === null) {
				return false;
			}
		}
		$data = array_merge($data, array('tid'=>$this->_inc(),'hot'=>0, 'highlight'=>0));
		if( $this->checkSpecialType($type) ) {
			$data['hot'] = -1;$data['highlight'] = 1;$data['dateline'] = time();
		}
		//保存自定义的用户数据
		((int)$data['permission'] == self::PER_CUSTOM) && ($data['relations'] = json_encode($relations));
		//存储信息
		if( $this->redis->hMset($this->getTopicKey(), $data) === false ) {
			return false;
		}
		//DUMP KEYS
		$this->redis->zAdd('dump:topic', $data['dateline'], $this->tid);
		//添加映射关系: 写入Map数据
		if( $type !== 'info' ) {
			if($type == 'forward')
				$this->redis->hSet($this->getMapKey($type), $data['tid'], $data['fid']);
			else if(in_array($type, $multiDataTypes))
				$this->redis->hSet($multiDataMapKey, $date, $data['tid']);
			else
				$this->redis->hSet($this->getMapKey($type), $data['fid'], $data['tid']);
		}
		//设置返回转发数据
		$type === 'forward' && ($data['forward'] = $this->_parserTopic($data['fid']));
		//格式化相册地址信息显示
		$type === 'album' && ($data['picurl'] = json_decode($data['picurl']));
		//时间友好显示
		$data['friendly_time'] = date('Ymd',$data['ctime']) == date('Ymd',$data['dateline']) ? '刚刚' :makeFriendlyTime($data['ctime']);
		return $data;
	}

	/**
	 * 符合数据类型的数据是否存在 (暂时没有用到
	 * @param array  数据库存在的数据
	 * @param string 数据
	 * @param mixed  数据的值
	 * @return integer -1 不存在, 其他存在
	 */
	protected function multiItemExists(&$data, $key, $value) {

		$index = -1;
		if(!empty($data)) {
			foreach($data as $idx => $val) {
				if($val[$key] == $value) {
					$index = $idx;
					break;
				}
			}
		}
		return $index;

	}

	/**
	 * social 类型的字段数据是否足够
	 * 供 $this->exists() 使用
	 * @return bool
	 */
	protected function isEnoughSocial(&$data) {
		$socialAllow = array('friend','follow','web');
		if(!($data['obj_name'] && $data['obj_uid'] && $data['obj_code'])
			|| !in_array($data['union'], $socialAllow)) {
			return false;
		} else {
			return true;
		}
	}

	/**
	 * 保存 social 类型的字段使用
	 * 供 $this->exists() 使用
	 * @return array
	 */
	protected function processDataSocial(&$data, &$rs, $tid) {
		if($rs) {
			$friends = json_decode($rs['friends'], true);
			$follows = json_decode($rs['follows'], true);
			$webs = json_decode($rs['webs'], true);
		} else {
			$friends = array();
			$follows = array();
			$webs = array();
		}
		switch($data['union']) {
			case 'friend':
				$friends[$data['obj_uid']] = array(
					'name' => $data['obj_name'],
					'code' => $data['obj_code'],
					'time' => $data['ctime'],
				);
				break;
			case 'follow':
				$follows[$data['obj_uid']] = array(
					'name' => $data['obj_name'],
					'code' => $data['obj_code'],
					'time' => $data['ctime'],
				);
				break;
			case 'web':
				$webs[$data['obj_code']] = array(
					'name' => $data['obj_name'],
					'owner' => $data['obj_uid'],
					'time' => $data['ctime'],
				);
				break;
		}
		$data['friends'] = json_encode($friends);
		$data['follows'] = json_encode($follows);
		$data['webs']    = json_encode($webs);
		//因为是引用操作data,所以需要赋值给rs来替换data的数据
		if($rs) {
			$rs['friends'] = $data['friends'];
			$rs['follows'] = $data['follows'];
			$rs['webs']    = $data['webs'];
		}
		unset($data['union'], $data['obj_uid'], $data['obj_code'], $data['obj_name']);
		//返回要更新的数据
		return array(
			'friends' => $data['friends'],
			'follows' => $data['follows'],
			'webs' => $data['webs'],
		);
	}

	/**
	 * answer 类型的字段数据是否足够
	 * 供 $this->exists() 使用
	 * @return bool
	 */
	protected function isEnoughAnswer(&$data) {
		return true;
	}

	/**
	 * 保存 answer 类型的字段使用
	 * 供 $this->exists() 使用
	 * @return array
	 */
	protected function processDataAnswer(&$data, &$rs, $tid) {
		$d = array();
		if($rs) {
			$d = json_decode($rs['questions'], true);
		}
		$d[$data['fid']] = array(
			'title' => $data['title'],
			'answers' => json_encode($data['answers']),
			'time' => $data['ctime'],
		);
		$data['questions'] = json_encode($d);
		//因为是引用操作data,所以需要赋值给rs来替换data的数据
		if($rs) {
			$rs['questions'] = $data['questions'];
		}
		unset($data['fid'], $data['title'], $data['answers']);
		//返回要更新的数据
		return array(
			'questions' => $data['questions'],
		);
	}

	/**
	 * join 类型的字段数据是否足够
	 * 供 $this->exists() 使用
	 * @return bool
	 */
	protected function isEnoughJoin(&$data) {
		return true;
	}

	/**
	 * 保存 join 类型的字段使用
	 * 供 $this->exists() 使用
	 * @return array
	 */
	protected function processDataJoin(&$data, &$rs, $tid) {
		$d = array();
		if($rs) {
			$d = json_decode($rs['events'], true);
		}
		$d[$data['fid']] = array(
			'title' => $data['title'],
			'cover' => $data['cover'],
			'time' => $data['ctime'],
		);
		$data['events'] = json_encode($d);
		//因为是引用操作data,所以需要赋值给rs来替换data的数据
		if($rs) {
			$rs['events'] = $data['events'];
		}
		unset($data['fid'], $data['title'], $data['cover']);
		//返回要更新的数据
		return array(
			'events' => $data['events'],
		);
	}

	/**
	 * 重复
	 * @param integer
	 * @param integer
	 * @param array
	 * @return bool
	 */
	protected function exists($mapKey, $date, &$data) {
		//初始化变量
		$rs = array();
		$funSuf = ucfirst($data['type']);
		//参数不足
		if(!$this->{"isEnough".$funSuf}($data)) {
			return null;
		}
		//通过mapKey获取指定时间内的tid
		$tid = $this->redis->hGet($mapKey, $date);
		if($tid) {
			$rs = $this->getTopicByTid($tid);
		}
		//仅当$tid存在时, updatedData 的返回值才有意义
		$updatedData = $this->{"processData".$funSuf}($data, $rs, $tid);
		//是否更新数据
		if($tid) {
			$this->redis->hMSet(
				$this->getTopicKey($tid),
				array_merge($updatedData, array('ctime'=>$data['ctime']))
			);
			$axis = new AxisModel();
			$axis->updatePoint($tid, $data['ctime']);
			$rs['ctime'] = $data['ctime'];
			$data = $rs;
			return true;
		} else {
			//注销数据
			return false;
		}
	}

	/**
	 * 删除多条目型数据中的条目
	 *
	 * @param array
	 * @return bool
	 */
	public function removeMultiItem(&$data) {
		//user id
		$uid = $data['uid'];
		//topic type
		$type = $data['type'];
		//需要修改的字段
		$field = isset($data['field'])?$data['field']:'';
		//字段索引值
		$index = $data['index'];
		//类型不符
		if(!$data['ctime'] || !$uid || !$type || !in_array($type, $this->multiDataTypes))
			return false;
		//获取字段名称
		if(!($field = $this->getMultiItemKey($type, $field)))
			return false;
		//通过映射关系找到tid
		$multiDataMapKey = $this->getMapKey($type).':'.$uid;
		$date = $this->getMultiMapKey($type, $data['ctime']);
		$tid = $this->redis->hGet($multiDataMapKey, $date);
		if(!$tid)
			return false;
		//获取topic 数据
		$topic = $this->getTopicByTid($tid);
		if(!$topic || $topic['uid']!=$uid)
			return false;
		$data = json_decode($topic[$field], true);
		if(!isset($data[$index]))
			return false;
		unset($data[$index]);
		//删除 Or 更新
		if(count($data)>0) {
			$this->redis->hSet(
				$this->getTopicKey($tid),
				$field,
				json_encode($data)
			);
		} else {
			if(!$this->redis->hDel($multiDataMapKey, $date)) {
				return false;
			}
			$axis = new AxisModel;
			if (!$axis->deletePoint($tid)) {
				return false;
			}
			$info = new InfoModel();
			$info->delInfo($tid);
			$this->delTopic($tid);
		}
		return true;
	}

	/**
	 * 获取多字段中需要更新的字段
	 * @return string
	 */
	protected function getMultiItemKey($type, $field) {
		if($type !== 'social') {
			switch($type){
				case 'answer':
					$field = 'answers';
					break;
				case 'join':
					$field = 'events';
					break;
				default:
					$field = '';
					break;
			}
		} else {
			if(!in_array($field, array('follows','friends','webs'))) {
				$field = '';
			}
		}
		return $field;
	}

	/**
	 * @author fbbin
	 * @desc 更新信息实体的字段数据
	 * @param array $data
	 */
	public function updateTopic( $data, $relations = array() )
	{
		$fields = $this->strictCheckFields($data, true);
		if( !$fields || count($data) < 3 || array_intersect(array_keys($data), $fields) !== array_keys($data) )
		{
			return false;
		}
		if( $data['fid'] )
		{
			$topicKey = $this->getTopicKey( $this->getTidByMap($data['fid'], $data['type']) );
			unset($data['fid']);
		}
		else 
		{
			return false;
		}
		if( $this->redis->exists($topicKey) === false )
		{
			return false;
		}
		//设置关系数据
		isset( $data['relations'] ) && $data['relations'] = json_encode($relations);
		//更新字段数据
		if( $this->redis->hMset($topicKey, $data) === false )
		{
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
	public function getTopicByFidAndType( $fid, $type )
	{
		if( !$fid || !$type )
		{
			return false;
		}
		return $this->getTopicByTid( $this->getTidByMap($fid, $type) );
	}
	
	/**
	 * @author fbbin
	 * @desc 根据tid来获取信息实体
	 * @param int $tid
	 */
	public function getTopicByTid( $tid )
	{
		if( !$tid )
		{
			return false;
		}
		$topic = $this->_parserTopic($tid);
		if( ! $topic )
		{
			return array();
		}
		return $topic;
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
	 * @author fbbin
	 * @desc 生成即将写入的实体数据的唯一主键
	 */
	private function _inc()
	{
		$this->tid = $this->redis->incr('TopicID');
		return $this->tid;
	}

	/**
	 * @desc 处理一条实体数据
	 * @author fbbin
	 * @param int $tid
	 */
	private function _parserTopic( $tid )
	{
		$data = $this->redis->hGetAll( $this->getTopicKey($tid) ) ?: false;
		if( $data !== false )
		{
			//相册数据处理
			if( $data['type'] == 'album' )
			{
				$data['picurl'] = json_decode($data['picurl']);
			}
			//活动数据处理
			elseif ($data['type'] == 'event')
			{
				$data['starttime'] = friendlyDate($data['starttime']);
			}
			//自定义关系数据处理
			((int)$data['permission'] == self::PER_CUSTOM) && ($data['relations'] = json_decode($data['relations']));
		}
		return $data;
	}

	/**
	 * @author fbbin
	 * @desc 获取实体数据的唯一主键
	 */
	private function getTopicKey( $tid = '' ) {
		if( empty($tid) ) {
			return "Topic:".$this->tid;
		}
		return "Topic:".$tid;
	}

	/**
	 * @author fbbin
	 * @desc 获取fid和实体TID映射关系
	 * @param int $fid
	 * @param string $type
	 */
	private function getMapKey($type) {
		return 'Map:'.strtolower($type);
	}

	/**
	 * @author fbbin
	 * @desc 更改信息实体中特殊键的值
	 * @param int $tid
	 * @param string $key
	 * @param int $value
	 */
	public function updateSpecialKey($tid, $key, $value) {

		$allowKeys = array('ctime', 'highlight', 'hot', 'relations', 'permission');
		if( ! in_array($key, $allowKeys) ) {
			return false;
		}
		$topickey = $this->getTopicKey($tid);
		if( $this->redis->exists($topickey) === false ) {
			return false;
		}
		if( $key != 'hot' ) {
			return $this->redis->hSet($topickey, $key, $value) === 0 ? true : false;
		} else {
			return $this->redis->hIncrBy($topickey, $key, intval($value) ) ? true : false;
		}
	}

	/**
	 * @author fbbin
	 * @desc 严格检测传入的字段信息
	 * @param array $data
	 */
	public function strictCheckFields( $data = '', $retrunFields = false ) {
		if( ! in_array($data['type'], array_merge($this->staticTypes, $this->allowTypes) ) ) {
			return false;
		}
		/**
		 * from说明:
		 * 1：来源于时间线的发表框
		 * 2：来源于时间线的各个应用
		 * 3：来于网页时间线发表框
		 * 4：来于网页各个应用
		 * 5: 系统直接发送
		 */
		$commonKeys = array('uid','dkcode','uname','from','type','permission','dateline','ctime');
		$fields = array(
			'blog'=>array('fid','title','action','fname','furl','nameurl','content','url'),
			'album'=>array('fid','title','content','photonum','picurl','url','note'),
			'info'=>array('content','title'),
			'ask'=>array('fid','title'),
			'event'=>array('fid','title','photo','url','starttime'),
			'forward'=>array('fid','title','content',),
			//修改封面/头像
			'change'=>array('fid', 'union', 'groupname', 'filename', 'imgtype'),
			//好友及关注
			'social'=>array('obj_name', 'obj_uid', 'obj_code', 'union'),
			//回答问题
			'answer'=>array('fid','title','answers'),
			//参加活动
			'join'=>array('fid', 'title', 'cover'),
			'video'=>array('fid','title','content','width','height','videourl','imgurl','url'),
			'sharevideo'=>array('title', 'content', 'videourl', 'imgurl', 'url'),
			'uinfo'=>array('fid','content','subtype','info'),
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
	 * @desc 删除一条信息实体
	 * @param string/int $tid
	 */
	public function delTopic( $tid = '' ) {
		if( empty($tid) ) {
			return false;
		}
		$infos = $this->redis->hMGet($this->getTopicKey($tid), array('fid', 'tid', 'type'));
		//删除映射关系
		if( $infos['type'] != 'info' ) {
			if( $infos['type'] == 'forward' ) {
				$this->redis->hDel( $this->getMapKey($infos['type']), $infos['tid'] );
			} else {
				$this->redis->hDel( $this->getMapKey($infos['type']), $infos['fid'] );
			}
		}
		unset($infos);
		return $this->redis->del( $this->getTopicKey( $tid ) );
	}

	/**
	 * @auther fbbin
	 * @desc 根据映射关系来获取TID
	 * @param int $fid
	 * @param int $uid
	 * @param string $type
	 */
	public function getTidByMap($fid, $type) {
		$tid = $this->redis->hGet($this->getMapKey($type), $fid);
		return $tid ?: false;
	}

	/**
	 * @author fbbin
	 * @desc 异步保存到disk
	 */
	public function __destruct() {
	}

}
