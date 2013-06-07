<?php

/**
 * 通知接口
 */
class NoticeService extends DK_Service {

    protected $mongo;

    function __construct() {
        parent::__construct();
		$this->mongo =	get_mongodb('default');
    }

    /**
     * 批量删除通知
     * @author gefeichao
     * Enter description here ...
     * @param  $id  通知分类id
     */
    function del_noticeall($id = NULL) {
        if (!$id)
            return false;
        $result = $this->mongo->where(array('ntype' => $id))->update_all('notice', array('is_delete' => 0));
        return $result;
    }

   /**
	 * @author gefeichao
	 * 添加通知消息
	 * @param $ntype  通知分类 个人通知为 1  网页通知为 网页id
	 * @param $uid	  当前uid
	 * @param $touid	  接收 uid
	 * @param $btype    通知大分类
	 * @param $stype    通知小分类 关于分类 请联系我给你添加
	 * @param $temp     数组 array('name'="",'url'="");
	 * @return array() 
	 */
	public function add_notice($ntype="1", $uid=NULL, $touid=NULL, $btype=NULL, $stype=NULL, $temp=null)
	{
		//对传入参数进行有效性验证
		//return $uid;		   
		if(is_array($touid)){
			$arrays = $touid;
		}else{
			$arrays =  array($touid);
		}
		if(!trim($btype))  return 2;
		if(!trim($stype))  return 3;
		if(!trim($uid))  return 4;
		/*if($uid==$touid && $stype != 'upload_true_videoweb' && $stype != 'upload_false_videoweb' 
		&& $stype != 'video_upload_true' && $stype != 'video_upload_false' && $stype != 'event_update_web' 
		 && $stype != 'event_ban_web') return 1;
		 */
		$state = 0; $s = array();
		//判断数组内容
			foreach ($arrays as $value) {
				$value = strval($value);
				$status=$this->gl_notice($btype, $stype, $value);
				if($status===FALSE){
					continue;
				}
				$ci =  get_instance();

				//$this
				$ci->config->load('notice');
				$temArray = config_item($stype);
				if(!$temArray)	return 5;
				$content = array($uid , $temArray , $temp);
				if(isset($temp['dateline'])){
					$date = $temp['dateline'];
				}else{
					$date = time();
				}
				if(isset($temp['state'])){
					$state = 1;
				}else{
					$state =0;
				}
				$mongoid = new MongoId();
		        $data = array(
		        	'_id'=>$mongoid,
		            'ntype' =>strval($ntype),
		            'uid' => $value,
		            'type' => $btype,
		            'stype' => $stype,
		            'content' => $content,
		            'dateline' => $date,
		            'is_delete' => 1,
		            'is_read' => 0
		        );
		        
		        $this->mongo->insert('notice', $data);
		        $result = $mongoid . '';
		        /* 添加未读通知数 */
		        if ($state == 0)
		            $this->setting($value, 'addnotice');

				if(!$result){
					continue;
				}else{
					$s[]=$result;
				}
			}
			 return $s;
			
	}
	
	/**
	 * 站内信、请求、通知总数加减
	 *
	 * @author gefeichao
	 * @modifer liufeng
	 * @date   2011/10/20
	 * @access public
	 * @param $uid 用户id
	 * @param $coltype 执行的字段名
	 */
	function setting($uid = null, $coltype = null,$num= null){
		if(!$uid || !$coltype){
			return false;
		}
		if(!$num){
			/*获取用户未读设置*/
			$result = $this->mongo->where(array('uid' => $uid))->select(array('un_msg','un_notice','un_invite'))->get('expand');
			if(!$result){
				$data = array('un_msg' => 0,'un_notice' => 0, 'un_invite' => 0, 'notice' => '', 'uid' => $uid);
				$this->mongo->insert('expand', $data);
				$un_msg = $un_invite = $un_notice = 0;
			}else{
				$un_msg = $result[0]['un_msg'];
				$un_invite = $result[0]['un_invite'];
				$un_notice = $result[0]['un_notice'];
			}
		}
		switch ($coltype){
			case 'addmsg':
				$modify_sql = $this->mongo->where(array('uid' => $uid))->update('expand',array('un_msg' => $un_msg + 1));
				break;
			case 'addinvite':
				$modify_sql = $this->mongo->where(array('uid' => $uid))->update('expand',array('un_invite' => $un_invite + 1));
				break;
			case 'addnotice':
				$modify_sql = $this->mongo->where(array('uid' => $uid))->update('expand',array('un_notice' =>$un_notice + 1));
				break;
			case 'editmsg':
				$modify_sql = $this->mongo->where(array('uid' => $uid))->update('expand',array('un_msg' => 0));
				break;
			case 'editinvite':
				if(!$num){
					if($un_invite ==0)	$num=0;
					else  $num = $un_invite -1;
					$modify_sql = $this->mongo->where(array('uid' => $uid))->update('expand',array('un_invite' => $num));
				}else{
					$modify_sql = $this->mongo->where(array('uid' => $uid))->update('expand',array('un_invite' => $num));
				}
				break;
			case 'editnotice':
				$modify_sql = $this->mongo->where(array('uid' => $uid))->update('expand',array('un_notice' => 0));
				break;
		}
		if(!isset($modify_sql)){
			return false;  //放在switch的default分支里做判断应该会更好些！
		}

		return true;

	}

    /**
     * 修改通知
     * @access public
     * @author gefeichao
     * @date 2012/05/14
     * @param $nid 通知id
     * @param  $date 修改时间
     * @return bool
     */
    function edit_notice($nid=null, $date=null) {
        if (!$nid || !$date)
            return false;
        $state = $this->mongo->where(array('_id' => new MongoId($nid)))->update('notice', array('dateline' => $date));
        return $state;
    }

    /**
     * 发送通知过滤函数
     * @author gefeichao
     * @param $btype 通知设置大分类
     * @param $stype 通知小分类
     * @param $uid 用户uid
     */
    function gl_notice($btype = NULL, $stype = NULL, $uid = NULL) {
        /*
         * 过滤函数 只要判断  传入的那一项设置 用户有没有设置过  有 false 无  true
         * */
        if (!$btype || !$stype || !$uid) {
            return false;
        }

        $valstr_rel = $this->mongo->where(array('uid' => $uid))->limit(1)->select(array('notice'))->get('expand');
        if (!$valstr_rel || !$valstr_rel [0]['notice']) {
            return true;
        }
        $arrays = $valstr_rel[0]['notice'];
        foreach ($arrays as $v) {
            if ($v[0] == $btype) {
                return in_array($stype, $v[1]) ? false : true;
            }
        }
    }

}