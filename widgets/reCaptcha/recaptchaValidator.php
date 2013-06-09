<?php
namespace app\widgets\reCaptcha;

glue::import('@app/widgets/reCaptcha/recaptchalib.php',true);

class recaptchaValidator extends \glue\Validator{

	function validateAttribute($model, $attribute, $value){
		if (isset($_POST["recaptcha_response_field"])) {
			$resp = recaptcha_check_answer("6LfCNb0SAAAAAK1J8rPQeDaQvz_wpIaowBiYRB2D", $_SERVER["REMOTE_ADDR"], $_POST["recaptcha_challenge_field"], $_POST["recaptcha_response_field"]);
			if($resp->is_valid)
				return true;
		}	
	}
}