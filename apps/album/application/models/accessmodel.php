<?php
/**
 * 权限
 * 
 * 权限类型说明
 * -1 : 自定义
 * 1 : 公开
 * 3 : 粉丝
 * 4 : 好友
 * 8 : 自己
 * 
 * @author weijian
 * @version $Id: accessmodel.php 27182 2012-06-05 17:30:58Z guzb $
 */
class accessmodel extends MY_Model
{	
	public function __construct()
    {
        parent::__construct();
        
    }
     
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
     * 用户的粉丝
     * @var array
     */
    protected $_user_fans = array();
    
    /**
     * 设置权限
     * 
     * @author guzhongbin
     * @param mix $object_id 对象编号
     * @param mix $permission	对应的权限或自定义uid
     */
    public function set($object_id, $permission, $uid)
    {
        if(is_numeric($permission) && $permission < 9){
		    $access_type = $permission;
		    $access_content = '-1';
		}else{
		    $access_type = -1;
		    if(empty($permission)){
		        $permission = '0';
		    }
		    $access_content = $permission;
		}
        $params = array(
            'object_type'	    =>    $access_type,
            'object_content'	=>    $access_content,
        );
        
        $result = $this->db->update(USER_ALBUM, $params, array('id' => $object_id));
        
        if(!$result) {
        	return false;
        }
        $this->batchUpdateAblumPermission($object_id, $params, $uid);
        return true;
    }
    
    /**
     * 获得权限的json字符串
     * 
     * @author guzhongbin
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
     * 检查是否有访问权限
     * 目前假设object_id均为同一个作者
     * 
     * @author guzhongbin
     * @param integer $uid	访问者
     * @param integer $action_uid 被访问者
     * @param integer $object_type 权限类型
     * @param string $object_content 自定义端口号
     */
    public function isAllow($uid, $action_uid, $object_type, $object_content)
    {
        if($uid == $action_uid){
            return true;
        }
        switch($object_type){
            case -1:
                $object_content_array = explode(",", $object_content);
                return in_array($uid, $object_content_array);
                break;
            case 1:    //公开
                return true;
                break;
            case 3:    //粉丝
                return getSocial('fans') ? true : false;
                break;
            case 4:    //好友
                return getSocial('friend') ? true : false;
                break;
            case 8:
                return $uid == $action_uid;
                break;
        }
    }
    
	/**
     * 检查uid是否是action_uid的朋友
     * 
     * @param integer $uid 访问者
     * @param integer $action_uid 被访问者
     */
    protected function checkFriend($uid, $action_uid)
    {
        return service('Relation')->isFriend($action_uid, $uid);
    }
    
    /**
     * 检查uid是否是action_uid的粉丝
     * 
     * @param integer $uid 访问者
     * @param integer $action_uid 被访问者
     */
    protected function checkFans($uid, $action_uid)
    {
        return service('Relation')->isFollower($action_uid, $uid);
    }
    
	/**
	 * 更新相册信息流照片权限
	 * 
	 * @param author guzhongbin
	 * @param access public
	 * @data 2012-05-16
	 * 
	 * @param int $aid 相册id
	 * @param array $permissioninfo 权限信息
	 */
    public function batchUpdateAblumPermission($aid,$permissioninfo = null, $uid) 
    {	
    	//单张照片时间戳
     	$sql = "SELECT id, dateline, count(*) as num FROM ".USER_PHOTO."
        		WHERE aid = ?
        		GROUP BY dateline";
        $res = $this->db->query($sql, array($aid));
        $list = $res->result_array();
        $pids = array();
        foreach($list as $item){
        	if($item['num'] == 1) {
        		if(service('Timeline')->getTopicByMap($item['id'], 'photo', $uid)){
        			$pids[] = $item['id'];
        		}
        	}
        }
    	$infoflow_data = array();
    	
    	//单张照片权限信息流信息数组
    	foreach ($pids as $pid) {
    		$infoflow_data[] = array(
    							'fid' =>$pid, 
    							'type'=>'photo',
    							'uid' => $uid,
    							'permission' => $permissioninfo['object_type'],
    							);
	    }
	    //soap请求
	    //print_r($infoflow_data);
    	if($permissioninfo['object_type'] == -1) {
    		$permissionarray = explode(',', $permissioninfo['object_content']);
	    	$result = service('Timeline')->updateTopic($infoflow_data, $permissionarray, true);
	    	service('Timeline')->updateTopic(array(
				    							'fid' =>$aid, 
				    							'type'=>'album',
				    							'uid' => $uid,
				    							'permission' => $permissioninfo['object_type'],
				    							));	
    	}else {
    		$result = service('Timeline')->updateTopic($infoflow_data, array(), true);
    		service('Timeline')->updateTopic(array(
				    							'fid' =>$aid, 
				    							'type'=>'album',
				    							'uid' => $uid,
				    							'permission' => $permissioninfo['object_type'],
				    							));	
    		}
    	
    	
    	if(!$result) {
			return false;
		}
    	return $result;   	
    } 
}

/* End of file accessmodel.php */
/* Location: ./app/album/application/albummodels/accessmodel.php */