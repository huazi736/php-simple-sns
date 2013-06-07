<?php
/**
 * @desc    对版本的操作
 * @author  sunlufu
 * @date    2012-04-26
 * @version v1.2.001
 */
class VersionModel extends MY_Model {
	private $module_action = array('new', 'update', 'del', 'show');
	private $version_table = 'wiki_item_version';
	private $action_record_table = 'wiki_action_record';
	private $items_table = 'wiki_items';
	private $module_version_table = 'wiki_module_version';
	private $module_table = 'wiki_module';
	private $ver_to_ver_table = 'wiki_version_to_version';
	private $version_report_table = 'wiki_version_report';
	private $item_recover_log_table = 'wiki_item_recover_log';

	function __construct(){
		parent::__construct();
	}

	/**
	 * 生成某一词条某一义项的版本，并存入数据库
	 * 
	 * @author bohailiang
	 * @date   2012/5/3
	 * @param  $citiao_id  int    词条id
	 * @param  $item_id    int    义项id
	 * @param  $modules    array  模块信息，array('new' => array('module_id' => $module_id, 'module_version' => $module_version), 'update' => array('old_module_id' => $old_module_id, 'module_id' => $module_id, 'module_version' => $module_version), 'del' => array('old_module_id' => $old_module_id));
	 * @param  $uid        int     操作者id
	 * @param  $is_first   boolean 是否第一个版本
	 * @access public
	 * @return array('current_version' => 当前版本, '_id' => 当前版本id) / false
	 */
	public function newVersion($citiao_id = 0, $item_id = 0, $modules = array(), $uid = 0, $is_first = false){
		if(empty($citiao_id) || empty($item_id) || empty($modules) || empty($uid)){
			return false;
		}

		$last_version = '';
		if(false === $is_first){
			//获取最新的版本
			$last_version = $this->getLastVersion($citiao_id, $item_id);
		}

		//生成新版本
		$new_version = $this->_newVersion($citiao_id, $item_id, $modules, $uid, $last_version);

		//插入新版本
		$result = $this->_addVersion($new_version);

		if($result){
			return array('current_version' => $new_version['version_id'], '_id' => $result);
		}

		return false;
	}

	/**
	 * 获取某一词条某一义项的最新的版本
	 * 
	 * @author bohailiang
	 * @date   2012/5/3
	 * @param  $citiao_id  int    词条id
	 * @param  $item_id    int    义项id
	 * @access public
	 * @return array
	 */
	public function getLastVersion($citiao_id = 0, $item_id = 0){
		if(empty($citiao_id) || empty($item_id) || !is_string($citiao_id) || !is_string($item_id)){
			return false;
		}

		$where = array('citiao_id' => $citiao_id, 'item_id' => $item_id);
		$last_version = $this->mdb->findAll($this->version_table, $where, array(), array('version_id' => -1), 1);

		if($last_version){
			return $last_version[0];
		}
		return false;
	}
	
	/**
	 * 生成某一词条某一义项的新版本
	 * 
	 * @author bohailiang
	 * @date   2012/5/3
	 * @param  $citiao_id  int    词条id
	 * @param  $item_id    int    义项id
	 * @param  $modules    array  模块信息，array('new' => array('module_id' => $module_id, 'module_version' => $module_version), 'update' => array('old_module_id' => $old_module_id, 'module_id' => $module_id, 'module_version' => $module_version), 'del' => array('old_module_id' => $old_module_id));
	 * @param  $uid        int     操作者id
	 * @param  $last_version  array  最新版本
	 * @access private
	 * @return array / false
	 */
	private function _newVersion($citiao_id = 0, $item_id = 0, $modules = array(), $uid = 0, $last_version = array()){
		if(empty($citiao_id) || empty($item_id) || empty($modules)){
			return false;
		}

		if(empty($last_version)){
			$last_version = array('citiao_id' => $citiao_id, 'item_id' => $item_id, 'module_ids' => array(), 'version_id' => 0);
		}

		//新版本
		$new_version = $last_version;
		unset($new_version['_id']);
		$new_version['edit_datetime'] = time();
		$new_version['editor'] = $uid;
		$new_version['version_id'] = $new_version['version_id'] + 1;
		$new_version['version_status'] = 1;
		foreach($modules as $key => $value){
			//对模块的操作
			$new_version['module_ids'] = $this->_dealModules($new_version['module_ids'], $value, $key);
		}

		return $new_version;
	}

	/**
	 * 对版本中的module做操作
	 * 
	 * @author bohailiang
	 * @date   2012/5/3
	 * @param  $module_ids  array    被操作模块
	 * @param  $module      array    模块
	 * @param  $action      string   操作类型:new, update, del
	 * @access private
	 * @return array / false
	 */
	private function _dealModules($module_ids = array(), $module = array(), $action = ''){
		if(!in_array($action, $this->module_action)){
			return $module_ids;
		}

		if('new' == $action){
			$module_ids[] = $module;
		} else {
			foreach($module_ids as $key => $value){
				if($value['module_id'] == $module['old_module_id']){
					if('update' == $action){
						$module_ids[$key]['module_id'] = $module['module_id'];
						$module_ids[$key]['module_version'] = $module['module_version'];
					} else if('del' == $action){
						unset($module_ids[$key]);
					}

					break;
				}
			}
		}

		return $module_ids;
	}

	/**
	 * 新版本存入数据库
	 * 
	 * @author bohailiang
	 * @date   2012/5/3
	 * @param  $new_version  array   新版本
	 * @access private
	 * @return boolean
	 */
	private function _addVersion($new_version = array()){
		if(empty($new_version) || !is_array($new_version)){
			return false;
		}

		$result = $this->mdb->insert($this->version_table, $new_version, 'string');

		return $result;
	}

	/**
	 * 更新某一词条某一义项的当前版本，最新发布时间，编辑次数
	 * 
	 * @author bohailiang
	 * @date   2012/5/3
	 * @param  $version_id  int   当前版本
	 * @param  $item_id     int   词条的义项id
	 * @access public
	 * @return boolean
	 */
	public function updateItemsVersion($version_id = 0, $item_id = 0){
		if(empty($version_id) || empty($item_id) || empty($_id)){
			return false;
		}

		$where = array('_id' => new MongoId($item_id));
		$data = array('$set' => array('current_version' => $version_id, 'lastest_datetime' => time()), '$inc' => array('edit_count' => 1));

		$result = $this->mdb->update_custom($this->items_table, $where, $data);
		return $result;
	}

	/**
	 * 添加操作记录
	 * 
	 * @author bohailiang
	 * @date   2012/5/3
	 * @param  $action_uid      int      操作者id
	 * @param  $action_version  int      当前版本
	 * @param  $citiao_id       int      词条id
	 * @param  $item_id         int      义项id
	 * @param  $item_title      string   义项标题
	 * @param  $record_content  string   操作内容
	 * @access public
	 * @return boolean
	 */
	public function addActionRecord($action_uid = 0, $action_version = 0, $citiao_id = 0, $item_id = 0, $item_title = '', $record_content = array()){
		if(empty($citiao_id) || empty($item_id)){
			return false;
		}

		$data = array('action_uid' => $action_uid, 'action_version' => $action_version, 'citiao_id' => $citiao_id, 'item_id' => $item_id, 'item_title' => $item_title, 'record_content' => $record_content, 'record_time' => time());

		$result = $this->mdb->insert($this->action_record_table, $data, 'string');
		return $result;
	}

	/**
	 * 获取历史版本列表
	 * 
	 * @author bohailiang
	 * @date   2012/5/4
	 * @param  $citiao_id     int  词条id
	 * @param  $item_id       int  义项id
	 * @param  $limit         int  查询数量
	 * @param  $last_version  int  历史版本号
	 * @access public
	 * @return array / false
	 */
	public function version_list($citiao_id = 0, $item_id = 0, $limit = 10, $min_record_time = 0){
		if(empty($citiao_id) || empty($item_id) || empty($limit) || !is_numeric($min_record_time)){
			return false;
		}

		$where = array('citiao_id' => $citiao_id, 'item_id' => $item_id);
		if(0 < $min_record_time){
			$where['record_time'] = array('$lt' => $min_record_time);
		}

		$result = $this->mdb->findAll($this->action_record_table, $where, array(), array('record_time' => -1), $limit);
		if($result){
			return $result;
		}
		return false;
	}

	/**
	 * 获取版本信息
	 * 
	 * @author bohailiang
	 * @date   2012/5/3
	 * @param  $_id     string  版本id
	 * @access public
	 * @return array / false
	 */
	public function getVersion($_id = ''){
		if(empty($_id) || !is_string($_id)){
			return false;
		}

		$where = array('_id' => new MongoId($_id));
		$result = $this->mdb->findOne($this->version_table, $where);

		if($result){
			return $result;
		}
		return false;
	}

	/**
	 * 获取版本中的模块信息
	 * 
	 * @author bohailiang
	 * @date   2012/5/3
	 * @param  $module_ids     array  模块id数组
	 * @access public
	 * @return array / false
	 */
	public function getModules($module_ids = array()){
		if(empty($module_ids) || !is_array($module_ids)){
			return false;
		}

		$ids = array();
		foreach($module_ids as $key => $value){
			if(isset($value['module_id']) && is_string($value['module_id'])){
				$ids[] = new MongoId($value['module_id']);
			}
		}

		$result = false;
		if($ids){
			$where = array('_id' => array( '$in' => $ids ));
			$result = $this->mdb->findAll($this->module_version_table, $where);
		}
		return $result;
	}

	/**
	 * 通过模块编辑版本id，获取该模块的所有编辑版本
	 * 
	 * @author bohailiang
	 * @date   2012/5/3
	 * @param  $module_ids     array  模块id数组
	 * @access public
	 * @return array / false
	 */
	public function getModuleVersions($module_id = ''){
		if(empty($module_id) || !is_string($module_id)){
			return false;
		}

		$where = array('versions' => $module_id);
		$result = $this->mdb->findOne($this->module_table, $where);
		if($result){
			return $result['versions'];
		}
		return false;
	}

	/**
	 * 整个版本的还原
	 * 
	 * @author bohailiang
	 * @date   2012/5/3
	 * @param  $version_id   string   版本id
	 * @param  $uid          int   用户id
	 * @access public
	 * @return array / false
	 */
	public function versionRecover($version_id = '', $uid = 0, $alert = true, $old_version = array()){
		if(empty($version_id) || !is_string($version_id)){
			return false;
		}

		$old_version = $this->getVersion($version_id);
		$current_version = $this->getLastVersion($old_version['citiao_id'], $old_version['item_id']);
		//新版本
		$new_version = $old_version;
		unset($new_version['_id']);
		$new_version['edit_datetime'] = time();
		$new_version['editor'] = $uid;
		$new_version['version_id'] = $current_version['version_id'] + 1;
		$new_version['version_status'] = 1;

		//判断是否已被还原
		if($current_version['module_ids'] == $new_version['module_ids']){
			$result['status'] = 0;
			$result['message'] = '该版本已被还原';

			echo json_encode($result);
			exit;
		}

		//添加新版本
		$result = $this->_addVersion($new_version);
		//关联相关版本
		$this->versionToVersion($old_version['citiao_id'], $old_version['item_id'], array('from' => $old_version['_id'], 'to' => $result));
		//复制被举报的次数
		$this->copyReport($old_version['_id'], $result);
		if($result){
			return array('current_version' => $new_version['version_id'], '_id' => $result);
		}

		return false;
	}

	/**
	 * 绑定还原的版本和新生成的版本
	 * 
	 * @author bohailiang
	 * @date   2012/5/3
	 * @param  $citiao_id   string   词条id
	 * @param  $item_id     string   义项id
	 * @param  $version_arr array    相关联的版本,array('from' => $old_version_id, 'to' => $new_version_id)
	 * @access public
	 * @return true / false
	 */
	public function versionToVersion($citiao_id = 0, $item_id = 0, $version_arr = array()){
		if(empty($citiao_id) || empty($item_id) || empty($version_arr) || !is_array($version_arr)){
			return false;
		}

		//判断先前是否已关联
		if(!isset($version_arr['from']) || !isset($version_arr['to'])){
			return false;
		}
		$relation_versions = $this->getRelationVersion($version_arr['from']);
		if(false == $relation_versions){
			//插入关联数据
			$data = array('citiao_id' => $citiao_id, 'item_id' => $item_id, 'versions' => array($version_arr['from'], $version_arr['to']));
			$result = $this->mdb->insert($this->ver_to_ver_table, $data, 'string');
		} else {
			//更新关联数据
			$data = array('$push' => array('versions' => $version_arr['to']));
			$where = array('id' => $relation_versions['_id']);
			$result = $this->mdb->update_custom($this->ver_to_ver_table, $where, $data);
		}

		if($result){
			return true;
		}

		return false;
	}

	/**
	 * 通过一个版本id，获取相关联的版本
	 * 
	 * @author bohailiang
	 * @date   2012/5/3
	 * @param  $version_id   string   版本id
	 * @access public
	 * @return array / false
	 */
	public function getRelationVersion($version_id = ''){
		if(empty($version_id) || !is_string($version_id)){
			return false;
		}

		$where = array('versions' => $version_id);
		$result = $this->mdb->findOne($this->ver_to_ver_table, $where);

		if($result){
			return $result;
		}

		return false;
	}

	/**
	 * 判断是否是当前正在使用的版本
	 * 
	 * @author bohailiang
	 * @date   2012/5/8
	 * @param  $version_id   string   版本id
	 * @param  $citiao_id    string   词条id
	 * @param  $item_id      string   义项id
	 * @access public
	 * @return true / false
	 */
	public function isCurrentVersion($version_id = '', $citiao_id = '', $item_id = ''){
		if(empty($version_id) || empty($citiao_id) || empty($item_id) || !is_string($version_id) || !is_string($citiao_id) || !is_string($item_id)){
			return false;
		}

		//获取当前的版本号
		$current_version = $this->getCurrentVersion($citiao_id, $item_id);

		$version_info = $this->getVersion($version_id);
		if(($version_info['citiao_id'] != $citiao_id || $version_info['item_id'] != $item_id) || $version_info['version_id'] != $current_version){
			return false;
		}

		return true;
	}

	/**
	 * 获取某词条某义项当前的版本号
	 * 
	 * @author bohailiang
	 * @date   2012/5/8
	 * @param  $citiao_id    string   词条id
	 * @param  $item_id      string   义项id
	 * @access public
	 * @return true / false
	 */
	public function getCurrentVersion($citiao_id = '', $item_id = ''){
		if(empty($citiao_id) || empty($item_id) || !is_string($citiao_id) || !is_string($item_id)){
			return false;
		}

		$where = array('citiao_id' => $citiao_id, '_id' => new MongoId($item_id));
		$result = $this->mdb->findOne($this->items_table, $where);

		if($result){
			return $result['current_version'];
		}
		return false;
	}

	/**
	 * 自动还原正在使用的版本
	 * 
	 * @author bohailiang
	 * @date   2012/5/8
	 * @param  $citiao_id    string   词条id
	 * @param  $item_id      string   义项id
	 * @access public
	 * @return true / false
	 */
	public function autoRecover($citiao_id = '', $item_id = '', $new_version_id = '', $old_version_id = ''){
		if(empty($citiao_id) || empty($item_id) || !is_string($citiao_id) || !is_string($item_id) || empty($version_id) || !is_string($version_id)){
			return false;
		}

		$old_version = $this->getVersion($old_version_id);
		$new_version = $this->getVersion($new_version_id);

		//更新义项编辑时间、编辑次数
		$this->updateItemsVersion($new_version['version_id'], $item_id);
		//记录操作
		$record_content = array('action' => 'system_recover', 'from_version' => array($old_version['_id'], $old_version['version_id']), 'to_version' => array($new_version['_id'], $new_version['version_id']));
		$this->addActionRecord(0, $new_version['version_id'], $citiao_id, $item_id, $new_version['_id'], $record_content);
		//记录还原日志
		$this->addRecoverLog($citiao_id, $item_id, '', $version_id, $result['_id'], 0);
		return true;
	}

	/**
	 * 获取某词条某义项最新的可用版本id
	 * 
	 * @author bohailiang
	 * @date   2012/5/8
	 * @param  $citiao_id    string   词条id
	 * @param  $item_id      string   义项id
	 * @access public
	 * @return true / false
	 */
	public function getAbleVersion($citiao_id = '', $item_id = ''){
		if(empty($citiao_id) || empty($item_id) || !is_string($citiao_id) || !is_string($item_id)){
			return false;
		}

		$where = array('citiao_id' => $citiao_id, 'item_id' => $item_id, 'version_status' => 1);
		$sort = array('version_id' => -1);
		$field = array('_id');
		$result = $this->mdb->findAll($this->version_table, $where, $field, $sort, 1);

		if($result){
			return $result['_id'];
		}
		return false;
	}

	/**
	 * 获取某词条某义项最新的可用版本id
	 * 
	 * @author bohailiang
	 * @date   2012/5/8
	 * @param  $version_id_from    string   源版本id
	 * @param  $version_id_to      string   目标版本id
	 * @access public
	 * @return true / false
	 */
	public function copyReport($version_id_from = '', $version_id_to = ''){
		if(empty($version_id_from) || empty($version_id_to) || !is_string($version_id_to) || !is_string($version_id_from)){
			return false;
		}

		$old_version_report = $this->getVersionReport($version_id_from);
		if(false == $old_version_report){
			return true;
		}
		$new_version_report = array('editor' => $old_version_report['editor'], 
									'report_info' => array('report_count' => $old_version_report['report_count'],
														  'new_report' => 0,
														  'report_ips' => array()
									),
									'version_id' => $version_id_to
							  );
		$this->mdb->insert($this->version_report_table, $new_version_report, 'string');
	}

	/**
	 * 获取某一版本的举报记录
	 * 
	 * @author bohailiang
	 * @date   2012/5/7
	 * @param  $version_id   string  版本id
	 * @access public
	 * @return array / false
	 */
	public function getVersionReport($version_id = ''){
		if(empty($version_id) || !is_array($version_id)){
			return false;
		}

		$where = array('version_id' => $version_id);
		$result = $this->mdb->findOne($this->version_report_table, $where);
		
		if($result){
			return $result;
		}
		return false;
	}

	/**
	 * 获取某些版本的举报记录
	 * 
	 * @author bohailiang
	 * @date   2012/5/7
	 * @param  $versions   array  版本id数组
	 * @access public
	 * @return array / false
	 */
	public function getVersionsReport($versions = array()){
		if(empty($versions) || !is_array($versions)){
			return false;
		}

		$where = array('version_id' => array('$in' => $versions));
		$result = $this->mdb->findAll($this->version_report_table, $where);
		
		
		if($result){
			return $result;
		}
		return false;
	}

	/**
	 * 记录某一词条某一义项的还原日志
	 * 
	 * @author bohailiang
	 * @date   2012/5/7
	 * @param  $citiao_id        string  词条id
	 * @param  $item_id          string  义项id
	 * @param  $module_id        string  被还原的模块id
	 * @param  $version_id       string  被还原的版本id
	 * @param  $new_version_id   string  还原后的版本id
	 * @access public
	 * @return array / false
	 */
	public function addRecoverLog($citiao_id = '', $item_id = '', $module_id = '', $version_id = '', $new_version_id = '', $uid = 0){
		if(empty($citiao_id) || empty($item_id) || empty($new_version_id) || (empty($module_id) && empty($version_id))){
			return false;
		}

		$data = array('citiao_id' => $citiao_id, 'item_id' => $item_id, 'time' => time(), 'recover_uid' => $uid, 'module_id' => $module_id, 'version_id' => $version_id, 'new_version' => $new_version_id);
		$result = $this->mdb->insert($this->item_recover_log_table, $data, 'string');

		if($result){
			return $result;
		}

		return false;
	}

	/**
	 * 统计某一词条某一义项的还原次数
	 * 
	 * @author bohailiang
	 * @date   2012/5/8
	 * @param  $citiao_id        string  词条id
	 * @param  $item_id          string  义项id
	 * @param  $time             int     时间戳
	 * @access public
	 * @return int / false
	 */
	public function recoverCount($citiao_id = '', $item_id = '', $time = 0){
		$where = array();
		if(!empty($citiao_id) && is_string($citiao_id)){
			$where['citiao_id'] = $citiao_id;
		}
		if(!empty($item_id) && is_string($item_id)){
			$where['item_id'] = $item_id;
		}
		if(!empty($time) && 0 < $time){
			$where['time'] = array('$gte' => $time);
		}

		$result = $this->mdb->where($where)->count($this->item_recover_log_table);
		if($result){
			return $result;
		}
		return false;
	}

	/**
	 * 判断该版本是否正常
	 * 
	 * @author bohailiang
	 * @date   2012/5/8
	 * @param  $version_id   string  版本id
	 * @access public
	 * @return true / false
	 */
	public function checkAbleVersion($version_id = ''){
		if(empty($version_id) || !is_string($version_id)){
			return false;
		}

		$where = array('_id' => new MongoId($version_id));
		$result = $this->mdb->findOne($this->version_table, $where, array('version_status'));
		if($result && 1 == $result['version_status']){
			return true;
		}
		return false;
	}

	/**
	 * 获取每个版本的状态，正常或非正常
	 * 
	 * @author bohailiang
	 * @date   2012/5/8
	 * @param  $citiao_id     int    词条id
	 * @param  $item_id       int    义项id
	 * @param  $version_list  array  版本号数组
	 * @access public
	 * @return true / false
	 */
	public function getVersionStatus($citiao_id = '', $item_id = '', $version_arr = array()){
		if(empty($citiao_id) || empty($item_id) || !is_string($citiao_id) || !is_string($item_id) || empty($version_arr) || !is_array($version_arr)){
			return false;
		}

		$where = array('citiao_id' => $citiao_id, 'item_id' => $item_id, 'version_id' => array('$in' => $version_arr));
		$field = array('version_id', 'version_status');
		$result = $this->mdb->findAll($this->version_table, $where, $field);
		if($result){
			$version_status = array();
			foreach($result as $key => $value){
				$version_status[$value['version_id']]['version_status'] = $value['version_status'];
				$version_status[$value['version_id']]['_id'] = $value['_id'];
			}
			return $version_status;
		}
		return false;
	}

	/**
	 * 把某一词条某一义项的当前版本置空
	 * 
	 * @author bohailiang
	 * @date   2012/5/8
	 * @param  $citiao_id     int    词条id
	 * @param  $item_id       int    义项id
	 * @access public
	 * @return true / false
	 */
	public function clearVersion($citiao_id = '', $item_id = ''){
		if(empty($citiao_id) || !is_string($citiao_id) || empty($item_id) || !is_string($item_id)){
			return false;
		}

		$where = array('_id' => new MongoId($item_id));
		$version_id = 0;
		$data = array('$set' => array('current_version' => $version_id, 'lastest_datetime' => time()), '$inc' => array('edit_count' => 1));

		$result = $this->mdb->update_custom($this->items_table, $where, $data);
		return $result;
	}

	/**
	 * 获取某一词条某一义项当前使用的版本
	 * 
	 * @author bohailiang
	 * @date   2012/5/10
	 * @param  $citiao_id  int    词条id
	 * @param  $item_id    int    义项id
	 * @param  $all        boolean true 返回全部 | false 返回_id
	 * @access public
	 * @return string / false
	 */
	public function getCurrentVersionId($citiao_id = '', $item_id = '', $all = false){
		if(empty($citiao_id) || empty($item_id) || !is_string($citiao_id) || !is_string($item_id)){
			return false;
		}

		$where = array('_id' => new MongoId($item_id), 'citiao_id' => $citiao_id);
		$field = array('current_version');
		$result = $this->mdb->findOne($this->items_table, $where, $field);
		if(!$result){
			return false;
		}

		$current_version = $result['current_version'];
		$where = array('citiao_id' => $citiao_id, 'item_id' => $item_id, 'version_id' => $current_version);
		$field = array('_id');
		$result = $this->mdb->findOne($this->version_table, $where, $field);
		if($result){
			return (false == $all) ? $result['_id'] : $result;
		}

		return false;
	}
}