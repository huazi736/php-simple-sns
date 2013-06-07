<?php
/**
 * 队列服务Httpsqs
 * @author guosb
 * @date 2011-12-22
 *  
 */

class DK_Httpsqs{
	
	private static $instance = array();
	
	protected $httpsqs_host;			// httpsqs的 地址
	protected $httpsqs_port;			// httpsqs的端口
	protected $httpsqs_auth;			// httpsqs的密码
	protected $httpsqs_charset;		// httpsqs的编码
		
	private function __construct($config='') 
	{
		// 获取配置列表里的httpsqs配置;默认为读取../config/httpsqs.php文件中的配置;
		$config = $this->getConfig($config);
		$this->httpsqs_host 	= $config['host'];
		$this->httpsqs_port 	= $config['port'];
		$this->httpsqs_auth 	= $config['auth'];
		$this->httpsqs_charset 	= $config['charset'];
	}

	// 获取对象 ;
	public static function getInstance($config='')
	{
		if(trim($config)==''){
			die('请输入初始化参数');
		}
		$key = is_string($config) ?  md5($config):md5(serialize($config));
        if(!isset(self::$instance[$key]))
        {
            self::$instance[$key] = new DK_Httpsqs($config);
        }
        return self::$instance[$key];
	}
	
	// 获取httpsqs的数据;
    private function http_get($query)
    {
        $socket = fsockopen($this->httpsqs_host, $this->httpsqs_port, $errno, $errstr, 5);
        if (!$socket)
        {
            return false;
        }
        $out = "GET ${query} HTTP/1.1\r\n";
        $out .= "Host: {$this->httpsqs_host}\r\n";
        $out .= "Connection: close\r\n";
        $out .= "\r\n";
        fwrite($socket, $out);
        $line = trim(fgets($socket));
        $header = $line;
        list($proto, $rcode, $result) = explode(" ", $line);
        $len = -1;
        while (($line = trim(fgets($socket))) != "")
        {
            $header .= $line;
            if (strstr($line, "Content-Length:"))
            {
                list($cl, $len) = explode(" ", $line);
 
            }
            if (strstr($line, "Pos:"))
            {
                list($pos_key, $pos_value) = explode(" ", $line);
            }                   
            if (strstr($line, "Connection: close"))
            {
                $close = true;
            }
        }
        if ($len < 0)
        {
            return false;
        }
        
        $body = fread($socket, $len);
        $fread_times = 0;
        while(strlen($body) < $len){
                $body1 = fread($socket, $len);
                $body .= $body1;
                unset($body1);
                if ($fread_times > 100) {
                        break;
                }
                $fread_times++;
        }
        //if ($close) fclose($socket);
                fclose($socket);
                $result_array["pos"] = (int)((isset($pos_value))?$pos_value:0);
                $result_array["data"] = $body;
        return $result_array;
    }

    // 存放数据;
    private  function http_post($query, $body)
    {
        $socket = fsockopen($this->httpsqs_host, $this->httpsqs_port, $errno, $errstr, 1);
        if (!$socket)
        {
            return false;
        }
        $out = "POST ${query} HTTP/1.1\r\n";
        $out .= "Host: {$this->httpsqs_host}\r\n";
        $out .= "Content-Length: " . strlen($body) . "\r\n";
        $out .= "Connection: close\r\n";
        $out .= "\r\n";
        $out .= $body;
        fwrite($socket, $out);
        $line = trim(fgets($socket));
        $header = $line;
        list($proto, $rcode, $result) = explode(" ", $line);
        $len = -1;
        while (($line = trim(fgets($socket))) != "")
        {
            $header .= $line;
            if (strstr($line, "Content-Length:"))
            {
                list($cl, $len) = explode(" ", $line);
            }
            if (strstr($line, "Pos:"))
            {
                list($pos_key, $pos_value) = explode(" ", $line);
            }                   
            if (strstr($line, "Connection: close"))
            {
                $close = true;
            }
        }
        if ($len < 0)
        {
            return false;
        }
        $body = @fread($socket, $len);
        //if ($close) fclose($socket);
                fclose($socket);
                $result_array["pos"] = (int)((isset($pos_value))?$pos_value:0);
                $result_array["data"] = $body;
        return $result_array;
    }
        
    /**
     * 存放数据到队列
     * @param $queue_name	队列名
     * @param $queue_data	存放数据
     * 
     * @return	bool
     *
     */
    public function put($queue_name, $queue_data)
    {
        $result = $this->http_post("/?auth=".$this->httpsqs_auth."&charset=".$this->httpsqs_charset."&name=".$queue_name."&opt=put", $queue_data);
                if ($result["data"] == "HTTPSQS_PUT_OK") {
                        return true;
                } else if ($result["data"] == "HTTPSQS_PUT_END") {
                        return false;
                }
                return false;
    }
    
    /**
     * 取出队列数据
     * @param	$queue_name		队列名
     * 
     * @return	队列中的数据;
     * 			如果没有未被取出的队列,则返回		HTTPSQS_GET_END
     * 			如果发生错误,则返回				false;
     */
    public function get($queue_name)
    {
        $result = $this->http_get("/?auth=".$this->httpsqs_auth."&charset=".$this->httpsqs_charset."&name=".$queue_name."&opt=get");
                if ($result == false || $result["data"] == "HTTPSQS_ERROR" || $result["data"] == false) {
                        return false;
                }
        return $result["data"];
    }
        
    /**
     * 取出队列数据和当前的队列读取点Pos
     * @param	$queue_name		队列名
     * 
     * @return	返回数组示例:			array('pos'=>5,'data'=>'text message');
     * 			如果没有未被取出的队列	array('pos'=>0,'data'=>'HTTPSQS_GET_END');
     * 			发生错误,返回布尔值		false;
     */
    public function gets($queue_name)
    {
        $result = $this->http_get("/?auth=".$this->httpsqs_auth."&charset=".$this->httpsqs_charset."&name=".$queue_name."&opt=get");
                if ($result == false || $result["data"] == "HTTPSQS_ERROR" || $result["data"] == false) {
                        return false;
                }
        return $result;
    }   
        
    /**
     * 获取队列的状态
     * @param	$queue_name		队列名 
     * 
     * @return	string;
     */
    public function status($queue_name)
    {
        $result = $this->http_get("/?auth=".$this->httpsqs_auth."&charset=".$this->httpsqs_charset."&name=".$queue_name."&opt=status");
                if ($result == false || $result["data"] == "HTTPSQS_ERROR" || $result["data"] == false) {
                        return false;
                }
        return $result["data"];
    }
        
    /**
     * 获取指定队列位置的内容;不出队操作;
     * @param	$queue_name		队列名
     * @param	$queue_pos		指定位置
     * 
     *  @return	string;
     */
    public function view($queue_name, $queue_pos)
    {
        $result = $this->http_get("/?auth=".$this->httpsqs_auth."&charset=".$this->httpsqs_charset."&name=".$queue_name."&opt=view&pos=".$queue_pos);
                if ($result == false || $result["data"] == "HTTPSQS_ERROR" || $result["data"] == false) {
                        return false;
                }
        return $result["data"];
    }
        
    /**
     * 重置指定队列
     * @param	$queue_name		队列名
     * 
     * @return	bool
     * 
     * */
    public function reset($queue_name)
    {
        $result = $this->http_get("/?auth=".$this->httpsqs_auth."&charset=".$this->httpsqs_charset."&name=".$queue_name."&opt=reset");
                if ($result["data"] == "HTTPSQS_RESET_OK") {
                        return true;
                }
        return false;
    }
        
    /**
     * 更改指定队列的最大队列数
     * @param	$queue_name		队列名
     * @param	$nun			最大队列数 
     * 
     * @return	bool
     * 
     * */
    public function maxqueue($queue_name, $num)
    {
        $result = $this->http_get("/?auth=".$this->httpsqs_auth."&charset=".$this->httpsqs_charset."&name=".$queue_name."&opt=maxqueue&num=".$num);
                var_dump($result);
				if ($result["data"] == "HTTPSQS_MAXQUEUE_OK") {
                        return true;
                }
        return false;
    }
        
    /**
     * 获取队列的状态(json格式)
     * @param	$queue_name		队列名
     * 
     * @return	json格式的队列信息
     * 
     */
    public function status_json($queue_name)
    {
        $result = $this->http_get("/?auth=".$this->httpsqs_auth."&charset=".$this->httpsqs_charset."&name=".$queue_name."&opt=status_json");
                if ($result == false || $result["data"] == "HTTPSQS_ERROR" || $result["data"] == false) {
                        return false;
                }
        return $result["data"];
    }

    /**
     *	 修改定时刷新内存缓冲区内同到磁盘的间隔时间
     * @param	$num		缓冲时间,单位为秒;
     * 
     * @return	bool
     */
    public function synctime($num)
    {
        $result = $this->http_get("/?auth=".$this->httpsqs_auth."&charset=".$this->httpsqs_charset."&name=httpsqs_synctime&opt=synctime&num=".$num);
                if ($result["data"] == "HTTPSQS_SYNCTIME_OK") {
                        return true;
                }
        return false;
    }
	// 测试使用
    /*public function test()
    {
    	return C();
    }*/
	
   private function getConfig($config)
    {
        if(is_string($config) and !empty($config))
        {
            $configs = include(CONFIG_PATH . 'httpsqs.php');
            $config = isset($configs[$config]) ? $configs[$config] : false;
        }
        
        if(is_array($config) and count($config)>0)
        {
            foreach($config as $key=>$value)
            {
                $config[$key] = $value;
            }
        }
        return $config;
    }
}