<?php

class VideoService extends DK_Service {

    function __construct() {
        parent::__construct();
		$this->init_db('video');
    }
	public function test() {
	   $vid = 1978768765;
	   $type = 2;
	   $uid = 1000002007;
	   print_r($this->getVideoInfo($vid,$type,$uid));exit;
	   if($this->getVideoInfo($vid,$type,$uid)){
			echo '可以访问';
	   }else{
			echo '不可访问';
	   };
    }
    /**
     * 收藏模块-视频数据请求
     * @author wangying
     * @param array $vid  视频id
     * @param array $type video模块1,web_video模块2
     * @param array $uid
	 * @return array
	 * 个人视频返回信息：dkcode_webid 为个人dkcode
	 Array ( [author] => 王盈 [dkcode_webid]=>100033 [title] => 2012-05-14 16:27:17 [discription] => [video_pic] => group2/M00/0A/11/wKgM8k_xkkHmlVl3AAA66LbRu1U220.jpg ) 
	 * 网页视频返回信息：dkcode_webid 为网页id
	 Array ( [author] => 5566 [dkcode_webid]=>1033 [title] => 2012-07-10 17:09:02 [discription] => [video_pic] => group2/M00/0B/D0/wKgM8k_78S2FisMRAAAlqTrFnTI724.jpg ) 
	* 没有信息：array()	
     */
    function getVideoInfo($vid,$type,$uid) {
		if(!$vid || !$type){
			return array();
		}
		if($type ==1){
			$sql = " SELECT `uid`,`title`,`discription`,`video_pic`,`object_type`,`object_content`,`lentime`  FROM `user_video` WHERE `id` = $vid AND `status`=1 LIMIT 1";
		}elseif($type ==2){
			$sql = " SELECT `web_id`,`title`,`discription`,`video_pic`,`lentime` FROM `web_video` WHERE `id` = $vid AND `status`=1 LIMIT 1";
		}
		$videoinfo = array();
		$query = $this->db->query($sql);
		$videoinfo = $query->row_array();
		if(empty($videoinfo)){
			return array();
		}
		if($type ==1){ //个人视频权限
			$bool = $this->isAllow($videoinfo['uid'],$uid,$videoinfo['object_type'],$videoinfo['object_content']);
			if(!$bool){
				return array();
			}
			$userinfo = service('User')->getUserInfo($videoinfo['uid'],'uid',array('username','dkcode'));
			$author = $userinfo['username'];
			$dkcode_webid = $userinfo['dkcode'];
		}else{
			$webinfo = service('interest')->get_web_info($videoinfo['web_id']);
			$author = $webinfo['name'];
			$dkcode_webid = $videoinfo['web_id'];
		}
		return array(
			'author'=>$author,
			'dkcode_webid'=>$dkcode_webid,
			'title'=>$videoinfo['title'],
			'discription'=>$videoinfo['discription'],
			'video_pic'=>$this->get_img_path($videoinfo['video_pic'],'_1'),
			'lentime'=>$videoinfo['lentime']
		);
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
				return service('Relation')->isFriend($action_uid,$uid);
                break;
            case 3: //粉丝可见
				return service('Relation')->isFollower($action_uid,$uid);
                break;
        }
    }
	/**
	 * 删除网页的全部视频接口
	 * @author qqyu
	 * @param unknown_type $web_id
	 */
	public function delWebVideoApi($web_id)
	{			
		if(!$web_id){
			return false;
		}
		$sql = 'select `id`,`web_id` from `web_video` where `web_id`='.$web_id.' limit 1';
		$query = $this->db->query($sql);
		$list = $query->row_array();
		if($list){
			$sql = 'update `web_video` set `status`=3 where `web_id`='.$web_id;
			$query = $this->db->query($sql);
			$rs = $this->db->affected_rows();
			if(empty($rs)){
				return false;
			} 
		}
		return true;
	}
	/**
	 * 删除网页的单个视频接口
	 * @author qqyu
	 * @param string $type
	 * @param int    $type_id
	 * @param int    $type
	 */
	public function delVideoApi($type,$type_id,$vid)
	{		
		if($type == 'video'){
			$table = 'user_video';
			$where = ' `uid`='.$type_id;
			$sql = 'select `id`,`uid` from '.$table.' where `id`='.$vid.' and '.$where;		
		}else{
			$table = 'web_video';
			$where = ' `web_id`='.$type_id;
			$sql = 'select `id`,`web_id` from '.$table.' where `id`='.$vid.' and '.$where;		
		}	
		$query = $this->db->query($sql);
		$list = $query->row_array();
		if($list){
			$sql = 'update '.$table.' set `status`=2 where `id`='.$vid.' and '.$where;
			$query = $this->db->query($sql);
			$rs = $this->db->affected_rows();
			if(empty($rs)){
				return false;
			} 
		}
		return true;
	}
	/**
	 * 修改个人视频权限接口
	 * @author qqyu
	 * @param  int $vid
	 *  $vid 视频id
		$power 权限值
		$custom 自定义权限值 （用户uid1，用户uid2，用户uid3）
	 */
	public function updateVideoPowerApi($vid,$power,$custom=null)
	{			
		if(!$vid){
			return false;
		}
		$sql = 'select `id` from `user_video` where `id`='.$vid.' limit 1';
		$query = $this->db->query($sql);
		$list = $query->row_array();
		if($list){
			$sql = 'update `user_video` set `object_type`='.$power.',`object_content`="'.$custom.'" where `id`='.$vid.' limit 1';
			$query = $this->db->query($sql);
			$rs = $this->db->affected_rows();
			if(!$rs){
				return false;
			} 
		}
		return true;
	}
	/**
	 *  获取图片主文件或者从文件地址 
	 *  $filename 例子：video10/M00/01/C0/wKgM8k-Q-imNhK8zAAAVoD6e72M251.jpg
	 *  $prefix 如果是从文件，必须加后缀名
	 */
	public function get_img_path($filename, $prefix=null)
	{
		if ($prefix) {
			$tmp = explode('.', $filename);
			return "{$tmp[0]}{$prefix}.{$tmp[1]}";
		}else {
			return "{$filename}";
		}
	}
}