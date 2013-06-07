<?php
/**
 * 用户资料(工作、教育、在校情况)增、删、改控制器
 * @author chenxujia
 * @date   2012/3/22
 */
require 'myjobandschooledit.php';
class jobAndSchoolDataEdit extends MY_JobAndSchoolEdit {
    public function __construct() {
        parent::__construct();
    }

    /**
     * 获取用户对应模块的权限
     * @author chenxujia
     * @date   2012/3/22
     * @param  $type 类型如edu
     * @access private
     * @return true/false
     */
    private function getPermission($type) {
        if (!$type) {
            return false;
        }
        $this -> load -> model('singleaccessmodel', '_access', true);
        return $this -> _access -> getAccess($type, $this -> uid);
    }

    /**
     * 用户基本资料修改
     * @author chenxujia
     * @date   2012/3/22
     * @param  $_POST 数据
     * @access public
     * @return json
     */
    public function baseEdit() {
        //获取数据
        $year = P('year');
        $month = P('month');
        $day = P('day');
        $lastname = P('lastname');   
        $sex = p('sex');  
       
        if(!empty($lastname)){
            $len=mb_strlen($lastname);
	        if ($len < 2 || $len > 10) {
	            die(json_encodes(0, L('姓名长度2-10！')));
	        }
		    if (!preg_match("/^[\x{4E00}-\x{9FFF}a-zA-Z]+$/u", $lastname)) {
		        die(json_encodes(0, L('姓名只支持中英文(不能输空格或数字)！')));
		    }
        }
        if (!empty($year) && !empty($month) && !empty($day)) {
            if (($year == -1) && ($month == -1) && ($day == -1)) {
                $_POST['birthday'] = 0;
            } else {
                $_POST['birthday'] = mktime(0, 0, 0, $month, $day, $year);
            }
           
        }
         $_POST['lastupdatebirthday'] = time();
            //修改生日时间
        $keys = array('lastname' => 'username', 'sex' => 'sex', 'birthday' => 'birthday', 'lastupdatebirthday' => 'lastupdatebirthday');

        $birthday = P('birthday');
        if (!empty($birthday)) {
            $times = $birthday;
            if ($times) {
                $hasValue = parent::hasValues();
                if ($hasValue) {//修改时间轴
                    $result = call_soap('timeline', 'Timeline', 'updateCtimeByMap', array($this -> uid, 'uinfo', $times));
                    $data = array('fid' => $this -> uid, 'content' => '出生于：' . date('Y年n月j日', $times), 'type' => 'uinfo');
                    $tt = call_soap('timeline', 'Timeline', 'updateTopic', array($data));
                } else {//添加时间轴
                    $permission = $this -> getPermission('base');
                    $timedata = array('dkcode' => $this -> dkcode, 'uid' => $this -> uid, 'fid' => $this -> uid, 'uname' => $this -> user['username'], 'content' => '出生于：' . date('Y年n月j日', $times), 'type' => 'uinfo', 'subtype' => 'born', 'info' => '', 'permission' => $permission['object_type'], 'dateline' => $birthday);
                    //入住时间轴
                    $result = call_soap('timeline', 'Timeline', 'addTimeLine', array($timedata));
                }
            }
        }
        if (parent::baseEdit($keys)) {
            /*
             * 如果姓名有修改，则同步修改redis
             * author liyundong
             * */
            if (!empty($lastname)) {
                 $res = call_soap('social', 'Social', 'setUserInfo', array( array('uid' => $this -> uid, 'uname' => $lastname, 'dkcode' => $this -> dkcode)));
                 //姓名改变，跟新相应的数据，重建索引
              $this -> addOrUpdateBasalInfoOfPeople($lastname);
            }
            if(!empty($sex)){
               $res = call_soap('social', 'Social', 'setUserInfo', array( array('uid' => $this -> uid, 'sex' => $sex, 'dkcode' => $this -> dkcode)));
             }
            //同时修改session
            if(!empty($lastname))
            {
                $sess_res=call_soap('ucenter', 'Passport', 'setUsernameFromSessinon',array($this->sessionid,$lastname));
            }
            die(json_encodes(1, L('操作成功！')));
            //operate_success
        } else {
           die(json_encodes(0, L('操作失败！')));
            //operate_fail
        }
        }
    /**
     * 用户大学资料添加
     * @author chenxujia
     * @date   2012/3/22
     * @param  ''
     * @access public
     * @return ''
     */
    public function universityAdd() {
        //validate
        $this -> addKeys = array('schoolId' => 'schoolid', 'school_name' => 'schoolname', 'eduCation_c' => 'edulevel', 'school_year' => 'starttime', 'dateline' => 'dateline', 'departmentId' => 'department_id', 'school_department' => 'department', 'pid' => 'area_id', 'classmate' => 'classmate');
        parent::universityAdd();
    }

    /**
     * 用户大学资料修改
     * @author chenxujia
     * @date   2012/3/22
     * @param  ''
     * @access public
     * @return ''
     */
    public function universityEdit() {
        $this -> editKeys = array('eduCation_c' => 'edulevel', 'school_year' => 'starttime', 'departmentId' => 'department_id', 'school_department' => 'department', 'pid' => 'area_id', 'classmate' => 'classmate');
        parent::universityEdit();
    }

    /**
     * 用户大学资料删除
     * @author chenxujia
     * @date   2012/3/22
     * @param  ''
     * @access public
     * @return ''
     */
    public function universityDelete() {
        parent::universityDelete();
    }

    /**
     * 用户中学资料添加
     * @author chenxujia
     * @date   2012/3/22
     * @param  ''
     * @access public
     * @return ''
     */
    public function highSchoolAdd() {
        $this -> addKeys = array('schoolId' => 'schoolid', 'school_name' => 'schoolname', 'eduCation_m' => 'edulevel', 'school_year' => 'starttime', 'dateline' => 'dateline', 'classmate' => 'classmate');
        parent::highSchoolAdd();
    }

    /**
     * 用户中学资料修改
     * @author chenxujia
     * @date   2012/3/22
     * @param  ''
     * @access public
     * @return ''
     */
    public function highSchoolEdit() {
        $this -> editKeys = array('schoolId' => 'schoolid', 'school_name' => 'schoolname', 'eduCation_m' => 'edulevel', 'school_year' => 'starttime', 'dateline' => 'dateline', 'classmate' => 'classmate');
        parent::highSchoolEdit();
    }

    /**
     * 用户中学资料删除
     * @author chenxujia
     * @date   2012/3/22
     * @param  ''
     * @access public
     * @return ''
     */
    public function highSchoolDelete() {
        parent::highSchoolDelete();
    }

    /**
     * 用户小学资料添加
     * @author chenxujia
     * @date   2012/3/22
     * @param  ''
     * @access public
     * @return ''
     */
    public function primarySchoolAdd() {
        $this -> addKeys = array('schoolId' => 'schoolid', 'school_name' => 'schoolname', 'type' => 'edulevel', 'school_year' => 'starttime', 'dateline' => 'dateline', 'classmate' => 'classmate');
        parent::primarySchoolAdd();
    }

    /**
     * 用户小学资料修改
     * @author chenxujia
     * @date   2012/3/22
     * @param  ''
     * @access public
     * @return ''
     */
    public function primarySchoolEdit() {
        $this -> editKeys = array('schoolId' => 'schoolid', 'school_name' => 'schoolname', 'eduCation_m' => 'edulevel', 'school_year' => 'starttime', 'dateline' => 'dateline', 'classmate' => 'classmate');
        parent::primarySchoolEdit();
    }

    /**
     * 用户小学资料删除
     * @author chenxujia
     * @date   2012/3/22
     * @param  ''
     * @access public
     * @return ''
     */
    public function primarySchoolDelete() {
        parent::primarySchoolDelete();
    }

    /**
     * 用户工作资料添加
     * @author chenxujia
     * @date   2012/3/22
     * @param  ''
     * @access public
     * @return ''
     */
    public function jobAdd() {
        $this -> keys = array('startdate' => 'starttime', 'enddate' => 'endtime', 'companyId' => 'companyid', 'company' => 'company', 'industry' => 'trade', 'position' => 'department', 'positionId' => 'positioncode', 'dateline' => 'dateline', 'colleague' => 'workmate');
        parent::jobAdd();
    }

    /**
     * 用户工作资料修改
     * @author chenxujia
     * @date   2012/3/22
     * @param  ''
     * @access public
     * @return ''
     */
    public function jobEdit() {
        $this -> keys = array('startdate' => 'starttime', 'enddate' => 'endtime', 'industry' => 'trade', 'position' => 'department', 'positionId' => 'positioncode', 'colleague' => 'workmate');
        parent::jobEdit();
    }

    /**
     * 用户工作资料删除
     * @author chenxujia
     * @date   2012/3/22
     * @param  ''
     * @access public
     * @return ''
     */
    public function jobDelete() {
        parent::jobDelete();
    }

    /**
     * 用户在校情况资料添加
     * @author chenxujia
     * @date   2012/3/22
     * @param  ''
     * @access public
     * @return ''
     */
    public function atSchoolAdd() {
        $subject_type = P('subject_type');
        switch ($subject_type) {
            case 1 :
                $this -> addSchoolarship();
                ////奖学金
                break;
            case 2 :
                $this -> addAward();
                //获得奖项
                break;
            case 3 :
                $this -> addPosition();
                //担任职务
                break;
            case 4 :
                $this -> addSocialPractice();
                //社会实践
                break;
            default :
                json_encodes(0, L('数据错误！'));
                //data_error
                break;
        }
    }

    /**
     * 用户在校情况奖学金资料添加
     * @author chenxujia
     * @date   2012/3/22
     * @param  ''
     * @access public
     * @return ''
     */
    public function addSchoolarship() {
        $this -> keys = array('subject_type' => 'type', 'level1' => 'level', 'level2' => 'level2', 'starttime' => 'starttime', 'endtime' => 'endtime', 'dateline' => 'dateline');
        parent::addSchoolarship();
    }

    /**
     * 用户在校情况获得奖项资料添加
     * @author chenxujia
     * @date   2012/3/22
     * @param  ''
     * @access public
     * @return ''
     */
    public function addAward() {
        $this -> keys = array('subject_type' => 'type', 'title' => 'title', 'level' => 'level', 'starttime' => 'starttime', 'dateline' => 'dateline');
        parent::addAward();
    }

    /**
     * 用户在校情况担任职务资料添加
     * @author chenxujia
     * @date   2012/3/22
     * @param  ''
     * @access public
     * @return ''
     */
    public function addPosition() {
        $this -> keys = array('subject_type' => 'type', 'title' => 'title', 'starttime' => 'starttime', 'endtime' => 'endtime', 'dateline' => 'dateline');
        parent::addPosition();
    }

    /**
     * 用户在校情况社会实践资料添加
     * @author chenxujia
     * @date   2012/3/22
     * @param  ''
     * @access public
     * @return ''
     */
    public function addSocialPractice() {
        $this -> keys = array('title' => 'title', 'endtime' => 'endtime', 'starttime' => 'starttime', 'content' => 'content', 'lastupdate_time' => 'updatetime', 'subject_type' => 'type');
        parent::addSocialPractice();
    }

    /**
     * 用户在校情况资料修改
     * @author chenxujia
     * @date   2012/3/22
     * @param  ''
     * @access public
     * @return ''
     */
    public function atSchoolEdit() {
        $subject_type = P('subject_type');
        switch ($subject_type) {
            case 1 :
                $this -> editSchoolarship();
                //奖学金编辑
                break;
            case 2 :
                $this -> editAward();
                //获得奖项编辑
                break;
            case 3 :
                $this -> editPosition();
                //担任职务编辑
                break;
            case 4 :
                $this -> editSocialPractice();
                //社会实践编辑
                break;
            default :
                json_encodes(0, L('数据错误！'));
            //data_error
        }
    }

    /**
     * 用户在校情况奖学金资料修改
     * @author chenxujia
     * @date   2012/3/22
     * @param  ''
     * @access public
     * @return ''
     */
    public function editSchoolarship() {
        $this -> keys = array('level1' => 'level', 'level2' => 'level2', 'starttime' => 'starttime', 'lastupdate_time' => 'updatetime');
        parent::editSchoolarship();
    }

    /**
     * 用户在校情况获得奖项资料修改
     * @author chenxujia
     * @date   2012/3/22
     * @param  ''
     * @access public
     * @return ''
     */
    public function editAward() {
        $this -> keys = array('title' => 'title', 'level' => 'level', 'starttime' => 'starttime', 'lastupdate_time' => 'updatetime');
        parent::editAward();
    }

    /**
     * 用户在校情况担任职务资料修改
     * @author chenxujia
     * @date   2012/3/22
     * @param  ''
     * @access public
     * @return ''
     */
    public function editPosition() {
        $this -> keys = array('title' => 'title', 'starttime' => 'starttime', 'endtime' => 'endtime', 'lastupdate_time' => 'updatetime');
        parent::editPosition();
    }

    /**
     * 用户在校情况社会实践资料修改
     * @author chenxujia
     * @date   2012/3/22
     * @param  ''
     * @access public
     * @return ''
     */
    public function editSocialPractice() {
        $this -> keys = array('title' => 'title', 'endtime' => 'endtime', 'starttime' => 'starttime', 'content' => 'content', 'lastupdate_time' => 'updatetime');
        parent::editSocialPractice();
    }

    /**
     * 用户在校情况[奖学金、获得奖项、担任职务、社会实践]资料删除
     * @author chenxujia
     * @date   2012/3/22
     * @param  ''
     * @access public
     * @return ''
     */
    public function atSchoolDelete() {
        parent::atSchoolDelete();
    }

    /*同步跟新用户的基本信息
     *@liyundong
     *@date 4-17
     * */
    function addOrUpdateBasalInfoOfPeople($uname='') {
        $this -> load -> model("userwikimodel", "userwiki");
        $this -> load -> model('myeditmodel', 'myedit');
        $this -> load -> model('FollowerModel');
        if(empty($uname)) {
            $uname=$this->username;
        }
        $company = $this -> userwiki -> getDataByUserId($this -> uid, USER_JOBEXPER);
        if (empty($company)) {
            $company = array();
        } else {
            $len = count($company);
            for ($i = 0; $i < $len; $i++) {
                $company[] = $company[$i]['company'];
            }
        }

        $home = $this -> myedit -> getUserByUid($this -> uid);
        $home_addr = $home['home_addr'];
        $now_addr = $home['now_addr'];
        $regdate = $home['regdate'];

        $arr = $this -> userwiki -> get_schoolData($this -> uid);
        $shool_name = array();
        foreach ($arr as $value) {
            $shool_name[] = $value['schoolname'];
        }
        $follower_num = $this -> FollowerModel -> getNumOfFollowers($this -> uid);
        $arg_arr=array(
                'uid' => $this -> uid,
                'uname' => $uname,
                'dkcode' => $this->dkcode,
                'follower_num' => $follower_num,
                'company' => $company,
                'home_addr' => $home_addr,
                'now_addr' => $now_addr,
                'school_name' => $shool_name,
                'regdate' => $regdate,
        );
        $result = call_soap('search', 'RelationIndex', 'onlyUpdatePeopleName', array($arg_arr));
    }

}
?>
