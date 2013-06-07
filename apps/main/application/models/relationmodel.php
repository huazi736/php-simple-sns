<?php
/**
 * 关系模型
 *
 * 处理关注、粉丝、好友公共的模型
 *
 * @author zengmm
 * @date 2012/7/26
 */
class RelationModel extends MY_Model {

    /**
     * 共同关注的人
     *
     * @author zengmm
     * @date 2012/7/26
     *
     * @param array $commonUser 共同关注的人
     * @param int $uid 目标用户UID
     * @param int $visituid 访问者UID
     *
     * @return string
     */
    private function _makeCommonFollowing($commonUser, $uid, $visituid)
    {
        $total = count($commonUser);

        if ($total == 0) { return ''; }

        // 初始化变量
        $commoninfo = $url = '';
        $i = 0;
        $tmp = array();

        foreach ($commonUser as $v) {

            $url = mk_url('main/index/profile',array('dkcode'=>$v['dkcode']));
            $tmp[] = '<a href=' . $url . '>' . $v['username'] . '</a>';

            // 只显示前三个用户
            if (++$i > 2) { break; }
        }

        if ($total > 0 && $total <= 3) {
            $commoninfo = '你和TA共同关注了' . implode('、', $tmp) . $total . '个人';
        } else {
            $popBoxUrl = mk_url('main/following/getFollowingsList',array('uid1'=>$visituid,'uid2'=>$uid));
            // rel="2"用于前端弹出框的标题显示
            $commoninfo = '你和TA共同关注了' . implode('、', $tmp) . '等<a class="sameFriend" rel="2" href="' . $popBoxUrl . '">' . $total . '</a>个人';
        }

        return $commoninfo;
    }

    /**
     * 共同好友
     *
     * @author zengmm
     * @date 2012/7/26
     *
     * @param array $commonUser 共同好友
     * @param int $uid 目标用户UID
     * @param int $visituid 访问者UID
     *
     * @return string
     */
    private function _makeCommonFriend($commonUser, $uid, $visituid)
    {
        $total = count($commonUser);

        if ($total == 0) { return ''; }

        // 初始化变量
        $commoninfo = $url = '';
        $i = 0;
        $tmp = array();

        foreach ($commonUser as $v) {

            $url = mk_url('main/index/profile',array('dkcode'=>$v['dkcode']));
            $tmp[] = '<a href=' . $url . '>' . $v['username'] . '</a>';

            // 只显示前三个用户
            if (++$i > 2) { break; }
        }

        if ($total > 0 && $total <= 3) {
            $commoninfo = '你和TA有' . implode('、', $tmp) . $total . '位共同好友';
        } else {
            $popBoxUrl = mk_url('main/following/getFriendsList',array('uid1'=>$visituid,'uid2'=>$uid));
            // rel="1"用于前端弹出框的标题显示
            $commoninfo = '你和TA有' . implode('、', $tmp) . '等<a class="sameFriend" rel="1" href="' . $popBoxUrl . '">' . $total . '</a>位共同好友';
        }

        return $commoninfo;
    }

    /**
     * 共同兴趣(共同关注的网页)
     *
     * @author zengmm
     * @date 2012/7/26
     *
     * @param array $commonInterest 共同兴趣
     * @param int $uid 目标用户UID
     * @param int $visituid 访问者UID
     *
     * @return string
     */
    private function _makeCommonInterest($commonInterest, $uid, $visituid)
    {
        $total = count($commonInterest);

        if ($total == 0) { return ''; }

        // 初始化变量
        $commoninfo = $url = '';
        $i = 0;
        $tmp = array();

        foreach ($commonInterest as $v) {

            $url = mk_url('webmain/index/main',array('web_id'=>$v['aid']));
            $tmp[] = '<a href=' . $url . '>' . $v['name'] . '</a>';

            // 只显示前三个用户
            if (++$i > 2) { break; }
        }

        if ($total > 0 && $total <= 3) {
            $commoninfo = '你和TA共同关注了' . implode('、', $tmp) . $total . '个网页';
        } else {
            $popBoxUrl = mk_url('main/following/getWebFollowingsList',array('uid1'=>$visituid,'uid2'=>$uid));
            // rel="3"用于前端弹出框的标题显示
            $commoninfo = '你和TA共同关注了' . implode('、', $tmp) . '等<a class="sameFriend" rel="3" href="' . $popBoxUrl . '">' . $total . '</a>个网页';
        }

        return $commoninfo;
    }

    /**
     * 生成共同信息的模板
     *
     * @author zengmm
     * @date 2012/7/26
     *
     * @param array $commoninfo 用户的共同信息
     * @param int $visituid 访问者UID
     *
     * @return array
     */
    private function _templateCommonInfo($commoninfo, $visituid)
    {
        if (empty($commoninfo)) { return array(); }

        $tplCommonInfo =  array();

        foreach ($commoninfo as $k => $v) {

            // 此处的'u'由接口定义, 不能随意修改
            $uid = (int) end(explode('u', $k));

            if ($v['relation'] == 2)  {
                // 用户之间没有关系
                $tplCommonInfo[$uid] = $this->_makeCommonFollowing($v['data'], $uid, $visituid);

            } elseif ($v['relation'] > 2 && $v['relation'] < 10) {
                // 用户之间有如下关系
                // 被关注、已关注(他人的粉丝)、相互关注、被邀请加对方好友、好友请求已发送
                $tplCommonInfo[$uid] = $this->_makeCommonFriend($v['data'], $uid, $visituid);

            } elseif ($v['relation'] == 10) {
                // 好友
                $tplCommonInfo[$uid] = $this->_makeCommonInterest($v['data'], $uid, $visituid);
            }
        }

        return $tplCommonInfo;
    }

    /**
     * 获取共同信息
     *
     * @author zengmm
     * @date 2012/7/26
     *
     * @param array $relationStatus 访问者与其他用户的关系状态
     * @param int $visituid 访问者UID
     *
     * @return array
     */

    public function getCommonInfo($relationStatus, $visituid)
    {
        $commoninfo = service('Relation')->getCommonRelationInfo($relationStatus, $visituid);

        return $this->_templateCommonInfo($commoninfo, $visituid);
    }
}