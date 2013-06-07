<?php

/**
 * @author yinxiaobing
 */
class CronApi extends DkApi {

    protected $cron;

    public function __initialize() {
        $this->cron = DKBase::import('Cron', 'credit');
    }

    /**
     * 生成全站积分等级排行的数据
     */
    public function rebuilingRankingList() {
        return $this->cron->rebuilingRankingList();
    }

    /**
     * 清除所有未在10分钟之内完成兑换的兑换记录
     */
    public function clearRedeems() {
        return $this->cron->clearRedeems();
    }

}