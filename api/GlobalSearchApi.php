<?php

/**
 * 全局搜索接口
 */
class GlobalSearchApi extends DkApi {

    protected $global;

    public function __initialize() {
        $this->global = DKBase::import('GlobalSearch', 'search');
    }

    public function test() {
        echo $keyword = '你好理发师';
        $res = $this->getEventList($keyword);
        echo '<pre>';
        PRINT_R($res);
        echo '</pre>';
    }

    public function getStatisticsByGroup($keyword) {
        return $this->global->getStatisticsByGroup($keyword);
    }

    /**
     * 获取人名与网页各8条的搜索
     * 
     * Enter description here ...
     * @param string $keyword 关键词
     */
    public function getPeopleAndWebsite($keyword = null, $start = 0, $limit = 8) {
        return $this->global->getPeopleAndWebsite($keyword, $start, $limit);
    }

    /**
     * 搜索人名
     * 
     * Enter description here ...
     * @param string $keyword 关键词
     * @param int $start 开始位置
     * @param int $limit 显示条数
     */
    public function getPeopleList($keyword, $start=0, $limit=10, $condition = array()) {
        return $this->global->getPeopleList($keyword, $start, $limit, $condition);
    }

    /**
     * 搜索网页
     * 
     * Enter description here ...
     * @param string $keyword 关键词
     * @param int $start 开始位置
     * @param int $limit 显示条数
     */
    public function getWebPageList($keyword, $start=0, $limit=10) {
        return $this->global->getWebPageList($keyword, $start, $limit);
    }

    /**
     * 搜索状态
     * 
     * Enter description here ...
     * @param string $keyword 关键词
     * @param int $start 开始位置
     * @param int $limit 显示条数
     */
    public function getStatusList($keyword, $start=0, $limit=10) {
        return $this->global->getStatusList($keyword, $start, $limit);
    }

    /**
     * 搜索图片
     * 
     * Enter description here ...
     * @param string $keyword 关键词
     * @param int $start 开始位置
     * @param int $limit 显示条数
     */
    public function getPhotoList($keyword, $start=0, $limit=10) {
        return $this->global->getPhotoList($keyword, $start, $limit);
    }

    /**
     * 搜索相册
     * 
     * Enter description here ...
     * @param string $keyword 关键词
     * @param int $start 开始位置
     * @param int $limit 显示条数
     */
    public function getAlbumList($keyword, $start=0, $limit=10) {
        return $this->global->getAlbumList($keyword, $start, $limit);
    }

    /**
     * 搜索视频
     * 
     * Enter description here ...
     * @param string $keyword 关键词
     * @param int $start 开始位置
     * @param int $limit 显示条数
     */
    public function getVideoList($keyword, $start=0, $limit=10) {
        return $this->global->getVideoList($keyword, $start, $limit);
    }

    /**
     * 搜索博客
     * 
     * Enter description here ...
     * @param string $keyword 关键词
     * @param int $start 开始位置
     * @param int $limit 显示条数
     */
    public function getBlogList($keyword, $start=0, $limit=10) {
        return $this->global->getBlogList($keyword, $start, $limit);
    }

    /**
     * 搜索问答
     * 
     * Enter description here ...
     * @param string $keyword 关键词
     * @param int $start 开始位置
     * @param int $limit 显示条数
     */
    public function getQuestionAndAnswerList($keyword, $start=0, $limit=10) {
        return $this->global->getQuestionAndAnswerList($keyword, $start, $limit);
    }

    /**
     * 搜索活动
     * 
     * Enter description here ...
     * @param string $keyword 关键词
     * @param int $start 开始位置
     * @param int $limit 显示条数
     */
    public function getEventList($keyword, $start=0, $limit=10) {
        return $this->global->getEventList($keyword, $start, $limit);
    }

}