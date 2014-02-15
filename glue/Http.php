<?php

namespace glue;

use Glue;
use \glue\Component;
use glue\util\Crypt;

class Http extends Component
{
	private $_scriptUrl;
	private $_baseUrl;
	private $_pathInfo;
	private $_csrfToken;
	private $_argv = array();

	function userAgent()
	{
		return isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : null;
	}
	
	function browser($userAgent = null)
	{
		return get_browser($userAgent ? $userAgent : $this->userAgent());
	}	
	
	function referrer()
	{
		return isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : null;
	}	

	function userHost()
	{
		return isset($_SERVER['REMOTE_HOST']) ? $_SERVER['REMOTE_HOST'] : null;
	}

	function userIp()
	{
		if(isset($_SERVER['HTTP_CF_CONNECTING_IP'])){
			return $_SERVER['HTTP_CF_CONNECTING_IP'];  // If from cloudflare lets switch it all
		}else{
			return isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '127.0.0.1';
		}
	}

	function userPort()
	{
		return isset($_SERVER['REMOTE_PORT']) ? $_SERVER['REMOTE_PORT'] : null;
	}
	
	public function name()
	{
		return $_SERVER['SERVER_NAME'];
	}
	
	public function port()
	{
		return $_SERVER['SERVER_PORT'];
	}
	
	public function securePort()
	{
		return $this->isSecureConnection() ? $this->port() : '443';
	}

	public function isSecureConnection()
	{
		return isset($_SERVER['HTTPS']) && ($_SERVER['HTTPS'] === 'on' || $_SERVER['HTTPS'] == 1)
			|| isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https';
	}

	public function scheme()
	{
		return $this->isSecureConnection() ? 'https' : 'http';
	}

	public function host()
	{
		if(php_sapi_name() == 'cli'){
			return isset(glue::$www) ? glue::$www : 'cli';
		}
		if(isset(glue::$www) && glue::$www !== null)
			return glue::$www;
		return isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : $_SERVER['SERVER_NAME'];
	}

	function hostInfo($scheme='')
	{
		return $this->scheme().'://'.$this->host();
	}

	function baseUrl($absolute = false)
	{
		if($this->_baseUrl === null){
			$this->_baseUrl = rtrim(dirname($this->scriptUrl()),'\\/');
		}
		//var_dump($this->hostInfo()); exit();
		return $absolute ? $this->hostInfo().$this->_baseUrl : $this->_baseUrl;
	}

	function requestUri()
	{
		return isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : null;
	}

	public function path()
	{
	    if($this->_pathInfo===null){
	        $pathInfo=$this->requestUri();

	        if(($pos=strpos($pathInfo,'?'))!==false)
	           $pathInfo=substr($pathInfo,0,$pos);

	        $pathInfo=$this->decodePathInfo($pathInfo);
	        $scriptUrl=$this->scriptUrl();
	        $baseUrl=$this->baseUrl();
	        if(strpos($pathInfo,$scriptUrl) === 0){
	            $pathInfo=substr($pathInfo,strlen($scriptUrl));
	        }elseif($baseUrl==='' || strpos($pathInfo,$baseUrl) === 0){
	            $pathInfo=substr($pathInfo,strlen($baseUrl));
	        }elseif(strpos($_SERVER['PHP_SELF'],$scriptUrl) === 0){
	            $pathInfo=substr($_SERVER['PHP_SELF'],strlen($scriptUrl));
	        }
	        $this->_pathInfo=trim($pathInfo,'/');
	    }
	    return $this->_pathInfo;
	}

	public function method()
	{
		return isset($_SERVER['REQUEST_METHOD']) ? strtoupper($_SERVER['REQUEST_METHOD']) : 'GET';
	}
	
	public function isHead()
	{
		return $this->method() === 'HEAD';
	}
	
	public function isOptions()
	{
		return $this->method() === 'OPTIONS';
	}	
	
	public function isGet()
	{
		return $this->method() === 'GET';
	}	

	public function isPost()
	{
		return $this->method() === 'POST';
	}
	
	public function isPut()
	{
		return $this->method() === 'PUT';
	}	

	public function isDelete()
	{
		return $this->method() === 'DELETE';
	}
	
	public function isPatch()
	{
		return $this->method() === 'PATCH';
	}
	
	public function isAjax()
	{
		return (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH']=="XMLHttpRequest");
	}

	public function isFlash()
	{
		return isset($_SERVER['HTTP_USER_AGENT']) &&
			(stripos($_SERVER['HTTP_USER_AGENT'], 'Shockwave') !== false || stripos($_SERVER['HTTP_USER_AGENT'], 'Flash') !== false);
	}	
	
	function scriptUrl()
	{
	    if($this->_scriptUrl===null){
	        $scriptName = basename($_SERVER['SCRIPT_FILENAME']);
	        if(basename($_SERVER['SCRIPT_NAME']) === $scriptName){
	            $this->_scriptUrl = $_SERVER['SCRIPT_NAME'];
	        }elseif(basename($_SERVER['PHP_SELF']) === $scriptName){
	            $this->_scriptUrl = $_SERVER['PHP_SELF'];
	        }elseif(isset($_SERVER['ORIG_SCRIPT_NAME']) && basename($_SERVER['ORIG_SCRIPT_NAME']) === $scriptName){
	            $this->_scriptUrl = $_SERVER['ORIG_SCRIPT_NAME'];
	        }elseif(($pos=strpos($_SERVER['PHP_SELF'],'/'.$scriptName)) !== false){
	            $this->_scriptUrl = substr($_SERVER['SCRIPT_NAME'],0,$pos).'/'.$scriptName;
	        }elseif(isset($_SERVER['DOCUMENT_ROOT']) && strpos($_SERVER['SCRIPT_FILENAME'],$_SERVER['DOCUMENT_ROOT']) === 0){
	            $this->_scriptUrl = str_replace('\\','/',str_replace($_SERVER['DOCUMENT_ROOT'],'',$_SERVER['SCRIPT_FILENAME']));
	        }
	    }
	    return $this->_scriptUrl;
	}

	public function param($attributes, $default_val = null)
	{
		if(!is_array($attributes)){
			$val = $default_val;
			if(isset($_POST[$attributes])){
				$val = $_POST[$attributes];
			}

			if(isset($_GET[$attributes])){
				$val = $_GET[$attributes];
			}
			return $val;
		}else{
			$ar = array();
			$val = null;

			foreach($attributes as $k => $v){
				if(!is_numeric($k)){
					$val = $v;
					$key = $k;
				}else{
					$val = $default_val;
					$key=$v;
				}

				if(isset($_POST[$key])){
					$val = $_POST[$key];
				}

				if(isset($_GET[$key])){
					$val = $_GET[$key];
				}
				$ar[$key] = $val;
			}
			return $ar;
		}
	}

	function url($path = '/', $params = array(), $host = '/', $scheme = 'http')
	{
		if($host === null){
			$host=$this->baseUrl();
		}else if($host == '/'){
			$host = $this->baseUrl(true);
		}else{
			if(strpos($host, 'http')!==0)
				$host = $scheme.'://'.$host;
		}

		$fragment = '';
		if(array_key_exists('#', $params)){
			$fragment = $params['#']; unset($params['#']);
		}

		if(!is_array($path) && $path == 'SELF'){
			$params = $_GET;
			unset($params['url']);

			$path = $this->path();
			return $host.'/'.$path.(count($params) > 0 ? '?'.$this->serialise($params) : '').($fragment ? '#'.$fragment : '');
		}

		if(is_array($path)){
			// Then this is a mege scenario
			if(array_key_exists('#', $path)){
				$fragment = $path['#']; unset($path['#']);
			}

			$getParams = $_GET;
			unset($getParams['url']);
			$params = array_merge($getParams, $path);
			$path = '/'.$this->path();
		}
		return $host.$path.(count($params) > 0 ? '?'.$this->serialise($params) : '').($fragment ? '#'.$fragment : '');
	}
	
	public function serialise($params = null,$array=false)
	{
		$ar = array();
		if(empty($params)){
			$params = $_GET;
			unset($params['url']);
		}
	
		foreach($params as $field => $value){
			if(is_array($value)){
				foreach($value as $f=>$v){
					$ar[] = $field.'[]='.$value;
				}
			}else{
				$ar[] = $field.'='.$value;
			}
		}
		return $array?$ar:implode('&amp;', $ar);
	}	

	function redirect($url, $attr = array(), $host='/')
	{
		header("Location: ".$this->url($url, $attr, $host));
		exit();
	}

	// There is no setHeader function, that smells like code buff
	function headers($headers)
	{
		foreach($headers as $key => $value){
			header($key.': '.$vlaue);
		}
	}

	/**
	 * Only gets the major name (i.e. "IE","Firefox","Chrome") of main stream browsers, all others respond as "u"
	 * This is mostly used for statistical reasons and allows us to log the most important data while showing 
	 * the rest without bogging the user down to their knees in grade d muff.
	 * 
	 * If you want a more accurate version please use `get_browser()`.
	 */
	function getMajorBrowserName()
	{
		$u_agent = $_SERVER['HTTP_USER_AGENT'];
		$u_brows_key = 'u';
		if(preg_match('/MSIE/i',$u_agent)){
			$u_brows_key = "ie";
		}elseif(preg_match('/Firefox/i',$u_agent)){
			$u_brows_key = "ff";
		}elseif(preg_match('/Chrome/i',$u_agent)){
			$u_brows_key = "chrome";
		}elseif(preg_match('/Safari/i',$u_agent)){
			$u_brows_key = "safari";
		}elseif(preg_match('/Opera/i',$u_agent)){
			$u_brows_key = "opera";
		}elseif(preg_match('/Netscape/i',$u_agent)){
			$u_brows_key = "netscape";
		}
		return $u_brows_key;
	}

	public function getNormalisedReferer()
	{
		if(isset($_SERVER['HTTP_REFERER'])){
			$referer = parse_url($_SERVER['HTTP_REFERER'], PHP_URL_HOST);
		}else{
			$referer = 'Direct Entry';
		}

		if($referer === false){
			return null;
		}
		if(strlen($referer) > 0){
			return $referer;
		}
		return null;
	}

	function is_search_bot($bot_string)
	{
		// TODO detect by browser
		if(strlen($bot_string) <= 0){ 
			return true;
		}

		$spam_array = array(
			"^Java",
			"^Jakarta",
			"User-Agent",
			"^Mozilla$",
			"[A-Z][a-z]{3,} [a-z]{4,} [a-z]{4,}"
		);

		while(list($key, $val) = each($spam_array)){
			if(preg_match("/".$val."/", $bot_string) > 0){
				//This is a robot
				return true;
			}
		}

		$bot_array = array(
			"googlebot",
			"Yahoo! Slurp",
		 	"shopwiki",
			"YahooSeeker",
	  		"inktomisearch",
			"Ask Jeeves",
			"MSNbot",
			"BecomeBot",
			"Gigabot",
			"libwww-perl",
			"exabot.com",
			"FAST Enterprise Crawler",
			"Speedy Spider",
	        "Xenu Link Sleuth",
	        "charlotte.betaspider.com",
	        "ConveraCrawler",
		    "YandexBot",
		    "bingbot",
		    "DotBot",
		    "Sogou",
		    "psbot",
		    "MJ12bot",
		    "Ezooms",
	        "Baiduspider",
	        "ia_archiver",
	        "SiteBot",
	        "FatBot",
	        "discobot",
	        "yrspider",
	        "spbot",
	        "LexxeBot",
	        "ichiro",
			"HyperEstraier",
			"Giant",
			"heeii/Nuts Java",
			"VadixBot",
			"Mozilla/5.0 (compatible; Jim +http://­www.­hanzo­archives.­com)",
			"Gungho",
			"Missouri College Browse",
			"panscient.com"
		);

		while(list($key, $val) = each($bot_array)){
			if(stristr($bot_string, $val) != false){
				//This is a robot
				return true;
			}
		}
		return false;
	}

	protected function decodePathInfo($pathInfo)
	{
	    $pathInfo = urldecode($pathInfo);

	    // is it UTF-8?
	    // http://w3.org/International/questions/qa-forms-utf-8.html
	    if(preg_match('%^(?:
	       [\x09\x0A\x0D\x20-\x7E]            # ASCII
	     | [\xC2-\xDF][\x80-\xBF]             # non-overlong 2-byte
	     | \xE0[\xA0-\xBF][\x80-\xBF]         # excluding overlongs
	     | [\xE1-\xEC\xEE\xEF][\x80-\xBF]{2}  # straight 3-byte
	     | \xED[\x80-\x9F][\x80-\xBF]         # excluding surrogates
	     | \xF0[\x90-\xBF][\x80-\xBF]{2}      # planes 1-3
	     | [\xF1-\xF3][\x80-\xBF]{3}          # planes 4-15
	     | \xF4[\x80-\x8F][\x80-\xBF]{2}      # plane 16
	    )*$%xs', $pathInfo)){
	        return $pathInfo;
	    }else{
	        return utf8_encode($pathInfo);
	    }
	}
	
	public function getCsrfToken()
	{
		$csrfToken = glue::session()->csrf;
		if(is_array($csrfToken)){
			$this->_csrfToken = $csrfToken['token'];
		}
		if($this->_csrfToken === null || $csrfToken['expires'] < time()){
			$chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789_-.';
			$mask = substr(str_shuffle(str_repeat($chars, 5)), 0, 14);
			$this->_csrfToken = base64_encode($mask . microtime(true) . Crypt::AES_encrypt256($mask));
			glue::session()->csrf = array('token' => $this->_csrfToken, 'expires' => time()+(60*60)); // Once an hour
		}
		return $this->_csrfToken;
	}
	
	public function validateCsrfToken($token)
	{
		$csrfToken = glue::session()->csrf;
		
		if(!is_array($csrfToken)){
			return false;
		}
		if($csrfToken['expires'] < time()){
			return false;
		}
		if($csrfToken['token'] !== $token){
			return false;
		}
		return true;
	}

	function arg($f)
	{
		$this->parseArgs($_SERVER['argv']);
		return isset($this->_argv[$f]) ? $this->_argv[$f] : null;
	}

    /**
     * PARSE ARGUMENTS
     * @author              Patrick Fisher <patrick@pwfisher.com>
     * @since               August 21, 2009
     * @see                 http://www.php.net/manual/en/features.commandline.php
     *                      #81042 function arguments($argv) by technorati at gmail dot com, 12-Feb-2008
     *                      #78651 function getArgs($args) by B Crawford, 22-Oct-2007
     * @usage               $args = CommandLine::parseArgs($_SERVER['argv']);
     */
	function parseArgs($argv)
	{
		array_shift($argv);
		$out                            = array();

		foreach($argv as $arg){

			// --foo --bar=baz
			if(substr($arg,0,2) == '--'){
				$eqPos                  = strpos($arg,'=');

				// --foo
				if ($eqPos === false){
					$key                = substr($arg,2);
					$value              = isset($out[$key]) ? $out[$key] : true;
					$out[$key]          = $value;
				}
				// --bar=baz
				else {
					$key                = substr($arg,2,$eqPos-2);
					$value              = substr($arg,$eqPos+1);
					$out[$key]          = $value;
				}
			}
			// -k=value -abc
			elseif(substr($arg,0,1) == '-'){

				// -k=value
				if(substr($arg,2,1) == '='){
					$key                = substr($arg,1,1);
					$value              = substr($arg,3);
					$out[$key]          = $value;
				}
				// -abc
				else{
					$chars              = str_split(substr($arg,1));
					foreach ($chars as $char){
						$key            = $char;
						$value          = isset($out[$key]) ? $out[$key] : true;
						$out[$key]      = $value;
					}
				}
			}
			// plain-arg
			else{
				$value                  = $arg;
				$out[]                  = $value;
			}
		}
		$this->_argv = array_merge($this->_argv, $out);
		return $out;
	}
}