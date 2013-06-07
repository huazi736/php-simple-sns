<?php

/**
 * 网页活动接口
 * @author hpw
 */
class WebEventApi extends DkApi {

    protected $webEvent;

    public function __initialize() {
        $this->webEvent = DKBase::import('TheWebEvent', 'app');
    }
    
    /**
     * 删除所有该网页的活动
     * @author hpw
     * @date 2012/07/13
     * @param int $webId  网页Id
     * @return bool
     */
    function delEvent($webId) {
        return $this->webEvent->delEvent($webId);
    }

    /**
     * 删除一个的活动
     * @author hpw
     * @date 2012/08/07
     * @param int $webId  网页Id
     * @param int $eventid 活动id
     * @return bool
     */
    function delOne($webId, $eventId) {
        return $this->webEvent->delOne($webId, $eventId);
    }

}