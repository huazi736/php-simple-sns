<?php
/*
 * 群组
 * title :
 * Created on 2012-07-05
 * @author hexin
 * discription : 群组内部应用基本业务逻辑
 */
class AppModel extends MY_Model
{
	/**
	 * 获取群组内已安装的所有应用列表
	 * @param int $gid
	 * @return array
	 */
	public function getGroupApps($gid)
	{
		$ids = $this->getDao('GroupAppShip')->findAllIdsByGroup($gid);
		$apps = $this->getDao('GroupApp')->findByIds($ids);
		foreach($apps as &$p) {
			$this->getThumb($p);
			$this->getIcon($p);
		}
		return $apps;
	}
	
	/**
	 * 获取群组未安装的可安装应用列表
	 */
	public function getInstallApps($gid)
	{
		$ids = $this->getDao('GroupAppShip')->findAllIdsByGroup($gid);
		$apps = $this->getDao('GroupApp')->findNotInByIds($ids);
		foreach($apps as &$p) {
			$this->getThumb($p);
			$this->getIcon($p);
		}
		return $apps;
	}
	
	/**
	 * 添加应用
	 * @param int $gid
	 * @param int $uid
	 * @param int $appId
	 * @return boolean
	 */
	public function install($gid,$uid,$appId)
	{
		$install = array(
			'gid' => $gid,
			'uid' => $uid,
			'aid' => $appId,
		);
		$this->getDao('GroupAppShip')->create($install);
		$this->getDao('GroupApp')->setAppInc($appId, 1);
		return true;
	}
	
	/**
	 * 删除应用
	 * @param int $gid
	 * @param int $uid
	 * @param int $appId
	 */
	public function unstall($gid,$uid,$appId)
	{
		$this->getDao('GroupAppShip')->deleteByGroup($gid,$uid,$appId);
		$this->getDao('GroupApp')->setAppInc($appId, -1);
		return true;
	}
	
	private function getThumb(&$app)
	{
		if(isset($app['thumb']) && !empty($app['thumb'])) {
			$app['thumb'] = MISC_ROOT . $app['thumb'];
		} else {
			$app['thumb'] = MISC_ROOT . 'img/group/icon/app/big/app_default.png';
		}
	}
	
	private function getIcon(&$app)
	{
		if(isset($app['icon']) && !empty($app['icon'])) {
			$app['icon'] = MISC_ROOT . $app['icon'];
		} else {
			$app['icon'] = MISC_ROOT . 'img/group/icon/app/icon_default.png';
		}
	}
}