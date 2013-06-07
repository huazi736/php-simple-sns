<?php
/**
 * [ Duankou Inc ]
 * Created on 2012-3-5
 * @author fbbin
 * The filename : WebtopicModel.php
 */
class WebtopicModel extends DkModel {

	private $tid = Null;
	private $allowTypes = array('album', 'info', 'photo', 'forward', 'video', 'wiki','event', 'goods', 'groupon','dish','travel','shoot','airticket');
	private $staticTypes = array('uinfo');
	private $withOutMapTypes = array('info');

	//标识来源于百科
	const TOPIC_FROM_WIKI = 5;
	const TOPIC_FROM_APP = 4;

	public function __initialize() {
		$this->init_redis();
	}

	/**
	 * @author fbbin
	 * @desc 网页信息流数据的写入
	 * @param array $data
	 * @param string $type
	 */
	public function addWebtopic( array $data ) {
		$data = array_merge($data, array('tid'=>$this->_inc(),'hot'=>0, 'highlight'=>0));
		$data['dateline'] = strtotime($data['dateline']);
		if( $this->redis->hMset($this->getWebtopicKey(), $data) === false ) {
			return DKBase::status(false, 'redis_save_err', 2003);
		}
		//DUMP KEYS
		$this->redis->zAdd('dump:webtopic', $data['dateline'], $this->tid);
		//添加映射关系
		if( !in_array($data['type'], $this->withOutMapTypes) )
		{
			if($data['type'] == 'forward')
				$this->redis->hSet($this->getMapKey($data['type'], $data['pid']), $data['tid'], $data['fid']);
			else
				$this->redis->hSet($this->getMapKey($data['type'], $data['pid']), $data['fid'], $data['tid']);
		}
		//数据返回设置开始
		$data['type'] === 'forward' && ($data['forward'] = $this->_parseWebtopic($data['fid']));
		//格式化相册地址信息显示
		$data['type'] === 'album' && ($data['picurl'] = json_decode($data['picurl']));
		//格式化相照片址信息显示
		$data['type'] === 'photo' && ($data['picurl'] = json_decode($data['picurl']));
		//返回时间友好显示
		$friendlyTime = $data['ctime']==date('YmdHis', $data['dateline'])?$data['dateline']:$data['ctime'];

		$data['friendly_time'] = makeFriendlyTime($friendlyTime);
		$data['friendly_line'] = makeFriendlyTime($data['dateline']);
		$data['dateline'] = date('YmdHis', $data['dateline']);
		$data['ymd'] = parseTime($data['ctime']);
		return $data;
	}

	/**
	 * @author fbbin
	 * @desc 生成即将写入的实体数据的唯一主键
	 */
	private function _inc()
	{
		$this->tid = $this->redis->incr('WebtopicID');
		return $this->tid;
	}

	/**
	 * @desc 处理转发数据
	 * @author fbbin
	 * @param int $tid
	 */
	private function _parseWebtopic( $tid )
	{
		$data = $this->redis->hGetAll( $this->getWebtopicKey($tid) ) ?: false;
		if( $data !== false )
		{
			//转发相册数据处理
			if( $data['type'] == 'album' )
			{
				$data['picurl'] = json_decode($data['picurl']);
			}
			elseif( $data['type'] == 'photo' )
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
			return "webtopic:".$this->tid;
		}
		return "webtopic:".$tid;
	}

	/**
	 * @author fbbin
	 * @desc 获取fid和实体TID映射关系
	 * @param int $fid
	 * @param string $type
	 */
	private function getMapKey($type, $pid = '')
	{
		if( empty($pid) ) {
			return DKBase::status(false, 'get_mapkey_need_pid', 120108, $type);
		}
		return 'webmap:'.strtolower($type). ":{$pid}";
	}

	/**
	 * @author fbbin
	 * @desc 获取完整的信息实体
	 * @param int $fid
	 * @param string $type
	 */
	public function getWebtopic($id, $type, $pid) {
		if( ! $id  ) {
			return false;
		}
		if( $type && in_array($type, $this->allowTypes)) {
			$id = $this->getTidByMap($id, $type, $pid);
		}
		return $this->_parseWebtopic( $id )  ?: array();
	}

	/**
	 * @auther fbbin
	 * @desc 更新信息实体的字段数据
	 * @param array $data
	 */
	public function updateWebtopic( $data )
	{
		$fields = $this->strictCheckFields($data, true);
		if( !$fields || array_intersect(array_keys($data), $fields) !== array_keys($data) ) {
			return DKBase::status(false, 'update_param_err', 120301, $fields);
		}
		if( $data['fid'] ) {
			$mapKey = $this->getTidByMap($data['fid'], $data['type'], $data['pid']);
			if ($mapKey === false ){
				return DKBase::status(false, 'topic_not_found_by_map', 120302);
			}
			$topicKey = $this->getWebtopicKey( $mapKey );
			unset($data['fid'],$data['type']);
		} else {
			return DKBase::status(false, 'no_fid_for_map', 120303);
		}
		if( $this->redis->exists($topicKey) === false ) {
			return DKBase::status(false, 'topic_key_not_exists', 120304);
		}
		//更新字段数据
		if( $this->redis->hMset($topicKey, $data) === false ) {
			return DKBase::status(false, 'redis_save_err', 2003);
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
		if( ! in_array($key, $allowKeys) ) {
			return DKBase::status(false, 'special_field_not_allow', 120306);
		}
		$topickey = $this->getWebtopicKey($tid);
		if($this->redis->exists($topickey) === false) {
			return DKBase::status(false, 'topic_key_not_exists', 120304);
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
	public function strictCheckFields( $data = '', $retrunFields = false )
	{
		if( ! in_array($data['type'], array_merge($this->staticTypes, $this->allowTypes) ) ) {
			return DKBase::status(false, 'type_not_allowed', 120101);
		}
		$commonKeys = array('num'=>array('uid','dkcode','pid','from','dateline','ctime'), 'str'=>array('uname','type'),'exp'=>array('uid'=>array('len'=>10),'dkcode'=>array('len'=>6)));
		$fields = array(
			'album'=>array('num'=>array('fid','photonum','note'), 'str'=>array('title','content','timedesc','picurl')),
			'photo'=>array('num'=>array('fid','note'), 'str'=>array('title','content','timedesc','picurl')),
			'info'=>array('str'=>array('content','timedesc','title')),
			'wiki'=>array('str'=>array('content','timedesc')),
			'event'=>array('num'=>array('fid','starttime'),'str'=>array('title','photo','url','timedesc')),
			'forward'=>array('num'=>array('fid'),'str'=>array('title','content')),
			'video'=>array('num'=>array('fid','width','height'), 'str'=>array('title','content','imgurl','timedesc')),
			'uinfo'=>array('str'=>array('fid','content','subtype','info','timedesc')),
			'goods'=>array('num'=>array('fid'),'str'=>array('title','content','goods', 'timedesc')),
			'groupon'=>array('num'=>array('fid'),'str'=>array('title','content','groupon', 'timedesc')),
			'dish'=>array('num'=>array('fid'),'str'=>array('title','content','dish', 'timedesc')),
			'travel'=>array('num'=>array('fid'),'str'=>array('title','content','travel', 'timedesc')),
			'shoot'=>array('num'=>array('fid'),'str'=>array('title','content','shoot', 'timedesc')),
			'airticket'=>array('num'=>array('fid'),'str'=>array('title','content','airticket', 'timedesc'))
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
			return $allFields;
		}
		sort($allFields);
		$dataFields = array_keys($data);
		sort($dataFields);
		if( $dataFields === $allFields) {
			if( array_intersect($commonFields, array_keys(array_filter($data))) != $commonFields ) {
				return DKBase::status(false, 'required_fields_empty', 120102);
			}
			return $this->_CheckFieldsType($data, array_merge_recursive($fields[$data['type']], $commonKeys));
		}
		return DKBase::status(false, 'fields_not_enough', 120103);
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
		unset($data, $allFields, $funcs);
		// return DKBase::status(empty($error), 'check_field_type_err', 110105, $error);
		return DKBase::status(empty($error), implode(';', $error), 110105);
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
	public function getTidByMap($fid, $type, $pid)
	{
		$tid = $this->redis->hGet($this->getMapKey($type, $pid), $fid);
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
		$infos = $this->redis->hMGet($this->getWebtopicKey($tid), array('fid', 'type','pid'));
		//删除映射关系
		if( $infos['type'] != 'info' )
		{
			$this->redis->hDel( $this->getMapKey($infos['type'], $infos['pid']), $infos['fid'] );
		}
		unset($infos);
		return $this->redis->del( $this->getWebtopicKey( $tid ) );
	}

}
