<?php

class WebTimelineService extends DK_Service {

    private $tid = Null;
	private $allowTypes = array('album', 'info', 'forward', 'video', 'wiki','event', 'goods', 'groupon');
	private $staticTypes = array('uinfo');

	//标识来源于百科
	const TOPIC_FROM_WIKI = 5;
	const TOPIC_WIKI_TYPE = 'wiki';
	const TOPIC_FROM_APP = 4;

    public function __construct() {
        parent::__construct();
        
        $this->init_redis();
        
        $this->helper('timeline');
    }

    /**
     * @author fbbin
     * @desc 添加网页数据
     * @param array $data
     * @param array $tags
     */
    public function addWebtopic($data, array $tags = array()) {
        !isset($data['from']) && $data['from'] = self::TOPIC_FROM_APP;
        $data['from'] == self::TOPIC_FROM_APP && $data['ctime'] = $data['dateline'];
        if ($this->_strictCheckFields($data) !== true) {
            return false;
        }
        $res = $this->_addWebtopic($data);
        if ($res === false) {
            return false;
        }
        $pointStatus = $this->_createPoint($res);
        if ($pointStatus) {
            $year = formatTime(parseTime($res['ctime']), 'Y');
            if (isset($res['timedesc']) && !empty($res['timedesc'])) {
                $this->_alias_set($res['pid'], $year, $res['timedesc']);
            }
        }
        $webInfoStatus = $this->_addWebinfo($res, $tags);
        return $webInfoStatus && $pointStatus ? json_encode($res) : false;
    }

    /**
     * @author fbbin
     * @desc 删除网页中的一条数据
     * @param int $tid
     * @param array $tags
     */
    public function delWebtopic($tid, array $tags) {
        if (!$tid) {
            return false;
        }
        $delStatus = $this->_delWebinfo($tid, $tags);
        $timelineStatus = $this->_deletePoint($tid);
        if (!$timelineStatus && !$delStatus) {
            return false;
        }
        return $this->_delWebtopic($tid) ? true : false;
    }

    /**
     * @author fbbin
     * @desc 取消关注一个网页
     * @param int $pid 网页ID
     * @param array $tags 网页标签数组
     */
    public function delAttentionWeb($uid, $pid, array $tags) {
        if ($uid && $pid && !empty($tags)) {
            return $this->_delAttentionWeb($uid, $pid, $tags);
        }
        return false;
    }

    /**
     * @author fbbin
     * @desc 删除一个网页
     * @param int $pid
     * @param array $tags
     */
    public function delWebpage($pid, array $tags) {
        if (!$pid) {
            return false;
        }
        return $this->_delWebpage($pid, $tags);
    }

    /**
     * @author fbbin
     * @desc 通过映射关系删除网页信息
     * @param int $fid
     * @param string $type
     * @param array $tags
     */
    public function delWebtopicByMap($fid, $type, array $tags) {
        if (!($type && $fid)) {
            return false;
        }
        $tid = $this->_getTidByMap($fid, $type);
        return $tid && $this->delWebtopic($tid, $tags);
    }

    /**
     * @author fbbin
     * @desc 各个应用更新时间线上面的信息实体
     * @param int $fid
     * @param bool $batch
     * @param array $infos
     */
    public function updateWebtopicByMap(array $infos, $batch = false) {
        !$batch && $infos = array($infos);
        $ctimeStatus = true;
        $updateStatus = true;
        foreach ($infos as $info) {
            if (count($info) < 3 || !isset($info['type']) || !isset($info['fid'])) {
                continue;
            }
            if (isset($info['dateline'])) {
                $tid = $this->_getTidByMap($info['fid'], $info['type']);
                $ctimeStatus = $this->updateWebtopicTime($tid, $info['dateline']);
                unset($info['dateline']);
            }
            $updateStatus = $this->_updateWebtopic($info) && $ctimeStatus;
        }
        return $updateStatus;
    }

    /**
     * @author fbbin
     * @desc 替换或者是添加 Topic 信息
     * @param array $data    Topic 数据
     * @param array $relations 关系人数据
     */
    public function replaceWebtopicByMap(array $infos, array $tags) {
        if (count($infos) < 3 || !isset($infos['type']) || !isset($infos['fid'])) {
            return false;
        }
        $webtopic = $this->getWebtopicByMap($infos['fid'], $infos['type']);
        if (!$webtopic) {
            return $this->addWebtopic($infos, $tags);
        } else {
            return $this->updateWebtopicByMap($infos);
        }
    }

    /**
     * @author fbbin
     * @desc 更新时间轴上的信息
     * @param type $tid
     * @param type $time
     * @return bool 
     */
    public function updateWebtopicTime($tid, $time) {
        if (!( $tid && $time )) {
            return false;
        }
        $timeline = $this->_updatePoint($tid, $time);
        $webTopic = $this->_updateSpecialKey($tid, 'ctime', $time);
        return $webTopic && $timeline;
    }

    /**
     * @author fbbin
     * @desc 更新时间轴上信息的突出显示状态
     * @param type $tid
     * @param type $highlight
     * @return bool
     */
    public function updateWebtopicHighlight($tid, $highlight = 1) {
        if (!$tid) {
            return false;
        }
        return $this->_updateSpecialKey($tid, 'highlight', $highlight);
    }

    /**
     * @author fbbin
     * @desc 更改 Topic 的 Hot 值
     * @param type $tid Topic ID
     * @param type $inc
     * @return bool 
     */
    public function updateWebtopicHot($tid, $inc = 1) {
        if (!$tid) {
            return false;
        }
        return $this->_updateSpecialKey($tid, 'hot', $inc);
    }

    /**
     * @author fbbin
     * @desc 根据映射关系返回一条完整的信息实体
     * @param string $fid
     * @param string $type
     */
    public function getWebtopicByMap($fid, $type) {
        $results = array();
        if (is_array($fid)) {
            foreach ($fid as $id) {
                $results[$id] = $this->getWebtopicByMap($id, $type);
            }
            return json_encode($results);
        }
        return $this->_getWebtopic($fid, $type);
    }

    // ============= WebTopic Model ===========================
    /**
	 * @author fbbin
	 * @desc 网页信息流数据的写入
	 * @param array $data
	 * @param string $type
	 */
	public function _addWebtopic( array $data )
	{
		$data = array_merge($data, array('tid'=>$this->_inc(),'hot'=>0, 'highlight'=>0));
		//来自于百科的信息流默认最大化显示
		if( $data['type'] == self::TOPIC_WIKI_TYPE )
		{
			$data['highlight'] = 1;$data['from'] = self::TOPIC_FROM_WIKI;
		}
		$data['dateline'] = strtotime($data['dateline']);
		if( $this->redis->hMset($this->_getWebtopicKey(), $data) === false ) {
			return false;
		}
		//DUMP KEYS
		$this->redis->zAdd('dump:webtopic', $data['dateline'], $this->tid);
		//添加映射关系
		if( $data['type'] !== 'info')
		{
			$this->redis->hSet($this->_getMapKey($data['type']), $data['fid'], $data['tid']);
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
		$data = $this->redis->hGetAll( $this->_getWebtopicKey($tid) ) ?: false;
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
	private function _getWebtopicKey( $tid = '' )
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
	private function _getMapKey($type)
	{
		return 'Webmap:'.strtolower($type);
	}

	/**
	 * @author fbbin
	 * @desc 获取完整的信息实体
	 * @param int $fid
	 * @param string $type
	 */
	public function _getWebtopic($tid, $type)
	{
		if( ! $tid  )
		{
			return false;
		}
		if( $type && in_array($type, $this->allowTypes))
		{
			$tid = $this->_getTidByMap($tid, $type);
		}
		return $this->_parseWebtopic( $tid )  ?: array();
	}

	/**
	 * @auther fbbin
	 * @desc 更新信息实体的字段数据
	 * @param array $data
	 */
	public function _updateWebtopic( $data )
	{
		$fields = $this->_strictCheckFields($data, true);
		if( !$fields || array_intersect(array_keys($data), $fields) !== array_keys($data) )
		{
			return false;
		}
		if( $data['fid'] )
		{
            $mapKey = $this->_getTidByMap($data['fid'], $data['type']);
            if ($mapKey === false)
            {
                return false;
            }
			$topicKey = $this->_getWebtopicKey( $mapKey );
			unset($data['fid'],$data['type']);
		}
		else
		{
			return false;
		}

		if( $this->redis->exists($topicKey) === false )
		{
			return false;
		}
		//更新字段数据
		if( $this->redis->hMset($topicKey, $data) === false )
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
	public function _updateSpecialKey($tid, $key, $value)
	{
		$allowKeys = array('ctime', 'highlight', 'hot');
		if( ! in_array($key, $allowKeys) )
		{
			return false;
		}
		$topickey = $this->_getWebtopicKey($tid);
		if($this->redis->exists($topickey) === false)
		{
			return false;
		}
		if( $key != 'hot' )
		{
			return $this->redis->hSet($topickey, $key, $value) === 0 ? true : false;
		}
		else 
		{
			return $this->redis->hIncrBy($topickey, $key, intval($value) ) ? true : false;
		}
	}

	/**
	 * @author fbbin
	 * @desc 严格检测传入的字段信息
	 * @param array $data
	 */
	public function _strictCheckFields( $data = '', $retrunFields = false )
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
			'goods'=>array('title','content','goods', 'timedesc'),
			'groupon'=>array('title','content','groupon', 'timedesc')
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
	public function _checkSpecialType( $type = '' )
	{
		return in_array($type, $this->staticTypes);
	}

	/**
	 * @auther fbbin
	 * @desc 根据映射关系来获取TID
	 * @param int $fid
	 * @param string $type
	 */
	public function _getTidByMap($fid, $type)
	{
		$tid = $this->redis->hGet($this->_getMapKey($type), $fid);
		return $tid ?: false;
	}

	/**
	 * @author fbbin
	 * @desc 删除网页上的一条信息实体
	 * @param string/int $tid
	 */
	public function _delWebtopic( $tid = '' )
	{
		if( empty($tid) )
		{
			return false;
		}
		$infos = $this->redis->hMGet($this->_getWebtopicKey($tid), array('fid', 'type'));
		//删除映射关系
		if( $infos['type'] != 'info' )
		{
			$this->redis->hDel( $this->_getMapKey($infos['type']), $infos['fid'] );
		}
		unset($infos);
		return $this->redis->del( $this->_getWebtopicKey( $tid ) );
	}
    
    // =======================
    
    public function _createPoint($data) {
        $pid = $data['pid'];
        $tid = $data['tid'];
        $type = $data['type'];
        $time = $data['ctime'];
        $year = formatTime(parseTime($time), 'Y');
        $month = formatTime(parseTime($time), 'n');

        //更新有效年份和月份
        $this->_updateYears($pid, $year);
        $this->_updateMonths($pid, $year, $month);

        $res = $this->_set($pid, $time, $tid);
        if ($res === false) {
            return false;
        }

        if ($this->_checkSpecialType($type)) {
            $this->_addMainPoint($pid, $time, $tid);
        } else if ($year < 0) {
            $this->_addTopPoint($pid, $time, $tid);
        }

        $this->_buildTopPoints($pid, $time);

        return true;
    }

    public function _deletePoint($tid) {
        //获取Topic信息
        $topic = $this->redis->hMGet('Webtopic:' . $tid, array('pid', 'ctime'));
        $uid = $topic['pid'];
        $ctime = $topic['ctime'];
        $year = formatTime(parseTime($ctime), 'Y');
        $month = formatTime(parseTime($ctime), 'n');
        
        //删除Main Point
        $this->_deleteMainPoint($uid, $year, $tid);

        //删除Top Point
        $this->_deleteTopPoint($uid, $year, $tid);

        //删除Hot Point
        $this->_deleteHotPoint($uid, $year, $tid);

        //删除常规的时间轴Point
        $res = $this->_delete($uid, $ctime, $tid);
        if ($res === false) {
            return false;
        }
        
        $this->_buildTopPoints($uid, $ctime);

        //更新有效年份和月份
        $this->_updateMonths($uid, $year, $month, false);
        $this->_updateYears($uid, $year, false);

        return true;
    }

    public function _updatePoint($tid, $time) {
        //获取Topic信息，根据过去的时间进行年月的定位
        $topic = $this->redis->hMGet('Webtopic:' . $tid, array('pid', 'ctime', 'type'));
        $uid = $topic['pid'];
        $ctime = $topic['ctime'];
        $type = $topic['type'];

        $old_year = formatTime(parseTime($ctime), 'Y');
        $old_month = formatTime(parseTime($ctime), 'n');
        $year = formatTime(parseTime($time), 'Y');
        $month = formatTime(parseTime($time), 'n');

        if ($year . $month != $old_year . $old_month) {
            $this->_delete($uid, $ctime, $tid);

            //更新有效月份
            $this->_updateMonths($uid, $old_year, $old_month, false);
            $this->_updateMonths($uid, $year, $month);
        }

        if ($year != $old_year) {
            //更新有效年份
            $this->_updateYears($uid, $old_year, false);
            $this->_updateYears($uid, $year);
        }

        if ($this->_checkSpecialType($type)) {
            //更新特别年度热点索引
            $this->_updateMainPoint($uid, $old_year, $year, $time, $tid);
        }

        //更新常规的时间轴Point
        $res = $this->_set($uid, $time, $tid);
        if ($res === false) {
            return false;
        }

        if ($year > 0) {
            //清除 Top Point
            $this->_deleteTopPoint($uid, $old_year, $tid);
        } else {
            //更新 Top Point
            $this->_updateTopPoint($uid, $old_year, $time, $tid);
        }
        
        //清除 Hot Point
        $this->_deleteHotPoint($uid, $old_year, $tid);
        //重建 Top Points
        $this->_buildTopPoints($uid, $time, $tid);

        return true;
    }

    public function _buildTopPoints($uid, $time, $update_tid = false) {
        //生成Top Point的年份
        $year = floatval(formatTime(parseTime($time), 'Y'));
        //获取每月热点的数量
        $top_num = 1;

        //获取月份的热点需要的参数
        $sort_params = array(
            'by' => 'Webtopic:*->hot',
            'limit' => array(0, $top_num),
            'get' => array('Webtopic:*->tid', 'Webtopic:*->ctime', 'Webtopic:*->type'),
            'sort' => 'desc',
            'alpha' => true,
        );

        //使用排序生成年份下的top集合，不使用排序生成
        if ($year > 0) {
            //删除月份过期的热点
            $this->redis->delete('webpage:timeaxis:' . $uid . ':' . $year . ':top');
            for ($month = 1; $month <= 12; $month++) {
                //获取月份的新热点
                $arr_hot = $this->redis->sort('webpage:timeaxis:' . $uid . ':' . $year . ':' . $month, $sort_params);
                //更新月份的热点
                for ($i = 0; $i < count($arr_hot); $i += 3) {
                    $tid = $arr_hot[$i];
                    $ctime = $arr_hot[$i + 1];
                    //对特定tid进行时间更新
                    if ($update_tid && $update_tid == $tid) {
                        $ctime = $time;
                    }
                    $type = $arr_hot[$i + 2];
                    if ($this->_checkSpecialType($type)) {
                        continue;
                    }
                    $this->_addTopPoint($uid, $ctime, $tid);
                }
            }
        }

        $this->_buildHotPoints($uid, $year);
    }

    public function _buildHotPoints($uid, $year) {
        //合并Main和Top到Hot中
        $key_main = 'webpage:timeaxis:' . $uid . ':' . $year . ':main';
        $key_top = 'webpage:timeaxis:' . $uid . ':' . $year . ':top';
        $this->redis->delete('webpage:timeaxis:' . $uid . ':' . $year . ':hot');
        $this->redis->zUnion('webpage:timeaxis:' . $uid . ':' . $year . ':hot', array($key_main, $key_top), array(1, 1), 'min');
    }

    //删除热点列表中指定的 Topic
    public function _deleteHotPoint($uid, $year, $tid) {
        return $this->redis->zDelete('webpage:timeaxis:' . $uid . ':' . $year . ':hot', $tid);
    }

    public function _addMainPoint($uid, $time, $tid) {
        $year = formatTime(parseTime($time), 'Y');
        return $this->redis->zAdd('webpage:timeaxis:' . $uid . ':' . $year . ':main', $time, $tid);
    }

    public function _deleteMainPoint($uid, $year, $tid) {
        return $this->redis->zDelete('webpage:timeaxis:' . $uid . ':' . $year . ':main', $tid);
    }

    public function _updateMainPoint($uid, $old_year, $year, $time, $tid) {
        if ($year != $old_year) {
            $this->_deleteMainPoint($uid, $old_year, $tid);
        }
        return $this->redis->zAdd('webpage:timeaxis:' . $uid . ':' . $year . ':main', $time, $tid);
    }

    public function _addTopPoint($uid, $time, $tid) {
        $year = formatTime(parseTime($time), 'Y');
        return $this->redis->zAdd('webpage:timeaxis:' . $uid . ':' . $year . ':top', $time, $tid);
    }

    public function _deleteTopPoint($uid, $year, $tid) {
        return $this->redis->zDelete('webpage:timeaxis:' . $uid . ':' . $year . ':top', $tid);
    }

    public function _updateTopPoint($uid, $old_year, $time, $tid) {
        $year = formatTime(parseTime($time), 'Y');
        $res = $this->_deleteTopPoint($uid, $old_year, $tid);
        if (is_int($res) && $res == 1) {
            return $this->redis->zAdd('webpage:timeaxis:' . $uid . ':' . $year . ':top', $time, $tid);
        }
        return $res;
    }

    private function _set($pid, $time, $tid) {
        $year = formatTime(parseTime($time), 'Y');
        $month = formatTime(parseTime($time), 'n');
        $time = floatval($time);
        return $this->redis->zAdd('webpage:timeaxis:' . $pid . ':' . $year . ':' . $month, $time, $tid);
    }

    private function _delete($uid, $time, $tid) {
        $year = formatTime(parseTime($time), 'Y');
        $month = formatTime(parseTime($time), 'n');
        return $this->redis->zDelete('webpage:timeaxis:' . $uid . ':' . $year . ':' . $month, $tid);
    }
    
    // ================ Web Model ===========================
    
    /**
	 * @author fbbin
	 * @desc 添加信息到网页信息流和关注人列表
	 * @param array $data
	 * @param array $tags 标签数组
	 */
	public function _addWebinfo( $data, array $tags )
	{
		if( !isset($data['pid']) || !isset($data['tid']) || empty($tags) )
		{
			return false;
		}
		//获取网页的粉丝列表，根据网页的ID获取TAGID，写入到用户的关注列表中
		return $this->_doWebpageTid($data['pid'], $data['tid'], strtotime($data['dateline'])) && $this->_doSentFans( $data['pid'], $data['tid'], strtotime($data['dateline']), $tags );
	}
	
	/**
	 * @author fbbin
	 * @desc 删除一条信息
	 * @param int $tid 信息实体ID
	 * @param array $tags 标签数组
	 */
	public function _delWebinfo( $tid, array $tags )
	{
		$infos = $this->redis->hGetAll( 'Webtopic:'.$tid );
		if( empty($infos) || empty($tags) )
		{
			return false;
		}
		//获取网页的粉丝列表，根据网页的ID获取TAGID，删除用户的关注列表中的数据
		return $this->_doWebpageTid($infos['pid'], $infos['tid'], $infos['dateline'], false) && $this->_doSentFans($infos['pid'], $infos['tid'], $infos['dateline'], $tags, false);
	}

	/**
	 * @author fbbin
	 * @desc 删除一个网页（删除该网页对应的全部信息）
	 * @param int $pid 网页ID 
	 * @param array $tags 网页标签数组
	 */
	public function _delWebpage( $pid, array $tags )
	{
		$dates = $this->redis->hKeys($this->_getWebpageInfos($pid));
		$tids = array();
		foreach ($dates as $date)
		{
			$tids = array_merge($tids, $this->redis->zRange( 'webpage:'.$pid.':'.$date, 0 , -1 ));
		}
		if( empty($tids) )
		{
			return true;
		}
		$delStatus = true;
		$failResults = array();
		$webtopic = new WebtopicModel();
		$timeline = new WebpageAxisModel();
		foreach ($tids as $tid)
		{
			//删除信息流，时间线，信息实体
			$delinfostatus = $this->_delWebinfo($tid, $tags);
			$timelinestatus = $timeline->deletePoint($tid);
			$topicstatus = $webtopic->delWebtopic($tid);
			if( ! ($delinfostatus && $timelinestatus && $topicstatus ) )
			{
				$delStatus = fasle;
				$failResults[] = $tid;
			}
		}
		return $delStatus ?: $failResults;
	}
	
	/**
	 * @author fbbin
	 * @desc 取消关注一个网页
	 * @param int $pid 网页ID
	 * @param array $tags 网页标签数组
	 */
	public function _delAttentionWeb( $uid, $pid, array $tags )
	{
		$attensionStartTime = time();//service('Relation')->getStartTtimeOfWeb($uid, $pid);
		if ($attensionStartTime === false)
		{
			return false;
		}
		$attensionStartTime = date('Ym', $attensionStartTime);
		$theObjKeys = $this->redis->keys('webpage:'.$pid.':2*');
		//对key进行过滤，避免不必要的操作
		$theObjKeys = array_filter($theObjKeys, function($key) use($attensionStartTime){
			if( substr($key, strrpos($key, ':')+1) >= $attensionStartTime )
			{
				return true;
			}
			return false;
		});
		$removeStatus = true;
		foreach ( $tags as $tid )
		{
			$indexInfoKey = $this->_getInfoIndexKey($uid, $tid);
			if( $indexInfoKey === false )
			{
				continue;
			}
			foreach ($theObjKeys as $key)
			{
				$date = substr($key, strrpos($key, ':') + 1 );
				$values = $this->redis->zRange($key, 0, -1) ?: array();
				$indexKey = "Info:" . $uid . ":" . $tid . ":" . $date;
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
		}
		return $removeStatus;
	}
	
	/**
	 * @author fbbin 
	 * @desc 获取个人关注标签的KEY
	 * @param int $uid
	 * @param int $tid 标签ID
	 * @param int $time
	 */
	private function _getInfoKey($uid, $tid, $time = '')
	{
		if( ! ($uid && $tid) )
		{
			return false;
		}
		$newTime = $time ? $time : time();
		return "Info:" . $uid . ":" . $tid . ":" . date('Ym', $newTime);
	}
	
	/**
	 * @author fbbin 
	 * @desc 获取个人关注标签索引的KEY
	 * @param int $uid
	 * @param int $tid 标签ID
	 */
	private function _getInfoIndexKey($uid, $tid)
	{
		if( ! ( $uid && $tid ) )
		{
			return false;
		}
		return "Info:" . $uid . ":" . $tid . ":infos";
	}
	
	/**
	 * @author fbbin 
	 * @desc 获取存储单个网页所有信息的key
	 * @param int $pid
	 */
	private function _getWebpageindex($pid, $dateline)
	{
		if( !$pid )
		{
			return false;
		}
		return "webpage:" . $pid . ':' . date('Ym', $dateline);
	}
	
	/**
	 * @author fbbin
	 * @desc 获取存储单个网页所有信息的记录总数的key
	 * @param int $pid
	 */
	private function _getWebpageInfos($pid)
	{
		if( !$pid )
		{
			return false;
		}
		return "webpage:" . $pid . ':infos';
	}
	
	/**
	 * @author fbbin
	 * @desc 根据网页获取网页的粉丝
	 * @param int $pageId 网页ID
	 */
	private function _getWebpageFans( $pageId )
	{
		if( ! $pageId )
		{
			return array();
		}
		static $_fans = array();
		if( isset($_fans[$pageId]) )
		{
			return $_fans[$pageId];
		}
		return $_fans[$pageId] = service('WebpageRelation')->getAllFollowers($pageId);
	}
	
	/**
	 * @author fbbin
	 * @desc 存储一个网页的所有的信息ID/删除整个网页的所有ID
	 * @param int $pid
	 * @param int $tid
	 * @param int $action
	 */
	private function _doWebpageTid($pid, $tid, $dateline, $action = true)
	{
		if( ! ($pid && $tid) )
		{
			return false;
		}
		$wpkey = $this->_getWebpageindex( $pid, $dateline );
		$wpindexKey = $this->_getWebpageInfos($pid);
		//往当前的网页中添加一条信息
		if( $action )
		{
			if( $this->redis->zAdd($wpkey, $dateline, $tid) )
			{
				return is_numeric($this->redis->hIncrBy($wpindexKey, date('Ym', $dateline), 1));
			}
		}
		//从当前的网页中删除一条信息
		else
		{
			if( $this->redis->zDelete($wpkey, $tid) )
			{
				return is_numeric($this->redis->hIncrBy($wpindexKey, date('Ym', $dateline), -1));
			}
		}
		return true;
	}
	
	/**
	 * @author fbbin
	 * @desc 执行发送给该网页的粉丝
	 * @param int $pid 网页的ID
	 * @param int $tid 当前信息的ID
	 * @param int $dateline 时间
	 * @param array $tags 该网页的所有标签
	 * @param bool $action 操作类型
	 */
	private function _doSentFans( $pid, $tid, $dateline, $tags, $action = true )
	{
		if( ! ($pid && $tid) || empty($tags) )
		{
			return false;
		}
		$dateline = $dateline ?: time();
		$fans = $this->_getWebpageFans( $pid );
		if( empty($fans) )	return true;
		foreach($fans as $uid)
		{
			foreach($tags as $tagid)
			{
				$infoKey = $this->_getInfoKey($uid, $tagid, $dateline);
				$infoIndexKey = $this->_getInfoIndexKey($uid, $tagid);
				//执行分发操作
				if( $action )
				{
					$zaddStatus = $this->redis->zAdd($infoKey, $dateline, $tid);
					if( $zaddStatus !== false )
					{
						$incrStatus = $this->redis->hIncrBy($infoIndexKey, date('Ym', $dateline), 1);
						if( $incrStatus === false )
						{
							//执行分发错误处理操作
							return false;
						}
					}
				}
				//执行删除操作
				else
				{
					$zDelStatus = $this->redis->zDelete($infoKey, $tid);
					if( $zDelStatus !== false )
					{
						$decrStatus = $this->redis->hIncrBy($infoIndexKey, date('Ym', $dateline), -1);
						if( $decrStatus === false )
						{
							//执行删除错误处理操作
							return false;
						}
					}
				}
			}
		}
		return true;
	}

    // =================== Line Model ========================
    
    /**
     * 更新有效年份
     * @param type $uid 用户ID
     * @param type $year 年份
     * @param type $plus 年份中的内容是增加还是减少: true 增加, false 减少
     * @return type 
     */
    public function _updateYears($uid, $year, $plus = true) {
        $key = 'webpage:timeline:' . $uid . ':years';
        $has = $this->redis->hExists($key, $year);
        if ($has) {
            if ($plus) {
                $this->redis->hIncrBy($key, $year, 1);
            } else {
                $has_year = $this->redis->exists('webpage:timeline:' . $uid . ':' . $year);
                if ($has_year) {
                    $this->redis->hIncrBy($key, $year, -1);
                } else {
                    $this->redis->hDel($key, $year);
                }
            }
        } else {
            $this->redis->hSet($key, $year, 1);
        }
    }

    /**
     * 更新年份下有效的月份
     * @param type $uid 用户ID
     * @param type $year 年份
     * @param type $month 月份
     * @param type $is_plus 月份中的内容是增加还是减少: true 增加, false 减少
     * @return type 
     */
    public function _updateMonths($uid, $year, $month, $plus = true) {
        $key = 'webpage:timeline:' . $uid . ':' . $year;
        $has = $this->redis->hExists($key, $month);
        if ($has) {
            if ($plus) {
                $this->redis->hIncrBy($key, $month, 1);
            } else {
                $has_month = $this->redis->exists('webpage:timeaxis:' . $uid . ':' . $year . ':' . $month);
                if ($has_month) {
                    $this->redis->hIncrBy($key, $month, -1);
                } else {
                    $this->redis->hDel($key, $month);
                }
            }
        } else {
            $this->redis->hSet($key, $month, 1);
        }
    }

    public function _getAllYears($uid){
        return $this->redis->hGetAll('webpage:timeline:' . $uid . ':years');
    }
    
    public function _alias_set($pageid, $date, $alias) {
        //Clear invalid alias
        //$this->_alias_clearInvalid($pageid);
        
        return $this->redis->hSet('datealias:' . $pageid, $date, $alias) !== false ? true : false;
    }

    public function _alias_get($pageid, $date) {
        return $this->redis->hGet('datealias:' . $pageid, $date);
    }

    public function _alias_getAll($pageid) {
        return json_encode($this->redis->hGetAll('datealias:' . $pageid));
    }

    public function _alias_delete($pageid, $date) {
        return $this->redis->hDel('datealias:' . $pageid, $date);
    }
    
    public function _alias_deleteAll($pageid) {
        return $this->redis->delete('datealias:' . $pageid) > 0 ? true : false;
    }
    
    private function _alias_clearInvalid($pageid) {
        $webpageLine = new WebpageLineModel();
        $years = $webpageLine->getAllYears($pageid);
        
        $keys = $this->redis->hKeys('datealias:' . $pageid);
        foreach ($keys as $key) {
            if (in_array($key, $years)) {
                continue;
            }
            $this->_delete($pageid, $key);
        }
    }
    
	/**
	 * @author fbbin
	 * @desc 异步保存到disk
	 */
	public function __destruct()
	{
		$this->redis->bgsave();
	}
    
}