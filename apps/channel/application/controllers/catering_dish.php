 <?php

/**
 * Catering Dish controller
 * @author shedequan
 */
class Catering_dish extends MY_Controller {
	
	public function __construct() {
		parent::__construct();
        $this->load->helper('channel');
		$this->load->model('catering_dish_model', 'dish');
        
        $this->assign('index_link', mk_url('channel/catering_dish/index', array(
				'web_id' => $this->web_id 
		)));
        $this->assign('create_link', mk_url('channel/catering_dish/create', array(
				'web_id' => $this->web_id 
		)));
        
        $this->web_info['avatar'] = get_webavatar($this->web_info['uid'], 's', $this->web_info['aid']);
		$this->assign('web_link', mk_url('webmain/index/main', array(
				'web_id' => $this->web_id 
		)));
		$this->assign('web_info', $this->web_info);
		$this->page = intval(get_post('page'));
		if($this->page<=0) {
			$this->page = 1;
		}
	}
	
	/**
	 * 菜谱列表
	 */
	public function index() {
		$this->common_display();
		
		$count = $this->dish->get_dishs_count($this->web_id);
		$this->assign('count',$count);
		
		$this->display('catering/dishes.html');
	}
	
	/**
	 * 分頁菜譜列表
	 */
	public function get_dish_page() {
		$this->assign('page_data','1');
		$this->common_display();
		$this->display('catering/dishes_page.html');
	}
	
	private function common_display() {
		$dishes = $this->dish->all($this->web_id, $this->page);
		$this->assign('dishes', $dishes);
		$this->assign('page',($this->page+1));
		$this->assign('web_id',$this->web_id);
		$continue_load = count($dishes) < $this->dish->dish_list_size ? false : true;
		$this->assign('continue_load',$continue_load);
	}
    
    public function create() {
        $this->display('catering/dish_create.html');
    }

    public function add() {   
		// 获得菜谱数据，入库
		$dish = $this->get_dish_data();
		if(is_string($dish)) {
			return $this->ajaxReturn('',$dish,0,'jsonp');
		}
		$dish['ctime'] = time();
		$this->dish->add($dish);
		$fid = $this->dish->get_insert_id();
		// 删除时间线冗余的数据
		unset($dish['uid'], $dish['web_id']);
		// 发布时间线
	  	$result = $this->save_timeline(array(
				'dish' => json_encode($dish) 
		), $fid);
		// 处理请求返回结果
		if (is_string($result)) {
			return $this->ajaxReturn('',$result,0,'jsonp');
		} else {
			return $this->ajaxReturn(array('data'=>$result ),'operation_success',1,'jsonp');
		}
	}
	
	/**
	 * 删除菜谱信息
	 */
	public function remove() {
		$fid = get_post('id');
		$this->dish->remove($fid);
		$delStatus = service('WebTimeline')->delWebtopicByMap($fid, 'dish', $this->getWebpageTagID($this->web_id), $this->web_id);
		if($delStatus) {
			return $this->ajaxReturn('','operation_success',1,'jsonp');
		} else {
			return $this->ajaxReturn('','operation_fail',0,'jsonp');
		}
	}
	
	public function update() {
		$fid = get_post('id');
		$dish = $this->get_dish_data();
		if(is_string($dish)) {
			return $this->ajaxReturn('',$dish,0,'jsonp');
		}
		$this->dish->set($fid, $dish);
		// 删除时间线冗余的数据
		unset($dish['uid'], $dish['web_id']);
		$data = array(
			'fid' => $fid,
			'type' => 'dish',
			'pid' => $this->web_id,
			'dish' => json_encode($dish)
		);
		$updateStatus = service('WebTimeline')->updateWebtopicByMap($data);
		unset($data, $dish); 
		if($updateStatus) {
			return $this->ajaxReturn('','operation_success',1,'jsonp');
		} else {
			return $this->ajaxReturn('','operation_fail',0,'jsonp');
		}
	}
	
	/**
	 * 菜谱的详细信息
	 */
	public function detail() {
		$id = get_post('id');
		//$catid = get_post('catid');
		$dish = $this->dish->get($id);
		//$category = $this->_get_detail_tree($catid);
/* 		if($category === false || ($data = checkData($dish['iid'])) === false) {
			return $this->ajaxReturn('','operation_fail',0,'jsonp');
		}	 */
		//$low_level = service('Interest')->get_category_level_name($data[0], $data[1]);
		//$dish['fastdfs'] = getFastdfs();
		$dish['pics'] = json_decode($dish['pics']);
/* 		$dish['catid'] = $low_level['id'];
		$dish['catname'] = $low_level['name'];
		$dish['category'] = $category; */
		return $this->ajaxReturn(array('data'=>$dish),'operation_success',1,'jsonp');
	}
	
	public function display_dish_photo() {
		$id = intval(get_post('id'));
		$dish = $this->dish->get($id);
		if(empty($dish)) {
			return $this->ajaxReturn('','operation_fail',0,'jsonp');
		}
		$dish['pics'] = $this->loop_update_img(json_decode($dish['pics'],true));
		$prev_next_lists = $this->dish->get_dish_prev_next($dish, $this->web_id);
		$this->assign('dish', $dish);
		$this->assign('prev_next_lists', $prev_next_lists);
		
		//清空ie见面缓存问题
		header("Expires:Mon, 25 Jul 1998 05:00:00 GMT");
		header("Cache-Control:no-cache,must-revalidate");
		header("Pragma:no-cache");
		
		$this->display('catering/dishes_photo');
	}
	
	private function get_dish_data() {
		/* $catid = get_post('catid');
		if(strpos($catid, '_') == false) {
			$catid = service('Interest')->get_category_group($catid, 4);
		} */
		$dish = array(
				'uid' => $this->uid,
				'web_id' => WEB_ID,
				'iid' => get_post('catid'),
				'name' => get_post('name'),
				'price' => get_post('price'),
				'pics' => json_encode($this->input->get_post('imgTag')),
				'description' => get_post('description'),
				'utime' => time() 
		);
		$webOwner = $this->web_info['uid'];
		if ($webOwner !== $this->uid) {
			return 'not_page_ownner';
		} else if(in_array('', $dish)) {
			return 'operation_fail';
		} else if(!isMoney($dish['price'])){
			return 'operation_fail';
		}
		
		return $dish;
	}
	
	/**
	 * 处理发布时间线图片数据
	 */
	public function deal_dish_data($res) {
		$dish = json_decode($res['dish'], true);
		$pics = $this->loop_update_img(json_decode($dish['pics'], true));
		$dish['pics'] = $pics;
		$res['dish'] = $dish;
		return $res;
	}
	
	/**
	 * 获取商品的分类信息，树状结构
	 */
	public function get_category_tree() {
		$data = checkData(get_post('catid'));
		if($data===false) {
			return $this->ajaxReturn('','operation_fail',0,'jsonp');
		}
		$cat_level_name = service('Interest')->get_category_level_name($data[0], $data[1]);
		if($cat_level_name['has_son']==1) {
			$catInfo = $this->_get_category_info($data);
			$cat_level_name['info'] = $catInfo;
		}
		return $this->ajaxReturn(array('data'=>$cat_level_name),'operation_success',1,'jsonp');
	}
	
	private function _get_detail_tree($catid) {
		$data = checkData($catid);
		if($data===false) {
			return false;
		}
		$cat_level_name = service('Interest')->get_category_level_name($data[0], $data[1]);
		if($cat_level_name['has_son']==1) {
			$catInfo = $this->_get_category_info($data);
			$cat_level_name['info'] = $catInfo;
		}
		return $cat_level_name;
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
	
}