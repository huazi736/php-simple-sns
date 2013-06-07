<?php
/**
 * 收藏接口类
 *
 * @author zhoulianbo
 * @date 2012-7-12
 */

class FavoriteService extends DK_Service {
	
	private $_favTable = 'favorites';
	private $_likeStatTable = 'link_stat';
	
	public function __construct() {
        parent::__construct();
        $this->init_db('system');
        $this->init_memcache('default');
    }
    
    /**
     * 
     * 判断是否收藏
     * @param integer $object_id   对象id
     * @param string  $object_type 对象类型
	 * + 'blog' => '日志', 
	 * + 'photo' => '照片', 
	 * + 'video' => '视频', 
	 * + 'album' => '相册',
	 * + 'web_blog' => '网页日志', 
	 * + 'web_photo' => '网页照片', 
	 * + 'web_video' => '网页视频', 
	 * + 'web_album' => '网页相册',
     * @param integer $uid 用户id
	 * @return boolean
     */
    public function checkFavorite($object_id, $object_type, $uid) {
    	
    	$allowTypes = getConfig('recommend', 'fav_allow_types');
    	$typeName = array_key_exists($object_type, $allowTypes) ? $allowTypes[$object_type] : '';
    	if (!$object_id || !$uid || !$typeName) {
    		return false;
    	}
    	
    	$where = array('uid' => $uid, 'object_id' => $object_id, 'object_type' => $object_type);
    	$data = $this->db->get_where($this->_favTable, $where, 1, 0)->num_rows();
    	
    	if ($data) {
    		return true;
    	}
    	return false;
    }
    
    /**
	 * delFavByWebid
	 * 删除网页的所有收藏
	 * 
	 * @param integer $web_id 网页ID
	 * @return boolean
	 */
    public function delFavByWebid($web_id) {
    	
    	if (!$web_id) {
			return false;
		}
		
		return $this->db->delete($this->_favTable, array('web_id' => $web_id));
    }
    
	/**
	 * delFav
	 * 删除模块或者网页的所有收藏
	 * 
	 * @param integer $object_id   对象ID
	 * @param string  $object_type 对象类型
	 * @param integer $web_id 网页ID
	 * @return integer|boolean
	 */
	public function delFav($object_id, $object_type) {
		
		if ((!$object_id && !$object_type) || !$web_id) {
			return false;
		}
		
		$params = array('object_id' => $object_id, 'object_type' => $object_type);
		$res = $this->db->delete($this->_favTable, $params);
		if ($res) {
			return $this->delLikeStat($object_id, $object_type);
		}
		return false;
	}
	
	/**
	 * _delLikeStat
	 * 删除收藏统计
	 * 
	 * @param integer $object_id   对象ID
	 * @param string  $object_type 对象类型
	 * @return integer|boolean
	 */
	private function _delLikeStat($object_id, $object_type) {
		
		if (!$object_id || !$object_type) {
			return false;
		}
		
		$this->db->where(array('object_id' => $object_id, 'object_type' => $object_type));
		$res = $this->db->get($this->_likeStatTable)->result_array();
		if (!$res) {
			return false;
		}
		$res = $res[0];
		
		// 清空收藏的统计数据
		$data = array(
			'favorite_count' => 0,
			'total_count' => $res['total_count'] - $res['favorite_count']
		);
		
		// 如果汇总统计为0，则删除该条统计数据，否则只清空收藏的统计数据
		if ($data['total_count'] <= 0) {
			return $this->db->delete($this->_likeStatTable, array('id' => $res['id']));
		}
		
		return $this->db->update($this->_likeStatTable, $data , 'id = ' . $res['id']);
	}
}