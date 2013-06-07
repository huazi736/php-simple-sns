<?php
/**
* [ Duankou Inc ]
* Created on 2012-3-5
* @author fbbin
* The filename : TopicModel.class.php
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
		'event', 'blog', 'album', 'photo', 'ask', 'link',
		'forward', 'info', 'video', 'sharevideo', 'change',
		'social', 'answer', 'join','mbphoto','mbvideo'
	);
	private $withOutMapTypes = array('info','sharevideo');
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
			$multiDataMapKey = $this->getMapKey($type, $uid);
			//这里会改变$data
			$exists = $this->exists($multiDataMapKey, $date, $data);
			//如果是数据已存在或操作失败则不继续Action中接下来的操作(已存在的情况下:方法内部完成时间轴更新)
			if($exists === null) {
				return DKBase::status(false, 'multi_required_fields_empty', 110104, $data);
			} else if($exists === true) {
				return DKBase::status(true, 'multi_data_updated', 0, $data);
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
			return DKBase::status(false, 'redis_save_err', 2003, $data);
		}
		//DUMP KEYS
		$this->redis->zAdd('dump:topic', $data['dateline'], $this->tid);
		//添加映射关系: 写入Map数据
		if( !in_array($type, $this->withOutMapTypes) ) {
			if($type == 'forward')
				$this->redis->hSet($this->getMapKey($type, $uid), $data['tid'], $data['fid']);
			else if(in_array($type, $multiDataTypes))
				$this->redis->hSet($multiDataMapKey, $date, $data['tid']);
			else
				$this->redis->hSet($this->getMapKey($type, $uid), $data['fid'], $data['tid']);
		}
		//设置返回转发数据
		$type === 'forward' && ($data['forward'] = $this->_parserTopic($data['fid']));
		//格式化相册地址信息显示
		$type === 'album' && ($data['picurl'] = json_decode($data['picurl']));
		//格式化照片地址信息显示
		$type === 'photo' && ($data['picurl'] = json_decode($data['picurl']));
		//时间友好显示
		$data['friendly_time'] = date('Ymd',$data['ctime']) == date('Ymd',$data['dateline']) ? '刚刚' :makeFriendlyTime($data['ctime']);
		return DKBase::status($data);
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
		return true;
	}

	/**
	 * 保存 social 类型的字段使用
	 * 供 $this->exists() 使用
	 * @return array
	 */
	protected function processDataSocial(&$data, &$rs, $tid) {
		$socialModel = DKBase::import('Social','timeline');
		list($follows_num, $friends_num) = $socialModel->getNums(
			$data['uid'], date('Ym', $data['ctime']));
		$data['friends_num'] = $friends_num;
		$data['follows_num'] = $follows_num;
		//因为是引用操作data,所以需要赋值给rs来替换data的数据
		if($rs) {
			$rs['friends_num'] = $friends_num;
			$rs['follows_num'] = $follows_num;
		}
		unset($data['union']);
		//返回要更新的数据
		return array(
			'friends_num' => $data['friends_num'],
			'follows_num' => $data['follows_num'],
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
	 * 复合数据类型数据是否存在
	 *
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
			$axis = DKBase::import('Axis','timeline');
			$axis->updatePoint($tid, $data['ctime']);
			$rs['ctime'] = $data['ctime'];
			$data = $rs;
			return true;
		} else {
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
		if(!$data['ctime'] || !$uid || !$type || !in_array($type, $this->multiDataTypes)) {
			return DKBase::status(false, 'multi_updated_data_err', 110301);
		}
		//获取字段名称
		if(!($field = $this->getMultiItemKey($type, $field))) {
			return DKBase::status(false, 'multi_updated_field_err', 110302);
		}
		//通过映射关系找到tid
		$multiDataMapKey = $this->getMapKey($type, $uid);
		$date = $this->getMultiMapKey($type, $data['ctime']);
		$tid = $this->redis->hGet($multiDataMapKey, $date);
		if(!$tid) {
			return DKBase::status(false, 'multi_topic_key_not_found', 110303, $data);
		}
		//获取topic 数据
		$topic = $this->getTopicByTid($tid);
		if(!$topic || $topic['uid']!=$uid) {
			return DKBase::status(false, 'multi_topic_not_found', 110304);
		}
		$d = array();
		$forceUpdate = false;
		//常规复合数据类型
		if($type!=='social') {
			$d = json_decode($topic[$field], true);
			if(!isset($d[$index]))
				return DKBase::status(false, 'multi_item_not_found', 110305);
			unset($d[$index]);
			$updatedData = json_encode($d);
		//social 类型变更
		} else {
			$socialModel = DKBase::import('Social', 'timeline');
			list($follows_num, $friends_num) = $socialModel->getNums(
				$data['uid'], date('Ym', $data['ctime']));
			$updatedData = ${$field};
			if($follows_num || $friends_num) {
				$forceUpdate = true;
			}
		}
		//删除 Or 更新
		if($forceUpdate || count($d)>0) {
			$this->redis->hSet(
				$this->getTopicKey($tid),
				$field,
				$updatedData
			);
		} else {
			if(!$this->redis->hDel($multiDataMapKey, $date)) {
				return DKBase::status(false, 'redis_delete_err', 2004, $multiDataMapKey.':'.$date);
			}
			$axis = DKBase::import('Axis','timeline');
			if (!$axis->deletePoint($tid)) {
				return DKBase::status(false, 'redis_delete_err', 2004, 'deletePoint!'.$tid);
			}
			$info = DKBase::import('Info','timeline');
			$info->delInfo($tid);
			$this->delTopic($tid);
		}
		return DKBase::status(true);
	}

	/**
	 * 获取多字段中需要更新的字段
	 * @return string
	 */
	protected function getMultiItemKey($type, $field) {
		if($type !== 'social') {
			switch($type){
				case 'answer':
					$field = 'questions';
					break;
				case 'join':
					$field = 'events';
					break;
				default:
					$field = '';
					break;
			}
		} else {
			if(!in_array($field, array('follows','friends'))) {
				$field = '';
			} else {
				$field .= '_num';
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
		if( !$fields || count($data) < 4 || array_intersect(array_keys($data), $fields) !== array_keys($data) )
		{
			return DKBase::status(false, 'update_param_err', 110301, $fields);
		}
		if( $data['fid'] )
		{
			$mapKey = $this->getTidByMap($data['fid'], $data['type'], $data['uid']);
			if($mapKey === false ){
				return DKBase::status(false, 'topic_not_found_by_map', 110302, $data);
			}
			$topicKey = $this->getTopicKey( $mapKey );
			unset($data['fid']);
		}
		else 
		{
			return DKBase::status(false, 'no_fid_for_map', 110303);
		}
		if( $this->redis->exists($topicKey) === false )
		{
			return DKBase::status(false, 'topic_key_not_exists', 110304);
		}
		//设置关系数据
		isset( $data['relations'] ) && $data['relations'] = json_encode($relations);
		//更新字段数据
		if( $this->redis->hMset($topicKey, $data) === false )
		{
			return DKBase::status(false, 'redis_save_err', 2003, $data);
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
	public function getTopicByFidAndType( $fid, $type, $uid )
	{
		if( !$fid || !$type )
		{
			return false;
		}
		return $this->getTopicByTid( $this->getTidByMap($fid, $type, $uid) );
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
			elseif( $data['type'] == 'photo' )
			{
				$data['picurl'] = json_decode($data['picurl']);
			}
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
			return "topic:".$this->tid;
		}
		return "topic:".$tid;
	}

	/**
	 * @author fbbin
	 * @desc 获取fid和实体TID映射关系
	 * @param int $fid
	 * @param string $type
	 */
	private function getMapKey($type, $uid = '') {
		if( empty($uid) ) {
			return DKBase::status(false, 'get_mapkey_need_uid', 110108, $type);
		}
		return 'map:'.strtolower($type) . ":{$uid}";
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
			return DKBase::status(false, 'operation_not_allow', 110201, $key);
		}
		$topickey = $this->getTopicKey($tid);
		if( $this->redis->exists($topickey) === false ) {
			return DKBase::status(false, 'data_not_exists', 110202, $topickey);
		}
		if( $key != 'hot' ) {
			$this->redis->hSet($topickey, $key, $value);
		} else {
			$this->redis->hIncrBy($topickey, $key, intval($value) );
		}
		return DKBase::status(true, $info, $code, array($tid, $key, $value));
	}

	/**
	 * @author fbbin
	 * @desc 严格检测传入的字段信息
	 * @param array $data
	 * from说明:
	 * 1：来源于时间线的发表框
	 * 2：来源于时间线的各个应用
	 * 3：来于网页时间线发表框
	 * 4：来于网页各个应用
	 * 5: 系统直接发送
	 * 6: 来自移动端得数据
	 */
	public function strictCheckFields( $data = '', $retrunFields = false ) {
		if( ! in_array($data['type'], array_merge($this->staticTypes, $this->allowTypes) ) ) {
			return DKBase::status(false, 'type_not_allowed', 110101, $data);
		}
		$commonKeys = array('num'=>array('uid','dkcode','from','permission','dateline','ctime'), 'str'=>array('uname','type'),'exp'=>array('uid'=>array('len'=>'10'),'dkcode'=>array('len'=>'6'),'uid'=>array('len'=>'10')));
		$fields = array(
			//博客
			'blog'=>array('num'=>array('fid','action'), 'str'=>array('title','fname','furl','nameurl','content','url')),
			//相册
			'album'=>array('num'=>array('fid','photonum','note'), 'str'=>array('title','content','picurl')),
			//照片
			'photo'=>array('num'=>array('fid','note'), 'str'=>array('title','content','picurl')),
			//状态
			'info'=>array('str'=>array('content','title')), 
			//问答
			'ask'=>array('num'=>array('fid'),'str'=>array('title')),
			//活动
			'event'=>array('num'=>array('fid','starttime'),'str'=>array('title','photo','url'),'exp'=>array('fid'=>array('len'=>3))),
			//转发
			'forward'=>array('num'=>array('fid'),'str'=>array('content','title')),
			//修改封面/头像
			'change'=>array('num'=>array('fid', 'width', 'height'), 'str'=>array('union', 'groupname', 'filename', 'imgtype')),
			//好友及关注
			'social'=>array('str'=>array('union')),
			//回答问题
			'answer'=>array('num'=>array('fid'), 'str'=>array('title'),'arr'=>array('answers')),
			//参加活动
			'join'=>array('num'=>array('fid'), 'str'=>array('cover','title')),
			//视频
			'video'=>array('num'=>array('fid','width','height'), 'str'=>array('title','content','imgurl')),
			//分享视频
			'sharevideo'=>array('str'=>array('title', 'content', 'videourl', 'imgurl', 'url')),
			//资料
			'uinfo'=>array('str'=>array('fid','content','subtype','info')),
			//移动的照片
			'mbphoto'=>array('num'=>array('fid','width','height'), 'str'=>array('title','content','picurl')),
			//移动端的视频
			'mbvideo'=>array('num'=>array('fid','width','height'), 'str'=>array('title','content','imgurl')),
		);
		$commonFields = array();
		$typeFields = array();
		foreach ($commonKeys as $key => $value) {
			if($key != 'exp'){
				$commonFields = array_merge($commonFields, $value);
			}
		}
		foreach ($fields[$data['type']] as $key => $value) {
			if($key != 'exp'){
				$typeFields = array_merge($typeFields, $value);
			}
		}
		$allFields = array_merge($commonFields, $typeFields);
		if( $retrunFields ) {
			return DKBase::status($allFields);
		}
		sort($allFields);
		$dataFields = array_keys($data);
		sort($dataFields);
		if( $dataFields === $allFields) {
			if( array_intersect($commonFields, array_keys(array_filter($data))) != $commonFields ) {
				return DKBase::status(false, 'required_fields_empty', 110102, $data);
			}
			return $this->_CheckFieldsType($data, array_merge_recursive($fields[$data['type']], $commonKeys));
		}
		return DKBase::status(false, 'fields_not_enough', 110103);
	}

	/**
	 * @author fbbin
	 * @desc 检测字段的数据类型
	 * @param array $data
	 */
	private function _CheckFieldsType($data, $allFields){
		$exp = array();
		if( isset($allFields['exp']) )
		{
			$exp = $allFields['exp'];
			unset($allFields['exp']);
		}
		$funcs = array('num'=>'is_numeric','str'=>'is_string','arr'=>'is_array');
		$error = array();
		foreach ($allFields as $key => $fields){
			foreach ($fields as $field){
				if( $funcs[$key]($data[$field]) ){
					if( $exp && isset($exp[$field]) ){
						$check = $exp[$field];
						if(is_array($check)){
							if(isset($check['len'])){
								$strlen = strlen($data[$field]);
								if(is_numeric($check['len']) && $strlen != $check['len']){
									$error[] = "{$field}: require len={$check['len']} values,{$strlen} given";
								}elseif(is_string($check['len']) && strpos($check['len'], ','))
								{
									$num = explode(',', $check['len']);
									if( $strlen < $num['0'] || $strlen > $num['1']){
										$error[] = "{$field}: require len in ({$check['len']}) values,{$strlen} given";
									}
								}
							}
						}elseif(is_string($check)){
							if($check == 'email' && !filter_var($data[$field], FILTER_VALIDATE_EMAIL)){
								$error[] = "{$field}: require email values";
							}
							if($check == 'url' && !filter_var($data[$field], FILTER_VALIDATE_URL))
							{
								$error[] = "{$field}: require url values";
							}
						}
					}
					continue;
				}else
				{
					$error[] = "{$field}: require a {$key} values";
				}
			}
		}
		unset($allFields, $funcs);
		return DKBase::status(empty($error), implode(';', $error), 110105, $data);
	}

	/**
	 * @author fbbin
	 * @desc 删除一条信息实体
	 * @param string/int $tid
	 * 时间线不删除映射和实体，只有info的实体才删除，应用的全部删除
	 */
	public function delTopic( $tid = '', $fromTimeline =  false ) {
		if( empty($tid) ) {
			return false;
		}
		$status = true;
		$infos = $this->redis->hMGet($this->getTopicKey($tid), array('fid', 'tid', 'type', 'uid'));
		//不是时间线的删除操作，那么就全部删除
		if( !$fromTimeline && $infos['type'] != 'info') {
			if( $infos['type'] == 'forward') {
				$this->redis->hDel( $this->getMapKey($infos['type'], $infos['uid']), $infos['tid'] );
			} else {
				$this->redis->hDel( $this->getMapKey($infos['type'], $infos['uid']), $infos['fid'] );
			}
			$status = $this->redis->del( $this->getTopicKey( $tid ) );
		}
		//从时间线上面删除的，并且类型只有是info才删除
		if( $infos['type'] == 'info' && $fromTimeline )
		{
			$status = $this->redis->del( $this->getTopicKey( $tid ) );
		}
		unset($infos);
		return $status;
	}

	/**
	 * @auther fbbin
	 * @desc 根据映射关系来获取TID
	 * @param int $fid
	 * @param int $uid
	 * @param string $type
	 */
	public function getTidByMap($fid, $type, $uid) {
		$tid = $this->redis->hGet($this->getMapKey($type, $uid), $fid);
		return $tid ?: false;
	}

}
