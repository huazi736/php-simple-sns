<?php
if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * 全局搜索
 * Enter description here ...
 * @author liuGC
 * @access public 
 * @version 1.0
 * @since 2012/03/24
 * @description  全局搜索Controller控制器 
 * @history <author><access><version><dateline><descrition>
 */
class Search extends DK_Controller
{
	//访问的个应用名

	private $settings = array(
								'people'=>array('name'=>'人名','offset'=>10),
								'website'=>array('name'=>'网页','offset'=>10),
								'status'=>array('name'=>'状态','offset'=>10),
								'photo'=>array('name'=>'照片','offset'=>12),
								'album'=>array('name'=>'相册','offset'=>12),
								'video'=>array('name'=>'视频','offset'=>12),
								'blog'=>array('name'=>'日志','offset'=>10),
//								'answer'=>array('name'=>'问答','offset'=>10),
								'event'=>array('name'=>'活动','offset'=>10),
								);
	
	private $search_entry_path = "main/search/main";
	
	/**
	 * 搜索主方法
	 * Enter description here ...
	 * @desciption 搜索主入口方法
	 */

	public function main()
	{
		$keyword = trim($this->input->get_post('term'));
                
                if ( ($type = trim($this->input->get_post('type'))) == "field" ) $this->field($keyword);
                
		if (array_key_exists($type, $this->settings))
		{	
			require_once dirname(dirname(__FILE__)).'/models/searchmodel.php';
			
			$this->search = new SearchModel();

			$this->search ->setCurrentUserID($this->uid);

			if ($this->isAjax() === false)
			{	
				$is_zero = true;
				
				$center_count_border = array('prefix'=>'&nbsp;', 'subfix'=>'');
				
				$search_keyword = mb_substr(urldecode($keyword), 0, 50, 'utf-8');
				
				$html_key = htmlspecialchars($search_keyword, ENT_QUOTES);
				
				$this->settings[$type]['html_more_result'] = '查看更多';
				
				$this->search-> setKeyword($search_keyword);
				
				$statistics = $this->search->getLeftNavStatistics();
			
				if ($statistics[$type] > 0 || $is_zero ) $center_count_border = array('prefix'=>'&nbsp;(', 'subfix'=>')');

				$this->assign('app_url_list', $this->getEveryAppUrlList($keyword));
				
				$this->assign('left_navigator', $statistics);
				
				$this->assign('center_count_border', $center_count_border);
				
				$this->assign('app_settings', $this->settings);
				
				$this->assign('ajax_path', mk_url($this->search_entry_path, array('type'=>$type, 'init'=>time())));
				
				$this->assign('keyword', $html_key);
				
				$this->assign('user_name', $this->username);
				
				$this->assign('user_avatar', get_avatar($this->uid));
				
				$this->assign('user_url', mk_url('main/index/main', array('dkcode'=>$this->dkcode)));
			
			}else
				
				$this->search -> setKeyword(mb_substr($keyword, 0, 50, 'utf-8'));
			
			$this->$type();		
			exit;
		}

		echo "<script type='text/javascript'>window.location.href=\"".mk_url($this->search_entry_path, array("type"=>"people","term"=>$keyword,"init"=>time()))."\"</script>";
		exit;
	}
	
	/**
	 * 头部输入框搜索
	 * 
	 * Enter description here ...
	 */
	
	protected function field($keyword)
	{
        	require_once dirname(dirname(__FILE__)).'/models/searchmodel.php';
			
		$this->search = new SearchModel();

		$this->search ->setCurrentUserID($this->uid);
                 
                $this->search -> setKeyword(mb_substr($keyword, 0, 50, 'utf-8'));
                
		if ($this->search->getKeyword()  != null)
		{
			$ajax = $this->search->getTopInputResult();
			
			$this->ajaxReturn($ajax, '', 1, 'jsonp');
		}else{
			$this->ajaxReturn(array(), '', 1, 'jsonp');
		}
	}
	
	/**
	 * 人名高级搜索 条件弹出层
	 * 
	 * Enter description here ...
	 * @since 2012/07/14
	 * @author LiuGC
	 */
	public function popup()
	{
		$selected = array("college"=>1, "company"=>4, "highschool"=>2);
		
		$num = $this->input->get_post('category');

		if(($html_name = array_search($num, $selected)) != false)
		{
			$this->display("search/school_company/".$html_name.".html");
			exit;
		}

	}
	
	/**
	 * 主页面人名搜索
	 * 
	 * Enter description here ...
	 */
	
	
	protected function people()
	{
		$params = array();

		$params['middle_school'] = $this->input->post('middle_school');
		
		$params['college'] = $this->input->post('college');
		
		$params['company'] = $this->input->post('company');
	
		$params['home_addr'] = $this->input->post('local_address');
		
		$params['now_addr'] = $this->input->post('province');

		$this->search->setParameters($params);

		if ($this->isAjax())	$this->showAjaxResponse();
		
		if ($this->search->getKeyword()  != null)
		{
			$this->search->setPeopleResult(0, $this->settings['people']['offset']);

			$people = $this->search->getRecords('people');

			if ($people['count'] > 0)
			{
				$this->assign('is_end', $people['is_end']);
				$this->assign('people',$people['list']);
				$this->assign('isEmpty', false);
				$this->display('search/user.html');
				exit;
			}
		}
		$this->getEmptyHTML('user');
	}

	

	

	/**
	 * 主页面网页搜索
	 * 
	 * Enter description here ...
	 */
	protected function website()
	{
		if ($this->isAjax()) $this->showAjaxResponse();
		
		if ($this->search->getKeyword() != null)
		{
			$this->search->setWebpageResult(0, $this->settings['website']['offset']);
			
			$webinfo = $this->search->getRecords('web');

			if ($webinfo['count'] > 0)
			{
				$this->assign('is_end', $webinfo['is_end']);
				$this->assign('website',$webinfo['list']);
				$this->assign('isEmpty', false);
				$this->display('search/webpage.html');
				exit;
			}				
		}
			$this->getEmptyHTML('webpage');		
	}
	
	/**
	 * 主页面时间线搜索
	 * 
	 * Enter description here ...
	 */
	
	protected function status()
	{
		if ($this->isAjax())	$this->showAjaxResponse();
		
		if ($this->search->getKeyword()  != null)
		{   
			$this->search->setStatusResult(0, $this->settings['status']['offset']);
			
			$status = $this->search->getRecords('status');
              
			if ($status['count'] > 0)
			{
				$this->assign('is_end', $status['is_end']);
				$this->assign('status',$status['list']);
				$this->assign('isEmpty', false);
				$this->display('search/status.html');
				exit;
			}
		}
			$this->getEmptyHTML('status');		
	}
	
	
	/**
	 * 主页面相片搜索
	 * 
	 * Enter description here ...
	 */
	
	protected function photo()
	{
		if ($this->isAjax()) $this->showAjaxResponse();
		
		if ($this->search->getKeyword() != null)
		{
			$this->search->setPhotoResult(0, $this->settings['photo']['offset']);
			$photo = $this->search->getRecords('photo');

			if ($photo['count'] > 0)
			{
				$this->assign('is_end', $photo['is_end']);
				$this->assign('photo',$photo['list']);
				$this->assign('isEmpty', false);
				$this->display('search/photo.html');
				exit;
			}				
		}
			$this->getEmptyHTML('photo');		
	}
	
	/**
	 * 主页面album搜索
	 * 
	 * Enter description here ...
	 */
	protected function album()
	{
		if ($this->isAjax()) $this->showAjaxResponse();
		
		if ($this->search->getKeyword() != null)
		{
			$this->search->setAlbumResult(0, $this->settings['album']['offset']);
			$album = $this->search->getRecords('album');

			if ($album['count'] > 0)
			{
				$this->assign('is_end', $album['is_end']);
				$this->assign('album',$album['list']);
				$this->assign('isEmpty', false);
				$this->display('search/album.html');
				exit;
			}				
		}
			$this->getEmptyHTML('album');		
	}	
	/**
	 * 主页面video搜索
	 * 
	 * Enter description here ...
	 */
	protected function video()
	{
		if ($this->isAjax()) $this->showAjaxResponse();
		
		if ($this->search->getKeyword() != null)
		{
			$this->search->setVideoResult(0, $this->settings['video']['offset']);
			
			$video = $this->search->getRecords('video');

			if ($video['count'] > 0)
			{
				$this->assign('is_end', $video['is_end']);
				$this->assign('video',$video['list']);
				$this->assign('isEmpty', false);
				$this->display('search/video.html');
				exit;
			}				
		}
			$this->getEmptyHTML('video');		
	}		
	
	/**
	 * 主页面博客搜索
	 * 
	 * Enter description here ...
	 */
	protected function blog()
	{
		if ($this->isAjax()) $this->showAjaxResponse();
		
		if ($this->search->getKeyword() != null)
		{
			$this->search->setBlogResult(0, $this->settings['blog']['offset']);
			
			$blog = $this->search->getRecords('blog');

			if ($blog['count'] > 0)
			{
				$this->assign('blog',$blog['list']);
				$this->assign('is_end', $blog['is_end']);
				$this->assign('isEmpty', false);
				$this->display('search/blog.html');
				exit;
			}				
		}
			$this->getEmptyHTML('blog');		
	}
	
	/**
	 * 主页面问答搜索
	 * 
	 * Enter description here ...
	 */
	protected function answer()
	{
		if ($this->isAjax()) $this->showAjaxResponse();
		
		if ($this->search->getKeyword() != null)
		{
			$this->search->setAskResult(0, $this->settings['answer']['offset']);
			$ask = $this->search->getRecords('ask');
			if ($ask['count'] > 0)
			{
				$this->assign('is_end', $ask['is_end']);
				$this->assign('ask',$ask['list']);
				$this->assign('isEmpty', false);
				$this->display('search/ask.html');
				exit;
			}				
		}
			$this->getEmptyHTML('ask');		
	}
	
	/**
	 * 主页面活动搜索
	 * 
	 * Enter description here ...
	 */
	protected function event()
	{
		if ($this->isAjax()) $this->showAjaxResponse();
		
		if ($this->search->getKeyword() != null)
		{
			$this->search->setEventResult(0, $this->settings['event']['offset']);
			$event = $this->search->getRecords('event');

			if ($event['count'] > 0)
			{
			
				$this->assign('is_end', $event['is_end']);
				$this->assign('event',$event['list']);
				$this->assign('isEmpty', false);
				$this->display('search/activity.html');
				exit;
			}				
		}	
			$this->getEmptyHTML('activity');		
	}

	protected function showAjaxResponse($is_globals = false)
	{
		$key = '';
		
		$app_no = $this->input->post('app');
		
		$page = $this->input->post('page')  > 0  ? $this->input->post('page') : 0;
		
		switch ($app_no)
		{
			case 1:
				$offset = $this->settings['people']['offset'];
				$this->search->setPeopleResult($page*$offset, $offset);
				$key = 'people';
				break;
			case 2:
				$offset = $this->settings['website']['offset'];
				$this->search->setWebpageResult($page*$offset, $offset);
				$key = 'web';
				break;
			case 3:
				$offset = $this->settings['status']['offset'];
				$this->search->setStatusResult($page*$offset, $offset);
				$key = 'status';
				break;
			case 4:
				$offset = $this->settings['photo']['offset'];
				$this->search->setPhotoResult($page*$offset, $offset);
				$key = 'photo';
				break;
			case 5:
				$offset = $this->settings['album']['offset'];
				$this->search->setAlbumResult($page*$offset, $offset);
				$key = 'album';
				break;
			case 6:
				$offset = $this->settings['video']['offset'];
				$this->search->setVideoResult($page*$offset, $offset);
				$key = 'video';
				break;
			case 7:
				$offset = $this->settings['blog']['offset'];
				$this->search->setBlogResult($page*$offset, $offset);
				$key = 'blog';
				break;
			case 8:
				$offset = $this->settings['answer']['offset'];
				$this->search->setAskResult($page*$offset, $offset);
				$key = 'ask';
				break;
			case 9:
				$offset = $this->settings['event']['offset'];
				$this->search->setEventResult($page*$offset, $offset);
				$key = 'event';
				break;
		}
		$response = $this->search->getRecords($key);

		$data['data'] = $response['list'];
		$data['type'] = $response['type'];
		$data['count'] = $response['count'];
		$data['is_end'] = $response['is_end'];
		$data['state'] = 1;
		
		$this->ajaxReturn($data);
	}
	
	protected function getEmptyHTML($html)
	{
		$this->assign('total', 0);				
		$this->assign('isEmpty', true);
		$this->display('search/'.$html.'.html');
		exit;		
	}
	/**
	 * 搜索页面下拉列表访问地址
	 * 
	 * Enter description here ...
	 * @param string $keyword 关键字
	 */
	protected function getEveryAppUrlList($keyword = null)
	{
		$list = array();
		
		$rand = str_shuffle(time());
		
		$keyword = $this->input->get("term");
		
		foreach ($this->settings as $key => $name)
		{
			$list[$key]['url'] =  mk_url($this->search_entry_path, array('type'=>$key,'term'=>$keyword, 'init'=>$rand));
			$list[$key]['name'] = $name;
		}

		return $list;
	}
        
        public function viewImage()
        {
            $img_profile = array();
            $img_profile["show"] = $this ->input->get("photo_show");
            $img_profile["title"] = $this ->input->get("photo_name");
            $img_profile["time"] = $this ->input->get("photo_time");
            $img_profile["id"] = $this ->input->get("photo_id");
            $img_profile["cut_title"] = $this ->input->get("photo_cut_name");
            $img_profile["author_name"] = $this ->input->get("author_name");
            $img_profile["author_url"] = $this ->input->get("author_home_page");
            $this ->  assign("photo", $img_profile);
            $this ->  display("search/album_picView.html");
        }
}
?>