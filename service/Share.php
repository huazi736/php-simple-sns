<?php

/**
 * 转发接口
 * @author yangshunjun
 */
include_once('Comlike.php');
class ShareService extends DK_Service {

    public function __construct() {
        parent::__construct();
        $this->init_redis();
        $this->comlike = new ComlikeService();
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
    public function add($object_type = '', $first_tid = 0, $parent_tid = 0, $tid = 0, $params = array()) {
        $params['tid'] = $tid;
        $params['time'] = time();
        
        $flag = $this->comlike->add($object_type, $first_tid, $tid, $params); 
    	
        //添加分页辅助数据
        if('topic' == $object_type){
        	$key = $this->comlike->getKey('Stat', 'share_paging');
        	$this->comlike->delList($key, $first_tid);
        	$this->comlike->lpush($key, $first_tid); 
        }
        
        if($flag && $first_tid <> $parent_tid && $parent_tid > 0){
            return $this->comlike->add($object_type, $parent_tid, $tid, $params);
        }
        return $flag;
    }

    /**
     * 删除转发
     * 
     * @param string $object_type 分享类型
     * @param integer $tid        删除的信息流编号
     */
    public function del($object_type, $tid) {
    	$flag = $this->comlike->del($object_type, $tid);
        return $flag;
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
        return $this->comlike->get($object_type, $tid);
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
        return $this->comlike->getPageList($object_type, $tid, $page, $pagesize);
    }

    /**
     * 获取转发数量 
     * 
     * @param string $object_type 分享类型
     * @param integer $tid 被转发的对象编号
     */
    public function getLen($object_type, $tid) {
        return $this->comlike->getLen($object_type, $tid);
    }

//	redistest
    function redistest(){
    	return $this->comlike->Mytest();
    }
}