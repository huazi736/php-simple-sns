<?php
/**
 *景点频道---物价机票
 */
 class airticket extends MY_Controller{

 	function __construct(){
 		parent::__construct();
 		$this->load->model("airticketmodel");
 		  // 物价机票信息
        $this->assign('index_link', mk_url('channel/airticket/index', array(
                    'web_id' => $this->web_id
                )));
        $this->assign('create_link', mk_url('channel/airticket/create', array(
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
 	public function index(){
 		$this->assign('page_data', '1');
        $this->common_display();
 		$this->display('trip/airticket.html');
 	}
 	/**
 	 *接收数据入驻时间线信息流并插入mysql
 	 *@author  wangh
 	 *@date     2012-08-11
 	 *@return   jsonp
 	 */
 	public function addAirticket(){
 		$airticketdata = array();
 		$airticketdata = array(
                  'uid' => $this->uid,
                  'web_id' => WEB_ID,
                  'gocity' => get_post('setout_city'),
                  'returntrip' => get_post('arrive_city'),
                  'andfromtime' => get_post('timestr'),
                  'price' => get_post('ticket_price'),
                  'rate' => get_post('discount'),
                  'link' => get_post('ticket_link'),
                  'travelsigns' => get_post('ticket_sort'),
                  'createtime' => time(),
                  'edittime' => time()
 		);
 		$webOwner = $this->web_info['uid'];
        if ($webOwner !== $this->uid) {
            return $this->ajaxReturn('', '不是网页创建者', 0, 'jsonp');
        } else if (in_array('', $airticketdata)) {
            return $this->ajaxReturn('', '参数错误', 0, 'jsonp');
        }else if(is_string($airticketdata)){
        	return $this->ajaxReturn('', '接收参数异常', 0, 'jsonp');
        }else {
        	$this->airticketmodel->add($airticketdata);
        	$fid = $this->airticketmodel->get_insert_id();
        	// 发布时间线
        	unset($airticketdata['uid'], $airticketdata['web_id']);
            $result = $this->save_timeline(array('airticket' => json_encode($airticketdata)), $fid);
        	return $this->ajaxReturn(array('data'=>$result), 'ok', 1, 'jsonp');
        }
 	}
 	 /**
	 * 处理发布时间线图片数据
	 */
	public function deal_airticket_data($res) {
		$airticket = json_decode($res['airticket'], true);
		//下面这句有问题 修改返回键值 by lanyanguang 2012/8/10
		$res['airticket'] = $airticket;
		return $res;
	}
	private function common_display() {
        $travels = $this->airticketmodel->all($this->web_id, $this->page);
        //print_r($travels);die;

        // Split flow
        $flows = array();
        foreach ($travels as $key => $travel) {
            switch ($key % 3) {
                case 0:
                    $flows['flow1'][] = $travel;
                    break;
                case 1:
                    $flows['flow2'][] = $travel;
                    break;
                case 2:
                    $flows['flow3'][] = $travel;
                    break;
            }
        }
        $this->assign('flows', $flows);

        $this->assign('groupon', $travels);
        $this->assign('page', ($this->page + 1));
        $this->assign('web_id', $this->web_id);
        $continue_load = count($travels) < $this->airticketmodel->airticket_list_size ? false : true;
        $this->assign('continue_load', $continue_load);
	}
 }
?>
