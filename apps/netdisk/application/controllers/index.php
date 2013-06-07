<?php

class Index extends DK_Controller
{
	private  $my_uid = NULL;
	private  $my_name = NULL;
	
	public function __construct()
	{
		parent::__construct();
		$navs = array(
			'text' => array('text' => '文字', 'url' => mk_url('netdisk/index/text')),
			'photo' => array('text' => '图片', 'url' => mk_url('netdisk/index/photo')),
			'video' => array('text' => '视频', 'url' => mk_url('netdisk/index/video')),
		);
		$action = trim($_GET['action']) ? trim($_GET['action']) : 'text';
		if (!array_key_exists($action, $navs)) {
			$action = 'text';
		}
		
		$this->my_avatar   = get_avatar ( $this->uid );
		$this->netdisk_url = mk_url('netdisk/index/text');
	    $this->author_url  = mk_url('main/index/profile', array('dkcode' => $this->dkcode));
	    
	    $this->assign ( 'author_url', $this->author_url );
		$this->assign ( 'myname', $this->username );
		$this->assign ( 'uid', $this->uid );
		$this->assign ( 'my_avatar', $this->my_avatar );
		$this->assign ( 'netdisk_url', $this->netdisk_url );
		$this->assign ( 'navs', $navs );
		$this->assign ( 'action', $action );
	}
	
	public function main() {
		$this->text();
	}
	
	public function index() {
		$this->text();
	}
	
	//文字
	public function text() {
	
		$this->display('index1.html');
	}	
	
	//图片
    public function photo() {
	
		$this->display('index2.html');
	}
	
	//视频
    public function video() {
	
		$this->display('index3.html');
	}
	
	//发布信息到时间线
	public function do_post() {
		
		$type = shtmlspecialchars($this->input->post('type'));
		$datas = shtmlspecialchars($this->input->post('data'));
		if (!$type || !$datas) {
			return $this->error('操作错误');
		}
		
		foreach($datas as $data) {
			$data['uid'] = $this->user ['uid'];;
			$data['dkcode'] = $this->user ['dkcode'];
			$data['from'] = 7;
			$data['permission'] = 0 ;
			$data['dateline'] = time();
			$data['uname'] = $this->user ['my_name'];
			$data['type'] = $type;
			switch($type)
			{
				case "photo":
					$data['picurl'] = $data['pic'] ;
					break;
				case "video":
					$data['videourl'] = $data['videourl'];
					$data['imgurl'] = $data['pic'];
					break;	
				case "info":
					$data['info'] = $data['content'];		
					break;
			}
			
			$res = service('Timeline')->addTimeLine($data);
			if( $res )
			{
				$data = array('state'=>1,'msg'=>'发布成功');
			
			}
			else 
			{
				$data = array('state'=>1,'msg'=>'发布失败');
			}
				exit(json_encode($data));
			
		}
		
	}
	
}