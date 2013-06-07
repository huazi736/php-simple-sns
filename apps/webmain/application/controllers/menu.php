<?php
/**
 * 首页应用区设计接口
 * @author yanghsunjun
 * @version 
 */

class Menu extends MY_Controller{
	function __construct(){
		parent::__construct();
		$this->load->model('apimodel');
        $this->load->helper('webmain');
	}
	
   	/**
   	 * 更改应用区排序
   	 * 
   	 * @author yangshunjun
   	 */
    public function resetAppSort(){
    	$menu_id = $this->input->get_post('dataMap');
   		$web_id = intval($this->input->get_post('webId'));
   		
   		//确保用户本人操作
   		if($this->is_self && $web_id){
   			$menid_array = explode('|', $menu_id);
   			
   			if(is_array($menid_array)){
   				$len = count($menid_array);
   				foreach($menid_array AS $key => $val){
   					$sort_data[] = array('menu_id' => $val,  'menu_sort' => $len);
   					
   					$len--;
   				}
   			}
			$data = array(
			   'web_id' => $web_id,
			   'sort' => $sort_data
			);
			
			$this->load->model('appmenumodel');
			$update = $this->appmenumodel->sortAppMenu($web_id, $sort_data);
			if($update === true){
				die(json_encode(array('state' => 1)));
			}
			
			die(json_encode(array('state' => 0)));
   		}
   		
   		die(json_encode(array('state' => 0)));
    }
}