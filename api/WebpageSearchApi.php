<?php

/**
 * 网页搜索接口
 */
class WebpageSearchApi extends DkApi {

    protected $webpage;

    public function __initialize() {
        $this->webpage = DKBase::import('WebpageSearch', 'search');
    }

    public function test() {
        $res = $this->deleteUserOfWeb(637, 1000002395);
        echo '<pre>';
        PRINT_R(json_decode($res));
        echo '</pre>';
    }

    /**
     * 关注网页
     * 
     * Enter description here ...
     * @param array $info
     */
    public function addAFansToWeb($info) {//DONE
        return $this->addAFanToAWeb($info);
    }

    /**
     * 取消对网页隐藏
     * 
     * Enter description here ...
     * @param array $info
     */
    public function unHidingAUserInWebpage($info) {//DONE
        return $this->hiddingOrUnhidding($info);
    }

    /**
     * 隐藏网页
     * Enter description here ...
     * @param array $info
     */
    public function hidingAUserInWebpage($info) {//DONE
        return $this->hiddingOrUnhidding($info, 1);
    }

    /**
     * 取消关注网页
     * 
     * Enter description here ...
     * @param int $web_id
     * @param int $user_id
     */
    public function deleteUserOfWeb($web_id=null, $user_id=null) {//DONE
        return $this->webpage->deleteUserOfWeb($web_id, $user_id);
    }

    /**
     * 网页的粉丝(搜索人名)
     * 
     * Enter description here ...
     * @param int $web_id
     * @param string $keyword
     * @param int $page
     * @param int $limit
     */
    public function getFansOfWebpage($web_id = null, $keyword = '', $page=1, $limit=27) {//DONE
        return $this->webpage->getFansOfWebpage($web_id, $keyword, $page, $limit);
    }

    /**
     * 用户关注的网页 (搜索网页)
     * 
     * Enter description here ...
     * @param int $user_id
     * @param int $category_id
     * @param string $keyword
     * @param int $page
     * @param int $limit
     */
    public function getWebpagesByUser($user_id = null, $category_id=null, $keyword = null, $page=1, $limit=27) {//DONE
        return $this->webpage->getWebpagesByUser($user_id, $category_id, $keyword, $page, $limit);
    }

    /**
     * 添加或减少一个粉丝
     * 
     */
    private function addOrReduceAFans($web_id, $is_incr = true) {//DONE
        return $this->webpage->addOrReduceAFans($web_id, $is_incr);
    }

    /**
     * 添加关注
     * 
     * Enter description here ...
     * @param array $info=[web_id,uid,user_dkcode,user_name,following_time,fans_count]
     * @param int $is_hidden 显示:0, 隐藏:1
     */
    private function addAFanToAWeb($info) {//DONE
        return $this->webpage->addAFanToAWeb($info);
    }

    /**
     * 显示或隐藏网页
     * 
     * Enter description here ...
     * @param array $info 
     * @param int $is_hidding 0:显示,1:隐藏
     */
    private function hiddingOrUnhidding($info, $is_hidding=0) {//DONE
        return $this->webpage->hiddingOrUnhidding($info, $is_hidding);
    }

}