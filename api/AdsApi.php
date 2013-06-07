<?php

/**
 * 
 * 广告充值
 * @author hujiashan
 *
 */
class AdsApi extends DkApi {

    protected $ads;

    public function __initialize() {
        $this->ads = DKBase::import('Ads', 'ads');
    }

    /**
     * 
     * 充值接口
     * @author hujiashan
     * @date 2012-07-07
     * @param array $data
     * $data = array(
     * 		  'cid' => $cid 广告商ID 
     * 		  'orderid' => $orderid   交易号
     * 		  'uid' => 	$uid , 用户ID
     * 		  'type'    =>  $type, 分类（网银 1 支付宝 2 其他 3
     *        'content' =>  $content 交易内容
     *        'descriiption'  => $descriiption 描述
     * 		  'money'    =>  $money, 金额
     *        'state'    =>  $state, 充值状态(0未支付 1成功 3出错
     *        'dateline'    =>  $dateline, 充值时间
     * )
     */
    public function paid($data = array()) {
        return $this->ads->paid($data);
    }

    /**
     * 
     * 查看广告商信息
     * @author hujiashan
     * @date 2012-07-07
     * @param int $uid
     * @param string $field
     */
    function get($uid = NULL, $field = ' * ') {
        return $this->ads->get($uid, $field);
    }

    /**
     * 
     * 删除充值记录
     * @author hujiashan
     * @date  2012-07-13
     * @param string $orderid
     * @param int $uid
     */
    function delete($orderid = NULL, $uid = NULL) {
        return $this->ads->delete($orderid, $uid);
    }

    /**
     * 
     * 通过 交易号获取广告ID
     * @param string $orderid
     */
    function get_ad_id($orderid = NULL, $field = ' * ', $where = NULL) {
        return $this->ads->get_ad_id($orderid, $field, $where);
    }

    public function pay($data = array()) {
        return $this->ads->pay($data);
    }

    /**
     * @author: qianc
     * @date: 2012-7-28
     * @desc: 获取广告列表
     * @access public
     * @return array
     */
    function getAds($nowpage =0, $limit=8, $where, $orderby) {
        //此处有bug，$where没有使用
        return $this->ads->getAds($nowpage, $limit,$where, $orderby);
    }

    /**
     * 	取得广告详情
     *  @author	    qianc
     * 	@date	    2012/7/30
     * @return		array
     */
    function getAdInfo($where) {
        return $this->ads->getAdInfo($where);
    }

    /**
     * 	取得随机广告
     *  @author	    qianc
     * 	@date	    2012/7/30
     * @return		array
     */
    function getAdRandom($where, $num) {
        return $this->ads->getAdRandom($where, $num);
    }

    /**
     * 	取得广告分成相关数据
     *  @author	    qianc
     * 	@date	    2012/8/6
     * @return		array
     */
    function getAdPayAssign($dkcode) {
        return $this->ads->getAdPayAssign($dkcode);
    }

}

?>