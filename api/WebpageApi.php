<?php

/**
 * 网页信息接口
 * @author shedequan
 */
class WebpageApi extends DkApi {

    protected $webpage;

    public function __initialize() {
        $this->webpage = DKBase::import('Webpage', 'webpage');
    }

    public function test() {
        return $this->webpage->test();
    }

    /**
     * 设置网页应用区菜单的封面
     *
     * @author zengmingming
     * @date 2012/7/5
     *
     * @param int $webid 网页id
     * @param int $menuid 应用菜单ID
     * @param string $imgpath 菜单图片地址
     * @param string $group FASTDFS的分组
     *
     * @return boolean
     */
    public function setAppMenuCover($webid, $menuid, $imgpath, $group = '') {
        return $this->webpage->setAppMenuCover($webid, $menuid, $imgpath, $group);
    }

}