<?php
/**
* [ Duankou Inc ]
* Created on 2012-4-5
* @author fbbin
* The filename : WebModel.php 01:46:54
*/
class WebModel extends RedisModel
{
	
	/**
	 * @author fbbin
	 * @desc 添加信息到网页信息流和关注人列表
	 * @param array $data
	 * @param array $tags 标签数组
	 */
	public function addWebinfo( $data, array $tags )
	{
		if( !isset($data['pid']) || !isset($data['tid']) || empty($tags) )
		{
			return false;
		}
		//获取网页的粉丝列表，根据网页的ID获取TAGID，写入到用户的关注列表中
		return $this->doWebpageTid($data['pid'], $data['tid'], strtotime($data['dateline'])) && $this->doSentFans( $data['pid'], $data['tid'], strtotime($data['dateline']), $tags );
	}
	
	/**
	 * @author fbbin
	 * @desc 删除一条信息
	 * @param int $tid 信息实体ID
	 * @param array $tags 标签数组
	 */
	public function delWebinfo( $tid, array $tags )
	{
		$infos = $this->_redis->hGetAll( 'Webtopic:'.$tid );
		if( empty($infos) || empty($tags) )
		{
			return false;
		}
		//获取网页的粉丝列表，根据网页的ID获取TAGID，删除用户的关注列表中的数据
		return $this->doWebpageTid($infos['pid'], $infos['tid'], $infos['dateline'], false) && $this->doSentFans($infos['pid'], $infos['tid'], $infos['dateline'], $tags, false);
	}

	/**
	 * @author fbbin
	 * @desc 删除一个网页（删除该网页对应的全部信息）
	 * @param int $pid 网页ID 
	 * @param array $tags 网页标签数组
	 */
	public function delWebpage( $pid, array $tags )
	{
		$dates = $this->_redis->hKeys($this->getWebpageInfos($pid));
		$tids = array();
		foreach ($dates as $date)
		{
			$tids = array_merge($tids, $this->_redis->zRange( 'webpage:'.$pid.':'.$date, 0 , -1 ));
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
			$delinfostatus = $this->delWebinfo($tid, $tags);
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
	public function delAttentionWeb( $uid, $pid, array $tags )
	{
		import("social.Action.SocialAction");
		$social = new SocialAction();
		import('social.Model.FollowerModel');
		$attensionStartTime = time();//$social->getStartTtimeOfWeb($uid, $pid);
		if ($attensionStartTime === false)
		{
			return false;
		}
		$attensionStartTime = date('Ym', $attensionStartTime);
		$theObjKeys = $this->_redis->keys('webpage:'.$pid.':2*');
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
			$indexInfoKey = $this->getInfoIndexKey($uid, $tid);
			if( $indexInfoKey === fasle )
			{
				continue;
			}
			foreach ($theObjKeys as $key)
			{
				$date = substr($key, strrpos($key, ':') + 1 );
				$values = $this->_redis->zRange($key, 0, -1) ?: array();
				$indexKey = "Info:" . $uid . ":" . $tid . ":" . $date;
				foreach ($values as $val)
				{
					if( $this->_redis->zDelete($indexKey, $val) )
					{
						$this->_redis->hIncrBy($indexInfoKey, $date, -1);
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
	private function getInfoKey($uid, $tid, $time = '')
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
	private function getInfoIndexKey($uid, $tid)
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
	private function getWebpageindex($pid, $dateline)
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
	private function getWebpageInfos($pid)
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
	private function getWebpageFans( $pageId )
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
		import("social.Action.WebpageAction");
		import("social.Model.WebpageFollowerModel");
		$webPage = new WebpageAction();
		return $_fans[$pageId] = $webPage->getAllFollowers($pageId);
	}
	
	/**
	 * @author fbbin
	 * @desc 存储一个网页的所有的信息ID/删除整个网页的所有ID
	 * @param int $pid
	 * @param int $tid
	 * @param int $action
	 */
	private function doWebpageTid($pid, $tid, $dateline, $action = true)
	{
		if( ! ($pid && $tid) )
		{
			return false;
		}
		$wpkey = $this->getWebpageindex( $pid, $dateline );
		$wpindexKey = $this->getWebpageInfos($pid);
		//往当前的网页中添加一条信息
		if( $action )
		{
			if( $this->_redis->zAdd($wpkey, $dateline, $tid) )
			{
				return is_numeric($this->_redis->hIncrBy($wpindexKey, date('Ym', $dateline), 1));
			}
		}
		//从当前的网页中删除一条信息
		else
		{
			if( $this->_redis->zDelete($wpkey, $tid) )
			{
				return is_numeric($this->_redis->hIncrBy($wpindexKey, date('Ym', $dateline), -1));
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
	private function doSentFans( $pid, $tid, $dateline, $tags, $action = true )
	{
		if( ! ($pid && $tid) || empty($tags) )
		{
			return false;
		}
		$dateline = $dateline ?: time();
		$fans = $this->getWebpageFans( $pid );
		if( empty($fans) )	return true;
		foreach($fans as $uid)
		{
			foreach($tags as $tagid)
			{
				$infoKey = $this->getInfoKey($uid, $tagid, $dateline);
				$infoIndexKey = $this->getInfoIndexKey($uid, $tagid);
				//执行分发操作
				if( $action )
				{
					$zaddStatus = $this->_redis->zAdd($infoKey, $dateline, $tid);
					if( $zaddStatus !== false )
					{
						$incrStatus = $this->_redis->hIncrBy($infoIndexKey, date('Ym', $dateline), 1);
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
					$zDelStatus = $this->_redis->zDelete($infoKey, $tid);
					if( $zDelStatus !== false )
					{
						$decrStatus = $this->_redis->hIncrBy($infoIndexKey, date('Ym', $dateline), -1);
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
	
	/**
	 * @author fbbin
	 * @desc 异步保存到disk
	 */
	public function __destruct()
	{
		$this->_redis->bgsave();
	}
	
}