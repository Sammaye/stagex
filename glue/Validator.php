<?php

namespace glue;

/**
 * Extend this class to add your own validators
 */
class Validator{
	function attributes($a){
		if(is_array($a)){
			foreach($a as $k => $v){
				$this->$k = $v;
			}
		}
	}

	function validateAttribute($model, $attribute, $value){}

	public function isEmpty($value, $trim  = false){
		return $value===null || $value===array() || $value==='' || $trim && is_scalar($value) && trim($value)==='';
	}
}