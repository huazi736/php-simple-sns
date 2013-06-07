<?php

class Mailmsn{

    private $startcomm = 0;
    private $username = '';
    private $password = '';
    private $commend = '';
    private $domain = '';
    private $socket = '';
    private $challenge = '';
    private $status = array();
    private $data = array();

    function set_account($username, $password) {

        $this->username = $username;

        $this->password = $password;
    }

    function getData() {

        $buffer = "";

        while (!feof($this->socket)) {

            $buffer .= fread($this->socket, 1024);

            if (preg_match("/\r/", $buffer)) {

                break;
            }
        }

        $this->checkData($buffer);
    }

    function getData2() {

        $buffer = "";

        while (!feof($this->socket)) {

            $buffer .= fread($this->socket, 1024);

            if (preg_match("/\r\n\r\n/", $buffer)) {

                break;
            }
        }

        $this->checkData($buffer);
    }

    function checkData($buffer) {

        if (preg_match("/lc\=(.+?)/Ui", $buffer, $matches)) {

            $this->challenge = "lc=" . $matches[1];
        }



        if (preg_match("/(XFR 3 NS )([0-9\.\:]+?) (.*) ([0-9\.\:]+?)/is", $buffer, $matches)) {

            $split = explode(":", $matches[2]);

            $this->startcomm = 1;

            $this->msn_connect($split[0], $split[1]);
        }



        if (preg_match("/tpf\=([a-zA-Z0-9]+?)/Ui", $buffer, $matches)) {

            $this->nexus_connect($matches[1]);
        }



        $split = explode("\n", $buffer);

        for ($i = 0; $i < count($split); $i++) {

            $detail = explode(" ", $split[$i]);

            if ($detail[0] == "LST") {

                if (isset($detail[2]))
                    $this->data[] = array($detail[1], urldecode($detail[2]));
            }
        }

        $this->status = array(200, $this->data);

        //echo $buffer;
    }

    function msn_connect($server, $port) {

        if ($this->socket) {

            fclose($this->socket);
        }

        $this->socket = @fsockopen($server, $port, $errno, $errstr, 20);

        if (!$this->socket) {

            $this->status = array(500, 'MSN验证服务器无法连接');

            return false;
        } else {

            $this->startcomm++;

            $this->send_command("VER " . $this->startcomm . " MSNP8 CVR0", 1);

            $this->send_command("CVR " . $this->startcomm . " 0x0409 win 4.10 i386 MSNMSGR 6.2 MSMSGS " . $this->username, 1);

            $this->send_command("USR " . $this->startcomm . " TWN I " . $this->username, 1);
        }
    }

    function send_command($command) {

        $this->commend = $command;

        $this->startcomm++;

        fwrite($this->socket, $command . "\r\n");

        $this->getData();
    }

    function nexus_connect($tpf) {

        $arr[] = "GET /rdr/pprdr.asp HTTP/1.0\r\n\r\n";

        $curl = curl_init();

        curl_setopt($curl, CURLOPT_URL, "https://nexus.passport.com:443/rdr/pprdr.asp");

        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

        curl_setopt($curl, CURLOPT_VERBOSE, 0);

        curl_setopt($curl, CURLOPT_HEADER, 1);

        curl_setopt($curl, CURLOPT_HTTPHEADER, $arr);

        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);

        $data = curl_exec($curl);

        curl_close($curl);

        preg_match("/DALogin=(.+?),/", $data, $matches);

        if (!isset($matches[1]))
            return false;

        $split = explode("/", $matches[1]);

        $headers[0] = "GET /$split[1] HTTP/1.1\r\n";

        $headers[1] = "Authorization: Passport1.4 OrgVerb=GET,OrgURL=http%3A%2F%2Fmessenger%2Emsn%2Ecom,sign-in=" . $this->username . ",pwd=" . $this->password . ", " . trim($this->challenge) . "\r\n";

        $curl = curl_init();

        curl_setopt($curl, CURLOPT_URL, "https://" . $split[0] . ":443/" . $split[1]);

        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

        curl_setopt($curl, CURLOPT_VERBOSE, 0);

        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);

        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

        curl_setopt($curl, CURLOPT_HEADER, 1);

        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);

        $data = curl_exec($curl);

        curl_close($curl);

        preg_match("/t=(.+?)'/", $data, $matches);

        if (!isset($matches[1])) {

            $this->status = array(404, '你输入的MSN帐号或者密码错误');

            return false;
        }

        $this->send_command("USR " . $this->startcomm . " TWN S t=" . trim($matches[1]) . "", 2);

        $this->send_command("CHG " . $this->startcomm . " HDN", 2);

        $this->send_command("SYN " . $this->startcomm . " 0", 2);

        $this->getData2();

        $this->send_command("SYN " . $this->startcomm . " 1 46 2", 2);

        $this->getData2();

        $this->send_command("CHG " . $this->startcomm . " BSY");

        $this->getData();
    }

    public function getStatus() {

        return $this->status;
    }

}
?>