<?php

/**
 * 用户资料除(工作、教育、在校情况)增、删、改控制器
 * @author chenxujia
 * @date   2012/3/22
 */
require 'myotherdataedit.php';

class otherDataEdit extends MY_OtherDataEdit {

    public function __construct() {
        parent::__construct();
    }

    /**
     * 用户培训经历添加
     * @author chenxujia
     * @date   2012/3/22
     * @param  $_POST 数据
     * @access public
     * @return ''
     */
    public function teachAdd() {
        $this->addKeys = array(
            'subject' => 'subject',
            'provider' => 'provider',
            'starttime' => 'starttime',
            'endtime' => 'endtime',
            'address' => 'address',
            'certificate' => 'certificate',
            'description' => 'description',
            'dateline' => 'dateline'
        );
        parent::teachAdd();
    }

    /**
     * 用户培训经历修改
     * @author chenxujia
     * @date   2012/3/22
     * @param  $_POST 数据
     * @access public
     * @return ''
     */
    public function teachEdit() {
        $this->editKeys = array(
            'subject' => 'subject',
            'provider' => 'provider',
            'starttime' => 'starttime',
            'endtime' => 'endtime',
            'address' => 'address',
            'certification' => 'certificate',
            'description' => 'description',
            'lastupdate_time' => 'updatetime'
        );

        parent::teachEdit();
    }

    /**
     * 用户培训经历删除
     * @author chenxujia
     * @date   2012/3/22
     * @param  $_POST 数据
     * @access public
     * @return ''
     */
    public function teachDelete() {
        parent::teachDelete();
    }

    /**
     * 用户语言状况添加
     * @author chenxujia
     * @date   2012/3/22
     * @param  $_POST 数据
     * @access public
     * @return ''
     */
    public function languageAdd() {
        $this->addKeys = array(
            'type' => 'type',
            'level' => 'level',
            'grade' => 'grade',
            'read' => 'read',
            'listen' => 'listen',
            'dateline' => 'dateline'
        );
        parent::languageAdd();
    }

    /**
     * 用户语言状况修改
     * @author chenxujia
     * @date   2012/3/22
     * @param  $_POST 数据
     * @access public
     * @return ''
     */
    public function languageEdit() {
        $this->editKeys = array(
            'level' => 'level',
            'grade' => 'grade',
            'read' => 'read',
            'listen' => 'listen',
            'lastupdate_time' => 'updatetime'
        );
        parent::languageEdit();
    }

    /**
     * 用户语言情况删除
     * @author chenxujia
     * @date   2012/3/22
     * @param  $_POST 数据
     * @access public
     * @return ''
     */
    public function languageDelete() {
        parent::languageDelete();
    }

    /**
     * 用户项目经历添加
     * @author chenxujia
     * @date   2012/3/22
     * @param  $_POST 数据
     * @access public
     * @return ''
     */
    public function projectAdd() {
        $this->addKeys = array(
            'name' => 'name',
            'starttime' => 'starttime',
            'endtime' => 'endtime',
            'responsibility' => 'response',
            'desc' => 'description',
            'dateline' => 'dateline'
        );
        parent::projectAdd();
    }

    /**
     * 用户项目经历修改
     * @author chenxujia
     * @date   2012/3/22
     * @param  $_POST 数据
     * @access public
     * @return ''
     */
    public function projectEdit() {
        $this->editKeys = array(
            'name' => 'name',
            'starttime' => 'starttime',
            'endtime' => 'endtime',
            'responsibility' => 'response',
            'desc' => 'description',
            'lastupdate_time' => 'updatetime'
        );
        parent::projectEdit();
    }

    /**
     * 用户项目经历删除
     * @author chenxujia
     * @date   2012/3/22
     * @param  $_POST 数据
     * @access public
     * @return ''
     */
    public function projectDelete() {
        parent::projectDelete();
    }

    /**
     * 用户获得证书添加
     * @author chenxujia
     * @date   2012/3/22
     * @param  $_POST 数据
     * @access public
     * @return ''
     */
    public function bookAdd() {
        $this->addKeys = array(
            'name' => 'name',
            'starttime' => 'starttime',
            'provider' => 'provider',
            'description' => 'description',
            'dateline' => 'dateline'
        );
        parent::bookAdd();
    }

    /**
     * 用户获得证书修改
     * @author chenxujia
     * @date   2012/3/22
     * @param  $_POST 数据
     * @access public
     * @return ''
     */
    public function bookEdit() {
        $this->editKeys = array(
            'name' => 'name',
            'starttime' => 'starttime',
            'provider' => 'provider',
            'description' => 'description',
            'lastupdate_time' => 'updatetime'
        );
        parent::bookEdit();
    }

    /**
     * 用户获得证书删除
     * @author chenxujia
     * @date   2012/3/22
     * @param  $_POST 数据
     * @access public
     * @return ''
     */
    public function bookDelete() {
        parent::bookDelete();
    }

    /**
     * 用户专业技能添加
     * @author chenxujia
     * @date   2012/3/22
     * @param  $_POST 数据
     * @access public
     * @return ''
     */
    public function skillAdd() {
        $this->addKeys = array(
            'type' => 'type',
            'name' => 'name',
            'month' => 'month',
            'degree' => 'degree',
            'dateline' => 'dateline',
            'lastupdate_time' => 'updatetime'
        );
        parent::skillAdd();
    }

    /**
     * 用户专业技能修改
     * @author chenxujia
     * @date   2012/3/22
     * @param  $_POST 数据
     * @access public
     * @return ''
     */
    public function skillEdit() {
        $this->editKeys = array(
            'type' => 'type',
            'name' => 'name',
            'month' => 'month',
            'degree' => 'degree',
            'lastupdate_time' => 'updatetime'
        );
        parent::skillEdit();
    }

    /**
     * 用户专业技能删除
     * @author chenxujia
     * @date   2012/3/22
     * @param  $_POST 数据
     * @access public
     * @return ''
     */
    public function skillDelete() {
        parent::skillDelete();
    }

    /**
     * 用户私密资料编辑
     * @author chenxujia
     * @date   2012/3/22
     * @param  $_POST 数据
     * @access public
     * @return json
     */
    public function privateEdit() {
        $keys = array(
            'ismarry' => 'ismarry',
            'haschildren' => 'haskid',
            'home_nation' => 'home_addr',
            'now_nation' => 'now_addr',
        );
        if (parent::privateEdit($keys)) {
            json_encodes(1, L('operate_success'));
        } else {
            json_encodes(0, L('operate_fail'));
        }
    }

    /**
     * 用户兴趣爱好添加修改删除
     * @author chenxujia
     * @date   2012/3/22
     * @param  $_POST 数据
     * @access public
     * @return json
     */
    public function interestEdit() {
        if (!isset($_POST['params'])) {
            json_encodes(0, L('lost para'));
        }
        $interest = $this->getUserInterest($this->uid);
        $params = $_POST['params']; //就是一个数组
        $edit = array();
        $add = array();
        $delete = array();
        $isreturn = true;
        foreach ($params as $key => $value) {
            $j = explode(':', $value);
            if ($interest) {//该用户存在数据
                if (in_array($j[0], $interest)) {//之前存在这个分类
//                    if ($j[1]) {//并且有值就修改
                        $edit[] = $this->getUpdateInterest($j[0], $j[1]);
//                    } else {//当前传过来的没有值就删除
//                        $delete[] = array('uid' => $this->uid, 'type' => $j[0]);
//                    }
                } else {//该用户不存在这个分类
                    $tmp = $this->getUpdateInterest($j[0], $j[1]);
                    if ($tmp) {
                        $add[] = $tmp;
                    }
                }
            } else {//不存在该用户信息就直接添加
                $tmp = $this->getUpdateInterest($j[0], $j[1]);
                if ($tmp) {
                    $add[] = $tmp;
                }
            }
        }
        if ($add) {//存在添加数据就添加
            if (!$this->interestsAdd($add)) {
                $isreturn = false;
            } else {
                $isreturn = true;
            }
        }
        if ($edit) {//存在修改的数据就修改
            if (!$this->interestsEdit($this->uid, $edit)) {
                $isreturn = false;
            } else {
                $isreturn = true;
            }
        }
        /*
        if ($delete) {//存在删除的数据就删除
            foreach ($delete as $key => $value) {
                $arr[] = $value['type'];
            }
            if (!interestsDelete($this->uid, $arr)) {
                $isreturn = false;
            } else {
                $isreturn = true;
            }
        }*/
        if ($isreturn) {
            json_encodes(1, L('operate_success'));
        } else {
            json_encodes(0, L('operate_fail'));
        }
    }

    /**
     * 用户兴趣爱好获取数据
     * @author chenxujia
     * @date   2012/3/22
     * @param  $type 类型 $interest爱好
     * @access public
     * @return array $newinterest
     */
    private function getUpdateInterest($type, $interest) {
        $newinterest = array();
        $time = time();
        $newinterest['uid'] = $this->uid;
        $newinterest['type'] = $type;
        $interest = $interest ? $interest : '';
        $newinterest['title'] = json_encode($interest);
        $newinterest['dateline'] = $time;
        return $newinterest;
    }

    /**
     * 对存在的数据过滤
     * @author chenxujia
     * @date   2012/3/22
     * @param  array $exists,array $tids
     * @access private
     * @return array $newinterest
     */
    private function exists_filter(array $exists, array $tids) {
        if (!($exists && $tids)) {
            return array();
        }
        $count = count($tids);
        $vals = array();
        for ($i = 0; $i < $count; $i++) {
            if ($tids[$i]) {
                if (!in_array($tids[$i], $exists)) {
                    $vals[] = $tids[$i];
                }
            }
        }
        return $vals;
    }

    /**
     * 编辑生活习惯
     * @author chenxujia
     * @date   2012/3/22
     * @param  ''
     * @access public
     * @return json
     */
    public function LifeEdit() {
        if ($oldVals = $this->getDataLife($this->uid)) {//
            $this->editDo($oldVals);
        } else {
            $this->addDo();
        }
        json_encodes(1, L('operate_success'));
    }
    
    /***************************************************************************/

    private function getUserInterest($uid) {
        $this->load->model('Interestmodel', 'Interest', true);
        return $this->Interest->getUserInterest($uid);
    }

    private function interestsEdit($uid, $data) {
        $this->load->model('Interestmodel', 'Interest', true);
        return $this->Interest->updateUserInterest($uid, $data);
    }

    private function interestsAdd($add) {
        if (!is_array($add)) {
            return false;
        }
        $this->load->model('Interestmodel', 'Interest', true);
        $data = $this->Interest->addUserInterest($add);
        if ($data) {
            return true;
        } else {
            return false;
        }
    }

}
