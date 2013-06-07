<?php

/**
 * [ Duankou Inc ]
 * Created on 2012-3-5
 * @author fbbin
 * The filename : GroupModel.php
 */
class TheGroupModel extends DkModel {

    private $id = null;

    public function __initialize() {
        $this->init_redis();
        $this->init_db('group');
    }

    public function addGroupInfo($data) {
        $data = array_merge($data, array('id' => $this->iniId(), 'dateline' => SYS_TIME));
        if ($this->redis->hMset($this->getInfoKey($this->id), $data) !== false) {
            $newInfo = $this->getNewInfo($data['gid'], $data['uid']);
            if ($this->redis->zAdd($this->getGroupKey($data['gid']), SYS_TIME, $this->id) !== false) {
                return array_merge(array($this->id => $data), $newInfo);
            }
            return array();
        } else {
            return array();
        }
    }

    private function iniId() {
        $this->id = $this->redis->incr('gtid');
        return $this->id;
    }

    private function getInfoKey($id) {
        if (empty($id)) {
            return "group:" . $this->id;
        }
        return "group:" . $id;
    }

    private function getGroupKey($gid) {
        return "gtinfo:" . $gid;
    }

    public function getNewInfo($gid, $uid = '') {
        $ids = $this->redis->zRangeByScore($this->getGroupKey($gid), SYS_TIME - 30, SYS_TIME) ? : array();
        $arr = array();
        foreach ($ids as $id) {
            $infos = $this->redis->hGetAll($this->getInfoKey($id));
            if ($uid && $infos['uid'] == $uid) {
                continue;
            }
            $arr[$id] = $infos;
        }
        return $arr;
    }

    public function getPageInfo($gid, $page = 1, $nums = 10) {
        $nowpage = ($page - 1) * $nums;
        $ids = $this->redis->zRevRange($this->getGroupKey($gid), $nowpage, $nowpage + $nums - 1) ? : array();
        $arr = array();
        foreach ($ids as $id) {
            $arr[$id] = $this->redis->hGetAll($this->getInfoKey($id));
        }
        return array('data' => $arr, 'count' => $this->getKeyCount($gid));
    }

    private function getKeyCount($gid) {
        return $this->redis->zSize($this->getGroupKey($gid));
    }

    public function getMyGroups($uid, $type = 'CUSTOM', $limit = 3) {
        $result = $this->db->from('group_membership')->where(array('uid' => intval($uid)))->select(array('gid'))->get()->result_array();
        $gids = array();
        foreach ($result as $r) {
            $gids[] = $r['gid'];
        }
        if (empty($gids))
            return array();
        $result = $this->db->from('group_info')->where("gid IN (" . implode(',', $gids) . ") AND source_type = '" . $type . "'")->order_by('id', 'desc')->limit($limit)->get()->result_array();
        $array = array();
        foreach ($result as $r) {
            $array[] = $r;
        }
        return $array;
    }

    /**
     * 获取用户所有的群组和子群
     *
     * @param $uid unknown_type       	
     */
    public function getUserGroup($uid) {
        $where = array('uid' => $uid);
        $field_groups = array('uid', 'gid');
        $field_subgroups = array('uid', 'sid');

        $result_groups = $this->db->from('group_membership')->where($where)->select($field_groups)->get()->result_array();

        $result_subgroups = $this->db->from('group_sub_membership')->where($where)->select($field_subgroups)->get()->result_array();

        /**
         * 循环添加有用数据：群名称，群类型
         */
        $array_group = array();
        $field = array('name', 'source_type');
       
        if ($result_groups) {
            foreach ($result_groups as &$result_group) {
                $obj_where = array('gid' => $result_group ['gid']);
                $obj_group = $this->db->from('group_info')->where($obj_where)->select($field)->get()->row_array();
                // 如果群组不存在，则忽略信息 ( 脏数据暂时处理方案 )
                if ( $obj_group ) {
                	$the_group ['roomid'] = $result_group ['gid'];
                	$the_group ['roomnick'] = $obj_group ['name'];
                	$the_group ['roomtype'] = '0';
                	
                	// 查询出群成员数
                	$if_where = array('gid' => $result_group ['gid']);
                	$if_field = array('member_counts');
                	$if_obj = $this->db->from('group_extend')->where($if_where)->select($if_field)->get()->row_array();
                	$the_group ['count'] = $if_obj['member_counts'];
                	array_push($array_group, $the_group);
                }
            }
        }

        if ($result_subgroups) {
            $field_sub = array('name', 'member_counts');
            foreach ($result_subgroups as &$result_subgroup) {
                $obj_subwhere = array('sid' => $result_subgroup ['sid']);
                $obj_subgroup = $this->db->from('group_sub_info')->where($obj_subwhere)->select($field_sub)->get()->row_array();
                $the_subgroup ['roomid'] = $result_subgroup ['sid'];
                $the_subgroup ['roomnick'] = $obj_subgroup ['name'];
                $the_subgroup ['roomtype'] = '1';
                
                // 子群成员数处理
                // $the_subgroup ['count'] = $obj_subgroup ['member_counts'];
                $num_subwhere = array('sid' => $result_subgroup ['sid']);
                $num_field_sub = array('id');
                $nums_subgroup = count( $this->db->from('group_sub_membership')->where($num_subwhere)->select($num_field_sub)->get()->result_array() ) ;
                $the_subgroup ['count'] = $nums_subgroup."";
                array_push($array_group, $the_subgroup);
            }
        }

        return $array_group;
    }

    /**
     * 查询父群内的子群列表
     * @param unknown_type $gid
     */
    public function getSubgroupByGroup($gid) {
        $where = array('gid' => $gid);
        $field = array('sid', 'name', 'creator');

        $result = $this->db->from('group_sub_info')->where($where)->select($field)->get()->result_array();

        $array_group = array();

        if ($result) {
            foreach ($result as $obj) {
                $the_group ['roomid'] = $obj ['sid'];
                $the_group ['roomnick'] = $obj ['name'];
                $the_group ['ownerid'] = $obj ['creator'];
                array_push($array_group, $the_group);
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
    public function getGroupMembers($id, $is_sub = false) {
        // 查询子群成员
        if ($is_sub) {
            // 查询群组成员
            $where = array('sid' => $id);
            $field = array('uid');

            $result = $this->db->from('group_sub_membership')->where($where)->select($field)->get()->result_array();
        } else {
            // 查询群组成员
            $where = array('gid' => $id);
            $field = array('uid');

            $result = $this->db->from('group_membership')->where($where)->select($field)->get()->result_array();
        }

        // 重构数据
        $array_group = array();
        foreach ($result as $obj) {
            array_push($array_group, $obj ['uid']);
        }

        return $array_group;
    }

    /**
     * 查询群信息
     * @param unknown_type $id
     */
    public function getGroupInfo($id) {
        $var_return = null;

        // 查询子群信息
        $field = array('name', 'creator', 'gid', 'member_counts');
        $where = array('sid' => $id);
        $obj = $this->db->from('group_sub_info')->where($where)->select($field)->get()->row_array();

        if ($obj) {
            $var_return['roomid'] = $id;
            $var_return['roomnick'] = $obj['name'];
            $var_return['roomtype'] = 0;
            $var_return['parentid'] = $obj['gid'];
            $var_return['ownerid'] = $obj['creator'];
            $var_return['count'] = $obj['member_counts'];
        } else {
            $field = array('name', 'creator');
            $where = array('gid' => $id);
            $obj = $this->db->from('group_info')->where($where)->select($field)->get()->row_array();

            if ($obj) {
                $var_return['roomid'] = $id;
                $var_return['roomnick'] = $obj['name'];
                $var_return['roomtype'] = 0;
                $var_return['parentid'] = 0;
                $var_return['ownerid'] = $obj['creator'];
                // 查询出群成员数
                $if_where = array('gid' => $id);
                $if_field = array('member_counts');
                $if_obj = $this->db->from('group_extend')->where($if_where)->select($if_field)->get()->row_array();
                $var_return['count'] = $if_obj['member_counts'];
            } else {
                return null;
            }
        }
        return $var_return;
    }
    
    /**
     * 检查用户是否在群组里面
     * @param unknown_type $gid
     * @param unknown_type $uid
     */
    public function checkGruopMember( $gid, $uid )
	{
		$field = array ( 'id' );
		$where = array ( 'uid' => $uid, 'gid' => $gid );
		
		$obj = $this->db->from( 'group_membership' )->where( $where )->select( $field )->get()->row_array();
		
		if ( $obj ) {
			return true;
		} else {
			return false;
		}
	}

}

?>