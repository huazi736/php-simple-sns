<?php
/**
 * 点击资料按钮进入的页面
 * @author guojianhua
 * 词条入口
 */
class Webwiki extends MY_Controller{
	protected $data;
	public function __construct() {
		parent::__construct();
        
		$this->load->model("webwikimodel", "wiki");
		
		$this->assignHeaderNav(array("网页资料"));
		
		$url_avatar = get_webavatar($this->web_info['uid'],'s',$this->web_id);
		$this->assign("url_avatar",$url_avatar);
		$this->assign("web_url",mk_url("main/index/index",array("web_id"=>$this->web_id)));
		$this->assign("create_url",mk_url("wiki/module/add",array("web_id"=>$this->web_id)));
    	$this->assign("web_info",$this->web_info);
    	$this->assign("wiki_index",mk_url("wiki/wikit/index",array("web_id"=>$this->web_id)));
	}

	
	
	public function index(){
		
		if(!intval($this->web_id)) $this->showMessage("参数错误", 2);
		
		$detail = intval(G("detail")); //区别网页资料详细页面和网页资料首页
		
		//判断网页是否是自己创建 
		
		$item_info = $this->wiki->findItemInfoByWebId($this->web_id);
		
		if ($this->uid == $this->web_info['uid']) {
			if ($item_info) {
				
				if($detail == 1){
					$this->wiki_info($item_info,TRUE);//网页资料详细页面
				}else{
				    $this->show_wiki($item_info, TRUE);//新版网页资料首页
				}
				
			} elseif ($citiao_infos = $this->wiki->findItemInfoByWebName($this->web_info['name'])) {
				//显示匹配结果
				$this->display_match($citiao_infos);
			} else {
				
				//显示创建词条,网页创建者
				
				$this->assign("action","create");
				$this->display("wiki_unmatch.html");
				
			}
		} else {
			if ($item_info) {
				
				if($detail == 1){
					$this->wiki_info($item_info);//网页资料详细页面
				}else{
				    $this->show_wiki($item_info);//新版网页资料首页
				}
				
			} else {
				
				//非网页创建者，显示为空
				
				$this->assign("action","display_none");
    			$this->display("wiki_unmatch.html");
			}
		}
	}
	
	/**
	 * 显示词条匹配（模糊搜索）所有义项 默认显示前5条
	 * @param unknown_type $citiao_infos
	 */
	
	public function display_match($citiao_infos = array()) {
		$size = 5;
		$data = $this->get_items(0,$size,$citiao_infos);
		if (!$data['item_num']) {
			//显示创建词条,网页创建者
			$this->assign("action","create");
			$this->display("wiki_unmatch.html");
			exit();
		} 
		
		$this->assign("data",$data);
		
		$this->assign("display_url",mk_url("wiki/citiaoContent/index",array("web_id"=>$this->web_id)));
		$this->assign("match_url",mk_url("wiki/webwiki/wiki_match",array("web_id"=>$this->web_id)));
		
		$this->display("wiki_match.html");
	}
	
	/**
	 * 分页获取匹配义项
	 * @param int $page
	 * @param int $size
	 * @param array $citiao_infos
	 */
	public function get_items($page = 0 ,$size = 5,$citiao_infos = array()) {
		if (!count($citiao_infos)) {
			$citiao_infos = $this->wiki->findItemInfoByWebName($this->web_info['name']);
		}
		$citiao_ids = array();
		
		foreach ($citiao_infos as $citiao_info) {
			$citiao_ids[] = sprintf("%s",$citiao_info['_id']);
		}
		$modules = $this->wiki->findItemInfo($citiao_ids);
		
		
		//词条义项按网页分类进行排序
		$tmp1 = array();
		$tmp2 = array();
		$tmp3 = array();
		foreach ($modules as $val) {
			if ($val['web_p_id'] == $this->web_info['imid']) {
				$tmp1[] = $val;
			} elseif ($val['web_s_ids'] == $this->web_info['iid']) {
				$tmp2[] = $val;
			} else {
				$tmp3[] = $val;
			}
		}
		
		$modules = array_merge($tmp1,$tmp2,$tmp3);
		
		
		$result['item_num'] = count($modules);
		
		if ($this->isAjax()) {
			$page = $this->input->post("page");
			$offset = ($page - 1)*$size;
			if ($offset > $result['item_num']) {
				$offset = $result['item_num'];
				$result['is_end'] = 1;
			} else {
				$result['is_end'] = 0;
			}
			$result['data'] = array_slice($modules, $offset , $size);
			$tmp = array();
			foreach ($result['data'] as $key=>$val ) {
				$val['description'] = $this->filter($val['description']);
				$tmp[$key] = $val;
			}
			$result['data'] = $tmp;
			
			$this->assign("data",$result);
			$this->assign("display_url",mk_url("wiki/citiaoContent/index",array("web_id"=>$this->web_id)));
			$result['data'] = $this->fetch("wiki_ajax_match.html");
			
			die($this->ajaxReturn($result,"获取成功"));
			
		} else {
			if ($result['item_num'] <= $size) {
				$result['is_end'] = 1;
			} else {
				$result['is_end'] = 0;
			}
			$result['data'] = array_slice($modules, 0 , $size);
			$tmp = array();
			foreach ($result['data'] as $key=>$val ) {
				$val['description'] = $this->filter($val['description']);
				$tmp[$key] = $val;
			}
			$result['data'] = $tmp;

			return $result;
		}
		
	}
	
	/**
	 * return   $result['status'] = 0/1
	 * 			$result['msg'] = 参数错误/匹配成功/匹配失败
	 * 			$result['url'] = 资料首页地址   
	 */
	public function wiki_match() {
		$item_id = $this->input->post("item_id");
		$version = (int)$this->input->post("version");
		if (empty($item_id) || empty($version)) {
			die($this->ajaxReturn("","参数错误",0));
		}
		if ($this->uid != $this->web_info['uid']) {
			die($this->ajaxReturn('',"无权引用",0));
		}
		$result = $this->wiki->updatebywebid($this->web_info,$item_id,$version);
		
		$result['url'] = mk_url("wiki/webwiki/index",array("web_id"=>$this->web_id));
		die($this->ajaxReturn($result,"引用成功"));
	} 
	
	/**
	 * 重新匹配词条
	 */
	public function rematch() {
		
		if ($this->uid != $this->web_info['uid']) {
			die("<script>history.go(-1)</script>");
		}
		$citiao_infos = $this->wiki->findItemInfoByWebName($this->web_info['name']);
		//显示匹配结果
		
		if (!count($citiao_infos)) {
			//显示创建词条,网页创建者
				
			$this->assign("action","create");
			$this->display("wiki_unmatch.html");
		} else {
			$this->display_match($citiao_infos);
		}
		
		
	}
	
 
 
	
    /**
     * 显示词条页面，先获取词条的当前使用的版本
     * @param array $item
     */
    public function wiki_info($item = array(),$is_self = FALSE) {
    	$item_id = $item['item_id'];
    	$version = $item['use_module_version'];
    	
    	$item_content = $this->wiki->findItemContent($item_id,$version);
    	
    	//设置词条、义项visit_count
    	
    	$this->load->model('commonmodel','common');
    	$result = $this->common->setVisitNum($item_content['citiao_id'],$item_id);
    	
    	$item_content['content'] = $this->filter($item_content['content']);
    	$item_content['description'] = $this->filter($item_content['description']);
    	
    	$new_version = isset($item['new_module_version']) ? $item['new_module_version'] : $version;
    	$history_url = mk_url("wiki/version/index",array("cid"=>$item_content['citiao_id'],
    													"iid"=>$item_id,
    													"t"=>1,
    													"use_module_version"=>$version,
    													"new_module_version"=>$new_version,
    													"web_id"=>$this->web_id));
    	
    	if ($item_content['create_uid'] == 0) {
    		$create_name = "系统用户";
    		$action_url = "javascript:;";
    	} else {
    		$create_info = getUserInfo((string)$this->web_info['uid']);
    		$create_name = $create_info['username'];
    		$action_url = mk_url("main/index/main",array("dkcode"=>$create_info['dkcode']));
    	}
    	
    	
    	$item_content['create_name'] = $create_name;
    	$item_content['action_url'] = $action_url;
    	$item_content['edit_datetime'] = date("Y年m月d日 H:i",$item_content['edit_datetime']);
    	$item_content['create_time'] = date("Y年m月d日",strtotime($this->web_info['create_time']));
    	
    	$item_content['version'] = $version;
    	$item_content['new_version'] = $is_self ? $item['new_module_version']: $version;
    
    	
    	$this->assign("rematch_url",mk_url("wiki/webwiki/rematch",array("web_id"=>$this->web_id)));
    	$this->assign("edit_url",mk_url("wiki/module/edit",array("item_id"=>$item_id,"version"=>$version,"web_id"=>$this->web_id)));
    	$this->assign("item_content",$item_content);
    	$this->assign("is_self",$is_self);
    	$this->assign("history_url",$history_url);
    	
    	
  		
		$this->display("wiki_info.html");
    }
    
	/**
	 * 过滤非法词
	 */
	public function filter($content = ""){
		$this->load->model("commonmodel", "common");
		return  $this->common->filterContent($content);	
	}
 
    /**
     * 判断词条是否匹配成功
     * @param array $data
     * @return boolean | array
     */
    private function is_find_item($data = array()){
    	if (!count($data)) {
    		return FALSE;
    	}
		foreach ( $data ['item_info'] as $item ) {
			$web_p_id = is_array($item['web_p_id']) ? $item['web_p_id'] : array($item['web_p_id']);
			if (in_array ( $data ['web_info'] ['imid'], $web_p_id ) && count ( array_intersect ( $item ['web_s_ids'], $data ['web_iids'] ) )) {
				return $item;
			}
		}
		return FALSE;
    }
  
    public function successful($msg = "",$data = '') {
    		$result['status'] = 1;
			$result['message'] = $msg;
			$result['data'] = $data;
			echo json_encode($result);
			exit;
    }
    
    public   function error($msg = "") {
    	$result['status'] = 0;
		$result['message'] = $msg;
		echo json_encode($result);
		exit;
    }
    /*
     * 新版网页资料首页 (新增网页个性化区域)  仿wiki_info方法
     */
    public function show_wiki($item = array(),$is_self = FALSE){
    	$item_id = $item['item_id'];
    	$version = $item['use_module_version'];
    	
    	$item_content = $this->wiki->findItemContent($item_id,$version);
    	
    	//设置词条、义项visit_count
    	
    	$this->load->model('commonmodel','common');
    	$result = $this->common->setVisitNum($item_content['citiao_id'],$item_id);
    	
    	$item_content['content'] = htmlspecialchars_decode($this->filter($item_content['content']));
    	$item_content['description'] = htmlspecialchars_decode($this->filter($item_content['description']));
    	
    	$new_version = isset($item['new_module_version']) ? $item['new_module_version'] : $version;
    	$history_url = mk_url("wiki/version/index",array("cid"=>$item_content['citiao_id'],
    													"iid"=>$item_id,
    													"t"=>1,
    													"use_module_version"=>$version,
    													"new_module_version"=>$new_version,
    													"web_id"=>$this->web_id));
    	
    	$create_info = getUserInfo((string)$this->web_info['uid']);
        $create_name = $create_info['username'];
    	$action_url = mk_url("main/index/main",array("dkcode"=>$create_info['dkcode']));
    	
    	
    	$item_content['create_name'] = $create_name;
    	$item_content['action_url'] = $action_url;
    	$item_content['edit_datetime'] = date("Y年m月d日 H:i",$item_content['edit_datetime']);
    	$item_content['create_time'] = date("Y年m月d日",strtotime($this->web_info['create_time']));
    	
    	$item_content['version'] = $version;
    	$item_content['new_version'] = $is_self ? $item['new_module_version']: $version;
    
    	
    	$this->assign("rematch_url",mk_url("wiki/webwiki/rematch",array("web_id"=>$this->web_id)));
    	$this->assign("edit_url",mk_url("wiki/module/edit",array("item_id"=>$item_id,"version"=>$version,"web_id"=>$this->web_id)));
    	$this->assign("item_content",$item_content);
    	$this->assign("is_self",$is_self);
    	$this->assign("history_url",$history_url);
    	
    	//查看更多连接
    	$this->assign("look_more_wiki_url", mk_url("wiki/webwiki/index", array("web_id" => $this->web_id, "detail" => 1)));
    	
    	//@todo 考虑做缓存
    	//显示插件
    	$this->assign("plugin_content", $this->showPlugin("normal"));
    	$this->assign("all_plugin_normal_js",$this->showPlugin("normal_js"));
    	
    	if($is_self) {
    		$this->assign("edit_plugin_content", $this->showPlugin("edit"));
    		$this->assign("all_plugin_edit_js", $this->showPlugin("edit_js"));
    	    //编辑提交插件url
    	    $update_plugin_url = mk_url("wiki/webwiki/ajaxUpdateWebPluginInfo", array("web_id" => $this->web_id));
    	    $this->assign("update_plugin_url", $update_plugin_url);
    	}
        
        $this->display("wiki_show.html");
    }
    
    public function showPlugin($action = ""){
    	//查询插件信息
    	$this->load->model("pluginmodel", "plugin");
    	$web_plugin_info = $this->plugin->getWebPluginInfo($this->web_id);
    	$plugin_content = "";
    	$all_plugin_config_id = array();
        
    	if(empty($web_plugin_info)){
        	$plugin_content = "";
        }else{
        	foreach($web_plugin_info as $k => $v){
        		$all_plugin_config_id[] = $v['_id'] = sprintf("%s", $v['_id']);
        		$v['web_id'] = $this->web_id;
        		$v['action'] = $action;
        		$plugin_content .= wiki_widget($v['plugin_widget'], $v);
        	}
        }
        
        if(!$this->isAjax() && ($action == "normal")){
           //赋值公共信息
           $this->assign("web_id", $this->web_id);
           
           $this->assign("all_plugin_config_id", $all_plugin_config_id ? implode(",", $all_plugin_config_id) : "");
        }
        return $plugin_content;
    }
    
    /*
     * ajax更新用户输入的插件值
     */
    public function ajaxUpdateWebPluginInfo(){
    	$web_id = intval(G("web_id"));
    	if(empty($web_id)) $this->ajaxReturn("", "参数错误", 2, "json");
    	
    	$post = $_POST;
    	$safe_post = safedata($post);
    	foreach($safe_post as $k => $v){
    		if(!check_mongo_id($k)){
                $this->ajaxReturn("", "参数错误", 2, "json");
    			break;
    		}
    		$safe_post["plugin_values.$k"] = $v;
    		unset($safe_post[$k]);
    	}
    	
    	$this->mdb->update("wiki_web_plugin_info", array("web_id" => $web_id), $safe_post, false, true);
    	$this->ajaxReturn("", "操作成功", 1, "json");
    }
    //更新等级数据
    public function updateRateInfo(){
       $web_id = intval(G("web_id"));
       if(!$web_id || !is_int($web_id))  $this->ajaxReturn("", "参数错误1", 2, "json");
       
       $plugin_config_id = P("idBox");
       if(!$plugin_config_id || !check_mongo_id($plugin_config_id))
			$this->ajaxReturn("", "参数错误2", 2, "json");
	   
	   $rate = P("rate");
	   if(!$rate || !is_numeric($rate))
	   		$this->ajaxReturn("", "参数错误3", 2, "json");
	   		
	   //当前的数据
	   $current_average = P("current_average");
	   if($current_average && !is_numeric($current_average))
	   		$this->ajaxReturn("", "参数错误4", 2, "json");
	   
	   //当前的评价人数
	   $current_rate_nums = intval(P("current_rate_nums"));
	   if($current_rate_nums && !is_int($current_rate_nums))  $this->ajaxReturn("", "参数错误5", 2, "json");
	   
	   //计算现在数据
	   $now_rate_nums = $current_rate_nums + 1;
	   $now_average = number_format((($current_average * $current_rate_nums) + $rate)/$now_rate_nums, 1, ".", "");
	   $update_data = array("average" =>$now_average, "rate_nums" => $now_rate_nums);
	   //更新现在的数据
	   $result = $this->mdb->update_custom("wiki_web_plugin_info", array("web_id" => $web_id), array('$set' => array("plugin_values.$plugin_config_id" => $update_data)), false, true);
	   
	   if($result){
	      $this->ajaxReturn("", "操作成功", 1, "json");		
	   }else{
	   	  $this->ajaxReturn("", "操作失败", 2, "json");	
	   }	
    }
}

//End By wiki/application/controllers/webwiki.php