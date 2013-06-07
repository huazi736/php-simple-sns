<?php
/*
 * 群组
 * title :
 * Created on 2012-06-19
 * @author hexin
 * discription : IM对接
 */
class Redis_User
{
	/**
	 * 获取用户信息
	 * @param max $uid 可以是一个id或id数组
	 * @return array(id,name,dkcode) | array(array(id,name,dkcode),...)
	 */
	function getUserInfo($uids)
	{
		if( !is_array( $uids ) )
		{
			$uids = array($uids);
		}
		require_cache(APPPATH . 'core' . DS . 'MY_Redis' . EXT);
		if (empty($uids))
		{
			return false;
		}
		$oRedis = MY_Redis::getInstance();
		$aResults = array();
		foreach ($uids as $uid)
		{
			$aResults[$uid] = $oRedis->hMGet('user:'.$uid, array('id','name','dkcode'));
			$aResults[$uid]['uid'] = $aResults[$uid]['id'];
			$aResults[$uid]['username'] = $aResults[$uid]['name'];
			unset($aResults[$uid]['id'], $aResults[$uid]['name']);
		}
		return count($uids) == 1 ? array_pop($aResults) : $aResults;
	}
}