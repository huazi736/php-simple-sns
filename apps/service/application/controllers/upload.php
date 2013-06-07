<?php
/**
 * 外部图片上传接口
 *
 * @author vicente
 * @version $Id
 */
class Upload extends CI_Controller {

    public function __construct() {
        parent::__construct();
        try {
            $this->load->library('HessianPHP_lib');
        } catch (Exception $e) {
            $message = 'Code: ' . $e->getCode() . ' Message: ' . $e->getMessage();
            log_message('ERROR', $message);
        }
    }
    
    /**
     * 保存外部图片
     */
    public function saveImage($params)
    {
    	try {
    		if(empty($params)){
    			exit('参数不能为空！');
    		}
    		
    		$params = json_decode($params , true);
    		if(!isset($file['path'])) exit('请上传图片！');
    		if(!isset($file['size'])) exit('请提供相应的图片参数！');
    		
    		$title = isset($file['title']) ? $file['title'] : '';
            
    		$this->load->model('UploadService_model');
            $service = new HessianService(new UploadService_model());
            $service->handle();
        } catch (Exception $e) {
            $message = 'Code: ' . $e->getCode() . ' Message: ' . $e->getMessage();
            log_message('ERROR', $message);
        }
    }
}