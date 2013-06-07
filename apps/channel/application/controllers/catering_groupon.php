<?php

/**
 * Catering Groupon controller
 * @author shedequan
 */
class Catering_groupon extends MY_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('catering_groupon_model', 'groupon');

        // 促销页信息
        $this->assign('index_link', mk_url('channel/catering_groupon/index', array(
                    'web_id' => $this->web_id
                )));
        $this->assign('create_link', mk_url('channel/catering_groupon/create', array(
                    'web_id' => $this->web_id
                )));

        // 网页信息
        $this->web_info['avatar'] = get_webavatar($this->web_info['uid'], 's', $this->web_info['aid']);
        $this->assign('web_link', mk_url('webmain/index/main', array(
                    'web_id' => $this->web_id
                )));
        $this->assign('web_info', $this->web_info);

        $this->page = intval(get_post('page'));
        if ($this->page <= 0) {
            $this->page = 1;
        }
    }

    public function index() {
        $this->common_display();
        $this->display('catering/groupons.html');
    }

    public function get_groupon_page() {
        $this->assign('page_data', '1');
        $this->common_display();
        $this->display('catering/groupons.html');
    }

    private function common_display() {
        $groupons = $this->groupon->all($this->web_id, $this->page);

        // Split flow
        $flows = array();
        foreach ($groupons as $key => $groupon) {
            switch ($key % 3) {
                case 0:
                    $flows['flow1'][] = $groupon;
                    break;
                case 1:
                    $flows['flow2'][] = $groupon;
                    break;
                case 2:
                    $flows['flow3'][] = $groupon;
                    break;
            }
        }
        $this->assign('flows', $flows);

        $this->assign('groupon', $groupons);
        $this->assign('page', ($this->page + 1));
        $this->assign('web_id', $this->web_id);
        $continue_load = count($groupons) < $this->groupon->groupon_list_size ? false : true;
        $this->assign('continue_load', $continue_load);
    }

    public function create() {
        $this->display('catering/groupon_create.html');
    }

    /**
     * 添加发布餐饮促销活动信息
     */
    public function add() {
        // 获得餐饮促销数据，入库
        $groupon = $this->get_groupon_data();
        if (is_string($groupon)) {
            return $this->ajaxReturn('', $groupon, 0, 'jsonp');
        }
        $this->groupon->add($groupon);
        $fid = $this->groupon->get_insert_id();
        // 删除、添加对应的数据
        unset($groupon['uid'], $groupon['web_id']);
        $spaprice = sprintf('%.1f', $groupon['original_price'] - $groupon['current_price']);
        $discount = sprintf('%.1f', ($groupon['current_price'] / $groupon['original_price']) * 10);
        $groupon['spaprice'] = $spaprice;
        $groupon['discount'] = $discount;
        
        // 发布时间线
        $result = $this->save_timeline(array('groupon' => json_encode($groupon)), $fid);
        // 处理请求返回结果
        if (is_string($result)) {
            return $this->ajaxReturn('', $result, 0, 'jsonp');
        } else {
            return $this->ajaxReturn(array('data' => $result), 'operation_success', 1, 'jsonp');
        }
    }

    /**
     * 获取餐饮发布促销活动信息
     */
    private function get_groupon_data() {
        $groupon = array(
            'uid' => $this->uid,
            'web_id' => WEB_ID,
            'title' => get_post('groupname'),
            'original_price' => get_post('oriprice'),
            'current_price' => get_post('currprice'),
            'img' => json_encode($this->input->get_post('imgTag')),
            'etime' => strtotime(get_post('expiretime')), // 过期时间
            'ctime' => time(),
            'utime' => time()
        );
        $webOwner = $this->web_info['uid'];
        if ($webOwner !== $this->uid) {
            return 'not_page_ownner';
        } else if (in_array('', $groupon)) {
            return 'operation_fail';
        } else if (!isMoney($groupon['original_price']) && !isMoney($groupon['current_price']) && !isDomain($groupon['link'])) {
            return 'operation_fail';
        } else if ($groupon['current_price'] > $groupon['original_price']) {
            return 'currprice_less_oriprice';
        }
        $groupon['link'] = get_post('href');
        $groupon['description'] = P('description');
        return $groupon;
    }

    /**
     * 处理发布时间线过期时间、图片信息
     */
    protected function deal_groupon_data($res) {
        $groupon = json_decode($res['groupon'], true);
        $diff = $groupon['etime'] - time();
        $res['diff'] = $diff > 0 ? $diff : 0;
        $pics = $this->loop_update_img(json_decode($groupon['img'], true));
        $groupon['img'] = $pics;
        $groupon['link'] = mk_url('channel/catering_groupon/detail_page',array('id'=>$res['fid'], 'web_id'=>$res['pid']));
        $res['groupon'] = $groupon;
        return $res;
    }

    public function remove() {
        $fid = get_post('id');
        $this->groupon->remove($fid);
        $delStatus = service('WebTimeline')->delWebtopicByMap($fid, 'dish', $this->getWebpageTagID($this->web_id), $this->web_id);
        if ($delStatus) {
            return $this->ajaxReturn('', 'operation_success', 1, 'jsonp');
        } else {
            return $this->ajaxReturn('', 'operation_fail', 0, 'jsonp');
        }
    }
    
    /**
     * 促销活动详细页
     */
    public function detail_page() {
    	$id = intval(G('id'));
    	$groupon = $this->groupon->get($id);
    	if(empty($groupon)) {
    		return 'operation_fail';
    	}
    	$diff = $groupon['etime'] - time();
    	$groupon['diff'] = $diff > 0 ? $diff : 0;
    	$groupon['spare_price'] = sprintf('%.0f', $groupon['original_price'] - $groupon['current_price']);
    	$groupon['discount'] = sprintf('%.1f', ($groupon['current_price'] / $groupon['original_price']) * 10);
    	$pics = $this->loop_update_img(json_decode($groupon['img'], true));
    	$groupon['img'] = $pics;
    	$groupon['msgurl'] = mk_url('channel/catering_groupon/detail_page',array('id'=>$groupon['id'], 'web_id'=>$this->web_id));
    	$this->assign('groupon', $groupon);
 	
    	$this->display('catering/groupon_detail.html');
    }

    /**
     * 菜谱小贴士
     */
    public function get_dish_tips() {
        $this->load->model('catering_dish_model', 'dish');
        return $this->dish->get_dishs(WEB_ID);
    }

}