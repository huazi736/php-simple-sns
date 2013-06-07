<?php
class Test extends MY_Controller {
	protected function _initialize() {
		//强制登录
		//call_soap("cache", "Memcache", "set",array(get_sessionid()."uid", 1000001035));
	}
	public function __construct(){
		parent::__construct();
		$this->load->library("Mongo_db", "", "mdb");
	}

	public function play() {
		 $this->mdb->update('wiki_items', array('_id' => new MongoId ("4fdb9fb07f8b9a7f07000001"),array('a' => 20)));
		//print_r($this->mdb->showCollections());
	}

	public function findall(){
		$result = $this->mdb->findAll('wiki_citiao',array('s_ids' =>array('$in'=>array(1))),array(),array('first_letter'=>-1));
		 
		print_r($result);
	}

	//汉字转拼音示例
	public function pinyin() {
		$this->load->library("Pinyin");
		 
		echo $this->pinyin->convert("李建伟");
	}

	public function t() {
		$uid = $pageid = 1;
		call_soap('social', 'Webpage', 'isFollowing', array($uid, $pageid));
	}

	public function testdb() {
		print_r(call_soap("ucenter", "MayKnow", "testdb"));
	}

	public function insert() {
		$data = array (
  			 "citiao_title"=>"插入测试",
  			 "create_datetime"=>123456789,
 			 "creator"=>123456,
 			 "first_letter"=>"C",
  			 "visit_count"=>0,
  			 "web_p_ids"=>array(2),
  			 "web_s_ids"=>array(32,5,2) 
		);
		$result = $this->mdb->insert("test",$data);
		$id = sprintf("%s",$result);
		var_dump($id);
	}

	/**
	 * 发送通知
	 *
	 */
	public function sendNotice(){
		$notice_type = 1;
		$uid = '1000002289';
		$to_uid = '1000002264';
		$btype = 'dk';
		$stype = 'dk_guanzhu';
		$param = array();
		$this->load->model("wikimodel", "wikimodel");
		$rs = $this->wikimodel->sendNotice($notice_type, $uid, $to_uid, $btype, $stype, $param);
		print_r($rs);
	}
	
	public function testWikiConfigItem() {
		echo getWikiConfigItem("upload_file_type");
	}
	
	public function filter(){
		$this->load->library("Mongo_db", "", "mdb");
		$this->load->model("commonmodel", "common");
		echo $this->common->filterContent("赌博机李建伟赌博机李建伟出售肾");	
	}
	
	public function webwiki(){
		$web_id = array(867,8512,948);
		$length = 140;
		$result = service('Webwiki')->getWebDesc($web_id,$length);
		
		print_r($result);
		
	}
	public function getStrLen(){
	  $str = "中国abc";
	  $str1 = "abcd";
	  echo getStrlen($str);
	  echo getStrlen($str1);	
	}
	
 	public function testa() {
    	$this->load->library('Mongon_db','','mdb');
    	
    	$result = $this->mdb->findAll('wiki_items',array('first_later'=>array()));
    	
    	print_r($result);
    }
    
    public function addvisitnum() {
    	$item_id = '4ffbc2c8dbd97c4c03009880';
    	$citiao_id = '5004c5a47f8b9ac867000001';
    	
    	$this->load->model('commonmodel','common');
    	
    	$result = $this->common->setVisitNum($citiao_id,$item_id);
    	
    	var_dump($result);
    }
    
    
	public function getpinyin() {
    	
    	$str = file_get_contents(EXTEND_PATH . DS .'vendor' .DS. 'Solr'.DS.'Pinyin'.DS.'pinyin.txt');
    	
    	$handle = fopen(EXTEND_PATH . DS .'vendor' .DS. 'Solr'.DS.'Pinyin'.DS.'pinyin.txt', 'rb');
    	
    	if (!$handle) {
    		die('文件打开失败！');
    	}
    	
    	$result = array();
    	
    	while (!feof($handle)) {
    		
    		$line = fgets($handle);
    		
    		$word = mb_substr($line, 0,1);
    		
    		preg_match_all("/$word([a-z]+)/is", $str, $matches);
    		
    		$result[$word] = $matches[1];
    		
    		//$this->insert_ziku($word,$matches[1]);
    	}
    	
    	$this->insert_ziku('pinyin',serialize($result));
    	
    	fclose($handle);
    	
    }
    
    public function showpinyin() {
    	$this->load->library("Mongo_db",'','mdb');
    	
    	$data = $this->mdb->findOne("ziku");
    	
    	
    	$data = unserialize($data['py']);
    	
    	foreach ($data as $key=>$val) {
    		$this->insert_ziku($key,$val);
    	}
    	
    }
    
    public function insert_ziku($char = '',$py = array()) {
    	
    	
    	$data = array(
    			'char' => $char,
  				'py' =>$py 
  
    		);
    	
    	$this->mdb->insert('ziku1',$data);
    }
    
    public function test_widget(){
    	wiki_widget("location", array("web_id" => 1327));
    }
    
    public function test10(){
    	$a = $this->mdb->findOne("wiki_web_plugin_info", array("_id" => new MongoId("500fe78d4e313ba009000002"), "plugin_values.plugin_config_id" => "2", "plugin_values.value" => "值2"));
    	print_r($a);
    	
    	//只更新数组中匹配的一个元素
    	//$this->mdb->update_custom("wiki_web_plugin_info", array("_id" => new MongoId("500fe78d4e313ba009000002"), "plugin_values.plugin_config_id" => "1"),array('$set' => array("plugin_values.$.plugin_config_id" => "lijianwei")));
    }
}