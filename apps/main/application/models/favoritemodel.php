<?php
/**
 * Favoritemodel
 * 收藏
 *
 * @author zhoulianbo
 * @date 2012-7-9
 */
class Favoritemodel extends MY_Model{
	
	private $_favTable		 = 'favorites';
	private $_likeStatTable = 'link_stat';
	
	public function __construct(){
		parent::__construct();
		$this->init_db('system');
	}
	
	/**
	 * 
	 * 取得我的收藏列表
	 * @param integer $uid UID
	 * @param string  $tpye 类型
	 * @param string  $keyword
	 * @param integer $start  起始条目
	 * @param integer $limit  每页限制条数
	 * @return array
	 */
	public function getList($uid, $type = '', $keyword, $start = 0, $limit = 10) {
		
		if (!$uid) {
			return array();
		}
		$sql = $this->_createWhere($uid, $type, $keyword);
		$sql .= ' ORDER BY id DESC LIMIT ' . $start . ',' . $limit;
		$res = $this->db->query($sql)->result_array();
		return $res;
	}
	
	/**
	 * 
	 * 取得收藏的总数
	 * @param integer $uid
	 * @param string  $type
	 * @param string  $keyword
	 */
	public function getCount($uid, $type = '', $keyword = '') {
		
		if (!$uid) {
			return false;
		}
		
		if ($type) {
			$sql = $this->_createWhere($uid, $type, $keyword);
			return $this->db->query($sql)->num_rows();
		} else {
			$sql = 'SELECT COUNT(*) as num,type FROM ' . $this->_favTable . ' WHERE uid = ' . $uid;
			if ($keyword) {
				$sql .= " AND title REGEXP '.*{$keyword}.*'";
			}
			$sql .= ' GROUP BY type';
			return $this->db->query($sql)->result_array();
		}
	}
	
	/**
	 * 
	 * 生成sql语句
	 * @param integer $uid UID
	 * @param string  $tpye 类型
	 * @param string  $keyword
	 * @return string
	 */
	private function _createWhere($uid, $type = '', $keyword = '') {
		
		$sql = 'SELECT * FROM ' . $this->_favTable . ' WHERE uid = ' . $uid;
		if ($type) {
			$sql .= " AND type = " . $type;
		}
		
		if ($keyword) {
			$sql .= " AND title REGEXP '.*{$keyword}.*'";
		}
		return $sql;
	}
	
	/**
	 * 
	 * 检查是否收藏
	 * @param integer $object_id 对象ID
	 * @param string  $object_type
	 * @param integer $uid 用户ID
	 * @return integer|boolean
	 */
	public function checkFav($object_id, $object_type, $uid) {
		
		$this->db->where(array('object_id' => $object_id, 'uid' => $uid, 'object_type' => $object_type));
		$res = $this->db->get($this->_favTable)->result_array();
		if ($res) {
			return $res[0];
		}
		return false;
	}
	
	/**
	 * 
	 * 取得一条收藏数据
	 * @param integer $fid
	 * @return array
	 */
	public function getFavById($fid) {
		
		$this->db->where(array('id' => $fid));
		$res = $this->db->get($this->_favTable)->result_array();
		if ($res) {
			return $res[0];
		}
		return array();
	}
	
	/**
	 * 
	 * 保存收藏
	 * @param array $data
	 */
	public function saveFav($data) {
		
		if (!$data) {
			return false;
		}
		
		if (!array_key_exists('dateline', $data)) {
			$data['dateline'] = time();
		}
		
		$res = $this->db->insert($this->_favTable, $data);
		if($res) {
			$count = $this->addLikeStat($data['object_id'], $data['object_type'], $data['web_id']);
			return $count;
		}
		return false;
	}
	
	/**
	 * 
	 * 取消收藏
	 * @param integer $id
	 * @param integer $object_id
	 * @param string  $object_type
	 * @return integer|boolean
	 */
	public function delFav($id, $object_id, $object_type) {
		
		$this->db->where('id', $id);
		$res = $this->db->delete($this->_favTable);
		if ($res) {
			$count = $this->delLikeStat($object_id, $object_type);
			if ($count) {
				return $count;
			}
			return $res;
		}
		return false;
	}
	
	/**
	 * 
	 * 添加统计数据
	 * @param integer $object_id
	 * @param string  $object_type
	 * @param integer $web_id
	 */
	public function addLikeStat($object_id, $object_type, $web_id = 0) {
		
		$this->db->where(array('object_id' => $object_id, 'object_type' => $object_type));
		$res = $this->db->get($this->_likeStatTable)->result_array();
		if ($res) {
			$res = $res[0];
			
			// 统计数量加1
			$data = array(
				'favorite_count' => $res['favorite_count'] + 1,
				'total_count' => $res['total_count'] + 1
			);
			
			$this->db->update($this->_likeStatTable, $data, 'id = ' . $res['id']);
		} else {
			$data = array(
				'object_id' => $object_id,
				'object_type' => $object_type,
				'total_count' => 1,
				'favorite_count' => 1,
				'web_id' => $web_id
			);
			$this->db->insert($this->_likeStatTable, $data);
		}

		return $data['favorite_count'];
	}
	
	/**
	 * 
	 * 删除收藏统计
	 * @param integer $object_id
	 * @param string  $object_type
	 * @return integer
	 */
	public function delLikeStat($object_id, $object_type) {

		$this->db->where(array('object_id' => $object_id, 'object_type' => $object_type));
		$res = $this->db->get($this->_likeStatTable)->result_array();
		if (!$res) {
			return false;
		}
		$res = $res[0];
		
		// 统计数量减1
		$data = array(
			'favorite_count' => $res['favorite_count'] ? $res['favorite_count'] - 1 : 0,
			'total_count' => $res['total_count'] ? $res['total_count'] - 1 : 0
		);
		
		$this->db->update($this->_likeStatTable, $data , 'id = ' . $res['id']);
		return $data['favorite_count'];
	}
}