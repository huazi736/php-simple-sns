<?php

/**
 * 通知接口
 */
class NoticeApi extends DkApi {

    protected $notice;

    public function __initialize() {
        $this->notice = DKBase::import('TheNotice', 'notice');
    }

    /**
     * 批量删除通知
     * @author gefeichao
     * Enter description here ...
     * @param  $id  通知分类id
     */
    function del_noticeall($id = NULL) {
        return $this->notice->del_noticeall($id);
    }

    /**
     * @author gefeichao
     * 添加通知消息
     * @param $ntype  通知分类 个人通知为 1  网页通知为 网页id
     * @param $uid	  当前uid
     * @param $touid	  接收 uid
     * @param $btype    通知大分类
     * @param $stype    通知小分类 关于分类 请联系我给你添加
     * @param $temp     数组 array('name'="",'url'="");
     * @return array() 
     */
    public function add_notice($ntype="1", $uid=NULL, $touid=NULL, $btype=NULL, $stype=NULL, $temp=null) {
        return $this->notice->add_notice($ntype, $uid, $touid, $btype, $stype, $temp);
    }

    /**
     * 站内信、请求、通知总数加减
     *
     * @author gefeichao
     * @modifer liufeng
     * @date   2011/10/20
     * @access public
     * @param $uid 用户id
     * @param $coltype 执行的字段名
     */
    function setting($uid = null, $coltype = null, $num= null) {
        return $this->notice->setting($uid, $coltype, $num);
    }

    /**
     * 修改通知
     * @access public
     * @author gefeichao
     * @date 2012/05/14
     * @param $nid 通知id
     * @param  $date 修改时间
     * @return bool
     */
    function edit_notice($nid=null, $date=null) {
        return $this->notice->edit_notice($nid, $date);
    }

    /**
     * 发送通知过滤函数
     * @author gefeichao
     * @param $btype 通知设置大分类
     * @param $stype 通知小分类
     * @param $uid 用户uid
     */
    function gl_notice($btype = NULL, $stype = NULL, $uid = NULL) {
        return $this->notice->gl_notice($btype, $stype, $uid);
    }

}