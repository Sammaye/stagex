<?php

namespace app\widgets;

use glue;

Glue::import('@app/widgets/reCaptcha/recaptchalib.php');

class recaptcha extends \glue\Widget{

	public $private_key;
	public $public_key;
	public $errors = array();

	function render(){
		return recaptcha_get_html($this->public_key, $this->errors);
	}
}