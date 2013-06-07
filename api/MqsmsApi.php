<?php

/**
 * 短信发送类
 * @author sunlufu 
 */
class MqsmsApi extends DkApi {

    protected static $con = null;
    //队列名
    protected $queue_name = 'sms.queue';
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
    //替换后的队列名字
    protected $queue;

    /**
     * 初始化队列服务器
     * @param type $queue_name  对列名
     */
    function __initialize() {
        //获取配置
        $config_arr = getConfig('mqsms', 'default');
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
     * @param string $to            接收者手机号
     * @param string $msg           短信内容
     */
    function sendMqsms($to, $msg) {
        try {
            $msg = sprintf('{"to":"%s","msg":"%s"}', $to, $msg);
            self::$con->send($this->queue, $msg);
            return true;
        } catch (ErrorException $e) {
            die($e->getMessage());
            //log error             
        }
    }

}