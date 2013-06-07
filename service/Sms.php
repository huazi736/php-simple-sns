<?php

/**
 * 短信接口
 */
class SmsService extends DK_Service {

    protected $client;
    protected $tableName = 'sms_log';

    public function __construct($config = array()) {
//        $this->init_db('sms);
        $this->client = get_sms();
    }

    /**
     * 发送短信
     *
     * @param string|array $mobiles 手机号
     * @param string $content 短信内容
     */
    public function sendSMS($mobiles = array(), $content) {
        return $this->sendTimingSMS($mobiles, $content, '');
    }

    /**
     * 发送定时短信
     * 
     * @param string|array $mobiles 手机号
     * @param string $content 短信内容
     * @param string $addSerial 定时发送，格式为yyyymmddHHiiss
     * 
     * @return array 
     * @see state: 0:    成功;
     *             17:   发送信息失败;
     *             18:   发送定时信息失败;
     *             101:  客户端网络故障;
     *             305:  服务器端返回错误，错误的返回值（返回值不是数字字符串）;
     *             307:  目标电话号码不符合规则，电话号码必须是以0、1开头;
     *             997:  平台返回找不到超时的短信，该信息是否成功无法确定;
     *             998:  由于客户端网络问题导致信息发送超时，该信息是否成功下发无法确定;
     *             9001: 号码为空;
     * @see error: 错误的手机号数组
     */
    public function sendTimingSMS($mobiles = array(), $content, $sendTime = '') {
        $_error = array();
        $_list = array();
        //验证号码
        if (is_string($mobiles)) {
            $mobiles = array($mobiles);
        }
        if (is_array($mobiles) && count($mobiles) > 0) {
            foreach ($mobiles as $one) {
                if (self::is_mobile($one)) {
                    $_list[] = $one;
                } else {
                    $_error[] = $one;
                }
            }
        }
        if (count($_list) == 0) {
            return array('state' => 9001, 'error' => $_error);
        }
        //return THINK_PATH;
        $result = $this->client->sendSMS($_list, $content, $sendTime, '', $this->charset, 5);
        return $result;

        return array('state' => $result, 'error' => $_error);
    }

    /*
     * 待发短信入库
     */

    public function addDbSMS($id, $mobile, $content, $type, $from_mobile = '') {
        $data = array();
        $data['msg_id'] = $id;
        $data['from_mobile'] = $from_mobile;
        $data['to_mobile'] = $mobile;
        $data['message'] = $content;
        $data['type'] = $type;
        $data['send_time'] = time();
        $data['report_time'] = 0;
        $data['sp'] = '';
        $data['state'] = -1;
        //$sms = D('SmsLog');        
        //return $sms->addSmsLog($data);
        return $this->addSmsLog($data);
    }

    /*
     * 待发短信入队
     */

    public function addQueueSMS($queue_key, $id, $mobile, $content) {
        $data = array('msg_id' => $id, 'mobile' => $mobile, 'content' => $content);
        //return service('Queue')->put($queue_key,$data);
    }

    /*
     * 更新短信状态
     */

    public function updateSMS($id, $state) {
        $data = array();
        $data['state'] = $state;
        if ($state == 0)
            $data['report_time'] = time();
//        $sms = D('SmsLog');
//        return $sms->updateSmsLog($id, $data);
        return $this->updateSmsLog($id, $data);
    }

    public function is_mobile($mobile) {
        return preg_match('/^1[3458][\d]{9}$/', $mobile);
    }

    ############Model

    /**
     * 公用构造日志ID函数
     */
    public function get_uuid() {
        $chars = md5(uniqid(mt_rand(), true));
        $uuid = substr($chars, 0, 8) . '-';
        $uuid .= substr($chars, 8, 4) . '-';
        $uuid .= substr($chars, 12, 4) . '-';
        $uuid .= substr($chars, 16, 4) . '-';
        $uuid .= substr($chars, 20, 16);
        return $uuid;
    }

    /**
     * 添加数据
     */
    public function addSmsLog($data) {
        $data['msg_id'] = $this->get_uuid();
        $result = $this->db->insert($this->tableName, $data);
        return $result;
    }

    /**
     * 
     * 更新数据
     * @param string $id
     * @param unknown_type $data
     */
    public function updateSmsLog($id, $data) {
        $where['msg_id'] = $id;
        $result = $this->db->where($where)->update($this->tableName, $data);
        return $result;
    }

    /**
     * 判断ID是否存在
     */
    public function isExistsByID($id) {
        $result = $this->db->where(array('msg_id' => $id))->select('msg_id')->row_array();
        return $result;
    }

    
    //一下接口用于后台短信管理
    //获取序列号
    public function getNumber() {
//        $number = C('serialNumber');
//        return $number;
        return $this->serialNumber;
        
    }

    //注册序列号
    public function login($sessionKey) {
//        return $this->client->login(C('sessionKey'));
        return $this->client->login($sessionKey);
    }

    //注销序列号
    public function logout() {
        return $this->client->logout();
    }

    //获得单价
    public function getPrice() {
        return $this->client->getEachFee();
    }

    //获得余额
    public function getBlance() {
        return $this->client->getBalance();
    }

    //注册企业信息
    public function registEntInfo($data = array()) {
        if (empty($data))
            return false;
        $ret = $this->client->registDetailInfo($data['eName'], $data['linkMan'], $data['phoneNum'], $data['mobile'], $data['email'], $data['fax'], $data['address'], $data['postcode']);
        return $ret;
    }

    /**
     * $cardId [充值卡卡号]
     * $cardPass [密码]
     * 
     * 请通过亿美销售人员获取 [充值卡卡号]长度为20内 [密码]长度为6
     * 
     */
    public function charges($cardId, $cardPass) {
        $statusCode = $this->client->chargeUp($cardId, $cardPass);
        return $statusCode;
    }

    /* 去掉获得上行消息 by sunlufu at 2012.6.7 
      public function getMessages(){
      $moResult = $this->client->getMO();
      $rs['返回数量'] = count($moResult);
      foreach($moResult as $mo){
      //$mo 是位于 Client.php 里的 Mo 对象
      $item = array();
      $item['发送者附加码'] = $mo->getAddSerial();
      $item['接收者附加码'] = $mo->getAddSerialRev();
      $item['通道号'] = $mo->getChannelnumber();
      $item['手机号'] = $mo->getMobileNumber();
      $item['发送时间'] = $mo->getSentTime();

      //由于服务端返回的编码是UTF-8,所以需要进行编码转换
      $item['短信内容'] = iconv("UTF-8","GBK",$mo->getSmsContent());
      $rs[] = $item;
      }
      return $rs;
      } */

    //设置新密码 
    public function setNewPwd($oPwd, $newPwd) {
        $oldpwd = C('password');
        if (empty($oldpwd))
            return false;
        $path = APP_PATH . '/Conf/pwdconfig.php';
        if ($oldpwd == $oPwd) {
            $pwdarr = array('password' => $newPwd);
            $str = var_export($pwdarr, true);
            $str = "<?php \r\n if(!defined('THINK_PATH')) exit('Path is Error'); \r\n return " . $str . ";";
            $ret = file_put_contents($path, $str);
            if (!$ret)
                return false;
            $result = $this->client->updatePassword($newPwd);
            return $result;
        }
        return '1000';
    }

}
