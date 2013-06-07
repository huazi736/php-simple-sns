<?php
/**
 * @desc            粉丝
 * @author          boolee
 * @date            2012-07-16
 * @description     网页粉丝列表\ 粉丝搜索
 * @history          <author><time><version><desc>
 */
class Follower extends MY_Controller {
	function __construct() {
		parent::__construct();
		$this->load->model('followermodel');
    }
	function dump($var){
    	echo "<pre>";
        print_r($var);
        echo "</pre>";
    }
	/**
     * 粉丝列表
     * @author	boolee
     * @date	2012-07-16
     */
    function index() {
    	//访问者身份
        //获得粉丝列表
        $followerlist   = $this->followermodel->getFollowersWithInfo( $this->web_id, 0, 20, $this->uid );
        //获得粉丝数量
        $numOfFollowers = $this->followermodel->getNumOfFollowers( $this->web_id );
        //当前网页信息
        $home_info = array(
            'self_url' 		 => mk_url('webmain/follower/index', array('web_id' => $this->web_id)),
            'url'			 => mk_url('webmain/index/main', array('web_id' => $this->web_id)),
            'src' 			 => get_webavatar($this->web_id,'ss'),
            'username' 		 => $this->action_user['username'],
            'is_self' 		 => $this->is_self,
            'NumOfFollowers' => $numOfFollowers,
        	'web_name'       => $this->web_info['name']
        );
		$this->assign('action_dkcode', $this->action_dkcode);

        $this->assign('home_info',$home_info);
        $this->assign('followerlist',$followerlist);
        $this->display('follower/list.html');
    }
    /**
     * 滚动分页

     */
    function getfollowerBypage(){
    	$page=$this->input->get_post('pager');
        $followerlist   = $this->followermodel->getFollowersWithInfo( $this->web_id, 20*($page-1), 20, $this->uid );
        $numOfFollowers = $this->followermodel->getNumOfFollowers( $this->web_id );
        $last 			= ($numOfFollowers-$page*20)>0?false:true;
        $this->ajaxReturn(array('data'=>$followerlist ,'last' =>$last));
    }

	/**
	 * 搜索网页粉丝
	 *
	 * 根据用户名搜索网页粉丝
	 *
	 * @author zengmm
	 * @date 2012/7/18
	 */
	function searchFollowerByName(){
        $list = '';
        $last = true;

        // 获得页码
        $page = intval($this->input->post('pager')) ? intval($this->input->post('pager')) : 1;

		// 搜索关键字
        $keyword = $this->input->post('keyword');

        //获得网页粉丝列表
        $getFollowerByName = $this->followermodel->getFollowerByName($this->web_id, $keyword, $page, $this->uid);
		
        $followerlist      = '';
        if($getFollowerByName['total'] > 0){

            foreach($getFollowerByName['object'] as $k => &$v){

				$v['id'] = $v['user_id'];

				$v['name'] = $v['user_name'];

				$v['dkcode'] = $v['user_dkcode'];

                $v['src'] = get_avatar($v['user_id'],'m');

                $v['href'] = mk_url('main/index/main', array('dkcode' => $v['user_dkcode']), false);

				// 关注数URL
				$v['following_url'] = mk_url('main/following/followingList', array('dkcode'=>$v['user_dkcode']));

				// 粉丝数URL
				$v['follower_url'] = mk_url('main/follower/index', array('dkcode'=>$v['user_dkcode']));

				// 好友数URL
				$v['friend_url'] = mk_url('main/friend/friendlist', array('dkcode'=>$v['user_dkcode']));
            }

			$followerlist = $getFollowerByName['object'];

            // 判断是否为最后一页
            $last = ($getFollowerByName['total'] > $page * 20) ? FALSE : TRUE;
        }

		$data = array(
			'last' => $last,
			'isSelf' => false,
			'data' => $followerlist
		);

		$this->ajaxReturn($data, 'success', 1);
    }  
}
?>