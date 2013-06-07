<?php

/**
 * Index controller
 * @author shedequan
 */
class Goods extends MY_Controller {
    var $fastdfs_url	= ""; 
	
	function __construct(){
		parent::__construct();
		
		//$this->redisdb 	= get_redis('user');
		$this->load->model('goodsmodel','goods');
		$this->goodsmodel	= $this->goods;
		
		
		$this->page = $this->input->get_post('page');
		if($this->page<=0) $this->page =1;
		// $this->assign('user' , $this->user);
		$this->assign('uid',$this->uid);
		$this->assign('dkcode',$this->dkcode);
		
		
		$fastdfs_domain	= config_item('fastdfs_domain');
		$this->fastdfs_url		= 'http://'.$fastdfs_domain.'/';
		$this->assign('fastdfs_domain' , $this->fastdfs_url);
		
		$this->assign('web_id' , $this->web_id );
		$this->assign('is_self' , $this->is_self );
		
	}
	
	
	
	// 添加商品
	public function add_goods(){
		if(!$this->is_self){
			$this->redirect( 'main/index/main' );
			die;
		}
		
		$this->assign('web_info', $this->web_info);
		$this->assign('user',$this->user);
		$this->assign('type','add_goods');
		$this->assign('goods_info','');
		
		$this->display('goods_add');
	}
	
	
	// 修改商品
	public function edit_goods(){
		if(!$this->is_self){
			$this->redirect( 'main/index/main' );
			die;
		}
		
		$gid 	= intval( $this->input->get_post('gid') );
		$this->assign('type','edit_goods');

		
		$goods_info	= $this->goods->get_goods_one($gid);
		if(!is_array($goods_info)){
			$this->redirect( 'webmain/index/main',array('web_id'=>$this->web_id) );
			die;
		}
		$goods_info['pics_arr']	= json_decode($goods_info['pics'],true);
		$iid_arr	= explode('_',$goods_info['iid']);
		$goods_info['catid']	= array_pop($iid_arr);
		if($goods_info['main_pics']){
			$main_pics_arr		= json_decode($goods_info['main_pics'],true);
			$main_pics_key		= key($main_pics_arr);
		}else{
			$main_pics_key		= $goods_info['pics_arr'];
		}
		($main_pics_key<1 || $main_pics_key>4) && $main_pics_key=1;
		$goods_info['main_pics_key']	= $main_pics_key;
		
		/*
		echo "<pre>";
		print_r($goods_info);
		die;
		*/
		$this->assign('goods_info',$goods_info);
		$this->assign('gid',$gid);
		
		
		$this->assign('web_info', $this->web_info);
		$this->assign('user',$this->user);
		$this->display('goods_add');
		
	}
	
	function alist(){
		
		$goods_result	= $this->goods->get_goods_info( $this->web_id, $this->page);
		
		foreach($goods_result as $key=>$arr){
			$goods_result[$key]['pics_m'] = "";
			$pics_m	= "";
			if($arr['main_pics']){
				$de_arr		= @json_decode($arr['main_pics'],true);
				$pics_m		= @current($de_arr);
			}else{
				if($arr['pics']){
					 $de_arr	= @json_decode($arr['pics'],true);
					 $pics_m	= @current($de_arr);
				}
			}
			
			if(is_array($pics_m)){
				$goods_result[$key]['pics_m']	= $pics_m['groupname'].'/'.$pics_m['filename'].'_s.'.$pics_m['type'];
				$goods_result[$key]['pics_sh']	= $pics_m['photosizes']['s']['h'];
			}
			
			// $arr['id']	= 135;
			// 评论
			$comment_arr	= @service('Comlike')->getRecommendData($arr['id'],'goods', $arr['uid'], array(0=>$arr['id']), $this->uid, $this->web_id);
			$goods_result[$key]['comment']	= @$comment_arr[$arr['id']];
			
		}
		
		// 是否己经到最后。	0不是  1 是
		$is_end	= count($goods_result) >= $this->goods->goods_page_size ? 0 : 1;
		
		$this->assign("goods_result" , $goods_result );
		$this->assign("is_end" , $is_end );
		
		$this->assign('web_info', $this->web_info);
		$this->assign('page', ($this->page+1) );
		$this->display('goods_list');
	}
	
	
	/**
	 * 获得 alist数据
	 * ajax 
	 * **/
	public function get_list(){
		$goods_result	= $this->goods->get_goods_info( $this->web_id, $this->page);
		
		foreach($goods_result as $key=>$arr){
			$goods_result[$key]['pics_m'] = "";
			$pics_m	= "";
			if($arr['main_pics']){
				$de_arr		= @json_decode($arr['main_pics'],true);
				$pics_m		= @current($de_arr);
			}else{
				if($arr['pics']){
					 $de_arr	= @json_decode($arr['pics'],true);
					 $pics_m	= @current($de_arr);
				}
			}
			
			if(is_array($pics_m)){
				$goods_result[$key]['pics_m']	= $pics_m['groupname'].'/'.$pics_m['filename'].'_s.'.$pics_m['type'];
				$goods_result[$key]['pics_sh']	= $pics_m['photosizes']['s']['h'];
			}
			
			// $arr['id']	= 135;
			// 评论
			$comment_arr	= @service('Comlike')->getRecommendData($arr['id'],'goods', $arr['uid'], array(0=>$arr['id']), $this->uid, $this->web_id);
			$goods_result[$key]['comment']	= @$comment_arr[$arr['id']];
			
		}
		
		$data	= null;
		foreach($goods_result as $key=>$arr){
			$this->assign("result_data" , $arr );
			$data[]	= $this->fetch("goods_list_page");
		}
		
		// is_end 	是否己经到最后。	0不是  1 是
		$is_end	= count($goods_result) >= $this->goods->goods_page_size ? 0 : 1;
		$ret = array('data'=>$data,'page'=>($this->page+1) , 'is_end'=>$is_end );
		$this->ajaxReturn($ret,'',1,'jsonp');
		
	}
	
	
	
	
	
	/**
	 * 获得单个商品的描述
	 * 页面的型式显示
	 * */
	public function goods_show(){
		$gid 	= intval( $this->input->get_post('gid') );
		
		$result	= $this->goods->get_goods_one($gid);
		if(count($result)<=0){
			$this->redirect( 'webmain/index/main',array('web_id'=>$this->web_id) );
			die;
		}
		
		$result['pics_img']= "";
		$result['pics_arr']= "";
		if($result['pics']){
			$pics_img_arr		= @json_decode($result['pics'],true);
			$result['pics_arr']	= $pics_img_arr;
			if(is_array($pics_img_arr)){
				foreach($pics_img_arr as $key=>$val){
					$result['pics_img'][$key]	= $val['groupname'].'/'.$val['filename'].'_b.'.$val['type'];
					$result['pics_img_f'][$key]	= $val['groupname'].'/'.$val['filename'].'_f.'.$val['type'];
					$result['pics_img_size'][$key]	= $val['photosizes'];
				}
			}
		}
		
		$result['main_pics_img'] = "";
		$pics_m	= "";
		if($result['main_pics']){
			$de_arr		= @json_decode($result['main_pics'],true);
			$pics_m		= @current($de_arr);
		}else{
			if($result['pics']){
				 $de_arr	= @json_decode($result['pics'],true);
				 $pics_m	= @current($de_arr);
			}
		}
		$result['main_pics_img']	= $pics_m['groupname'].'/'.$pics_m['filename'].'_f.'.$pics_m['type'];
		$result['cdate']	= @date('Y-m-d H-i-s' , $result['ctime']);
		
		$this->assign("result",$result);
		$this->assign("web_id",$this->web_id);
		$this->assign("web_info",$this->web_info);
		$this->assign("user",$this->user);
		$this->assign('uid',$this->uid);
		$this->assign('dkcode',$this->dkcode);
		$this->display('goods_show');
		
	}
	
	
	/***
	 * 获得单个商品的描述
	 * 弹出框的  商品描述
	 * **/
	public function goods_desc(){
		$gid 	= intval( $this->input->get_post('gid') );
		
		$result	= $this->goods->get_goods_one($gid);
		if(count($result)<=0){
			// 没有传频道
			$this->redirect( 'main/index/main');
			die;
		}
		
/*
{"status":1,"info":"","data":{"data":{} }}
{pics_img:[],uid:"",web_id:"",iid:"",brand_id:"",brand_name:"",name:"",link:"",price:"",description:"",date:""}

pics_img // 图片
uid      // 用户id
web_id   // 网页id
brand_id // 品牌id
brand_name// 品牌名称
name     // 商品名称
link     // 商品连接
price    // 商品价格
description // 商品描述
date     // 商品发表时间
*/
		$data			= null;
		$data['pics_img']= "";
		if($result['pics']){
			$pics_img_arr	= @json_decode($result['pics'],true);
			if(is_array($pics_img_arr)){
				foreach($pics_img_arr as $key=>$val){
					$data['pics_img'][$key]	= $val['groupname'].'/'.$val['filename'].'_b.'.$val['type'];
					$data['pics_img_f'][$key]	= $val['groupname'].'/'.$val['filename'].'_f.'.$val['type'];
					$data['pics_img_size'][$key] = $val['photosizes'];
				}
			}
		}
		$data['id']		= $result['id'];
		$data['uid']	= $result['uid'];
		$data['web_id']	= $result['web_id'];
		$data['brand_id']= $result['brand_id'];
		$data['brand_name']= "";
		$data['name']= $result['name'];
		$data['link']	= $result['link'];
		$data['price']	= $result['price'];
		$data['description']= $result['description'];
		$data['date']	= @gmdate("Y-n-d H-i-s" ,@$result['ctime']);
		//$this->ajaxReturn($data,'',1,'jsonp');
		$this->assign("data",$data);
		
		$this->assign("web_id",$this->web_id);
		$this->assign("web_info",$this->web_info);
		$this->assign("user",$this->user);
		$this->assign('uid',$this->uid);
		$this->assign('dkcode',$this->dkcode);
		$this->display('goods_page2');
	}
	
	
	

	/**
	 * 根据底级分类获取商品品牌 
	 */
	public function  get_brand() {
		$ci = & get_instance();
		$catid = $ci->input->get('catid');
		if(empty($catid)) {
			return $this->dump('post_error');
		}
		
		$brand = service('Interest')->get_category_brand($catid);
		return $this->dump('post_success', true, array('data'=>$brand));
	}
	
	/**
	 * 获取商品的分类信息，树状结构
	 */
	public function get_category_tree() {
		$ci = & get_instance();
		$catid = $ci->input->get('catid');
		$data = $this->checkData($catid);
		if($data===false) {
			return $this->dump('post_error');
		}
		$cat_level_name = service('Interest')->get_category_level_name($data[0], $data[1]);
		if($cat_level_name['has_son']==1) {
			$catInfo = $this->_get_category_info($data);
			$cat_level_name['info'] = $catInfo;
		}
		return $this->dump('post_success', true, array('data'=>$cat_level_name));
	}
	
	private function _get_category_info($data) {
		$cat_level = service('Interest')->get_category_level($data[0], $data[1]+1);
		foreach($cat_level as $k=>$c) {
			if($c['has_son']==1) {
				$res = service('Interest')->get_category_level($c['id'], $c['level']+1);
				$cat_level[$k]['child'] = $res;
			}
		}
		return $cat_level; 
	}
	
	private function checkData($data) {
		if(empty($data)) {
			return false;
		} else {
			$val = explode('_', $data);
			if(is_string($val)) {
				return array($val, 1);
			} else if(is_array($val)) {
				return array($val[count($val)-1], count($val));
			} else {
				return false;
			}
		}
	}
	
	/**
	 * 对输出进行控制
	 * 
	 * @author fbbin
	 * @param array/string $info        	
	 * @param bool $status        	
	 * @param array $extra        	
	 */
	private function dump($info = '', $status = false, $extra = array()) {
		if (is_string($info)) {
			$data = array(
					'data' => array(),
					'status' => (int)$status,
					'info' => $info
			);
		} elseif (is_array($info)) {
			$data = $info;
		}
		if (!empty($extra)) {
			$data = array_merge($data, $extra);
		}
		exit(json_encode($data));
	}
	
	
	

	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	public function test(){
		$this->goods->test();
	}
	
	
	public function page(){
		
		$this->display('goods_page');
	}
	
	
	public function page2(){
		
		$this->display('goods_page2');
	}
	
}