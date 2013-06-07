<?php
class Movies extends MY_Controller
{
	function __construct()
	{
		parent::__construct();
		
		$this->redisdb 	= get_redis('user');
		
		$this->load->model('moviesmodel' , 'mv_model');
		
		$target	= @$this->input->get_post('target');
		
		$this->init_channel();
		$this->assign('user' , $this->user);
		$this->assign('target' , $target );
		$this->assign('uid',$this->uid);
	}

	function index()
	{
		$this->main();
	}
	
	// 电影首页
	function main()
	{		
		$provice = $this->mv_model->getProvice();
		
		// $this->mv_model->addProvice();
		$citys = $this->mv_model->getCity(303);
		
		$city_id = '303';
		
		// $res = $this->mv_model->addCity(10,303,'杭州',0,0);
		
		$cinema = $this->mv_model->getCinema($city_id);
		
		
		$this->assign('cinema',$cinema);
		$this->display('movies/index');
	}
	
	function cinema()
	{
		$cinema_id = $_GET['cinema'];
		$cinema = $this->mv_model->getCinemaInfo($cinema_id);
		// var_dump($cinema);
		$city = $this->mv_model->getCityInfo($cinema['eid']);
		// var_dump($city);
		$this->display('movies/alist');
	}
	
	function mvlist()
	{
		$id = isset($_GET['mid']) ? $_GET['mid'] : '';
		$mvInfo = $this->mv_model->getCinemaInfo($cinema_id);
	}
	
	function test()
	{
		$type = $_GET['p_type'];
		if($type=='upload') 
		{
			$aaaa['status'] = '1';
			$aaaa['info'] = '';
			$aaaa['data'] = array('result'=>'1','src'=>'http://avatar.duankou.dev/webavatar_2139_b.jpg');
		} 
		elseif($type == 'submit') 
		{
			$aaaa['result'] = '1';
			$aaaa['status'] = '1';
			$return['id'] = '123';
			$return['title'] = '郎情妾意';
			$return['src'] = 'http://liying.duankou.com/www_duankou/interest/movies/index';
			$return['time'] = '2012-07-03';
			$return['second'] = '第一场';
			$return['rmb'] = '120';
			$info = array();
			$info['direct'] = '老扎';
			$info['actor'] = '李颖,杨幂';
			$info['type'] = '爱情,动作';
			$info['lan'] = '中文';
			$info['length'] = '120';
			$info['area'] = '中国';
			$info['time'] = '2012-07-03';
			$return['info'] = $info;
			$aaaa['info'] = '';
			$aaaa['data'] = $return;
		}
		else
		{
			echo 'other';
		}
		if(isset($_GET['callback'])) {
			$callback = $_GET['callback'];
			echo $callback,'(',json_encode($aaaa),')';
		}else{
			echo json_encode($aaaa);
		}
		exit;
		
		// $this->display('movies/alist');
	}
}
?>