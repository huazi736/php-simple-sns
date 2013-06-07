<?php

/**
 * 收藏接口类
 *
 * @author zhoulianbo
 * @date 2012-7-12
 */
class FavoriteApi extends DkApi {

    protected $favModel;

    /**
     * 
     * 判断是否收藏
     * @param integer $object_id   对象id
     * @param string  $object_type 对象类型
     * + 'blog' => '日志', 
     * + 'photo' => '照片', 
     * + 'video' => '视频', 
     * + 'album' => '相册',
     * + 'web_blog' => '网页日志', 
     * + 'web_photo' => '网页照片', 
     * + 'web_video' => '网页视频', 
     * + 'web_album' => '网页相册',
     * @param integer $uid 用户id
     * @return boolean
     */
    public function checkFavorite($object_id, $object_type, $uid) {
        $this->favModel = DKBase::import('TheFavorite', 'favorite');
        return $this->favModel->checkFavorite($object_id, $object_type, $uid);
    }

    /**
     * delFavByWebid
     * 删除网页的所有收藏
     * 
     * @param integer $web_id 网页ID
     * @return boolean
     */
    public function delFavByWebid($web_id) {
        $this->favModel = DKBase::import('TheFavorite', 'favorite');
        return $this->favModel->delFavByWebid($web_id);
    }

    /**
     * delFav
     * 删除模块或者网页的所有收藏
     * 
     * @param integer $object_id   对象ID
     * @param string  $object_type 对象类型
     * @param integer $web_id 网页ID
     * @return integer|boolean
     */
    public function delFav($object_id, $object_type) {
        $this->favModel = DKBase::import('TheFavorite', 'favorite');
        return $this->favModel->delFav($object_id, $object_type);
    }

}