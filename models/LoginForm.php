<?php

namespace app\models;

use glue;
use glue\Model;

/** Incase this is being used somewhere where it hasn't been included in the controller */
glue::import('@app/widgets/recaptcha/recaptchalib.php',true);

class LoginForm extends Model
{
	public $captchaError;
	
	public $email;
	public $password;
	public $hash;
	public $remember;

	public function rules()
	{
		return array(
			array('email, password', 'required', 'message' => 'You must enter a username and password to login'),
			array('email', 'email', 'message' => 'You must enter a valid email'),
			array("hash", "hash", 'message' => 'We could not verify the source of your post. Please use the submit button to submit the form.'),
			array('password', '\\app\\widgets\\recaptcha\\Validator', 'on' => 'captcha', 
					'message' => 'You must enter the Re-Captcha you see below correctly. This is because you have logged in 3 times unsuccessfully.'),
			array('remember', 'boolean', 'allowNull' => true)
		);
	}

	public function authenticate($field, $params)
	{
		if($this->getScenario() == "captcha"){
			if(isset($_POST["recaptcha_response_field"])){
				$resp = recaptcha_check_answer(
					"6LfCNb0SAAAAAK1J8rPQeDaQvz_wpIaowBiYRB2D", 
					$_SERVER["REMOTE_ADDR"], 
					$_POST["recaptcha_challenge_field"], 
					$_POST["recaptcha_response_field"]
				);

				if(!$resp->is_valid) {
					$this->setError("captcha", "You must enter the Re-Captcha you see below correctly. This is because you have logged in 3 times unsuccessfully.");
					$this->captchaError=$resp->error;
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