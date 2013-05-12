<?php
/**
 * Default Session Class
 *
 * This is the default session class of the framework.
 */

namespace glue;

use \glue\util\Crypt,
	\glue\Exception;

class Session extends \glue\Component{

	/**
	 * This decides when the user should no longer be "trusted" as being logged in
	 * @var int|float of seconds
	 */
	public $timeout = 5;

	/**
	 * This decides the lifetime (in seconds) of the session
	 *
	 * @access private
	 * @var int
	 */
	public $life_time='+2 weeks';

	/**
	 * This variable determines if cookies are actually allowed, true for yes and false for no
	 * @var boolean
	 */
	public $allowCookies = true;

	/**
	 * Decides whether the user is allowed to login via extended cookies
	 * @var boolean
	 */
	public $allowCookieLogins = true;

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
	public $domain;

	/**
	 * The cookie path
	 * @var string
	 */
	public $path='/';

	/**
	 * This stores the found session collection so that we don't
	 * waste resources by constantly going back for it
	 *
	 * @access private
	 * @var sessions
	 */
	private $_session = array();

	private $response;

	function init(){
		$this->open();
	}

	function response(){
		if($this->response){
			return $this->response;
		}else{
			return null;
		}
	}

	/**
	 * Constructor
	 */
	function open() {

		// Ensure index on Session ID
		// Why am I dong this here??
		Glue::db()->sessions->ensureIndex(array('session_id' => 1), array("unique" => true));

		// Register this object as the session handler
		session_set_save_handler(
			array( $this, "openSession" ),
			array( $this, "closeSession" ),
			array( $this, "readSession" ),
			array( $this, "writeSession"),
			array( $this, "destroySession"),
			array( $this, "gcSession" )
		);

		if($this->domain)
			ini_set("session.cookie_domain", $this->domain);
		session_start(); // Start the damn session

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
	function openSession( $save_path, $session_name ) {

		global $sess_save_path;

		$sess_save_path = $save_path;

		// Don't need to do anything. Just return TRUE.
		return true;

	}

	/**
	 * This function closes the session (end of session)
	 */
	function closeSession() {

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
	function readSession( $id ) {

		// Set empty result
		$data = '';

		// Fetch session data from the selected database
		$time = time();

		$this->_sessions = Glue::db()->sessions->findOne(array("session_id"=>$id));

		if (!empty($this->_sessions)) {
			$data = $this->_sessions['session_data'];
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
	function writeSession( $id, $data ) {

		//Write details to session table
		$time = strtotime('+2 weeks');

		// If the user is logged in record their uid
		$uid = $_SESSION['logged'] ? $_SESSION['uid'] : 0;

		$fields = array(
			"session_id"=>$id,
			"user_id"=>$uid,
			"session_data"=>$data,
			"expires"=>$time,
			"active"=>1
		);

		$fg = Glue::db()->sessions->update(array("session_id"=>$id), array('$set'=>$fields), array("fsync"=>1, "upsert"=>true));

		// DONE
		return true;
	}

	/**
	 * This function is called when a user calls session_destroy(). It
	 * kills the session and removes it.
	 *
	 * @param string $id
	 */
	function destroySession( $id ) {

		// Remove from Db
		Glue::db()->sessions->remove(array("session_id" => $id), true);

		return true;
	}

	/**
	 * This function GCs (Garbage Collection) all old and out of date sessions
	 * which still exist in the Db. It will remove by comparing the current to the time of
	 * expiring on the session record.
	 *
	 * @todo Make a cronjob to delete all sessions after about a day old and are still inactive
	 */
	function gcSession() {
		glue::db()->sessions->remove(array('expires' => array('$lt' => strtotime('+2 weeks'))));
		return true;
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
		glue::user()->populate(User::model()->findOne(array('_id' => new MongoId($_SESSION['uid']), 'email' => $_SESSION['email'], 'deleted' => 0)));
		

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
