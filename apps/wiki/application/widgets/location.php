<?php
/*
 * 位置挂件
 */
class location extends MY_Widget{
	public function __construct(){
		parent::__construct();
	}
    /**
	 * 渲染挂件模板
	 * @param array $params array('value' => "挂件值", 'label' => "挂件标签", 'web_id' => "网页id");
	 */
	public function render($data){
		
		//处理value
		$data['deal_value'] = "";
		$data['show_value'] = "";
		
	    if(!isset($data['value']['address'])) $data['value']['address'] = "请输入详细地址";
		
		if(isset($data['value']['n']['name'])){
		 $data['deal_value'] = $data['value']['n']['name']. " ". $data['value']['p']['name']. " ". $data['value']['c']['name']. " ". $data['value']['r']['name'];
		 $data['show_value'] = $data['value']['n']['name']. " ". $data['value']['p']['name']. " ". $data['value']['c']['name']. " ". $data['value']['r']['name'] . " ". $data['value']['address']; 
		}
		return $this->renderFile("location.html", $data);
	}
	
}