<?php

namespace glue;

use Glue;
use \glue\Component;
use \glue\util\Crypt;

class Session extends Component
{
	/**
	 * This decides the lifetime (in seconds) of the session
	 * @var int
	 */
	public $lifeTime = '+2 weeks';
	
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
	
	public $collectionName = 'session';
	
	public $logAttempts = true;
	public $logCollectionName = 'session_log';
	
	public $error;

	/**
	 * Set the default session values
	 */
	public function defaults()
	{
		$this->set(array(
			'_id' => 0,
			'email'=>'',
			'authed' => false
		));
	}	
	
	public function __get($name)
	{
		return $this->get($name);
	}

	public function __set($name, $value)
	{
		return $this->set($name, $value);
	}

	public function init()
	{
		if(php_sapi_name() == 'cli'){
			return; // No session in CLI
		}
		
		if(session_id() !== ''){
			return; // Session already set
		}

		if($this->cookieDomain !== null){
			ini_set("session.cookie_domain", $this->cookieDomain);
		}
		if($this->cookiePath !== null){
			ini_set("session.cookie_path", $this->cookiePath);
		}
		
		// Register this object as the session handler
		session_set_save_handler(
			array( $this, "open" ),
			array( $this, "close" ),
			array( $this, "read" ),
			array( $this, "write"),
			array( $this, "destroy"),
			array( $this, "gc" )
		);
		session_start(); // Start the damn session
// 		var_dump(session_id());
// 		var_dump($_SESSION);
// 		var_dump($this->authed);
// 		var_dump(isset($_COOKIE[$this->tempCookie])); 
		// Are they logged in?
		// I use the temp cookie here because it is like augmenting the PHPSESS cookie with
		// something relatively trustable
		if(glue::session()->authed && isset($_COOKIE[$this->tempCookie])){
			$this->validate();
		}elseif($this->allowCookies && isset($_COOKIE[$this->permCookie])){
			$this->restoreFromCookie();
		}else{
			$this->defaults();
		}
		// else we don't do anything if we are in console but we keep this class so that
		// it can be used to assign users to cronjobs.
	}
	
	public function get($name)
	{
		return isset($_SESSION[$name]) ? $_SESSION[$name] : null;
	}
	
	public function set($name, $value = null)
	{
		if(is_array($name)){
			foreach($name as $k => $v){
				$_SESSION[$k] = $v;
			}
		}else{
			$_SESSION[$name] = $value;
		}
	}
	
	public function getCollection()
	{
		return glue::db()->{$this->collectionName};
	}
	
	public function getLogCollection()
	{
		return glue::db()->{$this->logCollectionName};
	}
	
	public function getError()
	{
		return $this->error;
	}
	
	public function setError($message)
	{
		$this->error = $message;
	}

	/**
	 * Creates a log
	 *
	 * @param string $email
	 * @param boolean $success
	 */
	function log($email, $success = false)
	{
		if(!$this->logAttempts){
			return;
		}
		if($success){
			// If successful I wanna remove the users row so they don't get caught by it again
			$this->getLogCollection()->remove(array('email' => $email));
		}else{
			$response = $this->getLogCollection()->findOne(
				array('email' => $email, 'ts' => new \MongoDate(time()-(60*5))), 
				array('$inc' => array('c' => 1), '$set' => array('ts' => new \MongoDate())),
				array('upsert' => 1)
			);
		}
	}
	
	/**
	 * Check the session
	 */
	private function validate()
	{
		if(
			!($user = Glue::user()->findOne(array('_id' => new \MongoId($this->_id), 'deleted' => 0))) ||
			!isset($user->sessions[session_id()])
		){
			$this->logout(false);
		}elseif(($user->sessions[session_id()]['last_active']->sec + $this->timeout) < time()){
			$this->restoreFromCookie();
		}else{
			/** VALID */
			$this->setSession($user);
		}
	}
	
	/**
	 * Set the session
	 *
	 * @param string $user
	 * @param int $remember
	 */
	private function setSession($user, $remember = false, $init = false)
	{
		glue::setComponents(array('user' => array(
			'__i_' => $user
		)));
		
		$ident = array(
			'id' => session_id(),
			"ip"=>$_SERVER['REMOTE_ADDR'],
			"agent"=>$_SERVER['HTTP_USER_AGENT'],
			"last_request"=>$_SERVER['REQUEST_URI'],
			"last_active"=>new \MongoDate()
		);
	
		/** Set session */
		$this->_id = strval(glue::user()->_id);
		$this->authed = true;
	
		/** Single sign on active? */
		if((bool)glue::user()->singleSignOn){
			/** Delete all other sessions */
			glue::user()->sessions = array();
		}
	
		// Lets delete old sessions (anything older than 2 weeks)
		foreach(glue::user()->sessions as $k => $v){
			if($v['last_active']->sec < strtotime('-2 weeks')){
				unset(glue::user()->sessions[$k]);
			}
		}
	
		//var_dump($this->getErrors()); exit();
		$this->setSessionCookie($remember, $init);
	
		if($init){
			$ident['remember'] = (int)$remember;
			$ident['created'] = new \MongoDate();
				
			if((bool)glue::user()->emailLogins){
				$this->emailLoginNotification();
			}
		}
		glue::user()->sessions[session_id()] = $ident;
		glue::user()->saveAttributes(array('sessions'));
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
		$user = glue::user()->findOne(array('email' => $username));
	
		if(!$user){
			$this->logout(false);
			$this->setError("
				The username and/or password could not be be found. Please try again. 
				If you encounter further errors please try to recover your password.
			");
		}elseif($checkPassword === false || Crypt::verify($password, $user->password)){
			if($user->deleted){
				$this->setError("
					Your account has been deleted. 
					This process cannot be undone and may take upto 24 hours.
				");
			}elseif($user->banned){
				$this->setError('
					You have been banned from this site.
				');
			}else{
				/** Then log the login */
				$this->log($user->email, true);
				$this->setSession($user, $remember, true);
				return true;
			}
		}else{
			$this->log($user->email, false);
			$this->setError("
				The username and/or password could not be be found. 
				Please try again. If you encounter further errors please try to recover your password.
			");
		}
	}
	
	/**
	 * Logout a user
	 *
	 * @param bool $remember
	 */
	public function logout($remember = true)
	{
		$this->setCookie($this->tempCookie, "", 1);
	
		if(!$remember){
			$this->setCookie($this->permCookie, "", 1);
		}
		if($this->_id){
			glue::user()->updateAll(
				array('_id' => $this->_id), 
				array('$unset'=>array("sessions.".session_id()=>''))
			);
		}

		if(session_id() !== ''){
			session_unset();
			//session_destroy();
			//session_write_close();
			//setcookie(session_name(),'',0,'/');
		}
		$this->defaults();
		//glue::session()->regenerateID(true);
		glue::setComponents(array('user' => array()));
	
		/** SUCCESS */
		return true;
	}
	
	public function logoutAllDevices($devices = null)
	{
		if(is_array($devices)){
	
			$i = 0;
			foreach(glue::user()->sessions as $k=>$v){
				if($devices[$i] == $k){
					unset(glue::user()->sessions[$k]);
				}
			}
		}else{
			glue::user()->sessions = array();
		}
		glue::user()->saveAttributes(array('sessions'));
		return true;
	}
	
	/**
	 * Set the users cookie
	 *
	 * @param int $remember
	 * @param array $ins
	 */
	private function setSessionCookie($remember, $init = false)
	{
		/** Source the cookie information */
		$cookie_string = Crypt::AES_encrypt256($this->_id);
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

			$user = glue::user()->findOne(array(
				'_id' => new \MongoId($user_id),
				'deleted' => 0,
				'sessions.'.$s_id.'.id' => $s_id
			));

			/** Check variable to ensure the session is valid */
			if($user && $user->sessions[session_id()]['ip'] == $_SERVER['REMOTE_ADDR']){
				$this->setSession($user);
			}else{
				$this->logout(false);
			}
		}elseif(isset($_COOKIE[$this->permCookie])){
			list($user_id, $id) = unserialize($_COOKIE[$this->permCookie]);
			$user_id = Crypt::AES_decrypt256($user_id);
			$s_id = Crypt::AES_decrypt256($id);
	
			$user = glue::user()->findOne(array(
				"_id"=>new \MongoId($user_id),
				'sessions.'.$s_id.'.id' => $s_id,
				'sessions.'.$s_id.'.remember' => 1,
				"deleted" => 0
			));
	
			if($user){
				$this->setSession($user);
			}else{
				$this->logout(false);
			}
		}
		return false;
	}	

	/**
	 * Open session
	 *
	 * This function opens a session from a save path.
	 * The save path can be changed the method of opening also can
	 * but we do not change that we just do the basics and return
	 *
	 * @param string $save_path
	 * @param string $session_name
	 */
	public function open( $save_path, $session_name )
	{
		global $sess_save_path;
		$sess_save_path = $save_path;

		// Don't need to do anything. Just return TRUE.
		return true;

	}

	/**
	 * This function closes the session (end of session)
	 */
	public function close()
	{
		// Return true to indicate session closed
		return true;

	}

	/**
	 * This is the read function that is called when we open a session.
	 * This function attempts to find a session from the Db. If it cannot then
	 * the session class variable will remain null.
	 *
	 * @param string $id
	 */
	public function read($id)
	{
		// Set empty result
		$data = '';
		if((
			$session = $this->getCollection()->findOne(array("session_id" => $id))
		) !== null){
			$data = isset($session['data']) ? $session['data'] : '';
		}
		return $data;
	}

	/**
	 * This is the write function. It is called when the session closes and
	 * writes all new data to the Db. It will do two actions depending on whether or not
	 * a session already exists. If the session does exist it will just update the session
	 * otherwise it will insert a new session.
	 *
	 * @param string $id
	 * @param mixed $data
	 *
	 * @todo Need to make this function aware of other users since php sessions are not always unique maybe delete all old sessions.
	 */
	public function write($id, $data)
	{
		//Write details to session table
		$time = strtotime($this->lifeTime);

		$response = $this->getCollection()->update(
			array("session_id" => $id), 
			array('$set' => array(
				"session_id" => $id,
				"data" => $data,
				"expires" => $time,
			)), 
			array("upsert" => true)
		);
		
		if($response['err']){
			Glue::error(E_ERROR, $response['err'], __FILE__, __LINE__);
		}
		return true;
	}

	/**
	 * This function is called when a user calls session_destroy(). It
	 * kills the session and removes it.
	 *
	 * @param string $id
	 */
	public function destroy($id)
	{
		// Remove from Db
		$this->getCollection()->remove(array("session_id" => $id));
		return true;
	}

	/**
	 * This function GCs (Garbage Collection) all old and out of date sessions
	 * which still exist in the Db. It will remove by comparing the current to the time of
	 * expiring on the session record.
	 *
	 * @todo Make a cronjob to delete all sessions after about a day old and are still inactive
	 */
	public function gc()
	{
		$this->getCollection()->remove(array('expires' => array('$lt' => strtotime($this->lifeTime))));
		return true;
	}

	public function regenerateID($deleteOldSession = false)
	{
		$oldID = session_id();

		// if no session is started, there is nothing to regenerate
		if(empty($oldID))
			return;

		session_regenerate_id(false);
		$newID = session_id();

		if($this->getCollection()->findOne(array('session_id' => $oldID)) !== null){
			if($deleteOldSession){
				$this->getCollection()->update(
					array('session_id' => $oldID),
					array('$set' => array('session_id' => $newID))
				);
			}else{
				$row['session_id'] = $newID;
				$this->getCollection()->insert($row);
			}
		}else{
			$this->getCollection()->insert(array(
				'id'=>$newID,
				'expire'=>strtotime($this->lifeTime)
			));
		}
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
			glue::user()->email,
			array('no-reply@stagex.co.uk', 'StageX'),
			'Someone has logged onto your StageX account',
			"user/emailLogin.php",
			array_merge(glue::user()->sessions[session_id()], array("username" => glue::user()->username))
		);
		return true;
	}	
}