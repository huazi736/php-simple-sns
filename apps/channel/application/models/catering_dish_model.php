<?php

/**
 * Catering dish model
 * @author dequan.she
 */
class Catering_dish_model extends MY_Model {

    const TABLE_NAME = 'catering_dish';
    public $dish_list_size;

    public function __construct() {
        parent::__construct();
        $this->dish_list_size = 20;	// 网页列表  20 个 分类
        $this->init_db('interest');
    }

    public function add($data) {
        return $this->db->insert(self::TABLE_NAME, $data);
    }
    
    public function set($id, $data) {
        return $this->db->where('id', $id)->update(self::TABLE_NAME, $data);
    }  
    
    public function remove($id) {
        return $this->db->where('id', $id)->delete(self::TABLE_NAME);
    }

    public function get($id) {
        return $this->db->get_where(self::TABLE_NAME, array('id' => $id))->row_array();
    }

    public function all($webid, $page) {
    	$page--;
    	if($page<=0) {
    		$page = 0;
    	}
    	$limit_page	= $page * $this->dish_list_size;
        $this->db->where('web_id', $webid);
		$this->db->order_by('utime desc');
		$this->db->limit($this->dish_list_size, $limit_page);
		$list = $this->db->get('catering_dish')->result_array();
		foreach ($list as $dish) {
			$aiid = checkData($dish['iid']);
			$category = service('Interest')->get_category_level_name($aiid[0], $aiid[1]);
			$user = service('User')->getUserInfo($dish['uid'], 'uid',array('username'));
			$on['id'] = $dish['id'];
			$on['username'] = $user['username'];
			$on['catname'] = $category['name'];
			$on['dishname'] = $dish['name'];
			$on['price'] = $dish['price'];
			$pics = json_decode($dish['pics'], true);
			if(is_array($pics)) {
				foreach($pics as $_k=>$_v) {
					$pics[$_k]['s']['url'] = 'http://' .getFastdfs(). '/' . $pics[$_k]['s']['url'];
					$pics[$_k]['b']['url'] = 'http://' .getFastdfs(). '/' . $pics[$_k]['b']['url'];
				}
			}
			$on['img'] = $pics;
			$on['description'] = $dish['description'];
			$on['ctime'] = date('Y-m-d H:i:s', $dish['ctime']);
			$on['utime'] = date('Y-m-d H:i:s', $dish['utime']);
			$dish_list[] = $on;
		}
		return isset($dish_list) ? $dish_list : array();
    }
    
    public function get_insert_id() {
    	return $this->db->insert_id();
    }
    
    public function get_dishs($web_id) {
    	return $this->db->get_where(self::TABLE_NAME, array('web_id' => $web_id))->row_array();
    }
    
    /**
     * 获取当前菜谱上一个、下一个菜谱
     * @param unknown_type $dish
     * @param unknown_type $web_id
     */
    public function get_dish_prev_next($dish, $web_id) {
    	$next_id = '';
    	$prev_id = '';
    	//下一张菜谱
    	$next_sql = sprintf("SELECT id
    			FROM ".self::TABLE_NAME."
    			WHERE `id` <> '%d'
    			AND `web_id` = '%d'
    			AND `utime` < '%s'
    			ORDER BY `utime` DESC
    			LIMIT 1 ",$dish['id'], $web_id, $dish['utime']);
    	$next_lists = $this->db->query($next_sql)->row_array();
    	if($next_lists) {
    		$next_id = $next_lists['id'];
    	}
    	//上一张菜谱
    	$prev_sql = sprintf("SELECT id
    			FROM ".self::TABLE_NAME."
    			WHERE `id` <> '%d'
    			AND `web_id` = '%d'
    			AND `utime` > '%s'
    			ORDER BY `utime` ASC
    			LIMIT 1 ",$dish['id'], $web_id, $dish['utime']);
    	$prev_lists = $this->db->query($prev_sql)->row_array();
    	if($prev_lists){
    		$prev_id = $prev_lists['id'];
    	}
    	return array('prev_id' => $prev_id,'next_id' => $next_id);
    }
    
    public function get_dishs_count($web_id) {
    	$this->db->where('web_id', $web_id);
    	$this->db->from(self::TABLE_NAME);
    	return $this->db->count_all_results();
    }
}