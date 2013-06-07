<?php

/**
 * 修改索引接口 
 */
class RestorationSearchApi extends DkApi {

    protected $restoration;

    public function __initialize() {
        $this->restoration = DKBase::import('RestorationSearch', 'search');
    }

    public function test() {
        $keyword = array('id' => 1233, 'type' => 1);
        $res = $this->restorePhotoInfo($keyword);
        echo '<pre>';
        print_r($res);
        echo '</pre>';
    }

    /**
     * 用户粉丝数变更
     * 
     * Enter description here ...
     * @param int $user_id
     */
    public function restoreUserInfo($user_id) {//DONE
        return $this->restoration->restoreUserInfo($user_id);
    }

    /**
     * 网页粉丝数变更
     * 
     * Enter description here ...
     * @param int $web_id
     */
    public function restoreWebpageInfo($web_id) {
        return $this->restoration->restoreWebpageInfo($web_id);
    }

    /**
     * 状态修改
     * 
     * Enter description here ...
     * @param array $status_info
     */
    public function restoreStatusInfo(array $status_info) {//DONE
        return $this->restoration->restoreStatusInfo($status_info);
    }

    /**
     * 图片修改
     * 
     * Enter description here ...
     * @param array $photo_info
     */
    public function restorePhotoInfo(array $photo_info) {//DONE
        return $this->restoration->restorePhotoInfo($photo_info);
    }

    //图片
    private function photoChanged(array $photo_info) {
        return $this->restoration->photoChanged($photo_info);
    }

    /**
     * 转移图片
     * Enter description here ...
     * @param unknown_type $album_info
     */
    public function restorePhotoInfoTransfered(array $album_info) {
        return $this->restoration->restorePhotoInfoTransfered($album_info);
    }

    /**
     * 相册修改
     * 
     * Enter description here ...
     * @param array $album_info
     */
    public function restoreAlbumInfo(array $album_info) {//DONE
        return $this->restoration->restoreAlbumInfo($album_info);
    }

    /**
     * 视频修改
     * 
     * Enter description here ...
     * @param array $video_info
     */
    public function restoreVideoInfo(array $video_info) {//DONE
        return $this->restoration->restoreVideoInfo($video_info);
    }

    /**
     * 博客修改
     * 
     * Enter description here ...
     * @param int $blog_id
     */
    public function restoreBlogInfo($blog_id) {//DONE
        return $this->restoration->restoreBlogInfo($blog_id);
    }

    /**
     * 问答修改
     * 
     * Enter description here ...
     * @param int $qa_id
     */
    public function restoreAskInfo($qa_id) {//DONE
        return $this->restoration->restoreAskInfo($qa_id);
    }

    /**
     * 活动修改
     * 
     * Enter description here ...
     * @param array $event_info
     */
    public function restoreEventInfo(array $event_info) {
        return $this->restoration->restoreEventInfo($event_info);
    }

}

?>
