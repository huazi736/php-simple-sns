<?php


class Soft extends MY_Controller {


    var $fastdfs_url	= ""; 
	
	function __construct(){
		parent::__construct();
		
		$this->load->model('goodsmodel','goods');
		$this->goodsmodel	= $this->goods;
		
		
		$this->page = $this->input->get_post('page');
		if($this->page<=0) $this->page =1;
		// $this->assign('user' , $this->user);
		$this->assign('uid',$this->uid);
		$this->assign('dkcode',$this->dkcode);
		
		$fastdfs = getConfig('fastdfs', 'album');
		$this->fastdfs_url		= 'http://'.$fastdfs['host'].'/';
		$this->assign('fastdfs_domain' , $this->fastdfs_url);
		
		$this->assign('web_id' , $this->web_id );
		$this->assign('is_self' , $this->is_self );
		
	}


    //软件下载首页
	public function index(){

		$this->display('soft_list');

	}

    
	//单个软件信息
	public function info(){
	}



	//下载软件
	public function down(){
	}
	
	
	
	//上传软件页面
	public function add_soft(){
		if(!$this->is_self){
			$this->redirect( 'main/index/main' );
			die;
		}
		
		$this->assign('web_info', $this->web_info);
		$this->assign('user',$this->user);
		$this->assign('type','add_soft');
		$this->assign('soft_info','');
		
		$this->display('soft_add');
	}


	//上传软件开始
	public function upload_soft(){

		if(!$this->is_self){
			$this->redirect( 'main/index/main' );
			die;
		}

		$config['upload_path'] = VAR_PATH . 'soft/';
		$config['allowed_types'] = 'rar|tar|gz|zip';
		$config['max_size'] = '100';

		$this->load->library('upload',$config);

		if (!$this->upload->do_upload())
		{
			die(json_decode('status'=>'0','msg'=>$this->upload->display_errors()));
		}
		else
		{
			die(json_decode('status'=>'1','data'=>$this->upload->data()));
		}

	}


    //保存软件至数据库

	public function save_soft(){

		if(!$this->is_self){
			$this->redirect( 'main/index/main' );
			die;
		}

		$this->load->model('softmodel','soft');

		$data['uid'] = $this->uid;
		$data['webid'] = $this->web_id;
		$data['name'] = P('name');
		$data['size'] = get_size(P('file'));
		$data['version'] = P('version');
		$data['iid'] = service('Interest')->get_category_group(P('catid'), 4);
		$data['platform'] = P('platform');
		$data['language'] = P('language');
		$data['official'] = P('official');
		$data['description'] = P('description');
		$data['description2'] = P('description2');
		$data['file'] = P('file');
		$data['pics'] = P('pics');
		$data['main_pics'] = P('main_pics');
		$data['ctime'] = time();

        $result = $this->soft->addSoft($data);

		if ($result)
		{
			//入驻时间线
			die(json_decode('status'=>'1','msg'=>'发布成功！','data'=>$data));
		}
		else
		{
			die(json_decode('status'=>'0','msg'=>'发布失败！'));
		}

	}
	
	

	
}