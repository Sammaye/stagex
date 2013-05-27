<?php

namespace glue;

use glue;

class Http{

	private $script_url;
	private $baseUrl;
	private $_pathInfo;
	private $_params;
	private $argv = array();

	public $csrf_token_name = 'GCSRF_TOKEN';
	public $enableCsrfValidation = true;
	public $csrf_error = false;

	function userAgent(){
		return isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : null;
	}

	function userHost(){
		return isset($_SERVER['REMOTE_HOST']) ? $_SERVER['REMOTE_HOST'] : null;
	}

	function userIp(){
		if(isset($_SERVER['HTTP_CF_CONNECTING_IP'])){
			return $_SERVER['HTTP_CF_CONNECTING_IP'];  // If from cloudflare lets switch it all
		}else{
			return isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '127.0.0.1';
		}
	}

	function userPort(){
		return isset($_SERVER['REMOTE_PORT']) ? $_SERVER['REMOTE_PORT'] : null;
	}

	function get_browser($user_agent = null){
		return get_browser($user_agent ? $user_agent : $this->userAgent());
	}

	function isSecureConnection(){
		return $_SERVER['SERVER_PORT'] == '443';
	}

	function scheme(){
		return isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == '443' ? 'https' : 'http';
	}

	function host(){
		if(php_sapi_name() == 'cli'){
			return isset(glue::$www) ? glue::$www : 'cli';
		}
		if(isset(glue::$www)&&glue::$www!==null)
			return glue::$www;
		return isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : $_SERVER['SERVER_NAME'];
	}

	function hostInfo($scheme=''){
		return $this->scheme().'://'.$this->host();
	}

	function baseUrl($absolute = false){
		if($this->baseUrl === null){
			$this->baseUrl = rtrim(dirname($this->scriptUrl()),'\\/');
		}
		//var_dump($this->hostInfo()); exit();
		return $absolute ? $this->hostInfo().$this->baseUrl : $this->baseUrl;
	}

	function referrer(){
		return isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : null;
	}

	function requestUri(){
		return isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : null;
	}

	public function path()
	{
	    if($this->_pathInfo===null)
	    {
	        $pathInfo=$this->requestUri();

	        if(($pos=strpos($pathInfo,'?'))!==false)
	           $pathInfo=substr($pathInfo,0,$pos);

	        $pathInfo=$this->decodePathInfo($pathInfo);
	        $scriptUrl=$this->scriptUrl();
	        $baseUrl=$this->baseUrl();
	        if(strpos($pathInfo,$scriptUrl)===0){
	            $pathInfo=substr($pathInfo,strlen($scriptUrl));
	        }else if($baseUrl==='' || strpos($pathInfo,$baseUrl)===0)
	            $pathInfo=substr($pathInfo,strlen($baseUrl));
	        else if(strpos($_SERVER['PHP_SELF'],$scriptUrl)===0)
	            $pathInfo=substr($_SERVER['PHP_SELF'],strlen($scriptUrl));

	        $this->_pathInfo=trim($pathInfo,'/');
	    }
	    return $this->_pathInfo;
	}

	function isAjax(){
		return (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH']=="XMLHttpRequest");
	}

	function requestType(){
		if($this->isGet()){
			return "GET";
		}elseif($this->isPost()){
			return "POST";
		}
		return null;
	}

	function isPost(){
		return $_SERVER['REQUEST_METHOD'] === 'POST';
	}

	function isGet(){
		return $_SERVER['REQUEST_METHOD'] === 'GET';
	}

	function scriptUrl(){
	    if($this->script_url===null)
	    {
	        $scriptName = basename($_SERVER['SCRIPT_FILENAME']);
	        if(basename($_SERVER['SCRIPT_NAME']) === $scriptName)
	            $this->script_url = $_SERVER['SCRIPT_NAME'];
	        else if(basename($_SERVER['PHP_SELF']) === $scriptName)
	            $this->script_url = $_SERVER['PHP_SELF'];
	        else if(isset($_SERVER['ORIG_SCRIPT_NAME']) && basename($_SERVER['ORIG_SCRIPT_NAME']) === $scriptName)
	            $this->script_url = $_SERVER['ORIG_SCRIPT_NAME'];
	        else if(($pos=strpos($_SERVER['PHP_SELF'],'/'.$scriptName)) !== false)
	            $this->script_url = substr($_SERVER['SCRIPT_NAME'],0,$pos).'/'.$scriptName;
	        else if(isset($_SERVER['DOCUMENT_ROOT']) && strpos($_SERVER['SCRIPT_FILENAME'],$_SERVER['DOCUMENT_ROOT']) === 0)
	            $this->script_url = str_replace('\\','/',str_replace($_SERVER['DOCUMENT_ROOT'],'',$_SERVER['SCRIPT_FILENAME']));
	    }
	    return $this->script_url;
	}

	public function param($attributes, $default_val = null){
		if(!is_array($attributes)){
			$val = $default_val;
			if(isset($_POST[$attributes]))
			$val = $_POST[$attributes];

			if(isset($_GET[$attributes]))
			$val = $_GET[$attributes];

			return $val;
		}else{
			$ar = array();
			$val = null;

			foreach($attributes as $k => $v){
				if(!is_numeric($k)){
					$val = $v;
				}else{
					$val = $default_val;
				}

				if(isset($_POST[$k]))
				$val = $_POST[$k];

				if(isset($_GET[$k]))
				$val = $_GET[$k];

				$ar[$k] = $val;
			}
			return $ar;
		}
	}

	public function getParam($params = null){
		$ar = array();
		if(empty($params)){
			$params = $_GET;
			unset($_GET['url']);
		}

		foreach($params as $field => $value){
			$ar[] = $field.'='.$value;
		}
		return implode('&amp;', $ar);
	}

	public static function _get($attributes, $default_val = null){
		if(is_array($attributes)){
			$get = array();
			foreach($attributes as $k => $v){
				if(!is_numeric($k)){
					$get[$k] = isset($_GET[$k]) ? $_GET[$k] : $v;
				}else{
					$get[$k] = isset($_GET[$k]) ? $_GET[$k] : null;
				}
			}
			return $get;
		}else{
			return isset($_GET[$attributes]) ? $_GET[$attributes] : $default_val;
		}
	}

	public static function _post($attributes, $default_val = null){
		if(is_array($attributes)){
			$get = array();
			foreach($attributes as $k => $v){
				if(!is_numeric($k)){
					$get[$k] = isset($_POST[$k]) ? $_POST[$k] : $v;
				}else{
					$get[$k] = isset($_POST[$k]) ? $_POST[$k] : null;
				}
			}
			return $get;
		}else{
			return isset($_POST[$attributes]) ? $_POST[$attributes] : $default_val;
		}
	}

	function createUrl($path = '/', $params = array(), $host = '/', $scheme = 'http'){

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
			return $host.'/'.$path.(count($params) > 0 ? '?'.$this->getParams($params) : '').($fragment ? '#'.$fragment : '');
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
		return $host.$path.(count($params) > 0 ? '?'.$this->getParams($params) : '').($fragment ? '#'.$fragment : '');
	}

	public function getUrl($returnObj = false){
		if($returnObj){
			return array();
		}else{
			return $this->createUrl('SELF');
		}
	}
	
	public function getParams($params = null){
		$ar = array();
		if(empty($params)){
			$params = $_GET;
			unset($_GET['url']);
		}
	
		foreach($params as $field => $value){
			$ar[] = $field.'='.$value;
		}
		return implode('&amp;', $ar);
	}	

	function redirect($url, $attr = array(), $host='/'){
		header("Location: ".$this->createUrl($url, $attr, $host));
		exit();
	}

	// There is no setHeader function, that smells like code buff
	function headers($headers){
		foreach($headers as $key => $value){
			header($key.': '.$vlaue);
		}
	}

	/**
	 * Only gets the major versions of browsers, all others respond as u
	 */
	function get_major_ua_browser(){
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

	public function getNormalisedReferer(){
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
		}else{
			return null;
		}
	}

	function is_search_bot($bot_string){
		// TODO detect by browser
		if(strlen($bot_string) <= 0) return true;

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

	function setCsrfToken(){
		$_SESSION[$this->csrf_token_name] = md5(glue\util\Crypt::generate_new_pass());
	}

	function getCsrfToken(){
		if(!isset($_SESSION[$this->csrf_token_name])) $this->setCsrfToken();
		return $_SESSION[$this->csrf_token_name];
	}

	function validateCsrfToken($value = null){
		if($this->isPost() && $this->enableCsrfValidation){
			$session_token = isset($_SESSION[$this->csrf_token_name]) ? $_SESSION[$this->csrf_token_name] : '';
			$POST_token = isset($_POST[$this->csrf_token_name]) ? $_POST[$this->csrf_token_name] : $value;

			$valid = $session_token===$POST_token;
			$this->csrf_error = $valid ? false : true;
			return $valid;
		}
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
	    )*$%xs', $pathInfo))
	    {
	        return $pathInfo;
	    }
	    else
	    {
	        return utf8_encode($pathInfo);
	    }
	}

	function getArg($f){
		return isset($this->argv[$f]) ? $this->argv[$f] : null;
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
	function parseArgs($argv){

		array_shift($argv);
		$out                            = array();

		foreach ($argv as $arg){

			// --foo --bar=baz
			if (substr($arg,0,2) == '--'){
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
			else if (substr($arg,0,1) == '-'){

				// -k=value
				if (substr($arg,2,1) == '='){
					$key                = substr($arg,1,1);
					$value              = substr($arg,3);
					$out[$key]          = $value;
				}
				// -abc
				else {
					$chars              = str_split(substr($arg,1));
					foreach ($chars as $char){
						$key            = $char;
						$value          = isset($out[$key]) ? $out[$key] : true;
						$out[$key]      = $value;
					}
				}
			}
			// plain-arg
			else {
				$value                  = $arg;
				$out[]                  = $value;
			}
		}
		$this->argv = array_merge($this->argv, $out);
		return $out;
	}
}