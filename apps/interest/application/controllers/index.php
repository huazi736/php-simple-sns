<?php
/**
 * 发现兴趣
 * 
 * **/


class Index extends MY_Controller{
	public $page;
	
	
	function __construct(){
		parent::__construct();
		
		$this->redisdb 	= get_redis('user');
		
		$this->load->model('interest_categorymodel','category');
		$this->load->model('apps_infomodel' , 'apps_info');
		
		$this->page = isset($_GET['page']) ? intval($this->input->get('page')) : intval($this->input->post('page'));
		if($this->page<=0) $this->page =1;
		
		$this->assign('uid',$this->uid);
		

	}
	
	function index(){
		// 主分类
		
		// 插入数据
		/**
		$this->category->insert_category_main($arr=array());
		die;
		*/
		/**
		// 更新数据
		echo $this->category->update_category_main(1,$arr=array());
		die;
		**/
		/**
		// 删除
		echo $this->category->delete_category_main(17);
		die;
		**/
		
		
		// 二级分类
		/*
		// 插入
		$this->category->insert_category($arr=array());
		die;
		*/
		
		/*
		$this->load->library('image');
		echo $this->image->crop_ratio('12.jpg','15.jpg',500,500);
		die;
		*/
		$this->alist();
	}
	
	
	function alist(){
		$target	= @$this->input->get_post('target');
		$this->assign('target' , $target );
		

		$category_big = $this->category->get_category_main();
        
     
		$this->assign('category_big' , $category_big );
		$imid	= intval($this->input->get('imid'));	// 大分类名
		if($imid<=0){	// 默认
			$big_arr	= current($category_big);
			$imid		= $big_arr['imid'];
		}
		// 初使化  频道
		$imid = $this->init_channel();
		$this->assign('imid', $imid );

		
		// 两级分类
		$iid 	= intval($this->input->get_post('iid'));
		$category_org 		= $this->category->get_category($imid);
        
		foreach ($category_org as $val){
			$category[$val['iid']]	= $val;
		}
		
		if($iid<=0){
			$now_current	= @current($category);
			$iid			= $now_current['iid'];
		}else{
			$now_current	= @$category[$iid];
		}
		$this->assign('iid',$iid);
        
		$this->assign('now_current',$now_current);
		$this->assign('category_sort' , @$category);

		//将摄影分开处理
		if($imid != 11){       
			$child_group= null;
			$eresult	= $this->apps_info->get_entry_group($iid);
	        
			$child		= @$eresult[0];
			if(is_array($child) && @$child['piid_type']!=-1){
				for($i=0;$i<5;$i++){
					$child	= @$eresult[0];
					if(isset($child) && @$child['piid_type']!=2){
						$eresult2	= $this->apps_info->get_entry_group_eid($child['eid'] , 1);
						$child_group[] = $eresult;
						$eresult	= $this->apps_info->get_entry_group_eid($child['eid'] );
					}else{
						break;
					}
				}
			}
			
			$child_word	= @array_pop($child_group);
			if(is_array($child_word) ){
				foreach($child_word as $key=>$row){
					$child_word[$key]['result_data'] = $this->apps_info->get_entry_lemma_page1($row['eid']);
				}
			}
	
			$this->assign("child_word",$child_word);
			
			$this->assign('user' , $this->user);
			
			$this->assign('page',$this->page);
	        
			$this->display('index');
		}else{
			$char = $this->input->get_post('char');
			
			//是否创建页面
			$is_create = $this->input->get_post('is_create');
			$is_create = $is_create ? 1 : 0;
			/*限制第一个字母*/
			$limit_arr = array('a','b','c','d','e','f','g','h','i','j','k','l','m','n','o','p','q',
					'r','s','t','u','v','w','x','y','z');
			
			if(!in_array($char,$limit_arr)){
				$char = 'a';
			}
			$result = $this->apps_info->get_shoot_elements($imid,$iid,$char);
			
			/*处理ajax请求*/
			if($this->isAjax()){
				$arr = array();
				foreach($result as $k=>$v){
					$arr_key = ($k*50+1).'-'.($k+1)*50;
					foreach($v as $v1){
						if($is_create){
							//链接到创建页面
							$url = mk_url('webmain/create/found',array('iid'=>$iid,'imid'=>$imid,'eid'=>$v1['eid']));
						}else{
							$url = mk_url('interest/index/web_list',array('web_id'=>$v1['eid'],'target'=>$target,'pid'=>$v1['eid']));
						}
						$arr[$arr_key][] = array('name'=>$v1['name'],
											'url'=>$url);
					}
				}
				
				$this->ajaxReturn($arr);
			}
			
			$this->assign('char',$char);
			$this->assign("result_item",$result);
			$this->assign('user',$this->user);
			$this->assign('page',$this->page);
			$this->assign('is_create',$is_create);
			$this->display('photo/photo_index');
		}
	}
	
	
	// 列表
	public function channel_list(){
		
		
		$this->assign('user',$this->user);
		$this->assign('uid',$this->uid);
		$this->display('channel_list');
	}
	
	
	// 全部时的加载    top		ajax
	public function get_category_top(){
		$imid 	= intval( $this->input->get('imid') );
		$iid 	= intval( $this->input->get('iid') );
		$eid	= intval($this->input->get('eid'));
		if($imid<=0 || $iid<=0 || $eid<=0 ){
			echo '';
			die;
		}
		$this->assign('imid' , $imid);
		$this->assign('iid' , $iid);
		$this->assign('participle_id' , $eid);
		
		$eid_data_value	= $this->apps_info->get_entry_lemma_tow($eid , $this->page );
		//echo json_encode(array('act'=>'1','msg'=>( (object)$participle_data_value) ));
		$this->assign('item_data' , $eid_data_value);
		
		$continue_load = count($eid_data_value) < $this->apps_info->interest_top_page_size ? false : true;
		$this->assign('continue_load' , $continue_load);	// 是否还可以加载
		$this->assign("page", ($this->page+1) );
		$this->assign('ajax','ajax');
		
		$this->display('web_item_box');
		
	}
	
	
	
	// 分类时的加载		ajax
	public function get_categorys(){
/*
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
			$participle_data_value	= $this->apps_info->get_pinyin_entry_weba_0to9( $imid , $iid , $this->page);
		}else{
			$participle_data_value 	= $this->apps_info->get_pinyin_entry_web( $participle_arr[$participle_id] , $imid , $iid , $this->page );
		}
		
		$this->assign( 'item_data', $participle_data_value);
		$continue_load = count($participle_data_value) < $this->apps_info->interest_page_size ? false : true;
		$this->assign('continue_load' , $continue_load);	// 是否还可以加载
		$this->assign('ajax','ajax');
		
		$this->assign("page", ($this->page+1) );
		$this->display('web_item_box_min');
	*/	
		//echo json_encode(array('act'=>'1','msg'=>( (object)$participle_data_value) ));
		
	}
	
	
	/**
	 * 网页列表
	 * **/
	public function web_list(){
		$eid	= intval($this->input->get('web_id'));	// 三四级分类
		$iid	= intval($this->input->get('iid'));
        $pid = intval($this ->input->get("pid")); //读 当前分类的 父分类   LYD

        if(!empty($pid)) {
            $pid_name =$this ->apps_info-> get_category_entry_web($pid);
            $this ->assign("pid_name",$pid_name["name"]);
        }

		$imid	= $this->init_channel();

		$target	= @$this->input->get('target');
		$this->assign('target' , $target );
		
		if($eid>0){
			$result	= $this->apps_info->get_category_entry_web($eid);
			if(! isset($result['eid'])){
				$this->redirect( mk_url('main/index/main') );
				die;
			}
			$this->assign('list_name',$result['name']);
			$imid	= $result['imid'];
			$iid	= $result['iid'];
		}else if( $iid >0 ){
			$result	= $this->category->get_category_iid_one($iid);
			$imid	= $result['imid'];
			$this->assign('list_name',$result['iname']);
		}else{
			$result	= $this->category->get_category_imid_one($iid);
			$this->assign('list_name',$result['imname']);
		}
		$this->assign('eid',$eid);
		$this->assign('iid',$iid);
		$this->assign('imid',$imid);

		$imid_result	= $this->category->get_category_imid_one($imid);
		// 二级分类
		$category_org 		= $this->category->get_category($imid);
		foreach ($category_org as $val){
			$category[$val['iid']]	= $val;
		}
		$this->assign('category_sort' , @$category);
		
        
		if($imid_result['is_local']==1){	// 按区域查
			$area	= $this->user['cityid'];
		}else{
			$area	= -1;
		}
		
		if($eid>0){
			$result_list	= $this->apps_info->get_apps_info_eid($eid , $this->page , $area);
		}else if($iid>0){
			$result_list	= $this->apps_info->get_apps_info_iid($iid , $this->page , $area);
		}else{
			$result_list	= $this->apps_info->get_apps_info_imid($imid , $this->page, $area);
		}

		$this->assign('result_list',$result_list);
		if(!is_array($result_list)){	// 没有查到数据
			$this->assign('not_result_list','true');
		}else{
			$this->assign('not_result_list','false');
		}
		
		
		$continue_load = count($result_list) < $this->apps_info->interest_list_size ? false : true;
		$this->assign('continue_load' , $continue_load);	// 是否还可以加载
		$this->assign('page',($this->page+1));
		$this->assign('user' , $this->user);

		//$this->display('web_list');
        
        
        
             
        
        /*
         * @通过不同的$imid 来渲染 不同的频道模板;
         * @LYD
         * */
        switch ($imid) {
            case 12:  $this ->display("games/games");break;
            
            default:
                $this->display('web_list');
                break;
        }
        
        
        
        
        
        
        
        
	}
	
	
	/***
	 * 网页列表
	 * ajax 加载
	 * **/
	public function get_web_list(){
		$eid	= intval($this->input->get('web_id'));	// 三四级分类
		$iid	= intval($this->input->get('iid'));
		$imid	= intval($this->input->get('imid'));
        
      
		
		$target	= @$this->input->get('target');
		$this->assign('target' , $target );
		
		if($eid>0){
			$result	= $this->apps_info->get_category_entry_web($eid);
			
			if(! isset($result['eid'])){
				$this->redirect( mk_url('main/index/main') );
				die;
			}
			$this->assign('list_name',$result['name']);
			$imid	= $result['imid'];
			$iid	= $result['iid'];
		}else if( $iid >0 ){
			$result	= $this->category->get_category_iid_one($iid);
			$imid	= $result['imid'];
			$this->assign('list_name',$result['iname']);
		}else{
			$result	= $this->category->get_category_imid_one($iid);
			$this->assign('list_name',$result['imname']);
		}
		$this->assign('eid',$eid);
		$this->assign('iid',$iid);
		$this->assign('imid',$imid);
		if($eid>0){
			$result_list	= $this->apps_info->get_apps_info_eid($eid , $this->page );
		}else if($iid>0){
			$result_list	= $this->apps_info->get_apps_info_iid($iid , $this->page );
		}else{
			$result_list	= $this->apps_info->get_apps_info_imid($imid , $this->page );
		}
		$this->assign('result_list',$result_list);
		if(!is_array($result_list)){	// 没有查到数据
			$this->assign('not_result_list','true');
		}else{
			$this->assign('not_result_list','false');
		}
		
		$continue_load = count($result_list) < $this->apps_info->interest_list_size ? false : true;
		$this->assign('continue_load' , $continue_load);	// 是否还可以加载
		$this->assign('result_list',$result_list);
		$this->assign('page',($this->page+1));
		
		/*
         * @$imid 来渲染 游戏的频道模板;
         * @guojianhua
         * */
		
		if ($imid == 12) {
			$this->display('games/web_list_page');
		} else {
			$this->display('web_list_page');
		}
		
		
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
	public function module(){
		$mmid	= intval( $this->input->get('mmid') );
		
		
		
		$this->assign('user' , $this->user);
		
		
		$this->display('module_index');
		
	}
	
	// 生成 分类 拼音
	public function create_category_pinyin(){
		$this->category->create_pinyin();
		echo 1;
	}
	
	
	// 生成   词条的拼音
	public function create_entry_pinyin(){
		$this->category->create_entry_pinyin();
		echo 1;
	}
	
	public function create_category_tag(){
		$this->category->create_category_tag();
		echo 1;
	}
	
	
	// 测式
	public function test(){
		$tt	= service('Interest')->get_web_homonymy_name('西安2');
		print_r($tt);
	}
	
	
	
	
	
}

