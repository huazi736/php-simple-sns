<?php
/**
 * 控制器类文件
 * @author mawenpei<mawenpei@duankou.com>
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

		//判断用户是否有访问视频模块的权限
		$action_uid= ACTION_UID ? ACTION_UID: $this->uid;
		$this->checkVideoPurview($action_uid);
		
    }
    /**
     * 判断用户是否有访问视频模块的权限
     * @param integer $action_uid 被访问者
     * @return boolean 例：true 表示能访问
     */
    public function checkVideoPurview($action_uid)
    {
    	$bool = service('UserPurview')->checkAppPurview($action_uid, $this->uid, 'video');
		if(!$bool)  $this->error('对不起,您没有查看视频模块的权限！');
    }
	/**
	 * 产生10位随机vvid
	 * @author qqyu
	 */	
	public function rank_vid() {
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
	 public function get_vid(){	 
	 	//产生10位vid
		$vid = $this->rank_vid();	
		$num = $this->videomodel->isVid($vid,'1');
		while ($num == 1){
			$vid = $this->rank_vid();
			$num = $this->videomodel->isVid($vid,'1');
		}
		return $vid;
	 }
} 