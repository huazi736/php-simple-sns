<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 网页设置
 * @author zengxiangmo
 * 
 */
class web_setting extends MY_Controller 
{	
    public  $userinfo;
    public function __construct()
    {    	
    	parent::__construct();
    	$this->userinfo = $this->user;
    	$this->assign('user',$this->user);
    }
	/**
	 * 首页	  
	 */
	public function index()
	{	
		$this->settingWeb();
	}
	
	/*
	 * 取得当前用户所创建的网页列表
	 * aid为网页应用的id
	 * name为网页的名字
	 */
	public function settingWeb()
	{
		$web_total = service('Interest')->get_web_user_count($this->uid);
		 $web_total = $web_total ? $web_total :0;
		 $this->assign('web_total',$web_total);
		 $this->assign('login_name',$this->userinfo['username']);
		 $web_lists = $this->getWebDateFirst();
		 $this->assign('web_lists',$web_lists);
	     $this->display("my_weblist/setting_webpage.html");
	}
	
	
	//我的主页第一次加载时调用
	public function getWebDateFirst(){
			return $this->getWebDate(true);
	}
		
	//获取网页数据	
	public function getWebDate($flag=false){
			$uid = $this->uid;
			$web_data = array();
			$web_list_info = array('is_more'=>0);
			
			$page = $this->input->post('page') ? $this->input->post('page') :1;
			$page_size = 30;
			$start = (((int)$page)-1)*$page_size;
			//获得给定用户uid所创建的网页，先获取30条
			 $web_list = service('Interest')->get_webs_page($uid,$start,$page_size);
			 $web_list = json_decode($web_list,true);
			 
			 if(isset($web_list['data']) && count($web_list['data']))
			 {
			 		$total = ceil($web_list['ct'] / $page_size);
			 		if($page<$total){
			 			$web_list_info['is_more']=1;
			 		}
			 		
			 		//取得本次加载网页的所有web_id
					$web_ids = array();
					foreach($web_list['data'] as $v){
						$web_ids[] = $v['aid'];
					}
					
					//取得网页是否删除的结果集
					$is_delete = service('Interest')->get_display_web_info($web_ids);
					$is_delete = json_decode($is_delete,true);
					
			 		foreach ($web_list['data'] as $key => $val)	{
			 			//获取网页图像大小为50x50 (px)
			 			$web_avatar = get_webavatar($val['aid'],'s');
			 			
			 			//$del_ret=1为删除，0为未删除
			 			if(isset($is_delete[$val['aid']])  && ($is_delete[$val['aid']] ) ){
			 				 $del_ret = 1 ;
			 			} else{ 
			 				$del_ret = 0 ;
			 			}
			 			
			 			$fans_num = 0;
			 			$fans_num = number_format($val['fans_count'], 0, '.' ,', ');
			 			//生成web的网页url
			 			$url_link = mk_url('webmain/index/main',array('web_id'=>$val['aid']));
			 			$web_data[] = array('web_name'=>$val['name'], 'web_aid'=>$val['aid'],'fans_count'=>$fans_num,'web_avatar'=>$web_avatar,'is_del'=>$del_ret,'url'=>$url_link);
			 		}
			 		$web_list_info['data']=$web_data;
			 	}
			 	
			 	$web_list_info['page']=$page;
			 	
			 	//判断是否创建过网页 1为创建过，0为未创建过
			 	$web_list_info['web_exists'] = $web_list['ct'] ? 1 :0;
			 	//根据$flag 返回不同的格式
			 	if($flag){
			 		return $web_list_info;
			 	}else{
			 		 $this->ajaxReturn($web_list_info,'',1,'json');
			 	}
		}
		
	/**
	 * 
	 * 点击网页编辑按钮时调用
	 */
	public function  getoption(){
		$web_id = $this->input->get_post('web_id');
		$web_info = service('Interest')->get_web_info($web_id);
		$ret['state']=0;
		if( count($web_info)>0 ){
			//是否显示个人信息到网页资料(0不显示 , 1显示)
			$is_info = $web_info['is_info'];
			unset($web_info);
			if($is_info){
				$ret['state']=1;
			}
		}
		$this->ajaxReturn($ret,'',1,'json');
	}	
		
		
	//编辑网页设置
	public function editWeb(){
		
		$web_id = $this->input->get_post('web_id');
		$uid = $this->uid;
		
		//同步网页创建者信息到网页资料中 1为同步，0不同步
		$is_synname = $this->input->get_post('synname') ? 1 : 0;
		
		//置顶网页1为置顶，0为不置顶
		$is_topweb = $this->input->get_post('topweb') ? 1: 0;
		
		$ret = array('state'=>0,'msg'=>'设置失败');
		$syn_result = 0;
		$topweb_result = 0;
		
		$syn_result= 	$this->_synName($web_id,$is_synname);
		
		if(!$syn_result ){
			 $this->ajaxReturn($ret,'',1,'json');
		}
		
		//判断用户是否选择了网页置顶
		if($is_topweb){
			$topweb_result = 	$this->_topWeb($web_id,$uid);
			if($topweb_result && $syn_result){
				$ret['state'] = 1;
				$ret['msg'] = '设置成功';
			}
		}else{
			$ret['state'] = 1;
			$ret['msg'] = '设置成功';
		}
		 $this->ajaxReturn($ret,'',1,'json');
	}
	
	
	/**
	 * 设置网页是否同步
	 * ajax 
	 * */
	public function sysname(){
		//同步网页创建者信息到网页资料中 1为同步，0不同步
		$is_synname = intval($this->input->get_post('synname'))==1 ? 1 : 0;
		$web_id = $this->input->get_post('web_id');
		
		$syn_result	= $this->_synName($web_id,$is_synname);
		if($syn_result ){
			$arr	= array('state'=>1,'msg'=>'');
		}else{
			$arr	= array('state'=>0,'msg'=>'');
		}
		$this->ajaxReturn($arr,'',1,'jsonp');
	}
	
	/**
	 * 设置网页置顶
	 * ajax
	 * **/
	public function topweb(){
		$web_id = $this->input->get_post('web_id');
		$uid 	= $this->uid;
		$topweb_result = 	$this->_topWeb($web_id,$uid);
		if($topweb_result ){
			$arr	= array('state'=>1,'msg'=>'');
		}else{
			$arr	= array('state'=>0,'msg'=>'');
		}
		$this->ajaxReturn($arr,'',1,'jsonp');
		
	}
	
	public function del_web(){
		$web_id = intval($this->input->get_post('web_id'));
		if($web_id<=0){
			$arr	= array('state'=>0,'msg'=>'');
			die(json_encode($arr));
		}
		$result = 	$this->_delWeb($web_id);
		if($result){
			$arr	= array('state'=>1,'msg'=>'');
		}else{
			$arr	= array('state'=>0,'msg'=>'');
		}
		$this->ajaxReturn($arr,'',1,'jsonp');
		
	}
	
	
	/*
	 * $aid为网页id
	 * $is_info为是否同步创建者姓名到网页资料中0为不同步，1为同步
	 */
	public function _synName($aid=0,$is_info=0){
		if($aid<=0){
			return false;
		}
		
	 	$ret=service('Interest')->web_is_info($aid, $is_info);
		if(!$ret){
			return false;
		}
		return true;
	}
	
	
	//网页置顶
	public function _topWeb($web_id = 0,$uid=0){
		if($web_id<=0 || $uid <=0){
			return false;
		}
		$status =  service('Interest')->web_order($uid , $web_id);
		if($status) {
			$status = 1;
		}else{
			$status = 0;
		} 		
		return $status;
	}
	
	
	/*
	 * 删除网页根据传过来的网页id
	 */
	public function delWeb($aid=0){
		$web_id = $this->input->post('web_id');
		if($web_id<=0){
			return false;
		}
		//返回的状态1为成功，0为失败
		$url=mk_url('interest/web_setting/index');
		$ret = array('state'=>0,'url'=>$url);
		
		$web_info = array();
		//检查传过来的网页是否存在
		$web_info = service('Interest')->get_web_info($web_id);
		if( count($web_info)>0 ){
			
			//网页的名称
			$web_name = $web_info['name'];
			//网页所属的二级分类
			$iid = $web_info['iid'];
			//网页所属的一级分类
			$imid = $web_info['imid'];
			
			unset($web_info);
			$infos = array('uid'=>$this->uid, 'web_id'=>$web_id, 'web_name'=>$web_name, 'iid'=>$iid,'imid'=>$imid);
			$data[0]	 ['call']	= 'del_web';
			$data[0]	 ['data']	= $infos;
			$data = json_encode($data);
			
			//禁用网页     禁用成功返回一个数字，失败返回0
			$ret_web = service('Interest')->display_web($web_id,$data);
			if($ret_web){
				$ret['state']=1;
			}	
			$this->ajaxReturn($ret,'',1,'json');
		}
		$this->ajaxReturn($ret,'',1,'json');
	}
	
	// 删除网页
	public function _delWeb($web_id){
		$web_info = service('Interest')->get_web_info($web_id);
		if( count($web_info)>0 ){
		//网页的名称
			$web_name = $web_info['name'];
			//网页所属的二级分类
			$iid = $web_info['iid'];
			//网页所属的一级分类
			$imid = $web_info['imid'];
			
			unset($web_info);
			$infos = array('uid'=>$this->uid, 'web_id'=>$web_id, 'web_name'=>$web_name, 'iid'=>$iid,'imid'=>$imid);
			$data[0]	 ['call']	= 'del_web';
			$data[0]	 ['data']	= $infos;
			$data = json_encode($data);
			
			//禁用网页     禁用成功返回一个数字，失败返回0
			$ret_web = service('Interest')->display_web($web_id,$data);
			if($ret_web){
				return true;
			}
		}
		
		return false;
	}
	
	
}

