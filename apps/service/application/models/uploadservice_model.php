<?php

class UploadService_model extends MY_Model {

    /**
     * 保存外部图片
     * 
     * @param string $file
     * @param array $size
     */
    public function saveImage($file, $sizes) 
    {
    	$status = 0;
    	$msg = 'error';
    	$sizes = json_decode($sizes, true);
        $data = service('Album')->saveBuffImage($file, $sizes);
        if(!empty($data) && is_array($data)){
        	$status = 1;
    		$msg = 'ok';
    		$result = $data;
        }else{
        	$status = $data;
        	$result = $sizes;
        }

        return $this->encodeResult($status, $msg, $result);
    }
}
