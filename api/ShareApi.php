<?php

/**
 * 转发接口
 * @author yangshunjun
 */
class ShareApi extends DkApi {

    protected $share;

    public function __initialize() {
        $this->share = DKBase::import('Share', 'share');
    }

    /**
     * 新增转发
     * 
     * @param string $object_type 分享类型
     * @param integer $first_tid  原作者的信息流编号
     * @param integer $parent_tid  转发的信息流编号
     * @param integer $tid        分享后新增的信息流编号
     * @param array $params       转发需要保存的其他数据，例如uid、头像、内容、dkcode等等
     */
    public function add($object_type, $first_tid, $parent_tid, $tid, $params) {
        if(!$object_type || !$first_tid || !$tid){
        	return false;
        }
    	return $this->share->add($object_type, $first_tid, $parent_tid, $tid, $params);
    }

    /**
     * 删除转发
     * 
     * @param string $share_type 分享类型
     * @param integer $tid        删除的信息流编号
     */
    public function del($share_type, $tid) {
    	
    	if(!$share_type || !$tid){
        	return false;
        }
        return $this->share->del($share_type, $tid);
    }

    /**
     * 取转发信息
     * 
     * @param string $object_type 分享类型
     * @param integer $tid  被转发的对象编号
     * @return integer count 总数
     * @return array data 记录数组
     */
    public function get($object_type, $tid) {
    	if(!$object_type || !$tid){
        	return false;
        }
        return $this->share->get($object_type, $tid);
    }

    /**
     * 分页获得数据
     * 
     * @param string $object_type 转发类型
     * @param integer $tid 被转发的对象编号
     * @param integer $page 当前页
     * @param integer $pagesize 每页数量
     */
    public function getPageList($object_type, $tid, $page, $pagesize = 8) {
        return $this->share->getPageList($object_type, $tid, $page, $pagesize);
    }

    /**
     * 获取转发数量 
     * 
     * @param string $object_type 分享类型
     * @param integer $tid 被转发的对象编号
     */
    public function getLen($object_type, $tid) {
        return $this->share->getLen($object_type, $tid);
    }
}