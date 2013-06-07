<?php
  /**
   * @desc 测试缩略图
   * @author lijianwei
   * @date 2012-02-24
   */
class Test extends MY_Controller {
    
	public function index() {
	
	}

	public function del_web_page(){
		var_dump(Service('Comlike')->delWebPage(2019));
	}
}

