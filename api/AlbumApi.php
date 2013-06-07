<?php

class AlbumApi extends DkApi {

    protected $album;

    public function __initialize() {
        $this->album = DKBase::import('TheAlbum', 'app');
    }

    public function deleteWebAlbum($web_id) {
        return $this->album->deleteWebAlbum($web_id);
    }

    /**
     * 批量删除相册
     *
     * @author vicente
     * @access public
     * @param int $id 相册ID
     * @return boolean
     */
    public function batch_delete($id_s) {
        return $this->album->batch_delete($id_s);
    }

    /**
     * 获取网页下所有相册信息
     * 
     * @author vicente
     * @access public
     * @param int $web_id 相册ID
     * @return boolean
     */
    public function getWebAlbumList($web_id) {
        return $this->album->getWebAlbumList($web_id);
    }

    /*
     * 收藏模块-相册数据
     * @date 2012-07-14
     * @access publc
     * @author guzhongbin
     * $aid int 相册id
     * type string ‘album'=>个人相册 or 'walbum'=>‘网页相册'
     * uid 访问者uid
     */

    public function getAlbumInfo($aid, $type, $uid) {
        return $this->album->getAlbumInfo($aid, $type, $uid);
    }

    /**
     * 检查是否有访问权限
     * 
     * @author guzhongbin
     * @param integer $action_uid 被访问者
     * @param integer $uid 访问者
     * @param integer $object_type 权限类型
     * @param string $object_content 自定义端口号
     * @return boolean
     */
    public function isAllow($action_uid, $uid, $object_type, $object_content) {
        return $this->album->isAllow($action_uid, $uid, $object_type, $object_content);
    }

    /*
     * 博客模块-相册列表数据
     * @date 2012-07-14
     * @access publc
     * @author guzhongbin
     * uid 访问者uid
     * 返回id=>相册id,cover_id=>相册封面id,uid=>相册创建用户,album_cover=>相册封面地址
     */

    public function getAlbumList($uid) {
        return $this->album->getAlbumList($uid);
    }

    /*
     * 收藏模块-单张照片数据
     * @date 2012-07-14
     * @access publc
     * @author guzhongbin 
     * uid 访问者uid
     * pid 照片id
     * type： album=>首页相册，walbum=>网页相册
     */

    public function getPhotoInfo($pid, $type, $uid) {
        return $this->album->getPhotoInfo($pid, $type, $uid);
    }

    /*
     * 收藏模块-照片列表数据
     * @date 2012-07-14
     * @access publc
     * @author guzhongbin 
     * uid 访问者uid
     * aid 相册id
     * photoNum 照片个数
     */

    public function getPhotoList($aid, $uid, $photoNum=null) {
        return $this->album->getPhotoList($aid, $uid, $photoNum);
    }

    /*
     * 添加照片评论，使user_album中的is_comment变为1
     * 
     * @author guzhongbin
     * @param int $pid 照片id
     *
     */

    public function commentAdd($pid) {
        return $this->album->commentAdd($pid);
    }

    /*
     * 删除照片评论，使user_album中的is_comment变为0
     * 
     * @author guzhongbin
     * @param int $pid 照片id
     *
     */

    public function commentDelete($pid) {
        return $this->album->commentDelete($pid);
    }

    /**
     * 设置相册权限
     * 
     * @author guzhongbin
     * @param mix $object_id 对象编号
     * @param mix $permission	对应的权限或自定义uid
     */
    public function setAlbumPermission($object_id, $permission) {
        return $this->album->setAlbumPermission($object_id, $permission);
    }

    public function mdir($aimUrl) {
        return $this->album->mdir($aimUrl);
    }

    public function saveBuffImage($file, $size) {
        return $this->album->saveBuffImage($file, $size);
    }

    /**
     * 删除相册
     * 
     * @author guzhongbin
     * @param mix $aid 相册编号
     */
    function deleteAlbum($aid, $uid) {
        return $this->album->deleteAlbum($aid, $uid);
    }

    /**
     * 删除照片
     * 
     * @author guzhongbin
     * @param int $pid 照片编号
     */
    function deletePhoto($pid, $uid) {
        return $this->album->deletePhoto($pid, $uid);
    }

}