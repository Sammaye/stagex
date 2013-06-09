<?php

namespace glue;

use glue,
	\glue\util\Crypt;

class User extends \glue\db\Document{

	public $username;
	public $password;
	public $email;

	public $sessions=array();

	/**
	 * This decides when the user should no longer be "trusted" as being logged in
	 * @var int|float of seconds
	 */
	private $timeout = 5;

	/**
	 * This variable determines if cookies are actually allowed, true for yes and false for no
	 * @var boolean
	 */
	private $allowCookies = true;

	/**
	 * Name of the temp cookie that is used during the $timeout period to judge if the user is logged in.
	 * @var string
	 */
	private $tempCookie = '_temp';

	/**
	 * A more permanent cookie which will not give full access to the site
	 * @var string
	 */
	private $permCookie = '_perm';

	/**
	 * The cookie domain, defaults to the base Url.
	 *
	 * If you intend to use a domain that is not your root domain, i.e. a subdomain you will require cookies
	 *
	 * @var string
	 */
	private $domain;

	/**
	 * The cookie path
	 * @var string
	 */
	private $path='/';

	private $logAttempts=true;
	private $logCollectionName='session_log';

	function collectionName(){
		return "user";
	}

	public static function model($className = __CLASS__){
		return parent::model($className);
	}

	function init(){
		if(php_sapi_name() != 'cli'){
			if(session_id()===''){
				if($this->domain)
					ini_set("session.cookie_domain", $this->domain);
echo "i here";
				glue::session()->start();

				// Are they logged in?
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
	function log($email, $success = false){
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
	 * Set the default session values
	 */
	function defaults() {
		glue::session()->set(array(
			'id' => 0,
			'email'=>'',
			'authed' => false
		));
	}

	/**
	 * Check the session
	 */
	private function validateSession() {

		/** Query for the object */
		$user=$this->getCollection()->findOne(array('_id' => new \MongoId(glue::session()->id),'deleted' => 0));
		var_dump($user); exit();
		if(!$user){
			$this->logout(false);
			return false;
		}
		
		// Set the model attributes
		$this->clean();
		foreach($user as $k=>$v)
			$this->$k=$v;

		//echo "here"; echo session_id();
		if(isset($this->sessions[session_id()])){
			if(($this->sessions[session_id()]['last_active']->sec + $this->timeout) < time()){
				$this->restoreFromCookie();
			}else{
				/** VALID */
				$this->setSession();
			}
		}else{
			/** Not VALID */
			$this->logout(false);
		}
	}

	/**
	 * Set the session
	 *
	 * @param string $user
	 * @param int $remember
	 */
	private function setSession($remember = false, $init = false) {

		/** Single sign on active? */
		if((bool)$this->singleSignOn){
			/** Delete all other sessions */
			$this->sessions = array();
		}
		$this->setScenario('update');
		$this->setIsNewRecord(false);
		
		/** Set session */
		glue::session()->id=$this->_id;
		glue::session()->authed=true;

		//var_dump($this->user->ins);
		$this->sessions[session_id()] = array(
			'id' => session_id(),
			"ip"=>$_SERVER['REMOTE_ADDR'],
			"agent"=>$_SERVER['HTTP_USER_AGENT'],
			"last_request"=>$_SERVER['REQUEST_URI'],
			"last_active"=>new \MongoDate()
		);

		// Lets delete old sessions (anything older than 2 weeks)
		foreach($this->sessions as $k => $v){
			if($v['last_active']->sec < strtotime('-2 weeks'))
				unset($this->sessions[$k]);
		}

		if($init){
			$this->sessions[session_id()]['remember'] = (int)$remember;
			$this->sessions[session_id()]['created'] = new \MongoDate();
		}
		//var_dump($this->sessions[session_id()]);
		$this->save();
		$this->setCookie($remember, $init);

		/** Now if the user needs notifying via email lets do it */
		if($init){
			if((bool)$this->emailLogins){
				$this->emailLoginNotification();
			}
		}

		// refresh the doc now that I have had some fun with it
		$this->refresh();
	}

	/**
	 * Log the user in
	 *
	 * @param string $username
	 * @param string $password
	 * @param int $remember
	 */
	public function login($username, $password, $remember = false, $checkPassword = true){

		$this->logout(false);

		/** Find the user */
		$r=$this->getCollection()->findOne(array('email' => $username));

		if(!$r){
			$this->logout(false);
			$this->setError("The username and/or password could not be be found. Please try again. If you encounter further errors please try to recover your password.");
			return false;
		}

		$this->clean();
		foreach($r as $k=>$v)
			$this->$k=$v;

		if($checkPassword===false||Crypt::verify($password, $this->password)){
			if($this->deleted){
				$this->setError("Your account has been deleted. This process cannot be undone and may take upto 24 hours.");
			}elseif($this->banned){
				$this->setError('You have been banned from this site.');
			}else{
				/** Then log the login */
				$this->log($this->email, true);
				$this->setSession($remember, true);
				return true;
			}
		}else{
			// poop
			glue::user()->log($this->email, false);
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
	public function logout($remember = true){

		/** Deletes the temporary cookie */
		setcookie($this->tempCookie, "", 1);

		if(!$remember){
			/** Deletes the permanent cookie */
			setcookie($this->permCookie, "", 1);
		}

		/** Remove session from table */
		if($this->_id){
			glue\User::model()->updateAll(array('_id' => $this->_id), array('$unset'=>array("sessions.".session_id()=>'')));
		}

		/** Unset session */
		if(session_id()!==''){
			echo "calling this";
			//session_unset();
			//session_destroy();
			//session_write_close();
			//setcookie(session_name(),'',0,'/');
		}
		//$this->defaults();
		//glue::session()->regenerateID(true);
		$this->clean();

		/** SUCCESS */
		return true;
	}

	public function logoutAllDevices($devices = null){
		if(is_array($devices)){

			$i = 0;
			foreach($this->sessions as $k=>$v){
				if($devices[$i] == $k){
					unset($this->sessions[$k]);
				}
			}

			$this->save();
		}else{
			unset($this->sessions);
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
	private function setCookie($remember, $init = false){

		/** Source the cookie information */
		$cookie_string = Crypt::AES_encrypt256($this->_id);
		$session_cookie = Crypt::AES_encrypt256(session_id());

		$domain = isset($this->domain) ? $this->domain : '';

		/** If remember is set create the permanent cookie */
		if($init){
			if($remember){
				setcookie($this->permCookie, serialize(array($cookie_string, $session_cookie)), time()+60*60*24*365*10, "/", $domain);
			}else{
				setcookie($this->permCookie, "", 1);
			}
		}

		/** Set the temporary cookie anyway */
		setcookie($this->tempCookie, serialize(array($cookie_string, $session_cookie)), 0, "/", $domain);

	}

	/**
	 * Checks the users cookie to make sure it is valid.
	 * This will only ever check temporary cookies and not permanent
	 * ones
	 */
	private function restoreFromCookie(){

		/** Is the cookie set? */
		if(isset($_COOKIE[$this->tempCookie])){

			/** Source the information */
			list($user_id, $id) = unserialize($_COOKIE[$this->tempCookie]);
			$user_id = Crypt::AES_decrypt256($user_id);
			$s_id = Crypt::AES_decrypt256($id);

			/** Form the criteria for the search */
			$criteria['_id'] = new \MongoId($user_id);
			$criteria['sessions.id'] = $s_id;
			$criteria['deleted'] = 0;

			/** Get the matching user and session */
			$r=$this->getCollection()->findOne($criteria);
			if($r!==null){
				foreach($r as $k=>$v)
					$this->$k=$v;
			}

			/** Check variable to ensure the session is valid */
			if($this->sessions[session_id()]['ip'] == $_SERVER['REMOTE_ADDR']){

				/** Auth user */
				glue::session()->tier2_logged=true;
				$this->setSession();

			}else{
				/** Logout */
				$this->logout(false);
			}
		}elseif(isset($_COOKIE[$this->permCookie])){
			list($user_id, $id) = unserialize($_COOKIE[$this->permCookie]);
			$user_id = Crypt::AES_decrypt256($user_id);
			$s_id = Crypt::AES_decrypt256($id);

			$r = $this->getCollection()->findOne(array(
					"_id"=>new \MongoId($user_id),
					'sessions' => array('$elemMatch' => array('id' => $s_id, 'remember' => 1)),
					"deleted" => 0
			));
			if($r!==null){
				foreach($r as $k=>$v)
					$this->$k=$v;
			}

			if($this->_id){
				$this->setSession();
			}else{
				$this->logout(false);
			}
		}
		return false;
	}

	function emailLoginNotification(){
		glue::mailer()->mail($this->email, array('no-reply@stagex.co.uk', 'StageX'), 'Someone has logged onto your StageX account',	"user/emailLogin.php",
			array_merge($this->sessions[session_id()], array("username"=>$this->username)));
    	return true;
	}
}