<?php
/*
 * 词条搜索
 */
class Seach extends MY_Controller {
    public function __construct() {
        parent::__construct();
    }
    public function index() {
        $this -> load -> library('Mongo_db');
        $keyword = P('seach');

        if (empty($keyword))//如果提交的查询内容为空，直接返回
        {
            $this -> showmessage("查询不能为空", 2, mk_url("wiki/wikit/index", array("web_id" => $this->web_id)));
        }
        $cid = $this -> getCidByKeyword($keyword);
        $iid = $this->getFirstIidByCid($cid);
        
        if (empty($cid) || empty($iid)) { // 没有搜索到词条的时候
            $this -> assign('keyword', $keyword);
            $this -> assign('wiki_index_url', mk_url("wiki/wikit/index", array("web_id" => $this->web_id)));
            $this -> assignHeaderNav(array("词条搜索"));
            $this -> display('wiki_unsearch.html');
        } else {    
            header("Location:". mk_url("wiki/citiaoContent/index", array("citiaoid" => $cid, "mtmeas" => $iid, "web_id" => $this->web_id)));
        }

    }
    /*
     * 根据关键词查询cid
     */
    public function getCidByKeyword($keyword = "") {
        $citiaos = $this -> mongo_db -> findAll('wiki_citiao', array('citiao_title' => $keyword), array("_id"), array('visit_count' => -1), 1);
        return ($citiaos ? $citiaos[0]['_id']->__toString() : "");
    }
    /*
     * 根据cid查询第一个iid
     */
    public function getFirstIidByCid($cid = "") {
        $items = $this -> mongo_db -> findAll('wiki_items', array('citiao_id' => $cid), array("_id"), array('visit_count' => -1), 1);
        return ($items ? $items[0]['_id']->__toString() : "");
    }
}
?>