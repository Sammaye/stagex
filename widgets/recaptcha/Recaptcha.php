<?php

namespace app\widgets\recaptcha;

use glue;
use glue\Widget;

glue::import('@app/widgets/recaptcha/recaptchalib.php', true);

class Recaptcha extends Widget
{
	public $private_key;
	public $public_key;
	public $errors = array();

	public function render()
	{
		echo recaptcha_get_html($this->public_key, $this->errors);
	}
}