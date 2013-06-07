<?php
/**
 * @desc           词条匹配、创建
 * @author         guojianhua
 * @date           2012-05-08
 * @version        1.0
 * @description    词条匹配、创建、义项创建
 * @history         <author><time><version><desc>
 */
class WebWikiModel extends CI_Model{
	public function __construct() {
		$this->load->library("mongo_db", "", "mdb");
	}
	/**
	 * @desc 根据网页id查询关联的义项信息  即wiki_items的一条记录信息
	 * @param int $webid
	 */
	public function findItemInfoByWebId($webid = 0) {
		$item = array();
		return $item = $this->mdb->findOne("wiki_web_info", array("web_id" => intval($webid)));
	}
	
	/**
	 * 通过WebName匹配词条
	 * @param string $web_name
	 * @return array:
	 */
	public function findItemInfoByWebName($web_name = "") {
		if (empty($web_name)) {
			return array();
		}
		
		$citiao_title = new MongoRegex("/$web_name/i");
		$result = $this->mdb->findAll("wiki_citiao",array("citiao_title"=>$citiao_title));
		return $result ? $result :array();
	}
	
	/**
	 * 查询义项内容
	 * @param string $item_id
	 * @param int $version
	 * @return array
	 */
	public function findItemContent($item_id,$version) {
		$iteminfo = $this->mdb->findOne("wiki_items",array("_id"=>new MongoId("$item_id")));
		$module = $this->mdb->findOne("wiki_module_version",array("item_id"=>$item_id,"version"=>(int)$version));
		$result['content'] = $module['content'];
		$result['description'] = $module['description'];
		$result['edit_datetime'] = $module['edit_datetime'];
		$result['img_file'] = isset($module['image_file']) ? $module['image_file'] : "";
		$result['imgDesc'] = $module['imgDesc'];
		$result['item_desc'] = $iteminfo['item_desc'];
		$result['create_uid'] = $iteminfo['create_uid'];
		$result['web_name'] = $iteminfo['web_name'];
		$result['create_time'] = $iteminfo['create_time'];
		$result['citiao_id'] = $iteminfo['citiao_id'];
		$result['is_system'] = isset($iteminfo['is_system']) ? $iteminfo['is_system'] : '0';
		return $result;
	}
	
	/**
	 * 查询词条所有义项信息
	 * @param array $citiao_id
	 * @return multitype:|Ambigous <multitype:, unknown>
	 */
	public function findItemInfo($citiao_id) {
		if (empty($citiao_id)) {
			return array();
		}
		if (is_array($citiao_id)) {
			$citiao_id = array('$in'=>$citiao_id);
		}
		
		$items = $this->mdb->findAll("wiki_items",array("citiao_id"=>$citiao_id));
		$modules = array();
		foreach ($items as $val) {
			$module = array();
			$tmp = $this->mdb->findAll("wiki_module_version",array("item_id"=>sprintf("%s",$val['_id'])),array(),array('version'=>-1),1);
			if (count($tmp)) {
				$module['module_id'] = sprintf("%s",$tmp[0]['_id']);
				$module['description'] = $tmp[0]['description'];
				$module['item_id'] = $tmp[0]['item_id'];
				$module['version'] = $tmp[0]['version'];
				$module['item_desc'] = $val['item_desc'];
				$module['web_p_id'] = $val['web_p_id'];
				$module['web_name'] = $val['web_name'];
				$module['web_s_ids'] = $val['web_s_ids'];
				$module['citiao_id'] = $val['citiao_id'];
				
				$modules[] = $module;
			}
		}
		
		return $modules;
	}
	/**
	 * 获取义项详细信息
	 * @param array $item  wiki_items的一条记录信息
	 */
	public function getItemDetailByItem($item = array()) {
		if(!isset($item['_id']) || !isset($item['current_version'])) return array();
		
		$item_info = array();
		
		$item_version_info = $this->mdb->findOne("wiki_item_version", array("item_id" => $item["_id"], "version_id" => $item["current_version"]), array('module_ids'));
		
		$module_ids_arr = $item_version_info['module_ids'];
		$module_ids = arrayTwoOneByField($module_ids_arr, "module_version_id");
		
		$item['module_infos'] = $this->mdb->findAll("wiki_module_version", array("_id" => array('$in' => $module_ids)), array('content', 'module_template_id', 'module_version'));
		
		return $item;
	}
	
	/**
	 * 根据Item_id 查询义项信息
	 * @param string $item_id
	 * @return array 
	 */
	public function findSystemItem($item_id = "") {
		if (empty($item_id)) return array();
		
		$item_info = $this->mdb->findOne("wiki_items",array("_id"=>new MongoId($item_id)));
		
		return $item_info;
	}

	/**
	 * 更新wiki_items数据
	 * @param array $web_info
	 * @param string $item_id
	 * @param int $item_version
	 */
	public function updatebywebid($web_info, $item_id, $item_version) {
		//查询义项信息
		$web_id = intval($web_info['aid']);
		$item_info = $this->findSystemItem($item_id);
		
		//判断引用义项是否为系统义项，修改义项分类信息
		if (isset($item_info['is_system']) && $item_info['is_system'] && $item_info['web_p_id'] == 0) {
			$this->update_system_item($web_info,$item_info);
		}
		
		//查义该网页是否引用 过该义项，如果引用 过，只更新use_module_version，否则新加一条新纪录
		if ($this->mdb->findOne('wiki_web_info',array("web_id"=>$web_id,"item_id"=>$item_id))) {
			$data = array('use_module_version'=>intval($item_version),
						  'new_module_version'=>intval($item_info['edit_count'])
						);
			$result = $this->mdb->update ( 'wiki_web_info', array("web_id"=>$web_id), $data,FALSE,FALSE);
		} else {
			$data = array (
				'item_id' => $item_id, 
				'new_module_version' => intval($item_info['edit_count']), 
  				'use_module_version' => intval($item_version),
  				'web_id' => $web_id,
			);
			$result = $this->mdb->update ( 'wiki_web_info', array("web_id"=>$web_id), $data,FALSE,TRUE);
		}
		if ($result) {
			$return['status'] = 1;
			$return['msg'] = '添加成功';
		} else {
			$return['status'] = 0;
			$return['msg'] = '添加失败';
		}
		return $return;
	}
	
	/**
	 * 引用词条新版本功能
	 * @param string $item_id
	 * @param unknown_type $data
	 * @return string
	 */
	public function updatebyitemid($item_id = '',$new_version) {
		if ($this->mdb->update ( 'wiki_web_info', array("item_id"=>$item_id), array('new_module_version'=>intval($new_version)) , TRUE , FALSE)) {
			$result['status'] = 1;
			$result['msg'] = '添加成功';
		} else {
			$result['status'] = 0;
			$result['msg'] = '添加失败';
		}
		return $result;
	}
	/**
	 * 取汉字拼音，包括多音字，返回拼音的首字母
	 * @param string $char
	 * @return array
	 */
	public function get_pinyin_array($char) {
		$arr = $this->mdb->findOne("wiki_ziku",array("char"=>$char),array("py"));
		$first_letter = array();
		foreach ($arr['py'] as $val) {
			$first_letter[] = strtoupper(mb_substr($val, 0 ,1));
		}
		return $first_letter;
	}
	
	/**
	 * 匹配系统词条
	 * @param int $web_id
	 * @param string $item_id
	 * 
	 */
	public function update_system_item($web_info = array(),$item_info = array()){
		if (empty($web_info) || empty($item_info)) return FALSE;
		
		$data = array(
				'web_p_id' => $web_info['imid'],
  				'web_s_ids' => $web_info['iid'],
			);
		$this->mdb->update("wiki_items",array("_id"=>$item_info['_id']),$data);
	}
}

//End By wiki/application/models/webwikimodel.php