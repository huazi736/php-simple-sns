<?php

/**
 * 邀请码model 
 */
class InvitecodeModel extends DK_Model {

    //table_name
    protected $invitecode_table = 'invitecode';
    protected $invite_record_table = 'invite_record';
    protected $user_code_nums_table = 'user_code_nums';

    public function __construct() {
        parent::__construct();
        $this->init_db('user');
    }

    /**
     * 邀请码列表
     */
    public function getInviteCodeList($status, $where = array(), $start = 0, $offsset = 30) {
        if (!$where) {
            $where = array();
        }
        $res = $this->db->get_where($this->invitecode_table, $where, $offsset, $start)->result_array();
        $count = $this->db->from($this->invitecode_table)->where($where)->count_all_results();
        if (!$res) {
            return false;
        }
        $arr = array($res, $count);
        return $arr;
    }

    /**
     * 邀请记录列表
     */
    public function getInviteRecordList($status, $where = array(), $start = 0, $offsset = 30) {
        if (!is_array($where)) {
            $where = array();
        }
        $res = $this->db->get_where($this->invite_record_table, $where, $offsset, $start)->result_array();
        $count = $this->db->from($this->invite_record_table)->where($where)->count_all_results();

        return array($res, $count);
    }

    /*
     * 邀请码批量生成 lvxinxin add
     * @param $nums 设置生成邀请码的数量
     */

    public function inviteCodeFactoryAll($nums) {
        if (!$nums) {
            return false;
        }

        $maxId = $this->db->select_max('dkcode')->get($this->invitecode_table)->row_array();
        $maxId = $maxId['dkcode'] ? $maxId['dkcode'] : 99999;
        for ($i = 1; $i <= $nums; $i++) {
            $nums_s = $maxId + $i;
            $dk[] = $nums_s;
            $len = strlen($nums_s);
            //6连续
            if ($len == 6)
                $fl = preg_match('/(?:(?:0(?=1)|1(?=2)|2(?=3)|3(?=4)|4(?=5)|5(?=6)|6(?=7)|7(?=8)|8(?=9)){5}|(?:9(?=8)|8(?=7)|7(?=6)|6(?=5)|5(?=4)|4(?=3)|3(?=2)|2(?=1)|1(?=0)){5})\d/i', $nums_s);
            //7
            if ($len == 7)
                $fl = preg_match('/(?:(?:0(?=1)|1(?=2)|2(?=3)|3(?=4)|4(?=5)|5(?=6)|6(?=7)|7(?=8)|8(?=9)){5}|(?:9(?=8)|8(?=7)|7(?=6)|6(?=5)|5(?=4)|4(?=3)|3(?=2)|2(?=1)|1(?=0)){6})\d/i', $nums_s);
            //8
            if ($len == 8)
                $fl = preg_match('/(?:(?:0(?=1)|1(?=2)|2(?=3)|3(?=4)|4(?=5)|5(?=6)|6(?=7)|7(?=8)|8(?=9)){5}|(?:9(?=8)|8(?=7)|7(?=6)|6(?=5)|5(?=4)|4(?=3)|3(?=2)|2(?=1)|1(?=0)){7})\d/i', $nums_s);
            //9
            if ($len == 9)
                $fl = preg_match('/(?:(?:0(?=1)|1(?=2)|2(?=3)|3(?=4)|4(?=5)|5(?=6)|6(?=7)|7(?=8)|8(?=9)){5}|(?:9(?=8)|8(?=7)|7(?=6)|6(?=5)|5(?=4)|4(?=3)|3(?=2)|2(?=1)|1(?=0)){8})\d/i', $nums_s);

            //连续数字 
            $fl5 = preg_match('/([\d])\1{2,}/i', $nums_s);
            if ($fl || $fl5) {
                $dataList[] = array('status' => 2);
            } else {
                $dataList[] = array('status' => 0);
            }
        }

        $res = $this->db->insert_batch($this->invitecode_table, $dataList);

        return $res ? $dk : false;
    }

    /*
     * 邀请码批量生成
     * @param $nums 设置生成邀请码的数量
     */

    public function inviteCodeFactory($nums) {

        if (!$nums) {
            return false;
        }

        $maxId = $this->db->select_max('dkcode')->get($this->invitecode_table)->row_array();
        $maxId = $maxId['dkcode'] ? $maxId['dkcode'] : 99999;
        for ($i = 1; $i <= $nums; $i++) {
            $nums_s = $maxId + $i;
            $len = strlen($nums_s);
            //6连续
            if ($len == 6)
                $fl = preg_match('/(?:(?:0(?=1)|1(?=2)|2(?=3)|3(?=4)|4(?=5)|5(?=6)|6(?=7)|7(?=8)|8(?=9)){5}|(?:9(?=8)|8(?=7)|7(?=6)|6(?=5)|5(?=4)|4(?=3)|3(?=2)|2(?=1)|1(?=0)){5})\d/i', $nums_s);
            //7
            if ($len == 7)
                $fl = preg_match('/(?:(?:0(?=1)|1(?=2)|2(?=3)|3(?=4)|4(?=5)|5(?=6)|6(?=7)|7(?=8)|8(?=9)){5}|(?:9(?=8)|8(?=7)|7(?=6)|6(?=5)|5(?=4)|4(?=3)|3(?=2)|2(?=1)|1(?=0)){6})\d/i', $nums_s);
            //8
            if ($len == 8)
                $fl = preg_match('/(?:(?:0(?=1)|1(?=2)|2(?=3)|3(?=4)|4(?=5)|5(?=6)|6(?=7)|7(?=8)|8(?=9)){5}|(?:9(?=8)|8(?=7)|7(?=6)|6(?=5)|5(?=4)|4(?=3)|3(?=2)|2(?=1)|1(?=0)){7})\d/i', $nums_s);
            //9
            if ($len == 9)
                $fl = preg_match('/(?:(?:0(?=1)|1(?=2)|2(?=3)|3(?=4)|4(?=5)|5(?=6)|6(?=7)|7(?=8)|8(?=9)){5}|(?:9(?=8)|8(?=7)|7(?=6)|6(?=5)|5(?=4)|4(?=3)|3(?=2)|2(?=1)|1(?=0)){8})\d/i', $nums_s);

            //连续数字 
            $fl5 = preg_match('/([\d])\1{2,}/i', $nums_s);
            if ($fl || $fl5) {
                $dataList[] = array('status' => 2);
            } else {
                $dataList[] = array('status' => 0);
            }
        }

        $res = $res = $this->db->insert_batch($this->invitecode_table, $dataList);

        return $res ? true : false;
    }

    /*
     * 用户邀请码发放手机号码次数规则(3次限制),且在手机号为被激活使用的情况下
     * @param $userid 用户ID
     * @param $phonenum 发送手机号码
     */

    public function phoneTimesRule($userid, $phonenum) {

        if (!$userid || !$phonenum) {
            return array('status' => false, 'msg' => '用户ID和手机号码不能为空');
        }

        //判断手机号是否被注册过
        $p_where = array(
            'mobile' => "{$phonenum}",
            'status' => 1,
        );

        $p_nums = $this->db->where($p_where)->count_all_results($this->invite_record_table);
        if ($p_nums > 0) {
            return array('status' => false, 'msg' => '该用户已在端口网注册');
        }


        $where = array(
            'invite_uid' => $userid,
            'mobile' => $phonenum
        );
        $nums = $this->db->where($where)->count_all_results($this->invite_record_table);

        if ($nums == 3) {
            return array('status' => false, 'msg' => '同个手机号码最多可发送三条邀请信息');
        }

        return array('status' => true);
    }

    /*
     * 用户邀请码计数器递增
     * @param $userid 用户ID
     */

    public function addUserCodeNums($userid) {
        if (!$userid) {
            return false;
        }
        $where = array('uid' => $userid);
        $this->db->set('invite_user_nums', 'invite_user_nums+1', FALSE);

        $res = $this->db->where($where)->update($this->user_code_nums_table);
        if (!$res) {
            return false;
        }
        return true;
    }

    /**
     * 用户邀请码计数器递减
     * @param $userid 用户ID
     */
    public function decUserCodeNums($userid) {
        if (!$userid) {
            return false;
        }

        $where = array('uid' => $userid);

        $num = $this->db->where($where)->count_all_results($this->user_code_nums_table);

        if (!$num) {
            $data = array(
                'uid' => $userid,
                'invite_count' => 49,
            );
            $this->db->insert($this->user_code_nums_table, $data);
        } else {
            $this->db->set('invite_count', 'invite_count -1', false);
            $res = $this->db->where(array('uid' => $userid))->update($this->user_code_nums_table);
        }
        return true;
    }

    /*
     * 获取一个邀请码
     * @param $userid 用户ID
     */

    private function getOneInviteCode($userid) {

        if (!$userid) {
            return false;
        }

        //获取用户邀请计数器
        $res = $this->db->select('invite_count')->get_where($this->user_code_nums_table, array('uid' => $userid))->row_array();
        if ($res && $res['invite_count'] <= 0) {
            return array('status' => false, 'msg' => '您没有剩余邀请码，无法发送邀请');
        }

        $data = $this->db->select('dkcode')->get_where($this->invitecode_table, array('status' => 0))->row_array();
        if (!$data) {
            return array('status' => false, 'msg' => '系统错误，请稍后重试');
        }


        return array('status' => true, 'msg' => $data['dkcode']);
    }

    /**
     * 邀请一个用户
     * @param $code 邀请码
     * @param $name 用户名 
     * @param $mobile 手机号
     * @param $invite_uid 邀请人用户ID
     */
    public function inviteUser($name, $mobile, $invite_uid) {

        if (!$name || !$mobile || !$invite_uid) {
            $retError = array('status' => false, 'msg' => '请检查 邀请码，用户名，手机号，邀请人ID是否为空');
            return $retError;
        }

        //通过手机号判断用户是否还可以向手机发送邀请
        $res = $this->phoneTimesRule($invite_uid, $mobile);
        if (!$res['status']) {
            return $res;
        }


        $code = $this->getOneInviteCode($invite_uid);
        if (!$code['status']) {
            $retError = array('status' => false, 'msg' => $code['msg']);
            return $retError;
        }
        $data = array(
            'code' => $code['msg'],
            'name' => $name,
            'mobile' => $mobile,
            'invite_uid' => $invite_uid,
            'dateline' => time(),
        );

        $this->db->trans_begin();

        $res = $this->db->insert($this->invite_record_table, $data);
        if (!$res) {
            $retError = array('status' => false, 'msg' => '该用户已经邀请过');
            return $retError;
        }

        //改变邀请码使用状态
        $this->db->set('status', 'status+1', FALSE);

        $res1 = $this->db->where(array('dkcode' => $code['msg']))->update($this->invitecode_table);

        //减少用户邀请码使用计数器
        $res2 = $this->decUserCodeNums($invite_uid);

        if ($this->db->trans_status() === FALSE) {
            $this->db->trans_rollback();
        }

        $this->db->trans_commit();


        $retError = array('status' => true, 'msg' => '添加成功', 'data' => $code['msg']);
        return $retError;
    }

	/**
     * 邀请一个用户
     * @param $code 邀请码
     * @param $name 用户名 
     * @param $mobile 手机号
     * @param $invite_uid 邀请人用户ID
     */
    public function inviteUsers($name, $mobile, $invite_uid) {

        if (!$name || !$mobile || !$invite_uid) {
            // $retError = array('status' => false, 'msg' => '请检查 邀请码，用户名，手机号，邀请人ID是否为空');
            return false;//$retError;
        }

        //通过手机号判断用户是否还可以向手机发送邀请
        $res = $this->phoneTimesRule($invite_uid, $mobile);
        if (!$res['status']) {
            return $res;
        }


        $code = $this->getOneInviteCode($invite_uid);
        if (!$code['status']) {
            // $retError = array('status' => false, 'msg' => $code['msg']);
            return false;//$retError;
        }
        $data = array(
            'code' => $code['msg'],
            'name' => $name,
            'mobile' => $mobile,
            'invite_uid' => $invite_uid,
            'dateline' => time(),
        );

        $this->db->trans_begin();

        $res = $this->db->insert($this->invite_record_table, $data);
        if (!$res) {
            // $retError = array('status' => false, 'msg' => '该用户已经邀请过');
            return false;//$retError;
        }

        //改变邀请码使用状态
        $this->db->set('status', 'status+1', FALSE);

        $res1 = $this->db->where(array('dkcode' => $code['msg']))->update($this->invitecode_table);

        //减少用户邀请码使用计数器
        $res2 = $this->decUserCodeNums($invite_uid);

        if ($this->db->trans_status() === FALSE) {
            $this->db->trans_rollback();
        }

        $this->db->trans_commit();


        // $retError = array('status' => true, 'msg' => '添加成功', 'data' => $code['msg']);
        return $data;
    }
	
    /**
     * 邀请一个用户&获取剩余邀请码数量
     * 
     * @param type $name 姓名
     * @param type $mobile 手机
     * @param type $invite_uid 邀请者uid
     * @return type 
     */
    public function inviteUserByUid($name, $mobile, $invite_uid) {
        $result = $this->inviteUser($name, $mobile, $invite_uid);
        if ($result['status']) {
            $result['renums'] = $this->getInviteCodeNums($invite_uid);
        }

        return $result;
    }

    /*
     * 获取用户邀请码数量
     * @param $userid
     */

    public function getInviteCodeNums($userid) {

        if (!$userid) {
            return false;
        }
        $data = $this->db->select('invite_count')->get_where($this->user_code_nums_table, "uid = {$userid}")->row_array();
        if (count($data) > 0) {
            return $data['invite_count'];
        } else {
            return 50;
        }
    }

    /**
     * 获取推荐我的人
     * @param $userid 用户ID
     * @return 推荐我的人用户信息
     */
    public function getRecommandUser($code) {
        if (!$code) {
            return false;
        }
        $res = $this->db->select('user_info.uid,user_info.username,user_info.dkcode')->from('invite_record')->join('user_info', 'invite_record.invite_uid=user_info.uid')->where(array('invite_record.code'=>$code))->get()->result_array();
        
        return $res;
    }

    /**
     * 获取我推荐的人列表
     * @param userid 用户ID
     * @param $start 起始记录
     * @param $limit 每次获取条数
     * @return 返回用户推荐的人的用户信息列表
     */
    public function getMyRecommandUsers($userid, $start = 0, $limit = 10) {
        if (!$userid) {
            return false;
        }

        $userid = $this->db->escape($userid);
        $start = intval($start);
        $limit = intval($limit);
        $sql = " select b.uid,b.username,b.dkcode from invite_record a
		         inner join user_info b on a.code=b.dkcode where 
		         a.invite_uid={$userid} and a.status=1 limit {$start},{$limit}";

        $user_lists = $this->db->query($sql)->result_array();

        //关系
        $touid = array();
        foreach ($user_lists as $k => $val) {
            $touid[] = $val['uid'];
        }

        //services
        $Social = service('Relation');

        $result = $Social->getMultiRelationStatus($userid, $touid);
        $lists = array();
        foreach ($user_lists as $k => $v) {
            //判断某人与用户的关系
            $v['is_follow'] = $result['u' . $v['uid']];
            $lists[] = $v;
        }

        return $lists;
    }

    /**
     * 获取我推荐的人的总数
     * @param userid 用户ID
     * @return 返回推荐的人的总数
     */
    public function getMyRecommandUserCount($userid) {
        if (!$userid) {
            return false;
        }

        $res = $this->db->from($this->invite_record_table)->where(array('invite_uid' => $userid))->count_all_results();

        return $res;
    }

    /**
     * 判断邀请是否存在
     */
    public function checkDkCode($code) {
        if (!$code) {
            return false;
        }
        $field = array('name', 'dateline', 'status', 'invite_uid');
        $res = $this->db->select($field)->from($this->invite_record_table)->where(array('code' => $code))->get()->row_array();
        if (!$res) {
            return false;
        }

        return $res;
    }

    /**
     * 设置端口号状态,设置为激活状态
     * @param $code 端口号
     * @param $uid  用户ID
     */
    public function setDkStatus($code, $uid) {
        if (!$code || !$uid) {
            return false;
        }

        //更新邀请记录，设为已经激活使用
        $this->db->set('status', 'status+1', FALSE);
        $data['usedateline'] = time();
        $res = $this->db->where(array('code' => $code))->update($this->invite_record_table);

        if (!$res) {
            return false;
        }

        //更新用户邀请人数记录 
        $this->db->set('invite_user_nums', 'invite_user_nums+1', FALSE);
        $res = $this->db->where(array('uid' => $uid))->update($this->user_code_nums_table);

        return true;
    }

    /*
     * 获取用户总共邀请的数量 
     * @param $userid 用户ID
     * @return 返回邀请的数量
     */

    public function getUserInviteNums($userid) {
        if (!$userid) {
            return false;
        }

        $ret = $this->db->select('invite_user_nums')->get_where($this->user_code_nums_table, "uid = {$userid}")->row_array();
        if (!$ret) {
            return false;
        }

        return $ret['invite_user_nums'];
    }
    
    
    
    /**
     * 获取邀请码相关信息 推荐我的人、我推荐的人、我推荐且 成功注册人的总数、剩余邀请码数量
     * @param type $uid
     * @param type $dkcode
     * @param type $offsent
     * @param type $limit
     * @return type 
     */
    public function getInviteCodeAllStatus_module($uid, $dkcode, $offsent = 0, $limit = 10) {
        $data = array();
        $Social = service('Relation');

        //取得推荐我的人
        if (!$uid || !$dkcode) {
            return false;
        }
        $lists = $this->getRecommandUser($dkcode);
        if (!$lists) {
            $data['recmded_info'] = array();
        } else {
            $lists[0]['is_follow'] = $Social->getRelationStatus($uid, $lists[0]['uid']);
            $data['recmded_info'] = $lists;
            unset($lists);
        }

        ########################################################################
        //取得我推荐的人列表		
        $user_lists = $this->getMyRecommandUsers($uid, $offsent, $limit);
        if (!$user_lists) {
            $data['recmd_lists'] = array();
        } else {
            $data['recmd_lists'] = $user_lists;
            unset($user_lists);
        }


        ########################################################################
        //获取我推荐且 成功注册人的总数
        $data['getcount'] = $this->getUserInviteNums($uid);

        ########################################################################
        //获取剩余邀请码数量        
        $data['dkcode_nums'] = $this->getInviteCodeNums($uid);

        return $data;
    }

    
    //controller 调用
    
	/**
	 * 取得我推荐的人列表
	 *
	 * @author hujiashan
	 * @date   2012/3/8
	 * @access public
	 * @param string $uid 用户uid
	 * @param int $nowpage 查询当前页面
	 * @param int $limit 显示查询数据数目
	 * @return 返回推荐者数组
	 */
	function get_recommend_lists($uid = null,$start = 0,$limit = 50){
		
		$uid = mysql_real_escape_string($uid);
		if(!$uid){
			return false;
		}
		
		//返回我推荐的人 的用户信息列表
//		$user_lists = call_soap('ucenter', 'InviteCode', 'getMyRecommandUsers', array($uid, $start, $limit));
		$user_lists =$this->getMyRecommandUsers($uid, $start, $limit);
		$user_lists  = unserialize($user_lists);
		if(!$user_lists){
			return false;
		}

		$lists = array();
		foreach($user_lists as $k => $v){
			//头像小图50x50
			$v['avatar_img'] = get_avatar($v['uid'], 's');
			$v['url'] = mk_url(APP_URL.'/index/index', array('action_dkcode' => $v['dkcode']));
			$lists[] = $v;
		}
		
		return $lists;
	}

	
	/**
	 * 邀请一个用户,获取剩余邀请码数量
	 * @param $name string 被邀请者用户名 
	 * @param $mobile int 被邀请者手机号
	 * @param $uid string 邀请者用户ID
	 *  @return 返回数组
	 */
	
	function invite_user($name = null, $mobile = null, $uid = null){
		
		$uid = mysql_real_escape_string($uid);
		if(!$name || !$mobile || !$uid){
			return false;
		}
	
//		return call_soap('ucenter', 'InviteCode', 'inviteUserByUid', array($name, $mobile, $uid));
		return $this->inviteUserByUid($name, $mobile, $uid);
	}
	
	/**
	 * 
	 * 检查手机是否已被使用
	 * @param int $uid
	 * @param int $mobile
	 * @author  hujiashan
	 * @date  2012/4/18
	 */
	function checkmobile($uid = null, $mobile = null){
		if(!$mobile || !$uid){
			return false;
		}
//		return call_soap('ucenter', 'InviteCode', 'phoneTimesRule', array($uid, $mobile));
		return $this->phoneTimesRule($uid, $mobile);
	}
	
	
	/**
	 * 
	 *  首页加载时获取"提供邀请码给我的人"\"我邀请成功的人"\"剩余邀请码数量"\"获取我推荐且 成功注册人的总数"
	 *  @author hujiashan
	 *  @date 2012-5-25
	 * @param int $uid
	 * @param int $dkcode
	 * @param int = $offsent
	 * @param int $limit
	 * @return Array
	 */
	function getInviteCodeAllStatus($uid = NULL, $dkcode = NULL, $offsent =0, $limit = 12){
		if(!$uid || !$dkcode){
			return false;
		}
		
//		$data = call_soap('ucenter', 'InviteCode', 'getInviteCodeAllStatus', array($uid, $dkcode, $offsent, $limit));
		$data = $this->getInviteCodeAllStatus_module($uid, $dkcode, $offsent, $limit);
		return $data ;
    }
}