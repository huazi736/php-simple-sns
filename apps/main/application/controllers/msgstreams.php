<?php

/**
 * 获取信息个人 好友 信息流模型
 * @author zhoutianliang 
 * @date 2012.07.21
 */

class Msgstreams extends DK_Controller { 
    
	const TYPE_FANS = 'fansInfos';
	const TYPE_FRIS = 'frisInfos';
	private $page   = 0;
	public function __construct() {
		parent::__construct();
	}
	/**
	 * 取得关注页面的信息流
	 */
	public function followstream() {
		if(!$this->isAjax()) {
			$this->ajaxReturn(array('data'=>null,'msg'=>'不是ajax请求','status'=>0),'',0);
		}
		if(!$this->uid) {
			$this->ajaxReturn(array('data'=>null,'msg'=>'请先登录','status'=>0),'',0);
		}
		$action_uid = $this->input->post('action_uid') ? $this->input->post('action_uid')  : 0 ;
		if((int)$this->uid != (int)$action_uid) {
			$this->ajaxReturn(array('data'=>null,'msg'=>'非法操作','status'=>0),'',0);
		}
		$this->page  = $this->input->post('page') ? intval($this->input->post('page')) : 1;
		$paramlastid = $this->input->post('lastid') ? : 0;
		$paramscore  = $this->input->post('score')  ? : 0;
		$this->load->model('msgstreammodel');
		$data = $this->msgstreammodel->getMessage($this->uid,self::TYPE_FANS,$this->page,$paramlastid,$paramscore);
		$data[0]['following'] = $this->newestFollowing($this->uid);//取得关注
	    $this->ajaxReturn($data[0],$data['status']);
	}
	
	/**
	 * 取得好友信息流
	 */
	public function followFriStream() {
		if(!$this->isAjax()) {
			$this->ajaxReturn(array('data'=>null,'msg'=>'不是ajax请求','status'=>0),'',0);
		}
		if(!$this->uid) {
			$this->ajaxReturn(array('data'=>null,'msg'=>'请先登录','status'=>0),'',0);
		}
		$action_uid = $this->input->post('action_uid') ? $this->input->post('action_uid')  : 0 ;
		if((int)$this->uid != (int)$action_uid) {
			$this->ajaxReturn(array('data'=>null,'msg'=>'非法操作','status'=>0),'',0);
		}
		$this->page  = $this->input->post('page') ? intval($this->input->post('page')) : 1;
		$paramlastid = $this->input->post('lastid') ? : 0;
		$paramscore  = $this->input->post('score')  ? : 0;
		$this->load->model('msgstreammodel');
		$data = $this->msgstreammodel->getMessage($this->uid,self::TYPE_FRIS,$this->page,$paramlastid,$paramscore);
		$data[0]['friends'] = $this->newestFriend($this->uid);//取得好友
		$this->ajaxReturn($data[0],$data['status']);
	}
	/**
	 * 取得时间段是不是有新的数据产生，如果有返回总数 
	 */
	public function getTimeTopicCount() {
		if(!$this->isAjax()) {
			$this->ajaxReturn(array('data'=>null,'msg'=>'不是ajax请求','status'=>0),'',0);
		}
		if(!$this->uid) {
			$this->ajaxReturn(array('data'=>null,'msg'=>'请先登录','status'=>0),'',0);
		}
		$type  = $this->input->post('msgtype') ? :self::TYPE_FANS;
		$ctime = $this->input->post('ctime')   ? :SYS_TIME;
		$this->load->model('msgstreammodel');
		$data = $this->msgstreammodel->getTimeTopicCount($this->uid,$type,$ctime);
		$this->ajaxReturn($data[0],$data['status']);
	}
	/**
	 *取得时间段新产生的数据 
	 * 
	 */
	public function getTimeTopicInfo() {
		if(!$this->isAjax()) {
			$this->ajaxReturn(array('data'=>null,'msg'=>'不是ajax请求','status'=>0),'',0);
		}
		if(!$this->uid) {
			$this->ajaxReturn(array('data'=>null,'msg'=>'请先登录','status'=>0),'',0);
		}
		$type   = $this->input->post('msgtype') ? : self::TYPE_FANS;
		$ltime  = $this->input->post('ltime')   ? : SYS_TIME;
		$lastid = $this->input->post('lastid')  ? : 0; 
		$this->load->model('msgstreammodel');
		$data = $this->msgstreammodel->getTimeTopicInfo($this->uid,$type,$ltime,$lastid);
		$this->ajaxReturn($data[0],$data['status']);
	}
	/**
	 * 首页右边关注个人的列表
	 *
	 * @author zengmm
	 * @date 2012/7/31
	 */
	private function newestFollowing($uid)
	{
		$this->load->model('followingmodel');
		$following = $this->followingmodel->getFollowingsWithInfoByOffset($uid, true, 0, 7,$uid);
		if ($following) {
			foreach ($following as &$v) {
				// $v['id'] 用户UID
				$v['avatar'] = get_avatar($v['id'], 'mm');
	
				$v['href'] = mk_url('main/index/profile', array('dkcode'=>$v['dkcode']));
			}
		}
	
		$data = array(
				'href' => mk_url('main/following/followingList'),
				'data' => $following
		);
	
		return $data;
	}
	/**
	 * 获取用户最新的好友
	 *
	 *
	 *
	 * @author zengmm
	 * @date 2012/7/31
	 */
	private function newestFriend($uid)
	{
		$this->load->model('friendmodel');
		$friend = $this->friendmodel->getNewestFriend($uid, 0, 7);
	
		if ($friend) {
	
			foreach ($friend as &$v) {
				// 用户头像地址 65*65
				$v['avatar'] = get_avatar($v['id'], 'mm');
	
				$v['href'] = mk_url('main/index/profile', array('dkcode' => $v['dkcode']));
			}
		}
	
		$data = array(
				'href' => mk_url('main/friend/friendlist'),
				'data' => $friend
		);
	
		return $data;
	}
	
}
