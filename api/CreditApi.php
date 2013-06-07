<?php

/**
 * @author yinxiaobing
 */
class CreditApi extends DkApi {

    protected $credit;

    public function __initialize() {
        $this->credit = DKBase::import('TheCredit', 'credit');
    }

    public function invite($uid) {
        $this->execute('inv', true, $uid);
    }

    public function register() {
        return $this->credit->register();
    }

    public function cover() {
        $this->execute('cov');
    }

    public function avatar() {
        $this->execute('ava');
    }

    public function profile($module) {
        if (in_array($module, array('home', 'basic', 'intro', 'work', 'edu'))) {
            $this->execute($module);
        }
    }

    public function login() {
        return $this->credit->login();
    }

    public function web($isNew = true, $uid = false) {
        $this->execute('web', $isNew, $uid);
    }

    public function do_status($isNew = true) {
        $this->execute('ent', $isNew);
    }

    public function album($isNew = true, $uid = false) {
        $this->execute('ent', $isNew, $uid);
    }

    public function video($isNew = true, $uid = false) {
        $this->execute('ent', $isNew, $uid);
    }

    public function blog($isNew = true) {
        $this->execute('ent', $isNew);
    }

    public function ask($isNew = true) {
        $this->execute('ent', $isNew);
    }

    public function forward() {
        $this->execute('for');
    }

    public function comment() {
        $this->execute('comm');
    }

    public function like() {
        $this->execute('like');
    }

    public function follow() {
        $this->execute('flw');
    }

    /**
     * 用户确认好友时双方加积分
     * 
     * @param int $u1
     * @param int $u2
     */
    public function friend($u1, $u2) {
        $this->_friend((int) $u1);
        $this->_friend((int) $u2);
    }

    private function _friend($uid) {
        $this->execute('frd', true, $uid);
    }

    public function followWeb() {
        $this->execute('flweb');
    }

    public function activity($isNew = true) {
        $this->execute('acty', $isNew);
    }

    public function attend() {
        $this->log(serialize(func_get_args()), $this->_uid);
        $this->execute('att');
    }

    public function cancelAttend($uid) {
        $this->execute('att', false);
    }

    public function suggestion() {
        $this->execute('sugg');
    }

    public function complaint() {
        $this->execute('comp');
    }

    /**
     * 积分消费
     * 
     * @param int $credits
     */
    public function consume($credits) {
        return $this->credit->consume($credits);
    }

    public function validateConstraint($level, $credit) {
        return $this->credit->validateConstraint($level, $credit);
    }

    public function getInfo($uid) {
        return $this->credit->getInfo($uid);
    }

    public function getLevel($uid) {
        return $this->credit->getLevel($uid);
    }

    private function execute($field, $isNew = true, $uid = false) {
        return $this->credit->execute($field, $isNew, $uid);
    }

    /**
     * 获取用户等级
     * 
     *  公式： [35 * （x^2 - 1) + 40]  x为等级数
     * 
     * @param int 积分数
     */
    public function evaluateLevel($creditNum, $level) {
        return $this->credit->evaluateLevel($creditNum, $level);
    }

    public function getNextLevelCredit($level) {
        return $this->credit->getNextLevelCredit($level);
    }

}