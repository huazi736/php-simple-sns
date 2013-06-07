<?php
/**
 * 创建网页
 * 
 * **/

class Create extends MY_Controller{
	var $uid;
	var $number_escape	= array( 1=>'一', 2=>'二', 3=>'三', 4=>'四', 5=>'五', 6=>'六', 7=>'七', 8=>'八', 9=>'九', 10=>'十');	// 数字转义
	
	function __construct(){
		parent::__construct();
		$this->config->load('webmain');
		$this->mycache	= get_memcache('user');
		$this->uid	= $this->getLoginUID();
		
		//$this->load->library('Redisdb');
		
		$this->load->model('interestsmodel','category');
		$this->load->model('apps_infomodel','apps_info');
		
		$this->load->library('pinyin');
		
		$this->assign( 'username' , $this->username);
		$this->assign( 'uid' , $this->uid);
		$this->assign( 'user' , $this->user);
		
		$this->assign( 'number_escape', $this->number_escape );
		
	}
	
	public function index(){
		$this->found();
	}
	
	
	/**
	 * 创建时  选频道
	 * 
	 * **/
	public function channel(){
		// 大类
		$category_main 	= $this->category->get_category_level_one();
		$this->assign('category_main' , $category_main );
		$this->display('create_channel');
	}
	
	
	
	
	/**
	 * 创建网页
	 * **/
	public function found(){
		$category		= null;
		
		// 大类	(历吏问题)
		$category_main 	= $this->category->get_category_level_one();
		$this->assign('category_main' , $category_main );
		
		
		$imid	= intval($this->input->get_post('imid'));
		$iid	= intval($this->input->get_post('iid'));
		if($imid<=0){	
			// 一级分类  id
			$arr	= current($category_main);
			$category_main_select_id	= $arr['id'];
		}else{
			$category_main_select_id	= $imid;
		}
		$this->assign('category_main_select_id' , $category_main_select_id );
		
		// 获得大分类
		$category_main_result	= $this->category->get_category_main_one($category_main_select_id);
		$this->assign('category_main_result' , $category_main_result );
		
		if($category_main_result['is_local']==1 && $this->user['cityid']<=0){	// 住址不完善
			$this->redirect( 'webmain/create/channel' );
		}
		
		// 二级
		$category_two	= $this->category->get_category_level_two($category_main_select_id);
		$category[2]['data']= $category_two;
		// 二级分类  id
		$arr	= current($category_two);
		$category[2]['id']		= $arr['id'];
		$eid = $this->input->get_post('eid');
		$this->assign('imid',$category_main_select_id);
		$this->assign('iid',$iid);
		$this->assign('eid',$eid);
		if($category_main_select_id == 11 ){
			if($eid > 0 && $iid > 0){
				//摄影
				$this->assign('imidName',$category_main_result['ename']);
				$iidInfo = $this->category->get_category_level_name_two($iid);
				if(empty($iidInfo)){
					show_error('参数有误');
				}
				$this->assign('iidName',$iidInfo['name']);
				
				$eidInfo = $this->category->get_category_level_name_more($eid,3);
				//var_dump($eidInfo);exit;
				$this->assign('entry_info',$eidInfo);
			}else{
				$this->redirect( 'interest/index/alist?imid=11&is_create=1' );
				exit;
			}
		}
		$this->assign('category' , $category );
		$this->display('createweb');
	}
	
	
	/***
	 * 直接创建		不选任何分类
	 * **/
	public function simple_found(){
		
		
		$this->display('createweb_simple');
	}
	
	
	
	/***
	 * ajax 加载
	 * 获得分类数据
	 * $id  分类id		上传分类id
	 * $level 值				// $id , $level		1 是频道id
	 * **/
	public function get_category(){
		$id		= intval( $this->input->get_post('id') );
		$level	= intval( $this->input->get_post('level') );
		if($level<=0)	$level=1;
		$category 		= null;
		
		if( $level==1 ){
			$result 	= $this->category->get_category_level_one();
		}else if( $level==2 ){
			$result 	= $this->category->get_category_level_two($id);
		}else if( $level==3 ){
			$result 	= $this->category->get_category_level_three($id);
		}else{
			$result 	= $this->category->get_category_level_more($id , $level);
		}
		
		if($level==2 && $this->user['cityid']<=0 && $id>0){
			$category_result	= $this->category->get_category_level_name_one($id);
			if($category_result['is_local']==1 && $this->user['cityid']<=0){	//住址不全  不显示下级分类
				echo "";
				die;
			}
		}
		
		if( ($result && is_array($result)) || ($level==2 && $id==0) ){
			if($id==0){	$result='';	}	// 
			$category[$level]['data'] 	= $result;
			$category[$level]['id']		= 0;
			$this->assign('category' , $category );
			$this->display('creatweb_category');
		}else{	// 没有查询到数据
			if( $level>=3 ){
				/*
				$category_result	= $this->category->get_category_level_name_more($id,$level);
				if($category_result['piid_type']){
					
				}
				*/
				echo ""; die;
			}
			$category[$level]['data'] 	= '';
			$category[$level]['id']		= 0;
			$this->assign('category' , $category );
			$this->display('creatweb_category');
		}
	}
	
	
	
	
	
	
	
	
	
	
	
	
	public function cpost(){
		/*
Array
(
    [pagename] => dfgsdfgsdfg
    [select_category] => Array
        (
            [1] => 1
            [2] => 86
            [3] => 5113
            [4] => 6216
        )

    [__hash__] => bb29fd712ca6a5c989d51053824021a3
)
		 * */
		
		$aname 	= addslashes_deep( trim($this->input->post('pagename')) );
		if($aname==''){
			//echo '请输入网页名';
			$this->redirect( 'webmain/create/channel' );
			die;
		}
		if(mb_strlen($aname)>15*3 && mb_strlen($aname,'utf-8')>15*2 ){
			// echo '网页名不能超过15个字';
			$this->redirect( 'webmain/create/channel' );
			die;
		}
		$select_category	= $this->input->post('select_category');
		if(! isset($select_category[1]) ){
			// 没有传频道
			$this->redirect( 'webmain/create/channel');
			die;
		}
		
		asort($select_category);
		$category_group 	= "";
		$last_eid			= 0; 
		$category_arr_name	= null;
		foreach($select_category as $key=>$val){
			$val	= intval($val);
			if( $val>0) {
				if($key>2){		// eid 必须大于 2级分类
					$last_eid	= $val;
				}
				$category_group .= $val."_";
				$result	= service('Interest')->get_category_level_name($val , $key);
				$category_arr_name[]	= @$result['name'];
			}
		}
		$category_group	= substr($category_group, 0 , -1);
		
		if($this->user['cityid']<=0 && $select_category[1]>0){	// 判断住址是否完全
			$category_result	= $this->category->get_category_level_name_one($select_category[1]);
			if($category_result['is_local']==1){	//住址不全  不显示下级分类
				$this->redirect( 'webmain/create/channel');
				die;
			}
		}
		//var_dump($select_category);
		if(intval($select_category[1])>0 && intval($select_category[2])==0){	// 两级分类必选 
			$this->redirect( 'webmain/create/channel');
			die;
		}
		
		
		$insert['uid']		= $this->uid;
		$insert['dkcode']	= intval($this->dkcode);
		$insert['name']		= $aname;
		$insert['imid']		= intval(@$select_category[1]);
		$insert['iid']		= intval(@$select_category[2]);
		$insert['display']	= 1;
		$insert['name_pinyin']	= $this->pinyin->convert($aname);
		$insert['caput_pinyin']	= strtolower( substr($insert['name_pinyin'], 0 ,1 ) );
		$insert['eid']			= intval($last_eid);
		$insert['category_group']	= $category_group;
		$insert['town']			= $this->user['cityid'];
		
		/*
		// 区域
		$insert['nation']	= intval($this->input->post('now_nation'));
		$insert['province']	= intval($this->input->post('now_province'));
		$insert['city']		= intval($this->input->post('now_city'));
		$insert['town']		= intval($this->input->post('now_town'));
		$insert['nation']<0 && $insert['nation'] = 0;
		$insert['province']<0 && $insert['province'] = 0;
		$insert['city']<0 && $insert['city'] = 0;
		$insert['town']<0 && $insert['town'] = 0;
		*/
		
		
		$app_id	= $this->apps_info->add_apps($insert);
		if(!$app_id){
			echo "插入不成功";
			die;
		}
		
		$this->apps_info->add_apps_category($app_id,$insert['imid'],$insert['iid']);	// 插入网页分类
		if($last_eid>0){
			$this->apps_info->update_entry_fans_count($this->uid ,$app_id , $last_eid);		// 更新数据   下级分类表
			$this->apps_info->add_entry_web_info($last_eid);  //数据 记录到  下级分类表里
		}
		
		// 删除前端的缓存
		$mem_key	= "web_header_show_uid_".$this->uid;
		$this->mycache->delete($mem_key);
		
		// 记录网页数
		if(rand(1,100)<=5){
			$count	= $this->apps_info->get_iid_count($insert['iid']);
			$this->web_count($insert['iid'],$count);
		}else{
			$this->web_increase($insert['iid']);
		}
		
		
		/***   数据给一份  给搜索    ***/
		$app_arr = $this->apps_info->get_web_info($app_id);
		$arr['web_id']	= $app_arr['aid'];
		$arr['uid']		= $app_arr['uid'];
		$arr['fans_count']	= $app_arr['fans_count'];
		$arr['create_date']	= $app_arr['create_time'];
		$arr['name']		= $app_arr['name'];
		$arr['imname']		= @$category_arr_name[0];
		$arr['iname']		= @$category_arr_name[1];
		$arr['ename']		= @$category_arr_name[2];
		$arr['ename2']		= @$category_arr_name[3];
		service('RelationIndexSearch')->addOrUpdateWebpageinfo($arr);
		
		/***	积分接口 	 创建网页加分		***/
		service('credit')->web();
		
		//echo WEB_ROOT.APP_URL.'/index.php?c=avatar&m=avatar_init&web_id='.$app_id;
		//die;
		// redirect(WEB_ROOT.APP_URL.'/index.php?web_id='.$app_id);
		$this->redirect('webmain/avatar/avatar_init',array('aid'=>$app_id));
		
		die;
		
	}
	
	
	
	
	
	
	
	
	
	
	
	
	
	/**
	 * 获得分类被添加的次数
	 * ajax 调用
	 * **/ 
	public function get_category_stat(){
		
		$category_name	= trim($_REQUEST['cname']);
		if($category_name==''){
			echo json_encode( array('status'=>'0','msg'=>'分类名不能为空') );
			die;
		}
		$imid	= intval($this->input->get_post('imid'));
		if($imid<1){
			echo json_encode( array('status'=>'0','msg'=>'传参不正确') );
			die;
		}
		
		$result	= $this->apps_info->get_category_stat($imid , $category_name);
		
		if(is_array($result) && isset($result['stat']) ){
			$count	= $result['stat'];
		}else{
			$count	= 0;
		}
		echo json_encode( array('status'=>'1','msg'=>'','data'=>$count) );
		die;
	}
	
	
	
	/**
	 * ajax  获得  分类下的  标签
	 * */
	public function get_category_tag(){
		$imid	= intval($this->input->get_post('imid'));
		$iid	= intval($this->input->get_post('iid'));
		
		$result 	= $this->category->category_tag_iid($imid , $iid );
		echo json_encode( array('status'=>'1','msg'=>'','data'=>$result) );
		die;
	}
	
	
	
	
	
	
	
	
	
	
	
	
	
	
    /**
     * 分类下  网页记数会自动加1
     * $iid   二级分类id	
     * 
     * **/
    private function web_increase($iid){
    	$iid 	= intval($iid);
    	$id	= $this->category->increase_category_stat($iid);
    	$this->category->update_50_to_list($iid);
    	return $id;
    }
	
    
    /**
     * 分类下的网页数
     * $iid 	二级分类id
     * $count	网页数
     * **/
    public function web_count($iid,$count){
    	$iid 	= intval($iid);
    	$count 	= intval($count);
    	$id	=  $this->category->count_category_stat($iid , $count);
    	
    	$this->category->update_50_to_list($iid);
    	return $id;
    }
    
    
    public function page2(){
    	$this->display('creatpage2');
    }
    public function page3(){
    	$this->display('creatpage3');
    }
    
    
    
    
    public function add_fellow(){
    	$aid	= intval($this->input->get_post('aid'));
    	$limit	= 30;
    	
    	// 获得粉丝的 uid
    	//$fans_count 	= intval(service('Relation')->getNumOfFollowers($this->uid));
    	$fans_count 	= intval(service('Relation')->getNumOfFriends($this->uid, true));
    	$fans_start		= rand(0,$fans_count-$limit-1);
    	if($fans_start<=0)	$fans_start = 0;
    	
    	//$result			= service('Relation')->getFollowersWithInfo($this->uid, $fans_start , $limit);
    	$result			= service('Relation')->getFriendsWithInfoByOffset($this->uid, true, $fans_start , $limit);
    	$result_fans	= null;
    	$del_uid_arr		= null;
    	$del_uid_arr[]	= $this->uid;
    	
    	foreach($result as $key=>$arr){
    		$key_arr['uid']= $arr['id'];
    		$key_arr['username']= $arr['name'];
    		$key_arr['dkcode']	= $arr['dkcode'];
    		$key_arr['sex']		= $arr['sex'];
    		$key_arr['email']	= '';
    		$del_uid_arr[]		= $arr['id'];
    		$result_fans[]		= $key_arr;
    	}
    	$limit = $limit - count($result);
    	
    	/*
    	// 好友不够就   去用户名补全。
    	if(count($result)<30){
	    	$user_count	= intval( service('Passport')->get_user_counts() );
	    	$start	= rand(0,$user_count-$limit-1);
	    	if($start<=0) $start = 0;
	    	$result	= service('Passport')->get_user_info( $del_uid_arr ,$start,$limit);
	    	
	    	$result_fans	= array_merge($result_fans , $result);
    	}
    	*/
    	
    	$this->assign( 'result' , $result_fans);
    	$this->assign( 'aid' , $aid);
    	$this->display('creat_add_fellow');
    }
    
    
    public function add_post_fellow(){
    	$aid	= intval($this->input->get_post('aid'));
    	$send_uid	= ($this->input->get_post('send_uid'));
    	
    	$web_info	= $this->apps_info->get_web_info($aid);
    	if($web_info['uid']!=$this->uid){
    		$this->redirect('main/index/main' );
    		die;
    	}
    	
    	
    	if(is_array($send_uid)){
	    	foreach($send_uid as $key=>$val){
	    		//$rel = service('notice')->add_notice('272',1000002105,1000002106,'web','dk_creat_web',array('name'=>'留言内容留言内容留言内容留言内容留言内容留言内容留言内容留言内容','url'=>'http://www.baidu.com'));
	    		// call_soap('ucenter','Notice','add_notice',array($aid,$this->uid, intval($val) , 'web' ,'dk_creat_web' ,array('name'=>$web_info['name'],'url'=>WEB_ROOT.APP_URL.'/index.php?web_id='.$aid) ));
	    		service('notice')->add_notice($aid,$this->uid, intval($val) , 'web' ,'dk_creat_web' ,array('name'=>$web_info['name'],'url'=> mk_url('webmain/index/main',array('web_id'=>$aid)) ));
	    		
	    	}
    	}
    	$this->apps_info->set_web_enable($this->uid , $aid );
    	
    	$this->redirect( 'webmain/index/main',array('web_id'=>$aid)  );
		die;
    	
    }
    
    
    
    
    
    
    
}