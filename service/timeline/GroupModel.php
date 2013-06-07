<?php
/**
* [ Duankou Inc ]
* Created on 2012-3-5
* @author fbbin
* The filename : GroupModel.class.php   2012 02:51:58
*/
class GroupModel extends RedisModel
{
	
	private $id = null;
	
	public function addGroupInfo( $data )
	{
		$data = array_merge($data, array('id'=>$this->iniId(),'dateline'=>SYS_TIME));
		if( $this->_redis->hMset($this->getInfoKey(), $data) !== false )
		{
			$newInfo = $this->getNewInfo( $data['gid'], $data['uid'] );
			if( $this->_redis->zAdd($this->getGroupKey( $data['gid'] ), SYS_TIME, $this->id) !== false)
			{
				return array_merge(array($this->id=>$data), $newInfo);
			}
			return array();
		}
		else
		{
			return array();
		}
	}
	
	private function iniId()
	{
		$this->id = $this->_redis->incr('Gtid');
		return $this->id;
	}

	private function getInfoKey( $id )
	{
		if( empty($id) )
		{
			return "Group:".$this->id;
		}
		return "Group:".$id;
	}
	
	private function getGroupKey($gid)
	{
		return "Gtinfo:" . $gid;
	}
	
	public function getNewInfo( $gid, $uid = '' )
	{
		$ids = $this->_redis->zRangeByScore($this->getGroupKey($gid), SYS_TIME - 30, SYS_TIME) ?: array();
		$arr = array();
		foreach( $ids as $id )
		{
			$infos = $this->_redis->hGetAll($this->getInfoKey($id));
			if( $uid && $infos['uid'] == $uid )
			{
				continue;
			}
			$arr[$id] = $infos;
		}
		return $arr;
	}
	
	public function getPageInfo( $gid , $page = 1 , $nums = 10)
	{
		$nowpage = ($page-1) * $nums;
		$ids = $this->_redis->zRevRange($this->getGroupKey($gid), $nowpage, $nowpage + $nums -1 ) ?: array();
		$arr = array();
		foreach( $ids as $id )
		{
			$arr[$id] = $this->_redis->hGetAll($this->getInfoKey($id));
		}
		return array('data'=>$arr,'count'=>$this->getKeyCount($gid));
	}
	
	private function getKeyCount($gid)
	{
		return $this->_redis->zSize($this->getGroupKey($gid));
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

?>