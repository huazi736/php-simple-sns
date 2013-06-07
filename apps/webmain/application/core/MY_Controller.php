<?php

class MY_Controller extends DK_Controller {
    
    public function __construct() {
        parent::__construct();
        
        //判断网页是否自己本人的 add by lanyanguang 2012/04/26
        $this->_isSelf();
    }

    /**
     * 判断网页是否本人的 add by lanyanguang 2012/04/26
     */
    private function _isSelf() {
    	$this->is_self	= false;
        if (isset($this->web_info['uid'])) {
            if ($this->uid == $this->web_info['uid']) {
                $this->is_self = true;
            }
        } else {
            $this->is_self = true;
        }
    }

}