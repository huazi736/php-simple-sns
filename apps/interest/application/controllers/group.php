<?php
/**
 * 创建群组的第二步动作：让群主选择网页标签作为群组的名称
 * author: hexin
 * date: 2010-06-15
 * 
 */
class Group extends MY_Controller{
	public $page;
	
	function __construct(){
		parent::__construct();
		
		$this->load->library('Redisdb');
		
		$this->load->model('interest_categorymodel','category');
		$this->load->model('apps_infomodel' , 'apps_info');
		
		$this->page = isset($_GET['page']) ? intval($this->input->get('page')) : intval($this->input->post('page'));
		if($this->page<=0) $this->page =1;
		
		$this->assign('uid',$this->uid);
		
	}
	
	function index(){
		// 分词id
		$participle_arr	= config_item('participle');	// 分词
		
		//hexin 2013-06-15
		$participle_arr[0] = '词条汇总';
		
		$this->assign('participle_arr' , $participle_arr);
		$participle_id	= intval($this->input->get('participle_id'));
		if( ! isset($participle_arr[$participle_id]) ){
			$participle_id = current($participle_arr);
		}
		$this->assign('participle_id', $participle_id );
		$this->assign('participle_arr', $participle_arr );
		
		
		$category_big = $this->category->get_category_main();
		$this->assign('category_big' , $category_big );
		$imid	= intval($this->input->get('imid'));	// 大分类名
		if($imid<=0){	// 默认
			$big_arr	= current($category_big);
			$imid		= $big_arr['imid'];
		}
		$this->assign('imid', $imid );
		
		
		
		$redis_group_imid 	= "interest:categoryimid:group:".$imid;
		
		// 两级分类
		$iid 	= intval($this->input->get('iid'));
		$category_org 		= $this->category->get_category($imid);
		foreach ($category_org as $val){
			$category[$val['iid']]	= $val;
		}
		$category_copy	= $category;
		
		// 二级分类  排序  显示		
		$categoryimid_group 	= $this->redisdb->get($redis_group_imid);
		$categoryiid_clicks 	= $this->redisdb->get(explode(',',$categoryimid_group));
		@arsort($categoryiid_clicks);
		if(is_array($categoryiid_clicks)){
			foreach($categoryiid_clicks as $key=>$val){
				if($key!=''){
					$category_id = @substr($key, strrpos($key,':')+1);
					if( @$category[$category_id]){
						$category_sort[] = $category[$category_id];
						unset($category[$category_id]);
					}
					
				}
			}
		}
		if(count($category)>=1){
			foreach($category as $key=>$val){
				$category_sort[]	= $val;
			}
		}
		$this->assign('category_sort' , $category_sort);
		
		
		if($iid<=0){	// 获得二级分类
			$cat_arr 	= current($category_sort);
			$iid 		= $cat_arr['iid'];
			if( ! isset($category_copy[$iid]) ) {
				$cat_arr = current($category_copy);
				$iid 		= $cat_arr['iid'];
			}
		}else{
			/**  点击数 存入到 redis	**/
			$redis_key_iid 	= "interest:categoryiid:clicks:".$iid;
			$clicks			= $this->redisdb->get($redis_key_iid);
			if( (!$clicks) || rand(1,100)<=5 ){	// 魔术插入
				$this->set_redis_group_imid($redis_group_imid,$redis_key_iid);	// 设置建值组
			}
			$this->redisdb->set($redis_key_iid, intval($clicks)+1);
		}
		$this->assign('iid',$iid);
		
		
		
		
		
		
		// 热门
		//$hot_category_web	= $this->apps_info->hot_category_web($imid , $iid);
		//$this->assign('hot_category_web' , $hot_category_web);
		
		// 小分类  网页
		if($participle_id==0){			// 查询 全部
			foreach($participle_arr as $key=>$val){
				if( !($key==0 || $key== 27) ){
					$participle_data_arr[$key]	= $this->apps_info->get_pinyin_weba_page1($val, $imid, $iid );
				}elseif($key==27){
					$participle_data_arr[$key]	= $this->apps_info->get_pinyin_weba_page1_0to9($imid , $iid );
				}
			}
			$this->assign('participle_data_arr' , $participle_data_arr);
			
		}else{	// 单个查询
			 if($participle_id==27){	// 单个查询  查询   0-9
			 	$participle_data_value	= $this->apps_info->get_pinyin_weba_0to9( $imid , $iid , $this->page);
			 }else{
			 	$participle_data_value 	= $this->apps_info->get_pinyin_web( $participle_arr[$participle_id] , $imid , $iid , $this->page );
			 }
			 
			 $continue_load = count($participle_data_value) < $this->apps_info->interest_page_size ? false : true;
			 $this->assign('continue_load' , $continue_load);	// 是否还可以加载
			 $this->assign('participle_data_value' , $participle_data_value);
		}
		
		$this->assign('user' , $this->user);
		
		$this->assign('page',$this->page);
		$this->assign('create_group_step3', mk_url(WEB_ROOT.'single/group/index.php?c=group&m=exist'));
		$this->display('group/index');
	}
	
	// 全部时的加载    top		ajax
	public function get_category_top(){
		$participle_arr	= config_item('participle');	// 分词
		$imid 	= intval( $this->input->get('imid') );
		$iid 	= intval( $this->input->get('iid') );
		$participle_id	= intval($this->input->get('participle_id'));
		if($imid<=0 || $iid<=0 || $participle_id<=0 ){
			echo '';
			die;
		}
		$this->assign('imid' , $imid);
		$this->assign('iid' , $iid);
		$this->assign('participle_id' , $participle_id);
		
		if($participle_id==27){	// 单个查询  查询   0-9
		 	$participle_data_value	= $this->apps_info->get_pinyin_web_top0to9( $imid , $iid , $this->page);
		}else{
		 	$participle_data_value 	= $this->apps_info->get_pinyin_web_top( $participle_arr[$participle_id] , $imid , $iid , $this->page );
		}
		//echo json_encode(array('act'=>'1','msg'=>( (object)$participle_data_value) ));
		$this->assign('item_data' , $participle_data_value);
		
		$continue_load = count($participle_data_value) < $this->apps_info->interest_top_page_size ? false : true;
		$this->assign('continue_load' , $continue_load);	// 是否还可以加载
		$this->assign("page", ($this->page+1) );
		$this->assign('ajax','ajax');
		
		$this->display('group/web_item_box');
		
	}
	
	// 分类时的加载		ajax
	public function get_categorys(){
		$participle_arr	= config_item('participle');	// 分词
		$imid 	= intval( $this->input->get('imid') );
		$iid 	= intval( $this->input->get('iid') );
		$participle_id	= intval($this->input->get('participle_id'));
		if($imid<=0 || $iid<=0 || $participle_id<=0 ){
			echo "";
			die;
		}
		$this->assign('imid' , $imid);
		$this->assign('iid' , $iid);
		$this->assign('participle_id' , $participle_id);
		
		if($participle_id==27){
			$participle_data_value	= $this->apps_info->get_pinyin_weba_0to9( $imid , $iid , $this->page);
		}else{
			$participle_data_value 	= $this->apps_info->get_pinyin_web( $participle_arr[$participle_id] , $imid , $iid , $this->page );
		}
		
		$this->assign( 'item_data', $participle_data_value);
		$continue_load = count($participle_data_value) < $this->apps_info->interest_page_size ? false : true;
		$this->assign('continue_load' , $continue_load);	// 是否还可以加载
		$this->assign('ajax','ajax');
		
		$this->assign("page", ($this->page+1) );
		$this->display('group/web_item_box_min');
		
		//echo json_encode(array('act'=>'1','msg'=>( (object)$participle_data_value) ));
		
	}
	
	/***
	 * 设置建值组
	 * $redis_group_imid   	redis 的key
	 * $redis_key_iid		组里要加的值
	 */
	private function set_redis_group_imid($redis_group_imid , $redis_key_iid){
		$group 	= $this->redisdb->get($redis_group_imid);
		if($group) $array  = array_flip( explode(',',$group) );
		$array[$redis_key_iid]='99999';
		$new_group 	= implode(',',array_keys($array));
		$this->redisdb->set($redis_group_imid,$new_group);
		
	}
}