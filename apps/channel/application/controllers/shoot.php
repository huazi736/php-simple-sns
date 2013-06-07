<?php
class Shoot extends MY_Controller
{
	/**
	 * 初始化方法
	 */	
	protected function _initialize()
	{
		//初始化操作放这里面
		$this->config->load('shoot');
		$this->load->model('shootmodel','shootmodel');
	}
	
	
	
	public function index()
	{
// 		var_dump(session_name());
// 		var_dump(strlen(session_id()));
		$this->display('shoot/main');
	}
	
	
	/**
	 * 处理上传
	 */
	public function uploadImage()
	{
		$ret = $this->shootmodel->uploadImage($this->uid);
		$this->ajaxReturn($ret['data'],$ret['error_msg'],$ret['error_no']<=0 ? 1 : 0);		
	}
	
	/**
	 * 测试数据
	 */
	public function test()
	{
		$this->shootmodel->test_db($this->uid);
	}
	
	/**
	 * 处理提交信息
	 */
	public function addWorksInfo()
	{
		//作品集名称
		$set_name = $this->input->get_post('s_title');
		$set_name = $set_name ? html_escape($set_name) : date('Y-m-dHis',SYS_TIME);
		
		//作品集描述
		$set_desc = $this->input->get_post('s_desp');
		$set_desc = $set_desc ? html_escape($set_desc) : '';
		
		
		$_POST['type'] = 'shoot';
		//添加作品集信息
		$set_id = $this->shootmodel->addWorksSet($set_name,$this->uid,$this->web_id,$set_desc);
		
		if($set_id){
			//作品信息
			$a_pic = $this->input->get_post('a_pict');
			foreach($a_pic as $v){
				//作品名称
				$item_name = isset($v['s_title']) ? $v['s_title'] : '';
				$item_name = $item_name ? html_escape($item_name) : '';
				//作品标签
				$item_tag = isset($v['s_tags']) ? $v['s_tags'] : '';
				$item_tag = $item_tag ? html_escape($item_tag) : '';
				//作品描述
				$item_desc = isset($v['s_desp']) ? $v['s_desp'] : '';
				$item_desc = $item_desc ? html_escape($item_desc) : '';
				//作品ID
				$item_id = isset($v['i_id']) ? intval($v['i_id']) : 0;
				//排除无效id
				if($item_id > 0){
					$data['tag_name'] = $item_tag;
					$data['set_id']	 = $set_id;
					$data['description'] = $item_desc;
					if(!empty($item_name)){
						$data['name'] = $item_name;
					}
					$this->shootmodel->updateWorksItem($item_id,$data);
				}		
			}
			
			//加入时间线

			$timeLineData = $this->get_shoot_data($set_id);
						
			if(!empty($timeLineData)){
				//写入时间线信息
				$result = $this->save_timeline(array('shoot'=>json_encode($timeLineData)),$set_id);
				//var_dump($result);exit;
				if (is_string($result)) {
					return $this->ajaxReturn('',$result,0,'jsonp');
				} else {
					return $this->ajaxReturn(array('data'=>$result ),'operation_success',1,'jsonp');
				}
			}
		}
		$this->ajaxReturn('','添加成功',1);
	}
	
	
	private function get_shoot_data($set_id)
	{
		$timeLineData = array();
		//获取作品集信息
		$setInfo = $this->shootmodel->getWorksSetByID($this->uid,$set_id);
		//获取作品集下的作品信息
		$itemInfo = $this->shootmodel->getWorksItemInfo($this->uid,$set_id,true);
		if(!empty($setInfo) && !empty($itemInfo)){
			$timeLineData['setInfo'] = $setInfo;
			$timeLineData['itemInfo'] = $itemInfo;
		}
		return $timeLineData;
	}
	/**
	 * 处理返回的时间线数据
	 */
	public function deal_shoot_data($result)
	{
		$shoot = json_decode($result['shoot'],true);
		$result['shoot'] = $shoot;
		return $result;
	}
}
?>