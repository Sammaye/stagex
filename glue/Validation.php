<?php

namespace glue;

use Glue;
use \glue\Component;
use \glue\Model;

class Validation extends Component
{
	public static function validate($attributes, $rules)
	{
		$model = new Model;
		$model->setAttributes($attributes);
		$model->setRules($rules);
		$model->validate(false);
		return $model->getValid();
	}
}