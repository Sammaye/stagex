<?php
/** Incase this is being used somewhere where it hasn't been included in the controller */
Glue::import('application/widgets/reCaptcha/recaptchalib.php');

class loginForm extends GModel{

	protected $email;
	protected $password;
	protected $hash;
	protected $remember;

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
					$this->addError("captcha", $resp->error);
					return false;
				}
			}else{
				$this->addError("captcha", "You must enter the Re-Captcha you see below correctly. This is because you have logged in 3 times unsuccessfully.");
				return false;
			}
		}

		if(count($this->getErrors()) <= 0){
			if(glue::session()->login($this->email, $this->password)){
				return true;
			}else{
				switch(glue::session()->response()){
					case "BANNED":
						$this->addError('You have been banned from this site.');
						break;
					case "DELETED":
						$this->addError("Your account has been deleted. This process cannot be undone and may take upto 24 hours.");
						break;
					case "WRONG_CREDS":
						glue::session()->log($this->email, false);
						$this->addError("The username and/or password could not be be found. Please try again. If you encounter further errors please try to recover your password.");
						break;
					case "NOT_FOUND":
						$this->addError("The username and/or password could not be be found. Please try again. If you encounter further errors please try to recover your password.");
						break;
				}
				return false;
			}
		}
	}
}