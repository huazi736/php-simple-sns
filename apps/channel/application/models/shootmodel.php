<?php
class ShootModel extends MY_Model
{
	//图片路径
	private $img_url = '';
	
	//错误代码与提示
	private $error_no = 0;
	private $error_msg = '';
	
	public function __construct()
	{
		parent::__construct();
		$this->img_url = 'http://'.getFastdfs().'/';
	}
	
	/**
	 * 处理图片上传
	 */
	public function uploadImage($uid)
	{	
		$url = '';
		$item_id = 0;	
		//临时文件路径
		$path = $this->config->item('tmp_file_path');
		
		//允许上传的图片类型
		$allowed_types = $this->config->item('allowed_types');

		//允许上传的大小限制
		$max_size = $this->config->item('max_size');
		
		$file = $_FILES['uploadfile'];
		$name = $file['name'];
		$size = $file['size'];
		
		//判断大小
		if($size/1024 > $max_size || $size <=0){
			$this->error_no = 1;
			$this->error_msg = '文件大小不符合要求';
		}else{
			
			//小图配置
			$small_config = $this->config->item('small_pic');
			
			//中图配置
			$middle_config = $this->config->item('middle_pic');
		
			$imageModel = get_image('default');
			$pathinfo = pathinfo($file['name']);
			$type = strtolower($pathinfo['extension']);
			
			//判断类型
			if(!in_array($type, $allowed_types)){
				$this->error_no = 2;
				$this->error_msg = '文件类型不符合要求';
			}else{
			
				//生成缩略图(中等尺寸)
				$middle_filename = $path.'m'.date('YmdHis',time()).'_'.rand(1000,9999).'.'.$type;
				$ret1 = $imageModel->resize_ratio($file['tmp_name'], $middle_filename, $middle_config['width'], $middle_config['height'], $middle_config['quality']);
				if(!$ret1){
					$this->error_no = 3;
					$this->error_msg = '生成缩略图1失败';
				}else{
					//生成缩略图(小图)
					$small_filename = $path.'s'.date('YmdHis',time()).'_'.rand(1000,9999).'.'.$type;
					$ret2 = $imageModel->resize_ratio($file['tmp_name'], $small_filename, $small_config['width'], $small_config['height'], $small_config['quality']);
					
					if(!$ret2){
						$this->error_no = 4;
						$this->error_msg = '生成缩略图2失败';
					}else{
						
						$this->load->fastdfs('shoot','', 'fdfs');
						//上传原图到fdfs
						$org_pic_info = $this->fdfs->uploadFile($file['tmp_name'], $type);
						if(!$org_pic_info){
							$this->error_no = 5;
							$this->error_msg = '保存原图失败';
						}else{
							
							$fileName = $org_pic_info['filename'];
							$groupName = $org_pic_info['group_name'];
							
							//上传中等图片到fdfs	
							$org_pic_info = $this->fdfs->uploadFile($middle_filename, $type);
							if(!$org_pic_info){
								$this->error_no = 6;
								$this->error_msg = '保存缩略图1失败';
							}else{
								
								$middle_fileName = $org_pic_info['filename'];
								//上传小图到fdfs
								$org_pic_info = $this->fdfs->uploadFile($small_filename, $type);
								if(!$org_pic_info){
									$this->error_no = 7;
									$this->error_msg = '保存缩略图2失败';
								}else{	
									$small_fileName = $org_pic_info['filename'];						
							
									$item_id = $this->addWorksItem($name, $groupName, $fileName, $middle_fileName, $small_fileName, '', $size, 0, $type, $uid, '');
									if(!$item_id){
										$this->error_no = 7;
										$this->error_msg = '保存缩略图2失败';
									}else{
										$url = $this->img_url.$org_pic_info['group_name'].'/'.$org_pic_info['filename'];
									}
								}
							}
						}
						//删除临时图片
						@unlink($middle_filename);
						@unlink($small_filename);
					}
				}
			}
		}
		return $data = array('error_no'=>$this->error_no,
							 'error_msg'=>$this->error_msg,
							 'data'=>array('id'=>$item_id,'imgurl'=>$url));
	}
	
	/**
	 * 测试数据库查询
	 */
	public function test_db($uid)
	{
		//$sql = 'select * from apps_info where aid >= ? and imid = ? ';
		//$result = $this->db->query($sql,array(2000,11))->result_array();
// 		$result = $this->db->select('*')->where(array('app.imid'=>11))
// 				->where('(aid > '.$this->db->escape_str(2000).' or aid > '.$this->db->escape_str(1900).')')->from('apps_info as app')
// 				//->or_where('aid > 1900')
// 				->join('interest_category_main as app_main','app.imid=app_main.imid','INNER')
// 				->get()->result_array();
// 		var_dump($result);

// 		$this->db->trans_start();
// 		$set_id = $this->addWorksSet('test', $uid, 1111, 'ssss', 1);
// 		if($set_id){
// 			$item_id = $this->addWorksItem('test_item', 'group2', 'test', 'test1', 'test2', 'test', 1024, $set_id, 'jpeg', $uid, 'ceshi');
// 			if($item_id){
// 				var_dump($item_id);
// 			}else{
				
// 			}
// 			$this->db->trans_complete();
// 		}

		$return = $this->getWorksItemByID($uid, 53930);
		var_dump($return);
	}
	
	/**
	 * 添加作品集
	 */
	public function addWorksSet($name,$uid,$web_id,$description,$object_type = 1)
	{
		$curTime = time();
		$data = array(
				'set_name'			=>$name,
				'uid'			=>$uid,
				'web_id'		=>$web_id,
				'dateline'		=>$curTime,
				'last_dateline'	=>$curTime,
				'description'	=>$description,
				'object_type'	=>$object_type
				);
		$res = $this->db->insert('works_set',$data);
		if($res){
			return $this->db->insert_id();
		}else{
			return false;
		}
	}
	
	/**
	 * 添加作品集元素
	 */
	public function addWorksItem($name,$groupname,$filename,$middle_filename,$small_filename,$tag_name,$size,$set_id,$type,$uid,$description)
	{
		$curTime = time();
		$data = array(
					'name'				=>	$name,
					'groupname'			=>	$groupname,
					'filename'			=> 	$filename,
					'middle_filename'	=>	$middle_filename,
					'small_filename'	=>	$small_filename,
					'tag_name'			=>	$tag_name,
					'size'				=> 	$size,
					'set_id'			=>	$set_id,
					'type'				=>	$type,
					'uid'				=>	$uid,
					'description'		=>	$description,
					'dateline'			=>	$curTime
				);
		
		$res = $this->db->insert('works_item',$data);
		if($res){
			return $this->db->insert_id();
		}else{
			return false;
		}
	}
	
	/**
	 * 获取指定uid、web_id 的作品集信息
	 */
	public function getWorksSetInfo($uid,$web_id)
	{
		$param = array('uid'=>intval($uid),
						'web_id'=>intval($web_id),
						'is_delete'=>1
						);
		$result = $this->db->select('*')->from('works_set')->where($param)
						->get()->result_array();
		return $result;
	}
	
	/**
	 * 获取作品集元素信息
	 */
	public function getWorksItemInfo($uid,$set_id,$is_need_process = false)
	{
		//处理set_id 参数
		if(strpos($set_id, ',')){
			$set_id = explode(',', $set_id);
		}
		
		if(is_array($set_id)){
			$param_setid = $set_id;
		}else{
			$param_setid = array($set_id);
		}
		
		$param_uid = array('uid'=>intval($uid),
							'is_delete'=>1
					);
		$result = $this->db->select('*')->from('works_item')
							->where_in('set_id',$param_setid)
							->where($param_uid)
// 							->limit(2,0)
// 							->order_by('item_id','desc')
							->get()->result_array();
		if($is_need_process){
			$result = $this->processWorksItemInfoUrl($result);
		}
		
		return $result;
	}
	
	/**
	 * 处理作品url链接
	 * @param array $result
	 */
	private function processWorksItemInfoUrl($result = array())
	{
		if(!empty($result)){
			foreach ($result as &$v){
				$v['filename_url'] = $this->img_url.$v['groupname'].'/'.$v['filename'];
				$v['middle_filename_url'] = $this->img_url.$v['groupname'].'/'.$v['middle_filename'];
				$v['small_filename_url'] = $this->img_url.$v['groupname'].'/'.$v['small_filename'];
			}
		}
		return $result;
	}
	
	/**
	 * 根据ID获取作品集信息
	 * @param int $set_id
	 */
	public function getWorksSetByID($uid,$set_id)
	{
		$param = array('set_id'=>intval($set_id),
						'uid'=>intval($uid));
		$row = $this->db->select('*')->from('works_set')
						->where($param)
						->get()->row_array();
		return $row;		
	}
	/**
	 * 根据ID获取作品信息
	 * @param int $item_id
	 */
	public function getWorksItemByID($uid,$item_id)
	{
		$param = array('item_id'=>intval($item_id),
						'uid'	=>intval($uid)
					);
		$row = $this->db->select('*')->from('works_item')
						->where($param)
						->get()->row_array();
		return $row;
	}
	
	
	/**
	 * 更新作品信息
	 */
	public function updateWorksItem($item_id,$data = array())
	{
		if(!empty($data)){
			$this->db->where('item_id',intval($item_id));
			return $this->db->update('works_item',$data);
		}
	}
}
?>