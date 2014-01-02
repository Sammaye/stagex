<?php

namespace glue;

use Glue;
use \glue\Component;
use \glue\Model;

class Validation extends Component
{
	public static function validate($rules)
	{
		$model = new Model;
		$model->setRules($rules);
		$model->validate(false);
		return $model->getValid();
	}
}