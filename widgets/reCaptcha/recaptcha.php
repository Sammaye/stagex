<?php

Glue::import('application/widgets/reCaptcha/recaptchalib.php');

class recaptcha extends GWidget{

	public $private_key;
	public $public_key;
	public $errors = array();

	function render(){
		return recaptcha_get_html($this->public_key, $this->errors);
	}
}