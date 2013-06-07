<?php

class CronModel extends DkModel {

    private $_db;

    public function __initialize() {
        $this->init_mongo('credit');
        $this->_db = $this->mongodb->getDbInstance();
    }

    /**
     * 生成全站积分等级排行的数据
     */
    public function rebuilingRankingList() {
        foreach ($this->_db->user_credits->find()->sort(array('c' => -1, '_id' => 1))->limit(10) as $user) {
            $rankListUsers[] = $user;
        }

        // 组合用户的uid来获取用户的姓名、头像信息
        $uids = array();
        foreach ($rankListUsers as $user) {
            $uids[] = $user['_id'];
        }
        $userInfos = $this->getUserName($uids);

        $users = array();
        foreach ($userInfos as $u) {
            $users[$u['uid']] = $u;
        }

        if (!empty($userInfos)) {
            foreach ($rankListUsers as $key => $user) {
                $rankListUsers[$key]['uname'] = $users[$user['_id']]['username'];
                $rankListUsers[$key]['home'] = mk_url("main/index/main", array('dkcode' => $users[$user['_id']]['dkcode']));
                $rankListUsers[$key]['avatar'] = get_avatar($user['_id']);
            }

            $this->init_memcache();
            $rankListUsers = array('users' => $rankListUsers, 'time' => date('Y年m月d日 H:i'));
            $this->memcache->set('all:ranklist', $rankListUsers, 0, 3600);
            return $rankListUsers;
        } else {
            // 获取用户的信息失败
            return array('users' => array(), 'time' => date('Y年m月d日 H:i'));
        }
    }

    /**
     * 清除所有未在10分钟之内完成兑换的兑换记录
     */
    public function clearRedeems() {
        $products = array();

        // 统计所有未完成兑换的记录
        foreach ($this->_db->redeems->find(array('ctime' => array('$lt' => (time() - 60 * 10)), 'status' => 1)) as $redeemHistory) {
            $pid = strval($redeemHistory['pid']);
            if (isset($products[$pid])) {
                $products[$pid] += 1;
            } else {
                $products[$pid] = 1;
            }

            // 把该订单状态设为取消
            $redeemHistory['status'] = -1;
            $this->_db->redeems->save($redeemHistory);
        }

        // 把未完成兑换的产品数据重新加到商品库存上
        if (!empty($products)) {
            foreach ($products as $pid => $num) {
                $this->_db->products->update(array('_id' => new MongoId($pid)), array('left' => array('$inc' => $num)));
            }
        }
    }

    private function getUserName($uids) {
        $infos = array();
        $infos = service('user')->getUserList($uids, array('username', 'dkcode', 'uid'));
        return $infos;
    }

}