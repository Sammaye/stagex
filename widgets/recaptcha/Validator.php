<?php
namespace app\widgets\recaptcha;

use glue;

glue::import('@app/widgets/recaptcha/recaptchalib.php',true);

class Validator extends \glue\Validator
{
	public function validateAttribute($model, $attribute, $value)
	{
		if(isset($_POST["recaptcha_response_field"])){
			$resp = recaptcha_check_answer(
				"6LfCNb0SAAAAAK1J8rPQeDaQvz_wpIaowBiYRB2D",
				$_SERVER["REMOTE_ADDR"],
				$_POST["recaptcha_challenge_field"],
				$_POST["recaptcha_response_field"]
			);
			
			if($resp->is_valid){
				return true;
			}
		}
	}
}