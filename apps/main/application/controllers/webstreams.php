<?php

/**
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 * web 页面信息流
 * @author zhoutianliang
 */
class Webstreams extends DK_Controller {
    
    public function __construct() {
        parent::__construct();

    }
    //动态分类信息
    public function msgActionCate() {
        $tagid       = abs(intval($this->input->post('tagid')));
        $limit       = abs(intval($this->input->post('page'))) ? : 1;
        $a_uid       = abs(intval($this->input->post('action_uid')));
        $paramlastid = $this->input->post('lastid') ? : 0;
        $paramscore  = $this->input->post('score')  ? : 0;
        $uid = $this->uid;
        if($uid != $a_uid) {
            $this->ajaxReturn(array('data'=>null,'msg'=>'非法操作','status'=>0),'',0);
        }
        if(empty($tagid)) {
            $this->ajaxReturn(array('data'=>null,'msg'=>'非法操作','status'=>0),'',0);
        }
        $this->load->model('webstreammodel');
        $data = $this->webstreammodel->getMessage($uid,$tagid,$limit,$paramlastid,$paramscore);
        $data[0]['following'] = $this->newestFollowingWebpage($tagid,$this->uid);
        $this->ajaxReturn($data[0],$data['status']);
    }
    public function getTimeTopicCount() {
    	if(!$this->isAjax()) {
    		$this->ajaxReturn(array('data'=>null,'msg'=>'不是ajax请求','status'=>0),'',0);
    	}
    	if(!$this->uid) {
    		$this->ajaxReturn(array('data'=>null,'msg'=>'请先登录','status'=>0),'',0);
    	}
    	$tagid = $this->input->post('tagid');
    	$action_uid = $this->input->post('action_uid');
    	$ctime = $this->input->post('ctime') ? : SYS_TIME;
    	$this->load->model('webstreammodel');
    	$data = $this->webstreammodel->getTimeTopicCount($this->uid,$tagid,$ctime);
    	$this->ajaxReturn($data[0],$data['status']);
    }   
    public function getTimeTopicInfo() {
    	if(!$this->isAjax()) {
    		$this->ajaxReturn(array('data'=>null,'msg'=>'不是ajax请求','status'=>0),'',0);
    	}
    	if(!$this->uid) {
    		$this->ajaxReturn(array('data'=>null,'msg'=>'请先登录','status'=>0),'',0);
    	}
    	$tagid = $this->input->post('tagid');
    	$action_uid = $this->input->post('action_uid');
    	$ltime = $this->input->post('ltime') ? : SYS_TIME;
    	$lastid = $this->input->post('lastid') ? : 0;
    	$this->load->model('webstreammodel');
    	$data = $this->webstreammodel->getTimeTopicInfo($this->uid,$tagid,$ltime,$lastid);
    	$this->ajaxReturn($data[0],$data['status']);
    }
    /**
     * 首页右边关注网页列表
     *
     * 根据频道ID获取相应的关注网页
     *
     * @author zengmm
     * @date 2012/7/31
     */
    private function newestFollowingWebpage($tagid,$uid)
    {
    	// 频道ID
    	$channel_id = $tagid;
    	$this->load->model('followingmodel');
    	$newestFollowingWebpage = $this->followingmodel->getNewestFollowingWebpage($uid, $channel_id, 0, 7);
    
    	if ($newestFollowingWebpage) {
    		foreach ($newestFollowingWebpage as &$v) {
    
    			// 网页头像
    			$v['avatar'] = get_webavatar($v['aid'], 's');
    			// 网页链接
    			$v['href'] = mk_url('webmain/index/main', array('web_id' => $v['aid']));
    		}
    	}
    
    	$data = array(
    			'href' => mk_url('main/following/webFollowinglist', array('channel_id' => $channel_id)),
    			'data' => $newestFollowingWebpage
    	);
    
    	return $data;
    
    }
}

?>
