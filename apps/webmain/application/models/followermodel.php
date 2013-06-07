<?php
/**
 * @desc            网页粉丝模型
 * @author          boolee
 * @date            2012-07-16
 */
class FollowerModel extends DK_Model {
	function __construct() {
		parent::__construct();
    }
     /**
     * @param $web_id 网页id
     * @param $uid    当前用户id
     */
    function getFollowersWithInfo($web_id , $offset = 0, $limit = 20, $login_uid = 0){
    	$result = service('WebpageRelation')->getFollowersWithInfo($web_id, $offset, $limit);
		// 粉丝UID集合
		$uids = array();

		if ($result) {
			foreach ($result as $v) {
				$uids[] = $v['id'];
			}
			
			// 获取用户的关注、粉丝、好友数
			$relation_count = service('Relation')->getRelationNums($uids, $login_uid);
			// 获取用户的现居地
			$userinfo = service('user')->getUserList($uids);
			$now_addr = array();
			if ($userinfo) {
				foreach ($userinfo as $v) {
					$now_addr[$v['uid']] = $v['now_addr'];
				}
			}

			foreach($result as &$v){

				$v['src'] = get_avatar($v['id'],'mm');
				$v['href'] = mk_url('main/index/main', array('dkcode' => $v['dkcode']));

				if (isset($relation['u' . $v['id']])) {
					$v['relation'] = $relation['u' . $v['id']];
				}

				if (isset($relation_count[$v['id']])) {
					$v['following'] = $relation_count[$v['id']]['following'];
					$v['follower'] = $relation_count[$v['id']]['follower'];
					$v['friend'] = $relation_count[$v['id']]['friend'];
					$v['display'] ='';
				}

				if (isset($now_addr[$v['id']])) {
					$v['now_addr'] = $now_addr[$v['id']];
				} else {
					$v['now_addr'] = '';
				}
			}
		}
        return $result;
    }
	/**
	 * 获取粉丝数
	 **/
    function getNumOfFollowers( $web_id ){
    	 return service('WebpageRelation')->getNumOfFollowers( $web_id );
    }

	/**
	 * 通过用户名查找网页粉丝
	 *
	 * @author zengmm
	 * @date 2012/7/18
	 *
	 * @param int $webid 网页ID
	 * @param string $keyword 搜索关键字
	 * @param $page 分页码
	 * @param $visituid 访问者UID
	 *
	 * @return array
	 */
	public function getFollowerByName($webid = 0, $keyword = '', $page = 1, $visituid = 0) {

		$follower = service('WebpageSearch')->getFansOfWebpage($webid, $keyword, $page, 20);

		$follower = json_decode($follower, TRUE);

		if ($follower['object']) {

			foreach ($follower['object'] as $v) {
				$uids[] = $v['user_id'];
			}
			
			// 获取用户的关注、粉丝、好友数
			$relation_count = service('Relation')->getRelationNums($uids, $visituid);
			// 获取用户的现居地
			$userinfo = service('user')->getUserList($uids);
			$now_addr = array();
			if ($userinfo) {
				foreach ($userinfo as $v) {
					$now_addr[$v['uid']] = $v['now_addr'];
				}
			}

			foreach($follower['object'] as &$v){

				$v['src'] = get_avatar($v['user_id'],'mm');
				$v['href'] = mk_url('main/index/main', array('dkcode' => $v['user_dkcode']));

				if (isset($relation['u' . $v['user_id']])) {
					$v['relation'] = $relation['u' . $v['user_id']];
				}

				if (isset($relation_count[$v['user_id']])) {
					$v['following'] = $relation_count[$v['user_id']]['following'];
					$v['follower'] = $relation_count[$v['user_id']]['follower'];
					$v['friend'] = $relation_count[$v['user_id']]['friend'];
					$v['display'] ='';
				}

				if (isset($now_addr[$v['user_id']])) {
					$v['now_addr'] = $now_addr[$v['user_id']];
				} else {
					$v['now_addr'] = '';
				}
			}
		}

		return $follower;
	}
}
?>