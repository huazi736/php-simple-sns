<?php
/**
* [ Duankou Inc ]
* Created on 2012-7-7
* @author yaohaiqi
* The filename : info.php   10:03:45
*/
class Info extends MY_Controller
{
	
	const PERMISION_CUSTOM = -1;
	const PERMISION_PUBLIC = 1;
	const TOPIC_FROM_INFO = 1;
	
	private $allowTypes = array('info', 'album', 'video','ask');
	
	/**
	 * construct
	 */
	public function __construct()
	{
		parent::__construct();
		$this->load->helper('common');
	}
	
	/**
	 * @author yaohaiqi
	 * @desc 时间线或者信息流发布数据操作方法
	 * @param 前端需要提交的参数列表：
	 * @param content 用户填写的内容
	 * @param type 当前数据的格式:info/album/video
	 * @param timestr 选择的发布时间:格式：2012-03-02 08:15:54
	 * @param permission 当前数据实体设置的权限(int)
	 */
	public function doPost()
	{
		$data = array('uid'=>$this->uid, 'uname'=>$this->username, 'dkcode'=>$this->dkcode, 'title'=>date('Y-m-d H:i:s'), 'from'=>self::TOPIC_FROM_INFO, 'dateline'=>time());
		//数据类型:info/album/video/ask
        $ask = $this->input->post('ask');
		$data['type'] = P('type');
        $data['gid'] = P('gid');
		if( !in_array($data['type'], $this->allowTypes) )
		{
			return $this->dump( L('unknow_style_content'));
		}
        if($data['type'] == 'video'){
            $data['purl'] = P('purl');
            $data['vurl'] = P('vurl');
        }
        if($data['type'] == 'ask'){
			if(!is_array($ask)){
				$ask = array($ask);
			}
            list($flag, $poll) = service("Ask")->addAsk($ask, $this->uid, 4, $data['gid']);
            if($flag){
                $data['ask_id'] = $poll;
            }
        }
		//内容处理
		$data['content'] = preg_replace('/\s+/', ' ',P('content'));
		if( $data['content'] == '' && $data['type'] == 'info' ) // || filter($data['content'], 2)
		{
			return $this->dump( L('message_error') );
		}
		$data['content'] = autoLink( msubstr($data['content'], 0, 140, 'utf-8', false) );
		$data['ctime'] = preg_replace_callback('/(?P<year>\d{4})(-?)(?P<mon>\d{0,2})(-?)(?P<day>\d{0,2})/', function ($match)
		{
			!$match['mon'] && $match['mon'] = 1;
			!$match['day'] && $match['day'] = 1;
			return mktime(date('H'),date('i'),date('s'),$match['mon'],$match['day'],$match['year']);
		}, P('timestr')?:date('Y-m-d') );
		if($data['type'] != 'ask'){
            $parseMethod = '_parse'.ucfirst($data['type']).'Data';
            $data = array_merge($data, $this->$parseMethod());
		}
        $result = service("Group")->addGroupInfo($data);
		if( $result === false )
		{
			return $this->dump(L('operation_fail'));
		}
                $temp = json_decode($result);
                $temp['0']->avatar = get_avatar($temp['0']->uid);
                $temp['0']->dateline = round($temp['0']->dateline);
                $temp['0']->home_url = mk_url('main/index/profile',array('dkcode'=>$temp['0']->dkcode));
                if($data['type'] == 'ask'){
                    $temp['0']->ask = service("Ask")->getAskData($temp['0']->ask_id, $temp['0']->uid, 1);
                }
                return $this->dump(L('operation_success'), true, array('data'=>$temp));
	}
        
        public function infoLine(){
            $num = 10;
            $last = true;
            $data = array();
            $gid = intval($this->input->post('gid'));
            $page = intval($this->input->post('pager'));
            $temp = json_decode(service("Group")->getPageInfo($gid, $num, $page),true);
            if($temp){
                foreach ($temp['data'] as $k => $v) {
                    if($v['type'] == 'ask'){
                        $v['ask'] = service("Ask")->getAskData($v['ask_id'], $v['uid'], 1);
                    }
                    $v['dateline'] = round($v['dateline']);
                    $v['avatar'] = get_avatar($v['uid']);
                    $v['home_url'] = mk_url('main/index/profile',array('dkcode'=>$v['dkcode']));
                    $data[] = $v;
                }
                $last = $temp['count'] > $page * $num ? false : true;
            }
            die(json_encode(array('state' => '1' ,'msg' => "success!",'last' =>$last,'data' =>$data)));
        }
        
        public function center(){
            $num = 10;
            $data = array();
            $gid = intval($this->input->get_post('gid'));
            $page = intval($this->input->get_post('pager'));
            $temp = json_decode(service("Group")->getPageInfo($gid, $num, $page),true);
            if($temp){
                foreach ($temp['data'] as $k => $v) {
                    $v['dateline'] = round($v['dateline']);
                    $v['avatar'] = get_avatar($v['uid']);
                    $v['home_url'] = mk_url('main/index/profile',array('dkcode'=>$v['dkcode']));
                    $data[] = $v;
                }
            }
            $last = $temp['count'] > $page * $num ? false : true;
            $this->config->load("video");
            $this->load->model('groupmodel', 'group');
            $group = $this->group->getGroup( $gid, true );
            $data = array(
            	'group' => $group,
                'last' => $last,
                'data' => $data,
                'login_uid'=>$this->uid,
                'login_name'=>$this->user['username'],
                'login_avatar'=>get_avatar($this->user['uid']),
                'login_url'=> mk_url('main/index/profile',array('dkcode'=>$this->dkcode)),
                'videoname'=>date('YmdHis') . '_' . $this->uid,
                'video_upload_url'=>config_item('video_upload_url'),
                'authcode_url'=>base64_encode(authcode('video', '', config_item('authcode_key'))),
                'recordurl'=>config_item('recordurl'),
            );
            $var = $this->view('center', $data, true);
            $this->showMessage('success', ErrorCode::CODE_SUCCESS, $var, '', 0, 'jsonp');
        }
        
        /**
	 * @author yaohaiqi
	 * @desc 解析信息流数据
	 */
	private function _parseInfoData()
	{
		return array();
	}
    
    /**
	 * @author yaohaiqi
	 * @desc 对输出进行控制
	 * @param array/string $info
	 * @param bool $status
	 * @param array $extra
	 */
	private function dump($info = '', $status = false, $extra = array())
	{
		if( is_string( $info ) )
		{
			$data = array('data'=>array(), 'status'=>(int)$status, 'info'=>$info);
		}
		elseif( is_array( $info ) )
		{
			$data = $info;
		}
		if( !empty($extra) )
		{
			$data = array_merge($data, $extra);
		}
		exit( json_encode( $data ) );
	}
    
    /**
	 * @author yaohaiqi
	 * @desc 解析照片的数据
	 * @param 相册数据类型额外的参数列表：
	 * @param fid 相册的ID
	 * @param pid 相片的PID
	 * @param picurl 大图地址
	 */
	private function _parseAlbumData()
	{
		$album['fid'] = P('fid');//以时间戳为相册ID
		$album['photonum'] = 1;
		$album['picurl'] = P('picurl');//相片的JSON数据
		$album['url'] = '';//相册地址不需要，滞空
		$album['note'] = P('note');//真实的相册ID
		return $album;
	}
	
	/**
	 * @author yaohaiqi
	 * @desc 解析视频的数据
	 * @param 视频类型数据额外参数列表
	 * @param vid 视频ID
	 * @param videourl 视频资源地址
	 * @param imgurl 视频截图地址
	 * @param url 视频在视频模块中的链接地址
	 */
	private function _parseVideoData()
	{
		$video['fid'] = P('vid');
		$video['videourl'] = P('videourl');
		$video['imgurl'] = P('imgurl');
		$video['url'] = P('url');
		$video['width'] = P('width');
		$video['height'] = P('height');
		return $video;
	}
}
?>