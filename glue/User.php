<?php

namespace glue;

use glue,
	\glue\util\Crypt;

class User extends \glue\db\Document{

	public $username;

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
	 * Decides whether the user is allowed to login via extended cookies
	 * @var boolean
	 */
	private $allowCookieLogins = true;

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

	private $response;

	function response(){
		if($this->response){
			return $this->response;
		}else{
			return null;
		}
	}

	function init(){
		if(php_sapi_name() != 'cli'){

			if($this->domain)
				ini_set("session.cookie_domain", $this->domain);

			glue::session()->open();

			// Process the user login
			if(!isset($_SESSION['logged']))
				$this->session_defaults();

			// Are they logged in?
			if($_SESSION['logged'] && isset($_COOKIE[$this->tempCookie])) {

				/** Check session as normal */
				$this->_checkSession();

			}elseif(isset($_COOKIE[$this->permCookie])){

				$this->session_defaults();
				$this->_checkCookie($this->permCookie);
				$_SESSION['AUTH_TIER2'] = false;

			}else{

				/** Else in any other case default session variables */
				$this->session_defaults();
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
		if($success){
			// If successful I wanna remove the users row so they don't get caught by it again
			glue::db()->session_log->remove(array('email' => $email));
		}else{
			$doc = glue::db()->session_log->findOne(array('email' => $email));
			if($doc){
				if($doc['ts']->sec > time()-(60*5)){ // Last error was less than 5 mins ago update
					glue::db()->session_log->update(array('email' => $email), array('$inc' => array('c' => 1), '$set' => array('ts' => new MongoDate())));
					return;
				}
			}
			glue::db()->session_log->update(array('email' => $email), array('$set' => array('c' => 1, 'ts' => new MongoDate())), array('upsert' => true));
		}
	}

	/**
	 * Set the default session values
	 */
	function session_defaults() {
		$_SESSION['logged'] = false;
		$_SESSION['uid'] = 0;
		$_SESSION['AUTH_TIER2'] = false;
		$_SESSION['email'] = '';
		$_SESSION['server_key'] = '';
	}

	/**
	 * Check the session
	 */
	private function _checkSession() {

		/** Query for the object */
		$r=$this->getCollection()->findOne(array('_id' => new MongoId($_SESSION['uid']), 'email' => $_SESSION['email'], 'deleted' => 0));

		// Set the model attributes
		foreach($r as $k=>$v)
			$this->$k=$v;

		//echo "here"; echo session_id();
		if(isset($this->ins[session_id()])){
			if(($this->ins[session_id()]['ts']->sec + $this->timeout) < time()){
				$this->_checkCookie();
			}else{
				/** VALID */
				$this->_setSession();
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
	private function _setSession($remember = false, $init = false) {

		/** Single sign on active? */
		if((bool)$this->single_sign){
			/** Delete all other sessions */
			$this->ins = array();
		}

		/** Set session */
		$_SESSION['uid'] = $this->_id;
		$_SESSION['email'] = htmlspecialchars($this->email);
		$_SESSION['logged'] = true;
		//var_dump($this->user->ins);
		$this->ins[session_id()] = array(
			"key"=>$_SESSION['server_key'],
			"ip"=>$_SERVER['REMOTE_ADDR'],
			"agent"=>$_SERVER['HTTP_USER_AGENT'],
			"last_request"=>$_SERVER['REQUEST_URI'],
			"last_active"=>new MongoDate(),
		);

		// Lets delete old sessions (anything older than 2 weeks)
		$ins = $this->ins;
		$new_ins = array();
		foreach($ins as $k => $v){
			if($v['last_active']->sec > strtotime('-2 weeks')){
				$new_ins[$k] = $v;
			}
		}
		$this->ins = $new_ins;


		if($init){
			$this->remember = $remember;
			$this->ins[session_id()]['created'] = new MongoDate();
		}
		//var_dump($this->user->ins[session_id()]);

		$this->save();
		$this->_setCookie($remember, $init);

		/** Now if the user needs notifying via email lets do it */
		if($init){
			if((bool)$this->login_notify){
				$this->loginNotification_email();
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
	public function login($username, $password, $remember = false, $social_login = false){

		$this->logout(false);

		/** Find the user */
		$r = $this->getCollection()->findOne(array('email' => $username));
		if(!empty($r)){
			foreach($r as $k=>$v)
				$this->$k=$v;
		}

		if(!$this->_id){
			$this->logout(false);
			$this->response = 'NOT_FOUND';
			return false;
		}

		if($social_login){
			$valid = true;
		}else{
			$valid = Crypt::verify($password, $user->password);
			//$valid = glue::crypt()->verify($password, $this->user->password);
		}

		/** If found */
		if($valid){

			/** Is deleted? */
			if(!$this->deleted){

				/** Is banned? */
				if(!$this->banned){

					/** Is their IP correct? */

					/** Then log the login */
					$this->log($this->email, true);

					/** Set the session */
					$this->_setSession($remember, true);
					$_SESSION['AUTH_TIER2'] = true;

					/** Success */
					return true;
				}else{
					$this->logout(false);
					$this->response = 'BANNED';
					return false;
				}
			}else{
				$this->logout(false);
				$this->response = 'DELETED';
				return false;
			}
		}else{
			$this->logout(false);
			$this->response = 'WRONG_CREDS';
			return false;
		}
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
			User::model()->update(array('_id' => $this->_id), array('$unset'=>"ins".session_id()), true);
		}

		/** Unset session */
		session_unset();
		$this->session_defaults();
		$this->clean();

		/** SUCCESS */
		return true;
	}

	public function logoutAllDevices($devices = null){
		if(is_array($devices)){

			$i = 0;
			foreach($this->ins as $k=>$v){
				if($devices[$i] == $k){
					unset($this->ins[$k]);
				}
			}

			$this->save();
		}else{
			unset($this->ins);
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
	private function _setCookie($remember, $init = false){

		/** Source the cookie information */
		$cookie_string = Crypt::AES_encrypt256($this->_id);
		$session_cookie = Crypt::AES_encrypt256(session_id());

		$domain = isset($this->domain) ? $this->domain : '';

		/** If remember is set create the permanent cookie */
		if($init){
			if($remember){
				setcookie($this->permCookie, serialize(array($cookie_string, $session_cookie)), time()+60*60*24*365*10, "/", $domain);
				User::model()->update(array('_id' => $this->_id), array('$addToSet'=>array("rem_m"=>session_id())), true);
			}else{
				setcookie($this->permCookie, "", 1);
				User::model()->update(array('_id' => $this->_id), array('$pull'=>array("rem_m"=>session_id())), true);
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
	private function _checkCookie(){

		/** Is the cookie set? */
		if(isset($_COOKIE[$this->tempCookie])){

			/** Source the information */
			list($user_id, $id) = unserialize($_COOKIE[$this->tempCookie]);
			$user_id = Crypt::AES_decrypt256($user_id);
			$s_id = Crypt::AES_decrypt256($id);

			/** Form the criteria for the search */
			$criteria['_id'] = new MongoId($user_id);
			$criteria['ins.'.$s_id] = array("\$exists"=>true);
			$criteria['deleted'] = 0;

			/** Get the matching user and session */
			$r=$this->getCollection()->findOne($criteria);
			foreach($r as $k=>$v)
				$this->$k=$v;

			/** Check variable to ensure the session is valid */
			if($this->ins[session_id()]['ip'] == $_SERVER['REMOTE_ADDR']){

				/** Auth user */
				$_SESSION['AUTH_TIER2'] = true;
				$this->_setSession();

			}else{

				/** Logout */
				$this->logout(false);
			}
		}elseif(isset($_COOKIE[$this->permCookie])){
			list($user_id, $id) = unserialize($_COOKIE[$this->permCookie]);
			$user_id = Crypt::AES_decrypt256($user_id);
			$s_id = Crypt::AES_decrypt256($id);

			$r = $this->getCollection()->findOne(array(
					"_id"=>new MongoId($user_id),
					"rem_m"=>$s_id,
					"deleted" => 0
			));
			foreach($r as $k=>$v)
				$this->$k=$v;

			if($this->_id){
				$this->_setSession();
			}else{
				$this->logout(false);
			}
		}
		return false;
	}
}