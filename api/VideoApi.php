<?php

class VideoApi extends DkApi {

    protected $video;

    public function __initialize() {
        $this->video = DKBase::import('TheVideo', 'app');
    }

    public function test() {
		/*
        //$vid = 1427447443;$type = 1;$uid = 1000002913;
		//$vid = 1798398392;$type = 2;
		if($type == 1){
			print_r($this->getVideoInfo($vid, $type, $uid));
			////Array ( [author] => 秦庆玉 [dkcode_webid] => 100036 [title] => 2012-07-21 15:20 [discription] => [video_pic] => group2/M00/0C/E8/wKgM8lAKWELECdmDAAAL4_i9CjA070_1.jpg [lentime] => 1734 )
		}else{
			print_r($this->getVideoInfo($vid, $type));
			//Array ( [author] => Cookie [dkcode_webid] => 1365 [title] => 2012-07-21 14:00:38 [discription] => [video_pic] => group2/M00/0C/B2/wKgM8lAIs1zTrX1yAAAY6ve3zyA290_1.jpg [lentime] => 120 ) 
		}
        exit;
		*/
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
    function getVideoInfo($vid, $type, $uid=NULL) {
		if (!$vid || !$type) return array();
        $videoinfo = $this->video->getVideoInfo($vid, $type);
		if (empty($videoinfo)) return array();
        if ($type == 1) { //个人视频权限
            $bool = $this->video->isAllow($videoinfo['uid'], $uid, $videoinfo['object_type'], $videoinfo['object_content']);
            if (!$bool) return array();
            $userinfo = service('User')->getUserInfo($videoinfo['uid'], 'uid', array('username', 'dkcode'));
            $author = $userinfo['username'];
            $dkcode_webid = $userinfo['dkcode'];
        } else {
            $webinfo = service('interest')->get_web_info($videoinfo['web_id']);
            $author = $webinfo['name'];
            $dkcode_webid = $videoinfo['web_id'];
        }
        return array(
            'author' => $author,
            'dkcode_webid' => $dkcode_webid,
            'title' => $videoinfo['title'],
            'discription' => $videoinfo['discription'],
            'video_pic' => $this->video->get_img_path($videoinfo['video_pic'], '_1'),
            'lentime' => $videoinfo['lentime']
        );
    }

    /**
     * 删除网页的全部视频接口
     * @author qqyu
     * @param unknown_type $web_id
     */
    public function delWebVideoApi($web_id) {
		if (!$web_id) return false;
        return $this->video->delWebVideoApi($web_id);
    }

    /**
     * 删除网页的单个视频接口
     * @author qqyu
     * @param string $type
     * @param int    $type_id
     * @param int    $type
     */
    public function delVideoApi($type, $type_id, $vid) {
        return $this->video->delVideoApi($type, $type_id, $vid);
    }

    /**
     * 修改个人视频权限接口
     * @author qqyu
     * @param  int $vid
     *  $vid 视频id
      $power 权限值
      $custom 自定义权限值 （用户uid1，用户uid2，用户uid3）
     */
    public function updateVideoPowerApi($vid, $power, $custom=null) {
        return $this->video->updateVideoPowerApi($vid, $power, $custom);
    }

}