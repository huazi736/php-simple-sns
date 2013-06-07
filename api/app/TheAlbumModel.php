<?php

class TheAlbumModel extends DkModel {

    public function __initialize() {
        if (!defined('USER_ALBUM')) {
            define('USER_ALBUM', 'user_album');
            define('USER_PHOTO', 'user_photo');
        }
        $this->init_db('album');
    }

    public function deleteWebAlbum($web_id) {
        if ($web_id <= 0) {
            return false;
        }

        $album_ids = array();
        $album_list = $this->getWebAlbumList($web_id);

        //$params = array(	array(), 2, $web_id, 2);
        //call_soap('ucenter', 'UserWebMenuCover', 'mergeImages', $params);

        if (!empty($album_list)) {
            foreach ($album_list as $v) {
                $album_ids[] = $v['id'];
            }
            if (!$this->batch_delete($album_ids)) {
                return false;
            }
        }

        return true;
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
        $update_data['is_delete'] = '0';
        $update_data['photo_count'] = '0';
        $this->db->where_in('id', $id_s);
        $this->db->update('user_album', $update_data);

        $photo_data = array('id');
        $this->db->select($photo_data);
        $this->db->where_in('aid', $id_s);
        $photo_query = $this->db->get('user_photo');
        $photo_list = $photo_query->result_array();

        if (!empty($photo_list)) {
            $this->db->where_in('aid', $id_s);
            $this->db->update('user_photo', array('is_delete' => '0'));
        }

        return true;
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
        $data = array('id');
        $this->db->select($data);

        $this->db->where('is_delete', '1');
        $this->db->where('web_id', $web_id);
        $query = $this->db->get('user_album');
        $list = $query->result_array();

        return $list;
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
        $aid = intval($aid);
        if (!$aid || !$type || !$uid) {
            return array();
        }
        if ($type == 'album') {
            $sql = " SELECT `uid`,`name`, `web_id`,`discription`, `photo_count`, `cover_id`,`object_type`,`object_content`  FROM `user_album` WHERE `id` = $aid AND `is_delete`=1 LIMIT 1";
        } elseif ($type == 'walbum') {
            $sql = " SELECT `uid`, `web_id`,`name`,`discription`, `photo_count`, `cover_id` FROM `user_album` WHERE `id` = $aid AND `is_delete`=1 LIMIT 1";
        }
        $albuminfo = array();
        $query = $this->db->query($sql);
        $albuminfo = $query->row_array();
        if (empty($albuminfo)) {
            return array();
        }
        $photo_info = $this->getPhotoInfo($albuminfo['cover_id'], 'album', $albuminfo['uid']);
        if ($type == 'album') { //个人相册权限
            if ($albuminfo['name'] == '端口配图') {
                $bool = true;
            } else {
                $bool = $this->isAllow($albuminfo['uid'], $uid, $albuminfo['object_type'], $albuminfo['object_content']);
            }
            if (!$bool) {
                return array();
            }
            $userinfo = service('User')->getUserInfo($albuminfo['uid'], 'uid', array('username', 'dkcode'));
            $author = $userinfo['username'];
            $dkcode = $userinfo['dkcode'];
        } else {
            $webinfo = service('interest')->get_web_info($albuminfo['web_id']);
            $author = $webinfo['name'];
            $dkcode = $webinfo['dkcode'];
        }
        if (!isset($photoInfo['img_f']))
            $photo_info['img_f'] = MISC_ROOT . 'img/default/album_default.png';
        $result = array(
            'author' => $author,
            'name' => $albuminfo['name'],
            'discription' => $albuminfo['discription'],
            'photo_count' => $albuminfo['photo_count'],
            'uid' => $albuminfo['uid'],
            'web_id' => $albuminfo['web_id'],
            'cover_id' => $albuminfo['cover_id'],
            'album_cover' => $photo_info['img_f'],
            'dkcode' => $dkcode,
        );
        if ($type == 'album') {
            $result['object_type'] = $albuminfo['object_type'];
            $result['object_content'] = $albuminfo['object_content'];
        }
        return $result;
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
        if ($uid == $action_uid) {
            return true;
        }
        switch ($object_type) {
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
                return service('Relation')->isFriend($action_uid, $uid);
                break;
            case 3: //粉丝可见
                return service('Relation')->isBothFollow($action_uid, $uid);
                break;
        }
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
        $uid = intval($uid);
        $albumList = $this->db->select('photo_count, name, id, cover_id, uid')->where(array('is_delete' => 1, 'uid' => $uid, 'web_id' => 0))->get(USER_ALBUM)->result_array();

        foreach ($albumList as $key => $singeAlbumInfo) {
            $photoInfo = $this->getPhotoInfo($singeAlbumInfo['cover_id'], 'album', $singeAlbumInfo['uid']);
            if (isset($photoInfo['img_f'])) {
                $albumList[$key]['album_cover'] = $photoInfo['img_f'];
            } else {
                $albumList[$key]['album_cover'] = MISC_ROOT . 'img/default/album_default.png';
            }
        }
        return $albumList;
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
        if (empty($pid) || empty($type) || empty($uid)) {
            return array();
        }
        $photoInfo = $this->db->select('uid, name, aid, type, groupname, filename, description, size, dateline, notes')->where(array('id' => $pid, 'is_delete' => 1))->get(USER_PHOTO)->row_array();
        if (empty($photoInfo['aid'])) {
            return array();
        }
        if ($type == 'album') {
            $albuminfo = $this->db->select("id, name, uid, web_id, object_type, object_content")->where(array('id' => $photoInfo['aid'], 'is_delete' => 1))->get(USER_ALBUM)->row_array();
        } else {
            $albuminfo = $this->db->select("id, name, uid, web_id, object_type, object_content")->where(array('id' => $photoInfo['aid'], 'is_delete' => 1))->get(USER_ALBUM)->row_array();
        }
        if ($type == 'album') { //个人相册权限
            if ($albuminfo['name'] == '端口配图') {
                $bool = true;
            } else {
                $bool = $this->isAllow($albuminfo['uid'], $uid, $albuminfo['object_type'], $albuminfo['object_content']);
            }
            if (!$bool) {
                return array();
            }
            $userinfo = service('User')->getUserInfo($albuminfo['uid'], 'uid', array('username', 'dkcode'));
            $author = $userinfo['username'];
            $dkcode = $userinfo['dkcode'];
        } else {
            $webinfo = service('interest')->get_web_info($albuminfo['web_id']);
            $author = $webinfo['name'];
            $dkcode = $webinfo['dkcode'];
        }

        $return = array(
            'author' => $author,
            'name' => $photoInfo['name'],
            'discription' => $photoInfo['description'],
            'dateline' => $photoInfo['dateline'],
            'uid' => $photoInfo['uid'],
            'group_name' => $photoInfo['groupname'],
            'filename' => $photoInfo['filename'],
            'notes' => $photoInfo['notes'],
            'pid' => $pid,
            'size' => $photoInfo['size'],
            'type' => $photoInfo['type'],
            'web_id' => $albuminfo['web_id'],
            'albumName' => $albuminfo['name'],
            'aid' => $albuminfo['id'],
            'dkcode' => $dkcode,
        );

        $config = getConfig('album', 'thumb_pic_sizes', 'noReturn');
        foreach ($config['size'] as $key => $size) {
            $return['img_' . $key] = $this->getImgPath($photoInfo['groupname'], $photoInfo['filename'], $photoInfo['type'], $key);
        }
        return $return;
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
        $albumInfo = $this->getAlbumInfo($aid, 'album', $uid);
        if (empty($albumInfo))
            return false;
        if (!empty($photoNum) && is_numeric($photoNum)) {
            $photoList = $this->db->select('id, name, type, groupname, filename, description, notes')->where(array('is_delete' => 1, 'aid' => $aid))->order_by('p_sort', 'desc')->limit($photoNum)->get(USER_PHOTO)->result_array();
        } else {
            $photoList = $this->db->select('id, name, type, groupname, filename, description, notes')->where(array('is_delete' => 1, 'aid' => $aid))->order_by('p_sort', 'desc')->get(USER_PHOTO)->result_array();
        }
        $config = getConfig('album', 'thumb_pic_sizes', 'noReturn');
        if (!$photoList)
            return false;
        foreach ($photoList as $numb => $photoInfo) {
            foreach ($config['size'] as $key => $size) {
                $photoList[$numb]['img_' . $key] = $this->getImgPath($photoInfo['groupname'], $photoInfo['filename'], $photoInfo['type'], $key);
            }
        }
        return $photoList;
    }

    /**
     * 得到图片路径
     * 
     * @author guzhongbin
     * @param string $group 组名
     * @param string $filename 文件名（不带后缀）
     * @param string $ext 文件后缀
     * @param string $thumb 缩略图名称，如果为空则表示原图
     */
    private function getImgPath($group, $filename, $ext, $thumb = null) {
        $filename = null === $thumb ? $filename : $filename . "_" . $thumb;
        return "http://" . config_item('fastdfs_domain') . "/" . $group . "/" . $filename . "." . $ext;
    }

    /*
     * 添加照片评论，使user_album中的is_comment变为1
     * 
     * @author guzhongbin
     * @param int $pid 照片id
     *
     */

    public function commentAdd($pid) {
        $re = $this->db->query("select is_comment from " . USER_PHOTO . " where id = $pid limit 1");
        $cheack = $re->row_array();
        if (isset($cheack['is_comment']) && !$cheack['is_comment']) {
            $up = $this->db->query("update " . USER_PHOTO . " set is_comment=1 where id = $pid");
        }
    }

    /*
     * 删除照片评论，使user_album中的is_comment变为0
     * 
     * @author guzhongbin
     * @param int $pid 照片id
     *
     */

    public function commentDelete($pid) {
        $up = $this->db->query("update " . USER_PHOTO . " set is_comment=0 where id = $pid");
    }

    /**
     * 设置相册权限
     * 
     * @author guzhongbin
     * @param mix $object_id 对象编号
     * @param mix $permission	对应的权限或自定义uid
     */
    public function setAlbumPermission($object_id, $permission) {
        if (is_numeric($permission) && $permission < 9) {
            $access_type = $permission;
            $access_content = '-1';
        } else {
            $access_type = -1;
            if (empty($permission)) {
                $permission = '0';
            }
            $access_content = $permission;
        }
        $params = array(
            'object_type' => $access_type,
            'object_content' => $access_content,
        );

        $result = $this->db->update(USER_ALBUM, $params, array('id' => $object_id));

        if (!$result) {
            return false;
        }
        return true;
    }

    /**
     * 获得图片后缀
     * 
     * @author guzhongbin
     * @param stirng $type
     */
    private function getImgType($type) {
        if (strpos($type, 'gif') !== false) {
            return 'gif';
        } elseif (strpos($type, 'png') !== false) {
            return 'png';
        } elseif (strpos($type, 'jpg') !== false) {
            return 'jpg';
        } elseif (strpos($type, 'jpeg') !== false) {
            return 'jpg';
        } else {
            return false;
        }
    }

    public function mdir($aimUrl) {
        $aimUrl = dirname($aimUrl);
        if (is_dir($aimUrl))
            return true;
        $aimUrl = str_replace('\\', '/', $aimUrl);
        $aimDir = '';
        $arr = explode('/', $aimUrl);
        foreach ($arr as $str) {
            $aimDir .= $str . '/';
            if (!file_exists($aimDir)) {
                mkdir($aimDir);
                chmod($aimDir, 0777);
            }
        }

        return true;
    }

    public function saveBuffImage($file, $size) {
        $type = $this->getImgType(end(explode('.', $file)));
        $name = current(explode('.', end(explode('/', $file))));

        $files = file_get_contents($file);
        $local_name = VAR_PATH . "/files/image/album/" . date("YmdHis") . mt_rand(1000, 9999) . "." . $type;

        if (!$this->mdir($local_name)) {
            exit('目录不存在！');
        }

        $file_length = file_put_contents($local_name, $files);

        if ($file_length <= 0) {
            exit("图片保存失败！");
        }
        $img_data = getimagesize($local_name);

        if (!(is_numeric($img_data[2]) && in_array($img_data[2], array(1, 2, 3)))) {
            exit("格式有误！");
        }

        $file_size = filesize($local_name);

        //上传原图到fdfs     
        $this->load->fastdfs('album', '', 'fdfs');
        if ($file_size > 1048576 || $img_data[0] > 1920 || $img_data[1] > 1920) {
            $this->config->load('album');
            $local_server_tmp_storage_path = $this->config->item('tmp_storage_path');
            $org_filename = rtrim($local_server_tmp_storage_path, "/") . "/" . date("YmdHis") . mt_rand(1000, 9999) . "." . $type;
            $image = get_image('default');
            $image->resize_ratio($file['tmp_name'], $org_filename, 1920, 1920, 75);
            $org_pic_info = $this->fdfs->uploadFile($org_filename, $pt['type']);

            @unlink($org_filename);
        } else {
            $org_pic_info = $this->fdfs->uploadFile($file['tmp_name'], $pt['type']);
        }

        //上传原图到fdfs     
        $this->load->fastdfs('album', '', 'fdfs');
        $org_pic_info = $this->fdfs->uploadFileByBuff($files, $type);

        //验证是否上传成功
        if (!is_array($org_pic_info) || !isset($org_pic_info['group_name']) || !isset($org_pic_info['filename'])) {
            exit("服务器忙，请重新上传！");
        }

        $filename = substr($org_pic_info['filename'], 0, strrpos($org_pic_info['filename'], "."));
        $groupname = $org_pic_info['group_name'];

        //生成缩略图
        $p_list = array(
            'groupname' => $pt['groupname'],
            'filename' => $pt['filename'],
            'type' => $pt['type']
        );
        $this->config->load('album');
        $photo_quality_cfg = $this->load->config('photo_quality');
        $photo_quality = $photo_quality_cfg['normal'];

        $thumb_pic = $this->thumbPic($local_name, $p_list, $photo_quality, 0);

        unlink($local_name);
        $id = $this->db->insert_id();

        //不成功的图片，写入日志和队列
        if (isset($thumb_pic['error_data']) && !empty($thumb_pic['error_data'])) {
            $error_data = array($id => $thumb_pic['error_data']);
            log_user_msg($uid, $error_data, '', 'ALBUM');

            $error_data = json_encode($error_data);
            $queue = get_httpsqs('album');
            $key = 'photo_queue_' . date('Ymd');
            $queue->put($key, $error_data);
        }


        //检查文件大小
        if ($file['size'] > 1048576 * 10) {
            return false;
        }
        $pt = array(); //返回数据
        $pt['size'] = $file['size'];
        $pt['type'] = getImgType(strtolower(substr($file['name'], (strrpos($file['name'], '.') + 1))));

        //上传原图到fdfs     
        $this->load->fastdfs('album', '', 'fdfs');
        if ($file['size'] > 1048576 || $img_data[0] > 1920) {
            $this->config->load('album');
            $local_server_tmp_storage_path = $this->config->item('tmp_storage_path');
            $org_filename = rtrim($local_server_tmp_storage_path, "/") . "/" . date("YmdHis") . mt_rand(1000, 9999) . "." . $type;
            $image = get_image('default');
            $image->resize_ratio($file['tmp_name'], $org_filename, 1920, 1920, 75);
            $org_pic_info = $this->fdfs->uploadFile($org_filename, $pt['type']);

            @unlink($org_filename);
        } else {
            $org_pic_info = $this->fdfs->uploadFile($file['tmp_name'], $pt['type']);
        }
        //验证是否上传成功
        if (!is_array($org_pic_info) || !isset($org_pic_info['group_name']) || !isset($org_pic_info['filename']))
            return false;

        $pt['filename'] = substr($org_pic_info['filename'], 0, strrpos($org_pic_info['filename'], "."));
        $pt['groupname'] = $org_pic_info['group_name'];
        $pt['name'] = str_replace(".{$pt['type']}", "", $file['name']);
        $this->config->load('album');
        $photo_quality_cfg = $this->config->item('photo_quality');
        $photo_quality = $photo_quality_cfg['normal'];

        //设置相册类型及缩略图配置信息
        $pic_conf = GetThumbConf($pic_type); //大  中  小  尺寸 配置
        $local_server_tmp_storage_path = config_item("tmp_storage_path"); //临时存储图片路径，要有可写权限
        $is_concurrent = true; //判断是否并发
        $org_pic_url = $org_pic_info['filename'];
        //修改文件上传配置由一维数组改为二维数组配置 2012-03-08
        if (is_array($pic_conf['size'])) {
            $image = get_image('default');
            $loc_name = trim(substr($pt['filename'], strrpos($pt['filename'], '/') + 1));
            $sizes = array();

            foreach ($pic_conf['size'] AS $key => $val) {
                $processing_result = false;
                while (!$processing_result) {
                    $dst = rtrim($local_server_tmp_storage_path, "/") . "/" . $loc_name . "_" . $key . "." . $pt['type'];
                    $processing_result = $image->$val['type']($src, $dst, $val['width'], $val['height'], $photo_quality);
                }
                //把生成的缩略图放入fdfs
                if (is_file($dst)) {
                    $sign = $this->fdfs->uploadSlaveFile($dst, $org_pic_url, "_" . $key, $pt['type'], array(), $pt['groupname']);
                    if (is_file($dst)) {
                        while (!is_array($sign)) {
                            $sign = $this->fdfs->uploadSlaveFile($dst, $org_pic_url, "_" . $key, $pt['type'], array(), $pt['groupname']);
                        }
                    }
                    //增加缩略图尺寸记录
                    if (is_array($sign)) {
                        $size = getimagesize($dst);
                        $sizes[$key] = array('w' => $size[0], 'h' => $size[1]);
                        $img_url['img_' . $key] = $this->getImgPath($pt['groupname'], $pt['filename'], $pt['type'], $key);
                    }
                } else {
                    $processing_result = $this->image->$val['type']($src, $dst, $val['width'], $val['height'], $photo_quality);
                    $sign = $this->fdfs->uploadSlaveFile($dst, $org_pic_url, "_" . $key, $pt['type'], array(), $pt['groupname']);
                    $size = getimagesize($dst);
                    $sizes[$key] = array('w' => $size[0], 'h' => $size[1]);
                }
                @unlink($dst);
            }
        }
        if ($is_concurrent) {
            @unlink($src);
        }
        $pt['photosizes'] = $sizes;
        $pt['img_url'] = $img_url;
        return $pt;
    }

    /**
     * 删除相册
     * 
     * @author guzhongbin
     * @param mix $aid 相册编号
     */
    function deleteAlbum($aid, $uid) {
        if (!$aid) {
            return false;
        }
        $aid = intval($aid);
        //删除相册(is_delete标记为0)
        $update_data['is_delete'] = '0';
        $this->db->where('id', $aid);
        $this->db->where('uid', $uid);
        $this->db->where('a_type', '0');
        $del_res = $this->db->update(USER_ALBUM, $update_data);
        if (!$del_res) {
            return false;
        }

        $data = array('id');
        $this->db->select($data);
        $this->db->where('aid', $aid);
        $this->db->where('uid', $uid);
        $p_query = $this->db->get(USER_PHOTO);
        $p_lists = $p_query->result_array();

        if ($p_lists) {
            //删除该相册内的照片(is_delete标记为0)
            $p_data['is_delete'] = '0';
            $this->db->where('aid', $aid);
            $this->db->where('uid', $uid);
            $this->db->update(USER_PHOTO, $p_data);
        }
        return true;
    }

    /**
     * 删除照片
     * 
     * @author guzhongbin
     * @param int $pid 照片编号
     */
    function deletePhoto($pid, $uid) {
        $pid = intval($pid);

        //判断该照片是否存在
        $sql = "SELECT aid FROM " . USER_PHOTO . "
                WHERE id = " . $pid . " AND uid = " . $uid . " AND is_delete = 1";
        $res = $this->db->query($sql);
        $photo_info = $res->row_array();
        if (!$photo_info)
            return false;

        //单张action(is_delete 标记为0)
        $p_data['is_delete'] = '0';
        $this->db->where('id', $pid);
        $p_res = $this->db->update(USER_PHOTO, $p_data);
        if (!$p_res)
            return false;

        //相册照片总数变更
        $update_sql = sprintf("UPDATE " . USER_ALBUM . " 
	                        SET photo_count = photo_count - 1 
	                        WHERE id = '%s' 
	                        LIMIT 1 ", $photo_info['aid']);
        $this->db->query($update_sql);

        //判断是否为封面
        $this->checkAlbumCover($photo_info['aid'], $pid, $uid);
        return true;
    }

    /**
     * 移动或删除照片时检查相册封面
     * 如果目标照片为相册封面，则重置相册封面
     * 
     * @author guzhongbin
     * @data   2012-03-26
     * @access public
     * @param string $aid 相册编号
     * @param string $pid 照片编号
     */
    private function checkAlbumCover($aid, $pid, $uid) {
        $aid = intval($aid);
        $pid = intval($pid);
        $album_info = $this->getAlbumInfo($aid, 'album', $uid);
        if ($album_info['cover_id'] == $pid) {
            $this->resetAlbumCover($aid, $uid);
        }
    }

    /**
     * 重置相册的封面
     * 如果有照片，则选择第一张照片，如果没有照片则为空
     * 
     * @author guzhongbin
     * @data   2012-03-26
     * @access public
     * @param string $aid
     * @param string $uid
     * 
     */
    private function resetAlbumCover($aid, $uid, $clean = 0) {
        if (!$aid || !$uid)
            return false;
        if ($clean) {
            $update_data['cover_id'] = '';
        } else {
            $photo_list = $this->getPhotoList($aid, $uid);
            if ($photo_list) {
                $first = reset($photo_list);
                $update_data['cover_id'] = $first['id'];
            } else {
                $update_data['cover_id'] = '';
            }
        }
        $this->db->where('id', $aid);
        $update_res = $this->db->update(USER_ALBUM, $update_data);
        if (!$update_res)
            return false;

        return true;
    }

}