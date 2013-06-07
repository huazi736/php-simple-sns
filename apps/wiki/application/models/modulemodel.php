<?php
/**
 *
 * 模块操作方法
 * @author zhengfanggang
 * @date 2012年5月15日 16:04:07
 *
 *
 */
define ( "ITEMS", 'wiki_items' );
define ( "MODULE_VERSION", 'wiki_module_version' );
class ModuleModel extends MY_Model {

	protected $uid = null;
	protected $web_info = null;
	protected $web_id  = null;

	public function __construct(){

		parent::__construct();
		$this->load->model ( 'webwikimodel', 'webwiki' );
		$this->load->model ( 'versionmodel', 'version' );
		header("Content-type:text/html;charset=utf-8");

	}




	public function insertWiki($post,$web_info,$web_id,$uid) {

		$this->web_info = $web_info;
		$this->uid      = $uid;
		$this->web_id   = $web_id;
		$version        = 1;
		$citiao = $this->getWikiCitiao(array ('citiao_title' =>$this->web_info['name'] ) );
		$wiki_citiao_id = sprintf("%s",$citiao['_id']);

		//如果不存在此词条则添加，存在则不添加,存在则不添加词条，不执行此代码
		if(!$citiao['citiao_title']) {
			$data['wiki_citiao'] = array('citiao_title' => $this->web_info['name'],
			'create_time' => TIME,
			'create_uid' => $this->uid,
			'visit_count' => 1,
			'is_system' =>'0'
			);

			$wiki_citiao_id = $this->mdb->insert ( 'wiki_citiao', $data ['wiki_citiao'], 'string' );
		}


		//添加义项
		$data ['wiki_items'] = array (
		'citiao_id'      => $wiki_citiao_id,
		'create_uid'     => $this->uid,
		'item_desc'      => $post['item_desc'],
		'create_time'    => TIME,
		'visit_count'    => 1,
		'edit_count'     => $version,
		'web_name'       => $this->web_info['name'],
		'web_p_id'       => $this->web_info['imid'],
		'web_s_ids'      => $this->web_info['iid'],
		'last_datetime'  => TIME,
		'first_later'    => $post['first_later'],
		'is_system'      => '0'
		);
		$wiki_items_id = $this->mdb->insert( 'wiki_items', $data ['wiki_items'], 'string' );
		//添加内容
		$this->insertModuleVersion($post, $wiki_items_id, $this->uid, $version);
		$this->webwiki->updatebywebid($web_info, $wiki_items_id, '1');
		//$this->version->addActionRecord($this->uid, $version,$wiki_citiao_id,$wiki_items_id, $post['item_desc'], $record_content =$post['reason']);
	}


	/**
     *
     * 信息插入版本库
     * @author zhengfanggang
     * @param unknown_type $post
     * @param unknown_type $wiki_items_id
     * @param unknown_type $uid
     * @param unknown_type $version
     */
	public function insertModuleVersion($post,$wiki_items_id,$uid,$version) {

		$data['module_version'] = array('content' => $post['content'],
		'item_id' => $wiki_items_id,
		'description' => $post['description'],
		'edit_datetime' => TIME,
		'uid' => $uid,
		'version' => $version,
		'reason' => ($post['reason']? $post['reason'] : "创建第一个版本"),
		'imgDesc'=>$post['imgDesc'],
		'image_file'=>$post['uploadImgUrl']
		);
		$this->mdb->insert('wiki_module_version',$data['module_version'],'string');
		return true;
	}

	/**
     *
     * 摘要上传图片方法，将图片统一由FASTDFS处理
     * 使用此方法前，先调用checkImage($files);
     * @param  $files
     */
	public function uploadWikiPhoto($files) {
		
		//获得文件扩展名
		$temp_arr = explode(".", $files['name']);
		$file_ext = array_pop($temp_arr);
		$file_ext = trim($file_ext);
		$file_ext = strtolower($file_ext);

		$this->fdfs = $this->load->fastdfs(config_item("fastdfs_group"),true);
		$picInfo = $this->fdfs->uploadFile($files['tmp_name'],$file_ext);
		@unlink($files['tmp_name']);
		return $picInfo;

	}

	//图片上传验证
	public function checkImage($files) {

		$state ="SUCCESS";
		//定义允许上传的文件扩展名
		$upload_file_type = getWikiConfigItem('upload_file_type');

		$ext_arr  = explode(',',$upload_file_type);
		//最大文件大小
		$max_size = getWikiConfigItem('upload_file_size');
		$imageSize = @getimagesize($files['tmp_name']);

		//add by lijianwei 判断是否是真实的图片，防止伪造图片
		if(!is_array($imageSize) || !$imageSize)
		$state = '请上传图片';

		//获得文件扩展名
		$temp_arr = explode(".", $files['name']);
		$file_ext = array_pop($temp_arr);
		$file_ext = trim($file_ext);
		$file_ext = strtolower($file_ext);
		if(! in_array($file_ext, $ext_arr)){
			$state = '请选择正确的图片格式, gif,jpg,png,bmp';
		}
		if(($files['size']/1000) > $max_size) {
			$state = '图片大小请小于2M';
		}
		return $state;
	}

	//编辑器上传图片
	public  function uedWikiPhoto($files) {

		//定义允许上传的文件扩展名
		$upload_file_type = getWikiConfigItem('upload_file_type');

		$ext_arr  = explode(',',$upload_file_type);
		//最大文件大小
		$max_size = getWikiConfigItem('upload_file_size');
		$imageSize = @getimagesize($files['tmp_name']);
		//add by lijianwei 判断是否是真实的图片，防止伪造图片
		if(!is_array($imageSize) || !$imageSize)
		return array('picInfo'=>array(),'state'=>"请上传图片");

		//获得文件扩展名
		$temp_arr = explode(".", $files['name']);
		$file_ext = array_pop($temp_arr);
		$file_ext = trim($file_ext);
		$file_ext = strtolower($file_ext);

		$state ="SUCCESS";
		if(! in_array($file_ext, $ext_arr)){
			$state = '请选择正确的图片格式, gif,jpg,png,bmp';
			return array('picInfo'=>array(),'state'=> $state);
		}
		if(($files['size']/1000) > $max_size) {
			$state = '图片大小请小于2M';
			return array('picInfo'=>array(),'state'=> $state);
		}
		if ($state == "SUCCESS") {
			$this->fdfs = $this->load->fastdfs(config_item("fastdfs_group"),true);
			$picInfo = $this->fdfs->uploadFile($files['tmp_name'],$file_ext);
			@unlink($files['tmp_name']);
			if(is_array($picInfo))
			return  array('picInfo'=>$picInfo,'state'=>'SUCCESS');
			else
			return array('picInfo'=>$picInfo,'state'=>'未知错误');
		}
	}

	/**
     *
     * 获得某一义项信息
     * 最终结果只获取一条数据
     * @param ID $itemId
     * @param array $where
     */
	public function getWikiItem ($where=NULL) {

		if(empty($where)) {
			return  FALSE;
		}
		if($where && is_array($where)) {
			return $this->mdb->findOne(ITEMS,$where);
		}

	}

	/**
     *
     * 获取某版本记录
     * 最终结果只获取一条数据
     * @param ID $moduleVersionId
     * @param array $where
     */
	public function getWikiModuleVersion($where=NULL){

		if(empty($where)) {
			return  FALSE;
		}

		if($where && is_array($where)) {
			return  $this->mdb->findOne(MODULE_VERSION,$where);
		}

	}


	/**
     *
     * 获取某词条记录
     * 最终结果只获取一条数据
     * @param ID $moduleVersionId
     * @param array $where
     */
	public function getWikiCitiao($where=NULL) {

		if(empty($where)) {
			return  FALSE;
		}

		if($where && is_array($where)) {
			return  $this->mdb->findOne('wiki_citiao',$where);
		}
	}

	/**
     *
     * 获得词条的首字母
     *
     */
	public function getCitiaoFirstLater($title){

		$first_word = mb_substr($title, 0,1,"UTF-8");
		$num = ord($first_word);
		$first_letter = array();
		if ($num > 32 && $num < 127) {
			$first_letter = array(strtoupper($first_word));
		} else {
			$first_letter = $this->get_pinyin_array($first_word);
		}
		return $first_letter;
	}

	/**
     * 取汉字拼音，包括多音字，返回拼音的首字母
     * @param string $char
     * @return array
     */
	public function get_pinyin_array($char) {
		$arr = $this->mdb->findOne("wiki_ziku",array("char"=>$char),array("py"));
		$first_letter = array();
		foreach ($arr['py'] as $val) {
			$first_letter[] = strtoupper(mb_substr($val, 0 ,1));
		}
		return $first_letter;
	}
}

