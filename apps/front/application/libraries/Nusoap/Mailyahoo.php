<?php
class Mailyahoo{
  /*-------------------------------------------------------
  Public Variables
  -------------------------------------------------------*/
  /**
  * Service name (1. addressbook, 2. messenger, 3. newmail, 4. calendar)
  * @public
  * @var string
  */
  var $service            = "";
  
  /**
  * Yahoo! Account Username
  * @public
  * @var string
  */
  var $login              = "";

  /**
  * Yahoo! Account Password
  * @public
  * @var string
  */  
  var $password           = "";

  /**
  * Abosolute path to save the cookie
  * Default value is DOCUMENT_ROOT
  * @public
  * @var string
  */
  var $cookieJarPath      = "";
  
  /**
  * Abosulte path to the CA Bundle file
  * SSL Certificate required to verify CA cert
  * Usually required when script ran on Localhost
  * Remote servers may not require 
  * Default value is false
  * @public
  * @var string
  */
  var $caBundleFile       = "";

  /**
  * Specifies if Proxy server required as Gateaway
  * Default value is false
  * @public
  * @var boolean
  */  
  var $isUsingProxy       = false;
                          
  /**                     
  * Proxy host name       
  * @public               
  * @var string          
  */                      
  var $proxyHost          = "";
                          
  /**                     
  * Proxy port number     
  * @public               
  * @var int             
  */                      
  var $proxyPort          = 0;

  /*-------------------------------------------------------
  Private Variables
  -------------------------------------------------------*/
  /**
  * URL to Authenticate user on Yahoo!
  * @private
  * @var string
  */
  var $authUrl            = "http://login.yahoo.com/config/login?";

  /**
  * URL for the desired Service
  * @private
  * @var string
  */                          
  var $serviceUrl         = "";

  /**
  * URL to be used by cURL
  * @private
  * @var string
  */                          
  var $url                = "";

  /**
  * User agent (used to trick Yahoo!)
  * @private
  * @var string
  */
  var $userAgent          = "YahooSeeker-Testing/v3.9 (compatible; Mozilla 4.0; MSIE 5.5; http://search.yahoo.com/)";

  /**
  * Referer URL (used to trick Yahoo!)
  * @private
  * @var string
  */
  var $referer            = "http://my.yahoo.com";

  /**
  * Specifies whether output includes the header
  * @private
  * @var int
  */
  var $showHeader         = 0;

  /**
  * Specifies if cURL should follow the redirected URL
  * @private
  * @var int
  */
  var $follow             = 0;

  /**
  * Specifies number of post fields to pass
  * @private
  * @var int
  */                          
  var $numPostField       = 0;

  /**
  * Specify fields to send via POST method as key=value
  * @private
  * @var string
  */
  var $postFields         = "";

  /**
  * File where output is temporarily saved during authentication
  * @private
  * @var string
  */
  var $authOutputFile     = "";

  /**
  * Variable used by Yahoo to verify the request is valid
  * @private
  * @var string
  */
  var $crumb              = "";
  
  /**
  * File where service output is temporarily saved 
  * @private
  * @var string
  */
  var $outputFile         = "";

  /**
  * File where Cookie is temporarily saved 
  * @private
  * @var string
  */                          
  var $cookieFileJar      = "";

  /**
  * Cookie File that is read by service process
  * This carries same value as $cookieFileJar
  * @private
  * @var string
  */
  var $cookieFile         = "";

  /**
  * Specifies if Cookie is to be in header
  * @private
  * @var int
  */
  var $cookie             = 0;

  /**
  * Proxy address as proxy.host:port
  * @private
  * @var string
  */
  var $proxy              = "";

  /**
  * Error Information set by either cURL or Internal process
  * @private
  * @var string
  */
  var $errorInfo          = "";

  /**
  * Returns true if there is new mail otherwise false
  * @private
  * @var boolean
  */  
  var $newMailStatus      = false;
  
  /**
  * Sets Service URL
  * @return void
  */
  function setServiceUrl() 
  {
    if (empty($this->service))
    {
      $this->setError("provide_service");
      return false;
    }
    
    // Sets the URL depending on the choosen service
    switch ($this->service)
    {
  	  //updated by ovidiuw3b
	  case 'addressbook' : $this->serviceUrl   = "http://address.mail.yahoo.com/?_src=&VPC=print"; break;
	  //end update
	  
	  //updated by ovidiuw3b
	  case 'messenger'   : $this->serviceUrl   = "http://address.mail.yahoo.com/?_src=&VPC=print"; break;
	  //end update
	  
      case 'newmail'     : $this->serviceUrl   = "http://mail.yahoo.com/"; break;
      
      case 'calendar'    : $this->serviceUrl   = "http://calendar.yahoo.com/"; break;
    }
  }
  
  /**
  * Sets the Cookie Jar File where Cookie is temporarily saved
  * @return void
  */
  function setCookieJar()
  {
    // Sets the encrypted cookie filename using Yahoo! account username
    $this->cookieFilename = MD5($this->login);
    
    // Sets the Cookie Jar filename with an absolute path
    $this->cookieFileJar  = (!empty($this->cookieJarPath)) ? $this->cookieJarPath . "/" . $this->cookieFilename : $_SERVER['DOCUMENT_ROOT'] . "/" . $this->cookieFilename;
    
    fopen($this->cookieFileJar, "w");
  }

  /**
  * Initializes cURL session
  * @return void
  */  
  function initCurl()
  {
    $this->curlSession    = curl_init();
  }

  /**
  * Sets cURL options
  * @return boolean
  */  
  function setCurlOption() 
  {
    // Sets the User Agent  
    curl_setopt($this->curlSession, CURLOPT_USERAGENT, $this->userAgent);
    
    // Sets the HTTP Referer
    curl_setopt($this->curlSession, CURLOPT_REFERER, $this->referer);
    
    // Sets the URL that PHP will fetch using cURL
    curl_setopt($this->curlSession, CURLOPT_URL, $this->url);
    
    // Sets the number of fields to be passed via HTTP POST
    curl_setopt($this->curlSession, CURLOPT_POST, $this->numPostField);
    
    // Sets the fields to be sent via HTTP POST as key=value
    curl_setopt($this->curlSession, CURLOPT_POSTFIELDS, $this->postFields);
    
    // Sets the filename where cookie information will be saved
    curl_setopt($this->curlSession, CURLOPT_COOKIEJAR, $this->cookieFileJar);
    
    // Sets the filename where cookie information will be looked up
    curl_setopt($this->curlSession, CURLOPT_COOKIEFILE, $this->cookieFile);
    
    // Sets the option to set Cookie into HTTP header
    curl_setopt($this->curlSession, CURLOPT_COOKIE, $this->cookie);

    // Checks if the user needs proxy (to be set by user)
    if ($this->isUsingProxy) 
    { 
      // Checks if the proxy host and port is specified
      if ((empty($this->proxyHost)) || (empty($this->proxyPort)))
      { 
        $this->setError("proxy_required");
        $this->unlinkFile($this->cookieFileJar);
        return false;
      }
     
      // Sets the proxy address as proxy.host:port
      $this->proxy          = $this->proxyHost . ":" . $this->proxyPort;
    }
        
    // Sets the proxy server as proxy.host:port
    curl_setopt($this->curlSession, CURLOPT_PROXY, $this->proxy);
    
    // Sets the filename where output will be temporarily saved
    curl_setopt($this->curlSession, CURLOPT_RETURNTRANSFER, 1);
    
    curl_setopt($this->curlSession, CURLOPT_FOLLOWLOCATION, $this->follow);
    
    return true;
  }

  /**
  * Executes the Service
  * @param string $login Username of user's Yahoo! Account
  * @param string $password Password of the user's Yahoo! Account
  * @return array|false
  */  
  function execService($login, $password)
  {
    $login      = trim($login);
    $password   = trim($password);
    
    if (empty($login)) 
    {
      $this->setError("provide_login");
      return false;
    }
    
    if (empty($password)) 
    {
      $this->setError("provide_pass");
      return false;
    }
    
    $this->login      = $login;
    $this->password   = $password;
    
    $this->setServiceUrl();
    
    // Instructs to authenticate user on Yahoo!
    $this->auth       = $this->doAuthentication();
    
    if ($this->auth)
    {
      // Instructs to fetch output if Authenticated
      $this->getServiceOutput();
      
      return $this->serviceOutput;
    }
  }

  /**
  * Authenticates user on Yahoo!
  * @return boolean
  */
  function doAuthentication()
  {
    // Instructs to initialize cURL session
    $this->initCurl();
    
    // Sets the URL for authentication purpose
    $this->url              = $this->authUrl;
    
    // Sets the number of fields to send via HTTP POST
    $this->numPostField     = 22;
    
    // Sets the fields to be sent via HTTP POST as key=value
    $this->postFields       = "login=$this->login&passwd=$this->password&.src=&.tries=5&.bypass=&.partner=&.md5=&.hash=&.intl=us&.tries=1&.challenge=ydKtXwwZarNeRMeAufKa56.oJqaO&.u=dmvmk8p231bpr&.yplus=&.emailCode=&pkg=&stepid=&.ev=&hasMsgr=0&.v=0&.chkP=N&.last=&.done=" . $this->serviceUrl;
    
    // Instructs to set Cookie Jar
    $this->setCookieJar();
          
    // Checks if the cURL options are all set properly
    if ($this->setCurlOption())
    {
      // Instructs to execute cURL session
      $this->execCurl();

      // Checks if any cURL error is generated
      if ($this->getCurlError())
      {
        $this->unlinkFile($this->cookieFileJar);
        $this->setError("curl_error");
        return false;
      }

      // Checks if the authentication failed, either invalid login or username is not registered
      if ((preg_match("/invalid/i", $this->outputContent)) || (preg_match("/not yet taken/i", $this->outputContent)))
      {
        // Instructs to close cURL session
        $this->closeCurl();
        
        // Unlinks the cookie file
        $this->unlinkFile($this->cookieFileJar);
        
        $this->setError("invalid_login");
        return false;
      }
      
      $this->closeCurl();
    }
    
    unset($this->outputContent);
    
    return true;
  }

  /**
  * Sets the Service Output
  * @return void
  */  
  function getServiceOutput()
  {  
    // Instructs to process the choosen service
    switch ($this->service)
    {
      case 'addressbook'    : $this->showHeader     = 0;
                              $this->getCrumb();
                              $this->serviceOutput  = $this->processAddressBook(); 
                              break;
  
      case 'messenger'      : $this->showHeader     = 0;
                              $this->getCrumb();      
                              $this->serviceOutput  = $this->processMessengerList(); 
                              break;

      case 'newmail'        : $this->showHeader     = 0;
                              $this->follow         = 1;
                              $this->serviceOutput  = $this->processNewMail();
                              break;

      case 'calendar'       : $this->showHeader     = 0;
                              $this->serviceOutput  = $this->processCalendar();
                              break;
    }
    
    $this->unlinkFile($this->cookieFileJar);
  }

  /**
  * Processes Yahoo! Address Book
  * @return array|false
  */
  function processAddressBook()
  {
    $this->initCurl();
    $this->url              = $this->serviceUrl;
    $this->numPostField     = 1;
    
	//updated by ovidiuw3b
	$this->postFields		= "VPC=print&field[allc]=1&field[catid]=0&field[style]=>detailed&submit[action_display]=Display for Printing";
	//end update
	
    $this->cookieFile       = $this->cookieFileJar;
    $this->outputFile       = "addressBook." . md5($this->login) . ".txt";
    $this->fileHandler      = fopen($this->outputFile, "w");
    
    if ($this->setCurlOption())
    {
      $this->execCurl();
	  $res=$this->outputContent;
      $this->closeCurl();
	  
	  $emailA=array();$bulk=array();$contacts=array();
		
		$res=str_replace(array('  ','	',PHP_EOL,"\n","\r\n"),array('','','','',''),$res);
		preg_match_all("#\<tr class\=\"phead\"\>\<td colspan\=\"2\"\>(.+)\<\/tr\>(.+)\<div class\=\"first\"\>\<\/div\>\<div\>\<\/div\>(.+)\<\/div\>#U",$res,$bulk);
		if (!empty($bulk))
			{
			foreach($bulk[1] as $key=>$bulkName)
				{
				$nameFormated=trim(strip_tags($bulkName));
				if (preg_match('/\&nbsp\;\-\&nbsp\;/',$nameFormated)) 
					{
					$emailA=explode('&nbsp;-&nbsp;',$nameFormated);
					if (!empty($emailA[1])) $contacts[$emailA[1].'@yahoo.com']=array('first_name'=>$emailA[0],'email_1'=>$emailA[1].'@yahoo.com');
					}
				elseif (!empty($bulk[3][$key])) { $email=strip_tags(trim($bulk[3][$key])); $contacts[$email]=array('first_name'=>$nameFormated,'email_1'=>$email); }
				}
			}			
		foreach ($contacts as $email=>$name) if (!$this->isEmail($email)) unset($contacts[$email]);

		return $contacts;
    }    
  }

  /**
  * Locates and sets the value for crumb
  * @return void
  */  
  function getCrumb()
  {
    $this->initCurl();
    $this->url              = $this->serviceUrl . "?1&VPC=import_export&.rand=1238474830";
    
    $this->cookieFile       = $this->cookieFileJar;
    $this->outputFile       = "addressBook." . md5($this->login) . ".txt";
    $this->fileHandler      = fopen($this->outputFile, "w");
    
    if ($this->setCurlOption())
    {
      $this->execCurl();
      fwrite($this->fileHandler, $this->outputContent);      
      unset($this->outputContent);
      $this->closeCurl();
      fclose($this->fileHandler);    
      
      $fileContent          = file_get_contents($this->outputFile);
      $searchStr            = "/\.crumb.*\"/";

      preg_match($searchStr, $fileContent, $matches);

      if (!empty($matches))
      {
        $foundStr           = $matches[0];
        
        $pattern            = array (
                                    '/id/', '/(\.?)crumb(\d?)/', '/value/', '/=/', '/\"/'
                                    );
        
        $replacement        = array (
                                    '', '', '', '', ''
                                    );
                                    
        $this->crumb        = preg_replace($pattern, $replacement, $foundStr);
      }
    
      $this->unlinkFile($this->outputFile);
    }
  }

  /**
  * Processes Yahoo! Messenger Friend List (Grouped)
  * @return array|false
  */  
  function processMessengerList()
  {
    $this->initCurl();
    $this->url              = $this->serviceUrl;
    $this->numPostField     = 1;
    
	//updated by ovidiuw3b
	$this->postFields		= "VPC=print&field[allc]=1&field[catid]=0&field[style]=>detailed&submit[action_display]=Display for Printing";
	//end update
	
    $this->cookieFile       = $this->cookieFileJar;
    $this->outputFile       = "addressBook." . md5($this->login) . ".txt";
    $this->fileHandler      = fopen($this->outputFile, "w");
    
    if ($this->setCurlOption())
    {
      $this->execCurl();
	  $res=$this->outputContent;
      $this->closeCurl();
	  
	  $emailA=array();$bulk=array();$contacts=array();
		
		$res=str_replace(array('  ','	',PHP_EOL,"\n","\r\n"),array('','','','',''),$res);
		preg_match_all("#\<tr class\=\"phead\"\>\<td colspan\=\"2\"\>(.+)\<\/tr\>(.+)\<div class\=\"first\"\>\<\/div\>\<div\>\<\/div\>(.+)\<\/div\>#U",$res,$bulk);
		if (!empty($bulk))
			{
			foreach($bulk[1] as $key=>$bulkName)
				{
				$nameFormated=trim(strip_tags($bulkName));
				if (preg_match('/\&nbsp\;\-\&nbsp\;/',$nameFormated)) 
					{
					$emailA=explode('&nbsp;-&nbsp;',$nameFormated);
					if (!empty($emailA[1])) $contacts[$emailA[1].'@yahoo.com']=array('first_name'=>$emailA[0],'messenger_id'=>$emailA[1]);
					}
				elseif (!empty($bulk[3][$key])) { $email=strip_tags(trim($bulk[3][$key])); $contacts[$email]=array('first_name'=>$nameFormated,'messenger_id'=>$email); }
				}
			}			
		foreach ($contacts as $email=>$name) if (!$this->isEmail($email)) unset($contacts[$email]);

		return $contacts;
    }    
  }  

  /**
  * Processes Yahoo! Mail for Number of New Messages
  * @return array|false
  */  
  function processNewMail()
  {
    $this->initCurl();
    $this->url              = $this->serviceUrl;
    $this->cookieFile       = $this->cookieFileJar;
    $this->outputFile       = "newMailList." . md5($this->login) . ".txt";
    $this->fileHandler      = fopen($this->outputFile, "w");
    
    if ($this->setCurlOption())
    {
      $this->execCurl();
      fwrite($this->fileHandler, $this->outputContent); 
      unset($this->outputContent);
      $this->closeCurl();
      fclose($this->fileHandler);
      
      $fileContent  = file_get_contents($this->outputFile);
      $fileContent  = strip_tags($fileContent);
      
      // Finds out the string You have N unread Message
      $pattern      = "/inbox\s\(\d+\)/i";
      preg_match($pattern, $fileContent, $match);
      
      // Extracts the number of new message(s)
      $numPattern   = "/\d+/";
      preg_match($numPattern, $match[0], $match);
      
      $list['new_mail']   = $match[0];
      
      $this->unlinkFile($this->outputFile);
      
      if ($match[0] > 0)
      {
        $this->setNewMailStatus(true);
      }
      
      return $list;
    }
  }

  /**
  * Processes Yahoo! Calendar
  * @return array|false
  */
  function processCalendar()
  {
    $this->initCurl();
    $this->url              = $this->serviceUrl;
    $this->url             .= "YYY,dbadeb/srt,0/Yahoo.csv?v=12&Yahoo.csv";
    $this->cookieFile       = $this->cookieFileJar;
    $this->outputFile       = "calendar." . md5($this->login) . ".txt";
    $this->fileHandler      = fopen($this->outputFile, "w");
    
    if ($this->setCurlOption())
    {
      $this->execCurl();
      fwrite($this->fileHandler, $this->outputContent);      
      unset($this->outputContent);
      $this->closeCurl();
      fclose($this->fileHandler);
      
      // Sets the service output as a string
      $fileContent          = file_get_contents($this->outputFile);
      
      // Sets patterns and respective replacement strings to convert the output to a proper CSV format
      $pattern              = array (
                                    "/;\"\"\"/",
                                    "/;\"\"/",
                                    "/\"\"/",
                                    "/;\":\"/",
                                    "/;/"
                                    );
      $replacement          = array (
                                    ";\"\"|\"",
                                    ";\":\"",
                                    "\"|\"",
                                    ";\"\"",
                                    ","
                                    );
                                    
      $fileContent          = preg_replace($pattern, $replacement, $fileContent);

      // Sets the formatted output as an array
      $fileContentArr       = explode("|", $fileContent);

      // Sets the calendar column headings
      $clColumnHeadLine     = trim($fileContentArr[0]);
      $clColumnHeadLine     = str_replace("\"", "", $clColumnHeadLine);
      
      // Sets the calendar column headings into an array
      $clColumnHeadArr      = explode(",", $clColumnHeadLine);
      
      // Unsets the heading line from the file content array
      unset($fileContentArr[0]);
      
      foreach ($fileContentArr as $key => $value)
      {
        // Sets the calendar list individually
        $listColumnLine     = trim($value);
        $listColumnLine     = str_replace("\"", "", $listColumnLine);
        
        // Sets the individual list into an array
        $listColumnArr      = explode(",", $listColumnLine);
        
        // Iterates through each item of individual calendar item in the list
        foreach ($listColumnArr as $listColumnKey => $listColumnValue)
        {
          // Sets the column heading as key
          $listKey          = $clColumnHeadArr[$listColumnKey];
          
          // Sets the value for the key respectively
          $list_[$listKey]  = $listColumnValue;
        }
        
        // Sets the calendar list in an array
        $list[]             = $list_;
      }
      
      $this->unlinkFile($this->outputFile);
      
      return $list;      
    }    
  }
  
  /**
  * Sets the new mail status to true or false
  * @return void
  */  
  function setNewMailStatus($status)
  {
    $this->newMailStatus = ($status) ? true : false;
  }
  
  /**
  * Returns the new mail status
  * @return boolean
  */
  function getNewMailStatus()
  {
    return $this->newMailStatus;
  }
  
  /**
  * Executes cURL Session
  * @return void
  */  
  function execCurl()
  {
    $this->outputContent    = curl_exec($this->curlSession);  
  }

  /**
  * Closes cURL session
  * @return void
  */  
  function closeCurl()
  {
    curl_close($this->curlSession); 
    unset($this->curlSession); 
  }

  /**
  * Unlinks any given file
  * @return void
  */  
  function unlinkFile($fileToUnlink)
  {
    if (file_exists($fileToUnlink))
    {
      unlink($fileToUnlink);
    }
  }

  /**
  * Sets any cURL error generated
  * @return boolean
  */  
  function getCurlError()
  {
    $this->curlError    = curl_error($this->curlSession);
    
    return (!empty($this->curlError)) ? true : false;
  }
  
  /**
  * Sets Error Information
  * @return void
  */  
  function setError($error) 
  {
    $msg  = (!empty($error)) ? $this->getErrorInfo($error) : null;
    $this->errorCount++;
    $this->errorInfo = $msg;
  }

  /**
  * Provides the Error message
  * @param string $error Error code for which error message is generated
  * @return string
  */  
  function getErrorInfo($error) 
  {
    switch ($error) 
    {
      case 'provide_service'    : $msg  = "Must specify a Service"; break;
      
      case 'provide_login'      : $msg  = "Must provide Login name"; break;
                                
      case 'provide_pass'       : $msg  = "Must provide Password"; break;
                                
      case 'provide_ca'         : $msg  = "Must provide a SSL Certificate to verfiy CA cert"; break;
                                
      case 'proxy_required'     : $msg  = "Must provide both Proxy host and port"; break;
                                
      case 'invalid_login'      : $msg  = "Login information incorrect"; break;
                                
      case 'curl_error'         : $msg  = $this->curlError; break;
    }
    
    return $msg;
  }

  /*added by ovidiuw3b*/
  function isEmail($email)
   {
	return preg_match("/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,4})$/i", $email);
   }
  /*end code added by ovidiuw3b*/  
  }
?>