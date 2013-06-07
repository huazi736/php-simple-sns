<?php

/**
 * if (! defined ( 'BASEPATH' ))
	exit ( 'No direct script access allowed' );
 * 站内信息
 * Enter description here ...
 * @author gefeichao
 * @date   2012-02-23
 */
 
class Msg extends MY_Controller
{
    protected $fdfs, $url,$fastdfs;

    function __construct()
    {
        parent::__construct();
        define('UID', $this->uid);
        $this->load->model('messagemodel', '', TRUE);
		$this->fastdfs = require_once  CONFIG_PATH . 'fastdfs.php';
    }

    function index()
    {
        //默认加载站内信信息
        $this->show_message();
    }

	/**
     * 返回查询符合条件好友列表
     * @author	gefeichao
     * @return  jsonp  操作结果
     */
    function getfriends()
    {
        $keyword = $this->input->get('searchString');
        $users = $this->input->get('userids');
        //获取列表
		$result = service('PeopleSearch')->getFollowingUserEachOther(UID, shtmlspecialchars($keyword));
        $results = array();
        $result = (array)json_decode($result);
        if ($result['total'] > 0)
        {
            $user = explode(',', shtmlspecialchars($users));
            
           foreach ($result['object'] as $value)
           {
           		$value = (array)$value;
           		if(in_array($value['id'], $user) == FALSE){
           			 	$array['userid'] = $value['id'];
                        $array['username'] = $value['name'];
                        $array['avatar'] = get_avatar($value['id']);
                        $array['location'] = "中国";
                        $results[] = $array;
           		}
            }
        }
        $data = array('status' => 1, 'compactedObjects' => $results);
		$this->ajaxReturn($data,'',1,'jsonp');
    }
	
	/*转换 站内信 数据库 信息*/
	function replace_message(){
		$result = $this->messagemodel->replace_message();
		//var_dump($result);
	}

    /**
     * 新建站内信
     * @author	gefeichao
     * @return  json  操作结果
     */
    public function add_msg()
    {
        $t_uid = $this->input->post('userids'); //接收uid
        $message = $this->input->post('newMessageContent'); //信息内容
        $fileNames = $this->input->post('fileNames');
        if (! $t_uid)
        {
            return false;
        }
        if (! $message)
        {
            $message = "";
        }
        $result = $this->messagemodel->add_message(UID, shtmlspecialchars($t_uid), shtmlspecialchars($message), $fileNames);
        $data = array('state' => 1, 'locationID' => $result, 'info' => '操作成功!', 'data' => '失败信息');
		$this->ajaxReturn($data,'',1,'json');
    }

    /**
     * 回复站内信
     * @author gefeichao
     */
    function hf_msg()
    {
        $to_uid = $this->input->post('targetUserID');
        $message = $this->input->post('newMessage');
        $filename = $this->input->post('attachedFileName');
        $gid = $this->input->post('gid');
		//发送通知
        $result = $this->messagemodel->reply_message(UID, shtmlspecialchars($to_uid), shtmlspecialchars($message), $filename, shtmlspecialchars($gid));
        $state = $result ? 1 : 0;

        $filenames = explode(',', $filename);
		$fastdfs = $this->fastdfs['default'];
        foreach ($filenames as $f)
        {
            $filea = $this->messagemodel->get_files($f);
            if ($filea)
            {
            	$group = $filea[0]['group_name'];
                $filename = $filea[0]['orig_name'];
                $filea[0]['downurl'] = mk_url('main/msg/downloadPhoto', array('id' => $f));
                $filea[0]['url'] = 'http://'.config_item('fastdfs_domain') .'/' . $group . '/' . $filename;
                $filearray[] = $filea[0];
            }
            else
            {
                $filearray[] = "";
            }
        }
        
        $data = array(
        		'state' => $state, 
        		'info' => '操作成功!', 
        		'data' => '消息内容不能为空！', 
        		'content' => shtmlspecialchars($message), 
        		'avatar' => get_avatar($this->uid), 
        		'userid' => $this->uid, 
        		'username' => $this->username, 
        		'replytime' => friendlyDate(time() - 1), 
        		'replyfrom' => '聊天室', 
        		'dataid' => '223214', 
        		'files' => $filearray);
		 $this->ajaxReturn($data,'',$state,'json');
    }

    /**
     * 查看站内信详细对话列表
     * @author gefeichao
     * @access public
     * @param $fromid 会话id
     */
    function list_msg()
    {
        $fromid = $this->input->get('fromid')?strip_tags($this->input->get('fromid')):"";
       	$lastid = $this->input->get('lastId')?strip_tags($this->input->get('lastId')):"";
        //获取群组成员
        $users = $this->messagemodel->showgroup($fromid);
        $ulist = array();
         $list = explode(',', $users['g_list']);
    		foreach ($list as $v)
            {
                if ($v != UID)
                {
                    $ulist[] = $v;
                }
            }
        $this->assign('msg_url',mk_url('main/msg/show_message'));
        $this->assign('lastid', $lastid);
        $this->assign('gid', $fromid);
        $this->assign('avatar', get_avatar($this->uid));
        $this->assign('user', $this->user);
        $this->assign('fromid', implode(',', $ulist));
        $this->display('message/msgInfo.html');
    }
	
	/*异步加载 消息对话列表*/
    function msgdetail_more(){
    	$fromid = $this->input->get('dataid');
    	$pagesize = 9;
        $more = true;$status = 1;$msginfo = array();
        $page = $this->input->post('page') ? $this->input->post('page') : 1;
        $limit = ($page-1) * $pagesize;
        $result = $this->messagemodel->showdetailmessage($fromid, $pagesize, $limit);
		if (! $result) $result = array();
		$nextpage = array_pop($result);
            foreach ($result as $r)
            {
                $myfile = array();
                $myfiles = explode(',', $r['files']);
                foreach ($myfiles as $j)
                {
                    $file = $this->messagemodel->get_files($j);
                    if ($file)
                    {
                    	$file[0]['id']=$j;
                    	$file[0]['downurl'] = mk_url('main/msg/downloadPhoto', array('id' => $j));
                        $group = $file[0]['group_name'];
                        $filename = $file[0]['orig_name'];
                        $file[0]['url'] = 'http://'. config_item('fastdfs_domain') .'/' . $group . '/' . $filename;
                    }
                    else
                    {
                        $file[0] = '';
                    }
                    $myfile[] = $file[0];
                }
                $r['userpath'] = mk_url('main/index/main', array('dkcode' => $r['dkcode']));
                $r['files'] = $myfile;
                $msginfo[] = $r;
            }
        //清空未读信息数
		service('Notice')->setting(UID, 'editmsg');
		$messresult['more'] = $nextpage ==1 ? false : true ;
		$messresult['result'] = $msginfo;
		$messresult['state'] =  count($msginfo)>0 ? '1' : '0';
        $data = array('status' => $messresult['state'], 'data' => '这里是失败信息', 'messages' => $messresult['result'], 'isend' => $messresult['more']);
		$this->ajaxReturn($data,'',1,'json');
    }
    
   
    /**
     * 获取站内信已存档列表
     * @author	gefeichao
     * @date	20111110
     * @param  $page
	 * @param $pages 传入每页数量
	 * @param $searchkey 传入搜索关键字
     * @return	array
     */
    function showarchivelist($page = 0, $pages = 10, $searchkey = NULL)
    {
        $pagesize = $page ==1 ? $pages * ($page - 1) : $pages * ($page - 1) + $pages;
        $archivelist = $archiveresult = array();
        //调用后台方法，获得数据结果集
        $archiveresult = $this->messagemodel->message_archivelist($searchkey,$pages,$pagesize);
        if (! $archiveresult)
        {
            $archiveresult = array();
        }
		$nextpage = array_pop($archiveresult);
        foreach ($archiveresult as $value)
        {

			$username = $this->username;
			$str = "";$i = 0;$j = 0;
			
				$ulist = isset($value['u_list']) ? explode(',', $value['u_list']) : explode(',', $value['g_list']);
				$glist = explode(',', $value['g_list']);
				
				foreach ($ulist as $ul){
						$i++;
						$str = $str=="" ? $ul : $str . "," . $ul;
						if($i >= 3){
                			$j = count($ulist)-4;
                			break;
                		}
				}
                
				foreach ($glist as $gl){
					 if (count($glist) == 2){
						if ($gl != UID){
							$value['avatar'][] = get_avatar($gl); //获得用户头像
						}
					} else{
						$value['avatar'][] = get_avatar($gl); //获得用户头像
					}
				}
             if($j>0)	$str .= "还有其他". $j ."人";
            $value['username'] = $str;
            //获取已存档列表
            $archivelist[] = $value;
        }
		$resultarray['more'] = $nextpage ==1 ? false : true ;
		$resultarray['result'] = $archivelist;
		$resultarray['state'] =  count($archivelist)>0 ? '1' : '0';
        return $resultarray;
    }

    /**
     * 获取站内信未读信息列表
     * @author	gefeichao
     * @param  $page	传入页数
	 * @param $pages 传入每页数量
	 * @param $searchkey 传入搜索关键字
     * @return	array
     */
    function showunreadlist($page = 0, $pages = 10, $searchkey = NULL)
    {
		
        $pagesize = $page == 1 ? $pages * ($page - 1) : $pages * ($page - 1) + $pages;
        //调用后台方法，获得数据结果集
        $unreadlist = array();
        $unreadresult = $this->messagemodel->message_unreadlist($searchkey,$pages,$pagesize);
        if (! $unreadresult)
        {
            $unreadresult = array();
        }
		$nextpage = array_pop($unreadresult);
        foreach ($unreadresult as $value)
        {
			$username = $this->username;
			$str = "";$i = 0;$j = 0;
			
				$ulist = isset($value['u_list']) ? explode(',', $value['u_list']) : explode(',', $value['g_list']);
				$glist = explode(',', $value['g_list']);
				
				foreach ($ulist as $ul){
						$i++;
						$str = $str =="" ? $ul : $str . ',' . $ul;
						
						if($i >= 3){
                			$j = count($ulist)-4;
                			break;
                		}
				}
                
				foreach ($glist as $gl){
					 if (count($glist) == 2){
						if ($gl != UID){
							$value['avatar'][] = get_avatar($gl); //获得用户头像
						}
					} else{
						$value['avatar'][] = get_avatar($gl); //获得用户头像
					}
				}
             if($j>0)	$str .= "还有其他". $j ."人";
            $value['username'] = $str;
            //获取未读站内信列表
            $unreadlist[] = $value;
        }
      
		$resultarray['more'] = $nextpage ==1 ? false : true ;
		$resultarray['result'] = $unreadlist;
		$resultarray['state'] =  count($unreadlist)>0 ? '1' : '0';
        return $resultarray;
    }

    /**
     * 站内信收件箱列表显示
     * @author gefeichao
     * @param  $page	传入的页数
	 * @param $pages 传入每页数量
	 * @param $searchkey 传入搜索关键字
     */
    function showmlist($page = 0, $pages = 10, $searchkey = NULL)
    {
        $pagesize = $page ==1 ? $pages * ($page - 1) : $pages * ($page-1)+$pages;
        $messlist = array();	
        //调用后台方法，获得数据结果集
        $messresult = $this->messagemodel->message_showmlist($searchkey,$pages,$pagesize);
	
        if (! $messresult)
        {
            $messresult = array();
        }
		$nextpage = array_pop($messresult);
        foreach ($messresult as $value)
        {
             //获取群组成员
			$username = $this->username;
			$str = "";$i = 0;$j = 0;
			
				$ulist = isset($value['u_list']) ? explode(',', $value['u_list']) : explode(',', $value['g_list']);
				$glist = explode(',', $value['g_list']);
				
				foreach ($ulist as $ul){
						$i++;
						
						$str = $str =="" ? $ul : $str . "," . $ul;
						if($i >= 3){
                			$j = count($ulist)-4;
                			break;
                		}
				}
                
				foreach ($glist as $gl){
					 if (count($glist) == 2){
						if ($gl != UID){
							$value['avatar'][] = get_avatar($gl); //获得用户头像
						}
					} else{
						$value['avatar'][] = get_avatar($gl); //获得用户头像
					}
				}
            if($j>0)	$str .= "还有其他". $j ."人";
            $value['username'] = $str;
            //获取收件箱列表
            $messlist[] = $value;
        }

		$resultarray['more'] = $nextpage ==1 ? false : true ;
		$resultarray['result'] = $messlist;
		$resultarray['state'] =  count($messlist)>0 ? '1' : '0';
        return $resultarray;
    }

    /**
     * 获取站内信发送列表
     * @author gefeichao
     * @param $page 传入页数
	 * @param $pages 传入每页数量
	 * @param $searchkey 传入搜索关键字
     * @return array
     */
    function setmessages($page = 0, $pages = 10, $searchkey = NULL)
    {
        $pagesize = $page==1 ? $pages * ($page - 1) : $pages * ($page - 1) + $pages;
        $messlist = array();
        //调用后台方法，获得数据结果集
        $messresult = $this->messagemodel->sentmessage($searchkey,$pages,$pagesize);
        if (! $messresult)
        {
            return false;
        }
		$nextpage = array_pop($messresult);
        foreach ($messresult as $value)
        {
			$username = $this->username;
			$str = "";$i = 0;$j = 0;
			$ulist = isset($value['u_list']) ? explode(',', $value['u_list']) : explode(',', $value['g_list']);
			$glist = explode(',', $value['g_list']);
			foreach ($ulist as $ul){
					$i++;
					$str = $str =="" ? $ul : $str . "," . $ul;
					if($i >= 3){
						$j = count($ulist)-4;
						break;
					}

			}

			foreach ($glist as $gl){
				if (count($glist) == 2){
					if ($gl != UID){
						$value['avatar'][] = get_avatar($gl); //获得用户头像
					}
				} else{
					$value['avatar'][] = get_avatar($gl); //获得用户头像
				}
			}
			if($j>0)    $str .= "还有其他". $j ."人";
			$value['username'] = $str;
			//获取收件箱列表
			$messlist[] = $value;
        }
		
        $resultarray['more'] = $nextpage ==1 ? false : true ;
		$resultarray['result'] = $messlist;
		$resultarray['state'] =  count($messlist)>0 ? '1' : '0';
        return $resultarray;
    }

    /**
     * 站内信分页函数
     * @author gefeichao
     */
    private function msgpage($result, $pages, $pagesize)
    {
        //遍历分页设置
        $arr_rel = "";
        if (count($result) - $pagesize > $pages)
        {
            if ($pagesize == 0)
            {
                if (count($result) <= $pages)
                {
                    for ($i = 0; $i < count($result); $i ++)
                    {
                        $arr_rel[] = $result[$i];
                    }
                    $resultarray['more'] = true; //没有下一页
                }
                else
                {
                    for ($i = 0; $i < $pages; $i ++)
                    {
                        $arr_rel[] = $result[$i];
                    }
                    $resultarray['more'] = false;//有下一页
                }
            }
            else
            {
                for ($i = 0; $i < $pages; $i ++)
                {
                    $arr_rel[] = $result[$pagesize + $i];
                }
                $resultarray['more'] = false;
            }
        }
        else
        {
            if (count($result) < $pagesize)
            {
                $arr_rel = $result;
            }
            else
            {
                for ($i = 0; $i < count($result) - $pagesize; $i ++)
                {
                    $arr_rel[] = $result[$pagesize + $i];
                }
            }
            $resultarray['more'] = true;
        }
        $valarray = $arr_rel;
		$resultarray['more'] = false;
        $resultarray['result'] = $valarray;
        if (count($valarray) > 0 && $result)
        {
            $resultarray['state'] = '1';
        }
        else
        {
            $resultarray['state'] = '0';
        }
        return $resultarray;
    }

    /**
     * 点击站内信显示的最新列表
     * @author gefeichao
     * @return array
     */
    function msg_top()
    {
        $messlist = array();
        //调用后台方法，获得数据结果集
        $messresult = $this->messagemodel->message_list_top();
        if (! $messresult)
        {
            $messresult = array();
        }
        //清空未读信息数
		service('Notice')->setting(UID, 'editmsg');
        foreach ($messresult as $value)
        {
			$username = $this->username;
			$str = "";$i = 0;$j = 0;
				$ulist = isset($value['u_list']) ?  explode(',', $value['u_list']) : explode(',', $value['g_list']);
				$glist = explode(',', $value['g_list']);
				
				foreach ($ulist as $ul){
					
						$i++;
						$str = $str=="" ? $ul : $str .",". $ul;
						if($i == 3){
                			$j = count($ulist)-4;
                			break;
                		}
									
				}
                
				foreach ($glist as $gl){
					 if (count($glist) == 2){
						if ($gl != UID){
							$value['avatar'][] = get_avatar($gl); //获得用户头像
						}
					} else{
						$value['avatar'][] = get_avatar($gl); //获得用户头像
					}
				}
            
             if($j>0)	$str .= "还有其他". $j ."人";
            $value['username'] = $str;
            
            $value['isToUser'] = $value['toUser'] ? '<img src="'.MISC_ROOT.'img/system/forward.gif"/>':"";
            //获取收件箱列表
            $messlist[] = $value;
            
        }
        //显示下拉列表
        $msgstr = $this->msglisttop($messlist);
		$data = array('state' => '1', 'data' => $msgstr);
		echo $this->ajaxReturn($data,'',1,'jsonp');
    }

    /**
     * 站内信下拉列表
     * @author gefeichao
     */
    private function msglisttop($topresult)
    {
        $str = "";
        if ($topresult)
        {
            foreach ($topresult as $value)
            {
                if ($value['state'] == 1)
                {
                    if ($str == "")
                    {
                        $str .= "<li class='firstChild jewelItemNew'>";
                    }
                    else
                    {
                        $str .= "<li class='jewelItemNew'>";
                    }
                }
                else
                {
                    if ($str == "")
                    {
                        $str .= "<li class='firstChild '>";
                    }
                    else
                    {
                        $str .= "<li class=''>";
                    }
                }
                $str .= " <a href='" . mk_url('main/msg/list_msg', array('fromid' => $value['gid'],'lastId'=>$value['id'])) . "' class='itemBlock'>
							<div class='uiImageBlock '>";
                if (count($value['avatar']) > 1)
                {
                    $str .= "<span class='uiSplitPics '>";
                    $str .= "<span class='uiSplitPic leftThree'><img class='uiProfilePhoto uiProfilePhotoLarge img' src='" . $value['avatar'][0] . "' /></span>";
                    $str .= "<span class='uiSplitPic rTop'><img class='uiProfilePhoto uiProfilePhotoSmall img' src='" . $value['avatar'][1] . "' /></span>";
                    $str .= "<span class='uiSplitPic rBottom'><img class='uiProfilePhoto uiProfilePhotoSmall img' src='" . $value['avatar'][2] . "'  /></span>";
                    $str .= "</span>";
                }
                else
                {
                    $str .= "<img class='uiProfilePhoto fl' src='" . $value['avatar'][0] . "' alt='" . $value['username'] . "头像' />";
                }
                $str .= "<div class='uiImageBlockContent'>
									<div class='author'>
										<strong>" . $value['username'] . "</strong>
									</div>
									<div class='snippet'>
										<span>" . $value['isToUser'] .$value['mess'] . "</span>
									</div>
									<div class='time'>
										<abbr class='timestamp'>" . $value['dateline'] . "</abbr>
									</div>
								</div>
							</div>
						</a>
					</li>";
            }
        }
        else
        {
            $str .= "<li class='firstChild not-message-list'><span class='not-message-list'>暂时没有站内信</span></li>";
        }
        return $str;
    }

    /**
     * 搜索站内信
     * @author gefeichao
     */
    function search_msg()
    {
    	$gid = $this->input->post('gid');
        $searchkey = $this->input->post("searchkey");
        $lastid = $this->input->post("hd_lastId");
        $this->assign('lastid', shtmlspecialchars($lastid));
       	$this->assign('searchname',shtmlspecialchars(trim($searchkey)));
        $this->assign('user', $this->user);
        $this->assign('gid', shtmlspecialchars($gid));
        $this->assign('avatar', get_avatar(UID));
        $this->display('message/search.html');
    }

	/*异步加载站内信搜索列表*/
    function search_msg_more(){
    	$gid = $this->input->get('gid');
    	$searchkey = $this->input->get('searchkey');
    	if(!$gid)	return false;
    	$searchkey = str_replace(' ', '', $searchkey);
    	$page = $this->input->post('page') ? $this->input->post('page') : 1;
    	$pagesize = 9;$msginfo=array();
        $limit = ($page-1) * $pagesize;$sresult = array();
    	$sresult = $this->messagemodel->search_msg(shtmlspecialchars($searchkey), shtmlspecialchars($gid),$pagesize, $limit);
    	if(!$sresult) $sresult = array();
		$nextpage = array_pop($sresult);
      	foreach ($sresult as $r)
            {
                $myfile = array();
                $myfiles = explode(',', $r['files']);
                foreach ($myfiles as $j)
                {
                    $file = $this->messagemodel->get_files($j);
                    if ($file)
                    {
                    	$file[0]['id']=$j;
                    	$file[0]['downurl'] = mk_url('main/msg/downloadPhoto', array('id' => $j));
                        $group = $file[0]['group_name'];
                        $filename = $file[0]['orig_name'];
                        $file[0]['url'] = 'http://'.config_item('fastdfs_domain') .'/' . $group . '/' . $filename;
                    }
                    else
                    {
                        $file[0] = '';
                    }
                    $myfile[] = $file[0];
                }
                $r['files'] = $myfile;
                $msginfo[] = $r;
            }
    	$messresult['more'] = $nextpage ==1 ? false : true ;
		$messresult['result'] = $msginfo;
		$messresult['state'] =  count($msginfo)>0 ? '1' : '0';
    	$data = array('status' => $messresult['state'], 'data' => '这里是失败信息', 'messages' => $messresult['result'], 'isend' => $messresult['more']);
		$this->ajaxReturn($data,'',1,'json');
    }

    /**
     * 删除站内信   
     * @access  public
     * @param   $id  记录id
     * return bool
     */
    function del_pms()
    {
		$dataid = $this->input->post('dataid');
		$gid = $this->messagemodel->showmsgdetail($dataid);
		$ids = $this->messagemodel->get_msg_list($gid);
        $result = $this->messagemodel->del_pms($ids, UID);
		$state = $result ? 1 : 0;
        
        $data = array('state' => $state, 'data' => '这里删除信息失败');
		$this->ajaxReturn($data,'',$state,'json');
    }

	function del_pms_item(){
		$id = $this->input->post('dataid');
       
        if (! $id)
        {
            $this->showmessage('编号不能为空！');
            return FALSE;
        }
		
        if($id == '111111'){
        	$gid = $this->input->post('id');
        	$ids = $this->messagemodel->get_msg_list($gid);
        }else{
       		$ids = explode(',', $id);
        }
        $result = $this->messagemodel->del_pms($ids, UID);
		$state = $result ? 1 : 0;
        
        $data = array('state' => $state, 'data' => '这里删除信息失败');
		$this->ajaxReturn($data,'',$state,'json');
	}

    /**
     * 加载站内信首页显示列表
     * @author gefeichao
     * @date 20111110
     */
    public function show_message()
    {
		
        $this->assign('avatar', get_avatar(UID));
        $this->assign('user', $this->user);
        $this->display('message/index.html');
    }

    /**
     * 标记站内信读取状态
     * @access  public
     * @param   $id  记录id
     * return bool
     */
    function edit_message()
    {
        $id = $this->input->post('dataid');
        if (! $id)
        {
            $this->showmessage('编号不能为空！');
            return FALSE;
        }
        $result = $this->messagemodel->setmessage($id);
		$state = $result ? 1 : 0;
		if($result){
			$state = 1;
			$val = $result[0];
		}else{
			$state = 0;
			$val = false;
		}
        $data = array('state' => $state, 'data' => '站内信未读修改失败，禁止非法操作哦 亲！！！', 'readState' => $val);
		$this->ajaxReturn($data,'',$state,'json');
    }

    /**
     * 消息存档处理
     * @author gefeichao
     */
    function save_message()
    {
        $dataid = $this->input->post('dataid');
        if (! $dataid)
        {
            $this->showmessage('编号不能为空！');
            return FALSE;
        }
        $result = $this->messagemodel->setarchive($dataid);
		$state = $result ? 1 : 0;
        $data = array('state' => $state, 'data' => '站内信存档修改失败，禁止非法操作哦 亲！！！');
		$this->ajaxReturn($data,'',$state,'json');
    }

    /**
     * 完成消息附件上传操作
     * @author gefeichao
     * @access public
     */
    function message_upload()
    {
		
		$this->fdfs  =get_storage('default');
        $is_image = 0;
        $ext = strtolower($_FILES['FileData']['name']);
        $temp = trim(substr($ext, strrpos($ext, '.')), '.');
        $array = array('bmp', 'gif', 'jpeg', 'jpg', 'jpe', 'png', 'tiff', 'tif');
        if (in_array($temp, $array))
        {
            $is_image = 1;
        }
        $size = 1024*1024*10;
        if($_FILES['FileData']['size'] > $size){
        	 echo '<script type="text/javascript"> alert("附件大小超过10M，上传失败！"); </script>';
        	 return;
        }
		 $r = $this->fdfs->uploadFile($_FILES['FileData']['tmp_name'], $temp);
        if (is_array($r) && count($r) > 0)
        {
            $attachedGroupName = $r['group_name'];
            $attachedFileOriginalName = $r['filename'];
            $attachedFileName = $_FILES['FileData']['name'];
            $attachedFileSize = $_FILES['FileData']['size'];
            $callback = shtmlspecialchars($_POST['callback']);
            $inputFileId = shtmlspecialchars($_POST['inputFileId']);
            $sqldata = array('file_name' => $_FILES['FileData']['tmp_name'], 'file_type' => $_FILES['FileData']['type'], 'group_name' => $attachedGroupName, 
            'orig_name' => $r['filename'], 'client_name' => $attachedFileName, 'file_ext' => $temp, 'file_size' => $attachedFileSize, 'is_image' => $is_image);
            $res = $this->messagemodel->addfile($sqldata);
            $arr = array('id' => $res, 'filename' => $attachedFileName, 'fileOriginalName' => $attachedFileName, 'fileSize' => $attachedFileSize);
            echo $this->uploaderResult($callback, $inputFileId, $arr);
        }
        else
        {
            echo '<script type="text/javascript"> alert("上传失败"); </script>';
        }
    }

    /**
     * uploaderResult(param1, param2)
     * param1:客户端上传上来的回调函数
     * param2:input file Id
     * param3:保存文件后服务器输出的结果
     */
    function uploaderResult($_callback, $_inputFileId, $_arr)
    {
        $result = '<script type="text/javascript">';
        $result .= ';window.parent[\'' . $_callback . '\'].call(window,';
        $result .= '\'' . json_encode($_arr);
        $result .= '\'' . ');';
        $result .= 'window.parent.document.getElementById("uploader-loading").style.display = "none";';
        $result .= 'window.parent.document.getElementById("' . $_inputFileId . '").style.display = "block";';
        $result .= '</script>';
        return $result;
    }

    /**
     * 执行ajax 显示 操作函数
     * @author gefeichao
     * @date 20110927
     * return json
     * Enter description here ...
     */
    function show_ajaxdata()
    {
        /****获取相应消息数据****/
        $messageCateGory = $this->input->get('messagesCateGory'); /*消息筛选类型*/
        $searchKey = $this->input->get('MessaginSearchQuery'); /*消息搜索关键字*/
        $page = $this->input->post('page'); /*消息筛选类型请求的分页数*/
        $pages = $page ? $page : 1;
        $data = '';
        $searchKey = shtmlspecialchars($searchKey);
        if($pages == 1){
        	$pagesize = 20;
        }else{
        	$pagesize = 10;	
        }
        if ($messageCateGory == '0'){ //请求未读的消息
            $messresult = $this->showunreadlist($pages, $pagesize, $searchKey);
            $data = array('status' => $messresult['state'], 'data' => '这里是失败信息', 'messages' => $messresult['result'], 'isend' => $messresult['more']);
        }else if ($messageCateGory == '2'){ //请求存档的消息

            $messresult = $this->showarchivelist($pages, $pagesize, $searchKey);
            $data = array('status' => $messresult['state'], 'data' => '这里是失败信息', 'messages' => $messresult['result'], 'isend' => $messresult['more']);
        } else if ($messageCateGory == '6'){ //请求已发送的消息
            $messresult = $this->setmessages($pages, $pagesize, $searchKey);
            $data = array('status' => $messresult['state'], 'data' => '这里是失败信息', 'messages' => $messresult['result'], 'isend' => $messresult['more']);
        }else if ($messageCateGory == ''){ //请求全部消息
            $messresult = $this->showmlist($pages, $pagesize, $searchKey);
            $data = array('status' => $messresult['state'], 'data' => '这里是失败信息', 'messages' => $messresult['result'], 'isend' => $messresult['more']);
        }
        //echo json_encode($data);
		$this->ajaxReturn($data,'',1,'json');
    }

    /**
     * 获取未读信息条数
     * @author gefeichao
     * @date 2012-02-23
     * return json
     */
    function show_unreadinfo()
    {
		$infos = $this->messagemodel->show_unread(UID);
		if(!$infos){
			 $infos[0]['invite'] = 0;
			 $infos[0]['un_msg'] = 0;
			 $infos[0]['un_notice'] = 0;
             $infos[0]['un_invite'] = 0;    //add by shedequan to fix bug.
		}
        //返回json数据
        $this->ajaxReturn( array('requests' => $infos[0]['un_invite'], 'messages' => $infos[0]['un_msg'], 'notice' => $infos[0]['un_notice']),'',1,'jsonp');
    }
    
 	/**
     * 下载照片附件
     *
     * @author gefeichao
     * @date   2012-05-08
     * @access public
     */
    public function downloadPhoto(){
		$this->fdfs  = get_storage('default');
        $pid = $this->input->get('id');
        $photo_info = $this->messagemodel->get_files($pid);
        if(!$photo_info){
            echo "<script>alert('error!');</script>";
        }
        //取得照片原图
		$photores = $this->fdfs->downloadFileBuff($photo_info[0]['orig_name']); 
        if ($photores) {
            header('Content-Description: File Transfer');
            header('Content-Type: application/force-download');
            $filename = $photo_info[0]['client_name'];
            header('Content-Disposition: attachment; filename="'.$filename.'"');
            header('Content-Transfer-Encoding: binary');
            header('Expires: 0');
            header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
            header('Pragma: public');
            header('Content-Length:'.$photo_info[0]['file_size']);
            //ob_clean();
           // flush();
            echo $photores;
            exit;				
        }else{
            echo "<script>alert('文件不存在!');</script>";
        }

    }
}
?>	