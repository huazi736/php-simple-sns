<?php

/**
 * 邮件发送类
 * @author niupeiyuan 
 */
class MailService extends DK_Service {

    protected static $con = null;
    //队列名
    protected $queue_name = 'email.queue';
    //协议
    protected $protocol = 'tcp';
    //queue 服务器
    protected $queue_server = '';
    //是否需要验证
    protected $auth = false;
    //queue 用户名
    protected $queue_username = 'username';
    //queue 密码
    protected $queue_password = 'password';

    /**
     * 初始化队列服务器
     * @param type $queue_name  对列名
     */
    function __construct() {      
        //获取配置
        $config_arr = getConfig('mail', 'default');  
        $this->protocol = $config_arr['protocol'];
        $this->auth = $config_arr['auth'];
        $this->queue_server = $config_arr['host'] . ':' . $config_arr['port'];
        $this->queue_username = $config_arr['queue_username'];
        $this->queue_password = $config_arr['queue_password'];
        $this->queue_name = $config_arr['queue_name'];
        
        //设置队列名        
        $this->queue = sprintf("/queue/%s", $this->queue_name);
        self::getInstance();
    }

    /**
     * 获取obj
     * @return type 
     */
    function getInstance() {
        require_once EXTEND_PATH . '/stomp/Stomp.php';
        if (self::$con === null) {
            self::$con = new Stomp($this->_getConnetString());
            if ($this->auth) {
                self::$con->connect($this->queue_username, $this->queue_password);
            } else {
                self::$con->connect();
            }
        }
        return self::$con;
    }

    //获取一个连接string
    private function _getConnetString() {
        $string = sprintf("%s://%s", $this->protocol, $this->queue_server);
        return $string;
    }

    /**
     * 发送邮件
     * @param string $to            接收者mail地址
     * @param string $username      接收者名字
     * @param string $subject       主题
     * @param int $templetId        模板id
     * @param string $redirect_url  跳转地址
     * @param string $newmailbox    新邮箱
     */
    function sendEmail($to, $username, $subject, $templetId, $redirect_url, $newmailbox = '') {
        try {
            $time = date('Y年m月d日H时i分s秒', time());
			if(empty($newmailbox)) {
				$content = sprintf('"name":"%s","sendtime":"%s","redirect_url":"%s"',$username, $time, $redirect_url);
			} else {
				$content = sprintf('"name":"%s","sendtime":"%s","redirect_url":"%s","newmailbox":"%s"',$username, $time, $redirect_url ,$newmailbox);
			}
            $msg = sprintf('{"subject":"%s","to":"%s","templetId":"%d","context":{%s}}', $subject, $to, $templetId, $content);
            self::$con->send($this->queue, $msg);
            return true;
        } catch (ErrorException $e) {
            die($e->getMessage());
            //log error             
        }
    }

}

