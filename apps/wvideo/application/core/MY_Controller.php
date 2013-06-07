<?php
/**
 * 控制器类文件
 * @author wangying qqyu
 * @date <2012/02/14>
 */
class MY_Controller extends DK_Controller
{
    
    /**
     * 构造函数
     */    
    public function __construct()
    {
    	parent::__construct();
    	 $this->check_web();
    }
    /**
     * 检查网页，初始化
     * @author qqyu
     * Enter description here ...
     */
    protected function check_web(){
    	if($this->web_id == 0 ){
	    	if(!isset($_GET['vid'])){
	    		$this->error('温馨提示：您访问的网页不存在');
	    	}else{
	    		//根据vid来查询该视频所属网页web_id
	    		$this->load->model('videomodel');
				$vid = (int)$_GET['vid'];
	    		$info = $this->videomodel->getVideoInfo($vid,'web_id',2);
				if(empty($info)) $this->error('温馨提示：您访问的视频不存在');
	    		$this->web_id = $info['web_id'];   
	    		$this->web_info = service('interest')->get_web_info($this->web_id);
	    	}
    	}
    }
	/**
	 * 产生10位随机vvid
	 * @author qqyu
	 */	
	protected function rank_vid() {
        $chars = mt_rand(1000000000,9999999999);
        $head = '1';
        $vvid  = substr($chars, 2, 5);
        $vvid .= substr($chars, 4, 2);
        $vvid .= substr($chars, 6, 2);
        $vvid = $head.$vvid;
        return $vvid;
	 }
	/**
	 * 保证10位vid唯一
	 * @author qqyu
	 */	
	 protected function get_vid(){	 
	 	//产生10位vid
		$vid = $this->rank_vid();	
		$this->load->model('videomodel');
		$num = $this->videomodel->isVid($vid,'1');
		while ($num == 1){
			$vid = $this->rank_vid();
			$num = $this->videomodel->isVid($vid,'1');
		}
		return $vid;
	 }
} 