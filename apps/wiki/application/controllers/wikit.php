<?php
/**
 * wiki主页
 */
class Wikit extends MY_Controller {
    public function __construct() {
        parent::__construct();
    }

    /*端口百科首页
     * @auth liyundong
     * */
    public function index() {
        $this -> load -> library('Mongo_db');
        $this -> load -> model('wikimodel');
        $this -> load -> model('privmodel');
        
        //获取参数
        $main_id = intval(G("main_id"));
        $second_name = intval(G("second_name"));
        $later = G("later");

        //一级分类列表
        $main = $this -> wikimodel -> get_category_main();
        //一级id
        $mainId =  $main_id ? $main_id : $main[0]['imid'];
        //二级分类列表
        $sec_res = $this -> wikimodel -> get_category_scend($mainId);
        //二级id
        if($sec_res) {
           $second_name = $second_name ? $second_name : $sec_res[0]['iid'];
        }else{//如果没有二级分类列表
           $second_name = $second_name ? $second_name : 0;
        }
        //字母
        if(empty($later)) $later = "A";
        
        //验证参数
        $mainIds = arrayTwoOneByField($main, "imid");
        $secIds = arrayTwoOneByField($sec_res, "iid");
        $laters = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z', '0-9');
        
        if($mainId && $mainIds && !in_array($mainId, $mainIds))
            $this->showMessage("一级分类不存在", 2);
        if($second_name && $secIds && !in_array($second_name, $secIds))
        	$this->showMessage("二级分类不存在", 2);
       	if(!in_array($later, $laters))
       		$this->showMessage("字母参数错误", 2);
       	
       	//组装查询条件	
        $where = array();
       	
        //一级分类
        if($mainId) $where['web_p_id'] = "$mainId";
        //二级分类
        if($second_name) $where['web_s_ids'] = "$second_name";
        //字母
        $where_later = array();
       	if($later == "0-9")
       	   $where_later = array('0', '1', '2', '3', '4', '5', '6', '7', '8', '9');
       	else
       	   $where_later = array($later); 
        if($where_later) $where['first_later'] = array('$in' => $where_later);
        
        //查询数据
        $data = $this -> mongo_db -> findAll('wiki_items', $where, array(), array('visit_count' => -1));                                                   
        //过滤重复
        foreach($data as $k => $v){
            if(!isset($data[$v['citiao_id']])){
                $data[$v['citiao_id']] = $v;
            }
            unset($data[$k]);
        }
        
        //赋值
        $this -> assign("webid", $this->web_id);
        //一级分类
        $this -> assign('category_main', $main);
        //二级分类
        $this -> assign('category_secnd', $sec_res);
        //一级分类id
        $this -> assign('mainId', $mainId);
        //二级分类id
        $this -> assign('second_id', $second_name);
        $this -> assign('mainurl', mk_url('wiki/wikit/index', array('web_id' => $this->web_id)));
        //字母
        $this->assign("later_list", $laters);
        //当前字母
        $this->assign("input_later", $later);
        //词条信息
        $this -> assign('citiao', $data);
        //面包屑
        $this -> assignHeaderNav(array("分类列表"));
   
        $this -> assign('seach_url', mk_url("wiki/seach/index", array("web_id" => $this->web_id)));
        $this -> assign('citiao_view_url', mk_url("wiki/citiaoContent/index", array()));

        $this -> display("wiki_category");
    }
}
