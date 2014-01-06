<?php

namespace glue;

use glue\Component;

/**
 * Extend this class to add your own validators
 */
class Validator extends Component
{
	public $owner;
	
	public function validateAttribute($model, $attribute, $value)
	{
	}

	public function isEmpty($value, $trim  = false)
	{
		return $value===null || $value===array() || $value==='' || $trim && is_scalar($value) && trim($value)==='';
	}
}