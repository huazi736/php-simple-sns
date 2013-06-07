<?php
/**
 *@author wangh
 *@date   2012-07-24
 *@旅游景点频道
 */
 class trip_sort extends MY_Controller {

 	function __construct(){
 		parent::__construct();
 		$this->load->model("trip_sortmodel");
 		$this->load->model('interest_categorymodel','category');
		$this->load->model('apps_infomodel' , 'apps_info');
		$this->assign('uid',$this->uid);
 	}
 	/**
 	 *@景点首页显示
 	 *@author  wangh
 	 *@date    2012-07-25
 	 *@descripton   按城市进行分类
 	 *
 	 */
 	public function index(){
 		$target	= @$this->input->get_post('target');
		$this->assign('target' , $target );

		// 初使化  频道
		$imid = $this->init_channel();

		// 两级分类
		$iname 	= $this->input->get_post('iname');
		//echo $iname;
		$category_org 		= $this->trip_sortmodel->get_category_second();
		foreach ($category_org as $val){
			$category[$val['iname']]	= $val;
		}

		if(!$iname){
			$now_current	= @current($category);
			$iname			= $now_current['iname'];
		}else{
			$now_current	= @$category[$iname];
		}
		$this->assign('iid',$iname);
		$this->assign('now_current',$now_current);
		$this->assign('category_sort' , @$category);

		$child_group= null;
		$eresult	= $this->trip_sortmodel->getProvince_eid($iname);
		 //print_r($eresult);die;
		$child		= @$eresult;
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
		//print_r($child_word);die;
		if(is_array($child_word) ){
			foreach($child_word as $key=>$row){
				//print_r($row['name']);die;
				$cityeid = $this->trip_sortmodel->getCity_eid($row['name']);
				//echo $cityeid;die;
				$child_word[$key]['result_data'] = $this->trip_sortmodel->getTripName_eid($cityeid);
				}
		}
		$this->assign("child_word",$child_word);


		$this->assign('user' , $this->user);

		//$this->assign('page',$this->page);
		$this->display('trip_sort/trip_sort');
	}

 	function alist(){
		$target	= @$this->input->get_post('target');
		$this->assign('target' , $target );

		// 初使化  频道
		$imid = $this->init_channel();

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
		$child_word	= @current($child_group);
		if(is_array($child_word) ){
			foreach($child_word as $key=>$row){
				$child_word[$key]['result_data'] = $four[] =$this->apps_info->get_entry_lemma_page1($row['eid']);
				foreach ($child_word[$key]['result_data'] as $ckey=>$cv){
				$child_word[$key]['result_data'][$ckey]['jindian'] =$this->apps_info->get_entry_lemma_page1($cv['eid']);
				}
				}
		}

		$this->assign("child_word",$child_word);
		$this->assign('user' , $this->user);
		//$this->assign('page',$this->page);
		$this->display('trip_sort/index');
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
		$this->display('web_list');
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
		$this->display('web_list_page');

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

 }
?>
