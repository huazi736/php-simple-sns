<?php
use \Models as Model;
use \Domains as Domain;

class Api extends MY_Controller
{


	/**
	 * 封面图片
	 */
	public function userCover()
	{
		$uid = (int)$this->input->get('uid');
		$info = '';
		if (!$uid)
		{
			$status = 0;
			$info = "请求错误";
			
		}

		$user = new Domain\EventUser(array('uid'=>$uid));

		$status = 1;
		$data['img']= $user->getCover();
		$data['num'] = $user->getEventCount();
		$this->ajaxReturn($data, $info, $status, 'jsonp');
		
	}
	
}
