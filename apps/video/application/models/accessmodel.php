<?php
/**
 * 权限
 * 
 * @author wangying qqyu
 * @version $Id: accessmodel.php
 */
/*
 * @param integer 数字
 * @param string  字符串
 * @param float   浮点
 * @param array   数组
 * @param
 * return boolean 布尔型
 * return array 布尔型
 * return bool 布尔型
*/
class accessmodel extends MY_Model
{    

	/**
	 *构造函数
	 *自动加载数据库
	 */
	public function __construct()
	{
		parent::__construct();
	}
    /**
     * 检查是否有访问权限
     * 
     * @author wangying
     * @param integer $action_uid 被访问者
     * @param integer $uid 访问者
     * @param integer $object_type 权限类型
     * @param string $object_content 自定义端口号
	 * @return boolean
     */
    public function isAllow($action_uid,$uid,$object_type,$object_content)
    {
        if($uid == $action_uid){
            return true;
        }
        switch($object_type){
            case -1://自定义可见
                $object_content_array = explode(",", $object_content);
                return in_array($uid, $object_content_array);
                break;
            case 1: //公开可见
                return true;
                break;
            case 8: //自己可见
                //上面已经判断了
                break;
            case 4: //好友可见
                return $this->checkFriend($action_uid, $uid);
                break;
            case 3: //粉丝可见
                return $this->checkFans($action_uid, $uid);
                break;
        }
    }
    
    /**
     * 检查uid是否是action_uid的朋友
     * @author wangying
     * @param integer $action_uid 被访问者
     * @param integer $uid 访问者
     * @return boolean
     */
    protected function checkFriend($action_uid, $uid)
    {
		return service('Relation')->isFriend($action_uid,$uid);
    }
     /**
     * 检查uid是否和action_uid是互相关注关系
     * @author wangying
     * @param integer $action_uid 被访问者
     * @param integer $uid 访问者
     * @return boolean
     */
    protected function checkBothFollow($action_uid, $uid)
    {
		return service('Relation')->isBothFollow("$action_uid","$uid");
    }   
    /**
     * 检查uid是否是action_uid的粉丝
     * @author wangying
     * @param integer $action_uid 被访问者
     * @param integer $uid 访问者
     * @return boolean
     */
    protected function checkFans($action_uid, $uid)
    {
		return service('Relation')->isFollower("$action_uid","$uid");
    }
    /**
     * 通过字段获取用户具体信息
     * @author wangying
     * @param string $field_value 字段值
     * @param string $field_key 字段名
     * @param array $return_fields 字段数组集
     * return array
     */
    public function getUserInfo($field_value,$field_key = 'uid',$return_fields = array())
    { 
		return service('User')->getUserInfo($field_value,$field_key,$return_fields);
    }
    /**
     * 检查uid和action_uid的关系
     * @author wangying
     * @param integer $action_uid 被访问者
     * @param integer $uid 访问者
     * @return int 用户2与用户的关系值：2：无关系 4：粉丝 6：相互关注 8：等对方接受好友请求 10：好友
     */
    public function getRelationWithUser($action_uid, $uid)
    {
        if($uid == $action_uid){
            return 5;
        }else{
			$relation = service('Relation')->getRelationStatus("$uid","$action_uid");
			switch ($relation) {
				case 10:
					return 4; //好友
					break;
				case 6:
				case 8:
					return 3; //互相关注
					break;
				case 4:
					return 2; //粉丝
					break;
				default:
					return 1; //陌生人
					break;
			}
        }
    }
	
	/**
	 * 入驻时间线
	 * @author qqyu
	 * @param array $data
	 * @param string $permision
	 */
    public function setTimeline($data,$permision)
    {		
		return service('Timeline')->addTimeLine($data,$permision);
    }
  	/**
	 * 删除时间线
	 * @author qqyu
	 * @param  int $vid
	 */  
    public function delTimeline($vid,$uid)
    {
		return service('Timeline')->removeTimeline($vid,$uid, 'video');
    }
    /**
	 * 更新时间线
	 * @author qqyu
	 * @param array $data
	 * @param string $permision
	 */
    public function updateTimeline($uid,$vid,$update,$permission)
    {
		if(isset($update['title'])) $data['title']=$update['title'];
    	if(isset($update['content'])) $data['content']=$update['content'];
    	if(isset($update['permission'])) $data['permission']=$update['permission'];
		$data['fid'] = $vid;
		$data['uid'] = $uid;
    	$data['type'] = 'video';
		return service('Timeline')->updateTopic($data,$permission);
    }
	/**
	 * 通知调用接口
	 * @author qqyu
	 * Enter description here ...
	 */
    function api_ucenter_notice_addNotice($type,$uid, $touid, $btype, $stype, $temp){
		return service('Notice')->add_notice($type,$uid, $touid, $btype, $stype, $temp);
	}
	/**
	 * 搜索更新接口(新增、修改、从非公开转为公开信息的视频)
	 * @author qqyu
	 * @param array $video_info
     * @return boolean
	 */
    function addVideoSearch($vd){
    	$pic_path = get_img_path($vd['video_pic'],'_1');
		$video_info=array(
			'id'     => $vd['id'],
			'uid'    => $vd['uid'],
			'uname'  => $vd['uname'],
			'time'   => $vd['dateline'],
			'title'  => $vd['title'],
			'cover_pic'   => $pic_path,
		    'discription' => $vd['discription'],
			'is_web' =>  0,
			'web_id' => ''
		);
		return service('RelationIndexSearch')->addOrUpdateVideoInfo($video_info);
	}
	/**
	 * 删除搜索中数据接口（删除或从公开转为非公开权限的个人视频）
	 * @author qqyu
	 * @param int $video_id
     * @return boolean
	 */
    function searchDeleteVideoId($video_id){
		return service('RelationIndexSearch')->deleteVideo($video_id);
	}
	/**
	 * 修改搜索中数据接口（从公开转为非公开权限的网页视频）
	 * @author qqyu
	 * @param array $video_info
     * @return boolean
	 */
    function restoreVideoInfo($vid){
    	$video_info=array(
	    	'id'    => $vid,
	    	'is_web'=> 0,
	    	'type'  => 'video'
    	);
		return service('RestorationSearch')->restoreVideoInfo($video_info);
    }
	/**
	 * 设置应用区图片接口
	 * @author qqyu
	 * @param int $uid 用户uid
	 * @param string $imgpath 例：group2/M00/0C/C7/wKgM8lAIzJ-r76qZAAAQ1Ykqhhk897.jpg(主图片)
	 */
	function setUserMenuImg($uid,$imgpath){
		if( $uid && $imgpath){
			$pic = explode('/', $imgpath,2);
			$pic_ico = explode('.', $pic[1],2);
			$imgpath = $pic_ico[0].'_ico.'.$pic_ico[1];//从图片_ico
			$group = $pic[0];
			return service('User')->setAppMenuCover($uid,'video',$imgpath,$group);
		}else{
			return false;
		}
	}
	/**
	 * 积分系统
	 * @author qqyu
	 * Enter description here ...
	 */
	function video_credit($type,$uid=null){
		if($type == 'add'){
			$type = true;
		}else{
			$type = false;
		}
		if($uid){
			return service('credit')->video($type,$uid); 
		}else{
			return service('credit')->video($type); 
		}		
	}
	/**
	 * 判断时间线是否具有某条视频数据
	 * @author wangying
	 * @param int $uid 用户uid
	 * @param int $vid 视频id
	 * @return boolean true 表示能修改
	 * getTopicByMap 取得的视频例：
	Array ( [content] => [width] => 1280 [tid] => 3068 [type] => video [uid] => 1000002912 [title] => wKgMy0-YDvTSI0EbAEJmI4hMpto013 [dateline] => 1343369216 [permission] => 1 [videourl] => oflaDemo|data/02/E5/wKgMy1ASYm-0L_XLAyqhr7BdnnM710.flv [dkcode] => 100033 [hot] => 1 [imgurl] => group2/M00/0D/8B/wKgM8lASL_3LIHfPAAEJOA40s6U987.jpg [ctime] => 1343899005 [fid] => 1069489485 [url] => http://video.duankou.dev/single/video/index.php?c=video&m=player_video&vid=1069489485 [from] => 2 [uname] => 王盈 [highlight] => 0 [height] => 720 ) 
	*/
	function getTimelineVideoInfo($vid,$uid){
		if( !$vid || !$uid) return false;
		$return = api('Timeline')->getTopicByMap($vid, 'video', $uid);
		return empty($return)? false: true;
	}
    /**
	 * 更新时间线     //数据整合用
	 * @author qqyu
	 */
    public function resetTimeline($uid,$vid,$img,$permission)
    {
    	$data['imgurl'] = $img;
		$data['fid'] = $vid;
		$data['uid'] = $uid;
    	$data['type'] = 'video';
		return service('Timeline')->updateTopic($data,$permission);
    }
}