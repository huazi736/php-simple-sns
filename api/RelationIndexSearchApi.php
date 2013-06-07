<?php

class RelationIndexSearchApi extends DkApi {

    protected $relationIndex;

    public function __initialize() {
        $this->relationIndex = DKBase::import('RelationIndexSearch', 'search');
    }

    public function test() {
        $id = 386;
        $res = $this->deleteWebpage($id);
        echo '<pre>';
        PRINT_R($res);
        echo '</pre>';
    }

    public function addAFansForOne($follow_id) {//DONE
        return $this->relationIndex->addAFansForOne($follow_id);
    }

    public function removeAFansForOne($follow_id) {//DONE
        return $this->relationIndex->removeAFansForOne($follow_id);
    }

    //人名：注册与更新信息
    public function addOrUpdateBasalInfoOfPeople($user_info) {//DONE
        return $this->relationIndex->addOrUpdateBasalInfoOfPeople($user_info);
    }

    //人名修改
    public function onlyUpdatePeopleName($user_info) {//DONE
        return $this->relationIndex->onlyUpdatePeopleName($user_info);
    }

    //网页:注册与更新信息
    public function addOrUpdateWebpageinfo($web_info) {//DONE
        return $this->relationIndex->addOrUpdateWebpageinfo($web_info);
    }

    //状态:发布与更新状态信息
    public function addOrUpdateStatusInfo($status_info) {
        return $this->relationIndex->addOrUpdateStatusInfo($status_info);
    }

    //视频:添加或更新视频信息
    public function addOrUpdateVideoInfo($video_info) {//DONE
        return $this->relationIndex->addOrUpdateVideoInfo($video_info);
    }

    //博客:添加或更新博客信息
    public function addOrUpdateBlogArticleInfo($blog_info) {//DONE
        return $this->relationIndex->addOrUpdateBlogArticleInfo($blog_info);
    }

    //问答:添加或更新问答信息
    public function addOrUpdateQuestionAndAnswerInfo($qa_info) {//DONE
        return $this->relationIndex->addOrUpdateQuestionAndAnswerInfo($qa_info);
    }

    //活动:添加、修改活动
    public function addOrUpdateEventInfo($event_info) {//DONE
        return $this->relationIndex->addOrUpdateEventInfo($event_info);
    }

    //删除网页
    public function deleteWebpage($web_id) {//DONE
        return $this->relationIndex->deleteWebpage($web_id);
    }

    //删除状态
    public function deleteStatus($status_id) {//DONE
        return $this->relationIndex->deleteStatus($status_id);
    }

    public function deleteAStatusOfWeb($status_id) {//DONE
        return $this->relationIndex->deleteAStatusOfWeb($status_id);
    }

    //删除图片
    public function deletePhoto($photo_id) {//DONE
        return $this->relationIndex->deletePhoto($photo_id);
    }

    //删除相册
    public function deleteAlbum($album_id) {//DONE
        return $this->relationIndex->deleteAlbum($album_id);
    }

    //删除视频
    public function deleteVideo($video_id) {//DONE
        return $this->relationIndex->deleteVideo($video_id);
    }

    public function deleteAVideoOfWeb($video_id) {//DONE
        return $this->relationIndex->deleteAVideoOfWeb($video_id);
    }

    //删除博客文章
    public function deleteBlog($blog_id) {//DONE
        return $this->relationIndex->deleteBlog($blog_id);
    }

    //删除问答
    public function deleteAsk($ask_id) {//DONE
        return $this->relationIndex->deleteAsk($ask_id);
    }

    //删除活动
    public function deleteEvent($event_id) {//DONE
        return $this->relationIndex->deleteEvent($event_id);
    }

    public function deleteAEventOfWeb($event_id) {//DONE
        return $this->relationIndex->deleteAEventOfWeb($event_id);
    }

}