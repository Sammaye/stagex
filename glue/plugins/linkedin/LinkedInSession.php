<?php
include_once 'linkedin.php';

class LinkedInSession extends LinkedIn{

	/**
	 * You should assign all of the vars in the $this context of this class before you call this. The ones you need values for are:
	 *
	 * @param oauth_callback - The callback URL for the login
	 * @param consumer_key
	 * @param consumer_secret
	 */
	function init() {
		$this->consumer = new OAuthConsumer($this->consumer_key, $this->consumer_secret, $this->oauth_callback);
		$this->signature_method = new OAuthSignatureMethod_HMAC_SHA1();
		$this->request_token_path = $this->secure_base_url . "/uas/oauth/requestToken";
		$this->access_token_path = $this->secure_base_url . "/uas/oauth/accessToken";
		$this->authorize_path = $this->secure_base_url . "/uas/oauth/authorize";
	}

	/**
	 * Always run this, if no token found it will not connect, if token out of date will not connect correctly.
	 */
	function connect($props = array()){
		# Now we retrieve a request token. It will be set as $linkedin->request_token
		if($props['oauth_callback']){
			$this->oauth_callback = $props['oauth_callback'];
		}

		if($props['access_token']){
			$this->setAccessToken($props['access_token']['oauth_token'], $props['access_token']['oauth_token_secret']);
		}
	}

	/**
	 * Is User Authed?
	 */
	function isAuthed(){
		$this->user = $this->getCurrentUser();
		if(!$this->user){
			return false;
		}else{
			return true;
		}
	}

	/**
	 * Run this before you Auth a user, on the page before maybe so it gets everything ready
	 */
	function preAuth(){
		$token = $this->getRequestToken();
		setcookie("_ca_lnkd", serialize(array(
			"oauth_token"=>$token['oauth_token'],
			"oauth_token_secret"=>$token['oauth_token_secret']
		)), 0, "/");
	}

	/**
	 * Redirect from the Linkedin oauth link to a page using this function and then save the return to a DB or something
	 */
	function authorize(){

		$cookie_token = unserialize($_COOKIE['_ca_lnkd']);

		if (isset($_REQUEST['oauth_token']) && $cookie_token['oauth_token'] !== $_REQUEST['oauth_token']) {
			setcookie("_ca_lnkd", "", time()-3600, "/");
			return;
		}

		$this->setRequestToken($cookie_token['oauth_token'], $cookie_token['oauth_token_secret']);
		$token = $this->getAccessToken($_GET["oauth_verifier"]); // set the verifier so we can activate the $linkedin object

		setcookie("_ca_lnkd", "", time()-3600, "/");

		// Store this return in your data and send it in, in this format into the connect() function to connect to LinkedIn next time
		return array(
			"oauth_token"=>$token['oauth_token'],
			"oauth_token_secret"=>$token['oauth_token_secret']
		);
	}

	function getCurrentUser(){
		$u = simplexml_load_string($this->getProfile("~:(id,first-name,last-name,headline,picture-url)"));

		if($u->{'error-code'}){
			return false;
		}else{
			return $u;
		}
	}
}