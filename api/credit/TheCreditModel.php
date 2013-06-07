<?php

class TheCreditModel extends DkModel {

    /**
     * MongoDB数据库资源
     * 
     * @var MongoDB
     */
    private $_db;

    /**
     * 当天对应的年月日
     * 
     * @var string
     */
    private $_time;

    /**
     * 积分规则
     * 
     * @var array
     */
    private $_rules;

    /**
     * 当前登录者的UID
     * 
     * @var int
     */
    private $_uid;

    public function __initialize() {
        $this->init_mongo('credit');
        $this->_db = $this->mongodb->getDbInstance();
        $this->_time = strtotime(date('Y-m-d'));
        if (isset($_SESSION['uid'])) {
            $this->_uid = (int) $_SESSION['uid'];
        } else {
            // 某几个应用调用积分的时候session中不存在uid（头像通过相册接口上传，删除网页的cronjob）
        }
    }

    public function register() {
        $rule = $this->_initRule('reg');

        if ($rule) {
            if ($rule['is_active']) {
                // 插入用户积分信息
                $this->_db->user_credits->save(array('_id' => $this->_uid, 'lv' => 1, 'c' => $rule['credits']
                    , 'cc' => 0));

                // 插入当天登陆记录
                $collection = $this->_db->statistics;
                $collection->save(array('uid' => $this->_uid, 't' => $this->_time, 'log' => 1, 'ent' => 0
                    , 'like' => 0, 'comm' => 0, 'ask' => 0, 'for' => 0, 'inv' => 0, 'flw' => 0
                    , 'frd' => 0, 'flweb' => 0, 'wiki' => 0, 'acty' => 0, 'att' => 0, 'blog' => 0));

                // 只有第一次的情况, 初始化数据
                $this->_db->first_n->save(array('_id' => $this->_uid, 'cov' => 0, 'ava' => 0, 'edu' => 0, 'work' => 0, 'basic' => 0, 'intro' => 0, 'home' => 0, 'web' => 0));
            } else {
                // 该规则未激活
            }
        } else {
            // 没有相应的规则
        }
    }

    public function login() {
        // 找到最近一条登录记录
        $collection = $this->_db->statistics;

        $entity = $collection->findOne(array('uid' => $this->_uid, 't' => array('$lte' => $this->_time)));

        $rewardedCredits = 0;

        // 没有登陆记录
        if ($entity === NULL) {
            $collection->save(array('uid' => $this->_uid, 't' => $this->_time, 'log' => 1, 'ent' => 0
                , 'like' => 0, 'comm' => 0, 'ask' => 0, 'ans' => 0, 'for' => 0, 'inv' => 0, 'flw' => 0
                , 'frd' => 0, 'flweb' => 0, 'wiki' => 0, 'acty' => 0, 'att' => 0, 'blog' => 0));

            $rule = $this->_initRule('log');
            if ($rule['is_active']) {
                $rewardedCredits = $rule['credits'];
            }
        } else {
            // 判断一下这条记录是否是当天登陆的, 同一天不处理
            if ($entity['t'] == $this->_time) {
                if ($entity['log'] == 0) {
                    // 查找上一条登录记录
                    $lastSecondEntity = $collection->findOne(array('uid' => $this->_uid, 't' => array('$lt' => $this->_time)));

                    // 是不是昨天的，如果是昨天登陆的，计算连续登陆
                    if ($lastSecondEntity['t'] == ($this->_time - 86400)) {
                        $loginSequence = $lastSecondEntity['log'];
                        $entity['log'] = ++$loginSequence;
                        $collection->save($entity);

                        // 判断连续登陆的范围并给相应的账号添加积分
                        $rule = $this->_initRule('log');
                        if ($rule['is_active']) {
                            if ($loginSequence < $rule['seq']) {
                                $rewardedCredits = $rule['credits'];
                            } else {
                                $rewardedCredits = $rule['seqV'];
                            }
                        }
                    } else {
                        // 不是连续登陆的
                        $entity['log'] = 1;

                        $collection->save($entity);

                        $rule = $this->_initRule('log');
                        if ($rule['is_active']) {
                            $rewardedCredits = $rule['credits'];
                        }
                    }
                }
            } else {
                // 是不是昨天的，如果是昨天登陆的，计算连续登陆
                if ($entity['t'] == ($this->_time - 86400)) {
                    $loginSequence = $entity['log'];
                    $collection->save(array('uid' => $this->_uid, 't' => $this->_time, 'log' => ++$loginSequence, 'ent' => 0
                        , 'like' => 0, 'comm' => 0, 'ask' => 0, 'ans' => 0, 'for' => 0, 'inv' => 0, 'flw' => 0
                        , 'frd' => 0, 'flweb' => 0, 'wiki' => 0, 'acty' => 0, 'att' => 0, 'blog' => 0));

                    // 判断连续登陆的范围并给相应的账号添加积分
                    $rule = $this->_initRule('log');
                    if ($rule['is_active']) {
                        if ($loginSequence < $rule['seq']) {
                            $rewardedCredits = $rule['credits'];
                        } else {
                            $rewardedCredits = $rule['seqV'];
                        }
                    }
                } else {
                    // 不是连续登陆的，插入一条今天登陆的记录
                    $collection->save(array('uid' => $this->_uid, 't' => $this->_time, 'log' => 1, 'ent' => 0
                        , 'like' => 0, 'comm' => 0, 'ask' => 0, 'ans' => 0, 'for' => 0, 'inv' => 0, 'flw' => 0
                        , 'frd' => 0, 'flweb' => 0, 'wiki' => 0, 'acty' => 0, 'att' => 0, 'blog' => 0));

                    $rule = $this->_initRule('log');
                    if ($rule['is_active']) {
                        $rewardedCredits = $rule['credits'];
                    }
                }
            }
        }

        if ($rewardedCredits) {
            $this->updateCredit($rewardedCredits);
        }
    }

    /**
     * 对应行为加1
     *
     * @param string $field 行为名称
     * @throws MongoCursorTimeoutException
     */
    private function updateEntry($field, $isOthers = false) {
        if (!$isOthers) {
            $uid = $this->_uid;
        } else {
            $uid = (int) $isOthers;
        }
        return $this->_db->command(array('findAndModify' => 'statistics'
                    , 'query' => array('uid' => $uid, 't' => $this->_time)
                    , 'update' => array('$inc' => array($field => 1))
                    , 'new' => true));
    }

    private function updateCredit($creditsToModified, $uid = false) {
        if ($uid === false) {
            $uid = $this->_uid;
        }

        $this->_db->user_credits->update(array('_id' => (int) $uid)
                , array('$inc' => array('c' => $creditsToModified)));

        $this->reCalculateLevel((int) $uid);
    }

    /**
     * 计算用户等级
     * 
     * @param int $uid
     */
    private function reCalculateLevel($uid) {
        if ($creditInfo = $this->_db->user_credits->findOne(array('_id' => $uid))) {
            $evaluatedLevel = $this->evaluateLevel($creditInfo['c'], $creditInfo['lv']);
            if ($creditInfo['lv'] != $evaluatedLevel) {
                $this->_db->user_credits->update(array('_id' => $uid), array('$set' => array('lv' => $evaluatedLevel)));
            }
        }
    }

    /**
     * 积分消费
     * 
     * @param int $credits
     */
    public function consume($credits) {

        $credits = (int) $credits;
        $status = $this->_db->user_credits->update(array('_id' => $this->_uid), array('$inc' => array('cc' => $credits)), array('safe' => true));

        if ($status['ok'] && $status['n'] == 1 && $status['updatedExisting']) {
            $entity = $this->_db->user_credits->findOne(array('_id' => $this->_uid));

            // 重新计算用户等级
            $evaluatedLevel = $this->evaluateLevel($entity['c'], $entity['lv']);
            if ($evaluatedLevel != $entity['lv']) {
                $this->_db->user_credits->update(array('_id' => $uid), array('$set' => array('lv' => $evaluatedLevel)));
            }

            // 直接返回剩余可用积分
            return $entity['c'] - $entity['cc'];
        }

        return false;
    }

    public function validateConstraint($level, $credit) {
        $creditDetail = $this->_db->user_credits->findOne(array('_id' => $this->_uid));
        $leftCredits = $creditDetail['c'] - $creditDetail['cc'];
        if ($creditDetail['lv'] >= $level && $leftCredits >= $credit) {
            return true;
        } else {
            return false;
        }
    }

    public function getInfo($uid) {
        $creditDetail = $this->_db->user_credits->findOne(array('_id' => (int) $uid));

        // 获取到下个等级所需要的积分
        $creditDetail['demanded'] = $this->getNextLevelCredit($creditDetail['lv']);
        return $creditDetail;
    }

    public function getLevel($uid) {
        $creditDetail = $this->_db->user_credits->findOne(array('_id' => (int) $uid), array('lv'));
        if ($creditDetail) {
            return $creditDetail['lv'];
        }

        return 1;
    }

    public function execute($field, $isNew = true, $uid = false) {
        $rewardedCredits = 0;
        $rule = $this->_initRule($field);

        // 规则是否可用
        if ($rule) {
            if ($rule['is_active']) {
                if ($isNew) {
                    switch ($rule['cycle']) {
                        // 每天
                        case 1:
                            $rewardedCredits = $this->executeDaily($rule, $uid);
                            break;

                        // 只有第N次的情况
                        case 2:
                            $rewardedCredits = $this->executeN($rule, $rule['max']);
                            break;

                        // 没有上限
                        default:
                            $rewardedCredits = $rule['credits'];
                            break;
                    }
                } else {
                    // 删除行为，扣除相应的积分
                    $rewardedCredits = - $rule['credits'];
                }
            }
        } else {
            // 规则不存在 log
            return;
        }

        if ($rewardedCredits) {
            $this->updateCredit($rewardedCredits, $uid);
        }

        return;
    }

    private function executeN($rule, $count) {
        $rewardedCredits = 0;

        $status = $this->_db->command(array('findAndModify' => 'first_n'
            , 'query' => array('_id' => $this->_uid)
            , 'update' => array('$inc' => array($rule['_id'] => 1))
            , 'new' => true));

        if ($status['ok']) {
            if (isset($status['value'])) {
                if ($status['value'][$rule['_id']] <= $count) {
                    $rewardedCredits = $rule['credits'];
                }
            }
        }

        return $rewardedCredits;
    }

    private function executeDaily($rule, $uid) {
        $rewardedCredits = 0;

        // 有上限的积分规则
        if ($rule['max'] != -1) {
            $status = $this->updateEntry($rule['_id'], $uid);

            if ($status['ok']) {
                if (isset($status['value'])) {
                    //logging($status['value']);
                    $count = $status['value'][$rule['_id']];

                    // 判断是否有积分奖励
                    if ($count <= $rule['max']) {
                        if (isset($rule['loop']) && $rule['loop'] != 1) {
                            $rewardedCredits = ($count % $rule['loop']) ? 0 : $rule['credits'];
                        } else {
                            $rewardedCredits = $rule['credits'];
                        }
                    }
                } else {
                    // 如果没有当天的记录（目前是加好友）
                    if ($rule['_id'] == 'frd') {
                        $init = array('uid' => $uid, 't' => $this->_time, 'log' => 0, 'ent' => 0
                            , 'like' => 0, 'comm' => 0, 'ask' => 0, 'ans' => 0, 'for' => 0, 'inv' => 0, 'flw' => 0
                            , 'frd' => 0, 'flweb' => 0, 'wiki' => 0, 'acty' => 0, 'att' => 0, 'blog' => 0);

                        $init[$rule['_id']] = 1;
                        $this->_db->statistics->save($init);

                        // 判断是否有积分奖励
                        if (1 <= $rule['max']) {
                            if (isset($rule['loop']) && $rule['loop'] != 1) {
                                $rewardedCredits = ($count % $rule['loop']) ? 0 : $rule['credits'];
                            } else {
                                $rewardedCredits = $rule['credits'];
                            }
                        }
                    } else {
                        // 记录下这里为什么会出现一些问题
                        $this->log(serialize(array('status' => $status, 'n' => $rule['_id'])), $uid);
                    }
                }
            }
        } else {
            // 无上限，不需要再做判断
            return $rule['credits'];
        }

        return $rewardedCredits;
    }

    /**
     * 获取用户等级
     * 
     *  公式： [35 * （x^2 - 1) + 40]  x为等级数
     * 
     * @param int 积分数
     */
    public function evaluateLevel($creditNum, $level) {
        /*
          $levelCredits = array(0, 0, 145, 320, 565, 880, 1265, 1720, 2245, 2840, 3505, 4240, 5045);

          if ($creditNum < $levelCredits[$level + 1]) {
          return $level;
          } else {
          return ++$level;
          }
         */


        if ($creditNum < 135) {
            return 1;
        }

        return floor(sqrt((($creditNum - 40) * 1.0 / 35 + 1)));
    }

    public function getNextLevelCredit($level) {
        $levelCredits = array(0, 0, 145, 320, 565, 880, 1265, 1720, 2245, 2840, 3505, 4240, 5045);
        return $levelCredits[++$level];
    }

    /**
     * 获取相应的积分规则
     * 
     * @param string $ruleName
     */
    private function _initRule($ruleName) {
        return $this->_db->rules->findOne(array('_id' => $ruleName));
    }

    private function log($msg, $uid) {
        $this->_db->logs->save(array('msg' => $msg, 't' => date('Y-m-d H:i:s'), 'uid' => $uid));
    }

}

