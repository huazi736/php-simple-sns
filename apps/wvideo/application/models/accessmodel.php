<?php
/**
 * +-------------------------------
 * service调用模型
 * +-------------------------------
 * @author wangying qqyu
 * @date <2012/3/2>
 * @version $Id: MY_Model.php
 */
class accessmodel extends MY_Model
{    	
	public function __construct(){
		parent::__construct();
	}
	/**
	 * 网页视频入驻时间线
	 * @author qqyu
	 * @param array $data
	 * @param string $permision
	 */
    public function setTimeline($data,$web_id)
    {
    	$tags = $this->getWebTags($web_id);
		 return service('WebTimeline')->addWebtopic($data,$tags);
    }
  	/**
	 * 网页视频删除时间线
	 * @author qqyu
	 * @param array $data
	 * @param string $permision
	 */  
    public function delTimeline($fid,$web_id)
    {	
    	$tags = $this->getWebTags($web_id);
        return service('WebTimeline')->delWebtopicByMap($fid,'video',$tags,$web_id);
    }
    /**
	 * 网页视频更新时间线
	 * @author qqyu
	 * @param int $vid
	 * @param string $permision
	 */
    public function updateTimeline($vid,$web_id,$title,$discription)
    {
    	$data = array(
			'fid' => $vid,
    		'pid' => $web_id,
			'content' => $title,
			'content' => $discription,
			'type' => 'video'
		);
        return service('WebTimeline')->updateWebtopicByMap($data);
    }
    /**
	 * 调取dkcode中网页的tags
	 * @author qqyu
	 * @param  int $web_id
	 */
    public function getWebTags($web_id)
    {
        return service('Interest')->get_web_category_imid($web_id);    
    }
	/**
	 * 通知调用接口
	 * Enter description here ...
	 */
    function api_ucenter_notice_addNotice($type,$uid, $touid, $btype, $stype, $temp){
		return service('Notice')->add_notice($type,$uid, $touid, $btype, $stype, $temp);
	}

	/**
	 * 搜索更新接口(新增、修改、从非公开转为公开信息的视频)
	 * @param array $video_info
     * @return boolean
	 */
    function addVideoSearch($vd){
   	$pic_path = get_img_path($vd['video_pic'],'_1');
   		$video_info = array(
			'id'     => $vd['id'],
			'uid'    => $vd['uid'],
			'uname'  => $vd['uname'],
			'time'   => $vd['dateline'],
			'title'  => $vd['title'],
			'cover_pic'   => $pic_path,
		    'discription' => $vd['discription'],
			'is_web' =>  1,
			'web_id' => $vd['web_id']
		);
		return service('RelationIndexSearch')->addOrUpdateVideoInfo($video_info);
	}
	/**
	 * 修改搜索中数据接口（从公开转为非公开权限的网页视频）
	 * @param array $video_info
     * @return boolean
	 */
    function restoreVideoInfo($id){
    	$video_info=array(
			'id'=>$id,
			'type'=>1
		);
		return service('RestorationSearch')->restoreVideoInfo($video_info);
    }
	/**
	 * 删除搜索中数据接口（删除或从公开转为非公开权限的网页视频）
	 * @param int $video_id
     * @return boolean
	 */
    function apiSearchDeleteWebVideoId($video_id){
		return service('RelationIndexSearch')->deleteAVideoOfWeb($video_id);
	}
	/**
	 * 设置应用区图片接口
	 * @param int $webid 网页id
	 * @param string $imgpath 例：group2/00/0C/C7/wKgM8lAIzJ-r76qZAAAQ1Ykqhhk897.jpg(主图片)
	 */
	function apisetUserMenuImg($webid,$imgpath){
		if( $webid && $imgpath){
			$pic = explode('/', $imgpath,2);
			$pic_ico = explode('.', $pic[1],2);
			$imgpath = $pic_ico[0].'_ico.'.$pic_ico[1];//从图片_ico
			$group = $pic[0];
			return service('Webpage')->setAppMenuCover($webid, 3, $imgpath, $group);
		}else{
			return false;
		}
	}
	/**
	 * 判断时间线是否具有某条视频数据
	 * @author wangying
	 * @param int $uid 用户uid
	 * @param int $vid 视频id
	 * @return boolean true 表示能修改
	 * getWebtopicByMap 取得的视频例：
	Array ( [type] => video [fid] => 1996586586 [uid] => 1000002912 [pid] => 1559 [dkcode] => 100033 [uname] => 视频 [title] => sample [content] => dddddddddddsdsdddddddddddddddddsdsdddd [imgurl] => group2/M00/0D/4F/wKgM8lAPoRXGLCVNAAAQ1Ykqhhk874.jpg [width] => 320 [height] => 192 [timedesc] => [dateline] => 1343201582 [from] => 4 [ctime] => 20120725153302 [tid] => 1440 [hot] => 0 [highlight] => 0 ) 
	*/
	function getWebtopicVideoInfo($vid,$web_id){
		if( !$vid || !$web_id) return false;
		$return = api('WebTimeline')->checkTopicExists($vid, 'video', $web_id);
		return $return? true: false;
	}
    /**
	 * 网页视频更新时间线        //数据整合用
	 * @author qqyu
	 */
    public function resetTimeline($vid,$web_id,$imgurl)
    {
    	$data = array(
			'fid' => $vid,
    		'pid' => $web_id,
			'imgurl' => $imgurl,
			'type' => 'video'
		);
        return service('WebTimeline')->updateWebtopicByMap($data);
		// service('WebTimeline')->getWebtopicByMap($data['fid'],'video',$data['pid']);
        //return service('WebTimeline')->updateWebtopicByMap($data);
    }
}