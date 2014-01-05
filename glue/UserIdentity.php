<?php

namespace glue;

use Glue;
use \glue\db\Document;
use \glue\util\Crypt;
use \app\models\User;

class UserIdentity extends Document
{
	public $user;

	/**
	 * This decides when the user should no longer be "trusted" as being logged in
	 * @var int|float of seconds
	 */
	public $timeout = 5;

	/**
	 * This variable determines if cookies are actually allowed, true for yes and false for no
	 * @var boolean
	 */
	public $allowCookies = true;

	/**
	 * Name of the temp cookie that is used during the $timeout period to judge if the user is logged in.
	 * @var string
	 */
	public $tempCookie = '_temp';

	/**
	 * A more permanent cookie which will not give full access to the site
	 * @var string
	 */
	public $permCookie = '_perm';

	/**
	 * The cookie domain, defaults to the base Url.
	 *
	 * If you intend to use a domain that is not your root domain, i.e. a subdomain you will require cookies
	 *
	 * @var string
	 */
	public $cookieDomain;
	public $cookiePath='/';

	public $logAttempts = true;
	public $logCollectionName = 'session_log';

	/**
	 * Set the default session values
	 */
	public function defaults()
	{
		glue::session()->set(array(
			'_id' => 0,
			'email'=>'',
			'authed' => false
		));
	}	
	
	public function __get($k)
	{
		return $this->user->$k;
	}
	
	public function __set($k, $v)
	{
		return $this->user->$k = $v;
	}
	
	public function __call($name, $parameters)
	{
		if($this->user instanceof User){
			return call_user_func_array(array($this->user, $name), $parameters);
		}
	}

	public function init()
	{
		if(php_sapi_name() != 'cli'){
			if(session_id() === ''){
				if($this->cookie_domain!==null)
					ini_set("session.cookie_domain", $this->domain);
				if($this->cookie_path!==null)
					ini_set("session.cookie_path", $this->cookie_path);
				
				glue::session()->start();
				// Are they logged in?
				// I use the temp cookie here because it is like augmenting the PHPSESS cookie with 
				// something relatively trustable
				if(glue::session()->authed && isset($_COOKIE[$this->tempCookie])){

					$this->validateSession();
				}elseif($this->allowCookies && isset($_COOKIE[$this->permCookie])){
					$this->restoreFromCookie();
				}else
					$this->defaults();

			}
		}
		// else we don't do anything if we are in console but we keep this class so that
		// it can be used to assign users to cronjobs.
	}

	/**
	 * Creates a log table for keeping track of botters trying to spam the login form and
	 * if it catches one it will show a recaptcha
	 *
	 * @param string $email
	 * @param boolean $success
	 */
	function log($email, $success = false)
	{
		if($this->logAttempts){
			if($success){
				// If successful I wanna remove the users row so they don't get caught by it again
				glue::db()->{$this->logCollectionName}->remove(array('email' => $email));
			}else{
				$doc = glue::db()->{$this->logCollectionName}->findOne(array('email' => $email));
				if($doc){
					if($doc['ts']->sec > time()-(60*5)){ // Last error was less than 5 mins ago update
						glue::db()->{$this->logCollectionName}->update(array('email' => $email), array('$inc' => array('c' => 1), '$set' => array('ts' => new \MongoDate())));
						return;
					}
				}
				glue::db()->{$this->logCollectionName}->update(array('email' => $email), array('$set' => array('c' => 1, 'ts' => new \MongoDate())), array('upsert' => true));
			}
		}
	}

	/**
	 * Check the session
	 */
	private function validateSession()
	{
		if(
			$user = User::findOne(array('_id' => glue::session()->_id, 'deleted' => 0)) &&
			isset($user->sessions[session_id()])
		){
			$this->user = $user;
		}else{
			$this->logout(false);
			return false;
		}
		
		if(($this->getUser()->sessions[session_id()]['lastActive']->sec + $this->timeout) < time()){
			$this->restoreFromCookie();
		}else{
			/** VALID */
			$this->setSession();
		}
	}

	/**
	 * Set the session
	 *
	 * @param string $user
	 * @param int $remember
	 */
	private function setSession($remember = false, $init = false)
	{
		$ident = array(
			'id' => session_id(),
			"ip"=>$_SERVER['REMOTE_ADDR'],
			"agent"=>$_SERVER['HTTP_USER_AGENT'],
			"lastRequest"=>$_SERVER['REQUEST_URI'],
			"lastActive"=>new \MongoDate()
		);
		
		/** Set session */
		glue::session()->_id = strval($this->getUser()->_id);
		glue::session()->authed = true;

		/** Single sign on active? */
		if((bool)$this->user->singleSignOn){
			/** Delete all other sessions */
			$this->user->sessions = array();
		}		
		
		// Lets delete old sessions (anything older than 2 weeks)
		foreach($this->user->sessions as $k => $v){
			if($v['lastActive']->sec < strtotime('-2 weeks')){
				unset($this->sessions[$k]);
			}
		}
		
		//var_dump($this->getErrors()); exit();
		$this->setAuthCookie($remember, $init);		

		if($init){
			$ident['remember'] = (int)$remember;
			$ident['created'] = new \MongoDate();
			
			if((bool)$this->user->emailLogins){
				$this->emailLoginNotification();
			}
		}
		$this->user->sessions[session_id()] = $ident;
		$this->user->save();
	}

	/**
	 * Log the user in
	 *
	 * @param string $username
	 * @param string $password
	 * @param int $remember
	 */
	public function login($username, $password, $remember = false, $checkPassword = true)
	{
		$this->logout(false);

		/** Find the user */
		$user = User::findOne(array('email' => $username));

		if(!$user){
			$this->logout(false);
			$this->setError("The username and/or password could not be be found. Please try again. If you encounter further errors please try to recover your password.");
			return false;
		}

		if($checkPassword === false || Crypt::verify($password, $user->password)){
			if($user->deleted){
				$this->setError("Your account has been deleted. This process cannot be undone and may take upto 24 hours.");
			}elseif($user->banned){
				$this->setError('You have been banned from this site.');
			}else{
				/** Then log the login */
				$this->log($user->email, true);
				$this->user = $user;
				$this->setSession($remember, true);
				return true;
			}
		}else{
			// poop
			glue::user()->log($user->email, false);
			$this->setError("The username and/or password could not be be found. Please try again. If you encounter further errors please try to recover your password.");
			return false;
		}
		$this->logout(false);
	}

	/**
	 * Logout a user
	 *
	 * @param bool $remember
	 */
	public function logout($remember = true)
	{
		/** Deletes the temporary cookie */
		$this->setCookie($this->tempCookie, "", 1);

		if(!$remember){
			/** Deletes the permanent cookie */
// 			var_dump($_COOKIE);
// 			var_dump(ini_get("session.cookie_domain"));
// 			var_dump($this->domain);
// 			var_dump($this->permCookie);
			$this->setCookie($this->permCookie, "", 1);
		}

		/** Remove session from table */
		if($this->_id){
			User::updateAll(array('_id' => $this->user->_id), array('$unset'=>array("sessions.".session_id()=>'')));
		}
		
		//echo "in logout";

		/** Unset session */
		if(session_id()!==''){
			session_unset();
			//session_destroy();
			//session_write_close();
			//setcookie(session_name(),'',0,'/');
		}
		$this->defaults();
		//glue::session()->regenerateID(true);
		$this->user = null;

		/** SUCCESS */
		return true;
	}

	public function logoutAllDevices($devices = null)
	{
		if(is_array($devices)){

			$i = 0;
			foreach($this->user->sessions as $k=>$v){
				if($devices[$i] == $k){
					unset($this->user->sessions[$k]);
				}
			}

			$this->save();
		}else{
			unset($this->user->sessions);
			$this->save();
		}
		return true;
	}

	/**
	 * Set the users cookie
	 *
	 * @param int $remember
	 * @param array $ins
	 */
	private function setAuthCookie($remember, $init = false)
	{
		/** Source the cookie information */
		$cookie_string = Crypt::AES_encrypt256($this->user->_id);
		$session_cookie = Crypt::AES_encrypt256(session_id());

		/** If remember is set create the permanent cookie */
		if($init){
			if($remember){
				$this->setCookie($this->permCookie, serialize(array($cookie_string, $session_cookie)), time()+60*60*24*365*10);
			}else{
				$this->setCookie($this->permCookie, "", 1);
			}
		}

		/** Set the temporary cookie anyway */
		$this->setCookie($this->tempCookie, serialize(array($cookie_string, $session_cookie)), 0);
	}

	/**
	 * Checks the users cookie to make sure it is valid.
	 * This will only ever check temporary cookies and not permanent
	 * ones
	 */
	private function restoreFromCookie()
	{
		/** Is the cookie set? */
		if(isset($_COOKIE[$this->tempCookie])){

			/** Source the information */
			list($user_id, $id) = unserialize($_COOKIE[$this->tempCookie]);
			$user_id = Crypt::AES_decrypt256($user_id);
			$s_id = Crypt::AES_decrypt256($id);

			$user = User::findOne(array(
				'_id' => new \MongoId($user_id),
				'deleted' => 0,
				'sessions.id' => $s_id
			));
			
			/** Check variable to ensure the session is valid */
			if($user && $user->sessions[session_id()]['ip'] == $_SERVER['REMOTE_ADDR']){

				/** Auth user */
				$this->user = $user;
				$this->setSession();

			}else{
				/** Logout */
				$this->logout(false);
			}
		}elseif(isset($_COOKIE[$this->permCookie])){
			list($user_id, $id) = unserialize($_COOKIE[$this->permCookie]);
			$user_id = Crypt::AES_decrypt256($user_id);
			$s_id = Crypt::AES_decrypt256($id);

			$user = User::findOne(array(
					"_id"=>new \MongoId($user_id),
					'sessions' => array('$elemMatch' => array('id' => $s_id, 'remember' => 1)),
					"deleted" => 0
			));

			if($user && $user->_id){
				$this->user = $user;
				$this->setSession();
			}else{
				$this->logout(false);
			}
		}
		return false;
	}
	
	function setCookie($name, $content, $expire=0, $path=null, $domain=null, $secure=false, $httponly=false)
	{
		if($path === null && $this->cookiePath !== null){
			$path = $this->cookiePath;
		}
		if($domain === null && $this->cookieDomain !== null){
			$domain = $this->cookieDomain;
		}
		return setCookie($name, $content, $expire, $path, $domain, $secure, $httponly);
	}

	function emailLoginNotification()
	{
		glue::mailer()->mail(
			$this->user->email,
			array('no-reply@stagex.co.uk', 'StageX'),
			'Someone has logged onto your StageX account',
			"user/emailLogin.php",
			array_merge($this->user->sessions[session_id()], array("username"=>$this->user->username))
		);
    	return true;
	}
}