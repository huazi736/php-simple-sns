<?php
/**
 * 权限控制器
 * 
 * 获得权限列表
 * <code>
   $info = call_soap('purview','SystemPurview', 'getPurviewList', array('module' => 'album'));
   dump(json_decode($info['purview']));
   </code>
 * @author weijian
 * @version $Id: access.php 27166 2012-06-05 16:48:46Z guzb $
 */
class Access extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('accessmodel', '_class');
    }
    
    /**
     * 设置权限
     * 
     * @param string $type	对象类型，包括：album,video,blog,ask和用户资料字段名：base, private, contact, edu, job, school, teach, language, skill, book, life, interest
     * @param mix $object_id 对象编号
     * @param integer $access_type	对应的权限编号
     * @param string $access_content 自定义权限对应的用户端口号，以逗号分隔的字符串
     * @param object_type 1:公开；8：仅限自己； 4：好友； 3：粉丝； -1：自定义
     */
    public function set()
    {	
        $object_id = R('object_id');
        $permission = R('permission');
        
       
        $this->load->model('albummodel', 'album');
        
        $album_info = $this->album->getAlbums($this->uid, $object_id);
        $flag = false;
        if($album_info[0]['a_type'] == '0') {
        	$flag = $this->_class->set($object_id, $permission, $this->uid);
        }
        if($album_info[0]['object_type'] == 1 && $permission != 1) {
        	$this->album->albumSearchIndexDel($object_id);
        }
        
        if($album_info[0]['object_type'] != 1 && $permission == 1) {
        	$this->album->albumSearchIndex($object_id, 1);
        }
        
        
        $status = $flag ? 1 : 0;
        $this->ajaxReturn('', '', $status);
    }
}

/* End of file access.php */
/* Location: ./app/album/application/controllers/access.php */