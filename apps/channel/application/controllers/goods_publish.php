<?php

/**
 * Index controller
 * @author shedequan
 */
class Goods_publish extends MY_Controller {
	// 标识来源于网页
	const TOPIC_FROM_WEB = 3;
	// 转发网页中的数据权限为公开
	const PERMISSION_PUBLIC = 1;
	
	private $allowTypes = array(
			'album',
			'info',
			'video',
			'event',
			'goods',
			'groupon' 
	);
	
	function __construct(){
		parent::__construct();

		$this->load->helper('channel');
		
	}
	
	
	
	
	

	public function loadPostbox() {
		// 所有者鉴权
		$webOwner = $this->web_info['uid'];
		if ($webOwner !== $this->uid) {
			return $this->dump(L('not_page_ownner'));
		}
		$tpl = $this->input->get('page');
        
        $contents = '';
        if ($tpl) {
            ob_start();
            include_once APPPATH.'views/timeline/'.$tpl.'.html';
            $contents = ob_get_clean();
        }
        
//		$filename = APPPATH.'views/timeline/'.$tpl.'.html';
//		
//		$handle = fopen($filename, 'r');
//		$contents = '';
//		while(!feof($handle)) {
//			$contents .= fgetc($handle);
//		}
//		fclose($handle);
        
		echo $this->dump(L('page_success'), true, array('data'=>$contents));
	}
	
	
	
	

	/**
	 * 商品修改
	 * 
	 * @author heyuejuan
	 * @param	前端需要提交的参数列表：
	 * @param	content 用户填写的内容
	 * @param	type 当前数据的格式:info/album/video/event...
	 * @param	timestr 选择的发布时间:格式：2012-3-2
	 * @param	bc 公元前后表示：1（前），-1（后）
	 * @param	timedesc 时间描述信息
	 * @param	web_id 当前网页的ID(int)
	 */
	public function goods_edit() {
		// 所有者鉴权
		$webOwner = $this->web_info['uid'];
		if ($webOwner !== $this->uid) {
			return $this->dump(L('not_page_ownner'));
		}
		
		$data = array(
				'uid' => $this->uid,
				'dkcode' => $this->dkcode,
				'uname' => $this->web_info['name'],
				'title' => date('Y-m-d H:i:s', SYS_TIME),
				'from' => self::TOPIC_FROM_WEB,
				'pid' => WEB_ID,
				'dateline' => date('YmdHis', SYS_TIME) 
		);
		$data['type'] = P('type');
		
		if (!in_array($data['type'], $this->allowTypes)) {
			return $this->dump(L('unknow_style_content'));
		}
		// 内容处理
		$data['content'] = '';
		$data['timedesc'] = '';
		
		$data['ctime'] = date('YmdHis', SYS_TIME);
		
		
		
		$methodData = $this->_parse_goods_data_edit($data, $this->web_info);	// 更新数据库
		$data = array_merge($data, $methodData);
		
		$result = service('WebTimeline')->updateWebtopicByMap($data);
		if ($result === false) {
			return $this->dump(L('operation_fail'));
		}
		
		$result = $this->resultHanler($data);
		
		$this->ajaxReturn($result,'',1,'jsonp');	// 商品这里必段是 jsonp
		
	}
	
	
	private function resultHanler($res) {
		//$fastdfs 	= getConfig('fastdfs', 'album');
		$fastdfs_domain = config_item('fastdfs_domain');
		$this->fastdfs_url= 'http://'.$fastdfs_domain.'/';
		

		$goods = json_decode($res['goods'], true);
		
		$goods['img'] = array_map( array($this,"create_url") , $goods['img']);
		$goods['thumb'] = array_map( array($this,"create_url") , $goods['thumb']);
		$res['goods'] = $goods;
		
		return $res;
	}
	
	// url 全地址
	public function create_url($v){
		return $this->fastdfs_url.$v;
	}
	
	
	/***
	 * 删除  商品
	 * **/
	public function goods_delete(){
		$this->load->model('goodsmodel');
		$gid	= intval($this->input->get_post('gid'));
		
		$reslut	= $this->goodsmodel->get_goods_one($gid);
		if(is_array($reslut)){
			$iid_group	= explode( '_' ,$reslut['iid']);
			$imid_arr[]		= current($iid_group);
			service('WebTimeline')->delWebtopicByMap($gid,'goods',$imid_arr ,$this->web_id);
			$this->goodsmodel->delete_goods($gid);
		}
		
		$this->redirect( 'channel/goods/alist', array('web_id'=>$this->web_id) );
	}
	
	
	
	
	
	
	/***
	 * 商品
	 * 
	 * **/
	public function _parse_goods_data_edit($data, $web){
		$this->load->model('goodsmodel');
		
		$goods['goodsname'] = P('goodsname');
		$goods['href'] 		= P('href');
		$goods['saleprice'] = P('saleprice');
		$goods['img'] 		=  $this->input->get_post("img");
		$goods['thumb'] 	=  explode(',', P("thumb"));
		$goods['web_id']	= $this->web_id;
		$gid				= intval($this->input->get_post('gid'));
		
		$catid	= service('Interest')->get_category_group(P('catid'), 4);
		$brand_id	= P('brand');
		
		
	
		if($brand_id<=0){
			$brand_name	= trim(P('brand_name'));
			if($brand_name==''){
				return false;
			}
			$brand_id	= $this->goodsmodel->add_goods_brand($brand_name,$catid);
			
		}else{
			$brand_name	= $this->goodsmodel->get_goods_brand_name($catid);
		}
		$goods['brand_id'] 	= $brand_id;
		$goods['brand_name']= $brand_name;
		$firstCover	= intval(P('firstCover'))+1;
		
		
		$pics_img	= json_decode($goods['img'],true);
		if(is_array($pics_img)){
			foreach($pics_img as $key=>$val){
				$cc[$key] 	= $val['groupname'].'/'.$val['filename'].'_b.'.$val['type'];
				$pice_obj[$key]	= $val;
			}
			if(isset($cc[$firstCover])){
				$main_pics_arr_val	= $pics_img[$firstCover];
			}else{
				foreach($pics_img as $key=>$val){
					$main_pics_arr_val	= $val;
					$firstCover	= $key;
					break;
				}
			}
			$main_pics_arr[$firstCover]	= $main_pics_arr_val;
			$main_pics	= json_encode($main_pics_arr);
			

			$aa[]	= $cc[$firstCover];
			$tt[]	= $pice_obj[$firstCover]['photosizes'];
			foreach($cc as $key=>$val){
				if($firstCover!=$key){
					$aa[]	= $val;
					$tt[]	= $pice_obj[$key]['photosizes'];
				}
			}
			$goods['img']	= $aa;
			$goods['img_size']	= $tt;
		}else{
			$goods['img']= "";
			$goods['img_size']	= "";
			$main_pics 		= "";
		}
		
		
		$goodata = array(
			'iid' => $catid,
			'brand_id' => P('brand'),
			'main_pics' => $main_pics,
			'name' => $goods['goodsname'],
			'link' => $goods['href'],
			'price' => $goods['saleprice'],
			'pics' => addslashes_deep($this->input->get_post("img")),
			'description' => $data['content'],
			'utime' => SYS_TIME
		);
		$this->goodsmodel->update_goods($goodata , $gid);	// 加入数据库
		
		$goods['gid']	= $gid;	// 商品的两个标示
		
		return array('goods' => json_encode($goods) , 'fid'=>$gid);
		
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
	
	
	
	
	
}