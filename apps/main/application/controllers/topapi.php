<?php

/**
 * 和JS交互的头部API
 * @author mawenpei<mawenpei@duankou.com>
 * @date <2012/03/23>
 */
class Topapi extends MY_Controller
{

    public function __construct()
    {        
    	parent::__construct();
    }
    
    public function action()
    {
        $html = '';
        //$html += '<script>';
        $html += 'var current_uid = ' . $this->action_dkcode;
        //$html += '</script>';
        
        echo $html;
    }

    /**
     * 获取头部未读信息条数
     * @author 
     * @date 2012-3-6
     * return json
     */
    function topUnreadCount()
    {
        $infos = $this->getUnreadCount();
        //返回json数据
        $data = array('status' => 1, 
        'data' => array('requests' => $infos['unread_friendapply'], 'messages' => $infos['unread_msg'], 'notice' => $infos['unread_notice']));
        die(json_encode($data));
    }

    /**
     * 获取头部网页信息
     * @author mawenpei<mawenpei@duankou.com>
     * @date <2012/03/15>
     */
    public function topCircleNav()
    {
        $html = $this->getWeb();
        die($html);
    }

    /**
     * 获取头部信息
     * @author mawenpei<mawenpei@duankou.com>
     * @date <2012/03/15>
     */
    public function showheader()
    {
        $message = $this->getUnreadCount();
  
        if ($this->user)
        {
			$web = $this->getWeb();  
			//$url = mk_url('main/index/main', array('dkcode'=>$this->dkcode));
            $info = array('state' => 1, 'num' => array_values($message), 'app' => $web,           
            'msg' => '' );
            $this->ajaxReturn($info,'',1,'jsonp');
        }
    }

    private function getUnreadCount()
    {
        $infos = array();
        $this->load->model('messagemodel', '', TRUE);
        
        $info = $this->messagemodel->show_unread($this->user['uid']);
        if(!$info || empty($info))
        {
            $infos['unread_friendapply'] = 0;
            $infos['unread_msg'] = 0;
            $infos['unread_notice'] = 0;
            return $infos;
        }
        
        $infos['unread_friendapply'] = $info[0]['un_invite'];
        $infos['unread_msg'] = $info[0]['un_msg'];
        $infos['unread_notice'] = $info[0]['un_notice'];
        return $infos;
    }

    private function getWeb()
    {
    	$is_current	= false;
    	$html_arr 	= null;
    	$html_arr[0]="";
    	
    	$url 	= $this->input->get_post('url');
    	/*
    	$html_arr_current = null;
    	$html_arr_current[0] = '';
        if(  strpos($url ,'/interest/web_setting') >=1 ||(strpos($url ,'interest.')>=1 && strpos($url ,'/web_setting/')>=1) ){
        	$html_arr_current[0] = 1;
        }
    	*/
        
        $domin_arr	= parse_url($url);
        $domin_arr	= $this->domain_parse(@$domin_arr['query']);
        $web_id		= @$domin_arr['web_id'];
        
        $uid		= $this->uid;
        $weblist	= service('Interest')->get_webs($uid);
		$weblist	= json_decode($weblist);
        if(is_array($weblist)){
        	foreach ($weblist as $one){
        		$one	= (array ) $one;
        		$arr 	= null;
				$arr['url'] = mk_url('webmain/index/main', array('web_id'=>$one['aid']));
	            $arr['img'] = get_webavatar( $uid , 'ss' ,$one['aid']);
	            $arr['txt'] = $one['name'];
	            if($one['aid']==$web_id && (!$is_current) ){
	            	$arr['current'] = 1;
	            	$is_current		= true;
	            }
	            $arr['web_id'] 	= $one['aid'];
	            $html_arr[] = $arr;
	        }
        }
        
        
        $arr		= null;
        $arr['url'] = mk_url('webmain/create/channel');
        $arr['img'] = '';
        if( strpos($url ,'/create/')>=1 && (!$is_current) ){
	            $arr['current'] = 1;
	            $is_current		= true;
        }
        $arr['txt'] = '+';
        $arr['web_id'] = '';
        $html_arr[] = $arr;

        /*
        $arr		= null;
        $arr['url'] = mk_url('main/index/profile');
        $arr['current'] = $html_arr_current[2];
        $arr['txt'] = '我的专页';
        $arr['web_id'] = '';
        $html_arr[2] = $arr;
        
        

        
        $arr 		= null;
        $arr['url'] = mk_url('interest/index/alist');
        $arr['current'] = $html_arr_current[0];
        $arr['txt'] = '频道';
        $arr['web_id'] = '';
        $html_arr[1] = $arr;
        */
        
        $arr 		= null;
        $arr['url'] = mk_url('main/index/profile' );
        $arr['current'] = $is_current ? 0 : 1;
        $arr['txt'] = '个人主页';
        $arr['web_id'] = '';
        $html_arr[0] = $arr;
        
        
        return $html_arr;
        
	}
	
	
	
	
	/***
	 * 排序显示  网页
	 * **/
	public function web_order(){
		$uid		= $this->user['uid'];
		$aid		= intval( $this->input->get_post('web_id') );
		//$status		= call_soap('interest', 'Index' ,'web_order' , array($uid , $aid) );
		$status		= service('Interest')->web_order($uid , $aid);
		if($status) $status = 1;
		else 		$status = 0;
		
		//echo json_encode(array('status'=>$status, 'msg'=>'' ));
		$ret = array('status'=>$status, 'msg'=>'' );
		$this->ajaxReturn($ret,'','1','jsonp');
		//echo json_encode(array('status'=>$status, 'msg'=>'' ));
		//die;
	} 
	
	/**
	 * 域名后面的查询  值  进行解析
	 * $query   传   web_id=1653&ttt=sdjjf
	 * 
	 * **/
	private function domain_parse($query){
		$arr	= explode("&",$query);
		$domain_arr = null;
		if(is_array($arr)){
			foreach($arr as $key=>$val){
				$arr2	= explode("=",$val);
				if(count($arr2)>=2){
					$domain_arr[$arr2[0]]	= $arr2[1];
				}
			}
		}
		return $domain_arr;
	}
	
	
	
	
	
	
	
	
}