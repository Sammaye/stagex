<?php
namespace app\models;

use glue;

glue::import('@app/widgets/reCaptcha/recaptchalib.php');

class recoverForm extends \glue\Model{

	public $email;
	public $hash;

	public function rules(){
		return array(
				array('email', 'required', 'message' => 'You must supply a valid email address'),
				array("hash", "hash", 'message' => 'We could not verify the source of your post. Please use the submit button to submit the form.'),
				array('email', 'email', 'message' => 'You must supply a valid email address'),
				array('email', 'objExist',
					'class'=>'User',
					'field'=>'email', 'message' => 'This email does not exist on our records'
				),
				//array('email', 'validateCaptcha')
		);
	}

	function validateCaptcha(){
		if ($_POST["recaptcha_response_field"]) {
			$resp = recaptcha_check_answer("6LfCNb0SAAAAAK1J8rPQeDaQvz_wpIaowBiYRB2D", $_SERVER["REMOTE_ADDR"], $_POST["recaptcha_challenge_field"], $_POST["recaptcha_response_field"]);

			if(!$resp->is_valid) {
				$this->addError("You entered the reCAPTCHA incorrectly. Please try again.");
				$this->addError("captcha", $resp->error);
				return false;
			}else{
				return true;
			}
		}else{
			$this->addError("You must fill in the reCaptcha");
			return false;
		}
	}
}