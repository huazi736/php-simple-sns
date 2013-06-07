<?php
date_default_timezone_set('Asia/Shanghai');
 class Mailsina{

        private $gurl='';
        private function vlogin($url,$name, $ps){
            //global $gurl;
            $cookie_jar = tempnam('./tmp','cookie');//�ڵ�ǰĿ¼������һ������ļ�������ʱ�ļ�
            $ch = curl_init($url); //��ʼ��curlģ��

            $fields_post = array(
                                'logintype'=>'uid',
                                'u'=>$name,
                                'domain'=>'sina.com',
                                'psw'=>$ps,
                                'btnloginfree'=>'%B5%C7+%C2%BC'
            );
            curl_setopt($ch,CURLOPT_URL,$url);//��¼ҳ��ַ
            curl_setopt($ch, CURLOPT_POST, 1);//post��ʽ�ύ
            $fields_string = '';
            foreach($fields_post as $key => $value)
            {
               $fields_string .= $key . '=' . $value . '&';
            }
            $fields_string = rtrim($fields_string , '&');
            curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);//Ҫ�ύ������
            //�ѷ���$cookie_jar����cookie��Ϣ������$cookie_jar�ļ���
            curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie_jar);
            $headers_login = array(
                                 'Host'  => 'mail.sina.com.cn',
                                 'User-Agent'      => 'Mozilla/5.0 (Windows; U; Windows NT 5.1; zh-CN; rv:1.9.2.2) Gecko/20100316 Firefox/3.6.2',
                                 'Accept'          => 'text/javascript, text/html, application/xml',
                                 'Accept_Language' => 'zh-cn,zh;q=0.5',
                                 'Accept_Encoding' => 'gzip,deflate',
                                 'Accept_Charset'  => 'GB2312,utf-8;q=0.7,*;q=0.7\r\n',
                                 'Keep-Alive'      => '115',
                                 'Connection'      => 'keep-alive',
                                 'X-Requested-With'=> 'XMLHttpRequest',
                                 'Content_Type'    => 'application/x-www-form-urlencoded; charset=UTF-8',
                                 'Referer'         => 'http://mail.sina.com.cn/'
                             );
            //�趨���ص������Ƿ��Զ���ʾ
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            //�趨�Ƿ���ʾͷ��Ϣ
            curl_setopt($ch, CURLOPT_HEADER, 1);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers_login);
            //�趨�Ƿ����ҳ������
            curl_setopt($ch, CURLOPT_NOBODY, 0);
            $result=curl_exec ($ch);
            //echo $result;
            preg_match('/Location:[^\"].*/', $result, $location);
            if(empty($location)){
                //exit("��¼����");
                return 0;
            }
            list($lc,$urltemp)=explode(':',$location[0],2);
            $this -> gurl = $urltemp;
            curl_close($ch); //get data after login
            return $cookie_jar;
        }

        //��ȡͨѶ¼�б�
        public function getAddressList($username,$password){
             $result_array = array();
             $login_url   = 'http://mail.sina.com.cn/cgi-bin/login.cgi';
             $cookie_file = $this -> vlogin($login_url,$username,$password);    //ģ���½

             if(!$cookie_file){
                 return $result_array;
             }
             $this -> gurl = trim($this -> gurl);
             $ch   = curl_init($this -> gurl);
             $headers = array(
                             'Host'       => 'mail3-145.sinamail.sina.com.cn',
                             'User-Agent' => 'Mozilla/5.0 (Windows; U; Windows NT 5.1; zh-CN; rv:1.9.2.2) Gecko/20100316 Firefox/3.6.2'
             );
             curl_setopt($ch, CURLOPT_URL,$this -> gurl);
             curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
             curl_setopt($ch, CURLOPT_HEADER, true);
             curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 120);
             curl_setopt($ch, CURLOPT_POST, 0);
             curl_setopt($ch, CURLOPT_REFERER, 'http://mail.sina.com.cn/');
             curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
             //��֮ǰ�����cookie��Ϣ��һ���͵���������
             curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_file);
             curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie_file);
             curl_setopt($ch, CURLOPT_NOBODY, 0);
             $result = curl_exec($ch);

             curl_close($ch);
             preg_match('/Location:[^\"].*/', $result, $location);

             list($lc,$urltemp)=explode(':',$location[0],2);


             $this -> gurl=trim($this -> gurl);
             $server = substr(substr($this -> gurl,strpos($this -> gurl,'//')+2),0,strpos(substr($this -> gurl,strpos($this -> gurl,'//')+2),'/'));
             $ch   = curl_init($this -> gurl);
             $headers = array(
                             'Host'       => $server,
                             'User-Agent' => 'Mozilla/5.0 (Windows; U; Windows NT 5.1; zh-CN; rv:1.9.2.2) Gecko/20100316 Firefox/3.6.2'
             );
             curl_setopt($ch, CURLOPT_URL,$this -> gurl);
             curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
             curl_setopt($ch, CURLOPT_HEADER, true);
             curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 120);
             curl_setopt($ch, CURLOPT_POST, 0);
             curl_setopt($ch, CURLOPT_REFERER, 'http://mail.sina.com.cn/');
             curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
             //��֮ǰ�����cookie��Ϣ��һ���͵���������
             curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_file);
             curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie_file);
             curl_setopt($ch, CURLOPT_NOBODY, 0);
             // echo file_get_contents($cookie_file);
             $result = curl_exec($ch);
             curl_close($ch);

             $url = "http://".$server."/classic/address.php?ts=".mktime()."358_1";
             $ch   = curl_init($url);
             $headers = array(
                             'Host'       =>$server,
                             'User-Agent' => 'Mozilla/5.0 (Windows; U; Windows NT 5.1; zh-CN; rv:1.9.2.2) Gecko/20100316 Firefox/3.6.2'
             );
            curl_setopt($ch, CURLOPT_URL,$url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HEADER, true);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 120);
            curl_setopt($ch, CURLOPT_POST, 0);
            curl_setopt($ch, CURLOPT_REFERER, $this -> gurl);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
             //��֮ǰ�����cookie��Ϣ��һ���͵���������
            curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_file);
            curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie_file);
            curl_setopt($ch, CURLOPT_NOBODY, 0);
            $result = curl_exec($ch);
            curl_close($ch);

            $url="http://".$server."/classic/addr_member.php";
            $ch   = curl_init($url);
            $headers = array(
                     'Host'         => $server,
                     'User-Agent'   => 'Mozilla/5.0 (Windows; U; Windows NT 5.1; zh-CN; rv:1.9.2.2) Gecko/20100316 Firefox/3.6.2',
                     'Content-Type' => 'application/x-www-form-urlencoded; charset=UTF-8'
            );
            $str ="act=list&sort_item=letter&sort_type=desc";
            curl_setopt($ch, CURLOPT_URL,$url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 120);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $str);
            curl_setopt($ch, CURLOPT_REFERER, $this -> gurl);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            //��֮ǰ�����cookie��Ϣ��һ���͵���������
            curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_file);
            curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie_file);

            $result = curl_exec($ch);
            curl_close($ch);

            $result_mid[] = json_decode($result);

            $array = $result_mid[0]->data->contact;
            $return = array();

            for ($i=0;$i<count($array);$i++) {
                $return[$array[$i]->name]=$array[$i]->email;
            }

            return $return;
    }

    function object_array($array)
    {
       if(is_object($array))
       {
        $array = (array)$array;
       }
       if(is_array($array))
       {
        foreach($array as $key=>$value)
        {
         $array[$key] = object_array($value);
        }
       }
       return $array;
    }

}

?>