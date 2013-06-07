<?php
/**
 * 网页资料接口
 * @author guojianhua
 */
class WebwikiApi extends DkApi {

    protected $webWiki;

    public function __initialize() {
        $this->webWiki = DKBase::import('TheWebWiki', 'webpage');
    }
    
	/**
	 * 获取
	 * @author guojianhua
	 * @date 2012/07/12
	 * @param int | array $web_id
	 * @param int $length
	 * @return Array
	 */
	function getWebDesc($web_id = array(),$length = 140) {
        return $this->webWiki->getWebDesc($web_id, $length);
	}
    
}