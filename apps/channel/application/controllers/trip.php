<?php
/**
 *@author wangh
 *@date   2012-07-24
 *@旅游景点频道
 */
 class trip extends MY_Controller {

 	function __construct(){
 		parent::__construct();
 		$this->load->model("tripmodel");


 		   // 超值行程信息
        $this->assign('index_link', mk_url('channel/trip/index', array(
                    'web_id' => $this->web_id
                )));
        $this->assign('create_link', mk_url('channel/trip/createTrip', array(
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
 	/**
 	 *@景点应用区
 	 *@author  wangh
 	 *@date    2012-07-25
 	 *@descripton   超值行程
 	 *
 	 */
 	public function index(){
        $this->assign('page_data', '1');
        $this->common_display();
 		$this->display('trip/index');

 	}
 	public function createTrip(){
 		$this->display('trip/trip_add.html');
 	}
 	private function common_display() {
        $travels = $this->tripmodel->all($this->web_id, $this->page);
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
        $continue_load = count($travels) < $this->tripmodel->trip_list_size ? false : true;
        $this->assign('continue_load', $continue_load);
    }
 	/**
 	 *@超值行程数据存储mysql及入驻在redis中
 	 *@author  wangh
 	 *date     2012-08-07
 	 */
 	 public function addTravelDate(){
 	 	$traveldate = array();
 	 	$traveldate = array(
 	 	      'uid' => $this->uid,
 	 	      'web_id'=> get_post('web_id'),
 	 	      'name'=> $this->web_info['name'],
 	 	      'description'=> P('disc'),
 	 	      'pics'=> json_encode($this->input->get_post('imgTag')),
 	 	      'price'=> get_post('price'),
 	 	      'link'=> get_post('link'),
 	 	      'createtime'=> get_post('timestr'),
 	 	      'edittime'=> get_post('timestr')

 	 	);
 	 	//print_r($traveldate);die;
 	 	$webOwner = $this->web_info['uid'];
        if ($webOwner !== $this->uid) {
            return $this->ajaxReturn('', '不是网页创建者', 0, 'jsonp');
        } else if (in_array('', $traveldate)) {
            return $this->ajaxReturn('', '参数错误', 0, 'jsonp');
        }else if(is_string($traveldate)){
        	return $this->ajaxReturn('', '接收参数异常', 0, 'jsonp');
        }else {
        	$this->tripmodel->add($traveldate);
        	$fid = $this->tripmodel->get_insert_id();
        	// 发布时间线
        	 unset($traveldate['uid'], $traveldate['web_id']);
            $result = $this->save_timeline(array('travel' => json_encode($traveldate)), $fid);
        	return $this->ajaxReturn(array('data'=>$result), 'ok', 1, 'jsonp');
        }

 	 }
 	 /**
	 * 处理发布时间线图片数据
	 */
	public function deal_travel_data($res) {
		$travel = json_decode($res['travel'], true);
		$pics = $this->loop_update_img(json_decode($travel['pics'], true));
		//下面这句有问题 修改返回键值 by lanyanguang 2012/8/10
        $travel['pics'] = $pics;
		$res['travel'] = $travel;
		return $res;
	}
 }
?>
