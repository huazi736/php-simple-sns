<?php

class TimelineService extends DK_Service {
    
    //数据来源
    const DATA_FROM_INFO = 1;
    const DATA_FROM_APPS = 2;

    public function __construct() {
        parent::__construct();
        
        $this->init_redis();
        $this->helper('timeline');
    }
    
    public function test() {
        $this->redis->set('xxx', 'aaa');
        return $this->redis->get('xxx');
        return 'hello timeline';
    }

    /**
     * 添加信息到时间轴
     * @param type $data        信息的内容
     * @param type $premission  自定义权限时，用户ID列表
     * @return array 
     */
    public function addTimeline($data = array(), $permission = array()) {
        //设置添加时间轴信息的来源标记
        $data['from'] = isset($data['from']) ? $data['from'] : self::DATA_FROM_APPS;
        $data['from'] == self::DATA_FROM_APPS && $data['ctime'] = $data['dateline'];
        
        if (service('Info')->_strictCheckFields($data)) {
            //对来自应用的数据处理
            if ($data['from'] == 2 && isset($data['content'])) {
                $htmlres = htmlSubString($data['content'], 140);
                $data['content'] = $htmlres['0'];
            }
            
            //产生 Topic
            $topic = service('Info')->_addTopic($data, $permission);
            
            if ($topic === false) {
                return false;
            }
            
            if (!service('Info')->_checkSpecialType($data['type'])) {
                service('Info')->_add($topic, $permission);
            }
            
            //产生时间线
            $this->_createPoint($topic);
            return json_encode($topic);
        }
        return false;
    }

    /**
     * 更新时间轴上的信息
     * @param type $tid     Topic ID
     * @param type $time    定位时间
     * @return bool 
     */
    public function updateTimeline($tid, $time) {
        $res = $this->_updatePoint($tid, $time);

        //更新 Topic 的时间
        service('Info')->_updateSpecialKey($tid, 'ctime', $time);

        return $res;
    }

    /**
     * 更新时间轴上信息的突出显示状态
     * @param type $tid         Topic ID
     * @param type $highlight   显示状态：1 突出显示, 0 常规显示
     * @return bool
     */
    public function updateTimelineHighlight($tid, $highlight = 1) {
        return service('Info')->_updateSpecialKey($tid, 'highlight', $highlight);
    }

    /**
     * 删除时间轴上的信息
     * @param type $tid     Topic ID/ F(foreign) ID
     * @param type $type    F(foreign) 类型： blog, album ...
     * @return bool
     */
    public function removeTimeline($tid, $type = '') {
        if (!empty($type)) {
            //根据类型和 fid 获取 Topic ID, 此处传入的 tid 为 fid
            $tid = service('Info')->_getTidByMap($tid, $type);
        }
        $res = $this->_deletePoint($tid);
        //删除信息流
        if ($type != 'uinfo') {
            service('Info')->_delInfo($tid);
        }
        //删除 Topic
        if ($res) {
            $res2 = service('Info')->_delTopic($tid);
        } else {
            $res2 = false;
        }

        return $res && $res2;
    }

    /**
     * @author fbbin
     * @desc 解除人物关系后调用处理用户的数据
     * @param int $fromUid
     * @param int $toUid
     * @param int $relation
     * @todo test
     */
    public function delRelationsTopic($fromUid, $toUid, $relation = 1) {
        if (!($fromUid && $toUid && $relation) || !in_array($relation, array(1, 4))) {
            return false;
        }
        //解除好友关系
        if ((int) $relation == 1) {
            return service('Info')->_delRelationsTopic($fromUid, $toUid, $relation) && service('Info')->_delRelationsTopic($toUid, $fromUid, $relation);
        }
        //解除关注关系
        return service('Info')->_delRelationsTopic($fromUid, $toUid, $relation);
    }

    /**
     * 更新 Topic 信息
     * @param type $data    Topic 数据
     * @param batch 是否是批量更新
     * @return bool 
     * @todo test
     */
    public function updateTopic($data, $relations = array(), $batch = false) {
        !$batch && $data = array($data);
        $upstatus = true;
        $resultStatus = true;
        $permissionStatus = true;
        foreach ($data as $value) {
            if (!empty($value['fid']) && !empty($value['type'])) {
                if (isset($value['ctime'])) {
                    $upstatus = $this->updateCtimeByMap($value['fid'], $value['type'], $value['ctime']);
                    unset($value['ctime']);
                }
                if (isset($value['permission'])) {
                    $tid = service('Info')->_getTidByMap($value['fid'], $value['type']);
                    $permissionStatus = $this->updatePermission($tid, $value['permission'], $relations);
                    unset($value['permission']);
                }
                if (isset($value['dateline'])) {
                    $upstatus = $this->updateCtimeByMap($value['fid'], $value['type'], $value['dateline']);
                    unset($value['dateline']);
                }
            }
            $resultStatus = $upstatus && $permissionStatus;
            if (count($value) >= 3) {
                $resultStatus = service('Info')->_updateTopic($value, $relations) && $resultStatus;
            }
        }
        unset($data);
        return $resultStatus;
    }

    /**
     * @author fbbin
     * @desc 替换或者是添加 Topic 信息
     * @param array $data    Topic 数据
     * @param array $relations 关系人数据
     * @todo test
     */
    public function replaceTopic($data, $relations = array()) {
        if ($data['fid'] && $data['type'] && count($data) >= 3) {
            $topic = $this->getTopicByMap($data['fid'], $data['type']);
            if ($topic) {
                return $this->updateTopic($data, $relations);
            } else {
                return $this->addTimeline($data, $relations);
            }
        }
        return false;
    }

    /**
     * 更改 Topic 的 Hot 值
     * @param type $tid Topic ID
     * @param type $inc 步长
     * @return type 
     */
    public function updateTopicHot($tid, $inc = 1) {
        return service('Info')->_updateSpecialKey($tid, 'hot', $inc);
    }

    /**
     * 更新时间轴定位时间
     * @param type $fid     外键ID
     * @param type $type    外键类型
     * @param type $time    定位的时间戳
     * @return type 
     * @todo test
     */
    public function updateCtimeByMap($fid, $type, $time) {
        $tid = service('Info')->_getTidByMap($fid, $type);
        return $tid && $this->updateTimeline($tid, $time);
    }

    /**
     * @author fbbin
     * @desc 修改信息实体的权限值
     * @param intval $tid 
     * @param intval $newPermission
     * @param arrray $relationslist
     */
    public function updatePermission($tid, $newPermission, $relations = array()) {
        $topic = service('Info')->_getTopicByTid($tid);
        if (($topic['permission'] == $newPermission && $newPermission != -1 ) || ($newPermission == -1 && empty($relations))) {
            return false;
        }
        if ($tid && $newPermission) {
            $updateStatus = service('Info')->_updatePermission($tid, $newPermission, $relations);
            if ($updateStatus === false) {
                return false;
            }
            //更改topic的权限值
            $data = $topic;
            $data['dateline'] = $topic['ctime'];
            $data['permission'] = $newPermission;
            if ($newPermission == -1) {
                service('Info')->_updateSpecialKey($tid, 'relations', json_encode($relations));
                $data['relations'] = $relations;
            }
            $this->_deletePoint($tid) && $this->_createPoint($data);
            return service('Info')->_updateSpecialKey($tid, 'permission', $newPermission) && $updateStatus;
        }
        return false;
    }

    /**
     * @author fbbin
     * @desc 根据映射关系返回一条完整的信息实体
     * @param string $fid
     * @param string $type
     * @todo test
     */
    // array();
    // updateTopic($data)
    // service('Timeline')
    // array('fid' => 'aaa', 'type' => 'aaaa')
    public function getTopicByMap($fid, $type) {
        $results = array();
        if (is_array($fid)) {
            foreach ($fid as $id) {
                $results[$id] = $this->getTopicByMap($id, $type);
            }
            return json_encode($results);
        }
        return service('Info')->_getTopicByFidAndType($fid, $type);
    }

    /**
     * @author fbbin
     * @desc 根据TID返回一条完整的信息实体
     * @param string $fid
     * @param string $type
     */
    public function getTopicByTid($tid) {
        $results = array();
        if (is_array($tid)) {
            foreach ($tid as $id) {
                $results[$id] = $this->getTopicByTid($id);
            }
            return json_encode($results);
        }
        return service('Info')->_getTopicByTid($tid);
    }

    // ================================
    // Line Model
    // ================================

    /**
     * 更新有效年份
     * @param type $uid 用户ID
     * @param type $year 年份
     * @param type $plus 年份中的内容是增加还是减少: true 增加, false 减少
     * @return type 
     */
    public function _updateYears($uid, $year, $plus = true) {
        $key = 'timeline:' . $uid . ':years';
        $has = $this->redis->hExists($key, $year);
        if ($has) {
            if ($plus) {
                $this->redis->hIncrBy($key, $year, 1);
            } else {
                $has_year = $this->redis->exists('timeline:' . $uid . ':' . $year);
                if ($has_year) {
                    $this->redis->hIncrBy($key, $year, -1);
                } else {
                    $this->redis->hDel($key, $year);
                }
            }
        } else {
            $this->redis->hSet($key, $year, 1);
        }
    }

    /**
     * 更新年份下有效的月份
     * @param type $uid 用户ID
     * @param type $year 年份
     * @param type $month 月份
     * @param type $is_plus 月份中的内容是增加还是减少: true 增加, false 减少
     * @return type 
     */
    public function _updateMonths($uid, $year, $month, $plus = true) {
        $key = 'timeline:' . $uid . ':' . $year;
        $has = $this->redis->hExists($key, $month);
        if ($has) {
            if ($plus) {
                $this->redis->hIncrBy($key, $month, 1);
            } else {
                $has_month = $this->_existsMonthOfYear($uid, $year, $month);
                if ($has_month) {
                    $this->redis->hIncrBy($key, $month, -1);
                } else {
                    $this->redis->hDel($key, $month);
                }
            }
        } else {
            $this->redis->hSet($key, $month, 1);
        }
    }

    public function _existsMonthOfYear($uid, $year, $month) {
        $scopes = array('self', 'friend', 'custom');
        foreach ($scopes as $scope) {
            $has_month = $this->redis->exists('timeaxis:' . $uid . ':' . $scope . ':' . $year . $month);
            if ($has_month) {
                return true;
            }
        }
        return false;
    }

    // ================================
    // Axis Model
    // ================================

    public function _deletePoint($tid) {
        //获取Topic信息
        $topic = $this->redis->hMGet('Topic:' . $tid, array('uid', 'ctime', 'permission'));
        $uid = $topic['uid'];
        $ctime = $topic['ctime'];
        $permission = $topic['permission'];
        
        if (empty ($uid) || empty ($ctime) || empty ($permission)) {
            return false;
        }
        
        $year = date('Y', $ctime);
        $month = date('n', $ctime);
        
        $scopes = $this->_getScopesByPermission($permission);
        foreach ($scopes as $scope) {
            //删除Main Point
            $this->_deleteMainPoint($uid, $scope, $year, $tid);

            //删除Top Point
            $this->_deleteTopPoint($uid, $scope, $year, $tid);

            //删除Hot Point
            $this->_deleteHotPoint($uid, $scope, $year, $tid);

            //删除常规的时间轴Point
            $res = $this->_delete($uid, $scope, $ctime, $tid);
            if ($res === false) {
                return false;
            }

            //重建Top Points
            $this->_buildTopPoints($uid, $scope, $ctime, $tid);
        }

        //更新有效年份和月份
        $this->_updateMonths($uid, $year, $month, false);
        $this->_updateYears($uid, $year, false);

        return true;
    }

    public function _updatePoint($tid, $time) {
        //获取Topic信息，根据过去的时间进行年月的定位
        $topic = $this->redis->hMGet('Topic:' . $tid, array('uid', 'ctime', 'type', 'permission'));
        $uid = $topic['uid'];
        $ctime = $topic['ctime'];
        $type = $topic['type'];
        $permission = $topic['permission'];
        $old_year = date('Y', $ctime);
        $old_month = date('n', $ctime);
        $year = date('Y', $time);
        $month = date('n', $time);
        $scopes = $this->_getScopesByPermission($permission);
        
        if ($year . $month != $old_year . $old_month) {
            foreach ($scopes as $scope) {
                $this->_delete($uid, $scope, $ctime, $tid);
            }

            //更新有效月份
            $this->_updateMonths($uid, $old_year, $old_month, false);
            $this->_updateMonths($uid, $year, $month);
        }
        if ($year != $old_year) {
            //更新有效年份
            $this->_updateYears($uid, $old_year, false);
            $this->_updateYears($uid, $year);
        }

        foreach ($scopes as $scope) {
            if (service('Info')->_checkSpecialType($type)) {
                //更新特别年度热点索引
                $this->_updateMainPoint($uid, $scope, $old_year, $year, $time, $tid);
            }

            //更新常规的时间轴Point
            $res = $this->_set($uid, $scope, $time, $tid);
            if ($res === false) {
                return false;
            }

            //清除 Top,Hot Points
            $this->_deleteTopPoint($uid, $scope, $old_year, $tid);
            $this->_deleteHotPoint($uid, $scope, $old_year, $tid);
            //重建 Top Points
            $this->_buildTopPoints($uid, $scope, $time, $tid);
        }
        return true;
    }

    public function _createPoint($data) {
        $uid = $data['uid'];
        $tid = $data['tid'];
        $type = $data['type'];
        $time = $data['ctime'];
        $year = date('Y', $time);
        $month = date('n', $time);
        $permission = $data['permission'];
        $scopes = $this->_getScopesByPermission($permission);
        if (!$scopes) {
            return false;
        }
        
        //更新有效年份和月份
        $this->_updateYears($uid, $year);
        $this->_updateMonths($uid, $year, $month);

        foreach ($scopes as $scope) {
            $res = $this->_set($uid, $scope, $time, $tid);
            if ($res === false) {
                return false;
            }

            if (service('Info')->_checkSpecialType($type)) {
                $this->_addMainPoint($uid, $scope, $time, $tid);
            }
            $this->_buildTopPoints($uid, $scope, $time);
        }
        return true;
    }

    public function _buildTopPoints($uid, $scope, $time, $update_tid = false) {
        //生成Top Point的年份
        $year = date('Y', $time);
        //获取每月热点的数量
        $top_num = 1;

        //获取月份的热点需要的参数
        $sort_params = array(
            'by' => 'Topic:*->hot',
            'limit' => array(0, $top_num),
            'get' => array('Topic:*->tid', 'Topic:*->ctime', 'Topic:*->type'),
            'sort' => 'desc',
            'alpha' => true,
        );

        //删除月份过期的热点
        $this->redis->delete('timeaxis:' . $uid . ':' . $scope . ':' . $year . ':top');

        for ($month = 1; $month <= 12; $month++) {
            //获取月份的新热点
            $arr_hot = $this->redis->sort('timeaxis:' . $uid . ':' . $scope . ':' . $year . $month, $sort_params);
            //更新月份的热点
            for ($i = 0; $i < count($arr_hot); $i += 3) {
                $tid = $arr_hot[$i];
                $ctime = $arr_hot[$i + 1];
                //对特定tid进行时间更新
                if ($update_tid && $update_tid == $tid) {
                    $ctime = $time;
                }
                $type = $arr_hot[$i + 2];
                if (service('Info')->_checkSpecialType($type)) {
                    continue;
                }
                $this->_addTopPoint($uid, $scope, $ctime, $tid);
            }
        }

        $this->_buildHotPoints($uid, $scope, $year);
    }

    public function _buildHotPoints($uid, $scope, $year) {
        //合并Main和Top到Hot中
        $key_main = 'timeaxis:' . $uid . ':' . $scope . ':' . $year . ':main';
        $key_top = 'timeaxis:' . $uid . ':' . $scope . ':' . $year . ':top';
        $this->redis->delete('timeaxis:' . $uid . ':' . $scope . ':' . $year . ':hot');
        $this->redis->zUnion('timeaxis:' . $uid . ':' . $scope . ':' . $year . ':hot', array($key_main, $key_top), array(1, 1), 'min');
    }

    //删除热点列表中指定的 Topic
    public function _deleteHotPoint($uid, $scope, $year, $tid) {
        return $this->redis->zDelete('timeaxis:' . $uid . ':' . $scope . ':' . $year . ':hot', $tid);
    }

    public function _addMainPoint($uid, $scope, $time, $tid) {
        return $this->redis->zAdd('timeaxis:' . $uid . ':' . $scope . ':' . date('Y', $time) . ':main', $time, $tid);
    }

    public function _deleteMainPoint($uid, $scope, $year, $tid) {
        return $this->redis->zDelete('timeaxis:' . $uid . ':' . $scope . ':' . $year . ':main', $tid);
    }

    public function _updateMainPoint($uid, $scope, $old_year, $year, $time, $tid) {
        if ($year != $old_year) {
            $this->_deleteMainPoint($uid, $scope, $old_year, $tid);
        }
        return $this->redis->zAdd('timeaxis:' . $uid . ':' . $scope . ':' . $year . ':main', $time, $tid);
    }

    public function _addTopPoint($uid, $scope, $time, $tid) {
        return $this->redis->zAdd('timeaxis:' . $uid . ':' . $scope . ':' . date('Y', $time) . ':top', $time, $tid);
    }

    public function _deleteTopPoint($uid, $scope, $year, $tid) {
        return $this->redis->zDelete('timeaxis:' . $uid . ':' . $scope . ':' . $year . ':top', $tid);
    }

    public function _updateTopPoint($uid, $scope, $old_year, $time, $tid) {
        $res = $this->_deleteTopPoint($uid, $scope, $old_year, $tid);
        if (is_int($res) && $res == 1) {
            return $this->redis->zAdd('timeaxis:' . $uid . ':' . $scope . ':' . date('Y', $time) . ':top', $time, $tid);
        }
        return $res;
    }

    private function _set($uid, $scope, $time, $tid) {
        return $this->redis->zAdd('timeaxis:' . $uid . ':' . $scope . ':' . date('Y', $time) . date('n', $time), $time, $tid);
    }

    private function _delete($uid, $scope, $time, $tid) {
        return $this->redis->zDelete('timeaxis:' . $uid . ':' . $scope . ':' . date('Y', $time) . date('n', $time), $tid);
    }

    private function _getScopesByPermission($permission) {
        //未设置权限，默认为公开
        $permission = isset($permission) ? (int) $permission : 1;
        switch ($permission) {
            case -1: //自定义
                return array('custom');
                break;
            case 1: //公开
                return array('public', 'follower', 'friend');
                break;
            case 3: //粉丝
                return array('follower', 'friend');
                break;
            case 4: //好友
                return array('friend');
                break;
            case 8: //仅自己
                return array('self');
                break;
            default:
                return false;
        }
    }

}