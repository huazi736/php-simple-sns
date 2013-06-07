<?php

/**
 * 获取首页时间线，信息流数据
 * 
 * @author 应晓斌
 *
 */
class Timeline extends DK_Controller {
	
	/**
	 * 获取某个网站的时间线数据
	 */
	public function getTimelineYears()
	{
		if ($this->isAjax()) {
			$webId = intval($_POST['web_id']);
			
			if (empty($webId)) {
				echo json_encode(array('status' => 0, 'msg' => '传递参数不正确'));
				exit();
			}
			
			// 获取网页的创建时间
			$createYear = substr($this->web_info['create_time'], 0, 7);
			$this->load->model('TimelineModel');
			$data = $this->TimelineModel->getTimelineYears($webId, $createYear);
			echo json_encode(array('status' => 1, 'data' => $data));
			exit();
		}
	}
    
	/**
	 * 获取年度热点
	 * 
	 * @param int $uid  被访问的用户ID
	 */
	public function getYearHottestFeeds()
	{	
		if ($this->isAjax()) {
			$webId = $_POST['webId'];
			$year = $_POST['year'];
			
			if (empty($webId) || empty($year)) {
				echo json_encode(array('status' => 0, 'msg' => '传递参数不正确'));
				exit();
			}
			$this->load->model('TimelineModel');
			echo json_encode($this->TimelineModel->getYearHottestFeeds($webId, $year));
			exit();
		}
	}
	
	/**
	 * 获取时间轴上的用户数据
	 * 
	 * @param int $uid  被访问的用户ID
	 */
	//public function getFragmentFeeds($uid, $year, $month, $startScore, $lastTopicId, $page)
	public function getFragmentFeeds()
	{
		if ($this->isAjax()) {
			$webId = (int)$_POST['webId'];
			$year = (int)$_POST['year'];
			$month = (int)$_POST['month'];
			$lastTopicId = isset($_POST['lastTopicId']) ? $_POST['lastTopicId'] : 0;
			$startScore = isset($_POST['startSocre']) ? $_POST['startScore'] : 0;
			$page = isset($_POST['page']) ? $_POST['page'] : 0;
			
			if (empty($webId) || empty($year) || empty($month)) {
				echo json_encode(array('status' => 0, 'msg' => '传递参数不正确'));
				exit();
			}
			
			$this->load->model('TimelineModel');
			echo json_encode($this->TimelineModel->getFragmentFeeds($webId, $year, $month, $lastTopicId, $startScore));
			exit();
		}
	}
	
        
    public function updateHighlight() {
    	if ($this->isAjax()) {
    		$res = array(
    				'status' => 0
    		);
    		if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    			$tid = $this->input->post('tid');
    			$highlight = $this->input->post('highlight');
    			//$fiends = call_soap('timeline', 'Timeline', 'updateTimelineHighlight', array('tid' => $tid, $highlight));
    			$fiends   = service('Timeline')->updateTimelineHighlight($tid,$highlight);
    			$res['status'] = 1;
    		}
    		return json_encode($res);
    		exit();
    	}
    }
    
    //获取用户的时间描述名称，post请求，传入uid
    public function getAliasOfDate() {
        $res = array('status' => 0, 'data' => array());
        
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $pid = $this->input->post('webId');
            $date = $this->input->post('date');
            $this->load->model('TimelineModel');
            $aliases = $this->TimelineModel->getDateAliases($pid, $date);
            if ($aliases !== false) {
                $res['status'] = 1;
                $res['data'] = $aliases;
            }
        }
        die(json_encode($res));
    }
    
    public function test() {
        $uids = array(1000001001,1000001002,1000001003);
        $res = call_soap('social', 'Social', 'getMultiUserInfo', array('uids' => $uids));
        //$res = call_soap('social', 'Webpage', 'getAllFollowers', array('pageid' => '584'));
        var_dump($res);
    }
    
}
