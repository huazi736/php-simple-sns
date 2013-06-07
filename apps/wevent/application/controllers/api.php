<?php
use \Models as Model;
use \Domains as Domain;

/**
 * 网页应用区调用接口
 * @author hpw
 * @date 2012/07/09
 */
class Api extends MY_Controller
{
	/**
	 * 封面图片
	 */
	public function userCover()
	{
		$web_id = (int)$this->input->get('web_id');
		$status = 1;
		$info ='';
		if (!$web_id)
		{
			do_err:
			$status = 0;
			$info = "请求错误";

			goto do_echo;
		}

		$webinfo = service_api('Interest','get_web_info',array($web_id));

		if (empty($webinfo))
			goto do_err;

		$user = service_api('User','getUserInfo',array($webinfo['uid']));

		if (empty($user)) 
			goto do_err;

		$events = new Domain\Events($webinfo, $user);

		$return['img'] = $events->getCover();
		$return['num'] = $events->getEventCount();

		do_echo:
		$this->ajaxReturn($return, $info, $status);
	}

}
