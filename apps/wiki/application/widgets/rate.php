<?php
/*
 * 等级挂件
 */
class rate extends MY_Widget{
	public function __construct(){
		parent::__construct();
	}
	/**
	 * 渲染挂件模板
	 * @param array $params array('value' => "挂件值", 'label' => "挂件标签", 'web_id' => "网页id");
	 */
	public function render($data){
		//初始化值
		if(!isset($data['value']['average'])){
			$data['value']['average'] = 0;
			$data['value']['rate_nums'] = 0;
		}
		$this->assign("updateRateInfo", mk_url("wiki/webwiki/updateRateInfo", array("web_id" => $data['web_id'])));
		return $this->renderFile("rate.html", $data);
	}
	
}