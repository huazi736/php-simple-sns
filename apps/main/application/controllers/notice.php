<?php
if (! defined('BASEPATH'))
    exit('No direct script access allowed');

/**
 * 通知类
 * 
 * @author gefeichao
 * @date   2012-02-23
 */
class Notice extends MY_Controller
{
    function __construct()
    {
        parent::__construct();
       
        $this->load->model('noticemodel', '', TRUE);
        $userinfo = $this->user; 
		define('UID', $this->uid);
        $this->assign('login_username',$userinfo['username']);
        $this->assign('login_email',$userinfo['email']);
        $this->assign('user',$this->user);
		
    }

    function index()
    {
        //默认加载通知信息
		$this->list_notice();
		/*	 
		$rel = service('notice')->add_notice('1',1000002105,1000002106,'info','info_froward_blog',array('name'=>'日志之九评共产党','url'=>'http://www.google.com'));
		var_dump($rel);exit();*/
    }
	
    /**
     * 显示通知弹出层 top 5
     * @author gefeichao
     * return array
     */
    function top_notices()
    {
    	
        $noticestr = $this->noticemodel->list_notice_m($this->uid, 5);
        //清空未读通知数
		service('Notice')->setting(UID, 'editnotice');
        //返回通知信息列表
        $str = "";$state = 0;
        if ($noticestr)
        {
            foreach ($noticestr as $nstr)
            {
                if ($str == "")
                {
                    $str .= "<li class='firstChild' >";
                }
                else
                {
                    $str .= "<li class=''>";
                }
                 $str .= "<div class='uiImageBlock clearfix'>";
                if ($nstr['t'] == "photo")
                {
                    $str .= "<a title='".$nstr['content2']."' href='" . $nstr['url'] . "' class='itemBlock picView clearfix'>";
                }
                else
                {
                    $str .= "<a title='".$nstr['content2']."' href='" . $nstr['url'] . "' class='itemBlock clearfix'>";
                }
               
                $str .= "<img class='uiProfilePhoto fl' src='" . $nstr['avatar'] . "' alt='头像' />
							<div class='uiImageBlockContent'><div>";
                $str .= $nstr['content1'] . "</div><div class='metadata'>
							<div class='time clearfix'>";
                $str .= "<i class='icon ".$nstr["type"]."'></i>";
                $str .= "<abbr class='timestamp'>" . $nstr['dateline'] . "</abbr>
							</div></div></div></a></div></li>";
            }
            $state = 1;
        }
        else
        {
            $str .= "<li class='firstChild'><span class='not-notice-list'>暂时没有可显示的通知</span></li>";
            
        }
		$data = array('state' => $state, 'data' => $str, 'msg' => '通知获取错误！');
		$this->ajaxReturn($data,'',$state,'jsonp');
    }

    /**
     * 获取通知
     * @author gefeichao
     * @access public
     * @param $uid 接收者id
     */
    function list_notice()
    {
    	//获取网页信息
		$result = service('Interest')->get_webs(UID);
		$result = json_decode($result);
    	if(!$result)$result=array();
    	$myrelsult = array();
    	foreach ($result as $value) {
			$value 	= (array) $value;
    		$myshow = array();
    		$title = $value['name'].'的通知';
    		$ntype = msubstr($title, 0, 8);
    		$myshow['name'] = $title;
    		$myshow['name1'] = $ntype;
    		$myshow['aid'] = $value['aid'];
    		$myrelsult[] = $myshow;
    	}
    	$this->assign('type',$myrelsult);
        $this->assign ('user',$this->user);
        $this->display('notice/all_notice.html');
    }

    /**
     * 异步获取通知列表
     * @author gefeichao
     */
    function get_notice()
    {
        //获得通知列表
        $pagesize = 30;
        $more = true;$status = 1;
        $page = $this->input->post('page') ? $this->input->post('page') : 1;
        $typeid = $this->input->post('typeid') ? $this->input->post('typeid') : '';
        $limit = ($page-1) * $pagesize;
        $result = $this->noticemodel->list_notice_m($this->uid, '', $limit, $pagesize, (string)$typeid);
		
        $tar = array(); //所有时间列表数组
        if ($result)
        {
            foreach ($result as $res)
            {
                $time = date('m d', $res['date']);
                $tar[$time] = $res['date'];
            }
            array_unique($tar);
            $arrcount = $acount = array(); //代表 分组数组  $arrcount按直接分组集合  $acount 为整理完的完整集合
            $cTime = time();
            foreach ($tar as $t)
            {
                $arrs = array(); //存储 嵌套出的 按时间分组的 通知信息
                foreach ($result as $rt)
                {
                    if (date('m d', $rt['date']) == date('m d', $t))
                    {
                        $arrs[] = $rt;
                    }
                }
                $dTime = date('Ymd', $cTime) - date('Ymd', $t);
                if ($dTime < 1)
                {
                    $arrcount[0] = '发于 今天';
                }
                else
                {
                    $arrcount[0] = date("m月d日", $t);
                }
                $arrcount[1] = $arrs;
                $acount[] = $arrcount;
            }
	        $count = $this->noticemodel->getcount($this->uid, $typeid);
	        if ($count > ($page * $pagesize))
	        {
	            $more = false;
	        }
        }
        else
        {
            $acount = "";
            $status = 0;
        }
        
        $data = array('status' => $status, 'data' => $acount, 'isend' => $more, 'msg' => '获取通知信息失败');
		$this->ajaxReturn($data,'',$status,'json');
    }

    /**
     * 删除通知
     * @author gefeichao
     * @access public
     * @param $id 记录id
     */
    function del_notice()
    {
        $id = $this->input->post('rid');
        if (! $id)
        {
            $this->showmessage('编号不能为空！');
            return FALSE;
        }
        $state = $this->noticemodel->del_notice($id);
        if ($state)
        {
            $sta = 1;
        }
        else
        {
            $sta = 0;
        }
        $data = array('state' => $sta, 'data' => '这里是失败信息');
		$this->ajaxReturn($data,'',$sta,'json');
    }
    
    /**
     * 获得用户通知设置选中项
     * @author gefeichao
     * @access public
     * @param uid  当前用户uid
     * @return array
     */
    function settingnotice()
    {
        $lists = $this->noticemodel->noticesettingscount($this->uid);
		$weblists = $this->noticemodel->noticesettingsweb($this->uid);
        $this->assign('web_list', $weblists);
        $this->assign('userlist', $lists);
        $this->display('setting-userinfo/setting_notification.html');
    }

    /**
     * 修改用户通知设置
     * @author gefeichao
     * @return json
     */
    function noticeeditsetting()
    {
        $data = $this->input->post('data');
        if (count($data) == 1)
        {
            $d = array();
        }
        else
        {
            $d = $data['data'];
        }
        $this->noticemodel->noticeeditsetting($d, $this->uid, $data['type']);
        $arr = array('state' => 1, 'message' => 'success');
		$this->ajaxReturn($arr,'',1,'json');
    }
}
?>