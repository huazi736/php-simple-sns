<?php
/*
 * 日期挂件
 */
class date extends MY_Widget{
	public function __construct(){
		parent::__construct();
	}
	/**
	 * 渲染挂件模板
	 * @param array $params array('value' => "挂件值", 'label' => "挂件标签", 'web_id' => "网页id");
	 */
	public function render($data){
		
		$data['now'] = date("Y-m-d");
		$data['begin_year'] = "1800-01-01";
		$data['end_year'] = "2099-01-01";
		
		return $this->renderFile("date.html", $data);
	}
	
}