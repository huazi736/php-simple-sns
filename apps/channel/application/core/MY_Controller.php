<?php

class MY_Controller extends DK_Controller {
	
	protected $is_check_login = true;
	
	// 标识来源于网页
	const TOPIC_FROM_WEB = 4;
	
	public function __construct() {
		
		//Flash上传不验证用户登录  add by guojianhua
		$flash_uid = isset($_GET['flashUploadUid']) ? $_GET['flashUploadUid'] : 0;
    	if(!empty($flash_uid)){
    		$this->is_check_login = false;
    	}
		
		parent::__construct();
		
		//判断网页是否自己本人的 add by lanyanguang 2012/04/26
        $this->_isSelf();
		
		$this->load->helper('channel');
		$this->load->helper('validate');
	}
	
	
    /**
     * 判断网页是否本人的 add by lanyanguang 2012/04/26
     */
    private function _isSelf() {
        if (isset($this->web_info['uid'])) {
            if ($this->uid == $this->web_info['uid']) {
                $this->is_self = true;
            }
        } else {
            $this->is_self = true;
        }
    }
	
	
	/**
	 * 发布时间线公用方法
	 * @param $tpl_data 业务模板请求数据
	 */
	protected function save_timeline($tpl_data, $fid='') {
		// 获取请求时间线数据
		$time_data = $this->get_time_line_data();
		
		$data = array(
				'uid' => $this->uid,
				'dkcode' => $this->dkcode,
				'uname' => $this->web_info['name'],
				'title' => date('Y-m-d H:i:s', SYS_TIME),
				'from' => self::TOPIC_FROM_WEB,
				'pid' => WEB_ID,
				'dateline' => date('YmdHis', SYS_TIME) 
		);
		if(!empty($fid)) {
			$data['fid'] = $fid;
		}
		$data['type'] = $time_data['type'];
		// 内容处理
		$data['content'] = '';
		
		$data['timedesc'] = $time_data['timedesc'];
		$data['ctime'] = preg_replace_callback('/(?P<year>\d+)(-?)(?P<mon>\d{0,2})(-?)(?P<day>\d{0,2})/', function ($match) {
			(int)$match['mon'] < 10 && !($match['mon'] = '0' . $match['mon']) && $match['mon'] = '01';
			(int)$match['day'] < 10 && !($match['day'] = '0' . $match['day']) && $match['day'] = '01';
			return $match['year'] . min($match['mon'], 12) . min($match['day'], 31);
		}, $time_data['timestr'] ?  : date('Y-n-j', SYS_TIME));
		if ($time_data['bc'] < 1) {
			// 公元前
			$data['ctime'] = '-' . $data['ctime'] . '000000';
		} else {
			// 公元后
			$data['ctime'] = $data['ctime'] . ($data['ctime'] == date('Ymd', SYS_TIME) ? date('His', SYS_TIME) : '000000');
		}
		
		$data = array_merge($data, $tpl_data);
		$result = service('WebTimeline')->addWebtopic($data, $this->getWebpageTagID($data['pid']));
		if ($result === false) {
			return 'operation_fail';
		}
		$result = $this->resultHanler($result);
		
		service('RelationIndexSearch')->addOrUpdateStatusInfo(json_encode($result));
		return $result;
	}
	
	/**
	 * 处理发布时间线返回后的数据
	 * @param $result 时间线topic
	 */
	private function resultHanler($result) {
		// 调用子类处理相应的数据
		$subClassMethod = 'deal_'.$result['type'].'_data';
		if(method_exists($this, $subClassMethod))
		{
			$result = $this->$subClassMethod($result);
		}
		return $result;
	}
	
	/**
	 * 返回时间线需要数据
	 */
	private function get_time_line_data() {
		$timeline = array(
				'type' => get_post('type'),
				'timedesc' => get_post('timedesc'),
				'timestr' => get_post('timestr'),
				'bc' => get_post('bc')
		);
		return $timeline;
	}
	
	/**
	 * 通过网页ID获取网页的标签
	 *
	 * @author fbbin
	 * @param int $pageid
	 */
	protected function getWebpageTagID($pageid) {
		return service('Interest')->get_web_category_imid($pageid) ?  : array();
	}
	
	protected function loop_update_img($pics) {
		if(is_array($pics)) {
			foreach($pics as $_k=>$_v) {
				$pics[$_k]['b']['url'] = 'http://' .getFastdfs(). '/' . $pics[$_k]['b']['url'];
				$pics[$_k]['s']['url'] = 'http://' .getFastdfs(). '/' . $pics[$_k]['s']['url'];
			}
		} else {
    		$pics[0]['b']['url'] = '';
    		$pics[0]['s']['url'] = '';
    	}
		return $pics;
	}
}