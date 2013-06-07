<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');
class UserWiki extends MY_Controller {
	public $is_friend = false;
	public $is_fans = false;
	  
     function __construct() {
        parent::__construct();
    	 if (!$this->action_uid ) {
            $this->action_uid = $this->uid;
            $this->action_user = $this->user;
            $this->action_dkcode = $this->dkcode;
            $this->_self = true;
        }elseif ($this->action_uid == $this->uid) {
            $this->_self = true;
        }
        
        define('VISTER_UID_ziliao', $this->action_uid);
        $this -> load -> model('userwikimodel', 'userwiki');
    }

    /**
     * 获取被访问者的uid
     * @deprecated
     * @author liyundong
     * @date   2012/3/22
     * @param  $dkcode  int  访问者端口号
     * @access 
     * @return array / false
     */
    function getVisterUid($dkcode) {
        return $this -> userwiki -> getVUid($dkcode);
    }

    /** 本方法为用户资料入口方法
     * Created on 2012-2-29
     * @author chenxujia
     */
     function index() {
        $dkcode = $this -> action_dkcode;
        $this->assign('link_url', mk_url('main/index/index', array('action_dkcode' => $dkcode)));
        if (($this->uid == $this->action_uid)) {
            $this->selfView();
        } else {
            include_once (FCPATH . APPPATH . 'helpers/dkpair.php');

			$this->load->model('singleaccessmodel', '_access', true);
			$this->is_friend = $this->_access->isFriend($this->uid, $this->action_uid);
			$this->is_fans = $this->_access->isFans($this->action_uid, $this->uid);

            $this->socialsView();
        }
    }

    /** 本方法为获取用户资料并显示
     * Created on 2012-2-29
     * @author chenxujia
     */
     function selfView() {
        $datas = $this -> getDataInfo();
        $datas['isSelf'] = true;
        $this -> render('edit/edit.html', $datas);
    }

     function render($url, $datas) {
        $datas['image'] = get_avatar($this->action_uid,'ss');
        $this -> assign('datas', $datas);
        $this -> display($url);
    }

    /**
     * Created on 2011-12-14
     * @author  zhuzaiming@yeyaomai.net
     * @desc    社交页面资料 点击自己的或点击关注用户的资料（关注资料仅显示基本资料和兴趣爱好）
     */
     function socialsView() {
        $datas = $this -> getDataInfo();
        $datas['isSocials'] = 1;
        $this -> render('edit/edit.html', $datas);
    }

    /**
     * Created on 2012-02-02
     * @author  zhuzaiming
     * @desc    获取显示需要的数据
     */
    private function getSocialsDataInfo() {
        $datas = array();
        //基本资料
        $isAllowBase = $this -> isAllow('base');
        $isAllowPrivate = $this -> isAllow('private');
        $isAllowContact = 1;
        //$this -> isAllow('contact');

        if ($isAllowBase || $isAllowPrivate || $isAllowContact) {
            //因为所有信息存在同一张表中所以一次读取
            $datas = $this -> getUserDataInfo($datas);
        }

        if ($isAllowBase) {
            $datas['permission']['base'] = 1;
        } else {
            $datas['permission']['base'] = 0;
        }
        if ($isAllowPrivate) {
            $datas['permission']['private'] = 1;
        } else {
            $datas['permission']['private'] = 0;
        }
        if ($isAllowContact) {
            $datas['permission']['contact'] = 1;
        } else {
            $datas['permission']['contact'] = 0;
        }
        //兴趣爱好 生活习惯中的技工职能

        $isAllowInterest = $this -> isAllow('interest');
        $isAllowLife = $this -> isAllow('life');

        if ($isAllowInterest || $isAllowLife) {
            $datas = $this -> getInterestDataInfo($datas);
        }
        if ($isAllowInterest) {
            $datas['permission']['interest'] = 1;
        } else {
            $datas['permission']['interest'] = 0;
        }
        if ($isAllowLife) {
            $datas['permission']['life'] = 1;
        } else {
            $datas['permission']['life'] = 0;
        }
        return $datas;
    }

    /**
     * Created on 2011-12-16
     * @author  zhuzaiming@yeyaomai.net
     * @desc    读取用户信息 $keys 表示需要读取的信息键
     */
    private function getDataInfo() {
        $datas = $this -> getSocialsDataInfo();
        if ($this -> isAllow('edu')) {
            $datas = $this -> getEducationDataInfo($datas);
            $datas['permission']['edu'] = 1;
        } else {
            $datas['permission']['edu'] = 0;
        }
        if ($this -> isAllow('job')) {
            $datas['work'] = $this -> getJobDataInfo(VISTER_UID_ziliao);
            $datas['permission']['job'] = 1;
        } else {
            $datas['permission']['job'] = 0;
        }
        if ($this -> isAllow('book')) {
            $datas = $this -> getBookDataInfo($datas);
            $datas['permission']['book'] = 1;
        } else {
            $datas['permission']['book'] = 0;
        }
        if ($this -> isAllow('skill')) {
            $datas['skill'] = $this -> getSkillDataInfo();
            $datas['permission']['skill'] = 1;
        } else {
            $datas['permission']['skill'] = 0;
        }
        if ($this -> isAllow('teach')) {
            $datas = $this -> getTeachDataInfo($datas);
            $datas['permission']['teach'] = 1;
        } else {
            $datas['permission']['teach'] = 0;
        }
        if ($this -> isAllow('project')) {
            $datas = $this -> getProjectDataInfo($datas);
            $datas['permission']['project'] = 1;
        } else {
            $datas['permission']['project'] = 0;
        }
        if ($this -> isAllow('language')) {
            $datas = $this -> getLanguageDataInfo($datas);
            $datas['permission']['language'] = 1;
        } else {
            $datas['permission']['language'] = 0;
        }
        if ($this -> isAllow('school')) {
            $datas['atschool'] = $this -> getSchoolDataInfo(VISTER_UID_ziliao);
            $datas['permission']['school'] = 1;
        } else {
            $datas['permission']['school'] = 0;
        }
        if ($this -> isAllow('life')) {
            $datas = $this -> getLifeDataInfo($datas);
            $datas['permission']['life'] = 1;
        } else {
            $datas['permission']['life'] = 0;
        }
        $datas = $this -> getPermissionDataInfo($datas);
        return $datas;
    }

    /**
     * Created on 2011-12-16
     * @author  zhuzaiming@yeyaomai.net
     * @desc    获取模块对应的权限信息
     */
     function getPermissionDataInfo(array $datas) {
        $datas['permission_value']['base'] = $this -> getPermission('base');
        if (!$datas['permission_value']['base']['object_content']) {
            //$datas['permission_value']['base']['object_type'] = 1;
            //这个到时根据权限来动态获取，为了方便测试暂时固定
            $datas['permission_value']['base']['object_content'] = array();
        }
        $datas['permission_value']['private'] = $this -> getPermission('private');
        if (!$datas['permission_value']['private']['object_content']) {
            //$datas['permission_value']['private']['object_type'] = 1;
            //这个到时根据权限来动态获取，为了方便测试暂时固定
            $datas['permission_value']['private']['object_content'] = array();
        }
        $datas['permission_value']['contact'] = $this -> getPermission('contact');
        if (!$datas['permission_value']['contact']['object_content']) {
            //$datas['permission_value']['contact']['object_type'] = 1;
            //这个到时根据权限来动态获取，为了方便测试暂时固定
            $datas['permission_value']['contact']['object_content'] = array();
        }
        $datas['permission_value']['edu'] = $this -> getPermission('edu');
        if (!$datas['permission_value']['edu']['object_content']) {
            //$datas['permission_value']['edu']['object_type'] = 1;
            //这个到时根据权限来动态获取，为了方便测试暂时固定
            $datas['permission_value']['edu']['object_content'] = array();
        }
        $datas['permission_value']['job'] = $this -> getPermission('job');
        if (!$datas['permission_value']['job']['object_content']) {
            //$datas['permission_value']['job']['object_type'] = 1;
            //这个到时根据权限来动态获取，为了方便测试暂时固定
            $datas['permission_value']['job']['object_content'] = array();
        }
        $datas['permission_value']['school'] = $this -> getPermission('school');
        if (!$datas['permission_value']['school']['object_content']) {
            //$datas['permission_value']['school']['object_type'] = 1;
            //这个到时根据权限来动态获取，为了方便测试暂时固定
            $datas['permission_value']['school']['object_content'] = array();
        }
        $datas['permission_value']['teach'] = $this -> getPermission('teach');
        if (!$datas['permission_value']['teach']['object_content']) {
            //$datas['permission_value']['teach']['object_type'] = 1;
            //这个到时根据权限来动态获取，为了方便测试暂时固定
            $datas['permission_value']['teach']['object_content'] = array();
        }
        $datas['permission_value']['language'] = $this -> getPermission('language');
        if (!$datas['permission_value']['language']['object_content']) {
            //$datas['permission_value']['language']['object_type'] = 1;
            //这个到时根据权限来动态获取，为了方便测试暂时固定
            $datas['permission_value']['language']['object_content'] = array();
        }
        $datas['permission_value']['skill'] = $this -> getPermission('skill');
        if (!$datas['permission_value']['skill']['object_content']) {
            //$datas['permission_value']['skill']['object_type'] = 1;
            //这个到时根据权限来动态获取，为了方便测试暂时固定
            $datas['permission_value']['skill']['object_content'] = array();
        }
        $datas['permission_value']['book'] = $this -> getPermission('book');
        if (!$datas['permission_value']['book']['object_content']) {
            //$datas['permission_value']['book']['object_type'] = 1;
            //这个到时根据权限来动态获取，为了方便测试暂时固定
            $datas['permission_value']['book']['object_content'] = array();
        }
        $datas['permission_value']['life'] = $this -> getPermission('life');
        if (!$datas['permission_value']['life']['object_content']) {
            //$datas['permission_value']['life']['object_type'] = 1;
            //这个到时根据权限来动态获取，为了方便测试暂时固定
            $datas['permission_value']['life']['object_content'] = array();
        }
        $datas['permission_value']['interest'] = $this -> getPermission('interest');
        if (!$datas['permission_value']['interest']['object_content']) {
            //$datas['permission_value']['interest']['object_type'] = 1;
            //这个到时根据权限来动态获取，为了方便测试暂时固定
            $datas['permission_value']['interest']['object_content'] = array();
        }
        $datas['permission_value']['project'] = $this -> getPermission('project');
        if (!$datas['permission_value']['project']['object_content']) {
            //$datas['permission_value']['project']['object_type'] = 1;
            //这个到时根据权限来动态获取，为了方便测试暂时固定
            $datas['permission_value']['project']['object_content'] = array();
        }
        return $datas;
    }

    /**
     * Created on 2011-12-16
     * @author  zhuzaiming@yeyaomai.net
     * @desc    获取工作情况数据
     */
     function getJobDataInfo($uid) {
        $job = array();
        $job = $this -> userwiki -> getDataByUserId($uid, USER_JOBEXPER);
        if (empty($job))
            return $job;
        foreach ($job as $k => $v) {
			$v['workmate'] = str_replace(array('&amp;', '&quot;', '&lt;', '&gt;'), array('&', '"', '<', '>'), $v['workmate']);
            $v['workmate'] = str_replace(",", " ", json_decode($v['workmate'], true));
            $job[$k]["workmate"] = $v['workmate'];
        }
        
     
        return $job;
    }

    /**
     * Created on 2011-12-16
     * @author  zhuzaiming@yeyaomai.net
     * @desc    获取获得证书数据
     */
     function getBookDataInfo(array $datas) {
        $datas['book'] = $this -> get_books(VISTER_UID_ziliao);
        return $datas;
        
    }

    /**
     * Created on 2011-12-16
     * @author  zhuzaiming@yeyaomai.net
     * @desc    获取生活习惯数据
     */
     function getLifeDataInfo(array $datas) {
        $datas['life'] = $this -> get_lift(VISTER_UID_ziliao);
        return $datas;
    }

    /**
     * Created on 2011-12-16
     * @author  zhuzaiming@yeyaomai.net
     * @desc    获取用户表数据
     */
     function getUserDataInfo(array $datas) {
        $this -> load -> model('myeditmodel', 'myedit');
        $data['user'] = $this -> myedit -> getUserByUid(VISTER_UID_ziliao);

        include_once (FCPATH . APPPATH . 'helpers/dkpair.php');
        $datas['user']['uid'] = VISTER_UID_ziliao;
        $datas['user']['usr_name'] = $data['user']['username'];
        $datas['user']['usr_sex'] = returnInfomation('info', 'sex', $data['user']['sex']);
        $datas['user']['sex_val'] = $data['user']['sex'];
        $datas['user']['usr_birthday'] = $data['user']['birthday'];
        $datas['user']['usr_now_nation'] = $data['user']['now_addr'];
        $datas['user']['usr_home_nation'] = $data['user']['home_addr'];
        $datas['user']['usr_ismarry'] = returnInfomation('info', 'marry', $data['user']['ismarry']);
        $datas['user']['ismarry_val'] = $data['user']['ismarry'];
        $datas['user']['usr_haschildren'] = returnInfomation('info', 'haschildren', $data['user']['haskid']);
        $datas['user']['haschildren_val'] = $data['user']['haskid'];
        return $datas;
    }

    /**
     * Created on 2011-12-16
     * @author  zhuzaiming@yeyaomai.net
     * @desc    获取专业技能数据
     */
     function getSkillDataInfo() {
        $datas = $this -> userwiki -> getDataByUserId(VISTER_UID_ziliao, RESUME_SKILL);
        return $datas;
    }

    /**
     * Created on 2011-12-16
     * @author  zhuzaiming@yeyaomai.net
     * @desc    获取培训经历数据
     */
     function getTeachDataInfo(array $datas) {
        $datas['teach'] = $this -> get_train(VISTER_UID_ziliao);
        return $datas;
    }

    /**
     * Created on 2011-12-16
     * @author  zhuzaiming@yeyaomai.net
     * @desc    获取在校情况数据
     */
     function getSchoolDataInfo($uid) {
        $datas = $this -> userwiki -> getDataByUserId($uid, RESUME_SCHOOL);
        return $datas;
    }


    /**
     * Created on 2011-12-16
     * @author  zhuzaiming@yeyaomai.net
     * @desc    获取教育情况数据
     */
     function getEducationDataInfo(array $datas) {
        $datas['edu'] = $this -> get_school(VISTER_UID_ziliao);
      //  print_r($datas['edu']);
      //  exit;
        return $datas;
    }

    /**
     * Created on 2011-12-16
     * @author  zhuzaiming@yeyaomai.net
     * @desc    获取项目情况数据
     */
     function getProjectDataInfo(array $datas) {
        $datas['project'] = $this -> get_project(VISTER_UID_ziliao);

        return $datas;
    }

    /**
     * Created on 2011-12-16
     * @author  zhuzaiming@yeyaomai.net
     * @desc    获取语言情况数据
     */
     function getLanguageDataInfo(array $datas) {
        $datas['language'] = $this -> get_lang(VISTER_UID_ziliao);
        return $datas;
    }

    /**
     * Created on 2011-12-16
     * @author  zhuzaiming@yeyaomai.net
     * @desc    获取兴趣爱好情况数据
     */
     function getInterestDataInfo(array $datas) {
        $datas['interst'] = $this -> get_Intrest(VISTER_UID_ziliao);
        return $datas;
    }

    /**
     * Created on 2011-12-14
     * @author  zhuzaiming@yeyaomai.net
     * @desc    朋友页面资料
     */
    function friendView() {
        //显示好友信息
        include_once (FCPATH . APPPATH . 'helpers/dkpair.php');
        $this -> render('userwiki/index.html', $this -> getDataInfo());
    }

 	/**
	 * Created on 2012-3-29
	 * @author  chenxujia
	 * @desc    获取模板内容
	 */
	function getBlockTpl(){
		if (!P('blockName')) {
			json_encodes(0, L('system_exception_notice_admin'));
		}
		//获取到所有最小的时间，然后比较再取出最新的时间返回给前端
		$blockName = P('blockName');
		if($blockName == 'base'){//如果是基本资料需要查询各个模块的时间
			$times['edu'] = $this->userwiki->get_edutime($this->uid);
			$times['job'] = $this->userwiki->get_jobtime($this->uid);
			$times['school'] = $this->userwiki->get_schooltime($this->uid);
			$times['teach'] = $this->userwiki->get_teach($this->uid);
			$times['project'] = $this->userwiki->get_project($this->uid);
			$newarray = array();
			foreach($times as $key => $val){
				if(!empty($val)){
					$newarray[] = $val;
				}
			}
			sort($newarray);
			if(empty($newarray) || !is_array($newarray)){
				$time = '1912-01-01';
			}else{
				$time = date('Y-m-d',$newarray[0]);
			}
			$nowtime = time();
			$month = 2592000;//一个月的时间按照30天算
			$lastupdate = $this->userwiki->getLastUpdateBirth($this->uid);//上次修改生日的时间
			$ctime = $nowtime - $lastupdate;
			if($ctime < $month){//没有超过一个月不可以修改
                 $able = 0;
			}else{
				 $able = 1;
			}
		}
        
		if (in_array($blockName, array('university', 'primarySchool', 'highSchool'))) {
			$_POST['blockName'] = $blockName = 'education';
		}
		$file = APP_ROOT . 'views/edit/tpl/' . P('blockName') . '.html';
		if (is_readable($file)) {
			if ((P('blockName') == 'interest')) {
				$interest = $this -> getSysInterset();
				ob_start();
				include $file;
				$contents = ob_get_contents();
				ob_end_clean();
			}else {
				$contents = file_get_contents($file);
			}
			if($blockName != 'base'){
				$content = str_replace('<!--MISC_ROOT-->', MISC_ROOT, $contents);
			}else{
				$input = array('<!--TIME-->','<!--ABLE-->','<!--MISC_ROOT-->');
				$replace = array('<div id="time" class="hide">'.$time.'</div>','<div id="able" class="hide">'.$able.'</div>',MISC_ROOT);
				$content = str_replace($input,$replace,$contents);
			}
			json_encodes(1, '', $content);
		}
		else {
			json_encodes(0, L('system_exception_notice_admin'));
		}
	}
        
       
    /*
     * Created on 2011-12-14
     * @author  zhuzaiming@yeyaomai.net
     * @desc    设置权限
     */
     function setPermission() {
        // permission 0=>custom, 1 => 公开,2 => 自己,3 => 好友
		$type = P('type');
		$permission = P('permission');
        if (empty($type) || empty($permission)) {
            $return['state'] = 0;
            toJSON($return);
        }

		$object_id = P('object_id');
        $this->load->model('singleaccessmodel', '_access', true);
        if ($this -> _access -> set($type, $object_id, P('permission'))) {
			$this->updateTimeLinePermission($type, P('permission'));
            $return['state'] = 1;
            toJSON($return);
        } else {
            $return['state'] = 0;
            toJSON($return);
        }
    }

	/*
     * Created on 2012/5/10
     * @author  bohailiang
     * @desc    更新时间线上的权限
     */
	function updateTimeLinePermission($type, $permission){
		$fid_arr = array();
		if('edu' == $type){
			//教育情况
			$edu_info = $this->get_school($this->action_uid);
			if($edu_info){
				foreach($edu_info as $key => $value){
					foreach($value as $k => $v){
						$fid_arr[] = $v['id'] . '_edu';
					}
				}
			}
		} else if('job' == $type){
			//工作情况
			$job_info = $this->getJobDataInfo($this->action_uid);
			if($job_info){
				foreach($job_info as $key => $value){
					if(!empty($value['starttime'])){
						$fid_arr[] = $value['id'] . '_job_s';
					}
					if(!empty($value['endtime'])){
						$fid_arr[] = $value['id'] . '_job_e';
					}
				}
			}
		}

		if(!empty($fid_arr)){
			$permission_arr = array(1, 3, 4, 8);
			$access_type = 1;
			$access_content = array();
			if (is_numeric($permission) && in_array($permission, $permission_arr)) {
				$access_type = $permission;
			} else {
				$access_type = -1;
				//自定义
				if ($permission == '0') {
					$permission = '';
				}
				$access_content = explode(',', $permission);
			}
			
			$soap_arr = array();
			foreach($fid_arr as $key => $value){
				$soap_arr[] = array('fid' => $value, 'type' => 'uinfo', 'permission' => $access_type);
			}

			$result = call_soap('timeline', 'Timeline', 'updateTopic', array($soap_arr, $access_content, true));

			return true;
		}
		return false;
	}

    /*
     * Created on 2011-12-14
     * @author  zhuzaiming@yeyaomai.net
     * @desc    获取权限
     */
     function getPermission($type) {
        if (!$type) {
            return false;
        }
        $this -> load -> model('singleaccessmodel', '_access', true);
        return $this -> _access -> getAccess($type, VISTER_UID_ziliao);
    }

    /*
     * Created on 2011-12-14
     * @author  zhuzaiming@yeyaomai.net
     * @desc    获取是否允许执行操作
     */
     function isAllow($type) {
        if (!($type)) {
            return false;
        }
        $this -> load -> model('singleaccessmodel', '_access', true);
        return $this -> _access -> isAllow($type, VISTER_UID_ziliao, $this -> uid, $this -> action_uid, $this->is_friend, $this->is_fans);
    }

    /*
     * Created on 2012-3-14
     * @author  liyudong
     * @desc    查询学校对应的院系
     */
     function get_school($uid) {
        if (!$uid) {
            return false;
        }
        $arr = $this -> userwiki -> get_schoolData($uid);
        //学校归类
        $school_array = array(1 => 'primaryschool', 2 => 'primaryschool', 3 => 'highschool', 4 => 'highschool', 5 => 'university', 6 => 'university', 7 => 'university', 8 => 'university', 9 => 'university');
        $result = array();
        if ($arr) {
            foreach ($arr as $key => $value) {
				$value['classmate'] = str_replace(array('&amp;', '&quot;', '&lt;', '&gt;'), array('&', '"', '<', '>'), $value['classmate']);
                $value['classmate'] = str_replace(',', ' ', json_decode($value['classmate'], true));
                $result[$school_array[$value['edulevel']]][] = $value;
            }
        }
        ksort($result);
        return $result;
    }

    /**
     * 获取用户兴趣
     *
     * @author liyundong
     * @date   2012/3/22
     * @param  $uid  int  用户id
     * @access 
     * @return array / false
     */
     function get_Intrest($uid) {
         $datas = array();
            $interst = $this -> userwiki -> getInterest($uid);
            if (empty($interst)) return $datas;
            //兴趣分类
            foreach ($interst as $k => $v) {
                $v['title'] = str_replace(",", '、', json_decode($v['title']));
                switch ($v['type']) {
                    case '1' :
                        $datas['life_skill'] = $v['title'];  break;
                    case '2' :
                        $datas['sports'] = $v['title'];  break;
                    case '3' :
                        $datas['foods'] = $v['title'];  break;
                    case '4' :
                        $datas['books'] = $v['title'];   break;
                    case '5' :
                        $datas['movies'] = $v['title']; break;
                    case '6' :
                        $datas['programs'] = $v['title'];break;
                    case '7' :
                        $datas['entertainment'] = $v['title'];break;
                    case '8' :
                        $datas['hobby'] = $v['title']; break;
                    case '9' :
                        $datas['travel'] = $v['title']; break;
                    default : break;
                }
            }
        return $datas;
    }

    //josn 大学数据
     function getJosnCollega() {
        $adr = "../../";
    }

    /**
     * 获取用户兴趣
     *
     * @author liyundong
     * @date   2012/3/22
     * @param  $uid  int  用户id
     * @access 
     * @return array / false
     */
    function getSysInterset() {
        for ($i = 1; $i < 10; $i++) {
            $arr[$i] = $this -> userwiki -> getSysInterst($type = $i);
        }
        return $arr;
    }

    /**
     * 获取用户生活信息
     *
     * @author liyundong
     * @date   2012/3/22
     * @param  $uid  int  用户id
     * @access 
     * @return array / false
     */
     function get_lift($uid) {
        $arr = $this -> userwiki -> getInterestForLife($uid);
        if (!empty($arr)) {
            $arr = $arr[0];
        }
        return $arr;
    }

    /**
     * 获取用户项目经历
     * @author bohailiang
     * @date   2012/3/22
     * @param  $uid  int  用户id
     * @access 
     * @return array / false
     */
     function get_project($uid = 0) {
        if (empty($uid)) {
            return false;
        }
        $arr = $this -> userwiki -> getProjectForUser($uid);

        return $arr;
    }

    /**
     * 获取用户证书
     *
     * @author bohailiang
     * @date   2012/3/22
     * @param  $uid  int  用户id
     * @access 
     * @return array / false
     */
     function get_books($uid = 0) {
        if (empty($uid)) {
            return false;
        }
        $arr = $this -> userwiki -> getBooksForUser($uid);
        
        return $arr;
    }

    /**
     * 获取用户语言
     *
     * @author bohailiang
     * @date   2012/3/22
     * @param  $uid  int  用户id
     * @access 
     * @return array / false
     */
     function get_lang($uid = 0) {
        if (empty($uid)) {
            return false;
        }
        $arr = $this -> userwiki -> getLangForUser($uid);
        
        return $arr;
    }

    /**
     * 获取用户培训
     *
     * @author bohailiang
     * @date   2012/3/22
     * @param  $uid  int  用户id
     * @access 
     * @return array / false
     */
     function get_train($uid = 0) {
        if (empty($uid)) {
            return false;
        }
        $arr = $this -> userwiki -> getTrainForUser($uid);

        return $arr;
    }
     
     
     function show_frame()
     {
         $type = $this->input->get("frame");
         switch ($type) {
             case '1':
                 $this->display('edit/school_company/college.html'); break;
             case '2':
                 $this->display('edit/school_company/highschool.html'); break;
             case '3':
                    $this->display('edit/school_company/primaryschool.html');break;
             case '4':
                    $this->display('edit/school_company/company.html'); break;
                 case '5':
                    $this->display('edit/school_company/department.html'); break;
                 case '6':
                    $this->display('edit/school_company/post.html'); break;  
             default:
                    echo "非法操作，无对应项数据"; break;
         }
     }

}
