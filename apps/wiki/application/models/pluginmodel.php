<?php
/*
 * 插件model
 */
class PluginModel extends MY_Model{
	public function __construct(){
		parent::__construct();
	}
	/*
	 * 获取网页所有插件信息
	 */
	public function getAllWebPlugins(){
		return $this->mdb->findAll("wiki_web_plugins", array(), array(), array("admin_show_seq" => 1));
	}
	/*
	 * 根据网页id获取插件配置信息
	 */
	public function getPluginConfigByWebId($web_id = 0){
		static $plugin_config = array();
		if(isset($plugin_config[$web_id]))return $plugin_config[$web_id];

		//请求网页的大类
		$web_info = service("Interest")->get_web_info($web_id);
		
		if(!$web_info || !isset($web_info['imid'])) return array();
		$imid = $web_info['imid'];

		return $this->mdb->findAll("wiki_web_plugin_config", array("imid" =>$imid, "enabled" => 1), array(), array("seq" => 1));
	}
	
	/*
	 * 根据网id获取供前端显示的数据
	 */
	public function getWebPluginInfo($web_id = 0) {
		static $return = array();
		if($return) return $return;
		
		$plugin_config_info = $this->getPluginConfigByWebId($web_id);
		
		if(empty($plugin_config_info)) return array();
		
		$web_plugin_info = $this->mdb->findOne("wiki_web_plugin_info", array("web_id" => $web_id));

		$plugin_values = array();
		if($web_plugin_info && isset($web_plugin_info['plugin_values']))
			$plugin_values = $web_plugin_info['plugin_values'];
			
			

		$values = array();
		if(count($plugin_values)){
			foreach($plugin_values as $k => $v){
				$values[$k] = $v;
			}
		}

		foreach($plugin_config_info as $k => $v){
			$config_id = $v['_id']->__toString();
				
			$plugin_config_info[$k]['value'] = "";
			if($values && isset($values[$config_id])) $plugin_config_info[$k]['value'] = $values[$config_id];
		}
		
		return ($return = $plugin_config_info);
	}
}