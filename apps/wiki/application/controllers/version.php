<?php
/**
 * @desc            词条历史版本管理 
 * @author          sunlufu
 * @date            2012-04-26
 * @version         v1.2.001
 * @description     词条历史版本列表\词条某一版本详细内容\还原版本\举报等
 * @history         <author><time><version><desc>
 */
class Version extends MY_Controller {
	private $pagesize = 0; //每页数量
	private $wiki_index_url = ""; //wiki主页地址
	
	public function __construct(){
		parent::__construct();
		$this->load->model('wikimodel', '_wiki');
		
		$this->wiki_index_url = mk_url("wiki/wikit/index", array("web_id" => $this->web_id));
		$this->pagesize = 10; 
	}

	/**
	 * 历史版本列表页
	 * 
	 * @author lijianwei
	 * @date   2012-07-06
	 * @access public
	 */
	public function index(){
		//&cid=4fec20067f8b9a9554000000&iid=4fec20067f8b9a9554000001 测试安利
		
		//$_GET['iid'] = "4fec20067f8b9a9554000001";
		
		//获取义项id
		$item_id = G('iid');
		
		if(empty($item_id) || !check_mongo_id($item_id))
				$this->showMessage("参数错误", 2);
				
	    //过滤其他参数
	    if(isset($_GET['t']) || isset($_POST['t'])){
	       $t = intval($this->input->get_post("t"));
	       if(empty($t)) $this->showMessage("参数错误", 2);
	       $this->assign("t", $t);
	    }
	    
		if(isset($_GET['new_module_version']) || isset($_POST['new_module_version'])){
	       $new_module_version = intval($this->input->get_post("new_module_version"));
	       if(empty($new_module_version)) $this->showMessage("参数错误", 2);
	       $this->assign("new_module_version", $new_module_version);
	    }
	    
	    if(isset($_GET['use_module_version']) || isset($_POST['use_module_version'])){
	       $use_module_version = intval($this->input->get_post("use_module_version"));
	       if(empty($use_module_version)) $this->showMessage("参数错误", 2);
	       $this->assign("use_module_version", $use_module_version);
	    }
	    
	    $this->assign("web_id", $this->web_id);

		//获取义项信息
		$item_info = array();
		$item_info = $this->_wiki->getItemInfo($item_id);
		if(empty($item_info)){
			$this->showMessage("义项不存在", 2, $this->wiki_index_url);
		}
		
	    //获取词条名称
		$citiao_name = getCitiaoName($item_info['citiao_id']);
		if(empty($citiao_name)){
			$this->showMessage("词条不存在", 2, $this->wiki_index_url);
		}
		
		//获取义项历史版本列表
		$version_list = $this->_version_list($item_id);
		
		//判断是否显示   “更多“ 按钮 
		$pageoffset = $this->getPageOffset();
		if(($pageoffset['pagesize'] >= $item_info['edit_count']) || (($pageoffset['pagesize'] + $pageoffset['offset']) >= $item_info['edit_count'])){
		    $this->assign("more_display", false);	
		}else{
			$this->assign("more_display", true);
		}		
		
		if(false == $version_list){
			$this->showMessage("没有历史版本", 2, $this->wiki_index_url);
			
		}

		$this->assign('citiao_name', $citiao_name);
		$this->assign('item_info', $item_info);
		$this->assign('version_list', $version_list);
		$this->assign("edit_count", $item_info['edit_count']); //总的版本数
		
	    $this->assign("get_content_url", mk_url("wiki/version/getItemVersionContent"));  //获取内容ajax地址
		$this->assign("get_version_list_url", mk_url("wiki/version/ajaxGetVersionList", array("web_id" => $this->web_id)));  //获取版本列表ajax地址
		$this->assign("item_url", mk_url("wiki/citiaoContent/index", array("citiaoid" => $item_info['citiao_id'], "mtmeas" => $item_id, "web_id" => $this->web_id))); //义项url地址
		
		$this->assignHeaderNav(array("查看版本")); //导航
		$this->checkMatch(); //是否可以引用
		
		$this->display('wiki_version.html');
	}
	/**
	 * ajax 获取历史版本列表数据
	 * @param string $iid
	 * @param int $page
	 */
	public function ajaxGetVersionList(){
		$item_id = $this->input->get_post("item_id", true);
		if(empty($item_id) || !check_mongo_id($item_id)) $this->ajaxReturn("", "参数错误", 2);
		//获取义项历史版本列表
		$version_list = $this->_version_list($item_id);
		
		$more_display = true;
		$pageoffset = $this->getPageOffset();
		if(($pageoffset['pagesize'] + $pageoffset['offset']) >= $this->input->get_post('edit_count', true)){
			$more_display = false;
		}
		$this->assign("version_list", $version_list);
		$this->ajaxReturn(array('html' => $this->fetch("wiki_ajax_version_list.html"), 'more_display' => $more_display), '', 1);
	}
	private function getPageOffset() {
		static $pageoffset = array();
		if($pageoffset) return $pageoffset;
		
		$page   = intval($this->input->get_post("page", true));
	    $version_diff_num = intval($this->input->get_post("new_module_version", true) - $this->input->get_post("use_module_version", true));
	    
	    $offset = 0;
	    if(!$page) $page = 1;
	    
	    if(($version_diff_num&&$page == 1)){
	         $pagesize = $version_diff_num + 1;
	         if($pagesize < $this->pagesize) $pagesize = $this->pagesize;
	    }elseif($version_diff_num&&$page != 1){
	    	if(($version_diff_num + 1) > $this->pagesize){
	    		$offset = $version_diff_num + 1 + ($page - 2) * $this->pagesize;
	    	}else{
	    		$offset = ($page - 1) * $this->pagesize;
	    	}
	    	$pagesize = $this->pagesize;
	    }else{
	    	$pagesize = $this->pagesize;
	    	$offset = ($page - 1) * $pagesize;
	    }
		
		return $pageoffset = array('pagesize' => $pagesize, 'offset' => $offset);
	}
    /**
	 * 查看历史版本完整页
	 * 
	 * @author lijianwei
	 * @date   2012/6/13
	 * @access public
	 */
	public function view() {
		//&cid=4fec20067f8b9a9554000000&iid=4fec20067f8b9a9554000001&vid=1 测试安利
		$iid = $this->input->get("iid", true);//义项id
		$vid = $this->input->get("vid", true);//义项版本号
		
		//检测参数
		if(!is_numeric($vid) || !check_mongo_id($iid))
			$this->showMessage("参数错误", 2);
		
		//获取义项信息
		$item_info = $this->_wiki->getItemInfo($iid);
		if(empty($item_info)){
			$this->showMessage("义项信息不存在", 2, $this->wiki_index_url);
		}
		
		//获取词条名称
		$citiao_name = $this->_wiki->getCitiaoName($item_info['citiao_id']);
		if(empty($citiao_name)){
			$this->showMessage("词条不存在", 2, $this->wiki_index_url);
		}
		
		//获取义项版本信息
		$item_version_info = $this->_wiki->getItemVersionInfo($iid, $vid);
	    if(empty($item_version_info)){
			$this->showMessage("义项版本信息不存在", 2, $this->wiki_index_url);
		}
		
		$this->assign("citiao_name", $citiao_name);  //词条名称
		$this->assign("item_info", $item_info); //义项信息
		$this->assign("version_info", $item_version_info); //义项版本信息
		$this->assign("vid", ($vid < 10 ? "0".$vid : $vid)); //版本号
		
		
		$this->assignHeaderNav(array("查看版本")); //导航
		$this->checkMatch(); //是否可以引用
		
		$this->display("wiki_view_version.html");
	}
	
	//获取义项版本内容
	public function getItemVersionContent(){
		$item_id = $this->input->get_post("item_id", true);
		$version = $this->input->get_post("version", true);
		
		if(empty($item_id) || empty($version))
		    $this->ajaxReturn("", "参数错误", 2);
		
		$this->load->library("Mongo_db", "mdb");
		
		$version_info = $this->mdb->findOne("wiki_module_version", array("item_id" => $item_id, "version" => intval($version)));
		
		if($version_info && is_array($version_info)){
			 $this->assign("version_info", $version_info);
			 $this->ajaxReturn($this->fetch("wiki_ajax_version_content"), "", 1);
		}else{
			$this->ajaxReturn("", "版本信息不存在或已经删除", 2);
		}
	}
	
	/**
	 * 获取某词条某义项历史版本列表
	 * 
	 * @author lijianwei
	 * @date   2012-07-06
	 * @param  $item_id     string  义项id
	 * @access private
	 * @return array() or false
	 */
	private function _version_list($item_id = ""){
		$pageoffset = $this->getPageOffset();
		
        $version_list = $this->mdb->findAll("wiki_module_version", array("item_id" => $item_id), array(), array("version" => -1),  $pageoffset['pagesize'], $pageoffset['offset']);
         		
		if($version_list){
			$uids = array();
			foreach($version_list as $key => $value){
				if($value['uid']) $uids[$value['uid']] = $value['uid'];
				//查看连接
				$version_list[$key]['version_link'] = mk_url("wiki/version/view", array('iid' => $value['item_id'], 'vid' => $value['version'], 'web_id' => $this->web_id));
				$version_list[$key]['new_version'] = ($value['version'] < 10 ? "0".$value['version'] : $value['version']);
			}
		    //获取用户姓名、头像
			$users_info = $this->_getAuthorInfo($uids);
			
			foreach($version_list as $key => $value) {
				if(!$value['uid']){//系统导入,特殊处理
					$version_list[$key]['username'] = "系统用户";
					$version_list[$key]['avatar'] = MISC_ROOT . 'img/default/avatar_s.gif?v='. time();
					$version_list[$key]['author_url'] = "#";
				}else{
					$version_list[$key]['username'] = isset($users_info[$value['uid']]['username']) ? $users_info[$value['uid']]['username'] : "";
					$version_list[$key]['avatar'] = isset($users_info[$value['uid']]['avatar']) ? $users_info[$value['uid']]['avatar'] : "";
					$version_list[$key]['author_url'] = isset($users_info[$value['uid']]['author_url']) ? $users_info[$value['uid']]['author_url'] : "";
				}
			}
			
			return $version_list;
		}
		return false;
	}

	/**
	 * 获取版本作者的用户姓名、头像
	 * 
	 * @author bohailiang
	 * @date   2012/5/4
	 * @param  $uids          array   用户id
	 * @access private
	 */
	private function _getAuthorInfo($uids = array()){
		$users_info = array();
		if($uids){
			$users_info = service('user')->getUserList($uids,array('uid', 'username', 'dkcode'));
		}
		
		if($users_info){
			foreach($users_info as $key => $value){
				$users_info[$value['uid']]['username'] = $value['username'];//姓名
				$users_info[$value['uid']]['avatar'] = get_avatar($value['uid']);//头像
				$users_info[$value['uid']]['author_url'] = mk_url('main/index/main', array('dkcode' => $value['dkcode']));
			}
		}
		return $users_info;
	}

	/**
	 * 生成操作记录的语句
	 * 
	 * @author bohailiang
	 * @date   2012/5/9
	 * @param  $version_list    array   历史版本列表
	 * @access private
	 */
	private function _buildRecordContent($version_list = array()){
		$content_arr = array('module_add' => '<a class="fwb name" href="' . mk_url('main/index/index', array('action_dkcode' => '%s'), false) . '">%s</a> 添加了模块 <a class="module" href="' . mk_url('wiki/version/view', array('cid' => '%s', 'iid' => '%s', 'vid' => '%s', 'mid' => '%s')) . '">"%s"</a><span class="time">%s</span>',
							 'module_update' => array(
								'<a class="fwb name" href="' . mk_url('main/index/index', array('action_dkcode' => '%s'), false) . '">%s</a> 修改了模块 <a class="module" href="' . mk_url('wiki/version/view', array('cid' => '%s', 'iid' => '%s', 'vid' => '%s', 'mid' => '%s')) . '">"%s"</a><span class="time">%s</span>',
								'<a class="fwb name" href="' . mk_url('main/index/index', array('action_dkcode' => '%s'), false) . '">%s</a> 修改了模块 <a class="module" href="' . mk_url('wiki/version/view', array('cid' => '%s', 'iid' => '%s', 'vid' => '%s', 'mid' => '%s')) . '">"%s"</a> 为 <a class="module" href="' . mk_url('wiki/version/view', array('cid' => '%s', 'iid' => '%s', 'vid' => '%s', 'mid' => '%s')) . '">"%s"</a><span class="time">%s</span>'
							 ),
							 'module_recover' => '<a class="fwb name" href="' . mk_url('main/index/index', array('action_dkcode' => '%s'), false) . '">%s</a> 还原了 <a class="version" href="' . mk_url('wiki/version/view', array('cid' => '%s', 'iid' => '%s', 'vid' => '%s')) . '">版本NO.%s</a> 的模块 <a class="module" href="' . mk_url('wiki/version/view', array('cid' => '%s', 'iid' => '%s', 'vid' => '%s', 'mid' => '%s')) . '">"%s"</a><span class="time">%s</span>',
							 'version_recover' => '<a class="fwb name" href="' . mk_url('main/index/index', array('action_dkcode' => '%s'), false) . '">%s</a> 还原了 <a class="version" href="' . mk_url('wiki/version/view', array('cid' => '%s', 'iid' => '%s', 'vid' => '%s')) . '">版本NO.%s</a><span class="time">%s</span>',
							 'system_recover' => '版本NO.%s被举报次数过多，系统已自动还原至版本NO.%s<span class="time">%s</span>'
		);
		if($version_list){
			foreach($version_list as $key => $value){
				$content_str = '';
				$record_content = $value['record_content'];
				$is_system_recover = false;
				switch($record_content['action']){
					case "module_add":
						$content_str = sprintf($content_arr['module_add'], $value['author']['dkcode'], $value['author']['username'], $value['citiao_id'], $value['item_id'], $record_content['to_version'][0], $record_content['to_module'][0], $record_content['to_module'][1], $value['record_time']);
						break;
					case "module_update":
						if(empty($record_content['from_module'])){
							$content_str = sprintf($content_arr['module_update'][0], $value['author']['dkcode'], $value['author']['username'], $value['citiao_id'], $value['item_id'], $record_content['to_version'][0], $record_content['to_module'][0], $record_content['to_module'][1], $value['record_time']);
						} else {
							$content_str = sprintf($content_arr['module_update'][1], $value['author']['dkcode'], $value['author']['username'], $value['citiao_id'], $value['item_id'], $record_content['from_version'][0], $record_content['from_module'][0], $record_content['from_module'][1], $value['citiao_id'], $value['item_id'], $record_content['to_version'][0], $record_content['to_module'][0], $record_content['to_module'][1], $value['record_time']);
						}
						break;
					case "module_recover":
						$content_str = sprintf($content_arr['module_recover'], $value['author']['dkcode'], $value['author']['username'], $value['citiao_id'], $value['item_id'], $record_content['from_version'][0], $record_content['from_version'][1], $value['citiao_id'], $value['item_id'], $record_content['to_version'][0], $record_content['to_module'][0], $record_content['to_module'][1], $value['record_time']);
						break;
					case "version_recover":
						$content_str = sprintf($content_arr['version_recover'], $value['author']['dkcode'], $value['author']['username'], $value['citiao_id'], $value['item_id'], $record_content['to_version'][0], $record_content['to_version'][1], $value['record_time']);
						$is_system_recover = true;
						break;
					case "system_recover":
						$content_str = sprintf($content_arr['module_add'], $record_content['from_version'], $record_content['to_version'], $value['record_time']);
						break;
					case "":
					default:
						break;
				}
				$version_list[$key]['record_content'] = $content_str;
				$version_list[$key]['is_system_recover'] = $is_system_recover;
			}
		}
		return $version_list;
	}

	/**
	 * 获取每个版本的状态，正常或非正常
	 * 
	 * @author bohailiang
	 * @date   2012/5/8
	 * @param  $citiao_id     int  词条id
	 * @param  $item_id       int  义项id
	 * @param  $version_list    array   历史版本列表
	 * @access private
	 */
	private function _getVersionStatus($citiao_id = '', $item_id = '', $version_list = array()){
		$version_arr = array();
		if($version_list){
			foreach($version_list as $key => $value){
				$version_arr[] = (int)$value['action_version'];
			}

			$version_status = $this->_version->getVersionStatus($citiao_id, $item_id, $version_arr);
			if($version_status){
				foreach($version_list as $key => $value){
					$version_list[$key]['version_status'] = $version_status[$value['action_version']]['version_status'];
					$version_list[$key]['version_id'] = $version_status[$value['action_version']]['_id'];
					$version_list[$key]['version_view_url'] = mk_url('wiki/version/view', array('cid' => $value['citiao_id'], 'iid' => $value['item_id'], 'vid' => $version_status[$value['action_version']]['_id']));
					$version_list[$key]['is_current'] = false;
				}
			}
		}

		return $version_list;
	}

	/**
	 * ajax获取历史版本列表
	 * 
	 * @author bohailiang
	 * @date   2012/5/4
	 * @access public
	 */
	public function versionListAjax(){
		//获取词条id、义项id
		$citiao_id = $this->input->post('cid', true);
		$item_id = $this->input->post('iid', true);
		//获取最早时间
		$min_record_time = $this->input->post('mct', true);

		$result = array();
		if(empty($min_record_time) || !is_numeric($min_record_time)){
			$result['status'] = 0;
			$result['message'] = '非法的版本时间';

			echo json_encode($result);
			exit;
		}

		//获取历史版本列表
		$version_list = $this->_version_list($citiao_id, $item_id, $min_record_time);
		if(false === $version_list){
			$result['status'] = 0;
			$result['message'] = '没有更多历史版本';

			echo json_encode($result);
			exit;
		}

		$this->assign('version_list', $version_list);
		$result['status'] = 1;
		$result['data'] = $this->fetch('version/history_ajax.html');
		$result['mid'] = $version_list['min_record_time'];

		echo json_encode($result);
		exit;
	}

	/**
	 * 查看历史版本完整页
	 * 
	 * @author bohailiang
	 * @date   2012/5/4
	 * @access public
	 */
	public function view_old(){
		//获取词条id、义项id、版本id
		$citiao_id = $this->input->get('cid');
		$item_id = $this->input->get('iid');
		$the_version_id = $this->input->get('vid');
		$module_id = $this->input->get('mid');

		//获取词条名称
		$citiao_name = $this->_wiki->getCitiaoName($citiao_id);
		if(empty($citiao_name)){
			$this->redirect(mk_url('wiki/wiki'));
		}
		//获取词条当前义项名称
		$item_name = $this->_wiki->getItemName($item_id);
		if(empty($item_name)){
			$this->redirect(mk_url('wiki/wiki'));
		}

		//获取版本信息
		$version_info = $this->_version->getVersion($the_version_id);
		if(empty($version_info)){
			$this->redirect(mk_url('wiki/wiki'));
		}

		//获取版本中的模块信息
		$version_info['module_list'] = $this->_version->getModules($version_info['module_ids']);
		$version_info['module_list'] = json_encode($version_info['module_list']);
		//获取举报记录
		$report = $this->_version->getVersionReport($the_version_id);
		$user_ip = $this->input->ip_address();
		if($report && in_array($user_ip, $report['report_info']['report_ips'])){
			$version_info['allow_report'] = 0;
		} else {
			$version_info['allow_report'] = 1;
		}
		$version_info['edit_datatime'] = date($this->config->item('dateFormat'), $version_info['edit_datatime']);
		//是否是多义项
		$hava_more_item = $this->_wiki->haveMoreItems($citiao_id);

		$params = array(1 => array('url' => mk_url('wiki/wiki'), 'name' => '端口百科'),
						2 => array('url' => mk_url('wiki/wiki'), 'name' => $citiao_name),
						3 => array('url' => mk_url('wiki/version', array('cid' => $citiao_id, 'iid' => $item_id)), 'name' => '历史版本')
				  );
		$this->assignHeaderNav($params);

		$this->assign('item_name', $item_name);
		$this->assign('citiao_name', $citiao_name);
		$this->assign('version_info', $version_info);
		$this->assign('hava_more_item', $hava_more_item);
		$this->assign('module_id', $module_id);
		$this->display('version/view');
	}

	/**
	 * 单模块的还原
	 * 
	 * @author bohailiang
	 * @date   2012/5/4
	 * @access public
	 */
	public function moduleRecover(){
		$module_id = $this->input->post('mid');
		$citiao_id = $this->input->post('cid');
		$item_id = $this->input->post('iid');
		$current_version = $this->input->post('cver');
		$module_title = $this->input->post('mtitle');
		$version_id = $this->input->post('vid');

		//权限判断
		$result = $this->_checkPermission($item_id);
		if(true !== $result){
			echo json_encode($result);
			exit;
		}

		//必要数据检测
		$module_info = $this->_version->getModules(array($module_id));
		if(!$module_info){
			$result['status'] = 0;
			$result['message'] = '非法的模块id';

			echo json_encode($result);
			exit;
		}
		$module_info = $module_info[0];
		$module_version = $module_info['module_version'];

		//判断还原次数是否已达到限制
		$check = $this->_checkAllowRecover($citiao_id, $item_id);

		if(empty($module_id) || !is_string($module_id)){
			$result['status'] = 0;
			$result['message'] = '非法的模块id';

			echo json_encode($result);
			exit;
		}

		//判断模块是否存在于当前版本中
		//step1:获取当前版本
		$last_version = $this->_version->getCurrentVersionId($citiao_id, $item_id, true);
		if(false === $last_version){
			$result['status'] = 0;
			$result['message'] = '未找到当前最新版本';

			echo json_encode($result);
			exit;
		}
		$recover_type = 'new';
		$old_module_id = '';
		//step2:判断模块是否已存在,待完成
		$old_module_id = $this->_inModules($module_id, $last_version['module_ids']);

		$result = false;
		if(empty($old_module_id)){//新增模块
			$result = $this->_version->newVersion($citiao_id, $item_id, array('new' => array('module_id' => $module_id, 'module_version' => $module_version)), $this->uid);
		} else if($old_module_id != $module_id){//更新模块
			$result = $this->_version->newVersion($citiao_id, $item_id, array('update' => array('old_module_id' => $old_module_id, 'module_id' => $module_id, 'module_version' => $module_version)), $this->uid);
		} else if($old_module_id == $module_id){//模块已存在
			$result['status'] = 0;
			$result['message'] = '该模块已被还原';

			echo json_encode($result);
			exit;
		}

		if($result){
			//更新义项编辑时间、编辑次数
			$this->_version->updateItemsVersion($result['current_version'], $item_id);
			//记录操作
			$record_content = array('action' => 'module_recover', 'from_version' => array($version_id, $current_version), 'from_module' => array($module_info['_id'], $module_title));
			$this->_version->addActionRecord($this->uid, $result['current_version'], $citiao_id, $item_id, $result['_id'], $record_content);
			//记录还原日志
			$this->_version->addRecoverLog($citiao_id, $item_id, $module_id, '', $result['_id'], $this->uid);

			$result['status'] = 1;
			$result['message'] = '该模块还原成功';

			echo json_encode($result);
			exit;
		}

		$result['status'] = 1;
		$result['message'] = '该模块还原失败';

		echo json_encode($result);
		exit;
	}

	/**
	 * 判断一个module是否在所给module数组中
	 * 
	 * @author bohailiang
	 * @date   2012/5/4
	 * @param  $module_id   int    模块编辑版本id
	 * @param  $modules     array  模块数组
	 * @access private
	 * @return string / false
	 */
	private function _inModules($module_id = '', $modules = array()){
		if(empty($module_id) || empty($modules)){
			return false;
		}

		$old_module_id = '';
		//获取模块的历史版本
		$module_versions = $this->_version->getModuleVersions($module_id);
		foreach($modules as $key => $value){
			if($value['module_version_id'] == $module_id || in_array($value['module_version_id'], $module_versions)){
				$old_module_id = $value['module_version_id'];
				break;
			}
		}

		return $old_module_id;
	}

	/**
	 * 整个版本的还原
	 * 
	 * @author bohailiang
	 * @date   2012/5/4
	 * @access public
	 */
	public function versionRecover(){
		$version_id = $this->input->post('vid');
		$citiao_id = $this->input->post('cid');
		$item_id = $this->input->post('iid');

		//权限判断
		$result = $this->_checkPermission($item_id);
		if(true !== $result){
			echo json_encode($result);
			exit;
		}
		$result = array();

		//必要数据检测
		$version_info = $this->_version->getVersion($version_id);
		if(!$version_info || $version_info['citiao_id'] != $citiao_id || $version_info['item_id'] != $item_id){
			$result['status'] = 0;
			$result['message'] = '非法的版本id';

			echo json_encode($result);
			exit;
		}

		if(empty($version_id) || !is_string($version_id)){
			$result['status'] = 0;
			$result['message'] = '非法的版本id';

			echo json_encode($result);
			exit;
		}

		//判断还原次数是否已达到限制
		$check = $this->_checkAllowRecover($citiao_id, $item_id);

		//版本还原
		$result = $this->_version->versionRecover($version_id, $this->uid);

		if($result){
			//更新义项编辑时间、编辑次数
			$this->_version->updateItemsVersion($result['current_version'], $item_id);
			//记录操作
			$record_content = array('action' => 'version_recover', 'from_version' => array($version_info['_id'], $version_info['version_id']), 'to_version' => array($result['_id'], $result['current_version']));
			$this->_version->addActionRecord($this->uid, $result['current_version'], $citiao_id, $item_id, $result['_id'], $record_content);
			//记录还原日志
			$this->_version->addRecoverLog($citiao_id, $item_id, '', $version_id, $result['_id'], $this->uid);

			$result['status'] = 1;
			$result['message'] = '该版本还原成功';

			echo json_encode($result);
			exit;
		}

		$result['status'] = 1;
		$result['message'] = '该版本还原失败';

		echo json_encode($result);
		exit;
	}

	/**
	 * 判断是否允许还原
	 * 
	 * @author bohailiang
	 * @date   2012/5/8
	 * @access public
	 * @return true / false
	 */
	private function _checkAllowRecover($citioa_id = '', $item_id = ''){
		$now_hour = date('G');
		$now_hour = (int)$now_hour;
		if(8 <= $now_hour){
			$time = mktime(8, 0, 0, date('m'), date('d'), date('Y'));
		} else {
			$time = time() - 12 * 60 * 60;
			$time = mktime(8, 0, 0, date('m', $time), date('d', $time), date('Y', $time));
		}

		//获取24小时内还原的次数
		$recover_count = $this->_version->recoverCount($citioa_id, $item_id, $time);

		if($this->recover_check_count <= $recover_count){
			$result['status'] = 0;
			$result['message'] = $this->recover_check_hour . '小时内，只允许还原 ' . $this->recover_check_count .  ' 次';

			echo json_encode($result);
			exit;
		}
		//获取版本状态
		$is_able = $this->_version->checkAbleVersion($version_id);
		if(false == $is_able){
			$result['status'] = 0;
			$result['message'] = '该版本已非正常，不能还原';

			echo json_encode($result);
			exit;
		}

		return true;
	}

	/**
	 * 根据当前ip，更新是否允许举报状态
	 * 
	 * @author bohailiang
	 * @date   2012/5/10
	 * @param  $version_list    array   历史版本列表
	 * @access private
	 */
	private function _updateAllowReport($version_list = array()){
		$version_ids = array();
		foreach($version_list as $key => $value){
			$version_ids[] = $value['version_id'];
		}

		$version_reports = $this->_version->getVersionsReport();
		$ips = array();
		if($version_reports){
			foreach($version_report_ips as $key => $value){
				$get_ip = array();
				foreach($value['report_info']['report_ips'] as $v){
					$get_ip[] = $v['ip'];
				}
				$ips[$value['report_info']['version_id']] = $get_ip;
			}
		}

		$user_ip = $this->input->ip_address();
		foreach($version_list as $key => $value){
			if(!empty($ips) && isset($ips[$value['version_id']]) && in_array($user_ip, $ips[$value['version_id']])){
				$version_list[$key]['allow_report'] = 0;
			} else {
				$version_list[$key]['allow_report'] = 1;
			}
		}

		return $version_list;
	}

	/**
	 * 权限判断
	 * 
	 * @author bohailiang
	 * @date   2012/5/14
	 * @access private
	 */
	private function _checkPermission($item_id = 0){
		$this->load->model('privmodel', '_priv');
		//判断是否被禁
		$ban_check = $this->_priv->checkIdBan($this->uid, true);
		if(false === $ban_check){
			$json = array('status' => 1, 'action' => 'ban_check', 'result' => 0, 'message' => '被禁', 'html' => date('Y年m月d日', $ban_check));
			return $json;
		}

		$permission_check = $this->_priv->check_restore($this->uid, $item_id);
		if(false == $ban_check){
			//获取未关注页面
			$html = $this->getFollowWeb($item_id);
			$json = array('status' => 1, 'action' => 'permission_check', 'result' => 0, 'message' => '没有权限', 'html' => $html);
			return $json;
		}

		return true;
	}
}