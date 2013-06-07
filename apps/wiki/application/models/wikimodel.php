<?php
/**
 * @desc    对版本的操作
 * @author  sunlufu
 * @date    2012-04-26
 * @version v1.2.001
 */
class WikiModel extends MY_Model {
	private $citiaon_table = 'wiki_citiao';
	private $items_table = 'wiki_items';
	private $item_version_table = 'wiki_module_version';

	public function __construct(){
		parent::__construct();
        $this ->load->library("Mongo_db","","mdb");
	}
    
    //一级分类
    public function get_category_main()
    {
        return service("Interest")->get_category_main();
    }
    //二级分类
    public function get_category_scend($main_id)
    {
    	return service("Interest")->get_category_small($main_id);
    }
	/**
	 * 获取词条的名称
	 * 
	 * @author bohailiang
	 * @date   2012/5/4
	 * @param  $citiao_id  int    词条id
	 * @return string / false
	 */
	public function getCitiaoName($citiao_id = 0){
		if(empty($citiao_id)){
			return false;
		}

		$where = array('_id' => new MongoId($citiao_id));
		$name = $this->mdb->findOne($this->citiaon_table, $where, array('citiao_title'));
		return $name['citiao_title'];
	}
	/**
	 * 获取词条某义项的名称
	 * 
	 * @author bohailiang
	 * @date   2012/5/4
	 * @param  $item_id  int    义项id
	 * @return string / false
	 */
	public function getItemName($item_id = 0){
		if(empty($item_id)){
			return false;
		}

		$where = array('_id' => new MongoId($item_id));
		$name = $this->mdb->findOne($this->items_table, $where, array('item_desc'));
		return $name['item_desc'];
	}

	/**
	 * 获取词条某义项的信息
	 * 
	 * @author bohailiang
	 * @date   2012/5/4
	 * @param  $item_id  int    义项id
	 * @return array / false
	 */
	public function getItemInfo($item_id = 0){
		if(empty($item_id)){
			return false;
		}

		$where = array('_id' => new MongoId($item_id));
		$result = $this->mdb->findOne($this->items_table, $where);
		return $result;
	}

	/**
	 * 获取词条最后发布时间
	 * 
	 * @author bohailiang
	 * @date   2012/5/4
	 * @param  $citiao_id  int    词条id
	 * @return int / false
	 */
	public function getCitiaoLastTime($citiao_id = 0){
		if(empty($citiao_id)){
			return false;
		}

		$where = array('citiao_id' => $citiao_id);
		$result = $this->mdb->findAll($this->items_table, $where, array('lastest_datetime'), array('lastest_datetime' => -1), 1);
		if($result){
			return $result[0]['lastest_datetime'];
		}
		return false;
	}

	/**
	 * 获取词条编辑次数
	 * 
	 * @author bohailiang
	 * @date   2012/5/4
	 * @param  $citiao_id  int    词条id
	 * @return int / false
	 */
	public function getCitiaoEditCount($citiao_id = 0){
		if(empty($citiao_id)){
			return false;
		}

		$where = array('citiao_id' => $citiao_id);
		//获取词条下所有义项的编辑次数
		$result = $this->mdb->sum($this->items_table, $where, 'edit_count');
		if(false !== $result){
			return $result;
		}
		return false;
	}
	
    /**
     * 发送通知
     * 
     * @author	lanyanguang
     * @date	2012/3/8
     * @param int $notice_type 通知类型 1 个人通知 2 网页通知
     * @param int $uid  发送通知当前用户uid
     * @param int $to_uid  接收用户uid
     * @param string $btype  通知大分类
     * @param string $stype  通知小分类
     * @param array $param   其他参数（如URL）
     * @return state@
     * 1   操作对象uid 不存在
     * 2   大分类不存在
     * 3   小分类不存在
     * 4   当前用户登录uid不存在
     * 5   信息对应分类过滤失败
     * 6   小分类输入错误
     * 7   操作失败！
     * 8   操作成功！
     * */
    function sendNotice($notice_type = 1, $uid = NULL, $to_uid = NULL, $btype = NULL, $stype = NULL, $param = array()) {
        if (!$notice_type || !$uid || !$to_uid || !$btype || !$stype) {
            return false;
        }
        return call_soap('ucenter', 'Notice', 'add_notice',array($notice_type, $uid, $to_uid, $btype, $stype, $param));
    }

	/**
	 * 通过查找义项表，判断词条是否是多义项
	 * 
	 * @author bohailiang
	 * @date   2012/5/4
	 * @param  $citiao_id  int    词条id
	 * @return true 多义项 / false 单义项
	 */
	function haveMoreItems($citiao_id = ''){
		if(empty($citiao_id) || !is_string($citiao_id)){
			return false;
		}

		$where = array('citiao_id' => $citiao_id);
		$result = $this->mdb->where($where)->count($this->items_table);

		if($result){
			if(1 < $result){
				return true;
			}
		}
		return false;
	}
	/**
	 * 获取义项版本信息
	 * @param string $item_id 义项id
	 * @param int $version_id 义项版本
	 * return array
	 */
    public function getItemVersionInfo($item_id, $version_id) {
    	if(!check_mongo_id($item_id) || !is_numeric($version_id)) return array();
    	$map = array();
    	$map['item_id'] = $item_id;
    	$map['version'] = intval($version_id);
    	$version_info = $this->mdb->findOne($this->item_version_table, $map);
    	return $version_info;
    }
}