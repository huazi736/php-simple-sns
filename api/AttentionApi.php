<?php

/**
 * 网页关注接口
 * author heyuejuan
 */
class AttentionApi extends DkApi {

    protected $attention;

    public function __initialize() {
        $this->attention = DKBase::import('Attention', 'webpage');
    }
    
    /*
     * 加关注时   保存关注人的分类数据  与   网页的粉丝数
     * $uid   		用户id
     * $aid			网页id
     * $fans_count	网页的粉丝数
     * 
     * $action_time 操作时间
     * $expiry_time	关注失效时间
     */

    public function add_attention($uid, $aid, $fans_count, $action_time =0, $expiry_time=0) {
        return $this->attention->add_attention($uid, $aid, $fans_count, $action_time, $expiry_time);
    }

    /**
     * 修改关注时间
     * @param $uid   		用户id
     * @param $webid		网页id
     * @param $action_time  操作时间
     * @param $expiry_time	关注失效时间
     * @author boolee 2012/6/26
     */
    public function updateAttentionTime($uid, $webid, $action_time =0, $expiry_time=0) {
        return $this->attention->updateAttentionTime($uid, $webid, $action_time, $expiry_time);
    }

    /*
     * 删除加关注时   要处理分类数据与   网页的粉丝数据
     * $uid   		用户id
     * $aid			网页id
     * $fans_count	网页的粉丝数
     */

    public function del_attention($uid, $aid, $fans_count) {
        return $this->attention->del_attention($uid, $aid, $fans_count);
    }

    /**
     * 获得  网页里的关注 分类     （关注）
     * $uid   		uid用户名
     * $is_display	是否显示隐藏的分类	1 显示 ， 0 不显示
     * * */
    public function get_attention_category($uid, $is_display=0, $channel_id = 0) {
        return $this->attention->get_attention_category($uid, $is_display, $channel_id);
    }
	
    /**
     * 获得有效的关注分类
     * $uid		用户id		
     * 
     * **/
    public function get_attention_invalid_category($uid){
    	return $this->attention->get_attention_invalid_category($uid);
    	
    }
    
    
    /*     * *
     * 设置分类显示与隐藏   （关注）
     * $uid 	用户id
     * $iid		分类id
     * $is_show	是否显示   0 不显示  1 显示
     * * */

    public function set_attention_category_show($uid, $iid, $is_show=0) {
        return $this->attention->set_attention_category_show($uid, $iid, $is_show);
    }

    /*     * *
     * 获得   分类里的网页数据     （关注）    (如果 $is_display=0时   action_uid 等于网页人的id那么也显示)
     * $uid 	用户id
     * $iid		分类id
     * $is_display 是否显示隐藏的分类	1 显示 ， 0 不显示
     * $start   从第几条记录取起
     * $limit   取多少个
     * 
     * $action_uid	当前活动的用户id
     * * */

    /**
     * 获取用户关注的网页
     *
     * 根据关注网页所属分类获取
     *
     * @author zengmm
     * @date 2012/8/6
     *
     * @history <heyuejuan>
     *
     * @param int $uid 用户UID
     * @param int $cateid 分类ID
     * @param boolean $is_self 是否是自己(用于是否显示用户隐藏的关注网页)
     * @param int $offset 记录开始值
     * @param int $limit 记录偏移值
     * @param int $visituid 访问者UID
     * @param boolean $is_channel 为true时,根据一级分类获取(频道);为false时,根据二级分类获取
     * 
     */
    public function get_attention_web($uid, $cateid, $is_self, $offset, $limit, $visituid = 0, $is_channel = FALSE) {
        return $this->attention->get_attention_web($uid, $cateid, $is_self, $offset, $limit, $visituid, $is_channel);
    }
	/**
     * @abstract 过期关注分页数据
     * @author boolee 2012/7/13
     * @param $uid 	用户id
     * @param $iid		分类id
     * @param $is_display 是否显示隐藏的分类	1 显示 ， 0 不显示
     * @param $start   从第几条记录取起
     * @param $limit   取多少个
     * @param $action_uid	当前活动的用户id
     **/
	public function get_unvalidate_attention_web($uid, $iid, $is_display, $start, $limit, $action_uid=0) {
        return $this->attention->get_unvalidate_attention_web($uid, $iid, $is_display, $start, $limit, $action_uid);
    }
    /**
     * 批量设置网页显示与隐藏   （关注）|按照分类进行操作
     * @author boolee 2012/8/3
     * @param $uid 	用户id
     * @param $iid		网页分类
     * @param $is_show	是否显示   0 不显示  1 显示
     * * */
    public function set_attention_webs_show($uid, $iid, $is_show=0) {
         $result1 = $this->attention->set_web_category_show($uid, $iid, $is_show);  //指定目录隐藏
         $result2 = $this->attention->set_attention_webs_show($uid, $iid, $is_show);//多个网页隐藏
         return $result1 && $result2;
    }
    /**
     *通过分类获取web_id
     * @author boolee
     * @date 2012/8/3/
     * @param $select 查询字段
     * @param $condition 查询条件
     **/
    function get_web_id_by_iid($uid, $iid){
    	return $this->attention->get_fields('aid', 'iid='. $iid .' && uid='.$uid);
    }
	/**
     * 设置网页显示与隐藏   （关注）
     * $uid 	用户id
     * $aid		网页id
     * $is_show	是否显示   0 不显示  1 显示
     * * */

    public function set_attention_web_show($uid, $aid, $is_show=0) {
        return $this->attention->set_attention_web_show($uid, $aid, $is_show);
    }
    /**
     * 获得用户 关注的所有网页的  数据      按粉丝数排序
     * $uid		用户id
     * $start	起始值
     * $limit	取多少
     * */
    public function get_attention_name($uid, $start=0, $limit=9) {
        return $this->attention->get_attention_name($uid, $start, $limit);
    }

    /**
     * 设置网页的粉丝数
     * 
     * * */
    function set_fans_count($aid, $count) {
        return $this->attention->set_fans_count($aid, $count);
    }

    // 设置词 条的粉丝数
    function set_entry_count($aid) {
        return $this->attention->set_entry_count($aid);
    }

    /**
     * 传 aid
     * 获得网页  二级分类 id  
     * * */
    function aid_get_iid($aid) {
        return $this->attention->aid_get_iid($aid);
    }

    // 插入  用户关注分类数据
    public function add_attention_idd($arr) {
        return $this->attention->add_attention_idd($arr);
    }

    /**
     * 添加  注入里显示的兴趣分类
     * * */
    public function add_attention_category($arr) {
        return $this->attention->add_attention_category($arr);
    }

    // 删除关注
    public function del_attention_idd($aid, $imid, $iid, $uid) {
        return $this->attention->del_attention_idd($aid, $imid, $iid, $uid);
    }

    // 查询关注的分类
    public function get_user_iid($uid, $iid) {
        return $this->attention->get_user_iid($uid, $iid);
    }

    // 删除  注入里显示的兴趣分类
    public function del_attention_category($uid, $iid) {
        return $this->attention->del_attention_category($uid, $iid);
    }

	/**
	 * 获取用户对网页的隐藏状态
	 *
	 * @author zengmm
	 * @date 2012/7/14
	 *
	 * @param int $uid 用户UID
	 * @param int|array $webpge_ids 网页ID
	 *
	 * @return int|array
	 */
	public function getWebpageHiddenStatus($uid = 0, $webpage_ids = array())
	{
		return $this->attention->getWebpageHiddenStatus($uid, $webpage_ids);
	}

    /**
     * 获取用户关注的网页频道
     *
     * @todo 用于个人首页左边导航
     *
     * @author zengmm
     * @date 2012/7/30
     *
     * @param int $uid 用户UID
     *
     * @return array
     */
    public function getFollowingChannel($uid)
    {
        return $this->attention->getFollowingChannel($uid);
    }

    /**
     * 获取用户最近关注的网页
     *
     * 当前方法根据频道ID获取
     *
     * @author zengmm
     * @date 2012/8/1
     *
     * @param int $uid 被访问者UID
     * @param int $imid 网页所属的频道ID
     * @param int $offset 页码
     * @param int $limit 偏移量
     *
     * @return array
     */
    public function getNewestFollowingWebpage($uid, $imid, $offset = 0, $limit = 20)
    {
        return $this->attention->getNewestFollowingWebpage($uid, $imid, $offset, $limit);
    }


}

?>