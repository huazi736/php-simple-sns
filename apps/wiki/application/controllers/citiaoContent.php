<?php
class CitiaoContent extends MY_Controller {
    public function __construct() {
        parent::__construct();
    }

    public function index() {
        $this -> load -> library('Mongo_db');
        //获取参数值
        $second_name = G("second_name");
        $cid = G('citiaoid');
        $iid = G('mtmeas');
        //参数检测
        if(empty($cid) || !check_mongo_id($cid))
				$this->showMessage("参数错误", 2);
		if(empty($iid) || !check_mongo_id($iid))
				$this->showMessage("参数错误", 2);
		//获取所有义项
        $items = $this -> getItemsByCid($cid);
        //获取义项当前版本内容
        $itemcurrentversioninfo = $this->getItemCurrentVersionInfoByIid($iid);

        if (empty($items) || empty($itemcurrentversioninfo)) {
           $this ->showmessage("词条不存在或者义项不存在", 2);
        } else {
        	
        	$this -> load ->model("commonmodel");
            $this ->commonmodel ->setVisitNum($cid,$iid);//增加 查看次数

            $itemcurrentversioninfo['content'] = filterContent($itemcurrentversioninfo['content']) ;
            $itemcurrentversioninfo['description'] = filterContent($itemcurrentversioninfo['description']) ;
        }
        $this->assign("iid", $iid);
       
        $this -> assign('items', $items);
        $this -> assign('current_version_info', $itemcurrentversioninfo);
        
        $this-> assign("item_num", count($items)); 

        $this -> assign('item_url', mk_url("wiki/citiaoContent/index", array()));
        //历史版本url
        $this -> assign('histroy_url', mk_url("wiki/version/index", array('cid' => $cid, "iid" => $iid, 'web_id' => $this->web_id)));
        //编辑词条url
        $this -> assign('edit_url', mk_url("wiki/module/edit", array('cid' => $cid, "item_id" => $iid, "web_id" => $this->web_id,'version'=>$itemcurrentversioninfo['version'])));
        //面包屑
        $this -> assignHeaderNav(array("查看词条"));
        //是否可以引用
        $this->checkMatch();
        $this -> assign('webid', $this->web_id);
        
        $this -> display('wiki_view');
    }
    /*
     * 根据cid获取所有义项
     */
    public function getItemsByCid($cid = "") {
        return $this -> mongo_db -> findAll('wiki_items', array('citiao_id' => $cid), array(), array('visit_count' => -1));
    }
    /*
     * 根据iid获取义项当前版本信息
     */
    public function getItemCurrentVersionInfoByIid($iid = "") {
        $current_version_infos = $this -> mongo_db -> findAll('wiki_module_version', array('item_id' => $iid), array(), array("version" => -1), 1);
        return ($current_version_infos ? $current_version_infos[0] : array());
    }
}
?>