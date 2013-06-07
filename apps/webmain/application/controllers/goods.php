<?php

/**
 * [ Duankou Inc ]
 * Created on 2012-7-15
 * @author denggang
 * The filename : goods.php   13:33:45
 */
class goods extends DK_Controller {
	
	public function __construct() {
		parent::__construct();
		$this->load->model('goodsmodel');
	}
	
	/**
	 * 根据底级分类获取商品品牌 
	 */
	public function  get_brand() {
		$ci = & get_instance();
		$catid = $ci->input->get('catid');
		if(empty($catid)) {
			$this->ajaxReturn('','post_error',0,'jsonp');
			die;
			//return $this->dump('post_error');
		}
		
		$brand = service('Interest')->get_category_brand($catid);
		//return $this->dump('post_success', true, array('data'=>$brand));
		$this->ajaxReturn($brand,'',1,'jsonp');
	}
	
	/**
	 * 获取商品的分类信息，树状结构
	 */
	public function get_category_tree() {
		$ci = & get_instance();
		$catid = $ci->input->get('catid');
		$data = $this->checkData($catid);
		if($data===false) {
			//return $this->dump('post_error');
			$this->ajaxReturn('','post_error',0,'jsonp');
			die;
		}
		$cat_level_name = service('Interest')->get_category_level_name($data[0], $data[1]);
		if($cat_level_name['has_son']==1) {
			$catInfo = $this->_get_category_info($data);
			$cat_level_name['info'] = $catInfo;
		}
		//return $this->dump('post_success', true, array('data'=>$cat_level_name));
		$this->ajaxReturn($cat_level_name,'',1,'jsonp');
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
	
	public function test() {
		$this->load->model('goodsmodel');
		$this->goodsmodel->test();
	}
}

?>