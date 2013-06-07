<?php

/**
*	频道· 游戏模块
*   Author by guojianhua
*/

    class games extends MY_Controller   {
    	
    	public $flash_size = 10000000;
    	public $flash_type = array('swf','flv');
    	public $img_size = 4000000;
    	public $img_type = array('jpg','jpeg','gif','png');
    	
    	function __construct()  {
            parent::__construct();
            
            $this->fdfs = get_storage('games');
            
            $this->load->config('games');
        }
        
        function index() {
            
        }
        
        /**
         * 上传游戏页面
         */
        function add() {
        	$this->assign('web_info',$this->web_info);
        	$this->display('games/games_upload');
        }
        
        /**
         * 处理上传
         */
        function upload() {
        	
        	print_r($_FILES);
        	
        	if (isset($_FILES['uploadFlashFile'])) {
        		//$this->upload_flash($_FILES['uploadFlashFile']);
        	}
        	
        	print_r($_GET);
        	
        	print_r($_POST);
        	
        }
        
        /**
         * 处理Flash文件上传
         */
        function upload_flash() {
        	
        	print_r($_FILES);
        	
        	$files = $_FILES['uploadFlashFile'];
        	
        	$status = $this->upload_error($files['error']);
        	
        	if (!$status['status']) {
        		$this->ajaxReturn('',$status['msg'],0);
        	}
        	
        	if (!$this->is_allow_upload_size($files,$this->flash_size)) {
        		$msg = '请上传小于' . floor($this->flash_size / 1000000 ) . 'M的文件';
        		$this->ajaxReturn('',$msg,0);
        	} 
        	
        	if (!$this->is_allow_upload_types($files,$this->flash_type)) {
        		$msg = '文件格式出错，请上传' . implode(',', $this->flash_type) . '类型文件';
        		$this->ajaxReturn('',$msg,0);
        	}
        	
        	$file_ext = trim(strtolower(array_pop(explode('.', $files['name'])))); 
        	
        	$file_info = $this->fdfs->uploadfile($files['tmp_name'],$file_ext);
        	
        	unlink($files['tmp_name']);
        	
        	$this->gamesmodel->add_games();
        	
        }
        
        /**
         * 处理图片上传
         * @param unknown_type $files
         */
        function upload_img($files = array()) {
        	
        	print_r($_FILES);
        	
        }
        
        function is_allow_upload_size($files = array(),$file_size = 0) {
        	if ($files['size'] < $file_size) {
        		return TRUE;
        	} else {
        		return FALSE;
        	}
        }
        
        /**
         * 判断是否允许上传类型
         * @param unknown_type $file 上传文件的信息
         * @param unknown_type $file_type
         * @return boolean
         */
        function is_allow_upload_types($file = array(),$file_type = array()) {
        	
        	$ext = trim(substr($file['name'],strripos($file['name'] , '.') + 1));
        	
        	if (! in_array($ext,$file_type)) {
        		return FALSE;
        	}
        	
        	$allow_mines = array();
        	
        	require_once CONFIG_PATH . '/mimes.php';
        	
        	foreach ($file_type as $type) {
        		if (isset($mimes[$type])) {
        			if (is_array($mimes[$type])) {
        				foreach ($mimes[$type] as $val) {
        					$allow_mines[] = $val;
        				} 
        			} else {
        				$allow_mines[] = $mimes[$type];
        			}
        		}
        	}
        	
        	$ffinfo = new finfo(FILEINFO_MIME_TYPE);
        	
        	$file_mime = $ffinfo->file($file['tmp_name']);
        	
        	if (in_array($file_mime,$allow_mines)) {
        		return TRUE;
        	} else {
        		return FALSE;
        	}
        	
        }
        
        /**
         * 返回错误信息
         * @param unknown_type $error_num
         * @return Ambigous <multitype:, string>
         */
        function upload_error($error_num) {
        	
        	$result = array();
        	
        	switch ($error_num) {
        		case 0:
        			$result['status'] = 1;
        			$result['msg'] = '上传成功';
        			break;
        		case 1:
        			$result['status'] = 0;
        			$result['msg'] = '文件大小超过PHP设置';
        			break;
        		case 2:
        			$result['status'] = 0;
        			$result['msg'] = '文件大小超过表单设置';
        			break;
        		case 3:
        			$result['status'] = 0;
        			$result['msg'] = '文件只有部分被上传';
        			break;
        		case 4:
        			$result['status'] = 0;
        			$result['msg'] = '没有文件被上传';
        			break;
        		case 6:
        			$result['status'] = 0;
        			$result['msg'] = '找不到临时文件';
        			break;
        		case 7:
        			$result['status'] = 0;
        			$result['msg'] = '文件写入失败';
        			break;
        		default:
        			$result['status'] = 0;
        			$result['msg'] = '未知错误';
        			break;
        	}
        	
        	return $result;
        }
        
        
        public function test() {
        	
        	$this->load->config('games');
        	
        	$config = config_item('tmp_local_path');
        	
        	var_dump($config);
        	
        	$this->load->model('gamesmodel');
        	
        	$this->gamesmodel->add_games();
        	
        	exit;
        	
        	$file = $_SERVER['SCRIPT_FILENAME'];
        	
        	$name = pathinfo($file);
        	
        	
        	
        	$ext = trim(strtolower(array_pop(explode('.', $name['basename']))));
        	
        	//$file_info = $this->fdfs->uploadfile($file,$name['extension']);
        	
        	//print_r($file_info);
        	
        	$file_info = array ( 'group_name' => 'video10',
        						'filename' => 'M00/02/E9/wKgMy1AcvjvRES31AAAApUWoNns228.php'
        	); 
        	
        	var_dump($this->fdfs->deleteFile($file_info['group_name'],$file_info['filename']));
        	
        }
    }
