<?php

namespace app\models;

use glue;

/** Incase this is being used somewhere where it hasn't been included in the controller */
glue::import('@app/widgets/reCaptcha/recaptchalib.php');

class loginForm extends \glue\Model{

	public $email;
	public $password;
	public $hash;
	public $remember;

	public function rules(){
		return array(
			array('email, password', 'required', 'message' => 'You must enter a username and password to login'),
			array('email', 'email', 'message' => 'You must enter a valid email'),
			array("hash", "hash", 'message' => 'We could not verify the source of your post. Please use the submit button to submit the form.'),
			//array('recaptcha', 'application/widgets/reCaptcha/recaptchaValidator.php', 'private_key' => '6LfCNb0SAAAAAK1J8rPQeDaQvz_wpIaowBiYRB2D'),
			array('password', 'authenticate'),
			array('remember', 'boolean', 'allowNull' => true)
		);
	}

	function authenticate($field, $params){
		if($this->getScenario() == "captcha"){
			if ($_POST["recaptcha_response_field"]) {
				$resp = recaptcha_check_answer("6LfCNb0SAAAAAK1J8rPQeDaQvz_wpIaowBiYRB2D", $_SERVER["REMOTE_ADDR"], $_POST["recaptcha_challenge_field"], $_POST["recaptcha_response_field"]);

				if(!$resp->is_valid) {
					$this->setError("captcha", $resp->error);
					return false;
				}
			}else{
				$this->setError("captcha", "You must enter the Re-Captcha you see below correctly. This is because you have logged in 3 times unsuccessfully.");
				return false;
			}
		}
		return true;
	}
}