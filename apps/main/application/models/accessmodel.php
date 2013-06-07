<?php
/**
 * 权限
 * 
 * 权限类型说明
 * 0	=>	自定义
 * 1	=>	公开
 * 2	=>	自己
 * 3	=>	好友
 * 
 * @author weijian
 * @version $Id: accessmodel.php 3479 2012-02-09 05:47:52Z weij $
 */
class accessmodel extends CI_Model
{
    /**
     * 关系对应的表
     * 
     * @var array
     */
    protected $_tabel = array(
        'album'	=>    ACCESS_ALBUM,
        'video'	=>    ACCESS_VIDEO,
        'blog'	=>    ACCESS_BLOG,
        'ask'	=>    ACCESS_ASK,
        'edit'	=>    ACCESS_EDIT,
    );
    
    /**
     * 普通的对象类型
     * 
     * @var array
     */
    protected $_normal_type = array('album', 'video', 'blog', 'ask');
    
    /**
     * 资料的对象类型
     * 
	 * @var array 
     */
    protected $_edit_type = array('base', 'private', 'contact', 'edu', 'job', 'school', 'teach', 'language', 'skill', 'book', 'life', 'interest', 'project');
    
    /**
     * 用户的好友
     * 
     * @var array
     */
    protected $_user_friends = array();
    
    /**
     * 用户的熟人
     * 
     * @var array
     */
    protected $_user_follows = array();
    
    /**
     * 设置权限
     * 
     * @author weijian
     * @param string $type	对象类型，包括：album,video,blog,ask
     * @param mix $object_id 对象编号
     * @param mix $permission	对应的权限编号或者自定义权限的端口号
     */
    public function set($type, $object_id, $permission)
    {
        if(is_numeric($permission) && in_array($permission, array(1,2,3))){
		    $access_type = $permission;
		    $access_content = '';
		}else{
		    $access_type = 0;
		    if($permission == '0'){
		        $permission = '';
		    }
		    $access_content = $permission;
		}
        $type = strtolower($type);
        if(in_array($type, $this->_normal_type)){
            return $this->_set($type, $object_id, $access_type, $access_content);
        }elseif(in_array($type, $this->_edit_type)){
            return $this->_setEdit($type, $object_id, $access_type, $access_content);
        }else{
            return false;
        }
    }
    
    /**
     * 获得权限的json字符串
     * 
     * @author weijian
     * @param integer $access_type	对应的权限编号
     * @param string $access_content 自定义权限对应的用户端口号
     */
    protected function getObjectAccess($access_type, $access_content)
    {
        $object_access = array();
        $object_access['type'] = $access_type;
        if($access_type == 0 && !empty($access_content)){
            $object_access['content'] = explode(',', $access_content);
        }
        return json_encode($object_access);
    }
    
    /**
     * 设置普通的权限
     * 
     * @author weijian
     * @param string $type	对象类型
     * @param mix $object_id 对象编号
     * @param integer $access_type	对应的权限编号
     * @param string $access_content 自定义权限对应的用户端口号
     */
    protected function _set($type, $object_id, $access_type, $access_content)
    {
        $sql = "SELECT id FROM {$this->_tabel[$type]}
        		WHERE object_id = ?";
        $res = $this->db->query($sql, array($object_id));
        $row = $res->row_array();
        if(isset($row['id']) && $row['id']){
            $params = array(
                'object_type'	    =>    $access_type,
                'object_content'	=>    $access_content,
            );
            $query = $this->db->update($this->_tabel[$type], $params, array('id' => $row['id']));
        }else{
            $params = array(
                'object_id'		    =>    $object_id,
                'object_type'	    =>    $access_type,
                'object_content'	=>    $access_content,
            );
            $query = $this->db->insert($this->_tabel[$type], $params);
        }
        return $query;
    }
    
    /**
     * 设置资料的权限
     * 
     * @author weijian
     * @param string $field	设置字段
     * @param mix $uid 用户ID
     * @param integer $access_type	对应的权限编号
     * @param string $access_content 自定义权限对应的用户端口号
     */
    protected function _setEdit($field, $uid, $access_type, $access_content)
    {
        $sql = "SELECT id FROM {$this->_tabel['edit']}
        		WHERE object_id = ?";
        $res = $this->db->query($sql, array($uid));
        $row = $res->row_array();
        if(isset($row['id']) && $row['id']){
            $params = array(
                $field	=>    $this->getObjectAccess($access_type, $access_content),
            );
            $query = $this->db->update($this->_tabel['edit'], $params, array('id' => $row['id']));
        }else{
            $params = array(
                'object_id'		=>    $uid,
                $field        	=>    $this->getObjectAccess($access_type, $access_content),
            );
            $query = $this->db->insert($this->_tabel['edit'], $params);
        }
        return $query;
    }
    
    /**
     * 检查是否有访问权限
     * 目前假设object_id均为同一个作者
     * 
     * @author weijian
     * @param string $type	对象类型
     * @param mix $object_id 对象编号
     * @param string $dkcode 访问者的用户编号，一般为常量UID
     * @param string $action_uid 被访问者的用户编号，一般为常量ACTION_UID
     */
    public function isAllow($type, $object_id, $uid = UID, $action_uid = ACTION_UID)
    {
        $object_ids = is_array($object_id) ? $object_id : (array) $object_id;
        $return = array();
        if($uid == $action_uid){
            foreach($object_ids as $id){
                $return[] = $id;
            }
        }else{
            $dkcode = getUserDK($uid);
            $object_list = $this->getObject($type, $object_ids);
            foreach($object_ids as $id){
                if(in_array($type, $this->_edit_type)){
                    if(empty($object_list) || !isset($object_list[$id])){
                        switch($type){
                            //公开
                            case 'base':
                            case 'interest':
                                $return[] = $id;
                                break;
                            //仅限自己
                            case 'private':
                            case 'contact':
                                break;
                            //朋友
                            default:
                                if(in_array($action_uid, $this->getUserFriends($uid))){
                                    $return[] = $id;
                                }
                        }
                    }else{
                        switch($object_list[$id]['type']){
                            case 0:
                                if(in_array($dkcode, $object_list[$id]['content'])){
                                    $return[] = $id;
                                }
                                break;
                            case 1:    //公开
                                $return[] = $id;
                                break;
                            case 2:    //自己
                                //已处理
                                break;
                            case 3:    //好友
                                if(in_array($uid, $this->getUserFriends($action_uid))){
                                    $return[] = $id;
                                }
                                break;
                        }
                    }
                }else{
                    if(empty($object_list) || !isset($object_list[$id])){
                        continue;
                    }
                    switch($object_list[$id]['type']){
                        case 0:
                            if(in_array($dkcode, $object_list[$id]['content'])){
                                $return[] = $id;
                            }
                            break;
                        case 1:    //公开
                            $return[] = $id;
                            break;
                        case 2:    //自己
                            //已处理
                            break;
                        case 3:    //好友
                            if(in_array($uid, $this->getUserFriends($action_uid))){
                                $return[] = $id;
                            }
                            break;
                    }
                }
            }
        }
        return is_array($object_id) ? $return : in_array($object_id, $return);
    }
    
    /**
     * 得到用户的好友列表
     * 
     * @param string $uid 用户编号
     */
    protected function getUserFriends($uid)
    {
        if(!isset($this->_user_friends[$uid])){
            $sql = "SELECT f_usr_uid,f_friend_uid FROM ".FRIENDS."
            		WHERE f_usr_uid = ? OR f_friend_uid = ?";
            $res = $this->db->query($sql, array($uid, $uid));
            $list = array();
            foreach($res->result_array() as $item){
                $list[] = $item['f_usr_uid'] == $uid ? $item['f_friend_uid'] : $item['f_usr_uid'];
            }
            $this->_user_friends[$uid] = $list;
        }
        return $this->_user_friends[$uid];
    }
    
	/**
     * 得到用户的熟人列表
     * 
     * @param string $uid 用户编号
     * @param integer $status 状态 1为互相关注
     */
    protected function getUserFollows($uid, $status = null)
    {
        $key = empty($status) ? $uid : $uid."_".$status;
        if(!isset($this->_user_follows[$key])){
            $sql = "SELECT followed_uid FROM ".FOLLOWS."
            		WHERE follow_uid = ?";
            if($status){
                $status = intval($status);
                $sql .= " and status = '{$status}'";
            }
            $res = $this->db->query($sql, array($uid));
            $list = array();
            foreach($res->result_array() as $item){
                $list[] = $item['followed_uid'];
            }
            $this->_user_follows[$key] = $list;
        }
        return $this->_user_follows[$key];
    }
    
    /**
     * 获得用户所有的熟人和朋友
     * 
     * @author weijian
     * @param string $uid 用户编号
     * @param integer $status 如果为空，则仅是我关注的人，如果为1则是熟人
     */
    public function getAllFriends($uid, $status = 1)
    {
        //朋友
        $friends = $this->getUserFriends($uid);
        //互相关注
        $follows = $this->getUserFollows($uid, $status);
        $all = array_merge($friends, $follows);
        //去掉重复的用户
        $user_list = array_unique($all);
        $sql = "SELECT usr_id, usr_duankou, usr_name, usr_lastname FROM ".USERS."
        		WHERE usr_id in ('".implode("','", $user_list)."')";
        $res = $this->db->query($sql);
        $list = array(
            'status'	=>    1,
            'msg'	=>    '',
            'data'	=>    array(),
        );
        foreach($res->result_array() as $item){
            $list['data'][] = array(
                'usr_id'		=>    $item['usr_id'],            
                'avatar_img'	=>    getUserAvatar($item['usr_id']),
                'usr_duankou'	=>    $item['usr_duankou'],
                'username'	    =>    $item['usr_lastname'].$item['usr_name']
            );
        }
        return $list;
    }
    
    /**
     * 获得对象的权限列表
     * 
     * @author weijian
     * @param string $type	对象类型
     * @param array $object_ids 对象编号
     */
    protected function getObject($type, $object_ids)
    {
        $return = array();
        if(in_array($type, $this->_normal_type)){
            $sql = "SELECT object_id,object_type, object_content FROM {$this->_tabel[$type]}
            		WHERE object_id in ('".implode("','", $object_ids)."')";
            $res = $this->db->query($sql);
            foreach($res->result_array() as $item){
                $return[$item['object_id']] = array(
                    'type'	    =>    $item['object_type'],
                    'content'	=>    explode(',', $item['object_content']),
                );
            }
        }elseif(in_array($type, $this->_edit_type)){
            $sql = "SELECT object_id, {$type} FROM {$this->_tabel['edit']}
            		WHERE object_id in ('".implode("','", $object_ids)."')";
            $res = $this->db->query($sql);
            foreach($res->result_array() as $item){
                $return[$item['object_id']] = json_decode($item[$type], true);
            }
        }
        return $return;
    }
    
    /**
     * 获得对象的权限
     * 
     * @param string $type	对象类型，包括：album,video,blog,ask
     * @param mix $object_id 对象编号
     */
    public function getAccess($type, $object_id)
    {
        $object_ids = is_array($object_id) ? $object_id : (array) $object_id;
        $return = array();
        if(in_array($type, $this->_normal_type)){
            $sql = "SELECT object_id, object_type, object_content FROM {$this->_tabel[$type]}
            		WHERE object_id in ('".implode("','", $object_ids)."')";
            $res = $this->db->query($sql);
            foreach($res->result_array() as $item){
                $return[$item['object_id']] = array(
                    'object_type'    =>    $item['object_type'],
                    'object_content'	 =>    $item['object_content'],
                );
            }
        }elseif(in_array($type, $this->_edit_type)){
            $sql = "SELECT object_id, {$type} FROM {$this->_tabel['edit']}
            		WHERE object_id in ('".implode("','", $object_ids)."')";
            $res = $this->db->query($sql);
            $object_ids_info = $res->result_array();
            //已存在的编号
            $has_ids = array();
            foreach($object_ids_info as $item){
                $has_ids[] = $item['object_id'];
            }
            foreach($object_ids as $id){
                if(!in_array($id, $has_ids)){
                    $object_ids_info[] = array('object_id' => $id);
                }
            }
            foreach($object_ids_info as $item){
                if(!isset($item[$type]) || empty($item[$type])){
                    switch($type){
                        //公开
                        case 'base':
                        case 'interest':
                            $return[$item['object_id']]['object_type'] = 1;
                            break;
                        //仅限自己
                        case 'private':
                        case 'contact':
                            $return[$item['object_id']]['object_type'] = 2;
                            break;
                        //朋友
                        default:
                            $return[$item['object_id']]['object_type'] = 3;
                    }
                    $return[$item['object_id']]['object_content'] = '';
                }else{
                    $tmp = json_decode($item[$type], true);
                    $return[$item['object_id']] = array(
                        'object_type'    =>    $tmp['type'],
                        'object_content'	 =>    isset($tmp['content']) ? $tmp['content'] : '',
                    );
                }
            }
        }
        return is_array($object_id) ? $return : (isset($return[$object_id]) ? $return[$object_id] : array('object_type' => 1));
    }
}