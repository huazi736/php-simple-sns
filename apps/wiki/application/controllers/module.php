<?php
/**
 *
 * 一个词条由不同的模块组成，可以对词条的模块进行增加，修改模块属性
 * 这个页面主要实现这些功能
 * @author  zhengfanggang
 * @version 1.2.101.01
 * @date 2012年6月19日 10:45:32
 *
 */
class Module extends MY_Controller {

	private $upload_file_type ='';
	public function __construct() {

		define("TIME",time());
		parent::__construct ();
		$this->load->library ( "Mongo_db", "", "mdb" );
		$this->load->model ( 'webwikimodel', 'webwiki' );
		$this->load->model ( 'modulemodel', 'module' );
		$this->load->model ( 'versionmodel', 'version' );
		$this->load->model ('commonmodel','commom');
		$this->load->model ('privmodel','private');
		header("Content-type:text/html;charset=utf-8");
		//获得可上传图片类型
		$this->upload_file_type = getWikiConfigItem('upload_file_type');
		$fileType =  str_replace(',','、',$this->upload_file_type);
		$this->assign('fileType',strtoupper($fileType));
		$this->assign('url_wiki_edit',mk_url('wiki/module/doEdit'));
	}

	final public  function index() {}

	/**
     *
     * @author zhengfanggang
     * @date  2012年6月16日
     * 由网页为入口，创建词条页面，网页ID必须有
     */
	final public   function add() {
		$error = '';

		if(! $this->web_id) {
			$error = '没有权限创建词条';
		}
		if(!$this->private->check_match($this->web_id, $this->uid))  {
			$error = '没有权限创建词条';
		}
		$this->error($error);

		if($this->web_info['imid']) $imidName = service("Interest")->get_category_main($this->web_info['imid']);
		if($this->web_info['iid']) $iidName  = service("Interest")->get_iid_info($this->web_info['iid']);
		$imname = isset($imidName[0]['imname']) ? $imidName[0]['imname'] : '';
		$iname  = isset($iidName['iname']) ? $iidName['iname'] : '';
		if($imname && $iname)
		    $defaultName = $imname.'-'.$iname;
		else
		    $defaultName = $imname.$iname;

		$this->assign("defaultName",$defaultName ? $defaultName : '请输入义项名称');
		$this->assign("web_id",$this->web_id);
		$this->assign("citiao",$this->web_info['name']);
		$this->assign("edit",FALSE);
		$this->assign("quote",FALSE);
		$this->assignHeaderNav(array("创建词条"));
		$this->assign("image_file",'');
		$this->display("wiki_edit.html");

	}

	/**
     *
     *@author zhengfanggang
     * @date 2012年6月16日
     *
     * 编辑词条，读取词条内容显示给用户
     */
	final public  function edit() {
		
		$error = $item = $module = NULL;
		$post = $this->input->get();
		$get = array(
		'item_id' => isset($post ['item_id']) ? $post ['item_id'] : '',
		'version' => isset($post ['version']) ? $post ['version'] : ''

		);
		if(! $this->web_id) {
			$error = '缺少参数web_id参数';
		}
		if(! $get['version']) {
			$error = '缺少版本号';
		}
		$this->error($error);

		$status = $this->private->check_match_checkbox("edit",$get['item_id'],$get['version'],$this->web_id,$this->uid);
		$quote = $status['quote'];
		$item   = $this->module->getWikiItem(array ('_id' => new MongoId ($get['item_id'])));
		$citiao = $this->module->getWikiCitiao (array ('_id' => new MongoId ($item['citiao_id'])));
		$get['version']  = $get['version'] ? $get['version'] : $item['edit_count'];
		$module = $this->module->getWikiModuleVersion(array ('item_id'=>$get['item_id'],'version'=>intval($get['version'])));
		foreach (array('description','imgDesc')  as $val){
			$module[$val] = preg_replace("#(</|<|<\?|>|/>)#",'',htmlspecialchars_decode($module[$val]));
		}

		$this->assign("module",$module);
		$this->assign("item",$item);
		$this->assign("citiao",$citiao['citiao_title']);
		$this->assign("image_file",isset($module['image_file'])? $module['image_file'] :MISC_ROOT.'img/system/wiki_default.gif');
		$this->assign("web_id",$this->web_id);
		$this->assign("web_name",$this->web_info['name']);
		$this->assign("edit",TRUE);
		$this->assignHeaderNav(array("编辑词条"));
		$this->assign("quote",$quote);
		$this->assign("item_id", G("item_id"));
		$this->display("wiki_edit.html");

	}



	/**
     *
     * 添加，编辑百科内容，统一提交地址
     * URL：index.php?c=module&m=doEdit
     *
     */
	final public  function doEdit() {
		$error = '';

		// 数据消毒  【开启防XSS攻击】
		$post['item_desc'] = $this->input->post ('item_desc',TRUE);
		$post['imgDesc'] = $this->input->post ('imgDesc',TRUE);
		$post['reason']  = $this->input->post ('reason',TRUE);
		$post['web_id'] = $this->input->post ('web_id',TRUE);
		$post['item_id'] = $this->input->post ('item_id',TRUE);
		$post['uploadImgUrl'] = $this->input->post ('uploadImgUrl',TRUE);
		$post['quote'] = $this->input->post ('quote',TRUE);
		$post['description'] = $this->input->post ('description',TRUE);
		//		$post['content'] = htmlspecialchars($this->input->post ('content',TRUE),ENT_QUOTES);
		$post['content'] = safedata($this->input->post('content'),'010');

		foreach (array('item_desc','imgDesc','reason','web_id','item_id','uploadImgUrl','quote','description')  as $val){
			$post[$val] = preg_replace("#(</|<|<\?|>|/>)#",'',htmlspecialchars(addslashes($post[$val]),ENT_QUOTES));
		}

		if (!$post['item_desc']) {
			$error = '义项必须有';
		}
		if(!$post['web_id']) {
			$error = '缺少参数web_id参数';
		}

		//摘要无图片设为默认图片
		if($post['uploadImgUrl']=='') {
			$post['uploadImgUrl'] = MISC_ROOT.'img/system/wiki_default.gif';
		}

		$post['imgDesc'] = $this->checkStrLen($post['imgDesc'],15);  // 规定长度字符
		$post['description'] = $this->checkStrLen($post['description'],300);
		$post['item_desc']   = $this->checkStrLen($post['item_desc'],20);
		$post['first_later'] = $this->module->getCitiaoFirstLater($this->web_info['name']);
		$item = $this->module->getWikiItem(array ('_id' => new MongoId ($post['item_id'])));
		$post['citiao_id'] = isset($item['citiao_id']) ? $item['citiao_id'] : '';
		$this->error($error);
		if ($post['item_id']) {
			//【编辑词条】

			if (isset($post['citiao_id']) && $post['citiao_id']) {
				$this->update($post);
				//service('credit')->wiki();   2012/7/23 devin_yee 积分规则待定
				$this->Success($post);
			}
			else
			$error = '没有此义项';
		}
		else {
			//【添加词条】

			$this->module->insertWiki($post,$this->web_info,$this->web_id,$this->uid);
			//service('credit')->wiki();  2012/7/23 devin_yee 积分规则待定
			$this->Success($post);
		}

		$this->error($error);
	}


	/**
     *
     * 编辑词条
     * @param array $post
     * @param array $item
     */
	final protected  function  update($post) {

		// 【计算版本号】
		$web = $this->module->getWikiItem(array ('_id' => new MongoId ($post['item_id'])));
		$version = 1;
		if($web['edit_count'] > 0){
			$version = $web['edit_count'] + 1;
		}

		//【版本数据入库】
		$this->module->insertModuleVersion($post,$post['item_id'],$this->uid,$version);
		//【修改编辑次数】
		$this->mdb->update('wiki_items',array('_id' => new MongoId ($post['item_id'])),
		array('edit_count'=>$version,'last_datetime'=>TIME));
		// 是否引用词条
		if ($this->web_info['uid']==$this->uid &&  $post['quote']=='on') {
			$this->webwiki->updatebywebid($this->web_info,$post['item_id'],$version);
		}
		else {
			$this->webwiki->updatebyitemid($post['item_id'],$version);
		}
		//【日志记录】
		// $this->version->addActionRecord($this->uid, $version, $post['citiao_id'], $post['item_id'], $post['item_desc'], $record_content =$post['reason']);
	}


	protected   function Success($post) {

		$web_id = $post['web_id'];
		$is_match = $this->private->check_match($this->web_id,$this->uid);
		if((!$post['item_id'] && $is_match) || ($post['item_id'] && $post['quote'] && $is_match)){//创建者添加词条
			$back_url = $web_id ? mk_url("wiki/webwiki/index", array("web_id" => $web_id)) : "";
		} else {
			$back_url = mk_url("wiki/citiaoContent/index",array("citiaoid"=>$post['citiao_id'],"mtmeas"=>$post['item_id'],"web_id"=>$post['web_id']));
		}
		//$this->redirect($back_url);
		header("Location: $back_url", TRUE, 302);
	}

	/**
     *
     * 错误提示函数
     * @param unknown_type $content
     */
	public  function error($content='') {
		if ($content) {
			header("Content-type:text/html;charset=utf-8");
			exit("<script>alert('{$content}'); history.go(-1);</script>");
		}
	}

	/**
     *
     * @author zhengfanggang
     * @date 2012年6月26日
     * 编辑器图片处理方法
     *
     */
	final public function doFile() {


		$fckPicInfo =  $this->module->uedWikiPhoto($_FILES['upfile']);
		$fileUrl = 'http://'. config_item('fastdfs_domain') .'/' . $fckPicInfo['picInfo']['group_name'] . '/' . $fckPicInfo['picInfo']['filename'];

		$title = htmlspecialchars( $_POST[ 'pictitle' ] , ENT_QUOTES );
		$oriName =$_FILES['upfile']['name'];
		//echo json_encode(array('url' => $fileUrl,'title'=>$title,'original'=>$oriName,'state'=>'SUCCESS'));
		echo "{'url':'" . $fileUrl . "','title':'" . $title . "','original':'" . $oriName . "','state':'" . $fckPicInfo['state'] . "'}";
		exit;
	}

	/**
     *
     * 摘要图片上传
     */
	final public function doDescImage() {

		if($_FILES['uploadFile']){

			$filename = $_FILES['uploadFile'];
			$state = $this->module->checkImage($filename);
			if(!($state =='SUCCESS')){
				exit("<script>alert('{$state}!');parent.document.getElementById('wikiImgBgWrap').className = 'wikiImgBgWrap';parent.document.getElementById('uploadImg').style.display='';parent.document.getElementById('uploadPath').value='';</script>");
			}
			$D = getimagesize($filename['tmp_name']);
			$imagewidth		= $D['0'];
			$imageheight	= $D['1'];

			//设置图片裁剪默认大小
			$width = '180';
			$hight ='145';
			if($imagewidth < $imageheight){
				$width = '145';
				$hight ='185';
			}
			$temp_arr = explode(".", $filename['name']);
			$file_ext = array_pop($temp_arr);
			$file_ext = trim($file_ext);
			$file_ext = strtolower($file_ext);

			$local_server_tmp_storage_path = config_item("tmp_storage_path");
			$org_filename =  rtrim($local_server_tmp_storage_path, "/") . "/" . date("YmdHis") . mt_rand(1000, 9999). "." . $file_ext;
			$this->commom->resizeImageRatio($filename['tmp_name'],$org_filename,$width,$hight);
			$filename['tmp_name'] = $org_filename;
			$org_pic_info = $this->module->uploadWikiPhoto($filename);
			@unlink($org_filename);

			$fileUrl = 'http://'. config_item('fastdfs_domain') .'/' . $org_pic_info['group_name'] . '/' . $org_pic_info['filename'];
			$host = substr(strstr($_SERVER['HTTP_HOST'],'.'),1);
			echo "<script>document.domain ='{$host}'; parent.upload_callback('{$fileUrl}')</script>";
		}

		else
		return '0';
	}

	/**
     *
     * 获取规定长度的字符串
     * @param unknown_type $str
     * @param unknown_type $len
     */
	protected  function checkStrLen($str,$len='1') {

		$strLen = getStrlen($str);
		if($strLen && $strLen > $len)
		return mb_substr($str,0,$len);
		else
		return $str;
	}
}