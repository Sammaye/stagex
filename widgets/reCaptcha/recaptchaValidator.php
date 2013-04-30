<?php
class recaptchaValidator extends GValidationComponent{

	public $private_key;

	function validateAttribute($model, $attribute){
		if ($_POST["recaptcha_response_field"]) {
			$resp = recaptcha_check_answer("", $_SERVER["REMOTE_ADDR"], $_POST["recaptcha_challenge_field"], $_POST["recaptcha_response_field"]);

			if(!$resp->is_valid) {
				$this->addErrorMessage("captcha", $resp->error);
				return false;
			}
		}else{
			$model->addErrorMessage("captcha", "You must enter the Re-Captcha you see below correctly. This is because you have logged in 3 times unsuccessfully.");
			return false;
		}
	}

}