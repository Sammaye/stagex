<?php

namespace app\widgets\reCaptcha;

use glue;

Glue::import('@app/widgets/reCaptcha/recaptchalib.php',true);

class recaptcha extends \glue\Widget{

	public $private_key;
	public $public_key;
	public $errors = array();

	function render(){
		echo recaptcha_get_html($this->public_key, $this->errors);
	}
}