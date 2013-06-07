<?php

/**
 * 发现兴趣  与  目录接口
 * author heyuejuan
 */
class InterestApi extends DkApi {

    protected $interest;
	protected $webs;
    public function __initialize() {
        $this->interest = DKBase::import('Interest', 'interest');
        $this->webs 	= DKBase::import('Webs', 'interest');
    }

    /**
     * 添加分类接口
     * $imid			大类分类名
     * $category_name   类名
     * 
     * 返回    array(act=>'', 'msg'=>'')	act 是否执行成功    		msg 不成功返回错误原因	成功 返回id	 
     * * */
    public function add($imid, $category_name) {
        return $this->interest->add($imid, $category_name);
    }

    /**
     * 记录 分类下  网页记数会自动加1
     * $iid   二级分类id	
     * 
     * * */
    public function web_increase($iid) {
        return $this->interest->web_increase($iid);
    }

    /**
     * 记录  分类下的网页数
     * $iid 	二级分类id
     * $count	网页数
     * * */
    public function web_count($iid, $count) {
        return $this->interest->web_count($iid, $count);
    }

    /**
     * 获得 二级分类 信息
     * $iid  网页 id   或以是 数组 型式    iid=array(0=>iid , 1=>iid , 2=>iid )
     * return  （单维数组或多维数组）
     * * */
    public function get_iid_info($iid) {
        return $this->interest->get_iid_info($iid);
    }

    /**
     * 获得 所有显示的大类
     * $imid 大类id    如果没有传大类id 则返回 指定大类 
     * */
    public function get_category_main($imid=0) {
        return $this->interest->get_category_main($imid);
    }

    /**
     * 获得大分类下的  可以显示的二级分类数据
     * 	
     * * */
    public function get_category_small($imid) {
        return $this->interest->get_category_small($imid);
    }

    /*     * *
     * 获得分类下所有网页的数据	
     * $iid			二级分类
     * $first_char	拼音首字母		如果传空   则取所有
     * $start		数据记录 起始值
     * $limit		数据记录数
     * * */

    public function get_category_web_all($iid, $first_char='', $start=0, $limit=1) {
        return $this->interest->get_category_web_all($iid, $first_char, $start, $limit);
    }

    /**
     * $aid   网页id			可以是单个也可以是 数组   	array(0=>$aid,1=>$aid);
     * 获取网页数据  (不包括获取   二级分类 )
     * ** */
    public function get_web_info($aid) {
        return $this->interest->get_web_info($aid);
    }

    /**
     * 跟据网页名字   模湖查询 
     * $name	
     * * */
    public function get_name_web($name, $start, $limit) {
        return $this->interest->get_name_web($name, $start, $limit);
    }

    /*     * *
     * 传用户uid    
     * 获得用户所有网页数据    (不包括获取  二级分类id)
     * * */

    public function get_webs($uid) {
        return $this->interest->get_webs($uid);
    }

    /**
     * 获得用户网页数据     分页显示
     * $uid		用户id
     * $start	起始值
     * $limit	加载多少条
     * * */
    public function get_webs_page($uid, $start=0, $limit=30) {
        return $this->interest->get_webs_page($uid, $start, $limit);
    }

    /*     * *
     * 获得网页的  分类id
     * $aid 网页id
     * 返回 二级分类的 id   数组
     * * */

    public function get_web_category_id($aid) {
        return $this->interest->get_web_category_id($aid);
    }
	
    /***
     * 获得网页的大分类
     * $aid		网页id
     * 返回 一级分类的 id		数组			这里是因为提供数据给 时间线 。。为了延续以前的模式
     * **/
    public function get_web_category_imid($aid){
    	$aid	= intval($aid);
    	return $this->interest->get_web_category_imid($aid);
    }
    
    
    
    /**
     * 获得网页的   categroup_group 值
     * $web_id		网页id
     * */
    public function get_web_category_group($web_id){
    	$web_id		= intval($web_id);
    	$result		= $this->interest->get_web_category_group($web_id);
    	return @$result[0]['category_group'];
    	
    }
    
    /*     * *
     * 获得网页的  分类id
     * 
     * 返回 二级分类的 id   没有转变的数组  
     * * */

    public function get_web_category_id2($aid) {
        return $this->interest->get_web_category_id2($aid);
    }

    /**
     * 禁用网页
     * $aid		网页id
     * $data 	数据 		数据格式为    arr[0]['call']='fun'  arr[0]['data']='数据'		json 数据
     * * */
    public function display_web($aid, $data) {
        return $this->interest->display_web($aid, $data);
    }

    /**
     * 查询  网页是否在  删除装态
     * $aid_arr		网页id   可以是数组与 网页id 		   数组 array(0=>aid,1=>aid);
     * 
     * 返回    	false or true
     * * */
    public function get_display_web_info($aid_arr) {
        return $this->interest->get_display_web_info($aid_arr);
    }

    /**
     * 排序显示  网页
     * $uid 	用户id
     * $aid    	aid 会设在最前显示
     * * */
    public function web_order($uid, $aid) {
        return $this->interest->web_order($uid, $aid);
    }

    /**
     * 设置  是否显示个人信息到网页资料
     * $aid		网页id
     * $is_info	 0 不显示      1 显示
     * * */
    public function web_is_info($aid, $is_info) {
        return $this->interest->web_is_info($aid, $is_info);
    }

    /**
     * 获得网页数据     (品新新那边获取  用于生成头像)
     * start 	起始值    
     * limit	查多少数据	0 查出所有数据
     * * */
    public function get_webid_all($start=0, $limit=0) {
        return $this->interest->get_webid_all($start, $limit);
    }

    /**
     * 获得用户网页的数量
     * */
    public function get_web_user_count($uid) {
        return $this->interest->get_web_user_count($uid);
    }


    /*     * *    提供给 广告组   接口    start   ** */

    /**
     * imid		传入兴趣 id 数组   (可以是数组  也可以是单个)
     * start    起始值
     * limit	长度
     * 返回   数组 网页id 
     * * */
    public function get_web_info_imid($imid, $start=0, $limit=0) {
        return $this->interest->get_web_info_imid($imid, $start, $limit);
    }
    
    
    /**
     * iid		传入兴趣 id 数组   (可以是数组  也可以是单个)
     * start    起始值
     * limit	长度
     * 返回   数组 网页id 
     * * */
    public function get_web_info_iid($iid, $start=0, $limit=0) {
        return $this->interest->get_web_info_iid($iid, $start, $limit);
    }

    /**
     * 获得  所有大分类与二级分类
     * $start	起始值
     * $limit	取出的数量
     * * */
    public function get_category_all($start=0, $limit=0) {
        return $this->interest->get_category_all($start, $limit);
    }

    /*     * *    提供给 广告组   接口    end   ** */

    /**
     * 插入  二级分类
     * $arr		表 key=>value
     * * */
    public function insert_category($arr) {
        return $this->interest->insert_category($arr);
    }

    // 获得一条一级分类
    public function get_category_main_one($imid) {
        return $this->interest->get_category_main_one($imid);
    }

    // 获得二级分类
    public function get_category_one($imid, $iname) {
        return $this->interest->get_category_one($imid, $iname);
    }

    // 递增  分类的  网页数
    public function increase_category_stat($iid) {
        return $this->interest->increase_category_stat($iid);
    }

    // 50 时 就在  列表里显示
    public function update_50_to_list($iid) {
        return $this->interest->update_50_to_list($iid);
    }

    /*     * *
     * 递增分类数据
     * iid		二级分类id
     * count	网页数
     */

    public function count_category_stat($iid, $count) {
        return $this->interest->count_category_stat($iid, $count);
    }

    // 获得  二级分类的数据
    public function get_iid_info_db($iid) {
        return $this->interest->get_iid_info_db($iid);
    }

    // 批理  获得  二级分类 数据
    public function get_iid_info_arr($iid_arr) {
        return $this->interest->get_iid_info_arr($iid_arr);
    }

    // 获得所有可以显示的大类
    public function get_category_main_all() {
        return $this->interest->get_category_main_all();
    }

    /**
     * aid 获得一条记录   (不包括二级分类id)
     * */
    function get_data_one($aid) {
        return $this->interest->get_data_one($aid);
    }

    /**
     * 获得多维数组  
     * * */
    function get_data_multi($aid_arr) {
        return $this->interest->get_data_multi($aid_arr);
    }

    // 跟据网页名字   模湖查询 
    function get_name_data($name, $start, $limit) {
        return $this->interest->get_name_data($name, $start, $limit);
    }

    /**
     * 传 aid
     * 获得网页  二级分类 id  
     * * */
    function aid_get_iid($aid) {
        return $this->interest->aid_get_iid($aid);
    }
	
    /**
     * 获得  层级分类 的数据
     * $id  	上级分类id      顶级分类时  id 传0	
     * $level 	传本次要查询的级数    顶级传  1    二级传2 ..
     * return 二维数组		例  id=94是二级分类 要查的是三级分类    则传  get_category_level(94,3);
     * */
    public function get_category_level($id , $level=1){
    	return $this->interest->get_category_level($id , $level);
    }
    
    /**
     * 获得   分类id  的数据信息
     * $id 		分类id
     * $level	分类级别
     * **/
    public function get_category_level_name($id,$level=1){
    	return $this->interest->get_category_level_name($id,$level);
    }
    
    /***
     * 通过 组合分类获得品牌
     * $category_group 	分类组合ID	1_78_7205_7198
     * $start			起始位置
     * $limit			取多少个
     * **/
    public function get_category_goods_brand($category_group , $start=0 , $limit=30){
    	$start	= intval($start);
    	$limit	= intval($limit);
    	$category_group	= trim($category_group);
    	return $this->interest->get_category_goods_brand($category_group, $start, $limit);
    }
    
    
    /***
     * 通过最小分类id  获得品牌
     * $min_eid   最小分类id
     * 
     * **/
    public function get_category_brand($min_eid , $start=0 , $limit=30){
    	$start	= intval($start);
    	$limit	= intval($limit);
    	$min_eid	= trim($min_eid);
    	return $this->interest->get_category_brand($min_eid, $start, $limit);
    }
    
    
    /**
     * 根据 分类id 获得   层级分类的组合id		(暂时只提供购物 的组合id)
     * $eid   	分类id
     * $level 	级别
     * 返回     分类的组合 		如   1_2_3002_4985
     *  例: service('Interest')->get_category_group(40871 , 4);   返回 9_156_25061_40871
     * **/
    public function get_category_group($eid , $level){
    	$eid	= intval($eid);
    	$level	= intval($level);
    	return $this->interest->get_category_group($eid , $level);
    
    }
    
    
    /**
     * 添加网页的事务
     * $aid 	网页id
     * * */
    public function add_del_web_event($aid, $data) {
        return $this->interest->add_del_web_event($aid, $data);
    }

    public function get_del_web_event($aid_arr) {
        return $this->interest->get_del_web_event($aid_arr);
    }
	/**
	 *获取网页封面
	 *$web_id
	 *lvxinxin add 2012-07-18
	 */
	 public function get_web_cover($web_id){
		if(empty($web_id)) return false;
		return $this->interest->get_web_cover($web_id);
	 }
	 
	 /**
	  * 获得所有同名的网页
	  * $name	网页名
	  * return array
	  * */
	 public function get_web_homonymy_name($name){
		$name 	= trim($name);
		return $this->webs->get_web_homonymy_name($name);
	 }
	 
	 /***
	  * 获得所有同名的网页
	  * $id		网页id
	  * retyrn array
	  * **/
	 public function get_web_homonymy_id($id){
	 	$id		= intval($id);
	 	$reslut	= $this->get_web_info($id);
	 	return $this->get_web_homonymy_name($reslut['name']);
	 }
	 
	 
}
