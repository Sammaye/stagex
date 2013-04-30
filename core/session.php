<?php
/**
 * Default Session Class
 *
 * This is the default session clas of the framework.
 *
 * @author Sam Millman
 */
class session extends GApplicationComponent{

	public $timeout = 5;
	public $allowCookies = true;
	public $store = array();

	public $temp_cookie = '_temp';
	public $perm_cookie = '_perm';

	public $cookieDomain;

	public $user;

	private $response;

	function init(){
		$this->user = new User;
	}

	/**
	 * Constructor
	 */
	function start() {

		$domain = isset($this->cookieDomain) ? $this->cookieDomain : null;
		if($domain)
			ini_set("session.cookie_domain", $domain);

		glue::import($this->store['path']); // Import the session store
		new $this->store['class'];

		session_start(); // Start the damn session

		if(!isset($_SESSION['logged']))
			$this->session_defaults();

		// Are they logged in?
		if($_SESSION['logged'] && isset($_COOKIE[$this->temp_cookie])) {

			/** Check session as normal */
			@$this->_checkSession();

		}elseif(isset($_COOKIE[$this->perm_cookie])){

			$this->session_defaults();
			$this->_checkCookie($this->perm_cookie);
			$_SESSION['AUTH_TIER2'] = false;

		}else{

			/** Else in any other case default session variables */
			$this->user = new User();
			@$this->session_defaults();
		}
	}

	function response(){
		if($this->response){
			return $this->response;
		}else{
			return null;
		}
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
		$this->user = User::model()->findOne(array('_id' => new MongoId($_SESSION['uid']), 'email' => $_SESSION['email'], 'deleted' => 0));

		//echo "here"; echo session_id();
		if(isset($this->user->ins[session_id()])){
			if(($this->user->ins[session_id()]['ts']->sec + $this->timeout) < time()){
				@$this->_checkCookie();
			}else{
				/** VALID */
				@$this->_setSession();
			}
		}else{
			/** Not VALID */
			@$this->logout(false);
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
		if((bool)$this->user->single_sign){
			/** Delete all other sessions */
			$this->user->ins = array();
		}

		/** Set session */
		$_SESSION['uid'] = $this->user->_id;
		$_SESSION['email'] = htmlspecialchars($this->user->email);
		$_SESSION['logged'] = true;
//var_dump($this->user->ins);
		$this->user->ins[session_id()] = array(
			"key"=>$_SESSION['server_key'],
			"ip"=>$_SERVER['REMOTE_ADDR'],
			"agent"=>$_SERVER['HTTP_USER_AGENT'],
			"last_request"=>$_SERVER['REQUEST_URI'],
			"last_active"=>new MongoDate(),
		);

		// Lets delete old sessions (anything older than 2 weeks)
		$ins = $this->user->ins;
		$new_ins = array();
		foreach($ins as $k => $v){
			if($v['last_active']->sec > strtotime('-2 weeks')){
				$new_ins[$k] = $v;
			}
		}
		$this->user->ins = $new_ins;


		if($init){
			$this->user->remember = $remember;
			$this->user->ins[session_id()]['created'] = new MongoDate();
		}
		//var_dump($this->user->ins[session_id()]);

		$this->user->save();
		@$this->_setCookie($remember, $init);

		/** Now if the user needs notifying via email lets do it */
		if($init){
			if((bool)$this->user->login_notify){
				$this->user->loginNotification_email();
			}
		}

		$this->user = User::model()->findOne(array("_id"=>$this->user->_id));
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
		$this->user = User::model()->findOne(array('email' => $username));

		if(!$this->user){
			$this->logout(false);
			$this->response = 'NOT_FOUND';
			return false;
		}

		if($social_login){
			$valid = true;
		}else{
			$valid = GCrypt::verify($password, $this->user->password);
			//$valid = glue::crypt()->verify($password, $this->user->password);
		}

		/** If found */
		if($this->user->_id && $valid){

			/** Is deleted? */
			if(!$this->user->deleted){

				/** Is banned? */
				if(!$this->user->banned){

					/** Is their IP correct? */

						/** Then log the login */
						$this->log($this->user->email, true);

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
		setcookie($this->temp_cookie, "", 1);

		if(!$remember){
			/** Deletes the permanent cookie */
			setcookie($this->perm_cookie, "", 1);
		}

		/** Remove session from table */
		if($this->user){
			User::model()->update(array('_id' => $this->user->_id), array('$unset'=>"ins".session_id()), true);
		}

		/** Unset session */
		session_unset();
		$this->session_defaults();
		$this->user = new User();

		/** SUCCESS */
		return true;
	}

	public function logoutAllDevices($devices = null){
		if(is_array($devices)){

			$i = 0;
			foreach($this->user->ins as $k=>$v){
				if($devices[$i] == $k){
					unset($this->user->ins[$k]);
				}
			}

			$this->user->save();
		}else{
			unset($this->user->ins);
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
		$cookie_string = GCrypt::AES_encrypt256($this->user->_id);
		$session_cookie = GCrypt::AES_encrypt256(session_id());

		$domain = isset($this->cookieDomain) ? $this->cookieDomain : '';

		/** If remember is set create the permanent cookie */
		if($init){
			if($remember){
				setcookie($this->perm_cookie, serialize(array($cookie_string, $session_cookie)), time()+60*60*24*365*10, "/", $domain);
				User::model()->update(array('_id' => $this->user->_id), array('$addToSet'=>array("rem_m"=>session_id())), true);
			}else{
				setcookie($this->perm_cookie, "", 1);
				User::model()->update(array('_id' => $this->user->_id), array('$pull'=>array("rem_m"=>session_id())), true);
			}
		}

		/** Set the temporary cookie anyway */
		setcookie($this->temp_cookie, serialize(array($cookie_string, $session_cookie)), 0, "/", $domain);

	}

	/**
	 * Checks the users cookie to make sure it is valid.
	 * This will only ever check temporary cookies and not permanent
	 * ones
	 */
	private function _checkCookie(){

		/** Is the cookie set? */
		if(isset($_COOKIE[$this->temp_cookie])){

			/** Source the information */
			list($user_id, $id) = unserialize($_COOKIE[$this->temp_cookie]);
			$user_id = GCrypt::AES_decrypt256($user_id);
			$s_id = GCrypt::AES_decrypt256($id);

			/** Form the criteria for the search */
			$criteria['_id'] = new MongoId($user_id);
			$criteria['ins.'.$s_id] = array("\$exists"=>true);
			$criteria['deleted'] = 0;

			/** Get the matching user and session */
			$this->user = User::model()->findOne($criteria);

			/** Check variable to ensure the session is valid */
			if($this->user->ins[session_id()]['ip'] == $_SERVER['REMOTE_ADDR']){

				/** Auth user */
				$_SESSION['AUTH_TIER2'] = true;
				@$this->_setSession();

			}else{

				/** Logout */
				$this->logout(false);
			}
		}elseif(isset($_COOKIE[$this->perm_cookie])){
			list($user_id, $id) = unserialize($_COOKIE[$this->perm_cookie]);
			$user_id = GCrypt::AES_decrypt256($user_id);
			$s_id = GCrypt::AES_decrypt256($id);

			$this->user = User::model()->findOne(array(
				"_id"=>new MongoId($user_id),
				"rem_m"=>$s_id,
				"deleted" => 0
			));

			if($this->user){
				@$this->_setSession();
			}else{
				$this->logout(false);
			}
		}
		return false;
	}
}
