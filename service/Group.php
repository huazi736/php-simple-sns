<?php

class GroupService extends DK_Service {

    private $id = null;

    public function __construct() {
        parent::__construct();

        $this->init_redis();
        
        $this->init_db('group');
    }
	
	/**
	 * 获取用户所有的群组和子群
	 *
	 * @param $uid unknown_type       	
	 */
	public function getUserGroup( $uid )
	{
		$where = array ( 'uid' => $uid );
		$field_groups = array ( 'uid', 'gid' );
		$field_subgroups = array ( 'uid', 'sid' );
		
		$result_groups = $this->db->from( 'group_membership' )->where( $where )->select( $field_groups )->get()->result_array();
		
		$result_subgroups = $this->db->from( 'group_sub_membership' )->where( $where )->select( $field_subgroups )->get()->result_array();
		
		/**
		 * 循环添加有用数据：群名称，群类型
		 */
		$array_group = array();
		$field = array ( 'name', 'source_type' );
		if ( $result_groups ) {
			foreach ( $result_groups as &$result_group ) {
				$obj_where = array ( 'gid' => $result_group ['gid'] );
				$obj_group = $this->db->from( 'group_info' )->where( $obj_where )->select( $field )->get()->row_array();
				$the_group ['roomid']   = $result_group ['gid'];
				$the_group ['roomnick'] = $obj_group ['name'];
				$the_group ['roomtype'] = '0';
				
				// 查询出群成员数
				$if_where = array ( 'gid' => $result_group ['gid'] );
				$if_field = array ( 'member_counts' );
				$if_obj = $this->db->from( 'group_extend' )->where( $if_where )->select( $if_field )->get()->row_array();
				$the_group ['count'] 	= $if_obj['member_counts'];
				array_push( $array_group, $the_group );
			}
		}
		
		if ( $result_subgroups ) {
			$field_sub = array ( 'name','member_counts' );
			foreach ( $result_subgroups as &$result_subgroup ) {
				$obj_subwhere = array ( 'sid' => $result_subgroup ['sid'] );
				$obj_subgroup = $this->db->from( 'group_sub_info' )->where( $obj_subwhere )->select( $field_sub )->get()->row_array();
				$the_subgroup ['roomid']   = $result_subgroup ['sid'];
				$the_subgroup ['roomnick'] = $obj_subgroup ['name'];
				$the_subgroup ['roomtype'] = '1';
				$the_subgroup ['count'] 	   = $obj_subgroup ['member_counts'];
				array_push( $array_group, $the_subgroup );
			}
		}
		
		return $array_group;
	}
	
	/**
	 * 查询父群内的子群列表
	 * @param unknown_type $gid
	 */
	public function getSubgroupByGroup( $gid ) 
	{
		$where = array ( 'gid' => $gid );
		$field = array ( 'sid', 'name', 'creator' );
		
		$result = $this->db->from( 'group_sub_info' )->where( $where )->select( $field )->get()->result_array();
		
		$array_group = array();

		if ( $result ) {
			foreach ( $result as $obj ) {
				$the_group ['roomid']   = $obj ['sid'];
				$the_group ['roomnick'] = $obj ['name'];
				$the_group ['ownerid']  = $obj ['creator'];
				array_push( $array_group, $the_group );
			}
		}
	
		return $array_group;
	}
	
	/**
	 * 群成员列表
	 * @param unknown_type $id
	 * @param unknown_type $is_sub
	 * @return multitype:
	 */
	public function getGroupMembers( $id, $is_sub = false )
	{
		// 查询子群成员
		if ( $is_sub ) {
			// 查询群组成员
			$where = array ( 'sid' => $id );
			$field = array ( 'uid' );
				
			$result = $this->db->from( 'group_sub_membership' )->where( $where )->select( $field )->get()->result_array();
		} else {
			// 查询群组成员
			$where = array ( 'gid' => $id );
			$field = array ( 'uid' );
			
			$result = $this->db->from( 'group_membership' )->where( $where )->select( $field )->get()->result_array();
		}
		
		// 重构数据
		$array_group = array ();
		foreach ( $result as $obj ) {
			array_push( $array_group, $obj ['uid'] );
		}
		
		return $array_group;
	}
	
	/**
	 * 查询群信息
	 * @param unknown_type $id
	 */
	public function getGroupInfo( $id )
	{
		$var_return = null;
	
		// 查询子群信息
		$field = array ( 'name', 'creator', 'gid', 'member_counts' );
		$where = array ( 'sid' => $id );
		$obj = $this->db->from( 'group_sub_info' )->where( $where )->select( $field )->get()->row_array();
	
		if ( $obj ) {
			$var_return['roomid']   = $id;
			$var_return['roomnick'] = $obj['name'];
			$var_return['roomtype'] = 0;
			$var_return['parentid'] = $obj['gid'];
			$var_return['ownerid']  = $obj['creator'];
			$var_return['count'] 	= $obj['member_counts'];
		} else {
			$field = array ( 'name', 'creator' );
			$where = array ( 'gid' => $id );
			$obj = $this->db->from( 'group_info' )->where( $where )->select( $field )->get()->row_array();
	
			if ( $obj ) {
				$var_return['roomid']   = $id;
				$var_return['roomnick'] = $obj['name'];
				$var_return['roomtype'] = 0;
				$var_return['parentid'] = 0;
				$var_return['ownerid']  = $obj['creator'];
				// 查询出群成员数
				$if_where = array ( 'gid' => $id );
				$if_field = array ( 'member_counts' );
				$if_obj = $this->db->from( 'group_extend' )->where( $if_where )->select( $if_field )->get()->row_array();
				$var_return['count'] 	= $if_obj['member_counts'];
			} else {
				return null;
			}
		}
		return $var_return;
	}
	
	public function getMyGroups($uid, $type = 'CUSTOM', $limit = 3)
	{
		$result = $this->db->from('group_membership')->where(array('uid' => intval($uid)))->select(array('gid'))->get()->result_array();
		$gids = array();
		foreach( $result as $r ) {
			$gids[] = $r['gid'];
		}
		if(empty($gids)) return array();
		$result = $this->db->from('group_info')->where("gid IN (" . implode(',', $gids) . ") AND source_type = '" . $type . "'")->order_by('id', 'desc')->limit($limit)->get()->result_array();
		$array = array();
		foreach( $result as $r ) {
			$array[] = $r;
		}
		return $array;
	}
	
	public function getGroupsByCustom($uid, $limit = 5)
	{
		return $this->getMyGroups($uid, 'CUSTOM', $limit);	
	}

    public function addGroupInfo(array $data) {
        if (empty($data)) {
            return false;
        }
        return json_encode($this->_addGroupInfo($data));
    }

    public function getNowInfo() {
        return json_encode($this->_getNewInfo());
    }

    public function getPageInfo($gid, $num = 20, $page = 1) {
        if (!$gid) {
            return false;
        }
        return json_encode($this->_getPageInfo($gid, $page, $num));
    }

    // inside functions

    public function _addGroupInfo($data) {
        $data = array_merge($data, array('id' => $this->_iniId(), 'dateline' => SYS_TIME));
        if ($this->redis->hMset($this->_getInfoKey($this->_iniId()), $data) !== false) {
            $newInfo = $this->getNewInfo($data['gid'], $data['uid']);
            if ($this->redis->zAdd($this->_getGroupKey($data['gid']), SYS_TIME, $this->id) !== false) {
                return array_merge(array($this->id => $data), $newInfo);
            }
            return array();
        } else {
            return array();
        }
    }
    
	public function getNewInfo( $gid, $uid = '' )
	{
		$ids = $this->redis->zRangeByScore($this->_getGroupKey($gid), SYS_TIME - 30, SYS_TIME) ?: array();
		$arr = array();
		foreach( $ids as $id )
		{
			$infos = $this->redis->hGetAll($this->_getInfoKey($id));
			if( $uid && $infos['uid'] == $uid )
			{
				continue;
			}
			$arr[$id] = $infos;
		}
		return $arr;
	}

    private function _iniId() {
        $this->id = $this->redis->incr('Gtid');
        return $this->id;
    }

    private function _getInfoKey($id) {
        if (empty($id)) {
            return "Group:" . $this->id;
        }
        return "Group:" . $id;
    }

    private function _getGroupKey($gid) {
        return "Gtinfo:" . $gid;
    }

    public function _getNewInfo($gid, $uid = '') {
        $ids = $this->redis->zRangeByScore($this->_getGroupKey($gid), SYS_TIME - 30, SYS_TIME) ? : array();
        $arr = array();
        foreach ($ids as $id) {
            $infos = $this->redis->hGetAll($this->_getInfoKey($id));
            if ($uid && $infos['uid'] == $uid) {
                continue;
            }
            $arr[$id] = $infos;
        }
        return $arr;
    }

    public function _getPageInfo($gid, $page = 1, $nums = 10) {
        $nowpage = ($page - 1) * $nums;
        $ids = $this->redis->zRevRange($this->_getGroupKey($gid), $nowpage, $nowpage + $nums - 1) ? : array();
        $arr = array();
        foreach ($ids as $id) {
            $arr[$id] = $this->redis->hGetAll($this->_getInfoKey($id));
        }
        return array('data' => $arr, 'count' => $this->_getKeyCount($gid));
    }

    private function _getKeyCount($gid) {
        return $this->redis->zSize($this->_getGroupKey($gid));
    }

    /**
     * @author fbbin
     * @desc 异步保存到disk
     */
    public function __destruct() {
        $this->redis->bgsave();
    }

}